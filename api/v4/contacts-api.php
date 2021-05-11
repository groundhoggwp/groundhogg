<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Groundhogg\Contact;
use Groundhogg\Contact_Query;
use function Groundhogg\array_map_keys;
use function Groundhogg\get_array_var;
use function Groundhogg\get_contactdata;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use function Groundhogg\is_a_contact;
use function Groundhogg\is_email_address_in_use;
use function Groundhogg\sanitize_object_meta;

class Contacts_Api extends Base_Object_Api {

	public function register_routes() {

		parent::register_routes();

		register_rest_route( self::NAME_SPACE, '/contacts/(?P<ID>\d+)/tags', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_tags' ],
				'permission_callback' => [ $this, 'update_permissions_callback' ]
			],
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read_tags' ],
				'permission_callback' => [ $this, 'update_permissions_callback' ]
			],
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_tags' ],
				'permission_callback' => [ $this, 'update_permissions_callback' ]
			],
		] );

		register_rest_route( self::NAME_SPACE, '/contacts/(?P<ID>\d+)/files', [
//			[
//				'methods'             => WP_REST_Server::CREATABLE,
//				'callback'            => [ $this, 'create_files' ],
//				'permission_callback' => [ $this, 'create_files_permissions_callback' ]
//			],
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read_files' ],
				'permission_callback' => [ $this, 'read_files_permissions_callback' ]
			],
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_files' ],
				'permission_callback' => [ $this, 'delete_files_permissions_callback' ]
			],
		] );

		register_rest_route( self::NAME_SPACE, '/contacts/(?P<ID>\d+)/merge', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'merge' ],
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
		if ( $request->get_param( 'data' ) ) {
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

			$contact->update_meta( $meta );
			$contact->apply_tag( $tags );

			$added[] = $contact;
		}

		return self::SUCCESS_RESPONSE( [
			'total_items' => count( $added ),
			'items'       => $added,
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

		// Might have passed root level query
		$query  = (array) $request->get_param( 'query' ) ?: $request->get_json_params();
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

		$contacts = array_map( [ $this, 'map_raw_object_to_class' ], $contacts );

		return self::SUCCESS_RESPONSE( [
			'total_items' => $count,
			'items'       => $contacts,
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

		$data        = $request->get_param( 'data' );
		$meta        = $request->get_param( 'meta' );
		$add_tags    = $request->get_param( 'add_tags' ) ?: $request->get_param( 'apply_tags' );
		$remove_tags = $request->get_param( 'remove_tags' );

		if ( empty( $query ) && empty( $data ) && empty( $meta ) && empty( $add_tags ) && empty( $remove_tags ) ) {

			$items = $request->get_json_params();

			if ( empty( $items ) ) {
				return self::ERROR_422();
			}

			$contacts = [];

			foreach ( $items as $item ) {

				$id      = get_array_var( $item, 'ID' );
				$contact = new Contact( $id );

				if ( ! $contact->exists() ) {
					continue;
				}

				$data        = get_array_var( $item, 'data', [] );
				$meta        = get_array_var( $item, 'meta', [] );
				$add_tags    = get_array_var( $item, 'add_tags', get_array_var( $item, 'apply_tags', [] ) );
				$remove_tags = get_array_var( $item, 'remove_tags', [] );

				// get the email address
				$email_address = get_array_var( $data, 'email' );

				// skip if the email address is not being used
				if ( is_email_address_in_use( $email_address, $contact ) ) {
					continue;
				}

				$contact->update( $data );

				// If the current object supports meta data...
				if ( ! empty( $meta ) && is_array( $meta ) ) {
					$contact->update_meta( $meta );

				}

				$contact->apply_tag( $add_tags );
				$contact->remove_tag( $remove_tags );

				$contacts[] = $contact;
			}

			return self::SUCCESS_RESPONSE( [
				'total_items' => count( $contacts ),
				'items'       => $contacts,
			] );
		}

		$contact_query = new Contact_Query();

		$count    = $contact_query->count( $query );
		$contacts = $contact_query->query( $query );

		$contacts = array_map( [ $this, 'map_raw_object_to_class' ], $contacts );

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
			$contact->update_meta( $meta );
			$contact->apply_tag( $add_tags );
			$contact->remove_tag( $remove_tags );

		}

		return self::SUCCESS_RESPONSE( [
			'total_items' => $count,
			'items'       => $contacts,
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

		$contact->update_meta( $meta );

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
		$ID = absint( $request->get_param( 'ID' ) );

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
		$ID = absint( $request->get_param( 'ID' ) );

		$contact = get_contactdata( $ID );

		if ( ! is_a_contact( $contact ) ) {
			return self::ERROR_CONTACT_NOT_FOUND();
		}

		$tags = $request->get_json_params();

		$contact->apply_tag( $tags );

		return self::SUCCESS_RESPONSE( [ 'tags' => $contact->get_tags() ] );
	}

	/**
	 * Fetch tags associated with the contact record
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function read_tags( WP_REST_Request $request ) {
		$ID = absint( $request->get_param( 'ID' ) );

		$contact = get_contactdata( $ID );

		if ( ! is_a_contact( $contact ) ) {
			return self::ERROR_CONTACT_NOT_FOUND();
		}

		return self::SUCCESS_RESPONSE( [ 'tags' => $contact->get_tags() ] );
	}

	/**
	 * Add tags to a contact record
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_tags( WP_REST_Request $request ) {
		return $this->create_tags( $request );
	}

	/**
	 * Remove tags from a contact record
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function delete_tags( WP_REST_Request $request ) {
		$ID = absint( $request->get_param( 'ID' ) );

		$contact = get_contactdata( $ID );

		if ( ! is_a_contact( $contact ) ) {
			return self::ERROR_CONTACT_NOT_FOUND();
		}

		$tags = $request->get_json_params();

		$contact->remove_tag( $tags );

		return self::SUCCESS_RESPONSE( [ 'tags' => $contact->get_tags() ] );
	}

	/**
	 * Upload a file to a contact record.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_files( WP_REST_Request $request ) {


		$ID = absint( $request->get_param( 'ID' ) );

		$contact = get_contactdata( $ID );

		if ( ! is_a_contact( $contact ) ) {
			return self::ERROR_CONTACT_NOT_FOUND();
		}

		if ( empty( $_FILES ) ) {
			return self::ERROR_422( 'error', 'No files provided.' );
		}

		foreach ( $_FILES as $file ) {
			$result = $contact->upload_file( $file );

			if ( is_wp_error( $request ) ) {
				return $result;
			}
		}

		return self::SUCCESS_RESPONSE( [
			'total_items' => count( $contact->get_files() ),
			'items'       => $contact->get_files(),
		] );
	}

	/**
	 * Get contact files
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function read_files( WP_REST_Request $request ) {
		$ID = absint( $request->get_param( 'ID' ) );

		$contact = get_contactdata( $ID );

		if ( ! is_a_contact( $contact ) ) {
			return self::ERROR_CONTACT_NOT_FOUND();
		}

		$data = $contact->get_files();

		$limit  = absint( $request->get_param( 'limit' ) ) ?: 25;
		$offset = absint( $request->get_param( 'offset' ) ) ?: 0;

		return self::SUCCESS_RESPONSE( [
			'total_items' => count( $data ),
			'items'       => array_slice( $data, $offset, $limit )
		] );

	}

	/**
	 * Delete a file from the contact record.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_files( WP_REST_Request $request ) {

		$ID = absint( $request->get_param( 'ID' ) );

		$contact = get_contactdata( $ID );

		if ( ! is_a_contact( $contact ) ) {
			return self::ERROR_CONTACT_NOT_FOUND();
		}

		$files_to_delete = $request->get_json_params();

		if ( empty( $files_to_delete ) ) {
			return self::ERROR_422( 'error', 'Did not specify a file to delete.' );
		}

		foreach ( $files_to_delete as $file_name ) {
			$contact->delete_file( $file_name );
		}

		return self::SUCCESS_RESPONSE( [
			'total_items' => count( $contact->get_files() ),
			'items'       => $contact->get_files(),
		] );
	}

	/**
	 * Merge one or more contacts with the provided contact record.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function merge( WP_REST_Request $request ) {
		$ID = absint( $request->get_param( 'ID' ) );

		$contact = get_contactdata( $ID );

		if ( ! is_a_contact( $contact ) ) {
			return self::ERROR_CONTACT_NOT_FOUND();
		}

		$others = $request->get_json_params();

		foreach ( $others as $other ) {
			$contact->merge( $other );
		}

		return $contact;
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

	/**
	 * Permissions callback for files
	 *
	 * @return bool
	 */
	public function create_files_permissions_callback() {
		return current_user_can( 'download_contact_files' );
	}


	/**
	 * Permissions callback for files
	 *
	 * @return bool
	 */
	public function read_files_permissions_callback() {
		return current_user_can( 'download_contact_files' );
	}

	/**
	 * Permissions callback for files
	 *
	 * @return bool
	 */
	public function update_files_permissions_callback() {
		return current_user_can( 'download_contact_files' );
	}

	/**
	 * Permissions callback for files
	 *
	 * @return bool
	 */
	public function delete_files_permissions_callback() {
		return current_user_can( 'download_contact_files' );
	}
}