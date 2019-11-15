<?php

namespace Groundhogg\Admin\Funnels;

use Groundhogg\Admin\Admin_Page;
use Groundhogg\Funnel;
use Groundhogg\Library;
use function Groundhogg\get_array_var;
use function Groundhogg\get_db;
use function Groundhogg\get_post_var;
use function Groundhogg\get_store_products;
use function Groundhogg\enqueue_groundhogg_modal;
use function Groundhogg\get_request_var;
use function Groundhogg\get_upload_wp_error;
use function Groundhogg\html;
use function Groundhogg\is_option_enabled;
use Groundhogg\Plugin;
use Groundhogg\Contact_Query;
use Groundhogg\Step;
use function Groundhogg\isset_not_empty;

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * View Funnels
 *
 * Allow the user to view & edit the funnels
 *
 * @package     groundhogg
 * @subpackage  Includes/Funnels
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */
class Funnels_Page extends Admin_Page
{
    /**
     * @var
     */
    public $reporting_enabled = false;

    protected function add_ajax_actions()
    {
        add_action( 'wp_ajax_gh_get_templates', array( $this, 'get_funnel_templates_ajax' ) );
        add_action( 'wp_ajax_gh_save_funnel_via_ajax', array( $this, 'ajax_save_funnel' ) );
        add_action( 'wp_ajax_wpgh_get_step_html', array( $this, 'add_step' ) );
        add_action( 'wp_ajax_wpgh_delete_funnel_step', array( $this, 'delete_step' ) );
        add_action( 'wp_ajax_wpgh_duplicate_funnel_step', array( $this, 'duplicate_step' ) );
        add_action( 'wp_ajax_gh_add_contacts_to_funnel', array( $this, 'add_contacts_to_funnel' ) );
    }

    /**
     * @return bool
     */
    public function is_v2()
    {
        return absint( get_request_var( 'version' ) ) !== 1;
    }

    public function admin_title( $admin_title, $title )
    {
        switch ( $this->get_current_action() ) {
            case 'add':
                $admin_title = sprintf( "%s &lsaquo; %s", __( 'Add' ), $admin_title );
                break;
            case 'edit':
                $funnel_id = get_request_var( 'funnel' );
                $funnel = Plugin::$instance->utils->get_funnel( absint( $funnel_id ) );
                $admin_title = sprintf( "%s &lsaquo; %s &lsaquo; %s", $funnel->get_title(), __( 'Edit' ), $admin_title );
                break;
        }

        return $admin_title;
    }

    /**
     * Redirect to the add screen if no funnels are present.
     */
    public function redirect_to_add()
    {
        if ( get_db( 'funnels' )->count() == 0 ) {
            die( wp_redirect( $this->admin_url( [ 'action' => 'add' ] ) ) );
        }
    }

    protected function add_additional_actions()
    {
        $this->setup_reporting();

        if ( $this->is_current_page() && $this->get_current_action() === 'view' ) {
            add_action( 'admin_init', [ $this, 'redirect_to_add' ] );
        }

        if ( $this->is_current_page() && $this->get_current_action() === 'edit' ) {
            add_action( 'in_admin_header', array( $this, 'prevent_notices' ) );
            /* just need to enqueue it... */
            enqueue_groundhogg_modal();
        }

        add_action( "groundhogg/admin/gh_funnels/before", function () {
            if ( !get_db( 'funnels' )->count( [ 'status' => 'active' ] ) ) {
                Plugin::$instance->notices->add( 'no_active_funnels', sprintf( '%s %s', __( 'You have no active funnels.' ), html()->e( 'a', [
                    'href' => admin_url( 'admin.php?page=gh_funnels&status=inactive' ),
                ], __( 'Activate a funnel!' ) ) ), 'warning' );
            }
        } );
    }

    public function get_slug()
    {
        return 'gh_funnels';
    }

    public function get_name()
    {
        return _x( 'Funnels', 'page_title', 'groundhogg' );

    }

    public function get_cap()
    {
        return 'edit_funnels';
    }

    public function get_item_type()
    {
        return 'funnel';
    }

    public function get_priority()
    {
        return 30;
    }

