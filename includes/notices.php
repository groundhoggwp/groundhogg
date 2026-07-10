<?php

namespace Groundhogg;

use Groundhogg\Utils\Replacer;
use WP_Error;

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
	const REMOTE_NOTICES_URL = 'https://groundhogg.io/wp-json/gh/v4/broadcasts/archive';

	public static $dismissed_notices = [];
	public static $read_notices = [];

	public function __construct() {
		add_action( 'after_setup_theme', [ $this, 'init' ] );

		add_action( 'admin_notices', [ $this, 'pre_notices' ] );
		add_action( 'admin_notices', [ $this, 'notices' ] );
		add_action( 'admin_notices', [ $this, 'user_notices' ] );

		add_action( 'admin_init', [ $this, 'dismiss_notices' ] );
		add_action( 'wp_ajax_gh_dismiss_notice', [ $this, 'ajax_dismiss_notice' ] );
		add_action( 'wp_ajax_gh_undismiss_notice', [ $this, 'ajax_undismiss_notice' ] );
		add_action( 'wp_ajax_gh_read_notice', [ $this, 'ajax_read_notice' ] );
		add_action( 'wp_ajax_gh_remote_notifications', [ $this, 'ajax_fetch_remote_notices' ] );
	}

	/**
	 * Add a notice for a specific user
	 *
	 * @param string $message   the notice content
	 * @param string $type      the type of notice
	 * @param bool   $site_wide if the notice appears on all admin pages
	 * @param int    $user_id   the user id to show the notice to
	 *
	 * @return void
	 */
	public function add_user_notice( string $message, string $type = 'success', bool $site_wide = false, int $user_id = 0 ) {

		if ( ! $user_id && ! is_user_logged_in() ) {
			return;
		}

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$user_notices = get_user_meta( $user_id, 'groundhogg_notices', true ) ?: [];

		$user_notices[] = [
			'message'   => $message,
			'type'      => $type,
			'site_wide' => $site_wide,
		];

		update_user_meta( $user_id, 'groundhogg_notices', $user_notices );
	}

	/**
	 * Handle showing admin notices for the user
	 *
	 * @return void
	 */
	public function user_notices() {

		$notices = get_user_meta( get_current_user_id(), 'groundhogg_notices', true ) ?: [];

		if ( empty( $notices ) ) {
			return;
		}

		$show = array_filter_splice( $notices, function ( $notice ) {
			return $notice['site_wide'] === true || is_admin_groundhogg_page();
		} );

//		$show[] = [
//			'message' => 'some content',
//			'type'    => 'info'
//		];

		foreach ( $show as $notice ) {
			?>
            <div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible">
                <div class="display-flex gap-10">
					<?php groundhogg_icon( 24 ); ?>
                    <div class="notice-message">
						<?php echo wp_kses_post( wpautop( $notice['message'] ) ); ?>
                    </div>
                </div>
            </div>
			<?php
		}

		update_user_meta( get_current_user_id(), 'groundhogg_notices', $notices );
	}

	public function init() {
		$dismissed = get_user_meta( get_current_user_id(), self::DISMISSED_NOTICES_OPTION, true );

		if ( ! is_array( $dismissed ) ) {
			$dismissed = [];
		}

        // handle permanent or temp dismissal
        self::$dismissed_notices = array_filter( $dismissed, function( $value, $notice_id ){
            // if the key and the notice ID are the same, perma dismissed
            // otherwise, if the key is > time(), temp dismissed
            return $notice_id === $value || ( is_int($value) && $value > time() );
        }, ARRAY_FILTER_USE_BOTH );

        // let's handle some notices behaviorally, like the review nag
		if ( ! key_exists( 'review-please', self::$dismissed_notices ) ){

            $dismissable_reasons = [
                // don't show if white labeled
	            fn () => is_white_labeled(),
	            // don't show to regular users obviously
	            fn () => ! current_user_can( 'install_plugins' ),
	            // don't show if just installed
                fn () => get_transient( 'groundhogg_review_request_dismissed' ) === 1,
	            // at least 2 broadcasts sent or one flow
                fn () => db()->broadcasts->count( [ 'status' => 'sent' ] ) < 2 && db()->funnels->count( [ 'status' => 'active' ] ) < 1,
                // at least 100 completed events
                fn () => db()->events->count( [ 'status' => Event::COMPLETE ] ) < 100,
            ];

            // if any dismissable reason returns a truthy value, then don't show (count dismissed)
            foreach ( $dismissable_reasons as $dismissable_reason ){
                if ( call_user_func( $dismissable_reason ) ){
                    // don't perma dismiss so that we can check again later if anything changes
                    self::$dismissed_notices['review-please'] = time() + DAY_IN_SECONDS;
                    break;
                }
            }
		}

		self::$read_notices = get_user_meta( get_current_user_id(), self::READ_NOTICES_OPTION, true );

		if ( ! is_array( self::$read_notices ) ) {
			self::$read_notices = [];
		}
	}

    public static function get_dismissed_notice_ids() {
        return array_keys( self::$dismissed_notices );
    }

    public static function get_read_notice_ids() {
        return array_keys( self::$read_notices );
    }

	/**
	 * Fetch remote notices for ajax
	 *
	 * @return void
	 */
	function ajax_fetch_remote_notices() {
		wp_send_json( $this->fetch_remote_notices() );
	}

	/**
	 * Fetch the remote notices
	 *
	 * @return array|mixed
	 */
	function fetch_remote_notices() {

		$response = remote_post_json( self::REMOTE_NOTICES_URL, [ 'campaign' => 'dashboard-notifications' ], 'GET', [], true, DAY_IN_SECONDS );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$json = $response;

		if ( ! $json ) {
			return new WP_Error( 'no_json', __( 'No JSON returned from remote server.', 'groundhogg' ) );
		}

		$items = $json['items'];

        $replacer = new Replacer( [
            'siteowner' => wp_get_current_user()->first_name,
        ] );

		return array_map( function ( $item ) use ( $replacer ){

            $content = wp_kses_post( markdown2html( $item['plain'] ) );
            $content = $replacer->replace( $content );

			return [
				'id'      => absint( $item['ID'] ),
                'title'   => sanitize_text_field( $item['subject'] ),
				'content' => $content,
                'sent'    => sanitize_text_field( $item['sent'] )
  			];

		}, $items );
	}

	/**
	 * Retrieve the number of unread notices...
	 *
	 * @return int
	 */
	function count_unread() {
		$ids = wp_parse_id_list( wp_list_pluck( $this->fetch_remote_notices(), 'id' ) );

		return count( array_diff( $ids, array_values( self::$read_notices ) ) );
	}

	/**
	 * Arbitrary function to add any notices that don't really belong in other plugin files.
	 */
	public function pre_notices() {

		// Only show to admins, make sure the guided setup was completed
		if ( is_admin() && current_user_can( 'administrator' ) && ! get_option( 'permalink_structure' ) && guided_setup_finished() ) {

			$change_permalink_button = html()->e( 'a', [
				'href'  => admin_url( 'options-permalink.php' ),
				'class' => 'button'
			], __( 'Change Permalink Structure', 'groundhogg' ) );

			$this->add(
				'incorrect-permalinks',
                /* translators: %s: link to permalink settings */
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
     * optionally supply $days for temporary dismissal
     *
     * if $days is supplied the value will be set to the timestamp of when the notice should appear again, otherwise the value will simply be the ID of the notice
	 *
	 * @param mixed $ids
	 * @param  int  $days the number of days a notice should remain dismissed
	 */
	public function dismiss_notice( $ids, $days = 0 ) {

		$notices = parse_maybe_numeric_list( $ids );
		foreach ( $notices as $notice ) {
            $value = $days > 0 ? strtotime( "+$days days" ) : $notice;
			self::$dismissed_notices[ $notice ] = $value;
		}

		update_user_meta( get_current_user_id(), self::DISMISSED_NOTICES_OPTION, self::$dismissed_notices );
	}

	/**
	 * Mark a notice as dismissed
	 *
	 * @param $ids
	 */
	public function undismiss_notice( $ids ) {

		$notices = parse_maybe_numeric_list( $ids );
		foreach ( $notices as $notice ) {
			unset( self::$dismissed_notices[ $notice ] );
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

		if ( ! verify_admin_ajax_nonce() ) {
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

		if ( ! verify_admin_ajax_nonce() ) {
			wp_send_json_error();
		}

		$days   = absint( get_post_var( 'days', 0 ) );
		$notice = get_post_var( 'notice' );

		$this->dismiss_notice( $notice, $days );

		wp_send_json_success();
	}

	/**
	 * Dismiss a notice with Ajax
	 *
	 * @return void
	 */
	public function ajax_undismiss_notice() {

		if ( ! verify_admin_ajax_nonce() ) {
			wp_send_json_error();
		}

		$this->undismiss_notice( get_post_var( 'notice' ) );

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
	 * Get the notices
	 */
	public function notices() {

		$notices = $this->get_stored_notices();

        if ( empty( $notices ) ) {
            return;
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

		if ( ! empty( $notices ) ) {
			set_transient( $this->get_transient_name(), $notices, MINUTE_IN_SECONDS );
		} else {
			delete_transient( $this->get_transient_name() );
		}
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
        <div id="<?php echo esc_attr( $notice['code'] ); ?>" class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible">
			<?php echo wp_kses_post( wpautop( $notice['message'] ) ); ?>
			<?php if ( $notice['type'] === 'error' && ! empty( $notice['data'] ) ): ?>
                <p>
                    <textarea class="code" style="width: 100%;" readonly><?php echo wp_json_encode( $notice['data'], JSON_PRETTY_PRINT ); ?></textarea>
                </p>
			<?php endif; ?>
        </div>
		<?php
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

		return ob_get_clean();

	}

}
