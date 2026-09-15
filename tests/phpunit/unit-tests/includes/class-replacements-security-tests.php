<?php

use Groundhogg\Contact;
use Groundhogg\Replacements;
use function Groundhogg\do_replacements;
use function Groundhogg\escape_shortcodes;
use function Groundhogg\generate_contact_with_map;
use function Groundhogg\get_contactdata;
use function Groundhogg\replacements;

/**
 * Covers the 2nd-order injection fixes added around merge tag/shortcode handling:
 *
 * @see Replacements::scrub_merge_tags()
 * @see Replacements::escape_merge_tags()
 * @see Replacements::escape_shortcodes()
 * @see \Groundhogg\escape_shortcodes()
 * @see Replacements::do_replacement() nested vs. escaped branching
 * @see Replacements::is_disallowed_user_key()
 * @see Contact::update(), Contact::add_meta(), Contact::update_meta()
 * @see \Groundhogg\generate_contact_with_map()
 */
class Replacements_Security_Tests extends GH_UnitTestCase {

	/**
	 * strpos()-based substring assertions, used instead of PHPUnit's
	 * assertString(Not)ContainsString() so these tests run under both older and newer
	 * PHPUnit versions.
	 */
	protected function assertHasSubstring( $needle, $haystack, $message = '' ) {
		$this->assertNotFalse( strpos( $haystack, $needle ), $message ?: "Failed asserting that '$haystack' contains '$needle'." );
	}

	protected function assertNoSubstring( $needle, $haystack, $message = '' ) {
		$this->assertFalse( strpos( $haystack, $needle ), $message ?: "Failed asserting that '$haystack' does not contain '$needle'." );
	}

	/**
	 * Registered as non-nested (the default): its return value should be escaped, not expanded.
	 */
	const PLAIN_CODE = 'test_gh_plain_source';

	/**
	 * Registered with nested => true: its return value should be recursively expanded.
	 */
	const NESTED_CODE = 'test_gh_nested_source';

	/**
	 * What both of the above embed in their return value; only NESTED_CODE should cause this to
	 * actually resolve to TARGET_CODE's output.
	 */
	const TARGET_CODE = 'test_gh_target_code';

	const TARGET_VALUE = 'TARGET_VALUE';

	/**
	 * Non-nested code whose output embeds a registered shortcode.
	 */
	const SHORTCODE_SOURCE_CODE = 'test_gh_shortcode_source';

	const TEST_SHORTCODE = 'gh_test_shortcode';

	public function setUp(): void {
		parent::setUp();

		replacements()->add( self::TARGET_CODE, function () {
			return self::TARGET_VALUE;
		} );

		replacements()->add( self::NESTED_CODE, function () {
			return 'wrap-{' . self::TARGET_CODE . '}-wrap';
		}, '', '', 'other', '', '', true );

		replacements()->add( self::PLAIN_CODE, function () {
			return 'wrap-{' . self::TARGET_CODE . '}-wrap';
		} );

		replacements()->add( self::SHORTCODE_SOURCE_CODE, function () {
			return 'before [' . self::TEST_SHORTCODE . '] after';
		} );

		add_shortcode( self::TEST_SHORTCODE, function () {
			return 'PWNED';
		} );
	}

	public function tearDown(): void {
		replacements()->remove( self::TARGET_CODE );
		replacements()->remove( self::NESTED_CODE );
		replacements()->remove( self::PLAIN_CODE );
		replacements()->remove( self::SHORTCODE_SOURCE_CODE );
		remove_shortcode( self::TEST_SHORTCODE );

		parent::tearDown();
	}

	/* ---------------------------------------------------------------------
	 * Replacements::scrub_merge_tags()
	 * ------------------------------------------------------------------- */

	public function test_scrub_merge_tags_strips_tag_shaped_substrings() {
		$this->assertSame(
			'Hello  World',
			Replacements::scrub_merge_tags( 'Hello {user.user_pass} World' )
		);
	}

	public function test_scrub_merge_tags_strips_multiple_tags() {
		$this->assertSame(
			'--',
			Replacements::scrub_merge_tags( '{aa}-{b.c}-{d::default}' )
		);
	}

	public function test_scrub_merge_tags_leaves_non_tag_braces_alone() {
		// No content between the braces, or an unterminated brace: PATTERN doesn't match.
		$this->assertSame( 'price: {}', Replacements::scrub_merge_tags( 'price: {}' ) );
		$this->assertSame( 'unterminated {foo', Replacements::scrub_merge_tags( 'unterminated {foo' ) );
	}

