<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
use Groundhogg\Plugin;
use Groundhogg\Step;
use Groundhogg\Temp_Step;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Steps_Api extends Base_Object_Api {

	public function register_routes() {

		$route = $this->get_route();
		$key   = $this->get_primary_key();

		parent::register_routes();

		register_rest_route( self::NAME_SPACE, "/{$route}/html", [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'step_html' ],
				'permission_callback' => [ $this, 'read_permissions_callback' ]
			],
		] );
	}

	/**
	 * Gets the STEP HTML
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function step_html( \WP_REST_Request $request ) {

		$step = new Temp_Step(
			$request->get_param( 'ID' ),
			$request->get_param( 'data' ),
			$request->get_param( 'meta' )
		);

		ob_start();

		$step->html_v2();

		$html = ob_get_clean();

		return self::SUCCESS_RESPONSE( [
			'html' => $html,
			'step' => $step
		] );
	}

	/**
	 * The name of the table resource to use
	 *
	 * @return string
	 */
	public function get_db_table_name() {
		return 'steps';
	}

	/**
	 * Permissions callback for read
	 *
	 * @return bool
	 */
	public function read_permissions_callback() {
		return current_user_can( 'edit_funnels' );
	}

	/**
	 * Permissions callback for update
	 *
	 * @return mixed
	 */
	public function update_permissions_callback() {
		return current_user_can( 'edit_funnels' );
	}

	/**
	 * Permissions callback for create
	 *
	 * @return mixed
	 */
	public function create_permissions_callback() {
		return current_user_can( 'edit_funnels' );
	}

	/**
	 * Permissions callback for delete
	 *
	 * @return mixed
	 */
	public function delete_permissions_callback() {
		return current_user_can( 'edit_funnels' );
	}
}