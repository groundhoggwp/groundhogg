<?php

namespace Groundhogg\Admin\Tools;

use Groundhogg\Mcp\Connection;
use function Groundhogg\action_input;
use function Groundhogg\is_white_labeled;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * The AI agent tab: gets the MCP Adapter plugin installed, makes the application password an agent signs in with, and
 * shows how to add the connection to an AI client. See Groundhogg\Mcp\Connection.
 *
 * It shows one step at a time, the one that is next, so there is only ever one thing to do. The steps before it
 * collapse to a line and the ones after it wait as titles.
 *
 * Included by Tools_Page::mcp_view(), so $this is the Tools page.
 */

// the password just made, WordPress can show it only this once
$agent      = $this->new_agent_password;
$user      = wp_get_current_user();
$active     = Connection::is_active();
$served     = $active || Connection::is_served();
$endpoint   = Connection::endpoint();
$server     = Connection::server_name();
$passwords  = Connection::application_passwords();
$can_use_pw = Connection::can_use_application_passwords( $user );

// the optional plugin that adds sign-in for the ChatGPT and Claude apps, offered when this site can run it
$connector_slug      = Connection::CONNECTOR_PLUGIN_SLUG;
$connector_installed = Connection::is_installed( $connector_slug );
$connector_active    = Connection::is_active( $connector_slug );

// the plugin steps change the whole site, so they're only offered to people who can manage its plugins. Anyone who
// works with contacts can make a password for an assistant of their own, see Connection::can_connect()
$can_install_adapter = Connection::can_install();

// the sign-in box only shows when there's something this person can do with it: turn sign-in on in the plugin's
// settings, or get the plugin running
$show_connector = Connection::can_use_connector_plugin() && ( $connector_active
		? current_user_can( 'manage_options' )
		: Connection::can_install( $connector_slug ) );

// where they are: the first step that is not done
$current = ! $served ? 1 : ( ( $agent || ! empty( $passwords ) ) ? 3 : 2 );

$step_state = function ( int $step ) use ( $current ): string {
	return $step < $current ? 'done' : ( $step === $current ? 'current' : 'upcoming' );
};

// what goes into the examples: the real thing while we have it, a placeholder after that
$token    = $agent ? $agent['token'] : 'YOUR_AUTH_TOKEN';
$username = $agent ? $agent['username'] : $user->user_login;
$password = $agent ? $agent['password'] : 'YOUR_APPLICATION_PASSWORD';