	public function test_scrub_merge_tags_does_not_match_across_newlines() {
		$value = "{foo\nbar}";
		$this->assertSame( $value, Replacements::scrub_merge_tags( $value ) );
	}

	public function test_scrub_merge_tags_recurses_into_arrays() {
		$input = [
			'first_name' => 'Bad {user.user_pass}',
			'nested'     => [ 'note' => 'Also bad {tag}', 'fine' => 'ok' ],
		];

		$expected = [
			'first_name' => 'Bad ',
			'nested'     => [ 'note' => 'Also bad ', 'fine' => 'ok' ],
		];

		$this->assertSame( $expected, Replacements::scrub_merge_tags( $input ) );
	}

	public function test_scrub_merge_tags_passes_through_non_strings() {
		$this->assertSame( 5, Replacements::scrub_merge_tags( 5 ) );
		$this->assertNull( Replacements::scrub_merge_tags( null ) );
		$this->assertTrue( Replacements::scrub_merge_tags( true ) );
	}

	/* ---------------------------------------------------------------------
	 * Replacements::escape_merge_tags()
	 * ------------------------------------------------------------------- */

	public function test_escape_merge_tags_encodes_braces_in_html_context() {
		do_replacements( '', 0, 'html' ); // sets the instance context to 'html'

		$this->assertSame(
			'wrap-&#123;test_gh_target_code&#125;-wrap',
			replacements()->escape_merge_tags( 'wrap-{' . self::TARGET_CODE . '}-wrap' )
		);
	}

	public function test_escape_merge_tags_scrubs_in_plain_context() {
		do_replacements( '', 0, 'plain' ); // sets the instance context to 'plain'

		$this->assertSame(
			'wrap--wrap',
			replacements()->escape_merge_tags( 'wrap-{' . self::TARGET_CODE . '}-wrap' )
		);
	}

	public function test_escape_merge_tags_leaves_tag_free_strings_untouched() {
		do_replacements( '', 0, 'html' );
		$this->assertSame( 'no tags here', replacements()->escape_merge_tags( 'no tags here' ) );
	}

	/* ---------------------------------------------------------------------
	 * \Groundhogg\escape_shortcodes()
	 * ------------------------------------------------------------------- */

	public function test_escape_shortcodes_encodes_registered_shortcode_in_html_context() {
		$this->assertSame(
			'before &#91;' . self::TEST_SHORTCODE . '&#93; after',
			escape_shortcodes( 'before [' . self::TEST_SHORTCODE . '] after', true )
		);
	}

	public function test_escape_shortcodes_strips_registered_shortcode_in_plain_context() {
		$this->assertSame(
			'before  after',
			escape_shortcodes( 'before [' . self::TEST_SHORTCODE . '] after', false )
		);
	}

	public function test_escape_shortcodes_ignores_unregistered_bracket_text() {
		$value = 'these [brackets] are not a real shortcode';
		$this->assertSame( $value, escape_shortcodes( $value, true ) );
	}

	public function test_escape_shortcodes_recurses_into_arrays() {
		$input = [ 'a' => '[' . self::TEST_SHORTCODE . ']', 'b' => 'fine' ];

		$expected = [ 'a' => '&#91;' . self::TEST_SHORTCODE . '&#93;', 'b' => 'fine' ];

		$this->assertSame( $expected, escape_shortcodes( $input, true ) );
	}

	/* ---------------------------------------------------------------------
	 * Replacements::do_replacement() nested vs. escaped branching
	 * ------------------------------------------------------------------- */

	public function test_nested_code_recursively_expands_embedded_tags() {
		$result = do_replacements( '{' . self::NESTED_CODE . '}', 0, 'html' );

		$this->assertSame( 'wrap-' . self::TARGET_VALUE . '-wrap', $result );
	}

	public function test_non_nested_code_does_not_expand_embedded_tags_in_html() {
		$result = do_replacements( '{' . self::PLAIN_CODE . '}', 0, 'html' );

		$this->assertNoSubstring( self::TARGET_VALUE, $result );
		$this->assertNoSubstring( '{' . self::TARGET_CODE . '}', $result );
		$this->assertSame( 'wrap-&#123;' . self::TARGET_CODE . '&#125;-wrap', $result );
	}

