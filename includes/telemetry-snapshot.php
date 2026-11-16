<?php

namespace Groundhogg;

use Groundhogg\Classes\Activity;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Mcp\Connection;
use Groundhogg\Mobile\Promo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The weekly usage snapshot an opted-in site sends home, see docs/telemetry-payload.md for every field and why.
 *
 * Counts only: no contact data, no email content, no titles, no custom field names. Every query rides an index or
 * hits a small table, and the activity counts cover the last 7 days so they stay cheap on big sites.
 *
 * Each section is built on its own, so one that fails on an odd site is sent as { "error": "..." } instead of taking the
 * whole snapshot down with it.
 */
class Telemetry_Snapshot {

	/**
	 * How far back the activity counts go
	 */
	const PERIOD = WEEK_IN_SECONDS;

	/**
	 * How far back the email editor counts go, and at most how many emails they look at
	 */
	const EDITOR_PERIOD = 90 * DAY_IN_SECONDS;
	const EDITOR_LIMIT  = 500;

	/**
	 * @var int the start of the activity period
	 */
	protected $since;

	public function __construct() {
		$this->since = time() - self::PERIOD;
	}

	/**
	 * Every section
	 *
	 * @return array
	 */
	public function build() {

		$snapshot = [];

		foreach (
			[
				'system'     => 'system',
				'license'    => 'license',
				'addons'     => 'addons',
				'plugins'    => 'plugins',
				'size'       => 'size',
				'activity'   => 'activity',
				'features'   => 'features',
				'health'     => 'health',
				'onboarding' => 'onboarding',
			] as $key => $method
		) {
			$snapshot[ $key ] = $this->section( [ $this, $method ] );
		}

		/**
		 * Add an extension's own counts to the telemetry snapshot. Put them under the extension's download id, e.g.
		 * $snapshot['extensions'][ 12345 ] = [ 'orders_tracked_7d' => 88 ]. Counts only, never personal data or content.
		 *
		 * @param array $snapshot
		 */
		$snapshot = apply_filters( 'groundhogg/telemetry/snapshot', array_merge( $snapshot, [ 'extensions' => [] ] ) );

		if ( ! is_array( $snapshot ) ) {
			return [];
		}

		$snapshot['extensions'] = self::map( (array) ( $snapshot['extensions'] ?? [] ) );

		return $snapshot;
	}

	/**
	 * A map that stays a JSON object when it's empty, so it has the same shape every week
	 *
	 * @param array $map
	 *
	 * @return array|\stdClass
	 */
	protected static function map( array $map ) {
		return empty( $map ) ? new \stdClass() : $map;
	}

	/**
	 * Run a section, keeping its failure to itself
	 *
	 * @param callable $callback
	 *
	 * @return array
	 */
	protected function section( callable $callback ) {
		try {
			return call_user_func( $callback );
		} catch ( \Throwable $e ) {
			return [ 'error' => get_class( $e ) ];
		}
	}

	/**
	 * Server and software
	 *
	 * @return array
	 */
	public function system() {
		return [
			'php'           => PHP_VERSION,
			'wp'            => get_bloginfo( 'version' ),
			'db'            => $this->db_server(),
			'locale'        => get_locale(),
			'time_zone'     => wp_timezone_string(),
			'multisite'     => is_multisite(),
			'object_cache'  => (bool) wp_using_ext_object_cache(),
			'white_labeled' => (bool) is_white_labeled(),
			'safe_mode'     => get_option( 'gh_safe_mode_enabled' ) == 1,
		];
	}

	/**
	 * The database server, like "mysql 8.0.36" or "mariadb 10.11.6"
	 *
	 * @return string
	 */
	protected function db_server() {
		global $wpdb;

		$info = method_exists( $wpdb, 'db_server_info' ) ? (string) $wpdb->db_server_info() : '';

		// MariaDB reports something like 5.5.5-10.11.6-MariaDB-log
		if ( stripos( $info, 'mariadb' ) !== false && preg_match( '/(\d+\.\d+\.\d+)-mariadb/i', $info, $matches ) ) {
			return 'mariadb ' . $matches[1];
		}

		return 'mysql ' . $wpdb->db_version();
	}

	/**
	 * The plan and its status, never the key itself
	 *
	 * @return array
	 */
	public function license() {

		$key      = License_Manager::get_master_license();
		$licenses = License_Manager::get_licenses();
		$master   = $key && isset( $licenses[ $key ] ) ? $licenses[ $key ] : null;

		return [
			'premium'  => (bool) has_premium_features(),
			'plan'     => $master ? $master->item_name : null,
			'price_id' => $master && $master->price_id !== '' ? $master->price_id : null,
			// a date, or "lifetime"
			'expires'  => $master && $master->expires ? substr( $master->expires, 0, 10 ) : null,
			'key_hash' => $key ? hash( 'sha256', $key ) : null,
			'licenses' => count( $licenses ),
			'expired'  => count( License_Manager::get_expired_licenses() ),
		];
	}