    /**
     * enqueue editor scripts
     */
    public function scripts()
    {
        if ( $this->get_current_action() === 'edit' ) {

            wp_enqueue_style( 'editor-buttons' );
            wp_enqueue_style( 'jquery-ui' );

            wp_enqueue_editor();
            wp_enqueue_script( 'wplink' );

            wp_enqueue_script( 'jquery-ui-sortable' );
            wp_enqueue_script( 'jquery-ui-draggable' );
            wp_enqueue_script( 'jquery-ui-datepicker' );

//           wp_enqueue_script( 'groundhogg-admin-link-picker' );
            wp_enqueue_script( 'sticky-sidebar' );

            if ( $this->is_v2() ) {
                wp_enqueue_style( 'groundhogg-admin-funnel-editor-v2' );
                wp_enqueue_script( 'groundhogg-admin-funnel-editor-v2' );
                wp_localize_script( 'groundhogg-admin-funnel-editor-v2', 'Funnel', [
                    'id' => absint( get_request_var( 'funnel' ) )
                ] );
            } else {
                wp_enqueue_style( 'groundhogg-admin-funnel-editor' );
                wp_enqueue_script( 'groundhogg-admin-funnel-editor' );
                wp_localize_script( 'groundhogg-admin-funnel-editor', 'Funnel', [
                    'id' => absint( get_request_var( 'funnel' ) )
                ] );
            }

            wp_enqueue_script( 'jquery-flot' );
            wp_enqueue_script( 'jquery-flot-categories' );

            wp_enqueue_script( 'groundhogg-admin-replacements' );
        }

        wp_enqueue_style( 'groundhogg-admin' );
    }

    public function help()
    {
        $screen = get_current_screen();

        $screen->add_help_tab(
            array(
                'id' => 'gh_overview',
                'title' => __( 'Overview' ),
                'content' => '<p>' . __( 'Here you can edit your funnels. A funnel is a set of steps which can run automation based on contact interactions with your site. You can view the number of active contacts in each funnel, as well as when it was created and last updated.', 'groundhogg' ) . '</p>'
                    . '<p>' . __( 'Funnels can be either Active/Inactive/Archived. If a funnel is Inactive, no contacts can enter and any contacts that may have been in the funnel will stop moving forward. The same goes for Archived funnels which simply do not show in the main list.', 'groundhogg' ) . '</p>'
            )
        );

        $screen->add_help_tab(
            array(
                'id' => 'gh_add',
                'title' => __( 'Add A Funnel' ),
                'content' => '<p>' . __( 'To create a new funnel, simply click the Add New Button in the top left and select a pre-built funnel template. If you have a funnel import file you can click the import tab and upload the funnel file which will auto generate a funnel for you.', 'groundhogg' ) . '</p>'
            )
        );

        $screen->add_help_tab(
            array(
                'id' => 'gh_edit',
                'title' => __( 'Editing' ),
                'content' => '<p>' . __( 'When editing a funnel you can add Funnel Steps. Funnel Steps are either Benchmarks or Actions. Benchmarks are whenever a Contact "does" something, while Actions are doing thing to a contact such as sending an email. Simply drag in the desired funnel steps in any order.', 'groundhogg' ) . '</p>'
                    . '<p>' . __( 'Actions are run sequentially, so when an action takes place, it simply loads the next action. That means if you need to change it you can!', 'groundhogg' ) . '</p>'
                    . '<p>' . __( 'Benchmarks are a bit different. If you have several benchmarks in a row, what happens is once one of them is completed by a contact the first action found proceeding that benchmark is launched, skipping all other benchmarks. That way you can have multiple automation triggers. ', 'groundhogg' ) . '</p>'
                    . '<p>' . __( 'Once a benchmark is complete all steps that are scheduled before that benchmark will stop immediately.', 'groundhogg' ) . '</p>'
            )
        );

        $screen->add_help_tab(
            array(
                'id' => 'gh_reporting',
                'title' => __( 'Reporting' ),
                'content' => '<p>' . __( 'To view funnel reporting, simply go to the editing screen of any funnel, and then toggle the Reporting/Editing switch in the reporting box. You can select the time range which you would like to view by using the dropdown on the left and click the filter button.', 'groundhogg' ) . '</p>'
            )
        );

    }

