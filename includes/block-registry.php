<?php

namespace Groundhogg;

use WP_Query;

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

		$this->register( 'shortcode', [
			'html'  => [ $this, 'shortcode' ],
			'plain' => [ $this, 'shortcode_plain' ],
		] );

		$this->register( 'queryloop', [
			'html'  => [ $this, 'queryloop' ],
			'plain' => [ $this, 'queryloop' ],
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
	 * Register a dynamic block callback
	 *
	 * @param string $type
	 * @param array args
	 *
	 * @return bool
	 */
	public function register( string $type, array $args ) {

		$args = wp_parse_args( $args, [
			'html'  => '__return_empty_string',
			'plain' => '__return_empty_string',
		] );

		$this->blocks[ $type ] = $args;

		return true;
	}

	/**
	 * @param array  $block
	 * @param string $content
	 * @param string $context
	 *
	 * @return false|mixed
	 */
	public function render_block( array $block, string $content, string $context = 'html' ) {
		$this->cur_block = $block;

		return call_user_func( $this->blocks[ $block['type'] ][ $context ], $block, $content, $context );
	}

	/**
	 * Filter the query with the terms ids
	 *
	 * @param $query \WP_Query
	 */
	public function post_query_filter( &$query ) {

		$args = wp_parse_args( $this->cur_block, [

			// Deprecated
			'tag'          => [],
			'tag_rel'      => 'any',
			'category'     => [],
			'category_rel' => 'any',

			// Use theses
			'terms'        => [],
			'post_type'    => 'post',
			'offset'       => 0,
			'include'      => [],
			'exclude'      => [],
			'queryId' => ''
		] );

		[
			'post_type' => $post_type,
			'terms'     => $terms
		] = $args;

		$query->set( 'post_type', $post_type );
		$query->set( 'status', 'publish' );

		if ( $post_type === 'post' && ! empty( 'category' ) ) {
			$query->set( $args['category_rel'] === 'all' ? 'category__and' : 'category__in', wp_parse_id_list( $args['category'] ) );
		}

		if ( $post_type === 'post' && ! empty( 'tag' ) ) {
			$query->set( $args['tag_rel'] === 'all' ? 'tag__and' : 'tag__in', wp_parse_id_list( $args['tag'] ) );
		}

		$tax_query = [];

		$taxonomies = get_object_taxonomies( $post_type );

		foreach ( $taxonomies as $taxonomy ) {

			if ( ! isset_not_empty( $terms, $taxonomy ) ) {
				continue;
			}

			$tax_rel = get_array_var( $terms, "{$taxonomy}_rel", 'any' );
			$terms   = $terms[ $taxonomy ];

			// ANY
			if ( $tax_rel === 'any' ) {
				$tax_query[] = [
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => wp_parse_id_list( $terms ),
				];
			} // ALL
			else {
				foreach ( $terms as $term ) {
					$tax_query[] = [
						'taxonomy' => $taxonomy,
						'field'    => 'term_id',
						'terms'    => $term,
					];
				}
			}
		}

		if ( ! empty( $tax_query ) ) {
			$tax_query['relation'] = 'AND';
			$query->set( 'tax_query', $tax_query );
		}

		if ( ! empty( $args['include'] ) ) {
			$query->set( 'post__in', wp_parse_id_list( $args['include'] ) );
		}

		if ( ! empty( $args['exclude'] ) ) {
			$query->set( 'post__not_in', wp_parse_id_list( $args['exclude'] ) );
		}

		$query->set( 'offset', absint( $args['offset'] ) );

		// do query action so that the query can be modified
		if ( $args['queryId'] ) {
			$queryId = $args['queryId'];

			/**
			 * Allow filtering the query for the block content
			 *
			 * if you need to get the current contact within the hook, you can use the get_current_contact() function
			 *
			 * @param WP_Query $query the current query based on the ID
			 * @param array    $block the current block attributes
			 */
			do_action_ref_array( "groundhogg/block_query/{$queryId}", [
				&$query,
				$this->cur_block
			] );
		}

	}

	/**
	 * Given that block content is generally inside a TD, we have use this helper function to deal with that
	 *
	 * @param $new_content
	 * @param $td_wrapper
	 *
	 * @return string
	 */
	public static function replace_in_td( $new_content, $td_wrapper ) {

		if ( empty( $td_wrapper ) ) {
			return $new_content;
		}

		return preg_replace_callback(
			'/^(<td\b[^>]*>)(.*?)(<\/td>)$/i',
			function ( $matches ) use ( $new_content ) {
				return $matches[1] . $new_content . $matches[3];
			},
			trim( $td_wrapper )
		);
	}

	/**
	 * Extract content from within a <td></td>
	 *
	 * @param $td_wrapper
	 *
	 * @return string
	 */
	public static function get_content_from_td( $td_wrapper ) {
		return preg_replace_callback(
			'/^(<td\b[^>]*>)(.*?)(<\/td>)$/i',
			function ( $matches ) {
				return $matches[2];
			},
			trim( $td_wrapper )
		);
	}

	/**
	 * Do a query loop
	 *
	 * @param array  $props
	 * @param string $content
	 *
	 * @return string
	 */
	public function queryloop( array $props, string $content, string $context = 'html' ) {

		$props = wp_parse_args( $props, [
			'orderby' => 'date',
			'order'   => 'DESC',
			'number'  => 5,
			'gap'     => 10
		] );

		if ( defined( 'GH_DOING_BLOCK_PREVIEW' ) && $context === 'html' ) {
			$props['number'] -= 1;
		}

		// Special
		add_action( 'pre_get_posts', [ $this, 'post_query_filter' ] );

		// Replace the ID of the block with the Query ID so that the filters work
		$props['id'] = $props['queryId'] ?? '';

		$query = new WP_Query( [
			'posts_per_page' => $props['number'],
			'orderby'        => $props['orderby'],
			'order'          => $props['order'],
			'post_status'    => 'publish'
		] );

		if ( ! $query->have_posts() ) {
			return "No posts found";
		}

		$cells = [];

		if ( defined( 'GH_DOING_BLOCK_PREVIEW' ) && $context === 'html' ) {
			$cells[] = "<div class='replace-with-child-blocks'></div>";
		}

		while ( $query->have_posts() ):
			$query->the_post();

			$template = self::do_post_merge_tags( $content, $props );
			$cells[]  = $this->parse_blocks( $template, $context );
		endwhile;

		$query->reset_postdata();

		remove_action( 'pre_get_posts', [ $this, 'post_query_filter' ] );

		// generate the table HTML now...

		$columns = $props['columns'] ?? 2;

		if ( $columns <= 0 ) {
			$columns = 1;
		}

		$columnGap = sprintf( '<td class="email-columns-cell gap" style="width: %1$dpx;height: %1$dpx;line-height: 1;font-size: %1$dpx;" width="%1$d" height="%1$d">%2$s</td>', $props['gap'], str_repeat( '&nbsp;', 3 ) );

		$html = '<table class="email-columns query-loop responsive" role="presentation" width="100%" style="width: 100%; table-layout: fixed" cellpadding="0" cellspacing="0">';
		$html .= '<tbody>';

		$total_items = count( $cells );
		$rows        = ceil( $total_items / $columns );

		for ( $i = 0; $i < $rows; $i ++ ) {
			if ( $i > 0 ) {
				$html .= '<tr class="email-columns-row">' . $columnGap . '</tr>';
			}
			$html .= '<tr class="email-columns-row">';
			for ( $j = 0; $j < $columns; $j ++ ) {
				$index = $i * $columns + $j;
				if ( $j > 0 ) {
					$html .= $columnGap;
				}
				if ( $index < $total_items ) {
					$html .= '<td class="email-columns-cell query-loop-cell" style="vertical-align: top">' . $cells[ $index ] . '</td>';
				} else {
					$html .= '<td></td>'; // Fill in empty cells if items don't divide evenly
				}
			}
			$html .= '</tr>';
		}

		$html .= '</tbody>';
		$html .= '</table>';


		return $html;

	}

	/**
	 * Do post merge tag replacements in the content
	 *
	 * @param $content
	 *
	 * @return array|string|string[]
	 */
	public static function do_post_merge_tags( $content, $props = [] ) {

		$props = wp_parse_args( $props, [
			'thumbnail_size' => 'post-thumbnail'
		] );

		$alt = '';

		if ( has_post_thumbnail() ){
			$post_thumbnail_id = get_post_thumbnail_id();
			$alt               = trim( strip_tags( get_post_meta( $post_thumbnail_id, '_wp_attachment_image_alt', true ) ) );
		}

		$merge_tags = [
			'the_title'         => get_the_title(),
			'the_excerpt'       => get_the_excerpt(),
			'the_thumbnail'     => has_post_thumbnail() ? html()->e( 'img', [
				'src'   => get_the_post_thumbnail_url( null, $props['thumbnail_size'] ),
				'alt'   => $alt,
				'class' => 'post-thumbnail size-' . esc_attr( $props['thumbnail_size'] )
			] ) : '',
			'the_thumbnail_url' => has_post_thumbnail() ? get_the_post_thumbnail_url( null, $props['thumbnail_size'] ) : '#',
			'the_content'       => get_the_content(),
			'the_id'            => get_the_ID(),
			'the_date'          => get_the_date(),
			'the_author'        => get_the_author(),
			'the_url'           => get_the_permalink(),
			'read_more'         => html()->a( get_the_permalink(), __( 'Read More »', 'groundhogg' ) ),
		];

		/**
		 * Filter list of available merge tags, maybe add new ones?
		 *
		 * @param array $merge_tags merge tags to replace in the content
		 * @param array $props      the current blocks properties
		 */
		$merge_tags = apply_filters( 'groundhogg/post_merge_tags', $merge_tags, $props );

		// add wrapping #tag#
		$merge_tags = array_map_keys( $merge_tags, function ( $key ) {
			return "#$key#";
		} );

		return str_replace( array_keys( $merge_tags ), array_values( $merge_tags ), $content );
	}

	/**
	 * Handle posts dynamic block
	 *
	 * @param array  $props   the block props
	 * @param string $content the block content, will be empty...
	 *
	 * @return string
	 */
	public function posts( array $props, string $content ) {

		// if the content is not empty
		if ( ! empty( $content ) ) {
			$props = array_merge( $props, $this->pull_attributes_from_old_dynamic_content_string( $content ) );
		}

		// Special
		add_action( 'pre_get_posts', [ $this, 'post_query_filter' ] );

		// Handled by the query filter above
		unset( $props['tag'] );
		unset( $props['category'] );
		unset( $props['terms'] );
		unset( $props['offset'] );
		unset( $props['post_type'] );
		unset( $props['include'] );
		unset( $props['exclude'] );

		// Replace the ID of the block with the Query ID so that the filters work
		$props['id'] = $props['queryId'] ?? '';

		$posts = replacements()->posts( $props );

		remove_action( 'pre_get_posts', [ $this, 'post_query_filter' ] );

		return $posts;
	}

	/**
	 * handle posts dynamic block in plain text context
	 *
	 * @param array  $props
	 * @param string $content
	 *
	 * @return string
	 */
	public function posts_plain( array $props, string $content ) {

		add_action( 'pre_get_posts', [ $this, 'post_query_filter' ] );

		// Handled by the query filter above
		unset( $props['tag'] );
		unset( $props['category'] );
		unset( $props['terms'] );
		unset( $props['offset'] );
		unset( $props['post_type'] );
		unset( $props['include'] );
		unset( $props['exclude'] );

		// Replace the ID of the block with the Query ID so that the filters work
		$props['id']     = $props['queryId'] ?? '';
		$props['layout'] = 'plain';

		$posts = replacements()->posts( $props );

		remove_action( 'pre_get_posts', [ $this, 'post_query_filter' ] );

		return $posts;
	}

	/**
	 * Process a shortcode in html
	 *
	 * @param array  $props
	 * @param string $content
	 *
	 * @return array|string|string[]|null
	 */
	public function shortcode( array $props, string $content ) {

		$props = wp_parse_args( $props, [
			'shortcode' => ''
		] );

		// if the content is not empty
		if ( ! empty( $content ) ) {
			$props = array_merge( $props, $this->pull_attributes_from_old_dynamic_content_string( $content ) );
		}

		// backwards compatibility
		if ( ! empty( $props['shortcode'] ) ) {
			return do_shortcode( $props['shortcode'] );
		}

		return do_shortcode( $content );
	}

	/**
	 * Process a shortcode in plain text
	 *
	 * @param array  $props
	 * @param string $content
	 *
	 * @return string
	 */
	public function shortcode_plain( array $props, string $content ) {
		$props = wp_parse_args( $props, [
			'shortcode' => ''
		] );

		// backwards compatibility
		if ( ! empty( $props['shortcode'] ) ) {
			return html2markdown( do_shortcode( $props['shortcode'] ) );
		}

		return html2markdown( do_shortcode( $content ) );
	}

	/**
	 * Block pattern
	 *
	 * @return string
	 */
	public static function get_block_regex_pattern() {
		$pattern = '/%s<!--\\s*(?\'type\'[a-z]+):(?\'id\'[A-Za-z0-9-]+) (?\'attributes\'(?&json))\\s*-->(?\'content\'.*)<!--\\s*\/\\k\'type\':\\k\'id\'\\s*-->/s';

		return sprintf( $pattern, get_json_regex() );
	}

	/**
	 * Given content, parse the blocks
	 *
	 * @param string $content
	 *
	 * @return array|false
	 */
	public function parse_blocks( string $content, string $context = 'html' ) {

		return preg_replace_callback( self::get_block_regex_pattern(), function ( $matches ) use ( $context ) {

			$block_content = $matches['content'];
			$type          = $matches['type'];
			$id            = $matches['id'];
			$block         = json_decode( $matches['attributes'], true );

			// don't just do empty check because props could be an empty object
			if ( $block === false ) {
				// could not decode json
				// todo leave as is?
				return $block_content;
			}

			$block['id']   = $id;
			$block['type'] = $type;

			return $this->pre_render_block( $block, $block_content, $context );
		}, $content );
	}

	/**
	 * Before a block is rendered
	 *
	 * @param array  $block   the block props
	 * @param string $content the block content
	 *
	 * @return string
	 */
	public function pre_render_block( array $block, string $content, string $context = 'html' ) {

		// handle block visibility
		// block has visibility filters
		if ( isset_not_empty( $block, 'include_filters' ) || isset_not_empty( $block, 'exclude_filters' ) ) {

			$contact = the_email()->get_contact();

			if ( ! is_a_contact( $contact ) ) {
				// Remove the block
				return '';
			}

			$query = new Contact_Query( [
				'filters'         => $block['include_filters'] ?? [],
				'exclude_filters' => $block['exclude_filters'] ?? [],
				'include'         => $contact->get_id()
			] );

			$count = $query->count();

			// filters did not match, so remove this block
			if ( $count === 0 ) {
				// Remove the block
				return '';
			}
		}

		/**
		 * Allow plugins to filter the block content
		 *
		 * @param string $content the block inner content
		 * @param array  $block   the block props
		 * @param string $context html or plain
		 */
		$content = apply_filters( 'groundhogg/email/block_render', $content, $block, $context );

		// if the block is registered here, then parse it
		if ( $this->is_registered( $block['type'] ) ) {
			// HTML context
			if ( $context === 'html' ) {
				// only replace the html inside the TD...
				$content = preg_replace_callback(
					'/^(<td\b[^>]*>)(.*?)(<\/td>)$/s',
					function ( $matches ) use ( $block, $context ) {
						$inner_content = $this->render_block( $block, $matches[2], $context );

						return $matches[1] . $inner_content . $matches[3];
					},
					trim( $content )
				);
			} else {
				$content = $this->render_block( $block, $content, $context );
			}
		}

		// to parse inner blocks as well
		return $this->parse_blocks( $content, $context );
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
	 * Pulls attributes from a dynamic content string like [posts:foo-bar-baz:dynamicContent {"foo":"bar"}]
	 * only returns the first match
	 *
	 * @param string $content
	 *
	 * @return array the attributes from the dynamic content string
	 */
	public function pull_attributes_from_old_dynamic_content_string( $content ) {

		$json_regex          = get_json_regex();
		$dynamic_blocks      = implode( '|', array_keys( $this->blocks ) );
		$dynamic_block_regex = '/' . $json_regex . '\\[(?\'type\'' . $dynamic_blocks . '):(?\'id\'[A-Za-z0-9-]+):dynamicContent (?\'attributes\'(?&json))\\/\\]/s';
		$found               = preg_match_all( $dynamic_block_regex, $content, $matches );

		if ( ! $found ) {
			return [];
		}

		foreach ( $matches['type'] as $i => $type ) {

			$id    = $matches['id'][ $i ];
			$json  = $matches['attributes'][ $i ];
			$props = json_decode( $json, true );

			// couldn't decode? Replace with empty string
			if ( $props === false ) {
				return [];
			}

			return array_merge( [
				'id'   => $id,
				'type' => $type,
			], $props );
		}

		return [];
	}

	/**
	 * Replace the content in the email with dynamic content
	 *
	 * @since 4.1.1 this is only maintained for backwards compatibility
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

			// couldn't decode? Replace with empty string
			if ( $props === false ) {
				$content = str_replace( $match, '', $content );
				continue;
			}

			$block = array_merge( [
				'id'   => $id,
				'type' => $type,
			], $props );

			// old dynamic blocks do not have content
			$content = str_replace( $match, $this->render_block( $block, '', $context ), $content );
		}

		return $content;
	}

}
