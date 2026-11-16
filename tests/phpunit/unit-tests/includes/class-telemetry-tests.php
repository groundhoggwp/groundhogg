<?php

use Groundhogg\License_Manager;
use Groundhogg\Plugin;
use Groundhogg\Telemetry;
use Groundhogg\Telemetry_Snapshot;
use function Groundhogg\notices;

/**
 * What an opted-in site sends home, see docs/telemetry-payload.md: nothing without opting in, no site address or
 * license key ever, the notice before sites that opted in earlier send more, clones, lifecycle events and the snapshot.
 */
class Telemetry_Tests extends GH_UnitTestCase {

	/**
	 * @var array[] requests made, each [ url, args, body ]
	 */
	protected $requests = [];

	/**
	 * @var int the status the fake endpoint answers with
	 */
	protected $status = 200;

	protected $options = [
		Telemetry::OPTIN_OPTION,
		Telemetry::EMAIL_OPTION,
		Telemetry::SITE_ID_OPTION,
		Telemetry::URL_HASH_OPTION,
		Telemetry::CLONED_FROM_OPTION,
		Telemetry::LAST_VERSION_OPTION,
		Telemetry::LAST_SENT_OPTION,
		Telemetry::LAST_ERROR_OPTION,
		Telemetry::ABILITY_COUNTS_OPTION,
		Telemetry::NOTICE_SHOWN_OPTION,
		Telemetry::INSTALLED_AT_OPTION,
		Telemetry::INSTALLED_AT_ESTIMATED,
		Telemetry::MILESTONES_OPTION,
		Telemetry::MILESTONES_SENT_OPTION,
		'gh_guided_setup_finished',
	];

	public function setUp(): void {
		parent::setUp();

		foreach ( $this->options as $option ) {
			delete_option( $option );
		}

		$this->requests = [];
		$this->status   = 200;

		add_filter( 'pre_http_request', [ $this, 'capture' ], 10, 3 );

		// schema 2 is behind a flag, most tests are about it, the "flag off" ones turn it off again
		add_filter( 'groundhogg/telemetry/v2', '__return_true' );

		wp_clear_scheduled_hook( Telemetry::SNAPSHOT_SOON_HOOK );
		wp_clear_scheduled_hook( Telemetry::MILESTONES_HOOK );
	}

	/**
	 * A site installed since milestones were recorded
	 */
	protected function new_site() {
		update_option( Telemetry::INSTALLED_AT_OPTION, time() - HOUR_IN_SECONDS );
	}

	public function tearDown(): void {
		remove_filter( 'pre_http_request', [ $this, 'capture' ] );
		remove_filter( 'groundhogg/telemetry/v2', '__return_true' );
		remove_filter( 'groundhogg/telemetry/v2', '__return_false' );
		wp_set_current_user( 0 );
		unset( $_REQUEST['page'] );
		set_current_screen( 'front' );
		parent::tearDown();
	}

	public function capture( $pre, $args, $url ) {

		$this->requests[] = [
			'url'  => $url,
			'args' => $args,
			'body' => json_decode( $args['body'] ?? '', true ),
		];

		return [
			'headers'  => [],
			'body'     => '{}',
			'response' => [ 'code' => $this->status, 'message' => 'OK' ],
			'cookies'  => [],
		];
	}

	protected function telemetry(): Telemetry {
		return Plugin::instance()->stats_collection;
	}

	/**
	 * Requests to the telemetry endpoint, by message type
	 *
	 * @param string $type
	 *
	 * @return array[] the messages
	 */
	protected function sent( string $type = '' ) {

		$messages = [];

		foreach ( $this->requests as $request ) {
			if ( $request['url'] === Telemetry::ENDPOINT && ( ! $type || ( $request['body']['type'] ?? '' ) === $type ) ) {
				$messages[] = $request['body'];
			}
		}

		return $messages;
	}

	/**
	 * Opt in the way the settings do, as an admin
	 */
	protected function opt_in() {
		$admin = self::factory()->user->create( [ 'role' => 'administrator', 'user_email' => 'owner@example.com' ] );
		wp_set_current_user( $admin );
		update_option( Telemetry::OPTIN_OPTION, [ 'on' ] );
	}

	/**
	 * Opted in before schema 2: the option is on, there's no site ID
	 */
	protected function opt_in_before_schema_2() {
		remove_action( 'update_option_' . Telemetry::OPTIN_OPTION, [ $this->telemetry(), 'optin_option_updated' ] );
		remove_action( 'add_option_' . Telemetry::OPTIN_OPTION, [ $this->telemetry(), 'optin_option_added' ] );

		update_option( Telemetry::OPTIN_OPTION, [ 'on' ] );

		add_action( 'add_option_' . Telemetry::OPTIN_OPTION, [ $this->telemetry(), 'optin_option_added' ], 10, 2 );
		add_action( 'update_option_' . Telemetry::OPTIN_OPTION, [ $this->telemetry(), 'optin_option_updated' ], 10, 2 );
	}

