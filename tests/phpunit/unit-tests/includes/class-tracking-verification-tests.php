<?php

use Groundhogg\Submission;
use Groundhogg\Tracking;
use function Groundhogg\base64url_encode;
use function Groundhogg\do_replacements;
use function Groundhogg\encrypt;
use function Groundhogg\get_contactdata;
use function Groundhogg\tracking;

/**
 * Covers the identity-binding hardening added for a form submission that matches an *existing*
 * contact by a submitted, unverified email address — that's not proof of identity, so the
 * resulting session should only ever be able to render back what it has itself submitted, never
 * the real contact's full record, until independently verified.
 *
 * @see Tracking::form_filled()
 * @see Tracking::is_current_contact_verified()
 * @see Tracking::get_current_session_submission_ids()
 * @see Tracking::track_newly_created_contact()
 * @see Replacements::process() ambient-resolution gating ($unverified_ambient_context)
 * @see Replacements::get_unverified_submission_value()
 * @see Replacements::replacement_form_submission() submission_ids scoping
 */
class Tracking_Verification_Tests extends GH_UnitTestCase {

	protected function assertNoSubstring( $needle, $haystack, $message = '' ) {
		$this->assertFalse( strpos( $haystack, $needle ), $message ?: "Failed asserting that '$haystack' does not contain '$needle'." );
	}

	/**
	 * Tracking is a singleton with protected, in-memory cookie state that would otherwise leak
	 * between test methods (and between this class and any other test touching tracking()).
	 */
	protected function reset_tracking_state() {
		foreach ( [ 'cookie', 'newly_created_contact_ids' ] as $prop ) {
			$ref = new ReflectionProperty( Tracking::class, $prop );
			$ref->setAccessible( true );
			$ref->setValue( tracking(), [] );
		}
	}

	public function setUp(): void {
		parent::setUp();
		$this->reset_tracking_state();
	}

	public function tearDown(): void {
		$this->reset_tracking_state();
		parent::tearDown();
	}

	/**
	 * Helper: create a Submission (not the 'form' type, so get_answers() doesn't need a real
	 * Form_v2 config behind it) and post the given data to it.
	 */
	protected function make_submission( $contact_id, array $posted_data ) {
		$submission = new Submission();
		$submission->create( [
			'contact_id' => $contact_id,
			'type'       => 'other',
		] );
		$submission->add_posted_data( $posted_data );

		return $submission;
	}

	/* ---------------------------------------------------------------------
	 * Tracking::form_filled() — verified vs. unverified
	 * ------------------------------------------------------------------- */

	public function test_newly_created_contact_is_verified() {
		$contact = get_contactdata( self::factory()->contacts->create() );

		do_action( 'groundhogg/contact/created', $contact );

		tracking()->form_filled( $contact );

		$this->assertTrue( tracking()->is_current_contact_verified() );
		$this->assertSame( [], tracking()->get_current_session_submission_ids() );
	}

	public function test_matched_existing_contact_is_unverified() {
		// Not firing groundhogg/contact/created — simulates matching a pre-existing contact
		// purely by submitted email, exactly like Form_v2::submit()'s new Contact($data) does.
		$contact = get_contactdata( self::factory()->contacts->create() );

		tracking()->form_filled( $contact, 123 );

		$this->assertFalse( tracking()->is_current_contact_verified() );
		$this->assertSame( [ 123 ], tracking()->get_current_session_submission_ids() );
	}

	/**
	 * The most common real-world way a contact ever gets tracked at all: clicking a link in an
	 * email they received. Only Groundhogg itself, holding the site's own secret key, could have
	 * produced a string that decrypts to a real contact — as strong a proof of identity as the
	 * unsubscribe flow's signed permissions key.
	 */
	public function test_failsafe_identity_tracking_is_verified() {
		$contact = get_contactdata( self::factory()->contacts->create() );

		$_GET['gi'] = base64url_encode( encrypt( $contact->get_email() ) );

		tracking()->handle_failsafe_tracking();

		unset( $_GET['gi'] );

		$this->assertSame( $contact->get_id(), tracking()->get_current_contact_id() );
		$this->assertTrue( tracking()->is_current_contact_verified() );
	}

