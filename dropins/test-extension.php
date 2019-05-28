<?php

namespace Groundhogg\Dropins;

use Groundhogg\Admin\Admin_Menu;
use Groundhogg\DB\Manager;
use Groundhogg\Extension;

class Test_Extension extends Extension {

    public function includes()
    {
        // TODO: Implement includes() method.
    }

    public function init_components()
    {
        // TODO: Implement init_components() method.
    }

    /**
     * Register any proprietary DBS
     *
     * @param $db_manager Manager
     */
    public function add_dbs($db_manager)
    {
        // TODO: Implement add_dbs() method.
    }

    /**
     * Register any api endpoints.
     *
     * @param $api_manager
     * @return void
     */
    public function add_apis($api_manager)
    {
        // TODO: Implement add_apis() method.
    }

    /**
     * Register any new admin pages.
     *
     * @param $admin_menu Admin_Menu
     * @return void
     */
    public function add_admin_pages($admin_menu)
    {
        // TODO: Implement add_admin_pages() method.
    }

    /**
     * Get the version #
     *
     * @return mixed
     */
    public function get_version()
    {
        return '1.2.3';
    }

    /**
     * Get the ID number for the download in EDD Store
     *
     * @return int
     */
    public function get_download_id()
    {
        return 1234;
    }

    /**
     * @return string
     */
    public function get_display_name()
    {
        return __( 'Test Title' );
    }

    /**
     * @return string
     */
    public function get_display_description()
    {
        return __( 'This is a description.' );
    }

    /**
     * @return string
     */
    public function get_plugin_file()
    {
        return __FILE__;
    }
}