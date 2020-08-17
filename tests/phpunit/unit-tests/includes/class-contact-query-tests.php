<?php

use Groundhogg\Contact_Query;
use Groundhogg\Contact_Query_With_Filters;
use Groundhogg\Preferences;

class Contact_Query_Tests extends GH_UnitTestCase {

	public function test_optin_status_exclude() {

		$this->factory()->truncate();

		$this->factory()->contacts->create_many( 5, [
			'optin_status' => Preferences::CONFIRMED
		] );

		$this->factory()->contacts->create_many( 10, [
			'optin_status' => Preferences::UNCONFIRMED
		] );

		$query = new Contact_Query();

		$result = $query->query( [
			'optin_status_exclude' => Preferences::UNCONFIRMED
		] );

		$this->assertCount( 5, $result );
	}

	public function test_contact_query_advanced_filters_are_set() {

		new Contact_Query();

		$this->assertTrue( has_filter( 'groundhogg/contact_query/where_clauses' ) );
		$this->assertTrue( has_filter( 'groundhogg/contact_query/request_join' ) );

	}

	public function test_contact_query_with_filters_has_filters(){

		new Contact_Query();
		$this->assertTrue( Contact_Query::$extra_filters_handler->has_filter( 'first' ) );
	}

	public function test_contact_query_with_filters_first_name_eq() {

		$this->factory()->contacts->create( [
			'first_name' => 'testFirst'
		] );

		$query = new Contact_Query();

		$results = $query->query( [
			'filters' => [
				[
					'type' => 'first',
					'args' => [
						'compare' => 'eq',
						'value'   => 'testFirst'
					]
				]
			]
		] );

		$this->assertCount( 1, $results );
	}

	public function test_contact_query_with_filters_last_name_cnts() {

		$this->factory()->contacts->create( [
			'last_name' => 'testLast'
		] );

		$query = new Contact_Query();

		$results = $query->query( [
			'filters' => [
				[
					'type' => 'last',
					'args' => [
						'compare' => 'cnts',
						'value'   => 'estLas'
					]
				]
			]
		] );

		$this->assertCount( 1, $results );
	}
}
