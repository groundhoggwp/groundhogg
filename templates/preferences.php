<?php
namespace Groundhogg;

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
        if ( ! is_email( $email ) ){
            return false;
        }

        $parts = explode( '@', $email );

        $parts[ 0 ] = preg_replace( '/(?!^).(?=.+$)/', '*', $parts[ 0 ] );
        $parts[ 1 ] = preg_replace( '/(?!^).(?=.+\.)/', '*', $parts[ 1 ] );
        $email = implode( '@', $parts );

        return $email;
    }

endif;

if ( ! function_exists( 'mail_gdpr_data' ) ){

    /**
     * Mail the contact profile to the contact which requested it.
     * Uses the regular wp_mail function.
     *
     * @param $contact_id
     * @return bool
     */
    function mail_gdpr_data( $contact_id )
    {

        $contact = Plugin::$instance->utils->get_contact( $contact_id );

//        if ( ! $contact ){
//            return false;
//        }

        // 2D array

        $message = __( "You are receiving this message because you have requested an audit of your personal information. This message contains all current information about your contact profile.\n" );

        $contact_data = apply_filters( 'groundhogg/preferences/contact_data', $contact->get_as_array() );

        // Basic Information
        $message .= sprintf( "\n======== %s =========\n", __( 'Basic Information', 'groundhogg' ) );

        foreach ( $contact_data[ 'data' ] as $key => $contact_datum ){
            $message .= sprintf( "%s: %s\n", key_to_words( $key ),$contact_datum );
        }

        // Custom Information
        if ( isset_not_empty( $contact_data, 'meta' ) ){
            $message .= sprintf( "\n======== %s =========\n", __( 'Other Information', 'groundhogg' ) );
            foreach ( $contact_data[ 'meta' ] as $key => $contact_datum ){
                $message .= sprintf( "%s: %s\n", key_to_words( $key ),$contact_datum );
            }
        }

        // Custom Information
        if ( isset_not_empty( $contact_data, 'tags' ) ){
            $message .= sprintf( "\n======== %s =========\n", __( 'Profile Tags', 'groundhogg' ) );

            $tag_names = [];

            foreach ( $contact_data[ 'tags' ] as $tag_id ){
                $tag_names[] = Plugin::$instance->dbs->get_db( 'tags' )->get_column_by( 'tag_name', 'tag_id', $tag_id );
            }

            $message .= sprintf( "%s\n", implode( ', ', $tag_names ) );
        }

        // Files
        if ( isset_not_empty( $contact_data, 'files' ) ){
            $message .= sprintf( "\n======== %s =========\n", __( 'Files', 'groundhogg' ) );

            foreach ( $contact_data[ 'files' ] as $file_data ){
                $message .= sprintf( "%s: %s\n", $file_data[ 'file_name' ] , $file_data[ 'file_url' ] );
            }
        }

        return wp_mail( $contact->get_email(), sprintf( __( 'Your personal profile audit with %s', 'groundhogg' ), get_bloginfo( 'title' ) ), esc_html( $message ) );
    }

}

/**
 * Enqueue and dequeue relevant scripts.
 */
