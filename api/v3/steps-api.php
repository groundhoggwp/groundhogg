<?php

namespace Groundhogg\Api\V3;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Groundhogg\Admin\Dashboard\Dashboard_Widgets;
use function Groundhogg\get_db;
use Groundhogg\Plugin;
use function Groundhogg\show_groundhogg_branding;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

use Groundhogg\Admin\Dashboard\Widgets;

class Steps_Api extends Base {

	public function register_routes() {

		$callback = $this->get_auth_callback();

		register_rest_route( self::NAME_SPACE, '/steps', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => $callback,
				'callback'            => [ $this, 'create' ],
				'args'                => [
					'type'      => [
						'required' => true,
					],
					'funnel_id' => [
						'required' => true,
					],
				]
			],
			[
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => $callback,
				'callback'            => [ $this, 'read' ],
			],
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'permission_callback' => $callback,
				'callback'            => [ $this, 'update' ],
			],
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'permission_callback' => $callback,
				'callback'            => [ $this, 'delete' ],
			],
		] );

	}

	/**
	 * Create a new step!
	 *
	 * Requires:
	 *  - step_type
	 *  - funnel_id
	 *  - settings (optional)
	 *  - order
	 *
	 * @param WP_REST_Request $request
	 */
	public function create( WP_REST_Request $request ) {

	}

	public function read( WP_REST_Request $request ){

	}

	public function update( WP_REST_Request $request ){

	}

	public function delete( WP_REST_Request $request ){

	}


}