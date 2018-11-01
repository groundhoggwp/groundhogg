<?php namespace Groundhogg\Form_Blocks;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function register_form_category( $categories, $post ) {
    return array_merge(
        $categories,
        array(
            array(
                'slug' => 'groundhogg',
                'title' => __( 'Groundhogg Form Elements', 'groundhogg' ),
            ),
        )
    );
}

add_filter( 'block_categories',  __NAMESPACE__ . '\register_form_category', 10, 2 );

add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\enqueue_block_editor_assets' );

/**
 * Enqueue block editor only JavaScript and CSS.
 */
function enqueue_block_editor_assets() {
    // Make paths variables so we don't write em twice ;)
    $block_path = 'assets/js/editor.blocks.js';
    $style_path = 'assets/css/blocks.editor.css';

    // Enqueue the bundled block JS file
    wp_enqueue_script(
        'groundhogg-blocks',
        WPGH_PLUGIN_URL  . $block_path,
        [ 'wp-i18n', 'wp-element', 'wp-blocks', 'wp-components' ],
        filemtime( WPGH_PLUGIN_DIR . $block_path )
    );

    // Enqueue optional editor only styles
    wp_enqueue_style(
        'groundhogg-blocks-editor-css',
        WPGH_PLUGIN_URL . $style_path,
        [ 'wp-blocks' ],
        filemtime( WPGH_PLUGIN_DIR . $style_path )
    );

    wp_enqueue_script('wpgh-admin-js' );

}

add_action( 'enqueue_block_assets', __NAMESPACE__ . '\enqueue_assets' );

/**
 * Enqueue front end and editor JavaScript and CSS assets.
 */
function enqueue_assets() {
    $style_path = 'assets/css/blocks.style.css';
    wp_enqueue_style(
        'groundhogg-blocks',
        WPGH_PLUGIN_URL . $style_path,
        [ 'wp-blocks' ],
        filemtime( WPGH_PLUGIN_DIR . $style_path )
    );
}

add_action( 'enqueue_block_assets', __NAMESPACE__ . '\enqueue_frontend_assets' );

/**
 * Enqueue frontend JavaScript and CSS assets.
 */
function enqueue_frontend_assets() {

    // If in the backend, bail out.
    if ( is_admin() ) {
        return;
    }

    $block_path = 'assets/js/frontend.blocks.js';
    wp_enqueue_script(
        'groundhogg-blocks-frontend',
        WPGH_PLUGIN_URL . $block_path,
        [],
        filemtime( WPGH_PLUGIN_DIR . $block_path )
    );
}

function render_block_wpgh_form( $attributes, $content ) {

    $forms = WPGH()->steps->get_steps( array( 'step_type' => 'form_fill' ) );

    $options = array();

    foreach ( $forms as $form ){

        $step = new \WPGH_Step( $form->ID );

        if ( $step->is_active() ){
            $options[ $step->ID ] = $step->title;
        }
    }

    $args = array(
        'id' => 'form-picker',
        'name' => 'form-picker',
        'options' => $options,
        'class' => 'form-picker'
    );

    ob_start()

    ?>
    <?php echo WPGH()->html->dropdown( $args ); ?>
    <?php

    return ob_get_clean();
}

function render_block_wpgh_form_save( $attributes, $content ){

    ob_start();

    echo do_shortcode( sprintf( '[wpgh_form id="%s"]', $attributes[ 'id' ] ) );

    return ob_get_clean();

}

function register_wpgh_blocks(){

//    if ( function_exists( 'register_block_type' ) ){
    register_block_type( 'groundhogg/form', array(
        'render_callback' => __NAMESPACE__ . '\render_block_wpgh_form',
    ) );

    register_block_type( 'groundhogg/form-saved', array(
        'render_callback' => __NAMESPACE__ . '\render_block_wpgh_form_saved',
    ) );
//    }
}

add_action( 'init', __NAMESPACE__ . '\register_wpgh_blocks' );
