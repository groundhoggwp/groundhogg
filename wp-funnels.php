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

define ( 'WPFN_ASSETS_FOLDER', plugins_url( 'assets', __FILE__ ) );
define ( 'WPFN_INCLUDES_FOLDER', dirname( __FILE__ ) . '/includes/' );

//include dirname( __FILE__ ) . '/includes/admin/settings/settings.php';

foreach ( glob( dirname( __FILE__ ) . "/includes/*.php" ) as $filename )
{
	include $filename;
}

foreach ( glob( dirname( __FILE__ ) . "/includes/db/*.php" ) as $filename )
{
    include $filename;
}

/* Init groundhogg tables and options. */
function wpfn_activation()
{

    /* create tables */
	wpfn_create_contacts_db();
	wpfn_create_contact_meta_db();

	wpfn_create_emails_db();
	wpfn_create_email_meta_db();

	wpfn_create_events_db();

	wpfn_create_funnels_db();
	wpfn_create_funnel_meta_db();

	wpfn_create_funnelsteps_db();
	wpfn_create_funnelstep_meta_db();

	wpfn_create_contact_tags_db();
	wpfn_create_contact_tag_relationships_db();

	wpfn_create_superlinks_db();

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
}

register_activation_hook( __FILE__, 'wpfn_activation');