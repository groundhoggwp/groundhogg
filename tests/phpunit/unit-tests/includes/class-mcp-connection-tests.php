<?php

use Groundhogg\Mcp\Connection;

/**
 * Connecting an AI agent over MCP: where the adapter answers, the application password an agent signs in with, and
 * the tab in Tools that walks someone through it.
 */
class Mcp_Connection_Tests extends GH_UnitTestCase {

	/**
	 * The REST server the rest of the suite shares. Groundhogg's routes are only declared on the first server built in
	 * a process, so a server rebuilt from scratch has none of them: build it before the tests replace it, and put it
	 * back when they are done.
	 *
	 * @var WP_REST_Server
	 */
	protected $suite_rest_server;

	public function setUp(): void {
		parent::setUp();
		add_filter( 'wp_is_application_passwords_available', '__return_true' );
		$this->suite_rest_server = rest_get_server();
	}

	public function tearDown(): void {
		remove_filter( 'wp_is_application_passwords_available', '__return_true' );
		wp_set_current_user( 0 );
		if ( $this->route_callback ) {
			remove_action( 'rest_api_init', $this->route_callback );
		}
		$GLOBALS['wp_rest_server'] = $this->suite_rest_server;
		parent::tearDown();
	}

	protected function user_with_role( string $role ): int {
		$id = self::factory()->user->create( [ 'role' => $role ] );
		wp_set_current_user( $id );

		return $id;
	}

	/* ---- where it is ------------------------------------------------------------------------------------- */

	public function test_agents_connect_to_the_default_server_of_the_adapter() {
		$this->assertStringEndsWith( '/mcp/mcp-adapter-default-server', Connection::endpoint() );
		$this->assertSame( rest_url( 'mcp/mcp-adapter-default-server' ), Connection::endpoint() );
	}

	public function test_the_name_in_a_client_comes_from_the_host_of_the_site() {
		$this->assertMatchesRegularExpression( '/^[a-z0-9]+(-[a-z0-9]+)*$/', Connection::server_name() );
	}

	public function test_the_adapter_is_not_installed_here() {
		// nothing in the test site's plugins directory, so it is neither installed nor active
		$this->assertFalse( Connection::is_installed() );
		$this->assertFalse( Connection::is_active() );
	}

	public function test_a_copy_of_the_adapter_bundled_in_another_plugin_is_not_the_plugin() {

		// what WooCommerce and others do: ship the adapter's classes inside themselves
		if ( ! class_exists( '\WP\MCP\Core\McpAdapter' ) ) {
			eval( 'namespace WP\MCP\Core; final class McpAdapter {}' );
		}

		$this->assertFalse( Connection::is_installed() );
		$this->assertFalse( Connection::is_active(), 'the plugin is not active just because its classes are loaded' );
	}

	/* ---- whether the site answers MCP clients ------------------------------------------------------------ */

	protected $route_callback;

	protected function reset_rest_server() {
		global $wp_rest_server;
		$wp_rest_server = null;
	}

	/**
	 * Answer on the adapter's default address the way an MCP server does: a session from initialize, and the given tools
	 * from tools/list. See Connection::is_serving().
	 *
	 * @param array $tools what tools/list returns
	 */
	protected function serve_the_default_server( array $tools = [ [ 'name' => 'mcp-adapter-discover-abilities' ] ] ) {
		$this->route_callback = function () use ( $tools ) {
			register_rest_route( 'mcp', '/mcp-adapter-default-server', [
				'methods'             => [ 'POST', 'DELETE' ],
				'permission_callback' => '__return_true',
				'callback'            => function ( \WP_REST_Request $request ) use ( $tools ) {

					if ( $request->get_method() === 'DELETE' ) {
						return new \WP_REST_Response( null, 202 );
					}

					$message = json_decode( $request->get_body(), true );
					$method  = $message['method'] ?? '';

					if ( $method === 'initialize' ) {
						$response = new \WP_REST_Response( [ 'jsonrpc' => '2.0', 'id' => $message['id'], 'result' => [ 'protocolVersion' => '2025-11-25' ] ] );
						$response->header( 'Mcp-Session-Id', 'test-session' );

						return $response;
					}

					if ( $method === 'tools/list' && $request->get_header( 'Mcp-Session-Id' ) === 'test-session' ) {
						return [ 'jsonrpc' => '2.0', 'id' => $message['id'], 'result' => [ 'tools' => $tools ] ];
					}

					return new \WP_REST_Response( [ 'jsonrpc' => '2.0', 'id' => $message['id'] ?? null, 'error' => [ 'code' => -32600 ] ], 400 );
				},
			] );
		};

		add_action( 'rest_api_init', $this->route_callback );
		$this->reset_rest_server();
	}

