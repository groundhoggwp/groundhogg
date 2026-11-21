<?php

namespace Groundhogg\Abilities\Utils;

use Groundhogg\Abilities\Ability;
use WP_Error;
use function Groundhogg\html2markdown;
use function Groundhogg\is_white_labeled;
use function Groundhogg\utm_url;

/**
 * Searches the documentation on groundhogg.io, and reads an article, so an agent can answer from the docs and link the
 * article rather than answer from memory or make up a link. The Help page searches the same docs.
 *
 * It's read from groundhogg.io's REST API: only the search words are sent, nothing from the site. Results are cached,
 * a day for searches and articles, a week for the list of categories.
 *
 * The docs are ours, so it isn't registered on a white labeled site.
 */
class Search_Docs extends Ability {

	protected const NAME       = 'groundhogg/search-docs';
	protected const CATEGORY   = 'groundhogg-utils';
	protected const CAPABILITY = 'view_contacts';

	protected const READONLY   = true;
	protected const IDEMPOTENT = true;

	const API = 'https://groundhogg.io/wp-json/wp/v2/';

	const DOCS_URL = 'https://groundhogg.io/documentation/';

	/**
	 * A link handed to the AI assistant, tagged so groundhogg.io can tell the visit came from an assistant.
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	protected static function link( string $url ): string {
		return utm_url( $url, '', 'search-docs', 'ai' );
	}

	/**
	 * Categories for people writing code. Their articles are left out of a search unless it asks for them.
	 */
	const DEVELOPER_CATEGORIES = [ 'action-hooks', 'filter-hooks', 'functions', 'classes', 'code-samples', 'developer-guides', 'rest-api' ];

	/**
	 * An article longer than this, in characters, is cut short
	 */
	const MAX_CONTENT = 30000;

	public function __construct() {

		if ( is_white_labeled() ) {
			return;
		}

		parent::__construct();
	}

