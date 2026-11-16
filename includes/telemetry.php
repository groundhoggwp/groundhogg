<?php

namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Usage data an opted-in site sends home, see docs/telemetry-payload.md.
 *
 * - Nothing is sent unless the site opted in (gh_opted_in_stats_collection).
 * - Every message carries a random site ID, never the site's address. Only the opted_in message carries an email.
 * - A snapshot of usage goes once a week (Telemetry_Snapshot). Lifecycle events go when they happen: opted_in,
 *   activated, deactivated, upgraded. Opting out sends nothing, the site just stops.
 * - Milestones, the first time each step of getting started happens, are sent once each, a minute later from cron.
 *   Only for sites installed since they were recorded, so an old site doesn't report long-past firsts as new.
 * - Sites that opted in before snapshots had this much in them see a notice about it first, and send nothing new until
 *   it has been shown.
 * - All of the above is behind a flag, is_v2(), off until the endpoint is live. Until then opted-in sites send the old
 *   weekly ping to the CRM (send_legacy_snapshot()), as they always have.
 */
class Telemetry {

	const SCHEMA = 2;

	/**
	 * Where everything is sent. Override with the GROUNDHOGG_TELEMETRY_URL constant or the
	 * groundhogg/telemetry/endpoint filter.
	 */
	const ENDPOINT = 'https://telemetry.groundhogg.io/v2/collect';

	/**
	 * Still told about opt-ins, for the newsletter and the discount offered for opting in, until the endpoint above
	 * forwards them.
	 */
	const LEGACY_OPTIN_WEBHOOK = 'https://groundhogg.io/wp-json/gh/v4/webhooks/1724-telemetry-optin?token=vNhKt6H';

	/**
	 * Where the weekly ping went before schema 2, and still goes while the flag below is off
	 */
	const LEGACY_WEEKLY_WEBHOOK = 'https://groundhogg.io/wp-json/gh/v4/webhooks/1727-receive-telemetry?token=JVq8f3u';

	/**
	 * Whether schema 2 (the snapshot, events and milestones sent to ENDPOINT) is on by default. It stays off until the
	 * endpoint is live; until then sites send what they always have. Turn it on for one site with
	 * define( 'GROUNDHOGG_TELEMETRY_V2', true ) or the groundhogg/telemetry/v2 filter.
	 */
	const V2_DEFAULT = false;

	const CRON_HOOK          = 'groundhogg/telemetry';
	const SNAPSHOT_SOON_HOOK = 'groundhogg/telemetry/snapshot_soon';
	const MILESTONES_HOOK    = 'groundhogg/telemetry/milestones';

	const OPTIN_OPTION           = 'gh_opted_in_stats_collection';
	const EMAIL_OPTION           = 'gh_telemetry_email';
	const SITE_ID_OPTION         = 'gh_telemetry_site_id';
	const URL_HASH_OPTION        = 'gh_telemetry_url_hash';
	const CLONED_FROM_OPTION     = 'gh_telemetry_cloned_from';
	const LAST_VERSION_OPTION    = 'gh_telemetry_last_version';
	const LAST_SENT_OPTION       = 'gh_telemetry_last_sent';
	const LAST_ERROR_OPTION      = 'gh_telemetry_last_error';
	const ABILITY_COUNTS_OPTION  = 'gh_telemetry_ability_counts';
	const NOTICE_SHOWN_OPTION    = 'gh_telemetry_notice_shown';
	const INSTALLED_AT_OPTION    = 'gh_installed_at';
	const INSTALLED_AT_ESTIMATED = 'gh_installed_at_estimated';
	const MILESTONES_OPTION      = 'gh_telemetry_milestones';
	const MILESTONES_SENT_OPTION = 'gh_telemetry_milestones_sent';

	/**
	 * The first times that matter in getting started, each sent once, see milestone hooks in the constructor
	 */
	const MILESTONES = [
		'setup_finished',
		'contact_added',
		'contacts_imported',
		'form_submitted',
		'flow_activated',
		'flow_email_sent',
		'broadcast_sent',
		'agent_connected',
		'mobile_app_used',
	];

