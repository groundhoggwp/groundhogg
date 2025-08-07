<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Get form HTML to display in a Groundhogg Gutenberg block.
 *
 * @param array $attr Attributes passed by WPForms Gutenberg block.
 *
 * @return string
 * @since 1.4.8
 *
 */
function groundhogg_get_gutenberg_form_html( $attr ) {

	$id = ! empty( $attr['formId'] ) ? absint( $attr['formId'] ) : 0;

	if ( empty( $id ) ) {
		return '';
	}

	$title = ! empty( $attr['displayTitle'] ) ? true : false;
	if ( empty( $id ) ) {
		return '';
	}

	ob_start();

	echo "<div class='" . \Groundhogg\get_array_var( $attr, 'className', '' ) . "'>";

	echo do_shortcode( ' [gh_form id="' . $id . '" title="' . $title . '"] ' );

	echo "</div>";

	return ob_get_clean();
}

add_action( 'init', 'groundhogg_gutenberg_form_selector_init' );

/**
 * Register the block type
 *
 * @return void
 */
function groundhogg_gutenberg_form_selector_init() { // phpcs:ignore

	// Register block editor script for backend.
	wp_register_script(
		'groundhogg-form-block-js', // Handle.
		plugins_url( '/dist/blocks.build.js', dirname( __FILE__ ) ), // Block.build.js: We register the block here. Built with Webpack.
		array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ), // Dependencies, defined above.
		null, // filemtime( plugin_dir_path( __DIR__ ) . 'dist/blocks.build.js' ), // Version: filemtime — Gets file modification time.
		true // Enqueue the script in the footer.
	);

	add_action( 'enqueue_block_editor_assets', function (){

		$forms = \Groundhogg\get_form_list();

		$i18n = array(
			'title'            => \Groundhogg\white_labeled_name(),
			'description'      => esc_html__( 'Select and display one of your forms.', 'groundhogg' ),
			'form_select'      => esc_html__( 'Select a Form', 'groundhogg' ),
			'form_settings'    => esc_html__( 'Form Settings', 'groundhogg' ),
			'form_selected'    => esc_html__( 'Form', 'groundhogg' ),
			'show_title'       => esc_html__( 'Show Title', 'groundhogg' ),
			'show_description' => esc_html__( 'Show Description', 'groundhogg' ),
		);

		wp_localize_script(
			'groundhogg-form-block-js',
			'groundhogg_gutenberg_form_selector',
			array(
				'logo_url' => GROUNDHOGG_ASSETS_URL . 'images/phil-340x340.png',
				'forms'    => ! empty( $forms ) ? $forms : array(),
				'i18n'     => $i18n,
			)
		);

	} );

	// Enqueue the Groundhogg form style.
	register_block_type( 'groundhogg/forms', array(
		'attributes' => array(
			'formId'       => array(
				'type' => 'string',
			),
			'displayTitle' => array(
				'type' => 'boolean',
			),
			'displayDesc'  => array(
				'type' => 'boolean',
			),
			'ghReplacements' => [
				'type' => 'boolean',
			],
			'ghRestrictContent' => [
				'type' => 'boolean',
			],
			'ghIncludeFilters' => [
				'type' => 'string',
			],
			'ghExcludeFilters' => [
				'type' => 'string',
			]
		),

		'render_callback' => 'groundhogg_get_gutenberg_form_html',
		'style'           => 'groundhogg-form',
		'editor_script'   => 'groundhogg-form-block-js',
	) );


}

