<?php

use Groundhogg\Classes\Activity;
use Groundhogg\Contact;
use Groundhogg\Markdown\Contact_Markdown;
use Groundhogg\Markdown\Markdown;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;

/**
 * A record as markdown for an AI model: the sections, what's left out without personal details, and what each
 * contact section says
 */
class Markdown_Tests extends GH_UnitTestCase {

	public function set_up() {
		parent::set_up();
		Markdown::reset();
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	public function tear_down() {
		remove_all_actions( 'groundhogg/markdown/contact/sections' );
		Markdown::reset();
		parent::tear_down();
	}

	protected function contact( array $args = [] ): Contact {
		return get_contactdata( self::factory()->contacts->create( array_merge( [
			'email'      => 'md-' . uniqid() . '@example.com',
			'first_name' => 'Jordan',
			'last_name'  => 'Rivera',
		], $args ) ) );
	}

	public function test_a_contact_has_sections_to_pick_from() {

		$options = Markdown::section_options( 'contact' );
		$keys    = wp_list_pluck( $options, 'key' );

		foreach ( [ 'details', 'personal', 'tags', 'fields', 'notes', 'tasks', 'activity', 'emails', 'flows', 'submissions', 'page_visits' ] as $key ) {
			$this->assertContains( $key, $keys );
		}

		// for a screen to list: a label and a description, never the callback
		$this->assertArrayNotHasKey( 'callback', $options[0] );
		$this->assertNotEmpty( $options[0]['label'] );
		$this->assertNotEmpty( $options[0]['description'] );

		$personal = wp_list_pluck( $options, 'personal', 'key' );
		$this->assertTrue( $personal['personal'] );
		$this->assertFalse( $personal['notes'] );
	}

	public function test_an_add_on_can_add_a_section() {

		add_action( 'groundhogg/markdown/contact/sections', function () {
			Markdown::register( 'contact', 'orders', [
				'label'    => 'Orders',
				'callback' => function ( Contact $contact, array $args ) {
					return Markdown::items( [ '2026-11-20: Pro plan' ] );
				},
			] );
		} );

		$this->assertArrayHasKey( 'orders', Markdown::sections( 'contact' ) );
		$this->assertSame( "## Orders\n\n- 2026-11-20: Pro plan", Contact_Markdown::render( $this->contact(), [ 'orders' ] ) );
	}

	public function test_sections_come_in_the_order_asked_for_and_empty_ones_say_so() {

		$contact = $this->contact();
		$contact->add_tag( 'Markdown test tag' );

		$markdown = Contact_Markdown::render( $contact, [ 'tags', 'notes', 'details' ] );

		$this->assertStringStartsWith( "## Tags\n\nMarkdown test tag", $markdown );
		$this->assertStringContainsString( "## Notes\n\n" . Markdown::none(), $markdown, 'empty, not missing' );
		$this->assertGreaterThan( strpos( $markdown, '## Notes' ), strpos( $markdown, '## Details' ) );
	}

	public function test_a_section_someone_cant_see_says_so_rather_than_looking_empty() {

		add_action( 'groundhogg/markdown/contact/sections', function () {

			$items = function () {
				return Markdown::items( [ 'Pro plan' ] );
			};

			Markdown::register( 'contact', 'secret', [ 'label' => 'Orders', 'callback' => $items, 'can' => '__return_false' ] );
			Markdown::register( 'contact', 'some', [
				'label'    => 'Deals',
				'callback' => $items,
				'can'      => function () {
					return 'Only your own deals are shown.';
				},
			] );
			Markdown::register( 'contact', 'empty', [ 'label' => 'Visits', 'callback' => '__return_empty_string', 'can' => '__return_true' ] );
		} );

		$markdown = Contact_Markdown::render( $this->contact(), [ 'secret', 'some', 'empty' ] );

		$this->assertStringContainsString( "## Orders\n\n" . Markdown::hidden(), $markdown );
		$this->assertStringNotContainsString( 'Pro plan', explode( '## Deals', $markdown )[0], 'nothing of a hidden section' );
		$this->assertStringContainsString( "## Deals\n\nOnly your own deals are shown.\n\n- Pro plan", $markdown );
		$this->assertStringContainsString( "## Visits\n\n" . Markdown::none(), $markdown );

		// for a picker, without the callbacks
		$this->assertArrayNotHasKey( 'can', Markdown::section_options( 'contact' )[0] );
	}

	public function test_personal_details_are_left_out_unless_asked_for_and_allowed() {

		$contact = $this->contact();
		$email   = $contact->get_email();
		$contact->add_note( "Spoke with Jordan Rivera, write to {$email} next week." );

		$without = Contact_Markdown::render( $contact );

		$this->assertStringNotContainsString( '## Personal details', $without );
		$this->assertStringNotContainsString( $email, $without );
		$this->assertStringNotContainsString( 'Jordan', $without, 'taken out of notes too' );
		$this->assertStringContainsString( 'next week', $without );

		$with = Contact_Markdown::render( $contact, [], [ 'personal' => true ] );

		$this->assertStringContainsString( '## Personal details', $with );
		$this->assertStringContainsString( 'Email address: ' . $email, $with );
		$this->assertStringContainsString( 'Spoke with Jordan Rivera', $with );

		// someone who can't see personal details doesn't get them by asking
		$user = self::factory()->user->create( [ 'role' => 'administrator' ] );
		get_userdata( $user )->remove_cap( 'view_contact_pii' );
		add_filter( 'groundhogg/pii/is_restricted', '__return_true' );
		wp_set_current_user( $user );

		$restricted = Contact_Markdown::render( $contact, [], [ 'personal' => true ] );

		remove_filter( 'groundhogg/pii/is_restricted', '__return_true' );

		$this->assertStringNotContainsString( $email, $restricted );
		$this->assertStringContainsString( "## Personal details\n\n" . Markdown::hidden(), $restricted, 'hidden, not missing' );
	}

	public function test_activity_says_what_they_did() {

		$contact = $this->contact();

		$email = new \Groundhogg\Email( [
			'title'   => 'Spring sale email',
			'subject' => 'Everything is 20% off',
			'status'  => 'ready',
		] );

		foreach ( [
			[ Activity::EMAIL_OPENED, '' ],
			[ Activity::EMAIL_CLICKED, 'https://example.com/pricing' ],
			[ 'woocommerce_purchase', '' ],
		] as $i => $activity ) {
			get_db( 'activity' )->add( [
				'contact_id'    => $contact->get_id(),
				'email_id'      => $email->get_id(),
				'activity_type' => $activity[0],
				'referer'       => $activity[1],
				'value'         => $activity[0] === 'woocommerce_purchase' ? 99 : 0,
				'timestamp'     => time() - 100 + $i,
			] );
		}

		$markdown = Contact_Markdown::render( $contact, [ 'activity' ] );

		$this->assertStringContainsString( 'Opened the email "Everything is 20% off"', $markdown );
		$this->assertStringContainsString( 'Clicked https://example.com/pricing in the email "Everything is 20% off"', $markdown );
		$this->assertStringContainsString( 'Woocommerce purchase (value 99.00)', $markdown );

		// newest first, and no more than the limit
		$this->assertStringStartsWith( "## Activity\n\n- ", $markdown );
		$this->assertLessThan( strpos( $markdown, 'Opened' ), strpos( $markdown, 'Woocommerce' ) );
		$this->assertSame( 1, substr_count( Contact_Markdown::render( $contact, [ 'activity' ], [ 'limit' => 1 ] ), "\n- " ) );
	}

	/* ---- the get-contact-history ability ---------------------------------------------------------------------- */

	protected function history( array $input, bool $pii = true ) {

		if ( ! function_exists( 'wp_get_ability' ) || ! wp_get_ability( 'groundhogg/get-contact-history' ) ) {
			$this->markTestSkipped( 'The groundhogg/get-contact-history ability is not registered.' );
		}

		$user = self::factory()->user->create( [ 'role' => 'administrator' ] );
		get_userdata( $user )->add_cap( 'view_contact_pii', $pii );
		wp_set_current_user( $user );

		$out = wp_get_ability( 'groundhogg/get-contact-history' )->execute( $input );

		return is_wp_error( $out ) ? $out : json_decode( wp_json_encode( $out ), true );
	}

	public function test_an_assistant_gets_a_contact_in_one_call() {

		$contact = $this->contact();
		$contact->add_tag( 'History test tag' );
		$contact->add_note( 'Asked about the annual plan.' );

		$out = $this->history( [ 'id' => $contact->get_id() ] );

		$this->assertNotWPError( $out );
		$this->assertTrue( $out['personal'] );
		$this->assertStringContainsString( $contact->get_email(), $out['markdown'] );
		$this->assertStringContainsString( 'History test tag', $out['markdown'] );
		$this->assertStringContainsString( 'Asked about the annual plan.', $out['markdown'] );

		// just what's asked for
		$out = $this->history( [ 'id' => $contact->get_id(), 'sections' => [ 'notes' ], 'personal' => false ] );

		$this->assertSame( "## Notes\n\n", substr( $out['markdown'], 0, 10 ) );
		$this->assertStringNotContainsString( '## Tags', $out['markdown'] );
		$this->assertFalse( $out['personal'] );
	}

	public function test_an_assistant_without_personal_details_doesnt_get_them_by_asking() {

		$contact = $this->contact();
		$contact->add_note( 'Jordan prefers phone calls.' );

		$out = $this->history( [ 'id' => $contact->get_id(), 'personal' => true ], false );

		$this->assertFalse( $out['personal'] );
		$this->assertStringNotContainsString( $contact->get_email(), $out['markdown'] );
		$this->assertStringNotContainsString( 'Jordan', $out['markdown'] );
		$this->assertStringContainsString( 'prefers phone calls', $out['markdown'] );
	}

	public function test_an_assistant_only_gets_contacts_its_person_can_see() {

		$this->assertSame( 'groundhogg_contact_not_found', $this->history( [ 'id' => 999999999 ] )->get_error_code() );

		$contact = $this->contact();

		$deny = function ( $caps, $cap ) {
			return $cap === 'view_contact' ? [ 'do_not_allow' ] : $caps;
		};
		add_filter( 'map_meta_cap', $deny, 10, 2 );
		$out = $this->history( [ 'id' => $contact->get_id() ] );
		remove_filter( 'map_meta_cap', $deny, 10 );

		$this->assertSame( 'groundhogg_cannot_view_contact', $out->get_error_code() );
	}

	public function test_an_assistant_saves_its_summary_for_the_next_one() {

		if ( ! function_exists( 'wp_get_ability' ) || ! wp_get_ability( 'groundhogg/save-contact-summary' ) ) {
			$this->markTestSkipped( 'The groundhogg/save-contact-summary ability is not registered.' );
		}

		$contact = $this->contact();

		$this->assertNull( $this->history( [ 'id' => $contact->get_id() ] )['summary'] );

		$saved = wp_get_ability( 'groundhogg/save-contact-summary' )->execute( [
			'id'          => $contact->get_id(),
			'temperature' => 'hot',
			'reason'      => 'Booked a demo yesterday.',
			'summary'     => 'A new lead comparing plans.',
			'interests'   => [ 'Pricing', 'Flows' ],
			'next_step'   => 'Follow up after the demo.',
		] );

		$this->assertNotWPError( $saved );
		$this->assertSame( 'assistant', $saved['summary']['source'] );

		// the next assistant gets it with the history, and the record shows it
		$summary = $this->history( [ 'id' => $contact->get_id(), 'sections' => [ 'tags' ] ] )['summary'];

		$this->assertSame( 'hot', $summary['temperature'] );
		$this->assertSame( [ 'Pricing', 'Flows' ], $summary['interests'] );
		$this->assertSame( 'assistant', $summary['source'] );
		$this->assertNotEmpty( $summary['age'] );

		$this->assertSame( 'Follow up after the demo.', Groundhogg\AI\Jobs\Contact_Summary::get_saved( get_contactdata( $contact->get_id() ) )['next_step'] );
	}

	public function test_an_assistant_needs_to_be_able_to_edit_the_contact_to_save_a_summary() {

		if ( ! function_exists( 'wp_get_ability' ) || ! wp_get_ability( 'groundhogg/save-contact-summary' ) ) {
			$this->markTestSkipped( 'The groundhogg/save-contact-summary ability is not registered.' );
		}

		$contact = $this->contact();

		$deny = function ( $caps, $cap ) {
			return $cap === 'edit_contact' ? [ 'do_not_allow' ] : $caps;
		};
		add_filter( 'map_meta_cap', $deny, 10, 2 );

		$out = wp_get_ability( 'groundhogg/save-contact-summary' )->execute( [
			'id'          => $contact->get_id(),
			'temperature' => 'cold',
			'reason'      => 'Nothing yet.',
			'summary'     => 'Just added.',
		] );

		remove_filter( 'map_meta_cap', $deny, 10 );

		$this->assertWPError( $out );
		$this->assertNull( Groundhogg\AI\Jobs\Contact_Summary::get_saved( get_contactdata( $contact->get_id() ) ) );
	}

	public function test_add_on_sections_are_only_there_with_the_add_on() {

		$sections = Markdown::sections( 'contact' );

		$this->assertSame( (bool) get_db( 'deals' ), isset( $sections['deals'] ) );
		$this->assertSame( (bool) get_db( 'companies' ), isset( $sections['companies'] ) );
		$this->assertSame( function_exists( 'wc_get_orders' ), isset( $sections['woocommerce'] ) );
	}

	public function test_add_on_sections_need_the_add_ons_own_permission() {

		$contact = $this->contact();
		$args    = [ 'personal' => false, 'limit' => 5 ];

		$deny = function ( $caps, $cap ) {
			return in_array( $cap, [ 'view_companies', 'view_deals', 'view_appointments', 'manage_woocommerce', 'edit_shop_payments' ], true ) ? [ 'do_not_allow' ] : $caps;
		};
		add_filter( 'map_meta_cap', $deny, 10, 2 );

		// each says nothing before it reads anything, so this works without the add-ons
		$this->assertSame( '', Groundhogg\Markdown\Add_On_Markdown::companies( $contact, $args ) );
		$this->assertSame( '', Groundhogg\Markdown\Add_On_Markdown::deals( $contact, $args ) );
		$this->assertSame( '', Groundhogg\Markdown\Add_On_Markdown::appointments( $contact, $args ) );
		$this->assertSame( '', Groundhogg\Markdown\Add_On_Markdown::woocommerce( $contact, $args ) );
		$this->assertSame( '', Groundhogg\Markdown\Add_On_Markdown::edd( $contact, $args ) );

		// so they're marked hidden
		$this->assertFalse( Groundhogg\Markdown\Add_On_Markdown::can_see_deals() );
		$this->assertFalse( Groundhogg\Markdown\Add_On_Markdown::can_see_appointments() );

		remove_filter( 'map_meta_cap', $deny, 10 );

		// someone who sees only their own appointments is told there may be others
		$own = function ( $caps, $cap ) {
			if ( $cap === 'view_appointments' ) {
				return [ 'exist' ]; // the add-on isn't here to give it to administrators
			}

			return $cap === 'view_others_appointments' ? [ 'do_not_allow' ] : $caps;
		};
		add_filter( 'map_meta_cap', $own, 10, 2 );
		$this->assertIsString( Groundhogg\Markdown\Add_On_Markdown::can_see_appointments() );
		remove_filter( 'map_meta_cap', $own, 10 );
	}

	public function test_text_is_made_one_tidy_line() {
		$this->assertSame( 'Hello there. New paragraph', Markdown::text( '<p>Hello there.</p><p>New   paragraph</p>' ) );
		$this->assertSame( 'Hello & there', Markdown::text( 'Hello &amp; there' ) );
		$this->assertSame( 'abcd…', Markdown::text( 'abcdefgh', 5 ) );
		$this->assertSame( "- Plan: Pro\n- Seats: 3, 4", Markdown::pairs( [ 'Plan' => 'Pro', 'Empty' => '', 'Seats' => [ 3, 4 ] ] ) );
	}
}
