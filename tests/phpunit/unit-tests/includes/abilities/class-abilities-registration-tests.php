<?php

use Groundhogg\Abilities\Abilities;

/**
 * The actions add-ons register schema extensions, step types, categories and abilities on, and the order they
 * fire in
 */
class Abilities_Registration_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();

		if ( ! function_exists( 'wp_register_ability' ) || ! wp_get_ability( 'groundhogg/get-contact' ) ) {
			$this->markTestSkipped( 'The abilities are not registered.' );
		}
	}

	/**
	 * Set whether the schema actions count as having fired, which they have by the time a test runs
	 */
	protected function set_schemas_registered( bool $registered ) {
		$property = new ReflectionProperty( Abilities::class, 'schemas_registered' );
		$property->setAccessible( true );
		$property->setValue( null, $registered );
	}

	public function tearDown(): void {
		$this->set_schemas_registered( true );

		parent::tearDown();
	}

	public function test_the_schemas_are_registered_before_either_registry_registers_anything() {

		$this->assertTrue( Abilities::schemas_registered() );

		// 0, ahead of Groundhogg's own registrations and WordPress's, whichever registry is built first
		$this->assertSame( 0, has_action( 'wp_abilities_api_categories_init', [ Abilities::class, 'register_schemas' ] ) );
		$this->assertSame( 0, has_action( 'wp_abilities_api_init', [ Abilities::class, 'register_schemas' ] ) );
	}

	public function test_schema_extensions_come_before_step_types() {

		$fired = [];

		$extensions = function () use ( &$fired ) {
			$fired[] = 'extensions';
		};
		$step_types = function () use ( &$fired ) {
			$fired[] = 'step_types';
		};

		add_action( 'groundhogg/abilities/register_schema_extensions', $extensions );
		add_action( 'groundhogg/abilities/register_step_types', $step_types );

		$this->set_schemas_registered( false );
		Abilities::register_schemas();

		$this->assertSame( [ 'extensions', 'step_types' ], $fired );
		$this->assertTrue( Abilities::schemas_registered() );
	}

	public function test_step_types_can_build_on_what_the_extensions_added() {

		$seen = null;

		// an extension to the shared segment schema, like Segment_Schema::extend() makes
		add_filter( 'groundhogg/segment_schema/properties', $add = function ( $properties ) {
			$properties['registration_test'] = [ 'type' => 'string' ];

			return $properties;
		} );

		add_action( 'groundhogg/abilities/register_step_types', $check = function () use ( &$seen ) {
			$seen = array_key_exists( 'registration_test', Groundhogg\Abilities\Schemas\Segment_Schema::properties() );
		} );

		$this->set_schemas_registered( false );
		Abilities::register_schemas();

		remove_filter( 'groundhogg/segment_schema/properties', $add );

		$this->assertTrue( $seen );
	}

	public function test_the_schema_actions_only_fire_once() {

		$count = 0;

		add_action( 'groundhogg/abilities/register_schema_extensions', function () use ( &$count ) {
			$count ++;
		} );

		// it already ran this request, so the first two aren't a second run either
		Abilities::register_schemas();
		Abilities::register_schemas();

		$this->assertSame( 0, $count );

		$this->set_schemas_registered( false );
		Abilities::register_schemas();
		Abilities::register_schemas();

		$this->assertSame( 1, $count );
	}

	public function test_a_schema_action_that_triggers_registration_again_doesnt_loop() {

		$count = 0;

		add_action( 'groundhogg/abilities/register_schema_extensions', function () use ( &$count ) {
			$count ++;
			Abilities::register_schemas();
		} );

		$this->set_schemas_registered( false );
		Abilities::register_schemas();

		$this->assertSame( 1, $count );
	}

	public function test_categories_and_abilities_are_registered_after_groundhoggs_own() {

		$registry = ( new ReflectionClass( Abilities::class ) )->newInstanceWithoutConstructor();

		$categories = null;
		$abilities  = null;

		add_action( 'groundhogg/abilities/register_categories', function () use ( &$categories ) {
			$categories = wp_has_ability_category( 'groundhogg-contacts' ) && wp_has_ability_category( 'groundhogg-settings' );
		} );

		add_action( 'groundhogg/abilities/register_abilities', function () use ( &$abilities ) {
			$abilities = wp_has_ability( 'groundhogg/get-contact' ) && wp_has_ability( 'groundhogg/update-settings' );
		} );

		// everything of Groundhogg's own is registered already, so this only runs the actions
		$registry->register_categories();
		$registry->register_abilities();

		$this->assertTrue( $categories );
		$this->assertTrue( $abilities );
	}
}
