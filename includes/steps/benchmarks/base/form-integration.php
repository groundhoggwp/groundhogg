<?php

namespace Groundhogg\Steps\Benchmarks;

use Groundhogg\Contact;
use Groundhogg\Step;
use function Groundhogg\after_form_submit_handler;
use function Groundhogg\bold_it;
use function Groundhogg\generate_contact_with_map;
use function Groundhogg\get_array_var;
use function Groundhogg\get_mappable_fields;
use function Groundhogg\get_request_var;
use function Groundhogg\html;

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

	public function add_additional_actions() {
		add_action( "wp_ajax_get_form_integration_map_{$this->get_type()}", [ $this, 'get_map_ajax' ] );
	}

	public function admin_scripts() {
		wp_enqueue_script( 'groundhogg-funnel-form-integration' );
	}

	/**
	 * Get the html for the field map when the form ID is changed.
	 *
	 * @return void
	 */
	public function get_map_ajax() {
		if ( ! wp_doing_ajax() ) {
			return;
		}

		$step_id = absint( get_request_var( 'step_id' ) );
		$form_id = absint( get_request_var( 'form_id' ) );

		$this->set_current_step( new Step( $step_id ) );

		$table = $this->field_map_table( $form_id );

		wp_send_json_success( [ 'map' => $table ] );
	}

	/**
	 * Output the settings for the step, dropdown of all available contact forms...
	 *
	 * @param $step Step
	 */
	public function settings( $step ) {


		echo html()->e( 'p', [], __( 'Run when this form is submitted...', 'groundhogg' ) );

		echo html()->e( 'div', [
			'class' => 'display-flex gap-10 stretch'
		], [
			html()->select2( [
				'id'       => $this->setting_id_prefix( 'form_id' ),
				'name'     => $this->setting_name_prefix( 'form_id' ),
				'data'     => $this->get_forms_for_select_2(),
				'selected' => $this->get_setting( 'form_id' ),
				'class'    => 'gh-select2 form-integration-picker'
			] ),
			html()->modal_link( [
				'title'              => __( 'Map Fields', 'groundhogg' ),
				'text'               => __( 'Map Fields', 'groundhogg' ),
				'footer_button_text' => __( 'Save Changes' ),
				'id'                 => '',
				'class'              => 'gh-button primary no-padding',
				'source'             => $this->setting_id_prefix( 'field_map' ),
				'height'             => 600,
				'width'              => 600,
				'footer'             => 'true',
				'preventSave'        => 'true',
			] ),
			html()->e( 'span', [ 'class' => 'spinner' ], '', false ),
			html()->wrap( $this->field_map_table( $this->get_setting( 'form_id' ) ), 'div', [
				'class' => 'hidden field-map-wrapper',
				'id'    => $this->setting_id_prefix( 'field_map' )
			] ),
		] );

		echo '<p></p>';
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
	 * @param $form_id
	 *
	 * @return string
	 */
	protected function field_map_table( $form_id ) {

		$field_map = $this->get_setting( 'field_map' );
		$fields    = $this->get_form_fields( $form_id );

		if ( ! $fields ) {
			return __( 'Please select a valid form and update first.', 'groundhogg' );
		}

		$rows = [];

		foreach ( $fields as $key => $field ) {

			$row = $this->normalize_field( $key, $field );

			// If there is no row Id we cannot serve the field
			if ( ! $row['id'] ) {
				continue;
			}

			$rows[] = [
				$row['id'],
				$row['label'],
				html()->dropdown( [
					'option_none' => '* Do Not Map *',
					'options'     => get_mappable_fields(),
					'selected'    => get_array_var( $field_map, $row['id'] ),
					'name'        => $this->setting_name_prefix( 'field_map' ) . sprintf( '[%s]', $row['id'] ),
				] )
			];

		}

		ob_start();

		html()->list_table(
			[
				'class' => 'field-map'
			],
			[
				__( 'Field ID', 'groundhogg' ),
				__( 'Field Label', 'groundhogg' ),
				__( 'Map To', 'groundhogg' ),
			],
			$rows
		);

		return ob_get_clean();
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

	/**
	 * Save the given step
	 *
	 * @param $step Step
	 */
	public function save( $step ) {
		$this->save_setting( 'form_id', absint( $this->get_posted_data( 'form_id' ) ) );

		$field_map = map_deep( $this->get_posted_data( 'field_map', [] ), 'sanitize_key' );

		if ( ! array_filter( $field_map ) ) {
			$this->add_error( 'invalid_field_map', __( 'Your form map configuration is invalid.', 'groundhogg' ) );
		}

		$this->save_setting( 'field_map', $field_map );
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
	 * Genertae a step title based on the name of the form
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

		$posted_data = $this->get_data( 'posted_data' );
		$field_map   = $this->get_setting( 'field_map' );

		$contact = generate_contact_with_map( $posted_data, $field_map );

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
