<?php
/**
 * Benchmark Functions
 *
 * Functions to have users complete benchmarks within funnels...
 *
 * @package     groundhogg
 * @subpackage  Includes/Funnels
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

/**
 * Return a list of slugs for the available benchmarks in the funnel builder
 *
 * @return array
 */
function wpgh_get_funnel_benchmarks()
{
    $benchmarks = array();

    $benchmarks['form_fill'] = array( 'title' => __('Form Filled'), 'icon' => WPGH_ASSETS_FOLDER . '/images/builder-icons/form.png' );
    $benchmarks['email_opened'] = array( 'title' => __('Email opened', 'groundhogg' ), 'icon' => WPGH_ASSETS_FOLDER . '/images/builder-icons/opened-email.png' );
    $benchmarks['email_confirmed'] = array( 'title' => __('Email Confirmed', 'groundhogg' ), 'icon' => WPGH_ASSETS_FOLDER . '/images/builder-icons/email-confirmed.png' );
//    $benchmarks['link_clicked'] = array( 'title' => __('Link Clicked', 'groundhogg' ), 'icon' => WPGH_ASSETS_FOLDER . '/images/builder-icons/' );
    $benchmarks['page_visited'] = array( 'title' => __('Page Visited', 'groundhogg' ), 'icon' => WPGH_ASSETS_FOLDER . '/images/builder-icons/page-visited.png' );
    $benchmarks['tag_applied']  = array( 'title' => __('Tag Applied', 'groundhogg' ), 'icon' => WPGH_ASSETS_FOLDER . '/images/builder-icons/tag-applied.png' );
    $benchmarks['tag_removed']  = array( 'title' => __('Tag Removed', 'groundhogg' ), 'icon' => WPGH_ASSETS_FOLDER . '/images/builder-icons/tag-removed.png' );
    $benchmarks['account_created'] = array( 'title' => __('Account Created', 'groundhogg' ), 'icon' => WPGH_ASSETS_FOLDER . '/images/builder-icons/account-created.png' );
    $benchmarks['role_changed']    = array( 'title' => __('Role Changed', 'groundhogg' ), 'icon' => WPGH_ASSETS_FOLDER . '/images/builder-icons/role-changed.png' );

    return apply_filters( 'wpgh_funnel_benchmarks', $benchmarks );
}

/**
 * Complete the benchmark and queue up the next action in the event queue
 * Also dequeues any previously enqueued events.
 *
 * @param $benchmark_id int the ID of the benchmark to be completed
 * @param $contact_id int the ID of the contact for which the benchmark is being completed
 */
function wpgh_complete_benchmark( $benchmark_id, $contact_id )
{
    do_action( 'wpgh_complete_benchmark_before', $benchmark_id );

    $funnel_id = wpgh_get_step_funnel( $benchmark_id );

    //do not run if the funnel is set to inactive.
    if ( ! wpgh_is_funnel_active( $funnel_id ) )
        return;

    /* stop previously queued events from running and set their status to skipped. */
    wpgh_dequeue_contact_funnel_events( $contact_id, $funnel_id );

    /* Rather than juist starting the next action, enter this benchmark into the queue for easy goal reporting */
    wpgh_enqueue_event( time(), wpgh_get_step_funnel( $benchmark_id ), $benchmark_id, $contact_id );

    /* set the active contact */
    wpgh_set_the_contact( $contact_id );
    /* set the active funnel cookie*/
    wpgh_set_the_funnel( $funnel_id );
    /* set the funnel step cookie*/
    wpgh_set_the_step( $benchmark_id );

    do_action( 'wpgh_complete_benchmark_after', $benchmark_id );
}


/**
 * Check to see if the benchmark can kick off a funnel.
 *
 * @param $benchmark_id int ID of the benchmark
 * @return bool whether it can start a funnel
 */
function wpgh_is_starting( $benchmark_id  )
{

    $step_order = wpgh_get_step_order( $benchmark_id );
    $funnel_id = wpgh_get_step_funnel( $benchmark_id );

    if ( $step_order === 1 )
        return true;

    $step_order -= 1;

    while ( $step_order > 0 ){

        $step =  wpgh_get_funnel_step_by_order( $funnel_id, $step_order );

        if ( $step['funnelstep_group'] === 'action' ){
            return false;
        }

        $step_order -= 1;
    }

    return true;
}


/**
 * Complete account_created benchmarks for the funnels.
 * Create a new contact record if one doesn't exist.
 * If one exists, use the existing contact record.
 *
 * @param $userId int the ID of the user which was created
 */
