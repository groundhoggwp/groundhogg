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

	/* ---- narrowing a section ---------------------------------------------------------------------------------- */

	public function test_a_section_gets_only_the_options_it_offers_cleaned_up() {

		$got = null;

		add_action( 'groundhogg/markdown/contact/sections', function () use ( &$got ) {
			Markdown::register( 'contact', 'probe', [
				'label'    => 'Probe',
				'callback' => function ( $contact, $args ) use ( &$got ) {
					$got = $args;

					return '';
				},
				'options'  => Markdown::list_options() + [
						'funnel_ids' => [ 'type' => 'ids', 'label' => 'Flows' ],
						'kinds'      => [ 'type' => 'strings', 'label' => 'Kinds', 'choices' => [ 'a' => 'Kind A', 'b' => 'Kind B' ] ],
					],
			] );
		} );

		$markdown = Contact_Markdown::render( $this->contact(), [
			'probe' => [
				'funnel_ids' => '3, 4,x',
				'kinds'      => [ 'a', 'evil' ],
				'since'      => '2026-11-01',
				'limit'      => 5000,
				'unknown'    => 'dropped',
			],
		], [ 'until' => '2026-11-20' ] );

		$this->assertSame( [ 3, 4 ], $got['funnel_ids'] );
		$this->assertSame( [ 'a' ], $got['kinds'], 'only the choices offered' );
		$this->assertSame( 200, $got['limit'] );
		$this->assertSame( Markdown::time( '2026-11-01' ), $got['since'] );
		$this->assertSame( Markdown::time( '2026-11-20', true ), $got['until'], 'the time range of every list, until the end of that day' );
		$this->assertArrayNotHasKey( 'unknown', $got );

		// an empty list that was narrowed says so, so it isn't read as never
		$this->assertStringContainsString( 'Only what was asked for, so there may be more: Since: 2026-11-01; Until: 2026-11-20; Flows: 3, 4; Kinds: Kind A.', $markdown );
		$this->assertStringContainsString( Markdown::none(), $markdown );

		// a section without a time range doesn't get one
		$this->assertStringNotContainsString( 'Only what was asked for', Contact_Markdown::render( $this->contact(), [ 'tags' ], [ 'since' => '30 days ago' ] ) );
	}

	public function test_activity_can_be_narrowed_to_flows_broadcasts_kinds_and_time() {

		$contact = $this->contact();
		$email   = new \Groundhogg\Email( [ 'title' => 'Narrowing', 'subject' => 'Narrowing test', 'status' => 'ready' ] );

		$add = function ( $type, $funnel, $step, $ago ) use ( $contact, $email ) {
			get_db( 'activity' )->add( [
				'contact_id'    => $contact->get_id(),
				'email_id'      => $email->get_id(),
				'funnel_id'     => $funnel,
				'step_id'       => $step,
				'activity_type' => $type,
				'referer'       => $type === Activity::EMAIL_CLICKED ? "https://example.com/{$funnel}-{$step}" : '',
				'timestamp'     => time() - $ago * DAY_IN_SECONDS,
			] );
		};

		$add( Activity::EMAIL_CLICKED, 7, 70, 1 );     // flow 7
		$add( Activity::EMAIL_CLICKED, 8, 80, 2 );     // flow 8
		$add( Activity::EMAIL_CLICKED, 1, 55, 3 );     // broadcast 55
		$add( Activity::EMAIL_OPENED, 7, 70, 4 );      // flow 7, an open
		$add( Activity::EMAIL_CLICKED, 7, 70, 60 );    // flow 7, long ago

		$activity = function ( array $options ) use ( $contact ) {
			return Contact_Markdown::render( $contact, [ 'activity' => $options ] );
		};

		$flow7 = $activity( [ 'funnel_ids' => [ 7 ] ] );
		$this->assertSame( 3, substr_count( $flow7, "\n- " ) );
		$this->assertStringNotContainsString( '8-80', $flow7 );

		$broadcast = $activity( [ 'broadcast_ids' => [ 55 ] ] );
		$this->assertStringContainsString( '1-55', $broadcast );
		$this->assertSame( 1, substr_count( $broadcast, "\n- " ) );

		// a flow or a broadcast
		$this->assertSame( 4, substr_count( $activity( [ 'funnel_ids' => [ 7 ], 'broadcast_ids' => [ 55 ] ] ), "\n- " ) );

		$recent_clicks = $activity( [ 'types' => [ Activity::EMAIL_CLICKED ], 'since' => '10 days ago' ] );
		$this->assertSame( 3, substr_count( $recent_clicks, "\n- " ) );
		$this->assertStringNotContainsString( 'Opened', $recent_clicks );
	}

	public function test_the_scope_line_names_what_it_was_narrowed_to() {

		$funnel = new \Groundhogg\Funnel( [ 'title' => 'Long Term Nurture test', 'status' => 'inactive' ] );

		$markdown = Contact_Markdown::render( $this->contact(), [ 'flows' => [ 'funnel_ids' => [ $funnel->get_id(), 999999 ] ] ] );

		$this->assertStringContainsString( sprintf( 'Flows: "Long Term Nurture test" (%d), 999999.', $funnel->get_id() ), $markdown, 'a deleted one by its ID' );

		// a picker gets the options without how they're named, so they can be sent to the browser
		$options = Markdown::section_options( 'contact' );
		$this->assertNotFalse( wp_json_encode( $options ) );
		$this->assertArrayNotHasKey( 'name', wp_list_pluck( $options, 'options', 'key' )['flows']['funnel_ids'] );
	}

	public function test_their_latest_message_can_be_sent_whole() {

		$contact = $this->contact();
		$long    = str_repeat( 'I would like to cancel my plan because it costs too much. ', 30 );

		foreach ( [ [ 'inbound', 'Older question', 'Do you have a discount?' ], [ 'outbound', 'Our reply', 'Here is 10% off.' ], [ 'inbound', 'Cancel', $long ] ] as $i => $message ) {
			get_db( 'messages' )->add( [
				'object_id'    => $contact->get_id(),
				'object_type'  => 'contact',
				'direction'    => $message[0],
				'subject'      => $message[1],
				'content'      => $message[2],
				'date_created' => gmdate( 'Y-m-d H:i:s', time() - ( 10 - $i ) * HOUR_IN_SECONDS ),
			] );
		}

		$latest = Contact_Markdown::render( $contact, [ 'messages' => [ 'direction' => [ 'inbound' ], 'limit' => 1 ] ] );

		$this->assertStringContainsString( '"Cancel"', $latest );
		$this->assertStringNotContainsString( 'discount', $latest );
		$this->assertGreaterThan( 1500, strlen( $latest ), 'whole, not cut at 400 characters' );

		// many are cut short
		$this->assertLessThan( 1200, strlen( Contact_Markdown::render( $contact, [ 'messages' ] ) ) );
	}

	/* ---- what the review found -------------------------------------------------------------------------------- */

	public function test_someone_who_may_not_see_personal_details_doesnt_get_their_messages() {

		$contact = $this->contact();

		get_db( 'messages' )->add( [
			'object_id'    => $contact->get_id(),
			'object_type'  => 'contact',
			'direction'    => 'inbound',
			'subject'      => 'Call me',
			'content'      => 'My other number is 555 0199, ask for Sam.',
			'date_created' => gmdate( 'Y-m-d H:i:s' ),
		] );

		add_filter( 'groundhogg/pii/is_restricted', '__return_true' );
		$restricted = Contact_Markdown::render( $contact, [ 'messages' => [ 'limit' => 1 ] ] );
		remove_filter( 'groundhogg/pii/is_restricted', '__return_true' );

		$this->assertStringNotContainsString( '555', $restricted );
		$this->assertStringContainsString( "## Messages\n\n" . Markdown::hidden(), $restricted );
		$this->assertStringContainsString( '555 0199', Contact_Markdown::render( $contact, [ 'messages' ] ) );
	}

	public function test_notes_follow_who_may_see_them() {

		$contact = $this->contact();
		$me      = get_current_user_id();
		$other   = self::factory()->user->create( [ 'role' => 'administrator' ] );

		$contact->add_note( 'Mine', 'user', $me );
		$contact->add_note( 'Someone else\'s', 'user', $other );

		$only_own = function ( $caps, $cap ) {
			return $cap === 'view_others_notes' ? [ 'do_not_allow' ] : $caps;
		};
		add_filter( 'map_meta_cap', $only_own, 10, 2 );
		$own = Contact_Markdown::render( $contact, [ 'notes' ] );
		remove_filter( 'map_meta_cap', $only_own, 10 );

		$this->assertStringContainsString( 'Mine', $own );
		$this->assertStringNotContainsString( 'Someone else', $own );
		$this->assertStringContainsString( 'There may be others.', $own );

		$none = function ( $caps, $cap ) {
			return $cap === 'view_notes' ? [ 'do_not_allow' ] : $caps;
		};
		add_filter( 'map_meta_cap', $none, 10, 2 );
		$this->assertStringContainsString( "## Notes\n\n" . Markdown::hidden(), Contact_Markdown::render( $contact, [ 'notes' ] ) );
		remove_filter( 'map_meta_cap', $none, 10 );
	}

	public function test_times_are_read_the_way_people_write_them() {

		// a date on its own, until the end of that day
		$this->assertSame( Markdown::time( '2026-11-25 23:59:59' ), Markdown::time( '2026-11-25', true ) );
		$this->assertSame( Markdown::time( '2026-11-25 00:00:00' ), Markdown::time( '2026-11-25' ) );

		// eight digits is a date, not a timestamp in 1970
		$this->assertSame( Markdown::time( '2026-11-01' ), Markdown::time( '20261101' ) );
		$this->assertSame( 1790000000, Markdown::time( '1790000000' ) );

		$this->assertSame( 0, Markdown::time( 'whenever' ) );
		$this->assertSame( [ 'since' ], Markdown::unreadable_times( Markdown::list_options(), [ 'since' => 'whenever', 'until' => '2026-11-01' ] ) );
	}

	public function test_an_assistant_is_told_when_a_time_cant_be_read() {

		$out = $this->history( [ 'id' => $this->contact()->get_id(), 'options' => [ 'activity' => [ 'since' => 'the before times' ] ] ] );

		$this->assertWPError( $out );
		$this->assertSame( 'groundhogg_bad_time', $out->get_error_code() );
		$this->assertStringContainsString( 'options.activity.since', $out->get_error_message() );
	}

	public function test_task_due_dates_are_in_the_sites_time_zone() {

		update_option( 'timezone_string', 'America/Toronto' );

		$contact = $this->contact();

		get_db( 'tasks' )->add( [
			'object_id'   => $contact->get_id(),
			'object_type' => 'contact',
			'user_id'     => get_current_user_id(),
			'summary'     => 'Call back',
			'due_date'    => '2030-01-15 17:00:00', // local, like the tasks table keeps it
		] );

		$tasks = Contact_Markdown::render( $contact, [ 'tasks' ] );

		update_option( 'timezone_string', '' );

		$this->assertStringContainsString( 'due 2030-01-15 17:00', $tasks );
		$this->assertStringContainsString( 'not done yet', $tasks );
	}

	public function test_text_is_made_one_tidy_line() {
		$this->assertSame( 'Hello there. New paragraph', Markdown::text( '<p>Hello there.</p><p>New   paragraph</p>' ) );
		$this->assertSame( 'Hello & there', Markdown::text( 'Hello &amp; there' ) );
		$this->assertSame( 'abcd…', Markdown::text( 'abcdefgh', 5 ) );
		$this->assertSame( "- Plan: Pro\n- Seats: 3, 4", Markdown::pairs( [ 'Plan' => 'Pro', 'Empty' => '', 'Seats' => [ 3, 4 ] ] ) );
	}
}
