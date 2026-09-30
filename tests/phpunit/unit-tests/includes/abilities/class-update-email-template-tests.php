<?php

use Groundhogg\Email;
use function Groundhogg\get_db;

/**
 * groundhogg/update-email-template, and that an edit is stamped with last_updated
 */
class Update_Email_Template_Tests extends GH_UnitTestCase {

	const OLD = '2020-01-01 00:00:00';

	public function setUp(): void {
		parent::setUp();

		if ( ! function_exists( 'wp_get_ability' ) || ! wp_get_ability( 'groundhogg/update-email-template' ) ) {
			$this->markTestSkipped( 'The groundhogg/update-email-template ability is not registered.' );
		}

		$this->factory()->truncate();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * An email last updated long ago
	 *
	 * @return int
	 */
	protected function old_email(): int {

		$email = new Email( [ 'subject' => 'subject', 'title' => 'title', 'content' => '<p>content</p>' ] );

		$this->assertTrue( $email->exists() );

		$this->set_last_updated( $email->get_id(), self::OLD );

		return $email->get_id();
	}

	protected function set_last_updated( int $email_id, string $when ) {

		// through Email, so its cached row matches the table
		( new Email( $email_id ) )->update( [ 'last_updated' => $when ] );
	}

	/**
	 * What's stored, not what a loaded Email has
	 */
	protected function stored_last_updated( int $email_id ): string {
		global $wpdb;

		return (string) $wpdb->get_var( $wpdb->prepare( "SELECT last_updated FROM {$wpdb->prefix}gh_emails WHERE ID = %d", $email_id ) );
	}

	protected function update( int $email_id, array $fields ) {
		$out = wp_get_ability( 'groundhogg/update-email-template' )->execute( array_merge( [ 'email_id' => $email_id ], $fields ) );

		$this->assertNotWPError( $out );

		return json_decode( wp_json_encode( $out ), true );
	}

	protected function assert_just_updated( int $email_id, string $message = '' ) {
		$updated = strtotime( $this->stored_last_updated( $email_id ) );

		$this->assertGreaterThan( strtotime( self::OLD ), $updated, $message );
		$this->assertLessThan( 60, abs( $updated - strtotime( current_time( 'mysql' ) ) ), $message );
	}

	public function test_changing_a_column_updates_last_updated() {
		$id = $this->old_email();

		$out = $this->update( $id, [ 'subject' => 'changed' ] );

		$this->assertEquals( 'changed', ( new Email( $id ) )->subject );
		$this->assert_just_updated( $id );
		$this->assertEquals( $this->stored_last_updated( $id ), $out['last_updated'] );
	}

	public function test_changing_only_the_meta_updates_last_updated() {
		$id = $this->old_email();

		$this->update( $id, [ 'template_settings' => [ 'width' => 500 ] ] );

		$this->assertEquals( 500, ( new Email( $id ) )->get_meta( 'width' ) );
		$this->assert_just_updated( $id );
	}

	public function test_changing_only_the_campaigns_updates_last_updated() {
		$id       = $this->old_email();
		$campaign = get_db( 'campaigns' )->add( [ 'name' => 'Campaign', 'slug' => 'campaign' ] );

		$this->assertNotEmpty( $campaign );

		$this->update( $id, [ 'campaigns' => [ $campaign ] ] );

		$this->assert_just_updated( $id );
	}

	public function test_content_and_editor_update_last_updated() {
		$id = $this->old_email();

		$this->update( $id, [ 'content' => '<p>new</p>', 'editor' => 'html' ] );

		$this->assert_just_updated( $id );
	}

	public function test_a_refused_update_leaves_last_updated_alone() {
		$id = $this->old_email();

		// content without editor
		$out = wp_get_ability( 'groundhogg/update-email-template' )->execute( [ 'email_id' => $id, 'content' => '<p>new</p>' ] );

		$this->assertWPError( $out );
		$this->assertEquals( self::OLD, $this->stored_last_updated( $id ) );
	}
}
