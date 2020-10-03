<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Groundhogg\Contact;
use Groundhogg\Contact_Query;
use function Groundhogg\get_array_var;
use function Groundhogg\get_contactdata;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use function Groundhogg\get_db;
use function Groundhogg\is_a_contact;
use function Groundhogg\is_email_address_in_use;
use function Groundhogg\isset_not_empty;
use function Groundhogg\sanitize_contact_meta;

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

		register_rest_route( self::NAME_SPACE, '/contacts/(?P<id>\d+)', [
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

		register_rest_route( self::NAME_SPACE, '/contacts/(?P<id>\d+)/notes', [
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
		] );

		register_rest_route( self::NAME_SPACE, '/contacts/(?P<id>\d+)/tags', [
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

		register_rest_route( self::NAME_SPACE, '/contacts/(?P<id>\d+)/meta', [
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
	protected static function ERROR_CONTACT_NOT_FOUND() {
		return self::ERROR_404( 'error', 'Contact not found.' );
	}

	/**
	 * Handle tags to be added/removed...
	 *
	 * @param $contact Contact
	 * @param $tags array
	 */
	protected function handle_rest_tags( $contact, $tags ) {
		if ( isset_not_empty( $tags, 'add' ) ) {
			$contact->apply_tag( get_array_var( $tags, 'add' ) );
		} else if ( isset_not_empty( $tags, 'remove' ) ) {
			$contact->remove_tag( get_array_var( $tags, 'remove' ) );
		} else {
			$contact->apply_tag( $tags );
		}
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
		$items = $request->get_json_params();

		if ( empty( $items ) ) {
			return self::ERROR_422( 'error', 'No contact data provided.' );
		}

		$added = [];

		foreach ( $items as $item ) {

			$data = get_array_var( $item, 'data' );
			$meta = get_array_var( $item, 'meta' );
			$tags = get_array_var( $item, 'tags' );

			// get the email address
			$email_address = get_array_var( $data, 'email' );

			// skip if an email address was not provided
			// or if another contact is already using this address
			if ( ! $email_address || get_contactdata( $email_address ) ) {
				continue;
			}

			// Create the contact record...
			$contact = new Contact( $data );

			foreach ( $meta as $key => $value ) {
				$contact->update_meta( sanitize_key( $key ), sanitize_contact_meta( $value ) );
			}

			$contact->apply_tag( $tags );

			$added[] = $contact;
		}

		return self::SUCCESS_RESPONSE( [
			'items'       => $added,
			'total_items' => count( $added ),
		] );
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
		$query  = (array) $request->get_param( 'query' ) ?: [];
		$search = sanitize_text_field( wp_unslash( $request->get_param( 'search' ) ) );

		if ( ! key_exists( 'search', $query ) && ! empty( $search ) ) {
			$query['search'] = $search;
		}

		$contact_query = new Contact_Query();

		$count    = $contact_query->count( $query );
		$contacts = $contact_query->query( $query );

		$contacts = array_map( function ( $contact ) {
			return new Contact( $contact->ID );
		}, $contacts );

		$data        = $request->get_param( 'data' );
		$meta        = $request->get_param( 'meta' );
		$add_tags    = $request->get_param( 'add_tags' ) ?: $request->get_param( 'apply_tags' );
		$remove_tags = $request->get_param( 'remove_tags' );

		/**
		 * @var $contact Contact
		 */
		foreach ( $contacts as $contact ) {

			// get the email address
			$email_address = get_array_var( $data, 'email' );

			// skip if the email address is not being used
			if ( is_email_address_in_use( $email_address, $contact ) ) {
				continue;
			}

			$contact->update( $data );

			foreach ( $meta as $key => $value ) {
				$contact->update_meta( sanitize_key( $key ), sanitize_contact_meta( $value ) );
			}

			$contact->apply_tag( $add_tags );
			$contact->remove_tag( $remove_tags );

		}

		return self::SUCCESS_RESPONSE( [
			'items'       => $contacts,
			'total_items' => $count,
		] );
	}

	/**
	 * Delete contacts
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function delete( WP_REST_Request $request ) {

		$query = (array) $request->get_param( 'query' ) ?: [];

		// avoid deleting all contacts when query is empty
		if ( empty( $query ) ) {
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
	 * Create a contact or multiple contacts
	 * Should handle both cases
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function create_single( WP_REST_Request $request ) {

		$data = $request->get_param( 'data' );
		$meta = $request->get_param( 'meta' );
		$tags = $request->get_param( 'tags' );

		// get the email address
		$email_address = get_array_var( $data, 'email' );

		if ( ! $email_address ) {
			return self::ERROR_422( 'error', 'An email address is required.' );
		}

		// will return false if the email address is not being used
		if ( is_email_address_in_use( $email_address ) ) {
			return self::ERROR_409( 'error', 'Email address already in use.' );
		}

		// Create the contact record...
		$contact = new Contact( $data );

		foreach ( $meta as $key => $value ) {
			$contact->update_meta( sanitize_key( $key ), sanitize_contact_meta( $value ) );
		}

		$contact->apply_tag( $tags );

		return self::SUCCESS_RESPONSE( [
			'item' => $contact
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
		$ID = absint( $request->get_param( 'id' ) );

		$contact = get_contactdata( $ID );

		if ( ! is_a_contact( $contact ) ) {
			return self::ERROR_CONTACT_NOT_FOUND();
		}

		return self::SUCCESS_RESPONSE( [ 'item' => $contact ] );
	}

	/**
	 * Updates a contact given a contact array
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_single( WP_REST_Request $request ) {
		$ID = absint( $request->get_param( 'id' ) );

		$contact = get_contactdata( $ID );

		if ( ! is_a_contact( $contact ) ) {
			return self::ERROR_CONTACT_NOT_FOUND();
		}

		$data        = $request->get_param( 'data' );
		$meta        = $request->get_param( 'meta' );
		$add_tags    = $request->get_param( 'add_tags' ) ?: $request->get_param( 'apply_tags' );
		$remove_tags = $request->get_param( 'remove_tags' );

		// get the email address
		$email_address = get_array_var( $data, 'email' );

		// will return false if the email address is not being used
		if ( is_email_address_in_use( $email_address, $contact ) ) {
			return self::ERROR_409( 'error', 'Email address already in use.' );
		}

		$contact->update( $data );

		foreach ( $meta as $key => $value ) {
			$contact->update_meta( sanitize_key( $key ), sanitize_contact_meta( $value ) );
		}

		$contact->apply_tag( $add_tags );
		$contact->remove_tag( $remove_tags );

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
		$ID = absint( $request->get_param( 'id' ) );

		$contact = get_contactdata( $ID );

		if ( ! is_a_contact( $contact ) ) {
			return self::ERROR_CONTACT_NOT_FOUND();
		}

		$contact->delete();

		return self::SUCCESS_RESPONSE();
	}

	/**
	 * Create notes for a contact
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function create_notes( WP_REST_Request $request ) {

		$ID = absint( $request->get_param( 'id' ) );

		$contact = get_contactdata( $ID );

		if ( ! is_a_contact( $contact ) ) {
			return self::ERROR_CONTACT_NOT_FOUND();
		}

		$notes = $request->get_json_params();

		foreach ( $notes as $note ) {
			// @todo change context if from admin
			$contact->add_note( $note, 'api' );
		}

		$all_notes = $contact->get_notes();

		return self::SUCCESS_RESPONSE( [
			'items'       => $all_notes,
			'total_items' => count( $all_notes )
		] );
	}

	/**
	 * Get a contacts notes
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function read_notes( WP_REST_Request $request ) {
		$ID = absint( $request->get_param( 'id' ) );

		$contact = get_contactdata( $ID );

		if ( ! is_a_contact( $contact ) ) {
			return self::ERROR_CONTACT_NOT_FOUND();
		}

		$all_notes = $contact->get_notes();

		return self::SUCCESS_RESPONSE( [
			'items'       => $all_notes,
			'total_items' => count( $all_notes )
		] );
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