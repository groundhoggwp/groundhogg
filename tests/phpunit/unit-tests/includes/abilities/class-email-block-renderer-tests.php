<?php

use Groundhogg\Abilities\Emails\Create_Email_Template;
use Groundhogg\Abilities\Emails\Email_Block_Document;
use Groundhogg\Abilities\Emails\Email_Block_Renderer;
use Groundhogg\Abilities\Emails\Email_Block_Style;
use Groundhogg\Email;

/**
 * Email_Block_Renderer, which writes block editor content from block specs, and the abilities that take specs.
 *
 * The fixtures in fixtures/email-blocks were made on a real site and checked in the block editor:
 * - newsletter.input.json, specs for every core block type, was rendered with the site's settings (settings.json)
 * - the email was opened in the block editor, changed so the editor rendered every block again, and saved
 * - newsletter.html and newsletter.css are what the editor saved, which was the same as the rendered content
 * - newsletter.specs.json is that content read back as specs
 *
 * So if the renderer and the editor drift apart, these tests fail. When the editor's markup changes on purpose,
 * make the fixtures again the same way.
 */
class Email_Block_Renderer_Tests extends GH_UnitTestCase {

	const FIXTURES = __DIR__ . '/fixtures/email-blocks/';

	public function setUp(): void {
		parent::setUp();

		$this->factory()->truncate();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * Use the settings of the site the fixtures were made on
	 */
	protected function use_fixture_settings() {
		foreach ( json_decode( file_get_contents( self::FIXTURES . 'settings.json' ), true ) as $name => $value ) {
			update_option( $name, $value );
		}
	}

	protected function fixture( string $file ): string {
		return str_replace( '{{assets_url}}', GROUNDHOGG_ASSETS_URL, file_get_contents( self::FIXTURES . $file ) );
	}

	protected function renderer(): Email_Block_Renderer {
		return new Email_Block_Renderer( [ 'template' => 'boxed', 'width' => 600 ] );
	}

	/**
	 * Rendered content as it's saved
	 *
	 * @param array $specs
	 *
	 * @return array
	 */
	protected function render( array $specs, $renderer = null ): array {
		$out = ( $renderer ?: $this->renderer() )->render( $specs );

		$this->assertNotWPError( $out );

		$out['content'] = Create_Email_Template::kses_preserving_block_comments( $out['content'] );

		return $out;
	}

	/**
	 * Content with its block ids replaced by b1, b2..., to compare content with different ids
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	protected static function without_ids( string $content ): string {

		$ids = ( new Email_Block_Document( $content ) )->ids();

		// longest first, so an id isn't replaced inside a longer one
		$map = [];
		foreach ( $ids as $i => $id ) {
			$map[ $id ] = 'block' . $i . 'x';
		}

		uksort( $map, function ( $a, $b ) {
			return strlen( $b ) - strlen( $a );
		} );

		return strtr( $content, $map );
	}

	protected function run_ability( string $name, array $input ) {
		$out = wp_get_ability( "groundhogg/$name" )->execute( $input );

		return is_wp_error( $out ) ? $out : json_decode( wp_json_encode( $out ), true );
	}

	/*
	 * Against what the editor saved
	 */

	public function test_specs_render_what_the_editor_saves() {

		$this->use_fixture_settings();

		$specs = json_decode( $this->fixture( 'newsletter.specs.json' ), true );
		$out   = $this->render( $specs );

		$this->assertSame( $this->fixture( 'newsletter.html' ), $out['content'] );
		$this->assertSame( $this->fixture( 'newsletter.css' ), $out['css'] );
	}

	public function test_written_specs_render_what_the_editor_saves() {

		$this->use_fixture_settings();

		$specs = json_decode( $this->fixture( 'newsletter.input.json' ), true );
		$out   = $this->render( $specs );

		$this->assertSame( self::without_ids( $this->fixture( 'newsletter.html' ) ), self::without_ids( $out['content'] ) );
	}

	public function test_the_editor_saved_content_reads_back_as_the_specs() {

		$this->use_fixture_settings();

		$this->assertSame(
			json_decode( $this->fixture( 'newsletter.specs.json' ), true ),
			json_decode( wp_json_encode( $this->renderer()->to_specs( $this->fixture( 'newsletter.html' ) ) ), true )
		);
	}

	public function test_css_and_plain_text_of_the_editor_saved_content() {

		$this->use_fixture_settings();

		$made = $this->renderer()->css_and_plain_text( $this->fixture( 'newsletter.html' ) );

		$this->assertSame( $this->fixture( 'newsletter.css' ), $made['css'] );

		$text = $made['plain_text'];

		// block ids differ each time the fixtures are made
		$this->assertMatchesRegularExpression( '/<!--button:(button-[0-9a-f]+) \{"size":"lg"\}-->\[Go & see\]\(https:\/\/example\.com\/go\)<!--\/button:\1-->/', $text );
		$this->assertStringContainsString( '[Home](https://example.com) | [Blog](https://example.com/blog)', $text );
		$this->assertMatchesRegularExpression( '/<!--divider:(divider-[0-9a-f]+) \{\}-->---<!--\/divider:\1-->/', $text );
		$this->assertStringContainsString( '[![Logo](https://placehold.co/240x80?text=Logo)](https://example.com)', $text );
		// a spacer has no plain text
		$this->assertStringNotContainsString( 'spacer:', $text );
		// the blocks in columns are in the columns block
		$this->assertMatchesRegularExpression( '/<!--columns:columns-[0-9a-f]+ \{[^}]*\}--><!--heading:/', $text );
	}

	/*
	 * Each block type
	 */

	public function block_specs(): array {
		return [
			'text'               => [ [ 'type' => 'text', 'content' => '<p>Hi {first}, <a href="https://example.com">a link</a></p><ul><li>One</li></ul><p style="text-align:right">Right</p>' ] ],
			'text with fonts'    => [ [ 'type' => 'text', 'content' => '<h1>Big</h1><p>Small</p>', 'p' => [ 'fontSize' => '18px', 'fontFamily' => 'Georgia', 'color' => '#222' ], 'h1' => [ 'use' => 'Heading 1' ] ] ],
			'heading'            => [ [ 'type' => 'heading', 'tag' => 'h1', 'content' => 'Hello <em>you</em>', 'alignment' => 'center', 'fontStyle' => [ 'color' => '#ff0000', 'fontSize' => 40 ] ] ],
			'button'             => [ [ 'type' => 'button', 'text' => 'Tom & Jerry', 'link' => 'https://example.com/?a=1&b=2', 'size' => 'sm', 'align' => 'left', 'borderStyle' => [ 'borderStyle' => 'dashed', 'borderColor' => 'red', 'borderWidth' => 1 ] ] ],
			'image'              => [ [ 'type' => 'image', 'src' => 'https://example.com/a.png', 'alt' => 'A "quoted" image', 'width' => 300, 'link' => 'https://example.com', 'borderStyle' => [ 'borderRadius' => 10 ] ] ],
			'spacer'             => [ [ 'type' => 'spacer', 'height' => 45 ] ],
			'divider'            => [ [ 'type' => 'divider', 'height' => 2, 'width' => 50, 'color' => 'rgb(10, 20, 30)', 'lineStyle' => 'dotted' ] ],
			'menu'               => [ [ 'type' => 'menu', 'links' => [ [ 'One', 'https://example.com/1' ], [ 'Two', 'https://example.com/2' ] ], 'separator' => '|' ] ],
			'social'             => [ [ 'type' => 'social', 'use' => 'custom', 'socials' => [ [ 'facebook', 'https://facebook.com/x' ], [ 'bluesky', 'https://bsky.app/x' ] ] ] ],
			'html'               => [ [ 'type' => 'html', 'content' => '<table><tr><td>Cell</td></tr></table><script>alert(1)</script><p>After</p>' ] ],
			'footer'             => [ [ 'type' => 'footer', 'alignment' => 'center', 'tel' => false ] ],
			'columns'            => [ [ 'type' => 'columns', 'layout' => 'three_columns_left', 'columnStyle' => [ 'backgroundColor' => '#eee', 'padding' => 8 ], 'columns' => [ [ [ 'type' => 'text', 'content' => '<p>A</p>' ] ], [], [ [ 'type' => 'button', 'text' => 'Go' ] ] ] ] ],
			'custom columns'     => [ [ 'type' => 'columns', 'layout' => 'custom_columns', 'customColumns' => [ 25, 75 ], 'columns' => [ [ [ 'type' => 'spacer' ] ], [ [ 'type' => 'spacer' ] ] ] ] ],
			'nested columns'     => [ [ 'type' => 'columns', 'columns' => [ [ [ 'type' => 'columns', 'layout' => 'one_column', 'columns' => [ [ [ 'type' => 'text', 'content' => '<p>Deep</p>' ] ] ] ] ], [] ] ] ],
			'global'             => [ [ 'type' => 'global', 'templateId' => 12 ] ],
			'advanced style'     => [ [ 'type' => 'spacer', 'advancedStyle' => [ 'padding' => '10px 20px', 'backgroundColor' => '#f0f0f0', 'borderStyle' => 'solid', 'borderColor' => '#ccc', 'borderWidth' => 1, 'borderRadius' => 5 ], 'hide_on_mobile' => true, 'hide_in_plain' => true ] ],
			'no padding'         => [ [ 'type' => 'spacer', 'advancedStyle' => [ 'padding' => 0 ] ] ],
		];
	}

	/**
	 * Rendering what's read back from rendered blocks gives the same blocks: the editor wouldn't change them either
	 *
	 * @dataProvider block_specs
	 */
	public function test_blocks_read_back_render_the_same( array $spec ) {

		$out   = $this->render( [ $spec ] );
		$specs = $this->renderer()->to_specs( $out['content'] );
		$again = $this->render( $specs );

		$this->assertSame( $out['content'], $again['content'] );
		$this->assertSame( $out['css'], $again['css'] );

		$doc = new Email_Block_Document( $out['content'] );
		$this->assertTrue( $doc->is_valid(), implode( "\n", $doc->errors() ) );

		// full specs, without leaving anything out, render the same too
		$full = $this->render( $this->renderer()->to_specs( $out['content'], false ) );
		$this->assertSame( $out['content'], $full['content'] );
	}

	/**
	 * @dataProvider block_specs
	 */
	public function test_blocks_render_the_same_for_full_width_contained( array $spec ) {

		$renderer = new Email_Block_Renderer( [ 'template' => 'full_width_contained', 'width' => 500 ] );
		$out      = $this->render( [ $spec ], $renderer );

		$this->assertStringContainsString( 'style="width: 500px"', $out['content'] );

		$again = $this->render( $renderer->to_specs( $out['content'] ), new Email_Block_Renderer( [ 'template' => 'full_width_contained', 'width' => 500 ] ) );

		$this->assertSame( $out['content'], $again['content'] );
	}

	public function test_text_is_styled_like_the_editor() {

		$out = $this->render( [ [ 'type' => 'text', 'id' => 't', 'content' => '<p>One</p><p>Two <b>bold</b></p>' ] ] );

		$this->assertStringContainsString( '<p style="line-height: 1.4;font-family:system-ui, sans-serif;font-weight: normal;font-size: 14px;font-style: normal;text-transform: none;margin: 0px 0px 1em">One</p>', $out['content'] );
		$this->assertStringContainsString( 'margin: 1em 0px 0px">Two <b style="font-weight: bold">bold</b></p>', $out['content'] );
		$this->assertStringContainsString( '<!--text:t {}--><td id="b-t" class="b-text" bgcolor="" background="" valign="top" style="padding: 5px;border-width: 0px;overflow: hidden">', $out['content'] );
	}

	public function test_props_not_read_from_the_html_are_in_the_comment() {

		$out = $this->render( [
			[ 'type' => 'button', 'id' => 'b', 'text' => 'Go', 'size' => 'lg' ],
			[ 'type' => 'social', 'id' => 's', 'align' => 'left' ],
			[ 'type' => 'image', 'id' => 'i' ],
		] );

		$this->assertStringContainsString( '<!--button:b {"size":"lg"}-->', $out['content'] );
		$this->assertStringContainsString( '<!--social:s {"align":"left","use":"global"}-->', $out['content'] );
		$this->assertStringContainsString( '<!--image:i {"title":"placeholder image"}-->', $out['content'] );
	}

	/*
	 * Site settings
	 */

	public function test_site_block_defaults_are_used() {

		update_option( 'gh_email_editor_block_defaults', [
			'button' => [ 'backgroundColor' => '#123456', 'size' => 'sm' ],
			'spacer' => [ 'height' => 77 ],
		] );

		$out = $this->render( [ [ 'type' => 'button', 'text' => 'Go' ], [ 'type' => 'spacer' ] ] );

		$this->assertStringContainsString( 'bgcolor="#123456"', $out['content'] );
		$this->assertStringContainsString( 'padding: 8px 12px', $out['content'] );
		$this->assertStringContainsString( '<td height="77"', $out['content'] );
	}

	public function test_palette_colors_and_global_fonts() {

		update_option( 'gh_email_editor_color_palette', [ '#111111', '#AA0000' ] );
		update_option( 'gh_email_editor_global_fonts', [
			[ 'name' => 'Body', 'id' => 'font-body', 'style' => [ 'fontFamily' => 'Georgia, serif', 'fontSize' => '17', 'lineHeight' => '1.6', 'fontWeight' => 'normal', 'fontStyle' => 'normal', 'textTransform' => 'none' ] ],
		] );

		$out = $this->render( [
			[ 'type' => 'heading', 'id' => 'h', 'content' => 'Hi', 'fontStyle' => [ 'color' => 'palette:1', 'use' => 'Body' ] ],
			[ 'type' => 'text', 'id' => 't', 'content' => '<p>Hi</p>', 'p' => [ 'use' => 'body' ] ],
		] );

		// the heading doesn't support global fonts, so it gets the font's settings
		$this->assertMatchesRegularExpression( '/<h2 style="color: #aa0000;font-family:Georgia, serif;line-height: 1.6;[^"]*font-size: 17px/', $out['content'] );
		$this->assertStringContainsString( 'line-height: 1.6;font-family:Georgia, serif;font-weight: normal;font-size: 17px', $out['content'] );
	}

	/*
	 * Problems
	 */

	public function test_bad_specs_are_refused_with_what_is_wrong() {

		$renderer = $this->renderer();

		$error = $renderer->render( [
			[ 'type' => 'carousel' ],
			[ 'type' => 'text', 'id' => 'not valid!' ],
			[ 'type' => 'text', 'id' => 'same' ],
			[ 'type' => 'spacer', 'id' => 'same' ],
			[ 'type' => 'columns', 'layout' => 'five_columns', 'columns' => [ [ [ 'type' => 'nope' ] ] ] ],
			'just a string',
		] );

		$this->assertWPError( $error );

		$message = $error->get_error_message();

		$this->assertStringContainsString( 'Block 0 has the type "carousel"', $message );
		$this->assertStringContainsString( 'the id "not valid!" can only have', $message );
		$this->assertStringContainsString( 'the id "same" is already used', $message );
		$this->assertStringContainsString( 'layout must be one of', $message );
		$this->assertStringContainsString( 'Block 4.columns.0.0 has the type "nope"', $message );
		$this->assertStringContainsString( 'Block 5 isn\'t an object', $message );

		$this->assertWPError( $renderer->render( [] ) );
	}

	public function test_new_ids_do_not_clash_with_existing_ones() {

		$renderer = new Email_Block_Renderer( [], [ 'taken' ] );

		$this->assertWPError( $renderer->render_rows( [ [ 'type' => 'spacer', 'id' => 'taken' ] ] ) );

		$rows = $renderer->render_rows( [ [ 'type' => 'spacer' ], [ 'type' => 'spacer' ] ] );

		$this->assertNotWPError( $rows );
		$this->assertCount( 2, array_unique( $rows['ids'] ) );
		$this->assertMatchesRegularExpression( '/^spacer-[a-f0-9]{6}$/', $rows['ids'][0] );
	}

	/*
	 * Add-ons
	 */

	public function test_add_ons_can_render_their_own_blocks() {

		$types = function ( $types ) {
			$types[] = 'countdown';

			return $types;
		};

		$renderers = function ( $types ) {
			$types['countdown'] = [
				'defaults'   => [ 'until' => '2030-01-01', 'label' => 'Ends in' ],
				'attributes' => [ 'label' ],
				'html'       => function ( $props ) {
					return '<p class="countdown">' . esc_html( $props['label'] ) . '</p>';
				},
				'parse'      => function ( $inner ) {
					return [ 'label' => preg_match( '/<p class="countdown">(.*?)<\/p>/', $inner, $m ) ? $m[1] : null ];
				},
			];

			return $types;
		};

		add_filter( 'groundhogg/email/block_types', $types );
		add_filter( 'groundhogg/email/block_renderers', $renderers );

		$renderer = $this->renderer();
		$out      = $this->render( [ [ 'type' => 'countdown', 'id' => 'c', 'label' => 'Hurry' ] ], $renderer );

		$this->assertStringContainsString( '<!--countdown:c {"until":"2030-01-01"}--><td id="b-c" class="b-countdown"', $out['content'] );
		$this->assertStringContainsString( '<p class="countdown">Hurry</p>', $out['content'] );
		$this->assertSame( [ [ 'type' => 'countdown', 'id' => 'c', 'label' => 'Hurry' ] ], $renderer->to_specs( $out['content'] ) );

		remove_filter( 'groundhogg/email/block_types', $types );
		remove_filter( 'groundhogg/email/block_renderers', $renderers );
	}

	/*
	 * Styles
	 */

	public function test_styles_change_like_in_the_browser() {

		$style = new Email_Block_Style( 'text-align: center; color: rgb(255, 0, 0)' );

		$style->assign( [
			'padding'     => '5px 5px 5px 5px',
			'margin'      => '1em 0',
			'marginTop'   => 0,
			'fontSize'    => 16,     // a number without a unit is ignored
			'width'       => 'NaNpx', // so is NaN
			'color'       => '#ABC',
			'borderWidth' => '2px 0 0 0',
			'use'         => 'custom',
		] );

		$this->assertSame( 'text-align: center; color: #aabbcc; padding: 5px; margin: 0px 0px 1em; border-width: 2px 0px 0px; use: custom;', (string) $style );

		$style->set( 'color', '' )->set( 'margin-left', '3px' );

		$this->assertSame( '', $style->get( 'color' ) );
		$this->assertSame( '0px 0px 1em 3px', $style->get( 'margin' ) );
		$this->assertSame( '', ( new Email_Block_Style( 'padding-top: 1px' ) )->get( 'padding' ) );
		$this->assertSame( 'Georgia, serif', Email_Block_Style::normalize_font( '"Georgia"' ) );
		$this->assertSame( 'Arial, sans-serif', Email_Block_Style::normalize_font( 'Comic Neue' ) );
	}

	/*
	 * Abilities
	 */

	public function test_create_email_from_blocks() {

		$out = $this->run_ability( 'create-email-template', [
			'subject' => 'Hello',
			'blocks'  => [
				[ 'type' => 'heading', 'content' => 'Hi {first}' ],
				[ 'type' => 'text', 'content' => '<p>Welcome</p>' ],
				[ 'type' => 'button', 'text' => 'Start', 'link' => 'https://example.com' ],
			],
		] );

		$this->assertNotWPError( $out );
		$this->assertSame( 'blocks', $out['editor_type'] );
		$this->assertSame( 3, $out['block_count'] );

		$email = new Email( $out['id'] );

		$this->assertSame( 'blocks', $email->get_editor_type() );
		$this->assertTrue( ( new Email_Block_Document( $email->get_content() ) )->is_valid() );
		$this->assertStringContainsString( ' p, #b-', $email->get_meta( 'css' ) );
		$this->assertStringContainsString( '[Start](https://example.com)', $email->plain_text );
	}

	public function test_create_needs_blocks_or_content_but_not_both() {

		$this->assertWPError( $this->run_ability( 'create-email-template', [ 'subject' => 'Hello' ] ) );

		$this->assertWPError( $this->run_ability( 'create-email-template', [
			'subject' => 'Hello',
			'content' => '<p>Hi</p>',
			'blocks'  => [ [ 'type' => 'spacer' ] ],
		] ) );

		$this->assertWPError( $this->run_ability( 'create-email-template', [
			'subject' => 'Hello',
			'editor'  => 'html',
			'blocks'  => [ [ 'type' => 'spacer' ] ],
		] ) );

		$this->assertWPError( $this->run_ability( 'create-email-template', [
			'subject' => 'Hello',
			'blocks'  => [ [ 'type' => 'carousel' ] ],
		] ) );
	}

	public function test_create_uses_the_template_settings() {

		$out = $this->run_ability( 'create-email-template', [
			'subject'           => 'Hello',
			'blocks'            => [ [ 'type' => 'spacer' ] ],
			'template_settings' => [ 'layout' => 'full_width_contained', 'width' => 520 ],
			'message_type'      => 'transactional',
		] );

		$this->assertNotWPError( $out );
		$this->assertStringContainsString( 'style="width: 520px"', ( new Email( $out['id'] ) )->get_content() );
	}

	public function test_update_email_from_blocks() {

		$created = $this->run_ability( 'create-email-template', [ 'subject' => 'Hello', 'content' => '<p>Old</p>' ] );

		$out = $this->run_ability( 'update-email-template', [
			'email_id' => $created['id'],
			'blocks'   => [ [ 'type' => 'text', 'id' => 'intro', 'content' => '<p>New</p>' ] ],
		] );

		$this->assertNotWPError( $out );

		$email = new Email( $created['id'] );

		$this->assertSame( 'blocks', $email->get_editor_type() );
		$this->assertStringContainsString( '<!--text:intro {}-->', $email->get_content() );
		$this->assertStringContainsString( '#b-intro p', $email->get_meta( 'css' ) );
	}

	public function test_edit_email_adds_and_replaces_blocks_from_specs() {

		$created = $this->run_ability( 'create-email-template', [
			'subject' => 'Hello',
			'blocks'  => [
				[ 'type' => 'text', 'id' => 'intro', 'content' => '<p>Hi</p>' ],
				[ 'type' => 'columns', 'id' => 'cols', 'columns' => [ [ [ 'type' => 'text', 'id' => 'left', 'content' => '<p>Left</p>' ] ], [] ] ],
			],
		] );

		$out = $this->run_ability( 'edit-email', [
			'email_id'   => $created['id'],
			'operations' => [
				[ 'op' => 'add', 'at' => [ 'after' => 'intro' ], 'blocks' => [ [ 'type' => 'button', 'id' => 'cta', 'text' => 'Go', 'link' => 'https://example.com' ] ] ],
				[ 'op' => 'add', 'at' => [ 'in' => 'cols', 'column' => 1 ], 'blocks' => [ [ 'type' => 'text', 'id' => 'right', 'content' => '<p>Right</p>' ] ] ],
				// the replaced block's id can be used again
				[ 'op' => 'replace', 'block' => 'intro', 'blocks' => [ [ 'type' => 'heading', 'id' => 'intro', 'content' => 'Hello' ] ] ],
			],
		] );

		$this->assertNotWPError( $out );

		$email   = new Email( $created['id'] );
		$content = $email->get_content();
		$doc     = new Email_Block_Document( $content );

		$this->assertTrue( $doc->is_valid() );
		$this->assertSame( [ 'intro', 'cta', 'cols', 'left', 'right' ], $doc->ids() );
		$this->assertSame( 'heading', $doc->get( 'intro' )['type'] );
		$this->assertSame( 1, $doc->get( 'right' )['column'] );
		$this->assertStringContainsString( '#b-right p', $email->get_meta( 'css' ) );
		$this->assertStringContainsString( '[Go](https://example.com)', $email->plain_text );

		// blocks in a column aren't wrapped for full width contained templates
		$this->assertStringNotContainsString( 'b-right-inner', $content );

		// an id that's taken
		$this->assertWPError( $this->run_ability( 'edit-email', [
			'email_id'   => $created['id'],
			'operations' => [ [ 'op' => 'add', 'at' => [ 'position' => 'end' ], 'blocks' => [ [ 'type' => 'spacer', 'id' => 'cta' ] ] ] ],
		] ) );
	}

	public function test_get_email_as_specs() {

		$created = $this->run_ability( 'create-email-template', [
			'subject' => 'Hello',
			'blocks'  => [
				[ 'type' => 'heading', 'id' => 'title', 'content' => 'Hi', 'fontStyle' => [ 'color' => '#ff0000' ] ],
				[ 'type' => 'columns', 'id' => 'cols', 'columns' => [ [ [ 'type' => 'button', 'id' => 'btn', 'text' => 'Go' ] ], [] ] ],
			],
		] );

		$out = $this->run_ability( 'get-email-template', [ 'email_id' => $created['id'], 'view' => 'specs' ] );

		$this->assertNotWPError( $out );
		$this->assertArrayNotHasKey( 'content', $out );
		$this->assertSame( [
			[ 'type' => 'heading', 'id' => 'title', 'fontStyle' => [ 'color' => '#ff0000' ], 'content' => 'Hi' ],
			[ 'type' => 'columns', 'id' => 'cols', 'columns' => [ [ [ 'type' => 'button', 'id' => 'btn', 'text' => 'Go' ] ], [] ] ],
		], $out['specs'] );

		$one = $this->run_ability( 'get-email-template', [ 'email_id' => $created['id'], 'view' => 'specs', 'blocks' => [ 'btn' ] ] );

		$this->assertSame( [ [ 'type' => 'button', 'id' => 'btn', 'text' => 'Go' ] ], $one['specs'] );

		// and back, unchanged
		$before = ( new Email( $created['id'] ) )->get_content();

		$this->run_ability( 'update-email-template', [ 'email_id' => $created['id'], 'blocks' => $out['specs'] ] );

		$this->assertSame( $before, ( new Email( $created['id'] ) )->get_content() );
	}

	public function test_the_guide_has_the_site_design() {

		update_option( 'gh_email_editor_color_palette', [ '#111111', '#AA0000' ] );
		update_option( 'gh_email_editor_block_defaults', [ 'version' => 1, 'button' => [ 'backgroundColor' => '#123456' ] ] );

		$out = $this->run_ability( 'get-authoring-guide', [ 'topic' => 'email' ] );

		$this->assertNotWPError( $out );
		$this->assertStringNotContainsString( '{{email_design}}', $out['content'] );
		$this->assertStringContainsString( '`palette:1` #AA0000', $out['content'] );
		$this->assertStringContainsString( '"Heading 1"', $out['content'] );
		$this->assertStringContainsString( '- `button`: `{"backgroundColor":"#123456"}`', $out['content'] );
	}

	/**
	 * A typical newsletter takes about 2K characters of specs
	 */
	public function test_a_newsletter_from_a_short_spec() {

		$specs = json_decode( $this->fixture( 'newsletter.input.json' ), true );

		$this->assertLessThan( 2500, strlen( wp_json_encode( $specs ) ) );

		$out = $this->run_ability( 'create-email-template', [ 'subject' => 'News', 'blocks' => $specs ] );

		$this->assertNotWPError( $out );
		$this->assertSame( 20, $out['block_count'] );
	}
}