	/**
	 * Requests to an old CRM webhook
	 *
	 * @param string $url
	 *
	 * @return array[] the bodies
	 */
	protected function sent_to( string $url ) {
		return array_values( array_map( function ( $request ) {
			return $request['body'];
		}, array_filter( $this->requests, function ( $request ) use ( $url ) {
			return $request['url'] === $url;
		} ) ) );
	}

	/**
	 * The current user's notifications feed, the ones added in code
	 *
	 * @return array[] by id
	 */
	protected function feed() {
		return notices()->get_notifications( 'feed' );
	}

	protected function flag_off() {
		remove_filter( 'groundhogg/telemetry/v2', '__return_true' );
		add_filter( 'groundhogg/telemetry/v2', '__return_false' );
	}

	/* ---- the flag ---------------------------------------------------------------------------------------- */

	public function test_schema_2_is_off_by_default() {
		remove_filter( 'groundhogg/telemetry/v2', '__return_true' );

		$this->assertFalse( defined( 'GROUNDHOGG_TELEMETRY_V2' ) );
		$this->assertSame( Telemetry::V2_DEFAULT, Telemetry::is_v2() );
		$this->assertFalse( Telemetry::is_v2() );
	}

	public function test_with_the_flag_off_the_old_weekly_ping_is_sent_and_nothing_new() {
		$this->flag_off();
		$this->opt_in();

		$this->telemetry()->maybe_send_telemetry();
		$this->telemetry()->activating();
		$this->telemetry()->activated();
		$this->telemetry()->deactivated();

		$this->assertEmpty( $this->sent(), 'nothing goes to the new endpoint' );
		$this->assertFalse( get_option( Telemetry::SITE_ID_OPTION ), 'no site ID' );
		$this->assertFalse( wp_next_scheduled( Telemetry::SNAPSHOT_SOON_HOOK ) );

		$legacy = $this->sent_to( Telemetry::LEGACY_WEEKLY_WEBHOOK );

		$this->assertCount( 1, $legacy );

		foreach ( [ 'email', 'date', 'is_licensed', 'master_license', 'is_white_labeled', 'system_info', 'usage', 'extensions' ] as $key ) {
			$this->assertArrayHasKey( $key, $legacy[0] );
		}
	}

	public function test_with_the_flag_off_the_guided_setup_opt_in_still_reaches_the_crm() {
		$this->flag_off();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator', 'user_email' => 'owner@example.com' ] ) );

		$this->telemetry()->optin( true );

		$this->assertEmpty( $this->sent( 'opted_in' ) );
		$this->assertCount( 1, $this->sent_to( Telemetry::LEGACY_OPTIN_WEBHOOK ) );
		$this->assertSame( 'owner@example.com', get_option( Telemetry::EMAIL_OPTION ) );
	}

	public function test_with_the_flag_off_milestones_are_recorded_but_not_sent_and_abilities_not_counted() {
		$this->flag_off();
		$this->new_site();
		$this->opt_in();

		self::factory()->contacts->create();
		do_action( 'wp_after_execute_ability', 'groundhogg/search-contacts', [], [], null );

		$this->telemetry()->send_milestones();

		$this->assertArrayHasKey( 'contact_added', $this->telemetry()->get_milestones() );
		$this->assertEmpty( $this->sent( 'milestone' ) );
		$this->assertSame( [], $this->telemetry()->get_ability_counts() );
	}

	public function test_with_the_flag_off_there_is_no_notification() {
		$this->flag_off();
		$this->opt_in_before_schema_2();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->telemetry()->maybe_add_notification();

		$this->assertArrayNotHasKey( Telemetry::NOTICE_ID, $this->feed() );
	}

	public function test_a_site_that_opted_in_while_the_flag_was_off_gets_the_notice_when_it_is_turned_on() {
		$this->flag_off();
		$this->opt_in();

		remove_filter( 'groundhogg/telemetry/v2', '__return_false' );
		add_filter( 'groundhogg/telemetry/v2', '__return_true' );

		$this->assertTrue( $this->telemetry()->needs_notice() );
		$this->assertFalse( $this->telemetry()->can_send() );
	}

	/* ---- nothing without opting in ----------------------------------------------------------------------- */

