<?php

use Groundhogg\Api\V4\Emails_Api;
use Groundhogg\Email;
use function Groundhogg\get_edit_version;

/**
 * A save from the email editor sends the version it has, so it doesn't replace what something else changed while the
 * editor was open, like an AI agent.
 *
 * @see Emails_Api::update_single()
 */
class Emails_Update_Version_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	protected function create_email(): Email {

		$email = new Email();
		$email->create( [
			'title'   => 'Version test',
			'subject' => 'Version test',
			'content' => '<p>Before</p>',
			'status'  => 'draft',
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

	protected function content( Email $email ): string {
		return ( new Email( $email->get_id() ) )->get_content();
	}

	public function test_saves_with_the_current_version_and_returns_the_new_one() {

		$email   = $this->create_email();
		$version = get_edit_version( $email );

		$response = $this->update( $email, [
			'data'    => [ 'content' => '<p>Mine</p>' ],
			'version' => $version,
		] );

		$this->assertNotWPError( $response );
		$this->assertSame( '<p>Mine</p>', $this->content( $email ) );

		$data = $response->get_data();

		$this->assertSame( get_edit_version( $email ), $data['version'] );
		$this->assertNotSame( $version, $data['version'] );
	}

	public function test_refuses_a_save_when_the_email_changed_after_the_version() {

		$email   = $this->create_email();
		$version = get_edit_version( $email );

		// an AI agent changes it while the editor is open
		( new Email( $email->get_id() ) )->update( [ 'content' => '<p>Theirs</p>' ] );

		$response = $this->update( $email, [
			'data'    => [ 'content' => '<p>Mine</p>' ],
			'version' => $version,
		] );

		$this->assertWPError( $response );
		$this->assertSame( 'groundhogg_email_changed', $response->get_error_code() );
		$this->assertSame( 409, $response->get_error_data()['status'] );
		$this->assertSame( get_edit_version( $email ), $response->get_error_data()['data']['version'] );
		$this->assertSame( '<p>Theirs</p>', $this->content( $email ) );
	}

	public function test_a_meta_change_counts_as_a_change() {

		$email   = $this->create_email();
		$version = get_edit_version( $email );

		( new Email( $email->get_id() ) )->update_meta( 'css', '.theirs { color: red; }' );

		$response = $this->update( $email, [
			'data'    => [ 'content' => '<p>Mine</p>' ],
			'version' => $version,
		] );

		$this->assertWPError( $response );
		$this->assertSame( 'groundhogg_email_changed', $response->get_error_code() );
	}

	public function test_saves_without_a_version_as_before() {

		$email = $this->create_email();

		( new Email( $email->get_id() ) )->update( [ 'content' => '<p>Theirs</p>' ] );

		$response = $this->update( $email, [
			'data' => [ 'content' => '<p>Mine</p>' ],
		] );

		$this->assertNotWPError( $response );
		$this->assertSame( '<p>Mine</p>', $this->content( $email ) );
	}

	public function test_the_returned_version_is_the_one_a_later_save_needs() {

		$email = $this->create_email();

		$first = $this->update( $email, [
			'data'    => [ 'content' => '<p>First</p>' ],
			'version' => get_edit_version( $email ),
		] );

		$second = $this->update( $email, [
			'data'    => [ 'content' => '<p>Second</p>' ],
			'version' => $first->get_data()['version'],
		] );

		$this->assertNotWPError( $second );
		$this->assertSame( '<p>Second</p>', $this->content( $email ) );
	}

	public function test_the_edit_lock_doesnt_change_the_version() {

		$email   = $this->create_email();
		$version = get_edit_version( $email );

		// the editor's heartbeat refreshes the lock while it's open
		\Groundhogg\set_lock( new Email( $email->get_id() ) );

		$response = $this->update( $email, [
			'data'    => [ 'content' => '<p>Mine</p>' ],
			'version' => $version,
		] );

		$this->assertNotWPError( $response );
	}
}