	/**
	 * The notification, in the notifications feed, telling sites that opted in before what's shared now
	 */
	const NOTICE_ID = 'telemetry-schema-2';

	/**
	 * @var bool|null whether the opt-in in progress also subscribed to the newsletter
	 */
	protected $subscribing = null;

	/**
	 * @var bool whether the activation in progress is the plugin's first on this site
	 */
	protected $first_activation = false;

	public function __construct() {
		add_action( 'admin_init', [ $this, 'init_cron' ] );
		add_action( self::CRON_HOOK, [ $this, 'maybe_send_telemetry' ] );
		add_action( self::SNAPSHOT_SOON_HOOK, [ $this, 'maybe_send_telemetry' ] );
		add_action( self::MILESTONES_HOOK, [ $this, 'send_milestones' ] );

		// after the installer's fail-safe install, which runs at the default priority
		add_action( 'admin_init', [ $this, 'maybe_record_installed_at' ], 20 );
		add_action( 'admin_init', [ $this, 'maybe_send_upgraded' ], 20 );

		add_action( 'groundhogg/main/activating', [ $this, 'activating' ] );
		add_action( 'groundhogg/main/activated', [ $this, 'activated' ] );
		add_action( 'groundhogg/main/deactivated', [ $this, 'deactivated' ] );

		// milestones
		add_action( 'add_option_gh_guided_setup_finished', [ $this, 'setup_finished_changed' ], 10, 2 );
		add_action( 'update_option_gh_guided_setup_finished', [ $this, 'setup_finished_changed' ], 10, 2 );
		add_action( 'groundhogg/contact/created', [ $this, 'contact_created' ] );
		add_action( 'groundhogg/contact/imported', [ $this, 'contact_imported' ] );
		add_action( 'groundhogg/submission/post_create', [ $this, 'submission_created' ], 10, 2 );
		add_action( 'groundhogg/funnel/post_create', [ $this, 'flow_created' ], 10, 2 );
		add_action( 'groundhogg/funnel/post_update', [ $this, 'flow_updated' ], 10, 4 );
		add_action( 'groundhogg/event/complete', [ $this, 'event_completed' ] );
		add_action( 'added_user_meta', [ $this, 'user_meta_added' ], 10, 3 );

		add_action( 'add_option_' . self::OPTIN_OPTION, [ $this, 'optin_option_added' ], 10, 2 );
		add_action( 'update_option_' . self::OPTIN_OPTION, [ $this, 'optin_option_updated' ], 10, 2 );

		// the Abilities API, WordPress 6.9+
		add_action( 'wp_after_execute_ability', [ $this, 'count_ability' ] );

		// the notification for sites that opted in before, and knowing an admin has seen it
		add_action( 'admin_init', [ $this, 'maybe_add_notification' ] );
		add_action( 'groundhogg/notices/read', [ $this, 'notification_seen' ], 10, 2 );
		add_action( 'groundhogg/notices/dismissed', [ $this, 'notification_seen' ], 10, 2 );
	}

	/**
	 * Whether stats collection is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return (bool) apply_filters( 'groundhogg/stats_collection/is_enabled', is_option_enabled( self::OPTIN_OPTION ) );
	}

	/**
	 * Whether schema 2 is on. While it's off, an opted-in site sends the old weekly ping to the CRM and nothing else
	 * new: no snapshot, events, milestones or notice. Install dates and milestones are still recorded, so they're
	 * there when it's turned on.
	 *
	 * @return bool
	 */
	public static function is_v2() {

		$on = defined( 'GROUNDHOGG_TELEMETRY_V2' ) ? (bool) GROUNDHOGG_TELEMETRY_V2 : self::V2_DEFAULT;

		/**
		 * Filter whether schema 2 telemetry is on
		 *
		 * @param bool $on
		 */
		return (bool) apply_filters( 'groundhogg/telemetry/v2', $on );
	}