	public function test_the_site_is_served_when_something_answers_on_the_default_address() {
		$this->reset_rest_server();
		$this->assertFalse( Connection::is_served() );

		$this->serve_the_default_server();
		$this->assertTrue( Connection::is_served() );

		remove_action( 'rest_api_init', $this->route_callback );
		$this->reset_rest_server();
	}

	public function test_it_is_connected_once_an_agent_has_signed_in_and_a_server_answers() {
		$user = $this->user_with_role( 'administrator' );
		Connection::create_application_password( $user, 'My assistant' );

		// another plugin answers, the adapter plugin isn't active
		$this->serve_the_default_server();
		$this->assertTrue( Connection::is_serving() );

		// the connect steps make the password before it's in the assistant, so it isn't a connection yet
		$this->assertFalse( Connection::is_connected( $user ) );

		update_user_meta( $user, Connection::SEEN_META, time() );
		$this->assertTrue( Connection::is_connected( $user ) );

		// signed in before, but nothing answers now
		remove_action( 'rest_api_init', $this->route_callback );
		$this->reset_rest_server();
		$this->assertFalse( Connection::is_connected( $user ) );
	}

	public function test_a_server_without_tools_is_not_serving() {
		$this->user_with_role( 'administrator' );

		$this->serve_the_default_server( [] );
		$this->assertFalse( Connection::is_serving() );

		remove_action( 'rest_api_init', $this->route_callback );
		$this->reset_rest_server();
	}

