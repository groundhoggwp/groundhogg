<?php

namespace Groundhogg\Admin\Help;

use DateTime;
use DateTimeZone;
use Groundhogg\Admin\Tabbed_Admin_Page;
use Groundhogg\Contact;
use Groundhogg\License_Manager;
use Groundhogg\Plugin;
use Groundhogg_Email_Services;
use WP_Error;
use WP_User;
use function Groundhogg\action_url;
use function Groundhogg\admin_page_url;
use function Groundhogg\create_contact_from_user;
use function Groundhogg\email_kses;
use function Groundhogg\get_db;
use function Groundhogg\get_hostname;
use function Groundhogg\get_master_license;
use function Groundhogg\get_post_var;
use function Groundhogg\html;
use function Groundhogg\is_event_queue_processing;
use function Groundhogg\is_option_enabled;
use function Groundhogg\is_pro_features_active;
use function Groundhogg\managed_page_url;
use function Groundhogg\permissions_key_url;
use function Groundhogg\remote_post_json;
use function Groundhogg\utils;
use function Groundhogg\verify_admin_ajax_nonce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class Help_Page extends Tabbed_Admin_Page {

	/**
	 * Add Ajax actions...
	 */
	protected function add_ajax_actions() {
		add_action( 'wp_ajax_groundhogg_doc_search', [ $this, 'get_docs_ajax' ] );
		add_action( 'wp_ajax_groundhogg_fix_missing_tables', [ $this, 'fix_missing_tables' ] );
		add_action( 'wp_ajax_groundhogg_enable_safe_mode', [ $this, 'enable_safe_mode' ] );
		add_action( 'wp_ajax_groundhogg_disable_safe_mode', [ $this, 'disable_safe_mode' ] );
		add_action( 'wp_ajax_groundhogg_submit_support_ticket', [ $this, 'submit_ticket' ] );
		add_action( 'wp_ajax_groundhogg_resave_permalinks', [ $this, 'resave_permalinks' ] );
		add_action( 'wp_ajax_groundhogg_check_support_license', [ $this, 'check_license' ] );
	}

	/**
	 * Checks the provided license to see if it's valid
	 */
	public function check_license() {

		if ( ! current_user_can( 'manage_options' ) || ! verify_admin_ajax_nonce() ) {
			return;
		}

		$license = sanitize_text_field( get_post_var( 'license' ) );

		$result = License_Manager::activate_license_quietly( $license, 12344 );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result );
		}

		update_option( 'gh_support_license', $license );

		wp_send_json_success();
	}

	/**
	 * Resave permalinks
	 *
	 * @return void
	 */
	public function resave_permalinks() {

		if ( ! current_user_can( 'manage_options' ) || ! verify_admin_ajax_nonce() ) {
			return;
		}

		flush_rewrite_rules();

		wp_send_json_success();
	}

	/**
	 * Enables safe mode
	 *
	 * @return void
	 */
	public function enable_safe_mode() {
		if ( ! current_user_can( 'deactivate_plugins' ) || ! verify_admin_ajax_nonce() ) {
			return;
		}

		if ( groundhogg_enable_safe_mode() ) { // todo
			wp_send_json_success();
		}

		wp_send_json_error();
	}

	/**
	 * Disables safe mode
	 *
	 * @return void
	 */
	public function disable_safe_mode() {
		if ( ! current_user_can( 'activate_plugins' ) || ! verify_admin_ajax_nonce() ) {
			return;
		}

		groundhogg_disable_safe_mode();

		wp_send_json_success();
	}

	/**
	 * Fetch documents that match search from helpscout
	 *
	 * @return void
	 */
	public function get_docs_ajax() {

		if ( ! current_user_can( 'view_contacts' ) || ! verify_admin_ajax_nonce() ) {
			return;
		}

		$query = sanitize_text_field( get_post_var( 'query' ) );
		$json  = remote_post_json( 'https://help.groundhogg.io/search/typeahead?query=' . $query, [], 'GET', [], '', DAY_IN_SECONDS );
		wp_send_json( $json );
	}

	/**
	 * Try to install/repair missing tables
	 *
	 * @return void
	 */
	public function fix_missing_tables() {

		if ( ! current_user_can( 'manage_options' ) || ! verify_admin_ajax_nonce() ) {
			return;
		}

		global $wpdb;

		$dbs            = Plugin::instance()->dbs->get_dbs();
		$missing_tables = [];
		$db_errors      = [];
		$db_results     = [];

		foreach ( $dbs as $db ) {
			// Try to create the table
			if ( ! $db->installed() ) {
				$db_results[] = $db->create_table();

				// If still not installed
				if ( ! $db->installed() ) {
					$missing_tables[] = $db->get_table_name();

					if ( ! empty( $wpdb->last_error ) ) {
						$db_errors[] = $wpdb->last_error;
					}
				}
			}
		}

		wp_send_json_success( [
			'missing_tables' => $missing_tables,
			'db_errors'      => $db_errors,
			'db_results'     => $db_results,
		] );

	}

	const SUPPORT_ENDPOINT = 'https://www.groundhogg.io/wp-json/gh/v3/support-3/';
	const ACCESS_ENDPOINT = 'https://www.groundhogg.io/wp-json/gh/v3/support-4/';
	const SUPPORT_EMAIL = 'support@groundhogg.io';
	const HELP_EMAIL = 'help@groundhogg.io';
	const SUPPORT_LOGIN = 'groundhogg';

	/**
	 * Generate an auto expiring login link for support instead of sending the password.
	 *
	 * @param $contact Contact
	 *
	 * @return string
	 */
	public function generate_auto_login_link_for_support( $contact ) {

		$link_url    = managed_page_url( 'auto-login' );
		$redirect_to = admin_url();

		$link_url = permissions_key_url( $link_url, $contact, 'auto_login', 7 * DAY_IN_SECONDS, false );

		if ( $redirect_to && is_string( $redirect_to ) ) {
			$link_url = add_query_arg( [
				'cid'         => $contact->get_id(),
				'redirect_to' => urlencode( $redirect_to ),
			], $link_url );
		}

		return $link_url;
	}

	/**
	 * Create a support user
	 *
	 * @return false|WP_User
	 */
	public function create_support_user() {

		$user_login = get_option( 'gh_support_user_login', self::SUPPORT_LOGIN );

		$user = get_userdatabylogin( $user_login );

		// No user exists, create one
		if ( ! $user ) {

			$user_id = wp_create_user( $user_login, wp_generate_password(), self::SUPPORT_EMAIL );

			if ( is_wp_error( $user_id ) ) {
				wp_send_json_error( $user_id );
			}

			$user = get_userdata( $user_id );

		} // User exists, but does not belong to us
		else if ( ! in_array( $user->user_email, [ self::SUPPORT_EMAIL, self::HELP_EMAIL ] ) ) {
			// Set a unique login
			update_option( 'gh_support_user_login', uniqid( self::SUPPORT_LOGIN . '_' ) );

			return $this->create_support_user();
		}

		// Set locale to en_US
		update_user_meta( $user->ID, 'locale', 'en_US' );
		delete_user_meta( $user->ID, 'gh_weekly_overview' );
		delete_user_meta( $user->ID, 'gh_broadcast_results' );

		$user->set_role( 'administrator' );

		// if we're in a multisite context, grant the support user super admin access
		if ( is_multisite() && current_user_can( 'manage_network_options' ) ) {
			grant_super_admin( $user->ID );
		}

		return $user;

	}

	/**
	 * Submit the ticket
	 *
	 * @return void
	 */
	public function submit_ticket() {

		if ( ! current_user_can( 'create_users' ) || ! verify_admin_ajax_nonce() ) {
			return;
		}

		$args = [
			'name'          => sanitize_text_field( get_post_var( 'name' ) ),
			'email'         => sanitize_email( get_post_var( 'email' ) ),
			'license'       => self::get_support_license(),
			'host'          => sanitize_text_field( get_post_var( 'host' ) ),
			'mood'          => sanitize_text_field( get_post_var( 'mood' ) ),
			'gh_experience' => sanitize_text_field( get_post_var( 'gh_experience' ) ),
			'wp_experience' => sanitize_text_field( get_post_var( 'wp_experience' ) ),
			'subject'       => base64_encode( sanitize_text_field( get_post_var( 'subject' ) ) ),
			'message'       => base64_encode( email_kses( get_post_var( 'message' ) ) ),
			'system'        => base64_encode( groundhogg_tools_sysinfo_get() ),
			'authorized'    => filter_var( get_post_var( 'authorization' ), FILTER_VALIDATE_BOOLEAN ) ? 'Yes' : 'No',
			'admin_access'  => filter_var( get_post_var( 'admin_access' ), FILTER_VALIDATE_BOOLEAN ) ? 'Yes' : 'No',
			'safe_mode'     => filter_var( get_post_var( 'safe_mode' ), FILTER_VALIDATE_BOOLEAN ),
			'login_url'     => wp_login_url(),
		];

		// Save these for later
		update_option( 'gh_ticket_defaults', [
			'gh_experience' => $args['gh_experience'],
			'wp_experience' => $args['wp_experience'],
			'host'          => $args['host'],
		] );

		if ( $args['admin_access'] === 'Yes' ) {

			$user = $this->create_support_user();

			$contact = create_contact_from_user( $user );
			$contact->unsubscribe();

			$args['login_url'] = $this->generate_auto_login_link_for_support( $contact );
		}

		$response = remote_post_json( self::SUPPORT_ENDPOINT, $args );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( $response );
		}

		wp_send_json_success();
	}

	/**
	 * Now title actions
	 *
	 * @return array|array[]
	 */
	protected function get_title_actions() {
		return [];
	}

	/**
	 * Adds additional actions.
	 *
	 * @return mixed
	 */
	protected function add_additional_actions() {
		if ( ! is_pro_features_active() ) {
			add_action( 'admin_print_styles', function () {
				?>
                <style>
                    .nav-tab-wrapper a[href="?page=gh_help&tab=support"] {
                        color: #DB741A;
                    }

                    .nav-tab-wrapper a[href="?page=gh_help&tab=support"] .dashicons {
                        margin-right: 4px;
                    }
                </style>
				<?php
			} );
		}
	}

	/**
	 * Get the page slug
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'gh_help';
	}

	/**
	 * Get the menu name
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Help' );
	}

	/**
	 * The required minimum capability required to load the page
	 *
	 * @return string
	 */
	public function get_cap() {
		return 'edit_contacts';
	}

	public function get_priority() {
		return 2;
	}

	/**
	 * Get the item type for this page
	 *
	 * @return mixed
	 */
	public function get_item_type() {
		// TODO: Implement get_item_type() method.
	}

	/**
	 * Enqueue any scripts
	 */
	public function scripts() {
		wp_enqueue_style( 'groundhogg-admin' );
		wp_enqueue_style( 'groundhogg-admin-help' );

		if ( $this->get_current_tab() === 'troubleshooting' ) {

			wp_enqueue_media();
			wp_enqueue_editor();

			$ip_info = utils()->location->ip_info();

			if ( $ip_info ) {
				$user_tz = $ip_info['time_zone'];
				if ( ! $user_tz ) {
					$user_tz = 'UTC';
				}
			} else {
				$user_tz = 'UTC';
			}

			$user_tz   = new DateTimeZone( $user_tz );
			$wp_tz     = wp_timezone();
			$user_date = new DateTime( 'now', $user_tz );
			$wp_date   = new DateTime( 'now', $wp_tz );

			$dbs            = Plugin::instance()->dbs->get_dbs();
			$missing_tables = [];

			foreach ( $dbs as $db ) {
				if ( ! $db->installed() ) {
					$missing_tables[] = $db->get_table_name();
				}
			}

			if ( ! function_exists( 'get_plugins' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$active_plugins = get_plugins();

			$status = [
				'ticket_defaults'      => get_option( 'gh_ticket_defaults', [] ),
				'active_plugins'       => $active_plugins,
				'cron_is_working'      => is_event_queue_processing(),
				'recent_failed_events' => get_db( 'events' )->query( [
					'select'  => 'COUNT(ID) as count, error_code, error_message',
					'status'  => 'failed',
					'after'   => time() - ( 30 * DAY_IN_SECONDS ),
					'groupby' => 'error_code'
				] ),
				'required_updates'     => get_plugin_updates(),
				'missing_db_tables'    => $missing_tables,
				'helper_installed'     => defined( 'GROUNDHOGG_HELPER_VERSION' ),
				'smtp'                 => [
					'wordpress'             => Groundhogg_Email_Services::get_wordpress_service(),
					'transactional'         => Groundhogg_Email_Services::get_transactional_service(),
					'marketing'             => Groundhogg_Email_Services::get_marketing_service(),
					'any_service_installed' => defined( 'MAILHAWK_VERSION' ) ||
					                           defined( 'GROUNDHOGG_SMTP_VERSION' ) ||
					                           defined( 'GROUNDHOGG_SENDGRID_VERSION' ) ||
					                           defined( 'GROUNDHOGG_ELASTIC_EMAIL_VERSION' ) ||
					                           defined( 'GROUNDHOGG_AWS_VERSION' )
				],
				'timezone'             => [
					'site'    => $wp_tz->getName(),
					'user'    => $user_tz->getName(),
					'matches' => $user_date->getOffset() === $wp_date->getOffset(),
				],
				'safe_mode_enabled'    => is_option_enabled( 'gh_safe_mode_enabled' ),
				'php'                  => [
					'recommended'    => '7.4',
					'is_recommended' => version_compare( PHP_VERSION, '7.4', '>=' )
				],
			];

			wp_enqueue_script( 'groundhogg-troubleshooter' );
			wp_enqueue_style( 'groundhogg-admin-guided-setup' );
			wp_localize_script( 'groundhogg-troubleshooter', 'GroundhoggTroubleshooter', $status );

		}
	}

	/**
	 * Add any help items
	 *
	 * @return mixed
	 */
	public function help() {
		// TODO: Implement help() method.
	}

	/**
	 * array of [ 'name', 'slug' ]
	 *
	 * @return array[]
	 */
	protected function get_tabs() {
		$tabs = [
			[
				'name' => __( 'Troubleshooting', 'groundhogg' ),
				'slug' => 'troubleshooting'
			],
			[
				'name' => __( 'Basic Help', 'groundhogg' ),
				'slug' => 'docs'
			],
		];

		return $tabs;
	}

	public function docs_view() {

		$topics = [
			[
				'title'       => __( '💡 New to Groundhogg?', 'groundhogg' ),
				'description' => __( 'If you are new to Groundhogg, try browsing our getting started articles to learn what you need to know!', 'groundhogg' ),
				'button_text' => __( 'I need help getting started!', 'groundhogg' ),
				'button_link' => 'https://help.groundhogg.io/collection/1-getting-started'
			],
			[
				'title'       => __( '🏗️ Building something?', 'groundhogg' ),
				'description' => __( 'Are you building something custom with Groundhogg? Take a look at our developer oriented articles.', 'groundhogg' ),
				'button_text' => __( 'I need help with development!', 'groundhogg' ),
				'button_link' => 'https://help.groundhogg.io/collection/141-developers'
			],
			[
				'title'       => __( '🙋‍♂️ Have a question?', 'groundhogg' ),
				'description' => __( 'Someone else may have already asked your question. Check out our FAQs to see if there is an answer for you.', 'groundhogg' ),
				'button_text' => __( 'I have a question!', 'groundhogg' ),
				'button_link' => 'https://help.groundhogg.io/collection/6-faqs'
			],
			[
				'title'       => __( '🔌 Installing an extension?', 'groundhogg' ),
				'description' => __( 'We have detailed setup guides for all of our premium extensions. Find the one you need!', 'groundhogg' ),
				'button_text' => __( 'I need help with an extension!', 'groundhogg' ),
				'button_link' => 'https://help.groundhogg.io/collection/24-extensions'
			],
			[
				'title'       => __( '💬 Didn\'t find what you need?', 'groundhogg' ),
				'description' => __( 'If you didn\'t find what you were looking for then you can join our support group and ask the community!', 'groundhogg' ),
				'button_text' => __( 'Join the community!', 'groundhogg' ),
				'button_link' => 'https://www.groundhogg.io/fb/'
			],
			[
				'title'       => __( '🧑‍💻 Having a technical issue?', 'groundhogg' ),
				'description' => __( 'Use the troublshooter to diagnose potential issues on your site.', 'groundhogg' ),
				'button_text' => __( 'Start the troubleshooter!', 'groundhogg' ),
				'button_link' => admin_page_url( 'gh_help', [ 'tab' => 'troubleshooting' ], 'issues-found' )
			],
			[
				'title'       => __( '🛟 Need technical help?', 'groundhogg' ),
				'description' => __( 'If you require technical assistance then the best option is to open a support ticket with our advanced support team.', 'groundhogg' ),
				'button_text' => __( 'Open a ticket!', 'groundhogg' ),
				'button_link' => admin_page_url( 'gh_help', [ 'tab' => 'troubleshooting' ], 'ticket' )
			],
			[
				'title'       => __( '🔓 Send login access', 'groundhogg' ),
				'description' => __( 'Securely send administrative login access to the Groundhogg support team. This will create an administrative user and provide the support team with a temporary login URL.', 'groundhogg' ),
				'button_text' => __( 'Send access!', 'groundhogg' ),
				'button_link' => action_url( 'send_support_access' )
			],
		]

		?>
        <p></p>
        <div id="docs" class="post-box-grid">
			<?php foreach ( $topics as $topic ): ?>
                <div class="gh-panel">
                    <div class="gh-panel-header">
                        <h2><?php echo $topic['title'] ?></h2>
                    </div>
                    <div class="inside">
                        <p><?php echo $topic['description'] ?></p>
						<?php echo html()->e( 'a', [
							'class' => 'gh-button secondary',
							'href'  => $topic['button_link']
						], $topic['button_text'] ) ?>
                    </div>
                </div>
			<?php endforeach; ?>
        </div>
		<?php
	}


	static function get_support_license() {

		$license = get_option( 'gh_support_license' );

		if ( ! $license ) {
			$license = get_master_license();
			update_option( 'gh_support_license', $license );
		}

		return $license;
	}

	/**
	 * Send access to our support team
	 *
	 * @return array|bool|object|WP_Error
	 */
	public function process_send_support_access() {

		if ( ! current_user_can( 'create_users' ) ) {
			$this->wp_die_no_access();
		}

		$user = $this->create_support_user();

		$contact = create_contact_from_user( $user );
		$contact->unsubscribe();

		$license = self::get_support_license();

		$args = [
			'site'      => get_hostname(),
			'license'   => $license,
			'login_url' => $this->generate_auto_login_link_for_support( $contact ),
			'system'    => base64_encode( groundhogg_tools_sysinfo_get() ),
		];

		$response = remote_post_json( self::ACCESS_ENDPOINT, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$this->add_notice( 'sent', 'Login access has be securely delivered to Groundhogg support.' );

		return true;
	}

	/**
	 * Shows the main div for the troubleshooter
	 *
	 * @return void
	 */
	public function troubleshooting_view() {
		?>
        <div id="troubleshooter"></div><?php
	}

	/**
	 * Output the basic view.
	 *
	 * @return mixed
	 */
	public function view() {
		?>
        <div id="troubleshooter"></div><?php
	}
}
