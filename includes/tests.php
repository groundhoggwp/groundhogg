<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-08-03
 * Time: 9:24 AM
 */

//add_action( 'admin_init', 'test_event_queue' );

function test_event_queue()
{

    $step_id = wpfn_enqueue_next_funnel_action( 43, 5 );

    var_dump( $step_id );

    wp_die();
}

//add_action( 'admin_init', 'test_email' );

function test_email()
{
    wpfn_send_email( 5, 2 );
}