    public function get_pointers_add()
    {
        return [
            [
                'id' => 'default_funnel_templates',
                'screen' => $this->get_screen_id(),
                'target' => '#funnel-templates',
                'title' => 'Default Templates',
                'show_next' => true,
                'content' => "These are templates that we've created for you to get you started. The content contains instructions for what we've learned converts and turns leads into contacts.",
                'position' => [
                    'edge' => 'left', //top, bottom, left, right
                    'align' => 'middle' //top, bottom, left, right, middle
                ]
            ],
            [
                'id' => 'funnel_marketplace',
                'screen' => $this->get_screen_id(),
                'target' => '#funnel-marketplace',
                'title' => 'Funnel Marketplace',
                'show_next' => true,
                'content' => 'Browse the Groundhogg Marketplace for templates that fit your business niche.',
                'position' => [
                    'edge' => 'left',
                    'align' => 'middle'
                ]
            ],
            [
                'id' => 'import_funnel_template',
                'screen' => $this->get_screen_id(),
                'target' => '#funnel-import',
                'title' => 'Import Funnels',
                'show_next' => true,
                'content' => 'Downloaded a funnel from somewhere? Import it here.',
                'position' => [
                    'edge' => 'left', //top, bottom, left, right
                    'align' => 'middle' //top, bottom, left, right, middle
                ]
            ],
            [
                'id' => 'start_building_funnel',
                'screen' => $this->get_screen_id(),
                'target' => '#poststuff .postbox:first-child .button-primary',
                'title' => 'Start Building',
                'show_next' => false,
                'content' => "When you're ready to start building click this button to copy and edit the funnel template.",
                'position' => [
                    'edge' => 'left', //top, bottom, left, right
                    'align' => 'middle' //top, bottom, left, right, middle
                ]
            ],
        ];
    }

    public function get_pointers_edit()
    {
        return [
            [
                'id' => 'funnel_benchmarks',
                'screen' => $this->get_screen_id(),
                'target' => '#benchmarks',
                'title' => 'Benchmarks',
                'show_next' => true,
                'content' => "Benchmarks are used to trigger automation. Simply drag them into the funnel flow.",
                'position' => [
                    'edge' => 'right', //top, bottom, left, right
                    'align' => 'top' //top, bottom, left, right, middle
                ]
            ],
            [
                'id' => 'funnel_actions',
                'screen' => $this->get_screen_id(),
                'target' => '#actions',
                'title' => 'Actions',
                'show_next' => true,
                'content' => "Actions perform automation, like sending emails or text messages. Drag them into the funnel flow anywhere.",
                'position' => [
                    'edge' => 'right', //top, bottom, left, right
                    'align' => 'top' //top, bottom, left, right, middle
                ]
            ],
            [
                'id' => 'funnel_reporting',
                'screen' => $this->get_screen_id(),
                'target' => '#reporting',
                'title' => 'Reporting',
                'show_next' => true,
                'content' => "Enable reporting view to see how contacts are responding to your funnel.",
                'position' => [
                    'edge' => 'top', //top, bottom, left, right
                    'align' => 'middle' //top, bottom, left, right, middle
                ]
            ],
            [
                'id' => 'funnel_export',
                'screen' => $this->get_screen_id(),
                'target' => '#export',
                'title' => 'Sharing',
                'show_next' => true,
                'content' => "Share your funnel with clients and colleagues by sharing the download link.",
                'position' => [
                    'edge' => 'top', //top, bottom, left, right
                    'align' => 'left' //top, bottom, left, right, middle
                ]
            ],
            [
                'id' => 'funnel_status',
                'screen' => $this->get_screen_id(),
                'target' => '#status-toggle-switch',
                'title' => 'Status',
                'show_next' => true,
                'content' => "Turn your funnel on and off at the flick of a switch.",
                'position' => [
                    'edge' => 'right', //top, bottom, left, right
                    'align' => 'top' //top, bottom, left, right, middle
                ]
            ],
        ];
    }

    public function get_reporting_start_time()
    {
        return Plugin::$instance->reporting->get_start_time();
    }

    public function get_reporting_end_time()
    {
        return Plugin::$instance->reporting->get_end_time();
    }

    private function setup_reporting()
    {

        if ( get_request_var( 'reporting_on' ) ) {
            $this->reporting_enabled = true;
        }
    }

    /**
     * Get the current screen title based on the action
     */
    public function get_title()
    {
        switch ( $this->get_current_action() ) {
            case 'add':
                return _ex( 'Add Funnel', 'page_title', 'groundhogg' );
                break;
            case 'edit':
                return _ex( 'Edit Funnel', 'page_title', 'groundhogg' );
                break;
            case 'view':
            default:
                return _ex( 'Funnels', 'page_title', 'groundhogg' );
        }
    }

    public function process_delete()
    {
        if ( !current_user_can( 'delete_funnels' ) ) {
            $this->wp_die_no_access();
        }

        foreach ( $this->get_items() as $id ) {
            Plugin::$instance->dbs->get_db( 'funnels' )->delete( $id );
        }

        $this->add_notice(
            esc_attr( 'deleted' ),
            sprintf( _nx( 'Deleted %d funnel', 'Deleted %d funnels', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $this->get_items() ) ),
            'success'
        );

        return false;
    }

