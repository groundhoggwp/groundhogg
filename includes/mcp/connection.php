<?php

namespace Groundhogg\Mcp;

use WP_Error;
use function Groundhogg\admin_page_url;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Connecting an AI agent to this site over MCP (the Model Context Protocol).
 *
 * Abilities are registered with the core Abilities API (WordPress 6.9+). The official MCP Adapter plugin from
 * wordpress.org serves them to AI clients over HTTP, authenticated as a WordPress user with an application password.
 * This class knows how to get there:
 *
 *  - whether the adapter is installed and active, and how to install and activate it
 *  - where the adapter's default server answers
 *  - creating the application password an agent signs in with, tagged so we can tell it apart from the rest
 *
 * The page that walks someone through it is the AI agent tab in Tools, see admin/tools/mcp.php.
 */
class Connection {

	const PAGE = 'gh_tools';
	const TAB  = 'mcp';

	const PLUGIN_SLUG = 'mcp-adapter';

	/**
	 * Identifies the application passwords made for an agent. WordPress keeps it with the password and lets us
	 * look the password up by it, so it doesn't matter what the person named theirs.
	 */
	const APP_ID = '7c8f3d52-0a4e-4b6f-9d1e-5f2a8c6b9e34';

	/**
	 * @return string the tab in the admin that walks someone through connecting an agent
	 */
	public static function page_url(): string {
		return admin_page_url( self::PAGE, [ 'tab' => self::TAB ] );
	}

	/**
	 * Whether this site can serve abilities to an agent at all. The Abilities API arrived with WordPress 6.9, and
	 * the adapter has nothing to serve without it.
	 *
	 * @return bool
	 */
	public static function is_supported(): bool {
		return function_exists( 'wp_register_ability' );
	}

	/**
	 * The plugin file of the adapter, relative to the plugins directory, or an empty string when it isn't installed.
	 *
	 * @return string
	 */
	public static function plugin_file(): string {

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		foreach ( array_keys( get_plugins() ) as $file ) {
			if ( strpos( $file, self::PLUGIN_SLUG . '/' ) === 0 ) {
				return $file;
			}
		}

		return '';
	}

	/**
	 * @return bool
	 */
	public static function is_installed(): bool {
		return self::plugin_file() !== '';
	}

