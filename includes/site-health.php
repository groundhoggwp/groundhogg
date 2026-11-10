<?php

namespace Groundhogg;

use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Queue\Event_Queue;
use Groundhogg_Email_Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds the health of the CRM to Tools › Site Health
 *
 * The Status tab gets tests for what stops automation and email from working, the Info tab gets the configuration
 * support asks for, so it is copied along with the rest of the site's info.
 */
class Site_Health {

	/**
	 * Events this many seconds past their run time mean the queue isn't keeping up
	 */
	const OVERDUE_EVENTS_AFTER = 15 * MINUTE_IN_SECONDS;

	/**
	 * Background tasks not worked on for this many seconds are stuck
	 */
	const STUCK_TASKS_AFTER = HOUR_IN_SECONDS;

	public function __construct() {
		add_filter( 'site_status_tests', [ $this, 'register_tests' ] );
		add_filter( 'debug_information', [ $this, 'debug_information' ] );
	}

	/**
	 * Add the tests to the Status tab
	 *
	 * @param array $tests
	 *
	 * @return array
	 */
	public function register_tests( $tests ) {

		$direct = [
			'event_queue'      => [ __( 'Automation is running on time', 'groundhogg' ), 'test_event_queue' ],
			'cron'             => [ __( 'Automation runs every minute', 'groundhogg' ), 'test_cron' ],
			'email_sending'    => [ __( 'Email is being sent', 'groundhogg' ), 'test_email_sending' ],
			'email_service'    => [ __( 'Email is sent with an email service', 'groundhogg' ), 'test_email_service' ],
			'failed_events'    => [ __( 'Automation is completing without errors', 'groundhogg' ), 'test_failed_events' ],
			'background_tasks' => [ __( 'Background tasks are running', 'groundhogg' ), 'test_background_tasks' ],
			'database'         => [ __( 'The database is up to date', 'groundhogg' ), 'test_database' ],
			'permalinks'       => [ __( 'Permalinks work with tracking and the preferences center', 'groundhogg' ), 'test_permalinks' ],
			'safe_mode'        => [ __( 'Safe mode is off', 'groundhogg' ), 'test_safe_mode' ],
		];

		if ( Email_Logger::is_enabled() ) {
			$direct['failed_emails'] = [ __( 'Emails are being delivered without errors', 'groundhogg' ), 'test_failed_emails' ];
		}

		if ( Plugin::instance()->bounce_checker->get_bounce_inbox() ) {
			$direct['bounces'] = [ __( 'Bounces can be checked', 'groundhogg' ), 'test_bounces' ];
		}

		if ( ! is_white_labeled() && ! empty( Extension::get_extensions() ) ) {
			$direct['licenses'] = [ __( 'Extensions are licensed', 'groundhogg' ), 'test_licenses' ];
		}

		/**
		 * Filter the tests added to Site Health
		 *
		 * @param array $direct test id => [ label, method of this class or callable ]
		 */
		$direct = apply_filters( 'groundhogg/site_health/tests', $direct );

		foreach ( $direct as $id => [ $label, $callback ] ) {
			$tests['direct'][ 'groundhogg_' . $id ] = [
				'label' => $label,
				'test'  => is_string( $callback ) && method_exists( $this, $callback ) ? [ $this, $callback ] : $callback,
			];
		}

		return $tests;
	}

	/**
	 * Build a test result
	 *
	 * @param string $test        the test id, without the prefix
	 * @param string $status      good, recommended or critical
	 * @param string $label       the result's heading
	 * @param string $description HTML
	 * @param array  $actions     url => link text
	 *
	 * @return array
	 */
	protected function result( string $test, string $status, string $label, string $description, array $actions = [] ) {

		$links = [];

		foreach ( $actions as $url => $text ) {
			$links[] = sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html( $text ) );
		}

