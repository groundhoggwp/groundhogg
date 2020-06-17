<?php
namespace Groundhogg;

use Groundhogg\Queue\Event_Queue;

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
include_once __DIR__ . '/groundhogg.php';

global $wpdb;

if( Plugin::$instance->settings->is_option_enabled( 'gh_uninstall_on_delete' ) ) {

    //Delete DBS
    Plugin::$instance->dbs->drop_dbs();

    $other_tables = [
        'gh_contractmeta',
        'gh_contracts',
        'gh_dealmeta',
        'gh_deals',
        'gh_pipelines_stages',
        'gh_pipelines',
        'gh_proof',
        'gh_calendarmeta',
        'gh_calendar',
        'gh_appointmentmeta',
        'gh_appointments'

    ];

    foreach ( $other_tables as $table ){
        $table_name = $wpdb->prefix . $table;
        $wpdb->query( "DROP TABLE IF EXISTS " .$table_name );
    }

    //Remove Roles & Caps
    Plugin::$instance->roles->remove_roles_and_caps();

    //Remove all files
    Plugin::$instance->utils->files->delete_all_files();

    /** Cleanup Cron Events */
    wp_clear_scheduled_hook( Event_Queue::WP_CRON_HOOK );
    wp_clear_scheduled_hook( Bounce_Checker::ACTION );
    wp_clear_scheduled_hook( Stats_Collection::ACTION );
    wp_clear_scheduled_hook( 'groundhogg/sending_service/verify_domain' );

    //delete api keys from user_meta
    delete_metadata('user',0,'wpgh_user_public_key','',true);
    delete_metadata('user',0,'wpgh_user_secret_key','',true);

    global $wpdb;

    // Remove any transients and options we've left behind
    $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'gh\_%'" );
    $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'wpgh\_%'" );
    $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_wpgh\_%'" );
    $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_gh\_%'" );
    $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_site\_transient\_wpgh\_%'" );
    $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_site\_transient\_gh\_%'" );
    $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_timeout\_wpgh\_%'" );
    $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_timeout\_gh\_%'" );
    $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_site\_transient\_timeout\_wpgh\_%'" );
    $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_site\_transient\_timeout\_gh\_%'" );

    if ( ob_get_contents() ){
        file_put_contents( __DIR__ . '/../groundhogg-uninstall-errors.txt', ob_get_contents() );
    }

}