<?php

use Groundhogg\Abilities\Funnels\Flow_Operations;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Step;
use Groundhogg\Steps\Actions\Action;
use function Groundhogg\is_pro_features_active;

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

		if ( is_pro_features_active() ) {
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

	public function test_the_editor_saves_a_settings_panel_like_a_form_post() {

		[ $funnel, $steps ] = $this->flow();

		$yes = $steps['yes'];
		$tag = \Groundhogg\get_db( 'tags' )->add( [ 'tag_name' => 'From the panel' ] );

		$operations = new Flow_Operations( $funnel, true );

		$this->assertNotWPError( $operations->apply_all_or_nothing( [
			[
				'op'   => 'update',
				'step' => $yes->ID,
				// what the panel posts, without a branch, see flow-store.js formToSettings()
				'form' => [ 'tags' => [ (string) $tag ] ],
				'meta' => [ 'step_notes' => 'from JS' ],
			],
		] ) );

		$saved = new Step( $yes->ID );

		// saved by the step type
		$this->assertEquals( [ $tag ], wp_parse_id_list( $saved->get_meta( 'tags' ) ) );
		$this->assertEquals( 'from JS', $saved->get_meta( 'step_notes' ) );

		// it stays in its branch, saving would otherwise put it in main
		$this->assertEquals( "{$steps['if']->ID}-yes", $saved->branch );

		// its panel is sent back
		$this->assertEquals( [ $yes->ID ], $operations->get_touched() );
	}

	public function test_the_editor_sets_trigger_flags_as_they_are_stored() {

		$funnel = new Funnel( [ 'title' => 'flags', 'status' => 'inactive' ] );

		$trigger = $funnel->add_step( [
			'step_type'  => 'tag_applied',
			'step_group' => Step::BENCHMARK,
			'step_order' => 2,
		] );

		$funnel->set_step_levels();

		$this->assertNotWPError( $this->apply( $funnel, [
			[ 'op' => 'update', 'step' => $trigger->ID, 'flags' => [ 'is_conversion' => true, 'is_entry' => true, 'not_a_flag' => true ] ],
		] ) );

		$trigger = new Step( $trigger->ID );

		$this->assertTrue( $trigger->is_conversion() );
		$this->assertTrue( $trigger->is_entry() );
	}

	public function test_settings_islands() {

		[ $funnel, $steps ] = $this->flow();

		$islands = $funnel->while_editing( function () use ( $funnel ) {
			return $funnel->get_settings_islands( [ 0 ] );
		} );

		$this->assertEmpty( $islands );

		$islands = $funnel->while_editing( function () use ( $funnel ) {
			return $funnel->get_settings_islands();
		} );

		$this->assertCount( 5, $islands );

		$island = $islands[ $steps['yes']->ID ];

		$this->assertStringContainsString( 'main-step-settings-panel', $island['html'] );
		$this->assertStringContainsString( "step_{$steps['yes']->ID}_tags", $island['html'] );
		$this->assertTrue( $island['ignore_morph'] );

		// the server's panel still has it, fields can have random IDs so they're not compared
		$this->assertStringContainsString( 'main-step-settings-panel', $steps['yes']->html_v2( false ) );
	}

	public function test_duplicating_copies_the_branches_after_the_step() {

		[ $funnel, $steps ] = $this->flow();

		$operations = new Flow_Operations( $funnel );

		$added = $operations->apply_all_or_nothing( [
			[ 'op' => 'duplicate', 'step' => $steps['if']->ID, 'id' => 'copy' ],
			// the copy can be referred to by its local id
			[ 'op' => 'move', 'step' => $steps['last']->ID, 'at' => [ 'after' => 'copy' ] ],
		] );

		$this->assertNotWPError( $added );

		$copy = new Step( Flow_Operations::get_added_ids( $added )['copy'] );

		$this->assertEquals( 'if_else', $copy->get_type() );
		$this->assertEquals( 'inactive', $copy->step_status );
		$this->assertContains( (int) $copy->ID, $operations->get_touched() );

		$layout = $funnel->while_editing( function () use ( $funnel ) {
			return array_map( function ( Step $step ) {
				return [ $step->step_title, $step->branch ];
			}, $funnel->get_steps() );
		} );

		// right after the original with its own copies of the branch steps, then the moved step
		$this->assertEquals( [
			[ 'first', 'main' ],
			[ 'if', 'main' ],
			[ 'yes', "{$steps['if']->ID}-yes" ],
			[ 'no', "{$steps['if']->ID}-no" ],
			[ 'if', 'main' ],
			[ 'yes', "$copy->ID-yes" ],
			[ 'no', "$copy->ID-no" ],
			[ 'last', 'main' ],
		], $layout );
	}

	public function test_duplicating_without_branches() {

		[ $funnel, $steps ] = $this->flow();

		$added = $this->apply( $funnel, [
			[ 'op' => 'duplicate', 'step' => $steps['if']->ID, 'id' => 'copy', 'include_branches' => false, 'at' => [ 'branch' => 'main', 'position' => 'end' ] ],
		], false );

		$this->assertNotWPError( $added );

		$copy = Flow_Operations::get_added_ids( $added )['copy'];

		$in_branches = $funnel->while_editing( function () use ( $funnel, $copy ) {
			return array_filter( $funnel->get_steps(), function ( Step $step ) use ( $copy ) {
				return str_starts_with( $step->branch, "$copy-" );
			} );
		} );

		$this->assertEmpty( $in_branches );
		$this->assertEquals( $copy, $funnel->while_editing( function () use ( $funnel ) {
			return array_values( array_filter( $funnel->get_steps(), function ( Step $step ) {
				return $step->branch === 'main';
			} ) )[3]->ID;
		} ) );

		// the request isn't left changed
		$this->assertArrayNotHasKey( '__ignore_inner', $_POST );

	}

	public function test_step_types_get_their_duplicate_options() {

		[ $funnel, $steps ] = $this->flow();

		$type = Plugin::instance()->step_manager->get_element( 'delay_timer' );

		// reads them the way a step type's duplicate() does, like send_email's __duplicate_email
		$probe = new class extends \Groundhogg\Steps\Actions\Delay_Timer {
			public static $choice;

			public function duplicate( $new, $original ) {
				self::$choice = \Groundhogg\get_post_var( 'my_choice' );
			}
		};

		Plugin::instance()->step_manager->add_step( $probe );

		$this->assertNotWPError( $this->apply( $funnel, [
			[ 'op' => 'duplicate', 'step' => $steps['first']->ID, 'options' => [ 'my_choice' => 'yes please' ] ],
		], false ) );

		$this->assertEquals( 'yes please', $probe::$choice );

		Plugin::instance()->step_manager->add_step( $type );
	}

	public function test_copying_a_step_from_another_flow() {

		[ $from, $steps ] = $this->flow();
		$to = new Funnel( [ 'title' => 'to', 'status' => 'inactive' ] );

		// it needs to be told where
		$this->assertWPError( $this->apply( $to, [ [ 'op' => 'duplicate', 'step' => $steps['if']->ID ] ], false ) );

		$added = $this->apply( $to, [
			[ 'op' => 'duplicate', 'step' => $steps['if']->ID, 'id' => 'pasted', 'at' => [ 'branch' => 'main', 'position' => 'end' ] ],
		], false );

		$this->assertNotWPError( $added );

		$copy = new Step( Flow_Operations::get_added_ids( $added )['pasted'] );

		$this->assertEquals( $to->get_id(), $copy->get_funnel_id() );
		$this->assertEquals( 'main', $copy->branch );

		// with its branches, in the new flow
		$branch_steps = $to->while_editing( function () use ( $to, $copy ) {
			return array_map( function ( Step $step ) {
				return [ $step->step_title, $step->get_funnel_id() ];
			}, array_values( array_filter( $to->get_steps(), function ( Step $step ) use ( $copy ) {
				return str_starts_with( $step->branch, "$copy->ID-" );
			} ) ) );
		} );

		$this->assertEquals( [ [ 'yes', $to->get_id() ], [ 'no', $to->get_id() ] ], $branch_steps );

		// the original flow is untouched
		$this->assertCount( 5, $from->get_steps() );
	}

	public function test_locking_steps() {

		[ $funnel, $steps ] = $this->flow( 'active' );

		$this->assertNotWPError( $this->apply( $funnel, [ [ 'op' => 'lock', 'step' => $steps['last']->ID ] ], false ) );

		$this->assertTrue( ( new Step( $steps['last']->ID ) )->is_locked() );

		// written directly, not a change to publish
		$this->assertFalse( $funnel->while_editing( function () use ( $funnel ) {
			return $funnel->has_changes();
		} ) );

		// locked steps can't be moved
		$moved = $this->apply( $funnel, [ [ 'op' => 'move', 'step' => $steps['last']->ID, 'at' => [ 'branch' => 'main', 'position' => 'start' ] ] ], false );
		$this->assertWPError( $moved );
		$this->assertEquals( 'groundhogg_step_locked', $moved->get_error_code() );

		$this->assertNotWPError( $this->apply( $funnel, [ [ 'op' => 'unlock', 'step' => $steps['last']->ID ] ], false ) );
		$this->assertFalse( ( new Step( $steps['last']->ID ) )->is_locked() );
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
