<?php

use function Groundhogg\tracking;

/**
 * The tracking cookie is host-only, so a tracking link opened on example.com that redirects to
 * www.example.com (or the reverse) loses the cookie and the visitor can't be identified on the
 * destination. Tracking::match_request_host() keeps the visitor on the host they arrived on, but
 * only ever swaps the www/non-www twin of that host.
 *
 * @see Tracking::match_request_host()
 */
class Tracking_Redirect_Host_Tests extends GH_UnitTestCase {

	protected $original_host;

	public function setUp(): void {
		parent::setUp();
		$this->original_host = $_SERVER['HTTP_HOST'] ?? null;
	}

	public function tearDown(): void {

		if ( $this->original_host === null ) {
			unset( $_SERVER['HTTP_HOST'] );
		} else {
			$_SERVER['HTTP_HOST'] = $this->original_host;
		}

		parent::tearDown();
	}

	protected function match( $request_host, $url ) {
		$_SERVER['HTTP_HOST'] = $request_host;

		return tracking()->match_request_host( $url );
	}

	public function test_apex_target_moves_to_www_request_host() {
		$this->assertSame(
			'https://www.example.com/gh/click/935-career-advancement/',
			$this->match( 'www.example.com', 'https://example.com/gh/click/935-career-advancement/' )
		);
	}

	public function test_www_target_moves_to_apex_request_host() {
		$this->assertSame(
			'https://example.com/gh/click/935-career-advancement/',
			$this->match( 'example.com', 'https://www.example.com/gh/click/935-career-advancement/' )
		);
	}

	public function test_matching_host_is_untouched() {
		$url = 'https://example.com/thank-you/';
		$this->assertSame( $url, $this->match( 'example.com', $url ) );
	}

	public function test_host_comparison_ignores_case() {
		$url = 'https://Example.com/thank-you/';
		$this->assertSame( $url, $this->match( 'EXAMPLE.com', $url ) );
	}

	public function test_path_query_fragment_and_port_are_preserved() {
		$this->assertSame(
			'https://www.example.com:8443/a/b/?x=1&y=2#frag',
			$this->match( 'www.example.com', 'https://example.com:8443/a/b/?x=1&y=2#frag' )
		);
	}

	public function test_request_host_with_port_still_matches() {
		$this->assertSame(
			'https://www.example.com/page/',
			$this->match( 'www.example.com:8080', 'https://example.com/page/' )
		);
	}

	public function test_scheme_relative_url_is_handled() {
		$this->assertSame(
			'//www.example.com/page/',
			$this->match( 'www.example.com', '//example.com/page/' )
		);
	}

	public function test_relative_url_is_untouched() {
		$this->assertSame( '/thank-you/', $this->match( 'www.example.com', '/thank-you/' ) );
	}

	public function test_external_host_is_untouched() {
		$url = 'https://other.com/page/';
		$this->assertSame( $url, $this->match( 'www.example.com', $url ) );
	}

	public function test_other_subdomains_are_untouched() {
		$url = 'https://app.example.com/page/';
		$this->assertSame( $url, $this->match( 'www.example.com', $url ) );
		$this->assertSame( $url, $this->match( 'example.com', $url ) );
	}

	public function test_lookalike_host_is_untouched() {
		$url = 'https://example.com.evil.test/page/';
		$this->assertSame( $url, $this->match( 'www.example.com', $url ) );
	}

	public function test_a_foreign_request_host_cannot_pull_the_target_off_site() {
		$url = 'https://example.com/page/';
		$this->assertSame( $url, $this->match( 'evil.test', $url ) );
	}

	public function test_url_with_credentials_is_untouched() {
		$url = 'https://user:pass@example.com/page/';
		$this->assertSame( $url, $this->match( 'www.example.com', $url ) );
	}

	public function test_missing_request_host_leaves_url_untouched() {
		$url = 'https://example.com/page/';
		$this->assertSame( $url, $this->match( '', $url ) );
	}

	public function test_filter_can_turn_matching_off() {
		$url = 'https://example.com/page/';

		add_filter( 'groundhogg/tracking/match_request_host', '__return_false' );

		$this->assertSame( $url, $this->match( 'www.example.com', $url ) );

		remove_filter( 'groundhogg/tracking/match_request_host', '__return_false' );
	}
}
