<?php

use Groundhogg\Classes\Activity;
use Groundhogg\Contact;
use Groundhogg\Contact_Query;
use Groundhogg\Email;
use Groundhogg\Email_Variations;
use Groundhogg\Event;
use Groundhogg\Event_Queue_Item;
use Groundhogg\Preferences;
use Groundhogg\Steps\Actions\Send_Email;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;
use function Groundhogg\is_sending;

/**
 * Email variations: other versions of an email, like the email in another language, sent instead of it to the
 * contacts in their audience. See Email_Variations.
 */
class Email_Variations_Tests extends GH_UnitTestCase {

	/**
	 * @var string[] the subjects sent, in order
	 */
	protected $sent = [];

	public function setUp(): void {
		parent::setUp();
		$this->factory()->truncate();
		Email_Variations::flush();
		is_sending( false );
		$this->sent = [];
		add_action( 'groundhogg/email/before_send', [ $this, 'record_send' ], 10, 3 );
	}

	public function tearDown(): void {
		remove_action( 'groundhogg/email/before_send', [ $this, 'record_send' ], 10 );
		remove_all_filters( 'groundhogg/email/variation/resolve' );
		Email_Variations::flush();
		is_sending( false );
		parent::tearDown();
	}

	public function record_send( $email, $to, $subject ) {
		$this->sent[] = $subject;
	}

	protected function email( array $args = [] ): Email {
		$email = new Email();
		$email->create( array_merge( [
			'title'   => 'Welcome',
			'subject' => 'Welcome',
			'content' => '<p>Hello</p>',
			'status'  => 'ready',
		], $args ) );

		return $email;
	}

	protected function contact( string $locale = '' ): Contact {
		$contact = get_contactdata( self::factory()->contacts->create( [ 'optin_status' => Preferences::CONFIRMED ] ) );
		$contact->delete_meta( 'locale' );
		if ( $locale ) {
			$contact->set_locale( $locale );
		}

		return $contact;
	}

	/**
	 * A ready variation in a language
	 */
	protected function variation( Email $email, string $locale, array $args = [] ): Email {

		$variation = Email_Variations::create( $email, array_merge( [
			'audience' => [ 'locales' => [ $locale ] ],
			'copy'     => true,
		], $args ) );

		$this->assertInstanceOf( Email::class, $variation );

		$variation->update( [ 'subject' => 'Welcome ' . $locale, 'status' => $args['status'] ?? 'ready' ] );

		return $variation;
	}

	/* the model */

	public function test_the_table_supports_variations() {
		$this->assertTrue( Email_Variations::supported() );
	}

	public function test_nothing_uses_the_column_before_the_update_adds_it() {

		$email = $this->email();
		$this->variation( $email, 'fr_FR' );

		$option = get_db( 'emails' )->table_name . '_db_version';
		$before = get_option( $option );
		update_option( $option, '2.1' );
		Email_Variations::flush();

		try {
			$this->assertFalse( Email_Variations::supported() );
			$this->assertArrayNotHasKey( 'parent_id', get_db( 'emails' )->get_columns() );
			$this->assertSame( [], Email_Variations::get_variation_ids( $email->get_id() ) );
			$this->assertFalse( Email_Variations::resolve( $email, $this->contact( 'fr_FR' ) ) );
			$this->assertWPError( Email_Variations::create( $email, [ 'audience' => [ 'locales' => [ 'de_DE' ] ] ] ) );

			// saving and listing emails never names the column
			$queries = [];
			$log     = function ( $query ) use ( &$queries ) {
				$queries[] = $query;

				return $query;
			};
			add_filter( 'query', $log );
			$new = $this->email();
			get_db( 'emails' )->query( [] );
			remove_filter( 'query', $log );

			$this->assertTrue( $new->exists() );
			$this->assertEmpty( array_filter( $queries, fn( $query ) => str_contains( $query, 'parent_id' ) ) );
		} finally {
			update_option( $option, $before );
			Email_Variations::flush();
		}
	}

	public function test_create_copies_the_email() {

		$email     = $this->email( [ 'content' => '<p id="b-intro">Hello</p>' ] );
		$variation = Email_Variations::create( $email, [ 'audience' => [ 'locales' => [ 'fr-FR' ] ], 'copy' => true ] );

		$this->assertSame( $email->get_id(), $variation->get_parent_id() );
		$this->assertTrue( $variation->is_variation() );
		$this->assertSame( '<p id="b-intro">Hello</p>', $variation->get_content() );
		$this->assertTrue( $variation->is_draft() );
		$this->assertSame( 'fr_FR', $variation->get_meta( Email_Variations::LOCALE_KEY ) );
		$this->assertSame( [ 'locales' => [ 'fr_FR' ], 'include_filters' => [], 'exclude_filters' => [] ], Email_Variations::get_audience( $variation ) );
		$this->assertSame( [ $variation->get_id() ], wp_list_pluck( $email->get_variations(), 'ID' ) );
	}