		return [
			'test'        => 'groundhogg_' . $test,
			'status'      => $status,
			'label'       => $label,
			'badge'       => [
				'label' => white_labeled_name(),
				'color' => 'blue',
			],
			'description' => $description,
			'actions'     => empty( $links ) ? '' : '<p>' . implode( ' | ', $links ) . '</p>',
		];
	}

	/**
	 * Wrap paragraphs of plain text
	 *
	 * @param string ...$paragraphs
	 *
	 * @return string
	 */
	protected function paragraphs( ...$paragraphs ) {
		return implode( '', array_map( function ( $p ) {
			return '<p>' . esc_html( $p ) . '</p>';
		}, array_filter( $paragraphs ) ) );
	}

	/**
	 * Waiting events that should have run a while ago
	 *
	 * @return array
	 */
	public function test_event_queue() {

		$query = new Table_Query( 'event_queue' );
		$query->where()
		      ->in( 'status', [ Event::WAITING, Event::IN_PROGRESS ] )
		      ->lessThan( 'time', time() - self::OVERDUE_EVENTS_AFTER );

		$overdue = $query->count();

		if ( ! $overdue ) {
			return $this->result( 'event_queue', 'good', __( 'Automation is running on time', 'groundhogg' ), $this->paragraphs(
				__( 'Flow steps and broadcasts are being sent when they are scheduled to.', 'groundhogg' )
			) );
		}

		$oldest = absint( $query->setSelect( 'time' )->setOrderby( 'time' )->setOrder( 'ASC' )->setLimit( 1 )->get_var() );

		return $this->result( 'event_queue', 'critical', __( 'Automation is falling behind', 'groundhogg' ), $this->paragraphs(
			sprintf(
				/* translators: 1: number of events, 2: how long ago, e.g. "3 hours" */
				_n(
					'%1$s event should have run already, the oldest %2$s ago. Emails and flow steps are going out late, or not at all.',
					'%1$s events should have run already, the oldest %2$s ago. Emails and flow steps are going out late, or not at all.',
					$overdue,
					'groundhogg'
				),
				number_format_i18n( $overdue ),
				human_time_diff( $oldest )
			),
			__( 'This usually means the cron jobs that process the queue are not running, or the server is stopping them before they finish. Setting up a dedicated cron job fixes most cases. Events that are already late can be run or cancelled from the log management screen.', 'groundhogg' )
		), [
			admin_page_url( 'gh_tools', [ 'tab' => 'cron' ] )      => __( 'Set up the cron job', 'groundhogg' ),
			admin_page_url( 'gh_events', [ 'tab' => 'manage' ] ) => __( 'Fix unprocessed events', 'groundhogg' ),
		] );
	}

	/**
	 * Whether the queue is processed by its own cron job every minute
	 *
	 * @return array
	 */
	public function test_cron() {

		$setup_url = [ admin_page_url( 'gh_tools', [ 'tab' => 'cron' ] ) => __( 'Set up the cron job', 'groundhogg' ) ];

		if ( gh_cron_installed() && is_event_queue_processing() && apply_filters( 'groundhogg/cron/verified', true ) ) {
			return $this->result( 'cron', 'good', __( 'Automation runs every minute', 'groundhogg' ), $this->paragraphs(
				__( 'A dedicated cron job processes the event queue every minute, so emails and flow steps run on time.', 'groundhogg' )
			) );
		}

		if ( gh_cron_installed() ) {
			$last_ping = absint( get_option( 'gh_cron_last_ping' ) );

			return $this->result( 'cron', 'recommended', __( 'The cron job for automation has stopped running', 'groundhogg' ), $this->paragraphs(
				$last_ping
					/* translators: %s: how long ago, e.g. "3 hours" */
					? sprintf( __( 'The cron job is installed but last ran %s ago. It should run every minute.', 'groundhogg' ), human_time_diff( $last_ping ) )
					: __( 'The cron job is installed but has not run yet. It should run every minute.', 'groundhogg' ),
				__( 'Until it runs again, the queue is processed by WordPress cron when people visit the site, so emails and flow steps can run late. Check that the cron job still exists on your server or with your host.', 'groundhogg' )
			), $setup_url );
		}

		return $this->result( 'cron', 'recommended', __( 'Automation depends on site visits to run', 'groundhogg' ), $this->paragraphs(
			__( 'The event queue is processed by WordPress cron, which only runs when someone visits the site. On a quiet site emails and flow steps can run late, and on a busy one processing slows down page loads.', 'groundhogg' ),
			__( 'A cron job set up on your server or with your host processes the queue every minute, whether anyone visits or not.', 'groundhogg' )
		), $setup_url );
	}

	/**
	 * Whether emails are actually leaving the site
	 *
	 * @return array
	 */
	public function test_email_sending() {

		$settings = [ admin_page_url( 'gh_settings', [ 'tab' => 'email' ] ) => __( 'Email settings', 'groundhogg' ) ];

		if ( is_staging_environment() && ! is_option_enabled( 'gh_allow_email_in_staging' ) ) {
			return $this->result( 'email_sending', 'recommended', __( 'Emails to contacts are only being logged on this staging site', 'groundhogg' ), $this->paragraphs(
				__( 'This looks like a staging or development site, so marketing and transactional emails are logged instead of sent, to keep contacts from getting emails from a copy of your site. WordPress emails, like password resets, are still sent.', 'groundhogg' ),
				__( 'If this is your live site, set the environment type to production, or allow outgoing email in staging in the email settings.', 'groundhogg' )
			), $settings );
		}

		$labels      = Groundhogg_Email_Services::message_type_labels();
		$registered  = Groundhogg_Email_Services::get();
		$log_only    = [];
		$unavailable = [];

		foreach ( $labels as $type => $label ) {
			$service = Groundhogg_Email_Services::get_saved_service( $type );

			if ( $service === 'log_only' ) {
				$log_only[] = $label;
			} else if ( ! isset( $registered[ $service ] ) ) {
				$unavailable[] = $label;
			}
		}

		if ( ! empty( $log_only ) ) {
			return $this->result( 'email_sending', 'critical', __( 'Some emails are only being logged, not sent', 'groundhogg' ), $this->paragraphs(
				/* translators: %s: list of email types, e.g. "Marketing and Transactional" */
				sprintf( __( '%s emails are set to use the Log Only service, so they are written to the email log but never sent.', 'groundhogg' ), andList( $log_only ) ),
				__( 'Choose a service that sends email for these types if this is not intentional.', 'groundhogg' )
			), $settings );
		}

		if ( ! empty( $unavailable ) ) {
			return $this->result( 'email_sending', 'recommended', __( 'An email service that is no longer available is selected', 'groundhogg' ), $this->paragraphs(
				/* translators: %s: list of email types, e.g. "Marketing and Transactional" */
				sprintf( __( '%s emails are set to use a service that is not available, usually because the plugin that provides it was deactivated. They are being sent with the WordPress default instead.', 'groundhogg' ), andList( $unavailable ) ),
				__( 'Reactivate the plugin, or choose another service in the email settings.', 'groundhogg' )
			), $settings );
		}

		return $this->result( 'email_sending', 'good', __( 'Email is being sent', 'groundhogg' ), $this->paragraphs(
			__( 'Every type of email is set to be sent with a service that is available.', 'groundhogg' )
		) );
	}

	/**
	 * Whether another plugin sends wp_mail() with SMTP or an API, either by replacing it or by setting up PHPMailer
	 *
	 * @return bool
	 */
	protected function wp_mail_is_handled_by_plugin() {

		$defined_in = extrapolate_wp_mail_plugin();

		if ( $defined_in && wp_normalize_path( $defined_in ) !== wp_normalize_path( ABSPATH . WPINC . '/pluggable.php' ) ) {
			return true;
		}

		global $wp_filter;

		if ( ! isset( $wp_filter['phpmailer_init'] ) ) {
			return false;
		}

		$plugins_dir = trailingslashit( wp_normalize_path( WP_PLUGIN_DIR ) );

		// SMTP plugins configure PHPMailer here, but so do plenty of others to add headers or an alt body, so only count
		// the ones that look like email delivery plugins. The integrations are email services, which were checked already.
		foreach ( $wp_filter['phpmailer_init']->callbacks as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				try {
					$function   = $callback['function'];
					$reflection = is_array( $function ) ? new \ReflectionMethod( $function[0], $function[1] ) : new \ReflectionFunction( $function );
				} catch ( \Throwable $e ) {
					// a 'Class::method' string or an invokable object, can't tell where it's from
					continue;
				}

				$file = wp_normalize_path( (string) $reflection->getFileName() );

				if ( ! str_starts_with( $file, $plugins_dir ) ) {
					continue;
				}

				$slug = strtok( substr( $file, strlen( $plugins_dir ) ), '/' );

				if ( ! str_starts_with( $slug, 'groundhogg' ) && preg_match( '/smtp|mailgun|sendgrid|sparkpost|postmark|mailjet|mailersend|sendinblue|brevo|elastic-?email|resend|\bses\b/i', $slug ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Whether marketing email goes through something better than PHP mail()
	 *
	 * @return array
	 */
	public function test_email_service() {

		$marketing = Groundhogg_Email_Services::get_marketing_service();

		if ( $marketing !== 'wp_mail' || Mailhawk::is_connected() || $this->wp_mail_is_handled_by_plugin() ) {
			return $this->result( 'email_service', 'good', __( 'Email is sent with an email service', 'groundhogg' ), $this->paragraphs(
				__( 'Marketing emails are sent with a dedicated email service or SMTP, which helps them reach the inbox.', 'groundhogg' )
			) );
		}

		$actions = Mailhawk::is_disabled() || ! current_user_can( 'install_plugins' )
			? [ admin_page_url( 'gh_settings', [ 'tab' => 'email' ] ) => __( 'Email settings', 'groundhogg' ) ]
			: [ admin_page_url( 'gh_settings', [ 'tab' => 'email' ] ) => __( 'Send email with MailHawk', 'groundhogg' ) ];

		return $this->result( 'email_service', 'recommended', __( 'Email is sent by the web server', 'groundhogg' ), $this->paragraphs(
			__( 'Emails are sent with the WordPress default, which hands them to the web server. Most servers are not set up to send email, so messages often land in spam or are not delivered at all.', 'groundhogg' ),
			__( 'Connect a dedicated email service, or an SMTP or email API plugin, to improve deliverability.', 'groundhogg' )
		), $actions );
	}

	/**
	 * Events that failed in the last day
	 *
	 * @return array
	 */
	public function test_failed_events() {

		$query = new Table_Query( 'events' );
		$query->where()
		      ->equals( 'status', Event::FAILED )
		      ->greaterThan( 'time', time() - DAY_IN_SECONDS );

		$failed = $query->count();

		if ( ! $failed ) {
			return $this->result( 'failed_events', 'good', __( 'Automation is completing without errors', 'groundhogg' ), $this->paragraphs(
				__( 'No flow steps or broadcasts have failed in the last 24 hours.', 'groundhogg' )
			) );
		}

		return $this->result( 'failed_events', 'recommended', __( 'Some automation has failed recently', 'groundhogg' ), $this->paragraphs(
			sprintf(
				/* translators: %s: number of events */
				_n(
					'%s flow step or broadcast email failed in the last 24 hours.',
					'%s flow steps or broadcast emails failed in the last 24 hours.',
					$failed,
					'groundhogg'
				),
				number_format_i18n( $failed )
			),
			__( 'The event log shows the error for each one. Common causes are an email service rejecting the email, or a step that is missing a setting.', 'groundhogg' )
		), [
			admin_page_url( 'gh_events', [ 'status' => Event::FAILED ] ) => __( 'View failed events', 'groundhogg' ),
		] );
	}

	/**
	 * Emails that failed to send in the last day, only when the log is on
	 *
	 * @return array
	 */
	public function test_failed_emails() {

		$since = Ymd_His( time() - DAY_IN_SECONDS );

		$query = new Table_Query( 'email_log' );
		$query->where()->equals( 'status', 'failed' )->greaterThan( 'date_sent', $since );
		$failed = $query->count();

		if ( ! $failed ) {
			return $this->result( 'failed_emails', 'good', __( 'Emails are being delivered without errors', 'groundhogg' ), $this->paragraphs(
				__( 'No emails have failed to send in the last 24 hours.', 'groundhogg' )
			) );
		}

		$query = new Table_Query( 'email_log' );
		$query->where()->equals( 'status', 'sent' )->greaterThan( 'date_sent', $since );
		$sent = $query->count();

		// most of what was sent failed, so the service is likely misconfigured
		$status = $failed >= 10 && $failed >= $sent ? 'critical' : 'recommended';

		return $this->result( 'failed_emails', $status, __( 'Some emails failed to send', 'groundhogg' ), $this->paragraphs(
			sprintf(
				/* translators: 1: number of failed emails, 2: number of all emails */
				__( '%1$s of %2$s emails failed to send in the last 24 hours.', 'groundhogg' ),
				number_format_i18n( $failed ),
				number_format_i18n( $failed + $sent )
			),
			__( 'The email log shows the error the email service returned for each one, such as a sender address that is not verified or a sending limit being reached.', 'groundhogg' )
		), [
			admin_page_url( 'gh_events', [ 'tab' => 'emails', 'view' => 'failed' ] ) => __( 'View failed emails', 'groundhogg' ),
		] );
	}

	/**
	 * Background tasks that haven't been worked on in a while
	 *
	 * @return array
	 */
	public function test_background_tasks() {

		$query = new Table_Query( 'background_tasks' );
		$query->where()
		      ->in( 'status', [ 'pending', 'in_progress' ] )
		      ->lessThan( 'time', time() - self::STUCK_TASKS_AFTER );

		$stuck = $query->count();

		if ( ! $stuck ) {
			return $this->result( 'background_tasks', 'good', __( 'Background tasks are running', 'groundhogg' ), $this->paragraphs(
				__( 'Imports, exports, bulk edits and broadcasts that are scheduled in the background are being processed.', 'groundhogg' )
			) );
		}

		return $this->result( 'background_tasks', 'recommended', __( 'Some background tasks are not making progress', 'groundhogg' ), $this->paragraphs(
			sprintf(
				/* translators: %s: number of tasks */
				_n(
					'%s background task has not been processed in over an hour.',
					'%s background tasks have not been processed in over an hour.',
					$stuck,
					'groundhogg'
				),
				number_format_i18n( $stuck )
			),
			__( 'Background tasks are processed by WordPress cron. If it is not running, imports, bulk edits and broadcasts being scheduled will not finish.', 'groundhogg' )
		), [
			admin_page_url( 'gh_events', [ 'tab' => 'tasks' ] ) => __( 'View background tasks', 'groundhogg' ),
		] );
	}

	/**
	 * The plugin's tables that don't exist
	 *
	 * @return string[]
	 */
	protected function get_missing_tables() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- one query for every table, must not be cached
		$existing = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->prefix . 'gh_' ) . '%' ) );
		$missing  = [];

		foreach ( Plugin::instance()->dbs->get_dbs() as $db ) {
			if ( $db && ! in_array( $db->get_table_name(), $existing, true ) ) {
				$missing[] = $db->get_table_name();
			}
		}

		return $missing;
	}

	/**
	 * The updaters of the plugin and its extensions
	 *
	 * @return Updater[]
	 */
	protected function get_updaters() {

		$updaters = [ Plugin::instance()->updater ];

		foreach ( Extension::get_extensions() as $extension ) {
			$updaters[] = $extension->updater;
		}

		return array_filter( $updaters, function ( $updater ) {
			return $updater instanceof Updater;
		} );
	}

	/**
	 * Missing tables and updates waiting to be run
	 *
	 * @return array
	 */
	public function test_database() {

		$missing = $this->get_missing_tables();

		if ( ! empty( $missing ) ) {
			return $this->result( 'database', 'critical', __( 'Some database tables are missing', 'groundhogg' ), $this->paragraphs(
				/* translators: %s: list of database table names */
				sprintf( __( 'These tables do not exist: %s. Anything stored in them, like contacts, flows or the event queue, cannot be saved or read.', 'groundhogg' ), implode( ', ', $missing ) ),
				__( 'Run the installation again to create them. If they still do not appear, the database user may not have permission to create tables.', 'groundhogg' )
			), [
				admin_page_url( 'gh_tools', [ 'tab' => 'system' ] ) => __( 'Run the installation', 'groundhogg' ),
			] );
		}

		$pending = array_filter( $this->get_updaters(), function ( Updater $updater ) {
			return ! empty( $updater->get_updates_to_do() );
		} );

		if ( ! empty( $pending ) ) {

			$actions = [];

			foreach ( $pending as $updater ) {
				/* translators: %s: the plugin or extension name */
				$actions[ $updater->get_do_updates_url( [ 'page' => 'gh_tools', 'tab' => 'system' ] ) ] = sprintf( __( 'Update %s', 'groundhogg' ), $updater->get_display_name() );
			}

			return $this->result( 'database', 'critical', __( 'Database updates are waiting to be run', 'groundhogg' ), $this->paragraphs(
				/* translators: %s: list of plugin or extension names */
				sprintf( __( '%s need a database update. Some features may not work correctly until it is run.', 'groundhogg' ), andList( array_map( function ( Updater $updater ) {
					return $updater->get_display_name();
				}, $pending ) ) ),
				__( 'Consider backing up your site before updating.', 'groundhogg' )
			), $actions );
		}

		return $this->result( 'database', 'good', __( 'The database is up to date', 'groundhogg' ), $this->paragraphs(
			__( 'All database tables exist and no updates are waiting to be run.', 'groundhogg' )
		) );
	}

	/**
	 * Plain permalinks break tracking links, the preferences center and form submissions
	 *
	 * @return array
	 */
	public function test_permalinks() {

		if ( get_option( 'permalink_structure' ) ) {
			return $this->result( 'permalinks', 'good', __( 'Permalinks work with tracking and the preferences center', 'groundhogg' ), $this->paragraphs(
				__( 'Your permalink structure supports email tracking links, the preferences center and other pages that are generated for contacts.', 'groundhogg' )
			) );
		}

		return $this->result( 'permalinks', 'critical', __( 'Plain permalinks break tracking and the preferences center', 'groundhogg' ), $this->paragraphs(
			__( 'Your permalink structure is set to Plain. Email tracking links, unsubscribe links, the preferences center and other pages generated for contacts will not load.', 'groundhogg' ),
			__( 'Choose any other permalink structure. Post name is recommended.', 'groundhogg' )
		), [
			admin_url( 'options-permalink.php' ) => __( 'Change permalinks', 'groundhogg' ),
		] );
	}

	/**
	 * Safe mode turns off other plugins for whoever turned it on, it's easy to forget about
	 *
	 * @return array
	 */
	public function test_safe_mode() {

		// read the option directly, the safe mode functions are only there when its mu-plugin is installed
		if ( get_option( 'gh_safe_mode_enabled' ) != 1 ) {
			return $this->result( 'safe_mode', 'good', __( 'Safe mode is off', 'groundhogg' ), $this->paragraphs(
				__( 'All of your plugins are running normally.', 'groundhogg' )
			) );
		}

		return $this->result( 'safe_mode', 'recommended', __( 'Safe mode is on', 'groundhogg' ), $this->paragraphs(
			/* translators: %s: the plugin/brand name */
			sprintf( __( 'Safe mode turns off plugins that are not related to %s for the person who turned it on, to help find conflicts. Turn it off when you are done troubleshooting.', 'groundhogg' ), white_labeled_name() )
		), [
			admin_page_url( 'gh_tools', [ 'tab' => 'system' ] ) => __( 'Turn off safe mode', 'groundhogg' ),
		] );
	}

	/**
	 * A bounce inbox is set up but PHP can't read it
	 *
	 * @return array
	 */
	public function test_bounces() {

		if ( function_exists( 'imap_open' ) ) {
			return $this->result( 'bounces', 'good', __( 'Bounces can be checked', 'groundhogg' ), $this->paragraphs(
				__( 'The PHP IMAP extension is installed, so the bounce inbox can be checked for bounced emails.', 'groundhogg' )
			) );
		}

		return $this->result( 'bounces', 'recommended', __( 'Bounces cannot be checked', 'groundhogg' ), $this->paragraphs(
			__( 'A bounce inbox is set up, but the PHP IMAP extension is not installed, so it cannot be read. Contacts whose emails bounce will keep being sent to, which hurts your sender reputation.', 'groundhogg' ),
			__( 'Ask your host to enable the PHP IMAP extension.', 'groundhogg' )
		), [
			admin_page_url( 'gh_settings', [ 'tab' => 'email' ] ) => __( 'Bounce settings', 'groundhogg' ),
		] );
	}

	/**
	 * Expired licenses, and installed extensions without one
	 *
	 * @return array
	 */
	public function test_licenses() {

		$manage = [ admin_page_url( 'gh_settings', [ 'tab' => 'extensions' ] ) => __( 'Manage licenses', 'groundhogg' ) ];

		$unlicensed = License_Manager::get_unlicensed_extensions();

		if ( ! empty( $unlicensed ) ) {
			return $this->result( 'licenses', 'recommended', __( 'Some extensions do not have a license', 'groundhogg' ), $this->paragraphs(
				/* translators: %s: list of extension names */
				sprintf( __( '%s do not have an active license, so they will not get updates, including security fixes.', 'groundhogg' ), andList( array_map( function ( Extension $extension ) {
					return $extension->get_display_name();
				}, $unlicensed ) ) )
			), $manage );
		}

		$expired = License_Manager::get_expired_licenses();

		if ( ! empty( $expired ) ) {
			return $this->result( 'licenses', 'recommended', __( 'Some licenses have expired', 'groundhogg' ), $this->paragraphs(
				/* translators: %s: list of license names */
				sprintf( __( '%s expired, so the extensions they cover will not get updates, including security fixes.', 'groundhogg' ), andList( wp_list_pluck( $expired, 'item_name' ) ) )
			), $manage );
		}

		return $this->result( 'licenses', 'good', __( 'Extensions are licensed', 'groundhogg' ), $this->paragraphs(
			__( 'Every installed extension has an active license and will get updates.', 'groundhogg' )
		) );
	}

	/**
	 * Yes or no, translated for display, plain for the copied debug info
	 *
	 * @param bool $value
	 *
	 * @return array
	 */
	protected function yes_no( $value ) {
		return [
			'value' => $value ? __( 'Yes', 'groundhogg' ) : __( 'No', 'groundhogg' ),
			'debug' => $value ? 'yes' : 'no',
		];
	}

	/**
	 * How long ago an option's timestamp was
	 *
	 * @param string $option
	 *
	 * @return array
	 */
	protected function last_ping( string $option ) {
		$time = absint( get_option( $option ) );

		return [
			/* translators: %s: how long ago, e.g. "3 mins" */
			'value' => $time ? sprintf( __( '%s ago', 'groundhogg' ), human_time_diff( $time ) ) : __( 'Never', 'groundhogg' ),
			'debug' => $time ? gmdate( 'Y-m-d H:i:s', $time ) . ' UTC' : 'never',
		];
	}

	/**
	 * Add the configuration to the Info tab
	 *
	 * @param array $info
	 *
	 * @return array
	 */
	public function debug_information( $info ) {

		$plugin      = Plugin::instance();
		$preferences = $plugin->preferences;
		$bounces     = $plugin->bounce_checker;

		$extensions = array_map( function ( Extension $extension ) {
			return $extension->get_display_name() . ' ' . $extension->get_version();
		}, Extension::get_extensions() );

		$service_name = function ( $service ) {
			return Groundhogg_Email_Services::get_service_display_name( $service ) ?: $service;
		};

		$waiting = new Table_Query( 'event_queue' );
		$waiting->where()->equals( 'status', Event::WAITING );

		$overdue = new Table_Query( 'event_queue' );
		$overdue->where()->in( 'status', [ Event::WAITING, Event::IN_PROGRESS ] )->lessThan( 'time', time() - self::OVERDUE_EVENTS_AFTER );

		$failed = new Table_Query( 'events' );
		$failed->where()->equals( 'status', Event::FAILED )->greaterThan( 'time', time() - DAY_IN_SECONDS );

		$tasks = new Table_Query( 'background_tasks' );
		$tasks->where()->in( 'status', [ 'pending', 'in_progress' ] );

		$next_run   = wp_next_scheduled( Event_Queue::WP_CRON_HOOK );
		$uploads    = files()->get_base_uploads_dir();
		$avg_time   = floatval( get_option( 'gh_average_execution_time', 0 ) );
		$wp_mail_in = extrapolate_wp_mail_plugin();

		$fields = [
			'version'             => [
				'label' => __( 'Version', 'groundhogg' ),
				'value' => GROUNDHOGG_VERSION,
			],
			'extensions'          => [
				'label' => __( 'Extensions', 'groundhogg' ),
				'value' => empty( $extensions ) ? __( 'None', 'groundhogg' ) : implode( ', ', $extensions ),
				'debug' => empty( $extensions ) ? 'none' : implode( ', ', $extensions ),
			],
			'licenses'            => [
				'label' => __( 'Licenses', 'groundhogg' ),
				/* translators: 1: number of licenses, 2: number of expired licenses */
				'value' => sprintf( __( '%1$d, %2$d expired', 'groundhogg' ), License_Manager::get_num_licenses(), count( License_Manager::get_expired_licenses() ) ),
				'debug' => sprintf( '%d, %d expired', License_Manager::get_num_licenses(), count( License_Manager::get_expired_licenses() ) ),
			],
			'guided_setup'        => [ 'label' => __( 'Guided setup finished', 'groundhogg' ) ] + $this->yes_no( guided_setup_finished() ),
			'safe_mode'           => [ 'label' => __( 'Safe mode', 'groundhogg' ) ] + $this->yes_no( get_option( 'gh_safe_mode_enabled' ) == 1 ),
			'staging'             => [ 'label' => __( 'Staging site detected', 'groundhogg' ) ] + $this->yes_no( is_staging_environment() ),
			'email_in_staging'    => [ 'label' => __( 'Email allowed in staging', 'groundhogg' ) ] + $this->yes_no( is_option_enabled( 'gh_allow_email_in_staging' ) ),
			'wordpress_service'   => [
				'label' => __( 'WordPress email service', 'groundhogg' ),
				'value' => $service_name( Groundhogg_Email_Services::get_wordpress_service() ),
				'debug' => Groundhogg_Email_Services::get_wordpress_service(),
			],
			'transactional'       => [
				'label' => __( 'Transactional email service', 'groundhogg' ),
				'value' => $service_name( Groundhogg_Email_Services::get_transactional_service() ),
				'debug' => Groundhogg_Email_Services::get_transactional_service(),
			],
			'marketing'           => [
				'label' => __( 'Marketing email service', 'groundhogg' ),
				'value' => $service_name( Groundhogg_Email_Services::get_marketing_service() ),
				'debug' => Groundhogg_Email_Services::get_marketing_service(),
			],
			'wp_mail'             => [
				'label' => __( 'wp_mail() defined in', 'groundhogg' ),
				'value' => $wp_mail_in ?: __( 'Unknown', 'groundhogg' ),
				'debug' => $wp_mail_in ?: 'unknown',
			],
			'default_from'        => [
				'label'   => __( 'Default from', 'groundhogg' ),
				'value'   => sprintf( '%s <%s>', get_default_from_name(), get_default_from_email() ),
				'private' => true,
			],
			'email_logging'       => [ 'label' => __( 'Email logging', 'groundhogg' ) ] + $this->yes_no( Email_Logger::is_enabled() ),
			'bounce_inbox'        => [ 'label' => __( 'Bounce inbox set up', 'groundhogg' ) ] + $this->yes_no( $bounces->get_bounce_inbox() && $bounces->get_bounce_inbox_pw() ),
			'bounce_server'       => [
				'label'   => __( 'Bounce mail server', 'groundhogg' ),
				'value'   => $bounces->get_mail_server() . ':' . $bounces->get_port(),
				'private' => true,
			],
			'imap'                => [ 'label' => __( 'PHP IMAP extension', 'groundhogg' ) ] + $this->yes_no( function_exists( 'imap_open' ) ),
			'confirmation_strict' => [ 'label' => __( 'Confirmation required', 'groundhogg' ) ] + $this->yes_no( $preferences->is_confirmation_strict() ),
			'grace_period'        => [
				'label' => __( 'Confirmation grace period (days)', 'groundhogg' ),
				'value' => $preferences->get_grace_period(),
			],
			'gdpr'                => [ 'label' => __( 'GDPR features', 'groundhogg' ) ] + $this->yes_no( $preferences->is_gdpr_enabled() ),
			'gdpr_strict'         => [ 'label' => __( 'GDPR strict', 'groundhogg' ) ] + $this->yes_no( $preferences->is_gdpr_strict() ),
			'disable_wp_cron'     => [ 'label' => 'DISABLE_WP_CRON' ] + $this->yes_no( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ),
			'wp_cron_last_ping'   => [ 'label' => __( 'wp-cron.php last ran', 'groundhogg' ) ] + $this->last_ping( 'wp_cron_last_ping' ),
			'gh_cron_installed'   => [ 'label' => __( 'gh-cron.php installed', 'groundhogg' ) ] + $this->yes_no( gh_cron_installed() ),
			'gh_cron_last_ping'   => [ 'label' => __( 'gh-cron.php last ran', 'groundhogg' ) ] + $this->last_ping( 'gh_cron_last_ping' ),
			'queue_scheduled'     => [
				'label' => __( 'Queue next scheduled in WordPress cron', 'groundhogg' ),
				'value' => $next_run ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next_run ) : __( 'Not scheduled', 'groundhogg' ),
				'debug' => $next_run ? gmdate( 'Y-m-d H:i:s', $next_run ) . ' UTC' : 'not scheduled',
			],
			'queue_time'          => [
				'label' => __( 'Average queue run time (seconds)', 'groundhogg' ),
				'value' => number_format_i18n( $avg_time, 2 ),
				'debug' => round( $avg_time, 2 ),
			],
			'waiting_events'      => [
				'label' => __( 'Waiting events', 'groundhogg' ),
				'value' => number_format_i18n( $waiting->count() ),
			],
			'overdue_events'      => [
				'label' => __( 'Overdue events', 'groundhogg' ),
				'value' => number_format_i18n( $overdue->count() ),
			],
			'failed_events'       => [
				'label' => __( 'Failed events (last 24 hours)', 'groundhogg' ),
				'value' => number_format_i18n( $failed->count() ),
			],
			'background_tasks'    => [
				'label' => __( 'Unfinished background tasks', 'groundhogg' ),
				'value' => number_format_i18n( $tasks->count() ),
			],
			'contacts'            => [
				'label' => __( 'Contacts', 'groundhogg' ),
				'value' => number_format_i18n( get_db( 'contacts' )->count() ),
			],
			'active_flows'        => [
				'label' => __( 'Active flows', 'groundhogg' ),
				'value' => number_format_i18n( get_db( 'funnels' )->count( [ 'status' => 'active' ] ) ),
			],
			'uploads_dir'         => [
				'label' => __( 'Uploads directory', 'groundhogg' ),
				'value' => $uploads,
			],
			'uploads_writable'    => [ 'label' => __( 'Uploads directory writable', 'groundhogg' ) ] + $this->yes_no( wp_is_writable( $uploads ) ),
		];

		/**
		 * Filter the fields added to Site Health › Info
		 *
		 * @param array $fields field id => [ label, value, debug, private ], see the debug_information filter
		 */
		$fields = apply_filters( 'groundhogg/site_health/info_fields', $fields );

		$info['groundhogg'] = [
			'label'  => white_labeled_name(),
			/* translators: %s: the plugin/brand name */
			'description' => sprintf( __( 'How %s is set up to send email and run automation.', 'groundhogg' ), white_labeled_name() ),
			'fields' => $fields,
		];

		$missing = $this->get_missing_tables();
		$tables  = [];

		foreach ( $plugin->dbs->get_dbs() as $db ) {

			if ( ! $db ) {
				continue;
			}

			$table     = $db->get_table_name();
			$installed = ! in_array( $table, $missing, true );
			$version   = get_option( $table . '_db_version' );

			$tables[ $table ] = [
				'label' => $table,
				/* translators: %s: database table version */
				'value' => $installed ? sprintf( __( 'Version %s', 'groundhogg' ), $version ?: '?' ) : __( 'Missing', 'groundhogg' ),
				'debug' => $installed ? 'version ' . ( $version ?: '?' ) : 'missing',
			];
		}

		$info['groundhogg-database'] = [
			/* translators: %s: the plugin/brand name */
			'label'  => sprintf( __( '%s database tables', 'groundhogg' ), white_labeled_name() ),
			'fields' => $tables,
		];

		return $info;
	}
}
