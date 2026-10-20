<?php

use Groundhogg\Mobile\Manifest;
use Groundhogg\Mobile\Recent;

/**
 * What a person used last (the template they sent, the form they submitted, the tag they applied), kept per user so the
 * mobile app can put it at the top of the list next time.
 */
class Mobile_Recent_Tests extends GH_UnitTestCase {

	public function tearDown(): void {
		remove_all_filters( 'groundhogg/mobile/recent_kinds' );
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	protected function user( string $role = 'sales_rep' ): int {
		return self::factory()->user->create( [ 'role' => $role ] );
	}

	protected function ids( int $user, string $kind ): array {
		return array_column( Recent::get( $user )[ $kind ], 'id' );
	}

	/* ---- recording --------------------------------------------------------------------------------------- */

	public function test_what_was_used_last_comes_first() {
		$user = $this->user();

		Recent::record( $user, 'form', [ 'id' => 1, 'name' => 'Intake' ] );
		Recent::record( $user, 'form', [ 'id' => 2, 'name' => 'Site visit' ] );
		Recent::record( $user, 'form', [ 'id' => 3, 'name' => 'Follow up' ] );

		$this->assertSame( [ 3, 2, 1 ], $this->ids( $user, 'form' ) );
	}

	public function test_using_something_again_moves_it_to_the_front_without_a_duplicate() {
		$user = $this->user();

		foreach ( [ 1, 2, 3 ] as $id ) {
			Recent::record( $user, 'email_template', [ 'id' => $id, 'name' => "T$id" ] );
		}

		// used again, under a new name
		Recent::record( $user, 'email_template', [ 'id' => 1, 'name' => 'T1 renamed' ] );

		$this->assertSame( [ 1, 3, 2 ], $this->ids( $user, 'email_template' ) );
		$this->assertSame( 'T1 renamed', Recent::get( $user )['email_template'][0]['name'] );

		// and it is stored once, not once more each time it is used
		$stored = get_user_meta( $user, Recent::META_KEY, true );
		$this->assertSame( [ 1, 3, 2 ], array_column( $stored['email_template'], 'id' ) );
	}

	public function test_only_the_most_recent_few_are_kept() {
		$user = $this->user();

		for ( $id = 1; $id <= Recent::MAX + 5; $id ++ ) {
			Recent::record( $user, 'tag', [ 'id' => $id, 'name' => "Tag $id" ] );
		}

		$ids = $this->ids( $user, 'tag' );

		$this->assertCount( Recent::MAX, $ids );
		$this->assertSame( Recent::MAX + 5, $ids[0] );
		$this->assertNotContains( 1, $ids );
	}

	public function test_the_kinds_are_kept_apart_and_so_are_the_users() {
		$a = $this->user();
		$b = $this->user();

		Recent::record( $a, 'form', [ 'id' => 5, 'name' => 'A form' ] );
		Recent::record( $a, 'tag', [ 'id' => 6, 'name' => 'A tag' ] );

		$this->assertSame( [ 5 ], $this->ids( $a, 'form' ) );
		$this->assertSame( [ 6 ], $this->ids( $a, 'tag' ) );
		$this->assertSame( [], $this->ids( $a, 'email_template' ) );
		$this->assertSame( [], $this->ids( $b, 'form' ) );
	}

	public function test_the_name_and_second_line_are_kept_and_cleaned() {
		$user = $this->user();

		Recent::record( $user, 'email_template', [
			'id'   => 9,
			'name' => '<b>Pricing</b> <script>alert(1)</script>',
			'sub'  => str_repeat( 'x', 500 ),
		] );

		$item = Recent::get( $user )['email_template'][0];

		$this->assertStringNotContainsString( '<', $item['name'] );
		$this->assertStringNotContainsString( 'script', $item['name'] );
		$this->assertSame( Recent::MAX_TEXT, mb_strlen( $item['sub'] ) );
	}

	public function test_things_that_are_not_an_item_or_a_kind_are_refused() {
		$user = $this->user();

		$this->assertFalse( Recent::record( $user, 'form', [ 'name' => 'No id' ] ) );
		$this->assertFalse( Recent::record( $user, 'form', [ 'id' => 'abc' ] ) );
		$this->assertFalse( Recent::record( $user, 'form', [ 'id' => 0 ] ) );
		$this->assertFalse( Recent::record( $user, 'not_a_kind', [ 'id' => 1 ] ) );
		$this->assertFalse( Recent::record( 0, 'form', [ 'id' => 1 ] ) );
		$this->assertSame( [], $this->ids( $user, 'form' ) );
	}

	public function test_forgetting_removes_one() {
		$user = $this->user();

		Recent::record( $user, 'form', [ 'id' => 1, 'name' => 'a' ] );
		Recent::record( $user, 'form', [ 'id' => 2, 'name' => 'b' ] );
		Recent::forget( $user, 'form', 1 );

		$this->assertSame( [ 2 ], $this->ids( $user, 'form' ) );
	}

	public function test_damaged_stored_data_is_read_safely() {
		$user = $this->user();

		update_user_meta( $user, Recent::META_KEY, [
			'form' => [ 'nonsense', [ 'id' => 'x' ], [ 'id' => 4, 'name' => 'Fine' ], [ 'id' => 4, 'name' => 'Duplicate' ], null ],
			'tag'  => 'not a list',
		] );

		$this->assertSame( [ 4 ], $this->ids( $user, 'form' ) );
		$this->assertSame( [], $this->ids( $user, 'tag' ) );

		update_user_meta( $user, Recent::META_KEY, 'garbage' );

		$this->assertSame( [], $this->ids( $user, 'form' ) );
	}

	public function test_addons_can_add_a_kind() {
		add_filter( 'groundhogg/mobile/recent_kinds', function ( $kinds ) {
			$kinds[] = 'pipeline';

			return $kinds;
		} );

		$user = $this->user();

		$this->assertTrue( Recent::record( $user, 'pipeline', [ 'id' => 3, 'name' => 'Sales' ] ) );
		$this->assertSame( [ 3 ], $this->ids( $user, 'pipeline' ) );
	}

	/* ---- the manifest ------------------------------------------------------------------------------------ */

	public function test_the_manifest_carries_the_current_users_recents() {
		$user = $this->user();
		Recent::record( $user, 'form', [ 'id' => 7, 'name' => 'Intake', 'sub' => 'Front desk' ] );

		wp_set_current_user( $user );

		$recent = Manifest::build()['recent'];

		$this->assertSame( [ [ 'id' => 7, 'name' => 'Intake', 'sub' => 'Front desk' ] ], $recent['form'] );
		$this->assertSame( [], $recent['tag'] );

		wp_set_current_user( $this->user() );

		$this->assertSame( [], Manifest::build()['recent']['form'] );
	}

	/* ---- the endpoint ------------------------------------------------------------------------------------ */

	protected function post( string $kind, array $body ) {
		$request = new WP_REST_Request( 'POST', "/gh/v4/mobile/recent/$kind" );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( $body ) );

		return rest_do_request( $request );
	}

