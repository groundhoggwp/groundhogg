<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Groundhogg\Classes\Activity;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function Groundhogg\check_permissions_key;
use function Groundhogg\get_event_by_queued_id;
use function Groundhogg\get_permissions_key;
use function Groundhogg\managed_page_url;
use function Groundhogg\set_permissions_key_cookie;
use function Groundhogg\track_activity;
use function Groundhogg\track_event_activity;
use function Groundhogg\tracking;
use function Groundhogg\unsubscribe_url;

class Unsubscribe_Api extends Base_Api {

	public function register_routes() {

		register_rest_route( self::NAME_SPACE, '/unsubscribe/(?P<event>\w+)/(?P<pk>\w+)', [
			[
				'methods'              => WP_REST_Server::EDITABLE,
				'callback'             => [ $this, 'unsubscribe' ],
				'permission_callback' => '__return_true',
			],
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'unsubscribe' ],
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
			return self::ERROR_404( 'not_found', 'No contact could be found for the provided info.' );
		}

		if ( ! check_permissions_key( $pk, $event->get_contact() ) ) {
			return self::ERROR_401( 'invalid_token', 'Could not verify the request was authentic.' );
		}

		$event->get_contact()->unsubscribe();

		track_event_activity( $event, Activity::UNSUBSCRIBED );

		if ( $request->get_method() === 'GET' ){

			tracking()->set_current_contact( $event->get_contact() );

			set_permissions_key_cookie( $pk, 'preferences' );

			wp_redirect( wp_nonce_url( managed_page_url( 'preferences/unsubscribe' ), 'unsubscribe' ) );
			die();
		}

		return self::SUCCESS_RESPONSE();
	}

}
