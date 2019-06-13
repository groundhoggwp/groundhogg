<?php
namespace Groundhogg\Blocks\Beaver_Builder;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Form_Widget extends \FLBuilderModule
{
    public function __construct()
    {

        parent::__construct(array(
            'name' => __('Groundhogg Forms', 'groundhogg'),
            'description' => __('Use Groundhogg forms to collect leads and launch automation!', 'groundhogg'),
            //'group'           => __( 'Standard Modules', 'fl-builder' ),
            'category' => __('Forms', 'fl-builder'),
            'dir' => plugin_dir_path( __FILE__ ),
            'url' => plugin_dir_url( __FILE__ ),
            'icon' => 'icon.svg',
            'editor_export' => true, // Defaults to true and can be omitted.
            'enabled' => true, // Defaults to true and can be omitted.
            'partial_refresh' => false, // Defaults to false and can be omitted.
        ));

    }

}




