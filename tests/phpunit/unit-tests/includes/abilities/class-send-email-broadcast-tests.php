<?php

use Groundhogg\Broadcast;
use Groundhogg\Contact;
use Groundhogg\Email;
use Groundhogg\Event;
use Groundhogg\Preferences;
use function Groundhogg\event_queue_db;
use function Groundhogg\get_db;

/**
 * groundhogg/send-email-broadcast: the exclusion params (tags_exclude, exclude_segment) must narrow the
 * audience exactly as groundhogg/search-contacts does, for a "fixed" segment (contacts locked in at
 * schedule time) and a "dynamic" one (filters stored and re-evaluated when the events are queued).
 *
 * exclude_segment is exercised with `meta` (a custom field), which stands in for the add-on
 * sub-filters (edd, deals, ...) that core's tests can't load; they all arrive as Filters-DSL
 * conditions through the same Segment_Schema code path.
 */
class Send_Email_Broadcast_Tests extends GH_UnitTestCase {

	const ABILITY = 'groundhogg/send-email-broadcast';

	/**
	 * @var int[] contact IDs keyed by label
	 */
	protected $contacts = [];

	/**
	 * @var int[] tag IDs keyed by name
	 */
	protected $tags = [];

	/**
	 * @var Email
	 */
	protected $email;

	public function setUp(): void {
		parent::setUp();

		if ( ! function_exists( 'wp_get_ability' ) || ! wp_get_ability( self::ABILITY ) ) {
			$this->markTestSkipped( 'The ' . self::ABILITY . ' ability is not registered.' );
		}

		$this->factory()->truncate();
		$this->contacts = [];
		$this->tags     = [];

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->email = new Email( [
			'title'   => 'Broadcast test',
			'subject' => 'Broadcast test',
			'content' => '<p>content</p>',
			'status'  => 'ready',
		] );

		$this->assertTrue( $this->email->exists() );

		foreach ( [ 'target', 'unsubscribed-ish', 'vip', 'pro' ] as $name ) {
			$this->tags[ $name ] = get_db( 'tags' )->add( [ 'tag_name' => $name, 'tag_slug' => $name ] );
		}

		/*
		 * target             - tagged "target", the audience
		 * keep               - tagged "target", the only one that should be sent to
		 * tag_excluded       - tagged "target" and "vip"
		 * segment_excluded   - tagged "target", custom field plan = pro
		 * both_excluded      - tagged "target" and "vip", plan = pro
		 * outside            - not tagged "target" at all
		 */
		$this->make_contact( 'keep', [ 'target' ] );
		$this->make_contact( 'tag_excluded', [ 'target', 'vip' ] );
		$this->make_contact( 'segment_excluded', [ 'target' ], 'pro' );
		$this->make_contact( 'both_excluded', [ 'target', 'vip' ], 'pro' );
		$this->make_contact( 'outside', [], '' );
	}

	protected function make_contact( string $label, array $tags, string $plan = 'free' ) {

		$id = $this->factory()->contacts->create( [ 'optin_status' => Preferences::CONFIRMED ] );

		$contact = new Contact( $id );
		$contact->apply_tag( array_map( fn( $name ) => $this->tags[ $name ], $tags ) );

		if ( $plan !== '' ) {
			$contact->update_meta( 'plan', $plan );
		}

		$this->contacts[ $label ] = $id;
	}

	/**
	 * The segment params shared by every test: audience "target", minus tag "vip", minus plan = pro
	 *
	 * @return array
	 */
	protected function segment(): array {
		return [
			'tags_include'    => [ $this->tags['target'] ],
			'tags_exclude'    => [ $this->tags['vip'] ],
			'exclude_segment' => [
				'meta' => [ [ 'key' => 'plan', 'compare' => 'equals', 'value' => 'pro' ] ],
			],
			'marketable'      => true,
		];
	}

	protected function broadcast( array $input ) {

		return wp_get_ability( self::ABILITY )->execute( array_merge( [
			'email_id' => $this->email->get_id(),
			'when'     => wp_date( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS ),
		], $input ) );
	}

