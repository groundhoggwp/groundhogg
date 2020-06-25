<?php

class Contact_Query_Tests extends GH_UnitTestCase {

	public function test_optin_status_exclude(){

		$this->factory()->truncate();

		$this->factory()->contacts->create_many( 5, [
			'optin_status' => \Groundhogg\Preferences::CONFIRMED
		] );

		$this->factory()->contacts->create_many( 10, [
			'optin_status' => \Groundhogg\Preferences::UNCONFIRMED
		] );

		$query = new \Groundhogg\Contact_Query();

		$result = $query->query( [
			'optin_status_exclude' => \Groundhogg\Preferences::UNCONFIRMED
		] );

		$this->assertCount( 5, $result );
	}
}
