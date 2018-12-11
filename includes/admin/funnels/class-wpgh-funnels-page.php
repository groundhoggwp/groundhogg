<?php
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

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


class WPGH_Funnels_Page
{

    /**
     * @var WPGH_Notices
     */
    public $notices;

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

    /**
     * @var WPGH_Popup
     */
    public $popup;


    function __construct()
	{
	    add_action( 'admin_menu', array( $this, 'register' ) );

	    if ( is_admin() ){
            add_action( 'wp_ajax_gh_get_templates', array( $this, 'get_funnel_templates_ajax' ) );
            add_action( 'wp_ajax_gh_save_funnel_via_ajax', array( $this, 'ajax_save_funnel' ) );
            add_action( 'wp_ajax_wpgh_get_step_html', array( $this, 'add_step' ) );
            add_action( 'wp_ajax_wpgh_delete_funnel_step',  array( $this, 'delete_step' ) );
            add_action( 'wp_ajax_wpgh_duplicate_funnel_step',  array( $this, 'duplicate_step' ) );
            add_action( 'wp_ajax_gh_add_contacts_to_funnel',  array( $this, 'add_contacts_to_funnel' ) );
        }

		$this->notices = WPGH()->notices;

        $this->setup_reporting();

        if ( isset( $_GET['page'] ) && $_GET[ 'page' ] === 'gh_funnels' ){

			add_action( 'init' , array( $this, 'process_action' )  );
			add_action( 'admin_enqueue_scripts' , array( $this, 'scripts' )  );

            if ( $this->get_action() === 'edit' ){
                add_action( 'in_admin_header' , array( $this, 'prevent_notices' )  );
                /* just need to enqueue it... */
			    $this->popup = wpgh_enqueue_modal();

            }

		}
	}

    /**
     * enqueue editor scripts
     */
	public function scripts()
    {
       if ( $this->get_action() === 'edit' ){
           wp_enqueue_style('editor-buttons');
           wp_enqueue_style( 'jquery-ui' );


           wp_enqueue_editor();
           wp_enqueue_script('wplink');

           wp_enqueue_script( 'jquery-ui-sortable' );
           wp_enqueue_script( 'jquery-ui-draggable' );
           wp_enqueue_script( 'jquery-ui-datepicker' );

           wp_enqueue_script( 'link-picker', WPGH_ASSETS_FOLDER . '/js/admin/link-picker.min.js' );
//           wp_enqueue_script( 'sticky-sidebar', WPGH_ASSETS_FOLDER . '/lib/sticky-sidebar/sticky-sidebar.js' );
//           wp_enqueue_script( 'jquery-sticky-sidebar', WPGH_ASSETS_FOLDER . '/lib/sticky-sidebar/jquery.sticky-sidebar.js' );

           wp_enqueue_style( 'funnel-editor', WPGH_ASSETS_FOLDER . '/css/admin/funnel-editor.css', array(), filemtime(WPGH_PLUGIN_DIR . 'assets/css/admin/funnel-editor.css') );
           wp_enqueue_script( 'funnel-editor', WPGH_ASSETS_FOLDER . '/js/admin/funnel-editor.min.js', array(), filemtime(WPGH_PLUGIN_DIR . 'assets/js/admin/funnel-editor.min.js') );
           wp_enqueue_script( 'wpgh-flot-chart', WPGH_ASSETS_FOLDER . '/lib/flot/jquery.flot.min.js', array(), filemtime(WPGH_PLUGIN_DIR . 'assets/lib/flot/jquery.flot.min.js') );
           wp_enqueue_script( 'wpgh-flot-chart-categories', WPGH_ASSETS_FOLDER . '/lib/flot/jquery.flot.categories.js', array(), filemtime(WPGH_PLUGIN_DIR . 'assets/lib/flot/jquery.flot.categories.js') );

       }
    }


