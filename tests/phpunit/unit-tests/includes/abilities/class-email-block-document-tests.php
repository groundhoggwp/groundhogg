<?php

use Groundhogg\Abilities\Emails\Email_Block_Document;

/**
 * Reading, checking and changing block editor content a block at a time
 */
class Email_Block_Document_Tests extends GH_UnitTestCase {

	/**
	 * A block as the editor writes it
	 */
	public static function block( string $type, string $id, string $html, string $json = '{}', string $td = '' ): string {
		return "<tr><!--$type:$id $json--><td id=\"b-$id\"$td>$html</td><!--/$type:$id--></tr>";
	}

	/**
	 * Columns as the editor writes them, each column a list of block rows
	 */
	public static function columns( string $id, array $columns ): string {

		// with the markers the editor puts around each column, and the class it gives empty ones
		$cells = array_map( function ( $rows ) {
			return '<td class="email-columns-cell one-half" width="50%"><!-- START:COLUMN --><table class="column' . ( $rows ? ' ' : ' empty' ) . '" cellpadding="0" cellspacing="0" role="presentation" width="100%">' . implode( '', $rows ) . '</table><!-- END:COLUMN --></td>';
		}, $columns );

		$html = '<table class="email-columns two_columns responsive" cellspacing="0" cellpadding="0" width="100%" role="presentation"><tr class="email-columns-row">'
		        . implode( '<td class="email-columns-cell gap" width="10">&nbsp;</td>', $cells )
		        . '</tr></table>';

		return self::block( 'columns', $id, $html, '{"layout":"two_columns","gap":10}' );
	}

	public static function email( array $rows ): string {
		return '<table cellpadding="0" cellspacing="0" width="100%" role="presentation">' . implode( '', $rows ) . '</table>';
	}

	/**
	 * A newsletter: a banner, an intro, two columns with an image and text each, a button, a footer
	 */
	public static function newsletter(): string {
		return self::email( [
			self::block( 'image', 'banner', '<div class="img-container"><img src="https://example.com/banner.png" alt="Fall sale" width="600"></div>', '{"title":"banner"}' ),
			self::block( 'text', 'intro', '<p style="font-size: 16px">Hi {first::there},</p><p style="font-size: 16px">Our <strong>fall sale</strong> ends Friday.</p>', '{}', ' style="padding: 20px"' ),
			self::columns( 'row', [
				[
					self::block( 'image', 'left-img', '<img src="https://example.com/left.png" alt="">' ),
					self::block( 'text', 'left-text', '<p>Boots</p>' ),
				],
				[
					self::block( 'text', 'right-text', '<p>Coats</p>' ),
				],
			] ),
			self::block( 'button', 'cta', '<table><tr><td><a href="https://example.com/sale" style="color: #fff">Shop the sale</a></td></tr></table>', '{"text":"Shop the sale","link":"https://example.com/sale"}' ),
			self::block( 'footer', 'foot', '<div class="footer"><p>&copy; {business_name}</p><p><a href="{unsubscribe_url}">Unsubscribe</a></p></div>' ),
		] );
	}

	public function test_it_outlines_the_blocks_with_where_they_are() {

		$doc = new Email_Block_Document( self::newsletter() );

		// the editor's <!-- START:COLUMN --> markers aren't mistaken for broken blocks
		$this->assertTrue( $doc->is_valid(), implode( "\n", $doc->errors() ) );

		$outline = array_column( $doc->outline(), null, 'id' );

		$this->assertSame( [ 'banner', 'intro', 'row', 'left-img', 'left-text', 'right-text', 'cta', 'foot' ], array_keys( $outline ) );

		$this->assertArrayNotHasKey( 'parent', $outline['intro'] );
		$this->assertSame( 'row', $outline['left-text']['parent'] );
		$this->assertSame( 0, $outline['left-img']['column'] );
		$this->assertSame( 0, $outline['left-text']['column'] );
		$this->assertSame( 1, $outline['right-text']['column'] );

		$this->assertSame( 'Hi {first::there}, Our fall sale ends Friday.', $outline['intro']['summary'] );
		$this->assertSame( 'Fall sale (banner.png)', $outline['banner']['summary'] );
		$this->assertSame( 'left.png', $outline['left-img']['summary'] );
		$this->assertSame( 'Shop the sale -> https://example.com/sale', $outline['cta']['summary'] );
		$this->assertSame( 'two_columns, 2 columns, 3 blocks', $outline['row']['summary'] );
	}

