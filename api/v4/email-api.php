<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Groundhogg\Email;
use Groundhogg\Plugin;
use function Groundhogg\get_db;
use function Groundhogg\send_email_notification;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Email_Api extends Base {
	public function register_routes() {

		$callback = $this->get_auth_callback();

		register_rest_route( self::NAME_SPACE, '/emails', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => $callback,
				'callback'            => [ $this, 'create' ],
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

		register_rest_route( self::NAME_SPACE, '/emails/send', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'send_email' ],
			'permission_callback' => $callback,
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
	public function read( WP_REST_Request $request ) {

		if ( ! current_user_can( 'edit_emails' ) ) {
			return self::ERROR_INVALID_PERMISSIONS();
		}

		$args = array(
			'where'   => $request->get_param( 'where' ) ?: [],
			'limit'   => absint( $request->get_param( 'limit' ) ) ?: 25,
			'offset'  => absint( $request->get_param( 'offset' ) ) ?: 0,
			'order'   => sanitize_text_field( $request->get_param( 'offset' ) ) ?: 'DESC',
			'orderby' => sanitize_text_field( $request->get_param( 'orderby' ) ) ?: 'ID',
			'select'  => sanitize_text_field( $request->get_param( 'select' ) ) ?: '*',
			'search'  => sanitize_text_field( $request->get_param( 'search' ) ),
		);

		$total = get_db( 'emails' )->count( $args );
		$items = get_db( 'emails' )->query( $args );
		$items = array_map( function ( $item ) {
			return new Email( $item->ID );
		}, $items );

		return self::SUCCESS_RESPONSE( [ 'items' => $items, 'total_items' => $total ] );
	}

	/**
	 * Create and email
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error
	 */
	public function create( WP_REST_Request $request ){
		return self::ERROR_NOT_IN_SERVICE();
	}

	/**
	 * Update 1 or many emails
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error
	 */
	public function update( WP_REST_Request $request ){
		return self::ERROR_NOT_IN_SERVICE();
	}

	/**
	 * Delete 1 or many emails
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error
	 */
	public function delete( WP_REST_Request $request ){
		return self::ERROR_NOT_IN_SERVICE();
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