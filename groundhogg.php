<?php
/*
 * Plugin Name: Groundhogg
 * Plugin URI:  https://www.groundhogg.io/?utm_source=wp-plugins&utm_campaign=plugin-uri&utm_medium=wp-dash
 * Description: CRM and marketing automation for WordPress
 * Version: 3.7.4.1
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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GROUNDHOGG_VERSION', '3.7.4.1' );
define( 'GROUNDHOGG_PREVIOUS_STABLE_VERSION', '3.7.4' );

define( 'GROUNDHOGG__FILE__', __FILE__ );
define( 'GROUNDHOGG_PLUGIN_BASE', plugin_basename( GROUNDHOGG__FILE__ ) );
define( 'GROUNDHOGG_PATH', plugin_dir_path( GROUNDHOGG__FILE__ ) );
define( 'GROUNDHOGG_URL', plugins_url( '/', GROUNDHOGG__FILE__ ) );

define( 'GROUNDHOGG_ASSETS_PATH', GROUNDHOGG_PATH . 'assets/' );
define( 'GROUNDHOGG_ASSETS_URL', GROUNDHOGG_URL . 'assets/' );

add_action( 'plugins_loaded', 'groundhogg_load_plugin_textdomain' );

define( 'GROUNDHOGG_TEXT_DOMAIN', 'groundhogg' );
define( 'GROUNDHOGG_MINIMUM_PHP_VERSION', '7.1' );
define( 'GROUNDHOGG_MINIMUM_WORDPRESS_VERSION', '5.9' );

if ( ! version_compare( PHP_VERSION, GROUNDHOGG_MINIMUM_PHP_VERSION, '>=' ) ) {
	add_action( 'admin_notices', 'groundhogg_fail_php_version' );
} elseif ( ! version_compare( get_bloginfo( 'version' ), GROUNDHOGG_MINIMUM_WORDPRESS_VERSION, '>=' ) ) {
	add_action( 'admin_notices', 'groundhogg_fail_wp_version' );
} else {
	require __DIR__ . '/includes/plugin.php';
}

/**
 * Groundhogg loaded.
 *
 * Fires when Groundhogg was fully loaded and instantiated.
 *
 * @since 1.0.0
 */
do_action( 'groundhogg/loaded' );

/**
 * Load Groundhogg textdomain.
 *
 * Load gettext translate for Groundhogg text domain.
 *
 * @return void
 * @since 1.0.0
 *
 */
function groundhogg_load_plugin_textdomain() {
	load_plugin_textdomain( 'groundhogg', false, basename( __DIR__ ) . '/languages' );
}

/**
 * Groundhogg admin notice for minimum PHP version.
 *
 * Warning when the site doesn't have the minimum required PHP version.
 *
 * @return void
 * @since 2.0
 *
 */
function groundhogg_fail_php_version() {
	/* translators: %s: PHP version */
	$message      = sprintf( esc_html__( 'Groundhogg requires PHP version %s+, plugin is currently NOT RUNNING.', 'groundhogg' ), GROUNDHOGG_MINIMUM_PHP_VERSION );
	$html_message = sprintf( '<div class="error">%s</div>', wpautop( $message ) );
	echo wp_kses_post( $html_message );
}

/**
 * Groundhogg admin notice for minimum WordPress version.
 *
 * Warning when the site doesn't have the minimum required WordPress version.
 *
 * @return void
 * @since 2.0
 *
 */
function groundhogg_fail_wp_version() {
	/* translators: %s: WordPress version */
	$message      = sprintf( esc_html__( 'Groundhogg requires WordPress version %s+. Because you are using an earlier version, the plugin is currently NOT RUNNING.', 'groundhogg' ), GROUNDHOGG_MINIMUM_WORDPRESS_VERSION );
	$html_message = sprintf( '<div class="error">%s</div>', wpautop( $message ) );
	echo wp_kses_post( $html_message );
}
