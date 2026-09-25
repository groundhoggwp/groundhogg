<?php

namespace Groundhogg\Admin\Guided_Setup;

use Groundhogg\Admin\Admin_Page;
use Groundhogg\Background\Sync_Users_Last_Id;
use Groundhogg\Background_Tasks;
use Groundhogg\Classes\Inbox_Client;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\License_Manager;
use Groundhogg\Plugin;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_default_from_email;
use function Groundhogg\get_default_from_name;
use function Groundhogg\get_post_var;
use function Groundhogg\get_user_timezone;
use function Groundhogg\groundhogg_icon;
use function Groundhogg\is_option_enabled;
use function Groundhogg\is_white_labeled;
use function Groundhogg\notices;
use function Groundhogg\remote_post_json;
use function Groundhogg\utils;
use function Groundhogg\verify_admin_ajax_nonce;

/**
 * Guided Setup
 *
 * The few things a new install needs before it can send its first email: who the business is, who emails come
 * from, the privacy rules it's under, and optionally the license and receiving replies. Then it points at the first
 * useful thing to do. The UI is all JS, see assets/js/admin/features/guided-setup-v2.js
 *
 * @since       File available since Release 0.9
 * @subpackage  Admin/Guided Setup
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Guided_Setup extends Admin_Page {

	/**
	 * The most existing users to list for assigning roles, beyond that it's a job for the Users screen
	 */
	const MAX_TEAM_MEMBERS = 50;

	public function __construct() {
		parent::__construct();

		add_action( 'admin_notices', [ $this, 'maybe_show_guided_setup_nag' ] );
	}

	/**
	 * Show a notice to complete the guided setup
	 *
	 * @return void
	 */
	function maybe_show_guided_setup_nag() {

		if ( $this->is_current_page()
		     || ! current_user_can( 'manage_options' )
		     || is_option_enabled( 'gh_guided_setup_finished' )
		     || notices()->is_dismissed( 'guided_setup_nag' )
		     || is_white_labeled()
		) {
			return;
		}

		wp_enqueue_style( 'groundhogg-admin-element' );
		wp_enqueue_script( 'groundhogg-admin-data' );

		?>
        <div id="guided-setup-nag" class="notice notice-success display-flex gap-10 is-dismissible">
			<?php groundhogg_icon( 25 ) ?>
            <p>
				<?php
                /* translators: 1: opening anchor <a> tag, 2: closing anchor </a> tag */
                printf( esc_html__( 'Ready to get started with Groundhogg? Start the %1$sguided setup%2$s now!', 'groundhogg' ), "<a href=\"" . esc_url( admin_page_url( 'gh_guided_setup' ) ) . "\">", "</a>" ) ?>
            </p>
            <script>( ($) => {
                $('#guided-setup-nag').on('click', 'button.notice-dismiss', e => {
                  Groundhogg.api.ajax({
                    action  : 'gh_dismiss_notice',
                    notice  : 'guided_setup_nag',
                    _wpnonce: Groundhogg.nonces._wpnonce,
                  })
                })
              } )(jQuery)</script>
        </div>
		<?php
	}

	/**
	 * Add Ajax actions...
	 *
	 * @return void
	 */
	protected function add_ajax_actions() {
		// the troubleshooter activates licenses with this too
		add_action( 'wp_ajax_gh_guided_setup_license', [ $this, 'activate_license' ] );
		add_action( 'wp_ajax_gh_guided_setup_sync_users', [ $this, 'sync_users' ] );
		add_action( 'wp_ajax_gh_guided_setup_invite_user', [ $this, 'invite_user' ] );
		add_action( 'wp_ajax_gh_guided_setup_set_role', [ $this, 'set_role' ] );
		add_action( 'wp_ajax_gh_guided_setup_telemetry', [ $this, 'optin_to_telemetry' ] );
		add_action( 'wp_ajax_gh_guided_setup_subscribe', [ $this, 'subscribe_to_newsletter' ] );
	}

	/**
	 * Turn on telemetry, and maybe subscribe to the list along with it
	 */
	public function optin_to_telemetry() {

		if ( ! current_user_can( 'manage_options' ) || ! verify_admin_ajax_nonce() ) {
			wp_send_json_error();
		}

		$response = Plugin::instance()->stats_collection->optin( get_post_var( 'subscribed' ) === 'true' );

		// telemetry is on either way, only a problem here is worth telling them about, not one reaching groundhogg.io
		if ( is_wp_error( $response ) && $response->get_error_code() === 'invalid_email' ) {
			wp_send_json_error( new \WP_Error( 'invalid_email', __( 'Your WordPress account does not have a valid email address.', 'groundhogg' ) ) );
		}

		wp_send_json_success();
	}

	/**
	 * Subscribe the current user to the newsletter, without telemetry
	 */
	public function subscribe_to_newsletter() {

		if ( ! current_user_can( 'manage_options' ) || ! verify_admin_ajax_nonce() ) {
			wp_send_json_error();
		}

		$user = wp_get_current_user();

		remote_post_json( 'https://groundhogg.io/wp-json/gh/v3/webhook-listener?auth_token=NCM39k3&step_id=1641', [
			'email'     => $user->user_email,
			'name'      => $user->display_name,
			'time_zone' => get_user_timezone()->getName(),
		] );

		wp_send_json_success();
	}

	/**
	 * Don't redirect to the guided setup from the guided setup
	 *
	 * @return void
	 */
	public function maybe_redirect_to_guided_setup() {
	}

	/**
	 * Activates the provided license
	 */
	public function activate_license() {

		if ( ! current_user_can( 'manage_options' ) || ! verify_admin_ajax_nonce() ) {
			wp_send_json_error();
		}

		$license = sanitize_text_field( get_post_var( 'license' ) );

		if ( ! $license ) {
			wp_send_json_error( new \WP_Error( 'license_error', __( 'Enter your license key.', 'groundhogg' ) ) );
		}

		$result = License_Manager::activate_license( $license );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result );
		}

		wp_send_json_success();
	}

	/**
	 * Create contacts for the users of the site, in the background
	 */
	public function sync_users() {

		if ( ! current_user_can( 'edit_users' ) || ! current_user_can( 'add_contacts' ) || ! verify_admin_ajax_nonce() ) {
			wp_send_json_error();
		}

		if ( ! Background_Tasks::add( new Sync_Users_Last_Id() ) ) {
			wp_send_json_error( new \WP_Error( 'task_failed', __( 'The sync could not be started.', 'groundhogg' ) ) );
		}

		wp_send_json_success();
	}

	/**
	 * The roles that setup offers, the ones Groundhogg adds
	 *
	 * @return array role => [ name, description ]
	 */
	protected function team_roles() {

		$descriptions = [
			'marketer'      => __( 'Full access to Groundhogg: contacts, flows, emails, broadcasts and reports.', 'groundhogg' ),
			'sales_manager' => __( 'Works with every contact, their notes and tasks. Can view, but not edit, flows and emails.', 'groundhogg' ),
			'sales_rep'     => __( 'Works with the contacts they own. Can view, but not edit, flows and emails.', 'groundhogg' ),
		];

		$roles = [];

		foreach ( Plugin::instance()->roles->get_roles() as $role ) {
			$roles[ $role['role'] ] = [
				'name'        => $role['name'],
				'description' => $descriptions[ $role['role'] ] ?? '',
			];
		}

		return $roles;
	}

	/**
	 * Whether the current user can add users and give them roles
	 *
	 * @return bool
	 */
	protected function can_manage_team() {
		return current_user_can( 'create_users' ) && current_user_can( 'promote_users' );
	}

	/**
	 * A user as the team panel shows them
	 *
	 * @param \WP_User $user
	 *
	 * @return array
	 */
	protected function team_member( \WP_User $user ) {

		$role      = $user->roles ? reset( $user->roles ) : '';
		$wp_roles  = wp_roles()->get_names();
		$role_name = isset( $wp_roles[ $role ] ) ? translate_user_role( $wp_roles[ $role ] ) : '';

		return [
			'ID'        => $user->ID,
			'name'      => $user->display_name,
			'email'     => $user->user_email,
			'role'      => $role,
			'role_name' => $role_name,
		];
	}

	/**
	 * Users that could be on the team. Admins are left out so they can't be demoted from here by accident, and
	 * subscribers/customers because there are usually lots of them and they're not staff.
	 *
	 * @return array
	 */
	protected function team_members() {

		$users = get_users( [
			'role__not_in' => [ 'administrator', 'subscriber', 'customer' ],
			'exclude'      => [ get_current_user_id() ],
			'number'       => self::MAX_TEAM_MEMBERS,
			'orderby'      => 'display_name',
		] );

		return array_map( [ $this, 'team_member' ], $users );
	}

	/**
	 * Add a user with one of the team roles, and email them a link to set their password
	 */
	public function invite_user() {

		if ( ! $this->can_manage_team() || ! verify_admin_ajax_nonce() ) {
			wp_send_json_error();
		}

		$email = sanitize_email( get_post_var( 'email' ) );
		$name  = sanitize_text_field( get_post_var( 'name' ) );
		$role  = sanitize_key( get_post_var( 'role' ) );

		if ( ! is_email( $email ) ) {
			wp_send_json_error( new \WP_Error( 'invalid_email', __( 'Enter a valid email address.', 'groundhogg' ) ) );
		}

		if ( ! array_key_exists( $role, $this->team_roles() ) ) {
			wp_send_json_error( new \WP_Error( 'invalid_role', __( 'Pick a role.', 'groundhogg' ) ) );
		}

		if ( email_exists( $email ) ) {
			wp_send_json_error( new \WP_Error( 'user_exists', __( 'There is already a user with that email address. Give them a role from the list instead.', 'groundhogg' ) ) );
		}

		$name_parts = explode( ' ', $name, 2 );

		$user_id = wp_insert_user( [
			'user_login'   => $email,
			'user_email'   => $email,
			'user_pass'    => wp_generate_password(),
			'display_name' => $name ?: $email,
			'first_name'   => $name_parts[0] ?? '',
			'last_name'    => $name_parts[1] ?? '',
			'role'         => $role,
		] );

		if ( is_wp_error( $user_id ) ) {
			wp_send_json_error( $user_id );
		}

		wp_send_new_user_notifications( $user_id, 'user' );

		wp_send_json_success( [
			'member' => $this->team_member( get_userdata( $user_id ) ),
		] );
	}

	/**
	 * Give an existing user one of the team roles
	 */
	public function set_role() {

		if ( ! $this->can_manage_team() || ! verify_admin_ajax_nonce() ) {
			wp_send_json_error();
		}

		$user_id = absint( get_post_var( 'user_id' ) );
		$role    = sanitize_key( get_post_var( 'role' ) );
		$user    = get_userdata( $user_id );

		if ( ! $user || $user_id === get_current_user_id() || ! current_user_can( 'promote_user', $user_id ) || user_can( $user, 'manage_options' ) ) {
			wp_send_json_error( new \WP_Error( 'not_allowed', __( 'You can\'t change the role of that user.', 'groundhogg' ) ) );
		}

		if ( ! array_key_exists( $role, $this->team_roles() ) ) {
			wp_send_json_error( new \WP_Error( 'invalid_role', __( 'Pick a role.', 'groundhogg' ) ) );
		}

		$user->set_role( $role );

		wp_send_json_success( [
			'member' => $this->team_member( get_userdata( $user_id ) ),
		] );
	}

	public function screen_options() {
	}

	/**
	 * Adds additional actions.
	 *
	 * @return void
	 */
	protected function add_additional_actions() {
	}

	/**
	 * Get the page slug
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'gh_guided_setup';
	}

	/**
	 * Get the menu name
	 *
	 * @return string
	 */
	public function get_name() {
		return esc_html__( 'Guided Setup', 'groundhogg' );
	}

	/**
	 * The required minimum capability required to load the page
	 *
	 * @return string
	 */
	public function get_cap() {
		return 'manage_options';
	}

	/**
	 * Get the item type for this page
	 *
	 * @return mixed
	 */
	public function get_item_type() {
		return 'step';
	}

	/**
	 * Output the basic view.
	 *
	 * @return void
	 */
	public function view() {
		// silence is golden
	}

	/**
	 * @return string
	 */
	public function get_parent_slug() {
		return 'options.php';
	}

	/**
	 * The main output
	 */
	public function page() {
		?>
        <div id="guided-setup"></div><?php
	}

	/**
	 * How many users of the site don't have a contact yet
	 *
	 * @return int
	 */
	protected function count_unsynced_users() {

		$query = new Table_Query( 'contacts' );
		$query->where()->greaterThan( 'user_id', 0 );

		return max( 0, count_users()['total_users'] - $query->count() );
	}

	/**
	 * The name of a country from its code, as the country picker lists it
	 *
	 * @param string $code
	 * @param string $fallback the store's own name for it, if Groundhogg doesn't have the code
	 *
	 * @return string
	 */
	protected function country_name( $code, $fallback = '' ) {
		return $code ? ( utils()->location->get_countries_list( $code ) ?: $fallback ) : '';
	}

	/**
	 * Business details that were already entered in a store plugin, keyed by the Groundhogg setting they fill
	 *
	 * @return array[] the store's name => [ option => value ]
	 */
	protected function store_details() {

		$stores = [];

		if ( function_exists( 'WC' ) ) {

			$woo = [
				'gh_street_address_1'    => get_option( 'woocommerce_store_address' ),
				'gh_street_address_2'    => get_option( 'woocommerce_store_address_2' ),
				'gh_city'                => get_option( 'woocommerce_store_city' ),
				'gh_zip_or_postal'       => get_option( 'woocommerce_store_postcode' ),
				'gh_override_from_name'  => get_option( 'woocommerce_email_from_name' ),
				'gh_override_from_email' => get_option( 'woocommerce_email_from_address' ),
			];

			// The base country is US:CA until it's changed, so it's only the store's when there's an address with it
			if ( $woo['gh_street_address_1'] || $woo['gh_city'] ) {
				$countries = WC()->countries;
				$country   = $countries->get_base_country();
				$state     = $countries->get_base_state();
				$states    = $countries->get_states( $country );

				$woo['gh_country'] = $this->country_name( $country, $countries->get_countries()[ $country ] ?? '' );
				$woo['gh_region']  = is_array( $states ) && isset( $states[ $state ] ) ? $states[ $state ] : $state;
			}

			$stores['WooCommerce'] = $woo;
		}

		if ( function_exists( 'edd_get_option' ) ) {

			$edd = [
				'gh_business_name'       => edd_get_option( 'entity_name' ),
				'gh_street_address_1'    => edd_get_option( 'business_address' ),
				'gh_street_address_2'    => edd_get_option( 'business_address_2' ),
				'gh_city'                => edd_get_option( 'business_city' ),
				'gh_zip_or_postal'       => edd_get_option( 'business_postal_code' ),
				'gh_override_from_name'  => edd_get_option( 'from_name' ),
				'gh_override_from_email' => edd_get_option( 'from_email' ),
			];

			// same as WooCommerce, a country by itself is more likely a default than the business's
			if ( $edd['gh_street_address_1'] || $edd['gh_city'] ) {
				$country = edd_get_option( 'base_country' );
				$state   = edd_get_option( 'base_state' );

				$edd['gh_country'] = $this->country_name( $country, function_exists( 'edd_get_country_name' ) ? edd_get_country_name( $country ) : '' );
				$edd['gh_region']  = $state && function_exists( 'edd_get_state_name' ) ? edd_get_state_name( $country, $state ) : $state;
			}

			$stores['Easy Digital Downloads'] = $edd;
		}

		// store plugins keep some of these as HTML, like &ocirc;, the fields want the text
		return array_map( function ( $details ) {
			return array_filter( array_map( function ( $value ) {
				return is_string( $value ) ? trim( html_entity_decode( $value, ENT_QUOTES, 'UTF-8' ) ) : '';
			}, $details ) );
		}, $stores );
	}

	/**
	 * The business settings that Groundhogg doesn't have yet but a store plugin does. The first store that has a
	 * setting fills it, so a site with both can get the address from one and the sender from the other.
	 *
	 * @return array values: option => value, from: the names of the stores that filled something
	 */
	protected function prefill() {

		$values = [];
		$from   = [];

		foreach ( $this->store_details() as $store => $details ) {
			foreach ( $details as $option => $value ) {

				if ( isset( $values[ $option ] ) || get_option( $option ) ) {
					continue;
				}

				$values[ $option ] = $value;
				$from[ $store ]    = $store;
			}
		}

		return [
			'values' => $values,
			'from'   => array_values( $from ),
		];
	}

	/**
	 * Enqueue any scripts
	 */
	public function scripts() {

		wp_enqueue_style( 'groundhogg-admin-guided-setup' );
		wp_enqueue_script( 'groundhogg-admin-guided-setup' );

		$can_sync = current_user_can( 'edit_users' ) && current_user_can( 'add_contacts' );

		$setup = [
			'site'       => [
				'name'     => get_bloginfo( 'name' ),
				'is_https' => wp_parse_url( home_url(), PHP_URL_SCHEME ) === 'https',
			],
			'defaults'   => [
				'from_name'      => get_default_from_name(),
				'from_email'     => get_default_from_email(),
				'privacy_policy' => get_privacy_policy_url(),
			],
			'prefill'    => $this->prefill(),
			// names, not codes, the business country is shown in the email footer as it's saved
			'countries'  => array_values( utils()->location->get_countries_list() ),
			'hasLicense' => (bool) Inbox_Client::license_key(),
			'telemetry'  => Plugin::instance()->stats_collection->is_enabled(),
			'email'      => wp_get_current_user()->user_email,
			'unsynced'   => $can_sync ? $this->count_unsynced_users() : 0,
			'team'       => $this->can_manage_team() ? [
				'roles'   => $this->team_roles(),
				'members' => $this->team_members(),
			] : false,
			'links'      => [
				'dashboard'  => admin_page_url( 'groundhogg' ),
				'import'     => current_user_can( 'import_contacts' ) ? admin_page_url( 'gh_tools', [ 'tab' => 'import', 'action' => 'add' ] ) : false,
				'flow'       => current_user_can( 'add_funnels' ) ? admin_page_url( 'gh_funnels', [ 'action' => 'add' ] ) : false,
				'broadcast'  => current_user_can( 'schedule_broadcasts' ) ? admin_page_url( 'gh_broadcasts', [ 'action' => 'add' ] ) : false,
				'settings'   => admin_page_url( 'gh_settings', [ 'tab' => 'email' ] ),
				'pricing'    => 'https://groundhogg.io/pricing/?utm_source=plugin&utm_medium=link&utm_campaign=guided_setup&utm_content=license',
				'licenses'   => 'https://groundhogg.io/account/licenses/',
			],
		];

		wp_add_inline_script( 'groundhogg-admin-guided-setup', 'var GroundhoggGuidedSetup = ' . wp_json_encode( $setup ), 'before' );
	}

	/**
	 * Add any help items
	 *
	 * @return mixed
	 */
	public function help() {
	}

}