    public function process_restore()
    {
        if ( !current_user_can( 'edit_funnels' ) ) {
            $this->wp_die_no_access();
        }

        foreach ( $this->get_items() as $id ) {
            $args = array( 'status' => 'inactive' );
            Plugin::$instance->dbs->get_db( 'funnels' )->update( $id, $args );
        }

        $this->add_notice(
            esc_attr( 'restored' ),
            sprintf( _nx( 'Restored %d funnel', 'Deleted %d funnels', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $this->get_items() ) ),
            'success'
        );

        return false;
    }

    public function process_duplicate()
    {
        if ( !current_user_can( 'add_funnels' ) ) {
            $this->wp_die_no_access();
        }

        foreach ( $this->get_items() as $id ) {

            $funnel = new Funnel( $id );

            if ( !$funnel->exists() ) {
                continue;
            }

            $json = $funnel->export();

            $new_funnel = new Funnel();
            $id = $new_funnel->import( $json );

            $this->add_notice(
                esc_attr( 'duplicated' ),
                _x( 'Funnel duplicated', 'notice', 'groundhogg' ),
                'success'
            );

            $edit_url = $this->admin_url( [ 'action' => 'edit', 'funnel' => $id ] );

            if ( is_option_enabled( 'gh_use_builder_version_2' ) ) {
                $edit_url = add_query_arg( [ 'version' => '2' ], $edit_url );
            }

            return $edit_url;
        }

        return false;
    }

    /**
     * Archive a funnel
     *
     * @return bool
     */
    public function process_archive()
    {
        if ( !current_user_can( 'edit_funnels' ) ) {
            $this->wp_die_no_access();
        }

        foreach ( $this->get_items() as $id ) {
            $args = array( 'status' => 'archived' );
            Plugin::$instance->dbs->get_db( 'funnels' )->update( $id, $args );
        }

        $this->add_notice(
            esc_attr( 'archived' ),
            sprintf( _nx(
                'Archived %d funnel',
                'Archived %d funnels',
                count( $this->get_items() ),
                'notice', 'groundhogg' ),
                count( $this->get_items() ) ),
            'success'
        );

        return false;
    }


    /**
     * Process add action for the funnel.
     *
     * @return string|\WP_Error
     */
    public function process_add()
    {

        if ( !current_user_can( 'add_funnels' ) ) {
            $this->wp_die_no_access();
        }

        $funnel_id = false;

        if ( isset( $_POST[ 'funnel_template' ] ) ) {

//            include GROUNDHOGG_PATH . 'templates/assets/funnel-templates.php';
//
//            /* @var $funnel_templates array included from funnel-templates.php */
//            $template_name = get_post_var( 'funnel_template' );
//            if ( ! isset_not_empty( $funnel_templates, $template_name ) ){
//                return new \WP_Error( 'invalid_template', 'The requested template does not exist.' );
//            }
//
//            $file_name = basename( $funnel_templates[ $template_name ][ 'file' ] );
//
//            $file_path = GROUNDHOGG_PATH . "templates/assets/funnels/$file_name.funnel";
//
//            if ( ! file_exists( $file_path ) ){
//                return new \WP_Error( 'invalid_template', 'The requested template could not be read.' );
//            }

//            $json = file_get_contents( $file_path );

            $template_id = get_request_var( 'funnel_template' );
            $library = new Library();
            $template = $library->get_funnel_template($template_id);
//            var_dump($template);

            $json = json_encode($template->import_json);

//            var_dump($json);
//            wp_die('');


            $funnel_id = $this->import_funnel( json_decode( $json, true ) );

        } else if ( isset( $_POST[ 'funnel_id' ] ) ) {

            $from_funnel = absint( get_request_var( 'funnel_id' ) );
            $from_funnel = new Funnel( $from_funnel );

            $json = $from_funnel->export();
            $funnel_id = $this->import_funnel( $json );

        } else if ( isset( $_FILES[ 'funnel_template' ] ) ) {
            $file = get_array_var( $_FILES, 'funnel_template' );

            $file = map_deep( $file, 'sanitize_text_field' );

            $error = get_upload_wp_error( $file );

            if ( is_wp_error( $error ) ) {
                return $error;
            }

            $validate = wp_check_filetype_and_ext( $file[ 'tmp_name' ], $file[ 'name' ], [ 'funnel' => 'text/plain' ] );

            if ( $validate[ 'ext' ] !== 'funnel' || $validate[ 'type' ] !== 'text/plain' ) {
                return new \WP_Error( 'invalid_template', 'Please upload a valid funnel template.' );
            }

            $json = file_get_contents( $file[ 'tmp_name' ] );
            $json = json_decode( $json, true );

            if ( !$json ) {
                return new \WP_Error( 'invalid_json', 'Funnel template has invalid JSON.' );
            }

            $funnel_id = $this->import_funnel( $json );

        } else if ( $json = get_request_var( 'funnel_json' ) ) {

            $json = json_decode( $json, true );

            if ( !$json ) {
                return new \WP_Error( 'invalid_json', 'Invalid JSON provided.' );
            }

            $funnel_id = $this->import_funnel( $json );
        }

        if ( is_wp_error( $funnel_id ) ) {
            return $funnel_id;
        }

        if ( !isset( $funnel_id ) || empty( $funnel_id ) ) {
            return new \WP_Error( 'error', __( 'Could not create funnel.', 'groundhogg' ) );
        }

        $this->add_notice( esc_attr( 'created' ), _x( 'Funnel created', 'notice', 'groundhogg' ), 'success' );

        $edit_url = admin_url( 'admin.php?page=gh_funnels&action=edit&funnel=' . $funnel_id );

        if ( is_option_enabled( 'gh_use_builder_version_2' ) ) {
            $edit_url = add_query_arg( [ 'version' => '2' ], $edit_url );
        }

        return $edit_url;

    }

