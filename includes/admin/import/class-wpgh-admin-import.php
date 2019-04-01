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

    public function add_ajax_actions()
    {
        add_filter( "groundhogg/bulk_job/gh_import_contacts/query", [ $this, 'bulk_job_query' ] );
        add_action( "groundhogg/bulk_job/gh_import_contacts/ajax", [ $this, 'process_bulk_job' ] );
    }

    /**
     * Get the items to process.
     */
    public function bulk_job_query( $items )
    {
        if ( ! current_user_can( 'import_contacts' ) ){
            return $items;
        }

        $file_name = urldecode( $_GET[ 'file_name' ] );
        $file_name = wp_normalize_path( wpgh_get_csv_imports_dir( $file_name ) );

        return wpgh_get_items_from_csv( $file_name );
    }

    /**
     * Process the items
     */
    public function process_bulk_job()
    {
        if ( ! current_user_can( 'import_contacts' ) ){
            return;
        }
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

    public function scripts()
    {
        // TODO: Implement scripts() method.
    }

    public function help()
    {
        // TODO: Implement help() method.
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
        echo 'hi';
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

        wp_redirect( $this->admin_url( [
            'action' => 'map',
            'file_name' => urlencode( basename( $result['file'] ) ),
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