<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-08-16
 * Time: 12:05 PM
 */

$funnel_templates = array();

/* Welcome Series */

$funnel_templates[ 'welcome' ][ 'title' ] = 'Welcome Series';
$funnel_templates[ 'welcome' ][ 'description' ] = 'A nice way to welcome new subscribers into your community.';
$funnel_templates[ 'welcome' ][ 'src' ] = 'https://via.placeholder.com/350x250';
$funnel_templates[ 'welcome' ][ 'file' ] = dirname( __FILE__ ) . '/funnels/new-subscriber-welcome.funnel';

/* Hype Series */

$funnel_templates[ 'hype' ][ 'title' ] = 'Hype Series';
$funnel_templates[ 'hype' ][ 'description' ] = 'Get your list excited for an event or product launch';
$funnel_templates[ 'hype' ][ 'src' ] = 'https://via.placeholder.com/350x250';
$funnel_templates[ 'hype' ][ 'file' ] = dirname( __FILE__ ) . '/funnels/hype-series.funnel';

/* Long term Nurture */

$funnel_templates[ 'long_term_nurture' ][ 'title' ] = 'Long Term Nurture';
$funnel_templates[ 'long_term_nurture' ][ 'description' ] = 'For when you need to put the conversation on hold.';
$funnel_templates[ 'long_term_nurture' ][ 'src' ] = 'https://via.placeholder.com/350x250';
$funnel_templates[ 'long_term_nurture' ][ 'file' ] = dirname( __FILE__ ) . '/funnels/long-term-nurture.funnel';

/* Webinar Registration */

$funnel_templates[ 'webinar_registration' ][ 'title' ] = 'Webinar Registration';
$funnel_templates[ 'webinar_registration' ][ 'description' ] = 'Collect leads, send reminders, and follow up with this!';
$funnel_templates[ 'webinar_registration' ][ 'src' ] = 'https://via.placeholder.com/350x250';
$funnel_templates[ 'webinar_registration' ][ 'file' ] = dirname( __FILE__ ) . '/funnels/webinar-registration.funnel';

/* Feedback Request */

$funnel_templates[ 'feedback_request' ][ 'title' ] = 'Feedback Request';
$funnel_templates[ 'feedback_request' ][ 'description' ] = 'Looking to generate some reviews? This is what you need.';
$funnel_templates[ 'feedback_request' ][ 'src' ] = 'https://via.placeholder.com/350x250';
$funnel_templates[ 'feedback_request' ][ 'file' ] = dirname( __FILE__ ) . '/funnels/feedback-request.funnel';

/* Start from scratch */

$funnel_templates[ 'scratch' ][ 'title' ] = 'Start From Scratch';
$funnel_templates[ 'scratch' ][ 'description' ] = 'Have some inspiration? Use this blank canvas!';
$funnel_templates[ 'scratch' ][ 'src' ] = 'https://via.placeholder.com/350x250';
$funnel_templates[ 'scratch' ][ 'file' ] = dirname( __FILE__ ) . '/funnels/start-from-scratch.funnel';

$funnel_templates = apply_filters( 'wpgh_funnel_templates', $funnel_templates );