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
 *Add easy way for devs to register new funnel actions and benchmarks
 *
 * @param $name string the Name of the new action
 * @param $type string the identifier
 * @param $settings_callback string callback for the settings of the new action
 * @param $save_callback string callback to save the new settings
 * @param $action_callback string the callback to perform the action
 * @param $icon string a link or class to the icon of the new action.
 */
function wpfn_register_custom_action
(
        $name,
        $type,
        $settings_callback,
        $save_callback,
        $action_callback,
        $icon
)
{
    //todo
}

/**
 * Return a list of slugs for the available benchmarks in the funnel builder
 *
 * @return array
 */
function wpfn_get_funnel_benchmarks()
{
    $benchmarks = array();

    $benchmarks['form_fill'] = array( 'title' => __('Form Filled'), 'icon' => WPFN_ASSETS_FOLDER . '/images/builder-icons/form-filled.png' );
    $benchmarks['email_opened'] = array( 'title' => __('Email opened', 'groundhogg' ), 'icon' => WPFN_ASSETS_FOLDER . '/images/builder-icons/opened-email.png' );
//    $benchmarks['link_clicked'] = array( 'title' => __('Link Clicked', 'groundhogg' ), 'icon' => WPFN_ASSETS_FOLDER . '/images/builder-icons/' );
    $benchmarks['tag_applied']  = array( 'title' => __('Tag Applied', 'groundhogg' ), 'icon' => WPFN_ASSETS_FOLDER . '/images/builder-icons/tag-applied.png' );
    $benchmarks['tag_removed']  = array( 'title' => __('Tag Removed', 'groundhogg' ), 'icon' => WPFN_ASSETS_FOLDER . '/images/builder-icons/tag-removed.png' );
    $benchmarks['page_visited'] = array( 'title' => __('Page Visited', 'groundhogg' ), 'icon' => WPFN_ASSETS_FOLDER . '/images/builder-icons/page-visited.png' );
    $benchmarks['account_created'] = array( 'title' => __('Account Created', 'groundhogg' ), 'icon' => WPFN_ASSETS_FOLDER . '/images/builder-icons/account-created.png' );
    $benchmarks['role_changed']    = array( 'title' => __('Role Changed', 'groundhogg' ), 'icon' => WPFN_ASSETS_FOLDER . '/images/builder-icons/role-changed.png' );

    return apply_filters( 'wpfn_funnel_benchmarks', $benchmarks );
}

/**
 * Return a list of slugs for the available funnel actions
 *
 * @return array()
 */
function wpfn_get_funnel_actions()
{
    $actions = array();

    $actions['send_email']  = array( 'title' => __( 'Send Email', 'groundhogg' ), 'icon' => WPFN_ASSETS_FOLDER . '/images/builder-icons/send-email.png' );
    $actions['apply_note']  = array( 'title' => __( 'Apply Note', 'groundhogg' ), 'icon' => WPFN_ASSETS_FOLDER . '/images/builder-icons/apply-a-note.png' );
    $actions['apply_tag']   = array( 'title' => __( 'Apply Tag', 'groundhogg' ), 'icon' => WPFN_ASSETS_FOLDER . '/images/builder-icons/apply-tag.png' );
    $actions['remove_tag']  = array( 'title' => __( 'Remove Tag', 'groundhogg' ), 'icon' => WPFN_ASSETS_FOLDER . '/images/builder-icons/remove-tag.png' );
    $actions['create_user'] = array( 'title' => __( 'Create User', 'groundhogg' ), 'icon' => WPFN_ASSETS_FOLDER . '/images/builder-icons/create-account.png' );
//    $actions['delete_user'] = array( 'title' => __( '', 'groundhogg' ), 'icon' => WPFN_ASSETS_FOLDER . '/images/builder-icons/.png' );
    $actions['date_timer']  = array( 'title' => __( 'Date Timer', 'groundhogg' ), 'icon' => WPFN_ASSETS_FOLDER . '/images/builder-icons/date-timer.png' );
    $actions['delay_timer'] = array( 'title' => __( 'Delay Timer', 'groundhogg' ), 'icon' => WPFN_ASSETS_FOLDER . '/images/builder-icons/delay-timer.png' );

    return apply_filters( 'wpfn_funnel_actions', $actions );
}

/**
 * Get the icon for a step
 *
 * @param $step_id int the Id of the step
 * @return string the URL of the icon.
 */
