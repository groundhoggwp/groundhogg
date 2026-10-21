<?php

namespace Groundhogg\Admin\Tools;

use Groundhogg\Mcp\Connection;
use function Groundhogg\action_input;
use function Groundhogg\html;
use function Groundhogg\is_white_labeled;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * The AI agent tab: gets the MCP Adapter plugin installed, makes the application password an agent signs in with, and
 * shows how to add the connection to an AI client. See Groundhogg\Mcp\Connection.
 *
 * Included by Tools_Page::mcp_view(), so $this is the Tools page.
 */

$new        = $this->new_agent_password;
$user       = wp_get_current_user();
$active     = Connection::is_active();
$installed  = Connection::is_installed();
$endpoint   = Connection::endpoint();
$server     = Connection::server_name();
$passwords  = Connection::application_passwords();
$can_use_pw = Connection::can_use_application_passwords( $user );

// what goes into the examples: the real thing straight after it was made, a placeholder after that
$token    = $new ? $new['token'] : 'YOUR_AUTH_TOKEN';
$username = $new ? $new['username'] : $user->user_login;
$password = $new ? $new['password'] : 'YOUR_APPLICATION_PASSWORD';

$json = function ( array $config ): string {
	return wp_json_encode( $config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
};

$examples = [
	[
		'title'       => esc_html__( 'Custom connector (ChatGPT, Claude Desktop and others)', 'groundhogg' ),
		'description' => esc_html__( 'In your client, add a custom connector, and enter these details. Where it asks for a custom header, use the Authorization header. Support for custom headers depends on your client and plan, if you do not see it use one of the other methods.', 'groundhogg' ),
		'code'        => sprintf(
			"Name: %s\nURL: %s\nHeader name: Authorization\nHeader value: Basic %s",
			$server,
			$endpoint,
			$token
		),
	],
	[
		'title'       => esc_html__( 'Claude Code', 'groundhogg' ),
		'description' => esc_html__( 'Run this command in your terminal.', 'groundhogg' ),
		'code'        => sprintf( 'claude mcp add --transport http %s %s --header "Authorization: Basic %s"', $server, $endpoint, $token ),
	],
	[
		'title'       => esc_html__( 'Codex', 'groundhogg' ),
		'description' => esc_html__( 'Add this to your Codex configuration file, config.toml in the .codex folder of your home directory.', 'groundhogg' ),
		'code'        => sprintf(
			"[mcp_servers.%s]\nurl = \"%s\"\nhttp_headers = { Authorization = \"Basic %s\" }",
			$server,
			$endpoint,
			$token
		),
	],
	[
		'title'       => esc_html__( 'Cursor', 'groundhogg' ),
		'description' => esc_html__( 'Add this to mcp.json, in the .cursor folder of your home directory or of your project.', 'groundhogg' ),
		'code'        => $json( [
			'mcpServers' => [
				$server => [
					'url'     => $endpoint,
					'headers' => [ 'Authorization' => 'Basic ' . $token ],
				],
			],
		] ),
	],
	[
		'title'       => esc_html__( 'Gemini CLI', 'groundhogg' ),
		'description' => esc_html__( 'Run this command in your terminal.', 'groundhogg' ),
		'code'        => sprintf( 'gemini mcp add --transport http --header "Authorization: Basic %s" %s %s', $token, $server, $endpoint ),
	],
	[
		'title'       => esc_html__( 'VS Code (GitHub Copilot)', 'groundhogg' ),
		'description' => esc_html__( 'Add this to mcp.json, in the .vscode folder of your project, or use the MCP: Open User Configuration command.', 'groundhogg' ),
		'code'        => $json( [
			'servers' => [
				$server => [
					'type'    => 'http',
					'url'     => $endpoint,
					'headers' => [ 'Authorization' => 'Basic ' . $token ],
				],
			],
		] ),
	],
	[
		'title'       => esc_html__( 'Other clients that only run local programs', 'groundhogg' ),
		'description' => esc_html__( 'This uses a small helper that needs Node.js installed. Add this to the MCP settings file of your client, the file is named differently in each one. Claude Desktop, Windsurf and Zed can use it.', 'groundhogg' ),
		'code'        => $json( [
			'mcpServers' => [
				$server => [
					'command' => 'npx',
					'args'    => [ '-y', '@automattic/mcp-wordpress-remote@latest' ],
					'env'     => [
						'WP_API_URL'      => $endpoint,
						'WP_API_USERNAME' => $username,
						'WP_API_PASSWORD' => $password,
					],
				],
			],
		] ),
	],
];

?>
<style>
    .gh-mcp { max-width: 960px; }
    .gh-mcp .gh-mcp-step { margin-bottom: 28px; }
    .gh-mcp h2, .gh-mcp h3 { margin-top: 0; }
    .gh-mcp .gh-mcp-status { display: flex; align-items: center; gap: 8px; margin: 12px 0; }
    .gh-mcp textarea.code { font-family: monospace; white-space: pre; overflow: auto; width: 100%; }
    .gh-mcp details { margin: 10px 0; }
    .gh-mcp summary { cursor: pointer; font-weight: 500; padding: 4px 0; }
    .gh-mcp .gh-input-group { max-width: 600px; }
</style>
<p></p>
<div class="gh-panel">
    <div class="inside gh-mcp">
        <h2><?php esc_html_e( 'Work with your site from an AI assistant', 'groundhogg' ); ?></h2>
        <p><?php esc_html_e( 'Connect an AI assistant such as ChatGPT, Claude, Gemini or Cursor to your site, and ask it to look up contacts, write and send emails, build flows and more, in plain language. It connects over MCP, the open standard AI clients use to talk to other software.', 'groundhogg' ); ?></p>
        <p class="description"><?php esc_html_e( 'The assistant signs in as you, so it can do what you can do on this site and nothing more. Only connect assistants you trust, and revoke the application password when you are done with one.', 'groundhogg' ); ?></p>

        <!-- 1. the adapter -->
        <div class="gh-mcp-step">
            <h3><?php esc_html_e( '1. Install the MCP Adapter', 'groundhogg' ); ?></h3>
            <p><?php esc_html_e( 'The official MCP Adapter plugin from WordPress.org is what lets AI clients connect to your site.', 'groundhogg' ); ?></p>
			<?php if ( $active ): ?>
                <div class="gh-mcp-status">
                    <span aria-hidden="true">✅</span>
                    <span><?php esc_html_e( 'The MCP Adapter is installed and active.', 'groundhogg' ); ?></span>
                </div>
			<?php elseif ( current_user_can( 'install_plugins' ) ): ?>
                <form method="post" action="<?php echo esc_url( Connection::page_url() ); ?>">
					<?php action_input( 'install_mcp_adapter', true, true ); ?>
                    <button type="submit" class="gh-button primary">
						<?php $installed
							? esc_html_e( 'Activate the MCP Adapter', 'groundhogg' )
							: esc_html_e( 'Install and activate the MCP Adapter', 'groundhogg' ); ?>
                    </button>
                </form>
			<?php else: ?>
                <div class="notice notice-warning inline">
                    <p><?php esc_html_e( 'You are not allowed to install plugins on this site. Ask an administrator who can, or install the MCP Adapter plugin yourself.', 'groundhogg' ); ?></p>
                </div>
			<?php endif; ?>
        </div>

        <!-- 2. the application password -->
        <div class="gh-mcp-step">
            <h3><?php esc_html_e( '2. Create an application password', 'groundhogg' ); ?></h3>
            <p><?php esc_html_e( 'This is what the assistant signs in with. It is separate from your real password and can be revoked at any time without changing it.', 'groundhogg' ); ?></p>
			<?php if ( ! $can_use_pw ): ?>
                <div class="notice notice-warning inline">
                    <p><?php esc_html_e( 'Application passwords are not available on this site. WordPress requires a secure (HTTPS) connection to use them, and they may have been disabled by another plugin.', 'groundhogg' ); ?></p>
                </div>
			<?php else: ?>
				<?php if ( $new ): ?>
                    <div class="gh-panel outlined" style="margin-bottom: 20px;">
                        <div class="inside">
                            <p style="margin-top: 0;">
                                <b><?php
									/* translators: %s: the username the application password belongs to */
									echo esc_html( sprintf( __( 'Application password created for %s.', 'groundhogg' ), $new['username'] ) ); ?></b>
								<?php esc_html_e( 'Copy it now, it will not be shown again. The examples below already include it.', 'groundhogg' ); ?>
                            </p>
							<?php
							html()->start_form_table();

							html()->start_row();
							html()->th( esc_html__( 'Authentication token', 'groundhogg' ) );
							html()->td( [
								html()->input( [
									'class'    => 'code input large-text',
									'readonly' => true,
									'value'    => $new['token'],
									'onfocus'  => 'this.select()',
								] ),
								html()->description( esc_html__( 'The username and application password encoded for Basic authentication.', 'groundhogg' ) ),
							] );
							html()->end_row();

							html()->start_row();
							html()->th( esc_html__( 'Username', 'groundhogg' ) );
							html()->td( html()->input( [
								'class'    => 'code input regular-text',
								'readonly' => true,
								'value'    => $new['username'],
								'onfocus'  => 'this.select()',
							] ) );
							html()->end_row();

							html()->start_row();
							html()->th( esc_html__( 'Application password', 'groundhogg' ) );
							html()->td( html()->input( [
								'class'    => 'code input regular-text',
								'readonly' => true,
								'value'    => $new['password'],
								'onfocus'  => 'this.select()',
							] ) );
							html()->end_row();

							html()->end_form_table();
							?>
                        </div>
                    </div>
				<?php elseif ( ! empty( $passwords ) ): ?>
                    <div class="gh-mcp-status">
                        <span aria-hidden="true">✅</span>
                        <span><?php
							echo esc_html( sprintf(
							/* translators: %s: the names of the application passwords */
								_n( 'You have already created an application password for an assistant: %s.', 'You have already created application passwords for assistants: %s.', count( $passwords ), 'groundhogg' ),
								implode( ', ', wp_list_pluck( $passwords, 'name' ) )
							) ); ?></span>
                    </div>
                    <p class="description"><?php esc_html_e( 'A password can not be shown again once it is created. Create another to see the examples below with a password in them.', 'groundhogg' ); ?></p>
				<?php endif; ?>

                <form method="post" action="<?php echo esc_url( Connection::page_url() ); ?>">
					<?php action_input( 'create_agent_password', true, true ); ?>
                    <div class="gh-input-group">
						<?php html( [
							html()->input( [
								'name'        => 'agent_name',
								'id'          => 'agent_name',
								'placeholder' => esc_attr__( 'Name, e.g. My AI assistant', 'groundhogg' ),
								'required'    => true,
							] ),
							html()->button( [
								'class' => 'gh-button primary',
								'text'  => esc_html__( 'Create application password', 'groundhogg' ),
								'type'  => 'submit',
							] ),
						] ); ?>
                    </div>
                </form>
                <p class="description">
					<?php
					printf(
					/* translators: %s: link to the user profile */
						esc_html__( 'To see or revoke application passwords, visit the %s.', 'groundhogg' ),
						'<a href="' . esc_url( admin_url( 'profile.php#application-passwords-section' ) ) . '">' . esc_html__( 'application passwords section of your profile', 'groundhogg' ) . '</a>'
					); ?>
                </p>
			<?php endif; ?>
        </div>

        <!-- 3. the client -->
        <div class="gh-mcp-step">
            <h3><?php esc_html_e( '3. Add the connection to your AI client', 'groundhogg' ); ?></h3>
            <p><?php esc_html_e( 'Your site is reached at this address:', 'groundhogg' ); ?></p>
			<?php html( [
				html()->input( [
					'class'    => 'code input large-text',
					'readonly' => true,
					'value'    => $endpoint,
					'onfocus'  => 'this.select()',
				] )
			] ); ?>
            <p><?php esc_html_e( 'Clients ask for it in different ways, so pick yours:', 'groundhogg' ); ?></p>
			<?php foreach ( $examples as $i => $example ): ?>
                <details <?php echo $i === 0 ? 'open' : ''; ?>>
                    <summary><?php echo esc_html( $example['title'] ); ?></summary>
                    <p class="description"><?php echo esc_html( $example['description'] ); ?></p>
                    <textarea class="code" readonly rows="<?php echo esc_attr( min( 14, substr_count( $example['code'], "\n" ) + 2 ) ); ?>" onfocus="this.select()"><?php echo esc_textarea( $example['code'] ); ?></textarea>
                </details>
			<?php endforeach; ?>
			<?php if ( ! $new ): ?>
                <p class="description">
					<?php esc_html_e( 'Replace YOUR_AUTH_TOKEN and YOUR_APPLICATION_PASSWORD with the details from step 2.', 'groundhogg' ); ?>
                </p>
			<?php endif; ?>
            <p><?php esc_html_e( 'Restart your client afterwards if it does not pick the new connection up. Then try asking it, "What can you do with my site?"', 'groundhogg' ); ?></p>
			<?php if ( ! is_white_labeled() ): ?>
                <p>
                    <a href="https://groundhogg.io/doc/working-with-the-abilities-api-mcp/" target="_blank" rel="noopener"><?php esc_html_e( 'Read more about working with AI agents', 'groundhogg' ); ?></a>
                </p>
			<?php endif; ?>
        </div>
    </div>
</div>
