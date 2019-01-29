<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-01-29
 * Time: 1:01 PM
 */

class WPGH_Elementor_Blocks
{

    public function __construct()
    {
        add_action( 'plugins_loaded', array( $this, 'init' ) );
    }

    public function init()
    {
        if ( ! did_action( 'elementor/loaded' ) ) {
            return;
        }

        add_action( 'elementor/widgets/widgets_registered', array( $this, 'widgets_registered' ) );
    }

    public function widgets_registered() {

        if ( ! class_exists( 'WPGH_Elementor_Form_Widget' ) ){
            include_once dirname( __FILE__ ) . '/class-wpgh-elementor-form-widget.php';
        }

        Elementor\Plugin::instance()->widgets_manager->register_widget_type( new WPGH_Elementor_Form_Widget() );

    }

}