	public function test_create_refuses_what_cant_have_variations() {

		$email     = $this->email();
		$variation = $this->variation( $email, 'fr_FR' );

		$this->assertWPError( Email_Variations::create( $variation, [ 'audience' => [ 'locales' => [ 'de_DE' ] ] ] ) );
		$this->assertWPError( Email_Variations::create( $this->email( [ 'is_template' => 1 ] ), [ 'audience' => [ 'locales' => [ 'de_DE' ] ] ] ) );
		$this->assertWPError( Email_Variations::create( $email, [ 'audience' => [] ] ) );
	}

	public function test_variations_are_left_out_of_lists() {

		$email     = $this->email();
		$variation = $this->variation( $email, 'fr_FR' );

		$listed = wp_list_pluck( get_db( 'emails' )->query( [] ), 'ID' );
		$this->assertContains( (string) $email->get_id(), array_map( 'strval', $listed ) );
		$this->assertNotContains( (string) $variation->get_id(), array_map( 'strval', $listed ) );
		$this->assertSame( 1, get_db( 'emails' )->count( [] ) );

		// asked for by ID, or by parent
		$this->assertCount( 1, get_db( 'emails' )->query( [ 'include' => [ $variation->get_id() ] ] ) );
		$this->assertCount( 1, get_db( 'emails' )->query( [ 'parent_id' => $email->get_id() ] ) );
	}

	public function test_a_variation_is_sent_by_the_rules_of_its_email() {

		$email     = $this->email( [ 'message_type' => 'transactional' ] );
		$variation = $this->variation( $email, 'fr_FR' );
		$variation->update( [ 'message_type' => 'marketing' ] );

		$this->assertTrue( ( new Email( $variation->get_id() ) )->is_transactional() );
	}

	/* choosing a variation */

	public function test_resolve_by_language() {

		$email = $this->email();
		$fr    = $this->variation( $email, 'fr_FR' );
		$de    = $this->variation( $email, 'de_DE' );

		$this->assertSame( $fr->get_id(), Email_Variations::resolve( $email, $this->contact( 'fr_FR' ) )->get_id() );
		$this->assertSame( $de->get_id(), Email_Variations::resolve( $email, $this->contact( 'de_DE' ) )->get_id() );
		$this->assertFalse( Email_Variations::resolve( $email, $this->contact( 'es_ES' ) ) );
		$this->assertFalse( Email_Variations::resolve( $email, $this->contact() ) );
	}

	public function test_drafts_are_not_sent() {

		$email = $this->email();
		$this->variation( $email, 'fr_FR', [ 'status' => 'draft' ] );

		$this->assertFalse( Email_Variations::resolve( $email, $this->contact( 'fr_FR' ) ) );
	}

	public function test_the_lowest_priority_goes_first() {

		$email  = $this->email();
		$first  = $this->variation( $email, 'fr_FR', [ 'priority' => 5 ] );
		$second = $this->variation( $email, 'fr_FR', [ 'priority' => 1 ] );

		$this->assertSame( $second->get_id(), Email_Variations::resolve( $email, $this->contact( 'fr_FR' ) )->get_id() );

		Email_Variations::update( $second, [ 'priority' => 10 ] );

		$this->assertSame( $first->get_id(), Email_Variations::resolve( $email, $this->contact( 'fr_FR' ) )->get_id() );
	}

	public function test_resolve_by_filters_and_language() {

		$tag   = get_db( 'tags' )->add( [ 'tag_name' => 'vip', 'tag_slug' => 'vip' ] );
		$email = $this->email();
		$vip   = Email_Variations::create( $email, [
			'audience' => [
				'locales'         => [ 'fr_FR' ],
				'include_filters' => [ [ [ 'type' => 'tags', 'tags' => [ $tag ], 'compare' => 'includes', 'compare2' => 'any' ] ] ],
			],
			'status'   => 'ready',
		] );

		$french_vip = $this->contact( 'fr_FR' );
		$french_vip->apply_tag( $tag );
		$german_vip = $this->contact( 'de_DE' );
		$german_vip->apply_tag( $tag );

		$this->assertSame( $vip->get_id(), Email_Variations::resolve( $email, $french_vip )->get_id() );
		$this->assertFalse( Email_Variations::resolve( $email, $this->contact( 'fr_FR' ) ) );
		$this->assertFalse( Email_Variations::resolve( $email, $german_vip ) );
	}

	/* sending */

