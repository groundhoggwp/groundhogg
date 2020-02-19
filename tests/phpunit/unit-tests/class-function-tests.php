<?php

use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;

class Function_Tests extends GH_UnitTestCase
{

	/**
	 * Test that contacts are created when users are registered
	 */
	public function test_create_contact_when_user_registered_hook()
	{
		// Remove excess contact records.
		$this->factory()->contacts->get_db()->truncate();

		$user_ids = $this->factory()->user->create_many( 3 );

		$this->assertEquals( 3, get_db( 'contacts' )->count() );

		foreach ( $user_ids as $user_id ){
			$contact = get_contactdata( $user_id, true );

			$this->assertNotFalse( $contact );

			$this->assertTrue( $contact->exists() );

			$this->assertEquals( $user_id, $contact->get_user_id() );
		}
	}

}
