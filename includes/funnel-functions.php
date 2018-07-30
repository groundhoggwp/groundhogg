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
    $benchmarks[] = 'account_created';

    // WC compatibility
    if ( class_exists( 'WooCommerce' ) ){
        $benchmarks[] = 'wc_reached_checkout';
        $benchmarks[] = 'wc_product_added_to_cart';
        $benchmarks[] = 'wc_product_removed_from_cart';
        $benchmarks[] = 'wc_product_purchased';
    }

    //EDD compatibility
    if ( class_exists( 'EDD' ) ){
        $benchmarks[] = 'edd_reached_checkout';
        $benchmarks[] = 'edd_download_added_to_cart';
        $benchmarks[] = 'edd_download_removed_from_cart';
        $benchmarks[] = 'edd_download_purchased';
    }

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
    $actions[] = 'apply_tag';
    $actions[] = 'remove_tag';
    $actions[] = 'create_user';
    $actions[] = 'delete_user';
    $actions[] = 'wait';

    return apply_filters( 'wpfn_funnel_actions', $actions );
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