<?php
/*
Plugin Name: Groundhogg
Plugin URI: https://wordpress.org/plugins/groundhogg/
Description: CRM and marketing automation for WordPress
Version: 0.1.0
Author: Adrian Tobey
Author URI: http://health-check-team.example.com
Text Domain: groundhogg
Domain Path: /languages
*/

define( 'WPGH_ASSETS_FOLDER', plugins_url( 'assets', __FILE__ ) );
define( 'WPGH_INCLUDES_FOLDER', dirname( __FILE__ ) . '/includes/' );

define( 'WPGH_ID', 292 );
define( 'WPGH_NAME', 'groundhogg' );
define( 'WPGH_VERSION', '0.1' );

foreach ( glob( dirname( __FILE__ ) . "/includes/*.php" ) as $filename )
{
	include $filename;
}

foreach ( glob( dirname( __FILE__ ) . "/includes/db/*.php" ) as $filename )
{
    include $filename;
}

/* Init groundhogg tables and options. */
function wpgh_activation()
{

    /* create tables */
	wpgh_create_contacts_db();
	wpgh_create_contact_meta_db();

	wpgh_create_contact_tags_db();
    wpgh_create_contact_tag_relationships_db();

	wpgh_create_emails_db();
    wpgh_create_email_meta_db();

    wpgh_create_broadcasts_db();

    wpgh_create_events_db();

	wpgh_create_funnels_db();
	wpgh_create_funnel_meta_db();

	wpgh_create_funnelsteps_db();
	wpgh_create_funnelstep_meta_db();

	wpgh_create_superlinks_db();

	wpgh_create_activity_db();

	/* create endpoints */
	/* confirmation page */
	if ( ! get_option( 'gh_confirmation_page', false ) ){
        $confirmation_args = array(
            'post_title' => __( 'Email Confirmed', 'groundhogg' ),
            'post_content' => __( '<h2>Your email [gh_contact field="email"] has been confirmed.</h2><p>Thank you! Return to your inbox to receive further communication.</p>', 'groundhogg' ),
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        );
        $id = wp_insert_post( $confirmation_args );
        update_option( 'gh_confirmation_page', $id );
    }

    /* unbsubscribed page */
    if ( ! get_option( 'gh_unsubscribe_page', false ) ){
        $unsubscribed_args = array(
            'post_title' => __( 'Unsubscribed', 'groundhogg' ),
            'post_content' => __( '<h2>Your email [gh_contact field="email"] has been unsubscribed.</h2><p>This means you will not receive any further marketing communication from us, but you may receive transactional emails related to billing.</p><p>Note that opting in again to any optin form or program on our site is implied consent and may result in starting to receive email communication again.</p>', 'groundhogg' ),
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        );
        $id = wp_insert_post( $unsubscribed_args );
        update_option( 'gh_unsubscribe_page', $id );
    }

    /* email preferences page */
    if ( ! get_option( 'gh_email_preferences_page', false ) ){
        $email_preferences_args = array(
            'post_title' => __( 'Email Preferences', 'groundhogg' ),
            'post_content' => __( '<h2>Manage your email preferences!</h2><p>Use the form below to manage your email preferences.</p><p>[gh_email_preferences]</p>', 'groundhogg' ),
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        );
        $id = wp_insert_post( $email_preferences_args );
        update_option( 'gh_email_preferences_page', $id );
    }

    /* convert users to contacts */
    $args = array(
        'fields' => 'all_with_meta'
    );

    $users = get_users( $args );

    /* @var $wp_user WP_User */
    foreach ( $users as $wp_user )
    {
        $cid = wpgh_quick_add_contact( $wp_user->user_email, $wp_user->user_firstname, $wp_user->user_lastname );
        //todo log how created.
    }

    /* setup permissions */

	$gh_all_caps = array(
		'gh_manage_contacts',
		'gh_manage_funnels',
		'gh_manage_emails',
		'gh_manage_tags',
		'gh_manage_broadcasts',
		'gh_manage_superlinks',
		'gh_manage_events',
		'gh_manage_settings'
	);

	$role = get_role( 'administrator' );

	foreach ( $gh_all_caps as $cap )
	{
		$role->add_cap( $cap );
	}
}

register_activation_hook( __FILE__, 'wpgh_activation');

function wpgh_register_scripts()
{
    wp_register_style( 'select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/css/select2.min.css' );
    wp_register_script( 'select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/js/select2.min.js', array( 'jquery' ) );
}

add_action( 'admin_enqueue_scripts', 'wpgh_register_scripts' );

function wpgh_remove_footer_admin ( $text )
{
    return preg_replace( "/<\/span>/", sprintf( __( ' | Find a bug in Groundhogg? <a target="_blank" href="%s">Report It</a>!</span>' ), __( 'https://www.facebook.com/groups/274900800010203/' ) ), $text );
}

add_filter('admin_footer_text', 'wpgh_remove_footer_admin');

do_action( 'groundhogg_loaded' );