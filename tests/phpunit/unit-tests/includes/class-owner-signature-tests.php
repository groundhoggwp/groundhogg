<?php

use function Groundhogg\do_replacements;
use function Groundhogg\get_contactdata;

/**
 * @see \Groundhogg\Replacements::replacement_owner_signature()
 */
class Owner_Signature_Tests extends GH_UnitTestCase {

	protected $errors = [];

	public function setUp(): void {
		parent::setUp();
		$this->errors = [];
		set_error_handler( function ( $no, $message ) {
			$this->errors[] = $message;

			return true;
		} );
	}

	public function tearDown(): void {
		restore_error_handler();
		parent::tearDown();
	}

	public function test_a_contact_without_an_owner_gets_nothing_and_no_warning() {

		$contact = get_contactdata( self::factory()->contacts->create( [ 'email' => 'no-owner-' . uniqid() . '@example.com', 'owner_id' => 0 ] ) );

		$this->assertSame( '', trim( do_replacements( '{owner_signature}', $contact ) ) );
		$this->assertSame( [], $this->errors );
	}

	public function test_the_signature_of_the_owner_is_used() {

		$user_id = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		update_user_meta( $user_id, 'signature', 'Best, The Owner' );

		$contact = get_contactdata( self::factory()->contacts->create( [ 'email' => 'owned-' . uniqid() . '@example.com', 'owner_id' => $user_id ] ) );

		$this->assertStringContainsString( 'Best, The Owner', do_replacements( '{owner_signature}', $contact ) );
		$this->assertSame( [], $this->errors );
	}
}
