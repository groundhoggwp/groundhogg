<?php
/**
 * Created by PhpStorm.
 * User: Adrian
 * Date: 2018-11-03
 * Time: 5:10 PM
 */

class WPGH_Popup
{

    /**
     * @var WPGH_Popup
     */
    public static $instance;

	private function __construct() {

		add_action( 'admin_footer', array( $this, 'popup' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );

	}

	public function scripts()
	{
		wp_enqueue_script( 'wpgh-modal', WPGH_ASSETS_FOLDER . 'js/admin/modal.js', array(), filemtime( WPGH_PLUGIN_DIR . 'assets/js/admin/modal.js' ) );
		wp_enqueue_style( 'wpgh-modal', WPGH_ASSETS_FOLDER . 'css/admin/modal.css', array(), filemtime( WPGH_PLUGIN_DIR . 'assets/css/admin/modal.css' ) );
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
            <div class="iframe-loader-wrapper hidden">
                <div class="iframe-loader"></div>
            </div>
			<div class="popup-content">
			</div>
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

function wpgh_enqueue_modal()
{
    return WPGH_Popup::instance();
}