$json = function ( array $config ): string {
	return wp_json_encode( $config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
};

$clients = [
	[
		'label'       => esc_html__( 'ChatGPT or Claude app', 'groundhogg' ),
		'description' => esc_html__( 'In the app, open the settings, add a custom connector and fill in these details. Not every app or plan allows a custom header, if yours does not choose another option.', 'groundhogg' ),
		'code'        => sprintf(
			"Name: %s\nURL: %s\nHeader name: Authorization\nHeader value: Basic %s",
			$server,
			$endpoint,
			$token
		),
	],
	[
		'label'       => esc_html__( 'Claude Code', 'groundhogg' ),
		'description' => esc_html__( 'Run this command in your terminal.', 'groundhogg' ),
		'code'        => sprintf( 'claude mcp add --transport http %s %s --header "Authorization: Basic %s"', $server, $endpoint, $token ),
	],
	[
		'label'       => esc_html__( 'Codex', 'groundhogg' ),
		'description' => esc_html__( 'Add this to your Codex configuration file, config.toml in the .codex folder of your home directory.', 'groundhogg' ),
		'code'        => sprintf(
			"[mcp_servers.%s]\nurl = \"%s\"\nhttp_headers = { Authorization = \"Basic %s\" }",
			$server,
			$endpoint,
			$token
		),
	],
	[
		'label'       => esc_html__( 'Cursor', 'groundhogg' ),
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
		'label'       => esc_html__( 'Gemini CLI', 'groundhogg' ),
		'description' => esc_html__( 'Run this command in your terminal.', 'groundhogg' ),
		'code'        => sprintf( 'gemini mcp add --transport http --header "Authorization: Basic %s" %s %s', $token, $server, $endpoint ),
	],
	[
		'label'       => esc_html__( 'VS Code', 'groundhogg' ),
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
		'label'       => esc_html__( 'Something else', 'groundhogg' ),
		'description' => esc_html__( 'For assistants that only run local programs, like Windsurf and Zed. This uses a small helper that needs Node.js installed. Add this to the MCP settings file of your assistant, the file is named differently in each one.', 'groundhogg' ),
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

/**
 * The name of a new password, with one suggested so no one has to think of it. It is how they tell it apart in their
 * profile.
 */
$suggested  = Connection::next_name();
$name_field = function ( string $id ) use ( $suggested ): void {
	?>
    <p>
        <label for="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Name it so you can tell it apart later', 'groundhogg' ); ?></label><br>
        <input type="text" class="regular-text" id="<?php echo esc_attr( $id ); ?>" name="agent_name" value="<?php echo esc_attr( $suggested ); ?>" maxlength="100">
    </p>
	<?php
};

/**
 * The marker beside a step title: a tick when it is done, its number otherwise.
 */
$marker = function ( int $step ) use ( $step_state ): string {
	return sprintf(
		'<span class="gh-mcp-marker" aria-hidden="true">%s</span>',
		$step_state( $step ) === 'done' ? '&#10003;' : (int) $step
	);
};

?>
<style>
    .gh-mcp { max-width: 760px; }
    .gh-mcp h2 { margin-top: 0; }
    .gh-mcp-steps { list-style: none; margin: 24px 0 0; padding: 0; }
    .gh-mcp-step { margin: 0 0 18px; padding: 0 0 0 40px; position: relative; }
    .gh-mcp-step h3 { margin: 0; font-size: 15px; line-height: 28px; }
    .gh-mcp-marker { position: absolute; left: 0; top: 0; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 600; border: 1px solid var(--gh-border-gray, #949494); box-sizing: border-box; }
    .gh-mcp-step.is-current .gh-mcp-marker { background: var(--gh-primary, #0075ff); border-color: var(--gh-primary, #0075ff); color: #fff; }
    .gh-mcp-step.is-done .gh-mcp-marker { background: #00a32a; border-color: #00a32a; color: #fff; }
    .gh-mcp-step.is-upcoming { opacity: .6; }
    .gh-mcp-step p { margin: 8px 0; }
    .gh-mcp-step .gh-mcp-summary { margin: 0; line-height: 28px; opacity: .75; }
    .gh-mcp-step form { margin: 12px 0; }
    .gh-mcp-body { margin-top: 6px; }
    .gh-mcp-shortcut { margin: 12px 0 4px; padding: 12px 16px; border: 1px solid var(--gh-border-gray, #949494); border-left: 4px solid var(--gh-primary, #0075ff); border-radius: 4px; }
    .gh-mcp-shortcut p { margin: 6px 0; }
    .gh-mcp fieldset { border: 0; margin: 14px 0 0; padding: 0; position: relative; }
    .gh-mcp legend { padding: 0; margin-bottom: 8px; font-weight: 500; }
    /* the choices are radio buttons so that this works without any script, they just aren't drawn */
    .gh-mcp .gh-mcp-radio { position: absolute; opacity: 0; width: 1px; height: 1px; margin: 0; }
    .gh-mcp .gh-mcp-tablist { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
    .gh-mcp .gh-mcp-tablist label { cursor: pointer; padding: 5px 14px; border: 1px solid var(--gh-border-gray, #949494); border-radius: 999px; line-height: 1.5; }
    .gh-mcp .gh-mcp-panel { display: none; }
    .gh-mcp textarea.code { font-family: monospace; white-space: pre; overflow: auto; width: 100%; box-sizing: border-box; }
	<?php foreach ( $clients as $i => $client ): ?>
    #gh-mcp-client-<?php echo (int) $i; ?>:checked ~ .gh-mcp-tablist label[for="gh-mcp-client-<?php echo (int) $i; ?>"] { background: var(--gh-primary, #0075ff); border-color: var(--gh-primary, #0075ff); color: #fff; }
    #gh-mcp-client-<?php echo (int) $i; ?>:focus-visible ~ .gh-mcp-tablist label[for="gh-mcp-client-<?php echo (int) $i; ?>"] { outline: 2px solid var(--gh-primary, #0075ff); outline-offset: 2px; }
    #gh-mcp-client-<?php echo (int) $i; ?>:checked ~ .gh-mcp-panels .gh-mcp-panel-<?php echo (int) $i; ?> { display: block; }
	<?php endforeach; ?>
</style>
<p></p>
<div class="gh-panel">
    <div class="inside gh-mcp" id="gh-mcp" data-nonce="<?php echo esc_attr( wp_create_nonce( 'admin_ajax' ) ); ?>">
        <h2><?php esc_html_e( 'Connect an AI assistant', 'groundhogg' ); ?></h2>
        <p><?php esc_html_e( 'Let an assistant such as ChatGPT, Claude, Gemini or Cursor help with your contacts, emails and flows. It only takes a few minutes, one step at a time.', 'groundhogg' ); ?></p>

        <div id="gh-mcp-status" class="screen-reader-text" role="status" aria-live="polite"></div>
        <div id="gh-mcp-content">
        <ol class="gh-mcp-steps">

            <!-- 1. the adapter -->
            <li class="gh-mcp-step is-<?php echo esc_attr( $step_state( 1 ) ); ?>" <?php echo $current === 1 ? 'aria-current="step"' : ''; ?>>
				<?php echo $marker( 1 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above ?>
				<?php if ( $current === 1 ): ?>
                    <h3><?php esc_html_e( 'Get your site ready', 'groundhogg' ); ?></h3>
                    <div class="gh-mcp-body">
                        <p><?php esc_html_e( 'Assistants talk to your site through the official MCP Adapter plugin from WordPress.org. We can install it for you.', 'groundhogg' ); ?></p>
						<?php if ( $can_install_adapter ): ?>
                            <form method="post" action="<?php echo esc_url( Connection::page_url() ); ?>">
								<?php action_input( 'install_mcp_adapter', true, true ); ?>
                                <button type="submit" class="gh-button primary">
									<?php Connection::is_installed()
										? esc_html_e( 'Activate the MCP Adapter', 'groundhogg' )
										: esc_html_e( 'Install and activate the MCP Adapter', 'groundhogg' ); ?>
                                </button>
                            </form>
						<?php else: ?>
                            <div class="notice notice-warning inline">
                                <p><?php esc_html_e( 'An administrator needs to install and activate the MCP Adapter plugin on this site first. Ask one who can, then come back here to connect your assistant.', 'groundhogg' ); ?></p>
                            </div>
						<?php endif; ?>
                    </div>
				<?php else: ?>
                    <h3><?php esc_html_e( 'Get your site ready', 'groundhogg' ); ?></h3>
					<?php if ( $step_state( 1 ) === 'done' ): ?>
                        <p class="gh-mcp-summary"><?php echo $active
								? esc_html__( 'The MCP Adapter is installed and active.', 'groundhogg' )
								: esc_html__( 'Your site is ready for AI assistants.', 'groundhogg' ); ?></p>
					<?php endif; ?>
				<?php endif; ?>
            </li>

            <!-- 2. the application password -->
            <li class="gh-mcp-step is-<?php echo esc_attr( $step_state( 2 ) ); ?>" <?php echo $current === 2 ? 'aria-current="step"' : ''; ?>>
				<?php echo $marker( 2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above ?>
                <h3><?php esc_html_e( 'Create a password for your assistant', 'groundhogg' ); ?></h3>
				<?php if ( $current === 2 ): ?>
                    <div class="gh-mcp-body">
						<?php if ( ! $can_use_pw ): ?>
                            <div class="notice notice-warning inline">
                                <p><?php esc_html_e( 'Application passwords are not available on this site. WordPress requires a secure (HTTPS) connection to use them, and they may have been disabled by another plugin.', 'groundhogg' ); ?></p>
                            </div>
						<?php else: ?>
                            <p><?php esc_html_e( 'This is what the assistant signs in with. It is separate from your real password, and you can revoke it at any time.', 'groundhogg' ); ?></p>
                            <form method="post" action="<?php echo esc_url( Connection::page_url() ); ?>">
								<?php action_input( 'create_agent_password', true, true ); ?>
								<?php $name_field( 'gh-mcp-name-first' ); ?>
                                <button type="submit" class="gh-button primary"><?php esc_html_e( 'Create password', 'groundhogg' ); ?></button>
                            </form>
                            <p class="description"><?php esc_html_e( 'The assistant signs in as you, so it can do what you can do on this site and nothing more. Only connect assistants you trust.', 'groundhogg' ); ?></p>
						<?php endif; ?>
                    </div>
				<?php elseif ( $step_state( 2 ) === 'done' ): ?>
                    <p class="gh-mcp-summary"><?php esc_html_e( 'Password created.', 'groundhogg' ); ?></p>
				<?php endif; ?>
            </li>

            <!-- 3. the assistant -->
            <li class="gh-mcp-step is-<?php echo esc_attr( $step_state( 3 ) ); ?>" <?php echo $current === 3 ? 'aria-current="step"' : ''; ?>>
				<?php echo $marker( 3 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above ?>
                <h3><?php esc_html_e( 'Connect your assistant', 'groundhogg' ); ?></h3>
				<?php if ( $current === 3 ): ?>
                    <div class="gh-mcp-body">
						<?php if ( $agent ): ?>
                            <div class="notice notice-success inline">
                                <p><?php esc_html_e( 'Your password is already filled in below. It will not be shown again, so finish setting up your assistant before you leave this page.', 'groundhogg' ); ?></p>
                            </div>
						<?php elseif ( $can_use_pw ): ?>
                            <p><?php esc_html_e( 'Create a new password to have the details filled in below.', 'groundhogg' ); ?></p>
                            <form method="post" action="<?php echo esc_url( Connection::page_url() ); ?>">
								<?php action_input( 'create_agent_password', true, true ); ?>
								<?php $name_field( 'gh-mcp-name-another' ); ?>
                                <button type="submit" class="gh-button secondary"><?php esc_html_e( 'Create a new password', 'groundhogg' ); ?></button>
                            </form>
						<?php endif; ?>

						<?php if ( $show_connector ): ?>
                            <div class="gh-mcp-shortcut">
                                <strong><?php esc_html_e( 'Using the ChatGPT or Claude app? There is an easier way.', 'groundhogg' ); ?></strong>
                                <p><?php esc_html_e( 'The free Enable Abilities for MCP plugin lets those apps sign in with a login and approval screen, so there is no password to copy. It also adds abilities for the rest of WordPress.', 'groundhogg' ); ?></p>
								<?php if ( $connector_active ): ?>
                                    <p><a class="gh-button primary" href="<?php echo esc_url( Connection::connector_settings_url() ); ?>"><?php esc_html_e( 'Open its settings to turn on sign-in', 'groundhogg' ); ?></a></p>
								<?php else: ?>
                                    <form method="post" action="<?php echo esc_url( Connection::page_url() ); ?>">
										<?php action_input( 'install_mcp_connector', true, true ); ?>
                                        <button type="submit" class="gh-button primary">
											<?php $connector_installed
												? esc_html_e( 'Activate Enable Abilities for MCP', 'groundhogg' )
												: esc_html_e( 'Install and activate Enable Abilities for MCP', 'groundhogg' ); ?>
                                        </button>
                                    </form>
								<?php endif; ?>
                                <p class="description"><?php esc_html_e( 'Optional. The password steps above and the options below keep working either way.', 'groundhogg' ); ?></p>
                            </div>
						<?php endif; ?>

                        <fieldset>
                            <legend><?php esc_html_e( 'Which assistant do you use?', 'groundhogg' ); ?></legend>
							<?php foreach ( $clients as $i => $client ): ?>
                                <input type="radio" class="gh-mcp-radio" name="gh-mcp-client" id="gh-mcp-client-<?php echo (int) $i; ?>" <?php echo $i === 0 ? 'checked' : ''; ?>>
							<?php endforeach; ?>
                            <div class="gh-mcp-tablist">
								<?php foreach ( $clients as $i => $client ): ?>
                                    <label for="gh-mcp-client-<?php echo (int) $i; ?>"><?php echo esc_html( $client['label'] ); ?></label>
								<?php endforeach; ?>
                            </div>
                            <div class="gh-mcp-panels">
								<?php foreach ( $clients as $i => $client ): ?>
                                    <div class="gh-mcp-panel gh-mcp-panel-<?php echo (int) $i; ?>">
                                        <p class="description"><?php echo esc_html( $client['description'] ); ?></p>
                                        <textarea class="code" readonly rows="<?php echo esc_attr( min( 14, substr_count( $client['code'], "\n" ) + 2 ) ); ?>" onfocus="this.select()"><?php echo esc_textarea( $client['code'] ); ?></textarea>
                                    </div>
								<?php endforeach; ?>
                            </div>
                        </fieldset>

						<?php if ( ! $agent ): ?>
                            <p class="description"><?php esc_html_e( 'Replace YOUR_AUTH_TOKEN and YOUR_APPLICATION_PASSWORD with the details of a password you created.', 'groundhogg' ); ?></p>
						<?php endif; ?>
                        <p><?php esc_html_e( 'Once it is added, restart your assistant if it does not pick it up, then ask it: "What can you do with my site?"', 'groundhogg' ); ?></p>
                        <p class="description">
							<?php
							printf(
							/* translators: %s: link to the user profile */
								esc_html__( 'To see or revoke passwords, visit the %s.', 'groundhogg' ),
								'<a href="' . esc_url( admin_url( 'profile.php#application-passwords-section' ) ) . '">' . esc_html__( 'application passwords section of your profile', 'groundhogg' ) . '</a>'
							);
							if ( ! is_white_labeled() ) {
								echo ' <a href="https://groundhogg.io/doc/working-with-the-abilities-api-mcp/" target="_blank" rel="noopener">' . esc_html__( 'Read more about working with AI assistants', 'groundhogg' ) . '</a>';
							}
							?>
                        </p>
                    </div>
				<?php endif; ?>
            </li>
        </ol>
        </div>
    </div>
</div>
<script>
    /*
     * Each step is a form that works with a page load. With script it is sent in the background instead, and the page
     * is swapped for the one that comes back, so nothing has to reload. Without script, nothing here is needed.
     */
    ( function () {

        var root = document.getElementById( 'gh-mcp' );

        if ( ! root || ! window.fetch || ! window.DOMParser ) {
            return;
        }

        var steps = <?php echo wp_json_encode( Tools_Page::MCP_STEPS ); ?>;
        var text = <?php echo wp_json_encode( [
			// the dots come from the loading-dots class, which animates them
			'working' => __( 'Working', 'groundhogg' ),
			'failed'  => __( 'Something went wrong. Reload the page and try again.', 'groundhogg' ),
		] ); ?>;
        var content = document.getElementById( 'gh-mcp-content' );
        var status = document.getElementById( 'gh-mcp-status' );
        var busy = false;

        var showError = function ( form, message ) {
            var old = content.querySelector( '.gh-mcp-error' );
            if ( old ) {
                old.remove();
            }
            var notice = document.createElement( 'div' );
            notice.className = 'notice notice-error inline gh-mcp-error';
            notice.setAttribute( 'role', 'alert' );
            var p = document.createElement( 'p' );
            p.textContent = message;
            notice.appendChild( p );
            form.parentNode.insertBefore( notice, form );
        };

        content.addEventListener( 'submit', function ( e ) {

            var form = e.target;
            var field = form.querySelector( 'input[name="action"]' );
            var step = field ? field.value : '';

            if ( steps.indexOf( step ) === -1 ) {
                return;
            }

            e.preventDefault();

            if ( busy ) {
                return;
            }

            busy = true;

            var button = form.querySelector( 'button[type="submit"]' );
            var label = button ? button.textContent : '';

            if ( button ) {
                button.disabled = true;
                button.textContent = text.working;
                button.classList.add( 'loading-dots' );
            }

            var body = new URLSearchParams();
            body.append( 'action', 'gh_mcp_step' );
            body.append( 'step', step );

            var name = form.querySelector( 'input[name="agent_name"]' );

            if ( name ) {
                body.append( 'agent_name', name.value );
            }
            body.append( 'gh_admin_ajax_nonce', root.getAttribute( 'data-nonce' ) );

            fetch( window.ajaxurl || <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, {
                method: 'POST',
                credentials: 'same-origin',
                body: body
            } )
                .then( function ( response ) {
                    return response.json();
                } )
                .then( function ( json ) {

                    if ( ! json || ! json.success ) {
                        throw new Error( json && json.data && json.data.message ? json.data.message : text.failed );
                    }

                    var next = new DOMParser().parseFromString( json.data.html, 'text/html' ).getElementById( 'gh-mcp-content' );

                    if ( ! next ) {
                        throw new Error( text.failed );
                    }

                    content.innerHTML = next.innerHTML;
                    status.textContent = json.data.message;

                    var heading = content.querySelector( '[aria-current="step"] h3' );

                    if ( heading ) {
                        heading.setAttribute( 'tabindex', '-1' );
                        heading.focus();
                    }
                } )
                .catch( function ( error ) {
                    if ( button ) {
                        button.disabled = false;
                        button.textContent = label;
                        button.classList.remove( 'loading-dots' );
                    }
                    showError( form, error.message || text.failed );
                } )
                .then( function () {
                    busy = false;
                } );
        } );
    } )();
</script>
