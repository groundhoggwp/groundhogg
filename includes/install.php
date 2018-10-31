<?php
/**
 * Install
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Install
 *
 * Runs on plugin install by setting up the post types, custom taxonomies,
 * flushing rewrite rules to initiate the new 'downloads' slug and also
 * creates the plugin and populates the settings fields for those plugin
 * pages. After successful install, the user is redirected to the WPGH Welcome
 * screen.
 *
 * @since 1.0
 * @global $wpdb
 * @global $wpgh_options
 * @param  bool $network_wide If the plugin is being network-activated
 * @return void
 */
function wpgh_install( $network_wide = false ) {
    global $wpdb;

    if ( is_multisite() && $network_wide ) {

        foreach ( $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs LIMIT 100" ) as $blog_id ) {

            switch_to_blog( $blog_id );
            wpgh_run_install();
            restore_current_blog();

        }

    } else {

        wpgh_run_install();

    }

    file_put_contents( __DIR__ . '/my_log.html', ob_get_contents() );

}

register_activation_hook( WPGH_PLUGIN_FILE, 'wpgh_install' );


/**
 * Run the WPGH Install process
 *
 * @since  2.5
 * @return void
 */
function wpgh_run_install() {
    global $wpdb, $wpgh_options;

    // Add Upgraded From Option
    $current_version = wpgh_get_option( 'wpgh_version' );
    
    if ( $current_version ) {
        update_option( 'wpgh_version_upgraded_from', $current_version );
    }

    // Setup some default options
    $options = array();

    // Pull options from WP, not WPGH's global

    $current_options = wpgh_get_option( 'wpgh_settings', array() );

    // Create the databases
    @WPGH()->activity->create_table();
    @WPGH()->broadcasts->create_table();

    @WPGH()->contacts->create_table();
    @WPGH()->contact_meta->create_table();

    @WPGH()->emails->create_table();
    @WPGH()->email_meta->create_table();

    @WPGH()->events->create_table();

    @WPGH()->steps->create_table();
    @WPGH()->step_meta->create_table();

    @WPGH()->funnels->create_table();
    @WPGH()->superlinks->create_table();

    @WPGH()->tags->create_table();
    @WPGH()->tag_relationships->create_table();

    /* Setup the cron event */
    @WPGH()->event_queue->setup_cron_jobs();

    /* convert users to contacts */
    $args = array(
        'fields' => 'all_with_meta'
    );

    $users = get_users( $args );

    /* @var $wp_user WP_User */
    foreach ( $users as $wp_user ) {
        if ( ! WPGH()->contacts->exists( $wp_user->user_email, 'email' ) ){
            $cid = WPGH()->contacts->add( array(
                'first_name'    => $wp_user->first_name,
                'last_name'     => $wp_user->last_name,
                'email'         => $wp_user->user_email,
                'user_id'       => $wp_user->ID,
            ) );
        }
        //todo log how created.
    }

    /* Recount tag relationships */
    $tags = WPGH()->tags->get_tags();

    if ( ! empty( $tags ) ){
        foreach ( $tags as $tag ){
            $count = WPGH()->tag_relationships->count( $tag->tag_id, 'tag_id' );
            WPGH()->tags->update( $tag->tag_id, array( 'contact_count' => $count ) );
        }
    }

    $roles = new WPGH_Roles;
    $roles->add_roles();
    $roles->add_caps();

    if ( ! WPGH()->funnels->count() && is_admin() ){
        /* Install the email preferences center */
        include WPGH_PLUGIN_DIR . '/templates/funnel-templates.php';
        /* @var $funnel_templates array included from funnel-templates.php */
        $json = file_get_contents( $funnel_templates[ 'email_preferences' ]['file'] );
        $funnel_id = WPGH()->menu->funnels_page->import_funnel( json_decode( $json, true ) );
        WPGH()->funnels->update( $funnel_id, array( 'status' => 'active' ) );
        $forms = WPGH()->steps->get_steps( array( 'funnel_id' => $funnel_id, 'step_type' => 'form_fill' ) );
        $form = array_shift( $forms );
    }

    if ( ! wpgh_get_option( 'gh_confirmation_page', false ) ){
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
    if ( ! wpgh_get_option( 'gh_unsubscribe_page', false ) ){
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
    if ( ! wpgh_get_option( 'gh_email_preferences_page', false ) && isset( $form ) ){
        $email_preferences_args = array(
            'post_title' => __( 'Email Preferences', 'groundhogg' ),
            'post_content' => __( '<h2>Manage your email preferences!</h2><p>Use the form below to manage your email preferences.</p><p>[gh_form id="' . $form->ID . '" title="' . $form->step_title . '"]</p>', 'groundhogg' ),
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        );
        $id = wp_insert_post( $email_preferences_args );
        update_option( 'gh_email_preferences_page', $id );
    }

    update_option( 'wpgh_version', WPGH_VERSION );

    // Add a temporary option to note that WPGH pages have been created
    set_transient( '_wpgh_installed', true, 30 );

}

function wpgh_redirect_to_welcome( $plugin )
{
    if( $plugin == plugin_basename( WPGH_PLUGIN_FILE ) ) {
        exit( wp_redirect( admin_url( 'admin.php?page=groundhogg' ) ) );
    }
}

add_action( 'activated_plugin', 'wpgh_redirect_to_welcome' );


/**
 * When a new Blog is created in multisite, see if WPGH is network activated, and run the installer
 *
 * @since  2.5
 * @param  int    $blog_id The Blog ID created
 * @param  int    $user_id The User ID set as the admin
 * @param  string $domain  The URL
 * @param  string $path    Site Path
 * @param  int    $site_id The Site ID
 * @param  array  $meta    Blog Meta
 * @return void
 */
function wpgh_new_blog_created( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {

    if ( is_plugin_active_for_network( plugin_basename( WPGH_PLUGIN_FILE ) ) ) {

        switch_to_blog( $blog_id );
        wpgh_install();
        restore_current_blog();

    }

}

add_action( 'wpmu_new_blog', 'wpgh_new_blog_created', 10, 6 );


/**
 * Drop our custom tables when a mu site is deleted
 *
 * @since  2.5
 * @param  array $tables  The tables to drop
 * @param  int   $blog_id The Blog ID being deleted
 * @return array          The tables to drop
 */
function wpgh_wpmu_drop_tables( $tables, $blog_id ) {

    switch_to_blog( $blog_id );

    if ( WPGH()->contacts->installed() ) {
        $tables[] = WPGH()->contacts->table_name;
        $tables[] = WPGH()->contact_meta->table_name;
        $tables[] = WPGH()->emails->table_name;
        $tables[] = WPGH()->email_meta->table_name;
        $tables[] = WPGH()->broadcasts->table_name;
        $tables[] = WPGH()->funnels->table_name;
        $tables[] = WPGH()->superlinks->table_name;
        $tables[] = WPGH()->tags->table_name;
        $tables[] = WPGH()->tag_relationships->table_name;
        $tables[] = WPGH()->events->table_name;
        $tables[] = WPGH()->activity->table_name;
        $tables[] = WPGH()->steps->table_name;
        $tables[] = WPGH()->step_meta->table_name;
    }

    restore_current_blog();

    return $tables;

}

add_filter( 'wpmu_drop_tables', 'wpgh_wpmu_drop_tables', 10, 2 );

/**
 * Post-installation
 *
 * Runs just after plugin installation and exposes the
 * wpgh_after_install hook.
 *
 * @since 1.7
 * @return void
 */
function wpgh_after_install() {

    if ( ! is_admin() ) {
        return;
    }

    $wpgh_options     = get_transient( '_wpgh_installed' );
    $wpgh_table_check = wpgh_get_option( '_wpgh_table_check', false );

    if ( false === $wpgh_table_check || current_time( 'timestamp' ) > $wpgh_table_check ) {

        if ( ! @WPGH()->contacts->installed() ) {
            // Create the customers database (this ensures it creates it on multisite instances where it is network activated)

            // Create the databases
            @WPGH()->activity->create_table();
            @WPGH()->broadcasts->create_table();

            @WPGH()->contacts->create_table();
            @WPGH()->contact_meta->create_table();

            @WPGH()->emails->create_table();
            @WPGH()->email_meta->create_table();

            @WPGH()->events->create_table();

            @WPGH()->steps->create_table();
            @WPGH()->step_meta->create_table();

            @WPGH()->funnels->create_table();
            @WPGH()->superlinks->create_table();

            @WPGH()->tags->create_table();
            @WPGH()->tag_relationships->create_table();

            do_action( 'wpgh_after_install', $wpgh_options );
        }

        update_option( '_wpgh_table_check', ( current_time( 'timestamp' ) + WEEK_IN_SECONDS ) );

    }

    if ( false !== $wpgh_options ) {
        // Delete the transient
        delete_transient( '_wpgh_installed' );
    }


}

add_action( 'admin_init', 'wpgh_after_install' );

/**
 * Install user roles on sub-sites of a network
 *
 * Roles do not get created when WPGH is network activation so we need to create them during admin_init
 *
 * @since 1.9
 * @return void
 */
function wpgh_install_roles_on_network() {

    WP_Roles();

    global $wp_roles;

    if( ! is_object( $wp_roles ) ) {
        return;
    }


    if( empty( $wp_roles->roles ) || ! array_key_exists( 'sales_manager', $wp_roles->roles ) ) {

        $roles = new WPGH_Roles;
        $roles->add_roles();
        $roles->add_caps();

    }

}
add_action( 'admin_init', 'wpgh_install_roles_on_network' );
