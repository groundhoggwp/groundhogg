<?php

use Groundhogg\Abilities\Contacts\Bulk_Update_Contacts;
use Groundhogg\Abilities\Contacts\Update_Contacts;
use Groundhogg\Background_Tasks;
use Groundhogg\Contact;
use Groundhogg\Pii;
use Groundhogg\Preferences;
use function Groundhogg\get_db;
use function Groundhogg\parse_tag_list;

/**
 * groundhogg/update-contacts (a list of contacts, each with its own changes) and
 * groundhogg/bulk-update-contacts (the same changes for every contact in a segment)
 */
class Bulk_Update_Contacts_Tests extends GH_UnitTestCase {

	/**
	 * @var int[] tag IDs keyed by name
	 */
	protected $tags = [];

	/**
	 * @var int[] contact IDs that edit_contact is denied for
	 */
	protected $not_editable = [];

	public function setUp(): void {
		parent::setUp();

		foreach ( [ 'groundhogg/update-contacts', 'groundhogg/bulk-update-contacts' ] as $ability ) {
			if ( ! function_exists( 'wp_get_ability' ) || ! wp_get_ability( $ability ) ) {
				$this->markTestSkipped( "The $ability ability is not registered." );
			}
		}

		$this->factory()->truncate();
		$this->tags         = [];
		$this->not_editable = [];

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		foreach ( [ 'target', 'old', 'other' ] as $name ) {
			$this->tags[ $name ] = get_db( 'tags' )->add( [ 'tag_name' => $name, 'tag_slug' => $name ] );
		}

		add_filter( 'map_meta_cap', [ $this, 'deny_not_editable' ], 99, 4 );
	}

	public function tearDown(): void {
		remove_filter( 'map_meta_cap', [ $this, 'deny_not_editable' ], 99 );
		parent::tearDown();
	}

	public function deny_not_editable( $caps, $cap, $user_id, $args ) {

		if ( $cap !== 'edit_contact' || empty( $args[0] ) ) {
			return $caps;
		}

		$id = $args[0] instanceof Contact ? $args[0]->get_id() : absint( $args[0] );

		return in_array( $id, $this->not_editable, true ) ? [ 'do_not_allow' ] : $caps;
	}

	protected function make_contact( array $tags = [], array $args = [] ): int {

		$id = $this->factory()->contacts->create( array_merge( [ 'optin_status' => Preferences::CONFIRMED ], $args ) );

		( new Contact( $id ) )->apply_tag( array_map( fn( $name ) => $this->tags[ $name ], $tags ) );

		return $id;
	}

	protected function execute( string $ability, array $input ) {
		$out = wp_get_ability( $ability )->execute( $input );

		return is_wp_error( $out ) ? $out : json_decode( wp_json_encode( $out ), true );
	}

	protected function tag_exists( string $name ): bool {
		return ! empty( parse_tag_list( [ $name ], 'ID', false ) );
	}

	// --- groundhogg/update-contacts ---------------------------------------------

	public function test_update_contacts_applies_each_contacts_own_changes() {

		$a = $this->make_contact( [ 'old' ] );
		$b = $this->make_contact();

		$out = $this->execute( 'groundhogg/update-contacts', [
			'contacts' => [
				[ 'id' => $a, 'first_name' => 'Alice', 'tags_remove' => [ 'old' ], 'meta' => [ 'plan' => 'pro' ] ],
				[ 'id' => $b, 'tags_add' => [ $this->tags['target'] ], 'optin_status' => 'unsubscribed' ],
			],
		] );

		$this->assertNotWPError( $out );
		$this->assertSame( 2, $out['updated'] );
		$this->assertSame( 0, $out['failed'] );
		$this->assertSame( [ $a, $b ], array_column( $out['results'], 'id' ) );

		$a = new Contact( $a );
		$this->assertSame( 'Alice', $a->get_first_name() );
		$this->assertFalse( $a->has_tag( $this->tags['old'] ) );
		$this->assertSame( 'pro', $a->get_meta( 'plan' ) );

		$b = new Contact( $b );
		$this->assertTrue( $b->has_tag( $this->tags['target'] ) );
		$this->assertSame( Preferences::UNSUBSCRIBED, $b->get_optin_status() );
		$this->assertNotSame( 'Alice', $b->get_first_name() );
	}