	public function test_non_nested_code_scrubs_embedded_tags_in_plain_text() {
		$result = do_replacements( '{' . self::PLAIN_CODE . '}', 0, 'plain' );

		$this->assertNoSubstring( self::TARGET_VALUE, $result );
		$this->assertSame( 'wrap--wrap', $result );
	}

	public function test_non_nested_code_neutralizes_embedded_shortcode() {
		$result = do_replacements( '{' . self::SHORTCODE_SOURCE_CODE . '}', 0, 'html' );

		// The shortcode text survives, unexpanded, but is no longer parsable by do_shortcode().
		$this->assertHasSubstring( self::TEST_SHORTCODE, $result );
		$this->assertNoSubstring( '[' . self::TEST_SHORTCODE . ']', $result );

		// Simulate the do_shortcode() pass that Email::get_merged_content() runs afterward.
		$this->assertNoSubstring( 'PWNED', do_shortcode( $result ) );
	}

	/* ---------------------------------------------------------------------
	 * Replacements::is_disallowed_user_key() via the {user.*} merge tag
	 * ------------------------------------------------------------------- */

	public function test_user_tag_withholds_disallowed_wp_user_properties() {
		$user_id = self::factory()->user->create( [
			'user_pass'  => 'Sup3rSecret!',
			'user_email' => 'gh-security-test@example.org',
		] );

		$contact = get_contactdata( $user_id, true );

		$this->assertSame( '', do_replacements( '{user.user_pass}', $contact ) );
		$this->assertSame(
			'gh-security-test@example.org',
			do_replacements( '{user.user_email}', $contact )
		);
	}

	public function test_user_tag_withholds_keys_matching_disallowed_patterns() {
		$user_id = self::factory()->user->create();
		update_user_meta( $user_id, 'my_api_key', 'ABCD-1234' );
		update_user_meta( $user_id, 'favorite_color', 'blue' );

		$contact = get_contactdata( $user_id, true );

		$this->assertSame( '', do_replacements( '{user.my_api_key}', $contact ) );
		$this->assertSame( 'blue', do_replacements( '{user.favorite_color}', $contact ) );
	}

	/* ---------------------------------------------------------------------
	 * Storage-time scrubbing: Contact::update() / add_meta() / update_meta()
	 * ------------------------------------------------------------------- */

	public function test_contact_update_scrubs_merge_tags_from_column_data() {
		$contact = get_contactdata( self::factory()->contacts->create() );

		$contact->update( [ 'first_name' => 'Bad {user.user_pass} Name' ] );

		$stored = $contact->get_first_name();
		$this->assertNoSubstring( '{', $stored );
		$this->assertNoSubstring( 'user_pass', $stored );
	}

	public function test_contact_update_meta_scrubs_merge_tags() {
		$contact = get_contactdata( self::factory()->contacts->create() );

		$contact->update_meta( 'gh_security_test_bio', 'Hi {user.user_pass} there' );

		$stored = $contact->get_meta( 'gh_security_test_bio' );
		$this->assertNoSubstring( '{', $stored );
		$this->assertNoSubstring( 'user_pass', $stored );
	}

	public function test_contact_add_meta_scrubs_merge_tags() {
		$contact = get_contactdata( self::factory()->contacts->create() );

		$contact->add_meta( 'gh_security_test_new_field', 'X {tag} Y' );

		$stored = $contact->get_meta( 'gh_security_test_new_field' );
		$this->assertSame( 'X  Y', $stored );
	}

	/* ---------------------------------------------------------------------
	 * \Groundhogg\generate_contact_with_map() note/tag scrubbing
	 * ------------------------------------------------------------------- */

	public function test_generate_contact_with_map_scrubs_notes() {
		$fields = [
			'email' => 'gh-security-map-test@example.org',
			'note'  => 'Imported note {user.user_pass} end',
		];

		$map = [
			'email' => 'email',
			'note'  => 'notes',
		];

		$contact = generate_contact_with_map( $fields, $map );

		$this->assertInstanceOf( Contact::class, $contact );

		$notes = $contact->get_notes();
		$this->assertNotEmpty( $notes );

		$content = $notes[0]->content;
		$this->assertNoSubstring( '{', $content );
		$this->assertNoSubstring( 'user_pass', $content );
	}
}