	public function test_nothing_is_sent_without_opting_in() {
		$this->telemetry()->maybe_send_telemetry();
		$this->telemetry()->activated();
		$this->telemetry()->deactivated();

		update_option( Telemetry::LAST_VERSION_OPTION, '1.0' );
		$this->telemetry()->maybe_send_upgraded();

		$this->assertEmpty( $this->sent() );
		$this->assertFalse( get_option( Telemetry::SITE_ID_OPTION ) );
	}

	public function test_opting_out_sends_nothing() {
		$this->opt_in();
		$this->requests = [];

		update_option( Telemetry::OPTIN_OPTION, '' );

		$this->assertEmpty( $this->sent() );
	}

	public function test_abilities_are_not_counted_without_opting_in() {
		do_action( 'wp_after_execute_ability', 'groundhogg/search-contacts', [], [], null );

		$this->assertSame( [], $this->telemetry()->get_ability_counts() );
	}

	/* ---- opting in --------------------------------------------------------------------------------------- */

	public function test_opting_in_sends_the_email_once_and_schedules_a_snapshot() {
		$this->opt_in();

		$messages = $this->sent( 'opted_in' );

		$this->assertCount( 1, $messages );
		$this->assertSame( 'owner@example.com', $messages[0]['email'] );
		$this->assertFalse( $messages[0]['newsletter'] );
		$this->assertNotEmpty( wp_next_scheduled( Telemetry::SNAPSHOT_SOON_HOOK ) );

		// the email went with the opt-in only
		$this->telemetry()->send_snapshot();
		$this->assertArrayNotHasKey( 'email', $this->sent( 'snapshot' )[0] );
	}

	public function test_opting_in_from_the_guided_setup_says_whether_they_subscribed() {
		$admin = self::factory()->user->create( [ 'role' => 'administrator', 'user_email' => 'owner@example.com' ] );
		wp_set_current_user( $admin );

		$this->assertTrue( $this->telemetry()->optin( true ) );

		$messages = $this->sent( 'opted_in' );

		$this->assertCount( 1, $messages );
		$this->assertTrue( $messages[0]['newsletter'] );
		$this->assertSame( 'owner@example.com', get_option( Telemetry::EMAIL_OPTION ) );

		// the old webhook still hears about it, for the newsletter and the discount
		$legacy = array_filter( $this->requests, function ( $request ) {
			return $request['url'] === Telemetry::LEGACY_OPTIN_WEBHOOK;
		} );

		$this->assertCount( 1, $legacy );
	}

