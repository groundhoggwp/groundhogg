<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @since 1.0
 * @version 1.0
 */

if ( ! function_exists( 'obfuscate_email' ) ):
    /**
     * Obfuscate an email address
     *
     * @param $email
     * @return string|string[]|null
     */
    function obfuscate_email( $email )
    {
        return preg_replace("/(?!^).(?=[^@]+@)/", "*", $email );
    }

endif;

/**
 * Remove all other styles form this page so there are no conflicts.
 */
function enqueue_manage_preferences_styles()
{
    global $wp_styles;
    $wp_styles->queue = array();

    wp_enqueue_style( 'manage-preferences' );

    /**
     * Allow plugins to add styles to this page.
     */
    do_action( 'enqueue_manage_preferences_styles' );
}

/**
 * Enqueue any required JS.
 */
function enqueue_manage_preferences_scripts()
{
    wp_enqueue_script( 'manage-preferences' );

    /**
     * Allow plugins to add scripts to this page.
     */
    do_action( 'enqueue_manage_preferences_scripts' );
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
            .manage-preferences h1 a {
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
function manage_preferences_head( $title='', $action='' )
{
    add_action( 'wp_print_styles', 'enqueue_manage_preferences_styles' );
    add_action( 'wp_enqueue_scripts', 'enqueue_manage_preferences_scripts' );

    add_action( 'wp_head', 'noindex' );
    add_action( 'wp_head', 'wp_sensitive_page_meta' );
    add_action( 'wp_head', 'wp_login_viewport_meta' );
    add_action( 'wp_head', 'ensure_logo_is_there' );

    $mp_title = get_bloginfo( 'name', 'display' );

	/* translators: Login screen title. 1: Login screen name, 2: Network or site name */
	$mp_title = sprintf( __( '%1$s &lsaquo; %2$s' ), $title, $mp_title );
    $mp_title = apply_filters( 'manage_preferences_title', $mp_title, $title );

    $classes = [ $action ];
    $classes = apply_filters( 'manage_preferences_title', $classes, $action );


    if ( is_multisite() ) {
        $header_url   = network_home_url();
        $header_title = get_network()->site_name;
    } else {
        $header_url   = __( 'https://www.groundhogg.io/' );
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
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <title><?php echo $mp_title; ?></title>

    <?php wp_head(); ?>
</head>
<body class="manage-preferences <?php echo esc_attr( implode( ' ', $classes ) ); ?>">
<div id="main">
    <h1><a href="<?php echo esc_url( $header_url ); ?>" title="<?php echo esc_attr( $header_title ); ?>"><?php echo $header_text; ?></a></h1>
    <div id="content box">
    <?php
}

/**
 * Outputs the footer for the login page.
 */
function manage_preferences_footer() {
    ?>
    </div>
    <p id="backtoblog"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">
            <?php
            /* translators: %s: site title */
            printf( _x( '&larr; Back to %s', 'site' ), get_bloginfo( 'title', 'display' ) );
            ?>
        </a></p>
    <?php the_privacy_policy_link( '<div class="privacy-policy-page-link">', '</div>' ); ?>
</div>
<?php
wp_footer();
?>
<div class="clear"></div>
</body>
</html>
<?php
}

$action = get_query_var( 'action', 'manage' );
$contact_id = absint( get_query_var( 'contact_id', 0 ) );
$contact = \Groundhogg\Plugin::$instance->utils->get_contact( $contact_id );

if ( ! $contact ){
    wp_die( __( 'No contact to manage. This might be because you do not have cookies enabled, or you have clicked an expired link.', 'groundhogg' ) );
}

switch ( $action ):

    default:
    case 'manage':

        manage_preferences_head( __( 'Manage Preferences', 'groudhogg' ), 'manage' );

        ?>
<p><?php printf( __( 'Managing preferences for %s.', 'groundhogg' ), obfuscate_email( $contact->get_email() ) )?></p>
<form action="" id="preferences">
    <?php wp_nonce_field( 'manage_email_preferences' ); ?>

</form>
    <?php


        manage_preferences_footer();

        break;
    case 'unsubscribe':
        break;
    case 'confirm':
        break;

endswitch;
