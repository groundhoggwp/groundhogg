<?php

use Groundhogg\License_Manager;
use function Groundhogg\utm_campaign_for_screen;
use function Groundhogg\utm_url;

/**
 * UTM tags on links to groundhogg.io, see docs/utm-links.md
 */
class Utm_Links_Tests extends GH_UnitTestCase {

	protected $saved_get;
	protected $saved_pagenow;

	public function setUp(): void {
		parent::setUp();

		global $pagenow;

		$this->saved_get     = [ $_GET, $_REQUEST ];
		$this->saved_pagenow = $pagenow;
	}

	public function tearDown(): void {
		global $pagenow;

		[ $_GET, $_REQUEST ] = $this->saved_get;
		$pagenow = $this->saved_pagenow;

		set_current_screen( 'front' );
		remove_all_filters( 'groundhogg/utm_params' );

		parent::tearDown();
	}

	protected function query( string $url ): array {
		wp_parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );

		return $query;
	}

	public function test_other_hosts_are_left_alone() {
		foreach ( [ 'https://mailhawk.io/pricing/', 'https://evilgroundhogg.io/', 'https://groundhogg.io.example.com/', 'mailto:support@groundhogg.io' ] as $url ) {
			$this->assertSame( $url, utm_url( $url, 'x', 'y' ) );
		}
	}

	public function test_groundhogg_io_gets_the_scheme() {
		$query = $this->query( utm_url( 'https://groundhogg.io/pricing/', 'Upgrade Button', 'funnel-editor' ) );

		$this->assertSame( [
			'utm_source'   => 'groundhogg-plugin',
			'utm_medium'   => 'wp-admin',
			'utm_campaign' => 'funnel-editor',
			'utm_content'  => 'upgrade-button',
		], $query );
	}

	public function test_subdomains_and_fragments() {
		$url = utm_url( 'https://help.groundhogg.io/doc/x/?a=1#section', '', 'help' );

		$this->assertStringStartsWith( 'https://help.groundhogg.io/doc/x/?', $url );
		$this->assertStringEndsWith( '#section', $url );
		$this->assertSame( '1', $this->query( $url )['a'] );
		$this->assertSame( 'help', $this->query( $url )['utm_campaign'] );
		$this->assertArrayNotHasKey( 'utm_content', $this->query( $url ) );
	}

	public function test_someone_elses_scheme_is_kept() {
		$url = 'https://groundhogg.io/pricing/?utm_source=email&utm_campaign=email-affiliate';

		$this->assertSame( $url, utm_url( $url, 'x', 'y' ) );
	}

	public function test_legacy_add_on_header_links_are_retagged() {
		global $pagenow;

		set_current_screen( 'plugins' );
		$this->request( [] );
		$pagenow = 'plugins.php';

		$url = utm_url( 'https://www.groundhogg.io/downloads/woocommerce/?utm_source=wp-plugins&utm_campaign=plugin-uri&utm_medium=wp-dash&aff=3' );

		$this->assertStringStartsWith( 'https://www.groundhogg.io/downloads/woocommerce/?', $url );
		$this->assertSame( [
			'aff'          => '3',
			'utm_source'   => 'groundhogg-plugin',
			'utm_medium'   => 'wp-admin',
			'utm_campaign' => 'plugins',
			'utm_content'  => 'plugin-uri',
		], $this->query( $url ) );
	}

	public function test_legacy_links_keep_an_explicit_content() {
		$query = $this->query( utm_url( 'https://groundhogg.io/pricing/?utm_source=plugin&utm_medium=button&utm_campaign=troubleshooter&utm_content=license', 'support-license', 'help' ) );

		$this->assertSame( 'groundhogg-plugin', $query['utm_source'] );
		$this->assertSame( 'wp-admin', $query['utm_medium'] );
		$this->assertSame( 'help', $query['utm_campaign'] );
		$this->assertSame( 'support-license', $query['utm_content'] );
	}

	public function test_own_params_are_kept_and_missing_ones_filled() {
		$query = $this->query( utm_url( 'https://groundhogg.io/?utm_source=groundhogg-plugin&utm_content=keep', 'replace', 'campaign' ) );

		$this->assertSame( 'keep', $query['utm_content'] );
		$this->assertSame( 'campaign', $query['utm_campaign'] );
		$this->assertSame( 'wp-admin', $query['utm_medium'] );
	}

	public function test_medium() {
		$this->assertSame( 'ai', $this->query( utm_url( 'https://groundhogg.io/documentation/', '', 'search-docs', 'ai' ) )['utm_medium'] );
	}

	/**
	 * Like a real GET request, where $_REQUEST holds the query vars too
	 */
	protected function request( array $vars ) {
		$_GET     = $vars;
		$_REQUEST = $vars;
	}

	public function test_campaign_from_groundhogg_screens() {
		set_current_screen( 'dashboard' );

		$this->request( [ 'page' => 'gh_funnels', 'action' => 'edit', 'funnel' => '12' ] );
		$this->assertSame( 'funnels-edit', utm_campaign_for_screen() );

		$this->request( [ 'page' => 'gh_help', 'tab' => 'troubleshooter' ] );
		$this->assertSame( 'help-troubleshooter', utm_campaign_for_screen() );

		$this->request( [ 'page' => 'gh_guided_setup' ] );
		$this->assertSame( 'guided-setup', utm_campaign_for_screen() );

		$this->request( [ 'page' => 'groundhogg' ] );
		$this->assertSame( 'dashboard', utm_campaign_for_screen() );

		$this->assertSame( 'dashboard', $this->query( utm_url( 'https://groundhogg.io/' ) )['utm_campaign'] );
	}

	public function test_campaign_never_names_other_plugins_pages() {
		global $pagenow;

		set_current_screen( 'dashboard' );

		$this->request( [ 'page' => 'some-other-plugin' ] );
		$pagenow = 'admin.php';
		$this->assertSame( 'other-screen', utm_campaign_for_screen() );

		$this->request( [] );
		$pagenow = 'plugins.php';
		$this->assertSame( 'plugins', utm_campaign_for_screen() );
	}

	public function test_no_campaign_outside_admin() {
		$this->assertSame( '', utm_campaign_for_screen() );
		$this->assertArrayNotHasKey( 'utm_campaign', $this->query( utm_url( 'https://groundhogg.io/' ) ) );
	}

	public function test_filter_can_turn_it_off() {
		add_filter( 'groundhogg/utm_params', '__return_empty_array' );

		$this->assertSame( 'https://groundhogg.io/pricing/', utm_url( 'https://groundhogg.io/pricing/', 'x', 'y' ) );
	}

	public function test_links_in_html() {
		$html = '<p><a href="https://groundhogg.io/doc/sender-profiles/">Docs</a> <a class="x" href=\'https://wordpress.org/plugins/groundhogg/#developers\'>wp.org</a></p>'
		        . '<img src="https://groundhogg.io/wp-content/uploads/a.png">'
		        . '<a href="https://groundhogg.io/?a=1&amp;b=2">Home</a>';

		$tagged = \Groundhogg\utm_links_in_html( $html, 'notice-745', 'notifications' );

		preg_match_all( '/href=["\']([^"\']+)/', $tagged, $hrefs );
		$hrefs = array_map( 'html_entity_decode', $hrefs[1] );

		$this->assertSame( [
			'utm_source'   => 'groundhogg-plugin',
			'utm_medium'   => 'wp-admin',
			'utm_campaign' => 'notifications',
			'utm_content'  => 'notice-745',
		], $this->query( $hrefs[0] ) );

		$this->assertSame( 'https://wordpress.org/plugins/groundhogg/#developers', $hrefs[1] );
		$this->assertSame( '2', $this->query( $hrefs[2] )['b'] );
		$this->assertSame( 'notice-745', $this->query( $hrefs[2] )['utm_content'] );
		$this->assertStringContainsString( '<img src="https://groundhogg.io/wp-content/uploads/a.png">', $tagged );
		$this->assertSame( 'no links here', \Groundhogg\utm_links_in_html( 'no links here' ) );
	}

	public function test_license_manager_urls() {
		$query = $this->query( License_Manager::groundhogg_io_url( 'pricing/', [ 'utm_content' => 'missing-license' ] ) );

		$this->assertSame( 'groundhogg-plugin', $query['utm_source'] );
		$this->assertSame( 'missing-license', $query['utm_content'] );
	}
}
