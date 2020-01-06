<?php
namespace Groundhogg\Blocks\Gutenberg;

use function Groundhogg\get_db;

/**
 * Register Groundhogg Gutenberg block on the backend.
 *
 * @since 1.4.8
 */
if ( function_exists( 'register_block_type' ) ){

    add_action( 'init', 'Groundhogg\Blocks\Gutenberg\register_block' );
    add_action( 'enqueue_block_editor_assets',  'Groundhogg\Blocks\Gutenberg\enqueue_block_editor_assets' );

    function register_block()
    {
        // Enqueue the Groundhogg form style.
        register_block_type( 'groundhogg/forms', array(
            'attributes'      => array(
                'formId'       => array(
                    'type' => 'string',
                ),
                'displayTitle' => array(
                    'type' => 'boolean',
                ),
                'displayDesc'  => array(
                    'type' => 'boolean',
                ),
            ),
            'editor_style'    => 'groundhogg-gutenberg-form-selector',
            'render_callback' => 'Groundhogg\Blocks\Gutenberg\get_gutenberg_form_html' ,
        ) );
    }
}

/**
 * Load Groundhogg Gutenberg block scripts.
 *
 * @since 1.4.8
 */
function enqueue_block_editor_assets() {

    $i18n = array(
        'title'            => esc_html__( 'Groundhogg', 'groundhogg' ),
        'description'      => esc_html__( 'Select and display one of your forms.', 'groundhogg' ),
        'form_select'      => esc_html__( 'Select a Form', 'groundhogg' ),
        'form_settings'    => esc_html__( 'Form Settings', 'groundhogg' ),
        'form_selected'    => esc_html__( 'Form', 'groundhogg' ),
        'show_title'       => esc_html__( 'Show Title', 'groundhogg' ),
        'show_description' => esc_html__( 'Show Description', 'groundhogg' ),
    );

    wp_enqueue_script( 'groundhogg-gutenberg-form-selector',plugin_dir_url( __FILE__ ) . 'js/blocks.js', array( 'wp-blocks', 'wp-i18n', 'wp-element' ,'wp-editor', 'wp-components'  ),GROUNDHOGG_VERSION );
    wp_enqueue_style('groundhogg-form', GROUNDHOGG_ASSETS_URL . 'css/frontend/form.css', [], GROUNDHOGG_VERSION);

    $forms = get_db( 'steps' )->query( [ 'step_type' => 'form_fill' ] );

    wp_localize_script(
        'groundhogg-gutenberg-form-selector',
        'groundhogg_gutenberg_form_selector',
        array(
            'logo_url' => GROUNDHOGG_ASSETS_URL . 'images/phil-340x340.png',
            'forms'    => ! empty( $forms ) ? $forms : array(),
            'i18n'     => $i18n,
        )
    );
}

/**
 * Get form HTML to display in a Groundhogg Gutenberg block.
 *
 * @param array $attr Attributes passed by WPForms Gutenberg block.
 *
 * @since 1.4.8
 *
 * @return string
 */
function get_gutenberg_form_html( $attr ) {

    $id = ! empty( $attr['formId'] ) ? absint( $attr['formId'] ) : 0;

    if ( empty( $id ) ) {
        return '';
    }

    $title = ! empty( $attr['displayTitle'] ) ? true : false;
    if ( empty( $id ) ) {
        return '';
    }

    ob_start();

    echo do_shortcode( ' [gh_form id="'.$id.'" title="'.$title.'"] ' );

    return ob_get_clean();
}