	public function test_the_editors_own_content_parses_without_problems() {

		// as the editor saves it: uuid ids, no spaces in the comments, padding on the td
		$content = '<table cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><!--text:5046fcc2-314e-4052-8902-829c7fd24248 {}--><td id="b-5046fcc2-314e-4052-8902-829c7fd24248" class="b-text" bgcolor="#ffffff" background="" valign="top" style="padding: 40px;overflow: hidden"><p style="line-height: 1.4;font-family:system-ui, sans-serif">Hello,</p></td><!--/text:5046fcc2-314e-4052-8902-829c7fd24248--></tr></table>';

		$doc = new Email_Block_Document( $content );

		$this->assertTrue( $doc->is_valid(), implode( "\n", $doc->errors() ) );
		$this->assertSame( [ '5046fcc2-314e-4052-8902-829c7fd24248' ], $doc->ids() );

		// and normalizing it changes nothing
		$doc->normalize();
		$this->assertSame( $content, $doc->html() );
	}

	public function test_normalize_removes_whitespace_the_editor_would_trip_over() {

		$doc = new Email_Block_Document( "<table>\n<tr>\n  <!--  text:intro   {\"a\":1}  -->\n<td id=\"b-intro\"><p>Hi</p></td>\n<!-- /text:intro -->\n</tr>\n</table>" );

		$this->assertTrue( $doc->is_valid(), implode( "\n", $doc->errors() ) );

		$doc->normalize();

		$this->assertStringContainsString( '<tr><!--text:intro {"a":1}-->', $doc->html() );
		$this->assertStringContainsString( '<!--/text:intro--></tr>', $doc->html() );
		$this->assertTrue( $doc->is_valid() );
	}

	public function test_a_block_without_settings_gets_an_empty_object() {

		$doc = new Email_Block_Document( self::email( [ '<tr><!--spacer:gap--><td id="b-gap"></td><!--/spacer:gap--></tr>' ] ) );

		$this->assertTrue( $doc->is_valid(), implode( "\n", $doc->errors() ) );

		$doc->normalize();

		$this->assertStringContainsString( '<!--spacer:gap {}-->', $doc->html() );
	}

	/**
	 * @dataProvider provide_broken_content
	 */
	public function test_it_finds_what_the_editor_would_get_wrong( string $content, string $expect ) {

		$doc = new Email_Block_Document( $content );

		$this->assertFalse( $doc->is_valid() );
		$this->assertStringContainsString( $expect, implode( "\n", $doc->errors() ) );
	}

	public function provide_broken_content(): array {
		return [
			'invalid json'         => [ self::email( [ self::block( 'text', 'a', '<p>x</p>', '{"p":{"fontSize":16,}}' ) ] ), 'aren\'t valid JSON' ],
			'no closing comment'   => [ self::email( [ '<tr><!--text:a {}--><td id="b-a"><p>x</p></td></tr>' ] ), 'no closing comment' ],
			'mismatched closing'   => [ self::email( [ '<tr><!--text:a {}--><td id="b-a"><p>x</p></td><!--/text:b--></tr>' ] ), 'no closing comment' ],
			'duplicate id'         => [ self::email( [ self::block( 'text', 'a', 'x' ), self::block( 'text', 'a', 'y' ) ] ), 'more than one block' ],
			'td without the id'    => [ self::email( [ '<tr><!--text:a {}--><td><p>x</p></td><!--/text:a--></tr>' ] ), 'id="b-a"' ],
			'no td'                => [ self::email( [ '<tr><!--text:a {}--><p>x</p><!--/text:a--></tr>' ] ), 'followed directly by <td id="b-a">' ],
			'not in a row'         => [ '<table><!--text:a {}--><td id="b-a">x</td><!--/text:a--></table>', 'table row of its own' ],
			'uppercase type'       => [ self::email( [ self::block( 'text', 'a', 'x' ), '<tr><!--Text:b {}--><td id="b-b">x</td><!--/Text:b--></tr>' ] ), '"<!--Text:b" isn\'t a valid block comment' ],
			'underscore in id'     => [ self::email( [ '<tr><!--text:a_1 {}--><td id="b-a_1">x</td><!--/text:a_1--></tr>' ] ), 'isn\'t a valid block comment' ],
			'not a table'          => [ '<div>' . self::block( 'text', 'a', 'x' ) . '</div>', 'single <table>' ],
		];
	}

