<?php

namespace Groundhogg\Admin\Tools;

use Groundhogg\Admin\Tabbed_Admin_Page;
use Groundhogg\Bulk_Jobs\Create_Users;
use Groundhogg\Bulk_Jobs\Delete_Contacts;
use function Groundhogg\get_array_var;
use function Groundhogg\get_post_var;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use Groundhogg\Plugin;
use \WP_Error;
use function Groundhogg\isset_not_empty;
use function set_transient;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-04-01
 * Time: 3:19 PM
 */
class Tools_Page extends Tabbed_Admin_Page
{

    protected $uploads_path = [];

    /**
     * @var \Groundhogg\Bulk_Jobs\Import_Contacts
     */
    public $importer;

    /**
     * @var \Groundhogg\Bulk_Jobs\Export_Contacts
     */
    public $exporter;

    /**
     * @var Delete_Contacts
     */
    public $deleter;

    /**
     * @var \Groundhogg\Bulk_Jobs\Sync_Contacts
     */
    public $syncer;

    /**
     * @var Create_Users;
     */
    public $create_users;

    // Unused functions.
    public function view(){}

    public function scripts(){}

    public function help(){}

    public function add_ajax_actions(){}

    protected function add_additional_actions()
    {
        add_action( "groundhogg/admin/{$this->get_slug()}", [ $this, 'delete_warning' ] );

        $this->init_bulk_jobs();
    }

    public function init_bulk_jobs()
    {
        $this->importer = Plugin::$instance->bulk_jobs->import_contacts;
        $this->exporter = Plugin::$instance->bulk_jobs->export_contacts;
        $this->deleter = Plugin::$instance->bulk_jobs->delete_contacts;
        $this->syncer = Plugin::$instance->bulk_jobs->sync_contacts;
        $this->create_users = Plugin::$instance->bulk_jobs->create_users;
    }

    public function get_order()
    {
        return 98;
    }

    public function screen_options(){}

    protected function get_parent_slug()
    {
        return 'groundhogg';
    }

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

        switch ( $this->get_current_tab() ) {
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

        if ( $this->get_current_tab() === 'import' ) {
            $actions[] = [
                'link' => $this->admin_url( [ 'action' => 'add', 'tab' => 'import' ] ),
                'action' => __( 'Import New List' ),
            ];
        }

        if ( $this->get_current_tab() === 'export' ) {
            $actions[] = [
                'link' => Plugin::$instance->bulk_jobs->export_contacts->get_start_url(), //todo enable
                'action' => __( 'Export All Contacts' ),
            ];
        }

        return $actions;

    }