	public function test_send_sends_the_contacts_variation() {

		$email = $this->email();
		$fr    = $this->variation( $email, 'fr_FR' );

		$this->assertTrue( $email->send( $this->contact( 'fr_FR' ) ) );
		$this->assertSame( $fr->get_id(), $email->get_sent_email()->get_id() );

		$this->assertTrue( $email->send( $this->contact( 'en_US' ) ) );
		$this->assertSame( $email->get_id(), $email->get_sent_email()->get_id() );

		$this->assertSame( [ 'Welcome fr_FR', 'Welcome' ], $this->sent );
		$this->assertFalse( is_sending() );
	}

	public function test_tests_and_drafts_send_the_email_itself() {

		$email = $this->email();
		$this->variation( $email, 'fr_FR' );
		$contact = $this->contact( 'fr_FR' );

		$email->enable_test_mode();
		$this->assertTrue( $email->send( $contact ) );
		$this->assertSame( [ 'Welcome' ], $this->sent );

		$draft = $this->email( [ 'status' => 'draft' ] );
		$this->variation( $draft, 'fr_FR' );
		$this->assertWPError( $draft->send( $contact ) );
	}

	public function test_send_counts_the_event_for_the_variation() {

		$email   = $this->email();
		$fr      = $this->variation( $email, 'fr_FR' );
		$contact = $this->contact( 'fr_FR' );

		$event_id = get_db( 'event_queue' )->add( [
			'time'       => time(),
			'contact_id' => $contact->get_id(),
			'email_id'   => $email->get_id(),
			'event_type' => Event::EMAIL_NOTIFICATION,
			'step_id'    => $email->get_id(),
			'status'     => Event::IN_PROGRESS,
		] );

		$event = new Event_Queue_Item( $event_id );

		$this->assertTrue( $email->send( $contact, $event ) );
		$this->assertSame( $fr->get_id(), ( new Event_Queue_Item( $event_id ) )->get_email_id() );
	}

	/* everything about an email includes its variations */

	public function test_family_ids() {

		$email = $this->email();
		$fr    = $this->variation( $email, 'fr_FR' );
		$other = $this->email();

		$this->assertSame( [ $email->get_id(), $fr->get_id() ], Email_Variations::get_family_ids( $email->get_id() ) );
		$this->assertSame( [ $fr->get_id() ], Email_Variations::get_family_ids( $fr->get_id() ) );
		$this->assertSame( [ $email->get_id(), $other->get_id(), $fr->get_id() ], Email_Variations::get_family_ids( [ $email->get_id(), $other->get_id() ] ) );
		$this->assertSame( [], Email_Variations::get_family_ids( 0 ) );
	}

	public function test_opening_a_variation_counts_as_opening_the_email() {

		$email    = $this->email();
		$fr       = $this->variation( $email, 'fr_FR' );
		$opened   = $this->contact( 'fr_FR' );
		$unopened = $this->contact( 'fr_FR' );

		get_db( 'activity' )->add( [
			'timestamp'     => time(),
			'contact_id'    => $opened->get_id(),
			'funnel_id'     => 5,
			'step_id'       => 6,
			'email_id'      => $fr->get_id(),
			'activity_type' => Activity::EMAIL_OPENED,
		] );

		$query = new Contact_Query();
		$ids   = wp_parse_id_list( wp_list_pluck( $query->query( [
			'include_filters' => [ [ [ 'type' => 'email_opened', 'email_id' => $email->get_id() ] ] ],
		] ), 'ID' ) );

		$this->assertSame( [ $opened->get_id() ], $ids );
	}

	public function test_report_rows_are_merged_into_the_email() {

		$email = $this->email();
		$fr    = $this->variation( $email, 'fr_FR' );
		$other = $this->email();

		$rows = Email_Variations::merge_into_parents( [
			(object) [ 'email_id' => $email->get_id(), 'step_id' => 1, 'sent' => 3 ],
			(object) [ 'email_id' => $fr->get_id(), 'step_id' => 1, 'sent' => 2 ],
			(object) [ 'email_id' => $fr->get_id(), 'step_id' => 2, 'sent' => 1 ],
			(object) [ 'email_id' => $other->get_id(), 'step_id' => 1, 'sent' => 4 ],
		], [ 'sent' ] );

		$this->assertSame( [
			[ $email->get_id(), 1, 5 ],
			[ $email->get_id(), 2, 1 ],
			[ $other->get_id(), 1, 4 ],
		], array_map( fn( $row ) => [ absint( $row->email_id ), absint( $row->step_id ), absint( $row->sent ) ], $rows ) );
	}

	/* the email's life */

	public function test_deleting_an_email_deletes_its_variations() {

		$email = $this->email();
		$fr    = $this->variation( $email, 'fr_FR' );

		$email->delete();

		$this->assertFalse( ( new Email( $fr->get_id() ) )->exists() );
	}

