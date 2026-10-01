<?php

namespace Groundhogg\Steps\Benchmarks;

use Groundhogg\Contact;
use Groundhogg\Step;
use function Groundhogg\after_form_submit_handler;
use function Groundhogg\bold_it;
use function Groundhogg\generate_contact_with_map;
use function Groundhogg\html;
use function Groundhogg\sanitize_field_map;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-09-04
 * Time: 10:19 AM
 */
abstract class Form_Integration extends Benchmark {

	public function get_sub_group() {
		return 'forms';
	}

	/**
	 * The form picker and the field map are drawn by the flow editor's JS, see registerFormIntegrationType() in
	 * funnel-steps.js. Nothing here is named as a setting, so a save doesn't post stale values over the JS's.
	 *
	 * @param $step Step
	 */
	public function settings( $step ) {
		html( 'p', [], esc_html__( 'Run when this form is submitted...', 'groundhogg' ) );
		html( 'div', [ 'id' => $this->setting_id_prefix( 'form_integration' ) ] );
	}

	/**
	 * Tell the flow editor's JS that this step type picks a form and maps its fields.
	 *
	 * @return array
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		return array_merge( parent::jsonSerialize(), [
			'form_integration' => true,
		] );
	}

	/**
	 * Get the forms for a select2 picker.
	 *
	 * @return array
	 */
	abstract protected function get_forms_for_select_2();

	/**
	 * Returns an array of Ids => Labels for easy mapping.
	 *
	 * @param $form_id
	 *
	 * @return array
	 */
	abstract protected function get_form_fields( $form_id );

	/**
	 * Parse the filed into a normalize array.
	 *
	 * @param $key   int|string
	 * @param $field array|string
	 *
	 * @return array
	 */
	abstract protected function normalize_field( $key, $field );

	/**
	 * The forms to choose from, for the flow editor's form picker.
	 *
	 * @return array[] [ { id, text } ]
	 */
	public function get_forms_for_api() {

		$forms = [];

		foreach ( (array) $this->get_forms_for_select_2() as $id => $label ) {
			$forms[] = [
				'id'   => (string) $id,
				'text' => html_entity_decode( wp_strip_all_tags( (string) $label ), ENT_QUOTES ),
			];
		}

		return $forms;
	}

	/**
	 * The fields of a form that can be mapped, for the flow editor's field map.
	 *
	 * @param $form_id
	 *
	 * @return array[] [ { id, label } ] where id is the key of the field in the posted data
	 */
	public function get_fields_for_api( $form_id ) {

		$fields = [];

		foreach ( (array) $this->get_form_fields( $form_id ) as $key => $field ) {

			$row = $this->normalize_field( $key, $field );

			// If there is no row Id we cannot serve the field
			if ( empty( $row['id'] ) ) {
				continue;
			}

			$id = (string) $row['id'];

			// two fields with the same id are the same value in the posted data, so only the first can be mapped
			if ( isset( $fields[ $id ] ) ) {
				continue;
			}

			$fields[ $id ] = [
				'id'    => $id,
				'label' => html_entity_decode( wp_strip_all_tags( (string) ( $row['label'] ?? $id ) ), ENT_QUOTES ),
			];
		}

		return array_values( $fields );
	}

	public function validate_settings( Step $step ) {

		$field_map = (array) $step->get_meta( 'field_map' ) ?: [];

		if ( empty( $field_map ) ) {
			$step->add_error( 'invalid_field_map', __( 'Map your form fields to capture submissions.', 'groundhogg' ) );
		}

		if ( $field_map && ! in_array( 'email', $field_map ) ) {
			$step->add_error( 'missing_email_field', __( 'There is no email address field mapped, submissions may not be captured correctly.', 'groundhogg' ) );
		}
	}

	public function get_settings_schema() {
		return [
			'form_id'   => [
				'default'  => 0,
				'sanitize' => 'absint'
			],
			'field_map' => [
				'default'  => [],
				'sanitize' => function ( $value ) {

					if ( ! is_array( $value ) ) {
						return [];
					}

					return sanitize_field_map( $value );
				}
			]
		];
	}

	/**
	 * Assumes a CPT
	 *
	 * @param $form_id
	 *
	 * @return string
	 */
	protected function get_form_name( $form_id ) {
		return get_the_title( $form_id );
	}

	/**
	 * Generate a step title based on the name of the form
	 *
	 * @param $step
	 *
	 * @return false|string|null
	 */
	public function generate_step_title( $step ) {
		$form_id = $this->get_setting( 'form_id' );

		if ( ! $form_id ) {
			return 'Submits a form';
		}

		// Gets the title for the form
		$title = $this->get_form_name( $form_id );

		if ( ! $title ) {
			return null;
		}

		return sprintf( 'Submits %s', bold_it( $title ) );
	}

	/**
	 * Generate a contact from the map.
	 *
	 * @return false|Contact
	 */
	public function get_the_contact() {
		// SKIP if not the right form.
		if ( ! $this->can_complete_step() ) {
			return false;
		}

		$form_id     = absint( $this->get_setting( 'form_id' ) );
		$posted_data = $this->get_data( 'posted_data' );
		$field_map   = $this->get_setting( 'field_map' );

		$contact = generate_contact_with_map( $posted_data, $field_map, [
			'type'    => $this->get_type(),
			'step_id' => $this->get_current_step()->get_id(),
			'name'    => $this->get_form_name( $form_id )
		] );

		if ( ! $contact || is_wp_error( $contact ) ) {
			return false;
		}

		after_form_submit_handler( $contact );

		return $contact;
	}

	/**
	 * Compare the Form ID is the only requirement.
	 *
	 * @return bool
	 */
	public function can_complete_step() {
		return absint( $this->get_data( 'form_id' ) ) === absint( $this->get_setting( 'form_id' ) );
	}
}
