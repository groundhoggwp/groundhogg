<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-01-29
 * Time: 1:01 PM
 */

class WPGH_Visual_Composer_Blocks
{

    public function __construct()
    {
        add_action( 'plugins_loaded', array( $this, 'init' ) );
    }

    public function init()
    {

        // Create Shortcode new_shortcode
// Use the shortcode: [new_shortcode =""]
        function create__shortcode($atts) {
            // Attributes
            $atts = shortcode_atts(
                array(
                    '' => '',
                ),
                $atts,
                'new_shortcode'
            );
            // Attributes in var
            $a = $atts[''];

            // Output Code
            $output = 'Here you can write';
            $output .= 'your code.';

            return $output;
        }
        add_shortcode( 'new_shortcode', 'create__shortcode' );

// Create new_shortcode element for Visual Composer
        add_action( 'vc_before_init', '_integrateWithVC' );
        function _integrateWithVC() {
            vc_map( array(
                'name' => __( 'new_shortcode', 'textdomain' ),
                'base' => 'new_shortcode',
                'show_settings_on_create' => true,
                'category' => __( 'Content', 'textdomain'),
                'params' => array(
                    array(
                        'type' => 'textfield',
                        'holder' => 'div',
                        'class' => '',
                        'admin_label' => true,
                        'heading' => __( '', 'textdomain' ),
                        'param_name' => '',
                    ),
                )
            ) );
        }





        //add_action( 'vc_before_init', array( $this, 'widgets_registered' ) );
    }

    public function widgets_registered() {
            //include_once dirname(__FILE__) . '/widget/class-wpgh-beaver-builder-widget.php';



    }

}