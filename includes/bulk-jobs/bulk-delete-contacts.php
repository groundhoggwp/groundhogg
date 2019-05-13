<?php
namespace Groundhogg;

use Groundhogg\Bulk_Jobs\Bulk_Job;

if ( ! defined( 'ABSPATH' ) ) exit;

class Bulk_Delete_Contacts extends Bulk_Job
{

    /**
     * Get the action reference.
     *
     * @return string
     */
    function get_action(){
        return 'gh_delete_contacts';
    }

    /**
     * Get an array of items someway somehow
     *
     * @param $items array
     * @return array
     */
    public function query( $items )
    {
        if ( ! current_user_can( 'delete_contacts' ) ){
            return $items;
        }

        $query = new Contact_Query();
        $args = $_GET;

        $contacts = $query->query( $args );
        $ids = wp_list_pluck( $contacts, 'ID' );

        return $ids;
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
        if ( ! current_user_can( 'delete_contacts' ) ){
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
        Plugin::instance()->dbs->get_db('contacts')->delete( absint( $item ) );
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