    /**
     * Deconstructs the given array and builds a full funnel.
     *
     * @param $import array|string
     * @return bool|int whether the import was successful or the ID
     */
    public function import_funnel( $import = array() )
    {

        if ( !current_user_can( 'import_funnels' ) ) {
            $this->wp_die_no_access();
        }

        $funnel = new Funnel();
        $id = $funnel->import( $import );

        return $id;
    }

    /**
     * Save the funnel via ajax...
     */
    public function ajax_save_funnel()
    {
        if ( !wp_doing_ajax() ) {
            return;
        }

        if ( !$this->verify_action() ) {
            wp_send_json_error();
        }

        $result = $this->process_edit();

        if ( is_wp_error( $result ) ) {
            $this->add_notice( $result );
        }

        $result = [];

        $result[ 'chartData' ] = $this->get_chart_data();

        if ( !$this->is_v2() ) {
            $result[ 'steps' ] = $this->get_step_html();
        } else {
            $result[ 'settings' ] = $this->get_step_html();
            $result[ 'sortable' ] = $this->get_step_sortable();
        }


        $this->send_ajax_response( $result );

    }

    public function get_step_html()
    {
        $funnel = new Funnel( absint( get_request_var( 'funnel' ) ) );
        $steps = $funnel->get_steps();

        $html = "";

        foreach ( $steps as $step ) {

            if ( !$this->is_v2() ) {
                $html .= $step->__toString();
            } else {
                ob_start();
                $step->html_v2();
                $html .= ob_get_clean();

            }

        }

        return $html;
    }

    public function get_step_sortable()
    {
        $funnel = new Funnel( absint( get_request_var( 'funnel' ) ) );
        $steps = $funnel->get_steps();

        $html = "";

        foreach ( $steps as $step ) {
            ob_start();
            $step->sortable_item();
            $html .= ob_get_clean();
        }

        return $html;
    }

    /**
     * Chart Data
     *
     * @var array
     */
    protected $chart_data = [];

    /**
     * The chart data
     *
     * @return array
     */
    public function get_chart_data()
    {
        if ( !empty( $this->chart_data ) ) {
            return $this->chart_data;
        }

        $funnel = new Funnel( absint( get_request_var( 'funnel' ) ) );
        $steps = $funnel->get_steps();

        $dataset1 = array();
        $dataset2 = array();

        foreach ( $steps as $i => $step ) {

            $query = new Contact_Query();

            $args = array(
                'report' => array(
                    'funnel' => $funnel->get_id(),
                    'step' => $step->get_id(),
                    'status' => 'complete',
                    'start' => $this->get_reporting_start_time(),
                    'end' => $this->get_reporting_end_time(),
                )
            );

            $count = count( $query->query( $args ) );

            $url = add_query_arg( $args, admin_url( 'admin.php?page=gh_contacts' ) );

            $dataset1[] = array( ( $i + 1 ) . '. ' . $step->get_title(), $count, $url );

            $args = array(
                'report' => array(
                    'funnel' => intval( $_REQUEST[ 'funnel' ] ),
                    'step' => $step->ID,
                    'status' => 'waiting'
                )
            );

            $count = count( $query->query( $args ) );

            $url = add_query_arg( $args, admin_url( 'admin.php?page=gh_contacts' ) );

            $dataset2[] = array( ( $i + 1 ) . '. ' . $step->get_title(), $count, $url );

        }

        $ds[] = array(
            'label' => _x( 'Completed Events', 'stats', 'groundhogg' ),
            'data' => $dataset1
        );

        $ds[] = array(
            'label' => __( 'Waiting Contacts', 'stats', 'groundhogg' ),
            'data' => $dataset2
        );

        $this->chart_data = $ds;

        return $ds;
    }

