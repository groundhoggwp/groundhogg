<?php

namespace Groundhogg\Api\V4;
use Groundhogg\Plugin;
use Groundhogg\Admin\Settings\Settings_Page;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Groundhogg\Reports;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Settings_Api extends Base_Api {

	public function register_routes() {

		register_rest_route( self::NAME_SPACE, "/settings", [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read' ],
				'permission_callback' => [ $this, 'read_permissions_callback' ]
			],
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update' ],
				'permission_callback' => [ $this, 'update_permissions_callback' ]
			],
		] );

		register_rest_route(
			self::NAME_SPACE, '/settings/(?P<group_id>[\w-]+)', array(
				'args'   => array(
					'group' => array(
						'description' => esc_html__( 'Settings group ID.', 'groundhogg' ),
						'type'        => 'string',
					),
				),
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'read' ],
					'permission_callback' => [ $this, 'read_permissions_callback' ]
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update' ],
					'permission_callback' => [ $this, 'update_permissions_callback' ]
				],
			)
		);

	}

	protected function get_settings( $group_id ) {
		$settings_page = new Settings_Page();

		$settings_page->init_defaults();
		$settings_page->register_settings();

		$settings = wp_json_encode( $settings_page );
		$settings = json_decode( $settings, true );

		$_settings = [];

		foreach ( $settings['settings'] as $name => $setting ) {
			$group = $settings['sections'][ $setting['section'] ]['tab'];

			if ( $group_id && $group_id !== $group ) {
				continue;
			}

			$setting['group'] = $group;
			$_settings[ $name ] = $setting;
		}

		return $_settings;
	}

	protected function prepare_settings( $data, $group_id ) {
		$settings = array_keys( $this->get_settings( $group_id ) );
		$_data = [];
		foreach ( $data as $index => $setting ) {
			if ( ! in_array( $setting['id'], $settings, true ) ) {
				unset( $data[ $index ] );
			}

			$_data[ $setting['id'] ] = trim( $setting['value'] );
		}

		return $_data;
	}

	/**
	 * Takes a single parameter 'query' or empty to return a list of contacts.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function read( WP_REST_Request $request ) {

		$group_id = $request->get_param( 'group_id' );

		$settings = $this->get_settings( $group_id );

		return self::SUCCESS_RESPONSE( [ 'items' => $settings, 'total_items' => count( $settings ) ] );
	}

	/**
	 * Updates a contact given a contact array
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function update( WP_REST_Request $request ) {
		$data     = $request->get_param( 'update' ) ?: [];
		$group_id = $request->get_param( 'group_id' );

		$items = $this->prepare_settings( $data, $group_id );

		foreach ( $items as $name => $value ) {
			Plugin::$instance->settings->update_option( $name, $value );
		}

		return self::SUCCESS_RESPONSE( [
			'items'       => $items,
			'total_items' => count( $items ),
		] );
	}

	public function read_permissions_callback() {
		return current_user_can( 'manage_options' );
	}

	public function update_permissions_callback() {
		return current_user_can( 'manage_options' );
	}

}
