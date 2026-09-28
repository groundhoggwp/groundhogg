<?php

use Groundhogg\Abilities\Funnels\Flow_Operations;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Step;
use Groundhogg\Steps\Actions\Action;

/**
 * What the flow editor can do with Flow_Operations that groundhogg/edit-flow can't:
 * add steps of any registered type, set settings as they're stored, and restore deleted steps, for undo and redo
 */
class Flow_Operations_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();

		$this->factory()->truncate();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * A flow with an if/else that has a step in each branch
	 *
	 * @return array [ Funnel, step key => Step ]
	 */
	protected function flow( $status = 'inactive' ) {

		$funnel = new Funnel( [ 'title' => 'operations', 'status' => $status ] );

		$steps = [];
		$order = 0;

		$add = function ( $key, $type, $group, $branch = 'main' ) use ( $funnel, &$steps, &$order ) {
			$steps[ $key ] = $funnel->add_step( [
				'step_type'   => $type,
				'step_group'  => $group,
				'step_title'  => $key,
				'branch'      => $branch,
				'step_order'  => ++ $order,
				'step_status' => $funnel->is_active() ? 'active' : 'inactive',
			] );
		};

		$add( 'first', 'delay_timer', Step::ACTION );
		$add( 'if', 'if_else', Step::LOGIC );
		$add( 'yes', 'apply_tag', Step::ACTION, "{$steps['if']->ID}-yes" );
		$add( 'no', 'apply_note', Step::ACTION, "{$steps['if']->ID}-no" );
		$add( 'last', 'delay_timer', Step::ACTION );

		$funnel->set_step_levels();

		if ( $funnel->is_active() ) {
			$funnel->commit();
		}

		return [ $funnel, $steps ];
	}

	protected function apply( Funnel $funnel, array $operations, $editor = true ) {
		return ( new Flow_Operations( $funnel, $editor ) )->apply_all_or_nothing( $operations );
	}

	/**
	 * Each step's branch and position in it, in the draft
	 */
	protected function layout( Funnel $funnel ) {
		return $funnel->while_editing( function () use ( $funnel ) {

			$layout = [];

			foreach ( $funnel->get_steps() as $step ) {
				$layout[ $step->step_title ] = $step->branch;
			}

			return $layout;
		} );
	}

	public function test_the_editor_adds_steps_of_types_the_abilities_cant_describe() {

		Plugin::instance()->step_manager->add_step( new class extends Action {
			public function get_name() {
				return 'Undescribed';
			}

			public function get_type() {
				return 'undescribed_action';
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

			public function settings( $step ) {
			}
		} );

		[ $funnel, $steps ] = $this->flow();

		$operation = [
			'op'    => 'add',
			'at'    => [ 'after' => $steps['first']->ID ],
			'steps' => [ [ 'type' => 'undescribed_action', 'id' => 'tmp_x' ] ],
		];

		// not through the ability
		$this->assertWPError( $this->apply( $funnel, [ $operation ], false ) );

		$added = $this->apply( $funnel, [ $operation ] );

		$this->assertNotWPError( $added );

		$ids = Flow_Operations::get_added_ids( $added );
		$this->assertArrayHasKey( 'tmp_x', $ids );

		$step = new Step( $ids['tmp_x'] );

		$this->assertEquals( 'Undescribed', $step->step_title );
		$this->assertEquals( 'inactive', $step->step_status );
		$this->assertEquals( 'blue', $step->get_meta( 'color' ) );
		$this->assertEquals( 'main', $step->branch );
		$this->assertEquals( $steps['first']->get_order() + 1, $step->get_order() );

		// but not with settings, which can't be resolved
		$operation['steps'][0] = [ 'type' => 'undescribed_action', 'settings' => [ 'color' => 'red' ] ];
		$this->assertWPError( $this->apply( $funnel, [ $operation ] ) );
	}

	public function test_premium_placeholders_cant_be_added() {

		if ( ! Plugin::instance()->step_manager->type_is_registered( 'logic_stop' ) || ! Plugin::instance()->step_manager->get_element( 'logic_stop' )->is_premium() ) {
			$this->markTestSkipped( 'Pro is active' );
		}

		[ $funnel ] = $this->flow();

		$result = $this->apply( $funnel, [
			[ 'op' => 'add', 'at' => [ 'branch' => 'main' ], 'steps' => [ [ 'type' => 'logic_stop' ] ] ],
		] );

		$this->assertWPError( $result );
	}

	public function test_restoring_a_deleted_step_brings_back_its_branches() {

		[ $funnel, $steps ] = $this->flow();

		$before = $this->layout( $funnel );

		$this->assertNotWPError( $this->apply( $funnel, [ [ 'op' => 'delete', 'step' => $steps['if']->ID ] ] ) );
		$this->assertEquals( [ 'first' => 'main', 'last' => 'main' ], $this->layout( $funnel ) );

		// not through the ability
		$restore = [
			'op'    => 'restore',
			'step'  => $steps['if']->ID,
			'at'    => [ 'after' => $steps['first']->ID ],
			'steps' => [ $steps['yes']->ID, $steps['no']->ID ],
		];

		$this->assertWPError( $this->apply( $funnel, [ $restore ], false ) );

		$this->assertNotWPError( $this->apply( $funnel, [ $restore ] ) );

		$this->assertEquals( $before, $this->layout( $funnel ) );

		// same IDs, same place
		$funnel->while_editing( function () use ( $funnel, $steps ) {
			$ids = wp_list_pluck( $funnel->get_steps(), 'ID' );
			$this->assertEquals( [ $steps['first']->ID, $steps['if']->ID, $steps['yes']->ID, $steps['no']->ID, $steps['last']->ID ], $ids );
		} );

		// it's not deleted anymore, so it can't be restored again
		$this->assertWPError( $this->apply( $funnel, [ $restore ] ) );
	}

	public function test_restoring_on_an_active_flow_drops_the_staged_delete() {

		[ $funnel, $steps ] = $this->flow( 'active' );

		$this->apply( $funnel, [ [ 'op' => 'delete', 'step' => $steps['last']->ID ] ] );

		$this->assertTrue( $funnel->while_editing( function () use ( $funnel ) {
			return $funnel->has_changes();
		} ) );

		$this->assertNotWPError( $this->apply( $funnel, [
			[ 'op' => 'restore', 'step' => $steps['last']->ID, 'at' => [ 'branch' => 'main', 'position' => 'end' ] ],
		] ) );

		$last = new Step( $steps['last']->ID );

		$this->assertEquals( 'active', $last->step_status );
		$this->assertFalse( $funnel->while_editing( function () use ( $funnel ) {
			return $funnel->has_changes();
		} ) );
	}

	public function test_the_editor_sets_settings_as_they_are_stored() {

		[ $funnel, $steps ] = $this->flow();

		$steps['first']->update_meta( 'step_notes', 'remove me' );

		$this->assertNotWPError( $this->apply( $funnel, [
			[
				'op'    => 'update',
				'step'  => $steps['first']->ID,
				'meta'  => [ 'delay_amount' => 5, 'step_notes' => null ],
				'title' => 'Wait <b>5 days</b>',
			],
		] ) );

		$first = new Step( $steps['first']->ID );

		$this->assertEquals( 5, $first->get_meta( 'delay_amount' ) );
		$this->assertEmpty( $first->get_meta( 'step_notes' ) );
		// formatting like generated titles have is kept
		$this->assertEquals( 'Wait <b>5 days</b>', $first->step_title );

		// the ability can't
		$this->assertWPError( $this->apply( $funnel, [
			[ 'op' => 'update', 'step' => $steps['first']->ID, 'meta' => [ 'delay_amount' => 1 ] ],
		], false ) );
	}

	public function test_a_failed_operation_undoes_the_ones_before_it() {

		[ $funnel, $steps ] = $this->flow();

		$before = $this->layout( $funnel );

		$result = $this->apply( $funnel, [
			[ 'op' => 'move', 'step' => $steps['last']->ID, 'at' => [ 'branch' => 'main', 'position' => 'start' ] ],
			[ 'op' => 'restore', 'step' => $steps['first']->ID, 'at' => [ 'branch' => 'main' ] ],
		] );

		$this->assertWPError( $result );
		$this->assertEquals( 'groundhogg_step_not_deleted', $result->get_error_code() );
		$this->assertEquals( $before, $this->layout( $funnel ) );
		$this->assertEquals( 5, ( new Step( $steps['last']->ID ) )->get_order() );
	}
}