    /**
     * Save the funnel
     */
    public function process_edit()
    {
        if ( !current_user_can( 'edit_funnels' ) ) {
            $this->wp_die_no_access();
        }

        $funnel_id = absint( get_request_var( 'funnel' ) );
        $funnel = new Funnel( $funnel_id );

        if ( wp_verify_nonce( get_request_var( 'add_contacts_nonce' ), 'add_contacts_to_funnel' ) ) {
            if ( $funnel->is_active() ) {

                $query = [
                    'step_id' => absint( get_request_var( 'which_step' ) ),
                    'tags_include' => wp_parse_id_list( get_request_var( 'include_tags' ) ),
                    'tags_exclude' => wp_parse_id_list( get_request_var( 'exclude_tags' ) ),
                    'tags_include_needs_all' => absint( get_request_var( 'tags_include_needs_all' ) ),
                    'tags_exclude_needs_all' => absint( get_request_var( 'tags_exclude_needs_all' ) ),
                ];

                $query = array_filter( $query );

                Plugin::$instance->bulk_jobs->add_contacts_to_funnel->start( $query );
            } else {
                return new \WP_Error( 'inactive', __( 'You cannot do this while the funnel is not active.', 'groundhogg' ) );
            }
        }

        /* check if funnel is to big... */
        if ( count( $_POST, COUNT_RECURSIVE ) >= intval( ini_get( 'max_input_vars' ) ) ) {
            return new \WP_Error( 'post_too_big', _x( 'Your [max_input_vars] is too small for your funnel! You may experience odd behaviour and your funnel may not save correctly. Please <a target="_blank" href="http://www.google.com/search?q=increase+max_input_vars+php">increase your [max_input_vars] to at least double the current size.</a>.', 'notice', 'groundhogg' ) );
        }

        $title = sanitize_text_field( get_request_var( 'funnel_title' ) );
        $args[ 'title' ] = $title;

        $status = sanitize_text_field( get_request_var( 'funnel_status', 'inactive' ) );

        //do not update the status to inactive if it's not confirmed
        if ( $status === 'inactive' || $status === 'active' ) {
            $args[ 'status' ] = $status;
        }

        $args[ 'last_updated' ] = current_time( 'mysql' );

        $funnel->update( $args );

        //get all the steps in the funnel.
        $step_ids = wp_parse_id_list( get_request_var( 'step_ids' ) );

        if ( empty( $step_ids ) ) {
            return new \WP_Error( 'no_steps', 'Please add automation first.' );
        }

        $completed_steps = [];

        foreach ( $step_ids as $order => $stepId ) {

            $step = new Step( $stepId );

            $step->save();

            $completed_steps[] = $step;

        }

        $first_step = array_shift( $completed_steps );

        /* if it's not a bench mark then the funnel cant actually ever run */
        if ( !$first_step->is_benchmark() ) {
            return new \WP_Error( 'invalid_config', _x( 'Funnels must start with 1 or more benchmarks', 'warning', 'groundhogg' ) );
        }

        $this->add_notice( esc_attr( 'updated' ), _x( 'Funnel updated', 'notice', 'groundhogg' ), 'success' );

        return true;

    }

