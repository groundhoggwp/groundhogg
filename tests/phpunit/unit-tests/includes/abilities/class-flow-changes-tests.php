<?php

use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Step;
use function Groundhogg\event_queue_db;
use function Groundhogg\get_db;

/**
 * groundhogg/publish-flow-changes, groundhogg/discard-flow-changes, and how activate-flow and deactivate-flow
 * handle staged changes and deleted steps
 */
class Flow_Changes_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();

		if ( ! function_exists( 'wp_get_ability' ) || ! wp_get_ability( 'groundhogg/publish-flow-changes' ) ) {
			$this->markTestSkipped( 'The flow abilities are not registered.' );
		}

		$this->factory()->truncate();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	protected function execute( string $ability, array $input ) {
		$out = wp_get_ability( $ability )->execute( $input );

		if ( is_wp_error( $out ) ) {
			return $out;
		}

		return json_decode( wp_json_encode( $out ), true );
	}

	/**
	 * A flow with delays a, b, and c
	 *
	 * @return array title => step id, and 'flow' => flow id
	 */
	protected function create_flow( $active = true ) {

		$out = $this->execute( 'groundhogg/create-flow', [
			'title' => 'test flow',
			'steps' => [
				[ 'type' => 'delay_timer', 'title' => 'a' ],
				[ 'type' => 'delay_timer', 'title' => 'b' ],
				[ 'type' => 'delay_timer', 'title' => 'c' ],
			],
		] );

		$this->assertNotWPError( $out );

		if ( $active ) {
			$this->assertNotWPError( $this->execute( 'groundhogg/activate-flow', [ 'flow_id' => $out['id'] ] ) );
		}

		return array_merge( [ 'flow' => $out['id'] ], wp_list_pluck( $out['steps'], 'id', 'title' ) );
	}

	protected function edit( int $flow_id, array $operations ) {
		$out = $this->execute( 'groundhogg/edit-flow', [ 'flow_id' => $flow_id, 'operations' => $operations ] );
		$this->assertNotWPError( $out );

		return $out;
	}

	protected function titles( int $flow_id, $view = 'draft' ) {
		return wp_list_pluck( $this->execute( 'groundhogg/get-flow', [ 'flow_id' => $flow_id, 'view' => $view ] )['steps'], 'title' );
	}

	protected function wait_at( int $flow_id, int $step_id, $status = Event::WAITING ) {
		return $this->factory()->event_queue->create( [
			'funnel_id'  => $flow_id,
			'step_id'    => $step_id,
			'contact_id' => $this->factory()->contacts->create(),
			'event_type' => Event::FUNNEL,
			'status'     => $status,
		] );
	}

	protected function queued_at( int $step_id ) {
		return event_queue_db()->count( [ 'step_id' => $step_id, 'event_type' => Event::FUNNEL ] );
	}

	/**
	 * Staged edits: rename a, add "new" after c, delete b
	 */
	protected function stage_changes( array $ids ) {
		$this->edit( $ids['flow'], [
			[ 'op' => 'update', 'step' => $ids['a'], 'title' => 'renamed' ],
			[ 'op' => 'add', 'at' => [ 'after' => $ids['c'] ], 'steps' => [ [ 'type' => 'delay_timer', 'title' => 'new' ] ] ],
			[ 'op' => 'delete', 'step' => $ids['b'] ],
		] );
	}

	public function test_publish_makes_the_draft_live() {
		$ids = $this->create_flow();
		$this->stage_changes( $ids );

		$this->assertEquals( [ 'a', 'b', 'c' ], $this->titles( $ids['flow'], 'live' ) );

		$out = $this->execute( 'groundhogg/publish-flow-changes', [ 'flow_id' => $ids['flow'] ] );

		$this->assertNotWPError( $out );
		$this->assertTrue( $out['had_changes'] );
		$this->assertEquals( [ 'renamed', 'c', 'new' ], wp_list_pluck( $out['flow']['steps'], 'title' ) );
		$this->assertFalse( $out['flow']['has_unpublished_changes'] );
		$this->assertEquals( $this->titles( $ids['flow'] ), $this->titles( $ids['flow'], 'live' ) );

		// the deleted step without history is gone
		$this->assertFalse( ( new Step( $ids['b'] ) )->exists() );

		$again = $this->execute( 'groundhogg/publish-flow-changes', [ 'flow_id' => $ids['flow'] ] );
		$this->assertFalse( $again['had_changes'] );
	}

	public function test_publish_cancels_contacts_at_deleted_steps_by_default() {
		$ids = $this->create_flow();
		$this->wait_at( $ids['flow'], $ids['b'] );
		$this->stage_changes( $ids );

		$out = $this->execute( 'groundhogg/publish-flow-changes', [ 'flow_id' => $ids['flow'] ] );

		$this->assertNotWPError( $out );
		$this->assertEquals( [ [ 'id' => $ids['b'], 'title' => 'b', 'waiting_contacts' => 1, 'action' => 'cancel' ] ], $out['deleted_steps'] );
		$this->assertEquals( 0, $this->queued_at( $ids['b'] ) );
		$this->assertEquals( 1, get_db( 'events' )->count( [ 'step_id' => $ids['b'], 'error_code' => 'step_deleted' ] ) );

		// it has history now, so it's archived instead
		$this->assertEquals( 'archived', ( new Step( $ids['b'] ) )->step_status );
	}

	public function test_publish_can_move_contacts_at_deleted_steps() {
		$ids = $this->create_flow();
		$this->wait_at( $ids['flow'], $ids['b'] );
		$this->stage_changes( $ids );

		$out = $this->execute( 'groundhogg/publish-flow-changes', [
			'flow_id'       => $ids['flow'],
			'deleted_steps' => [ [ 'step' => $ids['b'], 'action' => 'move', 'to' => $ids['c'] ] ],
		] );

		$this->assertNotWPError( $out );
		$this->assertEquals( $ids['c'], $out['deleted_steps'][0]['moved_to'] );
		$this->assertEquals( 0, $this->queued_at( $ids['b'] ) );
		$this->assertEquals( 1, $this->queued_at( $ids['c'] ) );
	}

	public function test_publish_refuses_bad_choices_and_publishes_nothing() {
		$ids = $this->create_flow();
		$this->stage_changes( $ids );

		foreach ( [
			[ 'step' => $ids['c'], 'action' => 'cancel' ], // not deleted
			[ 'step' => $ids['b'], 'action' => 'move', 'to' => $ids['b'] ], // deleted
			[ 'step' => $ids['b'], 'action' => 'move', 'to' => 999999 ],
		] as $choice ) {
			$out = $this->execute( 'groundhogg/publish-flow-changes', [ 'flow_id' => $ids['flow'], 'deleted_steps' => [ $choice ] ] );
			$this->assertWPError( $out, wp_json_encode( $choice ) );
		}

		$this->assertEquals( [ 'a', 'b', 'c' ], $this->titles( $ids['flow'], 'live' ) );
	}

	public function test_publish_checks_the_revision() {
		$ids      = $this->create_flow();
		$revision = $this->execute( 'groundhogg/get-flow', [ 'flow_id' => $ids['flow'] ] )['revision'];
		$this->stage_changes( $ids );

		$out = $this->execute( 'groundhogg/publish-flow-changes', [ 'flow_id' => $ids['flow'], 'expected_revision' => $revision ] );

		$this->assertWPError( $out );
		$this->assertEquals( 'groundhogg_flow_changed', $out->get_error_code() );
	}

	public function test_publish_and_discard_are_only_for_active_flows() {
		$ids = $this->create_flow( false );

		foreach ( [ 'groundhogg/publish-flow-changes', 'groundhogg/discard-flow-changes' ] as $ability ) {
			$out = $this->execute( $ability, [ 'flow_id' => $ids['flow'] ] );
			$this->assertWPError( $out, $ability );
			$this->assertEquals( 'groundhogg_flow_not_active', $out->get_error_code() );
		}
	}

	public function test_discard_goes_back_to_the_live_flow() {
		$ids = $this->create_flow();
		$this->stage_changes( $ids );

		$out = $this->execute( 'groundhogg/discard-flow-changes', [ 'flow_id' => $ids['flow'] ] );

		$this->assertNotWPError( $out );
		$this->assertTrue( $out['had_changes'] );
		$this->assertEquals( [ 'a', 'b', 'c' ], wp_list_pluck( $out['flow']['steps'], 'title' ) );
		$this->assertFalse( $out['flow']['has_unpublished_changes'] );
		$this->assertEquals( 3, get_db( 'steps' )->count( [ 'funnel_id' => $ids['flow'] ] ) );
	}

	public function test_activate_removes_steps_deleted_while_inactive() {
		$ids = $this->create_flow( false );
		$this->wait_at( $ids['flow'], $ids['b'], Event::PAUSED );
		$this->wait_at( $ids['flow'], $ids['c'], Event::PAUSED );

		$this->edit( $ids['flow'], [
			[ 'op' => 'delete', 'step' => $ids['b'] ],
			[ 'op' => 'delete', 'step' => $ids['c'] ],
		] );

		$out = $this->execute( 'groundhogg/activate-flow', [
			'flow_id'       => $ids['flow'],
			'deleted_steps' => [ [ 'step' => $ids['b'], 'action' => 'move', 'to' => $ids['a'] ] ],
		] );

		$this->assertNotWPError( $out );
		$this->assertEquals( 'active', $out['status'] );
		$this->assertEquals( [ 'move', 'cancel' ], wp_list_pluck( $out['deleted_steps'], 'action' ) );

		// moved and unpaused
		$this->assertEquals( 1, event_queue_db()->count( [ 'step_id' => $ids['a'], 'status' => Event::WAITING ] ) );
		$this->assertEquals( 0, $this->queued_at( $ids['b'] ) );
		$this->assertEquals( 0, $this->queued_at( $ids['c'] ) );

		$this->assertEquals( [ 'a' ], $this->titles( $ids['flow'], 'live' ) );
	}

	public function test_activate_doesnt_count_deleted_steps() {
		$ids = $this->create_flow( false );

		$this->edit( $ids['flow'], [
			[ 'op' => 'delete', 'step' => $ids['a'] ],
			[ 'op' => 'delete', 'step' => $ids['b'] ],
			[ 'op' => 'delete', 'step' => $ids['c'] ],
		] );

		$out = $this->execute( 'groundhogg/activate-flow', [ 'flow_id' => $ids['flow'] ] );

		$this->assertWPError( $out );
		$this->assertEquals( 'groundhogg_flow_no_steps', $out->get_error_code() );
	}

	public function test_deactivate_asks_what_to_do_with_unpublished_changes() {
		$ids = $this->create_flow();
		$this->stage_changes( $ids );

		$out = $this->execute( 'groundhogg/deactivate-flow', [ 'flow_id' => $ids['flow'] ] );

		$this->assertWPError( $out );
		$this->assertEquals( 'groundhogg_flow_has_unpublished_changes', $out->get_error_code() );
		$this->assertTrue( ( new Funnel( $ids['flow'] ) )->is_active() );
	}

	public function test_deactivate_can_discard_unpublished_changes() {
		$ids = $this->create_flow();
		$this->stage_changes( $ids );

		$out = $this->execute( 'groundhogg/deactivate-flow', [ 'flow_id' => $ids['flow'], 'pending_changes' => 'discard' ] );

		$this->assertNotWPError( $out );
		$this->assertEquals( 'inactive', $out['status'] );
		$this->assertEquals( [ 'a', 'b', 'c' ], $this->titles( $ids['flow'] ) );
	}

	public function test_deactivate_can_publish_unpublished_changes() {
		$ids = $this->create_flow();
		$this->wait_at( $ids['flow'], $ids['b'] );
		$this->stage_changes( $ids );

		$out = $this->execute( 'groundhogg/deactivate-flow', [
			'flow_id'         => $ids['flow'],
			'pending_changes' => 'publish',
			'deleted_steps'   => [ [ 'step' => $ids['b'], 'action' => 'move', 'to' => $ids['c'] ] ],
		] );

		$this->assertNotWPError( $out );
		$this->assertEquals( 'inactive', $out['status'] );
		$this->assertEquals( 'move', $out['deleted_steps'][0]['action'] );
		$this->assertEquals( [ 'renamed', 'c', 'new' ], $this->titles( $ids['flow'] ) );

		// paused at the step they were moved to, since the flow is inactive now
		$this->assertEquals( 1, event_queue_db()->count( [ 'step_id' => $ids['c'], 'status' => Event::PAUSED ] ) );
	}

	public function test_deactivate_without_changes() {
		$ids = $this->create_flow();

		$out = $this->execute( 'groundhogg/deactivate-flow', [ 'flow_id' => $ids['flow'] ] );

		$this->assertNotWPError( $out );
		$this->assertEquals( 'inactive', $out['status'] );
		$this->assertEmpty( $out['deleted_steps'] );
	}
}
