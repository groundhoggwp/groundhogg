<?php
namespace Groundhogg;

/**
 * Installer
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 2.0
 */
abstract class Installer {

    /**
     * WPGH_Upgrade constructor.
     */
    public function __construct()
    {
        add_action( 'admin_init', [ $this, 'fail_safe_install' ] );

        register_activation_hook( $this->get_plugin_file(), [ $this, 'activation_hook' ] );
        register_deactivation_hook( $this->get_plugin_file(), [ $this, 'deactivation_hook' ] );

        add_action( 'wpmu_new_blog', [ $this, 'new_blog_created' ], 10, 6 );
        add_filter( 'wpmu_drop_tables', [ $this, 'wpmu_drop_tables' ], 10, 2 );
        add_action( 'activated_plugin', [ $this, 'plugin_activated' ] );
    }

    /**
     * Fail safe.
     *
     * @return mixed
     */
    public function fail_safe_install()
    {
        $installed = get_option( "groundhogg_{$this->get_installer_name()}_installed", false );

        if ( ! $installed ){
            $this->activation_hook();
        }

        return true;
    }

    abstract protected function activate();
    abstract protected function deactivate();

    /**
     * Wrap the activation process.
     */
    protected function activation_wrapper()
    {
        $this->pre_activate();
        $this->activate();
        $this->post_activate();
    }

    /**
     * Wrap the deactivation process.
     */
    protected function deactivation_wrapper()
    {
        $this->pre_deactivate();
        $this->deactivate();
        $this->post_deactivate();
    }

    /**
     * Take care of some basic stuff pre-activation
     */
    protected function pre_activate()
    {
        do_action( "groundhogg/activating", $this->get_installer_name() );
        do_action( "groundhogg/{$this->get_installer_name()}/activating" );
    }

    /**
     * Take care of basic stuff post activation
     */
    protected function post_activate()
    {
        do_action( "groundhogg/activated", $this->get_installer_name() );
        do_action( "groundhogg/{$this->get_installer_name()}/activated" );

        set_transient( "groundhogg_{$this->get_installer_name()}_activated", time(), MINUTE_IN_SECONDS );

        update_option( "groundhogg_{$this->get_installer_name()}_installed", time() );
    }

    /**
     * Take care of some basic stuff pre-deactivation
     */
    protected function pre_deactivate()
    {
        do_action( "groundhogg/deactivating", $this->get_installer_name() );
        do_action( "groundhogg/{$this->get_installer_name()}/deactivating" );
    }

    /**
     * Take care of basic stuff post deactivation
     */
    protected function post_deactivate()
    {
        do_action( "groundhogg/deactivated", $this->get_installer_name() );
        do_action( "groundhogg/{$this->get_installer_name()}/deactivated" );

        set_transient( "groundhogg_{$this->get_installer_name()}_deactivated", time(), MINUTE_IN_SECONDS );
        delete_option( "groundhogg_{$this->get_installer_name()}_installed" );
    }

    /**
     * Fires after the 'activated_plugin' hook.
     *
     * @param $plugin
     */
    public function plugin_activated( $plugin ){}

    /**
     * The path to the main plugin file
     *
     * @return string
     */
    abstract function get_plugin_file();

    /**
     * Get the plugin version
     *
     * @return string
     */
    abstract function get_plugin_version();

    /**
     * A unique name for the updater to avoid conflicts
     *
     * @return string
     */
    abstract protected function get_installer_name();

    /**
     * Install network wide.
     *
     * @param bool $network_wide
     */
    public function activation_hook( $network_wide=false )
    {
        global $wpdb;

        if ( is_multisite() && $network_wide ) {

            foreach ( $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs LIMIT 100" ) as $blog_id ) {
                switch_to_blog( $blog_id );
                $this->activation_wrapper();
                restore_current_blog();
            }

        } else {
            $this->activation_wrapper();
        }

        file_put_contents( dirname( $this->get_plugin_file() ) . '/activation-errors.txt', ob_get_contents() );
    }

    /**
     * Deactivate network wide
     *
     * @param bool $network_wide
     */
    public function deactivation_hook($network_wide=false )
    {
        global $wpdb;

        if ( is_multisite() && $network_wide ) {

            foreach ( $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs LIMIT 100" ) as $blog_id ) {
                switch_to_blog( $blog_id );
                $this->deactivation_wrapper();
                restore_current_blog();
            }

        } else {
            $this->deactivation_wrapper();
        }
    }

    /**
     * When a new Blog is created in multisite, see if WPGH is network activated, and run the installer
     *
     * @since  2.5
     * @param  int    $blog_id The Blog ID created
     * @param  int    $user_id The User ID set as the admin
     * @param  string $domain  The URL
     * @param  string $path    Site Path
     * @param  int    $site_id The Site ID
     * @param  array  $meta    Blog Meta
     * @return void
     */
    public function new_blog_created( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {

        if ( is_plugin_active_for_network( plugin_basename( $this->get_plugin_file() ) ) ) {

            switch_to_blog( $blog_id );
            $this->activation_wrapper();
            restore_current_blog();

        }

    }

    /**
     * Get the table names.
     *
     * @return string[]
     */
    protected function get_table_names()
    {
        return [];
    }

    /**
     * Drop our custom tables when a mu site is deleted
     *
     * @since  2.5
     * @param  array $tables  The tables to drop
     * @param  int   $blog_id The Blog ID being deleted
     * @return array          The tables to drop
     */
    public function wpmu_drop_tables( $tables, $blog_id ) {

        switch_to_blog( $blog_id );

        $tables = array_merge( $tables, $this->get_table_names() );

        restore_current_blog();

        return $tables;

    }

}