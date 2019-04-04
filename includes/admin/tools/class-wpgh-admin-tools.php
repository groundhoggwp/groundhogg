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

class WPGH_Admin_Tools extends WPGH_Admin_Page
{

    protected $uploads_path = [];

    // Unused functions.
    public function view(){}
    public function scripts(){}
    public function help(){}

    public function add_ajax_actions(){
        add_action( "groundhogg/bulk_job/gh_import_contacts/ajax", [ $this, 'import_process_bulk_job' ] );
        add_action( "groundhogg/bulk_job/gh_export_contacts/ajax", [ $this, 'export_process_bulk_job' ] );
        add_action( 'groundhogg/bulk_job/gh_delete_contacts/ajax', [ $this, 'bulk_delete_contacts_ajax' ] );

    }

    protected function add_additional_actions(){

        add_action( "groundhogg/admin/{$this->get_slug()}", [ $this, 'delete_warning' ] );

        add_filter( "groundhogg/bulk_job/gh_import_contacts/query", [ $this, 'import_bulk_job_query' ] );
        add_filter( "groundhogg/bulk_job/gh_import_contacts/max_items", [ $this, 'import_calc_max_items' ], 10, 2 );

        add_filter( "groundhogg/bulk_job/gh_export_contacts/query", [ $this, 'export_bulk_job_query' ] );
        add_filter( "groundhogg/bulk_job/gh_export_contacts/max_items", [ $this, 'export_calc_max_items' ], 10, 2 );

        add_filter( 'groundhogg/bulk_job/gh_delete_contacts/query', [ $this, 'bulk_delete_contacts_query' ] );
        add_filter( 'groundhogg/bulk_job/gh_delete_contacts/max_items', [ $this, 'bulk_delete_contacts_max_items' ], 10, 2 );

    }

    public function get_order(){return 98;}
    protected function get_parent_slug(){return 'groundhogg';}

    public function get_slug()
    {
        return 'gh_tools';
    }

    public function get_name()
    {
        return __( 'Tools' );
    }

    public function get_cap()
    {
        return 'manage_options';
    }

    public function get_item_type()
    {

        switch ( $this->current_tab() ){
            default:
            case 'system':
            case 'delete':
                $type = 'tool';
                break;
            case 'import':
                $type = 'import';
                break;
            case 'export':
                $type = 'export';
                break;
        }

        return $type;
    }

    protected function get_title_actions()
    {

        $actions = [];

        if ( $this->current_tab() === 'import' ){
            $actions[] = [
                'link'      => $this->admin_url( [ 'action' => 'add', 'tab' => 'import' ] ),
                'action'    => __( 'Import New List' ),
            ];
        }

        return $actions;

    }

    protected function get_tabs()
    {
        $tabs = [
            [
                'name'  => __( 'System Info' ),
                'slug'  => 'system',
            ],
            [
                'name' => __( 'Import' ),
                'slug'  => 'import',
            ],
            [
                'name' => __( 'Export' ),
                'slug'  => 'export',
            ],
            [
                'name' => __( 'Bulk Delete Contacts' ),
                'slug'  => 'delete',
            ]
        ];

        $tabs = apply_filters( 'groundhogg/tools/tabs', $tabs );

        return $tabs;
    }

    protected function current_tab()
    {
        return gisset_not_empty( $_GET, 'tab' ) ? $_GET[ 'tab' ] : $this->get_tabs()[ 0 ][ 'slug' ];
    }

    protected function do_page_tabs()
    {
        ?>
        <!-- BEGIN TABS -->
        <h2 class="nav-tab-wrapper">
            <?php foreach ( $this->get_tabs() as $id => $tab ): ?>
                <a href="?page=<?php echo $this->get_slug(); ?>&tab=<?php echo $tab[ 'slug' ]; ?>" class="nav-tab <?php echo $this->current_tab() ==  $tab[ 'slug' ] ? 'nav-tab-active' : ''; ?>"><?php _e(  $tab[ 'name' ], 'groundhogg'); ?></a>
            <?php endforeach; ?>
        </h2>
        <?php
    }

