<?php
namespace Groundhogg;

use Groundhogg\Form\Form;

/**
 * Enqueue and dequeue relevant scripts.
 */
function enqueue_form_submit_styles()
{
    dequeue_theme_css_compat();
    dequeue_wc_css_compat();

    wp_enqueue_style( 'groundhogg-managed-page' );
    wp_enqueue_style( 'groundhogg-form' );

    /**
     * Allow plugins to add styles to this page.
     */
    do_action( 'enqueue_form_submit_styles' );
}

/**
 * Enqueue any required JS.
 */
function enqueue_form_submit_scripts()
{
    wp_enqueue_script( 'fullframe' );

    /**
     * Allow plugins to add scripts to this page.
     */
    do_action( 'enqueue_form_submit_scripts' );
}

/**
 * Use the site logo.
 */
function ensure_logo_is_there()
{
    if ( has_custom_logo() ) :

        $image = wp_get_attachment_image_src( get_theme_mod( 'custom_logo' ), 'full' );

        ?>
        <style type="text/css">
            #main h1 a {
                background-image: url(<?php echo esc_url( $image[0] ); ?>);
                -webkit-background-size: <?php echo absint( $image[1] )?>px;
                background-size: <?php echo absint( $image[1] ) ?>px;
                height: <?php echo absint( $image[2] ) ?>px;
                width: <?php echo absint( $image[1] ) ?>px;
            }
        </style>
    <?php
    endif;
}

/**
 * Output the page header.
 *
 * @param string $title
 * @param string $action
 */
function form_submit_head( $title='', $action='' )
{
    add_action( 'wp_print_styles', 'Groundhogg\enqueue_form_submit_styles' );
    add_action( 'wp_enqueue_scripts', 'Groundhogg\enqueue_form_submit_scripts' );

    add_action( 'wp_head', 'noindex' );
    add_action( 'wp_head', 'wp_sensitive_page_meta' );
    add_action( 'wp_head', 'Groundhogg\ensure_logo_is_there' );

    $mp_title = get_bloginfo( 'name', 'display' );

    /* translators: Login screen title. 1: Login screen name, 2: Network or site name */
    $mp_title = sprintf( __( '%1$s &lsaquo; %2$s' ), $title, $mp_title );
    $mp_title = apply_filters( 'form_submit_title', $mp_title, $title );

    $classes = [ $action ];
    $classes = apply_filters( 'form_submit_title', $classes, $action );


    if ( is_multisite() ) {
        $header_url   = network_home_url();
        $header_title = get_network()->site_name;
    } else {
        $header_url   = site_url();
        $header_title = __( 'Powered by Groundhogg', 'groundhogg' );
    }

    /*
     * To match the URL/title set above, Multisite sites have the blog name,
     * while single sites get the header title.
     */
    if ( is_multisite() ) {
        $header_text = get_bloginfo( 'name', 'display' );
    } else {
        $header_text = $header_title;
    }

    ?><!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="profile" href="http://gmpg.org/xfn/11">
        <title><?php echo $mp_title; ?></title>
        <?php wp_head(); ?>
        <style>
            #main {max-width: 650px;}
        </style>
    </head>
    <body class="manage-preferences <?php echo esc_attr( implode( ' ', $classes ) ); ?>">
    <div id="main">
    <?php if ( has_custom_logo() ): ?>
    <h1><a href="<?php echo esc_url( $header_url ); ?>" title="<?php echo esc_attr( $header_title ); ?>"><?php echo $header_text; ?></a></h1>
<?php endif; ?>
    <div id="content">
    <?php
}

/**
 * Outputs the footer for the login page.
 */
function form_submit_footer() {
    ?>
    </div>
    <p id="extralinks"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">
            <?php
            /* translators: %s: site title */
            printf( _x( '&larr; Back to %s', 'site' ), get_bloginfo( 'title', 'display' ) );
            ?></a> | <a href="<?php echo esc_url( site_url( 'gh/preferences/profile' ) ); ?>">
            <?php
            /* translators: %s: site title */
            _e( 'Edit Profile', 'groundhogg' );
            ?></a> |
        <?php the_privacy_policy_link( '<span class="privacy-policy-page-link">', '</span>' ); ?></p>
    </div>
    <?php
    wp_footer();
    ?>
    <div class="clear"></div>
    </body>
    </html>
    <?php
}

$form_id = get_query_var( 'form_id' );
$form = new Form( [ 'id' => $form_id ] );

form_submit_head( __( 'Submit form', 'groundhogg' ), 'view' );

?>
    <div class="box">
        <?php

        if ( Plugin::$instance->submission_handler->has_errors() ){

            $errors = Plugin::$instance->submission_handler->get_errors();
            $err_html = "";

            foreach ( $errors as $error ){
                $err_html .= sprintf( '<li id="%s">%s</li>', $error->get_error_code(), $error->get_error_message() );
            }

            $err_html = sprintf( "<ul class='gh-form-errors'>%s</ul>", $err_html );
            echo sprintf( "<div class='gh-form-errors-wrapper'>%s</div>", $err_html );

        }

        ?>
        <?php echo $form->get_iframe_embed_code(); ?>
    </div>
    <?php

form_submit_footer();