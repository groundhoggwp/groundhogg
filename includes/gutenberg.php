<?php

/**
 * Register Groundhogg Gutenberg block on the backend.
 *
 * @since 1.4.8
 */

if ( function_exists( 'register_block_type' ) ){

    add_action( 'init', 'wpgh_register_block' );
    add_action( 'enqueue_block_editor_assets',  'wpgh_enqueue_block_editor_assets' );

    function wpgh_register_block()
    {
        wp_register_style(
            'groundhogg-form-styling-frontend',
            plugins_url( '../assets/css/form.css', __FILE__ ),
            array( 'wp-edit-blocks' )
        );

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
            'render_callback' => 'wpgh_get_gutenberg_form_html' ,
        ) );
    }
}


/**
 * Load Groundhogg Gutenberg block scripts.
 *
 * @since 1.4.8
 */
function wpgh_enqueue_block_editor_assets() {

    $i18n = array(
        'title'            => esc_html__( 'Groundhogg', 'groundhogg' ),
        'description'      => esc_html__( 'Select and display one of your forms.', 'groundhogg' ),
        'form_select'      => esc_html__( 'Select a Form', 'groundhogg' ),
        'form_settings'    => esc_html__( 'Form Settings', 'groundhogg' ),
        'form_selected'    => esc_html__( 'Form', 'groundhogg' ),
        'show_title'       => esc_html__( 'Show Title', 'groundhogg' ),
        'show_description' => esc_html__( 'Show Description', 'groundhogg' ),
    );

    $blockPath = '../assets/js/editor.blocks.js';

    wp_enqueue_script(
        'groundhogg-gutenberg-form-selector',
        plugins_url( $blockPath, __FILE__ ),
        array( 'wp-blocks', 'wp-i18n', 'wp-element' ,'wp-editor', 'wp-components'  ),
        filemtime( plugin_dir_path( __FILE__ ) . $blockPath )
    );

    $forms = WPGH()->steps->get_steps( array(
        'step_type' => 'form_fill'
    ) );

    wp_localize_script(
        'groundhogg-gutenberg-form-selector',
        'groundhogg_gutenberg_form_selector',
        array(
            'logo_url' =>   plugins_url('../assets/images/phil-340x340.png',__FILE__),
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
function wpgh_get_gutenberg_form_html( $attr ) {

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