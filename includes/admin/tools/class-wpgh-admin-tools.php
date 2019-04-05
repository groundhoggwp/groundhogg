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

    public $importer;
    public $exporter;
    public $deleter;
    public $syncer;

    public function __construct()
    {

        if ( ! class_exists( 'WPGH_Import_Bulk_Job' ) ){
            include dirname( __FILE__ ) . '/class-wpgh-import-bulk-job.php';
        }

        if ( ! class_exists( 'WPGH_Export_Bulk_job' ) ){
            include dirname( __FILE__ ) . '/class-wpgh-export-bulk-job.php';
        }

        if ( ! class_exists( 'WPGH_Delete_Bulk_Job' ) ){
            include dirname( __FILE__ ) . '/class-wpgh-delete-bulk-job.php';
        }

        if ( ! class_exists( 'WPGH_Sync_Bulk_Job' ) ){
            include dirname( __FILE__ ) . '/class-wpgh-sync-bulk-job.php';
        }

        $this->importer = new WPGH_Import_Bulk_Job();
        $this->exporter = new WPGH_Export_Bulk_job();
        $this->deleter  = new WPGH_Delete_Bulk_Job();
        $this->syncer   = new WPGH_Sync_Bulk_Job();

        parent::__construct();
    }

    // Unused functions.
    public function view(){}
    public function scripts(){}
    public function help(){}
    public function add_ajax_actions(){}

    protected function add_additional_actions(){
        add_action( "groundhogg/admin/{$this->get_slug()}", [ $this, 'delete_warning' ] );
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

        if ( $this->current_tab() === 'export' ){
            $actions[] = [
                'link'      => $this->exporter->get_start_url(),
                'action'    => __( 'Export All Contacts' ),
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
                'name' => __( 'Sync Users & Contacts' ),
                'slug'  => 'sync',
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

    ####### SYNC TAB FUNCTIONS #########

    public function sync_view()
    {
        ?>
        <div class="show-upload-view">
            <div class="upload-plugin-wrap">
                <div class="upload-plugin">
                    <p class="install-help"><?php _e( 'Sync Users & Contacts', 'groundhogg' ); ?></p>
                    <form method="post" class="wp-upload-form">
                        <?php wp_nonce_field(); ?>
                        <?php echo WPGH()->html->input( [
                            'type' => 'hidden',
                            'name' => 'action',
                            'value' => 'bulk_sync',
                        ] ); ?>
                        <p><?php _e( 'The sync process will create new contact records for all users in the database. If a contact records already exists then the association will be updated.' ); ?></p>
<!--                        --><?php //echo WPGH()->html->checkbox( [
//                            'label'         => 'Sync meta data?',
//                            'name'          => 'sync_meta',
//                            'value'         => '1',
//                            'checked'       => false,
//                            'required'      => false,
//                        ] ); ?>
                        <p class="submit" style="text-align: center;padding-bottom: 0;margin: 0;">
                            <button style="width: 100%" class="button-primary" name="sync_users" value="sync"><?php _ex('Start Sync Process', 'action', 'groundhogg'); ?></button>
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
    public function sync_bulk_sync_tool()
    {
        $this->syncer->start();
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

        $this->importer->start( [ 'import' => $file_name ] );
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
        $this->deleter->start( [ 'tags_include' => implode( ',', $tags ) ] );
    }

}