	public function test_update_contacts_reports_failed_items_and_still_updates_the_rest() {

		$taken       = $this->make_contact( [], [ 'email' => 'taken@example.com' ] );
		$ok          = $this->make_contact();
		$email_clash = $this->make_contact();
		$denied      = $this->make_contact();

		$this->not_editable[] = $denied;

		$out = $this->execute( 'groundhogg/update-contacts', [
			'contacts' => [
				[ 'id' => 999999, 'first_name' => 'Ghost' ],
				[ 'id' => $email_clash, 'email' => 'taken@example.com', 'first_name' => 'Clash' ],
				[ 'id' => $ok, 'first_name' => 'Fine' ],
				[ 'id' => $denied, 'first_name' => 'Denied' ],
				[ 'id' => $ok ],
			],
		] );

		$this->assertNotWPError( $out );
		$this->assertSame( 1, $out['updated'] );
		$this->assertSame( 4, $out['failed'] );

		$codes = array_map( fn( $result ) => $result['success'] ? 'ok' : $result['error']['code'], $out['results'] );

		$this->assertSame( [
			'groundhogg_contact_not_found',
			'groundhogg_email_in_use',
			'ok',
			'groundhogg_cannot_edit_contact',
			'groundhogg_no_changes',
		], $codes );

		$this->assertSame( 'Fine', ( new Contact( $ok ) )->get_first_name() );
		// A failed item is not partly applied
		$this->assertNotSame( 'Clash', ( new Contact( $email_clash ) )->get_first_name() );
		$this->assertNotSame( 'Denied', ( new Contact( $denied ) )->get_first_name() );
		$this->assertSame( 'taken@example.com', ( new Contact( $taken ) )->get_email() );
	}

	public function test_update_contacts_never_creates_a_tag_to_remove() {

		$id = $this->make_contact();

		$out = $this->execute( 'groundhogg/update-contacts', [
			'contacts' => [ [ 'id' => $id, 'tags_remove' => [ 'no-such-tag' ] ] ],
		] );

		$this->assertNotWPError( $out );
		$this->assertFalse( $this->tag_exists( 'no-such-tag' ) );
	}

	public function test_update_contacts_caps_the_list_size() {

		$items = array_fill( 0, Update_Contacts::MAX_ITEMS + 1, [ 'id' => 1, 'first_name' => 'x' ] );

		$out = wp_get_ability( 'groundhogg/update-contacts' )->execute( [ 'contacts' => $items ] );

		$this->assertWPError( $out );
	}

	// --- groundhogg/bulk-update-contacts ------------------------------------------

	public function test_bulk_update_refuses_without_an_audience() {

		$this->make_contact();

		$out = $this->execute( 'groundhogg/bulk-update-contacts', [ 'changes' => [ 'tags_add' => [ 'x' ] ] ] );

		$this->assertWPError( $out );
		$this->assertSame( 'groundhogg_no_audience', $out->get_error_code() );
		$this->assertFalse( $this->tag_exists( 'x' ) );
	}

	public function test_bulk_update_refuses_without_changes() {

		$this->make_contact( [ 'target' ] );

		$out = $this->execute( 'groundhogg/bulk-update-contacts', [
			'tags_include' => [ $this->tags['target'] ],
			'changes'      => [],
		] );

		$this->assertWPError( $out );
		$this->assertSame( 'groundhogg_no_changes', $out->get_error_code() );
	}

	public function test_bulk_update_refuses_when_only_unknown_tags_would_be_removed() {

		$this->make_contact( [ 'target' ] );

		$out = $this->execute( 'groundhogg/bulk-update-contacts', [
			'tags_include' => [ $this->tags['target'] ],
			'changes'      => [ 'tags_remove' => [ 'no-such-tag' ] ],
		] );

		$this->assertWPError( $out );
		$this->assertSame( 'groundhogg_no_changes', $out->get_error_code() );
		$this->assertFalse( $this->tag_exists( 'no-such-tag' ) );
	}

	public function test_bulk_update_refuses_an_audience_with_no_contacts() {

		$this->make_contact();

		$out = $this->execute( 'groundhogg/bulk-update-contacts', [
			'tags_include' => [ $this->tags['target'] ],
			'changes'      => [ 'tags_add' => [ $this->tags['other'] ] ],
		] );

		$this->assertWPError( $out );
		$this->assertSame( 'groundhogg_no_contacts', $out->get_error_code() );
	}

