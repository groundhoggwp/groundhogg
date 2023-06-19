<?php

namespace Groundhogg;

/**
 * Updater
 *
 * @since       File available since Release 1.0.16
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
 */
abstract class Updater {

	/**
	 * @var bool if updates were done during the request
	 */
	protected $did_updates = false;

	/**
	 * WPGH_Upgrade constructor.
	 */
	public function __construct() {

		// Show updates are required
		add_action( 'admin_init', [ $this, 'listen_for_updates' ], 9 );
		add_action( 'groundhogg/notices/before', [ $this, 'updates_notice' ] );
		add_action( "groundhogg/updater/{$this->get_updater_name()}/force_updates", [ $this, 'force_updates' ] );

		// Do automatic updates
		add_action( 'init', [ $this, 'do_automatic_updates' ], 8 );

		// Show updates path in tools area
		add_action( 'groundhogg/admin/tools/updates', [ $this, 'show_manual_updates' ] );
		add_filter( 'groundhogg/admin/tools/updaters', [ $this, 'show_updates_in_dropdown' ] );
		add_action( 'groundhogg/admin/tools/network_updates', [ $this, 'show_network_updates' ] );

		// Do the manual update
		add_action( 'admin_init', [ $this, 'do_manual_updates' ], 99 );

		// Save previous updates when plugin installed.
		add_action( 'activated_plugin', [ $this, 'save_previous_updates_when_installed' ], 99 );
		add_action( 'groundhogg/reset', [ $this, 'save_previous_updates_when_installed' ], 99 );
	}

	/**
	 * Get the previous version which the plugin was updated to.
	 *
	 * @return string[]
	 */
	public function get_previous_versions() {
		return Plugin::$instance->settings->get_option( $this->get_version_option_name(), [] );
	}

	/**
	 * Gets the DB option name to retrieve the previous version.
	 *
	 * @return string
	 */
	protected function get_version_option_name() {
		return sanitize_key( sprintf( 'gh_%s_version_updates', $this->get_updater_name() ) );
	}

	/**
	 * A unique name for the updater to avoid conflicts
	 *
	 * @return string
	 */
	abstract protected function get_updater_name();

	/**
	 * @param $plugins
	 *
	 * @return mixed
	 */
	public function show_updates_in_dropdown( $plugins ) {
		$plugins[ $this->get_updater_name() ] = $this->get_display_name();

		return $plugins;
	}

	/**
	 * Show the manual updates in the tools area
	 */
	public function show_manual_updates( $updater ) {

		if ( $updater !== $this->get_updater_name() ) {
			return;
		}

		?>
		<h3><?php echo apply_filters( 'groundhogg/updater/display_name', $this->get_display_name() ); ?></h3>
		<p><?php _e( 'Click on a version to run the update process for that version.', 'groundhogg' ); ?></p>
		<?php

		$updates = array_merge( $this->_get_available_updates(), $this->get_optional_updates() );

		usort( $updates, 'version_compare' );

		$_this = $this;

		html()->list_table( [
			'id' => 'updates-list'
		], [
			__( 'Completed', 'groundhogg' ),
			__( 'Version', 'groundhogg' ),
			__( 'Description', 'groundhogg' ),
		],
			array_map_with_keys( array_reverse( $updates ), function ( $update ) use ( $_this ) {
				return [
					$this->did_update( $update ) ? "<span style=\"color: green\">&#x2705;</span>" : '-',
					html()->e( 'a', [
						'href' => add_query_arg( [
							'updater'       => $this->get_updater_name(),
							'manual_update' => $update,
							'confirm'       => 'yes',
						], $_SERVER['REQUEST_URI'] )
					], $update ),
					esc_html( $_this->get_update_description( $update ) )
				];
			} )
		);
	}

