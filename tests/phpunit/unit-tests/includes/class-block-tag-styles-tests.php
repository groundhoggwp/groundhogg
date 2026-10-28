<?php

use Groundhogg\Email;
use function Groundhogg\get_contactdata;
use function Groundhogg\inline_block_tag_styles;
use function Groundhogg\parse_block_tag_rules;
use function Groundhogg\parse_css_declarations;
use function Groundhogg\replacements;

/**
 * What a replacement code adds to a text block, like the list of links of {files}, is not in the block until the email
 * is built, so the editor never put its inline styles on it, and the link color and font of the block only reached it
 * through the rules in the head, which some email clients drop.
 *
 * @see inline_block_tag_styles()
 * @see parse_block_tag_rules()
 * @see Email::get_merged_content()
 */
class Block_Tag_Styles_Tests extends GH_UnitTestCase {

	public function test_declarations_are_split_where_the_semicolons_are_not_in_quotes_or_parentheses() {

		$this->assertSame( [
			'color'       => '#a04400',
			'font-family' => "'Open Sans', sans-serif",
			'background'  => 'url(data:image/png;base64,AAAA)',
		], parse_css_declarations( "color: #a04400; font-family: 'Open Sans', sans-serif;BACKGROUND:url(data:image/png;base64,AAAA);;nothing" ) );
	}

	public function test_only_the_rules_for_a_tag_in_a_block_are_kept() {

		$css = '/* a comment */ #b-1 a{color:#a04400;font-family:Arial, sans-serif}'
		       . '#b-1 p, #b-1 li{font-size:16px}'
		       . '.row a{color:red}#b-2 .button{color:blue}#b-2 div{color:blue}'
		       . '@media (max-width:600px){#b-1 a{color:green}.stack{display:block}}'
		       . '#b-2 h2{font-size:24px}';

		$this->assertSame( [
			[ 'block' => 'b-1', 'tag' => 'a', 'style' => [ 'color' => '#a04400', 'font-family' => 'Arial, sans-serif' ] ],
			[ 'block' => 'b-1', 'tag' => 'p', 'style' => [ 'font-size' => '16px' ] ],
			[ 'block' => 'b-1', 'tag' => 'li', 'style' => [ 'font-size' => '16px' ] ],
			[ 'block' => 'b-2', 'tag' => 'h2', 'style' => [ 'font-size' => '24px' ] ],
		], parse_block_tag_rules( $css ) );
	}

	public function test_an_element_with_no_style_gets_the_styles_of_its_block() {

		$html = '<div id="b-1"><p style="color:#111">Files:</p><ul><li><a href="https://example.org/a.pdf">a.pdf</a></li></ul></div>';
		$css  = '#b-1 a{color:#a04400;font-family:Arial, sans-serif}#b-1 p, #b-1 li{color:#222;font-size:16px}';

		$out = inline_block_tag_styles( $html, $css );

		$this->assertStringContainsString( '<li style="color:#222;font-size:16px">', $out );
		$this->assertStringContainsString( '<a style="color:#a04400;font-family:Arial, sans-serif" href="https://example.org/a.pdf">', $out );

		// the paragraph that the editor styled is as it was
		$this->assertStringContainsString( '<p style="color:#111">Files:</p>', $out );
	}

	public function test_what_is_outside_of_the_blocks_is_left_alone() {

		$html = '<a href="https://example.org">outside</a><div id="b-1"><a href="#">in</a></div><div id="footer"><a href="#">footer</a></div>';
		$out  = inline_block_tag_styles( $html, '#b-1 a{color:#a04400}' );

		$this->assertSame( '<a href="https://example.org">outside</a><div id="b-1"><a style="color:#a04400" href="#">in</a></div><div id="footer"><a href="#">footer</a></div>', $out );
	}

	public function test_a_block_only_gets_its_own_rules() {

		$html = '<div id="b-1"><a href="#">one</a></div><div id="b-2"><a href="#">two</a></div>';
		$out  = inline_block_tag_styles( $html, '#b-1 a{color:red}#b-2 a{color:blue}' );

		$this->assertSame( '<div id="b-1"><a style="color:red" href="#">one</a></div><div id="b-2"><a style="color:blue" href="#">two</a></div>', $out );
	}