	public function test_prepare_refuses_content_without_blocks() {

		$this->assertWPError( Email_Block_Document::prepare( '<p>Just HTML</p>' ) );
		$this->assertSame( 'groundhogg_no_blocks', Email_Block_Document::prepare( '<p>Just HTML</p>' )->get_error_code() );
	}

	public function test_unknown_types_are_a_warning_not_an_error() {

		$doc = new Email_Block_Document( self::email( [ self::block( 'widget', 'a', 'x' ) ] ) );

		$this->assertTrue( $doc->is_valid() );
		$this->assertStringContainsString( '"widget"', implode( "\n", $doc->warnings() ) );
	}

	public function test_replace_text_changes_only_the_match() {

		$before = self::newsletter();
		$doc    = new Email_Block_Document( $before );

		$result = $doc->replace_text( 'ends Friday', 'ends Sunday' );

		$this->assertNotWPError( $result );
		$this->assertSame( [ 'intro' ], $result['blocks'] );
		$this->assertSame( str_replace( 'ends Friday', 'ends Sunday', $before ), $doc->html() );
	}

	public function test_replace_text_must_be_unique_unless_all() {

		$doc = new Email_Block_Document( self::newsletter() );

		$result = $doc->replace_text( 'https://example.com/', 'https://shop.example.com/' );

		$this->assertWPError( $result );
		$this->assertSame( 'groundhogg_text_not_unique', $result->get_error_code() );
		$this->assertStringContainsString( 'banner', $result->get_error_message() );

		$result = $doc->replace_text( 'https://example.com/', 'https://shop.example.com/', null, true );

		$this->assertNotWPError( $result );
		$this->assertSame( 3, $result['count'] );
		$this->assertSame( [ 'banner', 'left-img', 'cta' ], $result['blocks'] );
		$this->assertStringNotContainsString( 'https://example.com/', $doc->html() );
	}

	public function test_replace_text_can_be_scoped_to_a_block() {

		$doc = new Email_Block_Document( self::newsletter() );

		$result = $doc->replace_text( 'https://example.com/sale', 'https://example.com/fall', 'cta' );

		$this->assertNotWPError( $result );

		// the button's settings have the link too, and are kept the same
		$this->assertStringContainsString( '"link":"https://example.com/fall"', $doc->markup( 'cta' ) );
		$this->assertStringContainsString( 'href="https://example.com/fall"', $doc->markup( 'cta' ) );

		$this->assertWPError( $doc->replace_text( 'Boots', 'Shoes', 'cta' ) );
	}

	public function test_replace_text_finds_text_written_with_entities() {

		$doc = new Email_Block_Document( self::email( [ self::block( 'text', 'a', '<p>Tom &amp; Jerry</p>' ) ] ) );

		$this->assertNotWPError( $doc->replace_text( 'Tom & Jerry', 'Tom & Jerry & Spike' ) );
		$this->assertStringContainsString( '<p>Tom &amp; Jerry &amp; Spike</p>', $doc->html() );
	}

	public function test_replace_text_does_not_match_inside_block_comments() {

		$doc = new Email_Block_Document( self::email( [ self::block( 'text', 'greet', '<p>Hi</p>' ) . '<!-- a note: hello -->' ] ) );

		$result = $doc->replace_text( 'hello', 'bye' );

		$this->assertWPError( $result );
		$this->assertSame( 'groundhogg_text_not_found', $result->get_error_code() );
	}

	public function test_replace_text_that_would_break_a_block_is_refused() {

		$before = self::email( [ self::block( 'text', 'banner', '<p>Hello</p>' ) ] );
		$doc    = new Email_Block_Document( $before );

		// the only match is in <td id="b-banner">
		$result = $doc->replace_text( 'banner', 'header' );

		$this->assertWPError( $result );
		$this->assertSame( 'groundhogg_text_in_block_markup', $result->get_error_code() );
		$this->assertSame( $before, $doc->html() );
		$this->assertTrue( $doc->is_valid() );
	}

	public function test_set_content_that_would_break_the_block_is_refused() {

		$doc = new Email_Block_Document( self::newsletter() );

		$this->assertWPError( $doc->set_content( 'intro', '<p>x</p></td><!--/text:intro--></tr><tr><td>' ) );
		$this->assertWPError( $doc->set_content( 'intro', '<p>x</p></td><td>' ) );
		$this->assertWPError( $doc->set_content( 'intro', '<table><tr><td>x' ) );
		$this->assertSame( self::newsletter(), $doc->html() );

		// tables of its own are fine, like a button's
		$this->assertTrue( $doc->set_content( 'intro', '<table><tr><td><a href="#">x</a></td></tr></table>' ) );
	}