	public function test_repeat_submission_accumulates_session_submission_ids() {
		$contact = get_contactdata( self::factory()->contacts->create() );

		tracking()->form_filled( $contact, 1 );
		tracking()->form_filled( $contact, 2 );

		$this->assertFalse( tracking()->is_current_contact_verified() );
		$this->assertSame( [ 1, 2 ], tracking()->get_current_session_submission_ids() );
	}

	public function test_max_session_submissions_are_capped() {
		$contact = get_contactdata( self::factory()->contacts->create() );

		for ( $i = 1; $i <= Tracking::MAX_SESSION_SUBMISSIONS + 5; $i ++ ) {
			tracking()->form_filled( $contact, $i );
		}

		$ids = tracking()->get_current_session_submission_ids();

		$this->assertCount( Tracking::MAX_SESSION_SUBMISSIONS, $ids );
		$this->assertSame( Tracking::MAX_SESSION_SUBMISSIONS + 5, end( $ids ) );
	}

	/* ---------------------------------------------------------------------
	 * Replacements ambient-resolution gating
	 * ------------------------------------------------------------------- */

	public function test_ambient_unverified_rendering_uses_only_submitted_data() {
		$contact = get_contactdata( self::factory()->contacts->create( [
			'first_name' => 'RealName',
		] ) );

		$submission = $this->make_submission( $contact->get_id(), [ 'first_name' => 'FakeTypedName' ] );

		tracking()->form_filled( $contact, $submission->get_id() );

		$this->assertSame( 'FakeTypedName', do_replacements( '{first_name}' ) );
	}

	public function test_ambient_unverified_rendering_blocks_unsubmitted_field() {
		$contact = get_contactdata( self::factory()->contacts->create( [
			'first_name' => 'RealName',
		] ) );

		// The submission never mentioned first_name at all.
		$submission = $this->make_submission( $contact->get_id(), [ 'email' => $contact->get_email() ] );

		tracking()->form_filled( $contact, $submission->get_id() );

		$this->assertSame( '', do_replacements( '{first_name}' ) );
	}

	public function test_ambient_unverified_rendering_resolves_legacy_aliases_and_full_name() {
		$contact = get_contactdata( self::factory()->contacts->create( [
			'first_name' => 'RealFirst',
			'last_name'  => 'RealLast',
		] ) );

		// {first}/{last} are legacy aliases of {first_name}/{last_name} and must resolve to the
		// same submitted value, not the code's own (different) literal name; {full_name} has no
		// submission-shaped analog at all and must compose from the submitted parts instead.
		$submission = $this->make_submission( $contact->get_id(), [
			'first_name' => 'FakeFirst',
			'last_name'  => 'FakeLast',
		] );

		tracking()->form_filled( $contact, $submission->get_id() );

		$this->assertSame( 'FakeFirst', do_replacements( '{first}' ) );
		$this->assertSame( 'FakeLast', do_replacements( '{last}' ) );
		$this->assertSame( 'FakeFirst FakeLast', do_replacements( '{full_name}' ) );
	}

	public function test_ambient_unverified_rendering_allows_owner_name_but_not_owner_contact_info() {
		$owner_id = self::factory()->user->create( [
			'first_name' => 'OwnerFirst',
			'last_name'  => 'OwnerLast',
			'user_email' => 'gh-owner-test@example.org',
		] );

		$contact = get_contactdata( self::factory()->contacts->create( [ 'owner_id' => $owner_id ] ) );

		$submission = $this->make_submission( $contact->get_id(), [ 'email' => $contact->get_email() ] );

		tracking()->form_filled( $contact, $submission->get_id() );

		// Low-sensitivity — the owner's name is allowed even though this session is unverified.
		$this->assertSame( 'OwnerFirst', do_replacements( '{owner_first_name}' ) );
		$this->assertSame( 'OwnerLast', do_replacements( '{owner_last_name}' ) );
		// Directly reachable contact info for the rep stays blocked, same as before.
		$this->assertSame( '', do_replacements( '{owner_email}' ) );
		$this->assertSame( '', do_replacements( '{owner.user_email}' ) );
	}

