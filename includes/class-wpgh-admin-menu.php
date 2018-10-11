<?php
/**
 * Created by PhpStorm.
 * User: Adrian
 * Date: 2018-08-16
 * Time: 8:06 PM
 */

class WPGH_Admin_Menu
{
    /**
     * @var WPGH_Settings_Page
     */
    var $settings_page;

    /**
     * @var WPGH_Emails_Page
     */
    var $emails_page;

    /**
     * @var WPGH_Funnels_Page
     */
    var $funnels_page;

    /**
     * @var WPGH_Superlinks_Page
     */
    var $superlink_page;

    /**
     * @var WPGH_Tags_Page
     */
    var $tags_page;

    /**
     * @var WPGH_Contacts_Page
     */
    var $contacts_page;

    /**
     * @var WPGH_Broadcasts_Page
     */
    var $broadcasts_page;

    /**
     * @var WPGH_Events_Page
     */
    var $events_page;

    function __construct()
    {

        if (!class_exists('WPGH_Settings_page'))
            include dirname(__FILE__) . '/admin/settings/class-wpgh-settings-page.php';

        $this->settings_page = new WPGH_Settings_Page();

        if (!class_exists('WPGH_Broadcasts_Page'))
            include dirname(__FILE__) . '/admin/broadcasts/class-wpgh-broadcasts-page.php';

        $this->broadcasts_page = new WPGH_Broadcasts_Page();

        if (!class_exists('WPGH_Emails_Page'))
            include dirname(__FILE__) . '/admin/emails/class-wpgh-emails-page.php';

        $this->emails_page = new WPGH_Emails_Page();

        if (!class_exists('WPGH_Funnels_Page'))
            include dirname(__FILE__) . '/admin/funnels/class-wpgh-funnels-page.php';

        $this->funnels_page = new WPGH_Funnels_Page();

        if (!class_exists('WPGH_Superlinks_Page'))
            include dirname(__FILE__) . '/admin/superlinks/class-wpgh-superlinks-page.php';

        $this->superlink_page = new WPGH_Superlinks_Page();

        if (!class_exists('WPGH_Tags_Page'))
            include dirname(__FILE__) . '/admin/tags/class-wpgh-tags-page.php';

        $this->tags_page = new WPGH_Tags_Page();

        if (!class_exists('WPGH_Contacts_Page'))
            include dirname(__FILE__) . '/admin/contacts/class-wpgh-contacts-page.php';

        $this->contacts_page = new WPGH_Contacts_Page();

        if (!class_exists('WPGH_Events_Page'))
            include dirname(__FILE__) . '/admin/events/class-wpgh-events-page.php';

        $this->events_page = new WPGH_Events_Page();

        add_action('admin_menu', array($this, 'setup_menu_items'));

    }

    function setup_menu_items()
    {
        $page_title = 'Groundhogg';
        $menu_title = 'Groundhogg';
        $capability = 'manage_options';
        $slug = 'groundhogg';
        $callback = array($this->settings_page, 'wpgh_settings_content');
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
            'gh_manage_contacts',
            'gh_contacts',
            array($this->contacts_page, 'page')
        );

        add_action("load-" . $contacts_admin_add, array($this, 'contacts_help_bar'));

        $tags_admin_add = add_submenu_page(
            'groundhogg',
            'Tags',
            'Tags',
            'gh_manage_tags',
            'gh_tags',
            array($this->tags_page, 'page')
        );

        $superlinks_admin_add = add_submenu_page(
            'groundhogg',
            'Superlinks',
            'Superlinks',
            'gh_manage_superlinks',
            'gh_superlinks',
            array($this->superlink_page, 'page')
        );

        add_action("load-" . $superlinks_admin_add, array($this, 'superlinks_help_bar'));

        $broadcasts_admin_add = add_submenu_page(
            'groundhogg',
            'Broadcasts',
            'Broadcasts',
            'gh_manage_broadcasts',
            'gh_broadcasts',
            array($this->broadcasts_page, 'page')
        );

        $email_admin_add = add_submenu_page(
            'groundhogg',
            'Emails',
            'Emails',
            'gh_manage_emails',
            'gh_emails',
            array($this->emails_page, 'page')
        );

        add_action("load-" . $email_admin_add, array($this, 'emails_help_bar'));

        $funnel_admin_add = add_submenu_page(
            'groundhogg',
            'Funnels',
            'Funnels',
            'gh_manage_funnels',
            'gh_funnels',
            array($this->funnels_page, 'page')
        );

        add_action("load-" . $funnel_admin_add, array($this, 'funnels_help_bar'));

        add_submenu_page(
            'groundhogg',
            'Events',
            'Events',
            'gh_manage_events',
            'gh_events',
            array($this->events_page, 'page')
        );
        
        add_submenu_page(
            'groundhogg',
            'Settings',
            'Settings',
            'gh_manage_settings',
            'groundhogg',
            array($this->settings_page, 'wpgh_settings_content')
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
                    . '<p>' . __('Once a benchmark is complete all elements that are scheduled before that benchmark will stop immediately.', 'groundhogg') . '</p>'
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

    function contacts_help_bar()
    {
        $screen = get_current_screen();

        $screen->add_help_tab(
            array(
                'id' => 'gh_overview',
                'title' => __('Overview'),
                'content' => '<p>' . __( "This is where you can manage and view your contacts. Click the quick edit to quickly change contact details.", 'groundhogg' ) . '</p>'
            )
        );

        $screen->add_help_tab(
            array(
                'id' => 'gh_edit',
                'title' => __('Editing'),
                'content' => '<p>' . __( "While editing a contact you can modify any of their personal information. There are several points of interest...", 'groundhogg' ) . '</p>'
                . '<ul> '
                    . '<li>' . __( 'Manually unsubscribe a contact by checking the "mark as unsubscribed" button.', 'groundhogg' ) . '</li>'
                    . '<li>' . __( 'Make sure your in compliance by ensuring the terms of agreement and GDPR consent are both checked under the compliance section.', 'groundhogg' ) . '</li>'
                    . '<li>' . __( 'View the origin of the contact by looking at the lead source field.', 'groundhogg' ) . '</li>'
                    . '<li>' . __( 'Add or remove custom information about the contact by enabling the "Edit Meta" section. Each meta also includes a replacement code to include it in an email.', 'groundhogg' ) . '</li>'
                    . '<li>' . __( 'Re-run or cancel events for this contact by viewing the "Upcoming Events" or "Recent History" Section', 'groundhogg' ) . '</li>'
                    . '<li>' . __( 'Monitor their engagement by looking in the "Recent Email History" section.', 'groundhogg' ) . '</li>'
                . '</ul>'
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
                'content' => '<p>' . __( "Superlinks are special superlinks that allow you to apply/remove tags whenever clicked and then take the contact to a page of your choice. To use them, just copy the replacement code and paste in in email, button, or link.", 'groundhogg' ) . '</p>'
            )
        );
    }

}