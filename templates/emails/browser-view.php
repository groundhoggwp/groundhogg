<?php
namespace Groundhogg;

/**
 * Enqueue and dequeue relevant scripts.
 */
function enqueue_browser_view_styles()
{
    dequeue_theme_css_compat();
    dequeue_wc_css_compat();

    wp_enqueue_style( 'groundhogg-managed-page' );

    /**
     * Allow plugins to add styles to this page.
     */
    do_action( 'enqueue_browser_view_styles' );
}

/**
 * Enqueue any required JS.
 */
function enqueue_browser_view_scripts()
{
    wp_enqueue_script( 'fullframe' );

    /**
     * Allow plugins to add scripts to this page.
     */
    do_action( 'enqueue_browser_view_scripts' );
}

define( 'MANAGED_PAGE_WIDTH', 500 );

/**
 * Use the site logo.
 */
function ensure_logo_is_there()
{
	if ( has_custom_logo() ) :

		$image = wp_get_attachment_image_src( get_theme_mod( 'custom_logo' ), 'full' );

		// Resize image
		if ( $image[ 1 ] > MANAGED_PAGE_WIDTH ){
			$aspect_ratio = MANAGED_PAGE_WIDTH / $image[ 1 ];
			$image[ 1 ] = MANAGED_PAGE_WIDTH;
			$image[ 2 ] = $image[2] * $aspect_ratio;
		}

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
function browser_view_head( $title='', $action='' )
{
    add_action( 'wp_print_styles', 'Groundhogg\enqueue_browser_view_styles' );
    add_action( 'wp_enqueue_scripts', 'Groundhogg\enqueue_browser_view_scripts' );

    add_action( 'wp_head', 'noindex' );
    add_action( 'wp_head', 'wp_sensitive_page_meta' );
    add_action( 'wp_head', 'Groundhogg\ensure_logo_is_there' );

    $mp_title = get_bloginfo( 'name', 'display' );

    /* translators: Login screen title. 1: Login screen name, 2: Network or site name */
    $mp_title = sprintf( __( '%1$s &lsaquo; %2$s' ), $title, $mp_title );
    $mp_title = apply_filters( 'browser_view_title', $mp_title, $title );

    $classes = [ $action ];
    $classes = apply_filters( 'browser_view_title', $classes, $action );


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
function browser_view_footer() {
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

$email_id = absint( get_query_var( 'email_id' ) );
$email = Plugin::$instance->utils->get_email( $email_id );

if ( ! $email ){
    wp_die( __( 'Could not load email...' ) );
}

$contact = Plugin::$instance->tracking->get_current_contact();
$email->set_contact( $contact );

$subject = $email->get_merged_subject_line();

browser_view_head( $subject, 'view' );

?>
<div class="box">
    <iframe width="100%" src="<?php echo esc_url( site_url( 'gh/emails/' . $email_id ) ); ?>"></iframe>
</div>
<?php

browser_view_footer();
