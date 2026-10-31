<?php

use Groundhogg\Pii;
use function Groundhogg\get_contactdata;

/**
 * The view_contact_pii capability, and what a user without it can't see or search by through the abilities
 */
class Pii_Restriction_Tests extends GH_UnitTestCase {

	protected $contact_id;

	public function setUp(): void {
		parent::setUp();

		if ( ! function_exists( 'wp_get_ability' ) || ! wp_get_ability( 'groundhogg/get-contact' ) ) {
			$this->markTestSkipped( 'The groundhogg/get-contact ability is not registered.' );
		}

		$this->contact_id = $this->factory()->contacts->create( [
			'email'      => 'jane.doe@example.com',
			'first_name' => 'Jane',
			'last_name'  => 'Doe',
		] );

		get_contactdata( $this->contact_id )->update_meta( [
			'primary_phone'  => '555-0100',
			'company_name'   => 'Acme',
			'unregistered_x' => 'mystery',
		] );
	}

	protected function admin( bool $pii ): int {

		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$user    = get_userdata( $user_id );

		// WP_Roles::add_cap() doesn't refresh cached roles, so don't depend on the installer having run on this DB
		$user->add_cap( Pii::CAP, $pii );

		wp_set_current_user( $user_id );

		return $user_id;
	}

	protected function execute( string $ability, array $input ) {
		$out = wp_get_ability( $ability )->execute( $input );

		return is_wp_error( $out ) ? $out : json_decode( wp_json_encode( $out ), true );
	}

	public function test_a_user_with_the_capability_is_not_restricted() {
		$this->admin( true );
		$this->assertFalse( Pii::is_restricted() );
	}

	public function test_a_user_without_the_capability_is_restricted_even_as_administrator() {
		$this->admin( false );
		$this->assertTrue( Pii::is_restricted() );
	}

	public function test_no_user_is_a_system_context_and_never_restricted() {
		wp_set_current_user( 0 );
		$this->assertFalse( Pii::is_restricted() );
	}

	public function test_unrestricted_user_sees_the_contact() {
		$this->admin( true );

		$out = $this->execute( 'groundhogg/get-contact', [ 'id' => $this->contact_id, 'expand' => [ 'meta' ] ] );

		$this->assertNotWPError( $out );
		$this->assertEquals( 'jane.doe@example.com', $out['email'] );
		$this->assertEquals( 'Jane', $out['first_name'] );
		$this->assertEquals( '555-0100', $out['meta']['primary_phone'] );
	}

	public function test_restricted_user_gets_a_redacted_contact_with_the_same_id() {
		$this->admin( false );

		$out = $this->execute( 'groundhogg/get-contact', [ 'id' => $this->contact_id, 'expand' => [ 'tags', 'meta' ] ] );

		$this->assertNotWPError( $out );
		$this->assertEquals( $this->contact_id, $out['id'] );
		$this->assertEquals( Pii::REDACTED, $out['email'] );
		$this->assertEquals( Pii::REDACTED, $out['first_name'] );
		$this->assertEquals( Pii::REDACTED, $out['last_name'] );
		$this->assertArrayHasKey( 'date_created', $out );
		$this->assertArrayHasKey( 'optin_status', $out );
	}

	public function test_restricted_meta_keeps_safe_keys_and_drops_pii_and_unknown_keys() {
		$this->admin( false );

		$out = $this->execute( 'groundhogg/get-contact', [ 'id' => $this->contact_id, 'expand' => [ 'meta' ] ] );

		$this->assertNotWPError( $out );
		$this->assertEquals( 'Acme', $out['meta']['company_name'] );
		$this->assertArrayNotHasKey( 'primary_phone', $out['meta'] );
		$this->assertArrayNotHasKey( 'unregistered_x', $out['meta'] );
	}

	public function test_restricted_user_cannot_look_a_contact_up_by_email() {
		$this->admin( false );

		$out = $this->execute( 'groundhogg/get-contact', [ 'email' => 'jane.doe@example.com' ] );

		$this->assertWPError( $out );
		$this->assertEquals( 'groundhogg_pii_restricted', $out->get_error_code() );
	}

	public function test_restricted_user_cannot_search_by_text() {
		$this->admin( false );

		$out = $this->execute( 'groundhogg/search-contacts', [ 'search' => 'jane' ] );

		$this->assertWPError( $out );
		$this->assertEquals( 'groundhogg_pii_restricted', $out->get_error_code() );
	}

	public function test_restricted_user_cannot_filter_on_a_pii_meta_key() {
		$this->admin( false );

		$out = $this->execute( 'groundhogg/search-contacts', [
			'meta' => [ [ 'key' => 'primary_phone', 'compare' => 'starts_with', 'value' => '555' ] ],
		] );

		$this->assertWPError( $out );
		$this->assertEquals( 'groundhogg_pii_restricted', $out->get_error_code() );
	}

