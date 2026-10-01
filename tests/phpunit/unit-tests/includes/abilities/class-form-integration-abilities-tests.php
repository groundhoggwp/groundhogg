<?php

use Groundhogg\Abilities\Funnels\Step_Tree_Builder;
use Groundhogg\Abilities\Schemas\Step_Type_Schema;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Step;

/**
 * The form integration step types are buildable by the abilities without their add-ons doing anything, because
 * they all have the same settings.
 *
 * @see Step_Type_Schema::extend_form_integrations()
 * @see \Groundhogg\Abilities\Funnels\Get_Form_Integration_Fields
 */
class Form_Integration_Abilities_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();

		if ( ! function_exists( 'wp_get_ability' ) || ! wp_get_ability( 'groundhogg/edit-flow' ) ) {
			$this->markTestSkipped( 'The abilities are not registered.' );
		}

		// the abilities were registered before this step type, so it's opted in the way they did when they registered
		Plugin::instance()->step_manager->add_step( new GH_Test_Form_Integration_Step() );
		Step_Type_Schema::extend_form_integrations();

		$this->factory()->truncate();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	protected function execute( string $ability, array $input ) {
		$out = wp_get_ability( $ability )->execute( $input );

		if ( is_wp_error( $out ) ) {
			return $out;
		}

		// the way an MCP client would get it
		return json_decode( wp_json_encode( $out ), true );
	}

	/**
	 * Build a flow with one form integration step, the way create-flow does
	 *
	 * @return Funnel|WP_Error
	 */
	protected function build( array $settings ) {

		$funnel  = new Funnel( [ 'title' => 'test flow', 'status' => 'inactive' ] );
		$builder = new Step_Tree_Builder( $funnel );
		$out     = $builder->build( [ [ 'type' => 'test_form_integration', 'settings' => $settings ] ] );

		if ( is_wp_error( $out ) ) {
			return $out;
		}

		$funnel->set_step_levels();

		return $funnel;
	}

	public function test_form_integrations_are_buildable() {

		$this->assertContains( 'test_form_integration', Step_Type_Schema::supported_types() );
		$this->assertTrue( Step_Type_Schema::get_type_info( 'test_form_integration' )['buildable'] );

		$schema = Step_Type_Schema::settings_schema( 'test_form_integration' );

		$this->assertSame( [ 'form_id' ], $schema['required'] );
		$this->assertArrayHasKey( 'form_id', $schema['properties'] );
		$this->assertArrayHasKey( 'field_map', $schema['properties'] );

		// the contact fields the map can go to
		$this->assertContains( 'email', $schema['properties']['field_map']['additionalProperties']['enum'] );
		$this->assertContains( 'first_name', $schema['properties']['field_map']['additionalProperties']['enum'] );
	}

	public function test_an_add_on_that_opted_in_itself_is_left_alone() {

		// the type is already supported by now, so a second opt in is refused and the first one stays
		$schema = Step_Type_Schema::settings_schema( 'test_form_integration' );

		Step_Type_Schema::extend_form_integrations();

		$this->assertSame( $schema, Step_Type_Schema::settings_schema( 'test_form_integration' ) );
		$this->assertSame( 1, count( array_keys( Step_Type_Schema::supported_types(), 'test_form_integration', true ) ) );
	}

	public function test_a_form_and_its_field_map_are_saved() {

		$funnel = $this->build( [
			'form_id'   => 12,
			'field_map' => [ 'first_name' => 'first_name', 'email' => 'email' ],
		] );

		$this->assertNotWPError( $funnel );

		$step = $funnel->get_steps()[0];

		$this->assertEquals( 12, $step->get_meta( 'form_id' ) );
		$this->assertSame( [ 'first_name' => 'first_name', 'email' => 'email' ], $step->get_meta( 'field_map' ) );
	}

	public function test_it_comes_back_the_way_it_went_in() {

		$funnel = $this->build( [
			'form_id'   => 12,
			'field_map' => [ 'email' => 'email' ],
		] );

		$this->assertNotWPError( $funnel );

		$flow = $this->execute( 'groundhogg/get-flow', [ 'flow_id' => $funnel->get_id() ] );

		$this->assertNotWPError( $flow );

		$node = $flow['steps'][0];

		$this->assertTrue( $node['buildable'] );
		$this->assertSame( [ 'form_id' => 12, 'field_map' => [ 'email' => 'email' ] ], $node['settings'] );
	}

	public function test_edit_flow_changes_the_map() {

		$funnel = $this->build( [ 'form_id' => 12, 'field_map' => [ 'email' => 'email' ] ] );
		$step   = $funnel->get_steps()[0];

		$out = $this->execute( 'groundhogg/edit-flow', [
			'flow_id'    => $funnel->get_id(),
			'operations' => [
				[ 'op' => 'update', 'step' => $step->get_id(), 'settings' => [ 'field_map' => [ 'email' => 'email', 'first_name' => 'first_name' ] ] ],
			],
		] );

		$this->assertNotWPError( $out );

		$step = new Step( $step->get_id() );
		$step->merge_changes();

		$this->assertSame( 12, absint( $step->get_meta( 'form_id' ) ) );
		$this->assertSame( [ 'email' => 'email', 'first_name' => 'first_name' ], $step->get_meta( 'field_map' ) );
	}

	/**
	 * @dataProvider invalid_settings
	 */
	public function test_what_isnt_in_the_form_is_refused( array $settings, string $code ) {

		$out = $this->build( $settings );

		$this->assertWPError( $out );
		$this->assertSame( $code, $out->get_error_code() );
	}

	public function invalid_settings() {
		return [
			'no form'               => [ [ 'field_map' => [ 'email' => 'email' ] ], 'groundhogg_form_integration_no_form' ],
			'a form that is not'    => [ [ 'form_id' => 99, 'field_map' => [] ], 'groundhogg_form_integration_form_not_found' ],
			'a field that is not'   => [ [ 'form_id' => 12, 'field_map' => [ 'nope' => 'email' ] ], 'groundhogg_form_integration_unknown_fields' ],
			'a contact field not'   => [ [ 'form_id' => 12, 'field_map' => [ 'email' => 'not_a_contact_field' ] ], 'groundhogg_form_integration_unknown_contact_fields' ],
			'not a contact field 2' => [ [ 'form_id' => 12, 'field_map' => [ 'email' => [ 'email' ] ] ], 'groundhogg_form_integration_unknown_contact_fields' ],
		];
	}

	public function test_the_forms_and_fields_can_be_listed() {

		$forms = $this->execute( 'groundhogg/get-form-integration-fields', [ 'step_type' => 'test_form_integration' ] );

		$this->assertNotWPError( $forms );
		$this->assertSame( [
			[ 'id' => '12', 'text' => 'Contact Us' ],
			[ 'id' => '15', 'text' => 'Newsletter & Offers' ],
		], $forms['forms'] );
		$this->assertArrayNotHasKey( 'fields', $forms );

		$fields = $this->execute( 'groundhogg/get-form-integration-fields', [ 'step_type' => 'test_form_integration', 'form_id' => 12 ] );

		$this->assertNotWPError( $fields );
		$this->assertSame( [
			[ 'id' => 'first_name', 'label' => 'First Name' ],
			[ 'id' => 'email', 'label' => 'Email & Confirm' ],
		], $fields['fields'] );
		$this->assertArrayHasKey( 'email', $fields['contact_fields']['Contact Info'] );
	}

	public function test_only_form_integrations_can_be_listed() {

		$out = $this->execute( 'groundhogg/get-form-integration-fields', [ 'step_type' => 'apply_tag' ] );

		$this->assertWPError( $out );
		$this->assertSame( 'groundhogg_not_a_form_integration', $out->get_error_code() );
	}
}
