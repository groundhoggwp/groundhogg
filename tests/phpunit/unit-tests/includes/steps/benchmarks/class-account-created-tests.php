<?php

use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Preferences;
use Groundhogg\Step;
use Groundhogg\Steps\Benchmarks\Account_Created;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;

class Account_Created_Tests extends GH_UnitTestCase {

	public function __construct( $name = null, array $data = [], $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );
	}

	/**
	 * Test that the account created benchmark works when creating a new user
	 */
	public function test_wp_insert_user_event_created() {

		// empty all of the GH dbs, let's start fresh
		Plugin::instance()->dbs->truncate_dbs();

		$funnel = new Funnel( [
			'title'  => 'Test Funnel',
			'status' => 'active'
		] );

		$account_created = $funnel->add_step( [
			'step_title' => 'Account Created',
			'step_type'  => Account_Created::TYPE,
			'step_group' => Account_Created::GROUP,
			'meta'       => [
				'role' => [
					'subscriber'
				]
			]
		] );

		$user_id = $this->factory()->user->create();

		$events = $account_created->get_waiting_events();

		// Ensure events exist
		$this->assertNotEmpty( $events );
		// Ensure the contact is the one created from the user
		$this->assertEquals( $user_id, $events[0]->get_contact()->get_user_id() );
	}

	/**
	 * Test that the account created benchmark works when creating a new user and
	 * does not create an event if the role does not match the one provided
	 */
	public function test_wp_insert_user_event_not_created() {

		// empty all of the GH dbs, let's start fresh
		Plugin::instance()->dbs->truncate_dbs();

		$funnel = new Funnel( [
			'title'  => 'Test Funnel',
			'status' => 'active'
		] );

		$account_created = $funnel->add_step( [
			'step_title' => 'Account Created',
			'step_type'  => Account_Created::TYPE,
			'step_group' => Account_Created::GROUP,
			'meta'       => [
				'role' => [
					'subscriber'
				]
			]
		] );

		$user_id = $this->factory()->user->create( [
			'role' => 'administrator'
		] );

		$events = $account_created->get_waiting_events();

		// Ensure events exist
		$this->assertEmpty( $events );
	}


}