	public function test_restricted_user_cannot_filter_on_an_unknown_meta_key() {
		$this->admin( false );

		$out = $this->execute( 'groundhogg/search-contacts', [
			'meta' => [ [ 'key' => 'unregistered_x', 'value' => 'mystery' ] ],
		] );

		$this->assertWPError( $out );
	}

	public function test_restricted_user_cannot_filter_on_pii_inside_exclude_segment() {
		$this->admin( false );

		$out = $this->execute( 'groundhogg/search-contacts', [
			'exclude_segment' => [ 'meta' => [ [ 'key' => 'city', 'value' => 'Toronto' ] ] ],
		] );

		$this->assertWPError( $out );
	}

	public function test_restricted_user_can_segment_on_a_safe_key_and_gets_redacted_results() {
		$this->admin( false );

		$out = $this->execute( 'groundhogg/search-contacts', [
			'meta' => [ [ 'key' => 'company_name', 'value' => 'Acme' ] ],
		] );

		$this->assertNotWPError( $out );
		$this->assertEquals( 1, $out['total_items'] );
		$this->assertEquals( $this->contact_id, $out['contacts'][0]['id'] );
		$this->assertEquals( Pii::REDACTED, $out['contacts'][0]['email'] );
	}

	public function test_unrestricted_user_can_still_search_by_text() {
		$this->admin( true );

		$out = $this->execute( 'groundhogg/search-contacts', [ 'search' => 'jane.doe@example.com' ] );

		$this->assertNotWPError( $out );
		$this->assertEquals( 1, $out['total_items'] );
		$this->assertEquals( 'jane.doe@example.com', $out['contacts'][0]['email'] );
	}

	public function test_restricted_user_cannot_create_a_contact_that_exists() {
		$this->admin( false );

		$out = $this->execute( 'groundhogg/create-contact', [ 'email' => 'jane.doe@example.com' ] );

		$this->assertWPError( $out );
	}

	public function test_restricted_user_can_create_a_new_contact_and_gets_it_redacted() {
		$this->admin( false );

		$out = $this->execute( 'groundhogg/create-contact', [ 'email' => 'new.person@example.com', 'first_name' => 'New' ] );

		$this->assertNotWPError( $out );
		$this->assertTrue( $out['created'] );
		$this->assertEquals( Pii::REDACTED, $out['contact']['email'] );
		$this->assertEquals( 'new.person@example.com', get_contactdata( $out['contact']['id'] )->get_email() );
	}

	public function test_restricted_user_has_no_raw_table_access() {
		$this->admin( false );

		$out = $this->execute( 'groundhogg/query-table', [ 'table' => 'contactmeta' ] );

		$this->assertWPError( $out );
		$this->assertEquals( 'groundhogg_pii_restricted', $out->get_error_code() );
	}

	public function test_filters_on_pii_are_found_at_any_depth() {
		$this->assertTrue( Pii::filters_touch_pii( [ [ [ 'type' => 'email', 'compare' => 'contains', 'value' => 'x' ] ] ] ) );
		$this->assertTrue( Pii::filters_touch_pii( [ [ [ 'type' => 'sub_query', 'include_filters' => [ [ [ 'type' => 'first_name', 'value' => 'a' ] ] ] ] ] ] ) );
		$this->assertTrue( Pii::filters_touch_pii( [ [ [ 'type' => 'meta', 'meta' => 'primary_phone', 'value' => '1' ] ] ] ) );
		$this->assertFalse( Pii::filters_touch_pii( [ [ [ 'type' => 'tags', 'tags' => [ 1 ] ], [ 'type' => 'country', 'value' => 'CA' ] ] ] ) );
		$this->assertFalse( Pii::filters_touch_pii( [ [ [ 'type' => 'meta', 'meta' => 'company_name', 'value' => 'Acme' ] ] ] ) );
	}

	public function test_adding_a_field_as_pii_hides_its_value_from_a_restricted_user() {

		$this->admin( true );

		$added = $this->execute( 'groundhogg/add-custom-field', [
			'label' => 'Survey mobile', 'type' => 'text', 'group' => 'Survey', 'name' => 'survey_mobile', 'pii' => true,
		] );

		$this->assertNotWPError( $added );
		$this->assertTrue( $added['field']['pii'] );

		$listed = $this->execute( 'groundhogg/list-custom-fields', [] );
		$this->assertNotWPError( $listed );

		get_contactdata( $this->contact_id )->update_meta( [ 'survey_mobile' => '555-0123' ] );

		$this->admin( false );

		$out = $this->execute( 'groundhogg/get-contact', [ 'id' => $this->contact_id, 'expand' => [ 'meta' ] ] );

		$this->assertNotWPError( $out );
		$this->assertArrayNotHasKey( 'survey_mobile', $out['meta'] );
	}