	public function test_set_content_keeps_the_td_and_settings() {

		$doc = new Email_Block_Document( self::newsletter() );

		$this->assertTrue( $doc->set_content( 'intro', '<p>New intro</p>' ) );

		$this->assertSame( '<!--text:intro {}--><td id="b-intro" style="padding: 20px"><p>New intro</p></td><!--/text:intro-->', $doc->markup( 'intro' ) );
		$this->assertTrue( $doc->is_valid() );
	}

	public function test_set_content_refuses_blocks_with_blocks_inside_and_block_comments() {

		$doc = new Email_Block_Document( self::newsletter() );

		$this->assertWPError( $doc->set_content( 'row', '<p>x</p>' ) );
		$this->assertWPError( $doc->set_content( 'intro', self::block( 'text', 'x', 'y' ) ) );
		$this->assertWPError( $doc->set_content( 'nope', '<p>x</p>' ) );
	}

	public function test_set_props_merges_and_null_removes() {

		$doc = new Email_Block_Document( self::newsletter() );

		$this->assertTrue( $doc->set_props( 'cta', [ 'align' => 'center', 'text' => null ] ) );

		$this->assertSame( [ 'link' => 'https://example.com/sale', 'align' => 'center' ], $doc->get( 'cta' )['props'] );
	}

	public function test_insert_at_each_kind_of_position() {

		$doc = new Email_Block_Document( self::newsletter() );

		$this->assertSame( [ 'n1' ], $doc->insert( self::block( 'spacer', 'n1', '' ), [ 'after' => 'intro' ] ) );
		$this->assertSame( [ 'n2' ], $doc->insert( self::block( 'spacer', 'n2', '' ), [ 'before' => 'banner' ] ) );
		$this->assertSame( [ 'n3' ], $doc->insert( self::block( 'spacer', 'n3', '' ), [ 'position' => 'end' ] ) );
		$this->assertSame( [ 'n4' ], $doc->insert( self::block( 'spacer', 'n4', '' ), [ 'in' => 'row', 'column' => 1, 'position' => 'start' ] ) );
		$this->assertSame( [ 'n5' ], $doc->insert( self::block( 'spacer', 'n5', '' ), [ 'in' => 'row', 'column' => 0 ] ) );

		$this->assertTrue( $doc->is_valid(), implode( "\n", $doc->errors() ) );
		$this->assertSame( [ 'n2', 'banner', 'intro', 'n1', 'row', 'left-img', 'left-text', 'n5', 'n4', 'right-text', 'cta', 'foot', 'n3' ], $doc->ids() );

		$outline = array_column( $doc->outline(), null, 'id' );

		$this->assertSame( 'row', $outline['n4']['parent'] );
		$this->assertSame( 1, $outline['n4']['column'] );
		$this->assertSame( 0, $outline['n5']['column'] );
	}

	public function test_insert_takes_blocks_with_or_without_rows_or_the_table() {

		$doc = new Email_Block_Document( self::newsletter() );

		// no row
		$this->assertNotWPError( $doc->insert( '<!--spacer:a {}--><td id="b-a"></td><!--/spacer:a-->', [ 'position' => 'start' ] ) );

		// a whole table of rows
		$this->assertSame( [ 'b', 'c' ], $doc->insert( self::email( [ self::block( 'spacer', 'b', '' ), self::block( 'spacer', 'c', '' ) ] ), [ 'position' => 'end' ] ) );

		$this->assertTrue( $doc->is_valid(), implode( "\n", $doc->errors() ) );
		$this->assertSame( 'a', $doc->ids()[0] );
	}

	public function test_insert_into_an_empty_column_and_an_empty_email() {

		$doc = new Email_Block_Document( self::email( [ self::columns( 'row', [ [], [] ] ) ] ) );

		$this->assertNotWPError( $doc->insert( self::block( 'text', 'a', '<p>a</p>' ), [ 'in' => 'row', 'column' => 1 ] ) );
		$this->assertSame( 1, $doc->get( 'a' )['column'] );

		$empty = new Email_Block_Document( '' );

		$this->assertNotWPError( $empty->insert( self::block( 'text', 'a', '<p>a</p>' ), [ 'position' => 'end' ] ) );
		$this->assertTrue( Email_Block_Document::prepare( $empty->html() ) instanceof Email_Block_Document );
	}

