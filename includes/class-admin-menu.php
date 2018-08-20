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

        $page_title = 'Groundhogg';
        $menu_title = 'Groundhogg';
        $capability = 'manage_options';
        $slug = 'groundhogg';
        $callback = array( $this, 'groundhogg_callback' );
        $icon = 'dashicons-email-alt';
        $position = 2;

        $settings_page = add_menu_page( $page_title, $menu_title, $capability, $slug, $callback, $icon, $position );

        $contacts_admin_add = add_submenu_page(
            'groundhogg',
            'Contacts',
            'Contacts',
            'manage_options',
            'contacts',
            array( $this, 'contacts_callback' )
        );

        $tags_admin_add = add_submenu_page(
            'groundhogg',
            'Tags',
            'Tags',
            'manage_options',
            'tags',
            array( $this, 'tags_callback' )
        );

		$email_admin_add = add_submenu_page(
			'groundhogg',
			'Emails',
			'Emails',
			'manage_options',
			'emails',
			array( $this, 'emails_callback' )
		);

        add_action( "load-" . $email_admin_add, array( $this, 'emails_help_bar' ) );

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

        remove_submenu_page( 'groundhogg', 'groundhogg' );

    }

	function groundhogg_callback()
    {

    }

	function emails_callback()
	{
		include dirname( __FILE__ ) . '/admin/emails/emails.php';
	}

	function emails_help_bar()
    {
        $screen = get_current_screen();

        $screen->add_help_tab(
            array(
                'id'      => 'gh_overview',
                'title'   => __( 'Overview' ),
                'content' => '<p>' . __( 'Unlike most marketing automation platforms Groundhogg made the decision to <strong>store emails globally</strong>. That means that you can use the same email across different funnels without ever having to re-write it.
                From this screen you can <strong>View/Edit/Delete</strong> emails and see their respective open rates across different funnels.', 'groundhogg' ) . '</p>'
            )
        );

        $screen->add_help_tab(
            array(
                'id'      => 'gh_add',
                'title'   => __( 'Add New' ),
                'content' => '<p>' . __( 'When you add a new email you can either select a pre-written email template created by our in house digital marketing specialists or you can select one of your own past written emails to copy.', 'groundhogg' ) . '</p>'
            )
        );

        $screen->add_help_tab(
            array(
                'id'      => 'gh_edit',
                'title'   => __( 'Editing' ),
                'content' => '<p>' . __( 'When you are editing an email, you can drag in new blocks from the right hand side into your email content. You can edit the appearance of the email with the settings on the left hand side of the email content. When designing our
                email builder we made the decision to keep features sparse. Hence no columns and no frilly html stuff. Just the basics. The reason for this being 80% of email is read on mobile, so our email builder is optimized for better mobile consumption.', 'groundhogg' ) . '</p>' .
                    '<p>' . __( 'When you believe an email is ready for sending set the status to <strong>Ready</strong> and then you can use it in any broadcast or funnel. An email which is in draft mode will not be sent.' ) . '</p>'
            )
        );

        $screen->add_help_tab(
            array(
                'id'      => 'gh_test',
                'title'   => __( 'Testing' ),
                'content' => '<p>' . __( 'To test an email simply check off the <strong>Send Test</strong> checkbox and select the user account you\'d like to send the test to. For best results, use a minimal number of images, make text easy to read and write good content. Tests will be sent regardless of the email\'s current status.' , 'groundhogg' ) . '</p>'
            )
        );

    }

	function funnels_callback()
	{
		include dirname( __FILE__ ) . '/admin/funnels/funnels.php';
	}

	function funnels_help_bar()
    {
        $screen = get_current_screen();

        $screen->add_help_tab(
            array(
                'id'      => 'gh_overview',
                'title'   => __( 'Overview' ),
                'content' => '<p>' . __( '', 'groundhogg' ) . '</p>'
            )
        );
    }

    function contacts_callback()
    {
        include dirname( __FILE__ ) . '/admin/contacts/contacts.php';
    }

    function tags_callback()
    {
        include dirname( __FILE__ ) . '/admin/tags/tags.php';
    }

}

new WPFN_Admin_Menu();