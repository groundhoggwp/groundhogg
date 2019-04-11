<?php
namespace ElementorPro\Modules\Forms\Controls;

use Elementor\Control_Repeater;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class Fields_Map
 * @package ElementorPro\Modules\Forms\Controls
 *
 * each item needs the following properties:
 *   remote_id,
 *   remote_label
 *   remote_type
 *   remote_required
 *   local_id
 */
class Gh_Fields_Map extends Fields_Map {

	const CONTROL_TYPE = 'gh_fields_map';

	public function get_type() {
		return self::CONTROL_TYPE;
	}

	protected function get_default_settings() {
		return array_merge( parent::get_default_settings(), [
            'render_type' => 'none',
            'fields' => [
				[
					'name' => 'local_id',
					'type' => Controls_Manager::HIDDEN,
				],
				[
					'name' => 'remote_id',
					'type' => Controls_Manager::SELECT,
				],
			],
		] );
	}

	public function enqueue()
    {
        $IS_MINIFIED = wpgh_is_option_enabled( 'gh_script_debug' ) ? '' : '.min' ;

        wp_enqueue_script( 'groundhogg-elementor', plugin_dir_url( __FILE__ ) . 'elementor' . $IS_MINIFIED .'.js', [], WPGH()->version, true );

        $mappable_fields = wpgh_get_mappable_fields();
        $fields = [];

        foreach ( $mappable_fields as $field_id => $field_label ){
            $fields[] = [
                'remote_id'         => $field_id,
                'remote_label'      => $field_label,
                'remote_type'       => 'text',
                'remote_required'   => in_array( $field_id, [ 'email' ] ),
            ];
        }

        wp_localize_script( 'groundhogg-elementor', 'ghMappableFields', [
            'fields' => $fields
        ] );
    }
}
