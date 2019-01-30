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
            'icon' => 'icon.svg',
            'editor_export' => true, // Defaults to true and can be omitted.
            'enabled' => true, // Defaults to true and can be omitted.
            'partial_refresh' => false, // Defaults to false and can be omitted.
        ));

    }

}




