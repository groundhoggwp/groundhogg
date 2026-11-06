<?php

namespace Groundhogg\Mobile;

use function Groundhogg\admin_page_url;
use function Groundhogg\notices;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * How people find out the mobile app exists, and how to get to it, from the main admin.
 *
 *  - A **Mobile app** tab in Tools with a QR code that opens the app on a phone, the link, and how to add it to the
 *    home screen. See admin/tools/mobile.php.
 *  - A notification in the notifications feed, until the person has used the app or dismisses it. It links to the
 *    page above.
 *  - A command in the command palette, which the Tools page adds for its tab.
 *  - An item in the Welcome page checklist, ticked once the app has been used.
 *
 * Whether someone has used the app is remembered per user (user meta), set when they open it, so a person who already
 * has it isn't told about it again.
 */
class Promo {

	const NOTICE_ID = 'mobile-app-available';
	const USED_META = 'gh_mobile_app_used';
	const PAGE      = 'gh_tools';
	const TAB       = 'mobile';

	public function __construct() {
		add_action( 'admin_init', [ $this, 'maybe_add_notification' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'register_scripts' ] );
	}

	/**
	 * @return string the tab in the admin that explains how to get the app
	 */
	public static function page_url(): string {
		return admin_page_url( self::PAGE, [ 'tab' => self::TAB ] );
	}

	/**
	 * Whether the app has anything for this person: they can use Groundhogg's contacts, and the app has a home for them.
	 *
	 * @param int $user_id defaults to the current user
	 *
	 * @return bool
	 */
	public static function is_available_to( int $user_id = 0 ): bool {

		$user_id = $user_id ?: get_current_user_id();

		if ( ! $user_id || ! user_can( $user_id, 'view_contacts' ) ) {
			return false;
		}

		return isset( Features::instance()->for_user( $user_id )['features']['core'] );
	}

	/**
	 * Whether this person has opened the app.
	 *
	 * @param int $user_id defaults to the current user
	 *
	 * @return bool
	 */
	public static function has_used( int $user_id = 0 ): bool {
		return (bool) get_user_meta( $user_id ?: get_current_user_id(), self::USED_META, true );
	}

	/**
	 * Note that this person opened the app. Written at most once a day, since it only has to say "has used it".
	 *
	 * @param int $user_id defaults to the current user
	 */
	public static function record_use( int $user_id = 0 ): void {

		$user_id = $user_id ?: get_current_user_id();

		if ( ! $user_id ) {
			return;
		}

		if ( time() - (int) get_user_meta( $user_id, self::USED_META, true ) > DAY_IN_SECONDS ) {
			update_user_meta( $user_id, self::USED_META, time() );
		}
	}

	/**
	 * The notification, in the notifications feed. It is stored for the whole site and dismissed per person, so
	 * someone who has used the app already is treated as having dismissed it.
	 */
	public function maybe_add_notification() {

		if ( ! self::is_available_to() ) {
			return;
		}

		notices()->add_notification( self::NOTICE_ID, [
			'title'    => __( 'Your contacts, tasks and email now work on your phone.', 'groundhogg' ),
			'message'  => sprintf(
				'<p>%1$s</p><p><a class="button button-primary" href="%2$s">%3$s</a></p>',
				esc_html__( 'Scan a code with your phone to open the mobile app, then add it to your home screen so it is easy to find.', 'groundhogg' ),
				esc_url( self::page_url() ),
				esc_html__( 'Get the mobile app', 'groundhogg' )
			),
			'type'     => 'info',
			'cap'      => 'view_contacts',
			'location' => 'feed',
		] );

		if ( self::has_used() && ! notices()->is_dismissed( self::NOTICE_ID ) ) {
			notices()->dismiss_notice( self::NOTICE_ID );
		}
	}

	public function register_scripts() {

		$dot_min = \Groundhogg\Plugin::instance()->scripts->is_script_debug_enabled() ? '' : '.min';

		wp_register_script( 'groundhogg-qrcode', GROUNDHOGG_ASSETS_URL . 'lib/qrcode/qrcode.min.js', [], '1.4.4', true );

		wp_register_script( 'groundhogg-admin-mobile-app', GROUNDHOGG_ASSETS_URL . 'js/admin/features/mobile-app' . $dot_min . '.js', [
			'groundhogg-admin',
		], GROUNDHOGG_VERSION, true );
	}
}
