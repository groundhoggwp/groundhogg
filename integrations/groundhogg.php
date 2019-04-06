<?php
namespace ElementorPro\Modules\Forms\Actions;

use Elementor\Controls_Manager;
use ElementorPro\Modules\Forms\Classes\Form_Record;
use ElementorPro\Modules\Forms\Classes\Integration_Base;
use ElementorPro\Modules\Forms\Controls\Fields_Map;
use ElementorPro\Classes\Utils;
use Elementor\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Groundhogg extends Integration_Base {

	public function get_name() {
		return 'groundhogg';
	}

	public function get_label() {
		return __( 'Groundhogg', 'elementor-pro' );
	}

	public function register_settings_section( $widget ) {
		$widget->start_controls_section(
			'section_groundhogg',
			[
				'label' => __( 'Groundhogg', 'elementor-pro' ),
				'condition' => [
					'submit_actions' => $this->get_name(),
				],
			]
		);

		$widget->add_control(
			'groundhogg_fields_map',
			[
				'label' => __( 'Field Mapping', 'elementor-pro' ),
				'type' => Fields_Map::CONTROL_TYPE,
				'separator' => 'before',
				'fields' => [
					[
						'name' => 'remote_id',
						'type' => Controls_Manager::HIDDEN,
					],
					[
						'name' => 'local_id',
						'type' => Controls_Manager::SELECT,
					],
				],
			]
		);

		$widget->add_control(
			'groundhogg_tags',
			[
				'label' => __( 'Tags', 'elementor-pro' ),
				'type' => Controls_Manager::SELECT2,
				'options' => [],
				'multiple' => true,
				'label_block' => true,
			]
		);

		$widget->end_controls_section();
	}

	public function on_export( $element ) {
		unset(
			$element['settings']['groundhogg_fields_map'],
			$element['settings']['groundhogg_tags']
		);

		return $element;
	}

	/**
	 * @param Form_Record $record
	 * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
	 */
	public function run( $record, $ajax_handler ) {

		$form_settings = $record->get( 'form_settings' );
		$subscriber = $this->create_subscriber_object( $record );

		if ( ! $subscriber ) {
			$ajax_handler->add_admin_error_message( __( 'Groundhogg Integration requires an email field', 'elementor-pro' ) );
			return;
		}

		if ( '' !== $form_settings['groundhogg_tags'] ) {
			$subscriber->apply_tag( wp_parse_id_list( $form_settings['groundhogg_tags'] ) );
		}
	}

	/**
	 * Create subscriber array from submitted data and form settings
	 * returns a subscriber array or false on error
	 *
	 * @param Form_Record $record
	 *
	 * @return \WPGH_Contact|bool
	 */
	private function create_subscriber_object( Form_Record $record ) {

		$map = $this->get_fields_map( $record );
		$fields = $this->get_normalized_fields( $record );

		if ( ! isset( $fields['email'] ) ) {
			return false;
		}

		$contact = wpgh_generate_contact_with_map( $fields, $map );

		return $contact;
	}

	/**
	 * @param Form_Record $record
	 *
	 * @return array
	 */
	private function get_fields_map( Form_Record $record ) {
		$map = [];

		// Other form has a field mapping
		foreach ( $record->get_form_settings( 'groundhogg_fields_map' ) as $map_item ) {
			if ( empty( $fields[ $map_item['local_id'] ]['value'] ) ) {
				continue;
			}

			$map[ $map_item[ 'local_id' ] ] = $map[ $map_item[ 'remote_id' ] ];
		}

		return $map;
	}

	/**
	 * @param Form_Record $record
	 *
	 * @return array
	 */
	private function get_normalized_fields( Form_Record $record )
	{
		$fields = [];
		$raw_fields = $record->get( 'fields' );
		foreach ( $raw_fields as $id => $field ) {

			$fields[ $id ] = $field['value'];
		}

		return $fields;
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function handle_panel_request( array $data ) {

		$tags = WPGH()->tags->get_tags_select();

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

		$response = [
			'tags'      => $tags,
			'fields'    => $fields
		];

		return $response;
	}
}
