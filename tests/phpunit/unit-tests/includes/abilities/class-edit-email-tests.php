<?php

use Groundhogg\Abilities\Emails\Email_Block_Document;
use Groundhogg\Email;
use function Groundhogg\get_edit_version;

// for its example content
if ( ! class_exists( 'Email_Block_Document_Tests', false ) ) {
	require_once __DIR__ . '/class-email-block-document-tests.php';
}

/**
 * groundhogg/edit-email, groundhogg/get-email-template's outline, and how groundhogg/create-email-template and
 * groundhogg/update-email-template check block content
 */
class Edit_Email_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();

		foreach ( [ 'edit-email', 'get-email-template', 'create-email-template', 'update-email-template' ] as $ability ) {
			if ( ! function_exists( 'wp_get_ability' ) || ! wp_get_ability( "groundhogg/$ability" ) ) {
				$this->markTestSkipped( "The groundhogg/$ability ability is not registered." );
			}
		}

		$this->factory()->truncate();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	protected function run_ability( string $name, array $input ) {
		$out = wp_get_ability( "groundhogg/$name" )->execute( $input );

		return is_wp_error( $out ) ? $out : json_decode( wp_json_encode( $out ), true );
	}

	protected function create_newsletter(): int {

		$out = $this->run_ability( 'create-email-template', [
			'subject' => 'Fall sale',
			'content' => Email_Block_Document_Tests::newsletter(),
			'editor'  => 'blocks',
		] );

		$this->assertNotWPError( $out );

		return $out['id'];
	}

	protected function edit( int $id, array $operations, array $input = [] ) {
		return $this->run_ability( 'edit-email', array_merge( [ 'email_id' => $id, 'operations' => $operations ], $input ) );
	}

	protected function stored_content( int $id ): string {
		return ( new Email( $id ) )->get_content();
	}

	public function test_create_checks_the_blocks_and_returns_no_content() {

		$out = $this->run_ability( 'create-email-template', [
			'subject' => 'Fall sale',
			'content' => Email_Block_Document_Tests::newsletter(),
			'editor'  => 'blocks',
		] );

		$this->assertNotWPError( $out );
		$this->assertArrayNotHasKey( 'content', $out );
		$this->assertArrayNotHasKey( 'plain_text', $out );
		$this->assertSame( 8, $out['block_count'] );
		$this->assertNotEmpty( $out['version'] );

		$email = new Email( $out['id'] );

		// what's stored still parses, after Email's own sanitizing
		$this->assertTrue( ( new Email_Block_Document( $email->get_content() ) )->is_valid() );
		$this->assertStringContainsString( '<!--text:intro {}-->', $email->plain_text );
		$this->assertStringContainsString( 'fall sale', $email->plain_text );
	}

	public function test_create_refuses_broken_blocks_and_creates_nothing() {

		global $wpdb;

		$before = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gh_emails" );

		$out = $this->run_ability( 'create-email-template', [
			'subject' => 'Broken',
			'content' => '<table><tr><!--text:a {"p":}--><td id="b-a">x</td><!--/text:a--></tr></table>',
			'editor'  => 'blocks',
		] );

		$this->assertWPError( $out );
		$this->assertSame( 'groundhogg_invalid_blocks', $out->get_error_code() );
		$this->assertStringContainsString( 'valid JSON', $out->get_error_message() );
		$this->assertSame( $before, (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gh_emails" ) );
	}

	public function test_update_rebuilds_the_plain_text_from_new_content() {

		$id = $this->create_newsletter();

		$out = $this->run_ability( 'update-email-template', [
			'email_id' => $id,
			'editor'   => 'blocks',
			'content'  => Email_Block_Document_Tests::email( [ Email_Block_Document_Tests::block( 'text', 'only', '<p>Brand new</p>' ) ] ),
		] );

		$this->assertNotWPError( $out );
		$this->assertArrayNotHasKey( 'content', $out );
		$this->assertSame( 1, $out['block_count'] );

		$plain = ( new Email( $id ) )->plain_text;

		$this->assertStringContainsString( 'Brand new', $plain );
		$this->assertStringNotContainsString( 'fall sale', $plain );
	}

	public function test_update_takes_an_expected_version() {

		$id = $this->create_newsletter();

		$out = $this->run_ability( 'update-email-template', [ 'email_id' => $id, 'subject' => 'x', 'expected_version' => 'stale' ] );

		$this->assertWPError( $out );
		$this->assertSame( 'groundhogg_email_changed', $out->get_error_code() );

		$out = $this->run_ability( 'update-email-template', [ 'email_id' => $id, 'subject' => 'x', 'expected_version' => get_edit_version( new Email( $id ) ) ] );

		$this->assertNotWPError( $out );
	}

	public function test_get_returns_an_outline_by_default_and_blocks_on_request() {

		$id  = $this->create_newsletter();
		$out = $this->run_ability( 'get-email-template', [ 'email_id' => $id, 'blocks' => [ 'cta' ] ] );

		$this->assertNotWPError( $out );
		$this->assertArrayNotHasKey( 'content', $out );
		$this->assertArrayNotHasKey( 'plain_text', $out );
		$this->assertSame( strlen( $this->stored_content( $id ) ), $out['content_length'] );
		$this->assertSame( get_edit_version( new Email( $id ) ), $out['version'] );

		$this->assertSame( [ 'banner', 'intro', 'row', 'left-img', 'left-text', 'right-text', 'cta', 'foot' ], array_column( $out['outline'], 'id' ) );

		$this->assertStringStartsWith( '<!--button:cta ', $out['blocks']['cta'] );
		$this->assertStringEndsWith( '<!--/button:cta-->', $out['blocks']['cta'] );

		$this->assertWPError( $this->run_ability( 'get-email-template', [ 'email_id' => $id, 'blocks' => [ 'nope' ] ] ) );
	}

	public function test_get_full_returns_everything() {

		$id  = $this->create_newsletter();
		$out = $this->run_ability( 'get-email-template', [ 'email_id' => $id, 'view' => 'full' ] );

		$this->assertSame( $this->stored_content( $id ), $out['content'] );
		$this->assertArrayHasKey( 'plain_text', $out );
		$this->assertArrayNotHasKey( 'outline', $out );
	}

	public function test_get_returns_the_content_of_an_html_email() {

		$out = $this->run_ability( 'create-email-template', [ 'subject' => 'Plain', 'content' => '<p>Hello</p>', 'editor' => 'html' ] );
		$out = $this->run_ability( 'get-email-template', [ 'email_id' => $out['id'] ] );

		$this->assertSame( '<p>Hello</p>', $out['content'] );
		$this->assertArrayNotHasKey( 'outline', $out );
	}

	public function test_edit_applies_operations_in_order_and_saves() {

		$id      = $this->create_newsletter();
		$version = get_edit_version( new Email( $id ) );

		$out = $this->edit( $id, [
			[ 'op' => 'replace_text', 'find' => 'ends Friday', 'replace' => 'ends Sunday' ],
			[ 'op' => 'add', 'at' => [ 'after' => 'intro' ], 'html' => Email_Block_Document_Tests::block( 'spacer', 'gap', '', '{"height":20}' ) ],
			[ 'op' => 'set_content', 'block' => 'right-text', 'html' => '<p>Scarves</p>' ],
			[ 'op' => 'move', 'block' => 'banner', 'at' => [ 'position' => 'end' ] ],
			[ 'op' => 'delete', 'block' => 'left-img' ],
		], [ 'expected_version' => $version ] );

		$this->assertNotWPError( $out );
		$this->assertFalse( $out['dry_run'] );
		$this->assertSame( [ 'intro', 'gap', 'right-text', 'banner' ], array_column( $out['changed'], 'id' ) );
		$this->assertSame( [ 'left-img' ], $out['deleted'] );
		$this->assertSame( 8, $out['blocks'] );
		$this->assertNotSame( $version, $out['version'] );
		$this->assertSame( get_edit_version( new Email( $id ) ), $out['version'] );

		$email = new Email( $id );
		$doc   = new Email_Block_Document( $email->get_content() );

		$this->assertTrue( $doc->is_valid(), implode( "\n", $doc->errors() ) );
		$this->assertSame( [ 'intro', 'gap', 'row', 'left-text', 'right-text', 'cta', 'foot', 'banner' ], $doc->ids() );
		$this->assertStringContainsString( 'ends Sunday', $email->get_content() );
		$this->assertStringContainsString( 'ends Sunday', $email->plain_text );
		$this->assertStringContainsString( 'Scarves', $email->plain_text );
	}

	public function test_edit_leaves_the_blocks_it_does_not_touch_as_they_were() {

		$id     = $this->create_newsletter();
		$before = new Email_Block_Document( $this->stored_content( $id ) );

		$this->assertNotWPError( $this->edit( $id, [ [ 'op' => 'set_content', 'block' => 'intro', 'html' => '<p>Changed</p>' ] ] ) );

		$after = new Email_Block_Document( $this->stored_content( $id ) );

		foreach ( [ 'banner', 'row', 'cta', 'foot' ] as $id ) {
			$this->assertSame( $before->markup( $id ), $after->markup( $id ), $id );
		}
	}

	public function test_edit_is_all_or_nothing() {

		$id     = $this->create_newsletter();
		$before = $this->stored_content( $id );

		$out = $this->edit( $id, [
			[ 'op' => 'replace_text', 'find' => 'ends Friday', 'replace' => 'ends Sunday' ],
			[ 'op' => 'delete', 'block' => 'nope' ],
		] );

		$this->assertWPError( $out );
		$this->assertSame( 'groundhogg_block_not_found', $out->get_error_code() );
		$this->assertStringContainsString( 'Operation 2 ("delete")', $out->get_error_message() );
		$this->assertSame( $before, $this->stored_content( $id ) );
	}

	public function test_dry_run_saves_nothing() {

		$id     = $this->create_newsletter();
		$before = $this->stored_content( $id );

		$out = $this->edit( $id, [ [ 'op' => 'delete', 'block' => 'row' ] ], [ 'dry_run' => true ] );

		$this->assertNotWPError( $out );
		$this->assertTrue( $out['dry_run'] );
		$this->assertSame( [ 'row', 'left-img', 'left-text', 'right-text' ], $out['deleted'] );
		$this->assertSame( $before, $this->stored_content( $id ) );
	}

	public function test_edit_refuses_a_stale_version() {

		$id = $this->create_newsletter();

		$out = $this->edit( $id, [ [ 'op' => 'delete', 'block' => 'row' ] ], [ 'expected_version' => 'stale' ] );

		$this->assertWPError( $out );
		$this->assertSame( 'groundhogg_email_changed', $out->get_error_code() );
	}

	public function test_edit_refuses_an_email_someone_has_open() {

		$id = $this->create_newsletter();

		$other = self::factory()->user->create( [ 'role' => 'administrator' ] );

		( new Email( $id ) )->update_meta( '_edit_lock', time() . ':' . $other );

		$out = $this->edit( $id, [ [ 'op' => 'delete', 'block' => 'row' ] ] );

		$this->assertWPError( $out );
		$this->assertSame( 'groundhogg_email_locked', $out->get_error_code() );
	}

	public function test_edit_refuses_an_html_email() {

		$out = $this->run_ability( 'create-email-template', [ 'subject' => 'Plain', 'content' => '<p>Hello</p>', 'editor' => 'html' ] );
		$out = $this->edit( $out['id'], [ [ 'op' => 'replace_text', 'find' => 'Hello', 'replace' => 'Hi' ] ] );

		$this->assertWPError( $out );
		$this->assertSame( 'groundhogg_email_not_blocks', $out->get_error_code() );
	}

	public function test_edit_checks_each_operation_has_what_it_needs() {

		$id = $this->create_newsletter();

		$out = $this->edit( $id, [ [ 'op' => 'move', 'block' => 'cta' ] ] );

		$this->assertWPError( $out );
		$this->assertStringContainsString( '`at`', $out->get_error_message() );

		$out = $this->edit( $id, [ [ 'op' => 'move', 'block' => 'cta', 'at' => [ 'after' => 'intro', 'before' => 'row' ] ] ] );

		$this->assertWPError( $out );
		$this->assertSame( 'groundhogg_invalid_position', $out->get_error_code() );
	}

	public function test_edit_keeps_content_that_needs_sanitizing_safe() {

		$id = $this->create_newsletter();

		$out = $this->edit( $id, [ [ 'op' => 'set_content', 'block' => 'intro', 'html' => '<p>Hi</p><script>alert(1)</script>' ] ] );

		$this->assertNotWPError( $out );
		$this->assertStringNotContainsString( '<script', $this->stored_content( $id ) );
		$this->assertTrue( ( new Email_Block_Document( $this->stored_content( $id ) ) )->is_valid() );
	}
}