	public function test_the_rule_that_is_written_last_wins_and_blocks_inside_blocks_have_both() {

		$html = '<div id="b-1"><div id="b-2"><a href="#">in both</a></div><a href="#">in one</a></div>';
		$css  = '#b-1 a{color:red;font-size:14px}#b-2 a{color:blue}';
		$out  = inline_block_tag_styles( $html, $css );

		$this->assertStringContainsString( '<a style="color:blue;font-size:14px" href="#">in both</a>', $out );
		$this->assertStringContainsString( '<a style="color:red;font-size:14px" href="#">in one</a>', $out );
	}

	public function test_the_html_that_has_nothing_to_style_is_returned_as_it_was() {

		$html = "<!--[if mso]><a href=\"#\">mso</a><![endif]-->\n<table><tr><td><img src=\"a.png\"><br><a href=\"#\" style=\"color:red\">x</a></td></tr></table><script>var a = '<a>';</script>";

		$this->assertSame( $html, inline_block_tag_styles( $html, '#b-1 a{color:blue}' ) );
		$this->assertSame( $html, inline_block_tag_styles( $html, '' ) );
		$this->assertSame( '', inline_block_tag_styles( '', '#b-1 a{color:blue}' ) );
	}

	public function test_elements_after_a_void_element_or_an_unclosed_tag_are_still_in_the_block() {

		$html = '<div id="b-1"><p>One<br>two <img src="a.png"><a href="#">link</a><p>three<li>four</div><a href="#">after</a>';
		$out  = inline_block_tag_styles( $html, '#b-1 a{color:red}#b-1 p{margin:0}' );

		$this->assertStringContainsString( '<a style="color:red" href="#">link</a>', $out );
		$this->assertStringContainsString( '<p style="margin:0">three', $out );
		$this->assertStringContainsString( '</div><a href="#">after</a>', $out );
	}

	public function test_dynamic_content_of_an_email_is_styled_after_the_replacements() {

		replacements()->add( 'test_file_list', function () {
			return '<ul><li><a href="https://example.org/one.pdf">one.pdf</a></li><li><a href="https://example.org/two.pdf">two.pdf</a></li></ul>';
		} );

		$css = '#b-1 a{color:#a04400;font-family:Impact, sans-serif}#b-1 p, #b-1 li{font-size:16px}';

		$email = new Email( [
			'subject' => 'subject',
			'title'   => 'title',
			'content' => '<div id="b-1"><p style="font-size:16px">Files:</p><p>{test_file_list}</p><a href="#" style="color:#a04400">real link</a></div>',
		] );

		$email->add_meta( 'blocks', true );
		$email->add_meta( 'css', $css );

		$email   = new Email( $email->get_id() );
		$contact = get_contactdata( self::factory()->contacts->create( [ 'email' => 'files-' . uniqid() . '@example.com' ] ) );
		$email->set_contact( $contact );

		$content = $email->get_merged_content();

		$this->assertSame( 2, substr_count( $content, 'style="color:#a04400;font-family:Impact, sans-serif"' ) );
		$this->assertSame( 2, substr_count( $content, '<li style="font-size:16px">' ) );

		// what the editor styled is as it was
		$this->assertStringContainsString( '<p style="font-size:16px">Files:</p>', $content );
		$this->assertStringContainsString( '<a href="#" style="color:#a04400">real link</a>', $content );
	}

	public function test_an_email_without_dynamic_content_is_not_touched() {

		$email = new Email( [
			'subject' => 'subject',
			'title'   => 'title',
			'content' => '<div id="b-1"><p>No codes</p><a href="#">here</a></div>',
		] );

		$email->add_meta( 'blocks', true );
		$email->add_meta( 'css', '#b-1 a{color:#a04400}' );

		$email = new Email( $email->get_id() );
		$email->set_contact( get_contactdata( self::factory()->contacts->create( [ 'email' => 'plain-' . uniqid() . '@example.com' ] ) ) );

		$this->assertStringContainsString( '<a href="#">here</a>', $email->get_merged_content() );
	}
}
