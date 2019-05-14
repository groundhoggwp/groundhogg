<?php
/**
 * Funnel Templates
 *
 * @package     Templates
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$funnel_templates = array();

/* Welcome Series */

$funnel_templates[ 'welcome' ][ 'title' ] = _x( 'Welcome Series', 'funnel_template_name', 'groundhogg' );
$funnel_templates[ 'welcome' ][ 'description' ] = _x( 'A nice way to welcome new subscribers into your community.', 'funnel_template_description', 'groundhogg' );
$funnel_templates[ 'welcome' ][ 'src' ] = 'https://via.placeholder.com/350x250';
$funnel_templates[ 'welcome' ][ 'file' ] = dirname( __FILE__ ) . '/funnels/new-subscriber-welcome.funnel';

/* Hype Series */

$funnel_templates[ 'hype' ][ 'title' ] = _x( 'Hype Series', 'funnel_template_name', 'groundhogg' );
$funnel_templates[ 'hype' ][ 'description' ] = _x('Get your list excited for an event or product launch', 'funnel_template_description', 'groundhogg' );
$funnel_templates[ 'hype' ][ 'src' ] = 'https://via.placeholder.com/350x250';
$funnel_templates[ 'hype' ][ 'file' ] = dirname( __FILE__ ) . '/funnels/hype-series.funnel';

/* Long term Nurture */

$funnel_templates[ 'long_term_nurture' ][ 'title' ] = _x( 'Long Term Nurture', 'funnel_template_name', 'groundhogg' );
$funnel_templates[ 'long_term_nurture' ][ 'description' ] = _x( 'For when you need to put the conversation on hold.', 'funnel_template_description', 'groundhogg' );
$funnel_templates[ 'long_term_nurture' ][ 'src' ] = 'https://via.placeholder.com/350x250';
$funnel_templates[ 'long_term_nurture' ][ 'file' ] = dirname( __FILE__ ) . '/funnels/long-term-nurture.funnel';

/* Webinar Registration */

$funnel_templates[ 'webinar_registration' ][ 'title' ] = _x( 'Webinar Registration', 'funnel_template_name', 'groundhogg' );
$funnel_templates[ 'webinar_registration' ][ 'description' ] = _x( 'Collect leads, send reminders, and follow up with this!', 'funnel_template_description', 'groundhogg' );
$funnel_templates[ 'webinar_registration' ][ 'src' ] = 'https://via.placeholder.com/350x250';
$funnel_templates[ 'webinar_registration' ][ 'file' ] = dirname( __FILE__ ) . '/funnels/webinar-registration.funnel';

/* Feedback Request */

$funnel_templates[ 'feedback_request' ][ 'title' ] = _x( 'Feedback Request', 'funnel_template_name', 'groundhogg' );
$funnel_templates[ 'feedback_request' ][ 'description' ] = _x('Looking to generate some reviews? This is what you need.', 'funnel_template_description', 'groundhogg' );
$funnel_templates[ 'feedback_request' ][ 'src' ] = 'https://via.placeholder.com/350x250';
$funnel_templates[ 'feedback_request' ][ 'file' ] = dirname( __FILE__ ) . '/funnels/feedback-request.funnel';

/* Feedback Request */

$funnel_templates[ 'lead_magnet' ][ 'title' ] = _x( 'Lead Magnet Download', 'funnel_template_name', 'groundhogg' );
$funnel_templates[ 'lead_magnet' ][ 'description' ] = _x('Giving away a lead magnet? Make it irresistible.', 'funnel_template_description', 'groundhogg' );
$funnel_templates[ 'lead_magnet' ][ 'src' ] = 'https://via.placeholder.com/350x250';
$funnel_templates[ 'lead_magnet' ][ 'file' ] = dirname( __FILE__ ) . '/funnels/lead-magnet-download.funnel';

/* Login Abandonment */

$funnel_templates[ 'login_abandonment' ][ 'title' ] = _x( 'Login Abandonment', 'funnel_template_name', 'groundhogg' );
$funnel_templates[ 'login_abandonment' ][ 'description' ] = _x('Remind your subscribers to login every once in a while.', 'funnel_template_description', 'groundhogg' );
$funnel_templates[ 'login_abandonment' ][ 'src' ] = 'https://via.placeholder.com/350x250';
$funnel_templates[ 'login_abandonment' ][ 'file' ] = dirname( __FILE__ ) . '/funnels/login-abandonment.funnel';

/* Start from scratch */

$funnel_templates[ 'scratch' ][ 'title' ] = _x( 'Start From Scratch', 'funnel_template_name', 'groundhogg' );
$funnel_templates[ 'scratch' ][ 'description' ] = _x( 'Have some inspiration? Use this blank canvas!', 'funnel_template_description', 'groundhogg' );
$funnel_templates[ 'scratch' ][ 'src' ] = 'https://via.placeholder.com/350x250';
$funnel_templates[ 'scratch' ][ 'file' ] = dirname( __FILE__ ) . '/funnels/start-from-scratch.funnel';

$funnel_templates = apply_filters( 'wpgh_funnel_templates', $funnel_templates );
$funnel_templates = apply_filters( 'groundhogg/templates/funnels', $funnel_templates );