<?php

namespace Groundhogg\Api\v4;

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
use function Groundhogg\track_page_visit;

class Tracking_Api extends Base_Api {

	public function register_routes() {
		register_rest_route( self::NAME_SPACE, '/tracking/pages', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => [ $this, 'is_real_request' ],
				'callback'            => [ $this, 'page_view' ],
			]
		] );

		register_rest_route( self::NAME_SPACE, '/tracking/forms', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => [ $this, 'is_real_request' ],
				'callback'            => [ $this, 'form_impression' ],
			]
		] );
	}

	/**
	 * Verify that the request is real
	 *
	 * @param $request
	 *
	 * @return bool
	 */
	public function is_real_request( $request ){

		if ( ! class_exists( 'Browser' ) ) {
			require_once GROUNDHOGG_PATH . 'includes/lib/browser.php';
		}

		$browser = new \Browser();

		if ( $browser->isRobot() || $browser->isAol() ) {
			return false;
		}

		// More checks?

		return true;
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

		$visit = track_page_visit( $ref, $contact );

		if ( ! current_user_can( 'view_activity' ) ){
			return self::SUCCESS_RESPONSE();
		}

		if ( ! $visit || ! $visit->exists() ){

			global $wpdb;

			if ( $wpdb->last_error ){
				return self::ERROR_500( 'db_error', $wpdb->last_error );
			}

			return self::ERROR_500();

		}

		return self::SUCCESS_RESPONSE( $visit );

	}

	/**
	 * Log a form impressions for tracking purposes.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function form_impression( WP_REST_Request $request ) {

		$ID = absint( $request->get_param( 'form_id' ) );

		if ( ! get_db( 'steps' )->exists( $ID ) ) {
			return self::ERROR_400( 'form_dne', 'The given form does not exist.' );
		}

		$impressions = explode( ',', get_cookie( Tracking::FORM_IMPRESSIONS_COOKIE ) );

		$ip_address = Plugin::instance()->utils->location->get_real_ip();

		if ( ! in_array( $ID, $impressions ) || ! $ip_address ) {
			get_db( 'form_impressions' )->add( [ 'form_id' => $ID, 'ip_address' => $ip_address ] );
			do_action( 'groundhogg/api/v4/tracking/form-impression' );
		}

		return self::SUCCESS_RESPONSE();
	}

}
