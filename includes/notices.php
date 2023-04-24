<?php

namespace Groundhogg;

/**
 * Notices
 *
 * Easy implementation for notices on admin pages in Groundhogg.
 * This class is used by all admin page classes, thus all notices will appear on any admin page.
 *
 * @since       File available since Release 0.1
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Notices {
	const TRANSIENT = 'groundhogg_notices';
	const DISMISSED_NOTICES_OPTION = 'gh_dismissed_notices';
	const READ_NOTICES_OPTION = 'gh_read_notices';
    const REMOTE_NOTICES_URL = 'https://groundho.gg/wp-json/wp/v2/plugin-notification/';

	public static $dismissed_notices = [];
	public static $read_notices = [];

	public function __construct() {
		add_action( 'after_setup_theme', [ $this, 'init' ] );

		add_action( 'admin_notices', [ $this, 'pre_notices' ] );
		add_action( 'admin_notices', [ $this, 'notices' ] );

		add_action( 'admin_init', [ $this, 'dismiss_notices' ] );
		add_action( 'wp_ajax_gh_dismiss_notice', [ $this, 'ajax_dismiss_notice' ] );
		add_action( 'wp_ajax_gh_read_notice', [ $this, 'ajax_read_notice' ] );
		add_action( 'wp_ajax_gh_remote_notifications', [ $this, 'ajax_fetch_remote_notices' ] );
	}

	public function init() {
		self::$dismissed_notices = get_user_meta( get_current_user_id(), self::DISMISSED_NOTICES_OPTION, true );

		if ( ! is_array( self::$dismissed_notices ) ) {
			self::$dismissed_notices = [];
		}

		self::$read_notices = get_user_meta( get_current_user_id(), self::READ_NOTICES_OPTION, true );

		if ( ! is_array( self::$read_notices ) ) {
			self::$read_notices = [];
		}
	}

	/**
     * Fetch remote notices for ajax
     *
	 * @return void
	 */
    function ajax_fetch_remote_notices(){
        wp_send_json( $this->fetch_remote_notices() );
    }

	/**
     * Fetch the remote notices
     *
	 * @return array|mixed
	 */
    function fetch_remote_notices(){
	    $response = wp_remote_get( self::REMOTE_NOTICES_URL );

	    if ( is_wp_error( $response ) ) {
		    return [];
	    }

	    $json = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! $json ){
            return [];
        }

        return $json;
    }

	/**
	 * Retrieve the number of unread notices...
	 *
	 * @return int
	 */
	function count_unread() {
		$ids = get_transient( 'gh_notification_ids' );

		if ( empty( $ids ) || ! is_array( $ids ) ) {
			$ids  = wp_parse_id_list( wp_list_pluck( $this->fetch_remote_notices(), 'id' ) );
			set_transient( 'gh_notification_ids', $ids, DAY_IN_SECONDS );
		}

		return count( array_diff( $ids, array_values( Notices::$read_notices ) ) );
	}

	/**
	 * Arbitrary function to add any notices that don't really belong in other plugin files.
	 */
	public function pre_notices() {
		// If this site is updating from an older version
		if ( get_option( 'gh_updating_to_2_1' ) ) {
			// Show a notice that features have been removed
			$this->add(
				'features-removed-notice',
				sprintf( "IMPORTANT! Several features were removed in from Groundhogg in version 2.1. Please go here to <a class='button-primary' href='%s'>re-install features!</a> <a class='button' href='%s'>Dismiss</a>",
					admin_page_url( 'gh_tools', [ 'tab' => 'remote_install' ] ),
					action_url( 'gh_dismiss_notice', [ 'notice' => 'features-removed-notice' ] ) ),
				'warning',
				'administrator',
				true
			);
		}

		if ( is_option_enabled( 'gh_send_with_gh_api' ) ) {
			$this->add(
				'sending-service-deprecated',
				sprintf( "IMPORTANT! The sending service has been officially discontinued. Please use an alternative method such as <a href='%s'>SendWP</a>, <a href='%s'>AWS SES</a> or <a href='%s'>Another SMTP service</a>. <a class='button' href='%s'>Dismiss</a>",
					admin_page_url( 'gh_settings', [ 'tab' => 'email' ] ),
					'https://www.groundhogg.io/downloads/aws/',
					'https://www.groundhogg.io/downloads/smtp/',
					action_url( 'gh_dismiss_notice', [ 'notice' => 'sending-service-deprecated' ] )
				),
				'warning',
				'administrator',
				true
			);
		}

		// Only show to admins, make sure the guided setup was completed
		if ( is_admin() && current_user_can( 'administrator' ) && ! get_option( 'permalink_structure' ) && guided_setup_finished() ) {

			$change_permalink_button = html()->e( 'a', [
				'href'  => admin_url( 'options-permalink.php' ),
				'class' => 'button'
			], __( 'Change Permalink Structure', 'groundhogg' ) );

			$this->add(
				'incorrect-permalinks',
				sprintf( __( "Your site permalink structure is currently set to <code>Plain</code>. This setting is not compatible with Groundhogg. Change your permalink structure to any other setting to avoid issues. We recommend <code>Post name</code>.</p><p>%s", 'groundhogg' ), $change_permalink_button ),
				'warning',
				'administrator',
				true
			);
		}

		do_action( 'groundhogg/notices/before' );
	}

	/**
	 * Mark a notice as read
	 *
	 * @param $ids
	 */
	public function read_notice( $ids ) {

		$notices = parse_maybe_numeric_list( $ids );

		foreach ( $notices as $notice ) {
			self::$read_notices[ $notice ] = $notice;
		}

		update_user_meta( get_current_user_id(), self::READ_NOTICES_OPTION, self::$read_notices );
	}

	/**
	 * Mark a notice as dismissed
	 *
	 * @param $ids
	 */
	public function dismiss_notice( $ids ) {

		$notices = parse_maybe_numeric_list( $ids );
		foreach ( $notices as $notice ) {
			self::$dismissed_notices[ $notice ] = $notice;
		}

		update_user_meta( get_current_user_id(), self::DISMISSED_NOTICES_OPTION, self::$dismissed_notices );
	}

	/**
	 * Is a notice dismissed
	 *
	 * @param $id
	 *
	 * @return bool
	 */
	public function is_dismissed( $id ) {
		return isset_not_empty( self::$dismissed_notices, $id );
	}

	/**
	 * Mark a notice as read with Ajax
	 *
	 * @return void
	 */
	public function ajax_read_notice() {

		if ( ! wp_verify_nonce( get_request_var( '_wpnonce' ) ) ) {
			wp_send_json_error();
		}

		$this->read_notice( get_post_var( 'notice' ) );

		wp_send_json_success();
	}

	/**
	 * Dismiss a notice with Ajax
	 *
	 * @return void
	 */
	public function ajax_dismiss_notice() {

		if ( ! wp_verify_nonce( get_request_var( '_wpnonce' ) ) ) {
			wp_send_json_error();
		}

		$this->dismiss_notice( get_post_var( 'notice' ) );

		wp_send_json_success();
	}

	/**
	 * Dismiss a notice via the URL permanently.
	 */
	public function dismiss_notices() {

		if ( ! wp_verify_nonce( get_request_var( '_wpnonce' ), 'gh_dismiss_notice' ) ) {
			return;
		}

		$notice_id = sanitize_text_field( get_request_var( 'notice' ) );

		$this->dismiss_notice( $notice_id );

		// Send them back to from whence they came.
		wp_safe_redirect( wp_get_referer() );
		die();
	}

	/**
	 * @return bool|string
	 */
	protected function get_transient_name() {
		if ( is_user_logged_in() ) {
			return self::TRANSIENT . '_user_' . get_current_user_id();
		}

		if ( get_contactdata() ) {
			return self::TRANSIENT . '_contact_' . Plugin::$instance->tracking->get_current_contact_id();
		}

		return false;
	}

	/**
	 * @return bool
	 */
	protected function can_add_notices() {
		return (bool) $this->get_transient_name();
	}

	/**
	 * @return mixed
	 */
	protected function get_stored_notices() {
		return get_transient( $this->get_transient_name() );
	}

	/**
	 * @param array $notices
	 *
	 * @return bool
	 */
	protected function store_notices( $notices = [] ) {
		return set_transient( $this->get_transient_name(), $notices, MINUTE_IN_SECONDS );
	}

	/**
	 * Add a notice
	 *
	 * @param             $code      string|\WP_Error|array ID of the notice
	 * @param             $message   string message
	 * @param string      $type
	 * @param string|bool $cap
	 * @param bool        $site_wide whether the notice should be displayed site_wide
	 *
	 * @return true|false
	 */
	public function add( $code = '', $message = '', $type = 'success', $cap = false, $site_wide = false ) {
		if ( ! $this->can_add_notices() ) {
			return false;
		}

		$notices = $this->get_stored_notices();

		if ( ! $notices || ! is_array( $notices ) ) {
			$notices = array();
		}

		$data = [];

		// Is WP Error
		if ( is_wp_error( $code ) ) {
			$error   = $code;
			$code    = $error->get_error_code();
			$message = $error->get_error_message();

			if ( ! empty( $error->get_error_data() ) ) {
				$data = [
					'code'    => $error->get_error_code(),
					'message' => $error->get_error_message(),
					'data'    => $error->get_error_data(),
				];
			}

			$type = 'error';
		} // Passed as array
        else if ( is_array( $code ) ) {

			$args = wp_parse_args( $code, [
				'code'      => '',
				'message'   => '',
				'type'      => 'success',
				'data'      => false,
				'cap'       => false,
				'site_wide' => false
			] );

			extract( $args );
		}

		// Do not re-show dismissed notices
		if ( isset_not_empty( self::$dismissed_notices, $code ) ) {
			return false;
		}

		$notices[ $code ]['code']      = $code;
		$notices[ $code ]['message']   = $message;
		$notices[ $code ]['type']      = $type;
		$notices[ $code ]['data']      = $data;
		$notices[ $code ]['cap']       = $cap;
		$notices[ $code ]['site_wide'] = $site_wide;

		$this->store_notices( $notices );

		return true;
	}

	/**
	 * @param string $code
	 *
	 * @return bool
	 */
	public function remove( $code = '' ) {
		if ( ! $this->can_add_notices() ) {
			return false;
		}

		$notices = $this->get_stored_notices();
		unset( $notices[ $code ] );
		$this->store_notices( $notices );

		return true;
	}

	/**
	 * Print a notice
	 *
	 * @param $notice
	 *
	 * @return void
	 */
	public function print_notice( $notice ) {
		?>
        <div id="<?php esc_attr_e( $notice['code'] ); ?>"
             class="notice notice-<?php esc_attr_e( $notice['type'] ); ?> is-dismissible"><p>
                <strong><?php echo wp_kses_post( $notice['message'] ); ?></strong></p>
			<?php if ( $notice['type'] === 'error' && ! empty( $notice['data'] ) ): ?>
                <p><textarea class="code" style="width: 100%;"
                             readonly><?php echo wp_json_encode( $notice['data'], JSON_PRETTY_PRINT ); ?></textarea>
                </p>
			<?php endif; ?>
        </div>
		<?php
	}

	/**
	 * Get the notices
	 */
	public function notices() {

		$notices = $this->get_stored_notices();

		if ( ! $notices ) {
			$notices = [];
		}

		if ( ! wp_doing_ajax() ) {
			?><div id="groundhogg-notices"><?php
		}

		foreach ( $notices as $code => $notice ) {

			// If doing admin_notices do not show sitewide notices.
			if ( doing_action( 'admin_notices' ) && ! get_array_var( $notice, 'site_wide' ) ) {
				continue;
			}

			if ( isset_not_empty( $notice, 'cap' ) && ! current_user_can( $notice['cap'] ) ) {
				continue;
			}

			$this->print_notice( $notice );

			unset( $notices[ $code ] );
		}

		if ( ! wp_doing_ajax() ) {
			?></div><?php
		}

		if ( ! empty( $notices ) ) {
			set_transient( $this->get_transient_name(), $notices, MINUTE_IN_SECONDS );
		} else {
			delete_transient( $this->get_transient_name() );
		}
	}

	/**
	 * Print the notices.
	 *
	 * @param bool $echo
	 *
	 * @return true|string
	 */
	public function print_notices( $echo = true ) {
		if ( $echo ) {
			$this->notices();

			return true;
		}

		ob_start();

		$this->notices();

		$notices = ob_get_clean();

		return $notices;

	}

}
