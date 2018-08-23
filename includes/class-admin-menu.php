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
    var $emails_page;
    var $funnels_page;
    var $superlink_page;
    var $tags_page;
    var $contacts_page;

    function __construct()
    {

        if (!class_exists('WPFN_Settings_page'))
            include dirname(__FILE__) . '/admin/settings/settings.php';

        $this->settings_page = new WPFN_Settings_Page();

        if (!class_exists('WPFN_Emails_Page'))
            include dirname(__FILE__) . '/admin/emails/emails.php';

        $this->emails_page = new WPFN_Emails_Page();

        if (!class_exists('WPFN_Funnels_Page'))
            include dirname(__FILE__) . '/admin/funnels/funnels.php';

        $this->funnels_page = new WPFN_Funnels_Page();

        if (!class_exists('WPFN_Superlinks_Page'))
            include dirname(__FILE__) . '/admin/links/superlinks.php';

        $this->superlink_page = new WPFN_Superlinks_Page();

        if (!class_exists('WPFN_Tags_Page'))
            include dirname(__FILE__) . '/admin/tags/tags.php';

        $this->tags_page = new WPFN_Tags_Page();

        if (!class_exists('WPFN_Contacts_Page'))
            include dirname(__FILE__) . '/admin/contacts/contacts.php';

        $this->contacts_page = new WPFN_Contacts_Page();

        add_action('admin_menu', array($this, 'setup_menu_items'));

    }

    function setup_menu_items()
    {
        $page_title = 'Groundhogg';
        $menu_title = 'Groundhogg';
        $capability = 'manage_options';
        $slug = 'groundhogg';
        $callback = array($this->settings_page, 'wpfn_settings_content');
        $icon = 'dashicons-email-alt';
        $position = 2;

        $settings_page = add_menu_page(
            $page_title,
            $menu_title,
            $capability,
            $slug,
            $callback,
            $icon,
            $position
        );

        $contacts_admin_add = add_submenu_page(
            'groundhogg',
            'Contacts',
            'Contacts',
            'manage_options',
            'gh_contacts',
            array($this->contacts_page, 'page')
        );

        $tags_admin_add = add_submenu_page(
            'groundhogg',
            'Tags',
            'Tags',
            'manage_options',
            'gh_tags',
            array($this->tags_page, 'page')
        );

        $superlinks_admin_add = add_submenu_page(
            'groundhogg',
            'Superlinks',
            'Superlinks',
            'manage_options',
            'gh_superlinks',
            array($this->superlink_page, 'page')
        );

        add_action("load-" . $superlinks_admin_add, array($this, 'superlinks_help_bar'));

        $email_admin_add = add_submenu_page(
            'groundhogg',
            'Emails',
            'Emails',
            'manage_options',
            'gh_emails',
            array($this->emails_page, 'page')
        );

        add_action("load-" . $email_admin_add, array($this, 'emails_help_bar'));

        $funnel_admin_add = add_submenu_page(
            'groundhogg',
            'Funnels',
            'Funnels',
            'manage_options',
            'gh_funnels',
            array($this->funnels_page, 'page')
        );

        add_action("load-" . $funnel_admin_add, array($this, 'funnels_help_bar'));

        add_submenu_page(
            'groundhogg',
            'Settings',
            'Settings',
            'manage_options',
            'groundhogg',
            array($this->settings_page, 'wpfn_settings_content')
        );

        remove_submenu_page('groundhogg', 'groundhogg');

    }

    function emails_help_bar()
    {
        $screen = get_current_screen();

        $screen->add_help_tab(
            array(
                'id' => 'gh_overview',
                'title' => __('Overview'),
                'content' => '<p>' . __('Unlike most marketing automation platforms Groundhogg made the decision to <strong>store emails globally</strong>. That means that you can use the same email across different funnels without ever having to re-write it.
                From this screen you can <strong>View/Edit/Delete</strong> emails and see their respective open rates across different funnels.', 'groundhogg') . '</p>'
            )
        );

        $screen->add_help_tab(
            array(
                'id' => 'gh_add',
                'title' => __('Add New'),
                'content' => '<p>' . __('When you add a new email you can either select a pre-written email template created by our in house digital marketing specialists or you can select one of your own past written emails to copy.', 'groundhogg') . '</p>'
            )
        );

        $screen->add_help_tab(
            array(
                'id' => 'gh_edit',
                'title' => __('Editing'),
                'content' => '<p>' . __('When you are editing an email, you can drag in new blocks from the right hand side into your email content. You can edit the appearance of the email with the settings on the left hand side of the email content. When designing our
                email builder we made the decision to keep features sparse. Hence no columns and no frilly html stuff. Just the basics. The reason for this being 80% of email is read on mobile, so our email builder is optimized for better mobile consumption.', 'groundhogg') . '</p>' .
                    '<p>' . __('When you believe an email is ready for sending set the status to <strong>Ready</strong> and then you can use it in any broadcast or funnel. An email which is in draft mode will not be sent.') . '</p>'
            )
        );

        $screen->add_help_tab(
            array(
                'id' => 'gh_test',
                'title' => __('Testing'),
                'content' => '<p>' . __('To test an email simply check off the <strong>Send Test</strong> checkbox and select the user account you\'d like to send the test to. For best results, use a minimal number of images, make text easy to read and write good content. Tests will be sent regardless of the email\'s current status.', 'groundhogg') . '</p>'
            )
        );

    }

    function funnels_help_bar()
    {
        $screen = get_current_screen();

        $screen->add_help_tab(
            array(
                'id' => 'gh_overview',
                'title' => __('Overview'),
                'content' => '<p>' . __('Here you can edit your funnels. A funnel is a set of steps which can run automation based on contact interactions with your site. You can view the number of active contacts in each funnel, as well as when it was created and last updated.', 'groundhogg') . '</p>'
                    . '<p>' . __('Funnels can be either Active/Inactive/Archived. If a funnel is Inactive, no contacts can enter and any contacts that may have been in the funnel will stop moving forward. The same goes for Archived funnels which simply do not show in the main list.', 'groundhogg') . '</p>'
            )
        );

        $screen->add_help_tab(
            array(
                'id' => 'gh_add',
                'title' => __('Add A Funnel'),
                'content' => '<p>' . __('To create a new funnel, simply click the Add New Button in the top left and select a pre-built funnel template. If you have a funnel import file you can click the import tab and upload the funnel file which will auto generate a funnel for you.', 'groundhogg') . '</p>'
            )
        );

        $screen->add_help_tab(
            array(
                'id' => 'gh_edit',
                'title' => __('Editing'),
                'content' => '<p>' . __('When editing a funnel you can add Funnel Steps. Funnel Steps are either Benchmarks or Actions. Benchmarks are whenever a Contact "does" something, while Actions are doing thing to a contact such as sending an email. Simply drag in the desired funnel steps in any order.', 'groundhogg') . '</p>'
                    . '<p>' . __('Actions are run sequentially, so when an action takes place, it simply loads the next action. That means if you need to change it you can!', 'groundhogg') . '</p>'
                    . '<p>' . __('Benchmarks are a bit different. If you have several benchmarks in a row, what happens is once one of them is completed by a contact the first action found proceeding that benchmark is launched, skipping all other benchmarks. That way you can have multiple automation triggers. ', 'groundhogg') . '</p>'
                    . '<p>' . __('Once a benchmark is complete all actions that are scheduled before that benchmark will stop immediately.', 'groundhogg') . '</p>'
            )
        );

        $screen->add_help_tab(
            array(
                'id' => 'gh_reporting',
                'title' => __('Reporting'),
                'content' => '<p>' . __('To view funnel reporting, simply dgo to the editing screen of any funnel, and then toggle the Reporting/Editing switch in the reporting box. You can select the time range which you would like to view by using the dropdown on the left and click the filter button.', 'groundhogg') . '</p>'
            )
        );

    }

    function superlinks_help_bar()
    {
        $screen = get_current_screen();

        $screen->add_help_tab(
            array(
                'id' => 'gh_overview',
                'title' => __('Overview'),
                'content' => '<p>' . __( "Superlinks are special links that allow you to apply/remove tags whenever clicked and then take the contact to a page of your choice. To use them, just copy the replacement code and paste in in email, button, or link.", 'groundhogg' ) . '</p>'
            )
        );
    }

}

new WPFN_Admin_Menu();