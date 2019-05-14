<?php
namespace Groundhogg\Admin\Funnels;
use Groundhogg\Admin\Admin_Page;
use Groundhogg\Funnel;
use Groundhogg\Manager;
use function Groundhogg\enqueue_groundhogg_modal;
use function Groundhogg\get_request_var;
use Groundhogg\Plugin;
use Groundhogg\Contact_Query;
use Groundhogg\Step;







// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


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
     * @var int
     */
    public $reporting_start_time;

    /**
     * @var int
     */
    public $reporting_end_time;

    /**
     * @var
     */
    public $reporting_enabled = false;

    protected function add_ajax_actions()
    {
        add_action( 'wp_ajax_gh_get_templates', array( $this, 'get_funnel_templates_ajax' ) );
        add_action( 'wp_ajax_gh_save_funnel_via_ajax', array( $this, 'ajax_save_funnel' ) );
        add_action( 'wp_ajax_wpgh_get_step_html', array( $this, 'add_step' ) );
        add_action( 'wp_ajax_wpgh_delete_funnel_step',  array( $this, 'delete_step' ) );
        add_action( 'wp_ajax_wpgh_duplicate_funnel_step',  array( $this, 'duplicate_step' ) );
        add_action( 'wp_ajax_gh_add_contacts_to_funnel',  array( $this, 'add_contacts_to_funnel' ) );

    }

    protected function add_additional_actions()
    {
        $this->setup_reporting();

        if ( $this->get_current_action() === 'edit' ){
            add_action( 'in_admin_header' , array( $this, 'prevent_notices' )  );
            /* just need to enqueue it... */
            enqueue_groundhogg_modal();
        }
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

    public function get_priority(){
        return 30;
    }

    /**
     * enqueue editor scripts
     */
	public function scripts()
    {
       if ( $this->get_current_action() === 'edit' ){

           wp_enqueue_style('editor-buttons');
           wp_enqueue_style( 'jquery-ui' );

           wp_enqueue_editor();
           wp_enqueue_script('wplink');

           wp_enqueue_script( 'jquery-ui-sortable' );
           wp_enqueue_script( 'jquery-ui-draggable' );
           wp_enqueue_script( 'jquery-ui-datepicker' );

           wp_enqueue_script( 'groundhogg-admin-link-picker' );
           wp_enqueue_script( 'sticky-sidebar' );
           wp_enqueue_script( 'jquery-sticky-sidebar' );

           wp_enqueue_style(  'groundhogg-admin-funnel-editor' );
           wp_enqueue_script( 'groundhogg-admin-funnel-editor' );

           wp_localize_script( 'groundhogg-admin-funnel-editor', 'Funnel', [
               'id' => absint( get_request_var( 'funnel' ) )
           ] );

           wp_enqueue_script( 'jquery-flot' );
           wp_enqueue_script( 'jquery-flot-categories' );
       }
    }

    public function help()
    {
        $screen = get_current_screen();

        $screen->add_help_tab(
            array(
                'id' => 'gh_overview',
                'title' => __('Overview'),
                'content' => '<p>' . __('Here you can edit your funnels. A funnel is a set of steps which can run automation based on contact interactions with your site. You can view the number of active contacts in each funnel, as well as when it was created and last updated.', 'groundhogg') . '</p>'
                    . '<p>' . __('Funnels can be either Active/Inactive/Archived. If a funnel is Inactive, no contacts can enter and any contacts that may have been in the funnel will stop moving forward. The same goes for Archived funnels which simply do not show in the main list.', 'groundhogg') . '</p>'
            )
        );

        $screen->add_help_tab(
            array(
                'id' => 'gh_add',
                'title' => __('Add A Funnel'),
                'content' => '<p>' . __('To create a new funnel, simply click the Add New Button in the top left and select a pre-built funnel template. If you have a funnel import file you can click the import tab and upload the funnel file which will auto generate a funnel for you.', 'groundhogg') . '</p>'
            )
        );

        $screen->add_help_tab(
            array(
                'id' => 'gh_edit',
                'title' => __('Editing'),
                'content' => '<p>' . __('When editing a funnel you can add Funnel Steps. Funnel Steps are either Benchmarks or Actions. Benchmarks are whenever a Contact "does" something, while Actions are doing thing to a contact such as sending an email. Simply drag in the desired funnel steps in any order.', 'groundhogg') . '</p>'
                    . '<p>' . __('Actions are run sequentially, so when an action takes place, it simply loads the next action. That means if you need to change it you can!', 'groundhogg') . '</p>'
                    . '<p>' . __('Benchmarks are a bit different. If you have several benchmarks in a row, what happens is once one of them is completed by a contact the first action found proceeding that benchmark is launched, skipping all other benchmarks. That way you can have multiple automation triggers. ', 'groundhogg') . '</p>'
                    . '<p>' . __('Once a benchmark is complete all steps that are scheduled before that benchmark will stop immediately.', 'groundhogg') . '</p>'
            )
        );

        $screen->add_help_tab(
            array(
                'id' => 'gh_reporting',
                'title' => __('Reporting'),
                'content' => '<p>' . __('To view funnel reporting, simply dgo to the editing screen of any funnel, and then toggle the Reporting/Editing switch in the reporting box. You can select the time range which you would like to view by using the dropdown on the left and click the filter button.', 'groundhogg') . '</p>'
            )
        );

    }

    public function get_reporting_range()
    {
        return ( isset( $_POST[ 'date_range' ] ) )? $_POST[ 'date_range' ] : 'this_week' ;
    }

    public function get_reporting_start_time()
    {
        return $this->reporting_start_time;
    }

    public function get_reporting_end_time()
    {
        return $this->reporting_start_time;
    }

    private function setup_reporting(){

        if ( isset( $_POST[ 'reporting_on' ] ) ){
            $this->reporting_enabled = true;
        }

        switch ( $this->get_reporting_range() ):
            case 'today';
                $this->reporting_start_time   = strtotime( 'today' );
                $this->reporting_end_time     = $this->reporting_start_time + DAY_IN_SECONDS;
                break;
            case 'yesterday';
                $this->reporting_start_time   = strtotime( 'yesterday' );
                $this->reporting_end_time     = $this->reporting_start_time + DAY_IN_SECONDS;
                break;
            default:
            case 'this_week';
                $this->reporting_start_time   = mktime(0, 0, 0, date("n"), date("j") - date("N") + 1);
                $this->reporting_end_time     = $this->reporting_start_time + WEEK_IN_SECONDS;
                break;
            case 'last_week';
                $this->reporting_start_time   = mktime(0, 0, 0, date("n"), date("j") - date("N") + 1) - WEEK_IN_SECONDS;
                $this->reporting_end_time     = $this->reporting_start_time + WEEK_IN_SECONDS;
                break;
            case 'last_30';
                $this->reporting_start_time   = Plugin::$instance->utils->date_time->round_to_day( time() - MONTH_IN_SECONDS );
                $this->reporting_end_time     = Plugin::$instance->utils->date_time->round_to_day( time() );
                break;
            case 'last_month';
                $this->reporting_start_time   = strtotime( 'first day of ' . date( 'F Y' , TIME() - MONTH_IN_SECONDS ) );
                $this->reporting_end_time     = $this->reporting_start_time + MONTH_IN_SECONDS;
                break;
            case 'this_month';
                $this->reporting_start_time   = strtotime( 'first day of ' . date( 'F Y') );
                $this->reporting_end_time     = $this->reporting_start_time + MONTH_IN_SECONDS;
                break;
            case 'this_quarter';
                $quarter            = Plugin::$instance->utils->date_time->get_dates_of_quarter();
                $this->reporting_start_time   = $quarter[ 'start' ];
                $this->reporting_end_time     = $quarter[ 'end' ];
                break;
            case 'last_quarter';
                $quarter            = Plugin::$instance->utils->date_time->get_dates_of_quarter( 'previous' );
                $this->reporting_start_time   = $quarter[ 'start' ];
                $this->reporting_end_time     = $quarter[ 'end' ];
                break;
            case 'this_year';
                $this->reporting_start_time   = mktime(0, 0, 0, 1, 1, date( 'Y' ) );
                $this->reporting_end_time     = $this->reporting_start_time + YEAR_IN_SECONDS;
                break;
            case 'last_year';
                $this->reporting_start_time   = mktime(0, 0, 0, 1, 1, date( 'Y' , time() - YEAR_IN_SECONDS ));
                $this->reporting_end_time     = $this->reporting_start_time + YEAR_IN_SECONDS;
                break;
            case 'custom';
                $this->reporting_start_time   = Plugin::$instance->utils->date_time->round_to_day( strtotime( get_request_var( 'custom_date_range_start' ) ) );
                $this->reporting_end_time     = Plugin::$instance->utils->date_time->round_to_day( strtotime( get_request_var( 'custom_date_range_end' ) ) );
                break;
            endswitch;
    }

    /**
     * Get the current screen title based on the action
     */
	public function get_title()
	{
		switch ( $this->get_current_action() ){
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

	public function process_delete ()
    {
        if ( ! current_user_can( 'delete_funnels' ) ){
            $this->wp_die_no_access();
        }

        foreach ( $this->get_items() as $id ){
            Plugin::$instance->dbs->get_db('funnels')->delete( $id );
        }

        $this->add_notice(
            esc_attr( 'deleted' ),
            sprintf( _nx( 'Deleted %d funnel', 'Deleted %d funnels', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $this->get_items() ) ),
            'success'
        );

        return true;
    }

    public function process_restore()
    {
        if ( ! current_user_can( 'edit_funnels' ) ){
            $this->wp_die_no_access();
        }

        foreach ( $this->get_items() as $id )
        {
            $args = array( 'status' => 'inactive' );
            Plugin::$instance->dbs->get_db('funnels')->update( $id, $args );
        }

        $this->add_notice(
            esc_attr( 'restored' ),
            sprintf( _nx( 'Restored %d funnel', 'Deleted %d funnels', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $this->get_items() ) ),
            'success'
        );

        return true;
    }


	public function process_duplicate ()
    {
        if ( ! current_user_can( 'add_funnels' ) ){
            $this->wp_die_no_access();
        }

        foreach ( $this->get_items() as $id ){

            $funnel = new Funnel( $id );

            if ( ! $funnel->exists() ){
                continue;
            }

            $json = $funnel->export();

            $new_funnel = new Funnel();
            $new_funnel->import( $json );
        }

        $this->add_notice(
            esc_attr( 'duplicated' ),
            _x( 'Funnel duplicated', 'notice', 'groundhogg' ),
            'success'
        );

        return true;
    }

    /**
     * Archive a funnel
     *
     * @return bool
     */
    public function process_archive()
    {
        if ( ! current_user_can( 'edit_funnels' ) ){
            $this->wp_die_no_access();
        }

        foreach ( $this->get_items() as $id ) {
            $args = array( 'status' => 'archived' );
            Plugin::$instance->dbs->get_db('funnels')->update( $id, $args );
        }

        $this->add_notice(
            esc_attr( 'archived' ),
            sprintf( _nx( 'Archived %d funnel', 'Archived %d funnels', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $this->get_items() ) ),
            'success'
        );

//        do_action( 'wpgh_archive_funnels' );  todo remove
        return true ;
    }


    /**
     * Process add action for the funnel.
     *
     * @return string|\WP_Error
     */
	public function process_add()
    {

        if ( ! current_user_can( 'add_funnels' ) ){
            $this->wp_die_no_access();
        }

        if ( isset( $_POST[ 'funnel_template' ] ) ){

            include GROUNDHOGG_PATH . 'templates/funnel-templates.php';

            /* @var $funnel_templates array included from funnel-templates.php */

            $json = file_get_contents( $funnel_templates[ $_POST['funnel_template'] ]['file'] );
            $funnel_id = $this->import_funnel( json_decode( $json, true ) );

        } else if ( isset( $_POST[ 'funnel_id' ] ) ) {

            $from_funnel = absint( $_POST[ 'funnel_id' ] );
            $from_funnel = new Funnel( $from_funnel );

            $json = $from_funnel->export();
            $funnel_id = $this->import_funnel( $json );

        } else if ( isset( $_FILES[ 'funnel_template' ] ) ) {
            if ($_FILES['funnel_template']['error'] == UPLOAD_ERR_OK && is_uploaded_file( $_FILES['funnel_template']['tmp_name'] ) ) {
                $json = file_get_contents($_FILES['funnel_template']['tmp_name']);
                $funnel_id = $this->import_funnel(json_decode($json, true));
            }
        } else {
            return new \WP_Error( 'error', __('Please select a template...' , 'groundhogg')  );
        }

        if ( ! isset( $funnel_id ) || empty( $funnel_id ) ){
            wp_die( 'Error creating funnel.' );
        }

        $this->add_notice( esc_attr( 'created' ), _x( 'Funnel created', 'notice', 'groundhogg' ), 'success' );

        return admin_url( 'admin.php?page=gh_funnels&action=edit&funnel=' .  $funnel_id ) ;

    }

    /**
     * Deconstructs the given array and builds a full funnel.
     *
     * @param $import array|string
     * @return bool|int whether the import was successful or the ID
     */
    public function import_funnel( $import = array() )
    {

        if ( ! current_user_can( 'import_funnels' ) ){
            $this->wp_die_no_access();
        }

        $funnel = new Funnel();
        $funnel->import( $import );

        return $funnel->import( $import );
    }

	/**
	 * Save the funnel via ajax...
	 */
    public function ajax_save_funnel()
    {
        if ( ! wp_doing_ajax() ){
		    return;
	    }

	    $this->process_edit();
//	    wp_die( 'hi' );

	    ob_start();

//        $this->notices->notices(); todo check
        $this->add_notice();

        $notices = ob_get_clean();

        ob_start();

        do_action('wpgh_funnel_steps_before' ); ?>

        <?php $steps = Plugin::$instance->dbs->get_db('steps')->query( [ 'funnel_id' => intval( $_REQUEST[ 'funnel' ] )  ]  );


        if ( empty( $steps ) ): ?>
            <div class="">
                <?php esc_html_e( 'Drag in new steps to build the ultimate sales machine!' , 'groundhogg'); ?>
            </div>
        <?php else:

            foreach ( $steps as $i => $step ):
//                $step = wpgh_get_funnel_step( $step->ID ); // todo check
                $step = Plugin::$instance->utils->get_step($step->ID);
                $step->html();
                // echo $step;
            endforeach;

        endif; ?>
        <?php do_action('wpgh_funnel_steps_after' );

        $steps = ob_get_clean();

        $response = array(
            'notices'   => $notices,
            'steps'     => $steps,
            'chartData' => $this->get_chart_data(),

        );

        wp_die( json_encode( $response ) );

    }

    public function get_chart_data()
    {
        /* Pass funnel ID to get Steps */
        $steps = Plugin::$instance->dbs->get_db('steps')->query( [
            'funnel_id' => intval(  $_REQUEST[ 'funnel' ] )
        ] );

        $dataset1 = array();

        foreach ( $steps as $i => $step ) {

            $query = new Contact_Query();

            $args = array(
                'report' => array(
                    'funnel' => intval(  $_REQUEST[ 'funnel' ] ),
                    'step'   => $step->ID,
                    'status' => 'complete',
                    'start'  => $this->reporting_start_time,
                    'end'    => $this->reporting_end_time,
                )
            );

            $count = count($query->query($args));

            $dataset1[] = array( ( $i + 1 ) .'. '. $step->step_title , $count );

            $args = array(
                'report' => array(
                    'funnel' => intval(  $_REQUEST[ 'funnel' ] ),
                    'step' => $step->ID,
                    'status' => 'waiting'
                )
            );

            $count = count($query->query($args));

            $dataset2[] = array( ( $i + 1 ) .'. '. $step->step_title , $count );

        }

        $ds[] = array(
            'label' => _x( 'Completed Events', 'stats', 'groundhogg' ),
            'data'  => $dataset1
        ) ;
        $ds[] = array(
            'label' => __( 'Waiting Contacts', 'stats', 'groundhogg' ),
            'data'  => $dataset2
        ) ;

        return $ds;
    }

    /**
     * Save the funnel
     */
    public function process_edit()
    {
        if ( ! current_user_can( 'edit_funnels' ) ){
            $this->wp_die_no_access();
        }

        if ( empty( $_POST ) ) {
            return new \WP_Error( 'no_post', "POST variable not found." );
        }

        /* check if funnel is to big... */
        if ( count( $_POST, COUNT_RECURSIVE ) >= intval( ini_get( 'max_input_vars' ) ) ){
            return new \WP_Error( 'post_too_big', _x( 'Your [max_input_vars] is too small for your funnel! You may experience odd behaviour and your funnel may not save correctly. Please <a target="_blank" href="http://www.google.com/search?q=increase+max_input_vars+php">increase your [max_input_vars] to at least double the current size.</a>.', 'notice', 'groundhogg' ) );
        }

        $funnel_id = intval( $_REQUEST[ 'funnel' ] );

        do_action( 'wpgh_before_save_funnel', $funnel_id );

        $title = sanitize_text_field( stripslashes( $_POST[ 'funnel_title' ] ) );

        $args[ 'title' ] = $title;

        if ( isset( $_POST[ 'funnel_status' ] ) ){
            $status = sanitize_text_field( $_POST[ 'funnel_status' ] );
            if ( $status !== 'active' ){
                $status = 'inactive';
            }
        } else {
            $status = 'inactive';
            $this->add_notice( esc_attr( 'inactive' ), _x( 'Funnel is currently inactive', 'notice','groundhogg' ), 'info' );
        }

        //do not update the status to inactive if it's not confirmed
        if ( $status === 'inactive' || $status === 'active' ){
            $args[ 'status' ] = $status;
        }

        $args[ 'last_updated' ] = current_time( 'mysql' );

        Plugin::$instance->dbs->get_db('funnels')->update( $funnel_id, $args );

        //get all the steps in the funnel.
        $steps = $_POST['steps'];

        if ( ! $steps ){
            wp_die( 'Please add automation first.' );
        }

        foreach ( $steps as $i => $stepId ) {
            $stepId = intval( $stepId );
            $step = Plugin::$instance->utils->get_step($stepId);

            //quick Order Hack to get the proper order of a step...

            $order = $i + 1;
            $title = sanitize_text_field( wp_unslash( $_POST[ $step->prefix( 'title' ) ] ) );

            $args = array(
                'step_title'     =>  $title,
                'step_order'     =>  $order,
                'step_status'    =>  'ready',
            );

            $step->update( $args );

            if ( isset( $_POST[ $step->prefix( 'blog_id' ) ] ) ){
                $step->update_meta( 'blog_id', intval( $_POST[ $step->prefix( 'blog_id' ) ] ) );
            } else {
                $step->delete_meta( 'blog_id' );
            }

            if ( isset( $_POST[ $step->prefix( 'closed' ) ] ) && ! empty(  $_POST[ $step->prefix( 'closed' ) ] ) ){
                $step->update_meta( 'is_closed', 1 );
            } else {
                $step->delete_meta( 'is_closed' );
            }

            do_action( "groundhogg/steps/{$step->type}/save", $step );
//            do_action( "groundhogg/steps/{$step->type}/save", $step );

        }

//        $first_step = wpgh_get_funnel_step( $steps[0] ); todo check

        $first_step = Plugin::$instance->utils->get_step( $step[0] ) ;
        /* if it's not a bench mark then the funnel cant actually ever run */
        if ( ! $first_step->is_benchmark() ){
            return new \WP_Error( 'bad-funnel', _x( 'Funnels must start with 1 or more benchmarks', 'notice', 'groundhogg' ));
        }

        do_action( 'wpgh_funnel_updated', $funnel_id ); //todo remove

        $this->add_notice( esc_attr( 'updated' ), _x( 'Funnel updated', 'notice', 'groundhogg' ), 'success' );

        return true;

    }

    public function autosave_funnel()
    {
        if ( ! wp_doing_ajax() ){
            return;
        }

        $this->process_edit();

        wp_die('Auto saved successfully...' );
    }

    public function add_step()
    {
        if ( ! current_user_can( 'edit_funnels' ) ){
            $this->wp_die_no_access();
        }

        /* exit out if not doing ajax */
        if ( ! wp_doing_ajax() ){
            return;
        }

        $content = '';
        $step_type      = get_request_var( 'step_type' );
        $step_order     = absint( get_request_var( 'step_order') );
        $funnel_id      = absint( get_request_var( 'funnel_id' ) );

        $elements = Plugin::$instance->step_manager->get_elements();

        $title = $elements[ $step_type ]->get_name();
        $step_group = $elements[ $step_type ]->get_group();

        $step_id = Plugin::$instance->dbs->get_db('steps')->add( [
            'funnel_id'     => $funnel_id,
            'step_title'    => $title,
            'step_type'     => $step_type,
            'step_group'    => $step_group,
            'step_order'    => $step_order,
        ] );



        if ( $step_id ){

            $step = Plugin::$instance->utils->get_step($step_id ) ;
//            wpgh_get_funnel_step( $step_id ); todo check

            ob_start();

            $step->html();

            $content = ob_get_clean();
        }


        wp_die( $content );
    }

    public function duplicate_step()
    {

        if ( ! current_user_can( 'edit_funnels' ) ){
             $this->wp_die_no_access();
        }

        /* exit out if not doing ajax */
        if ( ! wp_doing_ajax() ){
            return;
        }

        if ( ! isset( $_POST['step_id'] ) )
            wp_die( 'No Step.' );

        $step_id = absint( intval( $_POST['step_id'] ) );

        $step =  Plugin::$instance->utils->get_step($step_id ) ;

        if ( ! $step || empty( $step->funnel_id ) )
            wp_die( 'Could not find step...' );

        $content = '';

        $newID = Plugin::$instance->dbs->get_db('steps')->add( [
            'funnel_id'      => $step->funnel_id,
            'step_title'     => $step->title,
            'step_type'      => $step->type,
            'step_group'     => $step->group,
            'step_status'    => 'ready',
            'step_order'     => $step->order + 1,
        ] );

        if ( ! $newID )
            wp_die( 'Oops' );

        $meta = Plugin::$instance->dbs->get_db('stepmeta')->get_meta( $step_id );

        foreach ( $meta as $key => $value ) {
            Plugin::$instance->dbs->get_db('stepmeta')->update_meta( $newID, $key, $value[0] );
        }

        if ( $newID ){

            $step = Plugin::$instance->utils->get_step( $newID );

            ob_start();

            $step->html();

            $content = ob_get_clean();
        }

        wp_die( $content );
    }

    /**
     * Ajax function to delete steps from the funnel view
     */
    public function delete_step()
    {
        if ( ! current_user_can( 'edit_funnels' ) ){
            $this->wp_die_no_access();
        }

        /* exit out if not doing ajax */
        if ( ! wp_doing_ajax() ){
            return;
        }

        if ( ! isset( $_POST['step_id'] ) )
            wp_die( 'No Step.' );

        $stepid = absint( intval( $_POST['step_id'] ) );
        $step = Plugin::$instance->utils->get_step( $stepid );
        if ( $contacts = $step->get_waiting_contacts() ){
            $next_step = $step->get_next_action();
            if ( $next_step instanceof Step && $next_step->is_active() ){
                 foreach ( $contacts as $contact ){
                     $next_step->enqueue( $contact );
                 }
            }
        }

        wp_die( Plugin::$instance->dbs->get_db('steps')->delete( $stepid ) );
    }

    /**
     * Quickly add contacts to a funnel VIA the funnel editor UI
     */
    public function add_contacts_to_funnel()
    {

        if ( ! current_user_can( 'edit_contacts' ) ){
            $this->wp_die_no_access();
        }

        $tags = array_map( 'intval', $_POST[ 'tags' ] );

        $query = new Contact_Query();
        $contacts = $query->query( array( 'tags_include'  => $tags ) );

        $step = Plugin::$instance->utils->get_step( intval( $_POST[ 'step' ] ) );

        foreach ( $contacts as $contact ){

            $contact = Plugin::$instance->utils->get_contact( $contact->ID );
            $step->enqueue( $contact );

        }

        $this->add_notice( 'contacts-added', sprintf( _nx( '%d contact added to funnel', '%d contacts added to funnel', count( $contacts ), 'notice', 'groundhogg' ), count( $contacts ) ), 'success' );

        ob_start();

        $this->add_notice();

        $content = ob_get_clean();

        wp_die( $content );

    }


	private function table()
	{
		if ( ! class_exists( 'Funnels_Table' ) ){
			include dirname(__FILE__) . '/funnels-table.php';
		}

		$funnels_table = new Funnels_Table();

		$funnels_table->views(); ?>
        <form method="post" class="search-form wp-clearfix" >

			<?php $funnels_table->prepare_items(); ?>
			<?php $funnels_table->display(); ?>
        </form>

		<?php
	}

	public function edit(){
        if ( ! current_user_can( 'edit_funnels' ) ){
            $this->wp_die_no_access();
        }

		include dirname(__FILE__) . '/funnel-editor.php';

	}

	public function add(){
        if ( ! current_user_can( 'add_funnels' ) ){
            $this->wp_die_no_access();
        }

		include dirname(__FILE__) . '/add-funnel.php';
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

	public function view()
	{
	    $this->table();
	}

	public function page()
    {
        if ( $this->get_current_action() === 'edit' ){
            $this->edit();
            return;
        }

        parent::page(); // TODO: Change the autogenerated stub
    }

    /**
     * Get template HTML via ajax
     */
	public function get_funnel_templates_ajax( )
    {


        $args = array();
        $args =  array( 'category' => 'templates' );
        $args = array( 's' => $_POST[ 's' ]);
        ob_start();
        $this->display_funnel_templates( $args );
        $html = ob_get_clean();

        $response = array(
            'html'  => $html
        );

       wp_die( json_encode( $response ) );

    }

    public function display_funnel_templates( $args = array() )
    {
        $page = isset( $_REQUEST[ 'p' ] ) ? intval( $_REQUEST[ 'p' ] ) : '1';
        $args[ 'page' ] = $page ;

        if ( isset( $_REQUEST[ 'tag' ] ) ){
            $args[ 'tag' ] = urlencode( $_REQUEST[ 'tag' ] );
        }

        if ( isset( $_REQUEST[ 's' ] ) ){
            $args[ 's' ] = urlencode( $_REQUEST[ 's' ] );
        }

        $args[ 'category' ] = 'templates';


        $products = WPGH()->get_store_products( $args ); //todo

        if ( is_object( $products ) && count( $products->products ) > 0 ) {

            foreach ($products->products as $product):
                ?>
                <div class="postbox" style="margin-right:20px;width: 400px;display: inline-block;">
                    <div class="">
                        <img height="200" src="<?php echo $product->info->thumbnail; ?>" width="100%">
                    </div>
                    <h2 class="hndle"><?php echo $product->info->title; ?></h2>
                    <div class="inside">
                        <p style="line-height:1.2em;  height:3.6em;  overflow:hidden;"><?php echo $product->info->excerpt; ?></p>

                        <?php $pricing = (array)$product->pricing;
                        if (count($pricing) > 1) {

                            $price1 = min($pricing);
                            $price2 = max($pricing);

                            ?>
                            <a class="button-primary" target="_blank"
                               href="<?php echo $product->info->link; ?>"> <?php printf( _x('Buy Now ($%s - $%s)', 'action', 'groundhogg'), $price1, $price2 ); ?></a>
                            <?php
                        } else {

                            $price = array_pop($pricing);

                            if ($price > 0.00) {
                                ?>
                                <a class="button-primary" target="_blank"
                                   href="<?php echo $product->info->link; ?>"> <?php printf( _x( 'Buy Now ($%s)', 'action','groundhogg' ), $price ); ?></a>
                                <?php
                            } else {
                                ?>
                                <a class="button-primary" target="_blank"
                                   href="<?php echo $product->info->link; ?>"> <?php _ex('Download', 'action', 'groundhogg'); ?></a>
                                <?php
                            }
                        }

                        ?>
                    </div>
                </div>
            <?php endforeach;
        } else {
            ?> <p style="text-align: center;font-size: 24px;"><?php _ex( 'Sorry, no templates were found.', 'notice', 'groundhogg' ); ?></p> <?php
        }
    }


    public function is_reporting_enabled()
    {
        return false;// todo I returned false for this function call and its working.
    }



}