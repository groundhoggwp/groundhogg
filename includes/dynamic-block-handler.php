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

		global $post;

		$props = wp_parse_args( $props, [
			'query'          => [],
			'hide_excerpt'   => false,
			'hide_meta_data' => false,
		] );

		$query = wp_parse_args( [
			'numberposts' => 5
		], $props['query'] );

		$posts = get_posts( $query );

		ob_start();

		?>
        <div class="email-columns">
			<?php

			foreach ( $posts as $post ):

				setup_postdata( $post )

				?>
                <div class="email-columns-row">
                    <div class="email-columns-cell one-third">
                        <img src="<?php echo get_the_post_thumbnail_url( $post ) ?>">
                    </div>
                    <div class="email-columns-cell" style="width: 20px;"></div>
                    <div class="email-columns-cell two-thirds">
                        <h2><?php the_title() ?></h2>
                        <p><?php the_excerpt(); ?></p>
                    </div>
                </div>
			<?php
			endforeach;
			?>
        </div>
		<?php
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
			return key_exists( 'type', $block );
		} );

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