	protected function get_args(): array {

		$article = [
			'type'       => 'object',
			'properties' => [
				'id'         => [ 'type' => 'integer' ],
				'title'      => [ 'type' => 'string' ],
				'url'        => [ 'type' => 'string' ],
				'categories' => [ 'type' => 'array', 'items' => [ 'type' => 'string' ] ],
				'updated'    => [ 'type' => 'string', 'description' => __( 'When the article was last updated, Y-m-d.', 'groundhogg' ) ],
			],
		];

		return [
			'label'       => __( 'Search Docs', 'groundhogg' ),
			'description' => __( 'Search the official documentation for an article on how to do something, then read it by id. Use it when someone asks how to do something, or when you tell them to do something themselves in the admin, so you can answer from the article and give them its link. Only link articles this returns, never make up a documentation link. Search with a few plain words about the task, like "cron job" or "import contacts". Don\'t put anyone\'s personal details in the search, it\'s sent to the documentation site.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [
					'search'    => [
						'type'        => 'string',
						'maxLength'   => 200,
						'description' => __( 'Words to look for. Give this or id.', 'groundhogg' ),
					],
					'id'        => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'An article\'s id, from a search, to read the whole article as Markdown.', 'groundhogg' ),
					],
					'developer' => [
						'type'        => 'boolean',
						'default'     => false,
						'description' => __( 'Also search the developer documentation: functions, hooks, classes and the REST API. Only when the question is about code.', 'groundhogg' ),
					],
					'limit'     => [
						'type'    => 'integer',
						'minimum' => 1,
						'maximum' => 10,
						'default' => 5,
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'results'   => [
						'type'        => 'array',
						'description' => __( 'The articles that match a search, best first.', 'groundhogg' ),
						'items'       => [
							'type'       => 'object',
							'properties' => $article['properties'] + [
									'excerpt' => [ 'type' => 'string' ],
								],
						],
					],
					'article'   => [
						'type'        => 'object',
						'description' => __( 'The article read by id.', 'groundhogg' ),
						'properties'  => $article['properties'] + [
								'content' => [ 'type' => 'string', 'description' => __( 'The article, in Markdown.', 'groundhogg' ) ],
							],
					],
					'all_docs'  => [
						'type'        => 'string',
						'description' => __( 'The documentation home page, to link when nothing matches.', 'groundhogg' ),
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$id     = absint( $input['id'] ?? 0 );
		$search = sanitize_text_field( $input['search'] ?? '' );

		if ( $id ) {
			return $this->article( $id );
		}

		if ( $search === '' ) {
			return new WP_Error( 'missing_search', __( 'Give `search`, some words to look for, or the `id` of an article to read.', 'groundhogg' ) );
		}

		return self::find( $search, ! empty( $input['developer'] ), absint( $input['limit'] ?? 5 ) ?: 5 );
	}

	/**
	 * Search the docs, for the ability or anywhere else that does, like the troubleshooter
	 *
	 * @param string $search
	 * @param bool   $developer whether to include the developer docs
	 * @param int    $limit
	 *
	 * @return array|WP_Error [ results => [ id, title, url, categories, updated, excerpt ][], all_docs => string ]
	 */
	public static function find( string $search, bool $developer = false, int $limit = 5 ) {

		$docs = self::fetch( 'doc', [
			'search'   => $search,
			'orderby'  => 'relevance',
			'per_page' => 20,
			'_fields'  => 'id,link,title,excerpt,modified,doc-category',
		], DAY_IN_SECONDS );

		if ( is_wp_error( $docs ) ) {
			return $docs;
		}

		$categories = self::categories();
		$results    = [];

		foreach ( $docs as $doc ) {

			$slugs = array_filter( array_map( function ( $id ) use ( $categories ) {
				return $categories[ $id ]['slug'] ?? '';
			}, (array) ( $doc['doc-category'] ?? [] ) ) );

			// only for developers when every category it's in is
			if ( ! $developer && $slugs && ! array_diff( $slugs, self::DEVELOPER_CATEGORIES ) ) {
				continue;
			}

			$results[] = self::describe( $doc, $categories ) + [
					'excerpt' => self::text( $doc['excerpt']['rendered'] ?? '' ),
				];

			if ( count( $results ) >= $limit ) {
				break;
			}
		}

		return [
			'results'  => $results,
			'all_docs' => self::link( self::DOCS_URL ),
		];
	}

	/**
	 * @param int $id
	 *
	 * @return array|WP_Error
	 */
	protected function article( int $id ) {

		$doc = self::fetch( 'doc/' . $id, [
			'_fields' => 'id,link,title,content,modified,doc-category',
		], DAY_IN_SECONDS );

		if ( is_wp_error( $doc ) ) {
			return $doc;
		}

		$content = self::markdown( $doc['content']['rendered'] ?? '' );

		if ( strlen( $content ) > self::MAX_CONTENT ) {
			$content = substr( $content, 0, self::MAX_CONTENT ) . "\n\n" . __( '[The article is cut short here. Link it for the rest.]', 'groundhogg' );
		}

		return [
			'article'  => self::describe( $doc, self::categories() ) + [
					'content' => $content,
				],
			'all_docs' => self::link( self::DOCS_URL ),
		];
	}

	/**
	 * What every result has: id, title, url, categories and when it was updated
	 *
	 * @param array $doc
	 * @param array $categories id => [ name, slug ]
	 *
	 * @return array
	 */
	protected static function describe( array $doc, array $categories ): array {

		$url = (string) ( $doc['link'] ?? '' );

		return [
			'id'         => absint( $doc['id'] ?? 0 ),
			'title'      => self::text( $doc['title']['rendered'] ?? '' ),
			// only ever a link to the docs
			'url'        => self::link( strpos( $url, 'https://groundhogg.io/' ) === 0 ? esc_url_raw( $url ) : self::DOCS_URL ),
			'categories' => array_values( array_filter( array_map( function ( $id ) use ( $categories ) {
				return $categories[ $id ]['name'] ?? '';
			}, (array) ( $doc['doc-category'] ?? [] ) ) ) ),
			'updated'    => substr( (string) ( $doc['modified'] ?? '' ), 0, 10 ),
		];
	}

	/**
	 * The doc categories by id, or none if they can't be fetched, in which case nothing is left out of a search
	 *
	 * @return array id => [ name, slug ]
	 */
	protected static function categories(): array {

		$terms = self::fetch( 'doc-category', [
			'per_page' => 100,
			'_fields'  => 'id,name,slug',
		], WEEK_IN_SECONDS );

		if ( is_wp_error( $terms ) ) {
			return [];
		}

		$categories = [];

		foreach ( $terms as $term ) {
			$categories[ absint( $term['id'] ?? 0 ) ] = [
				'name' => self::text( $term['name'] ?? '' ),
				'slug' => sanitize_key( $term['slug'] ?? '' ),
			];
		}

		return $categories;
	}

	/**
	 * An article as Markdown. html2markdown() leaves code, figures and entities as they are, so code becomes backticks
	 * and fences first, and whatever HTML is left after is taken out.
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	protected static function markdown( string $html ): string {

		$decode = function ( $code ) {
			return html_entity_decode( wp_strip_all_tags( $code ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		};

		// code blocks, kept out of html2markdown() so it doesn't touch what's in them
		$blocks = [];

		$html = preg_replace_callback( '#<pre[^>]*>(.*?)</pre>#is', function ( $match ) use ( &$blocks, $decode ) {
			$blocks[] = "```\n" . trim( $decode( preg_replace( '#<br\s*/?>#i', "\n", $match[1] ) ) ) . "\n```";

			return "\n\nGHCODEBLOCK" . ( count( $blocks ) - 1 ) . "\n\n";
		}, $html );

