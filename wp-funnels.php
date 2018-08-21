<?php
/*
Plugin Name: WP Funnels
Plugin URI: https://wordpress.org/plugins/groundhogg/
Description: CRM and marketing automation for WordPress
Version: 0.1.0
Author: Adrian Tobey
Author URI: http://health-check-team.example.com
Text Domain: groundhogg
Domain Path: /languages
*/

define ( 'WPFN_ASSETS_FOLDER', plugins_url( 'assets', __FILE__ ) );
define ( 'WPFN_INCLUDES_FOLDER', dirname( __FILE__ ) . '/includes/' );

//include dirname( __FILE__ ) . '/includes/admin/settings/settings.php';

foreach ( glob( dirname( __FILE__ ) . "/includes/*.php" ) as $filename )
{
	include $filename;
}

foreach ( glob( dirname( __FILE__ ) . "/includes/db/*.php" ) as $filename )
{
    include $filename;
}


/**
 * Create all the database tables
 */
function wpfn_activation()
{
	wpfn_create_contacts_db();
	wpfn_create_contact_meta_db();

	wpfn_create_emails_db();
	wpfn_create_email_meta_db();

	wpfn_create_events_db();

	wpfn_create_funnels_db();
	wpfn_create_funnel_meta_db();

	wpfn_create_funnelsteps_db();
	wpfn_create_funnelstep_meta_db();

	wpfn_create_contact_tags_db();
	wpfn_create_contact_tag_relationships_db();

	wpfn_create_superlinks_db();
}

register_activation_hook( __FILE__, 'wpfn_activation');