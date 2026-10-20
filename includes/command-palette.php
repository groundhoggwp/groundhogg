<?php

namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds Groundhogg to the WordPress command palette, which is available throughout the admin from WP 7.0.
 *
 * Commands are collected on the server with the `groundhogg/command_palette/commands` filter, where each one is
 * an array with a `name`, a `label`, and either a `url` to go to or an `action` to run, plus optionally `args` (passed
 * to the action), `keywords` (strings that should also match) and `category` (`view`, `command`, `edit` or `action`,
 * defaults to `view`).
 *
 * Actions are functions added to `GroundhoggCommands.actions` in JS, they receive the `args` of the command.
 *
 * Commands that cannot be known up front, such as searching contacts, are registered in JS using the `core/commands` store.
 */
class Command_Palette {

	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
	}

	/**
	 * The commands available to the current user
	 *
	 * @return array[]
	 */
	public function get_commands() {

		/**
		 * Filter the commands added to the command palette
		 *
		 * @param array[] $commands
		 */
		$commands = apply_filters( 'groundhogg/command_palette/commands', [] );

		$normalized = [];

		foreach ( $commands as $command ) {

			if ( empty( $command['name'] ) || empty( $command['label'] ) || empty( $command['url'] ) && empty( $command['action'] ) ) {
				continue;
			}

			$normalized[] = [
				'name'     => (string) $command['name'],
				'label'    => (string) $command['label'],
				'url'      => esc_url_raw( get_array_var( $command, 'url', '' ) ),
				'action'   => (string) get_array_var( $command, 'action', '' ),
				'args'     => (object) get_array_var( $command, 'args', [] ),
				'category' => get_array_var( $command, 'category', 'view' ),
				'keywords' => array_values( array_filter( array_map( 'strval', (array) get_array_var( $command, 'keywords', [] ) ) ) ),
			];
		}

		return $normalized;
	}

	public function enqueue() {

		// The command palette is not in the admin before WP 7.0, nothing to register with
		if ( ! function_exists( 'wp_enqueue_command_palette_assets' ) ) {
			return;
		}

		$commands = $this->get_commands();

		if ( empty( $commands ) ) {
			return;
		}

		$dot_min = Plugin::instance()->scripts->is_script_debug_enabled() ? '' : '.min';

		wp_enqueue_script( 'groundhogg-admin-commands', GROUNDHOGG_ASSETS_URL . 'js/admin/features/command-palette' . $dot_min . '.js', [
			'wp-commands',
			'wp-data',
		], GROUNDHOGG_VERSION, true );

		wp_add_inline_script( 'groundhogg-admin-commands', 'var GroundhoggCommands = ' . wp_json_encode( [
				'commands' => $commands,
			], JSON_HEX_TAG | JSON_UNESCAPED_SLASHES ), 'before' );
	}
}
