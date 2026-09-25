<?php

use Groundhogg\Funnel;
use Groundhogg\Step;
use Groundhogg\Steps\Actions\Delay_Timer;

/**
 * Editing a funnel outside the flow editor, and snapshots for undo/redo
 */
class Funnel_Editing_Tests extends GH_UnitTestCase {

	/**
	 * A funnel with two delay timers
	 *
	 * @param string $status the funnel status
	 *
	 * @return array [ Funnel, Step[] ]
	 */
	protected function make_funnel( $status = 'active' ) {

		$this->factory()->truncate();

		$funnel = new Funnel( [
			'title'  => 'test funnel',
			'status' => $status
		] );

		$steps = [];

		foreach ( [ 'first', 'second' ] as $title ) {
			$steps[] = $funnel->add_step( [
				'step_type'   => Delay_Timer::TYPE,
				'step_group'  => Step::ACTION,
				'step_title'  => $title,
				'step_status' => $status,
				'meta'        => [
					'delay_amount' => 3,
					'delay_type'   => 'days',
					'run_when'     => 'now',
				]
			] );
		}

		// meta added to active steps is staged, so start from a published funnel
		$funnel->commit();

		return [ $funnel, $steps ];
	}

	protected function add_inactive_step( Funnel $funnel ) {
		return $funnel->add_step( [
			'step_type'   => Delay_Timer::TYPE,
			'step_group'  => Step::ACTION,
			'step_title'  => 'added',
			'step_status' => 'inactive',
		] );
	}

	protected function get_step_ids( Funnel $funnel ) {
		return array_map( 'absint', wp_list_pluck( $funnel->get_steps(), 'ID' ) );
	}

	public function test_editing_mode_applies_to_every_instance_of_the_funnel() {
		[ $funnel ] = $this->make_funnel();

		$this->assertFalse( $funnel->is_editing() );

		$funnel->start_editing();

		$this->assertTrue( ( new Funnel( $funnel->get_id() ) )->is_editing() );

		$funnel->stop_editing();

		$this->assertFalse( ( new Funnel( $funnel->get_id() ) )->is_editing() );
	}

	public function test_editing_mode_can_be_nested() {
		[ $funnel ] = $this->make_funnel();

		$funnel->start_editing();

		$funnel->while_editing( function () {
		} );

		$this->assertTrue( $funnel->is_editing() );

		$funnel->stop_editing();

		$this->assertFalse( $funnel->is_editing() );
	}

	public function test_while_editing_stops_editing_if_the_callback_throws() {
		[ $funnel ] = $this->make_funnel();

		try {
			$funnel->while_editing( function () {
				throw new Exception( 'oops' );
			} );
		} catch ( Exception $e ) {
		}

		$this->assertFalse( $funnel->is_editing() );
	}

	public function test_get_steps_only_includes_staged_changes_while_editing() {
		[ $funnel, $steps ] = $this->make_funnel();

		$steps[0]->update( [ 'step_title' => 'changed' ] );
		$added = $this->add_inactive_step( $funnel );

		$live = $funnel->get_steps();
		$this->assertCount( 2, $live );
		$this->assertEquals( 'first', $live[0]->get_title() );

		$funnel->while_editing( function () use ( $funnel, $added ) {
			$editing = $funnel->get_steps();
			$this->assertCount( 3, $editing );
			$this->assertEquals( 'changed', $editing[0]->get_title() );
			$this->assertContains( $added->get_id(), $this->get_step_ids( $funnel ) );
		} );
	}

	public function test_restore_undoes_staged_changes_on_an_active_funnel() {
		[ $funnel, $steps ] = $this->make_funnel();

		$snapshot = $funnel->snapshot();

		$this->assertEquals( [ $steps[0]->get_id(), $steps[1]->get_id() ], wp_list_pluck( $snapshot, 'ID' ) );

		$steps[0]->update( [ 'step_title' => 'changed' ] );
		$steps[0]->update_meta( 'delay_amount', 5 );
		$steps[1]->delete();
		$added = $this->add_inactive_step( $funnel );

		$funnel->restore( $snapshot );

		$funnel->while_editing( function () use ( $funnel, $steps ) {
			$this->assertEquals( [ $steps[0]->get_id(), $steps[1]->get_id() ], $this->get_step_ids( $funnel ) );

			$first = $funnel->get_steps()[0];
			$this->assertEquals( 'first', $first->get_title() );
			$this->assertEquals( 3, $first->get_meta( 'delay_amount' ) );
		} );

		// nothing is left staged
		$this->assertFalse( ( new Step( $steps[0]->get_id() ) )->has_changes() );
		$this->assertFalse( ( new Step( $steps[1]->get_id() ) )->has_changes() );

		// the added step is soft deleted
		$this->assertEquals( 'deleted', ( new Step( $added->get_id() ) )->step_status );
	}

	public function test_restore_undoes_changes_on_an_inactive_funnel() {
		[ $funnel, $steps ] = $this->make_funnel( 'inactive' );

		$snapshot = $funnel->snapshot();

		$steps[0]->update( [ 'step_title' => 'changed' ] );
		$steps[0]->update_meta( 'delay_amount', 5 );
		$steps[1]->delete();
		$added = $this->add_inactive_step( $funnel );

		$funnel->restore( $snapshot );

		$funnel->while_editing( function () use ( $funnel, $steps ) {
			$this->assertEquals( [ $steps[0]->get_id(), $steps[1]->get_id() ], $this->get_step_ids( $funnel ) );
		} );

		$first = new Step( $steps[0]->get_id() );
		$this->assertEquals( 'first', $first->get_title() );
		$this->assertEquals( 3, $first->get_meta( 'delay_amount' ) );

		$this->assertEquals( 'inactive', ( new Step( $steps[1]->get_id() ) )->step_status );
		$this->assertEquals( 'deleted', ( new Step( $added->get_id() ) )->step_status );
	}

	public function test_snapshot_and_restore_leave_editing_mode() {
		[ $funnel ] = $this->make_funnel();

		$funnel->restore( $funnel->snapshot() );

		$this->assertFalse( $funnel->is_editing() );
	}
}