    public function register()
    {
        $page = add_submenu_page(
            'groundhogg',
            'Funnels',
            'Funnels',
            'edit_funnels',
            'gh_funnels',
            array($this, 'page')
        );

        if ( $this->get_action() !== 'edit' ){
            add_action("load-" . $page, array($this, 'help'));
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
                    . '<p>' . __('Once a benchmark is complete all elements that are scheduled before that benchmark will stop immediately.', 'groundhogg') . '</p>'
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
        return ( isset( $_POST[ 'date_range' ] ) )? $_POST[ 'date_range' ] : 'last_30' ;
    }

    private function setup_reporting(){

        if ( isset( $_POST[ 'reporting_on' ] ) ){
            $this->reporting_enabled = true;
        }

        switch ( $this->get_reporting_range() ):
            case 'last_24';
                $this->reporting_start_time = strtotime( '1 day ago' );
                $this->reporting_end_time   = time();
                break;
            case 'last_7';
                $this->reporting_start_time = strtotime( '7 days ago' );
                $this->reporting_end_time   = time();

                break;
            case 'last_30';
                $this->reporting_start_time = strtotime( '30 days ago' );
                $this->reporting_end_time   = time();

                break;
            case 'custom';
                $this->reporting_start_time = strtotime( $_POST['custom_date_range_start'] );
                $this->reporting_end_time   = strtotime( $_POST['custom_date_range_end'] );

                break;
            default:
                $this->reporting_start_time = strtotime( '1 day ago' );
                $this->reporting_end_time   = time();

                break;
        endswitch;
    }

	private function get_funnels()
	{
		$funnels = isset( $_REQUEST['funnel'] ) ? $_REQUEST['funnel'] : null;

		if ( ! $funnels )
			return false;

		return is_array( $funnels )? array_map( 'intval', $funnels ) : array( intval( $funnels ) );
	}

	private function get_action()
	{
		if ( isset( $_REQUEST['filter_action'] ) && ! empty( $_REQUEST['filter_action'] ) )
			return false;

		if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] )
			return $_REQUEST['action'];