	/**
	 * The site's extensions
	 *
	 * @return array[]
	 */
	public function addons() {

		$official   = Extension_Upgrader::get_extension_ids();
		$accessible = License_Manager::get_accessible_items();

		return array_values( array_map( function ( Extension $extension ) use ( $official, $accessible ) {

			$id = (int) $extension->get_download_id();

			return [
				'id'       => $id,
				'version'  => (string) $extension->get_version(),
				// only official extensions are licensed through groundhogg.io
				'licensed' => in_array( $id, $official ) ? in_array( $id, $accessible ) : null,
			];
		}, Extension::get_extensions() ) );
	}

	/**
	 * Every active plugin's folder name, leaving out this plugin and its extensions, which are sent already
	 *
	 * @return string[]
	 */
	public function plugins() {

		$active = (array) get_option( 'active_plugins', [] );

		if ( is_multisite() ) {
			$active = array_merge( $active, array_keys( (array) get_site_option( 'active_sitewide_plugins', [] ) ) );
		}

		$ours = [ plugin_basename( GROUNDHOGG__FILE__ ) ];

		foreach ( Extension::get_extensions() as $extension ) {
			$ours[] = plugin_basename( $extension->get_plugin_file() );
		}

		$slugs = [];

		foreach ( $active as $basename ) {

			if ( ! is_string( $basename ) || in_array( $basename, $ours, true ) ) {
				continue;
			}

			// a plugin in a folder is known by the folder, a single file plugin like hello.php by its name
			$slugs[] = str_contains( $basename, '/' ) ? strtok( $basename, '/' ) : basename( $basename, '.php' );
		}

		$slugs = array_unique( array_map( 'sanitize_key', $slugs ) );

		sort( $slugs );

		return array_values( array_filter( $slugs ) );
	}

	/**
	 * A count per value of a column
	 *
	 * @param string   $table
	 * @param string   $column
	 * @param callable $where optional, adds conditions to the query's Where
	 *
	 * @return int[] value => count
	 */
	protected function count_by( string $table, string $column, ?callable $where = null ) {

		$query = new Table_Query( $table );
		$query->setSelect( $column, [ 'COUNT(*)', 'total' ] )->setGroupby( $column );

		if ( $where ) {
			call_user_func( $where, $query->where(), $query );
		}

		$counts = [];

		foreach ( $query->get_results() as $row ) {
			$counts[ (string) $row->$column ] = (int) $row->total;
		}

		return $counts;
	}

	/**
	 * A count of rows
	 *
	 * @param string   $table
	 * @param callable $where optional, adds conditions to the query's Where
	 *
	 * @return int
	 */
	protected function count( string $table, ?callable $where = null ) {

		$query = new Table_Query( $table );

		if ( $where ) {
			call_user_func( $where, $query->where(), $query );
		}

		return (int) $query->count();
	}

	/**
	 * How many users have a recent timestamp in a user meta key
	 *
	 * @param string $meta_key
	 * @param int    $since
	 *
	 * @return int
	 */
	protected function users_since( string $meta_key, int $since ) {

		$query = new \WP_User_Query( [
			'number'      => 1,
			'fields'      => 'ID',
			'count_total' => true,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- once a week, in cron
			'meta_query'  => [
				[
					'key'     => $meta_key,
					'value'   => $since,
					'compare' => '>',
					'type'    => 'NUMERIC',
				],
			],
		] );

		return (int) $query->get_total();
	}

	/**
	 * Totals
	 *
	 * @return array
	 */
	public function size() {

		$team = new \WP_User_Query( [
			'number'      => 1,
			'fields'      => 'ID',
			'count_total' => true,
			'capability'  => 'view_contacts',
		] );

		return [
			'contacts'           => $this->count( 'contacts' ),
			'contacts_by_status' => self::map( $this->count_by( 'contacts', 'optin_status' ) ),
			'tags'               => $this->count( 'tags' ),
			'flows'              => self::map( $this->count_by( 'funnels', 'status' ) ),
			'emails'             => $this->count( 'emails', function ( $where ) {
				$where->equals( 'is_template', 0 );
			} ),
			'campaigns'          => $this->count( 'campaigns' ),
			'custom_fields'      => count( Properties::instance()->get_fields() ),
			'saved_searches'     => count( Saved_Searches::instance()->get_all() ),
			'custom_objects'     => $this->count( 'custom_objects' ),
			'team'               => (int) $team->get_total(),
		];
	}

