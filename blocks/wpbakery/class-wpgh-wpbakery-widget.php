<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WPGH_WPBakery_Widget extends WPBakeryShortCode {

    function __construct() {

        add_action( 'init', array( $this, 'wpgh_webform_mapping' ) );
        add_shortcode( 'wpgh_wpbakery_display_html', array( $this, 'wpgh_wpbakery_display_html' ) );
    }

    public function wpgh_webform_mapping() {

        // Stop all if VC is not enabled
        if ( !defined( 'WPB_VC_VERSION' ) ) {
            return;
        }


        $forms = WPGH()->steps->get_steps( array(
            'step_type' => 'form_fill'
        ) );

        $form_options = array();
        $default = 0;
        foreach ( $forms as $form ){
            if ( ! $default ){$default = $form->ID;}
            $step = wpgh_get_funnel_step( $form->ID );
            if ( $step->is_active() ){
                $form_options[] =  array(
                    'id'    => $form->ID,
                    'title' => $form->step_title
                );
            }
        }


        // Map the block with vc_map()
        vc_map(

            array(
                'name' => __('Groundhogg Forms', 'groundhogg'),
                'base' => 'wpgh_wpbakery_display_html',
                'description' => __('place Groundhogg Forms', 'groundhogg'),
                'category' => __('Content', 'groundhogg'),
                'icon' => WPGH_ASSETS_FOLDER.'/images/phil-340x340.png',
                'params' => array(
                    array(
                        'type' => 'dropdown',
                        'heading' => __( 'Select Form', 'groundhogg' ),
                        'param_name' => 'selected_form',
                        'value' => $form_options,
                        'description' => __( 'Please select grondhogg form.', 'groundhogg' ),
                        'admin_label' => false,
                        'weight' => 0,

                    ),
                )
            )
        );

    }

    // Element HTML
    public function wpgh_wpbakery_display_html( $atts ) {

        // Params extraction

        $value =  shortcode_atts(
            array(
                'selected_form'   => '',
            ),
            $atts
        );


        $form_id = intval( $value['selected_form']);
//
        if ( $form_id ){

            ob_start();
            echo do_shortcode( sprintf( '[gh_form id="%d"]', $form_id ) );
            $html = ob_get_clean();
        }

        return $html;

    }


}
