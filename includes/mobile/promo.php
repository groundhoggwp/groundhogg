<?php

namespace Groundhogg\Mobile;

use function Groundhogg\admin_page_url;
use function Groundhogg\get_url_var;
use function Groundhogg\is_admin_groundhogg_page;
use function Groundhogg\notices;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * How people find out the mobile app exists, and how to get to it, from the main admin.
 *
 *  - A **Mobile app** page in the menu (`gh_mobile`) with a QR code that opens the app on a phone, the link, and how
 *    to add it to the home screen. See Groundhogg\Admin\Mobile\Mobile_Page.
 *  - A dismissible notice on Groundhogg's own admin pages, until the person has used the app or closes it. On a phone
 *    it links straight to the app, on a computer it links to the page above.
 *  - A command in the command palette.
 *  - An item in the Welcome page checklist, ticked once the app has been used.
 *
 * Whether someone has used the app is remembered per user (user meta), set when they open it, so a person who already
 * has it isn't told about it again.
 */
class Promo {

	const NOTICE_ID = 'mobile-app-available';
	const USED_META = 'gh_mobile_app_used';
	const PAGE      = 'gh_mobile';

	public function __construct() {
		add_action( 'admin_notices', [ $this, 'maybe_add_notice' ], 1 );
		add_filter( 'groundhogg/command_palette/commands', [ $this, 'add_command' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'register_scripts' ] );
	}

	/**
	 * @return string the page in the admin that explains how to get the app
	 */
	public static function page_url(): string {
		return admin_page_url( self::PAGE );
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
	 * The notice, on Groundhogg's own pages. Already dismissed ones are left out by notices()->add().
	 */
	public function maybe_add_notice() {

		$page = (string) get_url_var( 'page' );

		// not on the page about it, nor in the middle of the setup
		if ( ! is_admin_groundhogg_page() || in_array( $page, [ self::PAGE, 'gh_guided_setup' ], true ) ) {
			return;
		}

		if ( notices()->is_dismissed( self::NOTICE_ID ) || self::has_used() || ! self::is_available_to() ) {
			return;
		}

		// on a phone the app is one tap away, so take them there. Otherwise explain how to get it onto one.
		$phone = wp_is_mobile();

		$message = sprintf(
			'<strong>%1$s</strong> %2$s</p><p><a class="button button-primary" href="%3$s">%4$s</a>',
			esc_html__( 'Your contacts, tasks and email now work on your phone.', 'groundhogg' ),
			$phone
				? esc_html__( 'Open the mobile app, then add it to your home screen so it is easy to find.', 'groundhogg' )
				: esc_html__( 'Scan a code with your phone to open it, and add it to your home screen.', 'groundhogg' ),
			esc_url( $phone ? App::url() : self::page_url() ),
			$phone ? esc_html__( 'Open the mobile app', 'groundhogg' ) : esc_html__( 'Get the mobile app', 'groundhogg' )
		);

		if ( notices()->add( self::NOTICE_ID, $message, 'info', 'view_contacts', false ) ) {
			// closing it with the X should be remembered, which that script does
			wp_enqueue_script( 'groundhogg-admin-mobile-app' );
		}
	}

	/**
	 * @param array[] $commands
	 *
	 * @return array[]
	 */
	public function add_command( $commands ) {

		if ( self::is_available_to() ) {
			$commands[] = [
				'name'     => 'groundhogg/mobile-app',
				'label'    => __( 'Mobile app', 'groundhogg' ),
				'url'      => self::page_url(),
				'keywords' => [ 'phone', 'mobile', 'app', 'install', 'qr', 'home screen' ],
				'category' => 'view',
			];
		}

		return $commands;
	}

	public function register_scripts() {

		$dot_min = \Groundhogg\Plugin::instance()->scripts->is_script_debug_enabled() ? '' : '.min';

		wp_register_script( 'groundhogg-qrcode', GROUNDHOGG_ASSETS_URL . 'lib/qrcode/qrcode.min.js', [], '1.4.4', true );

		wp_register_script( 'groundhogg-admin-mobile-app', GROUNDHOGG_ASSETS_URL . 'js/admin/features/mobile-app' . $dot_min . '.js', [
			'groundhogg-admin',
		], GROUNDHOGG_VERSION, true );
	}
}
