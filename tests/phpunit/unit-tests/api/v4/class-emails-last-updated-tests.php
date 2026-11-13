<?php

use Groundhogg\Api\V4\Emails_Api;
use Groundhogg\Email;

/**
 * The Last Updated of an email is when it was saved or copied, so the emails table can be sorted by it.
 *
 * @see Emails_Api::update_single()
 * @see Email::duplicate()
 */
class Emails_Last_Updated_Tests extends GH_UnitTestCase {

	const OLD = '2020-01-01 10:00:00';

	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * An email that was last updated a long time ago
	 */
	protected function create_old_email(): Email {

		$email = new Email();
		$email->create( [
			'title'        => 'Last updated test',
			'subject'      => 'Last updated test',
			'content'      => '<p>Before</p>',
			'status'       => 'draft',
			'last_updated' => self::OLD,
		] );

		return $email;
	}

	protected function update( Email $email, array $params ) {

		$request = new WP_REST_Request( 'PATCH', '/gh/v4/emails/' . $email->get_id() );
		$request->set_param( 'ID', $email->get_id() );

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		return ( new Emails_Api() )->update_single( $request );
	}

	protected function last_updated( Email $email ): string {
		return ( new Email( $email->get_id() ) )->get_last_updated();
	}

	protected function assertJustNow( string $mysql_date ) {
		$this->assertLessThan( 120, abs( strtotime( current_time( 'mysql' ) ) - strtotime( $mysql_date ) ), $mysql_date );
	}

	public function test_a_new_email_starts_with_the_old_value_it_was_given() {

		$this->assertSame( self::OLD, $this->last_updated( $this->create_old_email() ) );
	}

	public function test_a_save_from_the_editor_sets_it_to_now() {

		$email = $this->create_old_email();

		$response = $this->update( $email, [ 'data' => [ 'content' => '<p>After</p>' ] ] );

		$this->assertNotWPError( $response );
		$this->assertJustNow( $this->last_updated( $email ) );
		$this->assertSame( '<p>After</p>', ( new Email( $email->get_id() ) )->get_content() );
	}

	public function test_a_change_to_only_the_meta_sets_it_to_now() {

		$email = $this->create_old_email();

		$this->assertNotWPError( $this->update( $email, [ 'meta' => [ 'css' => '.mine { color: red; }' ] ] ) );
		$this->assertJustNow( $this->last_updated( $email ) );
	}

	public function test_a_change_to_only_the_campaigns_sets_it_to_now() {

		$email = $this->create_old_email();

		$this->assertNotWPError( $this->update( $email, [ 'campaigns' => [] ] ) );
		$this->assertJustNow( $this->last_updated( $email ) );
	}

	public function test_a_bulk_update_of_a_list_of_emails_sets_it_to_now() {

		$email = $this->create_old_email();
		$other = $this->create_old_email();

		// the editor saves the global blocks like this
		$request = new WP_REST_Request( 'PATCH', '/gh/v4/emails' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( [
			[ 'ID' => $email->get_id(), 'data' => [ 'content' => '<p>Changed</p>' ] ],
			[ 'ID' => $other->get_id(), 'meta' => [ 'css' => '.mine { color: red; }' ] ],
		] ) );

		$response = ( new Emails_Api() )->update( $request );

		$this->assertNotWPError( $response );
		$this->assertSame( 2, $response->get_data()['total_items'] );
		$this->assertJustNow( $this->last_updated( $email ) );
		$this->assertJustNow( $this->last_updated( $other ) );
		$this->assertSame( '<p>Changed</p>', ( new Email( $email->get_id() ) )->get_content() );
	}

	public function test_a_bulk_update_by_query_sets_it_to_now() {

		$email = $this->create_old_email();

		$request = new WP_REST_Request( 'PATCH', '/gh/v4/emails' );
		$request->set_param( 'query', [ 'ID' => $email->get_id() ] );
		$request->set_param( 'data', [ 'status' => 'ready' ] );

		$this->assertNotWPError( ( new Emails_Api() )->update( $request ) );
		$this->assertSame( 'ready', ( new Email( $email->get_id() ) )->get_status() );
		$this->assertJustNow( $this->last_updated( $email ) );
	}

	public function test_a_bulk_update_that_changes_nothing_leaves_it_alone() {

		$email = $this->create_old_email();

		$request = new WP_REST_Request( 'PATCH', '/gh/v4/emails' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( [ [ 'ID' => $email->get_id() ] ] ) );

		$this->assertNotWPError( ( new Emails_Api() )->update( $request ) );
		$this->assertSame( self::OLD, $this->last_updated( $email ) );
	}

	public function test_a_bulk_update_of_another_type_is_left_alone() {

		// the base only hands the data back, it is the emails that add to it
		$tags = new \Groundhogg\Api\V4\Tags_Api();
		$tag  = new \Groundhogg\Tag();
		$tag->create( [ 'tag_name' => 'bulk test' ] );

		$request = new WP_REST_Request( 'PATCH', '/gh/v4/tags' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( [ [ 'tag_id' => $tag->get_id(), 'data' => [ 'tag_description' => 'Described' ] ] ] ) );

		$this->assertNotWPError( $tags->update( $request ) );
		$this->assertSame( 'Described', ( new \Groundhogg\Tag( $tag->get_id() ) )->get_description() );
	}

	public function test_a_save_that_changes_nothing_leaves_it_alone() {

		$email = $this->create_old_email();

		$this->assertNotWPError( $this->update( $email, [] ) );
		$this->assertSame( self::OLD, $this->last_updated( $email ) );
	}

	public function test_the_version_that_comes_back_is_the_one_the_next_save_needs() {

		$email = $this->create_old_email();

		$first = $this->update( $email, [
			'data'    => [ 'content' => '<p>First</p>' ],
			'version' => \Groundhogg\get_edit_version( $email ),
		] );

		$second = $this->update( $email, [
			'data'    => [ 'content' => '<p>Second</p>' ],
			'version' => $first->get_data()['version'],
		] );

		$this->assertNotWPError( $second );
		$this->assertSame( '<p>Second</p>', ( new Email( $email->get_id() ) )->get_content() );
	}

	public function test_a_copy_starts_now_not_when_the_email_it_is_a_copy_of_was_updated() {

		$email = $this->create_old_email();
		$copy  = $email->duplicate();

		$this->assertNotSame( $email->get_id(), $copy->get_id() );
		$this->assertSame( 'draft', $copy->get_status() );
		$this->assertJustNow( $this->last_updated( $copy ) );

		// the email it is a copy of isn't touched
		$this->assertSame( self::OLD, $this->last_updated( $email ) );
	}

	public function test_a_caller_can_still_give_a_copy_its_own_value() {

		$email = $this->create_old_email();
		$copy  = $email->duplicate( [ 'last_updated' => '2021-02-03 04:05:06' ] );

		$this->assertSame( '2021-02-03 04:05:06', $this->last_updated( $copy ) );
	}
}
