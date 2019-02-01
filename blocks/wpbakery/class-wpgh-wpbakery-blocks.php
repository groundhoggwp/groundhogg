<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-01-29
 * Time: 1:01 PM
 */

class WPGH_WPBakery_Blocks
{

    public function __construct()
    {
        add_action( 'vc_before_init', array( $this, 'widgets_registered' ) );
    }


    public function widgets_registered() {

        include_once dirname( __FILE__ ) . '/class-wpgh-wpbakery-widget.php';
        new WPGH_WPBakery_Widget();
    }

}