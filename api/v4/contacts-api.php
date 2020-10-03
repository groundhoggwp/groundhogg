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
use function Groundhogg\is_a_contact;
use function Groundhogg\is_email_address_in_use;
use function Groundhogg\sanitize_object_meta;

class Contacts_Api extends Resource_Base_Object_Api {

	public function register_routes() {

		parent::register_routes();

		register_rest_route( self::NAME_SPACE, '/contacts/(?P<id>\d+)/tags', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read_tags' ],
				'permission_callback' => [ $this, 'update_permissions_callback' ]
			],
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_tags' ],
				'permission_callback' => [ $this, 'update_permissions_callback' ]
			],
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_tags' ],
				'permission_callback' => [ $this, 'update_permissions_callback' ]
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
	 * Create a contact or multiple contacts
	 * Should handle both cases
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function create( WP_REST_Request $request ) {

		// Create single maybe?
		if ( $request->get_param( 'data' ) ){
			return $this->create_single( $request );
		}

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
				$contact->update_meta( $key, $value );
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
				$contact->update_meta( sanitize_key( $key ), sanitize_object_meta( $value ) );
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
			$contact->update_meta( sanitize_key( $key ), sanitize_object_meta( $value ) );
		}

		$contact->apply_tag( $tags );

		return self::SUCCESS_RESPONSE( [
			'item' => $contact
		] );

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
			$contact->update_meta( sanitize_key( $key ), sanitize_object_meta( $value ) );
		}

		$contact->apply_tag( $add_tags );
		$contact->remove_tag( $remove_tags );

		return self::SUCCESS_RESPONSE( [ 'item' => $contact ] );
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
	 * The name of the table resource to use
	 *
	 * @return string
	 */
	public function get_db_table_name() {
		return 'contacts';
	}

	/**
	 * Permissions callback for read
	 *
	 * @return bool
	 */
	public function read_permissions_callback() {
		return current_user_can( 'view_contacts' );
	}

	/**
	 * Permissions callback for update
	 *
	 * @return mixed
	 */
	public function update_permissions_callback() {
		return current_user_can( 'edit_contacts' );
	}

	/**
	 * Permissions callback for create
	 *
	 * @return mixed
	 */
	public function create_permissions_callback() {
		return current_user_can( 'add_contacts' );
	}

	/**
	 * Permissions callback for delete
	 *
	 * @return mixed
	 */
	public function delete_permissions_callback() {
		return current_user_can( 'delete_contacts' );
	}
}