    public function process_action()
    {
        if (!$this->get_action() || !$this->verify_action())
            return;

        $base_url = remove_query_arg(array('_wpnonce', 'action'), wp_get_referer());

        $func = sprintf( "%s_%s_%s", $this->current_tab(), $this->get_action(), $this->get_item_type() );

        if ( method_exists( $this, $func ) ){
            $exitCode = call_user_func( [ $this, $func ] );
        }

        set_transient('gh_last_action', $this->get_action(), 30 );

        if ( $exitCode === self::SELF ){
            return;
        }

        $base_url = add_query_arg('ids', urlencode(implode(',', $this->get_items())), $base_url);

        wp_redirect($base_url);
        die();
    }

    public function page()
    {

        do_action( "groundhogg/admin/{$this->get_slug()}", $this );

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo $this->get_title(); ?></h1>
            <?php $this->do_title_actions(); ?>
            <div id="notices">
                <?php $this->notices->notices(); ?>
            </div>
            <hr class="wp-header-end">
            <?php $this->do_page_tabs(); ?>
            <?php

            $method = sprintf( '%s_%s', $this->current_tab(), $this->get_action() );

            if ( method_exists( $this, $method ) ){
                call_user_func( [ $this, $method ] );
            } else {
                do_action( "groundhogg/admin/{$this->get_slug()}/display/{$method}", $this );
            }

            ?>
        </div>
    <?php
    }

    ####### SYSTEM TAB FUNCTIONS #########

    /**
     * Regular system view.
     */
    public function system_view()
    {
        ?>
        <div id="poststuff">
            <div class="postbox">
                <h2 class="hndle"><?php _e( 'Download System Info', 'groundhogg' ); ?></h2>
                <div class="inside">
                    <p class="description"><?php _e( 'Download System Info when requesting support.', 'groundhogg' ); ?></p>

                    <textarea class="code" style="width: 100%;height:600px;" readonly="readonly" onclick="this.focus(); this.select()" id="system-info-textarea" name="wpgh-sysinfo"><?php echo wpgh_tools_sysinfo_get(); ?></textarea>
                    <p class="submit">
                        <a class="button button-primary" href="<?php echo admin_url( '?gh_download_sys_info=1' ) ?>"><?php _e( 'Download System Info', 'groundhogg' ); ?></a>
                    </p>

                </div>
            </div>
        </div>
        <?php
    }

    ####### IMPORT TAB FUNCTIONS #########

    /**
     * Imports tab view
     */
    public function import_view()
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

    /**
     * Add new import view
     */
    public function import_add()
    {
        include dirname( __FILE__ ) . '/add-import.php';
    }

    /**
     * Map import view
     */
    public function import_map()
    {
        include dirname( __FILE__ ) . '/map-import.php';
    }

