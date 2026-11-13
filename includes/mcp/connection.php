<?php

namespace Groundhogg\Mcp;

use WP_Error;
use function Groundhogg\admin_page_url;
use function Groundhogg\verify_admin_ajax_nonce;

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
	 * A second plugin that adds sign-in with a login and consent screen (OAuth) for the Claude and ChatGPT apps, so no
	 * password has to be pasted. It is optional, and needs a newer PHP than the rest of this does.
	 */
	const CONNECTOR_PLUGIN_SLUG = 'enable-abilities-for-mcp';
	const CONNECTOR_MIN_PHP     = '8.0';

	/**
	 * Identifies the application passwords made for an agent. WordPress keeps it with the password and lets us
	 * look the password up by it, so it doesn't matter what the person named theirs.
	 */
	const APP_ID = '7c8f3d52-0a4e-4b6f-9d1e-5f2a8c6b9e34';

	/**
	 * User meta for when an agent last used one of the site's MCP servers as that user, see note_seen().
	 */
	const SEEN_META = 'gh_mcp_last_seen';

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
	public static function plugin_file( string $slug = self::PLUGIN_SLUG ): string {

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		foreach ( array_keys( get_plugins() ) as $file ) {
			if ( strpos( $file, $slug . '/' ) === 0 ) {
				return $file;
			}
		}

		return '';
	}

	/**
	 * Whether this person can use the AI agent tab and the connect steps in the "Build with AI" popup. Anyone who works
	 * with contacts can connect an assistant of their own, it acts as them and can do only what they can do. The
	 * steps that change the site, like installing a plugin, check for more.
	 *
	 * @return bool
	 */
	public static function can_connect(): bool {
		return self::is_supported() && current_user_can( 'view_contacts' );
	}

	/**
	 * Whether this person can get a plugin running: activate it if it's here, or install and activate it if not.
	 *
	 * @param string $slug the plugin, the MCP Adapter unless said otherwise
	 *
	 * @return bool
	 */
	public static function can_install( string $slug = self::PLUGIN_SLUG ): bool {

		$file = self::plugin_file( $slug );

		if ( $file ) {
			return current_user_can( 'activate_plugin', $file );
		}

		return current_user_can( 'install_plugins' ) && current_user_can( 'activate_plugins' );
	}

	/**
	 * Whether this person's assistant has used the site lately, so there's no need to tell them about AI.
	 *
	 * @param int $user_id defaults to the current user
	 *
	 * @return bool
	 */
	public static function is_in_use( int $user_id = 0 ): bool {

		/**
		 * Filter how long after an assistant last used the site we still count it as in use.
		 *
		 * @param int $seconds 30 days
		 */
		$window = (int) apply_filters( 'groundhogg/mcp/in_use_window', 30 * DAY_IN_SECONDS );

		return self::last_seen( $user_id ) > time() - $window;
	}

	/**
	 * @param string $slug the plugin, the MCP Adapter unless said otherwise
	 *
	 * @return bool
	 */
	public static function is_installed( string $slug = self::PLUGIN_SLUG ): bool {
		return self::plugin_file( $slug ) !== '';
	}

	/**
	 * Whether this site can run the sign-in plugin, it needs a newer PHP than we do.
	 *
	 * @return bool
	 */
	public static function can_use_connector_plugin(): bool {
		return version_compare( PHP_VERSION, self::CONNECTOR_MIN_PHP, '>=' );
	}

	/**
	 * Where the sign-in plugin keeps its settings, the connection tab where the sign-in is turned on.
	 *
	 * @return string
	 */
	public static function connector_settings_url(): string {
		return admin_url( 'options-general.php?page=ewpa-settings&ewpa_tab=connection' );
	}

	/**
	 * Whether the MCP Adapter plugin itself is active.
	 *
	 * This is deliberately not a check for the adapter's classes: other plugins, WooCommerce for one, bundle their own
	 * copy, which says nothing about whether the plugin is installed and active. See is_served() for that.
	 *
	 * @return bool
	 */
	public static function is_active( string $slug = self::PLUGIN_SLUG ): bool {

		$file = self::plugin_file( $slug );

		if ( ! $file ) {
			return false;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( $file ) || ( is_multisite() && is_plugin_active_for_network( $file ) );
	}

	/**
	 * Whether the site answers on the default server's address, whatever provides it, the plugin or another plugin
	 * that bundles the adapter.
	 *
	 * @return bool
	 */
	public static function is_served(): bool {
		return array_key_exists( '/mcp/mcp-adapter-default-server', rest_get_server()->get_routes() );
	}

	/**
	 * Install the adapter from wordpress.org if it is not here yet, and activate it.
	 *
	 * @return true|WP_Error
	 */
	public static function install( string $slug = self::PLUGIN_SLUG ) {

		if ( self::is_active( $slug ) ) {
			return true;
		}

		$file = self::plugin_file( $slug );

		if ( ! $file ) {

			if ( ! current_user_can( 'install_plugins' ) ) {
				return new WP_Error( 'error', __( 'You are not allowed to install plugins on this site.', 'groundhogg' ) );
			}

			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

			$api = plugins_api( 'plugin_information', [ 'slug' => $slug, 'fields' => [ 'sections' => false ] ] );

			if ( is_wp_error( $api ) ) {
				return $api;
			}

			if ( ! empty( $api->requires_php ) && ! is_php_version_compatible( $api->requires_php ) ) {
				return new WP_Error( 'error', sprintf(
				/* translators: %s: the PHP version the plugin needs */
					__( 'That plugin needs PHP %s or newer, which this site does not have.', 'groundhogg' ),
					$api->requires_php
				) );
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
	 * A name for the next password, so no one has to think of one: "AI assistant", then "AI assistant 2" and so on.
	 *
	 * @param int $user_id defaults to the current user
	 *
	 * @return string
	 */
	public static function next_name( int $user_id = 0 ): string {

		$user_id = $user_id ?: get_current_user_id();
		$base    = __( 'AI assistant', 'groundhogg' );
		$taken   = array_map( 'strtolower', wp_list_pluck( \WP_Application_Passwords::get_user_application_passwords( $user_id ), 'name' ) );
		$name    = $base;
		$number  = 1;

		while ( in_array( strtolower( $name ), $taken, true ) ) {
			$name = sprintf( '%s %d', $base, ++ $number );
		}

		return $name;
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
	 * Whether this person has connected an agent: one has signed in as them, and the site still answers to MCP
	 * clients.
	 *
	 * Having a password isn't enough. The connect steps make one before the person has added it to their assistant,
	 * and counting it would move them on before they've copied it, the only time it's shown. Anyone whose assistant
	 * connected before we started noting sign-ins counts again the next time it uses the site.
	 *
	 * @param int $user_id defaults to the current user
	 *
	 * @return bool
	 */
	public static function is_connected( int $user_id = 0 ): bool {

		if ( self::last_seen( $user_id ) === 0 ) {
			return false;
		}

		// an assistant that connected before the server stopped working can't reach anything now
		return self::is_serving();
	}

	/**
	 * The protocol revision the connection check speaks. Every version of the adapter knows it, older ones know nothing
	 * newer.
	 */
	const CHECK_PROTOCOL = '2025-11-25';

	/**
	 * Whether the connection check is talking to a server right now, so note_seen() doesn't take it for an agent.
	 *
	 * @var bool
	 */
	protected static $checking = false;

	/**
	 * Whether an MCP server on this site actually answers, as the current user, with tools an agent can use. A plugin
	 * being active isn't enough: the server can fail to register, or another plugin can switch its tools off.
	 *
	 * Asks each MCP server the site has, the adapter's default server first, the way an agent would: initialize, list
	 * the tools, end the session. It goes through the REST API without leaving the server, so it says nothing about
	 * whether the site can be reached from outside, or whether the host strips the Authorization header.
	 *
	 * @return bool
	 */
	public static function is_serving(): bool {

		if ( ! is_user_logged_in() ) {
			return false;
		}

		$default = '/mcp/mcp-adapter-default-server';
		$routes  = array_values( array_filter( array_keys( rest_get_server()->get_routes() ), function ( $route ) use ( $default ) {
			// skip routes with parameters, a server's endpoint has none
			return strpos( $route, '/mcp/' ) === 0 && strpos( $route, '(' ) === false && $route !== $default;
		} ) );

		if ( self::is_served() ) {
			array_unshift( $routes, $default );
		}

		// asked once per request, unless the person or the servers change, like a plugin being activated
		$key = get_current_user_id() . ':' . implode( ',', $routes );

		if ( isset( self::$serving[ $key ] ) ) {
			return self::$serving[ $key ];
		}

		$serving = false;

		foreach ( $routes as $route ) {
			if ( self::lists_tools( $route ) ) {
				$serving = true;
				break;
			}
		}

		self::$serving[ $key ] = $serving;

		return $serving;
	}

	/**
	 * What is_serving() found, by person and servers, see there.
	 *
	 * @var bool[]
	 */
	protected static $serving = [];

	/**
	 * Ask one MCP server for its tools, as an agent would.
	 *
	 * @param string $route the server's REST route, e.g. /mcp/mcp-adapter-default-server
	 *
	 * @return bool whether it listed at least one tool
	 */
	protected static function lists_tools( string $route ): bool {

		self::$checking = true;

		$send = function ( string $method, array $message = [], string $session = '' ) use ( $route ) {

			$request = new \WP_REST_Request( $method, $route );
			$request->set_header( 'Accept', 'application/json, text/event-stream' );

			if ( $session ) {
				$request->set_header( 'Mcp-Session-Id', $session );
				$request->set_header( 'Mcp-Protocol-Version', self::CHECK_PROTOCOL );
			}

			if ( $message ) {
				$request->set_header( 'Content-Type', 'application/json' );
				$request->set_body( wp_json_encode( array_merge( [ 'jsonrpc' => '2.0' ], $message ) ) );
			}

			// as when serving a real request: older adapters, like the copy WooCommerce bundles, add the session header here
			return apply_filters( 'rest_post_dispatch', rest_do_request( $request ), rest_get_server(), $request );
		};

		$tools = [];

		try {

			$initialized = $send( 'POST', [
				'id'     => 1,
				'method' => 'initialize',
				'params' => [
					'protocolVersion' => self::CHECK_PROTOCOL,
					'capabilities'    => new \stdClass(),
					'clientInfo'      => [ 'name' => 'connection-check', 'version' => GROUNDHOGG_VERSION ],
				],
			] );

			if ( ! $initialized->is_error() ) {

				$session = (string) ( $initialized->get_headers()['Mcp-Session-Id'] ?? '' );
				$listed  = $send( 'POST', [ 'id' => 2, 'method' => 'tools/list' ], $session );
				$data    = json_decode( wp_json_encode( $listed->get_data() ), true );
				$tools   = $listed->is_error() ? [] : ( $data['result']['tools'] ?? [] );

				// tidy up, the adapter keeps sessions until they expire
				if ( $session ) {
					$send( 'DELETE', [], $session );
				}
			}

		} catch ( \Throwable $e ) {
			$tools = [];
		}

		self::$checking = false;

		return is_array( $tools ) && ! empty( $tools );
	}

	/**
	 * When an agent last used one of the site's MCP servers as this person.
	 *
	 * @param int $user_id defaults to the current user
	 *
	 * @return int a timestamp, 0 if no agent ever has
	 */
	public static function last_seen( int $user_id = 0 ): int {
		$user_id = $user_id ?: get_current_user_id();

		return $user_id ? absint( get_user_meta( $user_id, self::SEEN_META, true ) ) : 0;
	}

	/**
	 * Note when an agent uses one of the site's MCP servers, so that the "Build with AI" popup can tell the moment an
	 * assistant connects, however it signed in: an application password, or the sign-in plugin's OAuth.
	 *
	 * Runs after the callbacks because the OAuth server signs the user in from its permission callback.
	 *
	 * @param mixed            $response
	 * @param array            $handler
	 * @param \WP_REST_Request $request
	 *
	 * @return mixed the response, untouched
	 */
	public static function note_seen( $response, $handler, $request ) {

		if ( self::$checking || strpos( $request->get_route(), '/mcp/' ) !== 0 || is_wp_error( $response ) ) {
			return $response;
		}

		$user_id = get_current_user_id();

		// once a minute is plenty, an agent makes several requests in a row
		if ( $user_id && self::last_seen( $user_id ) < time() - MINUTE_IN_SECONDS ) {
			update_user_meta( $user_id, self::SEEN_META, time() );
		}

		return $response;
	}

	/**
	 * Lets the "Build with AI" popup ask whether an assistant has connected yet, while someone is off connecting one.
	 *
	 * @return void
	 */
	public static function ajax_is_connected() {

		if ( ! verify_admin_ajax_nonce() ) {
			wp_send_json_error( [ 'message' => __( 'This page has expired, please reload it and try again.', 'groundhogg' ) ] );
		}

		wp_send_json_success( [ 'connected' => self::is_connected() ] );
	}

	/**
	 * @return void
	 */
	public static function init() {
		add_filter( 'rest_request_after_callbacks', [ __CLASS__, 'note_seen' ], 10, 3 );
		add_action( 'wp_ajax_gh_mcp_is_connected', [ __CLASS__, 'ajax_is_connected' ] );
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
