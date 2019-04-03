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

class WPGH_Admin_Import extends WPGH_Admin_Page
{

    private $uploads_path = [];

    // Unused functions.
    public function scripts(){}
    public function help(){}

    public function add_ajax_actions(){
        add_action( "groundhogg/bulk_job/gh_import_contacts/ajax", [ $this, 'process_bulk_job' ] );
    }

    protected function add_additional_actions(){
        add_filter( "groundhogg/bulk_job/gh_import_contacts/query", [ $this, 'bulk_job_query' ] );
        add_filter( "groundhogg/bulk_job/gh_import_contacts/max_items", [ $this, 'calc_max_items' ], 10, 2 );
    }

    /**
     * @param $items
     * @return int
     */
    public function calc_max_items( $max, $items ){
        $item = array_shift( $items );
        $fields = count( $item );
        $max = intval( ini_get( 'max_input_vars' ) );
        $max_items = floor( $max / $fields ) - 1;

        return min( $max_items, 100 );
    }

    /**
     * Get the items to process.
     */
    public function bulk_job_query( $items )
    {
        if ( ! current_user_can( 'import_contacts' ) ){
            return $items;
        }

        $file_name = urldecode( $_GET[ 'import' ] );
        $file_path = wp_normalize_path( wpgh_get_csv_imports_dir( $file_name ) );

//        wp_die( $file_path );

        return wpgh_get_items_from_csv( $file_path );
    }

    /**
     * Process the items
     */
    public function process_bulk_job()
    {
        if ( ! current_user_can( 'import_contacts' ) ){
            return;
        }

        // get the map
        $field_map = wpgh_get_transient( 'gh_import_map' );
        $import_tags = wpgh_get_transient( 'gh_import_tags' );

        if ( ! $field_map ){
            return;
        }

        // get the posted items
        $items = $_POST[ 'items' ];

        $complete = 0;
        $skipped  = 0;

        // iterate over the contacts
        foreach ( $items as $item ){

            $contact = wpgh_generate_contact_with_map( $item, $field_map );

            if ( ! $contact ){
                $skipped++;
            } else {
                // Apply import specific tags.
                $contact->apply_tag( $import_tags );
                $complete++;
            }

        }

        $response = [ 'complete' => $complete + $skipped ];

        if ( filter_var( $_POST[ 'the_end' ], FILTER_VALIDATE_BOOLEAN ) ){
            wpgh_delete_transient( 'gh_import_map' );
            WPGH()->notices->add('finished', _x('Import finished!', 'notice', 'groundhogg') );
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
        return 'gh_imports';
    }

    public function get_name()
    {
        return __( 'Imports' );
    }

    public function get_cap()
    {
        return 'import_contacts';
    }
    public function get_item_type()
    {
        return 'import';
    }

    protected function get_title_actions()
    {
        return [
            [
                'link'      => $this->admin_url( [ 'action' => 'add' ] ),
                'action'    => __( 'Import New List' ),
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

        if ( ! current_user_can( 'import_contacts' ) ){
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

//        var_dump( $result );
//        wp_die();

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
        if ( ! current_user_can( 'import_contacts' ) ){
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

        wp_redirect( admin_url( 'admin.php?page=gh_bulk_jobs&action=gh_import_contacts&import=' . $file_name ) );
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
        $param['path']      = $this->uploads_path[ 'path'];
        $param['url']       = $this->uploads_path[ 'url' ];
        $param['subdir']    = $this->uploads_path[ 'subdir' ];

        return $param;
    }

    /**
     * Initialize the base upload path
     */
    private function set_uploads_path()
    {
        $this->uploads_path[ 'subdir' ] = wpgh_get_base_uploads_dir();
        $this->uploads_path[ 'path' ] = wpgh_get_csv_imports_dir();
        $this->uploads_path[ 'url' ] = wpgh_get_csv_imports_url();
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

        $this->set_uploads_path();

//        var_dump( $this->uploads_path );
//        wp_die();

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