<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Groundhogg\Contact;
use Groundhogg\Contact_Query;
use function Groundhogg\get_contactdata;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use function Groundhogg\is_a_contact;

class Contacts_Api extends Base_Api {

	public function register_routes() {

		register_rest_route( self::NAME_SPACE, '/contacts', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read' ],
				'permission_callback' => function () {
					return current_user_can( 'view_contacts' );
				},
			],
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create' ],
				'permission_callback' => function () {
					return current_user_can( 'add_contacts' );
				},
			],
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update' ],
				'permission_callback' => function () {
					return current_user_can( 'edit_contacts' );
				},
			],
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete' ],
				'permission_callback' => function () {
					return current_user_can( 'delete_contacts' );
				},
			],
		] );

		register_rest_route( self::NAME_SPACE, '/contacts/(?P<ID>\d+)', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read_single' ],
				'permission_callback' => function () {
					return current_user_can( 'view_contacts' );
				},
			],
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update_single' ],
				'permission_callback' => function () {
					return current_user_can( 'edit_contacts' );
				},
			],
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_single' ],
				'permission_callback' => function () {
					return current_user_can( 'delete_contacts' );
				},
			],
		] );

		register_rest_route( self::NAME_SPACE, '/contacts/(?P<ID>\d+)/notes', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read_notes' ],
				'permission_callback' => function () {
					return current_user_can( 'edit_contacts' );
				},
			],
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_notes' ],
				'permission_callback' => function () {
					return current_user_can( 'edit_contacts' );
				},
			],
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update_notes' ],
				'permission_callback' => function () {
					return current_user_can( 'edit_contacts' );
				},
			],
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_notes' ],
				'permission_callback' => function () {
					return current_user_can( 'edit_contacts' );
				},
			],
		] );

		register_rest_route( self::NAME_SPACE, '/contacts/(?P<ID>\d+)/tags', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read_tags' ],
				'permission_callback' => function () {
					return current_user_can( 'edit_contacts' );
				},
			],
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_tags' ],
				'permission_callback' => function () {
					return current_user_can( 'edit_contacts' );
				},
			],
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_tags' ],
				'permission_callback' => function () {
					return current_user_can( 'edit_contacts' );
				},
			],
		] );

		register_rest_route( self::NAME_SPACE, '/contacts/(?P<ID>\d+)/meta', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read_meta' ],
				'permission_callback' => function () {
					return current_user_can( 'edit_contacts' );
				},
			],
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_meta' ],
				'permission_callback' => function () {
					return current_user_can( 'edit_contacts' );
				},
			],
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update_meta' ],
				'permission_callback' => function () {
					return current_user_can( 'edit_contacts' );
				},
			],
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_meta' ],
				'permission_callback' => function () {
					return current_user_can( 'edit_contacts' );
				},
			],
		] );

	}

	/**
	 * Returns a contact not found error
	 *
	 * @return WP_Error
	 */
	protected static function ERROR_CONTACT_NOT_FOUND(){
		return self::ERROR_404( 'error', 'Contact not found.' );
	}

	/**
	 * Create a contact or multiple contacts
	 * Should handle both cases
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function create( WP_REST_Request $request ) {
		return self::ERROR_NOT_IN_SERVICE();
	}

	/**
	 * Takes a single parameter 'query' or empty to return a list of contacts.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function read( WP_REST_Request $request ) {

		$query  = (array) $request->get_param( 'query' ) ?: [];
		$search = sanitize_text_field( wp_unslash( $request->get_param( 'search' ) ) );

		if ( ! key_exists( 'search', $query ) && ! empty( $search ) ) {
			$query['search'] = $search;
		}

		$contact_query = new Contact_Query();

		$default_query_limit = absint( $request->get_param( 'limit' ) ) ?: 100;
		$default_offset      = absint( $request->get_param( 'offset' ) ) ?: 0;

		$query = wp_parse_args( $query, [
			'number' => $default_query_limit,
			'offset' => $default_offset,
		] );

		$count    = $contact_query->count( $query );
		$contacts = $contact_query->query( $query );
		$contacts = array_map( function ( $contact ) {
			return new Contact( $contact->ID );
		}, $contacts );

		return self::SUCCESS_RESPONSE( [
			'items'       => $contacts,
			'total_items' => $count,
		] );
	}

	/**
	 * Updates a contact given a contact array
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function update( WP_REST_Request $request ) {
		return self::ERROR_NOT_IN_SERVICE();
	}

	/**
	 * Delete contacts
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function delete( WP_REST_Request $request ) {

		$query  = (array) $request->get_param( 'query' ) ?: [];

		// avoid deleting all contacts when query is empty
		if ( empty( $query ) ){
			return self::ERROR_401();
		}

		$contact_query = new Contact_Query();

		$count    = $contact_query->count( $query );
		$contacts = $contact_query->query( $query );

		array_map( function ( $contact ) {
			get_contactdata( $contact )->delete();
		}, $contacts );

		return self::SUCCESS_RESPONSE( [
			'total_items' => $count
		] );
	}

	/**
	 * Fetches a single contact record
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function read_single( WP_REST_Request $request ) {
		$ID = absint( $request->get_param( 'ID' ) );

		$contact = get_contactdata( $ID );

		if ( ! is_a_contact( $contact ) ) {
			return self::ERROR_CONTACT_NOT_FOUND();
		}

		return self::SUCCESS_RESPONSE( [ 'item' => $contact ] );
	}

	/**
	 * Updates a contact given a contact array
	 *
	 * @todo sanitize data & meta
	 * @todo prevent duplicate email addresses
	 * @todo validate input
	 * @todo support add/remove tags
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_single( WP_REST_Request $request ) {
		$ID = absint( $request->get_param( 'ID' ) );

		$contact = get_contactdata( $ID );

		if ( ! is_a_contact( $contact ) ) {
			return self::ERROR_CONTACT_NOT_FOUND();
		}

		$data = $request->get_param( 'data' );
		$meta = $request->get_param( 'meta' );
		$tags = $request->get_param( 'tags' );

		$contact->update( $data );

		foreach ( $meta as $key => $value ){
			$contact->update_meta( sanitize_key( $key ), $value );
		}

		return self::SUCCESS_RESPONSE( [ 'item' => $contact ] );
	}

	/**
	 * Delete a contact
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_single( WP_REST_Request $request ) {
		$ID = absint( $request->get_param( 'ID' ) );

		$contact = get_contactdata( $ID );

		if ( ! is_a_contact( $contact ) ) {
			return self::ERROR_CONTACT_NOT_FOUND();
		}

		$contact->delete();

		return self::SUCCESS_RESPONSE();
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function create_notes( WP_REST_Request $request ) {
		return self::ERROR_NOT_IN_SERVICE();
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function read_notes( WP_REST_Request $request ) {
		return self::ERROR_NOT_IN_SERVICE();
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_notes( WP_REST_Request $request ) {
		return self::ERROR_NOT_IN_SERVICE();
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function delete_notes( WP_REST_Request $request ) {
		return self::ERROR_NOT_IN_SERVICE();
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function create_tags( WP_REST_Request $request ) {
		return self::ERROR_NOT_IN_SERVICE();
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function read_tags( WP_REST_Request $request ) {
		return self::ERROR_NOT_IN_SERVICE();
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_tags( WP_REST_Request $request ) {
		return self::ERROR_NOT_IN_SERVICE();
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function delete_tags( WP_REST_Request $request ) {
		return self::ERROR_NOT_IN_SERVICE();
	}


	/**
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function create_meta( WP_REST_Request $request ) {
		return self::ERROR_NOT_IN_SERVICE();
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function read_meta( WP_REST_Request $request ) {
		return self::ERROR_NOT_IN_SERVICE();
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_meta( WP_REST_Request $request ) {
		return self::ERROR_NOT_IN_SERVICE();
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function delete_meta( WP_REST_Request $request ) {
		return self::ERROR_NOT_IN_SERVICE();
	}


}