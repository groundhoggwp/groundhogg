<?php
namespace Groundhogg\Bulk_Jobs;

use function Groundhogg\admin_page_url;
use function Groundhogg\get_items_from_csv;
use Groundhogg\Plugin;
use Groundhogg\Preferences;
use function Groundhogg\get_url_var;
use function Groundhogg\guided_setup_finished;

if ( ! defined( 'ABSPATH' ) ) exit;

class Import_Contacts extends Bulk_Job
{

    protected $field_map = [];
    protected $import_tags = [];
    protected $confirm_contacts = false;

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

        $file_name = sanitize_file_name( get_url_var( 'import' ) );
        $file_path = wp_normalize_path( Plugin::$instance->utils->files->get_csv_imports_dir( $file_name ) );

        return get_items_from_csv( $file_path );
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
        $fields = count( array_keys( $item ) );

        $max = intval( ini_get( 'max_input_vars' ) );
        $max_items = floor( $max / $fields );

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
        $contact = \Groundhogg\generate_contact_with_map( $item, $this->field_map );

        if ( $contact ) {
            $contact->apply_tag( $this->import_tags );

            if ( $this->confirm_contacts ){
                $contact->change_marketing_preference( Preferences::CONFIRMED );
            }
        }
    }

    /**
     * Do stuff before the loop
     *
     * @return void
     */
    protected function pre_loop()
    {
        $this->field_map    = Plugin::$instance->settings->get_transient( 'gh_import_map' );
        $this->import_tags  = wp_parse_id_list( Plugin::$instance->settings->get_transient( 'gh_import_tags' ) );
        $this->confirm_contacts = Plugin::$instance->settings->get_transient( 'gh_import_confirm_contacts' );
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
        Plugin::$instance->settings->delete_transient( 'gh_import_map' );
        Plugin::$instance->settings->delete_transient( 'gh_import_tags' );
        Plugin::$instance->settings->delete_transient( 'gh_import_confirm_contacts' );
    }

    /**
     * Get the return URL
     *
     * @return string
     */
    protected function get_return_url()
    {
        $url = admin_page_url( 'gh_contacts' );

        // Return to guided setup if it's not yet complete.
        if ( ! guided_setup_finished() ){
            $url = admin_page_url( 'gh_guided_setup', [ 'step' => 4 ] );
        }

        return $url;
    }
}