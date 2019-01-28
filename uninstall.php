<?php
/**
 * Uninstall Groundhogg
 *
 * Deletes all the plugin data i.e.
 * 		1. Custom Post types.
 * 		2. Terms & Taxonomies.
 * 		3. Plugin pages.
 * 		4. Plugin options.
 * 		5. Capabilities.
 * 		6. Roles.
 * 		7. Database tables.
 * 		8. Cron events.
 *
 * @package     WPGH
 * @subpackage  Uninstall
 * @copyright   Copyright (c) 2015, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.4.3
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

// Load WPGH file.
include_once dirname( __FILE__ ) . '/groundhogg.php' ;

global $wpdb, $wp_roles;

if( wpgh_is_option_enabled( 'gh_uninstall_on_delete' ) ) {

    /** Delete the Plugin Pages */
    $wpgh_created_pages = array( 'gh_confirmation_page', 'gh_unsubscribe_page', 'gh_email_preferences_page' );
    foreach ( $wpgh_created_pages as $p ) {
        $page = wpgh_get_option( $p, false );
        if ( $page ) {
            wp_delete_post( $page, true );
        }
        delete_option( $p );
    }

    /** Delete all the Plugin Options */
    delete_option( 'wpgh_settings' );
    delete_option( 'wpgh_version' );

    /* delete permissions */
    WPGH()->roles->remove_caps();
    WPGH()->roles->remove_roles();

    // Delete the databases
    WPGH()->activity->drop();
    WPGH()->broadcasts->drop();

    WPGH()->contacts->drop();
    WPGH()->contact_meta->drop();

    WPGH()->emails->drop();
    WPGH()->email_meta->drop();

    WPGH()->events->drop();

    WPGH()->steps->drop();
    WPGH()->step_meta->drop();

    WPGH()->funnels->drop();
    WPGH()->superlinks->drop();

    WPGH()->tags->drop();
    WPGH()->tag_relationships->drop();

    WPGH()->tokens->drop();

    /** Cleanup Cron Events */
    wp_clear_scheduled_hook( 'wpgh_process_queue' );
    wp_clear_scheduled_hook( 'wpgh_check_bounces' );

    //delete api keys from user_meta
    delete_metadata('user',0,'wpgh_user_public_key','',true);
    delete_metadata('user',0,'wpgh_user_secret_key','',true);

    // Remove any transients we've left behind
    $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_wpgh\_%'" );
    $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_site\_transient\_wpgh\_%'" );
    $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_timeout\_wpgh\_%'" );
    $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_site\_transient\_timeout\_wpgh\_%'" );

    file_put_contents( __DIR__ . '/../uninstall-errors.txt', ob_get_contents() );

}