function wpgh_run_account_created_benchmark_action( $userId )
{
    //todo list of possible funnel steps.
    $user_info = get_userdata( $userId );

    if ( ! wpgh_get_contact_by_email( $user_info->user_email ) ){
        $contact_id = wpgh_quick_add_contact( $user_info->user_email, $_POST['first_name'], $_POST['last_name'] );
    } else {
        $contact = new WPGH_Contact( $user_info->user_email );
        $contact_id = $contact->ID;
    }

    $benchmarks = wpgh_get_funnel_steps_by_type( 'account_created' );

    foreach ( $benchmarks as $benchmark ) {

        $step_id = intval( $benchmark['ID'] );
        $step_order = intval( $benchmark['funnelstep_order'] );
        $funnel_id = intval( $benchmark['funnel_id'] );

        $role = wpgh_get_step_meta( $step_id, 'role', true );

        if ( ( wpgh_is_starting( $step_id ) || wpgh_contact_is_in_funnel( $contact_id,  $funnel_id ) ) && in_array( $role, $user_info->roles ) ){
            wpgh_complete_benchmark( $step_id, $contact_id );
        }
    }
}

add_action( 'user_register', 'wpgh_run_account_created_benchmark_action' );

/**
 * Run the benchmark for user role changes. Helpful for membership sites.
 *
 * @param $userId int the ID of a user.
 * @param $cur_role string the new role of the user
 * @param $old_roles array list of previous user roles.
 */
function wpgh_run_user_role_changed_benchmark( $userId, $cur_role, $old_roles )
{
    $user_info = get_userdata( $userId );

    $contact = new WPGH_Contact( $user_info->user_email );

    if ( ! $contact->email )
        return;

    $contact_id = $contact->ID;

    $benchmarks = wpgh_get_funnel_steps_by_type( 'role_changed' );

    foreach ( $benchmarks as $benchmark ) {

        $step_id = intval( $benchmark['ID'] );
        $step_order = intval( $benchmark['funnelstep_order'] );
        $funnel_id = intval( $benchmark['funnel_id'] );

        $role = wpgh_get_step_meta( $step_id, 'role', true );

        if ( ( wpgh_is_starting( $step_id ) || wpgh_contact_is_in_funnel( $contact_id,  $funnel_id ) ) && $cur_role === $role ){
            wpgh_complete_benchmark( $step_id, $contact_id );
        }
    }
}

add_action( 'set_user_role', 'wpgh_run_user_role_changed_benchmark', 10, 3 );

/**
 * Enqueue the scripts for the event runner process.
 * Appears on front-end & backend as it will be run by traffic to the site.
 */
