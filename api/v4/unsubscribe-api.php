<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function Groundhogg\check_permissions_key;
use function Groundhogg\get_event_by_queued_id;

class Unsubscribe_Api extends Base_Api {

	public function register_routes() {

		register_rest_route( self::NAME_SPACE, '/unsubscribe/(?P<event>\w+)/(?P<pk>\w+)', [
			[
				'methods'              => WP_REST_Server::EDITABLE,
				'callback'             => [ $this, 'unsubscribe' ],
				'permission_callback' => '__return_true',
			]
		] );
	}

	/**
	 * Perform a page view action
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function unsubscribe( WP_REST_Request $request ) {

		$pk    = $request->get_param( 'pk' );
		$event = absint( hexdec( $request->get_param( 'event' ) ) );

		$event = get_event_by_queued_id( $event );

		if ( ! $event || ! $event->exists() ) {
			return self::ERROR_404( 'not_found', ' contact could be found for the provided info.' );
		}

		if ( ! check_permissions_key( $pk, $event->get_contact() ) ) {
			return self::ERROR_401( 'invalid_token', 'Could not verify the request was authentic.' );
		}

		$event->get_contact()->unsubscribe();

		return self::SUCCESS_RESPONSE();
	}

}
