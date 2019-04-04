<?php
/**
 * Admin Menu
 *
 * This will init the admin menu, you can also access public menu item methods via this class.
 * If you are adding your own menu Item do not look here, just add a submenu item to the slug 'groundhogg'
 * and call it a day.
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

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
	 * @var WPGH_SMS_Page
	 */
    var $sms_page;

    /**
     * @var WPGH_Events_Page
     */
    var $events_page;

    /**
     * @var WPGH_Welcome_Page
     */
    var $welcome_page;

    /**
     * @var WPGH_Dashboard_Widgets
     */
    var $dashboard;

    /**
     * @var WPGH_Guided_Setup
     */
    var $guided_setup;

    /**
     * @var WPGH_Admin_Bulk_Job
     */
    var $bulk_jobs;

    /**
     * @var WPGH_Admin_Tools
     */
    var $tools;

    /**
     * Register the pages...
     *
     * WPGH_Admin_Menu constructor.
     */
    function __construct()
    {
        $this->includes();

        $this->welcome_page     = new WPGH_Welcome_Page();
        $this->dashboard        = new WPGH_Dashboard_Widgets();
        $this->contacts_page    = new WPGH_Contacts_Page();
        $this->tags_page        = new WPGH_Tags_Page();
        $this->superlink_page   = new WPGH_Superlinks_Page();
        $this->broadcasts_page  = new WPGH_Broadcasts_Page();
        $this->emails_page      = new WPGH_Emails_Page();
        $this->sms_page         = new WPGH_SMS_Page();
        $this->funnels_page     = new WPGH_Funnels_Page();
        $this->events_page      = new WPGH_Events_Page();
        $this->guided_setup     = new WPGH_Guided_Setup();
        $this->bulk_jobs        = new WPGH_Admin_Bulk_Job();
        $this->tools            = new WPGH_Admin_Tools();


        /**
         * Add multisite compat
         */
        if ( wpgh_should_if_multisite() ){
            $this->settings_page = new WPGH_Settings_Page();
        }

    }

    /**
     * Include the admin pages...
     */
    public function includes()
    {
        require_once dirname( __FILE__ ). '/admin/broadcasts/class-wpgh-broadcasts-page.php';
        require_once dirname( __FILE__ ). '/admin/dashboard/class-wpgh-dashboard-widgets.php';
        require_once dirname( __FILE__ ). '/admin/contacts/class-wpgh-contacts-page.php';
        require_once dirname( __FILE__ ). '/admin/emails/class-wpgh-emails-page.php';
        require_once dirname( __FILE__ ). '/admin/sms/class-wpgh-sms-page.php';
        require_once dirname( __FILE__ ). '/admin/events/class-wpgh-events-page.php';
        require_once dirname( __FILE__ ). '/admin/funnels/class-wpgh-funnels-page.php';
        require_once dirname( __FILE__ ). '/admin/settings/class-wpgh-settings-page.php';
        require_once dirname( __FILE__ ). '/admin/superlinks/class-wpgh-superlinks-page.php';
        require_once dirname( __FILE__ ). '/admin/tags/class-wpgh-tags-page.php';
        require_once dirname( __FILE__ ). '/admin/welcome/class-wpgh-welcome-page.php';
        require_once dirname( __FILE__ ). '/admin/guided-setup/class-wpgh-guided-setup.php';
        require_once dirname( __FILE__ ). '/admin/bulk-jobs/class-wpgh-admin-bulk-job.php';
//        require_once dirname( __FILE__ ). '/admin/import/class-wpgh-admin-import.php';
//        require_once dirname( __FILE__ ). '/admin/export/class-wpgh-admin-export.php';
        require_once dirname( __FILE__ ). '/admin/tools/class-wpgh-admin-tools.php';
    }

    public function current_page()
    {
        if ( isset( $_GET['page'] ) ){
            return $_GET['page'];
        }

        return false;
    }

    public function current_action()
    {
        if ( isset( $_GET['action'] ) ){
            return $_GET['action'];
        }

        return false;
    }

}