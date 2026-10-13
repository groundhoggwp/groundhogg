<?php

use Groundhogg\Email;

/**
 * The `css`, `custom_headers` and `utm` inputs of groundhogg/create-email-template and
 * groundhogg/update-email-template
 */
class Email_Extra_Meta_Abilities_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();

		foreach ( [ 'create', 'update', 'get' ] as $ability ) {
			if ( ! function_exists( 'wp_get_ability' ) || ! wp_get_ability( "groundhogg/$ability-email-template" ) ) {
				$this->markTestSkipped( "The groundhogg/$ability-email-template ability is not registered." );
			}
		}

		$this->factory()->truncate();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	protected function run_ability( string $name, array $input ) {
		return wp_get_ability( "groundhogg/$name-email-template" )->execute( $input );
	}

	protected function create( array $input = [] ) {
		return $this->run_ability( 'create', array_merge( [
			'subject' => 'subject',
			'content' => '<p>content</p>',
			'editor'  => 'blocks',
		], $input ) );
	}

	protected function update( int $id, array $input ) {
		return $this->run_ability( 'update', array_merge( [ 'email_id' => $id ], $input ) );
	}

	/**
	 * What's stored, through a fresh Email
	 */
	protected function email( int $id ): Email {
		return new Email( $id );
	}

	protected function email_count(): int {
		global $wpdb;

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gh_emails" );
	}

	public function test_css_is_saved_as_template_css_and_added_to_the_email_css() {

		$out = $this->create( [ 'css' => '@media (max-width:480px){ .big{ font-size:30px } }' ] );

		$this->assertNotWPError( $out );
		$this->assertStringContainsString( '.big', $out['css'] );

		$email = $this->email( $out['id'] );

		$this->assertStringContainsString( '.big', $email->get_meta( 'template_css' ) );
		$this->assertStringContainsString( '.big', $email->get_css() );
	}

	public function test_css_does_not_touch_the_block_editors_own_css() {

		$out   = $this->create( [ 'css' => '.a{color:red}' ] );
		$email = $this->email( $out['id'] );

		$this->assertEmpty( $email->get_meta( 'css' ) );
	}

	public function test_css_is_an_error_for_an_html_email_and_nothing_is_created() {

		$before = $this->email_count();

		$out = $this->create( [ 'editor' => 'html', 'css' => '.a{color:red}' ] );

		$this->assertWPError( $out );
		$this->assertSame( 'groundhogg_css_requires_blocks', $out->get_error_code() );
		$this->assertSame( $before, $this->email_count() );
	}

	public function test_update_css_replaces_and_empty_string_removes_it() {

		$id = $this->create( [ 'css' => '.a{color:red}' ] )['id'];

		$out = $this->update( $id, [ 'css' => '.b{color:blue}' ] );
		$this->assertNotWPError( $out );
		$this->assertSame( '.b{color:blue}', $out['css'] );

		$out = $this->update( $id, [ 'css' => '' ] );
		$this->assertNotWPError( $out );
		$this->assertSame( '', $out['css'] );
	}

	public function test_update_css_on_an_html_email_is_an_error_and_changes_nothing() {

		$id = $this->create( [ 'editor' => 'html' ] )['id'];

		$out = $this->update( $id, [ 'css' => '.a{color:red}', 'subject' => 'changed' ] );

		$this->assertWPError( $out );
		$this->assertSame( 'groundhogg_css_requires_blocks', $out->get_error_code() );
		$this->assertSame( 'subject', $this->email( $id )->subject );
	}

	public function test_update_css_while_switching_the_email_to_html_is_an_error() {

		$id = $this->create()['id'];

		$out = $this->update( $id, [ 'content' => '<p>html</p>', 'editor' => 'html', 'css' => '.a{color:red}' ] );

		$this->assertWPError( $out );
	}

	public function test_update_css_is_allowed_when_the_same_call_switches_the_email_to_blocks() {

		$id = $this->create( [ 'editor' => 'html' ] )['id'];

		$out = $this->update( $id, [ 'content' => '<p>blocks</p>', 'editor' => 'blocks', 'css' => '.a{color:red}' ] );

		$this->assertNotWPError( $out );
		$this->assertSame( '.a{color:red}', $out['css'] );
	}

	public function test_custom_headers_are_saved_and_returned() {

		$out = $this->create( [ 'custom_headers' => [ 'X-Campaign' => 'launch', 'X-Contact' => '{first}' ] ] );

		$this->assertNotWPError( $out );
		$this->assertEquals( (object) [ 'x-campaign' => 'launch', 'x-contact' => '{first}' ], $out['custom_headers'] );
		$this->assertSame( [ 'x-campaign' => 'launch', 'x-contact' => '{first}' ], $this->email( $out['id'] )->get_meta( 'custom_headers' ) );
	}

	public function test_custom_headers_that_change_the_recipients_are_refused() {

		foreach ( [ 'To', 'cc', 'BCC' ] as $name ) {

			$before = $this->email_count();
			$out    = $this->create( [ 'custom_headers' => [ $name => 'someone@example.com' ] ] );

			$this->assertWPError( $out, $name );
			$this->assertSame( 'groundhogg_blocked_header', $out->get_error_code() );
			$this->assertSame( $before, $this->email_count() );
		}
	}

	public function test_custom_header_names_can_only_have_letters_numbers_and_hyphens() {

		foreach ( [ 'X Campaign', "X-Campaign\r\nBcc", 'X_Campaign', '' ] as $name ) {

			$out = $this->create( [ 'custom_headers' => [ $name => 'x' ] ] );

			$this->assertWPError( $out, $name );
			$this->assertSame( 'groundhogg_invalid_header_name', $out->get_error_code() );
		}
	}

	public function test_custom_header_values_cannot_carry_line_breaks() {

		$out = $this->create( [ 'custom_headers' => [ 'X-Campaign' => "launch\r\nBcc: someone@example.com" ] ] );

		$this->assertNotWPError( $out );
		$this->assertStringNotContainsString( "\n", $this->email( $out['id'] )->get_meta( 'custom_headers' )['x-campaign'] );
	}

	public function test_update_custom_headers_replaces_the_set_and_empty_removes_them() {

		$id = $this->create( [ 'custom_headers' => [ 'X-One' => '1', 'X-Two' => '2' ] ] )['id'];

		$out = $this->update( $id, [ 'custom_headers' => [ 'X-Three' => '3' ] ] );
		$this->assertEquals( (object) [ 'x-three' => '3' ], $out['custom_headers'] );

		$out = $this->update( $id, [ 'custom_headers' => (object) [] ] );
		$this->assertNotWPError( $out );
		$this->assertEquals( (object) [], $out['custom_headers'] );
	}

	public function test_update_without_custom_headers_leaves_them_alone() {

		$id = $this->create( [ 'custom_headers' => [ 'X-One' => '1' ] ] )['id'];

		$out = $this->update( $id, [ 'subject' => 'changed' ] );

		$this->assertEquals( (object) [ 'x-one' => '1' ], $out['custom_headers'] );
	}

	public function test_custom_headers_are_sent() {

		$id = $this->create( [ 'custom_headers' => [ 'X-Campaign' => 'launch' ] ] )['id'];

		// get_headers() gives "name: value" lines
		$headers = array_map( 'strtolower', $this->email( $id )->get_headers() );

		$this->assertContains( 'x-campaign: launch', $headers );
	}

	public function test_utm_is_saved_and_returned() {

		$out = $this->create( [
			'utm' => [
				'source'        => 'newsletter',
				'medium'        => 'email',
				'campaign'      => 'launch',
				'term'          => 'crm',
				'content'       => 'button',
				'url_allowlist' => [ 'https://example.com', 'https://shop.example.com/' ],
			],
		] );

		$this->assertNotWPError( $out );
		$this->assertSame( [
			'source'        => 'newsletter',
			'medium'        => 'email',
			'campaign'      => 'launch',
			'term'          => 'crm',
			'content'       => 'button',
			'url_allowlist' => [ 'https://example.com', 'https://shop.example.com/' ],
		], $out['utm'] );
	}

	public function test_utm_is_added_to_links_that_are_in_the_allowlist() {

		$id    = $this->create( [ 'utm' => [ 'source' => 'newsletter', 'url_allowlist' => [ 'https://example.com' ] ] ] )['id'];
		$email = $this->email( $id );

		$html = $email->maybe_add_utm_to_links( '<a href="https://example.com/page">in</a><a href="https://other.com/page">out</a>' );

		$this->assertStringContainsString( 'href="https://example.com/page?utm_source=newsletter"', $html );
		$this->assertStringContainsString( 'href="https://other.com/page"', $html );
	}

	public function test_update_utm_only_changes_what_is_given() {

		$id = $this->create( [ 'utm' => [ 'source' => 'newsletter', 'medium' => 'email', 'url_allowlist' => [ 'https://example.com' ] ] ] )['id'];

		$out = $this->update( $id, [ 'utm' => [ 'medium' => 'social' ] ] );

		$this->assertSame( 'newsletter', $out['utm']['source'] );
		$this->assertSame( 'social', $out['utm']['medium'] );
		$this->assertSame( [ 'https://example.com' ], $out['utm']['url_allowlist'] );
	}

	public function test_update_utm_can_clear_a_value_and_the_allowlist() {

		$id = $this->create( [ 'utm' => [ 'source' => 'newsletter', 'url_allowlist' => [ 'https://example.com' ] ] ] )['id'];

		$out = $this->update( $id, [ 'utm' => [ 'source' => '', 'url_allowlist' => [] ] ] );

		$this->assertNotWPError( $out );
		$this->assertSame( '', $out['utm']['source'] );
		$this->assertSame( [], $out['utm']['url_allowlist'] );
	}

	public function test_utm_allowlist_entries_that_are_not_urls_are_dropped() {

		$out = $this->create( [ 'utm' => [ 'url_allowlist' => [ 'https://example.com', 'javascript:alert(1)', '' ] ] ] );

		$this->assertSame( [ 'https://example.com' ], $out['utm']['url_allowlist'] );
	}

	public function test_get_and_list_return_the_new_fields() {

		$id = $this->create( [
			'css'            => '.a{color:red}',
			'custom_headers' => [ 'X-One' => '1' ],
			'utm'            => [ 'source' => 'newsletter' ],
		] )['id'];

		$got = $this->run_ability( 'get', [ 'email_id' => $id ] );

		$this->assertSame( '.a{color:red}', $got['css'] );
		$this->assertEquals( (object) [ 'x-one' => '1' ], $got['custom_headers'] );
		$this->assertSame( 'newsletter', $got['utm']['source'] );

		$listed = wp_get_ability( 'groundhogg/list-email-templates' )->execute( [ 'expand' => [ 'css' ] ] );

		$this->assertNotWPError( $listed );
		$this->assertSame( '.a{color:red}', $listed['emails'][0]['css'] );
	}

	public function test_list_leaves_css_out_unless_asked_for() {

		$this->create( [ 'css' => '.a{color:red}' ] );

		$listed = wp_get_ability( 'groundhogg/list-email-templates' )->execute( [] );

		$this->assertArrayNotHasKey( 'css', $listed['emails'][0] );
	}
}