	public function test_bulk_update_refuses_an_invalid_owner() {

		$this->make_contact( [ 'target' ] );

		$out = $this->execute( 'groundhogg/bulk-update-contacts', [
			'tags_include' => [ $this->tags['target'] ],
			'changes'      => [ 'owner' => 999999 ],
		] );

		$this->assertWPError( $out );
		$this->assertSame( 'groundhogg_invalid_owner', $out->get_error_code() );
	}

	public function test_bulk_update_dry_run_counts_and_changes_nothing() {

		$a = $this->make_contact( [ 'target' ] );
		$this->make_contact( [ 'target' ] );
		$this->make_contact();

		$out = $this->execute( 'groundhogg/bulk-update-contacts', [
			'tags_include' => [ $this->tags['target'] ],
			'changes'      => [ 'tags_add' => [ 'brand-new' ], 'optin_status' => 'unsubscribed' ],
			'dry_run'      => true,
		] );

		$this->assertNotWPError( $out );
		$this->assertSame( 'dry_run', $out['mode'] );
		$this->assertSame( 2, $out['matched'] );
		$this->assertFalse( $this->tag_exists( 'brand-new' ) );
		$this->assertSame( Preferences::CONFIRMED, ( new Contact( $a ) )->get_optin_status() );
	}

	public function test_bulk_update_changes_only_the_segment() {

		$in_a    = $this->make_contact( [ 'target', 'old' ] );
		$in_b    = $this->make_contact( [ 'target' ] );
		$outside = $this->make_contact( [ 'old' ] );

		$out = $this->execute( 'groundhogg/bulk-update-contacts', [
			'tags_include' => [ $this->tags['target'] ],
			'changes'      => [
				'tags_add'     => [ 'brand-new' ],
				'tags_remove'  => [ 'old' ],
				'optin_status' => 'unsubscribed',
				'meta'         => [ 'plan' => 'pro' ],
			],
		] );

		$this->assertNotWPError( $out );
		$this->assertSame( 'immediate', $out['mode'] );
		$this->assertSame( 2, $out['matched'] );
		$this->assertSame( 2, $out['updated'] );
		$this->assertSame( 0, $out['skipped'] );

		$new_tag = parse_tag_list( [ 'brand-new' ], 'ID', false )[0];

		foreach ( [ $in_a, $in_b ] as $id ) {
			$contact = new Contact( $id );
			$this->assertTrue( $contact->has_tag( $new_tag ) );
			$this->assertFalse( $contact->has_tag( $this->tags['old'] ) );
			$this->assertSame( Preferences::UNSUBSCRIBED, $contact->get_optin_status() );
			$this->assertSame( 'pro', $contact->get_meta( 'plan' ) );
		}

		$outside = new Contact( $outside );
		$this->assertFalse( $outside->has_tag( $new_tag ) );
		$this->assertTrue( $outside->has_tag( $this->tags['old'] ) );
		$this->assertSame( Preferences::CONFIRMED, $outside->get_optin_status() );
		$this->assertEmpty( $outside->get_meta( 'plan' ) );
	}

	/**
	 * The segment params use optin_status and meta as filters too: at the top level they pick who is
	 * updated, inside changes they are what is set
	 */
	public function test_bulk_update_top_level_optin_status_and_meta_are_filters_not_changes() {

		$match    = $this->make_contact( [], [ 'optin_status' => Preferences::UNCONFIRMED ] );
		$no_match = $this->make_contact( [], [ 'optin_status' => Preferences::CONFIRMED ] );

		( new Contact( $match ) )->update_meta( 'plan', 'free' );
		( new Contact( $no_match ) )->update_meta( 'plan', 'free' );

		$out = $this->execute( 'groundhogg/bulk-update-contacts', [
			'optin_status' => [ Preferences::UNCONFIRMED ],
			'meta'         => [ [ 'key' => 'plan', 'compare' => 'equals', 'value' => 'free' ] ],
			'changes'      => [ 'optin_status' => 'confirmed', 'meta' => [ 'plan' => 'pro' ] ],
		] );

		$this->assertNotWPError( $out );
		$this->assertSame( 1, $out['matched'] );

		$match = new Contact( $match );
		$this->assertSame( Preferences::CONFIRMED, $match->get_optin_status() );
		$this->assertSame( 'pro', $match->get_meta( 'plan' ) );

		$this->assertSame( 'free', ( new Contact( $no_match ) )->get_meta( 'plan' ) );
	}

