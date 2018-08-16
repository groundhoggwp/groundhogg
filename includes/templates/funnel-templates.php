<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-08-16
 * Time: 12:05 PM
 */

$funnel_templates = array();

/* template template

$funnel_templates[ '' ][ 'title' ] = '';
$funnel_templates[ '' ][ 'description' ] = '';
$funnel_templates[ '' ][ 'src' ] = 'https://via.placeholder.com/350x350';
$funnel_templates[ '' ][ 'steps' ] = array(
    array(
        'group' => 'benchmark',
        'type' => '',
        'title' => '',
        'meta' => array(
            '' => ''
        )
    ),
    array(
        'group' => 'action',
        'type' => '',
        'title' => '',
        'meta' => array(
            '' => ''
        )
    )
);

*/

/* Welcome Series */

$funnel_templates[ 'welcome' ][ 'title' ] = 'Welcome Series';
$funnel_templates[ 'welcome' ][ 'description' ] = 'A nice way to welcome new subscribers into your community.';
$funnel_templates[ 'welcome' ][ 'src' ] = 'https://via.placeholder.com/350x350';
$funnel_templates[ 'welcome' ][ 'steps' ] = array(
    array(
        'group' => 'benchmark',
        'type' => 'account_created',
        'title' => 'New subscriber',
        'meta' => array(
            'role' => 'subscriber'
        )
    ),
    array(
        'group' => 'action',
        'type' => 'send_email',
        'title' => 'Send Welcome Email',
        'meta' => array(
            '' => ''
        )
    ),
    array(
        'group' => 'action',
        'type' => 'delay_timer',
        'title' => 'Wait 1 day',
        'meta' => array(
            'delay_amount' => 1,
            'delay_type'   => 'days',
            'run_when'     => 'now'
        )
    ),
    array(
        'group' => 'action',
        'type' => 'send_email',
        'title' => 'Follow Up',
        'meta' => array(
            '' => ''
        )
    ),
);

/* Hype Series */

$funnel_templates[ 'hype' ][ 'title' ] = 'Hype Series';
$funnel_templates[ 'hype' ][ 'description' ] = 'Get your list excited for an event or product launch';
$funnel_templates[ 'hype' ][ 'src' ] = 'https://via.placeholder.com/350x350';
$funnel_templates[ 'hype' ][ 'steps' ] = array(
    array(
        'group' => 'benchmark',
        'type' => 'tag_applied',
        'title' => 'Start Series',
        'meta' => array(
            '' => ''
        )
    ),
    array(
        'group' => 'action',
        'type' => 'delay_timer',
        'title' => 'Wait Till Morning',
        'meta' => array(
            'delay_amount' => 1,
            'delay_type'   => 'hours',
            'run_when'     => 'later',
            'run_time'     => '09:30'
        )
    ),
    array(
        'group' => 'action',
        'type' => 'send_email',
        'title' => 'Let them know something is coming...',
        'meta' => array(
            '' => ''
        )
    ),
    array(
        'group' => 'action',
        'type' => 'delay_timer',
        'title' => 'Wait 1 Day',
        'meta' => array(
            'delay_amount' => 1,
            'delay_type'   => 'days',
            'run_when'     => 'now'
        )
    ),
    array(
        'group' => 'action',
        'type' => 'send_email',
        'title' => 'Give them a hint about the big thing',
        'meta' => array(
            '' => ''
        )
    ),
    array(
        'group' => 'action',
        'type' => 'delay_timer',
        'title' => 'Wait 1 Day',
        'meta' => array(
            'delay_amount' => 1,
            'delay_type'   => 'days',
            'run_when'     => 'now'
        )
    ),
    array(
        'group' => 'action',
        'type' => 'send_email',
        'title' => 'Tell the the details about the thing',
        'meta' => array(
            '' => ''
        )
    ),
    array(
        'group' => 'action',
        'type' => 'delay_timer',
        'title' => 'Wait 1 Day',
        'meta' => array(
            'delay_amount' => 1,
            'delay_type'   => 'days',
            'run_when'     => 'now'
        )
    ),
    array(
        'group' => 'action',
        'type' => 'send_email',
        'title' => 'Send them a link to the big thing',
        'meta' => array(
            '' => ''
        )
    ),
    array(
        'group' => 'benchmark',
        'type' => 'page_visited',
        'title' => 'Saw the big thing',
        'meta' => array(
            '' => ''
        )
    )
);

$funnel_templates = apply_filters( 'wpfn_funnel_templates', $funnel_templates );