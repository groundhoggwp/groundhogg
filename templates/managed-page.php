<?php

namespace Groundhogg;

use function Groundhogg\Notices\add_notice;
use function Groundhogg\Notices\print_notices;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include __DIR__ . '/notices.php';

define( 'MANAGED_PAGE_WIDTH', 500 );

/**
 * Enqueue and dequeue relevant scripts.
 */
function enqueue_managed_page_styles() {
	dequeue_theme_css_compat();
	dequeue_wc_css_compat();

	wp_enqueue_style( 'groundhogg-managed-page' );
	wp_enqueue_style( 'groundhogg-form' );
	wp_enqueue_style( 'common' );

	/**
	 * Allow plugins to add styles to this page.
	 */
	do_action( 'enqueue_managed_page_styles' );
}

/**
 * Enqueue any required JS.
 */
function enqueue_managed_page_scripts() {
	wp_enqueue_script( 'fullframe' );

	/**
	 * Allow plugins to add scripts to this page.
	 */
	do_action( 'enqueue_managed_page_scripts' );
}

/**
 * Use the site logo.
 */
function ensure_logo_is_there() {
	if ( has_custom_logo() ) :

		$image = wp_get_attachment_image_src( get_theme_mod( 'custom_logo' ), 'full' );

		// Resize image
		if ( $image[1] > MANAGED_PAGE_WIDTH ) {
			$aspect_ratio = MANAGED_PAGE_WIDTH / $image[1];
			$image[1]     = MANAGED_PAGE_WIDTH;
			$image[2]     = $image[2] * $aspect_ratio;
		}

		?>
        <style type="text/css">
            #main h1 a {
                background-image: url(<?php echo esc_url( $image[0] ); ?>);
                -webkit-background-size: <?php echo absint( $image[1] )?>px;
                background-size: contain;
                height: <?php echo absint( $image[2] ) ?>px;
                width: <?php echo absint( $image[1] ) ?>px;
	            max-width: 100%;
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
function managed_page_head( $title = '', $action = '' ) {
	add_action( 'wp_print_styles', 'Groundhogg\enqueue_managed_page_styles' );
	add_action( 'wp_enqueue_scripts', 'Groundhogg\enqueue_managed_page_scripts' );

	add_filter( 'wp_robots', 'wp_robots_noindex' );
	add_filter( 'wp_robots', 'wp_robots_sensitive_page' );

	add_action( 'wp_head', 'Groundhogg\ensure_logo_is_there' );

	status_header( 200 );
	header( 'Content-Type: text/html; charset=utf-8' );
	nocache_headers();

	$mp_title = get_bloginfo( 'name', 'display' );

	/* translators: Login screen title. 1: Login screen name, 2: Network or site name */
	$mp_title = sprintf( __( '%1$s &lsaquo; %2$s' ), esc_html( $title ), esc_html( $mp_title ) );
	$mp_title = apply_filters( 'managed_page_title', $mp_title, $title );

	$classes = [ $action ];
	$classes = apply_filters( 'managed_page_classes', $classes, $action );


	if ( is_multisite() ) {
		$header_url   = network_home_url();
		$header_title = get_network()->site_name;
	} else {
		$header_url   = home_url();
		$header_title = sprintf( __( 'Powered by %s', 'groundhogg' ), white_labeled_name() );
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
		<?php no_index_tag(); ?>
        <link rel="profile" href="http://gmpg.org/xfn/11">
        <title><?php echo $mp_title; ?></title>
		<?php wp_head(); ?>
    </head>
    <body class="managed-page <?php echo esc_attr( implode( ' ', $classes ) ); ?>">
	<?php wp_body_open(); ?>
    <div id="main">
	<?php if ( has_custom_logo() ): ?>
        <h1><a href="<?php echo esc_url( $header_url ); ?>"
               title="<?php echo esc_attr( $header_title ); ?>"><?php echo esc_html( $header_text ); ?></a></h1>
	<?php endif;

	if ( $notice = get_url_var( 'notice' ) ) {
		add_notice( sanitize_key( $notice ) );
	}

	print_notices();

	?>
    <div id="content">
	<?php
}

/**
 * Outputs the footer for the login page.
 */
function managed_page_footer() {

	$privacy_policy_url = function_exists( 'get_privacy_policy_url' ) && get_privacy_policy_url() ? get_privacy_policy_url() : get_option( 'gh_privacy_policy' );

	$footer_links = [
		html()->e( 'a', [ 'href' => home_url( '/' ) ], '&larr; ' . sprintf( esc_html__( 'Back to %s', 'groundhogg' ), get_bloginfo( 'title', 'display' ) ) ),
		html()->e( 'a', [ 'href' => $privacy_policy_url ], esc_html__( 'Privacy Policy', 'groundhogg' ) ),
	];

    // A contact is being tracked...
	if ( get_contactdata() ) {
		$footer_links[] = html()->e( 'a', [ 'href' => managed_page_url( 'preferences/profile/' ) ], esc_html__( 'My Profile', 'groundhogg' ) );
    }

	/**
	 * Filter the footer links for the managed page
	 *
	 * @param $footer_links array
	 */
	$footer_links = apply_filters( 'groundhogg/managed_page/footer_links', $footer_links );

	$html = implode( ' | ', $footer_links );

	?>
    </div>
    <p id="extralinks"><?php echo $html; ?></p>
	<?php if ( is_option_enabled( 'gh_affiliate_link_in_email' ) ): ?>
        <p id="credit">
			<?php printf( esc_html__( "Powered by %s", 'groundhogg' ), html()->e( 'a', [
				'target' => '_blank',
				'href'   => add_query_arg( [
					'utm_source'   => 'email',
					'utm_medium'   => 'footer-link',
					'utm_campaign' => 'email-affiliate',
					'aff'          => absint( get_option( 'gh_affiliate_id' ) ),
				], 'https://www.groundhogg.io/pricing/' )
			], html()->e( 'img', [
				'width' => 85,
				'src'   => GROUNDHOGG_ASSETS_URL . 'images/groundhogg-logo-email-footer.png'
			], null, true ) ) ); ?>
        </p>
	<?php endif; ?>
    </div>
	<?php
	wp_footer();
	?>
    <div class="clear"></div>
    </body>
    </html>
	<?php
}
