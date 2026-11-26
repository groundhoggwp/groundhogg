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
		$this->assertSame( 21, Email_Strings::count_to_translate( $variation ) );
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
		$this->assertCount( 21, $read['strings'] );
		$this->assertSame( 'untranslated', $read['strings'][0]['status'] );

		$set = $this->ability( 'groundhogg/set-email-strings', [
			'email_id'         => $variation->get_id(),
			'expected_version' => $read['version'],
			'strings'          => [ 'subject' => 'Bonjour', 'text-cc143b' => 'Bonjour {first},' ],
		] );

		$this->assertNotWPError( $set );
		$this->assertEqualSets( [ 'subject', 'text-cc143b' ], $set['changed'] );
		$this->assertSame( 19, $set['left_to_translate'] );

		$this->assertWPError( $this->ability( 'groundhogg/set-email-strings', [
			'email_id'         => $variation->get_id(),
			'expected_version' => $read['version'],
			'strings'          => [ 'preview' => 'Lisez' ],
		] ) );

		$read = $this->ability( 'groundhogg/get-email-strings', [ 'email_id' => $variation->get_id(), 'needs_translation' => true ] );
		$this->assertCount( 19, $read['strings'] );
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
		$this->assertSame( 21, $data['variations'][0]['to_translate'] );
		$this->assertSame( [ 'de_DE' ], array_column( $data['languages'], 'locale' ) );
		$this->assertSame( 'Newsletter (Français)', ( new Email( $data['created'] ) )->get_title() );

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/gh/v4/emails/' . $data['created'] . '/variations' ) );
		$this->assertTrue( $response->get_data()['is_variation'] );
		$this->assertSame( $email->get_id(), $response->get_data()['original']['id'] );
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
		$this->assertCount( 21, $input['strings'] );
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
		$this->assertSame( 19, $response['left_to_translate'] );

		$strings = Email_Strings::get( new Email( $variation->get_id() ) );
		$this->assertSame( 'Bonjour & bienvenue', $strings['subject'] );
		$this->assertSame( 'Round <1>trip</1> test', $strings['heading-49807b'] );

		$this->assertSame( 'ai_empty', $job->respond( self::result( 'not json' ), $input )->get_error_code() );
	}
}