    /**
     * Process the import addition
     *
     * @return int
     */
    public function import_add_import()
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
            'action'    => 'map',
            'tab'       => 'import',
            'import'    => urlencode( basename( $result['file'] ) ),
        ] ) );

        die();

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
     * map the import
     */
    public function import_map_import()
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
    public function import_delete_import()
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
     * @param $items
     * @return int
     */
    public function import_calc_max_items( $max, $items ){
        $item = array_shift( $items );
        $fields = count( $item );
        $max = intval( ini_get( 'max_input_vars' ) );
        $max_items = floor( $max / $fields ) - 1;

        return min( $max_items, 100 );
    }

    /**
     * Get the items to process.
     */
    public function import_bulk_job_query( $items )
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
    public function import_process_bulk_job()
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

    ####### EXPORT TAB FUNCTIONS #########

    /**
     * Exports tab view
     */
    public function export_view()
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
    public function export_delete_export()
    {
        $files = $this->get_items();

        foreach ( $files as $file_name ){
            $filepath = wpgh_get_csv_exports_dir( $file_name );
            unlink( $filepath );
        }

        $this->notices->add( 'file_removed', __( 'Exports deleted.', 'groundhogg' ) );
        return self::PAGE;
    }

    /**
     * @param $items
     * @return int
     */
    public function export_calc_max_items( $max, $items ){
        return min( 500, intval( ini_get( 'max_input_vars' ) ) ) ;
    }

    /**
     * Get the items to process.
     */
    public function export_bulk_job_query( $items )
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
    public function export_process_bulk_job()
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
            WPGH()->notices->add('finished', sprintf( _x( 'Export file created. %s', 'notice', 'groundhogg'), "&nbsp;&nbsp;&nbsp;<a class='button button-primary' href='$file_url'>Download Now</a>" ) );
            $response[ 'return_url' ] = admin_url( 'admin.php?page=gh_contacts' );
        }

        wp_die( json_encode( $response ) );
    }

    ####### DELETE TAB FUNCTIONS #########

    public function delete_warning()
    {
        if ( $this->current_tab() === 'delete' ){
            $this->notices->add( 'no_going_back', __( '&#9888; There is no going back once the deletion process has started.', 'groudnhogg' ), 'warning' );
        }

    }

    public function delete_view()
    {
        ?>
        <div class="show-upload-view">
            <div class="upload-plugin-wrap">
                <div class="upload-plugin">
                    <p class="install-help"><?php _e( 'Delete Contacts in Bulk by Tag', 'groundhogg' ); ?></p>
                    <form method="post" class="wp-upload-form">
                        <?php wp_nonce_field(); ?>
                        <?php echo WPGH()->html->input( [
                            'type' => 'hidden',
                            'name' => 'action',
                            'value' => 'bulk_delete',
                        ] ); ?>
                        <?php echo WPGH()->html->tag_picker( [] ); ?>
                        <p>&#9888;&nbsp;<b><?php _e( 'Once you click the delete button there is no going back!' ); ?></b></p>
                        <p class="submit" style="text-align: center;padding-bottom: 0;margin: 0;">
                            <button style="width: 100%" class="button-primary" name="delete_contacts" value="delete"><?php _ex('Delete Contacts', 'action', 'groundhogg'); ?></button>
                        </p>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Delete all them contacts.
     */
    public function delete_bulk_delete_tool()
    {
        $tags = WPGH()->tags->validate( $_POST[ 'tags' ] );
        $url = add_query_arg( [ 'action' => 'gh_delete_contacts', 'tags_include' => implode( ',', $tags ) ], admin_url( 'admin.php?page=gh_bulk_jobs' ) );

        wp_redirect( $url );
        die();
    }

    /**
     * Get Ids of contacts to be deleted.
     *
     * @param $items
     * @return array
     */
    public function bulk_delete_contacts_query( $items )
    {
        if ( ! current_user_can( 'delete_contacts' ) ){
            return $items;
        }

        $query = new WPGH_Contact_Query();
        $args = $_GET;

        $contacts = $query->query( $args );
        $ids = wp_list_pluck( $contacts, 'ID' );

        return $ids;
    }

    /**
     * Do 100 at a time.
     *
     * @param $max
     * @param $items
     * @return int
     */
    function bulk_delete_contacts_max_items( $max, $items )
    {
        if ( ! current_user_can( 'delete_contacts' ) ){
            return $max;
        }

        return 100;
    }

    /**
     * Process the bulk delete ajax action
     */
    function bulk_delete_contacts_ajax()
    {
        if ( ! current_user_can( 'delete_contacts' ) ){
            return;
        }

        $ids = wp_parse_id_list( $_POST[ 'items' ] );

        $complete = 0;

        foreach ( $ids as $id ){
            WPGH()->contacts->delete( $id );
            $complete++;
        }

        $response = [ 'complete' => $complete ];

        if ( filter_var( $_POST[ 'the_end' ], FILTER_VALIDATE_BOOLEAN ) ){
            WPGH()->notices->add('finished', _x('Contacts deleted!', 'notice', 'groundhogg') );
            $response[ 'return_url' ] = admin_url( 'admin.php?page=gh_contacts' );
        }

        wp_die( json_encode( $response ) );
    }

}