	/**
	 * What happened in the last 7 days
	 *
	 * @return array
	 */
	public function activity() {

		$since = $this->since;

		$sent = $this->count_by( 'events', 'event_type', function ( $where ) use ( $since ) {
			$where->greaterThanEqualTo( 'time', $since )
			      ->equals( 'status', Event::COMPLETE )
			      ->greaterThan( 'email_id', 0 );
		} );

		$events = $this->count_by( 'events', 'status', function ( $where ) use ( $since ) {
			$where->greaterThanEqualTo( 'time', $since );
		} );

		$engagement = $this->count_by( 'activity', 'activity_type', function ( $where ) use ( $since ) {
			$where->greaterThanEqualTo( 'timestamp', $since )
			      ->in( 'activity_type', [ Activity::EMAIL_OPENED, Activity::EMAIL_CLICKED ] );
		} );

		return [
			'contacts_created' => $this->count( 'contacts', function ( $where ) use ( $since ) {
				$where->greaterThanEqualTo( 'date_created', gmdate( 'Y-m-d H:i:s', $since ) );
			} ),
			'emails_sent'      => [
				'flow'         => $sent[ Event::FUNNEL ] ?? 0,
				'broadcast'    => $sent[ Event::BROADCAST ] ?? 0,
				'notification' => $sent[ Event::EMAIL_NOTIFICATION ] ?? 0,
			],
			'events_completed' => $events[ Event::COMPLETE ] ?? 0,
			'events_failed'    => $events[ Event::FAILED ] ?? 0,
			'opens'            => $engagement[ Activity::EMAIL_OPENED ] ?? 0,
			'clicks'           => $engagement[ Activity::EMAIL_CLICKED ] ?? 0,
			'broadcasts'       => $this->count( 'broadcasts', function ( $where ) use ( $since ) {
				$where->equals( 'status', 'sent' )->greaterThanEqualTo( 'send_time', $since );
			} ),
			'abilities'        => self::map( Plugin::instance()->stats_collection->get_ability_counts() ),
			'agents_seen'      => $this->users_since( Connection::SEEN_META, $since ),
			'mobile_users'     => $this->users_since( Promo::USED_META, $since ),
		];
	}

	/**
	 * Which features are in use
	 *
	 * @return array
	 */
	public function features() {

		// steps in flows that are running
		$steps = new Table_Query( 'steps' );
		$steps->setSelect( 'step_type', [ 'COUNT(*)', 'total' ] )->setGroupby( 'step_type' );

		$flows = $steps->addJoin( 'LEFT', 'funnels' );
		$flows->onColumn( 'ID', 'funnel_id' );

		$steps->where()->equals( 'step_status', 'active' )->equals( "$flows->alias.status", 'active' );

		$step_types = [];

		foreach ( $steps->get_results() as $row ) {
			$step_types[ (string) $row->step_type ] = (int) $row->total;
		}

		// the editors recent emails were made with
		$emails = new Table_Query( 'emails' );
		$emails->setOrderby( [ 'last_updated', 'DESC' ] )->setLimit( self::EDITOR_LIMIT )
		       ->where()->greaterThanEqualTo( 'last_updated', gmdate( 'Y-m-d H:i:s', time() - self::EDITOR_PERIOD ) );

		$editors = [ 'blocks' => 0, 'html' => 0, 'legacy_blocks' => 0, 'legacy_plain' => 0 ];

		foreach ( $emails->get_objects() as $email ) {
			$type = $email->get_editor_type();

			$editors[ $type ] = ( $editors[ $type ] ?? 0 ) + 1;
		}

		$preferences = Plugin::instance()->preferences;

		return [
			'step_types'    => self::map( $step_types ),
			'email_editors' => $editors,
			'mcp'           => [
				'adapter_active'   => Connection::is_active(),
				'connector_active' => Connection::is_active( Connection::CONNECTOR_PLUGIN_SLUG ),
				'agents_ever'      => $this->users_since( Connection::SEEN_META, 0 ),
			],
			'settings'      => [
				'gdpr'                => (bool) $preferences->is_gdpr_enabled(),
				'gdpr_strict'         => (bool) $preferences->is_gdpr_strict(),
				'confirmation_strict' => (bool) $preferences->is_confirmation_strict(),
				'email_logging'       => (bool) Email_Logger::is_enabled(),
				'bounce_inbox'        => (bool) Plugin::instance()->bounce_checker->get_bounce_inbox(),
				'imap'                => function_exists( 'imap_open' ),
			],
		];
	}

