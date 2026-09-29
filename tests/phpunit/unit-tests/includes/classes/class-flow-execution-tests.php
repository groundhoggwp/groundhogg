<?php

use Groundhogg\Contact;
use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Step;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;
use function Groundhogg\parse_tag_list;
use function Groundhogg\process_events;

/**
 * Contacts going through flows built and changed by the flow editor's operations and the flow abilities,
 * which write the step order, branches, and levels the flow runs by
 */
class Flow_Execution_Tests extends GH_UnitTestCase {

	/**
	 * @var int[] tag name => tag ID
	 */
	protected $tags = [];

	public function setUp(): void {
		parent::setUp();

		if ( ! function_exists( 'wp_get_ability' ) || ! wp_get_ability( 'groundhogg/edit-flow' ) ) {
			$this->markTestSkipped( 'The groundhogg/edit-flow ability is not registered.' );
		}

		$this->factory()->truncate();
		$this->tags = [];

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	protected function execute( string $ability, array $input ) {
		$out = wp_get_ability( $ability )->execute( $input );

		$this->assertNotWPError( $out, $ability );

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
	 * An if/else that sends contacts with the tag down "yes"
	 */
	protected function if_has_tag( string $title, string $tag, array $yes = [], array $no = [] ): array {
		return [
			'type'     => 'if_else',
			'title'    => $title,
			'settings' => [ 'include_condition' => [ 'tags_include' => [ $this->tag( $tag ) ] ] ],
			'branches' => [ 'yes' => $yes, 'no' => $no ],
		];
	}

	/**
	 * Create a flow with groundhogg/create-flow
	 *
	 * @return array step title => step ID, and 'flow' => the flow ID
	 */
	protected function create_flow( array $steps ): array {

		$out = $this->execute( 'groundhogg/create-flow', [ 'title' => 'execution test', 'steps' => $steps ] );

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

	protected function edit( int $flow_id, array $operations ): array {
		return $this->execute( 'groundhogg/edit-flow', [ 'flow_id' => $flow_id, 'operations' => $operations ] );
	}

	protected function activate( int $flow_id ) {
		$this->execute( 'groundhogg/activate-flow', [ 'flow_id' => $flow_id ] );
	}

	protected function contact( array $tags = [] ): Contact {
		$contact = get_contactdata( $this->factory()->contacts->create() );

		if ( $tags ) {
			$contact->add_tag( array_map( [ $this, 'tag' ], $tags ) );
		}

		return $contact;
	}

	/**
	 * Run the contact's events that are due, see process_events()
	 */
	protected function process( Contact $contact ) {
		$result = process_events( [ $contact ] );

		$this->assertTrue( $result, is_array( $result ) ? implode( ' ', array_map( function ( $error ) {
			return $error->get_error_message();
		}, $result ) ) : '' );
	}

	/**
	 * Start the contact at the flow's first step, and run them through it
	 */
	protected function run_flow( int $flow_id, Contact $contact ) {
		$first = ( new Funnel( $flow_id ) )->get_steps()[0];

		$first->enqueue( $contact );

		$this->process( $contact );
	}

	/**
	 * The events that ran for the contact in the flow, in the order they ran
	 * Skipped ones count, like applying a tag the contact already has
	 */
	protected function events( int $flow_id, Contact $contact ): array {
		return get_db( 'events' )->query( [
			'contact_id' => $contact->get_id(),
			'funnel_id'  => $flow_id,
			'status'     => [ Event::COMPLETE, Event::SKIPPED ],
			'orderby'    => 'ID',
			'order'      => 'ASC',
		] );
	}

	/**
	 * The titles of the steps that ran for the contact in the flow, in the order they ran
	 */
	protected function path( int $flow_id, Contact $contact ): array {
		return array_map( function ( $event ) {
			return ( new Step( absint( $event->step_id ) ) )->get_title();
		}, $this->events( $flow_id, $contact ) );
	}

	public function test_contacts_follow_an_if_else_in_a_flow_from_create_flow() {

		$ids = $this->create_flow( [
			$this->action( 'a' ),
			$this->if_has_tag( 'if vip', 'vip', [ $this->action( 'yes' ) ], [ $this->action( 'no' ) ] ),
			$this->action( 'z' ),
		] );

		$this->activate( $ids['flow'] );

		$vip   = $this->contact( [ 'vip' ] );
		$other = $this->contact();

		$this->run_flow( $ids['flow'], $vip );
		$this->run_flow( $ids['flow'], $other );

		$this->assertEquals( [ 'a', 'yes', 'z' ], $this->path( $ids['flow'], $vip ) );
		$this->assertEquals( [ 'a', 'no', 'z' ], $this->path( $ids['flow'], $other ) );

		// the queue ran with its own copy of the contact
		$vip = new Contact( $vip->get_id() );

		$this->assertTrue( $vip->has_tag( $this->tag( 'yes' ) ) );
		$this->assertFalse( $vip->has_tag( $this->tag( 'no' ) ) );
	}

	public function test_contacts_run_steps_in_the_order_edit_flow_moves_them_to() {

		$ids = $this->create_flow( [ $this->action( 'a' ), $this->action( 'b' ), $this->action( 'c' ) ] );

		$this->edit( $ids['flow'], [
			[ 'op' => 'move', 'step' => $ids['c'], 'at' => [ 'branch' => 'main', 'position' => 'start' ] ],
			[ 'op' => 'add', 'at' => [ 'after' => $ids['a'] ], 'steps' => [ $this->action( 'new' ) ] ],
		] );

		$this->activate( $ids['flow'] );

		$contact = $this->contact();
		$this->run_flow( $ids['flow'], $contact );

		$this->assertEquals( [ 'c', 'a', 'new', 'b' ], $this->path( $ids['flow'], $contact ) );
	}

	public function test_contacts_run_steps_added_to_and_moved_into_branches() {

		$ids = $this->create_flow( [
			$this->if_has_tag( 'if vip', 'vip', [ $this->action( 'yes' ) ], [ $this->action( 'no' ) ] ),
			$this->action( 'z' ),
		] );

		$this->edit( $ids['flow'], [
			// the last step goes to the end of the "no" branch
			[ 'op' => 'move', 'step' => $ids['z'], 'at' => [ 'branch_of' => $ids['if vip'], 'branch' => 'no', 'position' => 'end' ] ],
			[ 'op' => 'add', 'at' => [ 'branch_of' => $ids['if vip'], 'branch' => 'yes', 'position' => 'start' ], 'steps' => [ $this->action( 'yes first' ) ] ],
			[ 'op' => 'add', 'at' => [ 'branch' => 'main', 'position' => 'end' ], 'steps' => [ $this->action( 'after' ) ] ],
		] );

		$this->activate( $ids['flow'] );

		$vip   = $this->contact( [ 'vip' ] );
		$other = $this->contact();

		$this->run_flow( $ids['flow'], $vip );
		$this->run_flow( $ids['flow'], $other );

		$this->assertEquals( [ 'yes first', 'yes', 'after' ], $this->path( $ids['flow'], $vip ) );
		$this->assertEquals( [ 'no', 'z', 'after' ], $this->path( $ids['flow'], $other ) );
	}

	public function test_contacts_run_a_duplicated_if_else_and_its_branches() {

		$ids = $this->create_flow( [
			$this->if_has_tag( 'if vip', 'vip', [ $this->action( 'yes' ) ], [ $this->action( 'no' ) ] ),
			$this->action( 'z' ),
		] );

		$out = $this->edit( $ids['flow'], [
			[ 'op' => 'duplicate', 'step' => $ids['if vip'] ],
		] );

		$copy_id = $out['added'][0][0]['id'] ?? null;
		$this->assertNotEmpty( $copy_id );

		$this->activate( $ids['flow'] );

		$vip = $this->contact( [ 'vip' ] );
		$this->run_flow( $ids['flow'], $vip );

		$events = $this->events( $ids['flow'], $vip );

		// the original's yes, then the copy's yes, which is a different step (skipped, the contact has its tag already)
		$this->assertEquals( [ 'yes', 'yes', 'z' ], $this->path( $ids['flow'], $vip ) );
		$this->assertEquals( $ids['yes'], absint( $events[0]->step_id ) );
		$this->assertEquals( "$copy_id-yes", ( new Step( absint( $events[1]->step_id ) ) )->branch );
	}

	public function test_staged_changes_only_run_once_published() {

		$ids = $this->create_flow( [ $this->action( 'a' ), $this->action( 'b' ) ] );

		$this->activate( $ids['flow'] );

		$this->edit( $ids['flow'], [
			[ 'op' => 'add', 'at' => [ 'after' => $ids['a'] ], 'steps' => [ $this->action( 'staged' ) ] ],
			[ 'op' => 'delete', 'step' => $ids['b'] ],
		] );

		$before = $this->contact();
		$this->run_flow( $ids['flow'], $before );

		$this->assertEquals( [ 'a', 'b' ], $this->path( $ids['flow'], $before ) );

		$this->execute( 'groundhogg/publish-flow-changes', [ 'flow_id' => $ids['flow'] ] );

		$after = $this->contact();
		$this->run_flow( $ids['flow'], $after );

		$this->assertEquals( [ 'a', 'staged' ], $this->path( $ids['flow'], $after ) );
	}

	public function test_contacts_moved_from_a_deleted_step_continue_from_where_they_were_moved() {

		$ids = $this->create_flow( [
			$this->action( 'a' ),
			[ 'type' => 'delay_timer', 'title' => 'wait', 'settings' => [ 'delay_amount' => 1, 'delay_type' => 'days' ] ],
			$this->action( 'b' ),
			$this->action( 'c' ),
		] );

		$this->activate( $ids['flow'] );

		$contact = $this->contact();
		$this->run_flow( $ids['flow'], $contact );

		// waiting at the delay
		$this->assertEquals( [ 'a' ], $this->path( $ids['flow'], $contact ) );

		$this->edit( $ids['flow'], [ [ 'op' => 'delete', 'step' => $ids['wait'] ] ] );

		$this->execute( 'groundhogg/publish-flow-changes', [
			'flow_id'       => $ids['flow'],
			'deleted_steps' => [ [ 'step' => $ids['wait'], 'action' => 'move', 'to' => $ids['c'] ] ],
		] );

		// the moved event runs when the delay would have
		get_db( 'event_queue' )->update( [ 'contact_id' => $contact->get_id() ], [ 'time' => time() - 1 ] );

		$this->process( $contact );

		$this->assertEquals( [ 'a', 'c' ], $this->path( $ids['flow'], $contact ) );
	}

	public function test_contacts_run_a_flow_started_by_a_trigger() {

		$ids = $this->create_flow( [
			[ 'type' => 'tag_applied', 'title' => 'trigger', 'settings' => [ 'tags' => [ $this->tag( 'start' ) ] ] ],
			$this->action( 'a' ),
			$this->if_has_tag( 'if vip', 'vip', [ $this->action( 'yes' ) ], [ $this->action( 'no' ) ] ),
		] );

		// added in front of the action by the operations
		$this->edit( $ids['flow'], [
			[ 'op' => 'add', 'at' => [ 'before' => $ids['a'] ], 'steps' => [ $this->action( 'first' ) ] ],
		] );

		$this->activate( $ids['flow'] );

		$contact = $this->contact( [ 'vip' ] );
		$contact->add_tag( $this->tag( 'start' ) );

		$this->process( $contact );

		$this->assertEquals( [ 'trigger', 'first', 'a', 'yes' ], $this->path( $ids['flow'], $contact ) );
	}
}
