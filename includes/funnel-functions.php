<?php
/**
 * Funnel Functions
 *
 * Fucntions to help the funnel builder do it's thing
 *
 * @package     groundhogg
 * @subpackage  Includes/Funnels
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

/**
 * Set a cookie to track which funnel the contact came from
 *
 * @param $id int the ID of the funnel
 *
 * @return int|false the given ID
 */
function wpgh_set_the_funnel( $id )
{
    if ( ! is_numeric( $id )  )
        return false;

    setcookie( 'gh_funnel', wpgh_encrypt_decrypt( $id, 'e' ) , time() + 24 * HOUR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );

    return $id;
}

/**
 * Get the funnel the user came from
 *
 * @return bool
 */
function wpgh_get_current_funnel()
{
    if ( is_admin() )
        return false;

    if ( isset( $_COOKIE[ 'gh_funnel' ] ) ){
        return intval( wpgh_encrypt_decrypt( $_COOKIE[ 'gh_funnel' ], 'd' ) );
    } else if ( isset( $_GET['funnel'] ) ){
        return intval( $_GET['funnel'] );
    }

    return false;
}

/**
 * Set a cookie to track which step they came from...
 *
 * @param $id int the ID of the step
 *
 * @return int|false the given ID
 */
function wpgh_set_the_step( $id )
{
    if ( ! is_numeric( $id )  )
        return false;

    setcookie( 'gh_step', wpgh_encrypt_decrypt( $id, 'e' ) , time() + 24 * HOUR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );

    return $id;
}

/**
 * Get the funnel step the user came from
 *
 * @return bool
 */
function wpgh_get_current_step()
{
    if ( is_admin() )
        return false;

    if ( isset( $_COOKIE[ 'gh_step' ] ) ){
        return intval( wpgh_encrypt_decrypt( $_COOKIE[ 'gh_step' ], 'd' ) );
    } else if ( isset( $_GET['step'] ) ) {
        return intval( $_GET['step'] );
    }

    return false;
}

/**
 * Get the icon for a step
 *
 * @param $step_id int the Id of the step
 * @return string the URL of the icon.
 */
function wpgh_get_step_icon( $step_id )
{

    $step_group = wpgh_get_step_group( $step_id );

    $step_type = wpgh_get_step_type( $step_id );

    if ( $step_group === 'benchmark' ){

        $benchmarks = wpgh_get_funnel_benchmarks();

        return $benchmarks[$step_type]['icon'];

    } else {

        $actions = wpgh_get_funnel_actions();

        return $actions[$step_type]['icon'];

    }
}
/**
 * Get the Name of a funnel
 *
 * @param $funnel_id int the ID of the funnel
 *
 * @return int|false the Name of the funnel or false if the funnel DNE
 */
function wpgh_get_funnel_name( $funnel_id )
{
    $funnel = wpgh_get_funnel_by_id( $funnel_id );

    if ( ! $funnel )
        return false;

    return $funnel->funnel_title;
}

/**
 * Get the status of a funnel
 *
 * @param $funnel_id int the ID of the funnel
 *
 * @return int|false the Name of the funnel or false if the funnel DNE
 */
function wpgh_get_funnel_status( $funnel_id )
{
    $funnel = wpgh_get_funnel_by_id( $funnel_id );

    if ( ! $funnel )
        return false;

    return $funnel->funnel_status;
}

/**
 * Get an array of associated funnel steps
 *
 * @param $funnel_id int the ID of the funnel
 * @return array|false an array of step IDs, false on failure
 */
function wpgh_get_funnel_steps( $funnel_id )
{
    $steps = wpgh_get_funnel_steps_by_funnel_id( $funnel_id );

    if ( ! $steps )
        return false;

    //var_dump( $steps );

    $idarray = array();

    foreach ($steps as $item) {
        $idarray[] = intval( $item['ID'] );
    }

    return $idarray;
}

/**
 * Get the handle of a certain step action.
 *
 * For example, given an ID, if the type is send_email, then it would return 'Send an Email'
 *
 * @param $step_id int the ID of the funnel step
 *
 * @return string the step's handle
 */
function wpgh_get_step_hndle( $step_id )
{
    //todo Set up some sort of easy way, hardcode?

    $step = wpgh_get_funnel_step_by_id( $step_id );

    if ( ! $step )
        return false;

    return $step->funnelstep_title;
}