    public function add_step()
    {
        if ( !current_user_can( 'edit_funnels' ) ) {
            $this->wp_die_no_access();
        }

        /* exit out if not doing ajax */
        if ( !wp_doing_ajax() ) {
            return;
        }

        $step_type = get_request_var( 'step_type' );
        $step_order = absint( get_request_var( 'step_order' ) );

        if ( $this->is_v2() ) {
            $after_step = new Step( absint( get_request_var( 'after_step' ) ) );
            $step_order = $after_step->get_order() + 1;
        }

        $funnel_id = absint( get_request_var( 'funnel_id' ) );

        $elements = Plugin::$instance->step_manager->get_elements();

        $title = $elements[ $step_type ]->get_name();
        $step_group = $elements[ $step_type ]->get_group();

        $step = new Step();

        $step_id = $step->create( [
            'funnel_id' => $funnel_id,
            'step_title' => $title,
            'step_type' => $step_type,
            'step_group' => $step_group,
            'step_order' => $step_order,
        ] );

        if ( !$step_id || !$step->exists() ) {
            wp_send_json_error();
        }

        if ( !$this->is_v2() ) {
            ob_start();
            $step->html();
            $content = ob_get_clean();
            $this->send_ajax_response( [ 'html' => $content ] );
        } else {
            ob_start();
            $step->sortable_item();
            $sortable = ob_get_clean();
            ob_start();
            $step->html_v2();
            $settings = ob_get_clean();
            $this->send_ajax_response( [
                'sortable' => $sortable,
                'settings' => $settings,
                'id' => $step->get_id(),
            ] );
        }

        wp_send_json_error();
    }

    public function duplicate_step()
    {

        if ( !current_user_can( 'edit_funnels' ) ) {
            $this->wp_die_no_access();
        }

        /* exit out if not doing ajax */
        if ( !wp_doing_ajax() ) {
            return;
        }

        if ( !isset( $_POST[ 'step_id' ] ) ) {
            wp_send_json_error();
        }

        $step_id = absint( intval( $_POST[ 'step_id' ] ) );

        $step = Plugin::$instance->utils->get_step( $step_id );

        if ( !$step ) {
            wp_send_json_error();
        }

        $new_step = new Step();

        $new_step_id = $new_step->create( [
            'funnel_id' => $step->get_funnel_id(),
            'step_title' => sprintf( __( '%s - (copy)', 'groundhogg' ), $step->get_title() ),
            'step_type' => $step->get_type(),
            'step_group' => $step->get_group(),
            'step_status' => 'ready',
            'step_order' => $step->get_order() + 1,
        ] );

        if ( !$new_step_id || !$new_step->exists() ) {
            wp_send_json_error();
        }

        $meta = $step->get_meta();

        foreach ( $meta as $key => $value ) {
            $new_step->update_meta( $key, $value );
        }

        if ( !$this->is_v2() ) {
            ob_start();
            $new_step->html();
            $content = ob_get_clean();
            wp_send_json_success( [ 'data' => [ 'html' => $content ] ] );
        } else {
            ob_start();
            $new_step->sortable_item();
            $sortable = ob_get_clean();
            ob_start();
            $new_step->html_v2();
            $settings = ob_get_clean();
            $this->send_ajax_response( [
                'sortable' => $sortable,
                'settings' => $settings,
                'id' => $new_step->get_id(),
            ] );
        }

        wp_send_json_error();
    }

    /**
     * Ajax function to delete steps from the funnel view
     */
    public function delete_step()
    {

        if ( !current_user_can( 'edit_funnels' ) ) {
            $this->wp_die_no_access();
        }

        /* exit out if not doing ajax */
        if ( !wp_doing_ajax() ) {
            return;
        }


        $stepid = absint( get_request_var( 'step_id' ) );
        $step = Plugin::$instance->utils->get_step( $stepid );

        // Move contacts forward

        if ( $contacts = $step->get_waiting_contacts() ) {
            $next_step = $step->get_next_action();
            if ( $next_step instanceof Step && $next_step->is_active() ) {
                foreach ( $contacts as $contact ) {
                    $next_step->enqueue( $contact );
                }
            }
        }

        if ( Plugin::$instance->dbs->get_db( 'steps' )->delete( $stepid ) ) {
            wp_send_json_success( [ 'id' => $stepid ] );
        }

        wp_send_json_error();
    }

    /**
     * Quickly add contacts to a funnel VIA the funnel editor UI
     */
    public function add_contacts_to_funnel()
    {

        if ( !current_user_can( 'edit_contacts' ) ) {
            $this->wp_die_no_access();
        }

        $tags = array_map( 'intval', $_POST[ 'tags' ] );

        $query = new Contact_Query();
        $contacts = $query->query( array( 'tags_include' => $tags ) );

        $step = Plugin::$instance->utils->get_step( intval( $_POST[ 'step' ] ) );

        foreach ( $contacts as $contact ) {

            $contact = Plugin::$instance->utils->get_contact( $contact->ID );
            $step->enqueue( $contact );

        }

        $this->add_notice( 'contacts-added', sprintf( _nx( '%d contact added to funnel', '%d contacts added to funnel', count( $contacts ), 'notice', 'groundhogg' ), count( $contacts ) ), 'success' );

        ob_start();

        $this->add_notice();

        $content = ob_get_clean();

        wp_die( $content );

    }

