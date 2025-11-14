<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function groundhogg_form_block_init() {
	$block = register_block_type( __DIR__ . '/build/' );
	$handle = $block->editor_script_handles[0];

	wp_scripts()->registered[$handle]->deps[] = 'groundhogg-admin';
	wp_scripts()->registered[$handle]->deps[] = 'groundhogg-admin-element';
	wp_scripts()->registered[$handle]->deps[] = 'groundhogg-make-el';
}

add_action( 'init', 'groundhogg_form_block_init' );
