<?php

use Groundhogg\Abilities\Utils\Search_Docs;

/**
 * groundhogg/search-docs: searching the documentation on groundhogg.io and reading an article, with groundhogg.io's
 * REST API answered here instead.
 */
class Search_Docs_Tests extends GH_UnitTestCase {

	/**
	 * @var string[] the URLs requested
	 */
	protected $requests = [];

	/**
	 * @var array the user agent of each request
	 */
	protected $agents = [];

	/**
	 * @var callable|null answers a request to the docs instead of the default
	 */
	protected $answer;

	public function set_up() {
		parent::set_up();

		if ( ! function_exists( 'wp_get_ability' ) || ! wp_get_ability( 'groundhogg/search-docs' ) ) {
			$this->markTestSkipped( 'Needs WordPress 6.9 for the Abilities API.' );
		}

		$this->requests = [];
		$this->agents   = [];
		$this->answer   = null;

		add_filter( 'pre_http_request', [ $this, 'fake_docs' ], 10, 3 );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	public function tear_down() {
		remove_filter( 'pre_http_request', [ $this, 'fake_docs' ], 10 );
		parent::tear_down();
	}

	protected static function response( $body, int $code = 200 ): array {
		return [
			'headers'  => [],
			'body'     => wp_json_encode( $body ),
			'response' => [ 'code' => $code, 'message' => '' ],
			'cookies'  => [],
		];
	}

	public function fake_docs( $pre, $args, $url ) {

		if ( strpos( $url, Search_Docs::API ) !== 0 ) {
			return $pre;
		}

		$this->requests[] = $url;
		$this->agents[]   = $args['user-agent'] ?? '';

		if ( $this->answer ) {
			return call_user_func( $this->answer, $url );
		}

		$path = strtok( substr( $url, strlen( Search_Docs::API ) ), '?' );

		if ( $path === 'doc-category' ) {
			return self::response( [
				[ 'id' => 1, 'name' => 'Getting Started', 'slug' => 'getting-started' ],
				[ 'id' => 2, 'name' => 'Troubleshooting', 'slug' => 'troubleshooting' ],
				[ 'id' => 3, 'name' => 'Functions', 'slug' => 'functions' ],
			] );
		}

		if ( $path === 'doc' ) {
			return self::response( [
				[
					'id'           => 10,
					'link'         => 'https://groundhogg.io/doc/enqueue_event/',
					'title'        => [ 'rendered' => 'enqueue_event' ],
					'excerpt'      => [ 'rendered' => '<p>Adds an event to the queue.</p>' ],
					'modified'     => '2026-06-09T13:25:03',
					'doc-category' => [ 3 ],
				],
				[
					'id'           => 11,
					'link'         => 'https://groundhogg.io/doc/cron-jobs/',
					'title'        => [ 'rendered' => 'Cron Jobs &amp; the Event Queue' ],
					'excerpt'      => [ 'rendered' => '<p>How to set up a <strong>real</strong> cron job &hellip;</p>' ],
					'modified'     => '2026-09-12T11:36:36',
					'doc-category' => [ 1, 2 ],
				],
				[
					'id'           => 12,
					'link'         => 'https://elsewhere.example/doc/',
					'title'        => [ 'rendered' => 'Somewhere else' ],
					'excerpt'      => [ 'rendered' => '' ],
					'modified'     => '2026-01-01T00:00:00',
					'doc-category' => [],
				],
			] );
		}

		if ( $path === 'doc/11' ) {
			return self::response( [
				'id'           => 11,
				'link'         => 'https://groundhogg.io/doc/cron-jobs/',
				'title'        => [ 'rendered' => 'Cron Jobs' ],
				'content'      => [ 'rendered' => '<h2>Setting it up</h2><p>Open your <a href="https://example.com/host">hosting panel</a> &raquo; <em>Cron</em>.</p><figure class="wp-block-image"><img src="https://groundhogg.io/shot.png" alt="" /></figure><p>Add this to <code>wp-config.php</code>:</p><pre class="wp-block-preformatted">&lt;?php define(\'DISABLE_WP_CRON\', true);</pre><ul><li>Add the job</li></ul>' ],
				'modified'     => '2026-09-12T11:36:36',
				'doc-category' => [ 1 ],
			] );
		}

		return self::response( [ 'code' => 'rest_post_invalid_id', 'message' => 'Invalid post ID.' ], 404 );
	}

	protected function run_ability( array $input ) {
		return wp_get_ability( 'groundhogg/search-docs' )->execute( $input );
	}

	public function test_search_leaves_out_developer_docs_and_cleans_up_the_results() {

		$result = $this->run_ability( [ 'search' => 'cron job' ] );

		$this->assertNotWPError( $result );
		$this->assertSame( Search_Docs::DOCS_URL, $result['all_docs'] );
		$this->assertSame( [ 11, 12 ], wp_list_pluck( $result['results'], 'id' ) );

		$cron = $result['results'][0];

		$this->assertSame( 'Cron Jobs & the Event Queue', $cron['title'] );
		$this->assertSame( 'https://groundhogg.io/doc/cron-jobs/', $cron['url'] );
		$this->assertSame( [ 'Getting Started', 'Troubleshooting' ], $cron['categories'] );
		$this->assertSame( '2026-09-12', $cron['updated'] );
		$this->assertSame( 'How to set up a real cron job …', $cron['excerpt'] );

		// a link anywhere but the docs isn't passed on
		$this->assertSame( Search_Docs::DOCS_URL, $result['results'][1]['url'] );

		$this->assertStringContainsString( 'search=cron%20job', $this->requests[0] );
		$this->assertStringContainsString( 'orderby=relevance', $this->requests[0] );
	}

	/**
	 * The troubleshooter searches the docs the same way, see Help_Page::get_docs_ajax()
	 */
	public function test_the_troubleshooter_gets_the_same_results() {

		$this->assertSame( $this->run_ability( [ 'search' => 'cron job' ] ), Search_Docs::find( 'cron job' ) );
	}

	public function test_developer_docs_when_asked_and_the_limit() {

		$result = $this->run_ability( [ 'search' => 'event', 'developer' => true, 'limit' => 1 ] );

		$this->assertSame( [ 10 ], wp_list_pluck( $result['results'], 'id' ) );
	}

	public function test_reading_an_article_gives_markdown() {

		$result = $this->run_ability( [ 'id' => 11 ] );

		$this->assertNotWPError( $result );

		$article = $result['article'];

		$this->assertSame( 'Cron Jobs', $article['title'] );
		$this->assertStringContainsString( 'Setting it up', $article['content'] );
		$this->assertStringContainsString( '[hosting panel](https://example.com/host) » ', $article['content'] );
		$this->assertStringContainsString( '`wp-config.php`', $article['content'] );
		$this->assertStringContainsString( "```\n<?php define('DISABLE_WP_CRON', true);\n```", $article['content'] );
		$this->assertStringNotContainsString( '<figure', $article['content'] );
		$this->assertStringContainsString( "(https://groundhogg.io/shot.png)\n\n", $article['content'] );
		$this->assertStringNotContainsString( '&', $article['content'] );
	}

	public function test_an_article_that_doesnt_exist() {
		$this->assertWPError( $this->run_ability( [ 'id' => 999 ] ) );
		$this->assertSame( 'not_found', $this->run_ability( [ 'id' => 999 ] )->get_error_code() );
	}

	public function test_nothing_found_is_not_an_error() {

		$this->answer = function () {
			return self::response( [] );
		};

		$result = $this->run_ability( [ 'search' => 'nothing like this' ] );

		$this->assertNotWPError( $result );
		$this->assertSame( [], $result['results'] );
	}

	public function test_when_the_docs_cant_be_reached_it_says_to_link_the_home_page() {

		$this->answer = function () {
			return new WP_Error( 'http_request_failed', 'Timed out' );
		};

		$result = $this->run_ability( [ 'search' => 'cron' ] );

		$this->assertWPError( $result );
		$this->assertSame( 'docs_unavailable', $result->get_error_code() );
		$this->assertStringContainsString( Search_Docs::DOCS_URL, $result->get_error_message() );
	}

	public function test_it_needs_a_search_or_an_id() {
		$this->assertSame( 'missing_search', $this->run_ability( [] )->get_error_code() );
	}

	public function test_results_are_cached_and_the_site_isnt_sent() {

		$this->run_ability( [ 'search' => 'cron' ] );
		$count = count( $this->requests );

		$this->run_ability( [ 'search' => 'cron' ] );

		$this->assertSame( $count, count( $this->requests ) );

		foreach ( $this->agents as $agent ) {
			$this->assertStringNotContainsString( wp_parse_url( home_url(), PHP_URL_HOST ), $agent );
		}
	}

	public function test_anyone_who_can_view_contacts_can_search() {

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'sales_rep' ] ) );
		$this->assertNotWPError( $this->run_ability( [ 'search' => 'cron' ] ) );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );
		$this->assertWPError( $this->run_ability( [ 'search' => 'cron' ] ) );
	}
}