	protected function add_field( string $name, bool $pii ): string {

		$this->admin( true );

		$added = $this->execute( 'groundhogg/add-custom-field', [
			'label' => $name, 'type' => 'text', 'group' => 'Survey', 'name' => $name, 'pii' => $pii,
		] );

		$this->assertNotWPError( $added );

		return $added['field']['id'];
	}

	public function test_a_restricted_user_cannot_clear_the_pii_flag() {

		$id = $this->add_field( 'flagged_field', true );

		$this->admin( false );

		$out = $this->execute( 'groundhogg/update-custom-field', [ 'id' => $id, 'pii' => false ] );

		$this->assertWPError( $out );
		$this->assertEquals( 'groundhogg_pii_restricted', $out->get_error_code() );
		$this->assertTrue( \Groundhogg\Properties::instance()->get_field( 'flagged_field' )['pii'] );
	}

	public function test_a_restricted_user_can_set_the_pii_flag() {

		$id = $this->add_field( 'unflagged_field', false );

		$this->admin( false );

		$out = $this->execute( 'groundhogg/update-custom-field', [ 'id' => $id, 'pii' => true ] );

		$this->assertNotWPError( $out );
		$this->assertTrue( $out['field']['pii'] );
	}

	public function test_an_unrestricted_user_can_clear_the_pii_flag() {

		$id = $this->add_field( 'clearable_field', true );

		$this->admin( true );

		$out = $this->execute( 'groundhogg/update-custom-field', [ 'id' => $id, 'pii' => false ] );

		$this->assertNotWPError( $out );
		$this->assertFalse( $out['field']['pii'] );
	}

	public function test_a_restricted_user_cannot_drop_the_flag_by_saving_the_fields_again() {

		$this->add_field( 'saved_field', true );

		$this->admin( false );

		// what the editor sends: the field's id is new and the flag is gone, only the internal name is the same
		$sanitized = \Groundhogg\Properties::sanitize( [
			'fields' => [ [ 'id' => 'readded', 'group' => 'g', 'label' => 'Saved', 'name' => 'saved_field', 'type' => 'text' ] ],
		] );

		$this->assertTrue( $sanitized['fields'][0]['pii'] );

		$this->admin( true );

		$sanitized = \Groundhogg\Properties::sanitize( [
			'fields' => [ [ 'id' => 'readded', 'group' => 'g', 'label' => 'Saved', 'name' => 'saved_field', 'type' => 'text' ] ],
		] );

		$this->assertArrayNotHasKey( 'pii', $sanitized['fields'][0] );
	}

	public function test_a_custom_field_flagged_as_pii_is_hidden_from_a_restricted_user() {

		$properties = \Groundhogg\Properties::instance();
		$all        = $properties->get_all();

		$all['fields'][] = [ 'id' => 'f_pii', 'group' => 'g', 'label' => 'Survey phone', 'name' => 'survey_phone', 'type' => 'text', 'pii' => true ];
		$all['fields'][] = [ 'id' => 'f_ok', 'group' => 'g', 'label' => 'Plan', 'name' => 'plan_tier', 'type' => 'text' ];

		update_option( 'gh_contact_custom_properties', $all );
		\Groundhogg\Properties::$instance = null;

		get_contactdata( $this->contact_id )->update_meta( [ 'survey_phone' => '555-0199', 'plan_tier' => 'gold' ] );

		$this->admin( false );

		$out = $this->execute( 'groundhogg/get-contact', [ 'id' => $this->contact_id, 'expand' => [ 'meta' ] ] );

		$this->assertNotWPError( $out );
		$this->assertArrayNotHasKey( 'survey_phone', $out['meta'] );
		$this->assertEquals( 'gold', $out['meta']['plan_tier'] );

		\Groundhogg\Properties::$instance = null;
	}

	/**
	 * @see Pii::scrub_text()
	 */
	public function test_scrub_text_leaves_the_text_alone_for_an_unrestricted_user() {
		$this->admin( true );

		$text = 'Spoke to Jane Doe on jane.doe@example.com';

		$this->assertSame( $text, Pii::scrub_text( $text, $this->contact_id ) );
	}

	public function test_scrub_text_redacts_the_contacts_own_details() {
		$this->admin( false );

		$out = Pii::scrub_text( 'Spoke to Jane Doe (jane.doe@example.com) about renewal, call her on 555-0100.', $this->contact_id );

		$this->assertSame( 'Spoke to [redacted] ([redacted]) about renewal, call her on [redacted].', $out );
	}