function wpfn_get_step_icon( $step_id )
{

    $step_group = wpfn_get_step_group( $step_id );

    $step_type = wpfn_get_step_type( $step_id );

    if ( $step_group === 'benchmark' ){

        $benchmarks = wpfn_get_funnel_benchmarks();

        return $benchmarks[$step_type]['icon'];

    } else {

        $actions = wpfn_get_funnel_actions();

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
function wpfn_get_funnel_name( $funnel_id )
{
    $funnel = wpfn_get_funnel_by_id( $funnel_id );

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
function wpfn_get_funnel_status( $funnel_id )
{
    $funnel = wpfn_get_funnel_by_id( $funnel_id );

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
function wpfn_get_funnel_steps( $funnel_id )
{
    $steps = wpfn_get_funnel_steps_by_funnel_id( $funnel_id );

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
function wpfn_get_step_hndle( $step_id )
{
    //todo Set up some sort of easy way, hardcode?

    $step = wpfn_get_funnel_step_by_id( $step_id );

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
function wpfn_get_step_settings( $step_id )
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
function wpfn_get_step_type( $step_id )
{
    $step = wpfn_get_funnel_step_by_id( $step_id );

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
function wpfn_get_step_group( $step_id )
{
    $step = wpfn_get_funnel_step_by_id( $step_id );

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
function wpfn_get_step_order( $step_id )
{
    $step = wpfn_get_funnel_step_by_id( $step_id );

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
function wpfn_get_step_funnel( $step_id )
{
    $step = wpfn_get_funnel_step_by_id( $step_id );

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
function wpfn_get_next_funnel_action( $step_id )
{
    $funnel_id = wpfn_get_step_funnel( $step_id );
    $step_order = wpfn_get_step_order( $step_id );
    $step_group = wpfn_get_step_group( $step_id );

    $next_steps = wpfn_get_funnel_steps_by_order( $funnel_id, $step_order );

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
function wpfn_enqueue_next_funnel_action( $last_step_id, $contact_id )
{
    $next_action_id = wpfn_get_next_funnel_action( $last_step_id );

    if ( ! $next_action_id )
        return false;

    $next_action = wpfn_get_funnel_step_by_id( $next_action_id );

    $next_action_type = $next_action->funnelstep_type;

    /**
     * @var $next_action_id int the step_id of the action
     * @var $contact_id int the Id of the contact
     */
    do_action( 'wpfn_enqueue_next_funnel_action_' . $next_action_type, $next_action_id, $contact_id );

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
function wpfn_add_funnel_step( $funnel_id, $title, $group, $type, $order )
{
    $funnel = wpfn_get_funnel_by_id( $funnel_id );

    if ( ! $funnel )
        return false;

    switch ( $group ):
        case 'action':

            $actions = wpfn_get_funnel_actions();

            if ( ! isset( $actions[ $type ] ) )
                return false;
            break;
        case 'benchmark':
            $benchmarks = wpfn_get_funnel_benchmarks();

            if ( ! isset( $benchmarks[ $type ] ) )
                return false;
            break;
        default:
            return false;
    endswitch;

    $order = absint( intval( $order ) );

    return wpfn_insert_new_funnel_step( $funnel_id, $title, 'inactive' , $group, $type, $order );
}

/**
 * Return whether or not the given element is a benchmark
 *
 * @param $element string the element to test
 *
 * @return bool, true if is a benchmark, false otherwise
 */
function wpfn_is_benchmark( $element )
{
    $benchmarks = wpfn_get_funnel_benchmarks();

    return isset( $benchmarks[$element] );
}

/**
 * Return true if the funnel is marked as active.
 *
 * @param $funnel_id int Funnel ID
 * @return bool whether the funnel is active.
 */
function wpfn_is_funnel_active( $funnel_id )
{
    $status = wpfn_get_funnel_status( $funnel_id );

    return $status === 'active';
}

/**
 * Extract the funnel ID from a link, only for use in ADMIN funnel editor.
 *
 * @param $link string link from the funnel editor page
 *
 * @return int|false the funnel ID, false otherwise
 */
function wpfn_url_to_funnel_id( $link )
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
function wpfn_get_the_report_range()
{
    return ( isset( $_POST[ 'date_range' ] ) )? $_POST[ 'date_range' ] : 'last_24' ;
}

/**
 * Out put the HTML for a step. used in the ajax call and in the funnel builder itself.
 *
 * @param $step_id int the ID of a step
 */
function wpfn_get_step_html( $step_id )
{
    ?>
    <div id="<?php echo $step_id; ?>" class="postbox step <?php echo wpfn_get_step_group( $step_id ); ?> <?php echo wpfn_get_step_type( $step_id ); ?>">
        <button type="button" class="handlediv delete-step-<?php echo $step_id;?>">
            <span class="dashicons dashicons-trash"></span>
            <script>
                jQuery(function(){jQuery('.delete-step-<?php echo $step_id;?>').click( wpfn_delete_funnel_step )})
            </script>
        </button>
        <h2 class="hndle ui-sortable-handle"><img class="hndle-icon" width="50" src="<?php echo esc_url( wpfn_get_step_icon( $step_id ) ); ?>"><input title="step title" type="text" id="<?php echo $step_id; ?>_title" name="<?php echo $step_id; ?>_title" class="regular-text" value="<?php echo __( wpfn_get_step_hndle( $step_id ), 'groundhogg' ); ?>"></h2>
        <div class="inside">
            <div class="step-edit">
                <input type="hidden" name="<?php echo wpfn_prefix_step_meta( $step_id, 'order' ); ?>" value="<?php wpfn_get_step_order( $step_id ) ?>" >
                <input type="hidden" name="steps[]" value="<?php echo $step_id; ?>">
                <div class="custom-settings">
                    <?php do_action( 'wpfn_step_settings_before' ); ?>
                    <?php do_action( 'wpfn_get_step_settings_' . wpfn_get_step_type( $step_id ), $step_id ); ?>
                    <?php do_action( 'wpfn_step_settings_after' ); ?>
                </div>
            </div>
            <div class="step-reporting hidden">
                <?php do_action( 'wpfn_step_reporting_before' );

                $range = wpfn_get_the_report_range();

                switch ( $range ):
                    case 'last_24';
                        $start = strtotime( '1 day ago' );
                        $end = strtotime( 'now' );
                        break;
                    case 'last_7';
                        $start = strtotime( '7 days ago' );
                        $end = strtotime( 'now' );
                        break;
                    case 'last_30';
                        $start = strtotime( '30 days ago' );
                        $end = strtotime( 'now' );
                        break;
                    case 'custom';
                        $start = strtotime( $_POST['custom_date_range_start'] );
                        $end = strtotime( $_POST['custom_date_range_end'] );
                        break;
                    default:
                        $start = strtotime( '1 day ago' );
                        $end = strtotime( 'now' );
                        break;
                endswitch;

                $report = new WPFN_Event_Report( wpfn_get_step_funnel( $step_id ), $step_id, $start, $end );

                ?>

                <?php if ( wpfn_get_step_group( $step_id ) === 'benchmark'): ?>
                    <p class="report"><?php _e('Completed', 'groundhogg') ?>: <a target="_blank" href="<?php echo admin_url( 'admin.php?page=gh_contacts&view=report&status=complete&funnel=' . wpfn_get_step_funnel( $step_id ) . '&step=' . $step_id . '&start=' . $start . '&end=' . $end ); ?>"><b><?php echo $report->getCompletedEventsCount(); ?></b></a></p>
                <?php else: ?>
                    <p class="report"><?php _e('Completed', 'groundhogg') ?>: <a target="_blank" href="<?php echo admin_url( 'admin.php?page=gh_contacts&view=report&status=complete&funnel=' . wpfn_get_step_funnel( $step_id ) . '&step=' . $step_id . '&start=' . $start . '&end=' . $end ); ?>"><b><?php echo $report->getCompletedEventsCount(); ?></b></a></p>
                    <hr>
                    <p class="report"><?php _e('Waiting', 'groundhogg') ?>: <a target="_blank" href="<?php echo admin_url( 'admin.php?page=gh_contacts&view=report&status=waiting&funnel=' . wpfn_get_step_funnel( $step_id ) . '&step=' . $step_id ); ?>"><b><?php echo $report->getQueuedEventsCount(); ?></b></a></p>
                <?php endif; ?>

                <?php do_action( 'wpfn_get_step_report_' . wpfn_get_step_type( $step_id ) ); ?>

                <?php do_action( 'wpfn_step_reporting_after' ); ?>
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
function wpfn_get_step_html_via_ajax()
{

    $step_type = $_POST['step_type'];
    $step_order = intval( $_POST['step_order'] );
    $funnel_id = wpfn_url_to_funnel_id( wp_get_referer() );

    $step_group = ( wpfn_is_benchmark( $step_type ) )? 'benchmark' : 'action' ;
//    wp_die( 'made-it-here' );

    foreach ( glob( dirname( __FILE__ ) . "/admin/funnels/elements/*/*.php" ) as $filename )
    {
        include $filename;
    }

    $step_id = wpfn_add_funnel_step( $funnel_id, 'New Step', $step_group, $step_type, $step_order );

    ob_start();

    wpfn_get_step_html( $step_id );

    $content = ob_get_contents();

    ob_end_clean();

    wp_die( $content );
}

add_action( 'wp_ajax_wpfn_get_step_html', 'wpfn_get_step_html_via_ajax' );

function wpfn_get_step_html_inside()
{
    $step_id = intval( $_POST['step_id'] );
    $step_order = intval( $_POST['step_order'] );

    wpfn_update_funnel_step( $step_id, 'funnelstep_order', $step_order );

    foreach ( glob( dirname( __FILE__ ) . "/admin/funnels/elements/*/*.php" ) as $filename )
    {
        include $filename;
    }

    ob_start();

    do_action( 'wpfn_step_settings_before' );
    do_action( 'wpfn_get_step_settings_' . wpfn_get_step_type( $step_id ), $step_id );
    do_action( 'wpfn_step_settings_after' );

    $content = ob_get_contents();

    ob_end_clean();

    wp_die( $content );
}

add_action( 'wp_ajax_wpfn_get_step_html_inside', 'wpfn_get_step_html_inside' );

/**
 * Delete the funnel step by it's ID
 */
function wpfn_delete_funnel_step_via_ajax()
{
    if ( ! isset( $_POST['step_id'] ) )
        wp_die( 'No Step.' );

    $stepid = absint( intval( $_POST['step_id'] ) );

    wp_die( wpfn_delete_funnel_step( $stepid ) );
}

add_action( 'wp_ajax_wpfn_delete_funnel_step', 'wpfn_delete_funnel_step_via_ajax' );

/**
 * pre-fix step setting attrivutes suych and name and id with the ID of the step for easy $_POST sorting.
 *
 * @param $step_id int the step ID
 * @param $atter string the meta attribute
 *
 * @return string the prefixed meta attribute.
 */
function wpfn_prefix_step_meta( $step_id, $atter )
{
    return $step_id . '_' . $atter;
}

/**
 * pre-fix step setting attrivutes suych and name and id with the ID of the step for easy $_POST sorting.
 *
 * @param $step_id int the step ID
 * @param $atter string the meta attribute
 *
 * @return void
 */
function wpfn_prefix_step_meta_e( $step_id, $atter )
{
	echo wpfn_prefix_step_meta( $step_id, $atter );
}

/**
 * Update the funnel and all of the funnel steps.
 *
 * @param $funnel_id
 */
function wpfn_save_funnel( $funnel_id )
{
    //todo user validation & permissions...

    if ( empty( $_POST ) )
        return;

    //wp_die( 'made-it-here' );

//    do_action( 'wpfn_save_funnel', $funnel_id );

    $title = sanitize_text_field( stripslashes( $_POST[ 'funnel_title' ] ) );
    wpfn_update_funnel( $funnel_id, 'funnel_title', $title );

    $status = sanitize_text_field( $_POST[ 'funnel_status' ] );
    if ( $status !== 'active' && $status !== 'inactive' )
        $status = 'inactive';

    //do not update the status to inactive if it's not confirmed
    if ( ( $status === 'inactive' && isset( $_POST['confirm'] ) && $_POST['confirm'] === 'yes' ) || $status === 'active' ){
        wpfn_update_funnel( $funnel_id, 'funnel_status', $status );
    }

    //get all the steps in the funnel.
    $steps = $_POST['steps'];

    if ( ! $steps ){
        ?>
        <div class="notice notice-error is-dismissible"><p><?php echo esc_html__( 'No funnel steps present. Please add some automation.', 'groundhogg' ); ?></p></div>
        <?php
        return;
    }

    foreach ( $steps as $i => $stepId )
    {

        //wp_die( 'made-it-here' );

        $stepId = intval( $stepId );
        //quick Order Hack to get the proper order of a step...
        $order = $i + 1;
        wpfn_update_funnel_step( $stepId, 'funnelstep_order', $order );

        $title = sanitize_text_field( stripslashes( $_POST[ wpfn_prefix_step_meta( $stepId, 'title' ) ] ) );

        wpfn_update_funnel_step( $stepId, 'funnelstep_title', $title );

        wpfn_update_funnel_step( $stepId, 'funnelstep_status', 'ready' );

        $step_type = wpfn_get_step_type( $stepId );

        do_action( 'wpfn_save_step_' . $step_type, $stepId );

    }
//    do_action( 'wpfn_save_funnel_after', $funnel_id );
}

add_action( 'wpfn_update_funnel', 'wpfn_save_funnel' );

/**
 * Auto save the funnel steps. nothing else
 */
function wpfn_auto_save_funnel()
{
    if ( ! wp_doing_ajax() )
        wp_die('You should not be running this function without an ajax request.');

    //todo user permissions

    $steps = $_POST['steps'];

    if ( ! $steps )
        wp_die('No steps present.');

    foreach ( $steps as $i => $stepId )
    {
        $stepId = intval( $stepId );
        //quick Order Hack to get the proper order of a step...
        $order = $i + 1;
        wpfn_update_funnel_step( $stepId, 'funnelstep_order', $order );

        $title = sanitize_text_field( stripslashes( $_POST[ wpfn_prefix_step_meta( $stepId, 'title' ) ] ) );
        wpfn_update_funnel_step( $stepId, 'funnelstep_title', $title );

        $step_type = wpfn_get_step_type( $stepId );

        do_action( 'wpfn_save_step_' . $step_type, $stepId );
    }

    wp_die('Auto Saved Successfully.');

}

add_action( 'wp_ajax_wpfn_auto_save_funnel_via_ajax', 'wpfn_auto_save_funnel' );

/**
 * Create a new funnel and redirect to the email editor.
 */
function wpfn_create_new_funnel()
{
    if ( isset( $_POST[ 'funnel_template' ] ) ){

        include dirname(__FILE__) . '/templates/funnel-templates.php';

        /* @var $funnel_templates array included from funnel-templates.php*/
        $funnel = $funnel_templates[ $_POST[ 'funnel_template' ] ];

        $funnel_id = wpfn_insert_new_funnel( $funnel['title'], 'inactive' );

        foreach ( $funnel['steps'] as $i => $step )
        {

            $step_id = wpfn_insert_new_funnel_step(
                $funnel_id,
                $step['title'],
                'ready',
                $step['group'],
                $step['type'],
                $i + 1
            );

            foreach ( $step[ 'meta' ] as $meta_key => $meta_value )
            {
                wpfn_update_step_meta( $step_id , $meta_key, $meta_value );
            }

        }


    } else if ( isset( $_POST[ 'funnel_id' ] ) ) {

        //todo copy and duplicate steps from old funnel...

        //todo import from files...

    } else {

        ?><div class="notice notice-error"><p><?php _e( 'Could not create funnel. PLease select a template.', 'groundhogg' ); ?></p></div><?php

        return;

    }

    wp_redirect( admin_url( 'admin.php?page=gh_funnels&action=edit&funnel=' .  $funnel_id ) );
    die();
}

add_action( 'wpfn_add_funnel', 'wpfn_create_new_funnel' );

/**
 * Return whether the contact is in a certain funnel.
 * Used to verify a certain benchmark should be completed or not.
 * Will return true if there are previous funnel ACTIONS (not benchmarks) in the event queue.
 *
 * @param $contact_id int the ID of the contact
 * @param $funnel_id int the ID of the funnel
 * @return true|false whether the contact is in the funnel
 */
function wpfn_contact_is_in_funnel( $contact_id, $funnel_id )
{
    global $wpdb;

    $table_name = $wpdb->prefix . WPFN_EVENTS;

    $results = $wpdb->get_results(
        $wpdb->prepare(
            "
         SELECT * FROM $table_name
		 WHERE contact_id = %d AND funnel_id = %d AND status = %s
		",
            $contact_id, $funnel_id, 'complete'
        ) );

    return ! empty( $results );

}

/**
 * Convert the funnel into a json object so it can be duplicated fairly easily.
 *
 * @param $funnel_id int the ID of the funnel to convert.
 * @return false|string the json string of a converted funnel or false on failure.
 */
function wpfn_convert_funnel_to_json( $funnel_id )
{

    if ( ! $funnel_id || is_int( $funnel_id) )
        return false;

    $funnel = wpfn_get_funnel_step_by_id( $funnel_id );

    if ( ! $funnel )
        return false;

    $funnelArray = array();

    $funnelArray['title'] = $funnel->funnel_title;

}