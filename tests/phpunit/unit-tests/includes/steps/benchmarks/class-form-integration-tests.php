<?php

use Groundhogg\Plugin;

/**
 * @see Form_Integration the forms and fields the flow editor's JS asks for, see registerFormIntegrationType() in funnel-steps.js
 * @see \Groundhogg\Api\V4\Funnels_Api::form_integration()
 */
class Form_Integration_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		Plugin::instance()->step_manager->add_step( new GH_Test_Form_Integration_Step() );
	}

	public function tearDown(): void {
		wp_set_current_user( 0 );
		unset( Plugin::instance()->step_manager->elements['test_form_integration'] );
		parent::tearDown();
	}

	protected function ask( array $params ) {
		$request = new WP_REST_Request( 'GET', '/gh/v4/funnels/form-integration' );
		$request->set_query_params( $params );

		return rest_do_request( $request );
	}

	protected function as_admin() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	public function test_forms_are_plain_text_with_string_ids() {

		$step = Plugin::instance()->step_manager->get_element( 'test_form_integration' );

		$this->assertSame( [
			[ 'id' => '12', 'text' => 'Contact Us' ],
			[ 'id' => '15', 'text' => 'Newsletter & Offers' ],
		], $step->get_forms_for_api() );
	}

	public function test_fields_skip_what_has_no_id_or_is_a_duplicate() {

		$step = Plugin::instance()->step_manager->get_element( 'test_form_integration' );

		$this->assertSame( [
			[ 'id' => 'first_name', 'label' => 'First Name' ],
			[ 'id' => 'email', 'label' => 'Email & Confirm' ],
		], $step->get_fields_for_api( 12 ) );
	}

	public function test_a_form_without_fields_has_none() {

		$step = Plugin::instance()->step_manager->get_element( 'test_form_integration' );

		$this->assertSame( [], $step->get_fields_for_api( 15 ) );
	}

	public function test_form_integration_types_say_so_to_the_editor() {

		$types = Plugin::instance()->step_manager->get_elements();

		$this->assertTrue( $types['test_form_integration']->jsonSerialize()['form_integration'] );
		$this->assertSame( 'test_form_integration', $types['test_form_integration']->jsonSerialize()['type'] );

		// the others aren't flagged
		$this->assertArrayNotHasKey( 'form_integration', $types['apply_tag']->jsonSerialize() );
		$this->assertArrayNotHasKey( 'form_integration', $types['web_form']->jsonSerialize() );
	}

	public function test_the_forms_come_from_the_api() {

		$this->as_admin();

		$response = $this->ask( [ 'type' => 'test_form_integration' ] );

		$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$this->assertSame( [
			[ 'id' => '12', 'text' => 'Contact Us' ],
			[ 'id' => '15', 'text' => 'Newsletter & Offers' ],
		], $response->get_data()['forms'] );
		$this->assertArrayNotHasKey( 'fields', $response->get_data() );
	}

	public function test_the_fields_come_from_the_api() {

		$this->as_admin();

		$response = $this->ask( [ 'type' => 'test_form_integration', 'form_id' => '12' ] );

		$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$this->assertSame( [
			[ 'id' => 'first_name', 'label' => 'First Name' ],
			[ 'id' => 'email', 'label' => 'Email & Confirm' ],
		], $response->get_data()['fields'] );
		$this->assertArrayNotHasKey( 'forms', $response->get_data() );
	}

	public function test_other_step_types_are_refused() {

		$this->as_admin();

		$this->assertSame( 404, $this->ask( [ 'type' => 'apply_tag' ] )->get_status() );
		$this->assertSame( 404, $this->ask( [ 'type' => 'not_a_registered_type' ] )->get_status() );
		$this->assertSame( 404, $this->ask( [] )->get_status() );
	}

	public function test_it_needs_permission_to_edit_flows() {

		$response = $this->ask( [ 'type' => 'test_form_integration' ] );

		$this->assertContains( $response->get_status(), [ 401, 403 ] );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$this->assertSame( 403, $this->ask( [ 'type' => 'test_form_integration' ] )->get_status() );
	}

	public function test_the_settings_leave_the_fields_to_the_editor() {

		$step = Plugin::instance()->step_manager->get_element( 'test_form_integration' );

		$funnel = new Groundhogg\Funnel( [ 'title' => 'Form Integration Flow' ] );
		$added  = $funnel->add_step( [
			'step_title' => 'Submits a form',
			'step_type'  => 'test_form_integration',
			'step_group' => 'benchmark',
		] );

		$html = $step->get_settings_island( $added )['html'];

		// a save posts what's named, and it'd overwrite what the editor's JS has set
		$this->assertStringNotContainsString( 'name="steps[', $html );
		$this->assertStringContainsString( sprintf( 'id="step_%d_form_integration"', $added->get_id() ), $html );
		$this->assertTrue( $step->get_settings_island( $added )['ignore_morph'] );
	}
}
