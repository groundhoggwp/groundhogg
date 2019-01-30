<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WPGH_Beaver_Builder_Widget extends  FLBuilderModule
{
    public function __construct()
    {

        parent::__construct(array(
            'name' => __('Groundhogg Forms', 'groundhogg'),
            'description' => __('A totally awesome Groundhogg forms!', 'groundhogg'),
            //'group'           => __( 'Standard Modules', 'fl-builder' ),
            'category' => __('Forms', 'fl-builder'),
            'dir' => WPGH_PLUGIN_DIR . 'blocks/beaver-builder/widget/',
            'url' => WPGH_PLUGIN_DIR . 'blocks/beaver-builder/widget/',
            'icon' => 'button.svg',
            'editor_export' => true, // Defaults to true and can be omitted.
            'enabled' => true, // Defaults to true and can be omitted.
            'partial_refresh' => false, // Defaults to false and can be omitted.
        ));


    }


}
/**
 * Register the module and its form settings.
 */

function wpgh_get_form_list() {

    $forms = WPGH()->steps->get_steps( array(
            'step_type' => 'form_fill'
        ) );
    $form_options = array();
    $default = 0;
    foreach ( $forms as $form ){
        if ( ! $default ){$default = $form->ID;}
        $step = new WPGH_Step( $form->ID );
        if ( $step->is_active() ){$form_options[ $form->ID ] = $form->step_title;}
    }
    return $form_options;
}

FLBuilder::register_module( 'WPGH_Beaver_Builder_Widget', array(
    'select-form'      => array(
        'title'         => __( 'Select Form', 'groundhogg' ),
        'sections'      => array(
            'groundhogg-forms'  => array(
                'title'         => __( 'Groundhogg Form', 'groundhogg' ),
                'fields'        => array(
                    'groundhogg_form_id' => array(
                        'type'          => 'select',
                        'label'         => __( 'Select Form', 'groundhogg' ),
                        'options'       => wpgh_get_form_list()
                    ),
                )
            )

        )
    )
) );