/**
 * Get the HTML settings for the step in question.
 *
 * For example, given a step of the type send_email, it would return settings related to sending an email.
 *
 * @param $step_id int the ID of the step.
 */
function wpgh_get_step_settings( $step_id )
{
    //todo, replaced with an action instead? May need later and will keep for now
}

/**
 * Get the type of a step.
 *
 * @param $step_id int the ID of the step
 *
 * @return int|false the Name of the funnel or false if the funnel DNE
 */
function wpgh_get_step_type( $step_id )
{
    $step = wpgh_get_funnel_step_by_id( $step_id );

    if ( ! $step )
        return false;

    return $step->funnelstep_type;
}

/**
 * Get the group of a funnel step, either action or benckmark
 *
 * @param $step_id int the ID of the step
 * @return bool|string the group of the step or false on failure
 */
function wpgh_get_step_group( $step_id )
{
    $step = wpgh_get_funnel_step_by_id( $step_id );

    if ( ! $step )
        return false;

    return $step->funnelstep_group;
}

/**
 * Get the order of a funnel step, either action or benchmark
 *
 * @param $step_id int the ID of the step
 * @return bool|int the order of the step of false on failure
 */
function wpgh_get_step_order( $step_id )
{
    $step = wpgh_get_funnel_step_by_id( $step_id );

    if ( ! $step )
        return false;

    return intval( $step->funnelstep_order );
}

/**
 * Get the funnel ID of a funnel step, either action or benchmark
 *
 * @param $step_id int the ID of the step
 * @return bool|int the Id of the funnel or false on failure
 */
function wpgh_get_step_funnel( $step_id )
{
    $step = wpgh_get_funnel_step_by_id( $step_id );

    if ( ! $step )
        return false;

    return intval( $step->funnel_id );
}

/**
 * Get the next action in a funnel, or false if benchmark
 *
 * @param $step_id int the ID of the step
 * @return false|int the Id of the next step, or false if there is not next action or the next step is a benchmark
 */
function wpgh_get_next_funnel_action( $step_id )
{
    $funnel_id = wpgh_get_step_funnel( $step_id );
    $step_order = wpgh_get_step_order( $step_id );
    $step_group = wpgh_get_step_group( $step_id );

    $next_steps = wpgh_get_funnel_steps_by_order( $funnel_id, $step_order );

    //var_dump( $next_steps );

    if ( ! $next_steps )
        return false;

    if ( $step_group === 'benchmark' ){
        foreach ( $next_steps as $step ){
            if ( $step['funnelstep_group'] === 'action' ){
                return intval( $step['ID'] );
            }
        }
        return false;
    } else {
        if ( $next_steps[0]['funnelstep_group'] === 'benchmark' )
            return false;

        return intval( $next_steps[0]['ID'] );
    }
}

/**
 * Enqueue the next funnel step in the queue
 *
 * @param $last_step_id int the last steps's ID, the current step being run.
 * @param $contact_id int the contact's ID
 *
 * @return int|false the ID of the next action to run, false on failure.
 */
function wpgh_enqueue_next_funnel_action( $last_step_id, $contact_id )
{
    $next_action_id = wpgh_get_next_funnel_action( $last_step_id );

    if ( ! $next_action_id )
        return false;

    $next_action = wpgh_get_funnel_step_by_id( $next_action_id );

    $next_action_type = $next_action->funnelstep_type;

    /**
     * @var $next_action_id int the step_id of the action
     * @var $contact_id int the Id of the contact
     */
    do_action( 'wpgh_enqueue_next_funnel_action_' . $next_action_type, $next_action_id, $contact_id );

    return $next_action_id;
}

/**
 * Add a step to a funnel
 *
 * @param $funnel_id int    the ID of the funnel
 * @param $title     string the title of the step
 * @param $group     string the step group
 * @param $type      string the step type
 * @param $order     int    the order
 *
 * @return int|false the new step ID, false on failure
 */
function wpgh_add_funnel_step( $funnel_id, $title, $group, $type, $order )
{
    $funnel = wpgh_get_funnel_by_id( $funnel_id );

    if ( ! $funnel )
        return false;

    switch ( $group ):
        case 'action':

            $actions = wpgh_get_funnel_actions();

            if ( ! isset( $actions[ $type ] ) )
                return false;
            break;
        case 'benchmark':
            $benchmarks = wpgh_get_funnel_benchmarks();

            if ( ! isset( $benchmarks[ $type ] ) )
                return false;
            break;
        default:
            return false;
    endswitch;

    $order = absint( intval( $order ) );

    return wpgh_insert_new_funnel_step( $funnel_id, $title, 'inactive' , $group, $type, $order );
}