		$html = preg_replace_callback( '#<code[^>]*>(.*?)</code>#is', function ( $match ) use ( $decode ) {
			return '`' . str_replace( [ '<', '>' ], [ '&lt;', '&gt;' ], $decode( $match[1] ) ) . '`';
		}, $html );

		$markdown = html2markdown( $html );

		// tags it doesn't convert, like figures, divs and iframes
		$markdown = preg_replace( '#</?[a-z][a-z0-9-]*(\s[^<>]*)?/?>#i', '', $markdown );
		$markdown = html_entity_decode( $markdown, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		// an image was in a figure, a block of its own, so what follows it starts a new one
		$markdown = preg_replace( '/(!\[[^\]]*\]\([^)\s]*\))(?=\S)/', "$1\n\n", $markdown );

		foreach ( $blocks as $i => $block ) {
			$markdown = str_replace( 'GHCODEBLOCK' . $i, $block, $markdown );
		}

		return trim( preg_replace( "/\n{3,}/", "\n\n", $markdown ) );
	}

	/**
	 * Plain text from a rendered title or excerpt
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	protected static function text( string $html ): string {
		return trim( preg_replace( '/\s+/', ' ', html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
	}

	/**
	 * GET from the docs' REST API, cached. Only the query is sent, not the site's address.
	 *
	 * @param string $path
	 * @param array  $query
	 * @param int    $ttl
	 *
	 * @return array|WP_Error
	 */
	protected static function fetch( string $path, array $query, int $ttl ) {

		$url = add_query_arg( array_map( 'rawurlencode', $query ), self::API . $path );
		$key = 'gh_docs_' . md5( $url );

		$cached = get_transient( $key );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$response = wp_remote_get( $url, [
			'timeout'    => 15,
			'user-agent' => 'Groundhogg/' . GROUNDHOGG_VERSION,
		] );

		$unavailable = new WP_Error( 'docs_unavailable', sprintf(
		/* translators: %s: the documentation's home page */
			__( 'The documentation couldn\'t be reached just now. Link %s instead, and don\'t make up a link to an article.', 'groundhogg' ),
			self::link( self::DOCS_URL )
		) );

		if ( is_wp_error( $response ) ) {
			return $unavailable;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$json = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code === 404 ) {
			return new WP_Error( 'not_found', __( 'There is no article with that id. Search for it instead.', 'groundhogg' ) );
		}

		if ( $code !== 200 || ! is_array( $json ) ) {
			return $unavailable;
		}

		set_transient( $key, $json, $ttl );

		return $json;
	}
}
