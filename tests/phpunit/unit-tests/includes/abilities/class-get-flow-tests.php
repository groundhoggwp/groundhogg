<?php

use Groundhogg\Abilities\Schemas\Step_Type_Schema;
use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Step;
use function Groundhogg\parse_tag_list;

/**
 * groundhogg/get-flow returns a flow as the tree create-flow takes
 */
class Get_Flow_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();

		if ( ! function_exists( 'wp_get_ability' ) || ! wp_get_ability( 'groundhogg/get-flow' ) ) {
			$this->markTestSkipped( 'The groundhogg/get-flow ability is not registered.' );
		}

		$this->factory()->truncate();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	protected function execute( string $ability, array $input ) {
		$out = wp_get_ability( $ability )->execute( $input );
		$this->assertNotWPError( $out );

		// the way an MCP client would get it
		return json_decode( wp_json_encode( $out ), true );
	}

	protected function create_flow( array $steps ) {
		return $this->execute( 'groundhogg/create-flow', [ 'title' => 'test flow', 'steps' => $steps ] );
	}

	protected function get_flow( int $id, array $input = [] ) {
		return $this->execute( 'groundhogg/get-flow', array_merge( [ 'flow_id' => $id ], $input ) );
	}

	public function test_returns_the_tree_create_flow_built() {

		$tag = parse_tag_list( [ 'get flow test' ], 'ID', true )[0];

		$created = $this->create_flow( [
			[ 'type' => 'tag_applied', 'settings' => [ 'tags' => [ $tag ] ] ],
			[ 'type' => 'create_task', 'id' => 'call', 'settings' => [ 'summary' => 'Call them' ] ],
			[ 'type' => 'task_completed', 'settings' => [ 'tasks' => [ 'call' ] ] ],
			[
				'type'     => 'if_else',
				'settings' => [ 'include_condition' => [ 'tags_include' => [ $tag ] ] ],
				'branches' => [
					'yes' => [ [ 'type' => 'delay_timer', 'settings' => [ 'delay_amount' => 2, 'delay_type' => 'days' ] ] ],
				],
			],
		] );

		$flow = $this->get_flow( $created['id'] );

		$this->assertEquals( $created['id'], $flow['id'] );
		$this->assertEquals( 'draft', $flow['view'] );
		$this->assertFalse( $flow['has_unpublished_changes'] );
		$this->assertEmpty( $flow['unplaced_steps'] );

		$steps = $flow['steps'];

		$this->assertEquals( [ 'tag_applied', 'create_task', 'task_completed', 'if_else' ], wp_list_pluck( $steps, 'type' ) );
		$this->assertEquals( wp_list_pluck( $created['steps'], 'id' ), wp_list_pluck( $steps, 'id' ) );
		$this->assertEquals( [ 'benchmark', 'action', 'benchmark', 'logic' ], wp_list_pluck( $steps, 'group' ) );

		$this->assertEquals( [ $tag ], wp_parse_id_list( $steps[0]['settings']['tags'] ) );
		$this->assertTrue( $steps[0]['buildable'] );
		$this->assertArrayHasKey( 'is_entry', $steps[0] );
		$this->assertArrayNotHasKey( 'is_entry', $steps[1] );

		$this->assertEquals( 'Call them', $steps[1]['settings']['summary'] );

		// references to other steps are real IDs
		$this->assertEquals( [ $steps[1]['id'] ], $steps[2]['settings']['tasks'] );

		// if_else's conditions are the stored filters, every branch is listed
		$if_else = $steps[3];
		$this->assertNotEmpty( $if_else['settings']['include_filters'] );
		$this->assertEquals( 'tags', $if_else['settings']['include_filters'][0][0]['type'] );
		$this->assertEquals( [ 'yes', 'no' ], array_keys( $if_else['branches'] ) );
		$this->assertEmpty( $if_else['branches']['no'] );

		$delay = $if_else['branches']['yes'][0];
		$this->assertEquals( 'delay_timer', $delay['type'] );
		$this->assertEquals( 2, $delay['settings']['delay_amount'] );
		$this->assertEquals( 'days', $delay['settings']['delay_type'] );
	}

	public function test_if_else_filters_can_be_passed_back() {

		$tag = parse_tag_list( [ 'get flow test' ], 'ID', true )[0];

		$created = $this->create_flow( [
			[ 'type' => 'if_else', 'settings' => [ 'include_condition' => [ 'tags_include' => [ $tag ] ] ] ],
		] );

		$settings = $this->get_flow( $created['id'] )['steps'][0]['settings'];

		$copy = $this->create_flow( [
			[
				'type'     => 'if_else',
				'settings' => [
					'include_filters' => $settings['include_filters'],
					'exclude_filters' => $settings['exclude_filters'],
				]
			],
		] );

		$this->assertEquals( $settings, $this->get_flow( $copy['id'] )['steps'][0]['settings'] );
	}

	public function test_if_else_refuses_both_a_condition_and_filters() {

		$out = wp_get_ability( 'groundhogg/create-flow' )->execute( [
			'title' => 'test flow',
			'steps' => [
				[
					'type'     => 'if_else',
					'settings' => [
						'include_condition' => [ 'marketable' => true ],
						'include_filters'   => [ [ [ 'type' => 'is_marketable', 'marketable' => 'yes' ] ] ],
					]
				]
			],
		] );

		$this->assertWPError( $out );
		$this->assertEquals( 'groundhogg_conflicting_if_else_settings', $out->get_error_code() );
	}

	public function test_steps_inside_a_benchmark_are_its_then_branch() {

		$created   = $this->create_flow( [ [ 'type' => 'tag_applied' ], [ 'type' => 'delay_timer' ] ] );
		$benchmark = $created['steps'][0]['id'];

		( new Funnel( $created['id'] ) )->add_step( [
			'step_type'  => 'delay_timer',
			'step_group' => Step::ACTION,
			'branch'     => "$benchmark",
		] );

		$steps = $this->get_flow( $created['id'] )['steps'];

		$this->assertCount( 2, $steps );
		$this->assertEquals( 'delay_timer', $steps[0]['branches']['then'][0]['type'] );
	}

	public function test_steps_on_a_missing_branch_are_unplaced() {

		$created = $this->create_flow( [ [ 'type' => 'delay_timer' ] ] );

		$orphan = ( new Funnel( $created['id'] ) )->add_step( [
			'step_type'  => 'delay_timer',
			'step_group' => Step::ACTION,
			'branch'     => '999999-yes',
		] );

		$flow = $this->get_flow( $created['id'] );

		$this->assertCount( 1, $flow['steps'] );
		$this->assertEquals( [ $orphan->get_id() ], wp_list_pluck( $flow['unplaced_steps'], 'id' ) );
	}

	public function test_draft_and_live_views_of_an_active_flow() {

		$created = $this->create_flow( [ [ 'type' => 'delay_timer', 'title' => 'wait' ] ] );
		$funnel  = new Funnel( $created['id'] );
		$funnel->update( [ 'status' => 'active' ] );
		$funnel->commit();

		( new Step( $created['steps'][0]['id'] ) )->update( [ 'step_title' => 'wait longer' ] );
		$funnel->add_step( [
			'step_type'   => 'delay_timer',
			'step_group'  => Step::ACTION,
			'step_status' => 'inactive',
			'step_title'  => 'new',
		] );

		$draft = $this->get_flow( $created['id'] );

		$this->assertTrue( $draft['has_unpublished_changes'] );
		$this->assertEquals( [ 'wait longer', 'new' ], wp_list_pluck( $draft['steps'], 'title' ) );
		$this->assertEquals( [ true, true ], wp_list_pluck( $draft['steps'], 'has_unpublished_changes' ) );

		$live = $this->get_flow( $created['id'], [ 'view' => 'live' ] );

		$this->assertFalse( $live['has_unpublished_changes'] );
		$this->assertEquals( [ 'wait' ], wp_list_pluck( $live['steps'], 'title' ) );
		$this->assertArrayNotHasKey( 'has_unpublished_changes', $live['steps'][0] );

		$this->assertFalse( $funnel->is_editing() );
	}

	public function test_deleted_steps_are_unpublished_changes() {

		$created = $this->create_flow( [ [ 'type' => 'delay_timer' ], [ 'type' => 'delay_timer' ] ] );
		$funnel  = new Funnel( $created['id'] );
		$funnel->update( [ 'status' => 'active' ] );
		$funnel->commit();

		( new Step( $created['steps'][1]['id'] ) )->delete();

		$draft = $this->get_flow( $created['id'] );

		$this->assertTrue( $draft['has_unpublished_changes'] );
		$this->assertCount( 1, $draft['steps'] );
	}

	public function test_waiting_contacts() {

		$created = $this->create_flow( [ [ 'type' => 'delay_timer' ] ] );
		$step_id = $created['steps'][0]['id'];

		foreach ( [ Event::WAITING, Event::PAUSED, Event::COMPLETE ] as $status ) {
			$this->factory()->event_queue->create( [
				'funnel_id'  => $created['id'],
				'step_id'    => $step_id,
				'contact_id' => $this->factory()->contacts->create(),
				'event_type' => Event::FUNNEL,
				'status'     => $status,
			] );
		}

		$this->assertEquals( 2, $this->get_flow( $created['id'] )['steps'][0]['waiting_contacts'] );
	}

	public function test_revision_changes_when_a_step_changes() {

		$created = $this->create_flow( [ [ 'type' => 'delay_timer' ] ] );

		$before = $this->get_flow( $created['id'] )['revision'];

		$this->assertEquals( $before, $this->get_flow( $created['id'] )['revision'] );

		( new Step( $created['steps'][0]['id'] ) )->update_meta( 'delay_amount', 9 );

		$this->assertNotEquals( $before, $this->get_flow( $created['id'] )['revision'] );
	}

	public function test_step_types_that_cant_be_built_have_no_settings() {

		$types = array_diff( Step_Type_Schema::all_registered_types(), Step_Type_Schema::supported_types() );

		if ( empty( $types ) ) {
			$this->markTestSkipped( 'Every registered step type can be built.' );
		}

		$created = $this->create_flow( [ [ 'type' => 'delay_timer' ] ] );

		( new Funnel( $created['id'] ) )->add_step( [
			'step_type'  => reset( $types ),
			'step_group' => Step::ACTION,
		] );

		$node = $this->get_flow( $created['id'] )['steps'][1];

		$this->assertFalse( $node['buildable'] );
		$this->assertArrayNotHasKey( 'settings', $node );
	}

	public function test_missing_flow() {
		$out = wp_get_ability( 'groundhogg/get-flow' )->execute( [ 'flow_id' => 999999 ] );

		$this->assertWPError( $out );
		$this->assertEquals( 'groundhogg_flow_not_found', $out->get_error_code() );
	}
}