	public function test_a_message_starts_with_the_envelope() {
		$this->opt_in();

		$message = $this->sent( 'opted_in' )[0];

		$this->assertSame( 2, $message['schema'] );
		$this->assertTrue( wp_is_uuid( $message['site_id'] ) );
		$this->assertSame( get_option( Telemetry::SITE_ID_OPTION ), $message['site_id'] );
		$this->assertSame( Telemetry::url_hash(), $message['url_hash'] );
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $message['sent_at'] );
		$this->assertContains( $message['environment'], [ 'production', 'staging', 'development', 'local', 'demo' ] );
		$this->assertSame( GROUNDHOGG_VERSION, $message['groundhogg'] );
		$this->assertNull( $message['cloned_from'] );
	}

	/**
	 * Pretend the site lives at an address
	 *
	 * @param string $url
	 *
	 * @return callable the filter, to remove
	 */
	protected function site_at( string $url ) {

		$filter = function () use ( $url ) {
			return $url;
		};

		add_filter( 'home_url', $filter );

		return $filter;
	}

	/**
	 * @dataProvider demo_sites
	 */
	public function test_demo_and_sandbox_sites_are_the_demo_environment( string $url ) {

		$filter = $this->site_at( $url );

		$this->assertTrue( \Groundhogg\is_demo_environment() );
		$this->assertSame( 'demo', Telemetry::get_environment() );

		remove_filter( 'home_url', $filter );
	}

	public function demo_sites() {
		return [
			'our demo sites' => [ 'https://acme-co.groundhogg-demo.com' ],
			'instawp'        => [ 'https://quick-test.instawp.xyz' ],
			'instawp link'   => [ 'https://quick-test.instawp.link' ],
			'instawp co'     => [ 'https://quick-test.instawp.co' ],
			'tastewp'        => [ 'https://buildname.s2-tastewp.com' ],
			'playground'     => [ 'https://playground.wordpress.net/scope:0.123' ],
		];
	}

	public function test_a_site_whose_name_only_mentions_a_demo_host_is_not_a_demo() {

		$filter = $this->site_at( 'https://groundhogg-demo.com.example.org' );

		$this->assertFalse( \Groundhogg\is_demo_environment() );

		remove_filter( 'home_url', $filter );
	}

	public function test_playground_is_a_demo_wherever_it_says_it_is() {

		$software = $_SERVER['SERVER_SOFTWARE'] ?? null;

		$_SERVER['SERVER_SOFTWARE'] = 'PHP.wasm';

		$demo = \Groundhogg\is_demo_environment();

		if ( $software === null ) {
			unset( $_SERVER['SERVER_SOFTWARE'] );
		} else {
			$_SERVER['SERVER_SOFTWARE'] = $software;
		}

		$this->assertTrue( $demo );
	}

	/**
	 * Put a must-use plugin in place for the length of a callback
	 *
	 * @param string   $file
	 * @param string   $headers
	 * @param callable $callback
	 */
	protected function with_mu_plugin( string $file, string $headers, callable $callback ) {

		$made_dir = ! is_dir( WPMU_PLUGIN_DIR ) && wp_mkdir_p( WPMU_PLUGIN_DIR );
		$path     = trailingslashit( WPMU_PLUGIN_DIR ) . $file;

		file_put_contents( $path, "<?php\n/*\n$headers\n*/\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- test fixture

		try {
			call_user_func( $callback );
		} finally {
			unlink( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- test fixture

			if ( $made_dir ) {
				rmdir( WPMU_PLUGIN_DIR ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- test fixture
			}
		}
	}

	public function test_a_site_without_instawps_plugins_is_not_on_instawp() {
		$this->assertFalse( \Groundhogg\is_instawp_site() );
	}

	public function test_instawp_on_its_own_domain_is_a_demo_by_its_must_use_plugins() {
		$this->with_mu_plugin( '0-iwp-upgrade-concurrency-guard.php', "Plugin Name: InstaWP — Upgrade concurrency guard\nAuthor: InstaWP", function () {
			$this->assertTrue( \Groundhogg\is_instawp_site() );
			$this->assertSame( 'demo', Telemetry::get_environment() );
		} );
	}

	public function test_instawp_is_found_by_the_author_when_the_file_is_renamed() {
		$this->with_mu_plugin( 'cache-guard.php', "Plugin Name: Cache guard\nAuthor: InstaWP", function () {
			$this->assertTrue( \Groundhogg\is_instawp_site() );
		} );

		$this->with_mu_plugin( 'someone-else.php', "Plugin Name: Something else\nAuthor: Someone", function () {
			$this->assertFalse( \Groundhogg\is_instawp_site() );
		} );
	}

	public function test_a_site_can_add_its_own_demo_hosts() {

		$filter = $this->site_at( 'https://shop.our-sandbox.test' );

		$hosts = function ( $suffixes ) {
			$suffixes[] = '.our-sandbox.test';

			return $suffixes;
		};

		add_filter( 'groundhogg/demo_host_suffixes', $hosts );

		$this->assertTrue( \Groundhogg\is_demo_environment() );

		remove_filter( 'groundhogg/demo_host_suffixes', $hosts );
		remove_filter( 'home_url', $filter );
	}

	/* ---- what is never sent ------------------------------------------------------------------------------ */

	public function test_the_site_address_and_license_key_are_never_sent() {

		$property = new ReflectionProperty( License_Manager::class, 'licenses' );
		$property->setAccessible( true );

		// typed, so it's not there until the licenses have been loaded once
		License_Manager::get_licenses();
		$saved = $property->getValue();
		$property->setValue( null, [] );

		License_Manager::add_license( 'not-a-real-key-1234567890', (object) [
			'success'          => true,
			'license'          => 'valid',
			'item_id'          => 12,
			'item_name'        => 'Test Plan',
			'expires'          => '2030-01-02 23:59:59',
			'price_id'         => '3',
			'license_limit'    => 5,
			'site_count'       => 1,
			'activations_left' => 4,
			'items'            => [ 1, 2, 3 ],
		] );

		try {
			$this->opt_in();
			$this->telemetry()->send_snapshot();
		} finally {
			$property->setValue( null, $saved );
			delete_option( 'gh_licenses' );
		}

		$host = wp_parse_url( home_url(), PHP_URL_HOST );

		$ours = array_filter( $this->requests, function ( $request ) {
			return in_array( $request['url'], [ Telemetry::ENDPOINT, Telemetry::LEGACY_OPTIN_WEBHOOK ], true );
		} );

		$this->assertNotEmpty( $ours );

		foreach ( $ours as $request ) {
			$this->assertStringNotContainsString( $host, $request['args']['body'] );
			$this->assertStringNotContainsString( $host, $request['args']['user-agent'] );
			$this->assertStringNotContainsString( 'not-a-real-key-1234567890', $request['args']['body'] );
		}

		$snapshot = $this->sent( 'snapshot' )[0];

		$this->assertArrayNotHasKey( 'master_license', $snapshot );
		$this->assertArrayNotHasKey( 'email', $snapshot );

		// the plan, and a hash of the key to match against the store
		$this->assertSame( 'Test Plan', $snapshot['license']['plan'] );
		$this->assertSame( '2030-01-02', $snapshot['license']['expires'] );
		$this->assertSame( hash( 'sha256', 'not-a-real-key-1234567890' ), $snapshot['license']['key_hash'] );
	}

	/* ---- the notice for sites that opted in before ------------------------------------------------------- */

	public function test_a_site_that_opted_in_before_sends_nothing_until_the_notice_was_shown() {
		$this->opt_in_before_schema_2();

		$this->assertTrue( $this->telemetry()->needs_notice() );
		$this->assertFalse( $this->telemetry()->can_send() );

		$this->telemetry()->maybe_send_telemetry();
		$this->telemetry()->deactivated();

		$this->assertEmpty( $this->sent() );
		$this->assertFalse( get_option( Telemetry::SITE_ID_OPTION ), 'no site ID made before the notice' );

		// it's in an admin's notifications feed
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$this->telemetry()->maybe_add_notification();

		$feed = $this->feed();

		$this->assertArrayHasKey( Telemetry::NOTICE_ID, $feed );
		$this->assertStringContainsString( 'Stop sharing', $feed[ Telemetry::NOTICE_ID ]['message'] );
		$this->assertStringContainsString( 'tab=misc#setting-gh_opted_in_stats_collection', $feed[ Telemetry::NOTICE_ID ]['message'], 'opens the settings at the setting' );
		$this->assertFalse( $this->telemetry()->can_send(), 'in the feed is not seen yet' );

		// they open the feed
		notices()->read_notice( [ Telemetry::NOTICE_ID ] );

		$this->assertNotEmpty( get_option( Telemetry::NOTICE_SHOWN_OPTION ) );
		$this->assertTrue( $this->telemetry()->can_send() );

		$this->telemetry()->maybe_send_telemetry();
		$this->assertCount( 1, $this->sent( 'snapshot' ) );

		// it stays for whoever hasn't dismissed it
		$this->telemetry()->maybe_add_notification();
		$this->assertArrayHasKey( Telemetry::NOTICE_ID, $this->feed() );
	}

	public function test_dismissing_the_notification_counts_as_seeing_it() {
		$this->opt_in_before_schema_2();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$this->telemetry()->maybe_add_notification();

		notices()->dismiss_notice( Telemetry::NOTICE_ID );

		$this->assertTrue( $this->telemetry()->can_send() );
	}

	public function test_the_notification_is_only_for_admins() {
		$this->opt_in_before_schema_2();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$this->telemetry()->maybe_add_notification();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );

		$this->assertArrayNotHasKey( Telemetry::NOTICE_ID, $this->feed() );

		// and someone who can't manage the site reading it doesn't count
		notices()->read_notice( [ Telemetry::NOTICE_ID ] );

		$this->assertFalse( get_option( Telemetry::NOTICE_SHOWN_OPTION ) );
	}

	public function test_opting_out_takes_the_notification_away() {
		$this->opt_in_before_schema_2();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$this->telemetry()->maybe_add_notification();

		update_option( Telemetry::OPTIN_OPTION, '' );
		$this->telemetry()->maybe_add_notification();

		$this->assertArrayNotHasKey( Telemetry::NOTICE_ID, $this->feed() );
	}

	public function test_new_opt_ins_need_no_notice() {
		$this->opt_in();

		$this->assertFalse( $this->telemetry()->needs_notice() );
		$this->assertTrue( $this->telemetry()->can_send() );
	}

	/* ---- clones ------------------------------------------------------------------------------------------ */

	public function test_a_clone_gets_its_own_id_and_says_where_it_came_from() {
		$this->opt_in();

		$original = get_option( Telemetry::SITE_ID_OPTION );

		// the site now answers somewhere else
		update_option( Telemetry::URL_HASH_OPTION, hash( 'sha256', 'staging.example.com' ) );

		$this->telemetry()->send_snapshot();

		$snapshot = $this->sent( 'snapshot' )[0];

		$this->assertNotSame( $original, $snapshot['site_id'] );
		$this->assertSame( $original, $snapshot['cloned_from'] );

		// said once, until a snapshot got through
		$this->assertFalse( get_option( Telemetry::CLONED_FROM_OPTION ) );
	}

	/* ---- lifecycle --------------------------------------------------------------------------------------- */

	public function test_an_update_is_told_once_with_the_versions() {
		$this->opt_in();

		update_option( Telemetry::LAST_VERSION_OPTION, '4.0' );

		$this->telemetry()->maybe_send_upgraded();
		$this->telemetry()->maybe_send_upgraded();

		$upgraded = $this->sent( 'upgraded' );

		$this->assertCount( 1, $upgraded );
		$this->assertSame( '4.0', $upgraded[0]['from'] );
		$this->assertSame( GROUNDHOGG_VERSION, $upgraded[0]['to'] );
	}

	public function test_the_first_version_seen_is_not_an_update() {
		$this->opt_in();

		$this->telemetry()->maybe_send_upgraded();

		$this->assertEmpty( $this->sent( 'upgraded' ) );
		$this->assertSame( GROUNDHOGG_VERSION, get_option( Telemetry::LAST_VERSION_OPTION ) );
	}

	public function test_the_first_activation_records_the_install_date() {
		// activation comes before anything else on a new site
		$this->telemetry()->activating();
		$this->opt_in();
		$this->telemetry()->activated();
		$this->telemetry()->activating();
		$this->telemetry()->activated();

		$activated = $this->sent( 'activated' );

		$this->assertTrue( $activated[0]['first_activation'] );
		$this->assertFalse( $activated[1]['first_activation'] );
		$this->assertEqualsWithDelta( time(), (int) get_option( Telemetry::INSTALLED_AT_OPTION ), 5 );
		$this->assertFalse( $this->telemetry()->is_installed_at_estimated() );
	}

	public function test_deactivating_is_told() {
		$this->opt_in();

		$this->telemetry()->deactivated();

		$this->assertCount( 1, $this->sent( 'deactivated' ) );
	}

	public function test_sites_installed_before_get_an_estimated_install_date() {
		self::factory()->contacts->create( [ 'date_created' => '2021-03-04 05:06:07' ] );

		$this->telemetry()->maybe_record_installed_at();

		$this->assertTrue( $this->telemetry()->is_installed_at_estimated() );
		$this->assertLessThanOrEqual( strtotime( '2021-03-04 05:06:07 UTC' ), $this->telemetry()->get_installed_at() );
	}

	/* ---- milestones -------------------------------------------------------------------------------------- */

	public function test_a_milestone_is_recorded_once_and_sent_once() {
		$this->new_site();
		$this->opt_in();

		self::factory()->contacts->create();
		self::factory()->contacts->create();

		$this->assertArrayHasKey( 'contact_added', $this->telemetry()->get_milestones() );
		$this->assertNotEmpty( wp_next_scheduled( Telemetry::MILESTONES_HOOK ), 'sent from cron, not during the request' );
		$this->assertEmpty( $this->sent( 'milestone' ) );

		$this->telemetry()->send_milestones();
		$this->telemetry()->send_milestones();

		$milestones = $this->sent( 'milestone' );

		$this->assertCount( 1, $milestones );
		$this->assertSame( 'contact_added', $milestones[0]['milestone'] );
		$this->assertStringEndsWith( ':milestone:contact_added', $milestones[0]['message_id'] );
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $milestones[0]['reached_at'] );
	}

	public function test_sites_installed_before_milestones_do_not_record_them() {
		update_option( Telemetry::INSTALLED_AT_OPTION, strtotime( '-2 years' ) );
		update_option( Telemetry::INSTALLED_AT_ESTIMATED, 1 );

		self::factory()->contacts->create();

		$this->assertSame( [], $this->telemetry()->get_milestones() );
	}

	public function test_milestones_reached_before_opting_in_are_sent_after() {
		$this->new_site();

		self::factory()->contacts->create();

		$this->telemetry()->send_milestones();
		$this->assertEmpty( $this->sent( 'milestone' ) );

		$this->opt_in();
		$this->assertNotEmpty( wp_next_scheduled( Telemetry::MILESTONES_HOOK ) );

		$this->telemetry()->send_milestones();
		$this->assertCount( 1, $this->sent( 'milestone' ) );
	}

	public function test_a_milestone_that_fails_is_sent_with_the_next_snapshot() {
		$this->new_site();
		$this->opt_in();

		self::factory()->contacts->create();

		$this->status = 500;
		$this->telemetry()->send_milestones();

		$this->status   = 200;
		$this->requests = [];
		$this->telemetry()->send_snapshot();

		$this->assertCount( 1, $this->sent( 'milestone' ) );
		$this->assertArrayHasKey( 'contact_added', (array) $this->sent( 'snapshot' )[0]['onboarding']['milestones'] );
	}

	public function test_the_installers_own_contact_is_not_a_milestone() {
		$this->new_site();

		do_action( 'groundhogg/main/activating' );
		self::factory()->contacts->create();

		$this->assertArrayNotHasKey( 'contact_added', $this->telemetry()->get_milestones() );
	}

	public function test_each_milestone_is_reached_from_its_hook() {
		$this->new_site();

		update_option( 'gh_guided_setup_finished', 1 );

		do_action( 'groundhogg/contact/imported', null );

		$this->telemetry()->submission_created( 1, [ 'type' => 'form' ] );

		$flow = self::factory()->funnels->create_and_get( [ 'status' => 'inactive' ] );
		$flow->update( [ 'status' => 'active' ] );

		self::factory()->events->create_and_get( [ 'event_type' => \Groundhogg\Event::FUNNEL, 'email_id' => 5 ] )->complete();
		self::factory()->events->create_and_get( [ 'event_type' => \Groundhogg\Event::BROADCAST ] )->complete();

		$user = self::factory()->user->create();
		add_user_meta( $user, \Groundhogg\Mcp\Connection::SEEN_META, time() );
		add_user_meta( $user, \Groundhogg\Mobile\Promo::USED_META, time() );

		$reached = array_keys( $this->telemetry()->get_milestones() );

		foreach ( [ 'setup_finished', 'contacts_imported', 'form_submitted', 'flow_activated', 'flow_email_sent', 'broadcast_sent', 'agent_connected', 'mobile_app_used' ] as $milestone ) {
			$this->assertContains( $milestone, $reached );
		}
	}

	public function test_an_unknown_milestone_is_ignored() {
		$this->new_site();

		$this->assertFalse( $this->telemetry()->reach_milestone( 'made_up' ) );
		$this->assertSame( [], $this->telemetry()->get_milestones() );
	}

	/* ---- abilities --------------------------------------------------------------------------------------- */

	public function test_the_plugins_abilities_are_counted_until_a_snapshot_is_sent() {
		$this->opt_in();

		do_action( 'wp_after_execute_ability', 'groundhogg/search-contacts', [], [], null );
		do_action( 'wp_after_execute_ability', 'groundhogg/search-contacts', [], [], null );
		do_action( 'wp_after_execute_ability', 'groundhogg-pro/do-thing', [], [], null );
		do_action( 'wp_after_execute_ability', 'other-plugin/do-thing', [], [], null );

		$this->assertSame( [ 'groundhogg/search-contacts' => 2, 'groundhogg-pro/do-thing' => 1 ], $this->telemetry()->get_ability_counts() );

		$this->telemetry()->send_snapshot();

		$this->assertSame( 2, $this->sent( 'snapshot' )[0]['activity']['abilities']['groundhogg/search-contacts'] );
		$this->assertSame( [], $this->telemetry()->get_ability_counts() );
	}

	/* ---- sending ----------------------------------------------------------------------------------------- */

	public function test_a_failed_snapshot_keeps_its_counts_and_says_why() {
		$this->opt_in();

		do_action( 'wp_after_execute_ability', 'groundhogg/search-contacts', [], [], null );

		$this->status = 500;

		$result = $this->telemetry()->send_snapshot();

		$this->assertWPError( $result );
		$this->assertNotEmpty( get_option( Telemetry::LAST_ERROR_OPTION ) );
		$this->assertSame( [ 'groundhogg/search-contacts' => 1 ], $this->telemetry()->get_ability_counts() );
		$this->assertFalse( get_option( Telemetry::LAST_SENT_OPTION ) );
	}

	public function test_a_snapshot_is_identified_by_its_week() {
		$this->opt_in();

		$this->telemetry()->send_snapshot();
		$this->telemetry()->send_snapshot();

		$snapshots = $this->sent( 'snapshot' );

		$this->assertSame( $snapshots[0]['message_id'], $snapshots[1]['message_id'] );
		$this->assertStringEndsWith( gmdate( 'o-\WW' ), $snapshots[0]['message_id'] );
	}

	public function test_a_message_can_be_filtered_out() {
		$this->opt_in();
		$this->requests = [];

		add_filter( 'groundhogg/telemetry/message', '__return_false' );
		$result = $this->telemetry()->send_snapshot();
		remove_filter( 'groundhogg/telemetry/message', '__return_false' );

		$this->assertWPError( $result );
		$this->assertEmpty( $this->sent() );
	}

	public function test_the_endpoint_can_be_changed() {
		$this->opt_in();

		$filter = function () {
			return 'https://telemetry.example.test/collect';
		};

		add_filter( 'groundhogg/telemetry/endpoint', $filter );
		$this->telemetry()->send_snapshot();
		remove_filter( 'groundhogg/telemetry/endpoint', $filter );

		$this->assertSame( 'https://telemetry.example.test/collect', end( $this->requests )['url'] );
	}

	/* ---- the snapshot ------------------------------------------------------------------------------------ */

	public function test_the_snapshot_has_every_section() {
		$snapshot = ( new Telemetry_Snapshot() )->build();

		foreach ( [ 'system', 'license', 'addons', 'plugins', 'size', 'activity', 'features', 'health', 'onboarding', 'extensions' ] as $section ) {
			$this->assertArrayHasKey( $section, $snapshot );
			$this->assertArrayNotHasKey( 'error', (array) $snapshot[ $section ], "the $section section failed: " . wp_json_encode( $snapshot[ $section ] ) );
		}
	}

	public function test_step_types_count_live_steps_in_active_flows_only() {
		$active   = self::factory()->funnels->create( [ 'status' => 'active' ] );
		$inactive = self::factory()->funnels->create( [ 'status' => 'inactive' ] );

		self::factory()->steps->create( [ 'funnel_id' => $active, 'step_type' => 'telemetry_test_step', 'step_status' => 'active' ] );
		self::factory()->steps->create( [ 'funnel_id' => $active, 'step_type' => 'telemetry_test_step', 'step_status' => 'active' ] );
		self::factory()->steps->create( [ 'funnel_id' => $active, 'step_type' => 'telemetry_test_step', 'step_status' => 'inactive' ] );
		self::factory()->steps->create( [ 'funnel_id' => $inactive, 'step_type' => 'telemetry_test_step', 'step_status' => 'active' ] );

		$features = ( new Telemetry_Snapshot() )->features();

		$this->assertSame( 2, $features['step_types']['telemetry_test_step'] );
	}

	public function test_plugins_are_sent_by_folder_without_this_plugin() {
		$active = get_option( 'active_plugins' );

		update_option( 'active_plugins', [ 'woocommerce/woocommerce.php', 'hello.php', plugin_basename( GROUNDHOGG__FILE__ ) ] );

		$plugins = ( new Telemetry_Snapshot() )->plugins();

		update_option( 'active_plugins', $active );

		$this->assertSame( [ 'hello', 'woocommerce' ], $plugins );
	}

	public function test_activity_counts_the_last_week() {
		self::factory()->contacts->create( [ 'date_created' => gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS ) ] );
		self::factory()->contacts->create( [ 'date_created' => gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS ) ] );

		$before = ( new Telemetry_Snapshot() )->activity()['contacts_created'];

		self::factory()->contacts->create( [ 'date_created' => gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ) ] );

		$this->assertSame( $before + 1, ( new Telemetry_Snapshot() )->activity()['contacts_created'] );
	}

	public function test_health_sends_statuses_not_messages() {
		$health = ( new Telemetry_Snapshot() )->health();

		$this->assertNotEmpty( $health['checks'] );

		foreach ( $health['checks'] as $id => $status ) {
			$this->assertContains( $status, [ 'good', 'recommended', 'critical' ], "check $id" );
		}

		$this->assertContains( $health['wp_cron'], [ 'page_loads', 'cron_job', 'off_and_late' ] );
	}

	public function test_a_section_that_fails_does_not_stop_the_rest() {
		$snapshot = new class extends Telemetry_Snapshot {
			public function size() {
				throw new \RuntimeException( 'odd site' );
			}
		};

		$built = $snapshot->build();

		$this->assertSame( [ 'error' => 'RuntimeException' ], $built['size'] );
		$this->assertArrayHasKey( 'php', $built['system'] );
	}

	public function test_empty_maps_are_sent_as_objects() {
		$json = wp_json_encode( ( new Telemetry_Snapshot() )->build() );

		$this->assertStringContainsString( '"abilities":{}', $json );
		$this->assertStringContainsString( '"extensions":{}', $json );
	}

	public function test_extensions_add_their_own_counts() {
		$filter = function ( $snapshot ) {
			$snapshot['extensions'][12345] = [ 'things' => 3 ];

			return $snapshot;
		};

		add_filter( 'groundhogg/telemetry/snapshot', $filter );
		$snapshot = ( new Telemetry_Snapshot() )->build();
		remove_filter( 'groundhogg/telemetry/snapshot', $filter );

		$this->assertSame( [ 'things' => 3 ], $snapshot['extensions'][12345] );
	}
}
