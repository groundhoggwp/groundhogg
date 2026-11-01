<?php

use Groundhogg\Classes\Message;
use Groundhogg\Classes\Note;
use Groundhogg\Classes\Task;
use Groundhogg\Pii;
use Groundhogg\Saved_Searches;
use Groundhogg\Submission;
use function Groundhogg\get_contactdata;

/**
 * What a user without the view_contact_pii capability can't see or search by through the REST API
 *
 * @see \Groundhogg\Pii_Rest
 * @see Pii_Restriction_Tests for the abilities
 */
class Pii_Restriction_Rest_Tests extends GH_UnitTestCase {

	const EMAIL = 'jane.doe@example.com';

	protected $contact_id;

	public function setUp(): void {
		parent::setUp();

		$this->contact_id = $this->factory()->contacts->create( [
			'email'      => self::EMAIL,
			'first_name' => 'Jane',
			'last_name'  => 'Doe',
		] );

		get_contactdata( $this->contact_id )->update_meta( [
			'primary_phone' => '555-0100',
			'company_name'  => 'Acme',
		] );
	}

	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	protected function admin( bool $pii ): int {

		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );

		// WP_Roles::add_cap() doesn't refresh cached roles, so don't depend on the installer having run on this DB
		get_userdata( $user_id )->add_cap( Pii::CAP, $pii );

		wp_set_current_user( $user_id );

