<?php

namespace Groundhogg;

class Dynamic_Block_Handler {

	/**
	 * @var array Store the dyanmic block handlers
	 */
	protected $blocks = [];

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
		$this->register( 'posts', [ $this, 'posts' ] );
	}

	/**
	 * Register a dynamic block callback
	 *
	 * @param $type
	 * @param $callback
	 *
	 * @return bool
	 */
	public function register( $type, $callback ) {

		if ( ! $type || ! is_callable( $callback ) ) {
			return false;
		}

		$this->blocks[ $type ] = $callback;

		return true;
	}

	/**
	 * @param $type
	 * @param $props
	 *
	 * @return false|mixed
	 */
	public function render_block( $type, $props ) {
		return call_user_func( $this->blocks[ $type ], $props );
	}

	/**
	 * handle posts dyanmic block
	 *
	 * @param $props
	 *
	 * @return string
	 */
	public function posts( $props ) {

		$props = wp_parse_args( $props, [
			'query' => [
				'numberposts' => '5'
			]
		] );

		$posts = get_posts( $props['query'] );

		ob_start();

		foreach ( $posts as $post ):
			?>
			<div class="post">
				<table>
					<tr>
						<td width="33.333%">
							<a href="<?php echo esc_url( get_permalink( $post ) ) ?>"><?php echo get_the_post_thumbnail( $post ); ?></a>
						</td>
						<td width="66.666%" style="padding-left: 20px">
							<h3 class="post-title" style="margin-top: 0">
								<a href="<?php echo esc_url( get_permalink( $post ) ) ?>"><?php esc_html_e( $post->post_title ); ?></a>
							</h3>
							<div class="post-excerpt">
								<?php echo wpautop( get_the_excerpt( $post ) ) ?>
							</div>
						</td>
					</tr>
				</table>
			</div>
		<?php
		endforeach;

		return ob_get_clean();
	}

	/**
	 * Replace the content in the email with dynamic content
	 *
	 * @param $content
	 * @param $blocks
	 *
	 * @return array|mixed|string|string[]
	 */
	public function replace_content( $content, $blocks ) {

		$blocks = array_filter( $blocks, function ( $block ) {
			return key_exists( 'type', $block ); } );

		foreach ( $blocks as $block ) {

			if ( key_exists( get_array_var( $block, 'type' ), $this->blocks ) ) {
				$content = str_replace( "<!-- {$block['type']}:{$block['id']} -->", $this->render_block( $block['type'], $block ), $content );
			}

			if ( key_exists( 'columns', $block ) ) {
				foreach ( $block['columns'] as $column ) {
					$content = $this->replace_content( $content, $column );
				}
			}

		}

		return $content;
	}

}