	/**
	 * Whether automation and email are working
	 *
	 * @return array
	 */
	public function health() {

		$site_health = Plugin::instance()->site_health;

		// status only, the messages are for people
		$checks = [];

		foreach ( $site_health->tests() as $id => [ $label, $callback ] ) {
			try {
				$result         = call_user_func( $callback );
				$checks[ $id ] = is_array( $result ) ? (string) ( $result['status'] ?? '' ) : '';
			} catch ( \Throwable $e ) {
				$checks[ $id ] = 'error';
			}
		}

		// an active plugin's folder, or the path of a file that replaced wp_mail(), which isn't sent
		$wp_mail = $site_health->wp_mail_plugin();

		if ( str_contains( $wp_mail, '/' ) ) {
			$wp_mail = 'custom';
		}

		$overdue = new Table_Query( 'event_queue' );
		$overdue->where()->in( 'status', [ Event::WAITING, Event::IN_PROGRESS ] )->lessThan( 'time', time() - Site_Health::OVERDUE_EVENTS_AFTER );

		$tasks = new Table_Query( 'background_tasks' );
		$tasks->where()->in( 'status', [ 'pending', 'in_progress' ] );

		return [
			'checks'           => self::map( $checks ),
			'email_services'   => [
				'wordpress'     => (string) \Groundhogg_Email_Services::get_wordpress_service(),
				'transactional' => (string) \Groundhogg_Email_Services::get_transactional_service(),
				'marketing'     => (string) \Groundhogg_Email_Services::get_marketing_service(),
			],
			'wp_mail_plugin'   => $wp_mail ?: null,
			'gh_cron'          => [
				'installed' => (bool) gh_cron_installed(),
				'working'   => gh_cron_installed() && is_event_queue_processing(),
			],
			'wp_cron'          => $this->wp_cron_mode(),
			'queue_seconds'    => round( floatval( get_option( 'gh_average_execution_time', 0 ) ), 2 ),
			'overdue_events'   => (int) $overdue->count(),
			'background_tasks' => (int) $tasks->count(),
		];
	}

	/**
	 * How WordPress cron runs: on page loads, from a cron job, or turned off and not running (the same as
	 * groundhogg/get-cron-status)
	 *
	 * @return string page_loads, cron_job or off_and_late
	 */
	protected function wp_cron_mode() {

		$in_config  = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON && ! defined( 'GH_SHOW_DISABLE_WP_CRON_OPTION' );
		$by_setting = is_option_enabled( 'gh_disable_wp_cron' );

		if ( ! $in_config && ! $by_setting ) {
			return 'page_loads';
		}

		$last_ping = absint( get_option( 'wp_cron_last_ping' ) );

		return $last_ping && $last_ping > time() - 15 * MINUTE_IN_SECONDS ? 'cron_job' : 'off_and_late';
	}

	/**
	 * When the site reached each step of getting started, as dates
	 *
	 * @return array
	 */
	public function onboarding() {

		$telemetry = Plugin::instance()->stats_collection;

		$first_email = new Table_Query( 'events' );
		$first_email->setSelect( 'time' )->setOrderby( [ 'time', 'ASC' ] )->setLimit( 1 )
		            ->where()->equals( 'status', Event::COMPLETE )->greaterThan( 'email_id', 0 );

		$first_email = absint( $first_email->get_var() );

		return [
			'installed_at'           => gmdate( 'Y-m-d', $telemetry->get_installed_at() ),
			'installed_at_estimated' => $telemetry->is_installed_at_estimated(),
			'guided_setup_finished'  => (bool) guided_setup_finished(),
			'first_contact_at'       => self::to_date( self::earliest( 'contacts', 'date_created' ) ),
			'first_flow_at'          => self::to_date( self::earliest( 'funnels', 'date_created', function ( $where ) {
				$where->equals( 'status', 'active' );
			} ) ),
			'first_email_at'         => $first_email ? gmdate( 'Y-m-d', $first_email ) : null,
			// the same as the milestone messages, in case one didn't get through
			'milestones'             => self::map( array_map( function ( $reached ) {
				return gmdate( 'Y-m-d\TH:i:s\Z', $reached );
			}, $telemetry->get_milestones() ) ),
		];
	}

	/**
	 * The earliest real value of a datetime column, as a timestamp
	 *
	 * @param string        $table
	 * @param string        $column
	 * @param callable|null $where
	 *
	 * @return int 0 when there is none
	 */
	public static function earliest( string $table, string $column, ?callable $where = null ) {

		// the query builder doesn't take MIN(), and ordering by an indexed column stops at the first row anyway
		$query = new Table_Query( $table );
		$query->setSelect( $column )->setOrderby( [ $column, 'ASC' ] )->setLimit( 1 );

		// rows created before dates were recorded have zeros
		$query->where()->greaterThan( $column, '2000-01-01 00:00:00' );

		if ( $where ) {
			call_user_func( $where, $query->where(), $query );
		}

		$earliest = $query->get_var();

		return $earliest ? (int) strtotime( $earliest . ' UTC' ) : 0;
	}

	/**
	 * @param int $timestamp
	 *
	 * @return string|null Y-m-d, or null for none
	 */
	protected static function to_date( int $timestamp ) {
		return $timestamp ? gmdate( 'Y-m-d', $timestamp ) : null;
	}
}