	/**
	 * @return bool
	 */
	public static function is_active(): bool {

		if ( class_exists( '\WP\MCP\Core\McpAdapter' ) ) {
			return true;
		}

		$file = self::plugin_file();

		if ( ! $file ) {
			return false;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( $file ) || ( is_multisite() && is_plugin_active_for_network( $file ) );
	}

	/**
	 * Install the adapter from wordpress.org if it is not here yet, and activate it.
	 *
	 * @return true|WP_Error
	 */
	public static function install() {

		if ( self::is_active() ) {
			return true;
		}

		$file = self::plugin_file();

		if ( ! $file ) {

			if ( ! current_user_can( 'install_plugins' ) ) {
				return new WP_Error( 'error', __( 'You are not allowed to install plugins on this site.', 'groundhogg' ) );
			}

			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

			$api = plugins_api( 'plugin_information', [ 'slug' => self::PLUGIN_SLUG, 'fields' => [ 'sections' => false ] ] );

			if ( is_wp_error( $api ) ) {
				return $api;
			}

			$skin     = new \WP_Ajax_Upgrader_Skin();
			$upgrader = new \Plugin_Upgrader( $skin );
			$install  = $upgrader->install( $api->download_link );

			if ( is_wp_error( $install ) ) {
				return $install;
			}

			if ( is_wp_error( $skin->result ) ) {
				return $skin->result;
			}

			if ( $skin->get_errors()->has_errors() ) {
				return $skin->get_errors();
			}

			if ( ! $install ) {
				return new WP_Error( 'error', __( 'The plugin could not be installed. You can install it from the Plugins screen instead.', 'groundhogg' ) );
			}

			$file = $upgrader->plugin_info();

			if ( ! $file ) {
				return new WP_Error( 'error', __( 'The plugin was installed, but could not be found to activate it.', 'groundhogg' ) );
			}
		}

		if ( ! current_user_can( 'activate_plugin', $file ) ) {
			return new WP_Error( 'error', __( 'You are not allowed to activate plugins on this site.', 'groundhogg' ) );
		}

		$activate = activate_plugin( $file );

		if ( is_wp_error( $activate ) ) {
			return $activate;
		}

		return true;
	}

	/**
	 * Where agents connect: the default server of the adapter.
	 *
	 * @return string
	 */
	public static function endpoint(): string {
		return apply_filters( 'groundhogg/mcp/endpoint', rest_url( 'mcp/mcp-adapter-default-server' ) );
	}

	/**
	 * Whether application passwords can be used on this site, they need HTTPS (or a local environment).
	 *
	 * @param \WP_User|null $user
	 *
	 * @return bool
	 */
	public static function can_use_application_passwords( $user = null ): bool {

		if ( ! wp_is_application_passwords_available() ) {
			return false;
		}

		return ! $user || wp_is_application_passwords_available_for_user( $user );
	}

	/**
	 * The application passwords this user made for an agent.
	 *
	 * @param int $user_id defaults to the current user
	 *
	 * @return array[]
	 */
	public static function application_passwords( int $user_id = 0 ): array {

		$user_id = $user_id ?: get_current_user_id();

		if ( ! $user_id || ! class_exists( '\WP_Application_Passwords' ) ) {
			return [];
		}

		return array_values( array_filter(
			\WP_Application_Passwords::get_user_application_passwords( $user_id ),
			function ( $password ) {
				return ( $password['app_id'] ?? '' ) === self::APP_ID;
			}
		) );
	}

	/**
	 * Make the password an agent signs in with.
	 *
	 * The password is returned once and WordPress keeps only a hash, so it can't be shown again.
	 *
	 * @param int    $user_id
	 * @param string $name what the person calls the agent, to tell it apart in their profile
	 *
	 * @return array|WP_Error username, password and the token for a Basic authorization header
	 */
	public static function create_application_password( int $user_id, string $name ) {

		$user = get_userdata( $user_id );

		if ( ! $user || ! current_user_can( 'edit_user', $user_id ) ) {
			return new WP_Error( 'error', __( 'You are not allowed to create application passwords for this user.', 'groundhogg' ) );
		}

		if ( ! self::can_use_application_passwords( $user ) ) {
			return new WP_Error( 'error', __( 'Application passwords are not available for this user.', 'groundhogg' ) );
		}

		if ( $name === '' ) {
			return new WP_Error( 'error', __( 'Please provide a name for the agent.', 'groundhogg' ) );
		}

		foreach ( \WP_Application_Passwords::get_user_application_passwords( $user_id ) as $existing ) {
			if ( strcasecmp( $existing['name'], $name ) === 0 ) {
				return new WP_Error( 'error', __( 'An application password with that name already exists for this user.', 'groundhogg' ) );
			}
		}

		$created = \WP_Application_Passwords::create_new_application_password( $user_id, [
			'name'   => $name,
			'app_id' => self::APP_ID,
		] );

		if ( is_wp_error( $created ) ) {
			return $created;
		}

		[ $password ] = $created;

		return [
			'username' => $user->user_login,
			'password' => $password,
			'token'    => \Groundhogg\Admin\Settings\Settings_Page::basic_auth_token( $user->user_login, $password ),
		];
	}

	/**
	 * Whether this person has got as far as connecting an agent: the adapter serves their site and they have a
	 * password for an agent to sign in with.
	 *
	 * @param int $user_id defaults to the current user
	 *
	 * @return bool
	 */
	public static function is_connected( int $user_id = 0 ): bool {
		return self::is_active() && ! empty( self::application_passwords( $user_id ) );
	}

	/**
	 * A name for the agent's entry in a client's list of servers, from the host of this site so that several sites
	 * can sit side by side.
	 *
	 * @return string
	 */
	public static function server_name(): string {
		$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
		$name = trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( $host ) ), '-' );

		return $name ?: 'wordpress';
	}
}
