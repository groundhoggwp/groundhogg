<?php

use Groundhogg\Steps\Benchmarks\Form_Integration;

/**
 * A form integration, with the forms and fields of a made up form plugin: form 12 has fields, form 15 doesn't.
 *
 * Register it with Plugin::instance()->step_manager->add_step( new GH_Test_Form_Integration_Step() ).
 */
class GH_Test_Form_Integration_Step extends Form_Integration {

	public function get_name() {
		return 'Test Forms';
	}

	public function get_type() {
		return 'test_form_integration';
	}

	public function get_description() {
		return 'Integrate Groundhogg with Test Forms.';
	}

	public function get_icon() {
		return '';
	}

	protected function get_complete_hooks() {
		return [];
	}

	public function setup( $data ) {
	}

	protected function get_forms_for_select_2() {
		return [
			12 => 'Contact <b>Us</b>',
			15 => 'Newsletter &amp; Offers',
		];
	}

	protected function get_form_fields( $form_id ) {

		if ( $form_id != 12 ) {
			// like WeForms, which gives nothing when there's no form
			return null;
		}

		return [
			'first'    => [ 'name' => 'first_name', 'title' => 'First <i>Name</i>' ],
			'email'    => [ 'name' => 'email', 'title' => 'Email &amp; Confirm' ],
			'nameless' => [ 'name' => '', 'title' => 'No id' ],
			'dupe'     => [ 'name' => 'email', 'title' => 'Email again' ],
		];
	}

	protected function normalize_field( $key, $field ) {
		return [
			'id'    => $field['name'],
			'label' => $field['title'],
		];
	}
}
