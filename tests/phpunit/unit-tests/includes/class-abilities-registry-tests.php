<?php

use Groundhogg\Abilities\Abilities;

/**
 * Abilities::add_category()/add_ability() must respect that WordPress only accepts registrations
 * while its init actions are running.
 */
class Abilities_Registry_Tests extends GH_UnitTestCase {

	protected array $saved_state = [];

	public function setUp(): void {
		parent::setUp();

		if ( ! function_exists( 'wp_register_ability' ) ) {
			$this->markTestSkipped( 'The WP Abilities API is not available.' );
		}

		// WordPress initialises the registries lazily on first access, firing the init actions.
		// Do that now so later checks don't run them (and flush our queue) at a surprising time.
		wp_has_ability_category( 'gh-test-init' );
		wp_has_ability( 'gh-test/init' );

		// Keep the real registry state so these tests can't leak queued fixtures into other tests
		foreach ( [ 'extra_abilities', 'extra_categories' ] as $prop ) {
			$this->saved_state[ $prop ] = $this->get_static( $prop );
		}
	}

	public function tearDown(): void {

		foreach ( $this->saved_state as $prop => $value ) {
			$this->set_static( $prop, $value );
		}

		parent::tearDown();
	}

	protected function get_static( string $prop ) {
		$ref = new ReflectionProperty( Abilities::class, $prop );
		$ref->setAccessible( true );

		return $ref->getValue();
	}

	protected function set_static( string $prop, $value ) {
		$ref = new ReflectionProperty( Abilities::class, $prop );
		$ref->setAccessible( true );
		$ref->setValue( null, $value );
	}

	/**
	 * Run a callback as though the given action were currently running.
	 */
	protected function while_doing( string $hook, callable $callback ) {
		global $wp_current_filter;

		$wp_current_filter[] = $hook;
		try {
			$callback();
		} finally {
			array_pop( $wp_current_filter );
		}
	}

	/**
	 * Run a callback as though the given action had not fired yet, i.e. we're on plugins_loaded.
	 */
	protected function before_hook_fired( string $hook, callable $callback ) {
		global $wp_actions;

		$count = $wp_actions[ $hook ] ?? null;
		unset( $wp_actions[ $hook ] );

		try {
			$callback();
		} finally {
			if ( $count !== null ) {
				$wp_actions[ $hook ] = $count;
			}
		}
	}

	protected function registry(): Abilities {
		return ( new ReflectionClass( Abilities::class ) )->newInstanceWithoutConstructor();
	}

	public function test_queued_registration_works() {

		$this->before_hook_fired( 'wp_abilities_api_categories_init', function () {
			Abilities::add_category( 'gh-test-queued', [ 'label' => 'Queued', 'description' => 'Fixture.' ] );
		} );

		$this->before_hook_fired( 'wp_abilities_api_init', function () {
			Abilities::add_ability( GH_Test_Queued_Ability::class );
		} );

		// Nothing is handed to WordPress until its actions run
		$this->assertFalse( wp_has_ability_category( 'gh-test-queued' ) );
		$this->assertFalse( wp_has_ability( 'gh-test/queued' ) );

		$this->while_doing( 'wp_abilities_api_categories_init', [ $this->registry(), 'register_categories' ] );
		$this->while_doing( 'wp_abilities_api_init', [ $this->registry(), 'register_abilities' ] );

		$this->assertTrue( wp_has_ability_category( 'gh-test-queued' ) );
		$this->assertTrue( wp_has_ability( 'gh-test/queued' ) );
	}

	public function test_mid_action_registration_works_and_is_not_doubled() {

		$this->while_doing( 'wp_abilities_api_categories_init', function () {
			Abilities::add_category( 'gh-test-mid', [ 'label' => 'Mid', 'description' => 'Fixture.' ] );
			// Asking twice, and then the queue being flushed, must not register twice
			Abilities::add_category( 'gh-test-mid', [ 'label' => 'Mid', 'description' => 'Fixture.' ] );
			$this->registry()->register_categories();
		} );

		$this->while_doing( 'wp_abilities_api_init', function () {
			Abilities::add_ability( GH_Test_Mid_Ability::class );
			Abilities::add_ability( GH_Test_Mid_Ability::class );
			$this->registry()->register_abilities();
		} );

		// A duplicate registration would have triggered _doing_it_wrong(), failing this test
		$this->assertTrue( wp_has_ability_category( 'gh-test-mid' ) );
		$this->assertTrue( wp_has_ability( 'gh-test/mid' ) );
	}

	public function test_too_late_registration_reports_incorrect_usage() {

		// Both init actions have finished by the time an add-on calls in this late
		global $wp_actions;
		$wp_actions['wp_abilities_api_categories_init'] = max( 1, $wp_actions['wp_abilities_api_categories_init'] ?? 0 );
		$wp_actions['wp_abilities_api_init']            = max( 1, $wp_actions['wp_abilities_api_init'] ?? 0 );

		$this->setExpectedIncorrectUsage( 'Groundhogg\Abilities\Abilities::add_category' );
		$this->setExpectedIncorrectUsage( 'Groundhogg\Abilities\Abilities::add_ability' );

		Abilities::add_category( 'gh-test-late', [ 'label' => 'Late', 'description' => 'Fixture.' ] );
		Abilities::add_ability( GH_Test_Late_Ability::class );

		$this->assertFalse( wp_has_ability_category( 'gh-test-late' ) );
		$this->assertFalse( wp_has_ability( 'gh-test/late' ) );
	}
}

abstract class GH_Test_Ability extends \Groundhogg\Abilities\Ability {

	protected const CATEGORY = 'groundhogg-utils';

	protected function get_args(): array {
		return [
			'label'         => 'Test ability',
			'description'   => 'Fixture.',
			'input_schema'  => [ 'type' => 'object' ],
			'output_schema' => [ 'type' => 'object' ],
		];
	}

	public function __invoke( $input ) {
		return [];
	}
}

class GH_Test_Queued_Ability extends GH_Test_Ability {
	protected const NAME = 'gh-test/queued';
}

class GH_Test_Mid_Ability extends GH_Test_Ability {
	protected const NAME = 'gh-test/mid';
}

class GH_Test_Late_Ability extends GH_Test_Ability {
	protected const NAME = 'gh-test/late';
}
