<?php

use Groundhogg\Admin\Funnels\Funnels_Page;
use Groundhogg\Funnel;
use Groundhogg\Step;

/**
 * Steps other steps point at in their settings can't be deleted, in the flow editor and the flow abilities
 */
class Step_References_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();

		if ( ! function_exists( 'wp_get_ability' ) || ! wp_get_ability( 'groundhogg/edit-flow' ) ) {
			$this->markTestSkipped( 'The flow abilities are not registered.' );
		}

		$this->factory()->truncate();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	public function tearDown(): void {
		$_POST    = [];
		$_REQUEST = [];
		parent::tearDown();
	}

	protected function execute( string $ability, array $input ) {
		$out = wp_get_ability( $ability )->execute( $input );

		return is_wp_error( $out ) ? $out : json_decode( wp_json_encode( $out ), true );
	}

	/**
	 * Create a flow, steps are named by their titles
	 *
	 * @return array step title => step id, and 'flow' => flow id
	 */
	protected function create_flow( array $steps ) {

		$out = $this->execute( 'groundhogg/create-flow', [ 'title' => 'references test', 'steps' => $steps ] );
		$this->assertNotWPError( $out );

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

	/**
	 * A task, and a trigger for when it's completed
	 */
	protected function task_flow() {
		return $this->create_flow( [
			[ 'type' => 'create_task', 'id' => 'call', 'title' => 'call' ],
			[ 'type' => 'delay_timer', 'title' => 'wait' ],
			[ 'type' => 'task_completed', 'title' => 'called', 'settings' => [ 'tasks' => [ 'call' ] ] ],
		] );
	}

	protected function can_delete( int $flow_id, array $step_ids ) {
		$funnel = new Funnel( $flow_id );

		return $funnel->while_editing( function () use ( $funnel, $step_ids ) {
			return $funnel->can_delete_steps( $step_ids );
		} );
	}

	/**
	 * Delete a step through the flow editor's save
	 */
	protected function delete_in_editor( int $flow_id, int $step_id ) {

		$_POST['_delete_step'] = $step_id;
		$_REQUEST['funnel']    = $flow_id;

		return ( new Funnels_Page() )->process_edit();
	}

	public function test_step_types_declare_their_references() {
		$ids = $this->create_flow( [
			[ 'type' => 'send_email', 'id' => 'first', 'title' => 'first' ],
			[ 'type' => 'send_email', 'title' => 'reply', 'settings' => [ 'reply_in_thread' => 'first' ] ],
			[ 'type' => 'create_task', 'id' => 'call', 'title' => 'call' ],
			[ 'type' => 'task_completed', 'title' => 'called', 'settings' => [ 'tasks' => [ 'call' ] ] ],
		] );

		$reply  = new Step( $ids['reply'] );
		$called = new Step( $ids['called'] );

		$this->assertEquals( [ $ids['first'] ], $reply->get_step_element()->get_step_references( $reply ) );
		$this->assertEquals( [ $ids['call'] ], $called->get_step_element()->get_step_references( $called ) );

		$this->assertEquals( [
			$ids['first'] => [ $ids['reply'] ],
			$ids['call']  => [ $ids['called'] ],
		], ( new Funnel( $ids['flow'] ) )->get_step_references_map() );
	}

	public function test_a_referenced_step_cant_be_deleted() {
		$ids = $this->task_flow();

		$result = $this->can_delete( $ids['flow'], [ $ids['call'] ] );

		$this->assertWPError( $result );
		$this->assertEquals( 'step_referenced', $result->get_error_code() );
		$this->assertEquals( [ $ids['call'] => [ $ids['called'] ] ], $result->get_error_data()['referenced_by'] );
		$this->assertStringContainsString( '"called"', $result->get_error_message() );
	}

	public function test_steps_can_be_deleted_with_the_steps_pointing_at_them() {
		$ids = $this->task_flow();

		$this->assertTrue( $this->can_delete( $ids['flow'], [ $ids['call'], $ids['called'] ] ) );
		$this->assertTrue( $this->can_delete( $ids['flow'], [ $ids['wait'] ] ) );
	}

	public function test_deleted_steps_dont_block() {
		$ids = $this->task_flow();

		( new Step( $ids['called'] ) )->delete();

		$this->assertTrue( $this->can_delete( $ids['flow'], [ $ids['call'] ] ) );
	}

	public function test_steps_in_a_deleted_branch_count() {
		$ids = $this->create_flow( [
			[
				'type'     => 'if_else',
				'title'    => 'if',
				'branches' => [
					'yes' => [ [ 'type' => 'create_task', 'id' => 'call', 'title' => 'call' ] ],
					'no'  => [ [ 'type' => 'task_completed', 'title' => 'inside', 'settings' => [ 'tasks' => [ 'call' ] ] ] ],
				],
			],
			[ 'type' => 'task_completed', 'title' => 'outside', 'settings' => [ 'tasks' => [ 'call' ] ] ],
		] );

		$if = new Step( $ids['if'] );
		$this->assertEqualsCanonicalizing( [ $ids['call'], $ids['inside'] ], $if->get_descendant_ids() );

		// "outside" still points at the task in the branch
		$this->assertWPError( $this->can_delete( $ids['flow'], array_merge( [ $ids['if'] ], $if->get_descendant_ids() ) ) );

		( new Step( $ids['outside'] ) )->delete();

		// "inside" is deleted with it
		$this->assertTrue( $this->can_delete( $ids['flow'], array_merge( [ $ids['if'] ], $if->get_descendant_ids() ) ) );
	}

	public function test_add_to_flow_in_another_flow_blocks() {
		$target = $this->create_flow( [ [ 'type' => 'delay_timer', 'title' => 'entry' ] ] );
		( new Funnel( $target['flow'] ) )->update( [ 'title' => 'target flow' ] );

		$this->create_flow( [
			[ 'type' => 'add_to_flow', 'title' => 'send them', 'settings' => [ 'flow_id' => $target['flow'], 'step_id' => $target['entry'] ] ],
		] );

		$result = $this->can_delete( $target['flow'], [ $target['entry'] ] );

		// names the step, and the other flow it's in
		$this->assertWPError( $result );
		$this->assertStringContainsString( '"send them" in the flow "references test"', $result->get_error_message() );
	}

	public function test_the_flow_editor_refuses_to_delete_a_referenced_step() {
		$ids = $this->task_flow();

		$result = $this->delete_in_editor( $ids['flow'], $ids['call'] );

		$this->assertWPError( $result );
		$this->assertEquals( 'step_referenced', $result->get_error_code() );
		$this->assertEquals( 'inactive', ( new Step( $ids['call'] ) )->step_status );

		// the editor posts the steps too, without them the rest of the save complains, the delete is what matters here
		$result = $this->delete_in_editor( $ids['flow'], $ids['wait'] );
		$this->assertNotEquals( 'step_referenced', is_wp_error( $result ) ? $result->get_error_code() : '' );
		$this->assertEquals( 'deleted', ( new Step( $ids['wait'] ) )->step_status );
	}

	public function test_the_flow_editor_checks_the_steps_in_branches() {
		$ids = $this->create_flow( [
			[
				'type'     => 'if_else',
				'title'    => 'if',
				'branches' => [ 'yes' => [ [ 'type' => 'create_task', 'id' => 'call', 'title' => 'call' ] ] ],
			],
			[ 'type' => 'task_completed', 'title' => 'outside', 'settings' => [ 'tasks' => [ 'call' ] ] ],
		] );

		$this->assertWPError( $this->delete_in_editor( $ids['flow'], $ids['if'] ) );
		$this->assertEquals( 'inactive', ( new Step( $ids['if'] ) )->step_status );
		$this->assertEquals( 'inactive', ( new Step( $ids['call'] ) )->step_status );
	}

	public function test_get_flow_reports_referenced_by() {
		$ids = $this->task_flow();

		$steps = $this->execute( 'groundhogg/get-flow', [ 'flow_id' => $ids['flow'] ] )['steps'];

		$this->assertEquals( [ $ids['called'] ], $steps[0]['referenced_by'] );
		$this->assertEquals( [], $steps[1]['referenced_by'] );
	}

	public function test_edit_flow_refuses_to_delete_a_referenced_step() {
		$ids    = $this->task_flow();
		$before = $this->execute( 'groundhogg/get-flow', [ 'flow_id' => $ids['flow'] ] );

		$out = $this->execute( 'groundhogg/edit-flow', [
			'flow_id'    => $ids['flow'],
			'operations' => [
				[ 'op' => 'delete', 'step' => $ids['wait'] ],
				[ 'op' => 'delete', 'step' => $ids['call'] ],
			],
		] );

		$this->assertWPError( $out );
		$this->assertEquals( 'step_referenced', $out->get_error_code() );
		$this->assertStringStartsWith( 'operations[1] (delete):', $out->get_error_message() );

		// all or nothing, the first delete was rolled back
		$this->assertEquals( $before, $this->execute( 'groundhogg/get-flow', [ 'flow_id' => $ids['flow'] ] ) );
	}

	public function test_edit_flow_deletes_the_referencing_step_first() {
		$ids = $this->task_flow();

		$out = $this->execute( 'groundhogg/edit-flow', [
			'flow_id'    => $ids['flow'],
			'operations' => [
				[ 'op' => 'delete', 'step' => $ids['called'] ],
				[ 'op' => 'delete', 'step' => $ids['call'] ],
			],
		] );

		$this->assertNotWPError( $out );
		$this->assertEquals( [ 'wait' ], wp_list_pluck( $out['flow']['steps'], 'title' ) );
	}
}
