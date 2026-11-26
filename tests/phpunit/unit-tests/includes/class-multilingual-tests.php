<?php

use Groundhogg\Abilities\Schemas\Contact_Schema;
use Groundhogg\Abilities\Schemas\Segment_Schema;
use Groundhogg\Contact;
use Groundhogg\Contact_Query;
use Groundhogg\Multilingual;
use Groundhogg\Preferences;

/**
 * A contact's language: Multilingual, the locale segment filter, locale capture from forms, and locale in the
 * contact and segment schemas the abilities use.
 */
class Multilingual_Tests extends GH_UnitTestCase {

	/**
	 * @var int[] contact IDs keyed by label
	 */
	protected $contacts = [];

	public function setUp(): void {
		parent::setUp();
		$this->factory()->truncate();
		$this->contacts = [];
		Multilingual::flush();
	}

	public function tearDown(): void {
		remove_all_filters( 'groundhogg/multilingual/plugin' );
		remove_all_filters( 'groundhogg/multilingual/site_languages' );
		remove_all_filters( 'groundhogg/multilingual/current_locale' );
		remove_all_filters( 'groundhogg/multilingual/default_locale' );
		remove_all_filters( 'groundhogg/multilingual/update_locale_from_form' );
		unset( $_POST[ Multilingual::FORM_FIELD ] );
		Multilingual::flush();
		parent::tearDown();
	}

	/**
	 * Pretend a multilingual plugin is active with these languages, the first being the default
	 *
	 * @param string[] $locales
	 * @param string   $current the language the visitor is browsing in
	 */
	protected function fake_multilingual( array $locales, string $current = '' ) {

		add_filter( 'groundhogg/multilingual/plugin', fn() => 'test' );
		add_filter( 'groundhogg/multilingual/default_locale', fn() => $locales[0] );
		add_filter( 'groundhogg/multilingual/site_languages', function () use ( $locales ) {
			$languages = [];
			foreach ( $locales as $i => $locale ) {
				$languages[ $locale ] = [ 'locale' => $locale, 'name' => $locale, 'default' => $i === 0 ];
			}

			return $languages;
		} );

		if ( $current ) {
			add_filter( 'groundhogg/multilingual/current_locale', fn() => $current );
		}

		Multilingual::flush();
	}

	/**
	 * Contacts keyed by label, each with the given locale meta ('' for none)
	 *
	 * @param array $locales label => locale
	 */
	protected function make_contacts( array $locales ) {
		foreach ( $locales as $label => $locale ) {
			$id      = $this->factory()->contacts->create( [ 'optin_status' => Preferences::CONFIRMED ] );
			$contact = new Contact( $id );
			// creating a contact sets its locale, start from what the test says
			$contact->delete_meta( 'locale' );
			if ( $locale ) {
				$contact->update_meta( 'locale', $locale );
			}
			$this->contacts[ $label ] = $id;
		}
	}

	protected function labels( array $contacts ): array {
		$ids    = array_map( fn( $contact ) => absint( is_object( $contact ) ? $contact->ID : $contact ), $contacts );
		$labels = array_keys( array_intersect( $this->contacts, $ids ) );
		sort( $labels );

		return $labels;
	}

	protected function query_locales( $locales ): array {
		$query = new Contact_Query();

		return $this->labels( $query->query( [
			'include_filters' => [ [ [ 'type' => 'locale', 'locales' => $locales ] ] ]
		] ) );
	}

	/* sanitize_locale */

	public function test_sanitize_locale() {
		$this->assertSame( 'fr_FR', Multilingual::sanitize_locale( 'fr_FR' ) );
		$this->assertSame( 'fr_FR', Multilingual::sanitize_locale( 'fr-fr' ) );
		$this->assertSame( 'pt_BR', Multilingual::sanitize_locale( ' pt-BR ' ) );
		$this->assertSame( 'de_DE_formal', Multilingual::sanitize_locale( 'de_DE_formal' ) );
		$this->assertSame( 'ja', Multilingual::sanitize_locale( 'ja' ) );
		$this->assertSame( '', Multilingual::sanitize_locale( "fr_FR' OR 1=1" ) );
		$this->assertSame( '', Multilingual::sanitize_locale( '' ) );
		$this->assertSame( '', Multilingual::sanitize_locale( [ 'fr_FR' ] ) );
		$this->assertSame( '', Multilingual::sanitize_locale( 'french' ) );
	}

	/* the locale segment filter */

	public function test_locale_filter_matches_the_default_language() {

		$this->make_contacts( [ 'none' => '', 'english' => 'en_US', 'french' => 'fr_FR' ] );

		// en_US used to match nobody: it isn't in wp_get_available_translations()
		$this->assertSame( [ 'english', 'none' ], $this->query_locales( [ 'en_US' ] ) );
		$this->assertSame( [ 'french' ], $this->query_locales( [ 'fr_FR' ] ) );
		$this->assertSame( [ 'english', 'french', 'none' ], $this->query_locales( [ 'en_US', 'fr-FR' ] ) );
		$this->assertSame( [], $this->query_locales( [ 'not a locale' ] ) );
	}