function wpgh_enqueue_page_view_scripts()
{
	wp_enqueue_script( 'wpgh-page-view', WPGH_ASSETS_FOLDER . '/js/page-view.js' , array('jquery') );
	wp_localize_script( 'wpgh-page-view', 'wpgh_ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
}

add_action( 'wp_enqueue_scripts', 'wpgh_enqueue_page_view_scripts' );
//add_action( 'admin_enqueue_scripts', 'wpgh_enqueue_page_view_scripts' );

/**
 * Complete the Page View benchmark.
 *
 * @param $post_object object post object goes unused.
 */
function wpgh_complete_page_visited_benchmark()
{
    if ( ! wp_doing_ajax() )
        return;

    $contact = wpgh_get_current_contact();

    if ( ! $contact )
        return;

    $contact_id = $contact->ID;

    $benchmarks = wpgh_get_funnel_steps_by_type( 'page_visited' );

    if ( ! $benchmarks )
        return;

    foreach ( $benchmarks as $benchmark ) {

        $step_id = intval( $benchmark['ID'] );
        $step_order = intval( $benchmark['funnelstep_order'] );
        $funnel_id = intval( $benchmark['funnel_id'] );

        if ( wpgh_is_funnel_active( $funnel_id ) ){

            $match_type = wpgh_get_step_meta( $step_id, 'match_type', true );
            $match_url = wpgh_get_step_meta( $step_id, 'url_match', true );

            if ( $match_type === 'exact' ){
                $is_page = wp_get_referer() === $match_url;
            } else {
                $is_page = strpos( wp_get_referer(), $match_url ) !== false;
            }

            if ( ( wpgh_is_starting( $step_id ) || wpgh_contact_is_in_funnel( $contact_id,  $funnel_id ) ) && $is_page ){
                wpgh_complete_benchmark( $step_id, $contact_id );
            }
        }
    }

    wp_die();
}

add_action( 'wp_ajax_wpgh_page_view', 'wpgh_complete_page_visited_benchmark' );
add_action( 'wp_ajax_nopriv_wpgh_page_view', 'wpgh_complete_page_visited_benchmark' );

/**
 * Complete the tag removed benchmark
 *
 * @param $contact_id int the ID of the contact
 * @param $tag_id int the ID of the tag which was just removed
 */
function wpgh_complete_tag_removed_benchmark( $contact_id, $tag_id )
{
    $benchmarks = wpgh_get_funnel_steps_by_type( 'tag_removed' );

    if ( ! $benchmarks )
        return;

    foreach ( $benchmarks as $benchmark ) {

        $step_id = intval( $benchmark['ID'] );
        $step_order = intval( $benchmark['funnelstep_order'] );
        $funnel_id = intval( $benchmark['funnel_id'] );

        $tags = wpgh_get_step_meta( $step_id, 'tags', true );

        if ( ( wpgh_is_starting( $step_id ) || wpgh_contact_is_in_funnel( $contact_id,  $funnel_id ) ) && in_array( $tag_id, $tags ) ){
            wpgh_complete_benchmark( $step_id, $contact_id );
        }
    }
}

add_action( 'wpgh_tag_removed' , 'wpgh_complete_tag_removed_benchmark' , 10, 2 );

/**
 * run the tag applied benchmark
 *
 * @param $contact_id int the ID of the contact
 * @param $tag_id int the ID of the tag
 */
function wpgh_complete_tag_applied_benchmark( $contact_id, $tag_id )
{
    $benchmarks = wpgh_get_funnel_steps_by_type( 'tag_applied' );

    if ( ! $benchmarks )
        return;

    foreach ( $benchmarks as $benchmark ) {

        $step_id = intval( $benchmark['ID'] );
        $step_order = intval( $benchmark['funnelstep_order'] );
        $funnel_id = intval( $benchmark['funnel_id'] );

        $tags = wpgh_get_step_meta( $step_id, 'tags', true );

        if ( ! $tags )
            continue;

        $tags = array_map( 'intval', $tags );

        if ( ( wpgh_is_starting( $step_id ) || wpgh_contact_is_in_funnel( $contact_id,  $funnel_id ) ) && in_array( $tag_id, $tags ) ){
            wpgh_complete_benchmark( $step_id, $contact_id );
        }
    }
}

add_action( 'wpgh_tag_applied' , 'wpgh_complete_tag_applied_benchmark' , 10, 2 );

/**
 * run the form-filled benchmark
 *
 * @param $step_id int the ID of the form step...
 * @param $contact_id int the ID of the contact
 */
function wpgh_complete_form_fill_benchmark( $step_id, $contact_id )
{
    $funnel_id = wpgh_get_step_funnel( $step_id );

    if ( ( wpgh_is_starting( $step_id ) || wpgh_contact_is_in_funnel( $contact_id,  $funnel_id ) ) ){
        wpgh_complete_benchmark( $step_id, $contact_id );
    }
}

add_action( 'wpgh_form_submit' , 'wpgh_complete_form_fill_benchmark' , 10, 2 );

/**
 * When an email is opened complete the followup benchmarks.
 *
 * @param $contact_id int ID of the contact which complete the step
 * @param $email_id int the ID of the email which was opened
 * @param $email_step_id int the ID of the associated Email step
 * @param $funnel_id int ID of the associated funnel
 */
function wpgh_complete_email_opened_benchmark( $contact_id, $email_id, $email_step_id, $funnel_id )
{
    $benchmarks = wpgh_get_funnel_steps_by_type( 'email_opened' );

    if ( ! $benchmarks )
        return;

    foreach ( $benchmarks as $benchmark ) {

        $step_id = intval( $benchmark['ID'] );
        $step_order = intval( $benchmark['funnelstep_order'] );
        $funnel_id = intval( $benchmark['funnel_id'] );

        $steps = wpgh_get_step_meta( $step_id, 'emails', true );

        if ( ( wpgh_is_starting( $step_id ) || wpgh_contact_is_in_funnel( $contact_id,  $funnel_id ) ) && in_array( $email_step_id, $steps ) ){
            wpgh_complete_benchmark( $step_id, $contact_id );
        }
    }}

add_action( 'wpgh_email_opened', 'wpgh_complete_email_opened_benchmark', 10, 4 );

/**
 * When an email address is confirmed complete the followup benchmarks.
 *
 * @param $contact_id int ID of the contact which complete the step
 * @param $in_funnel_id int ID of the associated funnel
 */
function wpgh_complete_email_confirmed_benchmark( $contact_id, $in_funnel_id )
{
    $benchmarks = wpgh_get_funnel_steps_by_type( 'email_confirmed' );

    if ( ! $benchmarks )
        return;

    foreach ( $benchmarks as $benchmark ) {

        $step_id = intval( $benchmark['ID'] );
        $step_order = intval( $benchmark['funnelstep_order'] );
        $funnel_id = intval( $benchmark['funnel_id'] );

        if ( ( wpgh_is_starting( $step_id ) || wpgh_contact_is_in_funnel( $contact_id,  $funnel_id ) ) && $in_funnel_id === $funnel_id ){
            wpgh_complete_benchmark( $step_id, $contact_id );
        }
    }
}

add_action( 'wpgh_email_confirmed', 'wpgh_complete_email_confirmed_benchmark', 10, 2 );
