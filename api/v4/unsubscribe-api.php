<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Groundhogg\Plugin;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use function Groundhogg\decrypt;
use function Groundhogg\get_contactdata;

class Unsubscribe_Api extends Base_Api {

	public function register_routes() {
		register_rest_route( self::NAME_SPACE, '/unsubscribe', [
			[
				'methods'  => WP_REST_Server::EDITABLE,
				'callback' => [ $this, 'unsubscribe' ],
				'args'     => [
					'contact' => [
						'description' => 'Encrypted contact ID or Email address',
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
	public function unsubscribe( WP_REST_Request $request ) {

		$enc_contact_id_or_email = $request->get_param( 'contact' );
		$contact_id_or_email = decrypt( $enc_contact_id_or_email );

		if ( ! $contact_id_or_email ){
			return self::ERROR_401( 'invalid_contact_id_or_email', 'The provided contact is invalid.' );
		}

		$contact = get_contactdata( $contact_id_or_email );

		if ( ! $contact ){
			return self::ERROR_401( 'invalid_contact_id_or_email', 'The provided contact is invalid.' );
		}

		$contact->unsubscribe();

		return self::SUCCESS_RESPONSE();
	}

}