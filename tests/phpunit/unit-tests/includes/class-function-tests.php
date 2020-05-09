<?php

use function Groundhogg\convert_user_to_contact_when_user_registered;
use function Groundhogg\create_user_from_contact;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;

class Function_Tests extends GH_UnitTestCase {

	public function test_generate_referer_hash(){
		$hash = \Groundhogg\generate_referer_hash( 'https://www.groundhogg.io/my/custom/path/' );
		$this->assertEquals( 20, strlen( $hash ) );
	}

	/**
	 * Test that contacts are created when users are registered
	 *
	 * @see create_user_from_contact()
	 * @see convert_user_to_contact_when_user_registered()
	 */
	public function test_create_contact_when_user_registered_hook() {
		// Remove excess contact records.
		$this->factory()->contacts->get_db()->truncate();

		$user_ids = $this->factory()->user->create_many( 3 );

		$this->assertEquals( 3, get_db( 'contacts' )->count() );

		foreach ( $user_ids as $user_id ) {
			$contact = get_contactdata( $user_id, true );

			$this->assertNotFalse( $contact );

			$this->assertTrue( $contact->exists() );

			$this->assertEquals( $user_id, $contact->get_user_id() );
		}
	}

	/**
	 * Test that the appropriate tags are added to a contact when the new account is created.
	 *
	 * @see create_user_from_contact()
	 * @see convert_user_to_contact_when_user_registered()
	 */
	public function test_contact_has_tags_when_new_user_is_registered() {

		$this->factory()->contacts->get_db()->truncate();

		$user_id  = $this->factory()->user->create( [
			'role' => 'subscriber'
		] );

		$contact = get_contactdata( $user_id, true );

		$this->assertTrue( $contact->has_tag( 'subscriber' ) );
	}

}