	public function test_the_endpoint_records_a_use_for_the_signed_in_person_only() {
		$user = $this->user();
		wp_set_current_user( $user );

		$response = $this->post( 'form', [ 'id' => 12, 'name' => 'Intake', 'sub' => '' ] );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( [ 12 ], array_column( $response->get_data()['items'], 'id' ) );
		$this->assertSame( [ 12 ], $this->ids( $user, 'form' ) );
		$this->assertSame( [], $this->ids( $this->user(), 'form' ) );
	}

	public function test_the_endpoint_refuses_a_bad_kind_and_a_missing_id() {
		wp_set_current_user( $this->user() );

		$this->assertSame( 404, $this->post( 'nonsense', [ 'id' => 1 ] )->get_status() );
		$this->assertSame( 400, $this->post( 'form', [ 'name' => 'No id' ] )->get_status() );
	}

	public function test_the_endpoint_needs_someone_who_can_use_the_app() {
		$this->assertContains( $this->post( 'form', [ 'id' => 1 ] )->get_status(), [ 401, 403 ] );

		wp_set_current_user( $this->user( 'subscriber' ) );

		$this->assertContains( $this->post( 'form', [ 'id' => 1 ] )->get_status(), [ 401, 403 ] );
	}

	public function test_the_endpoint_forgets_one() {
		$user = $this->user();
		wp_set_current_user( $user );

		Recent::record( $user, 'tag', [ 'id' => 1, 'name' => 'a' ] );
		Recent::record( $user, 'tag', [ 'id' => 2, 'name' => 'b' ] );

		$response = rest_do_request( new WP_REST_Request( 'DELETE', '/gh/v4/mobile/recent/tag/1' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( [ 2 ], $this->ids( $user, 'tag' ) );
	}
}
