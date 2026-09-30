<?php

use Groundhogg\Form\Form_v2;

/**
 * Covers the per-IP, per-form submission rate limiting on public form submissions.
 *
 * @see Form_v2::is_rate_limited()
 */
class Form_Rate_Limit_Tests extends GH_UnitTestCase {

	protected $remote_addr;

	public function setUp(): void {
		parent::setUp();
		$this->remote_addr = $_SERVER['REMOTE_ADDR'] ?? null;
		$_SERVER['REMOTE_ADDR'] = '203.0.113.10';
		wp_set_current_user( 0 );
	}

	public function tearDown(): void {
		if ( $this->remote_addr === null ) {
			unset( $_SERVER['REMOTE_ADDR'] );
		} else {
			$_SERVER['REMOTE_ADDR'] = $this->remote_addr;
		}

		remove_all_filters( 'groundhogg/form/v2/rate_limit' );
		remove_all_filters( 'groundhogg/form/v2/rate_limit_window' );
		parent::tearDown();
	}

	/**
	 * A form with a fixed ID, so the tests don't need a real step in the DB.
	 * A Form_v2 for a step that doesn't exist reports an ID of 0.
	 */
	protected function make_form( $id ) {
		$form = new class( [ 'id' => 0 ] ) extends Form_v2 {
			public $fake_id = 0;

			public function get_id() {
				return $this->fake_id;
			}
		};

		$form->fake_id = $id;

		return $form;
	}

	public function test_blocks_after_limit_is_exceeded() {
		add_filter( 'groundhogg/form/v2/rate_limit', fn() => 3 );
		$form = $this->make_form( 9001 );

		for ( $i = 0; $i < 3; $i ++ ) {
			$this->assertFalse( $form->is_rate_limited(), "Attempt $i should be allowed" );
		}

		$this->assertTrue( $form->is_rate_limited() );
	}

	public function test_limit_is_per_ip() {
		add_filter( 'groundhogg/form/v2/rate_limit', fn() => 1 );
		$form = $this->make_form( 9002 );

		$this->assertFalse( $form->is_rate_limited() );
		$this->assertTrue( $form->is_rate_limited() );

		$_SERVER['REMOTE_ADDR'] = '203.0.113.11';
		$this->assertFalse( $form->is_rate_limited() );
	}

	public function test_limit_is_per_form() {
		add_filter( 'groundhogg/form/v2/rate_limit', fn() => 1 );

		$this->assertFalse( $this->make_form( 9003 )->is_rate_limited() );
		$this->assertTrue( $this->make_form( 9003 )->is_rate_limited() );
		$this->assertFalse( $this->make_form( 9004 )->is_rate_limited() );
	}

	public function test_zero_disables_rate_limiting() {
		add_filter( 'groundhogg/form/v2/rate_limit', fn() => 0 );
		$form = $this->make_form( 9005 );

		for ( $i = 0; $i < 50; $i ++ ) {
			$this->assertFalse( $form->is_rate_limited() );
		}
	}

	public function test_privileged_users_are_exempt() {
		add_filter( 'groundhogg/form/v2/rate_limit', fn() => 1 );
		$form = $this->make_form( 9006 );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		wp_get_current_user()->add_cap( 'add_contacts' );

		for ( $i = 0; $i < 5; $i ++ ) {
			$this->assertFalse( $form->is_rate_limited() );
		}
	}

	public function test_fails_open_without_a_valid_ip() {
		add_filter( 'groundhogg/form/v2/rate_limit', fn() => 1 );
		$_SERVER['REMOTE_ADDR'] = 'not-an-ip';
		$form = $this->make_form( 9007 );

		for ( $i = 0; $i < 5; $i ++ ) {
			$this->assertFalse( $form->is_rate_limited() );
		}
	}

	public function test_window_expiry_resets_the_count() {
		add_filter( 'groundhogg/form/v2/rate_limit', fn() => 1 );
		$form = $this->make_form( 9008 );

		$this->assertFalse( $form->is_rate_limited() );
		$this->assertTrue( $form->is_rate_limited() );

		// expire the window
		$key = 'gh_form_rl_9008_' . md5( '203.0.113.10' );
		set_transient( $key, [ 'count' => 5, 'expires' => time() - 1 ], 60 );

		$this->assertFalse( $form->is_rate_limited() );
	}
}
