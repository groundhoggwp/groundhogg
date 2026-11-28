<?php

use Groundhogg\AI\AI;
use Groundhogg\AI\Jobs\Translate_Email;
use Groundhogg\Email;
use Groundhogg\Email_Strings;
use Groundhogg\Email_Variations;
use Groundhogg\Multilingual;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\ModelMessage;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;

/**
 * An email's text as strings to translate, and how a variation keeps track of what it's translated from. See
 * Email_Strings, and the translate-email AI job.
 */
class Email_Strings_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		$this->factory()->truncate();
		Email_Variations::flush();
		Multilingual::flush();
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	public function tearDown(): void {
		remove_all_filters( 'groundhogg/multilingual/plugin' );
		remove_all_filters( 'groundhogg/multilingual/site_languages' );
		Email_Variations::flush();
		Multilingual::flush();
		parent::tearDown();
	}

	protected function fixture(): string {
		return file_get_contents( __DIR__ . '/abilities/fixtures/email-blocks/newsletter.html' );
	}

	protected function block_email( string $content = '' ): Email {
		$email = new Email();
		$email->create( [
			'title'      => 'Newsletter',
			'subject'    => 'Hello & welcome',
			'pre_header' => 'Read this',
			'content'    => $content ?: $this->fixture(),
			'status'     => 'ready',
		] );
		$email->update_meta( 'blocks', true );

		return new Email( $email->get_id() );
	}

	protected function variation( Email $email, string $locale = 'fr_FR' ): Email {
		$variation = Email_Variations::create( $email, [ 'audience' => [ 'locales' => [ $locale ] ], 'copy' => true ] );
		$this->assertInstanceOf( Email::class, $variation );

		return new Email( $variation->get_id() );
	}

	/* reading */

	public function test_the_strings_of_a_block_email() {

		$strings = Email_Strings::get( $this->block_email() );

		$this->assertSame( [
			'subject'          => 'Hello & welcome',
			'preview'          => 'Read this',
			'image-9a53ec.alt' => 'Logo',
			'heading-49807b'   => 'Round <1>trip</1> test',
			'text-cc143b'      => 'Hi {first},',
			'text-cc143b.2'    => 'Some <1>bold</1>, <2>italic</2> and a <3>link</3>.',
			'text-cc143b.3'    => 'A sub heading',
			'text-cc143b.4'    => 'One',
			'text-cc143b.5'    => 'Two & three',
			'text-cc143b.6'    => 'First',
			'text-cc143b.7'    => 'Centered',
			'button-d9eb66'    => 'Go & see',
			'button-bc0080'    => 'Small',
			'heading-790a3a'   => 'Col 1',
			'text-42ca47'      => 'One',
			'image-e9759a.alt' => 'Pic',
			'text-4aecd0'      => 'Thirty',
			'text-6b50c2'      => 'Seventy',
			'menu-00f930'      => 'Home',
			'menu-00f930.2'    => 'Blog',
			'html-941cd0'      => 'Raw <1>HTML</1> & things<2/>line two',
			'text-fbf290'      => 'Boxed text',
		], $strings );
	}

	public function test_the_strings_of_an_html_email() {

		$email = new Email();
		$email->create( [
			'title'   => 'HTML',
			'subject' => 'Hi',
			'content' => '<!DOCTYPE html><html><head><title>Page</title><style>p { color: red }</style></head><body><table><tr><td><p>Hello <b style="color:red">world</b><br>again</p></td><td>Cell &amp; more</td><td>•</td></tr></table></body></html>',
			'status'  => 'ready',
		] );
		$email->update_meta( 'type', 'html' );
		$email = new Email( $email->get_id() );

		$this->assertSame( 'html', $email->get_editor_type() );
		$this->assertSame( [
			'subject' => 'Hi',
			'text.1'  => 'Hello <1>world</1><2/>again',
			'text.2'  => 'Cell & more',
		], Email_Strings::get( $email ) );

		$result = Email_Strings::set( $email, [ 'text.1' => 'Bonjour <1>monde</1><2/>encore', 'text.2' => 'Cellule & plus' ] );
		$this->assertNotWPError( $result );

		$content = ( new Email( $email->get_id() ) )->get_content();
		$this->assertStringContainsString( '<p>Bonjour <b style="color:red">monde</b><br>encore</p>', $content );
		$this->assertStringContainsString( '<td>Cellule &amp; plus</td>', $content );
		$this->assertStringContainsString( '<title>Page</title>', $content );
	}

	/* writing */

	public function test_writing_strings_keeps_the_tags() {

		$email = $this->block_email();
		$before = $email->get_content();

		$result = Email_Strings::set( $email, [
			'subject'          => 'Bonjour & bienvenue',
			'text-cc143b.2'    => 'Du <1>gras</1>, de l\'<2>italique</2> et un <3>lien</3>.',
			'button-d9eb66'    => 'Allez & voyez',
			'image-9a53ec.alt' => 'Logo "FR"',
		] );

		$this->assertNotWPError( $result );
		$this->assertEqualSets( [ 'subject', 'text-cc143b.2', 'button-d9eb66', 'image-9a53ec.alt' ], $result['changed'] );

		$email   = new Email( $email->get_id() );
		$content = $email->get_content();

		$this->assertSame( 'Bonjour & bienvenue', $email->get_subject_line() );
		$this->assertStringContainsString( 'Du <strong style="font-weight: bold">gras</strong>, de l\'<em>italique</em> et un <a href="https://example.com/a?x=1&amp;y=2" style="color: #dd3333">lien</a>.', $content );
		$this->assertStringContainsString( 'background-color: #1e73be">Allez &amp; voyez</a>', $content );
		$this->assertStringContainsString( 'alt="Logo &quot;FR&quot;"', $content );

		// everything else is as it was
		$strings = Email_Strings::get( $email );
		$this->assertSame( 'Hi {first},', $strings['text-cc143b'] );
		$this->assertSame( 'Du <1>gras</1>, de l\'<2>italique</2> et un <3>lien</3>.', $strings['text-cc143b.2'] );
		$this->assertSame( strlen( $before ) > 0, strlen( $content ) > 0 );
	}

	public function test_a_lost_placeholder_changes_nothing() {

		$email  = $this->block_email();
		$before = $email->get_content();

		$result = Email_Strings::set( $email, [
			'text-cc143b'   => 'Bonjour {first},',
			'text-cc143b.2' => 'Du gras, de l\'italique et un lien.',
		] );

		$this->assertWPError( $result );
		$this->assertStringContainsString( 'text-cc143b.2', $result->get_error_message() );
		$this->assertSame( $before, ( new Email( $email->get_id() ) )->get_content() );

		$this->assertWPError( Email_Strings::set( $email, [ 'nope' => 'x' ] ) );
		$this->assertWPError( Email_Strings::set( $email, [ 'text-cc143b' => '<1>Bonjour</1>' ] ) );
	}

	public function test_the_valid_strings_can_be_written_alone() {

		$email = $this->block_email();

		$result = Email_Strings::set( $email, [
			'text-cc143b'   => 'Bonjour {first},',
			'text-cc143b.2' => 'Lost the placeholders',
		], true );

		$this->assertSame( [ 'text-cc143b' ], $result['changed'] );
		$this->assertArrayHasKey( 'text-cc143b.2', $result['skipped'] );
		$this->assertSame( 'Bonjour {first},', Email_Strings::get( new Email( $email->get_id() ) )['text-cc143b'] );
	}

	/* keeping track of translations */

	protected function statuses( Email $variation ): array {
		return array_column( Email_Strings::compare( new Email( $variation->get_id() ) )['strings'], 'status', 'key' );
	}

	public function test_a_copy_starts_untranslated() {

		$variation = $this->variation( $this->block_email() );

		$this->assertSame( [ 'untranslated' ], array_values( array_unique( $this->statuses( $variation ) ) ) );
		$this->assertSame( 22, Email_Strings::count_to_translate( $variation ) );
	}

	public function test_translated_strings_and_changes_to_the_email() {

		$email     = $this->block_email();
		$variation = $this->variation( $email );

		Email_Strings::set( $variation, [
			'subject'     => 'Bonjour',
			'text-cc143b' => 'Bonjour {first},',
			// a name, the same in both
			'menu-00f930' => 'Home',
		] );

		$statuses = $this->statuses( $variation );
		$this->assertSame( 'translated', $statuses['subject'] );
		$this->assertSame( 'translated', $statuses['text-cc143b'] );
		$this->assertSame( 'translated', $statuses['menu-00f930'] );
		$this->assertSame( 'untranslated', $statuses['preview'] );

		// the email changes
		Email_Strings::set( $email, [ 'text-cc143b' => 'Hello {first}!' ] );

		$compared = array_column( Email_Strings::compare( new Email( $variation->get_id() ) )['strings'], null, 'key' );
		$this->assertSame( 'changed', $compared['text-cc143b']['status'] );
		$this->assertSame( 'Hello {first}!', $compared['text-cc143b']['source'] );
		$this->assertSame( 'translated', $compared['subject']['status'] );

		// translated again
		Email_Strings::set( new Email( $variation->get_id() ), [ 'text-cc143b' => 'Bonjour {first} !' ] );
		$this->assertSame( 'translated', $this->statuses( $variation )['text-cc143b'] );
	}

	public function test_text_the_variation_is_missing() {

		$email     = $this->block_email( '<table cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><!--text:a {}--><td id="b-a"><p>One</p></td><!--/text:a--></tr></table>' );
		$variation = $this->variation( $email );

		$email->update( [ 'content' => '<table cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><!--text:a {}--><td id="b-a"><p>One</p></td><!--/text:a--></tr><tr><!--text:b {}--><td id="b-b"><p>Two</p></td><!--/text:b--></tr></table>' ] );

		$compared = Email_Strings::compare( new Email( $variation->get_id() ) );
		$this->assertSame( [ [ 'key' => 'b', 'source' => 'Two' ] ], $compared['missing'] );
	}

	public function test_the_text_in_an_html_block_is_translated() {

		$email     = $this->block_email();
		$variation = $this->variation( $email );

		$this->assertNotWPError( Email_Strings::set( $variation, [ 'html-941cd0' => 'Du <1>HTML</1> brut & plus<2/>ligne deux' ] ) );

		$variation = new Email( $variation->get_id() );

		$this->assertStringContainsString( '<div style="text-align:center;color:#888">Du <b>HTML</b> brut &amp; plus<br>ligne deux</div>', $variation->get_content() );
		$this->assertStringContainsString( 'Du **HTML** brut', $variation->get_alt_body(), 'and in the plain text' );
		$this->assertSame( 'translated', $this->statuses( $variation )['html-941cd0'] );

		// and tracked like the rest when the email changes
		Email_Strings::set( $email, [ 'html-941cd0' => 'Plain <1>HTML</1> & more<2/>line two' ] );
		$this->assertSame( 'changed', $this->statuses( $variation )['html-941cd0'] );
	}

	public function test_the_blocks_that_are_not_translated() {

		$row = function ( $type, $id, $props = '{}', $inner = '' ) {
			return '<tr><!--' . $type . ':' . $id . ' ' . $props . '--><td id="b-' . $id . '">' . $inner . '</td><!--/' . $type . ':' . $id . '--></tr>';
		};

		$email = $this->block_email( '<table cellpadding="0" cellspacing="0" width="100%" role="presentation">'
		                             . $row( 'text', 'a', '{}', '<p>Hello</p>' )
		                             . $row( 'global', 'g', '{"templateId":5}' )
		                             . $row( 'posts', 'p' )
		                             . $row( 'queryloop', 'q' )
		                             . $row( 'shortcode', 's', '{"shortcode":"[gallery]"}' )
		                             . $row( 'footer', 'f', '{}', '<div id="footer" class="footer"><p>Footer</p></div>' )
		                             . $row( 'video', 'v', '{}', '<a href="https://example.com/v"><img src="https://example.com/v.jpg" alt="Our video"></a>' )
		                             . '</table>' );

		$this->assertSame( [ 'g' => 'global', 'p' => 'posts', 'q' => 'queryloop', 's' => 'shortcode' ], array_column( Email_Strings::not_translated( $email ), 'type', 'id' ), 'not the footer, which is made in the contact\'s language' );

		foreach ( Email_Strings::not_translated( $email ) as $block ) {
			$this->assertNotEmpty( $block['note'] );
		}

		$this->assertSame( [ 'subject', 'preview', 'a', 'v.alt' ], array_keys( Email_Strings::get( $email ) ), 'a video\'s alt text is a string, like an image\'s' );

		// the editor shows them for a version in another language, but a test version isn't a translation
		$variation = $this->variation( $email );
		$test      = Email_Variations::create( $email, [ 'kind' => 'test', 'copy' => true ] );
		$this->assertInstanceOf( Email::class, $test );

		$data = array_column( Email_Variations::editor_data( $email )['variations'], 'not_translated', 'id' );
		$this->assertCount( 4, $data[ $variation->get_id() ] );
		$this->assertSame( [], $data[ $test->get_id() ] );

		// an HTML email has no blocks
		$html = new Email();
		$html->create( [ 'title' => 'HTML', 'subject' => 'Hi', 'content' => '<p>Hello</p>', 'status' => 'ready' ] );
		$html->update_meta( 'type', 'html' );
		$this->assertSame( [], Email_Strings::not_translated( new Email( $html->get_id() ) ) );
	}

	/* the footer, in the language the email is sent in */

	/**
	 * @var WP_Locale_Switcher|null the real one, while switchable() has swapped it
	 */
	protected $switcher = null;

	/**
	 * Let the locale be switched to languages WordPress doesn't have installed in the tests
	 *
	 * @param string[] $installed
	 */
	protected function switchable( array $installed ) {
		$this->switcher = $GLOBALS['wp_locale_switcher'];
		$available      = fn() => $installed;

		add_filter( 'get_available_languages', $available );
		$GLOBALS['wp_locale_switcher'] = new WP_Locale_Switcher();
		$GLOBALS['wp_locale_switcher']->init();
		remove_filter( 'get_available_languages', $available );
	}

	protected function unswitchable() {

		while ( is_locale_switched() ) {
			restore_previous_locale();
		}

		if ( $this->switcher ) {
			remove_filter( 'locale', [ $GLOBALS['wp_locale_switcher'], 'filter_locale' ] );
			remove_filter( 'determine_locale', [ $GLOBALS['wp_locale_switcher'], 'filter_locale' ] );
			$GLOBALS['wp_locale_switcher'] = $this->switcher;
			$this->switcher                = null;
		}
	}

	protected function site_in( array $locales ) {
		add_filter( 'groundhogg/multilingual/plugin', fn() => 'test' );
		add_filter( 'groundhogg/multilingual/site_languages', fn() => array_map( fn( $locale ) => [
			'locale'  => $locale,
			'name'    => $locale,
			'default' => $locale === 'en_US',
		], array_combine( $locales, $locales ) ) );
		Multilingual::flush();
	}

	/**
	 * The email's footer as it's sent, the HTML and the plain text, and the same without the footer being made again
	 *
	 * @param Email $email
	 *
	 * @return array [ html, plain, saved html, saved plain ]
	 */
	protected function footers( Email $email ): array {

		$registry = \Groundhogg\Block_Registry::instance();
		\Groundhogg\the_email( $email );

		$sent = [ $registry->parse_blocks( $email->get_content(), 'html' ), $registry->parse_blocks( $email->get_alt_body(), 'plain' ) ];

		remove_filter( 'groundhogg/email/block_render', [ $registry, 'footer' ] );
		$saved = [ $registry->parse_blocks( $email->get_content(), 'html' ), $registry->parse_blocks( $email->get_alt_body(), 'plain' ) ];
		add_filter( 'groundhogg/email/block_render', [ $registry, 'footer' ], 10, 3 );

		return array_merge( $sent, $saved );
	}

	protected function with_plain_text( Email $email ): Email {
		// like the editor saves it
		$made = ( new \Groundhogg\Abilities\Emails\Email_Block_Renderer( \Groundhogg\Abilities\Emails\Email_Block_Renderer::context_of( $email ) ) )->css_and_plain_text( $email->get_content() );
		$email->update( [ 'plain_text' => $made['plain_text'] ] );

		return new Email( $email->get_id() );
	}

	public function test_the_footer_is_sent_as_it_was_saved_on_a_site_in_one_language() {

		$marketing     = $this->with_plain_text( $this->block_email() );
		$transactional = $this->with_plain_text( $this->block_email() );
		$transactional->update( [ 'message_type' => 'transactional' ] );
		$transactional = new Email( $transactional->get_id() );

		$this->switchable( [ 'fr_FR' ] );

		try {
			foreach ( [ $marketing, $transactional ] as $email ) {

				[ $html, $plain, $saved_html, $saved_plain ] = $this->footers( $email );
				$this->assertSame( $saved_html, $html );
				$this->assertSame( $saved_plain, $plain );
				$this->assertStringContainsString( '#unsubscribe_link#', $html );

				// even sent to a contact in another language, the site has only the one
				switch_to_locale( 'fr_FR' );
				[ $html, $plain, $saved_html, $saved_plain ] = $this->footers( $email );
				restore_previous_locale();

				$this->assertSame( $saved_html, $html );
				$this->assertSame( $saved_plain, $plain );
			}
		} finally {
			$this->unswitchable();
		}
	}

	public function test_the_footer_is_made_in_the_language_the_email_is_sent_in() {

		$this->site_in( [ 'en_US', 'fr_FR' ] );
		$this->switchable( [ 'fr_FR' ] );

		$email = $this->with_plain_text( $this->block_email() );

		try {
			// in the site's default language it's the saved one
			[ $html, $plain, $saved_html ] = $this->footers( $email );
			$this->assertSame( $saved_html, $html );

			switch_to_locale( 'fr_FR' );
			[ $html, $plain, $saved_html ] = $this->footers( $email );

			$this->assertStringContainsString( 'Se désabonner', $html );
			$this->assertStringContainsString( 'href="#unsubscribe_link#"', $html, 'the link is still replaced when it\'s sent' );
			$this->assertStringNotContainsString( '>Unsubscribe<', $html );
			$this->assertStringContainsString( '<td id="b-footer-28eb52"', $html, 'the block around it stays' );
			$this->assertSame( 1, substr_count( $html, '<div id="footer"' ) );
			$this->assertStringContainsString( 'Se désabonner', $plain );

			// a transactional email has no unsubscribe line
			$email->update( [ 'message_type' => 'transactional' ] );
			[ $html ] = $this->footers( new Email( $email->get_id() ) );
			$this->assertStringNotContainsString( 'Se désabonner', $html );
			$this->assertStringNotContainsString( '#unsubscribe_link#', $html );
			$this->assertStringContainsString( '<div id="footer"', $html );

			restore_previous_locale();
		} finally {
			$this->unswitchable();
		}
	}

	public function test_the_footer_made_again_is_the_one_the_editor_saves() {

		// the settings of the site the fixture was saved on, which its footer was made from
		foreach ( json_decode( file_get_contents( __DIR__ . '/abilities/fixtures/email-blocks/settings.json' ), true ) as $name => $value ) {
			update_option( $name, $value );
		}

		// made again in the site's own language, so it should be what the editor saved
		add_filter( 'groundhogg/email/footer/follows_locale', '__return_true' );

		$footer = function ( string $html ): string {
			$this->assertSame( 1, preg_match( '/<div id="footer".*?<\/div>/s', $html, $match ) );

			return $match[0];
		};

		try {
			$marketing = $this->with_plain_text( $this->block_email() );
			$saved     = $footer( $marketing->get_content() );

			[ $html, $plain, $saved_html, $saved_plain ] = $this->footers( $marketing );
			$this->assertSame( $saved, $footer( $html ) );
			$this->assertSame( $saved_html, $html );
			$this->assertSame( $saved_plain, $plain );

			// the editor leaves the unsubscribe line out of a transactional email's footer
			$transactional = $this->with_plain_text( $this->block_email() );
			$transactional->update( [ 'message_type' => 'transactional' ] );
			$transactional = new Email( $transactional->get_id() );

			[ $html, $plain, $saved_html, $saved_plain ] = $this->footers( $transactional );
			$this->assertSame( preg_replace( '/<p [^>]*>Don\'t want these emails\?.*?<\/p>/s', '', $saved ), $footer( $html ) );
			$this->assertSame( $saved_plain, $plain );
		} finally {
			remove_filter( 'groundhogg/email/footer/follows_locale', '__return_true' );
		}
	}

	public function test_the_plugins_own_translations_are_loaded_when_the_locale_is_switched() {

		global $wp_textdomain_registry;

		$this->switchable( [ 'fr_FR' ] );

		// an add-on that uses the same text domain registers its own folder, which WordPress loads it from after a switch
		$wp_textdomain_registry->set_custom_path( 'groundhogg', get_temp_dir() . 'an-add-on/languages' );

		try {
			switch_to_locale( 'fr_FR' );
			$this->assertSame( 'Se désabonner', __( 'Unsubscribe', 'groundhogg' ) );

			restore_previous_locale();
			$this->assertSame( 'Unsubscribe', __( 'Unsubscribe', 'groundhogg' ) );
		} finally {
			$wp_textdomain_registry->set_custom_path( 'groundhogg', GROUNDHOGG_PATH . 'languages' );
			$this->unswitchable();
		}
	}

	public function test_translations_made_in_the_editor_are_remembered() {

		$email     = $this->block_email();
		$variation = $this->variation( $email );

		// someone translates a paragraph in the editor, which saves the content
		$variation->update( [ 'content' => str_replace( '>Boxed text<', '>Texte encadré<', $variation->get_content() ) ] );
		do_action( 'groundhogg/api/email/updated', new Email( $variation->get_id() ) );

		$this->assertSame( 'translated', $this->statuses( $variation )['text-fbf290'] );

		Email_Strings::set( $email, [ 'text-fbf290' => 'Text in a box' ] );

		$this->assertSame( 'changed', $this->statuses( $variation )['text-fbf290'] );
	}

	/* abilities */

	protected function ability( string $name, array $input ) {
		$out = wp_get_ability( $name )->execute( $input );

		return is_wp_error( $out ) ? $out : json_decode( wp_json_encode( $out ), true );
	}

	public function test_the_abilities() {

		if ( ! function_exists( 'wp_get_ability' ) || ! wp_get_ability( 'groundhogg/get-email-strings' ) ) {
			$this->markTestSkipped( 'The email string abilities are not registered.' );
		}

		$email     = $this->block_email();
		$variation = $this->variation( $email );

		$read = $this->ability( 'groundhogg/get-email-strings', [ 'email_id' => $email->get_id() ] );
		$this->assertSame( 'Hi {first},', array_column( $read['strings'], 'text', 'key' )['text-cc143b'] );
		$this->assertArrayNotHasKey( 'status', $read['strings'][0] );

		$read = $this->ability( 'groundhogg/get-email-strings', [ 'email_id' => $variation->get_id(), 'needs_translation' => true ] );
		$this->assertSame( 'fr_FR', $read['locale'] );
		$this->assertCount( 22, $read['strings'] );
		$this->assertSame( 'untranslated', $read['strings'][0]['status'] );
		// the fixture's footer is made in the contact's language when it's sent, nothing else is left out
		$this->assertSame( [], $read['not_translated'] );

		$set = $this->ability( 'groundhogg/set-email-strings', [
			'email_id'         => $variation->get_id(),
			'expected_version' => $read['version'],
			'strings'          => [ 'subject' => 'Bonjour', 'text-cc143b' => 'Bonjour {first},' ],
		] );

		$this->assertNotWPError( $set );
		$this->assertEqualSets( [ 'subject', 'text-cc143b' ], $set['changed'] );
		$this->assertSame( 20, $set['left_to_translate'] );
		$this->assertSame( [], $set['not_translated'] );

		$this->assertWPError( $this->ability( 'groundhogg/set-email-strings', [
			'email_id'         => $variation->get_id(),
			'expected_version' => $read['version'],
			'strings'          => [ 'preview' => 'Lisez' ],
		] ) );

		$read = $this->ability( 'groundhogg/get-email-strings', [ 'email_id' => $variation->get_id(), 'needs_translation' => true ] );
		$this->assertCount( 20, $read['strings'] );
	}

	/* the email editor */

	public function test_the_editor_route() {

		add_filter( 'groundhogg/multilingual/plugin', fn() => 'test' );
		add_filter( 'groundhogg/multilingual/site_languages', fn() => [
			'en_US' => [ 'locale' => 'en_US', 'name' => 'English', 'default' => true ],
			'fr_FR' => [ 'locale' => 'fr_FR', 'name' => 'Français', 'default' => false ],
			'de_DE' => [ 'locale' => 'de_DE', 'name' => 'Deutsch', 'default' => false ],
		] );
		Multilingual::flush();

		$email = $this->block_email();

		$request = new WP_REST_Request( 'POST', '/gh/v4/emails/' . $email->get_id() . '/variations' );
		$request->set_body_params( [ 'locale' => 'fr_FR' ] );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['supported'] );
		$this->assertCount( 1, $data['variations'] );
		$this->assertSame( [ 'Français' ], $data['variations'][0]['languages'] );
		$this->assertSame( 22, $data['variations'][0]['to_translate'] );
		$this->assertSame( [], $data['variations'][0]['not_translated'] );
		$this->assertSame( [ 'de_DE' ], array_column( $data['languages'], 'locale' ) );
		$this->assertSame( 'Newsletter (Français)', ( new Email( $data['created'] ) )->get_title() );

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/gh/v4/emails/' . $data['created'] . '/variations' ) );
		$this->assertTrue( $response->get_data()['is_variation'] );
		$this->assertSame( $email->get_id(), $response->get_data()['original']['id'] );
	}

	public function test_the_languages_to_add() {

		add_filter( 'groundhogg/multilingual/plugin', fn() => 'test' );
		add_filter( 'groundhogg/multilingual/site_languages', fn() => [
			'en_US' => [ 'locale' => 'en_US', 'name' => 'English', 'default' => true ],
			'es_ES' => [ 'locale' => 'es_ES', 'name' => 'Español', 'default' => false ],
		] );
		Multilingual::flush();

		$email = $this->block_email();

		// only the site's languages, with how many contacts speak each
		foreach ( [ 'es_ES', 'es_ES', 'ja' ] as $locale ) {
			( new \Groundhogg\Contact( self::factory()->contacts->create() ) )->set_locale( $locale );
		}

		$data = Email_Variations::editor_data( $email );
		$this->assertSame( [ [ 'locale' => 'es_ES', 'name' => 'Español', 'contacts' => 2 ] ], $data['languages'] );

		// a template has none to offer, it can't have variations
		$template = $this->block_email();
		$template->update( [ 'is_template' => 1 ] );
		$data = Email_Variations::editor_data( new Email( $template->get_id() ) );
		$this->assertFalse( $data['supported'] );
		$this->assertSame( [], $data['languages'] );
	}

	/* the AI job */

	protected static function result( string $text ): GenerativeAiResult {
		return new GenerativeAiResult(
			'result-1',
			[ new Candidate( new ModelMessage( [ new MessagePart( $text ) ] ), FinishReasonEnum::stop() ) ],
			new TokenUsage( 10, 10, 20 ),
			new ProviderMetadata( 'fake-provider', 'Fake Provider', ProviderTypeEnum::cloud() ),
			new ModelMetadata( 'fake-model', 'Fake Model', [], [] )
		);
	}

	public function test_the_translate_job() {

		if ( ! AI::is_supported() ) {
			$this->markTestSkipped( 'Needs WordPress 7.0 for the AI client.' );
		}

		$email     = $this->block_email();
		$variation = $this->variation( $email );
		$job       = new Translate_Email();

		$this->assertWPError( $job->validate( [ 'email_id' => $email->get_id() ] ) );

		$input = $job->validate( [ 'email_id' => $variation->get_id() ] );
		$this->assertNotWPError( $input );
		$this->assertSame( 'fr_FR', $input['locale'] );
		$this->assertCount( 22, $input['strings'] );
		$this->assertSame( 'Some <1>bold</1>, <2>italic</2> and a <3>link</3>.', $input['strings']['text-cc143b.2'] );

		$response = $job->respond( self::result( wp_json_encode( [
			'strings' => [
				[ 'key' => 'subject', 'text' => 'Bonjour & bienvenue' ],
				[ 'key' => 'text-cc143b.2', 'text' => 'Du <1>gras</1>, de l\'<2>italique</2> et un <3>lien</3>.' ],
				// lost its placeholder, left for next time
				[ 'key' => 'heading-49807b', 'text' => 'Test aller-retour' ],
				// not asked for
				[ 'key' => 'invented', 'text' => 'x' ],
			],
		] ) ), $input );

		$this->assertNotWPError( $response );
		$this->assertSame( 2, $response['translated'] );
		$this->assertSame( [ 'heading-49807b' ], $response['skipped'] );
		$this->assertSame( 20, $response['left_to_translate'] );
		$this->assertSame( [], $response['not_translated'] );

		$strings = Email_Strings::get( new Email( $variation->get_id() ) );
		$this->assertSame( 'Bonjour & bienvenue', $strings['subject'] );
		$this->assertSame( 'Round <1>trip</1> test', $strings['heading-49807b'] );

		$this->assertSame( 'ai_empty', $job->respond( self::result( 'not json' ), $input )->get_error_code() );
	}
}