	public function test_locale_filter_uses_the_multilingual_default() {

		$this->fake_multilingual( [ 'de_DE', 'en_US' ] );

		$this->make_contacts( [ 'none' => '', 'english' => 'en_US' ] );

		$this->assertSame( [ 'none' ], $this->query_locales( [ 'de_DE' ] ) );
		$this->assertSame( [ 'english' ], $this->query_locales( [ 'en_US' ] ) );
	}

	/* the contact's locale */

	public function test_contact_locale_defaults_to_the_site_language() {

		$this->make_contacts( [ 'none' => '' ] );
		$contact = new Contact( $this->contacts['none'] );

		$this->assertSame( 'en_US', $contact->get_locale() );

		$this->fake_multilingual( [ 'de_DE', 'en_US' ] );
		$this->assertSame( 'de_DE', $contact->get_locale() );

		$contact->set_locale( 'fr-fr' );
		$this->assertSame( 'fr_FR', $contact->get_locale() );
		$this->assertSame( 'fr_FR', $contact->get_meta( 'locale' ) );
	}

	/* site languages */

	public function test_site_languages_without_a_plugin() {

		$this->assertFalse( Multilingual::is_active() );
		$this->assertSame( [ 'en_US' ], Multilingual::get_site_locales() );

		$languages = Multilingual::get_site_languages();
		$this->assertTrue( $languages[0]['default'] );
		$this->assertSame( 'English (United States)', $languages[0]['name'] );
	}

	public function test_site_languages_default_first() {

		$this->fake_multilingual( [ 'fr_FR', 'en_US', 'de_DE' ] );

		$this->assertTrue( Multilingual::is_active() );
		$this->assertSame( [ 'fr_FR', 'en_US', 'de_DE' ], Multilingual::get_site_locales() );
		$this->assertTrue( Multilingual::is_site_locale( 'de-DE' ) );
		$this->assertFalse( Multilingual::is_site_locale( 'es_ES' ) );
	}

	/* forms */

	public function test_form_input_only_when_multilingual() {

		$this->assertSame( '', Multilingual::get_form_input() );

		$this->fake_multilingual( [ 'en_US', 'fr_FR' ], 'fr_FR' );

		$input = Multilingual::get_form_input();
		$this->assertStringContainsString( 'type="hidden"', $input );
		$this->assertStringContainsString( 'name="' . Multilingual::FORM_FIELD . '"', $input );
		$this->assertStringContainsString( 'value="fr_FR"', $input );
	}

	public function test_form_submission_sets_the_locale() {

		$this->fake_multilingual( [ 'en_US', 'fr_FR' ] );
		$this->make_contacts( [ 'contact' => 'en_US' ] );
		$contact = new Contact( $this->contacts['contact'] );

		$_POST[ Multilingual::FORM_FIELD ] = 'fr_FR';
		do_action( 'groundhogg/form/v2/submit', null, null, $contact );

		$this->assertSame( 'fr_FR', ( new Contact( $contact->get_id() ) )->get_meta( 'locale' ) );
	}

	public function test_form_submission_ignores_languages_the_site_is_not_in() {

		$this->fake_multilingual( [ 'en_US', 'fr_FR' ] );
		$this->make_contacts( [ 'contact' => 'en_US' ] );
		$contact = new Contact( $this->contacts['contact'] );

		$_POST[ Multilingual::FORM_FIELD ] = 'es_ES';
		do_action( 'groundhogg/form/v2/submit', null, null, $contact );

		$this->assertSame( 'en_US', ( new Contact( $contact->get_id() ) )->get_meta( 'locale' ) );
	}

	public function test_form_submission_does_nothing_on_a_single_language_site() {

		$this->make_contacts( [ 'contact' => 'en_US' ] );
		$contact = new Contact( $this->contacts['contact'] );

		$_POST[ Multilingual::FORM_FIELD ] = 'en_GB';
		do_action( 'groundhogg/form/v2/submit', null, null, $contact );

		$this->assertSame( 'en_US', ( new Contact( $contact->get_id() ) )->get_meta( 'locale' ) );
	}

	public function test_form_submission_can_be_stopped_from_changing_the_locale() {

		$this->fake_multilingual( [ 'en_US', 'fr_FR' ] );
		$this->make_contacts( [ 'contact' => 'en_US' ] );
		$contact = new Contact( $this->contacts['contact'] );

		add_filter( 'groundhogg/multilingual/update_locale_from_form', '__return_false' );

		$_POST[ Multilingual::FORM_FIELD ] = 'fr_FR';
		do_action( 'groundhogg/form/v2/submit', null, null, $contact );

		$this->assertSame( 'en_US', ( new Contact( $contact->get_id() ) )->get_meta( 'locale' ) );
	}

	/* abilities */

