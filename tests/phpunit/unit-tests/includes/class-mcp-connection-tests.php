<?php

use Groundhogg\Mcp\Connection;

/**
 * Connecting an AI agent over MCP: where the adapter answers, the application password an agent signs in with, and
 * the tab in Tools that walks someone through it.
 */
class Mcp_Connection_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		add_filter( 'wp_is_application_passwords_available', '__return_true' );
	}

	public function tearDown(): void {
		remove_filter( 'wp_is_application_passwords_available', '__return_true' );
		wp_set_current_user( 0 );
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

	public function test_the_tab_is_not_for_people_who_can_not_manage_the_site() {
		$this->user_with_role( 'sales_rep' );

		$tools = new \Groundhogg\Admin\Tools\Tools_Page();
		$urls  = array_column( $tools->command_palette_tab_commands( [] ), 'url' );

		$this->assertNotContains( Connection::page_url(), $urls );
	}

	public function test_the_tab_walks_through_the_steps_and_does_not_name_the_product() {
		$this->user_with_role( 'administrator' );

		$tools = new \Groundhogg\Admin\Tools\Tools_Page();

		ob_start();
		$tools->mcp_view();
		$html = ob_get_clean();

		$this->assertStringContainsString( Connection::endpoint(), $html );
		$this->assertStringContainsString( 'YOUR_AUTH_TOKEN', $html, 'a placeholder, as nothing has been created yet' );
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

	public function test_a_password_just_made_is_in_the_examples() {
		$user = $this->user_with_role( 'administrator' );

		$tools                     = new \Groundhogg\Admin\Tools\Tools_Page();
		$tools->new_agent_password = Connection::create_application_password( $user, 'Claude' );

		ob_start();
		$tools->mcp_view();
		$html = ob_get_clean();

		$this->assertStringContainsString( $tools->new_agent_password['token'], $html );
		$this->assertStringNotContainsString( 'YOUR_AUTH_TOKEN', $html );
	}
}
