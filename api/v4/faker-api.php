<?php

namespace Groundhogg\Api\V4;

use Groundhogg\Contact;
use Groundhogg\Email;
use Groundhogg\Faker;
use Groundhogg\Funnel;
use Groundhogg\Utils\DateTimeHelper;

class Faker_Api extends Base_Api {

	/**
	 * @return void
	 */
	public function register_routes() {
		register_rest_route( self::NAME_SPACE, "/faker/broadcast", [
			'methods'             => \WP_REST_Server::CREATABLE,
			'permission_callback' => [ $this, 'admin_permissions_callback' ],
			'callback'            => [ $this, 'fake_broadcast' ],
		] );

		register_rest_route( self::NAME_SPACE, "/faker/funnel", [
			'methods'             => \WP_REST_Server::CREATABLE,
			'permission_callback' => [ $this, 'admin_permissions_callback' ],
			'callback'            => [ $this, 'fake_funnel_journey' ],
		] );
	}

	/**
	 * Fake a broadcast
	 *
	 * @throws \Exception
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function fake_broadcast( \WP_REST_Request $request ) {

		$email_id = absint( $request->get_param( 'email_id' ) );
		$query    = $request->get_param( 'query' ) ?: [];
		$date     = $request->get_param( 'date' );

		$date  = new DateTimeHelper( $date );
		$email = new Email( $email_id );

		if ( empty( $query ) || ! $email->exists() || $date->isFuture() ) {
			return self::ERROR_400();
		}

		Faker::broadcast( $email, $query, $date );

		return self::SUCCESS_RESPONSE();
	}

	/**
	 * Fake a funnel journey
	 *
	 * @throws \Exception
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function fake_funnel_journey( \WP_REST_Request $request ) {
		$query      = $request->get_param( 'query' ) ?: [];
		$modifier = sanitize_text_field( $request->get_param( 'modifier' ) );
		$funnel_id  = absint( $request->get_param( 'funnel_id' ) );
		$contact_id = absint( $request->get_param( 'contact_id' ) );

		$date   = new DateTimeHelper( $modifier );
		$funnel = new Funnel( $funnel_id );

		if ( ! empty( $query ) ) {
			Faker::funnel_journeys( $funnel, $query, $modifier );

			return self::SUCCESS_RESPONSE();
		}

		$contact = new Contact( $contact_id );

		if ( $contact->exists() ) {
			Faker::funnel_journey( $funnel, $contact, $date );
			return self::SUCCESS_RESPONSE();
		}

		return self::ERROR_400();
	}

	public function fake_page_visits( \WP_REST_Request $request ) {

	}
}