	public function test_bulk_update_all_contacts() {

		$ids = [ $this->make_contact(), $this->make_contact( [ 'target' ] ) ];

		$out = $this->execute( 'groundhogg/bulk-update-contacts', [
			'all_contacts' => true,
			'changes'      => [ 'tags_add' => [ $this->tags['other'] ] ],
		] );

		$this->assertNotWPError( $out );

		// Includes the contacts the factory made for the test users
		$this->assertSame( $out['matched'], $out['updated'] );

		foreach ( $ids as $id ) {
			$this->assertTrue( ( new Contact( $id ) )->has_tag( $this->tags['other'] ) );
		}
	}

	public function test_bulk_update_skips_contacts_the_user_cannot_edit() {

		$editable = $this->make_contact( [ 'target' ] );
		$denied   = $this->make_contact( [ 'target' ] );

		$this->not_editable[] = $denied;

		$out = $this->execute( 'groundhogg/bulk-update-contacts', [
			'tags_include' => [ $this->tags['target'] ],
			'changes'      => [ 'tags_add' => [ $this->tags['other'] ] ],
		] );

		$this->assertNotWPError( $out );
		$this->assertSame( 1, $out['updated'] );
		$this->assertSame( 1, $out['skipped'] );
		$this->assertTrue( ( new Contact( $editable ) )->has_tag( $this->tags['other'] ) );
		$this->assertFalse( ( new Contact( $denied ) )->has_tag( $this->tags['other'] ) );
	}

	public function test_bulk_update_moves_a_large_segment_to_the_background() {

		$ids = [];

		for ( $i = 0; $i <= Bulk_Update_Contacts::SYNC_LIMIT; $i ++ ) {
			$ids[] = $this->make_contact( [ 'target', 'old' ] );
		}

		$outside = $this->make_contact( [ 'old' ] );

		$out = $this->execute( 'groundhogg/bulk-update-contacts', [
			'tags_include' => [ $this->tags['target'] ],
			'changes'      => [ 'tags_remove' => [ 'old' ], 'tags_add' => [ 'brand-new' ] ],
		] );

		$this->assertNotWPError( $out );
		$this->assertSame( 'background', $out['mode'] );
		$this->assertSame( count( $ids ), $out['matched'] );
		$this->assertTrue( $out['scheduled'] );

		// The new tag is made once, up front, not in the background
		$this->assertTrue( $this->tag_exists( 'brand-new' ) );
		$new_tag = parse_tag_list( [ 'brand-new' ], 'ID', false )[0];

		// Nothing changed yet
		$this->assertTrue( ( new Contact( $ids[0] ) )->has_tag( $this->tags['old'] ) );

		$task = Background_Tasks::get_last_added_task();
		$this->assertNotNull( $task );

		$this->assertTrue( $task->process() );

		foreach ( [ $ids[0], end( $ids ) ] as $id ) {
			$contact = new Contact( $id );
			$this->assertFalse( $contact->has_tag( $this->tags['old'] ) );
			$this->assertTrue( $contact->has_tag( $new_tag ) );
		}

		$this->assertTrue( ( new Contact( $outside ) )->has_tag( $this->tags['old'] ) );
		$this->assertFalse( ( new Contact( $outside ) )->has_tag( $new_tag ) );
	}

	public function test_bulk_update_refuses_a_restricted_user_searching_by_pii() {

		$this->make_contact( [], [ 'email' => 'jane.doe@example.com' ] );

		get_userdata( get_current_user_id() )->add_cap( Pii::CAP, false );

		$out = $this->execute( 'groundhogg/bulk-update-contacts', [
			'search'  => 'jane.doe@example.com',
			'changes' => [ 'tags_add' => [ $this->tags['other'] ] ],
		] );

		$this->assertWPError( $out );
		$this->assertSame( 'groundhogg_pii_restricted', $out->get_error_code() );
	}
}