	public function test_an_agent_signing_in_is_noted_but_the_check_is_not() {
		$user = $this->user_with_role( 'administrator' );
		$this->serve_the_default_server();

		// the site asking its own server
		Connection::is_serving();
		$this->assertSame( 0, Connection::last_seen( $user ) );

		// an agent
		$request = new \WP_REST_Request( 'POST', '/mcp/mcp-adapter-default-server' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( [ 'jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => [] ] ) );
		rest_do_request( $request );

		$this->assertGreaterThan( 0, Connection::last_seen( $user ) );

		remove_action( 'rest_api_init', $this->route_callback );
		$this->reset_rest_server();
	}

	/**
	 * The tab, as the current user sees it.
	 */
	/**
	 * The tab as the page prints it. Without its script by default, which lists every step, so what is left is the
	 * forms and text that are really on the page.
	 */
	protected function render_tab( ?\Groundhogg\Admin\Tools\Tools_Page $tools = null, bool $with_script = false ): string {
		$tools = $tools ?: new \Groundhogg\Admin\Tools\Tools_Page();

		ob_start();
		$tools->mcp_view();
		$html = ob_get_clean();

		return $with_script ? $html : preg_replace( '#<script>.*?</script>#s', '', $html );
	}

	protected function stop_serving_the_default_server() {
		remove_action( 'rest_api_init', $this->route_callback );
		$this->reset_rest_server();
	}

	/* ---- the application password ------------------------------------------------------------------------ */

	public function test_an_application_password_is_made_and_tagged_for_an_agent() {
		$user = $this->user_with_role( 'administrator' );

		$created = Connection::create_application_password( $user, 'Claude' );

		$this->assertIsArray( $created );
		$this->assertNotEmpty( $created['password'] );
		$this->assertSame( get_userdata( $user )->user_login, $created['username'] );

		$passwords = Connection::application_passwords( $user );

		$this->assertCount( 1, $passwords );
		$this->assertSame( 'Claude', $passwords[0]['name'] );
		$this->assertSame( Connection::APP_ID, $passwords[0]['app_id'] );
	}

	public function test_the_token_is_the_username_and_password_for_basic_authentication() {
		$user = $this->user_with_role( 'administrator' );

		$created = Connection::create_application_password( $user, 'Claude' );

		// WordPress ignores the spaces in an application password, so the token leaves them out
		$this->assertSame(
			$created['username'] . ':' . str_replace( ' ', '', $created['password'] ),
			base64_decode( $created['token'] )
		);

		// and it signs in as that user, which WordPress only allows for API requests
		add_filter( 'application_password_is_api_request', '__return_true' );
		wp_set_current_user( 0 );

		$authenticated = wp_authenticate_application_password( null, $created['username'], $created['password'] );

		remove_filter( 'application_password_is_api_request', '__return_true' );

		$this->assertInstanceOf( WP_User::class, $authenticated );
		$this->assertSame( $user, $authenticated->ID );
	}

	public function test_passwords_made_for_other_things_are_not_counted() {
		$user = $this->user_with_role( 'administrator' );

		WP_Application_Passwords::create_new_application_password( $user, [ 'name' => 'Zapier' ] );

		$this->assertSame( [], Connection::application_passwords( $user ) );

		Connection::create_application_password( $user, 'Claude' );

		$this->assertCount( 1, Connection::application_passwords( $user ) );
	}

	public function test_passwords_are_kept_per_person() {
		$one     = $this->user_with_role( 'administrator' );
		$another = $this->user_with_role( 'administrator' );

		wp_set_current_user( $one );
		Connection::create_application_password( $one, 'Claude' );

		$this->assertCount( 1, Connection::application_passwords( $one ) );
		$this->assertSame( [], Connection::application_passwords( $another ) );
	}

	public function test_a_name_is_needed_and_can_not_be_used_twice() {
		$user = $this->user_with_role( 'administrator' );

		$this->assertWPError( Connection::create_application_password( $user, '' ) );

		$this->assertIsArray( Connection::create_application_password( $user, 'Claude' ) );
		$this->assertWPError( Connection::create_application_password( $user, 'claude' ) );
	}

	public function test_nobody_can_make_a_password_for_a_user_they_can_not_edit() {
		$other = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$this->user_with_role( 'editor' );

		$this->assertWPError( Connection::create_application_password( $other, 'Claude' ) );
		$this->assertSame( [], Connection::application_passwords( $other ) );
	}

	public function test_nothing_is_made_where_application_passwords_are_off() {
		$user = $this->user_with_role( 'administrator' );

		remove_filter( 'wp_is_application_passwords_available', '__return_true' );
		add_filter( 'wp_is_application_passwords_available', '__return_false' );

		$this->assertFalse( Connection::can_use_application_passwords() );
		$this->assertWPError( Connection::create_application_password( $user, 'Claude' ) );

		remove_filter( 'wp_is_application_passwords_available', '__return_false' );
	}

	public function test_it_is_not_connected_until_the_adapter_is_active_too() {
		$user = $this->user_with_role( 'administrator' );

		Connection::create_application_password( $user, 'Claude' );

		// a password and no adapter to answer with it
		$this->assertFalse( Connection::is_connected( $user ) );
	}

	/* ---- the tab in Tools -------------------------------------------------------------------------------- */

	public function test_the_tab_is_in_tools_for_administrators() {
		if ( ! Connection::is_supported() ) {
			$this->markTestSkipped( 'Needs WordPress 6.9 for the Abilities API.' );
		}

		$this->user_with_role( 'administrator' );

		$tools = new \Groundhogg\Admin\Tools\Tools_Page();
		$urls  = array_column( $tools->command_palette_tab_commands( [] ), 'url' );

		$this->assertContains( Connection::page_url(), $urls );
		$this->assertStringContainsString( 'page=' . Connection::PAGE, Connection::page_url() );
		$this->assertStringContainsString( 'tab=' . Connection::TAB, Connection::page_url() );
	}

	public function test_the_tab_is_for_anyone_who_works_with_contacts() {
		if ( ! Connection::is_supported() ) {
			$this->markTestSkipped( 'Needs WordPress 6.9 for the Abilities API.' );
		}

		// they can connect an assistant of their own, it acts as them
		$this->user_with_role( 'sales_rep' );

		$tools = new \Groundhogg\Admin\Tools\Tools_Page();
		$urls  = array_column( $tools->command_palette_tab_commands( [] ), 'url' );

		$this->assertContains( Connection::page_url(), $urls );

		$this->user_with_role( 'subscriber' );

		$tools = new \Groundhogg\Admin\Tools\Tools_Page();
		$urls  = array_column( $tools->command_palette_tab_commands( [] ), 'url' );

		$this->assertNotContains( Connection::page_url(), $urls );
	}

	public function test_only_people_who_can_manage_plugins_are_offered_the_plugin_steps() {
		$this->user_with_role( 'sales_rep' );

		$html = $this->render_tab();

		$this->assertStringNotContainsString( 'install_mcp_adapter', $html );
		$this->assertStringContainsString( 'An administrator needs to install and activate the MCP Adapter', $html );

		// and they can't send the step anyway
		$this->assertFalse( $this->send_step( 'install_mcp_adapter' )['success'] );
	}

	/* ---- one step at a time ------------------------------------------------------------------------------ */

	public function test_at_first_the_tab_only_asks_for_the_adapter() {
		$this->user_with_role( 'administrator' );
		$this->reset_rest_server();

		$html = $this->render_tab();

		$this->assertStringNotContainsString( 'installed and active', $html );
		$this->assertStringContainsString( 'Install and activate the MCP Adapter', $html );
		$this->assertStringContainsString( 'install_mcp_adapter', $html );

		// the next steps wait their turn
		$this->assertStringContainsString( 'Create a password for your assistant', $html );
		$this->assertStringContainsString( 'Connect your assistant', $html );
		$this->assertStringNotContainsString( 'create_agent_password', $html );
		$this->assertStringNotContainsString( 'claude mcp add', $html );
	}

	public function test_once_the_site_answers_the_tab_only_asks_for_a_password() {
		$this->user_with_role( 'administrator' );
		$this->serve_the_default_server();

		$html = $this->render_tab();

		$this->stop_serving_the_default_server();

		// another plugin answers, so there is nothing to install, and it is not the plugin that is said to be active
		$this->assertStringContainsString( 'Your site is ready for AI assistants.', $html );
		$this->assertStringNotContainsString( 'installed and active', $html );
		$this->assertStringNotContainsString( 'install_mcp_adapter', $html );

		$this->assertStringContainsString( 'create_agent_password', $html );
		$this->assertStringNotContainsString( 'claude mcp add', $html );
	}

	public function test_a_password_is_not_asked_for_where_they_are_off() {
		$this->user_with_role( 'administrator' );
		$this->serve_the_default_server();

		remove_filter( 'wp_is_application_passwords_available', '__return_true' );
		add_filter( 'wp_is_application_passwords_available', '__return_false' );

		$html = $this->render_tab();

		remove_filter( 'wp_is_application_passwords_available', '__return_false' );
		$this->stop_serving_the_default_server();

		$this->assertStringContainsString( 'Application passwords are not available on this site', $html );
		$this->assertStringNotContainsString( 'create_agent_password', $html );
	}

	public function test_the_next_name_is_not_one_that_is_taken() {
		$user = $this->user_with_role( 'administrator' );

		$this->assertSame( 'AI assistant', Connection::next_name( $user ) );

		Connection::create_application_password( $user, Connection::next_name( $user ) );
		$this->assertSame( 'AI assistant 2', Connection::next_name( $user ) );

		Connection::create_application_password( $user, Connection::next_name( $user ) );
		$this->assertSame( 'AI assistant 3', Connection::next_name( $user ) );

		// someone else's passwords do not count
		$this->assertSame( 'AI assistant', Connection::next_name( $this->user_with_role( 'administrator' ) ) );
	}

	public function test_the_tab_walks_through_the_steps_and_does_not_name_the_product() {
		$user = $this->user_with_role( 'administrator' );
		$this->serve_the_default_server();
		Connection::create_application_password( $user, 'Earlier' );

		$html = $this->render_tab();

		$this->stop_serving_the_default_server();

		$this->assertStringContainsString( Connection::endpoint(), $html );
		$this->assertStringContainsString( 'YOUR_AUTH_TOKEN', $html, 'a placeholder, as that password can not be shown again' );
		$this->assertStringContainsString( 'Create a new password', $html );
		$this->assertStringNotContainsString( 'can not be shown again', $html, 'passwords are cheap, just offer a new one' );
		$this->assertStringContainsString( 'Header name: Authorization', $html, 'for custom connectors' );
		// no one assistant is favored, each gets its own instructions
		$this->assertStringContainsString( 'claude mcp add', $html );
		$this->assertStringContainsString( 'gemini mcp add', $html );
		$this->assertStringContainsString( '[mcp_servers.', $html );

		// the examples are escaped for a textarea
		$text = html_entity_decode( $html );
		$this->assertStringContainsString( '"servers"', $text, 'VS Code' );
		$this->assertStringContainsString( '"mcpServers"', $text, 'Cursor' );
		$this->assertStringContainsString( '@automattic/mcp-wordpress-remote', $html );
		$this->assertStringNotContainsStringIgnoringCase( 'groundhogg', wp_strip_all_tags( $html ) );
	}

	public function test_the_sign_in_plugin_is_offered_with_the_last_step_only_where_it_can_run() {
		$user = $this->user_with_role( 'administrator' );
		$this->serve_the_default_server();
		Connection::create_application_password( $user, 'Earlier' );

		$html = $this->render_tab();

		$this->assertSame( version_compare( PHP_VERSION, '8.0', '>=' ), Connection::can_use_connector_plugin() );
		$this->assertStringContainsString( 'install_mcp_connector', $html );
		$this->assertStringContainsString( 'Install and activate Enable Abilities for MCP', $html );
		$this->assertStringContainsString( 'It also adds abilities for the rest of WordPress.', $html );
		$this->assertStringContainsString( 'ewpa-settings', Connection::connector_settings_url() );
		$this->assertStringNotContainsStringIgnoringCase( 'groundhogg', wp_strip_all_tags( $html ) );

		// not before they get there
		$this->stop_serving_the_default_server();
		$this->reset_rest_server();
		$this->assertStringNotContainsString( 'install_mcp_connector', $this->render_tab() );
	}

	public function test_the_plugin_helpers_work_for_any_plugin() {
		$this->assertSame( '', Connection::plugin_file( 'no-such-plugin-here' ) );
		$this->assertFalse( Connection::is_installed( 'no-such-plugin-here' ) );
		$this->assertFalse( Connection::is_active( 'no-such-plugin-here' ) );
	}

	/* ---- steps sent in the background -------------------------------------------------------------------- */

	/**
	 * Send a step the way the page does in the background, and read the answer.
	 *
	 * @param string $step
	 * @param bool   $with_nonce
	 *
	 * @return array the decoded JSON answer
	 */
	protected function send_step( string $step, bool $with_nonce = true, string $name = '' ): array {

		$tools = new \Groundhogg\Admin\Tools\Tools_Page();

		$die = function () {
			return function () {
				throw new \RuntimeException( 'wp_die' );
			};
		};

		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', $die );

		$_POST['step']                = $step;
		$_POST['agent_name']          = $name;
		$_REQUEST['gh_admin_ajax_nonce'] = $with_nonce ? wp_create_nonce( 'admin_ajax' ) : 'nope';

		ob_start();

		try {
			$tools->ajax_mcp_step();
		} catch ( \RuntimeException $e ) {
			// wp_send_json() ends the request
		}

		$out = ob_get_clean();

		remove_filter( 'wp_doing_ajax', '__return_true' );
		remove_filter( 'wp_die_ajax_handler', $die );
		unset( $_POST['step'], $_POST['agent_name'], $_REQUEST['gh_admin_ajax_nonce'] );

		return json_decode( $out, true ) ?: [];
	}

	public function test_the_page_can_send_a_step_without_a_reload() {
		$user = $this->user_with_role( 'administrator' );
		$this->serve_the_default_server();

		$html = $this->render_tab( null, true );

		$this->assertStringContainsString( 'id="gh-mcp-content"', $html, 'what is swapped' );
		$this->assertStringContainsString( 'gh_mcp_step', $html );
		$this->assertStringContainsString( 'data-nonce=', $html );
		foreach ( \Groundhogg\Admin\Tools\Tools_Page::MCP_STEPS as $step ) {
			$this->assertStringContainsString( $step, $html );
		}

		$answer = $this->send_step( 'create_agent_password' );

		$this->stop_serving_the_default_server();

		$this->assertTrue( $answer['success'] );
		$this->assertSame( 'Application password created!', $answer['data']['message'] );
		$this->assertCount( 1, Connection::application_passwords( $user ) );

		// the page that comes back has the password in it, and no notice is left to turn up on the next page
		$this->assertStringContainsString( 'will not be shown again', $answer['data']['html'] );
		$this->assertStringNotContainsString( 'YOUR_AUTH_TOKEN', $answer['data']['html'] );
		$this->assertEmpty( get_transient( 'groundhogg_notices_user_' . $user ) );
	}

	public function test_the_password_can_be_named_and_a_name_is_suggested_when_it_is_not() {
		$user = $this->user_with_role( 'administrator' );
		$this->serve_the_default_server();

		// the suggestion is in the form
		$this->assertStringContainsString( 'name="agent_name"', $this->render_tab() );
		$this->assertStringContainsString( 'value="AI assistant"', $this->render_tab() );

		$this->assertTrue( $this->send_step( 'create_agent_password', true, 'Work laptop' )['success'] );
		$this->assertSame( 'Work laptop', Connection::application_passwords( $user )[0]['name'] );

		// the same name twice is refused, the others are not touched
		$again = $this->send_step( 'create_agent_password', true, 'work laptop' );
		$this->assertFalse( $again['success'] );
		$this->assertCount( 1, Connection::application_passwords( $user ) );

		// left empty, one is picked
		$this->assertTrue( $this->send_step( 'create_agent_password', true, '  ' )['success'] );
		$names = wp_list_pluck( Connection::application_passwords( $user ), 'name' );
		$this->assertContains( 'AI assistant', $names );

		// and the form suggests the next free one, even with passwords already made
		$this->assertStringContainsString( 'value="AI assistant 2"', $this->render_tab() );

		$this->stop_serving_the_default_server();
	}

	public function test_a_step_sent_in_the_background_needs_the_nonce_and_the_right() {
		$user = $this->user_with_role( 'administrator' );

		$answer = $this->send_step( 'create_agent_password', false );
		$this->assertFalse( $answer['success'] );
		$this->assertCount( 0, Connection::application_passwords( $user ) );

		// someone who doesn't work with contacts
		$this->user_with_role( 'subscriber' );
		$answer = $this->send_step( 'create_agent_password' );
		$this->assertFalse( $answer['success'] );

		$this->user_with_role( 'administrator' );
		$answer = $this->send_step( 'delete_everything' );
		$this->assertFalse( $answer['success'], 'only the steps of the tab' );
	}

	public function test_a_password_just_made_is_in_the_examples() {
		$user = $this->user_with_role( 'administrator' );
		$this->serve_the_default_server();

		$tools                     = new \Groundhogg\Admin\Tools\Tools_Page();
		$tools->new_agent_password = Connection::create_application_password( $user, 'Claude' );

		$html = $this->render_tab( $tools );

		$this->stop_serving_the_default_server();

		$this->assertStringContainsString( $tools->new_agent_password['token'], $html );
		$this->assertStringNotContainsString( 'YOUR_AUTH_TOKEN', $html );
		$this->assertStringContainsString( 'will not be shown again', $html );
		$this->assertStringNotContainsString( 'Create a new password', $html, 'the one just made is right there' );
	}
}
