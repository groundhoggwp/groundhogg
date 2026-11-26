<?php

use Groundhogg\Broadcast;
use Groundhogg\Classes\Activity;
use Groundhogg\Contact;
use Groundhogg\Email;
use Groundhogg\Email_Variations;
use Groundhogg\Event;
use Groundhogg\Preferences;
use function Groundhogg\event_queue_db;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;

/**
 * A/B tests: test versions of an email, the split between them, the report and the winner, and a broadcast that tests
 * on a sample before sending the winner to everyone else. See Email_Variations.
 */
class Email_AB_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		$this->factory()->truncate();
		Email_Variations::flush();
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$this->factory()->truncate(); // the user got a contact
		wp_clear_scheduled_hook( Email_Variations::FINISH_BROADCAST_TEST_HOOK );
	}

	public function tearDown(): void {
		Email_Variations::flush();
		wp_clear_scheduled_hook( Email_Variations::FINISH_BROADCAST_TEST_HOOK );
		parent::tearDown();
	}

	protected function email(): Email {
		$email = new Email();
		$email->create( [
			'title'   => 'Sale',
			'subject' => 'Sale',
			'content' => '<p>Hello</p>',
			'status'  => 'ready',
		] );

		return $email;
	}

	protected function test_version( Email $email, array $args = [] ): Email {
		$test = Email_Variations::create( $email, array_merge( [ 'kind' => 'test', 'copy' => true, 'status' => 'ready' ], $args ) );
		$this->assertInstanceOf( Email::class, $test );

		return new Email( $test->get_id() );
	}

	protected function contacts( int $count, string $locale = '' ): array {
		$contacts = [];
		for ( $i = 0; $i < $count; $i ++ ) {
			$contact = get_contactdata( self::factory()->contacts->create( [ 'optin_status' => Preferences::CONFIRMED ] ) );
			if ( $locale ) {
				$contact->set_locale( $locale );
			}
			$contacts[] = $contact;
		}

		return $contacts;
	}

	/**
	 * Which version each contact gets, by ID
	 */
	protected function split( Email $email, array $contacts ): array {
		Email_Variations::flush();
		$email = new Email( $email->get_id() );

		return array_count_values( array_map( function ( Contact $contact ) use ( $email ) {
			$chosen = Email_Variations::resolve( $email, $contact );

			return $chosen ? $chosen->get_id() : $email->get_id();
		}, $contacts ) );
	}

	/* the split */

	public function test_test_versions_split_evenly() {

		$email = $this->email();
		$b     = $this->test_version( $email );

		$this->assertSame( 'Sale (B)', $b->get_title() );
		$this->assertSame( 50, Email_Variations::get_weight( $b ) );
		$this->assertTrue( Email_Variations::is_test( $b ) );
		$this->assertGreaterThan( 0, absint( $email->get_meta( Email_Variations::TEST_STARTED_KEY ) ) );

		$c = $this->test_version( $email );
		$this->assertSame( 'Sale (C)', $c->get_title() );
		$this->assertSame( 33, Email_Variations::get_weight( new Email( $b->get_id() ) ) );
		$this->assertSame( 33, Email_Variations::get_weight( $c ) );
	}

	public function test_contacts_are_split_and_always_get_the_same_version() {

		$email    = $this->email();
		$b        = $this->test_version( $email );
		$contacts = $this->contacts( 200 );

		$split = $this->split( $email, $contacts );

		// roughly half each
		$this->assertGreaterThan( 60, $split[ $email->get_id() ] );
		$this->assertGreaterThan( 60, $split[ $b->get_id() ] );

		// and the same every time
		$this->assertSame( $split, $this->split( $email, $contacts ) );
	}

	public function test_a_language_comes_before_the_test() {

		$email = $this->email();
		$this->test_version( $email, [ 'weight' => 100 ] );
		$fr = Email_Variations::create( $email, [ 'audience' => [ 'locales' => [ 'fr_FR' ] ], 'status' => 'ready' ] );

		$french = $this->contacts( 1, 'fr_FR' )[0];

		$this->assertSame( $fr->get_id(), Email_Variations::resolve( new Email( $email->get_id() ), $french )->get_id() );
	}

	public function test_drafts_are_left_out_of_the_test() {

		$email = $this->email();
		$this->test_version( $email, [ 'status' => 'draft', 'weight' => 100 ] );

		$this->assertSame( [ $email->get_id() => 20 ], $this->split( $email, $this->contacts( 20 ) ) );
	}

	public function test_everyone_gets_the_winner() {

		$email    = $this->email();
		$b        = $this->test_version( $email );
		$contacts = $this->contacts( 40 );

		$this->assertTrue( Email_Variations::declare_winner( $email, $b->get_id() ) );
		$this->assertSame( [ $b->get_id() => 40 ], $this->split( $email, $contacts ) );

		$this->assertTrue( Email_Variations::declare_winner( $email, $email->get_id() ) );
		$this->assertSame( [ $email->get_id() => 40 ], $this->split( $email, $contacts ) );

		$this->assertWPError( Email_Variations::declare_winner( $email, 999999 ) );

		// a new test version starts a new test
		$this->test_version( $email );
		$this->assertSame( 0, Email_Variations::get_winner_id( new Email( $email->get_id() ) ) );
	}

	public function test_weights() {

		$email = $this->email();
		$b     = $this->test_version( $email );
		$c     = $this->test_version( $email );

		$this->assertTrue( Email_Variations::update( $b, [ 'weight' => 60 ] ) );
		$this->assertWPError( Email_Variations::update( $c, [ 'weight' => 50 ] ) );
		$this->assertWPError( Email_Variations::update( $c, [ 'audience' => [ 'locales' => [ 'fr_FR' ] ] ] ) );

		$fr = Email_Variations::create( $email, [ 'audience' => [ 'locales' => [ 'fr_FR' ] ] ] );
		$this->assertWPError( Email_Variations::update( $fr, [ 'weight' => 10 ] ) );
	}

	/* the report */

	protected function sent( Email $version, Contact $contact, array $activity = [] ) {

		get_db( 'events' )->add( [
			'time'       => time(),
			'contact_id' => $contact->get_id(),
			'email_id'   => $version->get_id(),
			'funnel_id'  => 1,
			'step_id'    => 1,
			'event_type' => Event::FUNNEL,
			'status'     => Event::COMPLETE,
		] );

		foreach ( $activity as $type ) {
			get_db( 'activity' )->add( [
				'timestamp'     => time(),
				'contact_id'    => $contact->get_id(),
				'email_id'      => $version->get_id(),
				'funnel_id'     => 1,
				'step_id'       => 1,
				'activity_type' => $type,
			] );
		}
	}

	public function test_confidence() {
		$this->assertEqualsWithDelta( 0.0, Email_Variations::confidence( 10, 100, 10, 100 ), 0.0001 );
		$this->assertSame( 0.0, Email_Variations::confidence( 0, 0, 1, 10 ) );
		$this->assertGreaterThan( 0.99, Email_Variations::confidence( 30, 100, 10, 100 ) );
		$this->assertLessThan( 0.95, Email_Variations::confidence( 12, 100, 10, 100 ) );
	}

	public function test_the_report() {

		$email = $this->email();
		$b     = $this->test_version( $email );
		$email->update_meta( Email_Variations::TEST_STARTED_KEY, time() - 60 );

		foreach ( $this->contacts( 100 ) as $i => $contact ) {
			$this->sent( $email, $contact, $i < 10 ? [ Activity::EMAIL_OPENED, Activity::EMAIL_CLICKED ] : [ Activity::EMAIL_OPENED ] );
		}

		foreach ( $this->contacts( 100 ) as $i => $contact ) {
			$this->sent( $b, $contact, $i < 30 ? [ Activity::EMAIL_OPENED, Activity::EMAIL_CLICKED ] : [] );
		}

		$report   = Email_Variations::report( new Email( $email->get_id() ) );
		$versions = array_column( $report['versions'], null, 'id' );

		$this->assertSame( 100, $versions[ $email->get_id() ]['sent'] );
		$this->assertSame( 0.1, $versions[ $email->get_id() ]['click_rate'] );
		$this->assertSame( 1.0, $versions[ $email->get_id() ]['open_rate'] );
		$this->assertSame( 0.3, $versions[ $b->get_id() ]['click_rate'] );
		$this->assertSame( 50, $versions[ $b->get_id() ]['weight'] );
		$this->assertSame( 50, $versions[ $email->get_id() ]['weight'] );

		$this->assertSame( $b->get_id(), $report['leader'] );
		$this->assertSame( $b->get_id(), $report['suggested_winner'] );

		// by opens, A is ahead
		$by_opens = Email_Variations::report( new Email( $email->get_id() ), [ 'metric' => 'opens' ] );
		$this->assertSame( $email->get_id(), $by_opens['leader'] );

		// nothing from before the test started
		$email->update_meta( Email_Variations::TEST_STARTED_KEY, time() + 60 );
		$this->assertSame( 0, Email_Variations::report( new Email( $email->get_id() ) )['versions'][0]['sent'] );
	}

	/* a broadcast that tests first */

	public function test_a_broadcast_tests_on_a_sample_then_sends_the_winner_to_the_rest() {

		$email    = $this->email();
		$b        = $this->test_version( $email );
		$contacts = $this->contacts( 20 );

		$sample = Email_Variations::start_broadcast_test( $email, [ 'include' => wp_list_pluck( $contacts, 'ID' ) ], [ 'send_now' => true ], [
			'sample_percent' => 25,
			'wait_hours'     => 2,
		] );

		$this->assertInstanceOf( Broadcast::class, $sample );

		$sampled = wp_parse_id_list( $sample->get_query()['include'] );
		$this->assertCount( 5, $sampled );

		$record = ( new Email( $email->get_id() ) )->get_meta( Email_Variations::BROADCAST_TEST_KEY );
		$this->assertSame( 'testing', $record['status'] );
		$this->assertSame( 15, $record['rest'] );
		$this->assertNotFalse( wp_next_scheduled( Email_Variations::FINISH_BROADCAST_TEST_HOOK, [ $email->get_id() ] ) );

		// only one test at a time
		$this->assertWPError( Email_Variations::start_broadcast_test( $email, [ 'include' => wp_list_pluck( $contacts, 'ID' ) ], [ 'send_now' => true ], [] ) );

		// B did better on the sample
		foreach ( $sampled as $id ) {
			$version = new Email( $id % 2 ? $b->get_id() : $email->get_id() );
			get_db( 'events' )->add( [ 'time' => time(), 'contact_id' => $id, 'email_id' => $version->get_id(), 'funnel_id' => Broadcast::FUNNEL_ID, 'step_id' => $sample->get_id(), 'event_type' => Event::BROADCAST, 'status' => Event::COMPLETE ] );
			if ( $version->get_id() === $b->get_id() ) {
				get_db( 'activity' )->add( [ 'timestamp' => time(), 'contact_id' => $id, 'email_id' => $b->get_id(), 'funnel_id' => Broadcast::FUNNEL_ID, 'step_id' => $sample->get_id(), 'activity_type' => Activity::EMAIL_CLICKED ] );
			}
		}

		Email_Variations::finish_broadcast_test( $email->get_id() );

		$record = ( new Email( $email->get_id() ) )->get_meta( Email_Variations::BROADCAST_TEST_KEY );
		$this->assertSame( 'done', $record['status'], $record['error'] ?? '' );
		$this->assertSame( $b->get_id(), $record['winner'] );
		$this->assertSame( $b->get_id(), Email_Variations::get_winner_id( new Email( $email->get_id() ) ) );

		// the rest is everyone but the sample
		$rest = new Broadcast( $record['rest_broadcast_id'] );
		$this->assertTrue( $rest->exists() );
		$this->assertEqualSets( $sampled, wp_parse_id_list( $rest->get_query()['exclude'] ) );
		$this->assertSame( 15, absint( $rest->get_meta( 'total_contacts' ) ) );

		// and running it again does nothing
		Email_Variations::finish_broadcast_test( $email->get_id() );
		$this->assertSame( $record['rest_broadcast_id'], ( new Email( $email->get_id() ) )->get_meta( Email_Variations::BROADCAST_TEST_KEY )['rest_broadcast_id'] );
	}

	public function test_a_broadcast_test_needs_test_versions() {
		$email = $this->email();
		$this->assertWPError( Email_Variations::start_broadcast_test( $email, [ 'include' => wp_list_pluck( $this->contacts( 5 ), 'ID' ) ], [ 'send_now' => true ], [] ) );
	}

	/* abilities */

	protected function ability( string $name, array $input ) {
		$out = wp_get_ability( $name )->execute( $input );

		return is_wp_error( $out ) ? $out : json_decode( wp_json_encode( $out ), true );
	}

	public function test_the_abilities() {

		if ( ! function_exists( 'wp_get_ability' ) || ! wp_get_ability( 'groundhogg/get-email-variation-report' ) ) {
			$this->markTestSkipped( 'The A/B test abilities are not registered.' );
		}

		$email = $this->email();

		$created = $this->ability( 'groundhogg/create-email-variation', [ 'email_id' => $email->get_id(), 'kind' => 'test' ] );
		$this->assertNotWPError( $created );
		$this->assertSame( 'test', $created['variation']['variation']['kind'] );
		$this->assertSame( 50, $created['variation']['variation']['weight'] );

		$this->assertWPError( $this->ability( 'groundhogg/create-email-variation', [ 'email_id' => $email->get_id(), 'kind' => 'test', 'audience' => [ 'locales' => [ 'fr_FR' ] ] ] ) );
		$this->assertWPError( $this->ability( 'groundhogg/create-email-variation', [ 'email_id' => $email->get_id() ] ) );

		$b = $created['variation']['id'];
		( new Email( $b ) )->update( [ 'status' => 'ready' ] );

		$report = $this->ability( 'groundhogg/get-email-variation-report', [ 'email_id' => $email->get_id() ] );
		$this->assertNotWPError( $report );
		$this->assertSame( [ 'email', 'test' ], array_column( $report['versions'], 'kind' ) );
		$this->assertSame( 0, $report['winner'] );

		$this->assertWPError( $this->ability( 'groundhogg/declare-email-winner', [ 'email_id' => $email->get_id(), 'winner_id' => 12345 ] ) );

		$declared = $this->ability( 'groundhogg/declare-email-winner', [ 'email_id' => $email->get_id(), 'winner_id' => $b ] );
		$this->assertSame( $b, $declared['winner'] );

		$updated = $this->ability( 'groundhogg/update-email-variation', [ 'variation_id' => $b, 'weight' => 30 ] );
		$this->assertSame( 30, $updated['variation']['variation']['weight'] );

		$restarted = $this->ability( 'groundhogg/declare-email-winner', [ 'email_id' => $email->get_id(), 'winner_id' => 0 ] );
		$this->assertSame( 0, $restarted['winner'] );
	}

	public function test_the_broadcast_ability() {

		if ( ! function_exists( 'wp_get_ability' ) || ! wp_get_ability( 'groundhogg/send-email-broadcast' ) ) {
			$this->markTestSkipped( 'The broadcast ability is not registered.' );
		}

		$email    = $this->email();
		$contacts = $this->contacts( 10 );

		$input = [
			'email_id' => $email->get_id(),
			'include'  => wp_list_pluck( $contacts, 'ID' ),
			'send_now' => true,
			'ab_test'  => [ 'sample_percent' => 20, 'wait_hours' => 1 ],
		];

		// no test versions yet
		$this->assertWPError( $this->ability( 'groundhogg/send-email-broadcast', $input ) );

		$this->test_version( $email );

		$out = $this->ability( 'groundhogg/send-email-broadcast', $input );
		$this->assertNotWPError( $out );
		$this->assertSame( 2, $out['ab_test']['sample'] );
		$this->assertSame( 8, $out['ab_test']['rest'] );
		$this->assertSame( 2, $out['estimated_recipients'] );
	}

	/* the broadcast scheduler */

	public function test_the_scheduler_route() {

		$email    = $this->email();
		$contacts = $this->contacts( 10 );

		$schedule = function ( array $extra ) use ( $email, $contacts ) {
			$request = new WP_REST_Request( 'POST', '/gh/v4/broadcasts' );
			$request->set_body_params( array_merge( [
				'object_id'   => $email->get_id(),
				'object_type' => 'email',
				'query'       => [ 'include' => wp_list_pluck( $contacts, 'ID' ) ],
				'send_now'    => true,
				'ab_test'     => [ 'sample_percent' => 20, 'wait_hours' => 3, 'metric' => 'opens' ],
			], $extra ) );

			return rest_get_server()->dispatch( $request );
		};

		// no test versions yet
		$this->assertSame( 400, $schedule( [] )->get_status() );

		$this->test_version( $email );

		// a test is one send
		$this->assertSame( 400, $schedule( [ 'is_recurring' => true ] )->get_status() );

		$response = $schedule( [] );
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertSame( 2, $data['ab_test']['sample'] );
		$this->assertSame( 8, $data['ab_test']['rest'] );
		$this->assertCount( 2, wp_parse_id_list( ( new Broadcast( $data['item']->get_id() ) )->get_query()['include'] ) );

		$record = ( new Email( $email->get_id() ) )->get_meta( Email_Variations::BROADCAST_TEST_KEY );
		$this->assertSame( 'opens', $record['metric'] );
		$this->assertSame( 3, $record['wait_hours'] );

		// without ab_test it's a normal broadcast to everyone
		$response = $schedule( [ 'ab_test' => null ] );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 10, absint( $response->get_data()['item']->get_meta( 'total_contacts' ) ) );
	}

	/* the email editor */

	public function test_the_editor_routes() {

		$email = $this->email();

		$request = new WP_REST_Request( 'POST', '/gh/v4/emails/' . $email->get_id() . '/variations' );
		$request->set_body_params( [ 'kind' => 'test' ] );
		$data = rest_get_server()->dispatch( $request )->get_data();

		$this->assertSame( 'test', $data['variations'][0]['kind'] );
		$this->assertSame( [ 'email', 'test' ], array_column( $data['test']['versions'], 'kind' ) );

		$b = $data['variations'][0]['id'];
		( new Email( $b ) )->update( [ 'status' => 'ready' ] );

		$request = new WP_REST_Request( 'POST', '/gh/v4/emails/' . $email->get_id() . '/variations/winner' );
		$request->set_body_params( [ 'winner_id' => $b ] );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $b, $response->get_data()['test']['winner'] );
	}
}