	public function test_segment_schema_locales() {

		$this->make_contacts( [ 'none' => '', 'english' => 'en_US', 'french' => 'fr_FR', 'german' => 'de_DE' ] );

		$this->assertTrue( Segment_Schema::has_audience( [ 'locales' => [ 'fr_FR' ] ] ) );

		$this->assertSame( [ [ [ 'type' => 'locale', 'locales' => [ 'fr_FR', 'de_DE' ] ] ] ], Segment_Schema::to_filters( [ 'locales' => [ 'fr-FR', 'de_DE' ] ] ) );

		foreach ( [ [ [ 'french', 'german' ], [ 'fr_FR', 'de_DE' ] ], [ [ 'english', 'none' ], [ 'en_US' ] ] ] as [ $expected, $locales ] ) {

			$query = Segment_Schema::to_contact_query( [ 'locales' => $locales ] );
			$this->assertNotWPError( $query );
			$this->assertSame( $expected, $this->labels( $query->query() ) );

			$filters = Segment_Schema::to_filters( [ 'locales' => $locales ] );
			$this->assertSame( $expected, $this->labels( ( new Contact_Query() )->query( [ 'include_filters' => $filters ] ) ) );
		}

		$this->assertWPError( Segment_Schema::to_query( [ 'locales' => [ 'nope nope' ] ] ) );
		$this->assertWPError( Segment_Schema::to_filters( [ 'locales' => [ 'nope nope' ] ] ) );
	}

	public function test_contact_schema_has_the_locale() {

		$this->make_contacts( [ 'none' => '', 'french' => 'fr_FR' ] );

		$this->assertSame( 'en_US', Contact_Schema::transform( new Contact( $this->contacts['none'] ) )['locale'] );
		$this->assertSame( 'fr_FR', Contact_Schema::transform( new Contact( $this->contacts['french'] ) )['locale'] );
	}

	public function test_update_contact_sets_the_locale() {

		if ( ! function_exists( 'wp_get_ability' ) || ! wp_get_ability( 'groundhogg/update-contact' ) ) {
			$this->markTestSkipped( 'The contact abilities are not registered.' );
		}

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->make_contacts( [ 'contact' => '' ] );

		$result = wp_get_ability( 'groundhogg/update-contact' )->execute( [ 'id' => $this->contacts['contact'], 'locale' => 'fr-FR' ] );
		$this->assertNotWPError( $result );
		$this->assertSame( 'fr_FR', $result['contact']['locale'] );

		$result = wp_get_ability( 'groundhogg/update-contact' )->execute( [ 'id' => $this->contacts['contact'], 'locale' => 'French' ] );
		$this->assertWPError( $result );
		$this->assertSame( 'groundhogg_invalid_locale', $result->get_error_code() );
		$this->assertSame( 'fr_FR', ( new Contact( $this->contacts['contact'] ) )->get_locale() );
	}

	public function test_bulk_update_contacts_sets_the_locale() {

		if ( ! function_exists( 'wp_get_ability' ) || ! wp_get_ability( 'groundhogg/bulk-update-contacts' ) ) {
			$this->markTestSkipped( 'The contact abilities are not registered.' );
		}

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$this->factory()->truncate(); // the user got a contact

		$this->make_contacts( [ 'a' => '', 'b' => 'en_US', 'c' => 'de_DE' ] );

		$result = wp_get_ability( 'groundhogg/bulk-update-contacts' )->execute( [
			'locales' => [ 'en_US' ],
			'changes' => [ 'locale' => 'fr_FR' ],
		] );

		$this->assertNotWPError( $result );
		$this->assertSame( 2, $result['updated'] );
		$this->assertSame( [ 'a', 'b' ], $this->query_locales( [ 'fr_FR' ] ) );
		$this->assertSame( [ 'c' ], $this->query_locales( [ 'de_DE' ] ) );
	}

	public function test_setup_status_lists_languages() {

		if ( ! function_exists( 'wp_get_ability' ) || ! wp_get_ability( 'groundhogg/get-setup-status' ) ) {
			$this->markTestSkipped( 'The setup abilities are not registered.' );
		}

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$this->factory()->truncate(); // the user got a contact

		$this->fake_multilingual( [ 'en_US', 'fr_FR' ] );
		$this->make_contacts( [ 'a' => '', 'b' => 'en_US', 'c' => 'fr_FR', 'd' => 'es_ES', 'e' => 'es_ES' ] );

		$status    = wp_get_ability( 'groundhogg/get-setup-status' )->execute( [] );
		$languages = $status['languages'];

		$this->assertSame( 'test', $languages['plugin'] );
		$this->assertSame( 'en_US', $languages['default'] );
		$this->assertSame( [ 'en_US' => 2, 'fr_FR' => 1 ], array_column( $languages['site'], 'contacts', 'locale' ) );
		$this->assertSame( [ 'es_ES' => 2 ], array_column( $languages['other'], 'contacts', 'locale' ) );
	}
}
