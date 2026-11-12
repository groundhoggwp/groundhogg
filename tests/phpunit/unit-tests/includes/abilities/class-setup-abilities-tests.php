<?php

use function Groundhogg\gh_cron_installed;
use function Groundhogg\uninstall_gh_cron_file;

/**
 * The abilities an agent sets a site up with: get-setup-status, get-cron-status, set-up-cron, and
 * save-sender-profile / delete-sender-profile.
 */
class Setup_Abilities_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();

		if ( ! function_exists( 'wp_get_ability' ) || ! wp_get_ability( 'groundhogg/get-setup-status' ) ) {
			$this->markTestSkipped( 'The setup abilities are not registered.' );
		}

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	public function tearDown(): void {

		if ( gh_cron_installed() ) {
			uninstall_gh_cron_file();
		}

		delete_option( 'gh_disable_wp_cron' );
		delete_option( 'gh_cron_last_ping' );
		delete_option( 'gh_custom_sender_profiles' );

		parent::tearDown();
	}

	protected function run_ability( string $name, array $input = [] ) {
		return wp_get_ability( $name )->execute( $input );
	}

	protected function check( array $status, string $id ) {
		foreach ( $status['checks'] as $check ) {
			if ( $check['id'] === $id ) {
				return $check;
			}
		}

		$this->fail( "No $id check" );
	}

	public function test_setup_status_has_setup_and_health_checks() {

		$status = $this->run_ability( 'groundhogg/get-setup-status' );

		$this->assertIsArray( $status );

		$kinds = array_unique( wp_list_pluck( $status['checks'], 'kind' ) );
		sort( $kinds );
		$this->assertSame( [ 'health', 'setup' ], $kinds );

		$this->assertSame( count( $status['checks'] ), array_sum( $status['summary'] ) );

		// every problem says how it's fixed
		foreach ( $status['checks'] as $check ) {
			if ( $check['status'] === 'good' ) {
				$this->assertNull( $check['fix'] );
			} else {
				$this->assertContains( $check['fix']['by'], [ 'agent', 'person', 'either' ], $check['id'] );
			}
		}
	}

	public function test_missing_business_details_are_reported() {

		update_option( 'gh_business_name', '' );

		$check = $this->check( $this->run_ability( 'groundhogg/get-setup-status' ), 'business_info' );

		$this->assertSame( 'recommended', $check['status'] );
		$this->assertSame( 'agent', $check['fix']['by'] );
		$this->assertStringContainsString( 'business_name', $check['details'] );

		update_option( 'gh_business_name', 'Acme' );
		update_option( 'gh_street_address_1', '1 Main St' );
		update_option( 'gh_city', 'Springfield' );
		update_option( 'gh_country', 'US' );

		$this->assertSame( 'good', $this->check( $this->run_ability( 'groundhogg/get-setup-status' ), 'business_info' )['status'] );
	}

	public function test_sending_from_another_domain_is_reported() {

		$site = preg_replace( '/^www\./', '', wp_parse_url( home_url(), PHP_URL_HOST ) );

		update_option( 'gh_override_from_email', 'someone@gmail.com' );
		$this->assertSame( 'recommended', $this->check( $this->run_ability( 'groundhogg/get-setup-status' ), 'sender' )['status'] );

		update_option( 'gh_override_from_email', "news@$site" );
		$this->assertSame( 'good', $this->check( $this->run_ability( 'groundhogg/get-setup-status' ), 'sender' )['status'] );
	}

	public function test_only_problems_leaves_out_good_checks() {

		$status = $this->run_ability( 'groundhogg/get-setup-status', [ 'only_problems' => true ] );

		$this->assertNotContains( 'good', wp_list_pluck( $status['checks'], 'status' ) );
	}

	public function test_setup_status_needs_manage_options() {

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$this->assertWPError( $this->run_ability( 'groundhogg/get-setup-status' ) );
	}

	public function test_cron_status_says_what_to_do() {

		$status = $this->run_ability( 'groundhogg/get-cron-status' );

		$this->assertFalse( $status['event_queue_cron']['installed'] );
		$this->assertFalse( $status['event_queue_cron']['working'] );
		$this->assertStringEndsWith( 'gh-cron.php', $status['event_queue_cron']['job']['url'] );
		$this->assertStringContainsString( 'gh-cron.php', $status['event_queue_cron']['job']['command'] );
		$this->assertStringContainsString( 'set-up-cron', implode( ' ', $status['next_steps'] ) );
	}

	public function test_set_up_cron_installs_the_file() {

		$result = $this->run_ability( 'groundhogg/set-up-cron' );

		$this->assertTrue( $result['file_installed'] );
		$this->assertTrue( gh_cron_installed() );
	}

	/**
	 * The WordPress test suite defines DISABLE_WP_CRON, the same as a wp-config.php that turns it off. Only a person
	 * can change that, so it's reported rather than fought with.
	 */
	public function test_wordpress_cron_turned_off_in_config_is_left_alone() {

		if ( ! defined( 'DISABLE_WP_CRON' ) || ! DISABLE_WP_CRON ) {
			$this->markTestSkipped( 'DISABLE_WP_CRON is not defined in this environment.' );
		}

		$this->assertWPError( $this->run_ability( 'groundhogg/set-up-cron', [ 'wordpress_cron' => 'page_loads' ] ) );

		$result = $this->run_ability( 'groundhogg/set-up-cron', [ 'wordpress_cron' => 'cron_job' ] );

		$this->assertSame( 'cron_job', $result['wordpress_cron'] );
		$this->assertFalse( \Groundhogg\is_option_enabled( 'gh_disable_wp_cron' ) );

		$status = $this->run_ability( 'groundhogg/get-cron-status' );

		$this->assertSame( 'wp-config', $status['wordpress_cron']['turned_off_by'] );
		$this->assertFalse( $status['wordpress_cron']['runs_on_page_loads'] );
	}

	public function test_custom_sender_profiles_can_be_added_changed_and_removed() {

		// one made in the settings, which has to survive
		update_option( 'gh_custom_sender_profiles', wp_json_encode( [ [ 'Billing', 'billing@example.com', 'abcd1234' ] ] ) );

		$created = $this->run_ability( 'groundhogg/save-sender-profile', [
			'from_name'  => 'Support',
			'from_email' => 'support@example.com',
		] );

		$this->assertTrue( $created['created'] );
		$this->assertStringStartsWith( 'custom-', $created['id'] );

		$updated = $this->run_ability( 'groundhogg/save-sender-profile', [
			'id'         => $created['id'],
			'from_name'  => 'Help',
			'from_email' => 'help@example.com',
		] );

		$this->assertFalse( $updated['created'] );

		$stored = json_decode( get_option( 'gh_custom_sender_profiles' ), true );

		$this->assertSame( [
			[ 'Billing', 'billing@example.com', 'abcd1234' ],
			[ 'Help', 'help@example.com', substr( $created['id'], strlen( 'custom-' ) ) ],
		], $stored );

		$this->assertSame( [ 'deleted' => true ], $this->run_ability( 'groundhogg/delete-sender-profile', [ 'id' => $created['id'] ] ) );
		$this->assertSame( [ [ 'Billing', 'billing@example.com', 'abcd1234' ] ], json_decode( get_option( 'gh_custom_sender_profiles' ), true ) );

		$this->assertWPError( $this->run_ability( 'groundhogg/delete-sender-profile', [ 'id' => $created['id'] ] ) );
	}

	public function test_default_sender_profile_is_the_override_settings() {

		$this->run_ability( 'groundhogg/save-sender-profile', [
			'id'         => 'default',
			'from_name'  => 'Acme',
			'from_email' => 'hello@example.com',
		] );

		$this->assertSame( 'Acme', get_option( 'gh_override_from_name' ) );
		$this->assertSame( 'hello@example.com', get_option( 'gh_override_from_email' ) );
	}

	public function test_a_users_sender_profile_needs_an_owner() {

		$subscriber = self::factory()->user->create( [ 'role' => 'subscriber' ] );

		$this->assertWPError( $this->run_ability( 'groundhogg/save-sender-profile', [
			'id'         => "user-$subscriber",
			'from_name'  => 'Nope',
			'from_email' => 'nope@example.com',
		] ) );
	}
}