function enqueue_manage_preferences_styles()
{
    dequeue_theme_css_compat();
    dequeue_wc_css_compat();

    wp_enqueue_style( 'manage-preferences' );
    wp_enqueue_style( 'common' );

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
    add_action( 'wp_print_styles', 'Groundhogg\enqueue_manage_preferences_styles' );
    add_action( 'wp_enqueue_scripts', 'Groundhogg\enqueue_manage_preferences_scripts' );

    add_action( 'wp_head', 'noindex' );
    add_action( 'wp_head', 'wp_sensitive_page_meta' );
    add_action( 'wp_head', 'Groundhogg\ensure_logo_is_there' );

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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <title><?php echo $mp_title; ?></title>

    <?php wp_head(); ?>
</head>
<body class="manage-preferences <?php echo esc_attr( implode( ' ', $classes ) ); ?>">
<div id="main">
    <?php if ( has_custom_logo() ): ?>
    <h1><a href="<?php echo esc_url( $header_url ); ?>" title="<?php echo esc_attr( $header_title ); ?>"><?php echo $header_text; ?></a></h1>
    <?php endif;

    Plugin::$instance->notices->notices();
    ?>
    <div id="content">
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

$contact = Plugin::$instance->tracking->get_current_contact();
$action = get_query_var( 'action', 'profile' );

// Compat for erase action which will be true because there will be no contact.
if ( ! $contact && $action !== 'erase' ){
    $action = 'no_email';
}

switch ( $action ):

    default:
    case 'no_email':

        if ( wp_verify_nonce( get_request_var( '_wpnonce' ), 'identify_yourself' ) ) {

            $email = sanitize_email(get_request_var('email'));

            if ( is_email( $email ) ){
                $contact = Plugin::$instance->utils->get_contact( $email );

                if ( $contact ){
                    // Start tracking this contact
                    Plugin::$instance->tracking->start_tracking( $contact );
                    die( wp_redirect( site_url( 'gh/preferences/profile' ) ) );
                }
            }
        }

        manage_preferences_head( __( 'Manage Preferences', 'groundhogg' ), 'manage' );

        ?>
        <form action="" id="emailaddress" method="post">
            <?php wp_nonce_field( 'identify_yourself' ); ?>
            <p><?php _e( 'Please enter your email address to manage your preferences.', 'groundhogg' ); ?></p>
            <p><input type="email" name="email" id="email" placeholder="<?php esc_attr_e( "your.name@domain.com", 'groundhogg' ); ?>" required></p>
            <p>
                <input id="submit" type="submit" class="button" value="<?php esc_attr_e( 'Submit', 'groundhogg' ); ?>">
            </p>
        </form>
        <?php

        manage_preferences_footer();

    break;
    case 'download':

        if ( wp_verify_nonce( get_request_var( '_wpnonce' ), 'download_profile' ) ){
            if ( mail_gdpr_data( $contact->get_id() ) ){
                Plugin::$instance->notices->add( 'sent', __( 'Profile information sent to your inbox!', 'groundhogg' ) );

                /**
                 * After the request is made to download the profile
                 *
                 * @param $contact Contact
                 */
                do_action( 'groundhogg/preferences/download_profile', $contact );

            } else {
                Plugin::$instance->notices->add( new \WP_Error( 'failed', __( 'Something went wrong sending your email.', 'groundhogg' ) ) );
            }

        }

        wp_redirect( site_url( 'gh/preferences/profile/' ) );
        die();

    case 'profile':

        if ( wp_verify_nonce( get_request_var( '_wpnonce' ), 'update_contact_profile' ) ){

            $email = sanitize_email( get_request_var( 'email' ) );
            if ( ! $email ){
                Plugin::$instance->notices->add( new \WP_Error( 'bad_email', __( 'You must verify your email address.', 'groundhogg' ) ) );
            }

            $args = [
                'first_name' => sanitize_text_field( get_request_var( 'first_name' ) ),
                'last_name' =>  sanitize_text_field( get_request_var( 'last_name' ) ),
                'email' => sanitize_email( get_request_var( 'email' ) ) ,
            ];

            $args = apply_filters( 'groundhogg/preferences/update_profile', $args, $contact );

            if ( $contact->update( $args ) ){
                Plugin::$instance->notices->add( 'updated', __( 'Profile updated!', 'groundhogg' ) );
            }

            apply_filters( 'groundhogg/preferences/update_profile', $args, $contact );
        }

        manage_preferences_head( __( 'Update Profile', 'groundhogg' ), 'profile' );

        ?>
        <form action="" id="preferences" method="post">
            <p><b><?php printf( __( 'Update information for %s (%s).', 'groundhogg' ), $contact->get_full_name(), obfuscate_email( $contact->get_email() ) )?></b></p>
            <p><?php _e( 'Use the form below to update your information to the most current.', 'groundhogg' ) ?></p>
            <?php wp_nonce_field( 'update_contact_profile' ); ?>
            <p>
                <label><?php _e( 'First Name', 'groundhogg' ); ?>
                    <input type="text" name="first_name" required>
                </label>
            </p>
            <p>
                <label><?php _e( 'Last Name', 'groundhogg' ); ?>
                    <input type="text" name="last_name" required>
                </label>
            </p>
            <p>
                <label><?php _e( 'Confirm Email Address', 'groundhogg' ); ?>
                    <input type="email" name="email" required>
                </label>
            </p>
            <?php do_action( 'groundhogg/preferences/profile_form' ); ?>
            <p>
                <input id="submit" type="submit" class="button" value="<?php esc_attr_e( 'Save Changes', 'groundhogg' ); ?>">
            </p>
        </form>
        <div class="box">
            <p><?php _e( 'Click below to manage your communication preferences and determine when and how you would like to receive communication from us.', 'groundhogg' ) ?></p>
            <p>
                <a id="gotopreferences" class="button" href="<?php echo esc_url( site_url( 'gh/preferences/manage' ) ); ?>"><?php _e( 'Change Email Preferences', 'groundhogg' ) ?></a>
            </p>
        </div>
    <?php if ( Plugin::$instance->preferences->is_gdpr_enabled() ): ?>
        <div class="box">
            <p><?php _e( 'Click below to email yourself an audit of all personal information currently on file. Or if you wish for us to no longer have access to this information you can request a data erasure in accordance with your privacy rights.', 'groundhogg' ) ?></p>
            <p>
                <a id="downloadprofile" class="button" href="<?php echo esc_url( wp_nonce_url( site_url( 'gh/preferences/download' ), 'download_profile' ) ); ?>"><?php _e( 'Download Profile', 'groundhogg' ) ?></a>
                <a id="eraseprofile" class="button right" href="<?php echo esc_url( wp_nonce_url( site_url( 'gh/preferences/erase' ), 'erase_profile' ) ); ?>"><?php _e( 'Erase Profile', 'groundhogg' ) ?></a>
            </p>
        </div>
    <?php endif; ?>
    <?php do_action( 'groundhogg/preferences/profile_form/after' ); ?>
        <?php

        manage_preferences_footer();
        break;

    case 'manage':

        if ( wp_verify_nonce( get_request_var( '_wpnonce' ), 'manage_email_preferences' ) ){

            $preference = get_request_var( 'preference' );

            switch ( $preference ){
                case 'unsubscribe':
                    wp_redirect( wp_nonce_url( site_url( 'gh/preferences/unsubscribe' ), 'unsubscribe' ) );
                    die();
                    break;
                case 'confirm':
                    wp_redirect( wp_nonce_url( site_url( 'gh/preferences/confirm' ) ) );
                    die();
                    break;
                case 'weekly':
                    $contact->change_marketing_preference( Preferences::WEEKLY );
                    break;
                case 'monthly':
                    $contact->change_marketing_preference( Preferences::MONTHLY );
                    break;
            }

            Plugin::$instance->notices->add( 'updated', __( 'Preferences saved!' ) );

            wp_redirect( site_url( 'gh/preferences/profile' ) );
            die();

        }

        manage_preferences_head( __( 'Manage Preferences', 'groundhogg' ), 'manage' );

        $preferences = [
            'confirm'       => _x( 'I love this company, you can communicate with me whenever you feel like.', 'preferences', 'groundhogg' ),
            'weekly'        => _x( "It's getting a bit much. Communicate with me weekly.", 'preferences', 'groundhogg' ),
            'monthly'       => _x( 'Distance makes the heart grow fonder. Communicate with me monthly.', 'preferences', 'groundhogg' ),
            'unsubscribe'   => _x( 'I no longer wish to receive any form of communication. Unsubscribe me!', 'preferences', 'groundhogg' )
        ];

        if ( Plugin::$instance->preferences->is_gdpr_enabled() ){
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
    <?php if ( Plugin::$instance->preferences->is_gdpr_enabled() ): ?>
        <p style="display: none"><label><input type="checkbox" name="delete_everything" value="yes" class="preference-gdpr-delete">&nbsp;<?php _e( 'Request all information on record be removed.', 'groundhogg' ); ?></label></p>
    <?php endif; ?>
    <p>
        <input class="button" id="submit" type="submit" value="<?php esc_attr_e( 'Save Changes', 'groundhogg' ); ?>">
        <a id="gotoprofile" class="button right" href="<?php echo esc_url( site_url( 'gh/preferences/profile' ) ); ?>"><?php _e( 'Cancel' ) ?></a>
    </p>
</form>
    <?php


        manage_preferences_footer();

        break;
    case 'unsubscribe':

        if ( ! wp_verify_nonce( get_request_var( '_wpnonce' ), 'unsubscribe' ) ) {
            wp_redirect( site_url( 'gh/preferences/manage' ) );
        }

        $contact->unsubscribe();

        manage_preferences_head( __( 'Unsubscribed', 'groundhogg' ), 'unsubscribe' );

        ?>
    <div class="box">
        <p><b><?php printf( __( 'Your email address %s has just been unsubscribed.', 'groundhogg' ), obfuscate_email( $contact->get_email() ) )?></b></p>
        <p><?php _e( 'Further interactions with our site may be interpreted as re-subscribing to our list and will result in further electronic communication.' ); ?></p>
        <p>
            <a id="gotosite" class="button" href="<?php echo esc_url( site_url() ); ?>"><?php printf( __( 'Return to %s', 'groundhogg' ), get_bloginfo( 'title', 'display' ) ); ?></a>
        </p>
    </div>
<?php
        manage_preferences_footer();

        break;
    case 'confirm':

        if ( ! wp_verify_nonce( get_request_var( '_wpnonce' ) ) ) {
            wp_redirect( site_url( 'gh/preferences/manage' ) );
        }

        $contact->change_marketing_preference( Preferences::CONFIRMED );

        manage_preferences_head( __( 'Confirmed', 'groundhogg' ), 'confirm' );

        ?>
    <div class="box">
        <p><b><?php printf( __( 'Your email address %s has just been confirmed!', 'groundhogg' ), obfuscate_email( $contact->get_email() ) )?></b></p>
        <p><?php printf( __( 'You will now receive electronic communication from %1$s. Should you wish to change your communication preferences you may do so at any time by clicking the <b>Manage Preferences</b> link or <b>Unsubscribe</b> link in the footer of any email sent by %1$s.' ), get_bloginfo( 'title', 'display' ) ); ?></p>
        <p>
            <a id="gotosite" class="button" href="<?php echo esc_url( site_url() ); ?>"><?php printf( __( 'Return to %s', 'groundhogg' ), get_bloginfo( 'title', 'display' ) ); ?></a>
        </p>
    </div>
<?php

        manage_preferences_footer();

        break;

    case 'erase':

        if ( ! wp_verify_nonce( get_request_var( '_wpnonce' ), 'erase_profile' ) ){
            wp_redirect( site_url( 'gh/preferences/profile/' ) );
            die();
        }

        /**
         * Before the request is made to erase the profile
         *
         * @param $contact Contact
         */
        do_action( 'groundhogg/preferences/erase_profile', $contact );


        // Todo Erase profile data...

        manage_preferences_head( __( 'Erased', 'groundhogg' ), 'erase' );

        ?>
        <div class="box">
            <p><b><?php _e( 'Your data has been erased!', 'groundhogg' ); ?></b></p>
            <p><?php _e( 'Further interactions with our site may be interpreted as re-subscribing to our list and will result in further electronic communication.' ); ?></p>
            <p>
                <a id="gotosite" class="button" href="<?php echo esc_url( site_url() ); ?>"><?php printf( __( 'Return to %s', 'groundhogg' ), get_bloginfo( 'title', 'display' ) ); ?></a>
            </p>
        </div>
        <?php
        manage_preferences_footer();
        break;
endswitch;
