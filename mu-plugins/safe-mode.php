<?php
/*
 * Plugin Name: Groundhogg - Safe Mode
 * Plugin URI:  https://www.groundhogg.io/?utm_source=wp-plugins&utm_campaign=plugin-uri&utm_medium=wp-dash
 * Description: Safe mode functionality for Groundhogg debugging.
 * Version: 1.0
 * Author: Groundhogg Inc.
 * Author URI: https://www.groundhogg.io/?utm_source=wp-plugins&utm_campaign=author-uri&utm_medium=wp-dash
 * Text Domain: groundhogg
 * Domain Path: /languages
 *
 * Groundhogg is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * Groundhogg is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

use function Groundhogg\admin_page_url;
use function Groundhogg\html;
use function Groundhogg\nonce_url_no_amp;
use function Groundhogg\white_labeled_name;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

define( 'GROUNDHOGG_SAFE_MODE_INSTALLED', true );
define( 'GROUNDHOGG_SAFE_MODE_COOKIE', 'gh-safe-mode' );

/**
 * Whether the current user can manage safe mode
 *
 * @return bool
 */
function groundhogg_user_can_manage_safe_mode() {
	return _wp_get_current_user()->has_cap( 'activate_plugins' );
}

/**
 * Whether safe mode is enabled for the current user
 *
 * @return bool
 */
function groundhogg_current_user_has_safe_mode_enabled() {
	return groundhogg_validate_safe_mode_cookie() && groundhogg_is_safe_mode_enabled();
}

/**
 * If safe mode is enabled
 *
 * @return bool
 */
function groundhogg_is_safe_mode_enabled() {
	return get_option( 'gh_safe_mode_enabled' ) == 1;
}

/**
 * Standardize the hasher
 *
 * @return mixed|PasswordHash
 */
function groundhogg_pass_hasher() {
	static $hasher;
	if ( $hasher ) {
		return $hasher;
	}

	if ( ! class_exists( '\PasswordHash' ) ) {
		require_once ABSPATH . WPINC . '/class-phpass.php';
	}

	$hasher = new \PasswordHash( 8, true );

	return $hasher;
}

/**
 * Restore WP from safe mode
 *
 * @return bool
 */
function groundhogg_disable_safe_mode() {

	if ( ! groundhogg_user_can_manage_safe_mode() || ! groundhogg_is_safe_mode_enabled() ) {
		return false;
	}

	// Restore old plugins
	$active_plugins = (array) get_option( 'gh_safe_mode_restore_plugins', array() );
	update_option( 'active_plugins', $active_plugins );

	// Delete options
	delete_option( 'gh_safe_mode_enabled' );
	delete_option( 'gh_safe_mode_restore_plugins' );
	// last used
	update_option( 'gh_safe_mode_last_used', \Groundhogg\Ymd_His( 'now', true ) );

	// clear safe mode cookie
	\Groundhogg\delete_cookie( GROUNDHOGG_SAFE_MODE_COOKIE );

	// delete user meta
	delete_metadata( 'user', 0, 'gh_safe_mode_enabled', 1, true );

	do_action( 'groundhogg/safe_mode_disabled' );

	return true;
}

/**
 * Check to see if the safe mode cookie contains the required details
 *
 * @return bool
 */
function groundhogg_validate_safe_mode_cookie() {

	$cookie_value = $_COOKIE[ GROUNDHOGG_SAFE_MODE_COOKIE ] ?? '';

	if ( empty( $cookie_value ) ) {
		return false;
	}

	$cookie_parts = explode( '|', $cookie_value );

	if ( count( $cookie_parts ) !== 2 ) {
		return false;
	}

	$user_login = $cookie_parts[0];
	$key        = $cookie_parts[1];

	if ( empty( $user_login ) || empty( $key ) ) {
		return false;
	}

	$userdata = WP_User::get_data_by( 'login', $user_login );

	if ( ! $userdata ) {
		return false;
	}

	$user = new WP_User();
	$user->init( $userdata );

    if ( ! $user->has_cap( 'activate_plugins' ) ) {
        return false;
    }

	$stored_hash = get_user_meta( $user->ID, 'gh_safe_mode_enabled', true );

	return groundhogg_pass_hasher()->CheckPassword( $key, $stored_hash );
}

/**
 * Set the safe mode key for the current user
 *
 * @return void
 */
function groundhogg_set_safe_mode_cookie() {
	$safe_mode_key = wp_generate_password( 20, false );
	$hashed_key    = groundhogg_pass_hasher()->HashPassword( $safe_mode_key );
	update_user_meta( get_current_user_id(), 'gh_safe_mode_enabled', $hashed_key );
	\Groundhogg\set_cookie( GROUNDHOGG_SAFE_MODE_COOKIE, wp_get_current_user()->user_login . '|' . $safe_mode_key, YEAR_IN_SECONDS );
}