    protected function get_tabs()
    {
        $tabs = [
            [
                'name' => __( 'System Info' ),
                'slug' => 'system',
            ],
            [
                'name' => __( 'Import' ),
                'slug' => 'import',
            ],
            [
                'name' => __( 'Export' ),
                'slug' => 'export',
            ],
            [
                'name' => __( 'Sync Users & Contacts' ),
                'slug' => 'sync',
            ],
            [
                'name' => __( 'Create Users' ),
                'slug' => 'create_users',
            ],
            [
                'name' => __( 'Bulk Delete Contacts' ),
                'slug' => 'delete',
            ],
            [
                'name' => __( 'Updates' ),
                'slug' => 'updates'
            ]
        ];

        $tabs = apply_filters( 'groundhogg/admin/tools/tabs', $tabs );

        return $tabs;
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
                    <textarea class="code" style="width: 100%;height:600px;" readonly="readonly"
                              onclick="this.focus(); this.select()" id="system-info-textarea"
                              name="sysinfo"><?php echo groundhogg_tools_sysinfo_get(); ?></textarea>
                    <p class="submit">
                        <a class="button button-primary"
                           href="<?php echo admin_url( '?gh_download_sys_info=1' ) ?>"><?php _e( 'Download System Info', 'groundhogg' ); ?></a>
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
                        <?php echo Plugin::$instance->utils->html->input( [
                            'type' => 'hidden',
                            'name' => 'action',
                            'value' => 'bulk_sync',
                        ] ); ?>
                        <p><?php _e( 'The sync process will create new contact records for all users in the database. If a contact records already exists then the association will be updated.' ); ?></p>
                        <p class="submit" style="text-align: center;padding-bottom: 0;margin: 0;">
                            <button style="width: 100%" class="button-primary" name="sync_users"
                                    value="sync"><?php _ex( 'Start Sync Process', 'action', 'groundhogg' ); ?></button>
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
    public function process_sync_bulk_sync()
    {
        $this->syncer->start(); //todo
    }

    ####### CREATE USER FUNCTIONS #########

    public function create_users_view()
    {
        ?>
        <div class="show-upload-view">
            <div class="upload-plugin-wrap">
                <div class="upload-plugin">
                    <p class="install-help"><?php _e( 'Create Users', 'groundhogg' ); ?></p>
                    <form method="post" class="wp-upload-form">
                        <?php wp_nonce_field(); ?>
                        <?php echo Plugin::$instance->utils->html->input( [
                            'type' => 'hidden',
                            'name' => 'action',
                            'value' => 'start',
                        ] );

                        echo html()->e( 'p', [], [
                            __( 'Select contacts to create accounts for.', 'groundhogg' ),
                            html()->tag_picker( [
                                'name'              => 'tags_include[]',
                                'id'                => 'tags_include',
                            ] ),
                        ] );

                        echo html()->e( 'p', [], [
                            __( 'Exclude these contacts.', 'groundhogg' ),
                            html()->tag_picker( [
                                'name'              => 'tags_exclude[]',
                                'id'                => 'tags_exclude',
                            ] ),
                        ] );

                        echo html()->e( 'p', [], [
                            __( 'Choose role.', 'groundhogg' ),
                            html()->dropdown( [
                                'name'              => 'role',
                                'id'                => 'role',
                                'options' => Plugin::$instance->roles->get_roles_for_select(),
                                'selected' => 'subscriber',
                                'style' => [ 'width' => '100%' ]
                            ] ),
                        ] );

                        echo html()->e( 'p', [], [
                            html()->checkbox( [
                                'label'         => __( 'Send email notification to user.', 'groundhogg' ),
                                'name'          => 'send_email_notification',
                                'value'         => '1',
                                'checked'       => false,
                            ] ),
                            '<br/>',
                            html()->e( 'i', [], sprintf( ' (%s)', __( 'Much slower' ) ) )
                        ] );

                        ?>
                        <p class="submit" style="text-align: center;padding-bottom: 0;margin: 0;">
                            <button style="width: 100%" class="button-primary" name="start"
                                    value="start"><?php _ex( 'Create Users', 'action', 'groundhogg' ); ?></button>
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
    public function process_create_users_start()
    {

        if ( ! current_user_can( 'add_users' ) ){
            $this->wp_die_no_access();
        }

        delete_transient( 'gh_create_user_job_config' );

        $config = [
            'send_email' => boolval( get_request_var( 'send_email_notification' ) ),
            'role' => sanitize_text_field( get_request_var( 'role', 'subscriber' ) ),
        ];

        set_transient( 'gh_create_user_job_config', $config, HOUR_IN_SECONDS );

        $this->create_users->start( [
            'tags_include' => wp_parse_id_list( get_request_var( 'tags_include' ) ),
            'tags_exclude' => wp_parse_id_list( get_request_var( 'tags_exclude' ) ),
        ] ); //todo
    }

    ####### IMPORT TAB FUNCTIONS #########

    /**
     * Imports tab view
     */
    public function import_view()
    {
        if ( !class_exists( 'WPGH_Imports_Table' ) ) {
            require_once dirname( __FILE__ ) . '/imports-table.php';
        }

        $table = new Imports_Table(); ?>
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
     * @return int|WP_Error
     */
    public function process_import_add()
    {

        if ( !current_user_can( 'import_contacts' ) ) {
            $this->wp_die_no_access();
        }

        $file = get_array_var( $_FILES, 'import_file' );

        if ( ! $file || ! $file[ 'name' ] ||  mime_content_type( $file[ 'tmp_name' ] ) !== 'text/csv' ) {
            return new WP_Error( 'no_files', 'Please upload a valid CSV file!' );
        }

        $file[ 'name' ] = sanitize_file_name( md5( $file[ 'name' ] ) . '.csv' );
        $result = $this->handle_file_upload( $file );

        if ( is_wp_error( $result ) ) {

            if ( is_multisite() ) {
                return new WP_Error( 'multisite_add_csv', 'Could not import because CSV is not an allowed file type on this multisite. Please add CSV to the list of allowed file types in the network settings.' );
            }

            return $result;
        }

        return wp_redirect( $this->admin_url( [
            'action' => 'map',
            'tab' => 'import',
            'import' => urlencode( basename( $result[ 'file' ] ) ),
        ] ) );

    }

    /**
     * Upload a file to the Groundhogg file directory
     *
     * @param $file array
     * @param $config
     * @return array|bool|WP_Error
     */
    private function handle_file_upload( $file )
    {
        $upload_overrides = array( 'test_form' => false );

        if ( !function_exists( 'wp_handle_upload' ) ) {
            require_once( ABSPATH . '/wp-admin/includes/file.php' );
        }

        $this->set_uploads_path();

        add_filter( 'upload_dir', array( $this, 'files_upload_dir' ) );
        $mfile = wp_handle_upload( $file, $upload_overrides );
        remove_filter( 'upload_dir', array( $this, 'files_upload_dir' ) );

        if ( isset( $mfile[ 'error' ] ) ) {

            if ( empty( $mfile[ 'error' ] ) ) {
                $mfile[ 'error' ] = _x( 'Could not upload file.', 'error', 'groundhogg' );
            }

            return new WP_Error( 'BAD_UPLOAD', $mfile[ 'error' ] );
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
        $param[ 'path' ] = $this->uploads_path[ 'path' ];
        $param[ 'url' ] = $this->uploads_path[ 'url' ];
        $param[ 'subdir' ] = $this->uploads_path[ 'subdir' ];

        return $param;
    }

    /**
     * Initialize the base upload path
     */
    private function set_uploads_path()
    {
        $this->uploads_path[ 'subdir' ] = Plugin::$instance->utils->files->get_base_uploads_dir();
        $this->uploads_path[ 'path' ] = Plugin::$instance->utils->files->get_csv_imports_dir();
        $this->uploads_path[ 'url' ] = Plugin::$instance->utils->files->get_csv_imports_url();
    }

    /**
     * map the import
     */
    public function process_import_map()
    {
        if ( !current_user_can( 'import_contacts' ) ) {
            $this->wp_die_no_access();
        }

        $map = map_deep( get_post_var( 'map' ), 'sanitize_text_field' );

        if ( !is_array( $map ) ) {
            wp_die( 'Invalid map provided.' );
        }

        $file_name = sanitize_file_name( get_post_var( 'import' ) );

        $tags = [ sprintf( '%s - %s', __( 'Import' ), date_i18n( 'Y-m-d H:i:s' ) ) ];

        if ( isset_not_empty( $_POST, 'tags' ) ) {
            $tags = array_merge( $tags, get_post_var( 'tags' ) );
        }

        $tags = Plugin::$instance->dbs->get_db( 'tags' )->validate( $tags );

        set_transient( 'gh_import_tags', $tags, HOUR_IN_SECONDS );
        set_transient( 'gh_import_map', $map, HOUR_IN_SECONDS );

        if ( get_request_var( 'is_confirmed' ) ) {
            set_transient( 'gh_import_confirm_contacts', true, HOUR_IN_SECONDS );
        }

        $this->importer->start( [ 'import' => $file_name ] );
    }

    /**
     * @return int delete the files
     */
    public function process_import_delete()
    {
        $files = $this->get_items();

        foreach ( $files as $file_name ) {
            $filepath = Plugin::$instance->utils->files->get_csv_imports_dir( $file_name );
            if(file_exists( $filepath )) {
                unlink( $filepath );
            }
        }

        $this->add_notice( 'file_removed', __( 'Imports deleted.', 'groundhogg' ) );
        return admin_url( 'admin.php?page=gh_tools&action=add&tab=import' );
    }

    ####### EXPORT TAB FUNCTIONS #########

    /**
     * Exports tab view
     */
    public function export_view()
    {
        if ( !class_exists( 'Exports_Table' ) ) {
            require_once dirname( __FILE__ ) . '/exports-table.php';
        }

        $table = new Exports_Table(); ?>
        <form method="post" class="wp-clearfix">
            <?php $table->prepare_items(); ?>
            <?php $table->display(); ?>
        </form>
        <?php
    }

    /**
     * @return int delete the files
     */
    public function process_export_delete()
    {
        $files = $this->get_items();

        foreach ( $files as $file_name ) {
            $filepath = Plugin::$instance->utils->files->get_csv_exports_dir( $file_name );
            if ( file_exists( $filepath ) ) {
                unlink( $filepath );
            }
        }

        $this->add_notice( 'file_removed', __( 'Exports deleted.', 'groundhogg' ) );
        return admin_url( 'admin.php?page=gh_tools&action=add&tab=export' );
    }

    ####### UPDATES TAB FUNCTIONS #########

    public function updates_view()
    {
        ?>
        <div id="poststuff">
            <div class="postbox">
                <h2 class="hndle"><?php _e( 'Previous Updates', 'groundhogg' ); ?></h2>
                <div class="inside">
                    <p class="description"><?php _e( 'Run previous update paths in case of a failed update.', 'groundhogg' ); ?></p>
                    <?php

                    $updates = Plugin::$instance->updater->get_updates();

                    foreach ( $updates as $update ):

                        ?><p><?php

                            echo html()->e( 'a', [ 'href' => add_query_arg( [
                                'manual_update_nonce' => wp_create_nonce( 'gh_manual_update' ),
                                'updater' => 'main',
                                'manual_update' => $update
                            ], $_SERVER[ 'REQUEST_URI' ] ) ], sprintf( __( 'Run update to version %s', 'groundhogg' ), $update ) )

                        ?></p><?php

                    endforeach;

                    ?>
                </div>
            </div>
        </div>
        <?php
    }

    ####### DELETE TAB FUNCTIONS #########

    public function delete_warning()
    {
        if ( $this->get_current_tab() === 'delete' ) {
            $this->add_notice( 'no_going_back', __( '&#9888; There is no going back once the deletion process has started.', 'groudnhogg' ), 'warning' );
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
                        <?php echo Plugin::$instance->utils->html->input( [
                            'type' => 'hidden',
                            'name' => 'action',
                            'value' => 'bulk_delete',
                        ] ); ?>
                        <?php echo Plugin::$instance->utils->html->tag_picker( [] ); ?>
                        <p>
                            &#9888;&nbsp;<b><?php _e( 'Once you click the delete button there is no going back!' ); ?></b>
                        </p>
                        <p class="submit" style="text-align: center;padding-bottom: 0;margin: 0;">
                            <button style="width: 100%" class="button-primary" name="delete_contacts"
                                    value="delete"><?php _ex( 'Delete Contacts', 'action', 'groundhogg' ); ?></button>
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
    public function process_delete_bulk_delete()
    {
        $tags = Plugin::$instance->dbs->get_db( 'tags' )->validate( $_POST[ 'tags' ] );
        $this->deleter->start( [ 'tags_include' => implode( ',', $tags ) ] );
    }

    /**
     * Get the menu order between 1 - 99
     *
     * @return int
     */
    public function get_priority()
    {
        return 98;
    }
}