	public function test_scrub_text_ignores_case_and_possessives_but_not_other_words() {
		$this->admin( false );

		$out = Pii::scrub_text( 'JANE asked, then jane\'s manager. Janet is someone else, and the janeway is a ship.', $this->contact_id );

		$this->assertSame( '[redacted] asked, then [redacted]\'s manager. Janet is someone else, and the janeway is a ship.', $out );
	}

	public function test_scrub_text_finds_a_phone_number_however_it_is_punctuated() {
		$this->admin( false );

		foreach ( [ '555-0100', '555 0100', '5550100', '555.0100' ] as $written ) {
			$this->assertSame( 'Number: [redacted]', Pii::scrub_text( "Number: {$written}", $this->contact_id ), $written );
		}

		$this->assertSame( 'Number: 5550101', Pii::scrub_text( 'Number: 5550101', $this->contact_id ) );
	}

	public function test_scrub_text_redacts_the_longest_value_first() {
		$this->admin( false );

		// the address has the name in it, the whole address goes and not a part of it
		$this->assertSame( 'Write to [redacted]', Pii::scrub_text( 'Write to jane.doe@example.com', $this->contact_id ) );
	}

	public function test_scrub_text_finds_alternate_addresses_and_flagged_custom_fields() {

		$all             = \Groundhogg\Properties::instance()->get_all();
		$all['fields'][] = [ 'id' => 'f_pii2', 'group' => 'g', 'label' => 'Nickname', 'name' => 'nickname', 'type' => 'text', 'pii' => true ];
		$all['fields'][] = [ 'id' => 'f_ok2', 'group' => 'g', 'label' => 'Plan', 'name' => 'plan_tier', 'type' => 'text' ];

		update_option( 'gh_contact_custom_properties', $all );
		\Groundhogg\Properties::$instance = null;

		get_contactdata( $this->contact_id )->update_meta( [
			'alternate_emails' => "jd@work.example.org\nsecond@home.example.net",
			'nickname'         => 'Janie',
			'plan_tier'        => 'gold',
		] );

		$this->admin( false );

		$out = Pii::scrub_text( 'jd@work.example.org, second@home.example.net, Janie, gold plan', $this->contact_id );

		$this->assertSame( '[redacted], [redacted], [redacted], gold plan', $out );

		\Groundhogg\Properties::$instance = null;
	}

	public function test_scrub_text_leaves_values_too_short_to_tell_from_words() {

		$contact_id = $this->factory()->contacts->create( [ 'email' => 'al@example.com', 'first_name' => 'Al', 'last_name' => 'Li' ] );

		$this->admin( false );

		$this->assertSame( 'Al and Li came, so did [redacted]', Pii::scrub_text( 'Al and Li came, so did al@example.com', $contact_id ) );
	}

	public function test_scrub_text_filter_adds_what_to_find_and_what_replaces_it() {

		$add = function ( $pairs, $contact ) {
			$pairs['Acme Corp']   = '[company]';
			$pairs['Project X']   = '';
			$pairs['QX']          = '[short]';

			return $pairs;
		};

		add_filter( 'groundhogg/pii/scrub_text_pairs', $add, 10, 2 );

		$this->admin( false );

		$out = Pii::scrub_text( 'Jane of acme corp is on Project X, not QXY or QX', $this->contact_id );

		// the filter can add short values, they are not held to the minimum length of the contact's own
		$this->assertSame( '[redacted] of [company] is on [redacted], not QXY or [short]', $out );
		$this->assertArrayHasKey( 'Acme Corp', Pii::scrub_pairs( $this->contact_id ) );

		// text that is not about a contact is still scrubbed with what the filter adds
		$this->assertSame( '[company] and [redacted]', Pii::scrub_text( 'Acme Corp and Project X' ) );

		remove_filter( 'groundhogg/pii/scrub_text_pairs', $add, 10 );
	}

	public function test_scrub_text_with_no_contact_and_no_filter_changes_nothing() {
		$this->admin( false );

		$this->assertSame( 'Hello Jane', Pii::scrub_text( 'Hello Jane' ) );
	}

	public function test_a_restricted_user_gets_a_note_ability_without_the_contacts_details() {

		$this->admin( true );

		$added = $this->execute( 'groundhogg/add-contact-note', [
			'contact_id' => $this->contact_id,
			'content'    => 'Jane Doe called from 555-0100 about renewal',
		] );

		$this->assertNotWPError( $added );
		$this->assertStringContainsString( 'Jane Doe', $added['note']['content'] );

		$this->admin( false );

		$listed = $this->execute( 'groundhogg/list-contact-notes', [ 'contact_id' => $this->contact_id ] );

		$this->assertNotWPError( $listed );
		$this->assertSame( '[redacted] called from [redacted] about renewal', $listed['notes'][0]['content'] );
	}
}
