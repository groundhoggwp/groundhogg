<?php
/**
 * Funnel Functions
 *
 * Fucntions to help the funnel builder do it's thing
 *
 * @package     wp-funnels
 * @subpackage  Includes/Funnels
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

/**
 * Add the funnel menu items to the menu.
 */
function wpfn_add_funnel_menu_items()
{
    $funnel_admin_id = add_menu_page(
        'Funnels',
        'Funnels',
        'manage_options',
        'funnels',
        'wpfn_funnels_page',
        'dashicons-filter'
    );

    $funnel_admin_add = add_submenu_page(
        'funnels',
        'Add Email',
        'Add New',
        'manage_options',
        'add_funnel',
        'wpfn_add_funnels_page'
    );
}

add_action( 'admin_menu', 'wpfn_add_funnel_menu_items' );

/**
 * Include the relevant admin file to display the output.
 */
function wpfn_funnels_page()
{
    include dirname( __FILE__ ) . '/admin/funnels/funnels.php';
}

/**
 * Include the relevant admin file to display the output.
 */
function wpfn_add_funnels_page()
{

    $new_funnel = wpfn_create_new_funnel();

    $new_step = wpfn_add_funnel_step(
        $new_funnel,
        'Welcome Email',
        'action',
        'send_email',
        1
    );

    wp_redirect( admin_url( 'admin.php?page=funnels&ID=' . $new_funnel ) );
}


/**
 * Return a list of slugs for the available benchmarks in the funnel builder
 *
 * @return array
 */
function wpfn_get_funnel_benchmark_icons()
{
    $benchmarks = array();

    $benchmarks[] = 'form_fill';

    //GF compatibility
    if ( class_exists( 'GFCommon' ) ){
        $benchmarks[] = 'gf_form_fill';
    }

    $benchmarks[] = 'email_opened';
    $benchmarks[] = 'link_clicked';
    $benchmarks[] = 'page_visited';
    $benchmarks[] = 'tag_applied';
    $benchmarks[] = 'tag_removed';

    // WC compatibility
    if ( class_exists( 'WooCommerce' ) ){
        $benchmarks[] = 'wc_reached_checkout';
        $benchmarks[] = 'wc_product_added_to_cart';
        $benchmarks[] = 'wc_product_removed_from_cart';
        $benchmarks[] = 'wc_product_purchased';
    }

    //EDD compatibility
    //todo verify this is an appropriate check.
    if ( class_exists( 'EDD' ) ){
        $benchmarks[] = 'edd_reached_checkout';
        $benchmarks[] = 'edd_download_added_to_cart';
        $benchmarks[] = 'edd_download_removed_from_cart';
        $benchmarks[] = 'edd_download_purchased';
    }

    $benchmarks[] = 'account_created';

    return apply_filters( 'wpfn_funnel_benchmarks', $benchmarks );
}

/**
 * Return a list of slugs for the available funnel actions
 *
 * @return array()
 */
function wpfn_get_funnel_action_icons()
{
    $actions = array();

    $actions[] = 'send_email';
    $actions[] = 'apply_note';
    $actions[] = 'apply_tag';
    $actions[] = 'remove_tag';
    $actions[] = 'create_user';
    $actions[] = 'delete_user';
    $actions[] = 'date_timer';
    $actions[] = 'delay_timer';

    return apply_filters( 'wpfn_funnel_actions', $actions );
}


/**
 * Get the relevant dashicon for the step type
 *
 * @param $step_type string the step type
 *
 * @return string the dashicon class
 */
function wpfn_get_step_dashicon_by_step_type( $step_type )
{
    $dashicons = array();
    $dashicons['send_email'] = 'dashicons-email-alt';
    $dashicons['apply_note'] = 'dashicons-id-alt';
    $dashicons['apply_tag'] = 'dashicons-tag';
    $dashicons['remove_tag'] = 'dashicons-tag';
    $dashicons['create_user'] = 'dashicons-admin-users';
    $dashicons['delete_user'] = 'dashicons-admin-users';
    $dashicons['date_timer'] = 'dashicons-calendar';
    $dashicons['delay_timer'] = 'dashicons-clock';

    $dashicons['form_fill'] = 'dashicons-feedback';

    //todo GF dashicons
    $dashicons['gf_form_fill'] = '';

    $dashicons['email_opened'] = 'dashicons-email';
    $dashicons['link_clicked'] = 'dashicons-admin-links';
    $dashicons['page_visited'] = 'dashicons-welcome-view-site';
    $dashicons['tag_applied'] = 'dashicons-tag';
    $dashicons['tag_removed'] = 'dashicons-tag';
    $dashicons['account_created'] = 'dashicons-admin-users';

    //todo WC dashicons
    $dashicons['wc_reached_checkout'] = '';
    $dashicons['wc_product_added_to_cart'] = '';
    $dashicons['wc_product_removed_from_cart'] = '';
    $dashicons['wc_product_purchased'] = '';

    //todo EDD dashicons
    $dashicons['edd_reached_checkout'] = '';
    $dashicons['edd_download_added_to_cart'] = '';
    $dashicons['edd_download_removed_from_cart'] = '';
    $dashicons['edd_download_removed_from_cart'] = '';

    $dashicons = apply_filters( 'wpfn_element_dashicons', $dashicons );

    if ( ! in_array( $step_type, array_keys( $dashicons ) ) )
        return false;

    return $dashicons[ $step_type ];
}

