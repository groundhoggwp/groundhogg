<?php

namespace Groundhogg\Admin\AI;

use Groundhogg\Admin\Card_Sections;
use Groundhogg\Admin\Tabbed_Admin_Page;
use Groundhogg\AI\AI;
use Groundhogg\AI\Job;
use Groundhogg\Mcp\Build_With_AI;
use Groundhogg\Mcp\Connection;
use WP_Error;
use function Groundhogg\get_post_var;
use function Groundhogg\get_url_var;
use function Groundhogg\html;
use function Groundhogg\is_option_enabled;
use function Groundhogg\verify_admin_ajax_nonce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Everything AI in one place. There are two ways in, and neither needs the other:
 *
 *  - the AI assistant tab connects an assistant people already use, like ChatGPT or Claude, over MCP. It runs in their
 *    own app on their own subscription, and works in the CRM as them. See Groundhogg\Mcp\Connection.
 *  - the AI features tab is for the buttons that ask an AI provider for one thing, like alt text, on the key the site
 *    has in Settings › Connectors (WordPress 7.0+). See Groundhogg\AI\AI.
 *
 * The overview says where each stands and what to do next. The assistant tab used to be in Tools and the features
 * settings in Settings, links to both still work, see redirect_old_tabs().
 */
class AI_Page extends Tabbed_Admin_Page {

	const OVERVIEW = 'overview';

	public function __construct() {
		parent::__construct();

		add_action( 'admin_init', [ $this, 'redirect_old_tabs' ] );
	}

	/**
	 * Send links to where these tabs used to be, in Tools and Settings, here
	 *
	 * @return void
	 */
	public function redirect_old_tabs() {

		if ( wp_doing_ajax() || strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) !== 'GET' ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- only compared
			return;
		}

		$page = get_url_var( 'page' );
		$tab  = get_url_var( 'tab' );

		if ( $page === 'gh_tools' && $tab === Connection::TAB ) {
			wp_safe_redirect( Connection::page_url() );
			exit;
		}

