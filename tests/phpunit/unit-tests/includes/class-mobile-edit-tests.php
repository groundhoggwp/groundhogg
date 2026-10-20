<?php

/**
 * What the mobile contact editor (core fields and tags) depends on from the REST API, checked as the people that
 * will use it: a sales rep editing their own contacts, and not anyone else's.
 */
class Mobile_Edit_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		$this->reset_tracking_state();
	}

	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	protected function rep(): int {
		return self::factory()->user->create( [ 'role' => 'sales_rep' ] );
	}

	protected function contact_for( int $owner, array $data = [] ) {
		return self::factory()->contacts->create_and_get( array_merge( [ 'owner_id' => $owner ], $data ) );
	}

	protected function patch( int $contact_id, array $body ) {
		$request = new WP_REST_Request( 'PATCH', "/gh/v4/contacts/$contact_id" );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( $body ) );

		return rest_do_request( $request );
	}

	/* ---- fields ------------------------------------------------------------------------------------------ */

	public function test_a_rep_can_edit_the_fields_of_their_own_contact() {
		$rep     = $this->rep();
		$contact = $this->contact_for( $rep, [ 'first_name' => 'Ann' ] );

		wp_set_current_user( $rep );

		$response = $this->patch( $contact->get_id(), [
			'data' => [ 'first_name' => 'Anna' ],
			'meta' => [ 'mobile_phone' => '555 0100', 'company_name' => 'Acme', 'country' => 'CA', 'street_address_1' => '1 Main St' ],
		] );

		$this->assertSame( 200, $response->get_status() );

		$fresh = new \Groundhogg\Contact( $contact->get_id() );

		$this->assertSame( 'Anna', $fresh->first_name );
		// phone numbers are stored without spaces or punctuation, whatever was typed
		$this->assertSame( '5550100', $fresh->get_meta( 'mobile_phone' ) );
		$this->assertSame( 'Acme', $fresh->get_meta( 'company_name' ) );
		$this->assertSame( 'CA', $fresh->get_meta( 'country' ) );

		// the response is the contact as it is now, which is what the app shows without fetching it again
		$item = $response->get_data()['item'];
		$this->assertSame( 'Anna', $item->data['first_name'] ?? $item->first_name );
	}

	public function test_a_field_can_be_cleared() {
		$rep     = $this->rep();
		$contact = $this->contact_for( $rep );
		$contact->update_meta( 'mobile_phone', '555 0100' );

		wp_set_current_user( $rep );

		$this->assertSame( 200, $this->patch( $contact->get_id(), [ 'meta' => [ 'mobile_phone' => '' ] ] )->get_status() );
		$this->assertSame( '', ( new \Groundhogg\Contact( $contact->get_id() ) )->get_meta( 'mobile_phone' ) );
	}

	public function test_a_rep_cannot_edit_another_reps_contact() {
		$rep     = $this->rep();
		$other   = $this->rep();
		$contact = $this->contact_for( $other, [ 'first_name' => 'Ann' ] );

		wp_set_current_user( $rep );

		$this->assertContains( $this->patch( $contact->get_id(), [ 'data' => [ 'first_name' => 'Hacked' ] ] )->get_status(), [ 401, 403 ] );
		$this->assertSame( 'Ann', ( new \Groundhogg\Contact( $contact->get_id() ) )->first_name );
	}

	public function test_an_email_address_already_in_use_is_refused_and_says_so() {
		$rep   = $this->rep();
		$taken = $this->contact_for( $rep, [ 'email' => 'taken@example.test' ] );
		$mine  = $this->contact_for( $rep, [ 'email' => 'mine@example.test' ] );

		wp_set_current_user( $rep );

		$response = $this->patch( $mine->get_id(), [ 'data' => [ 'email' => 'taken@example.test' ] ] );

		$this->assertSame( 409, $response->get_status() );
		$this->assertStringContainsString( 'already in use', $response->get_data()['message'] );
		$this->assertSame( 'mine@example.test', ( new \Groundhogg\Contact( $mine->get_id() ) )->email );
	}

	/* ---- tags -------------------------------------------------------------------------------------------- */

	protected function tag_names( int $contact_id ): array {
		return array_map( function ( $tag ) {
			return $tag->tag_name;
		}, ( new \Groundhogg\Contact( $contact_id ) )->get_tags( true ) );
	}

	public function test_a_rep_can_list_tags_to_pick_from() {
		wp_set_current_user( $this->rep() );

		$response = rest_do_request( new WP_REST_Request( 'GET', '/gh/v4/tags' ) );

		$this->assertSame( 200, $response->get_status() );
	}

	public function test_a_rep_can_add_and_remove_tags_in_one_request() {
		$rep     = $this->rep();
		$contact = $this->contact_for( $rep );

		$keep   = \Groundhogg\get_db( 'tags' )->add( [ 'tag_name' => 'Keep Me', 'tag_slug' => 'keep-me' ] );
		$drop   = \Groundhogg\get_db( 'tags' )->add( [ 'tag_name' => 'Drop Me', 'tag_slug' => 'drop-me' ] );
		$fresh1 = \Groundhogg\get_db( 'tags' )->add( [ 'tag_name' => 'Add Me', 'tag_slug' => 'add-me' ] );

		$contact->apply_tag( [ $keep, $drop ] );

		wp_set_current_user( $rep );

		$response = $this->patch( $contact->get_id(), [ 'add_tags' => [ $fresh1 ], 'remove_tags' => [ $drop ] ] );

		$this->assertSame( 200, $response->get_status() );

		$names = $this->tag_names( $contact->get_id() );
		sort( $names );

		$this->assertSame( [ 'Add Me', 'Keep Me' ], $names );
	}

	public function test_a_tag_typed_by_name_is_created_and_applied() {
		$rep     = $this->rep();
		$contact = $this->contact_for( $rep );

		wp_set_current_user( $rep );

		$this->assertSame( 200, $this->patch( $contact->get_id(), [ 'add_tags' => [ 'Brand New Tag' ] ] )->get_status() );
		$this->assertContains( 'Brand New Tag', $this->tag_names( $contact->get_id() ) );
	}

	public function test_a_rep_cannot_tag_another_reps_contact() {
		$rep     = $this->rep();
		$contact = $this->contact_for( $this->rep() );

		wp_set_current_user( $rep );

		$this->assertContains( $this->patch( $contact->get_id(), [ 'add_tags' => [ 'Nope' ] ] )->get_status(), [ 401, 403 ] );
		$this->assertNotContains( 'Nope', $this->tag_names( $contact->get_id() ) );
	}

	public function test_a_request_with_no_changes_is_refused_not_silently_accepted() {
		$rep     = $this->rep();
		$contact = $this->contact_for( $rep );

		wp_set_current_user( $rep );

		$this->assertContains( $this->patch( $contact->get_id(), [] )->get_status(), [ 400, 401, 422 ] );
	}
}
