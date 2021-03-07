<?php

namespace Groundhogg;

/**
 * Modal
 *
 * An alternative to thickbox. This provides an easy modal system to display contact over the screen.
 *
 * @since       File available since Release 1.0.5
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
 */
class Modal {

	/**
	 * @var Modal
	 */
	public static $instance;

	private function __construct() {
		add_action( 'admin_footer', array( $this, 'popup' ) );
		if ( ! did_action( 'admin_enqueue_scripts' ) ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
		} else {
			$this->scripts();
		}
	}

	public function scripts() {
		wp_enqueue_style( 'groundhogg-admin-modal' );
		wp_enqueue_script( 'groundhogg-admin-modal' );
	}

	public function popup() {

		?>
        <div class="popup-overlay hidden"></div>
        <div class="popup-window hidden">
            <div class="popup-title-container">
                <h2 class="popup-title"></h2>
                <div class="popup-close">
                    <button id="popup-close" type="button">
                        <span class="dashicons dashicons-no"></span>
                    </button>
                </div>
            </div>
            <div class="iframe-loader-wrapper hidden" style="text-align: center;">
                <div class="iframe-loader"></div>
            </div>
            <div class="popup-content"></div>
            <div class="popup-footer">
                <button id="popup-close-footer" class="popup-close button button-secondary"
                        type="button"><?php _e( 'Close' ); ?></button>
            </div>
        </div>
		<?php

	}

	public static function instance() {

		if ( ! self::$instance instanceof Modal ) {
			self::$instance = new Modal();
		}

		return self::$instance;

	}

}
