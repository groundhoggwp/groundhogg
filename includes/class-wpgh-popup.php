<?php
/**
 * Modal
 *
 * An alternative to thickbox. This provides an easy modal system to display contact over the screen.
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 1.0.5
 */
class WPGH_Popup
{

    /**
     * @var WPGH_Popup
     */
    public static $instance;

	private function __construct() {
		add_action( 'admin_footer', array( $this, 'popup' ) );
		if ( ! did_action( 'admin_enqueue_scripts' ) ){
            add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
        } else {
		    $this->scripts();
        }
    }

	public function scripts()
	{
        wp_enqueue_style( 'groundhogg-admin-modal' );
        wp_enqueue_script( 'groundhogg-admin-modal' );
        wp_localize_script('groundhogg-admin-modal', 'wpghModalDefaults', array(
            'title'     => 'Modal',
            'footertext' => __( 'Save Changes' ),
            'height'    => 500,
            'width'     => 500,
            'footer'    => 'true',
        ) );
    }

	public function popup()
	{

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
				<button class="popup-save button button-primary" type="button"><?php _e( 'Save Changes' ); ?></button>
			</div>
		</div>
		<?php

	}

	public static function instance()
    {

        if ( ! self::$instance instanceof WPGH_Popup ){
            self::$instance = new WPGH_Popup();
        }

        return self::$instance;

    }

}

/**
 * Enqueues the modal scripts
 *
 * @return WPGH_Popup
 *
 * @since 1.0.5
 */
function wpgh_enqueue_modal()
{
    return WPGH_Popup::instance();
}
