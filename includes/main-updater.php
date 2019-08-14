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

        flush_rewrite_rules();

        update_option( 'gh_migrate_form_impressions', 1 );
    }

}