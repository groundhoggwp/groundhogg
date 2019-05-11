<?php
namespace Groundhogg;
use Groundhogg\Admin\Admin_Menu;
use Groundhogg\DB\DB;
use Groundhogg\DB\Manager;
use Groundhogg\DB\Meta_DB;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Extension
 *
 * Helper class for extensions with Groundhogg.
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */
abstract class Extension
{

    const STORE_URL = 'https://www.groundhogg.io';

    /**
     * Keep a going array of all the Extensions.
     *
     * @var Extension[]
     */
    public static $extensions = [];

    public function __construct()
    {
        add_action( 'groundhogg/init', [ $this, 'init' ] );

        // Add to main list
        self::$extensions[] = $this;
    }

    /**
     * Add any other components...
     *
     * @return void
     */
    public function init(){

        $this->includes();

        $this->init_components();

        add_action( 'groundhogg/db/manager/init', [ $this, 'add_dbs' ] );
        add_action( 'groundhogg/api/v3/pre_init', [ $this, 'add_apis' ] );
        add_action( 'groundhogg/admin/init',      [ $this, 'add_admin_pages' ] );

        add_filter( 'groundhogg/admin/settings/tabs', [ $this, 'register_settings_tabs' ] );
        add_filter( 'groundhogg/admin/settings/sections', [ $this, 'register_settings_sections' ] );
        add_filter( 'groundhogg/admin/settings/settings', [ $this, 'register_settings' ] );
    }

    abstract public function includes();

    abstract public function init_components();

    /**
     * Add settings to the settings page
     *
     * @param $settings array[]
     * @return array[]
     */
    public function register_settings( $settings ){ return $settings; }

    /**
     * Add settings sections to the settings page
     *
     * @param $sections array[]
     * @return array[]
     */
    public function register_settings_sections( $sections ){ return $sections; }

    /**
     * Add settings tabs to the settings page
     *
     * @param $tabs array[]
     * @return array[]
     */
    public function register_settings_tabs( $tabs ){ return $tabs; }


    /**
     * Register any proprietary DBS
     *
     * @param $db_manager Manager
     */
    abstract public function add_dbs( $db_manager );

    /**
     * Register any api endpoints.
     *
     * @param $api_manager
     * @return void
     */
    abstract public function add_apis( $api_manager );

    /**
     * Register any new admin pages.
     *
     * @param $admin_menu Admin_Menu
     * @return void
     */
    abstract public function add_admin_pages( $admin_menu );

    /**
     * Get the version #
     *
     * @return mixed
     */
    abstract public function get_version();

    /**
     * Get the ID number for the download in EDD Store
     *
     * @return int
     */
    abstract public function get_download_id();

    /**
     * @return string
     */
    abstract public function get_display_name();

    /**
     * @return string
     */
    abstract public function get_display_description();

    /**
     * @return string
     */
    abstract public function get_plugin_file();

    /**
     * Get this extension's license key
     *
     * @return string
     */
    public function get_license_key()
    {
        return get_array_var( get_option( 'gh_extensions', [] ), $this->get_download_id(), '' );
    }

    /**
     * Get the EDD updater.
     *
     * @return \GH_EDD_SL_Plugin_Updater
     */
    public function get_edd_updater()
    {
        if ( ! class_exists('\GH_EDD_SL_Plugin_Updater') ){
            require_once dirname(__FILE__) . '/lib/edd/GH_EDD_SL_Plugin_Updater.php';
        }

        return new \GH_EDD_SL_Plugin_Updater( self::STORE_URL, $this->get_plugin_file(), [
            'version' 	=> $this->get_version(),
            'license' 	=> $this->get_license_key(),
            'item_id'   => $this->get_download_id(),
            'url'       => home_url()
        ] );
    }

}