<?php

use Groundhogg\Contact;
use Groundhogg\Preferences;

/**
 * Reading a property on an object must never call the method by the same name.
 * Names often come from settings (webhook values, form fields, export columns), and
 * methods like delete() and unsubscribe() change the contact.
 */
class Base_Object_Get_Tests extends GH_UnitTestCase {

	protected function make_contact() {
		return new Contact( [
			'email'        => 'base-object-get-' . wp_generate_password( 6, false ) . '@example.com',
			'first_name'   => 'Søren',
			'optin_status' => Preferences::CONFIRMED,
		] );
	}

	public function test_reading_a_method_name_does_not_call_the_method() {

		$this->setExpectedIncorrectUsage( 'Groundhogg\Contact::__get' );

		$contact = $this->make_contact();
		$id      = $contact->get_id();

		$this->assertFalse( $contact->delete );
		$this->assertFalse( $contact->unsubscribe );

		$fresh = new Contact( $id );

		$this->assertTrue( $fresh->exists(), 'reading $contact->delete deleted the contact' );
		$this->assertSame( Preferences::CONFIRMED, $fresh->get_optin_status(), 'reading $contact->unsubscribe unsubscribed the contact' );
	}

	public function test_a_webhook_value_that_matches_a_method_name_stays_a_string() {

		$this->setExpectedIncorrectUsage( 'Groundhogg\Contact::__get' );

		$contact = $this->make_contact();
		$value   = 'update';

		// the Webhook step only swaps a value for the contact's field when the lookup is truthy
		$this->assertFalse( $contact->$value );
	}

	public function test_meta_named_like_a_method_is_readable() {

		$contact = $this->make_contact();
		$contact->update_meta( 'duplicate', 'from meta' );

		$this->assertSame( 'from meta', $contact->duplicate );
	}

	public function test_columns_and_properties_still_read() {

		$contact = $this->make_contact();

		$this->assertSame( 'Søren', $contact->first_name );
		$this->assertSame( $contact->get_id(), (int) $contact->ID );
		$this->assertFalse( $contact->no_such_field_or_method );
	}

	public function test_step_columns_sharing_a_method_name_read_the_column() {

		$funnel = new \Groundhogg\Funnel( [ 'title' => 'get tests', 'status' => 'inactive' ] );
		$step   = $funnel->add_step( [
			'step_type'  => \Groundhogg\Steps\Benchmarks\Account_Created::TYPE,
			'step_group' => \Groundhogg\Step::BENCHMARK,
			'is_entry'   => 1,
		] );

		$this->assertEquals( 1, $step->is_entry );
		$this->assertEquals( 0, $step->is_locked );
	}
}