		if ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] )
			return $_REQUEST['action2'];

		return false;
	}

    /**
     * Get the previous action
     *
     * @return mixed
     */
	private function get_previous_action()
	{
		$action = get_transient( 'gh_last_action' );

		delete_transient( 'gh_last_action' );

		return $action;
	}

    /**
     * Get the current screen title based on the action
     */
	private function get_title()
	{
		switch ( $this->get_action() ){
			case 'add':
				_e( 'Add Funnel' , 'groundhogg' );
				break;
			case 'edit':
				_e( 'Edit Funnel' , 'groundhogg' );
				break;
			default:
				_e( 'Funnels', 'groundhogg' );
		}
	}

    /**
     * Process the current action whatever it may be
     */
	public function process_action()
	{
		if ( ! $this->get_action() || ! $this->verify_action() )
			return;

		$base_url = remove_query_arg( array( '_wpnonce', 'action' ), wp_get_referer() );

		switch ( $this->get_action() )
		{
			case 'add':

                if ( ! current_user_can( 'add_funnels' ) ){
                    wp_die( WPGH()->roles->error( 'add_funnels' ) );
                }

				if ( isset( $_POST ) ) {
                    $this->add_funnel();
                }

				break;

            case 'edit':

                if ( ! current_user_can( 'edit_funnels' ) ){
                    wp_die( WPGH()->roles->error( 'edit_funnels' ) );
                }

                if ( isset( $_POST ) ){
                    $this->save_funnel();
                }

                break;

            case 'duplicate':

                if ( ! current_user_can( 'add_funnels' ) ){
                    wp_die( WPGH()->roles->error( 'add_funnels' ) );
                }

                foreach ( $this->get_funnels() as $id ){
                    $json = wpgh_convert_funnel_to_json( $id );
                    $newId = $this->import_funnel( $json );
                }

                $this->notices->add(
                    esc_attr( 'duplicated' ),
                    __( 'Funnel Duplicated', 'groundhogg' ),
                    'success'
                );

                break;

            case 'archive':

                if ( ! current_user_can( 'edit_funnels' ) ){
                    wp_die( WPGH()->roles->error( 'edit_funnels' ) );
                }

				foreach ( $this->get_funnels() as $id ) {
				    $args = array( 'status' => 'archived' );
				    WPGH()->funnels->update( $id, $args );
				}

				$this->notices->add(
                    esc_attr( 'archived' ),
                    sprintf( "%s %d %s",
                        __( 'Archived', 'groundhogg' ),
                        count( $this->get_funnels() ),
                        'Funnels' ),
                    'success'
                );

				do_action( 'wpgh_archive_funnels' );

				break;

            case 'delete':

                if ( ! current_user_can( 'delete_funnels' ) ){
                    wp_die( WPGH()->roles->error( 'delete_funnels' ) );
                }

				foreach ( $this->get_funnels() as $id ){
					WPGH()->funnels->delete( $id );
				}

                $this->notices->add(
					esc_attr( 'deleted' ),
					sprintf( "%s %d %s",
						__( 'Deleted', 'groundhogg' ),
						count( $this->get_funnels() ),
						'Funnels' ),
					'success'
				);

				do_action( 'wpgh_delete_funnels' );

				break;

            case 'restore':

                if ( ! current_user_can( 'edit_funnels' ) ){
                    wp_die( WPGH()->roles->error( 'edit_funnels' ) );
                }

				foreach ( $this->get_funnels() as $id )
				{
                    $args = array( 'status' => 'inactive' );
                    WPGH()->funnels->update( $id, $args );
				}

                $this->notices->add(
					esc_attr( 'restored' ),
					sprintf( "%s %d %s",
						__( 'Restored', 'groundhogg' ),
						count( $this->get_funnels() ),
						'Funnels' ),
					'success'
				);

				do_action( 'wpgh_restore_funnels' );

				break;

            case 'export':

                if ( ! current_user_can( 'export_funnels' ) ){
                    wp_die( WPGH()->roles->error( 'export_funnels' ) );
                }

                $this->export_funnel();

                break;

        }

		set_transient( 'gh_last_action', $this->get_action(), 30 );

		if ( $this->get_action() === 'edit' || $this->get_action() === 'add' )
			return;

		$base_url = add_query_arg( 'ids', urlencode( implode( ',', $this->get_funnels() ) ), $base_url );

		wp_redirect( $base_url );
		die();
	}

    /**
     * Export a funnel
     */
	private function export_funnel()
    {

        if ( ! current_user_can( 'export_funnels' ) ){
            wp_die( WPGH()->roles->error( 'export_funnels' ) );
        }

        $id = intval( $_GET['funnel'] );

        $funnel = WPGH()->funnels->get_funnel( $id );

        if ( ! $funnel )
            return;

        $export_string = wpgh_convert_funnel_to_json( $id );

        if ( ! $export_string )
            return;

        $filename = "groundhogg_funnel-" . $funnel->title . ' - '. date("Y-m-d_H-i", time() );

        header("Content-type: text/plain");

        header( "Content-disposition: attachment; filename=".$filename.".funnel");

        $file = fopen('php://output', 'w');

        fputs( $file, $export_string );

        fclose($file);

        exit();
    }

	private function add_funnel()
    {

        if ( ! current_user_can( 'add_funnels' ) ){
            wp_die( WPGH()->roles->error( 'add_funnels' ) );
        }

        if ( isset( $_POST[ 'funnel_template' ] ) ){

            include WPGH_PLUGIN_DIR . '/templates/funnel-templates.php';

            /* @var $funnel_templates array included from funnel-templates.php */

            $json = file_get_contents( $funnel_templates[ $_POST['funnel_template'] ]['file'] );
            $funnel_id = $this->import_funnel( json_decode( $json, true ) );

        } else if ( isset( $_POST[ 'funnel_id' ] ) ) {

            $from_funnel = intval( $_POST[ 'funnel_id' ] );
            $json = wpgh_convert_funnel_to_json( $from_funnel );
            $funnel_id = $this->import_funnel( json_decode( $json, true ) );

        } else if ( isset( $_FILES[ 'funnel_template' ] ) ) {

            if ($_FILES['funnel_template']['error'] == UPLOAD_ERR_OK && is_uploaded_file( $_FILES['funnel_template']['tmp_name'] ) ) {

                $json = file_get_contents($_FILES['funnel_template']['tmp_name'] );
                $funnel_id = $this->import_funnel( json_decode( $json, true ) );
            }

        } else {

            $this->notices->add( esc_attr( 'error' ), __( 'Please select a template...', 'groundhogg' ), 'error' );
            return;

        }

        if ( ! isset( $funnel_id ) || empty( $funnel_id ) ){
            wp_die( 'Error creating funnel.' );
        }

        do_action( 'wpgh_funnel_created', $funnel_id );

        $this->notices->add( esc_attr( 'created' ), __( 'Funnel Created', 'groundhogg' ), 'success' );

        wp_redirect( admin_url( 'admin.php?page=gh_funnels&action=edit&funnel=' .  $funnel_id ) );
        die();

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
            wp_die( WPGH()->roles->error( 'import_funnels' ) );
        }

        return wpgh_import_funnel( $import );
    }

	/**
	 * Save the funnel via ajax...
	 */
    public function ajax_save_funnel()
    {
        if ( ! wp_doing_ajax() ){
		    return;
	    }

	    $this->save_funnel();
//	    wp_die( 'hi' );

	    ob_start();

        $this->notices->notices();

        $notices = ob_get_clean();

        ob_start();

        do_action('wpgh_funnel_steps_before' ); ?>

        <?php $steps = WPGH()->steps->get_steps( array( 'funnel_id' => intval( $_REQUEST[ 'funnel' ] ) ) );

        if ( empty( $steps ) ): ?>
            <div class="">
                <?php esc_html_e( 'Drag in new steps to build the ultimate sales machine!' , 'groundhogg'); ?>
            </div>
        <?php else:

            foreach ( $steps as $i => $step ):

                $step = new WPGH_Step( $step->ID );

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
        $steps = WPGH()->steps->get_steps( array(
            'funnel_id' => intval(  $_REQUEST[ 'funnel' ] )
        ) );

        $dataset1 = array();

        foreach ( $steps as $i => $step ) {

            $query = new WPGH_Contact_Query();

            $args = array(
                'report' => array(
                    'funnel' => intval(  $_REQUEST[ 'funnel' ] ),
                    'step' => $step->ID,
                    'status' => 'complete',
                    'start' => WPGH()->menu->funnels_page->reporting_start_time,
                    'end' => WPGH()->menu->funnels_page->reporting_end_time,
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
            'label' => __( 'Completed Events' ),
            'data'  => $dataset1
        ) ;
        $ds[] = array(
            'label' => __( 'Waiting Contacts' ),
            'data'  => $dataset2
        ) ;

        return $ds;
    }

    /**
     * Save the funnel
     */
    private function save_funnel()
    {
        if ( ! current_user_can( 'edit_funnels' ) ){
            wp_die( WPGH()->roles->error( 'edit_funnels' ) );
        }

        if ( empty( $_POST ) )
            return;

        /* check if funnel is to big... */
        if ( count( $_POST, COUNT_RECURSIVE ) >= intval( ini_get( 'max_input_vars' ) ) ){
            $this->notices->add( 'post_too_big', __( 'Your [max_input_vars] is too small for your funnel! You may experience odd behaviour and your funnel may not save correctly. Please <a target="_blank" href="http://www.google.com/search?q=increase+max_input_vars+php">increase your [max_input_vars] to at least double the current size.</a>.' ), 'warning' );
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

            $this->notices->add( esc_attr( 'inactive' ), __( 'Funnel no longer active.', 'groundhogg' ), 'info' );
        }

        //do not update the status to inactive if it's not confirmed
        if ( $status === 'inactive' || $status === 'active' ){
            $args[ 'status' ] = $status;
        }

        $args[ 'last_updated' ] = current_time( 'mysql' );

        WPGH()->funnels->update( $funnel_id, $args );

        //get all the steps in the funnel.
        $steps = $_POST['steps'];

        if ( ! $steps ){
            wp_die( 'Please add automation first.' );
        }

        foreach ( $steps as $i => $stepId ) {

            $stepId = intval( $stepId );
            $step = new WPGH_Step( $stepId );

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

            do_action( 'wpgh_save_step_' . $step->type, $step );

        }

        $first_step = new WPGH_Step( $steps[0] );

        /* if it's not a bench mark then the funnel cant actually ever run */
        if ( ! $first_step->is_benchmark() ){

            $this->notices->add( 'bad-funnel', __( 'Funnels must start with 1 or more benchmarks', 'groundhogg' ), 'error' );

        }

        do_action( 'wpgh_funnel_updated', $funnel_id );

        $this->notices->add( esc_attr( 'updated' ), __( 'Funnel Updated', 'groundhogg' ), 'success' );

    }

    public function autosave_funnel()
    {
        if ( ! wp_doing_ajax() ){
            return;
        }

        $this->save_funnel();

        wp_die('Auto saved successfully...' );
    }

    public function add_step()
    {

        if ( ! current_user_can( 'edit_funnels' ) ){
            wp_die( WPGH()->roles->error( 'edit_funnels' ) );
        }

        /* exit out if not doing ajax */
        if ( ! wp_doing_ajax() ){
            return;
        }

        $content = '';

        $step_type = $_POST['step_type'];
        $step_order = intval( $_POST['step_order'] );

        $funnel_id = intval( wpgh_extract_query_arg( wp_get_referer(), 'funnel' ) );

        $elements = WPGH()->elements->get_elements();
        $title = $elements[ $step_type ][ 'title' ];
        $step_group = $elements[ $step_type ][ 'group' ];

        $step_id = WPGH()->steps->add( array(
            'funnel_id'     => $funnel_id,
            'step_title'    => $title,
            'step_type'     => $step_type,
            'step_group'    => $step_group,
            'step_order'    => $step_order,
        ));

        if ( $step_id ){

            $step = new WPGH_Step( $step_id );

            ob_start();

            $step->html();

            $content = ob_get_clean();
        }

        wp_die( $content );
    }

    public function duplicate_step()
    {

        if ( ! current_user_can( 'edit_funnels' ) ){
            wp_die( WPGH()->roles->error( 'edit_funnels' ) );
        }

        /* exit out if not doing ajax */
        if ( ! wp_doing_ajax() ){
            return;
        }

        if ( ! isset( $_POST['step_id'] ) )
            wp_die( 'No Step.' );

        $step_id = absint( intval( $_POST['step_id'] ) );

        $step = new WPGH_Step( $step_id );

        if ( ! $step || empty( $step->funnel_id ) )
            wp_die( 'Could not find step...' );

        $content = '';

        $newID = WPGH()->steps->add( array(
            'funnel_id' => $step->funnel_id,
            'step_title'     => $step->title,
            'step_type'      => $step->type,
            'step_group'     => $step->group,
            'step_status'    => 'ready',
            'step_order'     => $step->order + 1,
        ) );

        if ( ! $newID )
            wp_die( 'Oops' );

        $meta = WPGH()->step_meta->get_meta( $step_id );

        foreach ( $meta as $key => $value ) {
            WPGH()->step_meta->update_meta( $newID, $key, $value[0] );
        }

        if ( $newID ){

            $step = new WPGH_Step( $newID );

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
            wp_die( WPGH()->roles->error( 'edit_funnels' ) );
        }

        /* exit out if not doing ajax */
        if ( ! wp_doing_ajax() ){
            return;
        }

        if ( ! isset( $_POST['step_id'] ) )
            wp_die( 'No Step.' );

        $stepid = absint( intval( $_POST['step_id'] ) );

        wp_die( WPGH()->steps->delete( $stepid ) );
    }

    /**
     * Quickly add contacts to a funnel VIQ the funnel editor UI
     */
    public function add_contacts_to_funnel()
    {

        if ( ! current_user_can( 'edit_contacts' ) ){
            wp_die( WPGH()->roles->error( 'edit_contacts' ) );
        }

        $tags = array_map( 'intval', $_POST[ 'tags' ] );

        $query = new WPGH_Contact_Query();
        $contacts = $query->query( array( 'tags_include'  => $tags ) );

        $step = new WPGH_Step( intval( $_POST[ 'step' ] ) );

        foreach ( $contacts as $contact ){

            $contact = new WPGH_Contact( $contact->ID );
            $step->enqueue( $contact );

        }

        $this->notices->add( 'contacts-added', sprintf( __( '%d contacts added to funnel!', 'groundhogg' ), count( $contacts ) ), 'success' );

        ob_start();

        $this->notices->notices();

        $content = ob_get_clean();

        wp_die( $content );

    }

    /**
     * Verify that the current action is authorized
     *
     * @return bool
     */
	public function verify_action()
	{
		if ( ! isset( $_REQUEST['_wpnonce'] ) )
			return false;

		return wp_verify_nonce( $_REQUEST[ '_wpnonce' ] ) || wp_verify_nonce( $_REQUEST[ '_wpnonce' ], $this->get_action() ) || wp_verify_nonce( $_REQUEST[ '_wpnonce' ], 'bulk-funnels' ) ;
	}

	private function table()
	{
		if ( ! class_exists( 'WPGH_Funnels_Table' ) ){
			include dirname(__FILE__) . '/class-wpgh-funnels-table.php';
		}

		$funnels_table = new WPGH_Funnels_Table();

		$funnels_table->views(); ?>
        <form method="post" class="search-form wp-clearfix" >
            <!-- search form -->
            <p class="search-box">
                <label class="screen-reader-text" for="post-search-input"><?php _e( 'Search Funnels ', 'groundhogg'); ?>:</label>
                <input type="search" id="post-search-input" name="s" value="">
                <input type="submit" id="search-submit" class="button" value="<?php _e( 'Search Funnels ', 'groundhogg'); ?>">
            </p>
			<?php $funnels_table->prepare_items(); ?>
			<?php $funnels_table->display(); ?>
        </form>

		<?php
	}

	private function edit(){
        if ( ! current_user_can( 'edit_funnels' ) ){
            wp_die( WPGH()->roles->error( 'edit_funnels' ) );
        }

		include dirname( __FILE__ ) . '/funnel-editor.php';

	}

	private function add(){
        if ( ! current_user_can( 'add_funnels' ) ){
            wp_die( WPGH()->roles->error( 'add_funnels' ) );
        }

		include dirname( __FILE__ ) . '/add-funnel.php';
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

	public function page()
	{

	    if ( $this->get_action() === 'edit' ){
            $this->edit();

        } else {
            ?>
            <div class="wrap">
                <h1 class="wp-heading-inline"><?php $this->get_title(); ?></h1><a class="page-title-action aria-button-if-js" href="<?php echo admin_url( 'admin.php?page=gh_funnels&action=add' ); ?>"><?php _e( 'Add New' ); ?></a>
                <div id="notices">
                    <?php $this->notices->notices(); ?>
                </div>
                <hr class="wp-header-end">
                <?php switch ( $this->get_action() ){
                    case 'add':
                        $this->add();
                        break;
                    case 'edit':
                        $this->edit();
                        break;
                    default:
                        $this->table();
                } ?>
            </div>
            <?php
        }

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

    /**
     * @param array $args
     * @return array|mixed|object
     */
	public function get_funnel_templates( $args=array() )
    {
        $args = wp_parse_args( $args, array(
            //'category' => 'templates',
            'category' => '',
            'tag'      => '',
            's'        => '',
            'page'     => '',
            'number'   => '-1'
        ) );

        $url = 'https://groundhogg.io/edd-api/v2/products/';

        $response = wp_remote_get( add_query_arg( $args, $url ) );
        $products = json_decode( wp_remote_retrieve_body( $response ) );

        return $products;
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

        $products = $this->get_funnel_templates( $args );

        if ( count($products->products) > 0 ) {

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
                               href="<?php echo $product->info->link; ?>"> <?php _e('Buy Now ($' . $price1 . ' - $' . $price2 . ')', 'groundhogg'); ?></a>
                            <?php
                        } else {

                            $price = array_pop($pricing);

                            if ($price > 0.00) {
                                ?>
                                <a class="button-primary" target="_blank"
                                   href="<?php echo $product->info->link; ?>"> <?php _e('Buy Now ($' . $price . ')', 'groundhogg'); ?></a>
                                <?php
                            } else {
                                ?>
                                <a class="button-primary" target="_blank"
                                   href="<?php echo $product->info->link; ?>"> <?php _e('Download', 'groundhogg'); ?></a>
                                <?php
                            }
                        }

                        ?>
                    </div>
                </div>
            <?php endforeach;
        } else {
            ?> <h1>No results found.</h1> <?php
        }
    }
}