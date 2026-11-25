<?php

use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Step;
use Groundhogg\Steps\Actions\Action;
use Groundhogg\Steps\Actions\Delay_Timer;
use function Groundhogg\get_edit_version;

/**
 * Meta a step writes for itself while contacts go through it, like a counter or its last result,
 * isn't an edit, so it doesn't change the flow's version or get rolled back by undo/redo
 */
class Step_Runtime_Meta_Tests extends GH_UnitTestCase {

	const TYPE = 'runtime_meta_action';

	public function setUp(): void {
		parent::setUp();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		Plugin::instance()->step_manager->add_step( new class extends Action {
			public function get_name() {
				return 'Runtime meta';
			}

			public function get_type() {
				return Step_Runtime_Meta_Tests::TYPE;
			}

			public function get_description() {
				return '';
			}

			public function get_settings_schema() {
				return [
					'color' => [
						'sanitize' => 'sanitize_key',
						'initial'  => 'blue',
					],
				];
			}

			public function get_runtime_meta_keys() {
				return [ 'last_result', 'runs' ];
			}

			public function settings( $step ) {
			}
		} );
	}

	/**
	 * An active flow with a published step of the test type
	 *
	 * @return array [ Funnel, Step ]
	 */
	protected function flow( $type = self::TYPE, $meta = [ 'color' => 'blue' ] ) {

		$funnel = new Funnel( [ 'title' => 'Runtime meta', 'status' => 'active' ] );

		$step = $funnel->add_step( [
			'step_type'   => $type,
			'step_group'  => Step::ACTION,
			'step_status' => 'active',
			'meta'        => $meta,
		] );

		// meta added to active steps is staged, so start from a published flow
		$funnel->commit();

		return [ $funnel, new Step( $step->get_id() ) ];
	}

	protected function version( Funnel $funnel ) {
		return get_edit_version( new Funnel( $funnel->get_id() ) );
	}

	/**
	 * What running the step does, see Pro's AI Agent and Apply Owner steps
	 */
	protected function run_bookkeeping( Step $step, $key, $value ) {
		( new Step( $step->get_id() ) )->update_meta_bypass_changes( $key, $value );
	}

	public function test_the_step_type_names_its_runtime_meta() {
		[ , $step ] = $this->flow();

		$this->assertEquals( [ 'last_result', 'runs' ], $step->get_runtime_meta_keys() );

		[ , $timer ] = $this->flow( Delay_Timer::TYPE, [ 'delay_amount' => 3 ] );

		$this->assertEquals( [], $timer->get_runtime_meta_keys() );
	}

	public function test_runtime_meta_doesnt_change_the_flows_version() {
		[ $funnel, $step ] = $this->flow();

		$version = $this->version( $funnel );

		$this->run_bookkeeping( $step, 'last_result', [ 'answer' => 'yes' ] );
		$this->run_bookkeeping( $step, 'runs', [ 'date' => '2026-11-25', 'count' => 1 ] );
		$this->run_bookkeeping( $step, 'runs', [ 'date' => '2026-11-25', 'count' => 2 ] );

		$this->assertEquals( $version, $this->version( $funnel ) );

		// it's still saved
		$this->assertEquals( [ 'date' => '2026-11-25', 'count' => 2 ], ( new Step( $step->get_id() ) )->get_meta( 'runs' ) );
	}

	public function test_a_settings_edit_still_changes_the_flows_version() {
		[ $funnel, $step ] = $this->flow();

		$version = $this->version( $funnel );

		// staged, like the editor or an ability saves it
		$funnel->while_editing( function () use ( $step ) {
			( new Step( $step->get_id() ) )->update_meta( 'color', 'red' );
		} );

		$this->assertNotEquals( $version, $this->version( $funnel ) );
	}

	public function test_bookkeeping_a_step_type_doesnt_name_still_changes_the_flows_version() {
		[ $funnel, $step ] = $this->flow( Delay_Timer::TYPE, [ 'delay_amount' => 3 ] );

		$version = $this->version( $funnel );

		$this->run_bookkeeping( $step, 'index', 1 );

		$this->assertNotEquals( $version, $this->version( $funnel ) );
	}

	public function test_runtime_meta_keys_can_be_added_with_a_filter() {
		[ $funnel, $step ] = $this->flow( Delay_Timer::TYPE, [ 'delay_amount' => 3 ] );

		$filter = function ( $keys, $step ) {
			if ( $step->get_type() === Delay_Timer::TYPE ) {
				$keys[] = 'index';
			}

			return $keys;
		};

		add_filter( 'groundhogg/steps/runtime_meta_keys', $filter, 10, 2 );

		$version = $this->version( $funnel );

		$this->run_bookkeeping( $step, 'index', 1 );
		$this->run_bookkeeping( $step, 'index', 2 );

		$this->assertEquals( $version, $this->version( $funnel ) );

		remove_filter( 'groundhogg/steps/runtime_meta_keys', $filter );
	}

	public function test_snapshots_leave_out_runtime_meta() {
		[ $funnel, $step ] = $this->flow();

		$this->run_bookkeeping( $step, 'runs', [ 'count' => 1 ] );

		$snapshot = $funnel->snapshot();

		$this->assertEquals( 'blue', $snapshot[0]['meta']['color'] );
		$this->assertArrayNotHasKey( 'runs', $snapshot[0]['meta'] );
	}

	public function test_restore_doesnt_roll_back_runtime_meta() {
		[ $funnel, $step ] = $this->flow();

		$this->run_bookkeeping( $step, 'runs', [ 'count' => 1 ] );

		// the editor keeps the steps as it loaded them, runtime meta and all
		$snapshot = $funnel->while_editing( function () use ( $step ) {
			$step = new Step( $step->get_id() );

			return [
				[
					'ID'   => $step->get_id(),
					'data' => $step->get_data(),
					'meta' => $step->get_meta(),
				]
			];
		} );

		$this->assertEquals( [ 'count' => 1 ], $snapshot[0]['meta']['runs'] );

		// contacts go through, and the user edits a setting
		$this->run_bookkeeping( $step, 'runs', [ 'count' => 5 ] );

		$funnel->while_editing( function () use ( $step ) {
			( new Step( $step->get_id() ) )->update_meta( 'color', 'red' );
		} );

		// undo
		$funnel->restore( $snapshot );

		$restored = $funnel->while_editing( function () use ( $step ) {
			$step = new Step( $step->get_id() );
			$step->merge_changes();

			return $step;
		} );

		$this->assertEquals( 'blue', $restored->get_meta( 'color' ) );

		// the old count isn't staged either, which publishing would save
		$this->assertEquals( [ 'count' => 5 ], $restored->get_meta( 'runs' ) );
		$this->assertEquals( [ 'count' => 5 ], ( new Step( $step->get_id() ) )->get_meta( 'runs' ) );
	}
}
