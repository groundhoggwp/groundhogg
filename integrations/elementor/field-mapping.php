<?php
namespace Groundhogg\Integrations\Elementor;

use Elementor\Controls_Manager;
use ElementorPro\Modules\Forms\Controls\Fields_Map;
use function Groundhogg\get_mappable_fields;
use Groundhogg\Plugin;

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
class Field_Mapping extends Fields_Map {

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

// Whether to include minified files or not.
        $IS_MINIFIED = Plugin::$instance->scripts->is_script_debug_enabled() ? '' : '.min';
        wp_enqueue_script( 'groundhogg-elementor-integration', plugin_dir_url(__FILE__ ) . '/elementor' . $IS_MINIFIED .'.js', [], GROUNDHOGG_VERSION, true );
        $mappable_fields = get_mappable_fields();
        $fields = [];

        foreach ( $mappable_fields as $field_id => $field_label ){
            $fields[] = [
                'remote_id'         => $field_id,
                'remote_label'      => $field_label,
                'remote_type'       => 'text',
                'remote_required'   => in_array( $field_id, [ 'email' ] ),
            ];
        }

        wp_localize_script( 'groundhogg-elementor-integration', 'ghMappableFields', [
            'fields' => $fields
        ] );
    }
}