/**
 * Return whether or not the given element is a benchmark
 *
 * @param $element string the element to test
 *
 * @return bool, true if is a benchmark, false otherwise
 */
function wpgh_is_benchmark( $element )
{
    $benchmarks = wpgh_get_funnel_benchmarks();

    return isset( $benchmarks[$element] );
}

/**
 * Return true if the funnel is marked as active.
 *
 * @param $funnel_id int Funnel ID
 * @return bool whether the funnel is active.
 */
function wpgh_is_funnel_active( $funnel_id )
{
    $status = wpgh_get_funnel_status( $funnel_id );

    return $status === 'active';
}

/**
 * Extract the funnel ID from a link, only for use in ADMIN funnel editor.
 *
 * @param $link string link from the funnel editor page
 *
 * @return int|false the funnel ID, false otherwise
 */
function wpgh_url_to_funnel_id( $link )
{

    $queryString = parse_url( $link, PHP_URL_QUERY );

    $queryArgs = explode( '&', $queryString );

    foreach ( $queryArgs as $args ){

        $subArgs = explode( '=' , $args );
        if ( $subArgs[0] == 'funnel' ){
            return intval( $subArgs[1] );
        }

    }

    return false;
}

/**
 * Gt the current reporting range for a funnel...
 *
 * @return string
 */
function wpgh_get_the_report_range()
{
    return ( isset( $_POST[ 'date_range' ] ) )? $_POST[ 'date_range' ] : 'last_24' ;
}

/**
 * Get the end time of a report
 *
 * @param $range
 * @return false|int
 */
function wpgh_get_report_start( $range )
{
    switch ( $range ):
        case 'last_24';
            $start = strtotime( '1 day ago' );
            break;
        case 'last_7';
            $start = strtotime( '7 days ago' );
            break;
        case 'last_30';
            $start = strtotime( '30 days ago' );
            break;
        case 'custom';
            $start = strtotime( $_POST['custom_date_range_start'] );
            break;
        default:
            $start = strtotime( '1 day ago' );
            break;
    endswitch;

    return $start;
}

/**
 * Get the end time of a report
 *
 * @param $range
 * @return false|int
 */
function wpgh_get_report_end( $range )
{
    switch ( $range ):
        case 'last_24';
            $end = time();
            break;
        case 'last_7';
            $end = time();
            break;
        case 'last_30';
            $end = time();
            break;
        case 'custom';
            $end = strtotime( $_POST['custom_date_range_end'] );
            break;
        default:
            $end = time();
            break;
    endswitch;

    return $end;
}

/**
 * Out put the HTML for a step. used in the ajax call and in the funnel builder itself.
 *
 * @param $step_id int the ID of a step
 */