	public function test_insert_refuses_ids_already_used_and_bad_positions() {

		$doc = new Email_Block_Document( self::newsletter() );

		$this->assertSame( 'groundhogg_block_id_taken', $doc->insert( self::block( 'text', 'intro', 'x' ), [ 'position' => 'end' ] )->get_error_code() );
		$this->assertSame( 'groundhogg_block_not_found', $doc->insert( self::block( 'text', 'x', 'x' ), [ 'after' => 'nope' ] )->get_error_code() );
		$this->assertSame( 'groundhogg_invalid_position', $doc->insert( self::block( 'text', 'x', 'x' ), [ 'in' => 'intro' ] )->get_error_code() );
		$this->assertSame( 'groundhogg_invalid_position', $doc->insert( self::block( 'text', 'x', 'x' ), [ 'in' => 'row', 'column' => 2 ] )->get_error_code() );
		$this->assertSame( 'groundhogg_invalid_blocks', $doc->insert( '<tr><!--text:x {bad}--><td id="b-x"></td><!--/text:x--></tr>', [ 'position' => 'end' ] )->get_error_code() );
	}

	public function test_replace_can_reuse_the_ids_it_replaces() {

		$doc = new Email_Block_Document( self::newsletter() );

		$result = $doc->replace( 'row', self::block( 'text', 'row', '<p>No more columns</p>' ) . self::block( 'text', 'left-text', '<p>x</p>' ) );

		$this->assertNotWPError( $result );
		$this->assertSame( [ 'row', 'left-text' ], $result['added'] );
		$this->assertSame( [ 'left-img', 'right-text' ], $result['deleted'] );
		$this->assertSame( [ 'banner', 'intro', 'row', 'left-text', 'cta', 'foot' ], $doc->ids() );

		$this->assertSame( 'groundhogg_block_id_taken', $doc->replace( 'cta', self::block( 'text', 'intro', 'x' ) )->get_error_code() );
	}

	public function test_move_takes_the_blocks_inside_and_cannot_go_inside_itself() {

		$doc = new Email_Block_Document( self::newsletter() );

		$this->assertTrue( $doc->move( 'row', [ 'position' => 'end' ] ) );
		$this->assertSame( [ 'banner', 'intro', 'cta', 'foot', 'row', 'left-img', 'left-text', 'right-text' ], $doc->ids() );

		$this->assertTrue( $doc->move( 'cta', [ 'in' => 'row', 'column' => 1, 'position' => 'start' ] ) );
		$this->assertSame( 'row', $doc->get( 'cta' )['parent'] );
		$this->assertSame( 1, $doc->get( 'cta' )['column'] );

		$this->assertSame( 'groundhogg_invalid_position', $doc->move( 'row', [ 'after' => 'left-text' ] )->get_error_code() );
		$this->assertTrue( $doc->is_valid(), implode( "\n", $doc->errors() ) );
	}

	public function test_delete_takes_the_blocks_inside() {

		$doc = new Email_Block_Document( self::newsletter() );

		$this->assertSame( [ 'row', 'left-img', 'left-text', 'right-text' ], $doc->delete( 'row' ) );
		$this->assertSame( [ 'banner', 'intro', 'cta', 'foot' ], $doc->ids() );
		$this->assertTrue( $doc->is_valid() );
	}

	public function test_plain_text_has_each_blocks_text_in_its_comments() {

		$content = self::email( [
			self::block( 'text', 'intro', '<p>Hi {first::there},</p><p>See <a href="https://example.com">this</a>.</p>' ),
			self::block( 'text', 'hidden', '<p>Not in plain text</p>', '{"hide_in_plain":true}' ),
			self::columns( 'row', [ [ self::block( 'text', 'left', '<p>Left</p>' ) ], [ self::block( 'text', 'right', '<p>Right</p>' ) ] ] ),
			self::block( 'global', 'head', '', '{"templateId":"12"}' ),
		] );

		$plain = ( new Email_Block_Document( $content ) )->to_plain_text();

		$this->assertStringContainsString( '<!--text:intro {}-->', $plain );
		$this->assertStringContainsString( 'Hi {first::there},', $plain );
		$this->assertStringContainsString( '<!--/text:intro-->', $plain );
		$this->assertStringNotContainsString( 'Not in plain text', $plain );
		$this->assertStringContainsString( 'Left', $plain );
		$this->assertStringContainsString( 'Right', $plain );

		// a dynamic block keeps its settings, so it's still rendered when sent
		$this->assertStringContainsString( '<!--global:head {"templateId":"12"}--><!--/global:head-->', $plain );
	}
}
