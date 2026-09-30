<?php

use Groundhogg\Contact;
use Groundhogg\Funnel;
use function Groundhogg\event_queue_db;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;
use function Groundhogg\parse_tag_list;

/**
 * groundhogg/simulate-flow and groundhogg/live-simulate-flow, and which version of an active flow they run:
 * the published flow (live, the default) or the flow with its unpublished changes (draft)
 */
class Simulate_Flow_Tests extends GH_UnitTestCase {

	/**
	 * @var int[] tag name => tag ID
	 */
	protected $tags = [];

	public function setUp(): void {
		parent::setUp();

		if ( ! function_exists( 'wp_get_ability' ) || ! wp_get_ability( 'groundhogg/simulate-flow' ) ) {
			$this->markTestSkipped( 'The simulate flow abilities are not registered.' );
		}

		$this->factory()->truncate();
		$this->tags = [];

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	protected function execute( string $ability, array $input ) {
		$out = wp_get_ability( $ability )->execute( $input );

		if ( is_wp_error( $out ) ) {
			return $out;
		}

		return json_decode( wp_json_encode( $out ), true );
	}

	protected function tag( string $name ): int {
		if ( ! isset( $this->tags[ $name ] ) ) {
			$this->tags[ $name ] = parse_tag_list( [ $name ], 'ID', true )[0];
		}

		return $this->tags[ $name ];
	}

	/**
	 * An action that applies a tag named after it, so what ran can be seen on the contact too
	 */
	protected function action( string $title ): array {
		return [ 'type' => 'apply_tag', 'title' => $title, 'settings' => [ 'tags' => [ $this->tag( $title ) ] ] ];
	}

	/**
	 * An active flow of actions "one" and "two", with unpublished changes that
	 * - edit "one" to apply the tag "changed" instead, and retitle it "one edited"
	 * - add "added" after "two"
	 * - delete "two"
	 *
	 * So live is one, two, and draft is one edited, added.
	 *
	 * @return array step title => step ID, and 'flow' => the flow ID
	 */
	protected function create_flow_with_staged_changes(): array {

		$out = $this->execute( 'groundhogg/create-flow', [
			'title' => 'simulate test',
			'steps' => [ $this->action( 'one' ), $this->action( 'two' ) ],
		] );

		$this->assertNotWPError( $out );

		$ids = array_merge( [ 'flow' => $out['id'] ], wp_list_pluck( $out['steps'], 'id', 'title' ) );

		$this->assertNotWPError( $this->execute( 'groundhogg/activate-flow', [ 'flow_id' => $ids['flow'] ] ) );

		$edit = $this->execute( 'groundhogg/edit-flow', [
			'flow_id'    => $ids['flow'],
			'operations' => [
				[ 'op' => 'update', 'step' => $ids['one'], 'title' => 'one edited', 'settings' => [ 'tags' => [ $this->tag( 'changed' ) ] ] ],
				[ 'op' => 'add', 'at' => [ 'after' => $ids['two'] ], 'steps' => [ $this->action( 'added' ) ] ],
				[ 'op' => 'delete', 'step' => $ids['two'] ],
			],
		] );

		$this->assertNotWPError( $edit );

		// the id of the step added in the draft
		$draft        = $this->execute( 'groundhogg/get-flow', [ 'flow_id' => $ids['flow'], 'view' => 'draft' ] );
		$ids['added'] = wp_list_pluck( $draft['steps'], 'id', 'title' )['added'];

		return $ids;
	}

	protected function contact(): Contact {
		return get_contactdata( $this->factory()->contacts->create() );
	}

	protected function trace_ids( array $out ): array {
		return wp_list_pluck( $out['trace'], 'step_id' );
	}

	/**
	 * The tags (by name) a contact has of the ones these tests use
	 */
	protected function tags_of( Contact $contact ): array {
		global $wpdb;

		// straight from the table, since a contact loaded earlier keeps the tags it had then
		$applied = array_map( 'absint', $wpdb->get_col( $wpdb->prepare(
			"SELECT tag_id FROM {$wpdb->prefix}gh_tag_relationships WHERE contact_id = %d", $contact->get_id()
		) ) );

		return array_keys( array_filter( $this->tags, function ( $id ) use ( $applied ) {
			return in_array( $id, $applied, true );
		} ) );
	}

	public function test_simulate_traces_the_live_flow_by_default() {
		$ids = $this->create_flow_with_staged_changes();

		$out = $this->execute( 'groundhogg/simulate-flow', [
			'funnel_id'  => $ids['flow'],
			'contact_id' => $this->contact()->get_id(),
			'step_id'    => $ids['one'],
		] );

		$this->assertNotWPError( $out );
		$this->assertEquals( 'live', $out['view'] );
		$this->assertEquals( [ $ids['one'], $ids['two'] ], $this->trace_ids( $out ) );
		$this->assertEquals( [ 'one', 'two' ], wp_list_pluck( $out['trace'], 'title' ) );
		$this->assertEquals( 'completed', $out['stopped_reason'] );
	}

	public function test_simulate_can_trace_the_draft() {
		$ids = $this->create_flow_with_staged_changes();

		$out = $this->execute( 'groundhogg/simulate-flow', [
			'funnel_id'  => $ids['flow'],
			'contact_id' => $this->contact()->get_id(),
			'step_id'    => $ids['one'],
			'view'       => 'draft',
		] );

		$this->assertNotWPError( $out );
		$this->assertEquals( 'draft', $out['view'] );
		$this->assertEquals( [ $ids['one'], $ids['added'] ], $this->trace_ids( $out ) );
		$this->assertEquals( [ 'one edited', 'added' ], wp_list_pluck( $out['trace'], 'title' ) );
	}

	public function test_simulate_draft_leaves_the_flow_alone() {
		$ids     = $this->create_flow_with_staged_changes();
		$contact = $this->contact();

		$this->execute( 'groundhogg/simulate-flow', [
			'funnel_id'  => $ids['flow'],
			'contact_id' => $contact->get_id(),
			'step_id'    => $ids['one'],
			'view'       => 'draft',
		] );

		// dry, so no events and no tags
		$this->assertEquals( 0, event_queue_db()->count( [ 'contact_id' => $contact->get_id() ] ) );
		$this->assertEquals( 0, get_db( 'events' )->count( [ 'contact_id' => $contact->get_id() ] ) );
		$this->assertEmpty( $this->tags_of( $contact ) );

		// the changes are still staged, and editing mode was switched back off
		$flow = new Funnel( $ids['flow'] );
		$this->assertTrue( $flow->has_changes() );
		$this->assertFalse( $flow->is_editing() );
		$this->assertEquals( [ 'one', 'two' ], wp_list_pluck( $flow->get_steps(), 'step_title' ) );
	}

	public function test_a_step_must_be_in_the_version_being_traced() {

		$ids = $this->create_flow_with_staged_changes();

		$cases = [
			'added in the draft, traced live'    => [ $ids['added'], 'live' ],
			'deleted in the draft, traced draft' => [ $ids['two'], 'draft' ],
		];

		foreach ( $cases as $label => [ $step, $view ] ) {

			$out = $this->execute( 'groundhogg/simulate-flow', [
				'funnel_id'  => $ids['flow'],
				'contact_id' => $this->contact()->get_id(),
				'step_id'    => $step,
				'view'       => $view,
			] );

			$this->assertWPError( $out, $label );
			$this->assertEquals( 'groundhogg_step_not_in_view', $out->get_error_code(), $label );
		}

		// and each is fine in the version it does exist in
		$added = $this->execute( 'groundhogg/simulate-flow', [
			'funnel_id'  => $ids['flow'],
			'contact_id' => $this->contact()->get_id(),
			'step_id'    => $ids['added'],
			'view'       => 'draft',
		] );

		$this->assertNotWPError( $added );
		$this->assertEquals( [ $ids['added'] ], $this->trace_ids( $added ) );

		$two = $this->execute( 'groundhogg/simulate-flow', [
			'funnel_id'  => $ids['flow'],
			'contact_id' => $this->contact()->get_id(),
			'step_id'    => $ids['two'],
		] );

		$this->assertNotWPError( $two );
		$this->assertEquals( [ $ids['two'] ], $this->trace_ids( $two ) );
	}

	public function test_view_only_accepts_live_or_draft() {
		$ids = $this->create_flow_with_staged_changes();

		$out = $this->execute( 'groundhogg/simulate-flow', [
			'funnel_id'  => $ids['flow'],
			'contact_id' => $this->contact()->get_id(),
			'step_id'    => $ids['one'],
			'view'       => 'staged',
		] );

		$this->assertWPError( $out );
	}

	public function test_a_flow_without_changes_traces_the_same_either_way() {

		$out = $this->execute( 'groundhogg/create-flow', [
			'title' => 'no changes',
			'steps' => [ $this->action( 'one' ), $this->action( 'two' ) ],
		] );

		$this->assertNotWPError( $this->execute( 'groundhogg/activate-flow', [ 'flow_id' => $out['id'] ] ) );

		$input = [ 'funnel_id' => $out['id'], 'contact_id' => $this->contact()->get_id(), 'step_id' => $out['steps'][0]['id'] ];

		$live  = $this->execute( 'groundhogg/simulate-flow', $input + [ 'view' => 'live' ] );
		$draft = $this->execute( 'groundhogg/simulate-flow', $input + [ 'view' => 'draft' ] );

		$this->assertEquals( $this->trace_ids( $live ), $this->trace_ids( $draft ) );
		$this->assertCount( 2, $live['trace'] );
	}

	public function test_live_simulate_runs_the_live_flow_by_default() {
		$ids     = $this->create_flow_with_staged_changes();
		$contact = $this->contact();

		$out = $this->execute( 'groundhogg/live-simulate-flow', [
			'funnel_id'  => $ids['flow'],
			'contact_id' => $contact->get_id(),
			'step_id'    => $ids['one'],
		] );

		$this->assertNotWPError( $out );
		$this->assertEquals( 'live', $out['view'] );
		$this->assertEquals( [ $ids['one'], $ids['two'] ], $this->trace_ids( $out ) );

		// the published steps ran for real, and none of the unpublished changes did
		$this->assertEqualSets( [ 'one', 'two' ], $this->tags_of( $contact ) );
		$this->assertEquals( 2, get_db( 'events' )->count( [ 'contact_id' => $contact->get_id(), 'status' => 'complete' ] ) );
	}

	public function test_live_simulate_can_run_the_draft() {
		$ids     = $this->create_flow_with_staged_changes();
		$contact = $this->contact();

		$out = $this->execute( 'groundhogg/live-simulate-flow', [
			'funnel_id'  => $ids['flow'],
			'contact_id' => $contact->get_id(),
			'step_id'    => $ids['one'],
			'view'       => 'draft',
		] );

		$this->assertNotWPError( $out );
		$this->assertEquals( 'draft', $out['view'] );
		$this->assertEquals( [ $ids['one'], $ids['added'] ], $this->trace_ids( $out ) );

		// the edited step applied its staged tag instead of its published one, the new step ran,
		// and the step deleted in the draft didn't
		$this->assertEqualSets( [ 'changed', 'added' ], $this->tags_of( $contact ) );

		// the events were recorded against the steps that ran
		$events = get_db( 'events' )->query( [ 'contact_id' => $contact->get_id(), 'orderby' => 'ID', 'order' => 'ASC' ] );
		$this->assertEquals( [ $ids['one'], $ids['added'] ], array_map( 'absint', wp_list_pluck( $events, 'step_id' ) ) );

		// and publishing isn't implied
		$flow = new Funnel( $ids['flow'] );
		$this->assertTrue( $flow->has_changes() );
		$this->assertEquals( [ 'one', 'two' ], wp_list_pluck( $flow->get_steps(), 'step_title' ) );
	}

	public function test_live_simulate_keeps_its_edit_capability() {
		$ids = $this->create_flow_with_staged_changes();

		// view_funnels is enough to dry run the draft, but not to really run it
		$user = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		get_userdata( $user )->add_cap( 'view_funnels' );
		wp_set_current_user( $user );

		$input = [
			'funnel_id'  => $ids['flow'],
			'contact_id' => $this->contact()->get_id(),
			'step_id'    => $ids['one'],
			'view'       => 'draft',
		];

		$this->assertNotWPError( $this->execute( 'groundhogg/simulate-flow', $input ) );
		$this->assertWPError( $this->execute( 'groundhogg/live-simulate-flow', $input ) );
	}
}
