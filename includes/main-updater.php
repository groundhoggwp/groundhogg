<?php
namespace Groundhogg;

use Groundhogg\Bulk_Jobs\Manager;
use Groundhogg\DB\Activity;

/**
 * Upgrade
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 1.0.16
 */

class Main_Updater extends Updater {

    /**
     * A unique name for the updater to avoid conflicts
     *
     * @return string
     */
    protected function get_updater_name()
    {
        return 'main';
    }

    /**
     * Get a list of updates which are available.
     *
     * @return string[]
     */
    protected function get_available_updates()
    {
        return [
            '2.0',
            '2.0.7',
            '2.0.7.1',
            '2.0.8',
            '2.0.8.1',
            '2.0.9.2',
        ];
    }

    /**
     * Update to 2.0
     *
     * 1. Convert any options.
     * 2. Add new rewrite rules for iframe, forms, email preferences, unsubscribe, email confirmed.
     * 3. Move from impressions to new another DB.
     */
    public function version_2_0()
    {
        $privacy_policy_id = get_option( 'gh_privacy_policy' );

        if ( $privacy_policy_id ){
            $privacy_policy_link = get_permalink( absint( $privacy_policy_id ) );
            update_option( 'gh_privacy_policy', $privacy_policy_link );
        }

        $terms_id = get_option( 'gh_terms' );

        if ( $terms_id ){
            $terms_link = get_permalink( absint( $terms_id ) );
            update_option( 'gh_terms', $terms_link );
        }

        // Give the DBS a quick update...
	    Plugin::$instance->dbs->install_dbs();

        wp_clear_scheduled_hook( 'wpgh_process_queue' );

        update_option( 'gh_migrate_form_impressions', 1 );

        Plugin::$instance->utils->files->add_htaccess();

        install_custom_rewrites();

        set_transient( 'groundhogg_upgrade_notice_request_active', 1, WEEK_IN_SECONDS );
    }

    /**
     * Update the rewrites to support new file access links.
     */
    public function version_2_0_7()
    {
        install_custom_rewrites();
    }

    /**
     * Fix add .htaccess issue.
     */
    public function version_2_0_7_1()
    {
        Plugin::$instance->utils->files->mk_dir();
    }

    /**
     * Index the date_created column
     */
    public function version_2_0_8()
    {

        global $wpdb;

        $db = get_db( 'contacts' );

        $wpdb->query( "CREATE INDEX date_created ON {$db->get_table_name()}(date_created)" );

    }

	/**
	 * Fix tracking link bug. url encoding the string causes 404 error.
	 */
    public function version_2_0_8_1()
    {
    	install_custom_rewrites();
    }

    /**
	 * Attempt more compatibility with the rewrites.
     *
	 */
    public function version_2_0_9_2()
    {
    	install_custom_rewrites();
    }


}