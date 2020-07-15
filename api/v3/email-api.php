<?php

namespace Groundhogg\Api\V3;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Groundhogg\Email;
use Groundhogg\Plugin;
use function Groundhogg\send_email_notification;
use function Groundhogg\sort_by_string_in_array;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Email_Api extends Base {
	public function register_routes() {

		$auth_callback = $this->get_auth_callback();

		register_rest_route( self::NAME_SPACE, '/emails', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_emails' ],
				'permission_callback' => $auth_callback,
				'args'                => [
					'query'        => [
						'description' => _x( 'Any search parameters.', 'api', 'groundhogg' )
					],
					'select'       => [
						'required'    => false,
						'description' => _x( 'Whether to retrieve as available for a select input.', 'api', 'groundhogg' ),
					],
					'selectReact' => [
						'required'    => false,
						'description' => _x( 'Whether to retrieve as available for an ajax select2 input.', 'api', 'groundhogg' ),
					],
					'select2'      => [
						'required'    => false,
						'description' => _x( 'Whether to retrieve as available for an ajax select2 input.', 'api', 'groundhogg' ),
					],
					'search'       => [
						'required'    => false,
						'description' => _x( 'Search string for tag name.', 'api', 'groundhogg' ),
					],
					'q'            => [
						'required'    => false,
						'description' => _x( 'Shorthand for search.', 'api', 'groundhogg' ),
					],
				]
			]
		] );

		register_rest_route( self::NAME_SPACE, '/emails/send', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'send_email' ],
			'permission_callback' => $auth_callback,
			'args'                => array(
				'id_or_email' => [
					'required'    => true,
					'description' => _x( 'The ID or email of the contact you want to send email to.', 'api', 'groundhogg' ),
				],
				'by_user_id'  => [
					'required'    => false,
					'description' => _x( 'Search using the user ID.', 'api', 'groundhogg' ),
				],
				'email_id'    => [
					'required'    => true,
					'description' => _x( 'Email ID which you want to send.', 'api', 'groundhogg' ),
				]
			)
		) );

	}

	/**
	 * Get a list of emails which match a given query
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_emails( WP_REST_Request $request ) {
		if ( ! current_user_can( 'edit_emails' ) ) {
			return self::ERROR_INVALID_PERMISSIONS();
		}

		$query = $request->get_param( 'query' ) ? (array) $request->get_param( 'query' ) : [];

		$search = $request->get_param( 'q' ) ? $request->get_param( 'q' ) : $request->get_param( 'search' );
		$search = sanitize_text_field( stripslashes( $search ) );

		if ( ! key_exists( 'search', $query ) && ! empty( $search ) ) {
			$query['search'] = $search;
		}

		$is_for_select  = filter_var( $request->get_param( 'select' ), FILTER_VALIDATE_BOOLEAN );
		$is_for_select2 = filter_var( $request->get_param( 'select2' ), FILTER_VALIDATE_BOOLEAN );
		$is_for_select_react = filter_var( $request->get_param( 'selectReact' ), FILTER_VALIDATE_BOOLEAN );

		$emails = Plugin::$instance->dbs->get_db( 'emails' )->query( $query, 'ID' );

		if ( $is_for_select2 ) {
			$json = array();

			foreach ( $emails as $i => $email ) {

				$email = new Email( $email->ID );

				$json[] = array(
					'id'   => $email->get_id(),
					'text' => $email->get_title() . ' (' . $email->get_status() . ')'
				);

			}

			usort( $json, sort_by_string_in_array( 'text' ) );

			$results = array( 'results' => $json, 'more' => false );

			return rest_ensure_response( $results );
		} else if ( $is_for_select ) {

			$response_emails = [];

			foreach ( $emails as $i => $email ) {
				$response_emails[ $email->ID ] = $email->subject;
			}

			$emails = $response_emails;

		} else if ( $is_for_select_react ){
			$json = array();

			foreach ( $emails as $i => $email ) {
				$json[] = array(
					'value' => $email->ID,
					'label' => sprintf( "%s (%s)", $email->title ?: $email->subject, $email->status )
				);
			}

			$results = array( 'emails' => $json );

			return rest_ensure_response( $results );
		}

		return self::SUCCESS_RESPONSE( [ 'emails' => $emails ] );
	}

	/**
	 * Send an email to the provided contact
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function send_email( WP_REST_Request $request ) {
		if ( ! current_user_can( 'send_emails' ) ) {
			return self::ERROR_INVALID_PERMISSIONS();
		}

		$contact = self::get_contact_from_request( $request );

		if ( is_wp_error( $contact ) ) {
			return $contact;
		}

		$email_id = intval( $request->get_param( 'email_id' ) );

		if ( ! Plugin::$instance->dbs->get_db( 'emails' )->exists( $email_id ) ) {
			return self::ERROR_400( 'no_email', sprintf( _x( 'Email with ID %d not found.', 'api', 'groundhogg' ), $email_id ) );
		}

		$status = send_email_notification( $email_id, $contact->ID );

		if ( ! $status ) {
			return self::ERROR_UNKNOWN();
		}

		return self::SUCCESS_RESPONSE( [], _x( 'Email sent successfully to contact.', 'api', 'groundhogg' ) );
	}

}