<?php

namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

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
		wp_enqueue_style( 'groundhogg-admin-element' );
		wp_enqueue_script( 'groundhogg-admin-modal' );
	}

	public function popup() {

		?>
        <div class="gh-legacy-modal gh-modal hidden">
            <div class="gh-modal-overlay"></div>
            <div class="gh-modal-dialog no-padding has-header">
                <div class="gh-header">
                    <h3 class="gh-modal-dialog-title"></h3>
                    <button type="button" class="gh-button secondary text icon legacy-modal-close">
                        <span class="dashicons dashicons-no"></span>
                    </button>
                </div>
                <div class="gh-modal-dialog-content"></div>
                <div class="gh-modal-footer">
                    <button class="legacy-modal-close gh-button secondary" type="button" id="gh-legacy-modal-save-changes"><?php esc_html_e( 'Close' ); ?></button>
                </div>
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