    public function edit()
    {
        if ( !current_user_can( 'edit_funnels' ) ) {
            $this->wp_die_no_access();
        }

        if ( $this->is_v2() ) {
            include dirname( __FILE__ ) . '/funnel-editor-v2.php';
            return;
        }

        include dirname( __FILE__ ) . '/funnel-editor.php';

    }

    public function add()
    {
        if ( !current_user_can( 'add_funnels' ) ) {
            $this->wp_die_no_access();
        }

        include dirname( __FILE__ ) . '/add-funnel.php';
    }

    public function view()
    {
        if ( !class_exists( 'Funnels_Table' ) ) {
            include dirname( __FILE__ ) . '/funnels-table.php';
        }

        $funnels_table = new Funnels_Table();

        $funnels_table->views();
        $this->search_form( __( 'Search Funnels', 'groundhogg' ) );
        ?>
        <form method="post" class="wp-clearfix">
            <?php $funnels_table->prepare_items(); ?>
            <?php $funnels_table->display(); ?>
        </form>
        <?php
    }

    public function page()
    {
        if ( $this->get_current_action() === 'edit' ) {
            $this->edit();
            return;
        }

        parent::page();
    }

    /**
     * Prevent notices from other plugins appearing on the edit funnel screen as the break the format.
     */
    public function prevent_notices()
    {
        remove_all_actions( 'network_admin_notices' );
        remove_all_actions( 'user_admin_notices' );
        remove_all_actions( 'admin_notices' );
    }

    /**
     * Get template HTML via ajax
     */
    public function get_funnel_templates_ajax()
    {
        ob_start();

        $this->display_funnel_templates();
        $html = ob_get_clean();

        $response = array(
            'html' => $html
        );

        wp_send_json( $response );

    }

    public function display_funnel_templates( $args = array() )
    {
        $page = isset( $_REQUEST[ 'p' ] ) ? intval( $_REQUEST[ 'p' ] ) : '1';
        $args[ 'page' ] = $page;

        if ( isset( $_REQUEST[ 'tag' ] ) ) {
            $args[ 'tag' ] = urlencode( $_REQUEST[ 'tag' ] );
        }

        if ( isset( $_REQUEST[ 's' ] ) ) {
            $args[ 's' ] = urlencode( $_REQUEST[ 's' ] );
        }

        $args[ 'category' ] = 'templates';


        $products = get_store_products( $args );

        if ( is_object( $products ) && count( $products->products ) > 0 ) {

            foreach ( $products->products as $product ):
                ?>
                <div class="postbox" style="margin-right:20px;width: 400px;display: inline-block;">
                    <div class="">
                        <img height="200" src="<?php echo $product->info->thumbnail; ?>" width="100%">
                    </div>
                    <h2 class="hndle"><?php echo $product->info->title; ?></h2>
                    <div class="inside">
                        <p style="line-height:1.2em;  height:3.6em;  overflow:hidden;"><?php echo $product->info->excerpt; ?></p>

                        <?php $pricing = (array) $product->pricing;
                        if ( count( $pricing ) > 1 ) {

                            $price1 = min( $pricing );
                            $price2 = max( $pricing );

                            ?>
                            <a class="button-primary" target="_blank"
                               href="<?php echo $product->info->link; ?>"> <?php printf( _x( 'Buy Now ($%s - $%s)', 'action', 'groundhogg' ), $price1, $price2 ); ?></a>
                            <?php
                        } else {

                            $price = array_pop( $pricing );

                            if ( $price > 0.00 ) {
                                ?>
                                <a class="button-primary" target="_blank"
                                   href="<?php echo $product->info->link; ?>"> <?php printf( _x( 'Buy Now ($%s)', 'action', 'groundhogg' ), $price ); ?></a>
                                <?php
                            } else {
                                ?>
                                <a class="button-primary" target="_blank"
                                   href="<?php echo $product->info->link; ?>"> <?php _ex( 'Download', 'action', 'groundhogg' ); ?></a>
                                <?php
                            }
                        }

                        ?>
                    </div>
                </div>
            <?php endforeach;
        } else {
            ?>
            <p style="text-align: center;font-size: 24px;"><?php _ex( 'Sorry, no templates were found.', 'notice', 'groundhogg' ); ?></p> <?php
        }
    }

    /**
     * @return bool
     */
    public function is_reporting_enabled()
    {
        return (bool) get_request_var( 'reporting_on' );
    }

}