		return $user_id;
	}

	protected function request( string $method, string $route, array $params = [], $body = null ): WP_REST_Response {

		$request = new WP_REST_Request( $method, $route );

		if ( $method === 'GET' || $method === 'DELETE' ) {
			$request->set_query_params( $params );
		} else if ( $body !== null ) {
			$request->set_header( 'Content-Type', 'application/json' );
			$request->set_body( wp_json_encode( $body ) );
			$request->set_query_params( $params );
		} else {
			$request->set_header( 'Content-Type', 'application/json' );
			$request->set_body( wp_json_encode( $params ) );
		}

		return rest_do_request( $request );
	}

	protected function assertRestricted( WP_REST_Response $response ) {
		$this->assertSame( 403, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$this->assertSame( 'groundhogg_pii_restricted', $response->get_data()['code'] ?? '' );
	}

	protected function assertNoPii( WP_REST_Response $response ) {
		$json = wp_json_encode( $response->get_data() );

		foreach ( [ self::EMAIL, 'Jane', 'Doe', '555-0100' ] as $pii ) {
			$this->assertStringNotContainsString( $pii, $json );
		}
	}

	public function test_unrestricted_user_gets_the_whole_contact() {
		$this->admin( true );

		$response = $this->request( 'GET', "/gh/v4/contacts/{$this->contact_id}" );
		$item     = json_decode( wp_json_encode( $response->get_data() ), true )['item'];

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( self::EMAIL, $item['data']['email'] );
		$this->assertSame( 'Jane', $item['data']['first_name'] );
		$this->assertSame( '555-0100', $item['meta']['primary_phone'] );
	}

	public function test_restricted_user_gets_a_redacted_contact_with_the_same_id() {
		$this->admin( false );

		$response = $this->request( 'GET', "/gh/v4/contacts/{$this->contact_id}" );
		$item     = json_decode( wp_json_encode( $response->get_data() ), true )['item'];

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $this->contact_id, $item['ID'] );
		$this->assertSame( Pii::REDACTED, $item['data']['email'] );
		$this->assertSame( Pii::REDACTED, $item['data']['first_name'] );
		$this->assertSame( Pii::REDACTED, $item['data']['last_name'] );
		$this->assertSame( Pii::REDACTED, $item['data']['full_name'] );
		$this->assertArrayHasKey( 'date_created', $item['data'] );
		$this->assertArrayHasKey( 'optin_status', $item['data'] );
		$this->assertStringNotContainsString( md5( self::EMAIL ), $item['data']['gravatar'] );
		$this->assertSame( 'Contact #' . $this->contact_id, $item['i18n']['displayAs'] );
		$this->assertNoPii( $response );
	}

	public function test_restricted_meta_keeps_safe_keys_and_drops_pii() {
		$this->admin( false );

		$response = $this->request( 'GET', "/gh/v4/contacts/{$this->contact_id}" );
		$meta     = json_decode( wp_json_encode( $response->get_data() ), true )['item']['meta'];

		$this->assertSame( 'Acme', $meta['company_name'] );
		$this->assertArrayNotHasKey( 'primary_phone', $meta );
	}

	public function test_restricted_user_can_list_contacts_without_the_identity() {
		$this->admin( false );

		$response = $this->request( 'GET', '/gh/v4/contacts' );

		$this->assertSame( 200, $response->get_status() );
		$this->assertGreaterThanOrEqual( 1, $response->get_data()['total_items'] );
		$this->assertNoPii( $response );
	}

	public function test_restricted_user_can_still_query_by_non_pii() {
		$this->admin( false );

		$response = $this->request( 'GET', '/gh/v4/contacts', [ 'optin_status' => 2, 'count' => 1 ] );

		$this->assertSame( 200, $response->get_status() );
	}

	public function test_restricted_user_cannot_search_contacts() {
		$this->admin( false );

		$this->assertRestricted( $this->request( 'GET', '/gh/v4/contacts', [ 'search' => 'jane' ] ) );
		$this->assertRestricted( $this->request( 'GET', '/gh/v4/contacts', [ 'query' => [ 'search' => 'jane' ] ] ) );
		$this->assertRestricted( $this->request( 'GET', '/gh/v4/contacts', [ 'email' => self::EMAIL, 'count' => 1 ] ) );
		$this->assertRestricted( $this->request( 'GET', '/gh/v4/contacts', [ 'orderby' => 'email' ] ) );
	}

	public function test_restricted_user_cannot_filter_contacts_by_pii() {
		$this->admin( false );

		$filters = [ [ [ 'type' => 'email', 'compare' => 'equals', 'value' => self::EMAIL ] ] ];

		$this->assertRestricted( $this->request( 'GET', '/gh/v4/contacts', [ 'filters' => $filters, 'count' => 1 ] ) );
		$this->assertRestricted( $this->request( 'GET', '/gh/v4/contacts', [ 'query' => [ 'filters' => $filters ], 'count' => 1 ] ) );
	}

	public function test_restricted_user_cannot_filter_contacts_by_pii_meta() {
		$this->admin( false );

		$filters = [ [ [ 'type' => 'meta', 'meta' => 'primary_phone', 'compare' => 'equals', 'value' => '555-0100' ] ] ];

		$this->assertRestricted( $this->request( 'GET', '/gh/v4/contacts', [ 'filters' => $filters, 'count' => 1 ] ) );
	}

	public function test_restricted_user_cannot_bulk_update_or_delete_by_pii() {
		$this->admin( false );

		$this->assertRestricted( $this->request( 'PATCH', '/gh/v4/contacts', [], [
			'query' => [ 'search' => 'jane' ],
			'meta'  => [ 'company_name' => 'Hacked' ],
		] ) );

		$this->assertRestricted( $this->request( 'DELETE', '/gh/v4/contacts', [ 'query' => [ 'email' => self::EMAIL ] ] ) );

		$this->assertTrue( get_contactdata( $this->contact_id )->exists() );
		$this->assertSame( 'Acme', get_contactdata( $this->contact_id )->get_meta( 'company_name' ) );
	}

	public function test_restricted_user_cannot_find_out_an_address_is_a_contact_by_creating_it() {
		$this->admin( false );

		$response = $this->request( 'POST', '/gh/v4/contacts', [ 'email' => self::EMAIL ] );

		$this->assertSame( 422, $response->get_status() );
		$this->assertSame( 'groundhogg_contact_not_created', $response->get_data()['code'] );
		$this->assertNoPii( $response );
	}

	public function test_restricted_user_can_create_a_new_contact() {
		$this->admin( false );

		$response = $this->request( 'POST', '/gh/v4/contacts', [ 'email' => 'new.person@example.com', 'first_name' => 'New' ] );

		$this->assertContains( $response->get_status(), [ 200, 201 ], wp_json_encode( $response->get_data() ) );
		$this->assertStringNotContainsString( 'new.person@example.com', wp_json_encode( $response->get_data() ) );
	}

	public function test_restricted_user_cannot_reach_routes_that_are_personal_by_nature() {
		$this->admin( false );

		$this->assertRestricted( $this->request( 'GET', "/gh/v4/contacts/{$this->contact_id}/files" ) );
		$this->assertRestricted( $this->request( 'GET', "/gh/v4/contacts/{$this->contact_id}/inbox" ) );
		$this->assertRestricted( $this->request( 'GET', '/gh/v4/contacts/table/row', [ 'contact' => $this->contact_id ] ) );
		$this->assertRestricted( $this->request( 'GET', '/gh/v4/files/exports' ) );
	}

	public function test_unrestricted_user_can_search_contacts() {
		$this->admin( true );

		$response = $this->request( 'GET', '/gh/v4/contacts', [ 'search' => 'jane' ] );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 1, $response->get_data()['total_items'] );
	}

	public function test_restricted_user_gets_the_timeline_without_the_identity() {
		$this->admin( false );

		$response = $this->request( 'GET', "/gh/v4/contacts/{$this->contact_id}/timeline" );

		$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$this->assertNoPii( $response );
	}

	protected function create_message( string $direction = 'inbound' ): Message {

		$message = new Message();
		$message->create( [
			'object_type'  => 'contact',
			'object_id'    => $this->contact_id,
			'direction'    => $direction,
			'from_address' => self::EMAIL,
			'to_address'   => 'me@example.com',
			'subject'      => 'Question from Jane',
			'content'      => '<p>My number is 555-0100</p>',
			'is_read'      => 1,
		] );

		return $message;
	}

	public function test_unrestricted_user_reads_the_message() {
		$this->admin( true );
		$message = $this->create_message();

		$response = $this->request( 'GET', '/gh/v4/messages/' . $message->get_id() );
		$item     = json_decode( wp_json_encode( $response->get_data() ), true )['item'];

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'Question from Jane', $item['data']['subject'] );
		$this->assertSame( self::EMAIL, $item['data']['from_address'] );
	}

	public function test_restricted_user_reads_a_message_without_what_was_said_or_who_said_it() {
		$this->admin( false );
		$message = $this->create_message();

		$response = $this->request( 'GET', '/gh/v4/messages/' . $message->get_id() );
		$item     = json_decode( wp_json_encode( $response->get_data() ), true )['item'];

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $message->get_id(), $item['ID'] );
		$this->assertSame( Pii::REDACTED, $item['data']['subject'] );
		$this->assertSame( Pii::REDACTED, $item['data']['content'] );
		$this->assertSame( Pii::REDACTED, $item['data']['from_address'] );
		$this->assertSame( Pii::REDACTED, $item['preview'] );
		$this->assertStringNotContainsString( '555-0100', wp_json_encode( $response->get_data() ) );
		$this->assertStringNotContainsString( self::EMAIL, wp_json_encode( $response->get_data() ) );
	}

	public function test_restricted_user_cannot_search_messages_or_the_email_log() {
		$this->admin( false );

		$this->assertRestricted( $this->request( 'GET', '/gh/v4/messages', [ 'search' => 'Jane' ] ) );
		$this->assertRestricted( $this->request( 'GET', '/gh/v4/messages', [ 'from_address' => self::EMAIL ] ) );
		$this->assertRestricted( $this->request( 'GET', '/gh/v4/email_log', [ 'search' => 'Jane' ] ) );
		$this->assertRestricted( $this->request( 'GET', '/gh/v4/email_log', [ 'recipients' => self::EMAIL ] ) );
		$this->assertRestricted( $this->request( 'GET', '/gh/v4/page_visits', [ 'ip_address' => '127.0.0.1' ] ) );
		$this->assertRestricted( $this->request( 'GET', '/gh/v4/activity', [ 'orderby' => 'ip_address' ] ) );
	}

	public function test_restricted_user_can_still_read_activity() {
		$this->admin( false );

		$response = $this->request( 'GET', '/gh/v4/activity', [ 'contact_id' => $this->contact_id ] );

		$this->assertSame( 200, $response->get_status() );
	}

	public function test_saved_searches_are_listed_without_the_pii_they_filter_on() {
		Saved_Searches::instance()->add( 'pii-search', [
			'id'    => 'pii-search',
			'name'  => 'Janes',
			'query' => [ 'email_like' => 'jane' ],
		] );
		Saved_Searches::instance()->add( 'safe-search', [
			'id'    => 'safe-search',
			'name'  => 'Opted in',
			'query' => [ 'optin_status' => 2 ],
		] );

		$this->admin( true );
		$items = $this->request( 'GET', '/gh/v4/searches' )->get_data()['items'];
		$by_id = array_column( $items, null, 'id' );
		$this->assertSame( [ 'email_like' => 'jane' ], $by_id['pii-search']['query'] );

		$this->admin( false );
		$items = $this->request( 'GET', '/gh/v4/searches' )->get_data()['items'];
		$by_id = array_column( $items, null, 'id' );
		$this->assertSame( [ 'redacted' => true ], $by_id['pii-search']['query'] );
		$this->assertSame( [ 'optin_status' => 2 ], $by_id['safe-search']['query'] );

		$single = $this->request( 'GET', '/gh/v4/searches/pii-search' )->get_data()['item'];
		$this->assertSame( [ 'redacted' => true ], $single['query'] );

		Saved_Searches::instance()->delete( 'pii-search' );
		Saved_Searches::instance()->delete( 'safe-search' );
	}

	public function test_restricted_user_cannot_save_a_search_on_pii() {
		$this->admin( false );

		$this->assertRestricted( $this->request( 'POST', '/gh/v4/searches', [], [
			'name'  => 'Janes',
			'query' => [ 'email_like' => 'jane' ],
		] ) );

		$response = $this->request( 'POST', '/gh/v4/searches', [], [
			'name'  => 'Opted in',
			'query' => [ 'optin_status' => 2 ],
		] );

		$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );

		Saved_Searches::instance()->delete( $response->get_data()['item']['id'] );
	}

	protected function create_note( string $content ) {

		$note = new Note();
		$note->create( [
			'object_type' => 'contact',
			'object_id'   => $this->contact_id,
			'content'     => $content,
		] );

		return $note;
	}

	protected function create_task( string $summary, string $content ) {

		$task = new Task();
		$task->create( [
			'object_type' => 'contact',
			'object_id'   => $this->contact_id,
			'summary'     => $summary,
			'content'     => $content,
			'due_date'    => gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS ),
		] );

		return $task;
	}

	public function test_restricted_user_reads_a_note_without_the_contacts_details() {

		$this->admin( true );
		$note = $this->create_note( 'Jane Doe called from 555-0100, jane.doe@example.com, about renewal' );

		$response = $this->request( 'GET', '/gh/v4/notes/' . $note->get_id() );
		$item     = json_decode( wp_json_encode( $response->get_data() ), true )['item'];
		$this->assertStringContainsString( 'Jane Doe', $item['data']['content'] );

		$this->admin( false );

		$response = $this->request( 'GET', '/gh/v4/notes/' . $note->get_id() );
		$item     = json_decode( wp_json_encode( $response->get_data() ), true )['item'];

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( '[redacted] called from [redacted], [redacted], about renewal', $item['data']['content'] );
	}

	public function test_restricted_user_reads_a_task_without_the_contacts_details() {

		$this->admin( true );
		$task = $this->create_task( 'Call Jane Doe', 'Her number is 555-0100' );
		$task->update( [ 'complete' => 1, 'note' => 'Reached jane.doe@example.com, she will renew', 'outcome' => 'Jane agreed' ] );

		$this->admin( false );

		$response = $this->request( 'GET', '/gh/v4/tasks/' . $task->get_id() );
		$item     = json_decode( wp_json_encode( $response->get_data() ), true )['item'];

		$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$this->assertSame( 'Call [redacted]', $item['data']['summary'] );
		$this->assertSame( 'Her number is [redacted]', $item['data']['content'] );
		$this->assertSame( 'Contact #' . $this->contact_id, $item['associated']['name'] );
		$this->assertNotEmpty( $item['activity'] );
		$this->assertStringContainsString( 'she will renew', wp_json_encode( $item['activity'] ) );
		$this->assertNoPii( $response );
	}

	public function test_restricted_user_cannot_search_notes_and_tasks() {
		$this->admin( false );

		$this->assertRestricted( $this->request( 'GET', '/gh/v4/notes', [ 'search' => 'jane' ] ) );
		$this->assertRestricted( $this->request( 'GET', '/gh/v4/notes', [ 'content' => 'jane' ] ) );
		$this->assertRestricted( $this->request( 'GET', '/gh/v4/tasks', [ 'search' => 'jane' ] ) );
		$this->assertRestricted( $this->request( 'GET', '/gh/v4/tasks', [ 'query' => [ 'summary' => 'jane' ] ] ) );
	}

	protected function create_submission(): Submission {

		$submission = new Submission();
		$submission->create( [
			'type'       => 'custom',
			'name'       => 'Survey',
			'contact_id' => $this->contact_id,
		] );

		$submission->add_posted_data( [ 'best_phone' => '555-0100', 'comment' => 'Please email jane.doe@example.com' ] );

		return $submission;
	}

	public function test_a_restricted_user_gets_a_submission_without_the_answers() {

		$this->admin( true );
		$submission = $this->create_submission();
		$array      = json_decode( wp_json_encode( $submission->get_as_array() ), true );

		$this->assertSame( '555-0100', $array['meta']['best_phone'] );
		$this->assertNotEmpty( $array['i18n']['answers'] );

		$this->admin( false );
		$array = json_decode( wp_json_encode( $submission->get_as_array() ), true );

		$this->assertSame( $submission->get_id(), $array['ID'] );
		$this->assertSame( 'Survey', $array['data']['name'] );
		$this->assertEmpty( $array['meta'] );
		$this->assertEmpty( $array['i18n']['answers'] );
		$this->assertStringNotContainsString( '555-0100', wp_json_encode( $array ) );
	}

	public function test_restricted_user_reads_a_submission_over_rest_without_the_answers() {

		$this->admin( true );
		$submission = $this->create_submission();

		$response = $this->request( 'GET', '/gh/v4/submissions/' . $submission->get_id() );
		$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$this->assertSame( '555-0100', json_decode( wp_json_encode( $response->get_data() ), true )['item']['meta']['best_phone'] );

		$this->admin( false );

		$response = $this->request( 'GET', '/gh/v4/submissions/' . $submission->get_id() );
		$item     = json_decode( wp_json_encode( $response->get_data() ), true )['item'];

		$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$this->assertEmpty( $item['meta'] );
		$this->assertEmpty( $item['i18n']['answers'] );
		$this->assertNoPii( $response );
	}

	public function test_restricted_user_gets_the_submission_in_the_timeline_without_the_answers() {

		$this->admin( true );
		$this->create_submission();

		$this->admin( false );

		$response = $this->request( 'GET', "/gh/v4/contacts/{$this->contact_id}/timeline" );

		$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$this->assertStringContainsString( 'Survey', wp_json_encode( $response->get_data() ) );
		$this->assertNoPii( $response );
	}

	public function test_restricted_user_cannot_search_submissions_by_their_answers() {
		$this->admin( false );

		$this->assertRestricted( $this->request( 'GET', '/gh/v4/submissions', [ 'meta_key' => 'best_phone', 'meta_value' => '555-0100' ] ) );
		$this->assertRestricted( $this->request( 'GET', '/gh/v4/submissions', [ 'search' => 'jane' ] ) );
		$this->assertRestricted( $this->request( 'GET', '/gh/v4/submissions', [ 'query' => [ 'meta_query' => [ [ 'key' => 'comment', 'value' => 'jane', 'compare' => 'LIKE' ] ] ] ] ) );

		$this->assertSame( 200, $this->request( 'GET', '/gh/v4/submissions', [ 'contact_id' => $this->contact_id ] )->get_status() );
	}

	public function test_no_user_is_not_restricted_over_rest() {
		wp_set_current_user( 0 );

		$contact = get_contactdata( $this->contact_id );
		$array   = $contact->get_as_array();

		$this->assertSame( self::EMAIL, $array['data']['email'] );
	}
}
