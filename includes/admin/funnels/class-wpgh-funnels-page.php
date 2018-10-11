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


    function __construct()
	{
		if ( isset( $_GET['page'] ) && $_GET[ 'page' ] === 'gh_funnels' ){

			add_action( 'init' , array( $this, 'process_action' )  );
            add_action( 'wp_ajax_wpgh_auto_save_funnel_via_ajax', array( $this, 'autosave_funnel' ) );
            add_action( 'wp_ajax_wpgh_get_step_html', array( $this, 'add_step' ) );
            add_action( 'wp_ajax_wpgh_delete_funnel_step',  array( $this, 'delete_step' ) );

            $this->notices = WPGH()->notices;

            $this->setup_reporting();
		}
	}

    private function get_reporting_range()
    {
        return ( isset( $_POST[ 'date_range' ] ) )? $_POST[ 'date_range' ] : 'last_24' ;
    }

    private function setup_reporting(){

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
		if ( ! $this->get_action() || ! $this->verify_action() || ! current_user_can( 'gh_manage_funnels' ) )
			return;

		$base_url = remove_query_arg( array( '_wpnonce', 'action' ), wp_get_referer() );

		switch ( $this->get_action() )
		{
			case 'add':

				if ( isset( $_POST ) ) {
                    $this->add_funnel();
                }

				break;

            case 'edit':

                if ( isset( $_POST ) ){
                    $this->save_funnel();
                }

                break;

            case 'archive':

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
        if ( isset( $_POST[ 'funnel_template' ] ) ){

            include WPGH_PLUGIN_DIR . '/includes/templates/funnel-templates.php';

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
     * @param $import array
     * @return bool|int whether the import was successful or the ID
     */
    private function import_funnel( $import = array() )
    {
        if ( ! is_array( $import ) )
            return false;

        $title = $import[ 'title' ];

        $funnel_id = WPGH()->funnels->add( array( 'title' => $title, 'status' => 'inactive', 'author' => get_current_user_id() ) );

        $steps = $import[ 'steps' ];

        $valid_actions = WPGH()->elements->get_actions();
        $valid_benchmarks = WPGH()->elements->get_benchmarks();

        foreach ( $steps as $i => $step_args )
        {

            $step_title = $step_args['title'];
            $step_group = $step_args['group'];
            $step_type  = $step_args['type'];

            if ( ! isset( $valid_actions[$step_type] ) && ! isset( $valid_benchmarks[$step_type] ) )
                continue;

            $args = array(
                'funnel_id' => $funnel_id,
                'title'     => $step_title,
                'status'    => 'ready',
                'group'     => $step_group,
                'type'      => $step_type,
                'order'     => $i+1,
            );

            $step_id = WPGH()->steps->add( $args );

            $step_meta = $step_args['meta'];

            foreach ( $step_meta as $key => $value )
            {
                if ( is_array( $value ) ){
                    WPGH()->step_meta->update_meta( $step_id, $key, $value[0] );
                } else {
                    WPGH()->step_meta->update_meta( $step_id, $key, $value );
                }
            }

            $import_args = $step_args[ 'args' ];

            $step = new WPGH_Step( $step_id );

            do_action( 'wpgh_import_step_' . $step_type, $import_args, $step );

        }

        return $funnel_id;
    }

    private function save_funnel()
    {

        if ( empty( $_POST ) )
            return;

        $funnel_id = intval( $_REQUEST[ 'funnel' ] );

        do_action( 'wpgh_before_save_funnel', $funnel_id );

        $title = sanitize_text_field( stripslashes( $_POST[ 'funnel_title' ] ) );

        $args[ 'title' ] = $title;

        /* do NOT update status during an autosave... */
        if ( ! wp_doing_ajax() ){
            $status = sanitize_text_field( $_POST[ 'funnel_status' ] );
            if ( $status !== 'active' && $status !== 'inactive' )
                $status = 'inactive';

            //do not update the status to inactive if it's not confirmed
            if ( ( $status === 'inactive' && isset( $_POST['confirm'] ) && $_POST['confirm'] === 'yes' ) || $status === 'active' ){
                $args[ 'status' ] = $status;
            }
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
                'title'     =>  $title,
                'order'     =>  $order,
                'status'    =>  'ready',
            );

            $step->update( $args );

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
        /* exit out if not doing ajax */
        if ( ! wp_doing_ajax() ){
            return;
        }

        $content = '';

        $step_type = $_POST['step_type'];
        $step_order = intval( $_POST['step_order'] );

        $funnel_id = intval( $_REQUEST[ 'funnel' ] );

        $elements = WPGH()->elements->get_elements();
        $title = $elements[ $step_type ][ 'title' ];
        $step_group = $elements[ $step_type ][ 'group' ];

        $step_id = WPGH()->steps->add( array(
            'funnel_id' => $funnel_id,
            'title'     => $title,
            'group'     => $step_group,
            'order'     => $step_order,
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

        $newID = WPGH()->steps->add( array(
            'funnel_id' => $step->funnel_id,
            'title'     => $step->title,
            'type'      => $step->type,
            'group'     => $step->group,
            'status'    => 'ready',
            'order'     => $step->order + 1,
        ) );

        if ( ! $newID )
            wp_die( 'Oops' );

        $meta = WPGH()->step_meta->get_meta( $step_id );

        foreach ( $meta as $key => $value ) {
            WPGH()->step_meta->update_meta( $newID, $key, $value[0] );
        }

        $new_step = new WPGH_Step( $newID );

        wp_die( $new_step );
    }

    /**
     * Ajax function to delete steps from the funnel view
     */
    public function delete_step()
    {
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
		include dirname( __FILE__ ) . '/funnel-editor.php';

	}

	private function add(){
		include dirname( __FILE__ ) . '/add-funnel.php';
	}

	public function page()
	{
		?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php $this->get_title(); ?></h1><a class="page-title-action aria-button-if-js" href="<?php echo admin_url( 'admin.php?page=gh_funnels&action=add' ); ?>"><?php _e( 'Add New' ); ?></a>
			<?php $this->notices->notices(); ?>
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