<?php
namespace Groundhogg\Integrations;

use Groundhogg\Integrations\Elementor\Elementor_Integration;
use Groundhogg\Integrations\Elementor\Elementor_Integration_V2;
use Groundhogg\Integrations\Elementor\Field_Mapping;

class Integrations
{

    public function __construct()
    {

        add_action( 'elementor_pro/init', [ $this, 'init_elementor' ] );
        add_action( 'elementor_pro/init', [ $this, 'init_elementor_deprecated' ] );

    }

    public function init_elementor()
    {
        add_action( 'elementor/controls/controls_registered', function (){
            \Elementor\Plugin::instance()->controls_manager->register_control( 'gh_fields_map', new Field_Mapping() );
        } );

        // Instantiate the action class
        $groundhogg_action = new Elementor_Integration_V2();

        // Register the action with form widget
        \ElementorPro\Plugin::instance()->modules_manager->get_modules('forms')->add_form_action( $groundhogg_action->get_name(), $groundhogg_action);
    }

    public function init_elementor_deprecated()
    {
        // Instantiate the action class
        $groundhogg_action = new Elementor_Integration();
        // Register the action with form widget
        \ElementorPro\Plugin::instance()->modules_manager->get_modules('forms')->add_form_action($groundhogg_action->get_name(), $groundhogg_action);
    }

}