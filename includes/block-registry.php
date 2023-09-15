<?php

namespace Groundhogg;

/**
 * Maps a function to specific keys
 *
 * @param $array    array
 * @param $keys     mixed
 * @param $callback callable
 */
function map_to_keys( $array, $keys, $callback ) {
	$keys = wp_parse_list( $keys );

	foreach ( $keys as $key ) {

		if ( ! isset( $array[ $key ] ) ) {
			continue;
		}

		$array[ $key ] = call_user_func( $callback, $array[ $key ] );
	}

	return $array;
}

class Block_Registry {

	/**
	 * @var array Store the dyanmic block handlers
	 */
	protected $blocks = [];
	protected $cur_block = [];

	// Hold the class instance.
	private static $instance = null;

	// The object is created from within the class itself
	// only if the class has no instance.
	public static function instance() {
		if ( self::$instance == null ) {
			self::$instance = new Block_Registry();
		}

		return self::$instance;
	}

	public function __return_block( $block ) {
		return $block;
	}

	/**
	 * Init callbacks
	 */
	public function __construct() {

		$this->register( 'posts', [
			'html'  => [ $this, 'posts' ],
			'plain' => [ $this, 'posts_plain' ],
		] );
	}

	/**
	 * Whether the given type is registered
	 *
	 * @param $type
	 *
	 * @return bool
	 */
	public function is_registered( $type ) {
		return key_exists( $type, $this->blocks );
	}

	/**
	 * Whether a block type is dynamic
	 *
	 * @param $type
	 *
	 * @return bool
	 */
	public function is_dyanamic( $type ) {
		return $this->is_registered( $type ) && $this->blocks[ $type ]['dynamic'];
	}

	/**
	 * Register a dynamic block callback
	 *
	 * @param string $type
	 * @param array args
	 *
	 * @return bool
	 */
	public function register( $type, $args ) {

		if ( ! $type ) {
			return false;
		}

		$args = wp_parse_args( $args, [
			'html'  => '__return_empty_string',
			'plain' => '__return_empty_string',
		] );

		$this->blocks[ $type ] = $args;

		return true;
	}

	/**
	 * @param        $type
	 * @param        $props
	 * @param string $context
	 *
	 * @return false|mixed
	 */
	public function render_block( $type, $props, $context = 'html' ) {
		$this->cur_block = $props;

		return call_user_func( $this->blocks[ $type ][ $context ], $props );
	}

	/**
	 * Filter the query with the terms ids
	 *
	 * @param $query \WP_Query
	 */
	public function post_query_filter( &$query ) {

		$args = wp_parse_args( $this->cur_block, [
			'tag'          => [],
			'tag_rel'      => 'any',
			'category'     => [],
			'category_rel' => 'any',
		] );

		$query->set( $args['tag_rel'] === 'all' ? 'tag__and' : 'tag__in', wp_parse_id_list( $args['tag'] ) );
		$query->set( $args['category_rel'] === 'all' ? 'category__and' : 'category__in', wp_parse_id_list( $args['category'] ) );
	}

	/**
	 * handle posts dynamic block
	 *
	 * @param $props
	 *
	 * @return string
	 */
	public function posts( $props ) {

		add_action( 'pre_get_posts', [ $this, 'post_query_filter' ] );

		// Handled by the query filter above
		unset( $props['tag'] );
		unset( $props['category'] );

		$posts = replacements()->posts( $props );

		remove_action( 'pre_get_posts', [ $this, 'post_query_filter' ] );

		return $posts;
	}

	/**
	 * handle posts dynamic block
	 *
	 * @param $props
	 *
	 * @return string
	 */
	public function posts_plain( $props ) {
		return $this->posts( array_merge( $props, [
			'layout' => 'plain'
		] ) );
	}

	/**
	 * Given content, parse the blocks
	 *
	 * @param        $content
	 *
	 * @return array|false
	 */
	public function parse_blocks( $content ) {

		$found = preg_match_all( "/<!-- (?<type>[a-z]+):(?<id>[A-Za-z0-9-]+) (?<json>{(?:.(?! -->))*}) -->/", $content, $matches );

		if ( ! $found ) {
			return false;
		}

		$blocks = [];

		foreach ( $matches['type'] as $i => $type ) {

			$id    = $matches['id'][ $i ];
			$json  = $matches['json'][ $i ];
			$props = json_decode( $json, true );

			$blocks[] = array_merge( [
				'id'   => $id,
				'type' => $type,
			], $props );
		}

		return $blocks;
	}

	/**
	 * Replace a block with the given content
	 *
	 * @param $block
	 * @param $replace
	 * @param $content
	 *
	 * @return array|string|string[]|null
	 */
	public function replace_block( $block, $replace, $content ) {
		$search = "/<!-- {$block['type']}:{$block['id']} .*<-- \/{$block['type']}:{$block['id']} -->/s";

		return preg_replace( $search, $replace, $content );
	}

	/**
	 * Replace the content in the email with dynamic content
	 *
	 * @param string $content
	 * @param string $context
	 *
	 * @return string
	 */
	public function replace_dynamic_content( $content, $context = 'html' ) {

		$json_regex          = get_json_regex();
		$dynamic_blocks      = implode( '|', array_keys( $this->blocks ) );
		$dynamic_block_regex = '/' . $json_regex . '\\[(?\'type\'' . $dynamic_blocks . '):(?\'id\'[A-Za-z0-9-]+):dynamicContent (?\'attributes\'(?&json))\\/\\]/s';
		$found               = preg_match_all( $dynamic_block_regex, $content, $matches );

		if ( ! $found ) {
			return $content;
		}

		foreach ( $matches['type'] as $i => $type ) {

			$match = $matches[0][ $i ];
			$id    = $matches['id'][ $i ];
			$json  = $matches['attributes'][ $i ];
			$props = json_decode( $json, true );

			$block = array_merge( [
				'id'   => $id,
				'type' => $type,
			], $props );

			$content = str_replace( $match, $this->render_block( $block['type'], $block, $context ), $content );
		}

		return $content;
	}

}