	/**
	 * Labels of the contacts with a broadcast event queued, sorted
	 *
	 * @param int $broadcast_id
	 *
	 * @return string[]
	 */
	protected function queued_labels( int $broadcast_id ): array {

		$events = event_queue_db()->query( [
			'event_type' => Event::BROADCAST,
			'step_id'    => $broadcast_id,
			'funnel_id'  => Broadcast::FUNNEL_ID,
		] );

		$ids    = array_map( 'absint', wp_list_pluck( $events, 'contact_id' ) );
		$labels = array_keys( array_intersect( $this->contacts, $ids ) );
		sort( $labels );

		return $labels;
	}

	/**
	 * Queue everything a broadcast still has to enqueue, as the background task would
	 *
	 * @param Broadcast $broadcast
	 */
	protected function finish_scheduling( Broadcast $broadcast ) {

		for ( $i = 0; $i < 20 && $broadcast->is_schedulable(); $i ++ ) {
			$broadcast->enqueue_batch();
			$broadcast = new Broadcast( $broadcast->get_id() );
		}
	}

	/**
	 * search-contacts is the reference: what it counts is who should be sent to
	 */
	public function test_search_contacts_baseline() {

		$out = wp_get_ability( 'groundhogg/search-contacts' )->execute( $this->segment() );

		$this->assertNotWPError( $out );
		$this->assertSame( 1, $out['total_items'] );
		$this->assertSame( [ $this->contacts['keep'] ], array_map( 'absint', wp_list_pluck( $out['contacts'], 'id' ) ) );
	}

	public function test_fixed_segment_applies_tags_exclude_and_exclude_segment() {

		$out = $this->broadcast( array_merge( $this->segment(), [ 'segment_type' => 'fixed' ] ) );

		$this->assertNotWPError( $out );
		$this->assertSame( 1, $out['estimated_recipients'] );

		$this->assertSame( [ 'keep' ], $this->queued_labels( $out['broadcast_id'] ) );
	}

	public function test_fixed_segment_tags_exclude_alone() {

		$input = $this->segment();
		unset( $input['exclude_segment'] );

		$out = $this->broadcast( array_merge( $input, [ 'segment_type' => 'fixed' ] ) );

		$this->assertNotWPError( $out );
		$this->assertSame( [ 'keep', 'segment_excluded' ], $this->queued_labels( $out['broadcast_id'] ) );
		$this->assertSame( 2, $out['estimated_recipients'] );
	}

	public function test_fixed_segment_exclude_segment_alone() {

		$input = $this->segment();
		unset( $input['tags_exclude'] );

		$out = $this->broadcast( array_merge( $input, [ 'segment_type' => 'fixed' ] ) );

		$this->assertNotWPError( $out );
		$this->assertSame( [ 'keep', 'tag_excluded' ], $this->queued_labels( $out['broadcast_id'] ) );
		$this->assertSame( 2, $out['estimated_recipients'] );
	}

	public function test_dynamic_segment_stores_exclusions_and_applies_them_when_queued() {

		$out = $this->broadcast( array_merge( $this->segment(), [ 'segment_type' => 'dynamic' ] ) );

		$this->assertNotWPError( $out );
		$this->assertSame( 'dynamic', $out['segment_type'] );
		$this->assertSame( 1, $out['estimated_recipients'] );

		$broadcast = new Broadcast( $out['broadcast_id'] );
		$query     = $broadcast->get_query();

		// the exclusions are part of what's stored, so they apply again whenever the audience is evaluated
		$this->assertNotEmpty( $query['exclude_filters'] ?? $query['filters'] ?? [], 'stored query: ' . wp_json_encode( $query ) );

		$this->assertSame( [], $this->queued_labels( $broadcast->get_id() ), 'a dynamic segment queues nothing at schedule time' );

		$this->finish_scheduling( $broadcast );

		$this->assertSame( [ 'keep' ], $this->queued_labels( $broadcast->get_id() ) );
	}

	/**
	 * Audience changes between scheduling and sending must be picked up, exclusions included
	 */
	public function test_dynamic_segment_re_evaluates_exclusions_at_send_time() {

		$out = $this->broadcast( array_merge( $this->segment(), [ 'segment_type' => 'dynamic' ] ) );

		$this->assertNotWPError( $out );

		// a contact who qualified when scheduled becomes excluded, and an excluded one stops being so
		( new Contact( $this->contacts['keep'] ) )->apply_tag( [ $this->tags['vip'] ] );
		( new Contact( $this->contacts['segment_excluded'] ) )->update_meta( 'plan', 'free' );

		$this->finish_scheduling( new Broadcast( $out['broadcast_id'] ) );

		$this->assertSame( [ 'segment_excluded' ], $this->queued_labels( $out['broadcast_id'] ) );
	}