	/**
	 * Show all the network updates
	 */
	public function show_network_updates() {
		$action_url = Plugin::instance()->bulk_jobs->update_subsites->get_start_url( [ 'updater' => $this->get_updater_name() ] );

		?>
		<h3><?php echo $this->get_display_name(); ?></h3>
		<p><?php

		echo html()->e( 'a', [
			'class' => 'button',
			'href'  => $action_url
		], __( 'Run Network Upgrade', 'groundhogg' ) )

		?></p><?php
	}

	/**
	 * Get the display name of the updater for the tools page
	 *
	 * @return string
	 */
	public function get_display_name() {
		return key_to_words( $this->get_updater_name() );
	}

	/**
	 * Handler for new update callback format
	 *
	 * @return array
	 */
	public function _get_available_updates() {

		$versions = [];

		foreach ( $this->get_available_updates() as $i => $version ) {
			if ( is_array( $version ) ) {
				$versions[] = $i;
			} else {
				$versions[] = $version;
			}
		}

		return $versions;
	}

	/**
	 * Get a list of updates which are available.
	 *
	 * @return string[]
	 */
	abstract protected function get_available_updates();

	/**
	 * Get a list of updates that do not update automatically, but will show on the updates page
	 *
	 * @return string[]
	 */
	protected function get_optional_updates() {
		return [];
	}

	/**
	 * List of updates which will run automatically
	 *
	 * @return string[]
	 */
	protected function _get_automatic_updates() {

		$versions = array_keys( array_filter( $this->get_available_updates(), function ( $v ) {
			return is_array( $v ) && get_array_var( $v, 'automatic' ) === true;
		} ) );

		return array_merge( $versions, $this->get_automatic_updates() );
	}

	/**
	 * List of updates which will run automatically
	 *
	 * @return string[]
	 */
	protected function get_automatic_updates() {
		return [];
	}

	/**
	 * Get a description of a certain update.
	 *
	 * @param $update
	 *
	 * @return string
	 */
	private function get_update_description( $update ) {

		// Handler for new format
		if ( key_exists( $update, $this->get_available_updates() ) ) {
			return get_array_var( get_array_var( $this->get_available_updates(), $update ), 'description' );
		}

		return get_array_var( $this->get_update_descriptions(), $update );
	}

	/**
	 * Associative array of versions to descriptions
	 *
	 * @return string[]
	 */
	protected function get_update_descriptions() {
		return [];
	}

