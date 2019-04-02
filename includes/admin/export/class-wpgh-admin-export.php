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

        $meta_keys = WPGH()->contact_meta->get_keys();
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

        $file_name = wpgh_get_transient( 'gh_export_file_name' );

        $fp = false;

        // If the file path is not set, let's set it.
        if ( ! $file_name ){

            // randomize the file path to prevent direct access.
            $file_name = md5( wpgh_encrypt_decrypt( time() ) ) . '.csv';

            // get the full path.
            $file_path = wpgh_get_csv_exports_dir( $file_name, true );
            wpgh_set_transient( 'gh_export_file_name', $file_name, HOUR_IN_SECONDS );

            //write the headers to the export.
            $fp = fopen( $file_path,"w" );
            fputcsv( $fp, $headers );
        }

        // If we have the file name then open the file before we move on.
        if ( ! $fp ){
            $file_path = wpgh_get_csv_exports_dir( $file_name, true );
            $fp = fopen( $file_path,"w" );
        }

        foreach ( $ids as $id ){

            $line = [];

            $contact = wpgh_get_contact( $id );

            foreach ( $headers as $header ){
                $line[] = $contact->$header;
            }

            fputcsv( $fp, $line );

            $complete++;
        }

        fclose( $fp );

        $response = [ 'complete' => $complete ];

        if ( filter_var( $_POST[ 'the_end' ], FILTER_VALIDATE_BOOLEAN ) ){

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

        if ( ! class_exists( 'WPGH_Imports_Table' ) ){
            require_once dirname( __FILE__ ) . '/class-wpgh-imports-table.php';
        }

        $table = new WPGH_Imports_Table(); ?>
        <form method="post" class="search-form wp-clearfix">
            <?php $table->prepare_items(); ?>
            <?php $table->display(); ?>
        </form>
       <?php
    }

    public function add()
    {
        include dirname( __FILE__ ) . '/add-import.php';
    }

    public function add_import()
    {

        if ( ! current_user_can( 'export_contacts' ) ){
            wp_die( 'Oops...' );
        }

        if ( empty( $_FILES[ 'import_file' ][ 'name' ] ) ){
            $this->notices->add( new WP_Error( 'no_files', 'Please upload a file!' ) );
            return self::SELF;
        }

        $_FILES[ 'import_file' ][ 'name' ] = md5( $_FILES[ 'import_file' ][ 'name' ] ) . '.csv';

        $result = $this->handle_file_upload( 'import_file' );

        if ( is_wp_error( $result ) ){
            $this->notices->add( $result );
            return self::SELF;
        }

        wp_redirect( $this->admin_url( [
            'action' => 'map',
            'import' => urlencode( basename( $result['file'] ) ),
        ] ) );

        die();

    }

    public function map()
    {
        include dirname( __FILE__ ) . '/map-import.php';

    }

    public function map_import()
    {
        if ( ! current_user_can( 'export_contacts' ) ){
            wp_die( 'Oops...' );
        }

        $map = $_POST[ 'map' ];

        if ( ! is_array( $map ) ){
            wp_die( 'Oops...' );
        }

        $file_name = $_POST[ 'import' ];

        $tags = [ sprintf( '%s - %s', __( 'Import' ), date_i18n( 'Y-m-d H:i:s' ) ) ];

        if ( gisset_not_empty( $_POST, 'tags' ) ){
            $tags = array_merge( $tags, $_POST[ 'tags' ] );
        }

        $tags = WPGH()->tags->validate( $tags );

        wpgh_set_transient( 'gh_import_tags', $tags, HOUR_IN_SECONDS );
        wpgh_set_transient( 'gh_import_map', $map, HOUR_IN_SECONDS );

        wp_redirect( admin_url( 'admin.php?page=gh_bulk_jobs&action=gh_export_contacts&import=' . $file_name ) );
        die();

    }

    /**
     * @return int delete the files
     */
    public function delete_import()
    {
        $files = $this->get_items();

        foreach ( $files as $file_name ){
            $filepath = wpgh_get_csv_imports_dir( $file_name );
            unlink( $filepath );
        }

        $this->notices->add( 'file_removed', __( 'Imports deleted.', 'groundhogg' ) );
        return self::PAGE;
    }

    /**
     * Change the default upload directory
     *
     * @param $param
     * @return mixed
     */
    public function files_upload_dir( $param )
    {
        $mydir = '/groundhogg-imports';

        if ( is_multisite() ){
            $mydir .= '/' . get_current_blog_id();
        }

        $param['path'] = $param['basedir'] . $mydir;
        $param['url'] = $param['baseurl'] . $mydir;
        $param['subdir'] = $mydir;;

        return $param;
    }

    /**
     * Upload a file to the Groundhogg file directory
     *
     * @param $key
     * @param $config
     * @return array|bool|WP_Error
     */
    private function handle_file_upload( $key )
    {
        $file = $_FILES[ $key ];

        $upload_overrides = array( 'test_form' => false );

        if ( !function_exists('wp_handle_upload') ) {
            require_once( ABSPATH . '/wp-admin/includes/file.php' );
        }

        add_filter( 'upload_dir', array( $this, 'files_upload_dir' ) );
        $mfile = wp_handle_upload( $file, $upload_overrides );
        remove_filter( 'upload_dir', array( $this, 'files_upload_dir' ) );

        if( isset( $mfile['error'] ) ) {

            if ( empty( $mfile[ 'error' ] ) ){
                $mfile[ 'error' ] = _x( 'Could not upload file.', 'error', 'groundhogg' );
            }

            return new WP_Error( 'BAD_UPLOAD', $mfile['error'] );
        }

        return $mfile;
    }
}