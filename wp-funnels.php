<?php
/*
Plugin Name: WP Funnels
Plugin URI: https://wordpress.org/plugins/wp-funnels/
Description: CRM and marketing automation for WordPress
Version: 0.1.0
Author: Adrian Tobey
Author URI: http://health-check-team.example.com
Text Domain: wp-funnels
Domain Path: /languages
*/

include dirname( __FILE__ ) . '/includes/class-contact.php';
include dirname( __FILE__ ) . '/includes/contact-db.php';
include dirname( __FILE__ ) . '/includes/contact-functions.php';
include dirname( __FILE__ ) . '/includes/email-db.php';
include dirname( __FILE__ ) . '/includes/email-functions.php';
include dirname( __FILE__ ) . '/includes/field-functions.php';


/**
 * Create all the database tables
 */
function wpfn_activation()
{

	wpfn_create_contacts_db();
	wpfn_create_contact_meta_db();

	wpfn_create_emails_db();
	wpfn_create_email_meta_db();

}

register_activation_hook( __FILE__, 'wpfn_activation');