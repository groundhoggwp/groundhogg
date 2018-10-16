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
include_once( 'groundhogg.php' );

global $wpdb, $wp_roles;

if( get_option( 'gh_uninstall_on_delete', false ) ) {

    /** Delete the Plugin Pages */
    $wpgh_created_pages = array( 'gh_confirmation_page', 'gh_unsubscribe_page', 'gh_email_preferences_page' );
    foreach ( $wpgh_created_pages as $p ) {
        $page = get_option( $p, false );
        if ( $page ) {
            wp_delete_post( $page, true );
        }
        delete_option( $p );
    }

    /** Delete all the Plugin Options */
    delete_option( 'wpgh_settings' );
    delete_option( 'wpgh_version' );

    /* delete permissions */
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

    foreach ( $gh_all_caps as $cap ) {
        $role->remove_cap( $cap );
    }

    // Delete the databases
    @WPGH()->activity->drop();
    @WPGH()->broadcasts->drop();

    @WPGH()->contacts->drop();
    @WPGH()->contact_meta->drop();

    @WPGH()->emails->drop();
    @WPGH()->email_meta->drop();

    @WPGH()->events->drop();

    @WPGH()->steps->drop();
    @WPGH()->step_meta->drop();

    @WPGH()->funnels->drop();
    @WPGH()->superlinks->drop();

    @WPGH()->tags->drop();
    @WPGH()->tag_relationships->drop();

    /** Cleanup Cron Events */
    wp_clear_scheduled_hook( 'wpgh_cron_event' );

    // Remove any transients we've left behind
    $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_wpgh\_%'" );
    $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_site\_transient\_wpgh\_%'" );
    $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_timeout\_wpgh\_%'" );
    $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_site\_transient\_timeout\_wpgh\_%'" );
}
