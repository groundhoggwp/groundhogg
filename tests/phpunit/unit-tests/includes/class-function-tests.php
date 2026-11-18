<?php

use Groundhogg\Tag_Mapping;
use function Groundhogg\convert_user_to_contact_when_user_registered;
use function Groundhogg\create_user_from_contact;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;
use function Groundhogg\preg_quote_except;

class Function_Tests extends GH_UnitTestCase {

	public function test_generate_referer_hash() {
		$hash = \Groundhogg\generate_referer_hash( 'https://groundhogg.io/my/custom/path/' );
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

		// Role tags come from tag mapping, which is opt-in and only registers its hooks when enabled at construction
		update_option( 'gh_enable_tag_mapping', 'on' );
		new Tag_Mapping();

		$user_id = $this->factory()->user->create( [
			'role' => 'subscriber'
		] );

		$contact = get_contactdata( $user_id, true );

		$this->assertTrue( $contact->has_tag( 'Subscriber' ) );
	}

	/**
	 * @see create_user_from_contact()
	 */
	public function test_a_user_is_created_for_a_contact_and_linked() {

		$contact = get_contactdata( $this->factory()->contacts->create( [ 'email' => 'new.person@example.com' ] ) );

		$user_id = create_user_from_contact( $contact, 'subscriber', false );

		$this->assertIsInt( $user_id );
		$this->assertSame( 'new.person@example.com', get_userdata( $user_id )->user_email );
		$this->assertSame( $user_id, ( new \Groundhogg\Contact( $contact->get_id() ) )->get_user_id() );
	}

	/**
	 * wp_insert_user() gives an error when there is a user with the email already, which isn't a user to link to
	 *
	 * @see create_user_from_contact()
	 */
	public function test_a_contact_is_not_linked_to_a_user_when_the_user_could_not_be_created() {

		$this->factory()->contacts->get_db()->truncate();

		$contact = get_contactdata( $this->factory()->contacts->create( [ 'email' => 'taken@example.com' ] ) );

		// a user with the contact's email that the contact isn't linked to
		add_filter( 'groundhogg/should_convert_user_to_contact_when_user_registered', '__return_false' );
		$this->factory()->user->create( [ 'user_email' => 'taken@example.com', 'user_login' => 'taken_person' ] );
		remove_filter( 'groundhogg/should_convert_user_to_contact_when_user_registered', '__return_false' );

		$this->assertEmpty( ( new \Groundhogg\Contact( $contact->get_id() ) )->get_user_id() );

		$users = count_users()['total_users'];

		$this->assertFalse( create_user_from_contact( $contact, 'subscriber', false ) );

		$this->assertEmpty( ( new \Groundhogg\Contact( $contact->get_id() ) )->get_user_id() );
		$this->assertSame( $users, count_users()['total_users'] );
	}

	public function test_preg_quote_except() {

		// string, except, expected
		$tests = [
			[ '/my-path/$', [ '$', '-' ], '/my-path/$' ],
			[ '/my-path/$', [ '-' ], '/my-path/\$' ],
			[ 'https://wordpress.org/$', [ '$' ], 'https\://wordpress\.org/$' ],
			[ 'https://wordpress.org/$', [ '.', ':' ], 'https://wordpress.org/\$' ],
			[ 'https://wordpress.(org)/', [ ':' ], 'https://wordpress\.\(org\)/' ],
		];

		foreach ( $tests as $test ) {
			$this->assertEquals( preg_quote_except( $test[0], $test[1] ), $test[2] );
		}
	}

}
