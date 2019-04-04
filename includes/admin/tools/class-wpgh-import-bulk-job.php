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

class WPGH_Import_Bulk_Job extends WPGH_Bulk_Job
{

    protected $field_map = [];
    protected $import_tags = [];

    /**
     * Get the action reference.
     *
     * @return string
     */
    function get_action(){
        return 'gh_import_contacts';
    }

    /**
     * Get an array of items someway somehow
     *
     * @param $items array
     * @return array
     */
    public function query($items)
    {
        if ( ! current_user_can( 'import_contacts' ) ){
            return $items;
        }

        $file_name = urldecode( $_GET[ 'import' ] );
        $file_path = wp_normalize_path( wpgh_get_csv_imports_dir( $file_name ) );

        return wpgh_get_items_from_csv( $file_path );
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
        $item = array_shift( $items );
        $fields = count( $item );
        $max = intval( ini_get( 'max_input_vars' ) );
        $max_items = floor( $max / $fields ) - 1;

        return min( $max_items, 100 );
    }

    /**
     * Process an item
     *
     * @param $item mixed
     * @return void
     */
    protected function process_item( $item )
    {
        $contact = wpgh_generate_contact_with_map( $item, $this->field_map );
        if ( $contact ) {
            $contact->apply_tag( $this->import_tags );
        }
    }

    /**
     * Do stuff before the loop
     *
     * @return void
     */
    protected function pre_loop()
    {
        $this->field_map    = wpgh_get_transient( 'gh_import_map' );
        $this->import_tags  = wp_parse_id_list( wpgh_get_transient( 'gh_import_tags' ) );
    }

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
    protected function clean_up()
    {
        wpgh_delete_transient( 'gh_import_map' );
        wpgh_delete_transient( 'gh_import_tags' );
    }

    /**
     * Get the return URL
     *
     * @return string
     */
    protected function get_return_url()
    {
        $url = admin_url( 'admin.php?page=gh_contacts' );

        // Return to guided setup if it's not yet complete.
        if ( ! wpgh_get_option( 'gh_guided_setup_finished', false ) ){
            $url = admin_url( 'admin.php?page=gh_guided_setup&step=5' );
        }

        return $url;
    }
}