<?php

use Groundhogg\Abilities\Emails\Email_Html_Document;
use Groundhogg\Email;
use function Groundhogg\get_edit_version;

/**
 * Changing an email that isn't built with blocks a piece at a time: Email_Html_Document, and groundhogg/edit-email and
 * groundhogg/get-email-template for such emails
 */
class Email_Html_Document_Tests extends GH_UnitTestCase {

	/**
	 * A full HTML email, like one from the HTML editor
	 *
	 * @return string
	 */
	public static function email(): string {
		return '<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Fall sale</title>
<style>
.sale { color: #e33; }
@media (max-width: 600px) { .wrap { width: 100% !important; } }
</style>
</head>
<body>
<!--[if mso]><table><tr><td><![endif]-->
<table class="wrap" width="100%">
<tr><td style="padding: 24px"><h1 class="sale">Fall sale</h1><p>Hi {first::there}, our fall sale ends Sunday.</p><p>Tom &amp; Jerry say: don\'t miss it — 50% off.</p></td></tr>
<tr><td><a href="https://example.com/sale" style="color:#fff">Shop the sale</a></td></tr>
<tr><td><img src="https://example.com/images/banner.png" alt="Fall banner" width="600"></td></tr>
<tr><td style="font-size: 12px">{business_name} &middot; <a href="{unsubscribe_link}">Unsubscribe</a></td></tr>
</table>
<!--[if mso]></td></tr></table><![endif]-->
</body>
</html>';
	}

	protected function doc(): Email_Html_Document {
		return new Email_Html_Document( self::email() );
	}

	public function test_replace_text_changes_only_visible_text() {

		$doc = $this->doc();

		// "sale" is also in a class, a URL and the CSS
		$out = $doc->replace_text( 'Shop the sale', 'Shop now' );

		$this->assertNotWPError( $out );
		$this->assertSame( 1, $out['count'] );
		$this->assertStringContainsString( '>Shop now</a>', $doc->html() );
		$this->assertStringContainsString( 'href="https://example.com/sale"', $doc->html() );
		$this->assertStringContainsString( '.sale { color: #e33; }', $doc->html() );
	}

	public function test_replace_text_leaves_everything_else_byte_for_byte() {

		$doc = $this->doc();
		$doc->replace_text( 'ends Sunday', 'ends Monday' );

		$this->assertSame( str_replace( 'ends Sunday', 'ends Monday', self::email() ), $doc->html() );
	}

	public function test_replace_text_doesnt_match_attributes_css_or_comments() {

		$doc = $this->doc();

		foreach ( [ 'example.com', 'max-width', 'mso', 'padding' ] as $find ) {
			$out = $doc->replace_text( $find, 'x', true );
			$this->assertWPError( $out, $find );
			$this->assertSame( 'groundhogg_text_not_found', $out->get_error_code() );
			$this->assertStringContainsString( 'replace_html', $out->get_error_message(), $find );
		}

		$this->assertSame( self::email(), $doc->html() );
	}

	public function test_replace_text_finds_text_written_with_entities() {

		$doc = $this->doc();
		$out = $doc->replace_text( 'Tom & Jerry', 'Tom & Jerry & Spike' );

		$this->assertNotWPError( $out );
		$this->assertStringContainsString( 'Tom &amp; Jerry &amp; Spike say', $doc->html() );
	}

	public function test_replace_text_needs_one_match_unless_all() {

		$doc = $this->doc();

		// the title and the heading
		$out = $doc->replace_text( 'Fall sale', 'Winter sale' );

		$this->assertWPError( $out );
		$this->assertSame( 'groundhogg_text_not_unique', $out->get_error_code() );

		$out = $doc->replace_text( 'Fall sale', 'Winter sale', true );

		$this->assertSame( 2, $out['count'] );
		$this->assertStringContainsString( '<title>Winter sale</title>', $doc->html() );
		$this->assertStringContainsString( '<h1 class="sale">Winter sale</h1>', $doc->html() );
	}

	public function test_replace_html_replaces_an_exact_piece() {

		$doc = $this->doc();
		$out = $doc->replace_html( 'href="https://example.com/sale"', 'href="https://example.com/fall-sale"' );

		$this->assertNotWPError( $out );
		$this->assertSame( str_replace( 'https://example.com/sale"', 'https://example.com/fall-sale"', self::email() ), $doc->html() );
	}

	public function test_replace_html_matches_with_different_whitespace() {

		$doc = $this->doc();

		// copied with the line break between the rows left out, and spaces added inside a tag
		$out = $doc->replace_html(
			'<tr><td><img  src="https://example.com/images/banner.png" alt="Fall banner" width="600"></td></tr><tr><td style="font-size: 12px">',
			'<tr><td style="font-size: 12px">'
		);

		$this->assertNotWPError( $out );
		$this->assertStringNotContainsString( 'banner.png', $doc->html() );
		$this->assertStringContainsString( '{business_name}', $doc->html() );
	}

	public function test_replace_html_refuses_leaving_tags_open() {

		$doc = $this->doc();
		$out = $doc->replace_html( '<tr><td><a href="https://example.com/sale"', '<tr><td><div><a href="https://example.com/sale"' );

		$this->assertWPError( $out );
		$this->assertSame( 'groundhogg_unbalanced_html', $out->get_error_code() );
		$this->assertStringContainsString( '<div>', $out->get_error_message() );
		$this->assertSame( self::email(), $doc->html() );
	}

	public function test_replace_html_can_replace_a_whole_row() {

		$doc = $this->doc();
		$out = $doc->replace_html(
			'<tr><td><a href="https://example.com/sale" style="color:#fff">Shop the sale</a></td></tr>',
			'<tr><td><a href="https://example.com/sale" style="color:#fff">Shop the sale</a></td></tr><tr><td><p>Ends Sunday!</p></td></tr>'
		);

		$this->assertNotWPError( $out );
		$this->assertStringContainsString( '<p>Ends Sunday!</p>', $doc->html() );
	}

	public function test_replace_html_needs_one_match_unless_all() {

		$doc = $this->doc();

		$this->assertSame( 'groundhogg_html_not_unique', $doc->replace_html( '<tr><td>', '<tr><td class="x">' )->get_error_code() );
		$this->assertSame( 'groundhogg_html_not_found', $doc->replace_html( '<blink>', '' )->get_error_code() );
		// two rows, and Outlook's conditional comment, which replace_html can change too
		$this->assertSame( 3, $doc->replace_html( '<tr><td>', '<tr><td class="x">', true )['count'] );
	}

	public function test_find_returns_the_html_around_each_match() {

		$found = $this->doc()->find( 'shop THE sale' );

		$this->assertSame( 1, $found['count'] );

		$match = $found['matches'][0];

		$this->assertSame( 'Shop the sale', $match['match'] );
		$this->assertStringEndsWith( 'style="color:#fff">', $match['before'] );
		$this->assertStringStartsWith( '</a></td></tr>', $match['after'] );

		// what it returns is exactly as saved, so it can be copied for replace_html
		$this->assertStringContainsString( $match['before'] . $match['match'] . $match['after'], self::email() );
	}

	public function test_find_finds_text_written_with_entities_and_html() {

		$doc = $this->doc();

		$this->assertSame( 1, $doc->find( 'Tom & Jerry' )['count'] );
		// the four rows and Outlook's conditional comment
		$this->assertSame( 5, $doc->find( '<tr><td' )['count'] );
		$this->assertSame( 0, $doc->find( 'nowhere' )['count'] );
	}

	public function test_find_doesnt_cut_a_character_in_two() {

		$doc = new Email_Html_Document( str_repeat( 'é', 100 ) . 'needle' . str_repeat( '—', 100 ) );

		$match = $doc->find( 'needle' )['matches'][0];

		$this->assertTrue( mb_check_encoding( $match['before'], 'UTF-8' ) );
		$this->assertTrue( mb_check_encoding( $match['after'], 'UTF-8' ) );
	}

	public function test_outline_is_the_visible_text() {

		$text = $this->doc()->outline();

		$this->assertSame( implode( "\n", [
			'# Fall sale',
			'Hi {first::there}, our fall sale ends Sunday.',
			'Tom & Jerry say: don\'t miss it — 50% off.',
			'[Shop the sale](https://example.com/sale)',
			'![Fall banner](banner.png)',
			'{business_name} · [Unsubscribe]({unsubscribe_link})',
		] ), $text );
	}

	/*
	 * The abilities
	 */

	protected function run_ability( string $name, array $input ) {
		$out = wp_get_ability( "groundhogg/$name" )->execute( $input );

		return is_wp_error( $out ) ? $out : json_decode( wp_json_encode( $out ), true );
	}

	protected function create_html_email(): int {

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		if ( ! function_exists( 'wp_get_ability' ) || ! wp_get_ability( 'groundhogg/edit-email' ) ) {
			$this->markTestSkipped( 'The email abilities are not registered.' );
		}

		$out = $this->run_ability( 'create-email-template', [ 'subject' => 'Fall sale', 'content' => self::email(), 'editor' => 'html' ] );

		$this->assertNotWPError( $out );

		return $out['id'];
	}

	public function test_edit_email_changes_an_html_email_a_piece_at_a_time() {

		$id      = $this->create_html_email();
		$before  = ( new Email( $id ) )->get_content();
		$version = get_edit_version( new Email( $id ) );

		$out = $this->run_ability( 'edit-email', [
			'email_id'         => $id,
			'expected_version' => $version,
			'operations'       => [
				[ 'op' => 'replace_text', 'find' => 'ends Sunday', 'replace' => 'ends Monday' ],
				[ 'op' => 'replace_html', 'find' => 'href="https://example.com/sale"', 'replace' => 'href="https://example.com/fall-sale"' ],
			],
		] );

		$this->assertNotWPError( $out );
		$this->assertFalse( $out['dry_run'] );
		$this->assertSame( [ 'replace_text', 'replace_html' ], array_column( $out['changes'], 'op' ) );
		$this->assertSame( [ 1, 1 ], array_column( $out['changes'], 'count' ) );
		$this->assertStringContainsString( 'ends Monday', $out['changes'][0]['excerpt'] );
		$this->assertStringContainsString( 'fall-sale', $out['changes'][1]['excerpt'] );
		$this->assertArrayNotHasKey( 'content', $out );

		$email = new Email( $id );

		$this->assertSame( str_replace( [ 'ends Sunday', 'example.com/sale"' ], [ 'ends Monday', 'example.com/fall-sale"' ], $before ), $email->get_content() );
		$this->assertSame( 'html', $email->get_editor_type() );
		$this->assertSame( get_edit_version( $email ), $out['version'] );
		$this->assertSame( strlen( $email->get_content() ), $out['content_length'] );
	}

	public function test_edit_email_on_an_html_email_is_all_or_nothing() {

		$id     = $this->create_html_email();
		$before = ( new Email( $id ) )->get_content();

		$out = $this->run_ability( 'edit-email', [
			'email_id'   => $id,
			'operations' => [
				[ 'op' => 'replace_text', 'find' => 'ends Sunday', 'replace' => 'ends Monday' ],
				[ 'op' => 'replace_html', 'find' => '<marquee>', 'replace' => '' ],
			],
		] );

		$this->assertWPError( $out );
		$this->assertSame( 'groundhogg_html_not_found', $out->get_error_code() );
		$this->assertStringContainsString( 'Operation 2 ("replace_html")', $out->get_error_message() );
		$this->assertSame( $before, ( new Email( $id ) )->get_content() );
	}

	public function test_edit_email_on_an_html_email_dry_run_and_stale_version() {

		$id     = $this->create_html_email();
		$before = ( new Email( $id ) )->get_content();
		$ops    = [ [ 'op' => 'replace_text', 'find' => 'ends Sunday', 'replace' => 'ends Monday' ] ];

		$out = $this->run_ability( 'edit-email', [ 'email_id' => $id, 'operations' => $ops, 'dry_run' => true ] );

		$this->assertTrue( $out['dry_run'] );
		$this->assertSame( $before, ( new Email( $id ) )->get_content() );

		$out = $this->run_ability( 'edit-email', [ 'email_id' => $id, 'operations' => $ops, 'expected_version' => 'stale' ] );

		$this->assertSame( 'groundhogg_email_changed', $out->get_error_code() );
	}

	public function test_edit_email_on_an_html_email_keeps_it_safe() {

		$id = $this->create_html_email();

		$out = $this->run_ability( 'edit-email', [
			'email_id'   => $id,
			'operations' => [
				[ 'op' => 'replace_html', 'find' => '<h1 class="sale">', 'replace' => '<h1 class="sale" onclick="alert(1)">' ],
			],
		] );

		$this->assertNotWPError( $out );
		$this->assertStringNotContainsString( 'onclick', ( new Email( $id ) )->get_content() );
	}

	public function test_get_email_template_finds_html_in_an_html_email() {

		$id  = $this->create_html_email();
		$out = $this->run_ability( 'get-email-template', [ 'email_id' => $id, 'find' => 'unsubscribe' ] );

		$this->assertArrayNotHasKey( 'content', $out );
		$this->assertStringContainsString( '[Unsubscribe]({unsubscribe_link})', $out['text'] );
		$this->assertSame( 2, $out['found']['count'] );
		$this->assertArrayNotHasKey( 'position', $out['found']['matches'][0] );

		$this->assertWPError( $this->run_ability( 'get-email-template', [ 'email_id' => $id, 'view' => 'specs' ] ) );
	}
}
