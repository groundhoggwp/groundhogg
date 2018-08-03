<?php
/**
 * Benchmark Functions
 *
 * Functions to have users complete benchmarks within funnels...
 *
 * @package     wp-funnels
 * @subpackage  Includes/Funnels
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

/**
 * Complete the benchmark and queue up the next action in the event queue
 * Also dequeues any previously enqueued events.
 *
 * @param $benchmark_id int the ID of the benchmark to be completed
 * @param $contact_id int the ID of the contact for which the benchmark is being completed
 */
function wpfn_complete_benchmark( $benchmark_id, $contact_id )
{
    do_action( 'wpfn_complete_benchmark_before', $benchmark_id );

    $funnel_id = wpfn_get_step_funnel( $benchmark_id );

    wpfn_dequeue_contact_funnel_events( $contact_id, $funnel_id );

    //todo implement reporting for funnel. I imagine it will appear in the meta or something for the step or the funnel itself.

    wpfn_enqueue_next_funnel_action( $benchmark_id, $contact_id );

    do_action( 'wpfn_complete_benchmark_after', $benchmark_id );
}

/**
 * Complete account_created benchmarks for the funnels.
 * Create a new contact record if one doesn't exist.
 * If one exists, use the existing contact record.
 *
 * @param $userId int the ID of the user which was created
 */
function wpfn_run_account_created_benchmark_action( $userId )
{
    //todo list of possible funnel steps.
    $user_info = get_userdata( $userId );

    if ( ! wpfn_get_contact_by_email( $user_info->user_email ) ){
        $contact_id = wpfn_quick_add_contact( $user_info->user_email, $_POST['first_name'], $_POST['last_name'] );
    } else {
        $contact = new WPFN_Contact( $user_info->user_email );
        $contact_id = $contact->getId();
    }

    $benchmarks = wpfn_get_funnel_steps_by_type( 'account_created' );

    //var_dump( $benchmarks );

    foreach ( $benchmarks as $benchmark ) {

        $step_id = intval( $benchmark['ID'] );
        $step_order = intval( $benchmark['funnelstep_order'] );
        $funnel_id = intval( $benchmark['funnel_id'] );

        $role = wpfn_get_step_meta( $step_id, 'role', true );

        if ( ( 1 === $step_order || wpfn_contact_is_in_funnel( $contact_id,  $funnel_id ) ) && in_array( $role, $user_info->roles ) ){
            //wp_die( 'made it here' );
            wpfn_complete_benchmark( $step_id, $contact_id );
        }
    }
}

add_action( 'user_register', 'wpfn_run_account_created_benchmark_action' );

/**
 * Complete the Page View benchmark.
 *
 * @param $post_object object post object goes unused.
 */
function wpfn_complete_page_view_benchmark( $post_object )
{
    if ( is_admin() )
        return;

    $contact = wpfn_get_the_contact();

    if ( ! $contact )
        return;

    $contact_id = $contact->getId();

    $benchmarks = wpfn_get_funnel_steps_by_type( 'page_view' );

    if ( ! $benchmarks )
        return;

    foreach ( $benchmarks as $benchmark ) {

        $step_id = intval( $benchmark['ID'] );
        $step_order = intval( $benchmark['funnelstep_order'] );
        $funnel_id = intval( $benchmarks['funnel_id'] );

        $page_id = wpfn_get_step_meta( $step_id, 'page_id', true );

        if ( ( 1 === $step_order || wpfn_contact_is_in_funnel( $contact_id,  $funnel_id ) ) && $page_id === get_the_ID() ){
            wpfn_complete_benchmark( $step_id, $contact_id );
        }
    }
}

add_action( 'the_post', 'wpfn_complete_page_view_benchmark' );