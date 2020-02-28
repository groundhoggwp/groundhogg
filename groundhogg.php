<?php
/*
 * Plugin Name: Groundhogg
 * Plugin URI:  https://www.groundhogg.io/?utm_source=wp-plugins&utm_campaign=plugin-uri&utm_medium=wp-dash
 * Description: CRM and marketing automation for WordPress
 * Version: 2.1.13.6
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

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'GROUNDHOGG_VERSION', '2.1.13.6' );
define( 'GROUNDHOGG_PREVIOUS_STABLE_VERSION', '2.1.13.5' );

define( 'GROUNDHOGG__FILE__', __FILE__ );
define( 'GROUNDHOGG_PLUGIN_BASE', plugin_basename( GROUNDHOGG__FILE__ ) );
define( 'GROUNDHOGG_PATH', plugin_dir_path( GROUNDHOGG__FILE__ ) );

define( 'GROUNDHOGG_URL', plugins_url( '/', GROUNDHOGG__FILE__ ) );

define( 'GROUNDHOGG_ASSETS_PATH', GROUNDHOGG_PATH . 'assets/' );
define( 'GROUNDHOGG_ASSETS_URL', GROUNDHOGG_URL . 'assets/' );

add_action( 'plugins_loaded', 'groundhogg_load_plugin_textdomain' );

define( 'GROUNDHOGG_TEXT_DOMAIN', 'groundhogg' );

if ( ! version_compare( PHP_VERSION, '5.6', '>=' ) ) {
    add_action( 'admin_notices', 'groundhogg_fail_php_version' );
} elseif ( ! version_compare( get_bloginfo( 'version' ), '4.9', '>=' ) ) {
    add_action( 'admin_notices', 'groundhogg_fail_wp_version' );
} else {
    require GROUNDHOGG_PATH . 'includes/plugin.php';
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
 * @since 1.0.0
 *
 * @return void
 */
function groundhogg_load_plugin_textdomain() {
    load_plugin_textdomain( 'groundhogg', false, basename( dirname( __FILE__ ) ) . '/languages' );
}

/**
 * Groundhogg admin notice for minimum PHP version.
 *
 * Warning when the site doesn't have the minimum required PHP version.
 *
 * @since 2.0
 *
 * @return void
 */
function groundhogg_fail_php_version() {
    /* translators: %s: PHP version */
    $message = sprintf( esc_html__( 'Groundhogg requires PHP version %s+, plugin is currently NOT RUNNING.', 'groundhogg' ), '5.6' );
    $html_message = sprintf( '<div class="error">%s</div>', wpautop( $message ) );
    echo wp_kses_post( $html_message );
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
function groundhogg_fail_wp_version() {
    /* translators: %s: WordPress version */
    $message = sprintf( esc_html__( 'Groundhogg requires WordPress version %s+. Because you are using an earlier version, the plugin is currently NOT RUNNING.', 'groundhogg' ), '4.9' );
    $html_message = sprintf( '<div class="error">%s</div>', wpautop( $message ) );
    echo wp_kses_post( $html_message );
}