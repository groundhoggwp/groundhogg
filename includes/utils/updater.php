<?php
namespace Groundhogg;


/**
 * Updater
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 1.0.16
 */
abstract class Updater{

    /**
     * List of all version which have had update paths.
     *
     * @var string[]
     */
    protected $previous_versions = [];

    /**
     * WPGH_Upgrade constructor.
     */
    public function __construct()
    {
        add_action( 'init', [ $this, 'init' ] );
        add_action( 'init', [ $this, 'do_updates' ] );
    }

    /**
     * Init
     */
    public function init()
    {
        $this->previous_versions = Plugin::$instance->settings->get_option( $this->get_version_option_name(), [] );
    }

    /**
     * A unique name for the updater to avoid conflicts
     *
     * @return string
     */
    abstract protected function get_updater_name();

    /**
     * Get a list of updates which are available.
     *
     * @return string[]
     */
    abstract protected function get_available_updates();

    /**
     * Get the previous version which the plugin was updated to.
     *
     * @return string[]
     */
    protected function get_previous_versions()
    {
        return $this->previous_versions;
    }

    /**
     * Set the last updated to version in the DB
     *
     * @param $version
     */
    protected function remember_version_update( $version )
    {
        $this->previous_versions[] = $version;
        Plugin::$instance->settings->update_option( $this->get_version_option_name(), $this->previous_versions );
    }

    /**
     * Gets the DB option name to retrieve the previous version.
     *
     * @return string
     */
    protected function get_version_option_name()
    {
        return sanitize_key( sprintf( '%s_version_updates', $this->get_updater_name() ) );
    }

    /**
     * Check whether upgrades should happen or not.
     */
    public function do_updates()
    {

        $previous_updates  = $this->get_previous_versions();
        $available_updates = $this->get_available_updates();

        $missing_updates = array_diff( $available_updates, $previous_updates );

        if ( empty( $missing_updates ) ){
            return;
        }

        foreach ( $missing_updates as $update ){
            $this->update_to_version( $update );
        }

        do_action( "groundhogg/updater/{$this->get_updater_name()}/finished" );
    }

    /**
     * Takes the current version number and converts it to a function which can be clled to perform the upgrade requirements.
     *
     * @param $version string
     * @return bool|string
     */
    private function convert_version_to_function( $version )
    {

        $nums = explode( '.', $version );
        $func = sprintf( 'version_%s', implode( '_', $nums ) );

        if ( method_exists( $this, $func ) ){
            return $func;
        }

        return false;

    }

    private function update_to_version( $version )
    {
        /**
         * Check if the version we want to update to is greater than that of the db_version
         */
        $func = $this->convert_version_to_function( $version );

        if ( $func && method_exists( $this, $func ) ){

            call_user_func( array( $this, $func ) );

            $this->remember_version_update( $version );

            do_action( "groundhogg/updater/{$this->get_updater_name()}/{$func}" );
        }
    }
}