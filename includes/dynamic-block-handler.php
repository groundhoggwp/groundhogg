<?php

namespace Groundhogg;

class Dynamic_Block_Handler {

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
			self::$instance = new Dynamic_Block_Handler();
		}

		return self::$instance;
	}

	/**
	 * Init callbacks
	 */
	public function __construct() {
		$this->register( 'posts', [ $this, 'posts' ], [ $this, 'posts_plain' ] );
	}

	/**
	 * Register a dynamic block callback
	 *
	 * @param string   $type
	 * @param callable $html_callback
	 * @param callable $plain_text_callback
	 *
	 * @return bool
	 */
	public function register( $type, $html_callback, $plain_text_callback ) {

		if ( ! $type || ! is_callable( $html_callback ) ) {
			return false;
		}

		$this->blocks[ $type ] = [
			'html'  => $html_callback,
			'plain' => $plain_text_callback
		];

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
		$query->set( 'tag__in', wp_parse_id_list( get_array_var( $this->cur_block, 'tag', [] ) ) );
		$query->set( 'category__in', wp_parse_id_list( get_array_var( $this->cur_block, 'category', [] ) ) );
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
	 * Replace the content in the email with dynamic content
	 *
	 * @param $content
	 * @param $blocks
	 *
	 * @return array|mixed|string|string[]
	 */
	public function replace_content( $content, $blocks, $context = 'html' ) {

		$blocks = array_filter( $blocks, function ( $block ) {
			return key_exists( 'type', $block );
		} );

		foreach ( $blocks as $block ) {

			if ( key_exists( get_array_var( $block, 'type' ), $this->blocks ) ) {
				if ( $context === 'html' ) {
					$content = str_replace( "<!-- {$block['type']}:{$block['id']} -->", $this->render_block( $block['type'], $block, $context ), $content );
				} else {
					$content = str_replace( "[[{$block['type']}:{$block['id']}]]", $this->render_block( $block['type'], $block, $context ), $content );
				}
			}

			if ( key_exists( 'columns', $block ) && is_array( $block['columns'] ) ) {
				foreach ( $block['columns'] as $column ) {
					$content = $this->replace_content( $content, $column, $context );
				}
			}
		}

		return $content;
	}

}