/**
 * Enable safe mode which disables all active plugins
 *
 * @return bool
 */
function groundhogg_enable_safe_mode() {

	if ( ! groundhogg_user_can_manage_safe_mode() ) {
		return false;
	}

	// set the safe mode cookie for the current user
	groundhogg_set_safe_mode_cookie();

	// if safe mode is enabled
	if ( groundhogg_is_safe_mode_enabled() ) {
		return true;
	}

	$active_plugins = (array) get_option( 'active_plugins', array() );
	$keep_plugins   = array_filter( $active_plugins, function ( $plugin ) {
		return str_contains( $plugin, 'groundhogg' );
	} );

	// Store old plugins
	update_option( 'gh_safe_mode_restore_plugins', $active_plugins );

	// Save new plugins
	update_option( 'active_plugins', $keep_plugins );

	// Enabled safe mode
	update_option( 'gh_safe_mode_enabled', 1 );
	// last used
	update_option( 'gh_safe_mode_last_used', \Groundhogg\Ymd_His( 'now', true ) );

	do_action( 'groundhogg/safe_mode_enabled' );

	// Do a test to see if safe mode could be enabled
	$response = wp_remote_get( home_url() );

	// Some kind of 500 level error
	if ( wp_remote_retrieve_response_code( $response ) >= 500 ) {
		groundhogg_disable_safe_mode();

		return false;
	}

	return true;
}

/**
 * Groundhogg admin notice for minimum WordPress version.
 *
 * Warning when the site doesn't have the minimum required WordPress version.
 *
 * @since 2.0
 *
 * @return void
 */
function groundhogg_safe_mode_enabled_notice() {

	if ( ! groundhogg_current_user_has_safe_mode_enabled() || ! groundhogg_user_can_manage_safe_mode() ) {
		return;
	}

	$disable_safe_mode_url = nonce_url_no_amp( admin_page_url( 'gh_tools', [ 'action' => 'disable_safe_mode' ] ), 'disable_safe_mode' );

	?>
    <div class="notice notice-warning">
        <p>
	        <?php printf( __( '%s safe mode is <b>ON</b>. %s', 'groundhogg' ), white_labeled_name(), html()->e( 'a', [ 'href' => $disable_safe_mode_url ], __( 'Disable Safe Mode', 'groundhogg' ) ) ) ?>
        </p>
    </div>
	<?php
}

//add_action( 'admin_notices', 'groundhogg_safe_mode_enabled_notice' );

/**
 * Filter the active plugins option to show safe plugins to anyone that does not have safe mode enabled
 *
 * @param array $plugins
 *
 * @return array
 */
function groundhogg_safe_mode_filter_plugins_option( $plugins ) {

	// safe mode is not on, use regular plugins list
	if ( ! groundhogg_is_safe_mode_enabled() ) {
		return $plugins;
	}

	//  safe mode is on, but the user is authenticated
	if ( groundhogg_current_user_has_safe_mode_enabled() ) {
		return $plugins;
	}

	// otherwise use original plugins list kept in the restore plugins option
	return get_option( 'gh_safe_mode_restore_plugins' );
}

add_filter( 'option_active_plugins', 'groundhogg_safe_mode_filter_plugins_option' );

function groundhogg_safe_mode_admin_bar_menu_styles() {
    ?>
<style>
    #wp-admin-bar-groundhogg-safe-mode {
        background-color: orange !important;
        a {
            color: #000
        }
    }
</style>
<?php
}

/**
 * Show warning in admin bar if safe mode is on
 *
 * @param $admin_bar
 *
 * @return void
 */
function groundhogg_safe_mode_admin_bar_warning( $admin_bar ) {

	if ( groundhogg_current_user_has_safe_mode_enabled() ){

		$admin_bar->add_node( [
			'id'     => 'groundhogg-safe-mode',
			'title'  => 'Safe Mode Enabled',
			'parent' => 'top-secondary',
			'meta'   => [
				'class' => 'groundhogg-admin-safe-mode',
				'title' => 'Safe Mode'
			],
		] );

		$admin_bar->add_node( [
			'id'     => 'groundhogg-safe-mode-disable',
			'title'  => 'Disable Safe Mode',
			'parent' => 'groundhogg-safe-mode',
			'meta'   => [
				'class' => 'groundhogg-admin-safe-mode',
				'title' => 'Disable Safe Mode'
			],
			'href' => nonce_url_no_amp( admin_page_url( 'gh_tools', [ 'action' => 'disable_safe_mode' ] ), 'disable_safe_mode' )
		] );
	}

    add_action( is_admin() ? 'admin_footer' : 'wp_footer', 'groundhogg_safe_mode_admin_bar_menu_styles' );

}

add_action( 'admin_bar_menu', 'groundhogg_safe_mode_admin_bar_warning', 999 );
