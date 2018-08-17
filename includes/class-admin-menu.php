<?php
/**
 * Created by PhpStorm.
 * User: Adrian
 * Date: 2018-08-16
 * Time: 8:06 PM
 */

class WPFN_Admin_Menu
{

	var $settings_page;

	function __construct() {

		if ( ! class_exists( 'WPFN_Settings_page' ) )
			include dirname( __FILE__ ) . '/admin/settings/settings.php';

		$this->settings_page = new WPFN_Settings_Page();

		add_action( 'admin_menu', array( $this, 'setup_menu_items' ) );

	}

	function setup_menu_items()
	{

		$email_admin_add = add_submenu_page(
			'groundhogg',
			'Emails',
			'Emails',
			'manage_options',
			'emails',
			array( $this, 'emails_callback' )
		);

		$funnel_admin_add = add_submenu_page(
			'groundhogg',
			'Funnels',
			'Funnels',
			'manage_options',
			'funnels',
			array( $this, 'funnels_callback' )
		);

		add_submenu_page(
			'groundhogg',
			'Settings',
			'Settings',
			'manage_options',
			'groundhogg',
			array( $this->settings_page, 'wpfn_settings_content' )
		);
	}

	function emails_callback()
	{
		include dirname( __FILE__ ) . '/admin/emails/emails.php';
	}

	function funnels_callback()
	{
		include dirname( __FILE__ ) . '/admin/funnels/funnels.php';
	}

}

new WPFN_Admin_Menu();