		if ( $page === 'gh_settings' && $tab === 'ai' ) {
			wp_safe_redirect( AI::page_url() );
			exit;
		}
	}

	public function get_slug() {
		return AI::PAGE;
	}

	public function get_name() {
		return _x( 'AI', 'page_title', 'groundhogg' );
	}

	protected function get_title() {
		return _x( 'AI', 'page_title', 'groundhogg' );
	}

	/**
	 * Anyone who works with contacts can connect an assistant of their own, the AI features settings are for
	 * administrators, see get_tabs()
	 *
	 * @return string
	 */
	public function get_cap() {
		return Connection::is_supported() || AI::is_supported() ? 'view_contacts' : 'do_not_allow';
	}

	public function get_item_type() {
		return null;
	}

	/**
	 * Just before Tools
	 *
	 * @return int
	 */
	public function get_priority() {
		return 104;
	}

	protected function get_tabs() {

		$tabs = [
			[
				'name' => __( 'Overview', 'groundhogg' ),
				'slug' => self::OVERVIEW,
				'cap'  => 'view_contacts',
			],
			// needs the Abilities API, which arrived with WordPress 6.9. The steps that change the site check for more,
			// see Connection::can_connect()
			[
				'name' => __( 'AI assistant', 'groundhogg' ),
				'slug' => Connection::TAB,
				'cap'  => Connection::is_supported() ? 'view_contacts' : 'do_not_allow',
			],
			// needs the AI client, which arrived with WordPress 7.0
			[
				'name' => __( 'AI features', 'groundhogg' ),
				'slug' => AI::TAB,
				'cap'  => AI::is_supported() ? 'manage_options' : 'do_not_allow',
			],
		];

		return apply_filters( 'groundhogg/admin/ai/tabs', $tabs );
	}

	public function scripts() {
		wp_enqueue_style( 'groundhogg-admin-element' );

		// what the assistant can build and look into once it's connected, see admin/ai/assistant.php
		if ( $this->get_current_tab() === Connection::TAB ) {
			Build_With_AI::enqueue( 'strategy', [], [ 'email', 'flow', 'setup', 'troubleshoot' ] );
		}
	}

	public function help() {
	}

	protected function add_ajax_actions() {
		add_action( 'wp_ajax_gh_mcp_step', [ $this, 'ajax_mcp_step' ] );
		add_action( 'wp_ajax_gh_mcp_view', [ $this, 'ajax_mcp_view' ] );
	}

	protected function add_additional_actions() {
	}

	public function view() {
	}

	/* ---- Overview ---------------------------------------------------------------------------------------------- */

	/**
	 * Where each way of using AI stands, and the one thing to do next for each
	 *
	 * @return void
	 */
	public function overview_view() {
		?>
        <div class="gh-ai-overview">
            <p class="gh-ai-overview-intro"><?php esc_html_e( 'There are two ways to use AI here. They work on their own, so you can use either one, or both.', 'groundhogg' ); ?></p>
            <div class="gh-ai-overview-cards">
				<?php
				if ( Connection::can_connect() ) {
					$this->assistant_card();
				}

				$this->features_card();
				?>
            </div>
        </div>
		<?php
	}

	/**
	 * One of the two cards on the overview
	 *
	 * @param string $title
	 * @param string $description what it is
	 * @param string $pill        where it stands, a word or two
	 * @param string $color       of the pill: green, orange or colorless
	 * @param string $status      what that means, a sentence
	 * @param array  $actions     url => label, the first is the main one
	 *
	 * @return void
	 */
	protected function card( string $title, string $description, string $pill, string $color, string $status, array $actions = [] ) {
		?>
        <div class="gh-panel gh-ai-overview-card">
            <div class="inside">
                <h2><?php echo esc_html( $title ); ?> <span class="pill <?php echo esc_attr( $color ); ?>"><?php echo esc_html( $pill ); ?></span></h2>
                <p><?php echo esc_html( $description ); ?></p>
                <p class="gh-ai-overview-status"><?php echo esc_html( $status ); ?></p>
				<?php if ( ! empty( $actions ) ): ?>
                    <p class="gh-ai-overview-actions">
						<?php
						$first = true;
						foreach ( $actions as $url => $label ) {
							?><a class="gh-button <?php echo $first ? 'primary' : 'secondary'; ?>" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a> <?php
							$first = false;
						}
						?>
                    </p>
				<?php endif; ?>
            </div>
        </div>
		<?php
	}

	protected function assistant_card() {

		$title       = __( 'AI assistant', 'groundhogg' );
		$description = __( 'Connect an assistant you already use, like ChatGPT, Claude, Gemini or Cursor. It works in your CRM as you, building flows and emails, looking into your reports and fixing problems, on your own AI subscription.', 'groundhogg' );

		if ( Connection::is_connected() ) {
			$this->card( $title, $description, __( 'Connected', 'groundhogg' ), 'green', sprintf(
			/* translators: %s: how long ago, like "5 minutes" */
				__( 'Your assistant last worked here %s ago.', 'groundhogg' ),
				human_time_diff( Connection::last_seen() )
			), [
				Connection::page_url() => __( 'Put it to work', 'groundhogg' ),
			] );

			return;
		}

		if ( Connection::is_served() ) {
			$this->card( $title, $description, __( 'Not connected yet', 'groundhogg' ), 'orange', __( 'This site is ready for an assistant. Add the connection to your AI app to finish.', 'groundhogg' ), [
				Connection::page_url() => __( 'Finish connecting', 'groundhogg' ),
			] );

			return;
		}

		$this->card( $title, $description, __( 'Not set up', 'groundhogg' ), 'colorless', __( 'It takes a few minutes: a plugin, a password for your assistant, and a connection in your AI app.', 'groundhogg' ), [
			Connection::page_url() => __( 'Connect an assistant', 'groundhogg' ),
		] );
	}

	protected function features_card() {

		$title       = __( 'AI features', 'groundhogg' );
		$description = __( 'Buttons that do one job for you, like writing alt text for an image in an email. They use an AI provider key added to this site, and each request is billed to that key.', 'groundhogg' );
		$admin       = current_user_can( 'manage_options' );
		$settings    = $admin ? [ AI::page_url() => __( 'AI features settings', 'groundhogg' ) ] : [];

		switch ( AI::status() ) {

			case 'unsupported':
				$this->card( $title, $description, __( 'Not available', 'groundhogg' ), 'colorless', __( 'AI features need WordPress 7.0 or newer, with AI allowed on the site.', 'groundhogg' ) );
				break;

			case 'disabled':
				$this->card( $title, $description, __( 'Turned off', 'groundhogg' ), 'colorless', $admin
					? __( 'AI features are turned off. You can turn them back on in the settings.', 'groundhogg' )
					: __( 'An administrator turned AI features off.', 'groundhogg' ), $settings );
				break;

			case 'no_provider':
				$this->card( $title, $description, __( 'Needs a key', 'groundhogg' ), 'orange', $admin
					? __( 'Add a key for an AI provider in Settings › Connectors, and AI features turn on.', 'groundhogg' )
					: __( 'An administrator needs to add a key for an AI provider first.', 'groundhogg' ), $admin ? [
						admin_url( 'options-connectors.php' ) => __( 'Add a key', 'groundhogg' ),
					] + $settings : [] );
				break;

			case 'limit_reached':
				$this->card( $title, $description, __( 'Paused', 'groundhogg' ), 'orange', __( 'This month\'s limit is used up. AI features start again on the first of next month.', 'groundhogg' ), $settings );
				break;

			default:
				$this->card( $title, $description, __( 'Ready', 'groundhogg' ), 'green', AI::can_generate( Job::IMAGE )
					? __( 'The providers on this site can write text and make images.', 'groundhogg' )
					: __( 'The providers on this site can write text.', 'groundhogg' ), $settings );
				break;
		}
	}

	/* ---- AI features ------------------------------------------------------------------------------------------- */

	/**
	 * Which providers can do what, the two settings, and this month's use
	 *
	 * @return void
	 */
	public function features_view() {

		// a key may have been added since, finding out is what this tab is for
		AI::forget_providers();

		$ready = function ( bool $can ) {
			echo $can // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped below
				? '<span class="pill green">' . esc_html__( 'Ready', 'groundhogg' ) . '</span>'
				: '<span class="pill colorless">' . esc_html__( 'No provider', 'groundhogg' ) . '</span>';
		};

		$limit = AI::get_limit();

		settings_errors();

		?>
        <div class="gh-settings-sections">
			<?php

			Card_Sections::section_start( __( 'AI providers', 'groundhogg' ), __( 'AI features use the provider keys this site has in Settings › Connectors. Each request is billed to that key.', 'groundhogg' ) );

			Card_Sections::row( __( 'Writing text', 'groundhogg' ), '', function () use ( $ready ) {
				$ready( AI::can_generate( Job::TEXT ) );
			}, __( 'Alt text, and the features coming next.', 'groundhogg' ) );

			Card_Sections::row( __( 'Making images', 'groundhogg' ), '', function () use ( $ready ) {
				$ready( AI::can_generate( Job::IMAGE ) );
			} );

			Card_Sections::row( __( 'Keys', 'groundhogg' ), '', function () {
				?><a class="gh-button secondary" href="<?php echo esc_url( admin_url( 'options-connectors.php' ) ); ?>"><?php esc_html_e( 'Settings › Connectors', 'groundhogg' ); ?></a><?php
			}, __( 'Where WordPress keeps the keys for AI providers, for every plugin on the site.', 'groundhogg' ) );

			Card_Sections::section_end();

			?>
            <form method="post" action="options.php">
				<?php

				settings_fields( AI::SETTINGS_GROUP );

				Card_Sections::section_start( __( 'Settings', 'groundhogg' ) );

				Card_Sections::row( __( 'Turn off AI features', 'groundhogg' ), AI::DISABLED_OPTION, function () {
					echo html()->checkbox( [ // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built by html()
						'label'   => __( 'Turn off', 'groundhogg' ),
						'name'    => AI::DISABLED_OPTION,
						'id'      => AI::DISABLED_OPTION,
						'value'   => 'on',
						'checked' => is_option_enabled( AI::DISABLED_OPTION ),
					] );
				}, sprintf(
				/* translators: %s: link to the AI assistant tab */
					__( 'Hide every feature that asks an AI provider for something on this site\'s key. An %s people connect themselves keeps working.', 'groundhogg' ),
					'<a href="' . esc_url( Connection::page_url() ) . '">' . esc_html__( 'AI assistant', 'groundhogg' ) . '</a>'
				) );

				Card_Sections::row( __( 'Monthly limit', 'groundhogg' ), AI::LIMIT_OPTION, function () use ( $limit ) {
					echo html()->number( [ // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built by html()
						'name'        => AI::LIMIT_OPTION,
						'id'          => AI::LIMIT_OPTION,
						'value'       => $limit ?: '',
						'min'         => 0,
						'class'       => 'input',
						'placeholder' => __( 'No limit', 'groundhogg' ),
					] );
				}, __( 'The most AI requests a month, for everyone together. When they\'re used up, AI features pause until the first of the next month. Leave it empty for no limit.', 'groundhogg' ) );

				Card_Sections::row( '', '', function () {
					?><button type="submit" class="gh-button primary"><?php esc_html_e( 'Save Changes', 'groundhogg' ); ?></button><?php
				} );

				Card_Sections::section_end();

				?>
            </form>
			<?php

			Card_Sections::section_start( __( 'This month', 'groundhogg' ), __( 'Since the first of the month, for everyone together.', 'groundhogg' ) );

			Card_Sections::row( __( 'Requests', 'groundhogg' ), '', function () use ( $limit ) {
				$requests = AI::requests_this_month();

				echo esc_html( $limit
					/* translators: 1: requests made this month, 2: the monthly limit */
					? sprintf( __( '%1$s of %2$s', 'groundhogg' ), number_format_i18n( $requests ), number_format_i18n( $limit ) )
					: number_format_i18n( $requests ) );

				if ( AI::limit_reached() ) {
					echo ' <span class="pill orange">' . esc_html__( 'Limit used up', 'groundhogg' ) . '</span>';
				}
			} );

			Card_Sections::row( __( 'Tokens', 'groundhogg' ), '', function () {
				echo esc_html( number_format_i18n( AI::tokens_this_month() ) );
			}, __( 'What providers bill by. Your provider\'s dashboard has the cost.', 'groundhogg' ) );

			Card_Sections::section_end();

			?>
            <p class="description"><?php
				printf(
				/* translators: %s: link to the AI assistant tab */
					esc_html__( 'Looking to have an assistant like ChatGPT or Claude work in your CRM? That\'s the %s tab.', 'groundhogg' ),
					'<a href="' . esc_url( Connection::page_url() ) . '">' . esc_html__( 'AI assistant', 'groundhogg' ) . '</a>'
				);
				?></p>
        </div>
		<?php
	}

	/* ---- AI assistant ------------------------------------------------------------------------------------------ */

	/**
	 * How to connect an AI assistant
	 */
	public function mcp_view() {
		include __DIR__ . '/assistant.php';
	}

	/**
	 * The AI assistant tab on its own, so the "Build with AI" popup can walk someone through connecting an assistant
	 * without sending them off to another page. See assets/js/admin/build-with-ai.js.
	 */
	public function ajax_mcp_view() {

		if ( ! verify_admin_ajax_nonce() ) {
			wp_send_json_error( [ 'message' => __( 'This page has expired, please reload it and try again.', 'groundhogg' ) ] );
		}

		if ( ! Connection::can_connect() ) {
			wp_send_json_error( [ 'message' => __( 'You are not allowed to do that.', 'groundhogg' ) ] );
		}

		ob_start();
		$this->mcp_view();

		wp_send_json_success( [ 'html' => ob_get_clean() ] );
	}

	/**
	 * The password an AI agent signs in with, kept in memory so the page can show it once
	 *
	 * @var array|null
	 */
	public $new_agent_password = null;

	/**
	 * The steps of the AI assistant tab. Each one is a form that works on its own with a page load, and the page also
	 * sends them in the background, see ajax_mcp_step().
	 */
	const MCP_STEPS = [ 'install_mcp_adapter', 'install_mcp_connector', 'create_agent_password' ];

	/**
	 * Do one of the steps of the AI assistant tab
	 *
	 * @param string $step one of MCP_STEPS
	 * @param string $name what to call the password, when the step makes one. Left empty, a name is picked
	 *
	 * @return string|WP_Error what was done, in words, or what went wrong
	 */
	protected function do_mcp_step( string $step, string $name = '' ) {

		if ( ! Connection::can_connect() || ! in_array( $step, self::MCP_STEPS, true ) ) {
			return new WP_Error( 'error', __( 'You are not allowed to do that.', 'groundhogg' ) );
		}

		// the plugin steps change the whole site, so they're for people who can manage its plugins
		if ( $step !== 'create_agent_password' && ! Connection::can_install( $step === 'install_mcp_adapter' ? Connection::PLUGIN_SLUG : Connection::CONNECTOR_PLUGIN_SLUG ) ) {
			return new WP_Error( 'error', __( 'You are not allowed to install or activate plugins on this site. Ask an administrator who can.', 'groundhogg' ) );
		}

		switch ( $step ) {

			case 'install_mcp_adapter':

				$result = Connection::install();

				return is_wp_error( $result ) ? $result : __( 'The MCP Adapter is installed and active.', 'groundhogg' );

			case 'install_mcp_connector':

				if ( ! Connection::can_use_connector_plugin() ) {
					return new WP_Error( 'error', sprintf(
					/* translators: %s: the PHP version the plugin needs */
						__( 'That plugin needs PHP %s or newer, which this site does not have.', 'groundhogg' ),
						Connection::CONNECTOR_MIN_PHP
					) );
				}

				$result = Connection::install( Connection::CONNECTOR_PLUGIN_SLUG );

				return is_wp_error( $result ) ? $result : __( 'The plugin is installed and active.', 'groundhogg' );

			default: // create_agent_password

				// always the person asking, the agent can then do what they can do and nothing more
				$name    = trim( sanitize_text_field( $name ) );
				$created = Connection::create_application_password( get_current_user_id(), $name !== '' ? $name : Connection::next_name() );

				if ( is_wp_error( $created ) ) {
					return $created;
				}

				// kept in memory so the page can show it, once
				$this->new_agent_password = $created;

				return __( 'Application password created!', 'groundhogg' );
		}
	}

	/**
	 * Install and activate the MCP adapter plugin
	 *
	 * @return true|WP_Error
	 */
	public function process_install_mcp_adapter() {
		return $this->process_mcp_step( 'install_mcp_adapter' );
	}

	/**
	 * Install and activate the plugin that adds sign-in for the Claude and ChatGPT apps
	 *
	 * @return true|WP_Error
	 */
	public function process_install_mcp_connector() {
		return $this->process_mcp_step( 'install_mcp_connector' );
	}

	/**
	 * Make the application password an AI agent signs in with
	 *
	 * @return true|WP_Error
	 */
	public function process_create_agent_password() {
		return $this->process_mcp_step( 'create_agent_password' );
	}

	/**
	 * A step sent as a form with a page load: the result is a notice
	 *
	 * @param string $step
	 *
	 * @return true|WP_Error
	 */
	protected function process_mcp_step( string $step ) {

		if ( ! Connection::can_connect() ) {
			$this->wp_die_no_access();
		}

		$result = $this->do_mcp_step( $step, (string) get_post_var( 'agent_name' ) );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$this->add_notice( 'success', $result );

		return true;
	}

	/**
	 * A step sent in the background: answers with the tab as it is now, so the page can swap it in without a reload.
	 * No notice is queued, as it would show up on the next page the person opens.
	 */
	public function ajax_mcp_step() {

		if ( ! verify_admin_ajax_nonce() ) {
			wp_send_json_error( [ 'message' => __( 'This page has expired, please reload it and try again.', 'groundhogg' ) ] );
		}

		$result = $this->do_mcp_step( sanitize_key( (string) get_post_var( 'step' ) ), (string) get_post_var( 'agent_name' ) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		ob_start();
		$this->mcp_view();

		wp_send_json_success( [
			'message' => $result,
			'html'    => ob_get_clean(),
		] );
	}
}