	/**
	 * Manually perform a selected update routine.
	 */
	public function do_manual_updates() {

		if ( get_request_var( 'updater' ) !== $this->get_updater_name() || ! get_request_var( 'manual_update' ) || ! wp_verify_nonce( get_request_var( 'manual_update_nonce' ), 'gh_manual_update' ) || ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		$update = get_url_var( 'manual_update' );

		$updates = array_merge( $this->_get_available_updates(), $this->get_optional_updates() );

		if ( ! in_array( $update, $updates ) ) {
			return;
		}

		if ( $this->update_to_version( $update ) ) {
			Plugin::$instance->notices->add( 'updated', sprintf( __( 'Update to version %s successful!', 'groundhogg' ), $update ), 'success', 'manage_options' );
		} else {
			Plugin::$instance->notices->add( new \WP_Error( 'update_failed', __( 'Update failed.', 'groundhogg' ) ) );
		}

		wp_safe_redirect( admin_page_url( 'gh_tools', [ 'tab' => 'system' ] ) );
		die();
	}

	/**
	 * Given a version number call the related function
	 *
	 * @param $version
	 *
	 * @return bool
	 */
	private function update_to_version( $version ) {

		$func = $this->convert_version_to_function( $version );

		// Handler for new format
		if ( array_key_exists( $version, $this->get_available_updates() ) ){

			$update = get_array_var( $this->get_available_updates(), $version );
			$callback = get_array_var( $update, 'callback' );

			if ( ! is_callable( $callback ) ){
				return false;
			}

			call_user_func( $callback );

			$this->remember_version_update( $version );

			do_action( "groundhogg/updater/{$this->get_updater_name()}/{$func}" );

			return true;
		}

		if ( $func && method_exists( $this, $func ) ) {

			call_user_func( array( $this, $func ) );

			$this->remember_version_update( $version );

			do_action( "groundhogg/updater/{$this->get_updater_name()}/{$func}" );

			return true;
		}

		return false;
	}

	/**
	 * Takes the current version number and converts it to a function which can be clled to perform the upgrade requirements.
	 *
	 * @param $version string
	 *
	 * @return bool|string
	 */
	private function convert_version_to_function( $version ) {

		$nums = explode( '.', $version );
		$func = sprintf( 'version_%s', implode( '_', $nums ) );

		if ( method_exists( $this, $func ) ) {
			return $func;
		}

		return false;
	}

	/**
	 * Set the last updated to version in the DB
	 *
	 * @param $version
	 *
	 * @return bool
	 */
	protected function remember_version_update( $version ) {
		$versions = $this->get_previous_versions();

		$date_of_updates = get_option( $this->get_version_option_name() . '_dates', [] );

		if ( ! in_array( $version, $versions ) ) {
			$versions[] = $version;
		}

		$date_of_updates[ $version ] = time();

		// Save the date updated for this version
		update_option( $this->get_version_option_name() . '_dates', $date_of_updates );

		return update_option( $this->get_version_option_name(), $versions );
	}

	/**
	 * Remove a version from the previous versions so that the updater will perform that version update
	 *
	 * @param $version
	 *
	 * @return bool
	 */
	public function forget_version_update( $version ) {
		$versions = $this->get_previous_versions();

		if ( ! in_array( $version, $versions ) ) {
			return false;
		}

		unset( $versions[ array_search( $version, $versions ) ] );

		return update_option( $this->get_version_option_name(), $versions );
	}

	/**
	 * When the plugin is installed save the initial versions.
	 * Do not overwrite older versions.
	 *
	 * @return bool
	 */
	public function save_previous_updates_when_installed() {

		$updates = $this->get_previous_versions();

		if ( ! empty( $updates ) ) {
			return false;
		}

		return update_option( $this->get_version_option_name(), $this->get_available_updates() );
	}

	/**
	 * If there are missing updates, show a notice to run the upgrade path.
	 *
	 * @return void
	 */
	public function updates_notice() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$previous_updates = $this->get_previous_versions();

		// No previous updates, if this is the case something has gone wrong...
		if ( empty( $previous_updates ) || $this->did_updates ) {
			return;
		}

		$available_updates = $this->_get_available_updates();
		$missing_updates   = array_diff( $available_updates, $previous_updates );

		if ( empty( $missing_updates ) ) {
			return;
		}

		if ( is_multisite() && is_main_site() && is_groundhogg_network_active() ) {
			$action_url = Plugin::instance()->bulk_jobs->update_subsites->get_start_url( [ 'updater' => $this->get_updater_name() ] );
		} else {
			$action     = 'gh_' . $this->get_updater_name() . '_do_updates';
			$action_url = action_url( $action );
		}

		$update_button = html()->e( 'a', [
			'href'  => $action_url,
			'class' => 'button button-secondary'
		], __( 'Update Now!', 'groundhogg' ) );

		$update_descriptions = "";

		foreach ( $missing_updates as $missing_update ) {
			if ( $this->get_update_description( $missing_update ) ) {
				$update_descriptions .= sprintf( '<li style="margin-left: 10px"><b>%1$s</b> - %2$s</li>', $missing_update, $this->get_update_description( $missing_update ) );
			} else {
				$update_descriptions .= sprintf( '<li style="margin-left: 10px"><b>%1$s</b></li>', $missing_update );
			}
		}

		if ( ! empty( $update_descriptions ) ) {
			$update_descriptions = sprintf( __( "Required Upgrades:<span style='font-weight: normal'><ul>%s</ul></span>", 'groundhogg' ), $update_descriptions );
		}

		$notice = sprintf( __( "%s requires an update. Consider backing up your site before updating. </p>%s<p>%s", 'groundhogg' ), white_labeled_name(), $update_descriptions, $update_button );
		notices()->add( 'updates_required', $notice, 'info', 'manage_options', true );
	}

