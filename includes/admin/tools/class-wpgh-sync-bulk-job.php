<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-04-04
 * Time: 3:22 PM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WPGH_Bulk_Job' ) ){
    include WPGH_PLUGIN_DIR. 'includes/class-wpgh-bulk-job.php';
}

class WPGH_Sync_Bulk_Job extends WPGH_Bulk_Job
{

    /**
     * Get the action reference.
     *
     * @return string
     */
    function get_action(){
        return 'gh_sync_users';
    }

    /**
     * Get an array of items someway somehow
     *
     * @param $items array
     * @return array
     */
    public function query( $items )
    {
        if ( ! current_user_can( 'add_contacts' ) ){
            return $items;
        }

        /* convert users to contacts */
        $args = array(
            'fields' => 'all_with_meta'
        );

        $users = get_users( $args );

        $uids = [];

        /* @var $wp_user WP_User */
        foreach ( $users as $wp_user ) {
            $uids[] = $wp_user->ID;
        }

        return $uids;
    }

    /**
     * Get the maximum number of items which can be processed at a time.
     *
     * @param $max int
     * @param $items array
     * @return int
     */
    public function max_items($max, $items)
    {
        if ( ! current_user_can( 'add_contacts' ) ){
            return $max;
        }

        return min( 100, intval( ini_get( 'max_input_vars' ) ) ) ;
    }

    /**
     * Process an item
     *
     * @param $item mixed
     * @return void
     */
    protected function process_item( $item )
    {
        if ( ! current_user_can( 'add_contacts' ) ){
            return;
        }

        wpgh_create_contact_from_user( absint( $item ) );
    }

    /**
     * Do stuff before the loop
     *
     * @return void
     */
    protected function pre_loop(){}

    /**
     * do stuff after the loop
     *
     * @return void
     */
    protected function post_loop(){}

    /**
     * Cleanup any options/transients/notices after the bulk job has been processed.
     *
     * @return void
     */
    protected function clean_up(){}

    /**
     * Get the return URL
     *
     * @return string
     */
    protected function get_return_url()
    {
        return admin_url( 'admin.php?page=gh_contacts' );
    }
}