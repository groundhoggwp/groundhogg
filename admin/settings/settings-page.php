<?php

namespace Groundhogg\Admin\Settings;

use Groundhogg\Admin\Admin_Page;
use Groundhogg\Api\V3\Base;
use Groundhogg\Api\V4\Base_Api;
use Groundhogg\Extension;
use Groundhogg\License_Manager;
use Groundhogg\Mailhawk;
use Groundhogg\Plugin;
use Groundhogg\Tag_Mapping;
use Groundhogg_Email_Services;
use function Groundhogg\action_input;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_array_var;
use function Groundhogg\get_post_var;
use function Groundhogg\get_request_var;
use function Groundhogg\get_url_var;
use function Groundhogg\get_valid_contact_tabs;
use function Groundhogg\has_constant_support;
use function Groundhogg\header_icon;
use function Groundhogg\html;
use function Groundhogg\is_white_labeled;
use function Groundhogg\isset_not_empty;
use function Groundhogg\maybe_get_option_from_constant;
use function Groundhogg\white_labeled_name;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Settings
 *
 * This  is your fairly typical settings page.
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
		wp_enqueue_style( 'groundhogg-admin-element' );
//		wp_enqueue_style( 'groundhogg-admin-extensions' );

		if ( get_url_var( 'tab' ) === 'email' ) {
			wp_enqueue_script( 'groundhogg-admin-form-fields-editor' );

			$fields = [
				'gh_custom_profile_fields'    => get_option( 'gh_custom_profile_fields', [] ),
				'gh_custom_preference_fields' => get_option( 'gh_custom_preference_fields', [] )
			];

			wp_add_inline_script( 'groundhogg-admin-form-fields-editor', "const CustomFields = " . wp_json_encode( $fields ), 'before' );
		}
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
		return 110;
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

        <form id="api-key-generate-form" method="post"
              action="<?php echo admin_url( 'admin.php?page=gh_settings&tab=api_tab' ); ?>">
			<?php action_input( 'create_api_key', true, true ); ?>
            <div class="gh-input-group">
				<?php echo html()->dropdown_owners( [
					'option_none' => __( 'Select a user', 'groundhogg' ),
					'name'        => 'user_id',
					'id'          => 'user_id'
				] );

				echo html()->button( [
					'class' => 'gh-button primary',
					'text'  => __( 'Generate new API key' ),
					'type'  => 'submit',
				] )

				?>
            </div>
        </form>

        <div style="max-width: 900px;"><?php
		$api_keys_table->display();
		?></div><?php

		html()->start_form_table();
		html()->start_row();
		html()->th( __( 'API v4 Route', 'groundhogg' ) );
		html()->td( [
			html()->input( [
					'class'    => 'code input regular-text',
					'readonly' => true,
					'value'    => rest_url( Base_Api::NAME_SPACE ),
					'onfocus'  => 'this.select()'
				]
			),
			html()->description( html()->e( 'a', [
				'href' => admin_page_url( 'gh_tools', [ 'tab' => 'api' ] )
			], __( 'Test out the API in the new Rest API Playground.', 'groundhogg' ) ) ),
		] );
		html()->end_row();
		html()->start_row();
		html()->th( __( 'API v3 Route', 'groundhogg' ) );
		html()->td( html()->input( [
				'class'    => 'code input regular-text',
				'readonly' => true,
				'value'    => rest_url( Base::NAME_SPACE ),
				'onfocus'  => 'this.select()'
			]
		) );
		html()->end_row();
		html()->end_form_table();
	}

	public function show_extensions() {

		$extensions = Extension::get_extensions();

		?>
        <div>
			<?php wp_nonce_field(); ?>
			<?php

			if ( ! empty( $extensions ) ) :

				if ( ! is_white_labeled() && License_Manager::has_expired_licenses() ):

					$verify_license_url = Plugin::instance()->bulk_jobs->check_licenses->get_start_url();

					?>
                    <p><?php printf( __( 'If your license key has expired, <a href="https://groundhogg.io/account/licenses/">please renew your license</a>. If you have recently renewed your license <a href="%s">click here to re-verify it</a>.', 'groundhogg' ), $verify_license_url ); ?></p>
				<?php

				else:

					?><p></p><?php

				endif;

				?>
                <div class="post-box-grid"><?php

				foreach ( $extensions as $extension ):
					echo $extension;
				endforeach;

				?></div><?php
			else:
				?>
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
	 * Generate a new api key
	 */
	public function process_create_api_key() {

		// No access
		if ( ! is_super_admin() ) {
			$this->wp_die_no_access();
		}

		$user_id = absint( get_post_var( 'user_id' ) );

		$has_key = get_user_meta( $user_id, 'wpgh_user_secret_key', true );

		if ( ! empty( $has_key ) ) {
			return new \WP_Error( 'error', 'An API key has already been issued for this user.' );
		}

		if ( API_Keys_Table::generate_api_key( $user_id ) ) {
			$this->add_notice( 'success', 'API key created!' );

			return true;
		}

		return new \WP_Error( 'error', 'Unable to generate API key.' );
	}

	/**
	 * Generate a new api key
	 */
	public function process_reissue_api_key() {

		// No access
		if ( ! is_super_admin() ) {
			$this->wp_die_no_access();
		}

		$user_id = absint( get_request_var( 'user_id' ) );

		if ( API_Keys_Table::generate_api_key( $user_id ) ) {
			$this->add_notice( 'success', 'New API key created!' );

			return true;
		}

		return new \WP_Error( 'error', 'Unable to generate new API key.' );
	}

	/**
	 * Revoke an existing api key
	 */
	public function process_revoke_api_key() {

		if ( ! is_super_admin() ) {
			$this->wp_die_no_access();
		}

		$user_id = absint( get_request_var( 'user_id' ) );

		delete_user_meta( $user_id, 'wpgh_user_public_key' );
		delete_user_meta( $user_id, 'wpgh_user_secret_key' );

		$this->add_notice( 'success', 'API key revoked!' );

		return true;
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
			'general'      => array(
				'id'    => 'general',
				'title' => _x( 'General', 'settings_tabs', 'groundhogg' )
			),
			'marketing'    => array(
				'id'    => 'marketing',
				'title' => _x( 'Compliance', 'settings_tabs', 'groundhogg' )
			),
			'email'        => array(
				'id'    => 'email',
				'title' => _x( 'Email', 'settings_tabs', 'groundhogg' )
			),
			'tags'         => [
				'id'    => 'tags',
				'title' => _x( 'Tags', 'settings_tabs', 'groundhogg' )
			],
			'api_tab'      => array(
				'id'    => 'api_tab',
				'title' => _x( 'API', 'settings_tabs', 'groundhogg' )
			),
			'integrations' => array(
				'id'    => 'integrations',
				'title' => _x( 'Integrations', 'settings_tabs', 'groundhogg' )
			),
			'misc'         => array(
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

		$sections = [
			'business_info'         => [
				'id'    => 'business_info',
				'title' => _x( 'Business Settings', 'settings_sections', 'groundhogg' ),
				'tab'   => 'general'
			],
			'general_other'         => [
				'id'    => 'general_other',
				'title' => _x( 'Other', 'settings_sections', 'groundhogg' ),
				'tab'   => 'general'
			],
//			'misc_info'             => [
//				'id'    => 'misc_info',
//				'title' => _x( 'Misc Settings', 'settings_sections', 'groundhogg' ),
//				'tab'   => 'misc'
//			],
			'interface'             => [
				'id'    => 'interface',
				'title' => _x( 'Interface', 'settings_sections', 'groundhogg' ),
				'tab'   => 'misc'
			],
			'performance'           => [
				'id'    => 'performance',
				'title' => _x( 'Performance', 'settings_sections', 'groundhogg' ),
				'tab'   => 'misc'
			],
			'developer'             => [
				'id'    => 'developer',
				'title' => _x( 'Developers', 'settings_sections', 'groundhogg' ),
				'tab'   => 'misc'
			],
			'page_tracking'         => [
				'id'    => 'page_tracking',
				'title' => _x( 'Site Activity Tracking', 'settings_sections', 'groundhogg' ),
				'tab'   => 'misc'
			],
			'wp_cron'               => [
				'id'    => 'wp_cron',
				'title' => _x( 'WP Cron', 'settings_sections', 'groundhogg' ),
				'tab'   => 'misc',
			],
			'affiliate'             => [
				'id'    => 'affiliate',
				'title' => _x( 'Affiliate Section', 'settings_sections', 'groundhogg' ),
				'tab'   => 'misc'
			],
			'wordpress'             => [
				'id'    => 'wordpress',
				'title' => _x( 'WordPress', 'settings_sections', 'groundhogg' ),
				'tab'   => 'integrations'
			],
			'captcha'               => [
				'id'    => 'captcha',
				'title' => _x( 'Google reCAPTCHA', 'settings_sections', 'groundhogg' ),
				'tab'   => 'integrations'
			],
			'event_notices'         => [
				'id'    => 'event_notices',
				'title' => _x( 'Event Notices', 'settings_sections', 'groundhogg' ),
				'tab'   => 'misc'
			],
			'danger_zone'           => [
				'id'    => 'danger_zone',
				'title' => _x( 'Danger Zone', 'settings_sections', 'groundhogg' ),
				'tab'   => 'misc'
			],
			'compliance'            => [
				'id'    => 'compliance',
				'title' => _x( 'Compliance', 'settings_sections', 'groundhogg' ),
				'tab'   => 'marketing'
			],
			'cookies'               => [
				'id'    => 'cookies',
				'title' => _x( 'Cookies', 'settings_sections', 'groundhogg' ),
				'tab'   => 'marketing'
			],
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
				'title' => _x( 'Defaults', 'settings_sections', 'groundhogg' ),
				'tab'   => 'email'
			],
			'footer'                => [
				'id'    => 'footer',
				'title' => _x( 'Footer', 'settings_sections', 'groundhogg' ),
				'tab'   => 'email'
			],
			'tracking'              => [
				'id'    => 'tracking',
				'title' => _x( 'Tracking', 'settings_sections', 'groundhogg' ),
				'tab'   => 'email',
			],
			'unsubscribe'           => [
				'id'    => 'unsubscribe',
				'title' => _x( 'Unsubscribe Settings', 'settings_sections', 'groundhogg' ),
				'tab'   => 'email'
			],
			'preferences_center'           => [
				'id'       => 'preferences_center',
				'title'    => _x( 'Preferences Center', 'settings_sections', 'groundhogg' ),
				'tab'      => 'email',
				'callback' => function () {

					?>
                    <table class="form-table">
                        <tbody>
                        <tr>
                            <th><?php _e( 'Personal Details Form', 'groundhogg' ); ?></th>
                            <td>
                                <div style="max-width: 400px">
                                    <div id="gh_custom_profile_fields"></div>
                                </div>
                                <p class="description" style="margin-top: 20px">
									<?php _e( 'Show additional profile fields in the preferences center. Delete all fields to show the default form.', 'groundhogg' ) ?>
                                </p>
                            </td>
                        </tr>
						<?php if ( defined( 'GROUNDHOGG_ADVANCED_PREFERENCES_VERSION' ) ): ?>
                            <tr>
                                <th><?php _e( 'Preferences Form', 'groundhogg' ); ?></th>
                                <td>
                                    <div style="max-width: 400px">
                                        <div id="gh_custom_preference_fields"></div>
                                    </div>
                                    <p class="description" style="margin-top: 20px">
										<?php _e( 'Show additional fields on the email preferences screen.', 'groundhogg' ) ?>
                                    </p>
                                </td>
                            </tr>
						<?php endif; ?>
                        </tbody>
                    </table>
					<?php

				}
			],
			'email_logging'         => [
				'id'       => 'email_logging',
				'title'    => _x( 'Email Logging', 'settings_sections', 'groundhogg' ),
				'tab'      => 'email',
				'callback' => function () {
					?>
                    <div id="email-logging"></div><?php
				}
			],
			'bounces'               => [
				'id'       => 'bounces',
				'title'    => _x( 'Email Bounces', 'settings_sections', 'groundhogg' ),
				'tab'      => 'email',
				'callback' => [ Plugin::$instance->bounce_checker, 'test_connection_ui' ],
			],
			'optin_status_tags'     => [
				'id'       => 'optin_status_tags',
				'title'    => _x( 'Opt-in Status Tags', 'settings_sections', 'groundhogg' ),
				'tab'      => 'tags',
				'callback' => [ Plugin::$instance->tag_mapping, 'reset_tags_ui' ],
			],
		];

		// If SMTP or WP Mail is not in use, hide the bounce settings. We don't need them.
		if ( ! Groundhogg_Email_Services::service_in_use( 'wp_mail' ) && ! Groundhogg_Email_Services::service_in_use( 'smtp' ) ) {
			unset( $sections['bounces'] );
		}

		if ( ! Tag_Mapping::enabled() ) {
			unset( $sections['optin_status_tags'] );
		}

        if ( is_white_labeled() ){
            unset( $sections['affiliate'] );
        }

		return apply_filters( 'groundhogg/admin/settings/sections', $sections );
	}

	private function get_default_settings() {

		$settings = [
			'gh_business_name'                       => [
				'id'      => 'gh_business_name',
				'section' => 'business_info',
				'label'   => _x( 'Business Name', 'settings', 'groundhogg' ),
				'desc'    => _x( 'Your business name as it appears in the email footer.', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => [
					'id'          => 'gh_business_name',
					'name'        => 'gh_business_name',
					'placeholder' => get_bloginfo( 'name' )
				],
			],
			'gh_street_address_1'                    => [
				'id'      => 'gh_street_address_1',
				'section' => 'business_info',
				'label'   => _x( 'Street Address 1', 'settings', 'groundhogg' ),
				'desc'    => _x( 'As it should appear in your email footer.', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => [
					'id'          => 'gh_street_address_1',
					'name'        => 'gh_street_address_1',
					'placeholder' => '123 Any St.'
				],
			],
			'gh_street_address_2'                    => [
				'id'      => 'gh_street_address_2',
				'section' => 'business_info',
				'label'   => _x( 'Street Address 2', 'settings', 'groundhogg' ),
				'desc'    => _x( '(Optional) As it should appear in your email footer.', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => [
					'id'          => 'gh_street_address_2',
					'name'        => 'gh_street_address_2',
					'placeholder' => __( 'Unit 42' )
				],
			],
			'gh_city'                                => [
				'id'      => 'gh_city',
				'section' => 'business_info',
				'label'   => __( 'City' ),
				'desc'    => _x( 'As it should appear in your email footer.', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => [
					'id'          => 'gh_city',
					'name'        => 'gh_city',
					'placeholder' => __( 'Toronto' )
				],
			],
			'gh_zip_or_postal'                       => [
				'id'      => 'gh_zip_or_postal',
				'section' => 'business_info',
				'label'   => _x( 'Postal/Zip Code', 'settings', 'groundhogg' ),
				'desc'    => _x( 'As it should appear in your email footer.', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => [
					'id'          => 'gh_zip_or_postal',
					'name'        => 'gh_zip_or_postal',
					'placeholder' => 'A1A 1A1'
				],
			],
			'gh_region'                              => [
				'id'      => 'gh_region',
				'section' => 'business_info',
				'label'   => _x( 'State/Province/Region', 'settings', 'groundhogg' ),
				'desc'    => _x( 'As it should appear in your email footer.', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => [
					'id'          => 'gh_region',
					'name'        => 'gh_region',
					'placeholder' => 'Ontario'
				],
			],
			'gh_country'                             => [
				'id'      => 'gh_country',
				'section' => 'business_info',
				'label'   => __( 'Country' ),
				'desc'    => _x( 'As it should appear in your email footer.', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => [
					'id'          => 'gh_country',
					'name'        => 'gh_country',
					'placeholder' => 'Canada'
				],
			],
			'gh_phone'                               => [
				'id'      => 'gh_phone',
				'section' => 'business_info',
				'label'   => __( 'Phone' ),
				'desc'    => _x( 'As it should appear in your email footer.', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => [
					'type'        => 'tel',
					'id'          => 'gh_phone',
					'name'        => 'gh_phone',
					'placeholder' => '+1 (555) 555-555'
				],
			],
			'gh_primary_user'                        => [
				'id'      => 'gh_primary_user',
				'section' => 'general_other',
				'label'   => __( 'Default Contact Owner', 'groundhogg' ),
				'desc'    => _x( 'The primary contact owner which will be automatically assigned to contacts if another is not specified.', 'settings', 'groundhogg' ),
				'type'    => 'dropdown_owners',
				'atts'    => [
					'id'   => 'gh_primary_user',
					'name' => 'gh_primary_user',
				],
			],
			'gh_disable_user_sync'                   => [
				'id'      => 'gh_disable_user_sync',
				'section' => 'wordpress',
				'label'   => __( 'Disable User Syncing', 'groundhogg' ),
				'desc'    => _x( 'Disable the automatic syncing of WordPress users and contacts.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => [
					'label' => __( 'Disable' ),
					'name'  => 'gh_disable_user_sync',
					'id'    => 'gh_disable_user_sync',
					'value' => 'on',
				],
			],
			'gh_sync_user_meta'                      => [
				'id'      => 'gh_sync_user_meta',
				'section' => 'wordpress',
				'label'   => __( 'Sync User Meta', 'groundhogg' ),
				'desc'    => _x( 'When enabled all user meta will be synced in real time with contact meta if automatic user syncing is enabled.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => [
					'label' => __( 'Enable' ),
					'name'  => 'gh_sync_user_meta',
					'id'    => 'gh_sync_user_meta',
					'value' => 'on',
				],
			],
			'gh_uninstall_on_delete'                 => [
				'id'      => 'gh_uninstall_on_delete',
				'section' => 'danger_zone',
				'label'   => sprintf( _x( 'Delete %s data', 'settings', 'groundhogg' ), white_labeled_name() ),
				'desc'    => _x( 'Delete all information when uninstalling. This cannot be undone.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => [
					'label' => __( 'Enable' ),
					//keep brackets for backwards compat
					'name'  => 'gh_uninstall_on_delete[]',
					'id'    => 'gh_uninstall_on_delete',
					'value' => 'on',
				],
			],
			'gh_opted_in_stats_collection'           => [
				'id'      => 'gh_opted_in_stats_collection',
				'section' => 'danger_zone',
				'label'   => _x( 'Opt-in to anonymous usage tracking.', 'settings', 'groundhogg' ),
				'desc'    => sprintf( _x( 'Help us make %s better by providing anonymous usage information about your site.', 'settings', 'groundhogg' ), white_labeled_name() ),
				'type'    => 'checkbox',
				'atts'    => [
					'label' => __( 'Enable' ),
					'name'  => 'gh_opted_in_stats_collection',
					'id'    => 'gh_opted_in_stats_collection',
					'value' => 'on',
				],
			],
			'gh_allow_unrestricted_file_access'      => [
				'id'      => 'gh_allow_unrestricted_file_access',
				'section' => 'danger_zone',
				'label'   => _x( 'Allow unrestricted access to contact file uploads.', 'settings', 'groundhogg' ),
				'desc'    => _x( 'This will allow anyone with a file access link to view uploads regardless of whether they are logged in.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => [
					'label' => __( 'Allow' ),
					'name'  => 'gh_allow_unrestricted_file_access',
					'id'    => 'gh_allow_unrestricted_file_access',
					'value' => 'on',
				],
			],
			'gh_enable_experimental_features'        => [
				'id'      => 'gh_enable_experimental_features',
				'section' => 'developer',
				'label'   => _x( 'Enable experimental features.', 'settings', 'groundhogg' ),
				'desc'    => sprintf( _x( 'This will enabled experimental features in %s and various extensions.', 'settings', 'groundhogg' ), white_labeled_name() ),
				'type'    => 'checkbox',
				'atts'    => [
					'label' => __( 'Enable' ),
					'name'  => 'gh_enable_experimental_features',
					'id'    => 'gh_enable_experimental_features',
					'value' => 'on',
				],
			],
			'gh_get_beta_versions'                   => [
				'id'      => 'gh_get_beta_versions',
				'section' => 'developer',
				'label'   => _x( 'Get updates for beta versions of extensions!', 'settings', 'groundhogg' ),
				'desc'    => _x( 'This will show automatic updates or extensions which may have experimental features.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => [
					'label' => __( 'Enable' ),
					'name'  => 'gh_get_beta_versions',
					'id'    => 'gh_get_beta_versions',
					'value' => 'on',
				],
			],
			'gh_affiliate_id'                        => [
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
			],
			'gh_affiliate_link_in_email'             => [
				'id'      => 'gh_affiliate_link_in_email',
				'section' => 'affiliate',
				'label'   => _x( 'Affiliate Link in emails', 'settings', 'groundhogg' ),
				'desc'    => _x( 'This settings adds affiliate link in every email you send using Groundhogg.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => [
					'label' => __( 'Enable', 'groundhogg' ),
					'name'  => 'gh_affiliate_link_in_email',
					'id'    => 'gh_affiliate_link_in_email',
				],
			],
			'gh_send_notifications_on_event_failure' => [
				'id'      => 'gh_send_notifications_on_event_failure',
				'section' => 'event_notices',
				'label'   => _x( 'Event Failure Notifications', 'settings', 'groundhogg' ),
				'desc'    => _x( 'This will let you know if something goes wrong in a funnel so you can fix it.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => [
					'label' => __( 'Enable' ),
					'name'  => 'gh_send_notifications_on_event_failure',
					'id'    => 'gh_send_notifications_on_event_failure',
					'value' => 'on',
				],
			],
			'gh_event_failure_notification_email'    => [
				'id'      => 'gh_event_failure_notification_email',
				'section' => 'event_notices',
				'label'   => _x( 'Event Failure Notification Email', 'settings', 'groundhogg' ),
				'desc'    => _x( 'The email which you would like to send failure notifications to.', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => [
					'type'        => 'email',
					'id'          => 'gh_event_failure_notification_email',
					'name'        => 'gh_event_failure_notification_email',
					'placeholder' => get_option( 'admin_email' )
				],
			],
			'gh_ignore_event_errors'    => [
				'id'      => 'gh_ignore_event_errors',
				'section' => 'event_notices',
				'label'   => _x( 'Ignore Failed Events', 'settings', 'groundhogg' ),
				'desc'    => _x( 'A list of error codes you wish to ignore in the event failure report. Enter one per line. For example, <code>wp_mail_failed</code>.', 'settings', 'groundhogg' ),
				'type'    => 'textarea',
			],
			'gh_script_debug'                        => [
				'id'      => 'gh_script_debug',
				'section' => 'developer',
				'label'   => _x( 'Enable script debug mode', 'settings', 'groundhogg' ),
				'desc'    => _x( 'This will attempt to load full JS files instead of minified JS files for debugging.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => [
					'label' => __( 'Enable' ),
					'name'  => 'gh_script_debug',
					'id'    => 'gh_script_debug',
					'value' => 'on',
				],
			],
			'gh_use_object_cache'                    => [
				'id'      => 'gh_use_object_cache',
				'section' => 'performance',
				'label'   => _x( 'Enable Object Caching', 'settings', 'groundhogg' ),
				'desc'    => _x( 'Use the WordPress core object caching system to improve performance. This may cause strange behaviour on some hosts.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => [
					'label' => __( 'Enable' ),
					'name'  => 'gh_use_object_cache',
					'id'    => 'gh_use_object_cache',
					'value' => 'on',
				],
			],
			'gh_ignore_user_precedence'              => [
				'id'      => 'gh_ignore_user_precedence',
				'section' => 'page_tracking',
				'label'   => _x( 'Disable logged in user tracking precedence', 'settings', 'groundhogg' ),
				'desc'    => sprintf( _x( 'By default, %s will always show info of a logged in user before referencing information from tracking links or forms. You can disable this behaviour with this option.', 'settings', 'groundhogg' ), white_labeled_name() ),
				'type'    => 'checkbox',
				'atts'    => [
					'label' => __( 'Disable' ),
					'name'  => 'gh_ignore_user_precedence',
					'id'    => 'gh_ignore_user_precedence',
					'value' => 'on',
				],
			],
			'gh_disable_page_tracking'              => [
				'id'      => 'gh_disable_page_tracking',
				'section' => 'page_tracking',
				'label'   => _x( 'Disable frontend page tracking', 'settings', 'groundhogg' ),
				'desc'    => 'The journey of your contacts on the frontend of your site, and form impressions, will no longer be tracked.',
				'type'    => 'checkbox',
				'atts'    => [
					'label' => __( 'Disable' ),
					'value' => 'on',
				],
			],
			'gh_hide_tooltips'                       => [
				'id'      => 'gh_hide_tooltips',
				'section' => 'interface',
				'label'   => _x( 'Hide Tooltips', 'settings', 'groundhogg' ),
				'desc'    => _x( 'This will hides the tooltips user see in new installations.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => [
					'label' => __( 'Hide', 'groundhogg' ),
					'name'  => 'gh_hide_tooltips',
					'id'    => 'gh_hide_tooltips',
					'value' => 'on',
				],
			],
			'gh_is_admin_bar_widget_disabled'        => [
				'id'      => 'gh_is_admin_bar_widget_disabled',
				'section' => 'interface',
				'label'   => _x( 'Hide Admin Toolbar Widget', 'settings', 'groundhogg' ),
				'desc'    => _x( 'Hide the Admin Toolbar Widget.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => [
					'label' => __( 'Hide', 'groundhogg' ),
					'name'  => 'gh_is_admin_bar_widget_disabled',
					'id'    => 'gh_is_admin_bar_widget_disabled',
					'value' => 'on',
				],
			],
			'gh_default_contact_tab'                 => [
				'id'      => 'gh_default_contact_tab',
				'section' => 'interface',
				'label'   => _x( 'Default Contact Tab', 'settings', 'groundhogg' ),
				'desc'    => _x( 'Which tab should be selected by default when opening a contact record.', 'settings', 'groundhogg' ),
				'type'    => 'dropdown',
				'atts'    => [
					'name'        => 'gh_default_contact_tab',
					'id'          => 'gh_default_contact_tab',
					'options'     => get_valid_contact_tabs(),
					'option_none' => false
				],
			],
			'gh_show_legacy_steps'                   => [
				'id'      => 'gh_show_legacy_steps',
				'section' => 'interface',
				'label'   => _x( 'Enable Legacy Funnel Steps', 'settings', 'groundhogg' ),
				'desc'    => _x( 'This will allow supported legacy funnels steps to be added in the funnel editor.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => [
					'label' => __( 'Show', 'groundhogg' ),
					'name'  => 'gh_show_legacy_steps',
					'id'    => 'gh_show_legacy_steps',
					'value' => 'on',
				],
			],
			'gh_force_custom_step_names'             => [
				'id'      => 'gh_force_custom_step_names',
				'section' => 'interface',
				'label'   => _x( 'Enable Custom Step Titles', 'settings', 'groundhogg' ),
				'desc'    => _x( 'This will ensure all step titles can be customized.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => [
					'label' => __( 'Enable', 'groundhogg' ),
					'name'  => 'gh_force_custom_step_names',
					'id'    => 'gh_force_custom_step_names',
					'value' => 'on',
				],
			],
			'gh_privacy_policy'                      => [
				'id'      => 'gh_privacy_policy',
				'section' => 'compliance',
				'label'   => __( 'Privacy Policy' ),
				'desc'    => _x( 'Link to your privacy policy.', 'settings', 'groundhogg' ),
				'type'    => 'link_picker',
				'atts'    => [
					'name' => 'gh_privacy_policy',
					'id'   => 'gh_privacy_policy',
				],
			],
			'gh_terms'                               => [
				'id'      => 'gh_terms',
				'section' => 'compliance',
				'label'   => _x( 'Terms & Conditions (Terms of Service)', 'settings', 'groundogg' ),
				'desc'    => _x( 'Link to your terms & conditions.', 'settings', 'groundhogg' ),
				'type'    => 'link_picker',
				'atts'    => [
					'name' => 'gh_terms',
					'id'   => 'gh_terms',
				],
			],
			'gh_strict_confirmation'                 => [
				'id'      => 'gh_strict_confirmation',
				'section' => 'compliance',
				'label'   => _x( 'Only send to confirmed emails.', 'settings', 'groundhogg' ),
				'desc'    => _x( 'This will stop emails being sent to contacts who do not have confirmed emails outside of the below grace period.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => [
					'label' => __( 'Enable' ),
					//keep brackets for backwards compat
					'name'  => 'gh_strict_confirmation[]',
					'id'    => 'gh_strict_confirmation',
					'value' => 'on',
				],
			],
			'gh_confirmation_grace_period'           => [
				'id'      => 'gh_confirmation_grace_period',
				'section' => 'compliance',
				'label'   => _x( 'Email confirmation grace period', 'settings', 'groundhogg' ),
				'desc'    => _x( 'The number of days for which you can send an email to a contact after they are created but their email has not been confirmed. The default is 14 days.', 'settings', 'groundhogg' ),
				'type'    => 'number',
				'atts'    => [
					'id'          => 'gh_confirmation_grace_period',
					'name'        => 'gh_confirmation_grace_period',
					'placeholder' => '14'
				],

			],
			'gh_enable_gdpr'                         => [
				'id'      => 'gh_enable_gdpr',
				'section' => 'compliance',
				'label'   => _x( 'Enable GDPR features', 'settings', 'groundhogg' ),
				'desc'    => _x( 'This will add a consent box to your forms as well as a "Delete Everything" Button to your email preferences page.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => [
					'label' => __( 'Enable' ),
					//keep brackets for backwards compat
					'name'  => 'gh_enable_gdpr[]',
					'id'    => 'gh_enable_gdpr',
					'value' => 'on',
				],
			],
			'gh_strict_gdpr'                         => [
				'id'      => 'gh_strict_gdpr',
				'section' => 'compliance',
				'label'   => _x( 'Do not send email without consent', 'settings', 'groundhogg' ),
				'desc'    => _x( 'This will prevent your system from sending emails to contacts for which you do not have explicit consent. Only works if GDPR features are enabled.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => [
					'label' => __( 'Enable' ),
					//keep brackets for backwards compat
					'name'  => 'gh_strict_gdpr[]',
					'id'    => 'gh_strict_gdpr',
					'value' => 'on',
				],
			],
			'gh_disable_unnecessary_cookies'         => [
				'id'      => 'gh_disable_unnecessary_cookies',
				'section' => 'cookies',
				'label'   => _x( 'Disable unnecessary cookies', 'settings', 'groundhogg' ),
				'desc'    => _x( 'This will prevent the <code>groundhogg-lead-source</code>, <code>groundhogg-page-visits</code>, and <code>groundhogg-form-impressions</code> cookies from being set.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => [
					'label' => __( 'Disable' ),
					'name'  => 'gh_disable_unnecessary_cookies',
					'id'    => 'gh_disable_unnecessary_cookies',
					'value' => 'on',
				],
			],
			'gh_consent_cookie_name'                 => [
				'id'      => 'gh_consent_cookie_name',
				'section' => 'cookies',
				'label'   => _x( 'Consent cookie name', 'settings', 'groundhogg' ),
				'desc'    => _x( 'The name of the cookie that records consent to allow cookies. This is provided by a third party plugin. This has no effect unless GDPR features are enabled.', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => [
					'placeholder' => 'viewed_cookie_policy',
					'name'        => 'gh_consent_cookie_name',
					'id'          => 'gh_consent_cookie_name',
				],
			],
			'gh_consent_cookie_value'                => [
				'id'      => 'gh_consent_cookie_value',
				'section' => 'cookies',
				'label'   => _x( 'Consent cookie value', 'settings', 'groundhogg' ),
				'desc'    => _x( 'The value of the consent cookie indicating acceptance to use cookies. This is provided by a third party plugin. This has no effect unless GDPR features are enabled.', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => [
					'placeholder' => 'yes',
					'name'        => 'gh_consent_cookie_value',
					'id'          => 'gh_consent_cookie_value',
				],
			],
			'gh_recaptcha_site_key'                  => [
				'id'      => 'gh_recaptcha_site_key',
				'section' => 'captcha',
				'label'   => _x( 'Site Key', 'settings', 'groundhogg' ),
				'desc'    => _x( 'This is the key which faces the users on the front-end', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => [
					'name' => 'gh_recaptcha_site_key',
					'id'   => 'gh_recaptcha_site_key',
				],
			],
			'gh_recaptcha_secret_key'                => [
				'id'      => 'gh_recaptcha_secret_key',
				'section' => 'captcha',
				'label'   => _x( 'Secret Key', 'settings', 'groundhogg' ),
				'desc'    => _x( 'Never ever ever share this with anyone!', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => [
					'name' => 'gh_recaptcha_secret_key',
					'id'   => 'gh_recaptcha_secret_key',
				],
			],
			'gh_recaptcha_version'                   => [
				'id'      => 'gh_recaptcha_version',
				'section' => 'captcha',
				'label'   => _x( 'Version', 'settings', 'groundhogg' ),
				'desc'    => _x( 'Which version of reCAPTCHA you want to use.', 'settings', 'groundhogg' ),
				'type'    => 'dropdown',
				'atts'    => [
					'name'        => 'gh_recaptcha_version',
					'id'          => 'gh_recaptcha_version',
					'options'     => [
						'v2' => 'V2',
						'v3' => 'V3'
					],
					'option_none' => false
				],
			],
			'gh_recaptcha_v3_score_threshold'        => [
				'id'      => 'gh_recaptcha_v3_score_threshold',
				'section' => 'captcha',
				'label'   => _x( 'v3 Score Threshold', 'settings', 'groundhogg' ),
				'desc'    => _x( 'The score threshold to block form submissions. <code>0.5</code> by default.', 'settings', 'groundhogg' ),
				'type'    => 'number',
				'atts'    => [
					'name'        => 'gh_recaptcha_v3_score_threshold',
					'id'          => 'gh_recaptcha_v3_score_threshold',
					'min'         => 0,
					'max'         => 1,
					'step'        => '0.1',
					'placeholder' => '0.5'
				],
			],
			'gh_imap_inbox_address'                  => [
				'id'      => 'gh_imap_inbox_address',
				'section' => 'imap',
				'label'   => _x( 'IMAP Inbox', 'settings', 'groundhogg' ),
				'desc'    => _x( 'This is the inbox which emails and replies will be sent to.', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => [
					'type'        => 'email',
					'name'        => 'gh_imap_inbox_address',
					'id'          => 'gh_imap_inbox_address',
					'placeholder' => 'replies@' . ( ( substr( $_SERVER['SERVER_NAME'], 0, 4 ) == 'www.' ) ? substr( $_SERVER['SERVER_NAME'], 4 ) : $_SERVER['SERVER_NAME'] ),
				],
			],
			'gh_imap_inbox_password'                 => [
				'id'      => 'gh_imap_inbox_password',
				'section' => 'imap',
				'type'    => 'input',
				'label'   => _x( 'IMAP Inbox Password', 'settings', 'groundhogg' ),
				'desc'    => _x( 'This password to access the inbox.', 'settings', 'groundhogg' ),
				'atts'    => [
					'type' => 'password',
					'name' => 'gh_imap_inbox_password',
					'id'   => 'gh_imap_inbox_password',
				],
			],
			'gh_imap_inbox_host'                     => [
				'id'      => 'gh_imap_inbox_host',
				'section' => 'imap',
				'label'   => _x( 'Mail Server', 'settings', 'groundhogg' ),
				'desc'    => _x( 'This is the domain your email inbox is hosted. Most likely mail.yourdomain.com', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => [
					'type'        => 'text',
					'name'        => 'gh_imap_inbox_host',
					'id'          => 'gh_imap_inbox_host',
					'placeholder' => 'mail.' . ( ( substr( $_SERVER['SERVER_NAME'], 0, 4 ) == 'www.' ) ? substr( $_SERVER['SERVER_NAME'], 4 ) : $_SERVER['SERVER_NAME'] ),
				],
			],
			'gh_imap_inbox_port'                     => [
				'id'      => 'gh_imap_inbox_port',
				'section' => 'imap',
				'label'   => _x( 'IMAP Port', 'settings', 'groundhogg' ),
				'desc'    => _x( 'Most IMAP ports are 993.', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => [
					'type'        => 'number',
					'name'        => 'gh_imap_inbox_port',
					'id'          => 'gh_imap_inbox_port',
					'placeholder' => 993,
				],
			],
			'gh_bounce_inbox'                        => [
				'id'      => 'gh_bounce_inbox',
				'section' => 'bounces',
				'label'   => _x( 'Bounce Inbox', 'settings', 'groundhogg' ),
				'desc'    => _x( 'This is the inbox which emails will be sent to.', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => [
					'type'        => 'email',
					'name'        => 'gh_bounce_inbox',
					'id'          => 'gh_bounce_inbox',
					'placeholder' => 'bounce@' . ( ( substr( $_SERVER['SERVER_NAME'], 0, 4 ) == 'www.' ) ? substr( $_SERVER['SERVER_NAME'], 4 ) : $_SERVER['SERVER_NAME'] ),
				],
			],
			'gh_bounce_inbox_password'               => [
				'id'      => 'gh_bounce_inbox_password',
				'section' => 'bounces',
				'type'    => 'input',
				'label'   => _x( 'Bounce Inbox Password', 'settings', 'groundhogg' ),
				'desc'    => _x( 'This password to access the inbox.', 'settings', 'groundhogg' ),
				'atts'    => [
					'type' => 'password',
					'name' => 'gh_bounce_inbox_password',
					'id'   => 'gh_bounce_inbox_password',
				],
			],
			'gh_bounce_inbox_host'                   => [
				'id'      => 'gh_bounce_inbox_host',
				'section' => 'bounces',
				'label'   => _x( 'Mail Server', 'settings', 'groundhogg' ),
				'desc'    => _x( 'This is the domain your email inbox is hosted. Most likely mail.yourdomain.com', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => [
					'type'        => 'text',
					'name'        => 'gh_bounce_inbox_host',
					'id'          => 'gh_bounce_inbox_host',
					'placeholder' => 'mail.' . ( ( substr( $_SERVER['SERVER_NAME'], 0, 4 ) == 'www.' ) ? substr( $_SERVER['SERVER_NAME'], 4 ) : $_SERVER['SERVER_NAME'] ),
				],
			],
			'gh_bounce_inbox_port'                   => [
				'id'      => 'gh_bounce_inbox_port',
				'section' => 'bounces',
				'label'   => _x( 'IMAP Port', 'settings', 'groundhogg' ),
				'desc'    => _x( 'The bounce checker requires an IMAP connection. Most IMAP ports are 993.', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => [
					'type'        => 'number',
					'name'        => 'gh_bounce_inbox_port',
					'id'          => 'gh_bounce_inbox_port',
					'placeholder' => 993,
				],
			],
			'gh_override_from_name'                  => [
				'id'      => 'gh_override_from_name',
				'section' => 'overrides',
				'label'   => _x( 'Default From Name', 'settings', 'groundhogg' ),
				'desc'    => _x( 'If no <b>From Name</b> is available default to this.', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => [
					'name'        => 'gh_override_from_name',
					'id'          => 'gh_override_from_name',
					'placeholder' => Plugin::$instance->settings->get_option( 'gh_business_name' ),
				],
			],
			'gh_override_from_email'                 => [
				'id'      => 'gh_override_from_email',
				'section' => 'overrides',
				'label'   => _x( 'Default From Email', 'settings', 'groundhogg' ),
				'desc'    => _x( 'If no <b>From Email Address</b> is available default to this.', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => [
					'type'        => 'email',
					'name'        => 'gh_override_from_email',
					'id'          => 'gh_override_from_email',
					'placeholder' => Plugin::$instance->settings->get_option( 'admin_email' ),
				],
			],
			'gh_email_footer_alignment'              => [
				'id'      => 'gh_email_footer_alignment',
				'section' => 'footer',
				'label'   => _x( 'Email Footer Alignment', 'settings', 'groundhogg' ),
				'desc'    => _x( 'The alignment of the email footer in all emails.', 'settings', 'groundhogg' ),
				'type'    => 'dropdown',
				'atts'    => [
					'name'        => 'gh_email_footer_alignment',
					'id'          => 'gh_email_footer_alignment',
					'options'     => [
						'left'   => __( 'Left' ),
						'center' => __( 'Center' ),
					],
					'option_none' => false,
				],
			],
			'gh_custom_email_footer_text'            => [
				'id'      => 'gh_custom_email_footer_text',
				'section' => 'footer',
				'label'   => _x( 'Custom Footer Text', 'settings', 'groundhogg' ),
				'desc'    => _x( 'Text that will appear before the footer in every email. Accepts HTML and plain text.', 'settings', 'groundhogg' ),
				'type'    => 'editor',
				'args'    => [ 'sanitize_callback' => 'wp_kses_post' ],
				'atts'    => [ 'replacements_button' => true ],
			],
			'gh_enable_tag_mapping'                  => [
				'id'      => 'gh_enable_tag_mapping',
				'section' => 'interface',
				'label'   => _x( 'Enable Legacy Tag Mapping', 'settings', 'groundhogg' ),
				'desc'    => _x( 'Tag mapping for opt-in status and user roles was originally introduced as a stop-gap measure. There are now much better methods of filtering contacts based on opt-in status and user role.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => [
					'label' => __( 'Enable' ),
					'name'  => 'gh_enable_tag_mapping',
					'id'    => 'gh_enable_tag_mapping',
					'value' => 'on',
				],
			],
			'gh_confirmed_tag'                       => [
				'id'      => 'gh_confirmed_tag',
				'section' => 'optin_status_tags',
				'label'   => _x( 'Confirmed Tag', 'settings', 'groundhogg' ),
				'desc'    => _x( 'All confirmed contacts will have this tag.', 'settings', 'groundhogg' ),
				'type'    => 'tag_picker',
				'atts'    => [
					'name'  => 'gh_confirmed_tag',
					'id'    => 'gh_confirmed_tag',
					'class' => 'gh-single-tag-picker'
				],
			],
			'gh_unconfirmed_tag'                     => [
				'id'      => 'gh_unconfirmed_tag',
				'section' => 'optin_status_tags',
				'label'   => _x( 'Unconfirmed Tag', 'settings', 'groundhogg' ),
				'desc'    => _x( 'All unconfirmed contacts will have this tag.', 'settings', 'groundhogg' ),
				'type'    => 'tag_picker',
				'atts'    => [
					'name'  => 'gh_unconfirmed_tag',
					'id'    => 'gh_unconfirmed_tag',
					'class' => 'gh-single-tag-picker'
				],
			],
			'gh_unsubscribed_tag'                    => [
				'id'      => 'gh_unsubscribed_tag',
				'section' => 'optin_status_tags',
				'label'   => _x( 'Unsubscribed Tag', 'settings', 'groundhogg' ),
				'desc'    => _x( 'All unsubscribed contacts will have this tag.', 'settings', 'groundhogg' ),
				'type'    => 'tag_picker',
				'atts'    => [
					'name'  => 'gh_unsubscribed_tag',
					'id'    => 'gh_unsubscribed_tag',
					'class' => 'gh-single-tag-picker'
				],
			],
			'gh_spammed_tag'                         => [
				'id'      => 'gh_spammed_tag',
				'section' => 'optin_status_tags',
				'label'   => _x( 'Spam Tag', 'settings', 'groundhogg' ),
				'desc'    => _x( 'All contacts which are marked as spam will have this tag.', 'settings', 'groundhogg' ),
				'type'    => 'tag_picker',
				'atts'    => [
					'name'  => 'gh_spammed_tag',
					'id'    => 'gh_spammed_tag',
					'class' => 'gh-single-tag-picker'
				],
			],
			'gh_bounced_tag'                         => [
				'id'      => 'gh_bounced_tag',
				'section' => 'optin_status_tags',
				'label'   => _x( 'Bounced Tag', 'settings', 'groundhogg' ),
				'desc'    => _x( 'All contacts which have bounced will have this tag.', 'settings', 'groundhogg' ),
				'type'    => 'tag_picker',
				'atts'    => [
					'name'  => 'gh_bounced_tag',
					'id'    => 'gh_bounced_tag',
					'class' => 'gh-single-tag-picker'
				],
			],
			'gh_complained_tag'                      => [
				'id'      => 'gh_complained_tag',
				'section' => 'optin_status_tags',
				'label'   => _x( 'Complained Tag', 'settings', 'groundhogg' ),
				'desc'    => _x( 'All contacts which have complained will have this tag.', 'settings', 'groundhogg' ),
				'type'    => 'tag_picker',
				'atts'    => [
					'name'  => 'gh_complained_tag',
					'id'    => 'gh_complained_tag',
					'class' => 'gh-single-tag-picker'
				],
			],
			'gh_monthly_tag'                         => [
				'id'      => 'gh_monthly_tag',
				'section' => 'optin_status_tags',
				'label'   => _x( 'Subscribed Monthly Tag', 'settings', 'groundhogg' ),
				'desc'    => _x( 'All contacts which have requested monthly emails will have this tag.', 'settings', 'groundhogg' ),
				'type'    => 'tag_picker',
				'atts'    => [
					'name'  => 'gh_monthly_tag',
					'id'    => 'gh_monthly_tag',
					'class' => 'gh-single-tag-picker'
				],
			],
			'gh_weekly_tag'                          => [
				'id'      => 'gh_weekly_tag',
				'section' => 'optin_status_tags',
				'label'   => _x( 'Subscribed Weekly Tag', 'settings', 'groundhogg' ),
				'desc'    => _x( 'All contacts which have requested weekly emails will have this tag.', 'settings', 'groundhogg' ),
				'type'    => 'tag_picker',
				'atts'    => [
					'name'  => 'gh_weekly_tag',
					'id'    => 'gh_weekly_tag',
					'class' => 'gh-single-tag-picker'
				],
			],
			'gh_marketable_tag'                      => [
				'id'      => 'gh_marketable_tag',
				'section' => 'optin_status_tags',
				'label'   => _x( 'Marketable Tag', 'settings', 'groundhogg' ),
				'desc'    => _x( 'All contacts which are considered marketable will have this tag.', 'settings', 'groundhogg' ),
				'type'    => 'tag_picker',
				'atts'    => [
					'name'  => 'gh_marketable_tag',
					'id'    => 'gh_marketable_tag',
					'class' => 'gh-single-tag-picker'
				],
			],
			'gh_non_marketable_tag'                  => [
				'id'      => 'gh_non_marketable_tag',
				'section' => 'optin_status_tags',
				'label'   => _x( 'Non Marketable Tag', 'settings', 'groundhogg' ),
				'desc'    => _x( 'All contacts which are considered unmarketable will have this tag.', 'settings', 'groundhogg' ),
				'type'    => 'tag_picker',
				'atts'    => [
					'name'  => 'gh_non_marketable_tag',
					'id'    => 'gh_non_marketable_tag',
					'class' => 'gh-single-tag-picker'
				],
			],
			'gh_open_tracking_delay'                 => [
				'section' => 'tracking',
				'label'   => _x( 'Open Tracking Delay', 'settings', 'groundhogg' ),
				'desc'    => _x( 'Opens that happen within the delay period (in seconds) after an email is sent will be ignored. <br/>Recommended value <code>60</code> seconds. <br/>Set to <code>0</code> or leave empty for no delay.', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => [
					'type'     => 'number',
					'class'    => 'input',
					'disabled' => defined( 'GH_OPEN_TRACKING_DELAY' )
				]
			],
			'gh_click_tracking_delay'                => [
				'section' => 'tracking',
				'label'   => _x( 'Click Tracking Delay', 'settings', 'groundhogg' ),
				'desc'    => _x( 'Clicks that are tracked within the delay period (in seconds) after an email is sent will be ignored. <br/>Recommended value <code>90</code> seconds. <br/>Set to <code>0</code> or leave empty for no delay.', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => [
					'type'     => 'number',
					'class'    => 'input',
					'disabled' => defined( 'GH_CLICK_TRACKING_DELAY' )
				]
			],
			'gh_disable_open_tracking'               => [
				'id'      => 'gh_disable_open_tracking',
				'section' => 'tracking',
				'label'   => _x( 'Disable Email Open Tracking', 'settings', 'groundhogg' ),
				'desc'    => _x( 'Disable all email open tracking.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => [
					'label' => __( 'Disable' ),
					'name'  => 'gh_disable_open_tracking',
					'id'    => 'gh_disable_open_tracking',
					'value' => 'on',
				],
			],
			'gh_disable_click_tracking'              => [
				'id'      => 'gh_disable_click_tracking',
				'section' => 'tracking',
				'label'   => _x( 'Disable Email Link Click Tracking', 'settings', 'groundhogg' ),
				'desc'    => _x( 'Disable all link click tracking in emails.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => [
					'label' => __( 'Disable' ),
					'name'  => 'gh_disable_click_tracking',
					'id'    => 'gh_disable_click_tracking',
					'value' => 'on',
				],
			],
			'gh_url_tracking_exclusions'             => [
				'id'      => 'gh_url_tracking_exclusions',
				'section' => 'tracking',
				'label'   => _x( 'Tracking URL Exclusions', 'settings', 'groundhogg' ),
				'desc'    => sprintf( _x( 'URLs containing these strings will not be tracked. For example, adding <code>/my-page/</code> would exclude <code>%s/my-page/download/</code>. You can also enter full URLs and URLs of other domains such as <code>https://wordpress.org</code>. To match an exact path use <code>$</code> at the end of the path.', 'settings', 'groundhogg' ), site_url() ),
				'type'    => 'textarea',
				'atts'    => [
					'name' => 'gh_url_tracking_exclusions',
					'id'   => 'gh_url_tracking_exclusions',
				],
			],
			'gh_wordpress_email_service'             => [
				'id'      => 'gh_wordpress_email_service',
				'section' => 'outgoing_email_config',
				'label'   => _x( 'WordPress Email', 'settings', 'groundhogg' ),
				'desc'    => _x( 'Choose which installed service should handle core WordPress email. This service will apply to <b>all WordPress email</b> and email from third party plugins like <b>LifterLMS</b> or <b>BuddyBoss</b>.</p><p class="description"><code>WordPress Default</code> is whichever email service WordPress is using at the moment. This could be your server\'s email or a third party SMTP plugin.', 'settings', 'groundhogg' ),
				'type'    => 'dropdown',
				'atts'    => [
					'name'        => 'gh_wordpress_email_service',
					'id'          => 'gh_wordpress_email_service',
					'option_none' => false,
					'options'     => Groundhogg_Email_Services::dropdown()
				],
			],
			'gh_transactional_email_service'         => [
				'id'      => 'gh_transactional_email_service',
				'section' => 'outgoing_email_config',
				'label'   => _x( 'Transactional Email', 'settings', 'groundhogg' ),
				'desc'    => sprintf( _x( 'Choose which installed service should handle transactional email from %1$s. This service will apply to %1$s emails which have their <code>message type</code> set to <b>Transactional</b>, admin notifications and other %1$s notifications.', 'settings', 'groundhogg' ), white_labeled_name() ),
				'type'    => 'dropdown',
				'atts'    => [
					'name'        => 'gh_transactional_email_service',
					'id'          => 'gh_transactional_email_service',
					'option_none' => false,
					'options'     => Groundhogg_Email_Services::dropdown()
				],
			],
			'gh_marketing_email_service'             => [
				'id'      => 'gh_marketing_email_service',
				'section' => 'outgoing_email_config',
				'label'   => _x( 'Marketing Email', 'settings', 'groundhogg' ),
				'desc'    => sprintf( _x( 'Choose which installed service should handle marketing email from %1$s. This service will only apply to %1$s emails which have their <code>message type</code> set to <b>Marketing</b>.', 'settings', 'groundhogg' ), white_labeled_name() ),
				'type'    => 'dropdown',
				'atts'    => [
					'name'        => 'gh_marketing_email_service',
					'id'          => 'gh_marketing_email_service',
					'option_none' => false,
					'options'     => Groundhogg_Email_Services::dropdown()
				],
			],
			'gh_log_emails'                          => [
				'id'      => 'gh_log_emails',
				'section' => 'email_logging',
				'label'   => _x( 'Enable Email Logging', 'settings', 'groundhogg' ),
				'desc'    => sprintf( _x( 'This will have %s save all emails sent to the database for a period of time. Useful for debugging or verifying someone received an email.', 'settings', 'groundhogg' ), white_labeled_name() ),
				'type'    => 'checkbox',
				'atts'    => [
					'label' => __( 'Enable' ),
					'name'  => 'gh_log_emails',
					'id'    => 'gh_log_emails',
					'value' => 'on',
				],
			],
			'gh_email_log_retention'                 => [
				'id'      => 'gh_email_log_retention',
				'section' => 'email_logging',
				'label'   => _x( 'Email Log Retention', 'settings', 'groundhogg' ),
				'desc'    => sprintf( _x( 'The number of days to retain logged emails. Logs older then <code>%d</code> days will be deleted.', 'settings', 'groundhogg' ), get_option( 'gh_email_log_retention' ) ?: 14 ),
				'type'    => 'input',
				'atts'    => [
					'type'        => 'number',
					'min'         => 0,
					'class'       => 'input',
					'name'        => 'gh_email_log_retention',
					'id'          => 'gh_email_log_retention',
					'placeholder' => 14,
				],
			],
			'gh_enable_one_click_unsubscribe'        => [
				'id'      => 'gh_enable_one_click_unsubscribe',
				'section' => 'unsubscribe',
				'label'   => _x( 'Enable One-Click Unsubscribe', 'settings', 'groundhogg' ),
				'desc'    => _x( 'When contacts click the unsubscribe link in emails they will be instantly unsubscribed instead of having to confirm. This is not recommended because inbox bots could follow the link and unsubscribe contacts accidentally.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => [
					'label' => __( 'Enable' ) . ' <i>(' . __( 'Not recommended', 'groundhogg' ) . ')</i>',
					'name'  => 'gh_enable_one_click_unsubscribe',
					'id'    => 'gh_enable_one_click_unsubscribe',
					'value' => 'on',
				],
			],
			'gh_unsubscribe_email'                   => [
				'id'      => 'gh_unsubscribe_email',
				'section' => 'unsubscribe',
				'label'   => _x( 'Send Unsubscribe Email Notifications to...', 'settings', 'groundhogg' ),
				'desc'    => _x( 'Outlook, iCloud, Yahoo, and other inboxes will send unsubscribes email notifications to this email address.', 'settings', 'groundhogg' ),
				'type'    => 'input',
				'atts'    => [
					'type'        => 'email',
					'name'        => 'gh_unsubscribe_email',
					'id'          => 'gh_unsubscribe_email',
					'placeholder' => get_bloginfo( 'admin_email' ),
				],
			],
			'gh_disable_wp_cron'                     => [
				'id'      => 'gh_disable_wp_cron',
				'section' => 'wp_cron',
				'label'   => _x( 'Disable WP Cron.', 'settings', 'groundhogg' ),
				'desc'    => defined( 'DISABLE_WP_CRON' ) && ! defined( 'GH_SHOW_DISABLE_WP_CRON_OPTION' )
					? _x( 'WP Cron has been disabled by your host or in your <code>wp-config.php</code> file already.', 'settings', 'groundhogg' )
					: _x( 'Disable the built-in WP Cron system. This is recommended if you are using an external cron job.', 'settings', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => [
					'label'    => __( 'Disable' ),
					'name'     => 'gh_disable_wp_cron',
					'id'       => 'gh_disable_wp_cron',
					'value'    => 'on',
					'disabled' => defined( 'DISABLE_WP_CRON' ) && ! defined( 'GH_SHOW_DISABLE_WP_CRON_OPTION' ),
					'checked'  => defined( 'DISABLE_WP_CRON' ),
				],
			],
			'gh_purge_page_visits'                   => [
				'id'      => 'gh_purge_page_visits',
				'section' => 'page_tracking',
				'label'   => _x( 'Delete old page visit logs (recommended)', 'settings', 'groundhogg' ),
				'desc'    => __( 'To preserve storage in the database and overall performance it is recommended to delete old page visit logs.', 'groundhogg' ),
				'type'    => 'checkbox',
				'atts'    => [
					'label' => __( 'Enable' ),
					'name'  => 'gh_purge_page_visits',
					'id'    => 'gh_purge_page_visits',
					'value' => 'on',
				],
			],
			'gh_page_visits_log_retention'           => [
				'id'      => 'gh_page_visits_log_retention',
				'section' => 'page_tracking',
				'label'   => _x( 'Log retention', 'settings', 'groundhogg' ),
				'desc'    => sprintf( _x( 'The number of days to retain logged page visits. Logs older then <code>%d</code> days will be deleted.', 'settings', 'groundhogg' ), get_option( 'gh_page_visits_log_retention' ) ?: 90 ),
				'type'    => 'input',
				'atts'    => [
					'type'        => 'number',
					'min'         => 0,
					'class'       => 'input',
					'name'        => 'gh_page_visits_log_retention',
					'id'          => 'gh_page_visits_log_retention',
					'placeholder' => 90,
				],
			],
		];

		// Dependent settings

		return apply_filters( 'groundhogg/admin/settings/settings', $settings );
	}

	/**
	 * Register all the settings
	 */
	public function register_settings() {

		do_action( 'groundhogg/admin/register_settings/before', $this );

		foreach ( $this->settings as $id => $setting ) {

			$setting = wp_parse_args( $setting, [
				'id'   => $id,
				'type' => 'input',
				'atts' => []
			] );

			$setting['atts'] = wp_parse_args( $setting['atts'], [
				'id'   => $id,
				'name' => $id
			] );

			$this->settings[ $id ] = $setting;

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
	 * @param $tab
	 *
	 * @return bool
	 */
	protected function tab_has_sections( $tab ) {
		return count( array_filter( $this->sections, function ( $section ) use ( $tab ) {
				return $section['tab'] == $tab;
			} ) ) >= 1;
	}

	public function page() {
		do_action( "groundhogg/admin/{$this->get_slug()}/before" );

		?>
        <div id="" class="gh-header is-sticky no-padding display-flex flex-start" style="margin-left:-20px;padding-right: 10px">
			<?php header_icon(); ?>
            <h1><?php echo __( 'Settings' ); ?></h1>
			<?php echo html()->button( [
				'text'  => __( 'Save Changes' ),
				'class' => 'gh-button primary',
				'id'    => 'save-from-header'
			] ) ?>
        </div>
        <h2 class="gh-nav nav-tab-wrapper">
			<?php foreach ( $this->tabs as $id => $tab ):

				// Force API Tab & Licenses Tab
				if ( ! $this->tab_has_settings( $tab['id'] ) && ! in_array( $tab['id'], [
						'extensions',
						'api_tab'
					] ) ) {
					continue;
				}

				// Check for cap restriction on the tab...
				$cap = get_array_var( $tab, 'cap' );

				// ignore if there is no cap, but if there is one check if the user has required privileges...
				if ( $cap && ! current_user_can( $cap ) ) {
					continue;
				}

				?>

                <a href="?page=gh_settings&tab=<?php echo $tab['id']; ?>"
                   class="nav-tab <?php echo $this->active_tab() == $tab['id'] ? 'nav-tab-active' : ''; ?>"><?php _e( $tab['title'], 'groundhogg' ); ?></a>
			<?php endforeach; ?>
        </h2>
        <script>
          document.getElementById('save-from-header').addEventListener('click', e => {
            document.getElementById('primary-submit').click()
          })</script>
        <div class="wrap">

            <div id="notices">
				<?php Plugin::instance()->notices->notices(); ?>
            </div>
            <hr class="wp-header-end">
			<?php

			$this->view();

			?>
        </div>
		<?php

		do_action( "groundhogg/admin/{$this->get_slug()}/after" );
	}

	/**
	 * Output the settings content
	 */
//    public function settings_content()
	public function view() {
		?>
        <style>
            td .select2 {
                max-width: 300px;
            }
        </style>
        <div class="wrap">
			<?php
			settings_errors();
			$action = $this->tab_has_settings( $this->active_tab() ) ? 'options.php' : ''; ?>
            <form method="POST" enctype="multipart/form-data" action="<?php echo $action; ?>">

                <!-- BEGIN SETTINGS -->
				<?php
				if ( $this->tab_has_settings() && $this->user_can_access_tab() ) {

					settings_fields( 'gh_' . $this->active_tab() );
					do_settings_sections( 'gh_' . $this->active_tab() );
					do_action( "groundhogg/admin/settings/{$this->active_tab()}/after_settings" );
//					submit_button();

					echo html()->e( 'p', [], html()->button( [
						'text'  => __( 'Save Changes' ),
						'class' => 'gh-button primary',
						'type'  => 'submit',
						'id'    => 'primary-submit'
					] ) );

				}

				do_action( "groundhogg/admin/settings/{$this->active_tab()}/after_submit" );
				?>
                <!-- END SETTINGS -->
            </form>
			<?php do_action( "groundhogg/admin/settings/{$this->active_tab()}/after_form" ); ?>
        </div> <?php
	}

	public function settings_callback( $field ) {

		$constant_value = maybe_get_option_from_constant( null, $field['id'] );

		// Check if the option has been defined instead
		if ( $constant_value !== null && has_constant_support( $field['id'] ) ) {

			// protected option
			if ( isset( $field['atts'] ) && isset( $field['atts']['type'] ) && $field['atts']['type'] === 'password' ) {
				printf( '<p class="description">%s</p>', __( 'This option has been defined elsewhere. Probably in <code>wp-config.php</code>.', 'groundhogg' ) );

				return;
			}

			printf( '<p class="description">%s</p>', sprintf( __( 'This option has been defined elsewhere and is set to <code>%s</code>. Probably in <code>wp-config.php</code>.', 'groundhogg' ), $constant_value ) );

			return;
		}

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
			case 'checkboxes':
				$field['atts']['checked'] = $value;
				break;
			case 'input':
			default:
				$field['atts']['value'] = $value;
				break;
		}

		$field['atts']['id'] = esc_attr( sanitize_key( $field['id'] ) );

		echo html()->wrap( call_user_func( array(
			html(),
			$field['type']
		), $field['atts'] ), 'div', [ 'style' => [ 'max-width' => '700px' ] ] );

		if ( isset( $field['desc'] ) && $desc = $field['desc'] ) {
			printf( '<p class="description">%s</p>', $desc );
		}
	}


}
