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
	const NOTIFICATIONS_OPTION = 'gh_notifications';
	const NOTIFICATION_LOCATIONS = [ 'feed', 'banner', 'both' ];
	const REMOTE_NOTICES_URL = 'https://groundhogg.io/wp-json/gh/v4/broadcasts/archive';
	const CDN_NOTICES_URL = 'https://cdn.groundhogg.io/broadcasts';

	public static $dismissed_notices = [];
	public static $read_notices = [];

	public function __construct() {
		add_action( 'after_setup_theme', [ $this, 'init' ] );

		add_action( 'admin_notices', [ $this, 'pre_notices' ] );
		add_action( 'admin_notices', [ $this, 'notices' ] );
		add_action( 'admin_notices', [ $this, 'user_notices' ] );
		add_action( 'admin_notices', [ $this, 'notification_banners' ] );

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
		wp_send_json( $this->get_feed() );
	}

	/**
	 * Add a notification that stays in the notifications feed (the bell in the admin) until each person dismisses it.
	 * Use this instead of hooking into admin_notices, e.g. from an update path or after something was set up.
	 *
	 * The id is what dismissal and "read" are remembered by, so give a new notification a new id.
	 * Adding the same id again updates the content but keeps the date it first appeared, so it is safe to call
	 * on every request.
	 *
	 * @param string $id   unique id for the notification, e.g. 'updated-to-4-12'
	 * @param array  $args {
	 *
	 * @type string  $title     the heading in the feed
	 * @type string  $message   the content, HTML allowed (same tags as post content)
	 * @type string  $type      info, success, warning, error, which colors the banner
	 * @type string  $cap       capability needed to see it, false for anyone who can see Groundhogg's admin
	 * @type string  $location  'feed' (default), 'banner' (admin_notices only) or 'both'
	 * @type bool    $site_wide for banners, whether it appears on every admin page and not only Groundhogg's
	 * @type int     $expires   timestamp after which it is no longer shown, 0 to keep it
	 * }
	 *
	 * @return bool whether it was stored
	 */
	public function add_notification( string $id, array $args = [] ): bool {

		$id = sanitize_key( $id );

		if ( $id === '' ) {
			return false;
		}

		// ids from the remote feed are numbers, keep ours apart from them
		if ( is_numeric( $id ) ) {
			$id = 'notice-' . $id;
		}

		$args = wp_parse_args( $args, [
			'title'     => '',
			'message'   => '',
			'type'      => 'info',
			'cap'       => false,
			'location'  => 'feed',
			'site_wide' => false,
			'expires'   => 0,
		] );

		if ( trim( $args['title'] ) === '' && trim( $args['message'] ) === '' ) {
			return false;
		}

		$now    = time();
		$stored = array_filter( $this->get_stored_notifications(), function ( $notification ) use ( $now ) {
			return ! $notification['expires'] || $notification['expires'] > $now;
		} );

		$notification = [
			'id'        => $id,
			'title'     => sanitize_text_field( $args['title'] ),
			'message'   => wp_kses_post( $args['message'] ),
			'type'      => in_array( $args['type'], [ 'info', 'success', 'warning', 'error' ], true ) ? $args['type'] : 'info',
			'cap'       => $args['cap'] ? sanitize_text_field( $args['cap'] ) : false,
			'location'  => in_array( $args['location'], self::NOTIFICATION_LOCATIONS, true ) ? $args['location'] : 'feed',
			'site_wide' => (bool) $args['site_wide'],
			'expires'   => absint( $args['expires'] ),
			'added'     => isset( $stored[ $id ]['added'] ) ? absint( $stored[ $id ]['added'] ) : $now,
		];

		// nothing changed, don't write on every request
		if ( isset( $stored[ $id ] ) && $stored[ $id ] === $notification && count( $stored ) === count( $this->get_stored_notifications() ) ) {
			return true;
		}

		$stored[ $id ] = $notification;

		update_option( self::NOTIFICATIONS_OPTION, $stored, false );

		return true;
	}

	/**
	 * Remove a notification, for everyone
	 *
	 * @param string $id
	 *
	 * @return bool whether there was one to remove
	 */
	public function remove_notification( string $id ): bool {

		$stored = $this->get_stored_notifications();
		$id     = sanitize_key( $id );

		if ( ! isset( $stored[ $id ] ) ) {
			return false;
		}

		unset( $stored[ $id ] );

		update_option( self::NOTIFICATIONS_OPTION, $stored, false );

		return true;
	}

	/**
	 * @return array every stored notification, expired or not
	 */
	protected function get_stored_notifications(): array {
		$stored = get_option( self::NOTIFICATIONS_OPTION, [] );

		return is_array( $stored ) ? $stored : [];
	}

	/**
	 * The notifications the current user can see, that haven't expired.
	 *
	 * @param string $where 'feed' or 'banner' for the ones that belong there, empty for all
	 *
	 * @return array[] keyed by id
	 */
	public function get_notifications( string $where = '' ): array {

		$now = time();
		$out = [];

		foreach ( $this->get_stored_notifications() as $id => $notification ) {

			if ( $notification['expires'] && $notification['expires'] <= $now ) {
				continue;
			}

			if ( $notification['cap'] && ! current_user_can( $notification['cap'] ) ) {
				continue;
			}

			$location = $notification['location'];

			// the bell is hidden when white labeled, so the feed can't be reached
			if ( $location === 'feed' && is_white_labeled() ) {
				$location = 'banner';
			}

			if ( ( $where === 'feed' && $location === 'banner' ) || ( $where === 'banner' && $location === 'feed' ) ) {
				continue;
			}

			$out[ $id ] = $notification;
		}

		return $out;
	}

	/**
	 * The items for the notifications feed: the remote notices and the ones added in code, newest first.
	 * If the remote ones can't be fetched the others are still returned.
	 *
	 * @return array[]|WP_Error
	 */
	public function get_feed() {

		$remote = $this->fetch_remote_notices();
		$rows   = [];

		foreach ( $this->get_notifications( 'feed' ) as $notification ) {
			$rows[] = [
				'ts'   => $notification['added'],
				'i'    => count( $rows ),
				'item' => [
					'id'      => $notification['id'],
					'title'   => $notification['title'],
					'content' => wp_kses_post( wpautop( $notification['message'] ) ),
					'sent'    => wp_date( 'Y-m-d H:i:s', $notification['added'] ),
				],
			];
		}

		if ( is_wp_error( $remote ) ) {
			if ( empty( $rows ) ) {
				return $remote;
			}

			$remote = [];
		}

		// one with a date that can't be read stays next to the one before it
		$ts = 0;

		foreach ( $remote as $item ) {
			$ts     = strtotime( $item['sent'] ) ?: $ts;
			$rows[] = [
				'ts'   => $ts,
				'i'    => count( $rows ),
				'item' => $item,
			];
		}

		// usort isn't stable before PHP 8, so ties keep their order by index
		usort( $rows, function ( $a, $b ) {
			return $b['ts'] <=> $a['ts'] ?: $a['i'] <=> $b['i'];
		} );

		return array_column( $rows, 'item' );
	}

	/**
	 * Show the notifications that are set to appear as a banner, until they are dismissed.
	 *
	 * @return void
	 */
	public function notification_banners() {

		foreach ( $this->get_notifications( 'banner' ) as $id => $notification ) {

			if ( $this->is_dismissed( $id ) ) {
				continue;
			}

			if ( ! $notification['site_wide'] && ! is_admin_groundhogg_page() ) {
				continue;
			}

			$dismiss_url = wp_nonce_url( add_query_arg( 'notice', $id ), 'gh_dismiss_notice' );

			?>
            <div id="gh-notification-<?php echo esc_attr( $id ); ?>" class="notice notice-<?php echo esc_attr( $notification['type'] ); ?>">
                <div class="display-flex gap-10">
					<?php groundhogg_icon( 24 ); ?>
                    <div class="notice-message">
						<?php if ( $notification['title'] ): ?>
                            <p><strong><?php echo esc_html( $notification['title'] ); ?></strong></p>
						<?php endif; ?>
						<?php echo wp_kses_post( wpautop( $notification['message'] ) ); ?>
                        <p><a href="<?php echo esc_url( $dismiss_url ); ?>"><?php esc_html_e( 'Dismiss', 'groundhogg' ); ?></a></p>
                    </div>
                </div>
            </div>
			<?php
		}
	}

	/**
	 * Fetch the remote notices
	 *
	 * @return array|mixed
	 */
	function fetch_remote_notices() {

		$body = [ 'campaign' => 'dashboard-notifications' ];

		// Try the CDN first, fall back to groundhogg.io if it's unreachable or returns something unexpected
		$response = remote_post_json( self::CDN_NOTICES_URL, $body, 'GET', [], true, DAY_IN_SECONDS );

		if ( is_wp_error( $response ) || ! isset( $response['items'] ) ) {
			$response = remote_post_json( self::REMOTE_NOTICES_URL, $body, 'GET', [], true, DAY_IN_SECONDS );
		}

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
		$feed = $this->get_feed();

		if ( is_wp_error( $feed ) ) {
			return 0;
		}

		$ids  = wp_list_pluck( $feed, 'id' );
		$done = array_merge( array_values( self::$read_notices ), self::get_dismissed_notice_ids() );

		return count( array_diff( $ids, $done ) );
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