	public function test_estimated_recipients_matches_queued_events() {

		foreach ( [ 'fixed', 'dynamic' ] as $segment_type ) {

			$out = $this->broadcast( array_merge( $this->segment(), [ 'segment_type' => $segment_type ] ) );

			$this->assertNotWPError( $out );

			$this->finish_scheduling( new Broadcast( $out['broadcast_id'] ) );

			$this->assertCount( $out['estimated_recipients'], $this->queued_labels( $out['broadcast_id'] ), $segment_type );
		}
	}

	/**
	 * Several excluded tags: a contact with ANY one of them is dropped, whichever path builds the query
	 */
	/**
	 * A contact the query matches but the scheduler skips (here, one an add-on reports undeliverable) is
	 * not a recipient, and must not be counted as one
	 */
	public function test_estimated_recipients_leaves_out_contacts_that_are_skipped() {

		$skipped = $this->contacts['keep'];

		$skip = function ( $deliverable, $contact ) use ( $skipped ) {
			return $contact->get_id() === $skipped ? false : $deliverable;
		};

		add_filter( 'groundhogg/contact/is_deliverable', $skip, 10, 2 );

		$out = $this->broadcast( [
			'tags_include' => [ $this->tags['target'] ],
			'segment_type' => 'fixed',
		] );

		remove_filter( 'groundhogg/contact/is_deliverable', $skip, 10 );

		$this->assertNotWPError( $out );
		$this->assertTrue( $out['scheduling_complete'] );

		$queued = $this->queued_labels( $out['broadcast_id'] );

		$this->assertNotContains( 'keep', $queued );
		$this->assertCount( 3, $queued );
		$this->assertSame( 3, $out['estimated_recipients'] );
	}

	public function test_tags_exclude_with_several_tags_drops_a_contact_with_any_of_them() {

		$this->make_contact( 'pro_tagged', [ 'target', 'pro' ] );

		$input = [
			'tags_include' => [ $this->tags['target'] ],
			'tags_exclude' => [ $this->tags['vip'], $this->tags['pro'] ],
		];

		$search = wp_get_ability( 'groundhogg/search-contacts' )->execute( $input );

		$this->assertNotWPError( $search );
		$this->assertSame( 2, $search['total_items'], 'search-contacts' );

		$out = $this->broadcast( array_merge( $input, [ 'segment_type' => 'fixed' ] ) );

		$this->assertNotWPError( $out );
		$this->assertSame( [ 'keep', 'segment_excluded' ], $this->queued_labels( $out['broadcast_id'] ) );
		$this->assertSame( 2, $out['estimated_recipients'] );
	}

	public function test_tags_exclude_needs_all() {

		$this->make_contact( 'both_tags', [ 'target', 'vip', 'pro' ] );

		$out = $this->broadcast( [
			'tags_include'           => [ $this->tags['target'] ],
			'tags_exclude'           => [ $this->tags['vip'], $this->tags['pro'] ],
			'tags_exclude_needs_all' => true,
			'segment_type'           => 'fixed',
		] );

		$this->assertNotWPError( $out );

		// only a contact with BOTH excluded tags is dropped
		$this->assertNotContains( 'both_tags', $this->queued_labels( $out['broadcast_id'] ) );
		$this->assertContains( 'tag_excluded', $this->queued_labels( $out['broadcast_id'] ) );
	}

	public function test_optin_status_exclude() {

		// still marketable, so only the exclusion can keep this contact out
		( new Contact( $this->contacts['segment_excluded'] ) )->update( [ 'optin_status' => Preferences::UNCONFIRMED ] );

		$out = $this->broadcast( [
			'tags_include'         => [ $this->tags['target'] ],
			'optin_status_exclude' => [ Preferences::UNCONFIRMED ],
			'segment_type'         => 'fixed',
		] );

		$this->assertNotWPError( $out );
		$this->assertSame( [ 'both_excluded', 'keep', 'tag_excluded' ], $this->queued_labels( $out['broadcast_id'] ) );
	}
}