	public function test_ambient_unverified_rendering_andlist_orlist_ol_ul_use_submission_data() {
		$contact = get_contactdata( self::factory()->contacts->create() );
		$contact->update_meta( 'secret_field', 'do-not-leak' );

		$submission = $this->make_submission( $contact->get_id(), [ 'favorite_color' => 'blue' ] );

		tracking()->form_filled( $contact, $submission->get_id() );

		// A field that WAS submitted resolves through each formatting wrapper...
		$this->assertSame( 'blue', do_replacements( '{andList.favorite_color}' ) );
		$this->assertSame( 'blue', do_replacements( '{orList.favorite_color}' ) );
		// ...but the real contact's own secret field, never submitted, still doesn't leak through
		// any of them just because they're a different code name than {meta.x} itself.
		$this->assertSame( '', do_replacements( '{andList.secret_field}' ) );
		$this->assertSame( '', do_replacements( '{orList.secret_field}' ) );
		$this->assertNoSubstring( 'do-not-leak', do_replacements( '{ol.secret_field}' ) );
		$this->assertNoSubstring( 'do-not-leak', do_replacements( '{ul.secret_field}' ) );
	}

	public function test_ambient_unverified_rendering_allows_contact_independent_codes() {
		$contact = get_contactdata( self::factory()->contacts->create() );

		$submission = $this->make_submission( $contact->get_id(), [ 'email' => $contact->get_email() ] );

		tracking()->form_filled( $contact, $submission->get_id() );

		// None of these read anything specific to the ambient contact, so they should run
		// normally even though this session is unverified.
		$this->assertSame( 'literal text', do_replacements( '{redact.literal text}' ) );
		$this->assertSame( 'a+b', do_replacements( '{urlencode.a b}' ) );
		$this->assertNotSame( '', do_replacements( '{site_url}' ) );
	}

	public function test_ambient_unverified_rendering_blocks_unsubmitted_meta() {
		$contact = get_contactdata( self::factory()->contacts->create() );
		$contact->update_meta( 'secret_field', 'do-not-leak' );

		$submission = $this->make_submission( $contact->get_id(), [ 'email' => $contact->get_email() ] );

		tracking()->form_filled( $contact, $submission->get_id() );

		$this->assertSame( '', do_replacements( '{meta.secret_field}' ) );
	}

	public function test_ambient_unverified_rendering_blocks_non_scopable_codes() {
		$contact = get_contactdata( self::factory()->contacts->create() );
		$contact->add_tag( [ 'secret-vip-tag' ] );

		$submission = $this->make_submission( $contact->get_id(), [ 'email' => $contact->get_email() ] );

		tracking()->form_filled( $contact, $submission->get_id() );

		$this->assertSame( '', do_replacements( '{tag_names}' ) );
		$this->assertSame( '', do_replacements( '{notes}' ) );
	}

	public function test_verified_session_gets_full_personalization() {
		$contact = get_contactdata( self::factory()->contacts->create( [
			'first_name' => 'RealName',
		] ) );

		do_action( 'groundhogg/contact/created', $contact );
		tracking()->form_filled( $contact );

		$this->assertSame( 'RealName', do_replacements( '{first_name}' ) );
	}

	public function test_explicit_contact_id_bypasses_ambient_gating() {
		// Simulates an outgoing email/broadcast targeting a specific contact directly — always
		// fully personalized, regardless of whatever the current browser's own tracked session
		// happens to be. This is what keeps automated sends unaffected by any of this.
		$victim = get_contactdata( self::factory()->contacts->create( [ 'first_name' => 'Victim' ] ) );

		$attacker_session_contact = get_contactdata( self::factory()->contacts->create() );
		$submission               = $this->make_submission( $attacker_session_contact->get_id(), [
			'email' => $attacker_session_contact->get_email(),
		] );

		tracking()->form_filled( $attacker_session_contact, $submission->get_id() );

		$this->assertSame( 'Victim', do_replacements( '{first_name}', $victim->get_id() ) );
	}

	public function test_form_submission_tag_scoped_to_session_submissions() {
		$contact = get_contactdata( self::factory()->contacts->create() );

		// An older, unrelated submission this session did NOT make.
		$this->make_submission( $contact->get_id(), [ 'secret_answer' => 'from an earlier real visit' ] );

		// The submission this session actually made.
		$new_submission = $this->make_submission( $contact->get_id(), [ 'email' => $contact->get_email() ] );

		tracking()->form_filled( $contact, $new_submission->get_id() );

		$result = do_replacements( '{form_submission}' );

		$this->assertNoSubstring( 'secret_answer', $result );
		$this->assertNoSubstring( 'from an earlier real visit', $result );
	}
}