	/**
	 * Listen for the updates url param to tell us the updates button has been clicked
	 */
	public function listen_for_updates() {

		$action = 'gh_' . $this->get_updater_name() . '_do_updates';

		if ( ! current_user_can( 'manage_options' ) || ! get_url_var( 'action' ) === $action || ! wp_verify_nonce( get_url_var( '_wpnonce' ), $action ) ) {
			return;
		}

		$this->unlock_updates();

		if ( $this->do_updates() ) {
			notices()->add( 'updated', sprintf( __( "%s updated successfully!", 'groundhogg' ), white_labeled_name() ), 'success', 'manage_options', true );
		}

		wp_safe_redirect( wp_get_referer() );
		die();
	}

	/**
	 * Remove the update lock before running the upgrade path...
	 *
	 * @return bool
	 */
	public function force_updates() {

		// Remove the update lock...
		$this->unlock_updates();

		return $this->do_updates();
	}

	/**
	 * Check whether upgrades should happen or not.
	 */
	public function do_updates() {

		// Check if an update lock is present.
		if ( $this->are_updates_locked() ) {
			return false;
		}

		// Set lock so second update process cannot be run before this one is complete.
		$this->lock_updates();

		$previous_updates = $this->get_previous_versions();

		// No previous updates, if this is the case something has gone wrong...
		if ( empty( $previous_updates ) ) {
			return false;
		}

		$available_updates = array_merge( $this->_get_available_updates(), $this->_get_automatic_updates() );
		$missing_updates   = array_diff( $available_updates, $previous_updates );

		if ( empty( $missing_updates ) ) {
			return false;
		}

		foreach ( $missing_updates as $update ) {
			$this->update_to_version( $update );
		}

		$this->did_updates = true;

		do_action( "groundhogg/updater/{$this->get_updater_name()}/finished" );

		return true;
	}

	/**
	 * Do any automatic updates required by GH
	 *
	 * @return bool
	 */
	public function do_automatic_updates() {

		$previous_updates = $this->get_previous_versions();

		// No previous updates, if this is the case something has gone wrong...
		if ( empty( $previous_updates ) ) {
			return false;
		}

		$available_updates = $this->_get_automatic_updates();
		$missing_updates   = array_diff( $available_updates, $previous_updates );

		if ( empty( $missing_updates ) ) {
			return false;
		}

		// Check if an update lock is present.
		if ( $this->are_updates_locked() ) {
			return false;
		}

		// Set lock so second update process cannot be run before this one is complete.
		$this->lock_updates();

		foreach ( $missing_updates as $update ) {
			$this->update_to_version( $update );
		}

		$this->did_updates = true;

		do_action( "groundhogg/updater/{$this->get_updater_name()}/finished" );

		return true;
	}

	/**
	 * Whether a certain update was performed or not.
	 *
	 * @param $version
	 *
	 * @return bool
	 */
	public function did_update( $version ) {
		return in_array( $version, $this->get_previous_versions() );
	}

	/**
	 * Get the plugin file for this extension
	 *
	 * @return string
	 */
	protected function get_plugin_file() {
		return GROUNDHOGG__FILE__;
	}

	protected function get_update_lock_name() {
		return 'gh_' . $this->get_updater_name() . '_doing_updates';
	}

	protected function lock_updates() {
		return set_transient( $this->get_update_lock_name(), time(), MINUTE_IN_SECONDS );
	}

	protected function unlock_updates() {
		return delete_transient( $this->get_update_lock_name() );
	}

	protected function are_updates_locked() {
		return time() - get_transient( $this->get_update_lock_name() ) < MINUTE_IN_SECONDS;
	}
}
