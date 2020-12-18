<?php

namespace Groundhogg\Admin\Settings;

use Groundhogg\Admin\Admin_Page;
use Groundhogg\Extension;
use Groundhogg\Mailhawk;
use Groundhogg\SendWp;
use Groundhogg_Email_Services;
use function Groundhogg\get_array_var;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use function Groundhogg\is_white_labeled;
use Groundhogg\License_Manager;
use Groundhogg\Plugin;
use function Groundhogg\isset_not_empty;
use function Groundhogg\white_labeled_name;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Settings
 *
 * This  is your fairly typical settigns page.
 * It's a BIT of a mess, but I digress.
 *
 * @since       File available since Release 0.1
 * @subpackage  Admin/Settings
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Admin
 */
class Settings_Page extends Admin_Page {

	// UNUSED FUNCTIONS
	protected function add_ajax_actions() {
	}

	public function help() {
	}

	public function scripts() {
		wp_enqueue_style( 'groundhogg-admin' );
		wp_enqueue_style( 'groundhogg-admin-extensions' );
	}

	/**
	 * Settings_Page constructor.
	 */
	public function __construct() {
		$this->add_additional_actions();
		parent::__construct();
	}

	protected function add_additional_actions() {
		add_action( 'admin_init', array( $this, 'init_defaults' ) );
		add_action( 'admin_init', array( $this, 'register_sections' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( "groundhogg/admin/settings/api_tab/after_form", [ $this, 'api_keys_table' ] );
		add_action( "groundhogg/admin/settings/extensions/after_submit", [ $this, 'show_extensions' ] );
	}

	public function get_slug() {
		return 'gh_settings';
	}

	public function get_name() {
		return _x( 'Settings', 'page_title', 'groundhogg' );
	}

	public function get_title() {
		return sprintf( __( "%s Settings", 'groundhogg' ), white_labeled_name() );
	}

	public function get_cap() {
		return 'manage_options';
	}

	public function get_item_type() {
		return null;
	}

	public function get_priority() {
		return 99;
	}

	protected function get_title_actions() {
		return [];
	}

	/**
	 * A list of the settings tabs
	 *
	 * @var array
	 */
	private $tabs;

	/**
	 * A list of tab sections
	 *
	 * @var array
	 */
	private $sections;

	/**
	 * A list of all the settings
	 *
	 * @var array
	 */
	private $settings;


	/**
	 * Init the default settings & sections.
	 */
	public function init_defaults() {
		$this->tabs     = $this->get_default_tabs();
		$this->sections = $this->get_default_sections();
		$this->settings = $this->get_default_settings();

		do_action( 'groundhogg/admin/settings/init_defaults', $this );
	}

	public function screen_options() {
	}

	/**
	 * display the API keys table
	 */
	public function api_keys_table() {
		$api_keys_table = new API_Keys_Table();
		$api_keys_table->prepare_items();
		?>
        <h3><?php _e( 'API Keys', 'groundhogg' ); ?></h3>
        <div style="max-width: 900px;"><?php
		$api_keys_table->display();
		?></div><?php
	}

	public function show_extensions() {

		$extensions = Extension::get_extensions();

		?>
        <div id="poststuff">
			<?php wp_nonce_field(); ?>
			<?php

			if ( ! empty( $extensions ) ) :

				if ( ! is_white_labeled() ):

					$verify_license_url = Plugin::instance()->bulk_jobs->check_licenses->get_start_url();

					?>
                    <p><?php printf( __( 'Enter your extension license keys here to receive updates for purchased extensions. If your license key has expired, <a href="https://groundhogg.io/account/">please renew your license</a>. If you have recently renewed your license <a href="%s">click here to re-verify it</a>.', 'groundhogg' ), $verify_license_url ); ?></p>
				<?php

				endif;

				?>
                <div class="post-box-grid"><?php

				foreach ( $extensions as $extension ):
					echo $extension;
				endforeach;

				?></div><?php
			else:
				?>
                <style>
                    .masonry {
                        columns: 1;
                        column-gap: 1.5em;
                    }

                    .postbox {
                        display: inline-block;
                        vertical-align: top;
                    }

                    @media only screen and (max-width: 1023px) and (min-width: 768px) {
                        .masonry {
                            columns: 2;
                        }
                    }

                    @media only screen and (min-width: 1024px) {
                        .masonry {
                            columns: 5;
                        }
                    }
                </style>
                <p><?php _e( 'You have no extensions installed. Want some?', 'groundhogg' ); ?> <a
                            href="https://groundhogg.io/pricing/"><?php _e( 'Get your first extension!', 'groundhogg' ) ?></a>
                </p>
                <div class="extensions">
					<?php include __DIR__ . '/extensions.php'; ?>
                </div>
			<?php endif; ?>
        </div>
		<?php
	}

	/**
	 * When we submit the form but to the page not to options.php
	 */
	public function process_view() {

		if ( get_request_var( 'activate_license' ) ) {

			$licenses = get_request_var( 'license', [] );

			foreach ( $licenses as $item_id => $license ) {

				License_Manager::activate_license( $license, absint( $item_id ) );

			}

		}

	}

	/**
	 * Deactivate a license key
	 */
	public function process_deactivate_license() {

		$item_id = absint( get_request_var( 'extension' ) );
		$license = sanitize_text_field( get_request_var( 'license' ) );

		License_Manager::deactivate_license( $item_id ?: $license );
	}

	/**
	 * Check a license key
	 */
	public function process_check_license() {

		$item_id = absint( get_request_var( 'extension' ) );

		if ( $item_id ) {
			License_Manager::verify_license( $item_id );
		}
	}

	/**
	 * Add the default settings sections
	 */
	public function register_sections() {

		do_action( 'wpgh_settings_pre_register_sections', $this );

		foreach ( $this->sections as $id => $section ) {

			$callback = array();

			if ( key_exists( 'callback', $section ) ) {
				$callback = $section['callback'];
			}

			add_settings_section( 'gh_' . $section['id'], $section['title'], $callback, 'gh_' . $section['tab'] );
		}

	}

	/**
	 * Returns a list of tabs
	 *
	 * @return array
	 */
	private function get_default_tabs() {
		$tabs = [
			'general'   => array(
				'id'    => 'general',
				'title' => _x( 'General', 'settings_tabs', 'groundhogg' )
			),
			'marketing' => array(
				'id'    => 'marketing',
				'title' => _x( 'Compliance', 'settings_tabs', 'groundhogg' )
			),
			'email'     => array(
				'id'    => 'email',
				'title' => _x( 'Email', 'settings_tabs', 'groundhogg' )
			),
			'tags'      => [
				'id'    => 'tags',
				'title' => _x( 'Tags', 'settings_tabs', 'groundhogg' )
			],
			'api_tab'   => array(
				'id'    => 'api_tab',
				'title' => _x( 'API', 'settings_tabs', 'groundhogg' )
			),
			'misc'      => array(
				'id'    => 'misc',
				'title' => _x( 'Misc', 'settings_tabs', 'groundhogg' )
			),
		];

		if ( ! is_white_labeled() || ! is_multisite() || is_main_site() ) {
			$tabs['extensions'] = [
				'id'    => 'extensions',
				'title' => _x( 'Licenses', 'settings_tabs', 'groundhogg' ),
				'cap'   => 'manage_gh_licenses'
			];
		}

		return apply_filters( 'groundhogg/admin/settings/tabs', $tabs );
	}

	/**
	 * Returns a list of all the default sections
	 *
	 * @return array
	 */
	private function get_default_sections() {

		$sections = array(
			'business_info'         => array(
				'id'    => 'business_info',
				'title' => _x( 'Business Settings', 'settings_sections', 'groundhogg' ),
				'tab'   => 'general'
			),
			'general_other'         => array(
				'id'    => 'general_other',
				'title' => _x( 'Other', 'settings_sections', 'groundhogg' ),
				'tab'   => 'general'
			),
			'misc_info'             => array(
				'id'    => 'misc_info',
				'title' => _x( 'Misc Settings', 'settings_sections', 'groundhogg' ),
				'tab'   => 'misc'
			),
			'wp_cron'               => [
				'id'    => 'wp_cron',
				'title' => _x( 'WP Cron', 'settings_sections', 'groundhogg' ),
				'tab'   => 'misc',
			],
			'affiliate'             => array(
				'id'    => 'affiliate',
				'title' => _x( 'Affiliate Section', 'settings_sections', 'groundhogg' ),
				'tab'   => 'misc'
			),
			'captcha'               => array(
				'id'    => 'captcha',
				'title' => _x( 'Google reCAPTCHA', 'settings_sections', 'groundhogg' ),
				'tab'   => 'misc'
			),
			'event_notices'         => [
				'id'    => 'event_notices',
				'title' => _x( 'Event Notices', 'settings_sections', 'groundhogg' ),
				'tab'   => 'misc'
			],
			'compliance'            => array(
				'id'    => 'compliance',
				'title' => _x( 'Compliance', 'settings_sections', 'groundhogg' ),
				'tab'   => 'marketing'
			),
//			'sendwp'            => [
//				'id'       => 'sendwp',
//				'title'    => _x( 'SendWP', 'settings_sections', 'groundhogg' ),
//				'tab'      => 'email',
//				'callback' => [ SendWp::instance(), 'settings_connect_ui' ],
//			],
			'mailhawk'              => [
				'id'       => 'mailhawk',
				'title'    => _x( 'MailHawk', 'settings_sections', 'groundhogg' ),
				'tab'      => 'email',
				'callback' => [ Mailhawk::instance(), 'settings_connect_ui' ],
			],
			'outgoing_email_config' => [
				'id'    => 'outgoing_email_config',
				'title' => _x( 'Outgoing Email', 'settings_sections', 'groundhogg' ),
				'tab'   => 'email'
			],
			'overrides'             => [
				'id'    => 'overrides',
				'title' => _x( 'Overrides', 'settings_sections', 'groundhogg' ),
				'tab'   => 'email'
			],
			'tracking'              => array(
				'id'    => 'tracking',
				'title' => _x( 'Tracking', 'settings_sections', 'groundhogg' ),
				'tab'   => 'email',
			),
			'bounces'               => array(
				'id'       => 'bounces',
				'title'    => _x( 'Email Bounces', 'settings_sections', 'groundhogg' ),
				'tab'      => 'email',
				'callback' => [ Plugin::$instance->bounce_checker, 'test_connection_ui' ],
			),
//			'api_settings'      => array(
//				'id'    => 'api_settings',
//				'title' => _x( 'API Settings', 'settings_sections', 'groundhogg' ),
//				'tab'   => 'api_tab'
//			),
			'optin_status_tags'     => [
				'id'       => 'optin_status_tags',
				'title'    => _x( 'Optin Status Tags', 'settings_sections', 'groundhogg' ),
				'tab'      => 'tags',
				'callback' => [ Plugin::$instance->tag_mapping, 'reset_tags_ui' ],

			],
		);

		if ( defined( 'DISABLE_WP_CRON' ) && ! defined( 'GH_SHOW_DISABLE_WP_CRON_OPTION' ) ) {
			unset( $sections['wp_cron'] );
		}


		return apply_filters( 'groundhogg/admin/settings/sections', $sections );
	}

	private function get_default_settings() {

		$settings = array(
			'gh_business_name'                       => array(
				'id'      => 'gh_business_name',
				'section' => 'business_info',
				'label'   => _x( 'Business Name', 'settings', 'groundhogg' ),
				'desc'    => _x( 'Your business name as it appears in the email footer.', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => array(
					'id'          => 'gh_business_name',
					'name'        => 'gh_business_name',
					'placeholder' => get_bloginfo( 'name' )
				),
			),
			'gh_street_address_1'                    => array(
				'id'      => 'gh_street_address_1',
				'section' => 'business_info',
				'label'   => _x( 'Street Address 1', 'settings', 'groundhogg' ),
				'desc'    => _x( 'As it should appear in your email footer.', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => array(
					'id'          => 'gh_street_address_1',
					'name'        => 'gh_street_address_1',
					'placeholder' => '123 Any St.'
				),
			),
			'gh_street_address_2'                    => array(
				'id'      => 'gh_street_address_2',
				'section' => 'business_info',
				'label'   => _x( 'Street Address 2', 'settings', 'groundhogg' ),
				'desc'    => _x( '(Optional) As it should appear in your email footer.', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => array(
					'id'          => 'gh_street_address_2',
					'name'        => 'gh_street_address_2',
					'placeholder' => __( 'Unit 42' )
				),
			),
			'gh_city'                                => array(
				'id'      => 'gh_city',
				'section' => 'business_info',
				'label'   => __( 'City' ),
				'desc'    => _x( 'As it should appear in your email footer.', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => array(
					'id'          => 'gh_city',
					'name'        => 'gh_city',
					'placeholder' => __( 'Toronto' )
				),
			),
			'gh_zip_or_postal'                       => array(
				'id'      => 'gh_zip_or_postal',
				'section' => 'business_info',
				'label'   => _x( 'Postal/Zip Code', 'settings', 'groundhogg' ),
				'desc'    => _x( 'As it should appear in your email footer.', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => array(
					'id'          => 'gh_zip_or_postal',
					'name'        => 'gh_zip_or_postal',
					'placeholder' => 'A1A 1A1'
				),
			),
			'gh_region'                              => array(
				'id'      => 'gh_region',
				'section' => 'business_info',
				'label'   => _x( 'State/Province/Region', 'settings', 'groundhogg' ),
				'desc'    => _x( 'As it should appear in your email footer.', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => array(
					'id'          => 'gh_region',
					'name'        => 'gh_region',
					'placeholder' => 'Ontario'
				),
			),
			'gh_country'                             => array(
				'id'      => 'gh_country',
				'section' => 'business_info',
				'label'   => __( 'Country' ),
				'desc'    => _x( 'As it should appear in your email footer.', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => array(
					'id'          => 'gh_country',
					'name'        => 'gh_country',
					'placeholder' => 'Canada'
				),
			),
			'gh_phone'                               => array(
				'id'      => 'gh_phone',
				'section' => 'business_info',
				'label'   => __( 'Phone' ),
				'desc'    => _x( 'As it should appear in your email footer.', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => array(
					'type'        => 'tel',
					'id'          => 'gh_phone',
					'name'        => 'gh_phone',
					'placeholder' => '+1 (555) 555-555'
				),
			),
			'gh_primary_user'                        => array(
				'id'      => 'gh_primary_user',
				'section' => 'general_other',
				'label'   => __( 'Primary User', 'groundhogg' ),
				'desc'    => _x( 'The primary user to reference for contact information.', 'settings', 'groundhogg' ),
				'type'    => 'dropdown_owners',
				'atts'    => array(
					'id'   => 'gh_primary_user',
					'name' => 'gh_primary_user',
				),
			),
			'gh_uninstall_on_delete'                 => array(
				'id'      => 'gh_uninstall_on_delete',
				'section' => 'misc_info',
				'label'   => sprintf( _x( 'Delete %s data', 'settings', 'groundhogg' ), white_labeled_name() ),
				'desc'    => _x( 'Delete all information when uninstalling. This cannot be undone.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => array(
					'label' => __( 'Enable' ),
					//keep brackets for backwards compat
					'name'  => 'gh_uninstall_on_delete[]',
					'id'    => 'gh_uninstall_on_delete',
					'value' => 'on',
				),
			),
			'gh_opted_in_stats_collection'           => array(
				'id'      => 'gh_opted_in_stats_collection',
				'section' => 'misc_info',
				'label'   => _x( 'Optin to anonymous usage tracking.', 'settings', 'groundhogg' ),
				'desc'    => sprintf( _x( 'Help us make %s better by providing anonymous usage information about your site.', 'settings', 'groundhogg' ), white_labeled_name() ),
				'type'    => 'checkbox',
				'atts'    => array(
					'label' => __( 'Enable' ),
					//keep brackets for backwards compat
					'name'  => 'gh_opted_in_stats_collection',
					'id'    => 'gh_opted_in_stats_collection',
					'value' => 'on',
				),
			),
			'gh_allow_unrestricted_file_access'      => array(
				'id'      => 'gh_allow_unrestricted_file_access',
				'section' => 'misc_info',
				'label'   => _x( 'Allow unrestricted access to contact file uploads.', 'settings', 'groundhogg' ),
				'desc'    => _x( 'This will allow anyone with a file access link to view uploads regardless of whether they are logged in.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => array(
					'label' => __( 'Allow' ),
					//keep brackets for backwards compat
					'name'  => 'gh_allow_unrestricted_file_access',
					'id'    => 'gh_allow_unrestricted_file_access',
					'value' => 'on',
				),
			),
			'gh_enable_experimental_features'        => array(
				'id'      => 'gh_enable_experimental_features',
				'section' => 'misc_info',
				'label'   => _x( 'Enable experimental features.', 'settings', 'groundhogg' ),
				'desc'    => _x( 'This will enabled experimental features in Groundhogg and various extensions.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => array(
					'label' => __( 'Enable' ),
					//keep brackets for backwards compat
					'name'  => 'gh_enable_experimental_features',
					'id'    => 'gh_enable_experimental_features',
					'value' => 'on',
				),
			),
			'gh_get_beta_versions'                   => array(
				'id'      => 'gh_get_beta_versions',
				'section' => 'misc_info',
				'label'   => _x( 'Get updates for pre-release versions of extensions!', 'settings', 'groundhogg' ),
				'desc'    => _x( 'This will show automatic updates or extensions which may have experimental features.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => array(
					'label' => __( 'Enable' ),
					//keep brackets for backwards compat
					'name'  => 'gh_get_beta_versions',
					'id'    => 'gh_get_beta_versions',
					'value' => 'on',
				),
			),
			'gh_affiliate_id'                        => array(
				'id'      => 'gh_affiliate_id',
				'section' => 'affiliate',
				'label'   => _x( 'Affiliate ID', 'settings', 'groundhogg' ),
				'desc'    => _x( 'Please enter your affiliate ID you received from Groundhogg.', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => [
					'type' => 'number',
					'id'   => 'gh_affiliate_id',
					'name' => 'gh_affiliate_id',
				],
			),
			'gh_affiliate_link_in_email'             => array(
				'id'      => 'gh_affiliate_link_in_email',
				'section' => 'affiliate',
				'label'   => _x( 'Affiliate Link in emails', 'settings', 'groundhogg' ),
				'desc'    => _x( 'This settings adds affiliate link in every email you send using Groundhogg.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => [
					'label' => __( 'Enable', 'groundhogg' ),
					//keep brackets for backwards compat
					'name'  => 'gh_affiliate_link_in_email',
					'id'    => 'gh_affiliate_link_in_email',
				],
			),
			'gh_send_notifications_on_event_failure' => array(
				'id'      => 'gh_send_notifications_on_event_failure',
				'section' => 'event_notices',
				'label'   => _x( 'Event Failure Notifications', 'settings', 'groundhogg' ),
				'desc'    => _x( 'This will let you know if something goes wrong in a funnel so you can fix it.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => array(
					'label' => __( 'Enable' ),
					'name'  => 'gh_send_notifications_on_event_failure',
					'id'    => 'gh_send_notifications_on_event_failure',
					'value' => 'on',
				),
			),
			'gh_event_failure_notification_email'    => array(
				'id'      => 'gh_event_failure_notification_email',
				'section' => 'event_notices',
				'label'   => _x( 'Event Failure Notification Email', 'settings', 'groundhogg' ),
				'desc'    => _x( 'The email which you would like to send failure notifications to.', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => array(
					'type'        => 'email',
					'id'          => 'gh_event_failure_notification_email',
					'name'        => 'gh_event_failure_notification_email',
					'placeholder' => get_option( 'admin_email' )
				),
			),
			'gh_script_debug'                        => array(
				'id'      => 'gh_script_debug',
				'section' => 'misc_info',
				'label'   => _x( 'Enable script debug mode', 'settings', 'groundhogg' ),
				'desc'    => _x( 'This will attempt to load full JS files instead of minified JS files for debugging.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => array(
					'label' => __( 'Enable' ),
					'name'  => 'gh_script_debug',
					'id'    => 'gh_script_debug',
					'value' => 'on',
				),
			),
//            'gh_use_classic_builder' => array(
//                'id' => 'gh_use_classic_builder',
//                'section' => 'misc_info',
//                'label' => _x('Use the classic funnel builder.', 'settings', 'groundhogg'),
//                'desc' => _x("Don't like the new funnel builder? You can use the original one by enabling this option.", 'settings', 'groundhogg'),
//                'type' => 'checkbox',
//                'atts' => array(
//                    'label' => __('Enable'),
//                    'name' => 'gh_use_classic_builder',
//                    'id' => 'gh_use_classic_builder',
//                    'value' => 'on',
//                ),
//            ),
			'gh_ignore_user_precedence'              => array(
				'id'      => 'gh_ignore_user_precedence',
				'section' => 'misc_info',
				'label'   => _x( 'Disable logged in user tracking precedence.', 'settings', 'groundhogg' ),
				'desc'    => _x( 'By default, Groundhogg will always show info of a logged in user before referencing information from tracking links or forms. You can disable this behaviour with this option.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => array(
					'label' => __( 'Disable' ),
					'name'  => 'gh_ignore_user_precedence',
					'id'    => 'gh_ignore_user_precedence',
					'value' => 'on',
				),
			),
			'gh_hide_tooltips'                       => array(
				'id'      => 'gh_hide_tooltips',
				'section' => 'misc_info',
				'label'   => _x( 'Hide Tooltips', 'settings', 'groundhogg' ),
				'desc'    => _x( 'This will hides the tooltips user see in new installations.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => array(
					'label' => __( 'Enable' ),
					//keep brackets for backwards compat
					'name'  => 'gh_hide_tooltips',
					'id'    => 'gh_hide_tooltips',
					'value' => 'on',
				),
			),
			'gh_privacy_policy'                      => array(
				'id'      => 'gh_privacy_policy',
				'section' => 'compliance',
				'label'   => __( 'Privacy Policy' ),
				'desc'    => _x( 'Link to your privacy policy.', 'settings', 'groundhogg' ),
				'type'    => 'link_picker',
				'atts'    => array(
					'name' => 'gh_privacy_policy',
					'id'   => 'gh_privacy_policy',
				),
			),
			'gh_terms'                               => array(
				'id'      => 'gh_terms',
				'section' => 'compliance',
				'label'   => _x( 'Terms & Conditions (Terms of Service)', 'settings', 'groundogg' ),
				'desc'    => _x( 'Link to your terms & conditions.', 'settings', 'groundhogg' ),
				'type'    => 'link_picker',
				'atts'    => array(
					'name' => 'gh_terms',
					'id'   => 'gh_terms',
				),
			),
			'gh_strict_confirmation'                 => array(
				'id'      => 'gh_strict_confirmation',
				'section' => 'compliance',
				'label'   => _x( 'Only send to confirmed emails.', 'settings', 'groundhogg' ),
				'desc'    => _x( 'This will stop emails being sent to contacts who do not have confirmed emails outside of the below grace period.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => array(
					'label' => __( 'Enable' ),
					//keep brackets for backwards compat
					'name'  => 'gh_strict_confirmation[]',
					'id'    => 'gh_strict_confirmation',
					'value' => 'on',
				),
			),
			'gh_confirmation_grace_period'           => array(
				'id'      => 'gh_confirmation_grace_period',
				'section' => 'compliance',
				'label'   => _x( 'Email confirmation grace Period', 'settings', 'groundhogg' ),
				'desc'    => _x( 'The number of days for which you can send an email to a contact after they are created but their email has not been confirmed. The default is 14 days.', 'settings', 'groundhogg' ),
				'type'    => 'number',
				'atts'    => array(
					'id'          => 'gh_confirmation_grace_period',
					'name'        => 'gh_confirmation_grace_period',
					'placeholder' => '14'
				),

			),
			'gh_enable_gdpr'                         => array(
				'id'      => 'gh_enable_gdpr',
				'section' => 'compliance',
				'label'   => _x( 'Enable GDPR features.', 'settings', 'groundhogg' ),
				'desc'    => _x( 'This will add a consent box to your forms as well as a "Delete Everything" Button to your email preferences page.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => array(
					'label' => __( 'Enable' ),
					//keep brackets for backwards compat
					'name'  => 'gh_enable_gdpr[]',
					'id'    => 'gh_enable_gdpr',
					'value' => 'on',
				),
			),
			'gh_strict_gdpr'                         => array(
				'id'      => 'gh_strict_gdpr',
				'section' => 'compliance',
				'label'   => _x( 'Do not send email without consent.', 'settings', 'groundhogg' ),
				'desc'    => _x( 'This will prevent your system from sending emails to contacts for which you do not have explicit consent. Only works if GDPR features are enabled.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => array(
					'label' => __( 'Enable' ),
					//keep brackets for backwards compat
					'name'  => 'gh_strict_gdpr[]',
					'id'    => 'gh_strict_gdpr',
					'value' => 'on',
				),
			),
			'gh_recaptcha_site_key'                  => array(
				'id'      => 'gh_recaptcha_site_key',
				'section' => 'captcha',
				'label'   => _x( 'reCAPTCHA Site Key', 'settings', 'groundhogg' ),
				'desc'    => _x( 'This is the key which faces the users on the front-end', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => array(
					'name' => 'gh_recaptcha_site_key',
					'id'   => 'gh_recaptcha_site_key',
				),
			),
			'gh_recaptcha_secret_key'                => array(
				'id'      => 'gh_recaptcha_secret_key',
				'section' => 'captcha',
				'label'   => _x( 'reCAPTCHA Secret Key', 'settings', 'groundhogg' ),
				'desc'    => _x( 'Never ever ever share this with anyone!', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => array(
					'name' => 'gh_recaptcha_secret_key',
					'id'   => 'gh_recaptcha_secret_key',
				),
			),
			'gh_recaptcha_version'                   => array(
				'id'      => 'gh_recaptcha_version',
				'section' => 'captcha',
				'label'   => _x( 'reCAPTCHA Version', 'settings', 'groundhogg' ),
				'desc'    => _x( 'Which version of reCAPTCHA you want to use.', 'settings', 'groundhogg' ),
				'type'    => 'dropdown',
				'atts'    => array(
					'name'        => 'gh_recaptcha_version',
					'id'          => 'gh_recaptcha_version',
					'options'     => [
						'v2' => 'V2',
						'v3' => 'V3'
					],
					'option_none' => false
				),
			),
			'gh_recaptcha_v3_score_threshold'        => array(
				'id'      => 'gh_recaptcha_v3_score_threshold',
				'section' => 'captcha',
				'label'   => _x( 'reCAPTCHA v3 Score Threshold', 'settings', 'groundhogg' ),
				'desc'    => _x( 'The score threshold to block form submissions. <code>0.5</code> by default.', 'settings', 'groundhogg' ),
				'type'    => 'number',
				'atts'    => array(
					'name'        => 'gh_recaptcha_v3_score_threshold',
					'id'          => 'gh_recaptcha_v3_score_threshold',
					'min'         => 0,
					'max'         => 1,
					'step'        => '0.1',
					'placeholder' => '0.5'
				),
			),
			'gh_bounce_inbox'                        => array(
				'id'      => 'gh_bounce_inbox',
				'section' => 'bounces',
				'label'   => _x( 'Bounce Inbox', 'settings', 'groundhogg' ),
				'desc'    => _x( 'This is the inbox which emails will be sent to.', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => array(
					'type'        => 'email',
					'name'        => 'gh_bounce_inbox',
					'id'          => 'gh_bounce_inbox',
					'placeholder' => 'bounce@' . ( ( substr( $_SERVER['SERVER_NAME'], 0, 4 ) == 'www.' ) ? substr( $_SERVER['SERVER_NAME'], 4 ) : $_SERVER['SERVER_NAME'] ),
				),
			),
			'gh_bounce_inbox_password'               => array(
				'id'      => 'gh_bounce_inbox_password',
				'section' => 'bounces',
				'type'    => 'input',
				'label'   => _x( 'Bounce Inbox Password', 'settings', 'groundhogg' ),
				'desc'    => _x( 'This password to access the inbox.', 'settings', 'groundhogg' ),
				'atts'    => array(
					'type' => 'password',
					'name' => 'gh_bounce_inbox_password',
					'id'   => 'gh_bounce_inbox_password',
				),
			),
			'gh_bounce_inbox_host'                   => array(
				'id'      => 'gh_bounce_inbox_host',
				'section' => 'bounces',
				'label'   => _x( 'Mail Server', 'settings', 'groundhogg' ),
				'desc'    => _x( 'This is the domain your email inbox is hosted. Most likely mail.yourdomain.com', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => array(
					'type'        => 'text',
					'name'        => 'gh_bounce_inbox_host',
					'id'          => 'gh_bounce_inbox_host',
					'placeholder' => 'mail.' . ( ( substr( $_SERVER['SERVER_NAME'], 0, 4 ) == 'www.' ) ? substr( $_SERVER['SERVER_NAME'], 4 ) : $_SERVER['SERVER_NAME'] ),
				),
			),
			'gh_bounce_inbox_port'                   => array(
				'id'      => 'gh_bounce_inbox_port',
				'section' => 'bounces',
				'label'   => _x( 'IMAP Port', 'settings', 'groundhogg' ),
				'desc'    => _x( 'The bounce checker requires an IMAP connection. Most IMAP ports are 993.', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => array(
					'type'        => 'number',
					'name'        => 'gh_bounce_inbox_port',
					'id'          => 'gh_bounce_inbox_port',
					'placeholder' => 993,
				),
			),
			'gh_override_from_name'                  => [
				'id'      => 'gh_override_from_name',
				'section' => 'overrides',
				'label'   => _x( 'Default From Name', 'settings', 'groundhogg' ),
				'desc'    => _x( 'Override the default <code>wp_mail</code> from name.', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => array(
					'name'        => 'gh_override_from_name',
					'id'          => 'gh_override_from_name',
					'placeholder' => Plugin::$instance->settings->get_option( 'gh_business_name' ),
				),
			],
			'gh_override_from_email'                 => [
				'id'      => 'gh_override_from_email',
				'section' => 'overrides',
				'label'   => _x( 'Default From Email', 'settings', 'groundhogg' ),
				'desc'    => _x( 'Override the default <code>wp_mail</code> from email.', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => array(
					'type'        => 'email',
					'name'        => 'gh_override_from_email',
					'id'          => 'gh_override_from_email',
					'placeholder' => Plugin::$instance->settings->get_option( 'admin_email' ),
				),
			],
			'gh_email_footer_alignment'              => [
				'id'      => 'gh_email_footer_alignment',
				'section' => 'overrides',
				'label'   => _x( 'Email Footer Alignment', 'settings', 'groundhogg' ),
				'desc'    => _x( 'The alignment of the email footer in all emails.', 'settings', 'groundhogg' ),
				'type'    => 'dropdown',
				'atts'    => array(
					'name'        => 'gh_email_footer_alignment',
					'id'          => 'gh_email_footer_alignment',
					'options'     => [
						'left'   => __( 'Left' ),
						'center' => __( 'Center' ),
					],
					'option_none' => false,
				),
			],
			'gh_custom_email_footer_text'            => [
				'id'      => 'gh_custom_email_footer_text',
				'section' => 'overrides',
				'label'   => _x( 'Custom Footer Text', 'settings', 'groundhogg' ),
				'desc'    => _x( 'Text that will appear before the footer in every email. Accepts HTML and plain text.', 'settings', 'groundhogg' ),
				'type'    => 'editor',
				'args'    => [ 'sanitize_callback' => 'wp_kses_post' ],
				'atts'    => [ 'replacements_button' => true ],
			],
//			'gh_disable_api'                         => array(
//				'id'      => 'gh_disable_api',
//				'section' => 'api_settings',
//				'label'   => sprintf( _x( 'Disable the %s API', 'settings', 'groundhogg' ), white_labeled_name() ),
//				'desc'    => _x( 'Disabling the API will prevent other platforms from accessing information on this site. Functionality in some extensions may be affected as well.', 'settings', 'groundhogg' ),
//				'type'    => 'checkbox',
//				'atts'    => array(
//					'label' => __( 'Disable' ),
//					'name'  => 'gh_disable_api',
//					'id'    => 'gh_disable_api',
//					'value' => 'on',
//				),
//			),
			'gh_confirmed_tag'                       => [
				'id'      => 'gh_confirmed_tag',
				'section' => 'optin_status_tags',
				'label'   => _x( 'Confirmed Tag', 'settings', 'groundhogg' ),
				'desc'    => _x( 'All confirmed contacts will have this tag.', 'settings', 'groundhogg' ),
				'type'    => 'tag_picker',
				'atts'    => array(
					'name'  => 'gh_confirmed_tag',
					'id'    => 'gh_confirmed_tag',
					'class' => 'gh-single-tag-picker'
				),
			],
			'gh_unconfirmed_tag'                     => [
				'id'      => 'gh_unconfirmed_tag',
				'section' => 'optin_status_tags',
				'label'   => _x( 'Unconfirmed Tag', 'settings', 'groundhogg' ),
				'desc'    => _x( 'All unconfirmed contacts will have this tag.', 'settings', 'groundhogg' ),
				'type'    => 'tag_picker',
				'atts'    => array(
					'name'  => 'gh_unconfirmed_tag',
					'id'    => 'gh_unconfirmed_tag',
					'class' => 'gh-single-tag-picker'
				),
			],
			'gh_unsubscribed_tag'                    => [
				'id'      => 'gh_unsubscribed_tag',
				'section' => 'optin_status_tags',
				'label'   => _x( 'Unsubscribed Tag', 'settings', 'groundhogg' ),
				'desc'    => _x( 'All unsubscribed contacts will have this tag.', 'settings', 'groundhogg' ),
				'type'    => 'tag_picker',
				'atts'    => array(
					'name'  => 'gh_unsubscribed_tag',
					'id'    => 'gh_unsubscribed_tag',
					'class' => 'gh-single-tag-picker'
				),
			],
			'gh_spammed_tag'                         => [
				'id'      => 'gh_spammed_tag',
				'section' => 'optin_status_tags',
				'label'   => _x( 'Spam Tag', 'settings', 'groundhogg' ),
				'desc'    => _x( 'All contacts which are marked as spam will have this tag.', 'settings', 'groundhogg' ),
				'type'    => 'tag_picker',
				'atts'    => array(
					'name'  => 'gh_spammed_tag',
					'id'    => 'gh_spammed_tag',
					'class' => 'gh-single-tag-picker'
				),
			],
			'gh_bounced_tag'                         => [
				'id'      => 'gh_bounced_tag',
				'section' => 'optin_status_tags',
				'label'   => _x( 'Bounced Tag', 'settings', 'groundhogg' ),
				'desc'    => _x( 'All contacts which have bounced will have this tag.', 'settings', 'groundhogg' ),
				'type'    => 'tag_picker',
				'atts'    => array(
					'name'  => 'gh_bounced_tag',
					'id'    => 'gh_bounced_tag',
					'class' => 'gh-single-tag-picker'
				),
			],
			'gh_complained_tag'                      => [
				'id'      => 'gh_complained_tag',
				'section' => 'optin_status_tags',
				'label'   => _x( 'Complained Tag', 'settings', 'groundhogg' ),
				'desc'    => _x( 'All contacts which have complained will have this tag.', 'settings', 'groundhogg' ),
				'type'    => 'tag_picker',
				'atts'    => array(
					'name'  => 'gh_complained_tag',
					'id'    => 'gh_complained_tag',
					'class' => 'gh-single-tag-picker'
				),
			],
			'gh_monthly_tag'                         => [
				'id'      => 'gh_monthly_tag',
				'section' => 'optin_status_tags',
				'label'   => _x( 'Subscribed Monthly Tag', 'settings', 'groundhogg' ),
				'desc'    => _x( 'All contacts which have requested monthly emails will have this tag.', 'settings', 'groundhogg' ),
				'type'    => 'tag_picker',
				'atts'    => array(
					'name'  => 'gh_monthly_tag',
					'id'    => 'gh_monthly_tag',
					'class' => 'gh-single-tag-picker'
				),
			],
			'gh_weekly_tag'                          => [
				'id'      => 'gh_weekly_tag',
				'section' => 'optin_status_tags',
				'label'   => _x( 'Subscribed Weekly Tag', 'settings', 'groundhogg' ),
				'desc'    => _x( 'All contacts which have requested weekly emails will have this tag.', 'settings', 'groundhogg' ),
				'type'    => 'tag_picker',
				'atts'    => array(
					'name'  => 'gh_weekly_tag',
					'id'    => 'gh_weekly_tag',
					'class' => 'gh-single-tag-picker'
				),
			],
			'gh_marketable_tag'                      => [
				'id'      => 'gh_marketable_tag',
				'section' => 'optin_status_tags',
				'label'   => _x( 'Marketable Tag', 'settings', 'groundhogg' ),
				'desc'    => _x( 'All contacts which are considered marketable will have this tag.', 'settings', 'groundhogg' ),
				'type'    => 'tag_picker',
				'atts'    => array(
					'name'  => 'gh_marketable_tag',
					'id'    => 'gh_marketable_tag',
					'class' => 'gh-single-tag-picker'
				),
			],
			'gh_non_marketable_tag'                  => [
				'id'      => 'gh_non_marketable_tag',
				'section' => 'optin_status_tags',
				'label'   => _x( 'Non Marketable Tag', 'settings', 'groundhogg' ),
				'desc'    => _x( 'All contacts which are considered unmarketable will have this tag.', 'settings', 'groundhogg' ),
				'type'    => 'tag_picker',
				'atts'    => array(
					'name'  => 'gh_non_marketable_tag',
					'id'    => 'gh_non_marketable_tag',
					'class' => 'gh-single-tag-picker'
				),
			],
			'gh_url_tracking_exclusions'             => [
				'id'      => 'gh_url_tracking_exclusions',
				'section' => 'tracking',
				'label'   => _x( 'Tracking URL Exclusions', 'settings', 'groundhogg' ),
				'desc'    => sprintf( _x( 'URLs containing these strings will not be tracked. For example, adding <code>/my-page/</code> would exclude <code>%s/my-page/download/</code>. You can also enter full URLs and URLs of other domains such as <code>https://wordpress.org</code>. To match an exact path use <code>$</code> at the end of the path.', 'settings', 'groundhogg' ), site_url() ),
				'type'    => 'textarea',
				'atts'    => array(
					'name' => 'gh_url_tracking_exclusions',
					'id'   => 'gh_url_tracking_exclusions',
				),
			],
			'gh_transactional_email_service'             => [
				'id'      => 'gh_transactional_email_service',
				'section' => 'outgoing_email_config',
				'label'   => _x( 'Transactional Email Service', 'settings', 'groundhogg' ),
				'desc'    => _x( 'Choose which installed service should handle transactional email from WordPress & Groundhogg. This service will apply to <b>all WordPress email</b> and Groundhogg emails with their <code>message type</code> set to <b>Transactional</b>.', 'settings', 'groundhogg' ),
				'type'    => 'dropdown',
				'atts'    => array(
					'name'        => 'gh_transactional_email_service',
					'id'          => 'gh_transactional_email_service',
					'option_none' => false,
					'options'     => Groundhogg_Email_Services::dropdown()
				),
			],
			'gh_marketing_email_service'         => [
				'id'      => 'gh_marketing_email_service',
				'section' => 'outgoing_email_config',
				'label'   => _x( 'Marketing Email Service', 'settings', 'groundhogg' ),
				'desc'    => _x( 'Choose which installed service should handle marketing email from Groundhogg. This service will only apply to Groundhogg emails which have their <code>message type</code> set to as <b>Marketing</b>.', 'settings', 'groundhogg' ),
				'type'    => 'dropdown',
				'atts'    => array(
					'name'        => 'gh_marketing_email_service',
					'id'          => 'gh_marketing_email_service',
					'option_none' => false,
					'options'     => Groundhogg_Email_Services::dropdown()
				),
			]
		);

		if ( ! defined( 'DISABLE_WP_CRON' ) || defined( 'GH_SHOW_DISABLE_WP_CRON_OPTION' ) ) {
			$settings['gh_disable_wp_cron'] = array(
				'id'      => 'gh_disable_wp_cron',
				'section' => 'wp_cron',
				'label'   => _x( 'Disable WP Cron.', 'settings', 'groundhogg' ),
				'desc'    => _x( 'Disable the built-in WP Cron system. This is recommended if you are using an external cron job.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => array(
					'label' => __( 'Disable' ),
					'name'  => 'gh_disable_wp_cron',
					'id'    => 'gh_disable_wp_cron',
					'value' => 'on',
				),
			);
		}

		return apply_filters( 'groundhogg/admin/settings/settings', $settings );
	}

	/**
	 * Register all the settings
	 */
	public function register_settings() {

		do_action( 'groundhogg/admin/register_settings/before', $this );

		foreach ( $this->settings as $id => $setting ) {

			if ( ! isset_not_empty( $this->sections, $setting['section'] ) ) {
				continue;
			}

			add_settings_field( $setting['id'], $setting['label'], array(
				$this,
				'settings_callback'
			), 'gh_' . $this->sections[ $setting['section'] ]['tab'], 'gh_' . $setting['section'], $setting );
			$args = isset_not_empty( $setting, 'args' ) ? $setting['args'] : [];
			register_setting( 'gh_' . $this->sections[ $setting['section'] ]['tab'], $setting['id'], $args );
		}

		do_action( 'groundhogg/admin/register_settings/after', $this );
	}

	/**
	 * Add a tab to the settings page
	 *
	 * @param string $id    if of the tab
	 * @param string $title title of the tab
	 *
	 * @return bool
	 */
	public function add_tab( $id = '', $title = '' ) {
		if ( ! $id || ! $title ) {
			return false;
		}


		$this->tabs[ $id ] = array(
			'id'    => $id,
			'title' => $title,
		);

		return true;
	}

	/**
	 * Add a section to a tab
	 *
	 * @param string $id    id of the section
	 * @param string $title title of the section
	 * @param string $tab   the tab
	 *
	 * @return bool
	 */
	public function add_section( $id = '', $title = '', $tab = '' ) {
		if ( ! $id || ! $title || ! $tab ) {
			return false;
		}


		$this->sections[ $id ] = array(
			'id'    => $id,
			'title' => $title,
			'tab'   => $tab,
		);

		return true;
	}

	/**
	 * Add a setting to the page
	 *
	 * @param array $args
	 *
	 * @return bool
	 */
	public function add_setting( $args = array() ) {
		$setting = wp_parse_args( $args, array(
				'id'      => '',
				'section' => 'misc',
				'label'   => '',
				'desc'    => '',
				'type'    => 'input',
				'atts'    => array(
					//keep brackets for backwards compat
					'name' => '',
					'id'   => '',
				)
			)
		);

		if ( empty( $setting['id'] ) ) {
			return false;
		}

		$this->settings[ $setting['id'] ] = $setting;

		return true;
	}

	/**
	 * Return the id of the active tab
	 *
	 * @return string
	 */
	private function active_tab() {
		return sanitize_key( get_request_var( 'tab', 'general' ) );
	}

	/**
	 * Return whether a tab has settings or not.
	 *
	 * @param $tab string the ID of the tab
	 *
	 * @return bool
	 */
	private function tab_has_settings( $tab = '' ) {

		if ( ! $tab ) {
			$tab = $this->active_tab();
		}

		global $wp_settings_sections;

		return isset( $wp_settings_sections[ 'gh_' . $tab ] );
	}

	/**
	 * If a cap is specific for the tab, check to see if the user has the required permissions...
	 *
	 * @param $tab
	 *
	 * @return bool
	 */
	private function user_can_access_tab( $tab = '' ) {

		if ( ! $tab ) {
			$tab = $this->active_tab();
		}

		$tab = get_array_var( $this->tabs, $tab );

		// Check for cap restriction on the tab...
		$cap = get_array_var( $tab, 'cap' );

		// ignore if there is no cap, but if there is one check if the user has require privileges...
		if ( $cap && ! current_user_can( $cap ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Output the settings content
	 */
//    public function settings_content()
	public function view() {
		?>
        <style>
            .select2 {
                max-width: 300px;
            }
        </style>
        <div class="wrap">
			<?php
			settings_errors();
			$action = $this->tab_has_settings( $this->active_tab() ) ? 'options.php' : ''; ?>
            <form method="POST" enctype="multipart/form-data" action="<?php echo $action; ?>">

                <!-- BEGIN TABS -->
                <h2 class="nav-tab-wrapper">
					<?php foreach ( $this->tabs as $id => $tab ):

						// Check for cap restriction on the tab...
						$cap = get_array_var( $tab, 'cap' );

						// ignore if there is no cap, but if there is one check if the user has require privileges...
						if ( $cap && ! current_user_can( $cap ) ) {
							continue;
						}

						?>

                        <a href="?page=gh_settings&tab=<?php echo $tab['id']; ?>"
                           class="nav-tab <?php echo $this->active_tab() == $tab['id'] ? 'nav-tab-active' : ''; ?>"><?php _e( $tab['title'], 'groundhogg' ); ?></a>
					<?php endforeach; ?>
                </h2>
                <!-- END TABS -->

                <!-- BEGIN SETTINGS -->
				<?php
				if ( $this->tab_has_settings() && $this->user_can_access_tab() ) {

					settings_fields( 'gh_' . $this->active_tab() );
					do_settings_sections( 'gh_' . $this->active_tab() );
					do_action( "groundhogg/admin/settings/{$this->active_tab()}/after_settings" );
					submit_button();

				}

				do_action( "groundhogg/admin/settings/{$this->active_tab()}/after_submit" );
				?>
                <!-- END SETTINGS -->
            </form>
			<?php do_action( "groundhogg/admin/settings/{$this->active_tab()}/after_form" ); ?>
        </div> <?php
	}

	public function settings_callback( $field ) {
		$value = Plugin::$instance->settings->get_option( $field['id'] );

		switch ( $field['type'] ) {
			case 'editor':
				$field['atts']['id']       = $field['id'];
				$field['atts']['content']  = $value;
				$field['atts']['settings'] = [ 'editor_height' => 200 ];
				break;
			case 'select2':
			case 'dropdown_emails':
			case 'tag_picker':
				$field['atts']['selected'] = is_array( $value ) ? $value : [ $value ];
				break;
			case 'dropdown':
			case 'dropdown_owners':
				$field['atts']['selected'] = $value;
				break;
			case 'checkbox':
				$field['atts']['checked'] = (bool) $value;
				break;
			case 'input':
			default:
				$field['atts']['value'] = $value;
				break;
		}

		$field['atts']['id'] = esc_attr( sanitize_key( $field['id'] ) );

		echo html()->wrap( call_user_func( array(
			Plugin::$instance->utils->html,
			$field['type']
		), $field['atts'] ), 'div', [ 'style' => [ 'max-width' => '700px' ] ] );

		if ( isset( $field['desc'] ) && $desc = $field['desc'] ) {
			printf( '<p class="description">%s</p>', $desc );
		}
	}


}