	/**
	 * Whether a value of the opt-in option means opted in, the same way is_option_enabled() reads it
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 */
	protected static function value_enabled( $value ) {
		return is_array( $value ) ? in_array( 'on', $value ) : (bool) $value;
	}

	/**
	 * Whether the site opted in before snapshots had this much in them, and hasn't seen the notice that says so.
	 * New opt-ins get a site ID straight away, so a site that opted in without one opted in to the old snapshot.
	 *
	 * @return bool
	 */
	public function needs_notice() {
		return $this->is_enabled() && get_option( self::SITE_ID_OPTION ) === false && ! get_option( self::NOTICE_SHOWN_OPTION );
	}

	/**
	 * Whether anything may be sent
	 *
	 * @return bool
	 */
	public function can_send() {
		return self::is_v2() && $this->is_enabled() && ! $this->needs_notice();
	}

	/**
	 * Send the snapshot weekly, daily is too much
	 */
	public function init_cron() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'weekly', self::CRON_HOOK );
		}
	}

	/**
	 * Opt-in to the stats tracker system
	 *
	 * @deprecated
	 */
	public function stats_tracking_optin() {
		return $this->optin( true );
	}

	/**
	 * Opt in the current user's site, from the guided setup
	 *
	 * @param bool $subscribed whether they also want the newsletter
	 *
	 * @return true|\WP_Error
	 */
	public function optin( $subscribed = false ) {

		$telemetry_email = wp_get_current_user()->user_email;

		// telemetry is turned on either way
		if ( ! is_email( $telemetry_email ) ) {
			update_option( self::OPTIN_OPTION, [ 'on' ] );

			return new \WP_Error( 'invalid_email', 'Invalid email address.' );
		}

		update_option( self::EMAIL_OPTION, $telemetry_email );

		$this->subscribing = (bool) $subscribed;

		// sends opted_in when it turns telemetry on, see optin_option_updated()
		update_option( self::OPTIN_OPTION, [ 'on' ] );

		$this->subscribing = null;

		$this->post( self::LEGACY_OPTIN_WEBHOOK, [
			'email'       => $telemetry_email,
			'date'        => Ymd_His(),
			'subscribed'  => $subscribed ? 'yes' : 'no',
			'time_zone'   => get_user_timezone()->getName(),
			'system_info' => [
				'php_version' => PHP_VERSION,
				'wp_version'  => get_bloginfo( 'version' ),
				'gh_version'  => GROUNDHOGG_VERSION,
				'site_lang'   => get_bloginfo( 'language' ),
				'time_zone'   => wp_timezone()->getName(),
			],
		], false );

		return true;
	}

	/**
	 * @param string $option
	 * @param mixed  $value
	 */
	public function optin_option_added( $option, $value ) {
		$this->optin_option_updated( false, $value );
	}

	/**
	 * When telemetry is turned on, from anywhere: the guided setup, the settings, an ability
	 *
	 * @param mixed $old_value
	 * @param mixed $value
	 */
	public function optin_option_updated( $old_value, $value ) {

		if ( self::value_enabled( $old_value ) || ! self::value_enabled( $value ) || ! $this->is_enabled() ) {
			return;
		}

		$email = $this->get_email();

		if ( ! get_option( self::EMAIL_OPTION ) && is_email( $email ) ) {
			update_option( self::EMAIL_OPTION, $email );
		}

		// no site ID either, so a site that opts in now sees the notice when schema 2 is turned on
		if ( ! self::is_v2() ) {
			return;
		}

		$this->send( 'opted_in', [
			'email'      => $email,
			'newsletter' => (bool) $this->subscribing,
			'time_zone'  => get_user_timezone()->getName(),
		] );

		// the first snapshot, and any milestones reached before opting in, in a moment
		if ( ! wp_next_scheduled( self::SNAPSHOT_SOON_HOOK ) ) {
			wp_schedule_single_event( time() + MINUTE_IN_SECONDS, self::SNAPSHOT_SOON_HOOK );
		}

		$this->schedule_milestones();
	}

	/**
	 * The email the opt-in is sent with: whoever is opting in, or who did before, or the site admin
	 *
	 * @return string
	 */
	protected function get_email() {

		$email = wp_get_current_user()->user_email;

		if ( is_email( $email ) ) {
			return $email;
		}

		return get_option( self::EMAIL_OPTION ) ?: get_bloginfo( 'admin_email' );
	}

	/**
	 * Where messages go
	 *
	 * @return string
	 */
	public function get_endpoint() {
		$url = defined( 'GROUNDHOGG_TELEMETRY_URL' ) ? GROUNDHOGG_TELEMETRY_URL : self::ENDPOINT;

		/**
		 * Filter where telemetry is sent
		 *
		 * @param string $url
		 */
		return (string) apply_filters( 'groundhogg/telemetry/endpoint', $url );
	}

	/**
	 * A hash of the site's address, so a clone or a move can be told apart without sending the address
	 *
	 * @return string
	 */
	public static function url_hash() {

		$url = strtolower( untrailingslashit( home_url() ) );
		$url = preg_replace( '#^https?://(www\.)?#', '', $url );

		return hash( 'sha256', $url );
	}

	/**
	 * The site's ID, made the first time it's needed. A site whose address changed since its ID was made is a clone or
	 * a move, so it gets a new ID and says which one it came from until a snapshot has been sent.
	 *
	 * @return string
	 */
	public function get_site_id() {

		$site_id     = (string) get_option( self::SITE_ID_OPTION, '' );
		$stored_hash = (string) get_option( self::URL_HASH_OPTION, '' );
		$hash        = self::url_hash();

		if ( $site_id && $stored_hash && $stored_hash !== $hash ) {
			update_option( self::CLONED_FROM_OPTION, $site_id, false );
			$site_id = '';
		}

		if ( ! $site_id ) {
			$site_id = wp_generate_uuid4();
			update_option( self::SITE_ID_OPTION, $site_id, false );
		}

		if ( $stored_hash !== $hash ) {
			update_option( self::URL_HASH_OPTION, $hash, false );
		}

		return $site_id;
	}

	/**
	 * demo, production, staging, development or local.
	 *
	 * - Demo and sandbox sites (is_demo_environment()) are demo whatever WordPress says, they're usually set to production.
	 * - Some hosts leave staging copies set to production, so what is_staging_environment() spots counts as staging.
	 *
	 * @return string
	 */
	public static function get_environment() {

		if ( is_demo_environment() ) {
			return 'demo';
		}

		$environment = wp_get_environment_type();

		if ( $environment === 'production' && is_staging_environment() ) {
			return 'staging';
		}

		return $environment;
	}

	/**
	 * What every message starts with
	 *
	 * @param string $type
	 * @param string $key what makes the message unique for its type, when it's not the time
	 *
	 * @return array
	 */
	public function envelope( string $type, string $key = '' ) {

		$site_id = $this->get_site_id();
		$now     = time();

		if ( ! $key ) {
			// a snapshot is one a week, so a retry in the same week is recognised as the same one
			$key = $type === 'snapshot' ? gmdate( 'o-\WW', $now ) : (string) $now;
		}

		$message_id = sprintf( '%s:%s:%s', $site_id, $type, $key );

		return [
			'schema'      => self::SCHEMA,
			'type'        => $type,
			'site_id'     => $site_id,
			'message_id'  => $message_id,
			'sent_at'     => gmdate( 'Y-m-d\TH:i:s\Z', $now ),
			'url_hash'    => self::url_hash(),
			'cloned_from' => get_option( self::CLONED_FROM_OPTION ) ?: null,
			'environment' => self::get_environment(),
			'groundhogg'  => GROUNDHOGG_VERSION,
		];
	}

	/**
	 * Send a message
	 *
	 * @param string $type
	 * @param array  $data     the message's own fields
	 * @param bool   $blocking false to not wait for the answer, for events that happen during someone's request
	 * @param string $key      what makes the message unique for its type, see envelope()
	 *
	 * @return true|\WP_Error
	 */
	public function send( string $type, array $data = [], bool $blocking = false, string $key = '' ) {

		// the envelope's fields can't be overwritten
		$message = $this->envelope( $type, $key ) + $data;

		/**
		 * Filter a telemetry message before it is sent. Return false to not send it.
		 *
		 * @param array|false $message
		 * @param string      $type
		 */
		$message = apply_filters( 'groundhogg/telemetry/message', $message, $type );

		if ( ! is_array( $message ) ) {
			return new \WP_Error( 'not_sent', 'The message was filtered out.' );
		}

		return $this->post( $this->get_endpoint(), $message, $blocking );
	}

	/**
	 * POST JSON. Not with remote_post_json(), which puts the site's address in the user agent.
	 *
	 * @param string $url
	 * @param array  $body
	 * @param bool   $blocking
	 *
	 * @return true|\WP_Error
	 */
	protected function post( string $url, array $body, bool $blocking ) {

		$response = wp_remote_post( $url, [
			'headers'     => [ 'Content-Type' => 'application/json; charset=utf-8' ],
			'body'        => wp_json_encode( $body ),
			'data_format' => 'body',
			'timeout'     => $blocking ? 15 : 1,
			'blocking'    => $blocking,
			'user-agent'  => 'Groundhogg/' . GROUNDHOGG_VERSION,
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! $blocking ) {
			return true;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( $code < 200 || $code >= 300 ) {
			/* translators: %d: an HTTP status code */
			return new \WP_Error( 'telemetry_http_error', sprintf( 'The telemetry endpoint answered with %d.', $code ), [ 'status' => $code ] );
		}

		return true;
	}

	/**
	 * The weekly snapshot, and the first one after opting in
	 *
	 * @return void
	 */
	public function maybe_send_telemetry() {

		if ( ! $this->is_enabled() ) {
			return;
		}

		if ( ! self::is_v2() ) {
			$this->send_legacy_snapshot();

			return;
		}

		if ( $this->can_send() ) {
			$this->send_snapshot();
		}
	}

	/**
	 * The weekly ping sent before schema 2, unchanged, while schema 2 is off. Remove it with the flag.
	 *
	 * @return void
	 */
	public function send_legacy_snapshot() {

		$date      = new \DateTime( 'now', wp_timezone() );
		$_7daysago = clone $date;
		$_7daysago->modify( '6 days ago 12:00 am' );

		$request = [
			'email'            => get_option( self::EMAIL_OPTION ) ?: get_bloginfo( 'admin_email' ),
			'date'             => Ymd_His(),
			'is_licensed'      => has_premium_features(),
			'master_license'   => get_master_license(),
			'is_white_labeled' => is_white_labeled(),
			'system_info'      => [
				'php_version' => PHP_VERSION,
				'wp_version'  => get_bloginfo( 'version' ),
				'gh_version'  => GROUNDHOGG_VERSION,
				'site_lang'   => get_bloginfo( 'language' ),
			],
			'usage'            => [
				'funnels'    => get_db( 'funnels' )->count( [ 'status' => 'active' ] ),
				'contacts'   => get_db( 'contacts' )->count( [ 'after' => $_7daysago->format( 'Y-m-d H:i:s' ) ] ),
				'broadcasts' => get_db( 'broadcasts' )->count( [ 'after' => $_7daysago->getTimestamp() ] ),
			],
			'extensions'       => implode( ',', Extension::$extension_ids ),
		];

		remote_post_json( self::LEGACY_WEEKLY_WEBHOOK, $request );
	}

	/**
	 * Build and send the snapshot
	 *
	 * @return true|\WP_Error
	 */
	public function send_snapshot() {

		if ( ! $this->can_send() ) {
			return new \WP_Error( 'not_enabled', 'Telemetry is not enabled.' );
		}

		$this->maybe_send_upgraded();

		$snapshot = ( new Telemetry_Snapshot() )->build();

		$result = $this->send( 'snapshot', $snapshot, true );

		if ( is_wp_error( $result ) ) {
			update_option( self::LAST_ERROR_OPTION, $result->get_error_message(), false );

			return $result;
		}

		update_option( self::LAST_SENT_OPTION, time(), false );
		delete_option( self::LAST_ERROR_OPTION );

		// counted again from now, and the clone has said where it came from
		delete_option( self::ABILITY_COUNTS_OPTION );
		delete_option( self::CLONED_FROM_OPTION );

		// milestones that didn't get through when they were reached
		$this->send_milestones();

		return true;
	}

	/**
	 * Continue continuous tracking of the site.
	 *
	 * @deprecated use Plugin::instance()->stats_collection->maybe_send_telemetry()
	 */
	public static function send_telemetry() {
		Plugin::instance()->stats_collection->maybe_send_telemetry();
	}

	/**
	 * Tell when the plugin was updated. There's no hook for that, so compare against the version seen last.
	 *
	 * @return void
	 */
	public function maybe_send_upgraded() {

		$last = get_option( self::LAST_VERSION_OPTION );

		if ( $last === GROUNDHOGG_VERSION ) {
			return;
		}

		update_option( self::LAST_VERSION_OPTION, GROUNDHOGG_VERSION );

		// the first time there's nothing to compare with
		if ( $last && $this->can_send() ) {
			$this->send( 'upgraded', [
				'from' => (string) $last,
				'to'   => GROUNDHOGG_VERSION,
			] );
		}
	}

	/**
	 * Remember when the plugin was first activated, before the installer creates anything, so nothing it creates makes
	 * the install date look estimated
	 *
	 * @return void
	 */
	public function activating() {

		$this->first_activation = ! get_option( self::INSTALLED_AT_OPTION );

		if ( $this->first_activation ) {
			update_option( self::INSTALLED_AT_OPTION, time() );
		}
	}

	/**
	 * @return void
	 */
	public function activated() {

		if ( $this->can_send() ) {
			$this->send( 'activated', [ 'first_activation' => $this->first_activation ] );
		}

		$this->first_activation = false;
	}

	/**
	 * @return void
	 */
	public function deactivated() {
		if ( $this->can_send() ) {
			$this->send( 'deactivated' );
		}
	}

	/**
	 * Sites installed before the install date was recorded get an estimate: the earliest of the first contact (the
	 * installer creates one), the first flow, and the first update routine.
	 *
	 * @return void
	 */
	public function maybe_record_installed_at() {

		if ( get_option( self::INSTALLED_AT_OPTION ) ) {
			return;
		}

		$dates = (array) get_option( 'gh_main_version_updates_dates', [] );

		$candidates = array_filter( array_merge( array_map( 'absint', array_values( $dates ) ), [
			Telemetry_Snapshot::earliest( 'contacts', 'date_created' ),
			Telemetry_Snapshot::earliest( 'funnels', 'date_created' ),
		] ) );

		// autoloaded, milestones check them on busy paths like completing events
		update_option( self::INSTALLED_AT_OPTION, empty( $candidates ) ? time() : min( $candidates ) );
		update_option( self::INSTALLED_AT_ESTIMATED, 1 );
	}

	/**
	 * @return int a timestamp
	 */
	public function get_installed_at() {
		$this->maybe_record_installed_at();

		return absint( get_option( self::INSTALLED_AT_OPTION ) );
	}

	/**
	 * @return bool
	 */
	public function is_installed_at_estimated() {
		return (bool) get_option( self::INSTALLED_AT_ESTIMATED );
	}

	/**
	 * @return int[] milestone => when it was reached
	 */
	public function get_milestones() {

		$milestones = get_option( self::MILESTONES_OPTION, [] );

		return is_array( $milestones ) ? array_map( 'absint', $milestones ) : [];
	}

	/**
	 * Record the first time something that matters in getting started happens. It's recorded whether or not the site
	 * opted in, so the date is right if they opt in later, and sent once, a minute later, from cron, so nothing is
	 * sent during someone's request.
	 *
	 * @param string $name one of self::MILESTONES
	 *
	 * @return bool whether it was reached just now
	 */
	public function reach_milestone( string $name ) {

		// this runs on busy paths like completing events, and the option is autoloaded, so this is nearly free
		$milestones = $this->get_milestones();

		if ( isset( $milestones[ $name ] ) || ! in_array( $name, self::MILESTONES, true ) ) {
			return false;
		}

		// a site installed before milestones were recorded would report long-past firsts as new
		$this->maybe_record_installed_at();

		if ( $this->is_installed_at_estimated() ) {
			return false;
		}

		$milestones[ $name ] = time();

		update_option( self::MILESTONES_OPTION, $milestones );

		$this->schedule_milestones();

		return true;
	}

	/**
	 * Send the milestones that haven't been, in a moment
	 *
	 * @return void
	 */
	protected function schedule_milestones() {
		if ( $this->can_send() && ! wp_next_scheduled( self::MILESTONES_HOOK ) ) {
			wp_schedule_single_event( time() + MINUTE_IN_SECONDS, self::MILESTONES_HOOK );
		}
	}

	/**
	 * Send each milestone that hasn't been. One that doesn't get through is tried again after the next snapshot.
	 *
	 * @return void
	 */
	public function send_milestones() {

		if ( ! $this->can_send() ) {
			return;
		}

		$sent = get_option( self::MILESTONES_SENT_OPTION, [] );

		if ( ! is_array( $sent ) ) {
			$sent = [];
		}

		foreach ( $this->get_milestones() as $name => $reached ) {

			if ( isset( $sent[ $name ] ) ) {
				continue;
			}

			$result = $this->send( 'milestone', [
				'milestone'  => $name,
				'reached_at' => gmdate( 'Y-m-d\TH:i:s\Z', $reached ),
			], true, $name );

			if ( is_wp_error( $result ) ) {
				break;
			}

			$sent[ $name ] = true;
		}

		update_option( self::MILESTONES_SENT_OPTION, $sent, false );
	}

	/**
	 * The guided setup was finished
	 *
	 * @param mixed $old_value_or_option
	 * @param mixed $value
	 */
	public function setup_finished_changed( $old_value_or_option, $value ) {
		if ( self::value_enabled( $value ) ) {
			$this->reach_milestone( 'setup_finished' );
		}
	}

	/**
	 * A contact was added, other than the one the installer creates for whoever activated the plugin
	 *
	 * @return void
	 */
	public function contact_created() {

		if ( did_action( 'groundhogg/main/activating' ) && ! did_action( 'groundhogg/main/activated' ) ) {
			return;
		}

		$this->reach_milestone( 'contact_added' );
	}

	/**
	 * @return void
	 */
	public function contact_imported() {
		$this->reach_milestone( 'contacts_imported' );
	}

	/**
	 * @param int   $id
	 * @param array $data
	 */
	public function submission_created( $id, $data ) {
		if ( ( $data['type'] ?? 'form' ) === 'form' ) {
			$this->reach_milestone( 'form_submitted' );
		}
	}

	/**
	 * A flow made active, by an import or an agent say
	 *
	 * @param int   $id
	 * @param array $data
	 */
	public function flow_created( $id, $data ) {
		if ( ( $data['status'] ?? '' ) === 'active' ) {
			$this->reach_milestone( 'flow_activated' );
		}
	}

	/**
	 * @param int    $id
	 * @param array  $data
	 * @param Funnel $flow
	 * @param array  $old_data
	 */
	public function flow_updated( $id, $data, $flow, $old_data ) {

		$was = ( (array) $old_data )['status'] ?? '';

		if ( ( $data['status'] ?? '' ) === 'active' && $was !== 'active' ) {
			$this->reach_milestone( 'flow_activated' );
		}
	}

	/**
	 * An email sent by a flow, or a broadcast going out
	 *
	 * @param Event $event
	 */
	public function event_completed( $event ) {

		if ( ! $event instanceof Event ) {
			return;
		}

		$type = $event->get_event_type();

		if ( $type === Event::FUNNEL && $event->get_email_id() ) {
			$this->reach_milestone( 'flow_email_sent' );
		} else if ( $type === Event::BROADCAST ) {
			$this->reach_milestone( 'broadcast_sent' );
		}
	}

	/**
	 * The first time someone used an AI agent with the site, or the mobile app
	 *
	 * @param int    $meta_id
	 * @param int    $user_id
	 * @param string $meta_key
	 */
	public function user_meta_added( $meta_id, $user_id, $meta_key ) {

		if ( $meta_key === Mcp\Connection::SEEN_META ) {
			$this->reach_milestone( 'agent_connected' );
		} else if ( $meta_key === Mobile\Promo::USED_META ) {
			$this->reach_milestone( 'mobile_app_used' );
		}
	}

	/**
	 * Count this plugin's and its extensions' abilities as they run, only while opted in
	 *
	 * @param string $name
	 *
	 * @return void
	 */
	public function count_ability( $name ) {

		// only while schema 2 is on, the counts are reset by its snapshots
		if ( ! is_string( $name ) || ! str_starts_with( $name, 'groundhogg' ) || ! self::is_v2() || ! $this->is_enabled() ) {
			return;
		}

		$counts = get_option( self::ABILITY_COUNTS_OPTION, [] );

		if ( ! is_array( $counts ) ) {
			$counts = [];
		}

		$counts[ $name ] = absint( $counts[ $name ] ?? 0 ) + 1;

		update_option( self::ABILITY_COUNTS_OPTION, $counts, false );
	}

	/**
	 * @return int[] ability name => times it ran since the last snapshot
	 */
	public function get_ability_counts() {

		$counts = get_option( self::ABILITY_COUNTS_OPTION, [] );

		return is_array( $counts ) ? array_map( 'absint', $counts ) : [];
	}

	/**
	 * Tell sites that opted in to the old, smaller ping what is shared now, and how to stop, in the notifications
	 * feed. Nothing new is sent until an admin has read it. It stays in the feed until each person dismisses it, and
	 * goes when the site opts out.
	 *
	 * @return void
	 */
	public function maybe_add_notification() {

		$notices = Plugin::instance()->notices;

		// needed, or seen already and kept for whoever hasn't dismissed it
		if ( ! self::is_v2() || ! $this->is_enabled() || ( ! $this->needs_notice() && ! get_option( self::NOTICE_SHOWN_OPTION ) ) ) {
			$notices->remove_notification( self::NOTICE_ID );

			return;
		}

		$notices->add_notification( self::NOTICE_ID, [
			'title'    => __( 'The usage data you share now includes more detail', 'groundhogg' ),
			'message'  => sprintf(
				'<p>%1$s</p><p><a href="%2$s">%3$s</a></p>',
				esc_html( sprintf(
					/* translators: %s: the plugin/brand name */
					__( 'You opted in to sharing usage data to help make %s better. Once a week it now also includes which flow steps, editors and settings you use, totals and weekly counts of contacts, emails and automation, whether email sending and cron are working, and which plugins are active. Never your contacts, what\'s in your emails, or your site\'s address.', 'groundhogg' ),
					white_labeled_name()
				) ),
				// straight to the setting, the same anchor the command palette uses
				esc_url( admin_page_url( 'gh_settings', [ 'tab' => 'misc' ], Admin\Settings\Settings_Page::setting_anchor( self::OPTIN_OPTION ) ) ),
				esc_html__( 'Stop sharing', 'groundhogg' )
			),
			'type'     => 'info',
			'cap'      => 'manage_options',
			'location' => 'feed',
		] );
	}

	/**
	 * Once an admin has read or dismissed the notification, the site can send. When white labeled, the feed is shown
	 * as a banner, which counts once it's dismissed.
	 *
	 * @param string[] $ids
	 * @param int      $user_id
	 *
	 * @return void
	 */
	public function notification_seen( $ids, $user_id ) {

		if ( ! in_array( self::NOTICE_ID, (array) $ids, true ) || ! user_can( $user_id, 'manage_options' ) ) {
			return;
		}

		if ( ! get_option( self::NOTICE_SHOWN_OPTION ) ) {
			update_option( self::NOTICE_SHOWN_OPTION, time(), false );
		}
	}
}
