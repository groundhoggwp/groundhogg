<?php

use Groundhogg\Mobile\App;
use Groundhogg\Mobile\Features;
use Groundhogg\Mobile\Manifest;

/**
 * The mobile app's feature registry and the manifest built from it.
 *
 * What matters most: the manifest is filtered per user on the server, so a user never receives a tab, card or
 * bundle they can't use, and nothing a registration keeps server side (a callable capability) reaches the client.
 */
class Mobile_Features_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		$this->reset_singleton();
	}

	public function tearDown(): void {
		remove_all_actions( 'groundhogg/mobile/register_features' );
		$this->reset_singleton();
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * The registry is a singleton that fires its action once, so tests that register through it need a fresh one.
	 */
	protected function reset_singleton() {
		$ref = new ReflectionProperty( Features::class, 'instance' );
		$ref->setAccessible( true );
		$ref->setValue( null, null );
	}

	protected function user_with_role( string $role ): int {
		return self::factory()->user->create( [ 'role' => $role ] );
	}

	protected function nav_ids( array $visible, string $feature = 'core' ): array {
		return array_column( $visible['features'][ $feature ]['nav'] ?? [], 'id' );
	}

	/* ---- registration ------------------------------------------------------------------------------------ */

	public function test_registering_an_id_twice_is_rejected() {
		$this->setExpectedIncorrectUsage( 'Groundhogg\Mobile\Features::register' );

		$features = new Features();

		$this->assertTrue( $features->register( 'thing', [ 'nav' => [ [ 'id' => 'a', 'route' => '/a' ] ] ] ) );
		$this->assertFalse( $features->register( 'thing', [] ) );
	}

	public function test_a_feature_without_an_id_is_rejected() {
		$this->setExpectedIncorrectUsage( 'Groundhogg\Mobile\Features::register' );

		$this->assertFalse( ( new Features() )->register( '', [] ) );
	}

	public function test_addons_register_on_the_action_and_core_is_always_there() {
		add_action( 'groundhogg/mobile/register_features', function ( Features $features ) {
			$features->register( 'pipeline', [ 'nav' => [ [ 'id' => 'deals', 'route' => '/deals' ] ] ] );
		} );

		$all = ( new Features() )->all();

		$this->assertArrayHasKey( 'core', $all );
		$this->assertArrayHasKey( 'pipeline', $all );
	}

	public function test_the_action_fires_once_per_registry() {
		$calls = 0;
		add_action( 'groundhogg/mobile/register_features', function () use ( &$calls ) {
			$calls ++;
		} );

		$features = new Features();
		$features->all();
		$features->all();
		$features->for_user( 1 );

		$this->assertSame( 1, $calls );
	}

	/* ---- visibility -------------------------------------------------------------------------------------- */

	public function test_an_administrator_sees_the_core_screens_in_priority_order() {
		$visible = ( new Features() )->for_user( $this->user_with_role( 'administrator' ) );

		$this->assertSame( [ 'home', 'contacts', 'tasks' ], $this->nav_ids( $visible ) );
		$this->assertSame( [ 'call', 'text', 'email' ], array_column( $visible['features']['core']['contact_actions'], 'id' ) );
	}

	/**
	 * Call and text are plain links. Email isn't: the app offers a choice of the phone's email app, writing one in
	 * the app, or a saved email, so it has no href and is handled in JS.
	 */
	public function test_call_and_text_are_links_and_email_is_a_handler_action() {
		$actions = ( new Features() )->for_user( $this->user_with_role( 'sales_rep' ) )['features']['core']['contact_actions'];
		$by_id   = array_column( $actions, null, 'id' );

		$this->assertStringStartsWith( 'tel:', $by_id['call']['href'] );
		$this->assertStringStartsWith( 'sms:', $by_id['text']['href'] );
		$this->assertArrayNotHasKey( 'href', $by_id['email'] );
	}

	public function test_sales_reps_and_managers_get_the_app() {
		foreach ( [ 'sales_rep', 'sales_manager', 'marketer' ] as $role ) {
			$visible = ( new Features() )->for_user( $this->user_with_role( $role ) );

			$this->assertContains( 'contacts', $this->nav_ids( $visible ), "$role should see contacts" );
		}
	}

	public function test_a_user_without_groundhogg_access_gets_no_features() {
		$visible = ( new Features() )->for_user( $this->user_with_role( 'subscriber' ) );

		$this->assertSame( [], $visible['features'] );
	}

	public function test_contributions_are_filtered_by_their_own_capability() {
		add_action( 'groundhogg/mobile/register_features', function ( Features $features ) {
			$features->register( 'pipeline', [
				'cap'         => 'view_contacts',
				'nav'         => [
					[ 'id' => 'deals', 'route' => '/deals' ],
					[ 'id' => 'reports', 'route' => '/reports', 'cap' => 'manage_options' ],
				],
				'home_cards'  => [ [ 'id' => 'only-admin', 'cap' => 'manage_options' ] ],
			] );
		} );

		$rep   = ( new Features() )->for_user( $this->user_with_role( 'sales_rep' ) );
		$admin = ( new Features() )->for_user( $this->user_with_role( 'administrator' ) );

		$this->assertSame( [ 'deals' ], $this->nav_ids( $rep, 'pipeline' ) );
		$this->assertSame( [], $rep['features']['pipeline']['home_cards'] );
		$this->assertSame( [ 'deals', 'reports' ], $this->nav_ids( $admin, 'pipeline' ) );
	}

	public function test_a_feature_the_user_cannot_use_is_dropped_with_its_bundle() {
		add_action( 'groundhogg/mobile/register_features', function ( Features $features ) {
			$features->register( 'admin-only', [
				'cap'    => 'manage_options',
				'script' => [ 'src' => 'https://example.test/admin-only.js' ],
				'nav'    => [ [ 'id' => 'x', 'route' => '/x' ] ],
			] );
		} );

		$rep = ( new Features() )->for_user( $this->user_with_role( 'sales_rep' ) );

		$this->assertArrayNotHasKey( 'admin-only', $rep['features'] );
		$this->assertStringNotContainsString( 'admin-only.js', wp_json_encode( $rep ) );
	}

	public function test_a_feature_with_nothing_visible_is_dropped() {
		add_action( 'groundhogg/mobile/register_features', function ( Features $features ) {
			$features->register( 'empty', [ 'nav' => [ [ 'id' => 'x', 'route' => '/x', 'cap' => 'manage_options' ] ] ] );
		} );

		$rep = ( new Features() )->for_user( $this->user_with_role( 'sales_rep' ) );

		$this->assertArrayNotHasKey( 'empty', $rep['features'] );
	}

	public function test_an_array_capability_needs_all_of_them() {
		add_action( 'groundhogg/mobile/register_features', function ( Features $features ) {
			$features->register( 'both', [ 'cap' => [ 'view_contacts', 'manage_options' ], 'nav' => [ [ 'id' => 'x', 'route' => '/x' ] ] ] );
		} );

		$rep   = ( new Features() )->for_user( $this->user_with_role( 'sales_rep' ) );
		$admin = ( new Features() )->for_user( $this->user_with_role( 'administrator' ) );

		$this->assertArrayNotHasKey( 'both', $rep['features'] );
		$this->assertArrayHasKey( 'both', $admin['features'] );
	}

	public function test_a_callable_capability_gets_the_user_and_never_reaches_the_client() {
		$allowed = $this->user_with_role( 'sales_rep' );
		$denied  = $this->user_with_role( 'sales_rep' );

		add_action( 'groundhogg/mobile/register_features', function ( Features $features ) use ( $allowed ) {
			$features->register( 'dynamic', [
				'cap' => function ( int $user_id ) use ( $allowed ) {
					return $user_id === $allowed;
				},
				'nav' => [ [ 'id' => 'x', 'route' => '/x', 'cap' => function () {
					return true;
				} ] ],
			] );
		} );

		$yes = ( new Features() )->for_user( $allowed );
		$no  = ( new Features() )->for_user( $denied );

		$this->assertArrayHasKey( 'dynamic', $yes['features'] );
		$this->assertArrayNotHasKey( 'dynamic', $no['features'] );

		// the contribution's own `cap` isn't sent, and the JSON can be encoded (a closure can't)
		$this->assertArrayNotHasKey( 'cap', $yes['features']['dynamic']['nav'][0] );
		$this->assertNotFalse( wp_json_encode( $yes ) );
	}

	public function test_a_feature_needing_a_newer_api_than_core_has_is_dropped() {
		add_action( 'groundhogg/mobile/register_features', function ( Features $features ) {
			$features->register( 'future', [ 'min_api_version' => Features::API_VERSION + 1, 'nav' => [ [ 'id' => 'x', 'route' => '/x' ] ] ] );
			$features->register( 'current', [ 'min_api_version' => Features::API_VERSION, 'nav' => [ [ 'id' => 'y', 'route' => '/y' ] ] ] );
		} );

		$visible = ( new Features() )->for_user( $this->user_with_role( 'administrator' ) );

		$this->assertArrayNotHasKey( 'future', $visible['features'] );
		$this->assertArrayHasKey( 'current', $visible['features'] );
	}

	public function test_contributions_sort_by_priority_then_id_and_unknown_keys_are_stripped() {
		add_action( 'groundhogg/mobile/register_features', function ( Features $features ) {
			$features->register( 'cards', [
				'home_cards' => [
					[ 'id' => 'b', 'priority' => 20, 'secret' => 'nope' ],
					[ 'id' => 'a', 'priority' => 20 ],
					[ 'id' => 'c', 'priority' => 5 ],
				],
			] );
		} );

		$visible = ( new Features() )->for_user( $this->user_with_role( 'administrator' ) );

		$this->assertSame( [ 'c', 'a', 'b' ], array_column( $visible['features']['cards']['home_cards'], 'id' ) );
		$this->assertStringNotContainsString( 'nope', wp_json_encode( $visible ) );
	}

	public function test_nav_placement_defaults_to_more_and_only_accepts_tab() {
		add_action( 'groundhogg/mobile/register_features', function ( Features $features ) {
			$features->register( 'p', [ 'nav' => [
				[ 'id' => 'one', 'route' => '/one' ],
				[ 'id' => 'two', 'route' => '/two', 'placement' => 'tab' ],
				[ 'id' => 'three', 'route' => '/three', 'placement' => 'banana' ],
			] ] );
		} );

		$nav = ( new Features() )->for_user( $this->user_with_role( 'administrator' ) )['features']['p']['nav'];

		// equal priority, so by id
		$this->assertSame( [ 'one' => 'more', 'three' => 'more', 'two' => 'tab' ], array_column( $nav, 'placement', 'id' ) );
	}

	/* ---- manifest ---------------------------------------------------------------------------------------- */

	public function test_manifest_carries_a_rest_nonce_and_the_users_role_shape() {
		$rep_id = $this->user_with_role( 'sales_rep' );
		wp_set_current_user( $rep_id );

		$manifest = Manifest::build();

		$this->assertSame( Features::API_VERSION, $manifest['apiVersion'] );
		$this->assertSame( $rep_id, $manifest['user']['id'] );
		$this->assertTrue( $manifest['user']['isRep'] );
		$this->assertFalse( $manifest['user']['isMgr'] );
		$this->assertNotEmpty( $manifest['rest']['nonce'] );
		$this->assertStringContainsString( 'gh/v4', $manifest['rest']['root'] );
		$this->assertSame( App::url(), $manifest['app']['url'] );
	}

	public function test_manifest_caps_are_booleans_for_what_is_asked_about_not_the_whole_capability_list() {
		wp_set_current_user( $this->user_with_role( 'sales_rep' ) );

		$caps = Manifest::build()['caps'];

		// a rep can view contacts and tasks but not other reps' contacts
		$this->assertTrue( $caps['view_contacts'] );
		$this->assertTrue( $caps['add_tasks'] );
		$this->assertFalse( $caps['view_others_contacts'] );

		foreach ( $caps as $value ) {
			$this->assertIsBool( $value );
		}

		$this->assertArrayNotHasKey( 'manage_options', $caps );
		$this->assertArrayNotHasKey( 'install_plugins', $caps );
	}

	/**
	 * The email sheet decides which options to offer from these.
	 */
	public function test_manifest_caps_say_whether_the_user_can_send_and_view_emails() {
		wp_set_current_user( $this->user_with_role( 'sales_rep' ) );

		$caps = Manifest::build()['caps'];

		$this->assertTrue( $caps['send_emails'] );
		$this->assertTrue( $caps['view_emails'] );

		wp_set_current_user( $this->user_with_role( 'subscriber' ) );

		$this->assertFalse( Manifest::build()['caps']['send_emails'] );
	}

	public function test_manifest_caps_include_those_mentioned_by_visible_registrations() {
		add_action( 'groundhogg/mobile/register_features', function ( Features $features ) {
			$features->register( 'pipeline', [ 'cap' => 'view_contacts', 'quick_create' => [ [ 'id' => 'new-deal', 'cap' => 'edit_funnels' ] ], 'nav' => [ [ 'id' => 'd', 'route' => '/d' ] ] ] );
		} );

		wp_set_current_user( $this->user_with_role( 'administrator' ) );

		$caps = Manifest::build()['caps'];

		$this->assertArrayHasKey( 'edit_funnels', $caps );
		$this->assertTrue( $caps['edit_funnels'] );
	}

	public function test_manifest_can_be_filtered() {
		add_filter( 'groundhogg/mobile/manifest', function ( $manifest ) {
			$manifest['custom'] = 'yes';

			return $manifest;
		} );

		wp_set_current_user( $this->user_with_role( 'administrator' ) );

		$this->assertSame( 'yes', Manifest::build()['custom'] );
	}

	/* ---- shell ------------------------------------------------------------------------------------------- */

	/**
	 * The manifest is inlined in the page, so a label can't be allowed to close the script block.
	 */
	public function test_the_shell_cannot_be_broken_out_of_through_the_manifest() {
		$manifest = [
			'app'      => [ 'name' => 'Groundhogg</script><script>alert(1)</script>', 'url' => App::url() ],
			'features' => [ 'core' => [ 'label' => '<!-- &amp; -->' ] ],
		];

		ob_start();
		include GROUNDHOGG_PATH . 'templates/mobile/shell.php';
		$html = ob_get_clean();

		$this->assertStringNotContainsString( '<script>alert(1)', $html );
		$this->assertSame( 1, preg_match( '#<script type="application/json" id="ghm-manifest">(.*?)</script>#s', $html, $m ) );

		// it still round trips
		$decoded = json_decode( $m[1], true );
		$this->assertSame( $manifest['app']['name'], $decoded['app']['name'] );
		$this->assertSame( $manifest['features']['core']['label'], $decoded['features']['core']['label'] );
	}

	/**
	 * The app is used on white labeled sites, so the labels it shows, and the links it offers, can't name the brand.
	 * The app's own name is the exception: it comes from the white label setting.
	 */
	public function test_labels_and_links_core_registers_do_not_name_the_brand() {
		$visible = ( new Features() )->for_user( $this->user_with_role( 'administrator' ) );
		$strings = [];

		array_walk_recursive( $visible['features'], function ( $value, $key ) use ( &$strings ) {
			if ( in_array( $key, [ 'label', 'href' ], true ) ) {
				$strings[] = (string) $value;
			}
		} );

		$this->assertNotEmpty( $strings );

		foreach ( $strings as $string ) {
			$this->assertStringNotContainsStringIgnoringCase( 'groundhogg', $string );
		}
	}

	public function test_the_app_name_is_never_blank() {
		$this->assertNotSame( '', App::name() );
	}

	public function test_the_app_lives_under_the_managed_page() {
		$this->assertStringEndsWith( '/app/', App::url() );
		$this->assertStringStartsWith( home_url(), App::url() );
	}
}
