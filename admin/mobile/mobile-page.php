<?php

namespace Groundhogg\Admin\Mobile;

use Groundhogg\Admin\Admin_Page;
use Groundhogg\Mobile\App;
use Groundhogg\Mobile\Promo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Tells people the mobile app exists and how to get it: a code to scan, the link, and how to add it to a home screen.
 */
class Mobile_Page extends Admin_Page {

	protected function add_ajax_actions() {
	}

	protected function add_additional_actions() {
	}

	public function get_priority() {
		return 103;
	}

	public function get_slug() {
		return Promo::PAGE;
	}

	public function get_name() {
		return esc_html__( 'Mobile app', 'groundhogg' );
	}

	/**
	 * Everyone the app is for. What it shows them is up to their capabilities, see Groundhogg\Mobile\Features.
	 */
	public function get_cap() {
		return 'view_contacts';
	}

	public function get_item_type() {
		return 'mobile_app';
	}

	public function help() {
	}

	public function scripts() {
		wp_enqueue_script( 'groundhogg-qrcode' );
		wp_enqueue_script( 'groundhogg-admin-mobile-app' );
	}

	public function view() {

		$url = App::url();

		?>
        <style>
            .gh-mobile-grid { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 40px; align-items: start; max-width: 960px; }
            .gh-mobile-grid h2 { margin-top: 0; }
            .gh-mobile-grid ol, .gh-mobile-grid ul { margin-left: 1.4em; }
            .gh-mobile-grid ol { list-style: decimal; }
            .gh-mobile-grid ul { list-style: disc; }
            .gh-mobile-grid li { margin: 8px 0; }
            .gh-mobile-qr-wrap { text-align: center; }
            /* a code has to be dark on light to scan, so it keeps its own white whatever the admin theme is */
            #gh-mobile-qr { display: inline-block; padding: 12px; background: #fff; border-radius: 12px; line-height: 0; border: 1px solid rgba(0, 0, 0, .1); }
            #gh-mobile-qr svg { width: 220px; height: 220px; }
            .gh-mobile-link { word-break: break-all; }
            .gh-mobile-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 18px; }
            @media (max-width: 782px) {
                .gh-mobile-grid { grid-template-columns: 1fr; }
                .gh-mobile-qr-wrap { order: -1; }
            }
        </style>
        <div class="gh-panel">
            <div class="inside">
                <div class="gh-mobile-grid">
                    <div>
                        <h2><?php esc_html_e( 'Take your contacts with you', 'groundhogg' ); ?></h2>
                        <p><?php esc_html_e( 'The mobile app puts your day in your pocket. Look up anyone, call, text or email them, add notes and tasks, fill out forms, and see what has happened with them, all from your phone.', 'groundhogg' ); ?></p>

                        <h3><?php esc_html_e( 'Get it on your phone', 'groundhogg' ); ?></h3>
                        <ol>
                            <li>
								<?php
								printf(
								/* translators: %s: the link to the mobile app */
									esc_html__( 'Scan the code with your phone\'s camera, or open %s on your phone.', 'groundhogg' ),
									'<a class="gh-mobile-link" href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( $url ) . '</a>'
								);
								?>
                            </li>
                            <li><?php esc_html_e( 'Sign in with the same username and password you use here.', 'groundhogg' ); ?></li>
                            <li>
								<?php esc_html_e( 'Add it to your home screen, so it opens like any other app:', 'groundhogg' ); ?>
                                <ul>
                                    <li><?php echo wp_kses( __( '<strong>iPhone and iPad:</strong> open it in Safari, tap the Share button, then <strong>Add to Home Screen</strong>.', 'groundhogg' ), [ 'strong' => [] ] ); ?></li>
                                    <li><?php echo wp_kses( __( '<strong>Android:</strong> open it in Chrome, tap the menu (the three dots), then <strong>Install app</strong> or <strong>Add to Home screen</strong>.', 'groundhogg' ), [ 'strong' => [] ] ); ?></li>
                                </ul>
                            </li>
                        </ol>
                        <p class="description"><?php esc_html_e( 'There is nothing to download from an app store, and it is always up to date. You see what your role lets you see here, nothing more.', 'groundhogg' ); ?></p>

                        <div class="gh-mobile-actions">
                            <a class="gh-button primary" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open the mobile app', 'groundhogg' ); ?></a>
                            <button type="button" class="gh-button secondary" id="gh-mobile-copy" data-url="<?php echo esc_attr( $url ); ?>" data-copied="<?php esc_attr_e( 'Copied!', 'groundhogg' ); ?>"><?php esc_html_e( 'Copy link', 'groundhogg' ); ?></button>
                        </div>
                    </div>
                    <div class="gh-mobile-qr-wrap">
                        <div id="gh-mobile-qr" data-url="<?php echo esc_attr( $url ); ?>" data-label="<?php esc_attr_e( 'A code that opens the mobile app when scanned with a phone', 'groundhogg' ); ?>"></div>
                        <p><?php esc_html_e( 'Scan with your phone\'s camera', 'groundhogg' ); ?></p>
                    </div>
                </div>
            </div>
        </div>
		<?php
	}
}