function wpgh_get_step_html( $step_id )
{
    ?>
    <div title="<?php echo wpgh_get_step_hndle( $step_id ) ?>" id="<?php echo $step_id; ?>" class="postbox step <?php echo wpgh_get_step_group( $step_id ); ?> <?php echo wpgh_get_step_type( $step_id ); ?>">
        <button title="Delete" type="button" class="handlediv delete-step-<?php echo $step_id;?>">
            <span class="dashicons dashicons-trash"></span>
            <script>
                jQuery(function(){jQuery('.delete-step-<?php echo $step_id;?>').click( wpgh_delete_funnel_step )})
            </script>
        </button>
        <button title="Duplicate" type="button" class="handlediv duplicate-step-<?php echo $step_id;?>">
            <span class="dashicons dashicons-admin-page"></span>
            <script>
                jQuery(function(){jQuery('.duplicate-step-<?php echo $step_id;?>').click( wpgh_duplicate_step )})
            </script>
        </button>
        <h2 class="hndle ui-sortable-handle"><img class="hndle-icon" width="50" src="<?php echo esc_url( wpgh_get_step_icon( $step_id ) ); ?>"><input title="step title" type="text" id="<?php echo $step_id; ?>_title" name="<?php echo $step_id; ?>_title" class="regular-text" value="<?php echo __( wpgh_get_step_hndle( $step_id ), 'groundhogg' ); ?>"> :<?php echo $step_id; ?></h2>
        <div class="inside">
            <div class="step-edit">
                <input type="hidden" name="<?php echo wpgh_prefix_step_meta( $step_id, 'order' ); ?>" value="<?php wpgh_get_step_order( $step_id ) ?>" >
                <input type="hidden" name="steps[]" value="<?php echo $step_id; ?>">
                <div class="custom-settings">
                    <?php do_action( 'wpgh_step_settings_before' ); ?>
                    <?php do_action( 'wpgh_get_step_settings_' . wpgh_get_step_type( $step_id ), $step_id ); ?>
                    <?php do_action( 'wpgh_step_settings_after' ); ?>
                </div>
            </div>
            <div class="step-reporting hidden">
                <?php do_action( 'wpgh_step_reporting_before' );

                $range = wpgh_get_the_report_range();

                $start = wpgh_get_report_start( $range );
                $end   = wpgh_get_report_end( $range);

                $report = new WPGH_Event_Report( wpgh_get_step_funnel( $step_id ), $step_id, $start, $end );

                ?>

                <?php if ( wpgh_get_step_group( $step_id ) === 'benchmark'): ?>
                    <p class="report"><?php _e('Completed', 'groundhogg') ?>: <a target="_blank" href="<?php echo admin_url( 'admin.php?page=gh_contacts&view=report&status=complete&funnel=' . wpgh_get_step_funnel( $step_id ) . '&step=' . $step_id . '&start=' . $start . '&end=' . $end ); ?>"><b><?php echo $report->getCompletedEventsCount(); ?></b></a></p>
                <?php else: ?>
                    <p class="report"><?php _e('Completed', 'groundhogg') ?>: <a target="_blank" href="<?php echo admin_url( 'admin.php?page=gh_contacts&view=report&status=complete&funnel=' . wpgh_get_step_funnel( $step_id ) . '&step=' . $step_id . '&start=' . $start . '&end=' . $end ); ?>"><b><?php echo $report->getCompletedEventsCount(); ?></b></a></p>
                    <hr>
                    <p class="report"><?php _e('Waiting', 'groundhogg') ?>: <a target="_blank" href="<?php echo admin_url( 'admin.php?page=gh_contacts&view=report&status=waiting&funnel=' . wpgh_get_step_funnel( $step_id ) . '&step=' . $step_id ); ?>"><b><?php echo $report->getQueuedEventsCount(); ?></b></a></p>
                <?php endif; ?>

                <?php do_action( 'wpgh_get_step_report_' . wpgh_get_step_type( $step_id ), $step_id, $start, $end ); ?>

                <?php do_action( 'wpgh_step_reporting_after' ); ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Return the html of the new step VIA ajax.
 *
 * @var $step_type string the typeof step to create
 * @var $
 */
function wpgh_get_step_html_via_ajax()
{

    $step_type = $_POST['step_type'];
    $step_order = intval( $_POST['step_order'] );
    $funnel_id = wpgh_url_to_funnel_id( wp_get_referer() );

    $step_group = ( wpgh_is_benchmark( $step_type ) )? 'benchmark' : 'action' ;
//    wp_die( 'made-it-here' );

    foreach ( glob( dirname( __FILE__ ) . "/admin/funnels/elements/*/*.php" ) as $filename )
    {
        include $filename;
    }

    if ( $step_group === 'benchmark' ){
        $benchmarks = wpgh_get_funnel_benchmarks();
        $title = $benchmarks[ $step_type ][ 'title' ];
    } else {
        $actions = wpgh_get_funnel_actions();
        $title = $actions[ $step_type ][ 'title' ];
    }

    $step_id = wpgh_add_funnel_step( $funnel_id, $title, $step_group, $step_type, $step_order );

    ob_start();

    wpgh_get_step_html( $step_id );

    $content = ob_get_contents();

    ob_end_clean();

    wp_die( $content );
}

add_action( 'wp_ajax_wpgh_get_step_html', 'wpgh_get_step_html_via_ajax' );

function wpgh_get_step_html_inside()
{
    $step_id = intval( $_POST['step_id'] );
    $step_order = intval( $_POST['step_order'] );

    wpgh_update_funnel_step( $step_id, 'funnelstep_order', $step_order );

    foreach ( glob( dirname( __FILE__ ) . "/admin/funnels/elements/*/*.php" ) as $filename )
    {
        include $filename;
    }

    ob_start();

    do_action( 'wpgh_step_settings_before' );
    do_action( 'wpgh_get_step_settings_' . wpgh_get_step_type( $step_id ), $step_id );
    do_action( 'wpgh_step_settings_after' );

    $content = ob_get_contents();

    ob_end_clean();

    wp_die( $content );
}

add_action( 'wp_ajax_wpgh_get_step_html_inside', 'wpgh_get_step_html_inside' );

/**
 * Delete the funnel step by it's ID
 */
function wpgh_delete_funnel_step_via_ajax()
{
    if ( ! isset( $_POST['step_id'] ) )
        wp_die( 'No Step.' );

    $stepid = absint( intval( $_POST['step_id'] ) );

    wp_die( wpgh_delete_funnel_step( $stepid ) );
}

add_action( 'wp_ajax_wpgh_delete_funnel_step', 'wpgh_delete_funnel_step_via_ajax' );


/**
 * Duplicate a funnel step
 */
function wpgh_duplicate_funnel_step_via_ajax()
{
    if ( ! isset( $_POST['step_id'] ) )
        wp_die( 'No Step.' );

    $step_id = absint( intval( $_POST['step_id'] ) );

    $step = wpgh_get_funnel_step_by_id( $step_id, OBJECT );

    if ( ! $step || empty( $step->funnel_id ) )
        wp_die( 'Could not find step...' );

    $newID = wpgh_insert_new_funnel_step( intval( $step->funnel_id ), $step->funnelstep_title, 'ready', $step->funnelstep_group, $step->funnelstep_type, intval( $step->funnelstep_order ) - 1 );

    if ( ! $newID )
        wp_die( 'Oops' );

    $meta = wpgh_get_step_meta( $step_id );

//    wp_die( json_encode( $meta ) );

    foreach ( $meta as $key => $value )
    {
        wpgh_update_step_meta( $newID, $key, $value[0] );
    }

    foreach ( glob( dirname( __FILE__ ) . "/admin/funnels/elements/*/*.php" ) as $filename )
    {
        include $filename;
    }

    ob_start();

    wpgh_get_step_html( $newID );

    $content = ob_get_contents();

    ob_end_clean();

    wp_die( $content );
}

add_action( 'wp_ajax_wpgh_duplicate_funnel_step', 'wpgh_duplicate_funnel_step_via_ajax' );

/**
 * Wrapper function for smaller file sizes lol...
 *
 * @param $step_id
 * @param $attr
 */
function gh_meta_e( $step_id, $attr )
{
    wpgh_prefix_step_meta_e( $step_id, $attr );
}

/**
 * pre-fix step setting attrivutes suych and name and id with the ID of the step for easy $_POST sorting.
 *
 * @param $step_id int the step ID
 * @param $atter string the meta attribute
 *
 * @return string the prefixed meta attribute.
 */
function wpgh_prefix_step_meta( $step_id, $atter )
{
    return intval( $step_id ) . '_' . esc_attr( $atter );
}

/**
 * pre-fix step setting attrivutes suych and name and id with the ID of the step for easy $_POST sorting.
 *
 * @param $step_id int the step ID
 * @param $atter string the meta attribute
 *
 * @return void
 */
function wpgh_prefix_step_meta_e( $step_id, $atter )
{
	echo wpgh_prefix_step_meta( $step_id, $atter );
}

/**
 * Update the funnel and all of the funnel steps.
 *
 * @param $funnel_id
 */
function wpgh_save_funnel( $funnel_id )
{
    //todo user validation & permissions...

    if ( empty( $_POST ) )
        return;

    //wp_die( 'made-it-here' );

    do_action( 'wpgh_before_save_funnel', $funnel_id );

    $title = sanitize_text_field( stripslashes( $_POST[ 'funnel_title' ] ) );
    wpgh_update_funnel( $funnel_id, 'funnel_title', $title );


    /* do NOT update status during an autosave... */
    if ( ! wp_doing_ajax() ){
	    $status = sanitize_text_field( $_POST[ 'funnel_status' ] );
	    if ( $status !== 'active' && $status !== 'inactive' )
		    $status = 'inactive';

	    //do not update the status to inactive if it's not confirmed
	    if ( ( $status === 'inactive' && isset( $_POST['confirm'] ) && $_POST['confirm'] === 'yes' ) || $status === 'active' ){
		    wpgh_update_funnel( $funnel_id, 'funnel_status', $status );
	    }
    }

    //get all the steps in the funnel.
    $steps = $_POST['steps'];

    if ( ! $steps ){
        wp_die( 'Please add automation first.' );
    }

    foreach ( $steps as $i => $stepId )
    {

        //wp_die( 'made-it-here' );

        $stepId = intval( $stepId );
        //quick Order Hack to get the proper order of a step...
        $order = $i + 1;
        wpgh_update_funnel_step( $stepId, 'funnelstep_order', $order );

        $title = sanitize_text_field( stripslashes( $_POST[ wpgh_prefix_step_meta( $stepId, 'title' ) ] ) );

        wpgh_update_funnel_step( $stepId, 'funnelstep_title', $title );

        wpgh_update_funnel_step( $stepId, 'funnelstep_status', 'ready' );

        $step_type = wpgh_get_step_type( $stepId );

        do_action( 'wpgh_save_step_' . $step_type, $stepId );

    }

	/* if it's not a bench mark then the funnel cant actually ever run */
	if ( ! wpgh_is_benchmark( wpgh_get_step_type( intval( $steps[0] ) ) ) ){
		wpgh_add_notice( 'bad-funnel', __( 'Funnels must start with 1 or more benchmarks', 'groundhogg' ), 'error' );
	}

    do_action( 'wpgh_save_funnel_after', $funnel_id );
}

add_action( 'wpgh_update_funnel', 'wpgh_save_funnel' );

/**
 * Auto save the funnel steps. nothing else
 */
function wpgh_auto_save_funnel()
{
    if ( ! wp_doing_ajax() )
        wp_die('You should not be running this function without an ajax request.');

    //todo user permissions

    $funnel_id = wpgh_url_to_funnel_id( wp_get_referer() );

    wpgh_save_funnel( $funnel_id );

    wp_die('Auto Saved' );

}

add_action( 'wp_ajax_wpgh_auto_save_funnel_via_ajax', 'wpgh_auto_save_funnel' );

/**
 * Create a new funnel and redirect to the email editor.
 */
function wpgh_create_new_funnel()
{
    if ( isset( $_POST[ 'funnel_template' ] ) ){

        include dirname(__FILE__ ) . '/templates/funnel-templates.php';

        /* @var $funnel_templates array included from funnel-templates.php*/

        $json = file_get_contents( $funnel_templates[ $_POST['funnel_template'] ]['file'] );

        $funnel_id = wpgh_import_funnel( json_decode( $json, true ) );

    } else if ( isset( $_POST[ 'funnel_id' ] ) ) {

        $from_funnel = intval( $_POST[ 'funnel_id' ] );
        $json = wpgh_convert_funnel_to_json( $from_funnel );
        $funnel_id = wpgh_import_funnel( json_decode( $json, true ) );

    } else if ( isset( $_FILES[ 'funnel_template' ] ) ) {

        if ($_FILES['funnel_template']['error'] == UPLOAD_ERR_OK && is_uploaded_file( $_FILES['funnel_template']['tmp_name'] ) ) {

            $json = file_get_contents($_FILES['funnel_template']['tmp_name'] );
            $funnel_id = wpgh_import_funnel( json_decode( $json, true ) );
        }

    } else {

        ?><div class="notice notice-error"><p><?php _e( 'Could not create funnel. PLease select a template.', 'groundhogg' ); ?></p></div><?php
        return;
    }

    if ( ! $funnel_id )
        wp_die( 'Error creating funnel.' );

    wp_redirect( admin_url( 'admin.php?page=gh_funnels&action=edit&funnel=' .  $funnel_id ) );
    die();
}

add_action( 'wpgh_add_funnel', 'wpgh_create_new_funnel' );

/**
 * Return whether the contact is in a certain funnel.
 * Used to verify a certain benchmark should be completed or not.
 * Will return true if there are previous funnel ACTIONS (not benchmarks) in the event queue.
 *
 * @param $contact_id int the ID of the contact
 * @param $funnel_id int the ID of the funnel
 * @return true|false whether the contact is in the funnel
 */
function wpgh_contact_is_in_funnel( $contact_id, $funnel_id )
{
    global $wpdb;

    $table_name = $wpdb->prefix . WPGH_EVENTS;

    $results = $wpdb->get_results(
        $wpdb->prepare(
            "
         SELECT * FROM $table_name
		 WHERE contact_id = %d AND funnel_id = %d AND ( status = %s OR status = %s )
		",
            $contact_id, $funnel_id, 'complete', 'waiting'
        ) );

    return ! empty( $results );

}