<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-04-01
 * Time: 3:19 PM
 */

if ( ! class_exists( 'WPGH_Admin_Page' ) ){
    require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-admin-page.php';
}

class WPGH_Admin_Export extends WPGH_Admin_Page
{
    // Unused functions.
    public function scripts(){}
    public function help(){}

    public function add_ajax_actions(){
        add_action( "groundhogg/bulk_job/gh_export_contacts/ajax", [ $this, 'process_bulk_job' ] );
    }

    protected function add_additional_actions(){
        add_filter( "groundhogg/bulk_job/gh_export_contacts/query", [ $this, 'bulk_job_query' ] );
        add_filter( "groundhogg/bulk_job/gh_export_contacts/max_items", [ $this, 'calc_max_items' ], 10, 2 );
    }

    /**
     * @param $items
     * @return int
     */
    public function calc_max_items( $max, $items ){
        return min( 500, intval( ini_get( 'max_input_vars' ) ) ) ;
    }

    /**
     * Get the items to process.
     */
    public function bulk_job_query( $items )
    {
        if ( ! current_user_can( 'export_contacts' ) ){
            return $items;
        }

        $query = new WPGH_Contact_Query();
        $args = $_GET;

        $contacts = $query->query( $args );
        $ids = wp_list_pluck( $contacts, 'ID' );

        return $ids;
    }

    /**
     * Process the items
     */
    public function process_bulk_job()
    {
        if ( ! current_user_can( 'export_contacts' ) ){
            return;
        }

        $ids = wp_parse_id_list( $_POST[ 'items' ] );

        $complete = 0;

        $meta_keys = array_values( WPGH()->contact_meta->get_keys() );
        $default_keys = [
            'ID',
            'email',
            'first_name',
            'last_name',
            'user_id',
            'owner_id',
            'optin_status',
            'date_created',
        ];

        $headers = array_merge( $default_keys, $meta_keys );

//        var_dump( $headers );
//        die();

        $file_name = wpgh_get_transient( 'gh_export_file' );

        $fp = false;

        // If the file path is not set, let's set it.
        if ( ! $file_name ){

            // randomize the file path to prevent direct access.
            $file_name = md5( wpgh_encrypt_decrypt( time() ) ) . '.csv';

            // get the full path.
            $file_path = wpgh_get_csv_exports_dir( $file_name, true );
            wpgh_set_transient( 'gh_export_file', $file_name, HOUR_IN_SECONDS );

            //write the headers to the export.
            $fp = fopen( $file_path,"w" );
            fputcsv( $fp, $headers );
        }

        // If we have the file name then open the file before we move on.
        if ( ! $fp ){
            $file_path = wpgh_get_csv_exports_dir( $file_name, true );
            $fp = fopen( $file_path,"a" );
        }

        foreach ( $ids as $id ){

            $line = [];

            $contact = wpgh_get_contact( $id );

            foreach ( $headers as $header ){
                // Check for array type
                $line[] = is_array( $contact->$header ) ? multi_implode( ',', $contact->$header ) : $contact->$header;
            }

            fputcsv( $fp, $line );

            $complete++;
        }

        fclose( $fp );

        $response = [ 'complete' => $complete ];

        if ( filter_var( $_POST[ 'the_end' ], FILTER_VALIDATE_BOOLEAN ) ){

            wpgh_delete_transient( 'gh_export_file' );

            $file_url = wpgh_get_csv_exports_url( $file_name );
            WPGH()->notices->add('finished', sprintf( _x( 'Export file created. %s', 'notice', 'groundhogg'), "<a class='button button-primary' href='$file_url'>Download Now</a>" ) );
            $response[ 'return_url' ] = admin_url( 'admin.php?page=gh_contacts' );
        }

        wp_die( json_encode( $response ) );
    }

    public function get_order()
    {
        return 10;
    }

    protected function get_parent_slug()
    {
        return 'options.php';
    }

    public function get_slug()
    {
        return 'gh_exports';
    }

    public function get_name()
    {
        return __( 'Exports' );
    }

    public function get_cap()
    {
        return 'export_contacts';
    }
    
    public function get_item_type()
    {
        return 'export';
    }

    protected function get_title_actions()
    {
        return [
            [
                'link'      => $this->admin_url( [ 'action' => 'add' ] ),
                'action'    => __( 'Export Contacts' ),
            ]
        ];
    }

    public function view()
    {
        if ( ! class_exists( 'WPGH_Exports_Table' ) ){
            require_once dirname( __FILE__ ) . '/class-wpgh-exports-table.php';
        }

        $table = new WPGH_Exports_Table(); ?>
        <form method="post" class="wp-clearfix">
            <?php $table->prepare_items(); ?>
            <?php $table->display(); ?>
        </form>
       <?php
    }

    /**
     * @return int delete the files
     */
    public function delete_export()
    {
        $files = $this->get_items();

        foreach ( $files as $file_name ){
            $filepath = wpgh_get_csv_exports_dir( $file_name );
            unlink( $filepath );
        }

        $this->notices->add( 'file_removed', __( 'Exports deleted.', 'groundhogg' ) );
        return self::PAGE;
    }
}