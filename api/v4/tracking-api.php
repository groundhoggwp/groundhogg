<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Groundhogg\Tracking;
use function Groundhogg\get_cookie;
use function Groundhogg\get_current_contact;
use function Groundhogg\get_db;
use Groundhogg\Plugin;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Tracking_Api extends Base {


	public function register_routes() {
		register_rest_route( self::NAME_SPACE, '/tracking/page-view', [
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'permission_callback' => function ( WP_REST_Request $request ) {
					return wp_verify_nonce( $request->get_param( '_ghnonce' ), 'groundhogg_frontend' );
				},
				'callback'            => [ $this, 'page_view' ],
				'args'                => [
					'_ghnonce' => [
						'description' => 'Need this!',
						'required'    => true
					]
				]
			]
		] );

		register_rest_route( self::NAME_SPACE, '/tracking/form-impression', [
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'form_impression' ],
				'permission_callback' => function ( WP_REST_Request $request ) {
					return wp_verify_nonce( $request->get_param( '_ghnonce' ), 'groundhogg_frontend' );
				},
				'args'                => [
					'_ghnonce' => [
						'description' => 'Need this!',
						'required'    => true
					]
				]
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
	public function page_view( WP_REST_Request $request ) {
		$contact = get_current_contact();

		if ( ! $contact ) {
			return self::ERROR_200( 'no_contact', 'No contact to track...' );
		}

		$ref = $request->get_param( 'ref' );

		if ( ! $ref ) {
			return self::ERROR_400( 'no_ref', 'Cannot track blank pages...' );
		}

		do_action( 'groundhogg/api/v3/tracking/page-view', $ref, $contact );

		return self::SUCCESS_RESPONSE();

	}

	/**
	 * Log a form impressions for tracking purposes.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function form_impression( WP_REST_Request $request ) {
		if ( ! class_exists( 'Browser' ) ) {
			require_once GROUNDHOGG_PATH . 'includes/lib/browser.php';
		}

		$browser = new \Browser();

		if ( $browser->isRobot() || $browser->isAol() ) {
			return self::ERROR_401( 'looks_like_a_bot', 'Form impressions do not track bots.' );
		}

		$ID = absint( $request->get_param( 'form_id' ) );

		if ( ! get_db( 'steps' )->exists( $ID ) ) {
			return self::ERROR_400( 'form_dne', 'The given form does not exist.' );
		}

		$impressions = explode( ',', get_cookie( Tracking::FORM_IMPRESSIONS_COOKIE ) );

		$ip_address = Plugin::instance()->utils->location->get_real_ip();

		if ( ! in_array( $ID, $impressions ) || ! $ip_address ) {
			get_db( 'form_impressions' )->add( [ 'form_id' => $ID, 'ip_address' => $ip_address ] );
			do_action( 'groundhogg/api/v3/tracking/form-impression' );
		}

		return self::SUCCESS_RESPONSE();
	}

}