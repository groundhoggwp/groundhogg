<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-01-29
 * Time: 1:01 PM
 */

class WPGH_Beaver_Builder_Blocks
{

    public function __construct()
    {
        add_action( 'plugins_loaded', array( $this, 'init' ) );
    }

    public function init()
    {
        add_action( 'init', array( $this, 'widgets_registered' ) );
    }

    public function widgets_registered() {
        if ( class_exists( 'FLBuilder' ) ){
            include_once dirname(__FILE__) . '/widget/class-wpgh-beaver-builder-widget.php';
        }
    }

}