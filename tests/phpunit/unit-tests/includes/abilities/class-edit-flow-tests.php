<?php

use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Step;
use function Groundhogg\db;
use function Groundhogg\parse_tag_list;

/**
 * groundhogg/edit-flow
 */
class Edit_Flow_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();

		if ( ! function_exists( 'wp_get_ability' ) || ! wp_get_ability( 'groundhogg/edit-flow' ) ) {
			$this->markTestSkipped( 'The groundhogg/edit-flow ability is not registered.' );
		}

		$this->factory()->truncate();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
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
	 * Create a flow, steps are named by their titles
	 *
	 * @return array step title => step id, and 'flow' => flow id
	 */
	protected function create_flow( array $steps, $active = false ) {

		$out = $this->execute( 'groundhogg/create-flow', [ 'title' => 'test flow', 'steps' => $steps ] );
		$this->assertNotWPError( $out );

		if ( $active ) {
			$funnel = new Funnel( $out['id'] );
			$funnel->update( [ 'status' => 'active' ] );
			$funnel->commit();
		}

		$ids = [ 'flow' => $out['id'] ];

		$collect = function ( $nodes ) use ( &$collect, &$ids ) {
			foreach ( $nodes as $node ) {
				$ids[ $node['title'] ] = $node['id'];
				foreach ( $node['branches'] ?? [] as $sub_nodes ) {
					$collect( $sub_nodes );
				}
			}
		};

		$collect( $out['steps'] );

		return $ids;
	}

	protected function delay( $title, $settings = [] ) {
		return [ 'type' => 'delay_timer', 'title' => $title, 'settings' => $settings ];
	}

	protected function if_else( $title, $branches = [] ) {
		return [ 'type' => 'if_else', 'title' => $title, 'branches' => $branches ];
	}

	protected function edit( int $flow_id, array $operations, array $input = [] ) {
		return $this->execute( 'groundhogg/edit-flow', array_merge( [ 'flow_id' => $flow_id, 'operations' => $operations ], $input ) );
	}

	protected function get_flow( int $flow_id, $view = 'draft' ) {
		return $this->execute( 'groundhogg/get-flow', [ 'flow_id' => $flow_id, 'view' => $view ] );
	}

	/**
	 * The tree as titles, branches as title => [ key => [ ... ] ]
	 */
	protected function outline( array $nodes ) {
		return array_map( function ( $node ) {
			if ( empty( $node['branches'] ) ) {
				return $node['title'];
			}

			return [
				$node['title'] => array_map( [ $this, 'outline' ], $node['branches'] )
			];
		}, $nodes );
	}

	protected function count_step_rows( int $flow_id ) {
		return db()->steps->count( [ 'funnel_id' => $flow_id ] );
	}

	public function test_add_after_a_step() {
		$ids = $this->create_flow( [ $this->delay( 'a' ), $this->delay( 'b' ) ] );

		$out = $this->edit( $ids['flow'], [
			[ 'op' => 'add', 'at' => [ 'after' => $ids['a'] ], 'steps' => [ $this->delay( 'new' ) ] ],
		] );

		$this->assertNotWPError( $out );
		$this->assertEquals( [ 'a', 'new', 'b' ], $this->outline( $out['flow']['steps'] ) );
		$this->assertEquals( 'new', $out['added'][0][0]['title'] );
		$this->assertEquals( [ 'a', 'new', 'b' ], $this->outline( $this->get_flow( $ids['flow'] )['steps'] ) );
	}

	public function test_add_to_a_branch_and_with_branches() {
		$ids = $this->create_flow( [ $this->if_else( 'if', [ 'no' => [ $this->delay( 'x' ) ] ] ) ] );

		$out = $this->edit( $ids['flow'], [
			[ 'op' => 'add', 'at' => [ 'branch_of' => $ids['if'], 'branch' => 'no', 'position' => 'start' ], 'steps' => [ $this->delay( 'first' ) ] ],
			[ 'op' => 'add', 'at' => [ 'branch' => 'main', 'position' => 'end' ], 'steps' => [ $this->if_else( 'if2', [ 'yes' => [ $this->delay( 'y' ) ] ] ) ] ],
		] );

		$this->assertNotWPError( $out );
		$this->assertEquals( [
			[ 'if' => [ 'yes' => [], 'no' => [ 'first', 'x' ] ] ],
			[ 'if2' => [ 'yes' => [ 'y' ], 'no' => [] ] ],
		], $this->outline( $out['flow']['steps'] ) );
	}

	public function test_later_operations_can_reference_added_steps() {
		$ids = $this->create_flow( [ $this->delay( 'a' ) ] );

		$out = $this->edit( $ids['flow'], [
			[ 'op' => 'add', 'at' => [ 'after' => $ids['a'] ], 'steps' => [ [ 'type' => 'create_task', 'id' => 'call', 'title' => 'call' ] ] ],
			[ 'op' => 'add', 'at' => [ 'after' => 'call' ], 'steps' => [ [ 'type' => 'task_completed', 'title' => 'called', 'settings' => [ 'tasks' => [ 'call' ] ] ] ] ],
			[ 'op' => 'add', 'at' => [ 'branch' => 'main' ], 'steps' => [ $this->delay( 'no local id' ) ] ],
		] );

		$this->assertNotWPError( $out );

		$call  = $out['added'][0][0]['id'];
		$added = $out['added'][2][0]['id'];

		$out = $this->edit( $ids['flow'], [
			[ 'op' => 'move', 'step' => $added, 'at' => [ 'before' => $ids['a'] ] ],
		] );

		$this->assertNotWPError( $out );
		$this->assertEquals( [ 'no local id', 'a', 'call', 'called' ], $this->outline( $out['flow']['steps'] ) );
		$this->assertEquals( [ $call ], $out['flow']['steps'][3]['settings']['tasks'] );
	}

	public function test_update_changes_only_the_given_settings() {
		$ids = $this->create_flow( [ $this->delay( 'a', [ 'delay_amount' => 3, 'delay_type' => 'hours' ] ) ] );

		$out = $this->edit( $ids['flow'], [
			[ 'op' => 'update', 'step' => $ids['a'], 'settings' => [ 'delay_amount' => 5 ], 'title' => 'renamed' ],
		] );

		$this->assertNotWPError( $out );

		$node = $out['flow']['steps'][0];
		$this->assertEquals( 'renamed', $node['title'] );
		$this->assertEquals( 5, $node['settings']['delay_amount'] );
		$this->assertEquals( 'hours', $node['settings']['delay_type'] );
	}

	public function test_update_if_else_condition_replaces_only_its_side() {
		$tag = parse_tag_list( [ 'edit flow test' ], 'ID', true )[0];

		$ids = $this->create_flow( [
			[
				'type'     => 'if_else',
				'title'    => 'if',
				'settings' => [ 'include_condition' => [ 'marketable' => true ], 'exclude_condition' => [ 'tags_include' => [ $tag ] ] ]
			]
		] );

		$before = $this->get_flow( $ids['flow'] )['steps'][0]['settings'];

		$out = $this->edit( $ids['flow'], [
			[ 'op' => 'update', 'step' => $ids['if'], 'settings' => [ 'include_condition' => [ 'tags_include' => [ $tag ] ] ] ],
		] );

		$this->assertNotWPError( $out );

		$after = $out['flow']['steps'][0]['settings'];
		$this->assertEquals( 'tags', $after['include_filters'][0][0]['type'] );
		$this->assertEquals( $before['exclude_filters'], $after['exclude_filters'] );
	}

	public function test_update_benchmark_flags() {
		$ids = $this->create_flow( [ [ 'type' => 'tag_applied', 'title' => 'trigger' ], $this->delay( 'a' ) ] );

		$out = $this->edit( $ids['flow'], [
			[ 'op' => 'update', 'step' => $ids['trigger'], 'is_conversion' => true ],
		] );

		$this->assertNotWPError( $out );
		$this->assertTrue( $out['flow']['steps'][0]['is_conversion'] );

		$out = $this->edit( $ids['flow'], [
			[ 'op' => 'update', 'step' => $ids['a'], 'is_conversion' => true ],
		] );

		$this->assertWPError( $out );
		$this->assertEquals( 'groundhogg_not_a_benchmark', $out->get_error_code() );
	}

	public function test_move_takes_the_branches_along() {
		$ids = $this->create_flow( [
			$this->delay( 'a' ),
			$this->if_else( 'if', [ 'yes' => [ $this->delay( 'x' ) ] ] ),
			$this->delay( 'b' ),
		] );

		$out = $this->edit( $ids['flow'], [
			[ 'op' => 'move', 'step' => $ids['b'], 'at' => [ 'branch_of' => $ids['if'], 'branch' => 'yes', 'position' => 'start' ] ],
			[ 'op' => 'move', 'step' => $ids['if'], 'at' => [ 'before' => $ids['a'] ] ],
		] );

		$this->assertNotWPError( $out );
		$this->assertEquals( [
			[ 'if' => [ 'yes' => [ 'b', 'x' ], 'no' => [] ] ],
			'a',
		], $this->outline( $out['flow']['steps'] ) );
	}

	public function test_move_into_a_benchmarks_then_branch() {
		$ids = $this->create_flow( [ [ 'type' => 'tag_applied', 'title' => 'trigger' ], $this->delay( 'a' ) ] );

		$out = $this->edit( $ids['flow'], [
			[ 'op' => 'move', 'step' => $ids['a'], 'at' => [ 'branch_of' => $ids['trigger'], 'branch' => 'then' ] ],
		] );

		$this->assertNotWPError( $out );
		$this->assertEquals( [ [ 'trigger' => [ 'then' => [ 'a' ] ] ] ], $this->outline( $out['flow']['steps'] ) );
	}

	public function test_a_step_cant_move_into_its_own_branches() {
		$ids = $this->create_flow( [ $this->if_else( 'if', [ 'yes' => [ $this->if_else( 'inner' ) ] ] ) ] );

		$out = $this->edit( $ids['flow'], [
			[ 'op' => 'move', 'step' => $ids['if'], 'at' => [ 'branch_of' => $ids['inner'], 'branch' => 'no' ] ],
		] );

		$this->assertWPError( $out );
		$this->assertEquals( 'groundhogg_invalid_position', $out->get_error_code() );
	}

	public function test_delete_takes_the_branches_along() {
		$ids = $this->create_flow( [
			$this->delay( 'a' ),
			$this->if_else( 'if', [ 'yes' => [ $this->delay( 'x' ) ] ] ),
		] );

		$this->factory()->event_queue->create( [
			'funnel_id'  => $ids['flow'],
			'step_id'    => $ids['x'],
			'contact_id' => $this->factory()->contacts->create(),
			'event_type' => Event::FUNNEL,
			'status'     => Event::PAUSED,
		] );

		$out = $this->edit( $ids['flow'], [
			[ 'op' => 'delete', 'step' => $ids['if'] ],
		] );

		$this->assertNotWPError( $out );
		$this->assertEquals( [ 'a' ], $this->outline( $out['flow']['steps'] ) );

		$deleted = wp_list_pluck( $out['deleted_steps'], 'waiting_contacts', 'id' );
		$this->assertEquals( [ $ids['if'] => 0, $ids['x'] => 1 ], $deleted );

		// soft deleted, activating removes them
		$this->assertEquals( 'deleted', ( new Step( $ids['x'] ) )->step_status );
	}

	public function test_deleted_steps_cant_be_referenced() {
		$ids = $this->create_flow( [ $this->delay( 'a' ), $this->delay( 'b' ) ] );

		$out = $this->edit( $ids['flow'], [
			[ 'op' => 'delete', 'step' => $ids['a'] ],
			[ 'op' => 'update', 'step' => $ids['a'], 'title' => 'nope' ],
		] );

		$this->assertWPError( $out );
		$this->assertEquals( 'groundhogg_step_not_found', $out->get_error_code() );
		$this->assertEquals( 1, $out->get_error_data()['operation'] );
	}

	public function test_edits_to_an_active_flow_are_staged() {
		$ids = $this->create_flow( [ $this->delay( 'a' ), $this->delay( 'b' ) ], true );

		$out = $this->edit( $ids['flow'], [
			[ 'op' => 'add', 'at' => [ 'after' => $ids['a'] ], 'steps' => [ $this->delay( 'new' ) ] ],
			[ 'op' => 'update', 'step' => $ids['a'], 'title' => 'renamed' ],
			[ 'op' => 'move', 'step' => $ids['b'], 'at' => [ 'before' => $ids['a'] ] ],
		] );

		$this->assertNotWPError( $out );
		$this->assertTrue( $out['flow']['has_unpublished_changes'] );
		$this->assertEquals( [ 'b', 'renamed', 'new' ], $this->outline( $out['flow']['steps'] ) );

		$this->assertEquals( [ 'a', 'b' ], $this->outline( $this->get_flow( $ids['flow'], 'live' )['steps'] ) );

		// publishing makes them live
		$funnel = new Funnel( $ids['flow'] );
		$funnel->commit();

		$this->assertEquals( [ 'b', 'renamed', 'new' ], $this->outline( $this->get_flow( $ids['flow'], 'live' )['steps'] ) );
	}

	public function test_dry_run_changes_nothing() {
		$ids    = $this->create_flow( [ $this->delay( 'a' ), $this->if_else( 'if', [ 'yes' => [ $this->delay( 'x' ) ] ] ) ] );
		$before = $this->get_flow( $ids['flow'] );
		$rows   = $this->count_step_rows( $ids['flow'] );

		$out = $this->edit( $ids['flow'], [
			[ 'op' => 'add', 'at' => [ 'after' => $ids['a'] ], 'steps' => [ $this->delay( 'new' ) ] ],
			[ 'op' => 'update', 'step' => $ids['a'], 'settings' => [ 'delay_amount' => 9 ] ],
			[ 'op' => 'delete', 'step' => $ids['if'] ],
		], [ 'dry_run' => true ] );

		$this->assertNotWPError( $out );
		$this->assertTrue( $out['dry_run'] );
		$this->assertEquals( [ 'a', 'new' ], $this->outline( $out['flow']['steps'] ) );

		$this->assertEquals( $before, $this->get_flow( $ids['flow'] ) );
		$this->assertEquals( $rows, $this->count_step_rows( $ids['flow'] ) );
	}

	public function test_a_failed_operation_rolls_back_the_others() {
		$ids    = $this->create_flow( [ $this->delay( 'a' ) ], true );
		$before = $this->get_flow( $ids['flow'] );
		$rows   = $this->count_step_rows( $ids['flow'] );

		$out = $this->edit( $ids['flow'], [
			[ 'op' => 'add', 'at' => [ 'after' => $ids['a'] ], 'steps' => [ $this->delay( 'new' ) ] ],
			[ 'op' => 'update', 'step' => $ids['a'], 'settings' => [ 'delay_amount' => 9 ] ],
			[ 'op' => 'move', 'step' => 999999, 'at' => [ 'branch' => 'main' ] ],
		] );

		$this->assertWPError( $out );
		$this->assertStringStartsWith( 'operations[2] (move):', $out->get_error_message() );

		$this->assertEquals( $before, $this->get_flow( $ids['flow'] ) );
		$this->assertEquals( $rows, $this->count_step_rows( $ids['flow'] ) );
	}

	public function test_expected_revision() {
		$ids      = $this->create_flow( [ $this->delay( 'a' ) ] );
		$revision = $this->get_flow( $ids['flow'] )['revision'];

		$update = [ [ 'op' => 'update', 'step' => $ids['a'], 'title' => 'renamed' ] ];

		$this->assertNotWPError( $this->edit( $ids['flow'], $update, [ 'expected_revision' => $revision ] ) );

		$out = $this->edit( $ids['flow'], $update, [ 'expected_revision' => $revision ] );

		$this->assertWPError( $out );
		$this->assertEquals( 'groundhogg_flow_changed', $out->get_error_code() );
	}

	public function test_locked_steps_cant_be_changed() {
		$ids = $this->create_flow( [ $this->delay( 'a' ) ] );

		db()->steps->update( $ids['a'], [ 'is_locked' => 1 ] );

		foreach ( [
			[ 'op' => 'update', 'step' => $ids['a'], 'title' => 'renamed' ],
			[ 'op' => 'move', 'step' => $ids['a'], 'at' => [ 'branch' => 'main' ] ],
			[ 'op' => 'delete', 'step' => $ids['a'] ],
		] as $operation ) {
			$out = $this->edit( $ids['flow'], [ $operation ] );
			$this->assertWPError( $out );
			$this->assertEquals( 'groundhogg_step_locked', $out->get_error_code() );
		}
	}

	public function test_invalid_positions() {
		$ids = $this->create_flow( [ $this->delay( 'a' ), $this->if_else( 'if' ) ] );

		foreach ( [
			[],
			[ 'after' => $ids['a'], 'before' => $ids['a'] ],
			[ 'branch_of' => $ids['if'], 'branch' => 'maybe' ],
			[ 'branch_of' => $ids['a'], 'branch' => 'yes' ],
		] as $at ) {
			$out = $this->edit( $ids['flow'], [ [ 'op' => 'add', 'at' => $at, 'steps' => [ $this->delay( 'new' ) ] ] ] );
			$this->assertWPError( $out, wp_json_encode( $at ) );
		}
	}
}