/**
 * Get the dashicon for a cetain step
 *
 * @param $step_id int Step ID
 * @return string the dashicon class
 */
function wpfn_get_step_dashicon_by_id( $step_id )
{
    $type = wpfn_get_step_type( $step_id );

    return wpfn_get_step_dashicon_by_step_type( $type );
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
 * create a new funnel!
 *
 * @return int|false new funnel ID, false on failure
 */
function wpfn_create_new_funnel()
{
    $title = __( 'My awesome new funnel!', 'wp-funnels' );
    $status = 'inactive';

    return wpfn_insert_new_funnel( $title, $status );
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
            if ( ! in_array( $type, wpfn_get_funnel_action_icons() ) )
                return false;
            break;
        case 'benchmark':
            if ( ! in_array( $type, wpfn_get_funnel_benchmark_icons() ) )
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
    return in_array( $element, wpfn_get_funnel_benchmark_icons() );
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
        if ( $subArgs[0] == 'ID' ){
            return intval( $subArgs[1] );
        }

    }

    return false;

}

/**
 * Return the html of the new step VIA ajax.
 *
 * @var $step_type string the typeof step to create
 * @var $
 */
function wpfn_get_step_html_via_ajax()
{
    //wp_die( 'made-it-here' );

    $step_type = $_POST['step_type'];
    $step_order = $_POST['step_order'];
    $funnel_id = wpfn_url_to_funnel_id( wp_get_referer() );

    $step_group = ( wpfn_is_benchmark( $step_type ) )? 'benchmark' : 'action' ;

    foreach ( glob( dirname( __FILE__ ) . "/admin/funnels/elements/*/*.php" ) as $filename )
    {
        include $filename;
    }

    $step_id = wpfn_add_funnel_step( $funnel_id, 'New Step', $step_group, $step_type, $step_order );

    ob_start();

    ?>
    <div id="<?php echo $step_id; ?>" class="postbox <?php echo $step_group; ?>">
        <button type="button" class="handlediv delete-step-<?php echo $step_id;?>">
            <span class="dashicons dashicons-trash"></span>
            <script>
                jQuery('.delete-step-<?php echo $step_id;?>').click( wpfn_delete_funnel_step );
            </script>
        </button>
        <h2 class="hndle ui-sortable-handle"><label for="<?php echo $step_id; ?>_title"><span class="dashicons <?php echo esc_attr( wpfn_get_step_dashicon_by_step_type( $step_type ) ); ?>"></span></label><input title="step title" type="text" id="<?php echo $step_id; ?>_title" name="<?php echo $step_id; ?>_title" class="regular-text" value="<?php echo __( wpfn_get_step_hndle( $step_id ), 'wp-funnels' ); ?>"></h2>
        <div class="inside">
            <?php do_action( 'wpfn_step_settings_before' ); ?>
            <?php do_action( 'wpfn_get_step_settings_' . $step_type, $step_id ); ?>
            <?php do_action( 'wpfn_step_settings_after' ); ?>
        </div>
    </div>
    <?php

    $content = ob_get_contents();

    ob_end_clean();

    wp_die( $content );
}

add_action( 'wp_ajax_wpfn_get_step_html', 'wpfn_get_step_html_via_ajax' );

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
 * Update the funnel and all of the funnel steps.
 *
 * @param $funnel_id
 */
function wpfn_save_funnel( $funnel_id )
{
    //todo user validation...
    if ( ! isset( $_POST[ 'save' ] ) )
        return;

    do_action( 'wpfn_save_funnel', $funnel_id );

    $title = sanitize_text_field( $_POST[ 'funnel_title' ] );
    wpfn_update_funnel( $funnel_id, 'funnel_title', $title );

    $status = sanitize_text_field( $_POST[ 'funnel_status' ] );
    if ( $status !== 'active' && $status !== 'inactive' )
        $status = 'inactive';

    wpfn_update_funnel( $funnel_id, 'funnel_status', $status );

    //get all the steps in the funnel.
    $steps = $_POST['steps'];

    foreach ( $steps as $i => $stepId )
    {
        $stepId = intval( $stepId );
        //quick Order Hack to get the proper order of a step...
        $order = $i + 1;
        wpfn_update_funnel_step( $stepId, 'funnelstep_order', $order );

        $title = sanitize_text_field( $_POST[ wpfn_prefix_step_meta( $stepId, 'title' ) ] );
        wpfn_update_funnel_step( $stepId, 'funnelstep_title', $title );

        $step_type = wpfn_get_step_type( $stepId );

        do_action( 'wpfn_save_step_' . $step_type, $stepId );

    }

    do_action( 'wpfn_save_funnel_after', $funnel_id );

    ?>
<div class="notice notice-success is-dismissible"><p><?php echo esc_html__( 'Funnel updated!', 'wp-funnels' ); ?></p></div>
    <?php
}

add_action( 'wpfn_funnel_editor_before_everything', 'wpfn_save_funnel' );