	public function test_copying_an_email_copies_its_variations() {

		$email = $this->email();
		$this->variation( $email, 'fr_FR' );

		$copy = $email->duplicate();

		$variations = $copy->get_variations();
		$this->assertCount( 1, $variations );
		$this->assertSame( 'Welcome fr_FR', $variations[0]->get_subject_line() );
		$this->assertSame( [ 'fr_FR' ], Email_Variations::get_audience( $variations[0] )['locales'] );

		// the original keeps its own
		$this->assertCount( 1, ( new Email( $email->get_id() ) )->get_variations() );
	}

	public function test_variations_are_exported_and_imported_with_the_email() {

		$email = $this->email();
		$this->variation( $email, 'fr_FR' );

		$export = json_decode( wp_json_encode( $email->export() ), true );
		$this->assertCount( 1, $export['variations'] );

		$step = new \Groundhogg\Step();
		$step->create( [ 'funnel_id' => 1, 'step_type' => Send_Email::TYPE, 'step_group' => 'action' ] );

		( new Send_Email() )->import( [ 'email' => $export ], $step );

		$imported = new Email( absint( $step->get_meta( 'email_id' ) ) );
		$this->assertNotSame( $email->get_id(), $imported->get_id() );

		$variations = $imported->get_variations();
		$this->assertCount( 1, $variations );
		$this->assertSame( 'Welcome fr_FR', $variations[0]->get_subject_line() );
		$this->assertTrue( $variations[0]->is_ready() );
	}

	/* abilities */

	protected function ability( string $name, array $input ) {
		$out = wp_get_ability( $name )->execute( $input );

		return is_wp_error( $out ) ? $out : json_decode( wp_json_encode( $out ), true );
	}

	public function test_the_abilities() {

		if ( ! function_exists( 'wp_get_ability' ) || ! wp_get_ability( 'groundhogg/create-email-variation' ) ) {
			$this->markTestSkipped( 'The email variation abilities are not registered.' );
		}

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$email   = $this->email();
		$contact = $this->contact( 'fr_FR' );

		$created = $this->ability( 'groundhogg/create-email-variation', [
			'email_id' => $email->get_id(),
			'audience' => [ 'locales' => [ 'fr_FR' ] ],
		] );

		$this->assertNotWPError( $created );
		$variation_id = $created['variation']['id'];
		$this->assertSame( $email->get_id(), $created['variation']['variation_of'] );
		$this->assertSame( [ 'fr_FR' ], $created['variation']['variation']['audience']['locales'] );
		$this->assertSame( 'draft', $created['variation']['status'] );

		// it's a draft, so the contact gets the email
		$resolved = $this->ability( 'groundhogg/resolve-email-variation', [ 'email_id' => $email->get_id(), 'contact_id' => $contact->get_id() ] );
		$this->assertSame( $email->get_id(), $resolved['email_id'] );
		$this->assertSame( 'not_ready', $resolved['variations'][0]['reason'] );

		( new Email( $variation_id ) )->update( [ 'status' => 'ready' ] );
		Email_Variations::flush();

		$resolved = $this->ability( 'groundhogg/resolve-email-variation', [ 'email_id' => $email->get_id(), 'contact_id' => $contact->get_id() ] );
		$this->assertSame( $variation_id, $resolved['email_id'] );
		$this->assertTrue( $resolved['is_variation'] );
		$this->assertSame( 'chosen', $resolved['variations'][0]['reason'] );

		$read = $this->ability( 'groundhogg/get-email-template', [ 'email_id' => $email->get_id() ] );
		$this->assertSame( [ $variation_id ], array_column( $read['email']['variations'] ?? $read['variations'] ?? [], 'id' ) );

		// a segment audience
		$updated = $this->ability( 'groundhogg/update-email-variation', [ 'variation_id' => $variation_id, 'audience' => [ 'locales' => [ 'de_DE' ] ] ] );
		$this->assertNotWPError( $updated );

		$resolved = $this->ability( 'groundhogg/resolve-email-variation', [ 'email_id' => $email->get_id(), 'contact_id' => $contact->get_id() ] );
		$this->assertSame( 'language', $resolved['variations'][0]['reason'] );

		$this->assertWPError( $this->ability( 'groundhogg/update-email-variation', [ 'variation_id' => $email->get_id(), 'priority' => 1 ] ) );
		$this->assertWPError( $this->ability( 'groundhogg/create-email-variation', [ 'email_id' => $email->get_id(), 'audience' => [] ] ) );

		$deleted = $this->ability( 'groundhogg/delete-email-variation', [ 'variation_id' => $variation_id ] );
		$this->assertTrue( $deleted['deleted'] );
		$this->assertFalse( ( new Email( $variation_id ) )->exists() );
		$this->assertTrue( ( new Email( $email->get_id() ) )->exists() );
	}
}
