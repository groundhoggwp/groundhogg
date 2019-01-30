<?php

/*
Element Description: VC Info Box
*/

// Element Class
class WPGH_Visual_Composer_Widget extends WPBakeryShortCode {

    // Element Init
    function __construct() {
        add_action( 'init', array( $this, 'vc_informationbox_mapping' ) );
        add_shortcode( 'vc_informationbox', array( $this, 'vc_informationbox_html' ) );
    }

    // Element Mapping
    public function vc_informationbox_mapping() {


        // Stop all if VC is not enabled
        if ( !defined( 'WPB_VC_VERSION' ) ) {
            return;
        }

        // Map the block with vc_map()
        vc_map(
            array(
                'name' => __('VC Informationbox', 'text-domain'),
                'base' => 'vc_informationbox',
                'description' => __('A simple VC box', 'text-domain'),
                'category' => __('My Own Custom Elements', 'text-domain'),
                'icon' => get_template_directory_uri().'/assets/img/vc-icon.png',
                'params' => array(

                    array(
                        'type' => 'textfield',
                        'holder' => 'h3',
                        'class' => 'title-class',
                        'heading' => __( 'Title', 'text-domain' ),
                        'param_name' => 'title',
                        'value' => __( 'Default value', 'text-domain' ),
                        'description' => __( 'Box Title', 'text-domain' ),
                        'admin_label' => false,
                        'weight' => 0,
                        'group' => 'Custom Group',
                    ),

                    array(
                        'type' => 'textarea',
                        'holder' => 'div',
                        'class' => 'text-class',
                        'heading' => __( 'Text', 'text-domain' ),
                        'param_name' => 'text',
                        'value' => __( 'Default value', 'text-domain' ),
                        'description' => __( 'Box Text', 'text-domain' ),
                        'admin_label' => false,
                        'weight' => 0,
                        'group' => 'Custom Group',
                    )

                )
            )
        );

    }


    // Element HTML
    public function vc_informationbox_html( $atts ) {

        // Params extraction
        extract(
            shortcode_atts(
                array(
                    'title'   => '',
                    'text' => '',
                ),
                $atts
            )
        );

        // Fill $html var with data
        $html = '
    <div class="vc-informationbox-wrap">
     
        <h2 class="vc-informationbox-title">' . 'hello' . '</h2>
         
        <div class="vc-informationbox-text">' . 'world' . '</div>
     
    </div>';

        return $html;

    }

} // End Element Class

// Element Class Init
new WPGH_Beaver_Builder_Widget();