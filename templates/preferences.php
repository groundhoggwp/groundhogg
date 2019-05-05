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
        $email = preg_replace("/(?!@).(?=.{2}[^@]+\.)/", "*", $email );
        return $email;
    }

endif;

/**
 * Remove all other styles form this page so there are no conflicts.
 */
function enqueue_manage_preferences_styles()
{

    // Dequeue Theme Support.
    wp_dequeue_style( basename( get_stylesheet_directory() ) . '-style' );

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
function manage_preferences_head( $title='', $action='' )
{
    add_action( 'wp_print_styles', 'enqueue_manage_preferences_styles' );
    add_action( 'wp_enqueue_scripts', 'enqueue_manage_preferences_scripts' );

    add_action( 'wp_head', 'noindex' );
    add_action( 'wp_head', 'wp_sensitive_page_meta' );
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
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <title><?php echo $mp_title; ?></title>

    <?php wp_head(); ?>
</head>
<body class="manage-preferences <?php echo esc_attr( implode( ' ', $classes ) ); ?>">
<div id="main">
    <?php if ( has_custom_logo() ): ?>
    <h1><a href="<?php echo esc_url( $header_url ); ?>" title="<?php echo esc_attr( $header_title ); ?>"><?php echo $header_text; ?></a></h1>
    <?php endif; ?>
    <div id="content box">
    <?php
}

/**
 * Outputs the footer for the login page.
 */
function manage_preferences_footer() {
    ?>
    </div>
    <p id="extralinks"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">
            <?php
            /* translators: %s: site title */
            printf( _x( '&larr; Back to %s', 'site' ), get_bloginfo( 'title', 'display' ) );
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

$action = get_query_var( 'action', 'manage' );
$contact_id = absint( get_query_var( 'contact_id', 0 ) );
//$contact = \Groundhogg\Plugin::$instance->utils->get_contact( $contact_id );
$contact = new \Groundhogg\Contact( 1 );
//$contact = false;

// Force email collection.
if ( ! $contact ){
    $action = 'no_email';
}

switch ( $action ):

    default:
    // Support for reaching this page if there is no email to manage...
    case 'no_email':

    manage_preferences_head( __( 'Manage Preferences', 'groundhogg' ), 'manage' );

    ?>
    <form action="" id="emailaddress" method="post">
        <?php wp_nonce_field( 'manage_email_preferences' ); ?>
        <p><?php _e( 'Please enter your email address to manage your preferences.', 'groundhogg' ); ?></p>
        <p><input type="email" name="email" id="email" placeholder="<?php esc_attr_e( "your.name@domain.com", 'groundhogg' ); ?>" required></p>
        <p>
            <input id="submit" type="submit" value="<?php esc_attr_e( 'Submit', 'groundhogg' ); ?>">
        </p>
    </form>
    <?php

    manage_preferences_footer();

    break;
    case 'manage':

        manage_preferences_head( __( 'Manage Preferences', 'groundhogg' ), 'manage' );

        $preferences = [
            'confirm'       => _x( 'I love this company, you can communicate with me whenever you feel like.', 'preferences', 'groundhogg' ),
            'weekly'        => _x( "It's getting a bit much. Communicate with me weekly.", 'preferences', 'groundhogg' ),
            'monthly'       => _x( 'Distance makes the heart grow fonder. Communicate with me monthly.', 'preferences', 'groundhogg' ),
            'unsubscribe'   => _x( 'I no longer wish to receive any form of communication. Unsubscribe me!', 'preferences', 'groundhogg' )
        ];

        if ( \Groundhogg\Plugin::$instance->preferences->is_gdpr_enabled() ){
            $preferences[ 'gdpr_delete' ] = _x( 'Unsubscribe me and delete any personal information about me.', 'preferences', 'groundhogg' );
        }

        $preferences = apply_filters( 'manage_email_preferences_options', $preferences );

        ?>
<form action="" id="preferences" method="post">
    <p><b><?php printf( __( 'Managing preferences for %s (%s).', 'groundhogg' ), $contact->get_full_name(), obfuscate_email( $contact->get_email() ) )?></b></p>
    <?php wp_nonce_field( 'manage_email_preferences' ); ?>
    <ul class="preferences">
        <?php foreach ( $preferences as $preference => $text ): ?>
        <li><label><input type="radio" name="preference" value="<?php esc_attr_e( $preference ); ?>" class="preference-<?php esc_attr_e( $preference ); ?>" required><?php echo $text; ?></label></li>
    <?php endforeach; ?>
    </ul>
    <?php if ( \Groundhogg\Plugin::$instance->preferences->is_gdpr_enabled() ): ?>
        <p style="display: none"><label><input type="checkbox" name="delete_everything" value="yes" class="preference-gdpr-delete">&nbsp;<?php _e( 'Request all information on record be removed.', 'groundhogg' ); ?></label></p>
    <?php endif; ?>
    <p>
        <input id="submit" type="submit" value="<?php esc_attr_e( 'Save Changes', 'groundhogg' ); ?>">
    </p>
</form>
    <script>
        jQuery(function ($) {
           var $preferences = $( 'input[name="preference"]' ).change( function () {
               $preferences.closest( 'li' ).removeClass( 'checked' );
               if ( $( this ).is( ':checked' ) ){
                   $( this ).closest( 'li' ).addClass( 'checked' );
               }
           });
        });
    </script>
    <?php


        manage_preferences_footer();

        break;
    case 'unsubscribe':

        manage_preferences_head( __( 'Unsubscribed', 'groundhogg' ), 'unsubscribe' );

        ?>
    <div class="box">
        <p><b><?php printf( __( 'Your email address %s has just been unsubscribed.', 'groundhogg' ), obfuscate_email( $contact->get_email() ) )?></b></p>
        <p><?php _e( 'Further interactions with our site may be interpreted as re-subscribing to our list and will result in further electronic communication.' ); ?></p>
    </div>
<?php
        manage_preferences_footer();

        break;
    case 'confirm':

        manage_preferences_head( __( 'Confirmed', 'groundhogg' ), 'confirm' );

        ?>
    <div class="box">
        <p><b><?php printf( __( 'Your email address %s has just been confirmed!', 'groundhogg' ), obfuscate_email( $contact->get_email() ) )?></b></p>
        <p><?php printf( __( 'You will now receive electronic communication from %1$s. Should you wish to change your communication preferences you may do so at any time by clicking the <b>Manage Preferences</b> button in the footer of any email sent by %1$s.' ), get_bloginfo( 'title', 'display' ) ); ?></p>
    </div>
<?php

        manage_preferences_footer();

        break;

endswitch;
