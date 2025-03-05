<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Groundhogg\Admin\Contacts\Tables\Contacts_Table;
use Groundhogg\Background_Tasks;
use Groundhogg\Contact;
use Groundhogg\Contact_Query;
use Groundhogg\Plugin;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function Groundhogg\generate_contact_with_map;
use function Groundhogg\get_array_var;
use function Groundhogg\get_contactdata;
use function Groundhogg\is_a_contact;
use function Groundhogg\is_email_address_in_use;
use function Groundhogg\isset_not_empty;
use function Groundhogg\sanitize_object_meta;

class Contacts_Api extends Base_Object_Api {

	public function register_routes() {

		parent::register_routes();

		register_rest_route( self::NAME_SPACE, '/contacts/(?P<ID>\d+)/tags', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_tags' ],
				'permission_callback' => [ $this, 'update_single_permissions_callback' ]
			],
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read_tags' ],
				'permission_callback' => [ $this, 'update_single_permissions_callback' ]
			],
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_tags' ],
				'permission_callback' => [ $this, 'update_single_permissions_callback' ]
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
				'permission_callback' => [ $this, 'delete_permissions_callback' ]
			],
		] );

		register_rest_route( self::NAME_SPACE, '/contacts/(?P<ID>\d+)/inbox', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read_inbox' ],
				'permission_callback' => [ $this, 'read_single_permissions_callback' ]
			],
		] );

		register_rest_route( self::NAME_SPACE, '/contacts/table/row', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'admin_table_row' ],
				'permission_callback' => [ $this, 'read_single_permissions_callback' ]
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
		if ( $request->get_param( 'data' ) || $request->get_param( 'email' ) ) {
			return $this->create_single( $request );
		}

		$items = $request->get_json_params();

		if ( empty( $items ) ) {
			return self::ERROR_422( 'error', 'No contact data provided.' );
		}

		$added = [];

		foreach ( $items as $item ) {

			if ( isset_not_empty( $item, 'email' ) ) {

				$existed = is_email_address_in_use( $item['email'] );

				try {
					$contact = generate_contact_with_map( $item, [], [
						'type' => 'api',
						'name' => __( 'REST API', 'groundhogg' )
					] );
				} catch ( \Exception $e ) {
					return self::ERROR_500( 'error', $e->getMessage() );
				}

				if ( ! is_a_contact( $contact ) ) {
					return self::ERROR_500( 'error', 'Unable to create contact record.' );
				}

				$added[] = $contact;

				if ( $existed ) {
					$this->do_object_updated_action( $contact );
				} else {
					$this->do_object_created_action( $contact );
				}

				continue;
			}

			$data = get_array_var( $item, 'data' );
			$meta = get_array_var( $item, 'meta' );
			$tags = get_array_var( $item, 'tags' );

			// get the email address
			$email_address = get_array_var( $data, 'email' );

			// skip if an email address was not provided
			if ( ! $email_address ) {
				continue;
			} // If the email address is in use, update the contact instead
			else if ( is_email_address_in_use( $email_address ) ) {
				$contact = new Contact( $email_address );
				$contact->update( $data );
			} // Otherwise, create the contact record
			else {
				$contact = new Contact( $data );
			}

			$contact->update_meta( $meta );
			$contact->apply_tag( $tags );

			$added[] = $contact;

			$this->do_object_created_action( $contact );
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
		$query  = (array) $request->get_param( 'query' ) ?: $request->get_params();
		$search = sanitize_text_field( wp_unslash( $request->get_param( 'search' ) ) );

		if ( ! key_exists( 'search', $query ) && ! empty( $search ) ) {
			$query['search'] = $search;
		}

		$contact_query = new Contact_Query();

		$default_query_limit = absint( $request->get_param( 'limit' ) ) ?: 100;
		$default_offset      = absint( $request->get_param( 'offset' ) ) ?: 0;

		$query = wp_parse_args( $query, [
			'number'     => $default_query_limit,
			'offset'     => $default_offset,
			'found_rows' => true
		] );

		if ( $request->get_param( 'count' ) ) {

			$count = $contact_query->count( $query );

			return self::SUCCESS_RESPONSE( [
				'total_items' => $count,
			] );
		}

		$contacts = $contact_query->query( $query );
		$count    = $contact_query->found_items;

		$contacts = array_map( [ $this, 'map_raw_object_to_class' ], $contacts );

		return self::SUCCESS_RESPONSE( [
			'total_items' => $count,
			'items'       => $contacts,
		] );
	}

	/**
	 * Unable to edit
	 *
	 * @param $contact Contact
	 *
	 * @return WP_Error
	 */
	public static function ERROR_INVALID_PERMISSIONS_CANT_EDIT( $contact ) {

		if ( ! is_a_contact( $contact ) || ! $contact->exists() ) {
			return self::ERROR_401( 'error', 'The requested contact does not exist.', [
				'given' => $contact
			] );
		}

		return self::ERROR_401( 'error', 'You do not have sufficient permissions to edit this contact.', [
			'id' => $contact->get_id()
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

				$id            = get_array_var( $item, 'ID' );
				$data          = get_array_var( $item, 'data', [] );
				$meta          = get_array_var( $item, 'meta', [] );
				$email_address = get_array_var( $data, 'email' );
				$remove_tags   = get_array_var( $item, 'remove_tags', [] );
				$add_tags      = array_reduce( [ 'tags', 'add_tags', 'apply_tags' ], function ( $tags, $key ) use ( $item ) {
					return array_merge( $tags, get_array_var( $item, $key, [] ) );
				}, [] );

				if ( ! $id && ! $email_address ) {
					continue;
				}

				$contact = new Contact( $id ?: $email_address );

				if ( ! $contact->exists() ) {
					continue;
				}

				if ( ! current_user_can( 'edit_contact', $contact ) ) {
					continue;
				}

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

				$this->do_object_updated_action( $contact );
			}

			return self::SUCCESS_RESPONSE( [
				'total_items' => count( $contacts ),
				'items'       => $contacts,
			] );
		}

		if ( $request->has_param( 'bg' ) ) {

			Background_Tasks::update_contacts( $query, [
				'data'        => $data,
				'meta'        => $meta,
				'add_tags'    => $add_tags,
				'remove_tags' => $remove_tags,
			] );

			return self::SUCCESS_RESPONSE();
		}

		$contact_query = new Contact_Query();
		$contacts      = $contact_query->query( $query, true );
		$updated       = 0;

		/**
		 * @var $contact Contact
		 */
		foreach ( $contacts as $contact ) {

			$_data = $data;

			if ( ! current_user_can( 'edit_contact', $contact ) ) {
				continue;
			}

			// get the email address if part of the request
			$email_address = get_array_var( $data, 'email' );

			// remove email from update request if in use by another contact
			if ( $email_address && is_email_address_in_use( $email_address, $contact ) ) {
				unset( $_data['email'] );
			}

			$contact->update( $_data );
			$contact->update_meta( $meta );
			$contact->apply_tag( $add_tags );
			$contact->remove_tag( $remove_tags );

			$updated ++;

			$this->do_object_updated_action( $contact );
		}

		if ( $request->has_param( 'total_only' ) ) {
			return self::SUCCESS_RESPONSE( [
				'total_items' => $updated,
			] );
		}

		return self::SUCCESS_RESPONSE( [
			'total_items' => $updated,
			'items'       => $contacts,
		] );
	}

	/**
	 * Fetch a contact given the user ID
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return bool
	 */
	protected function by_user_id( WP_REST_Request $request ) {
		return $request->has_param( 'by_user_id' ) && $request->get_param( 'by_user_id' );
	}

	/**
	 * Fetch a contact record
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function read_single( WP_REST_Request $request ) {

		$primary_key = absint( $request->get_param( $this->get_primary_key() ) );

		$object = new Contact( $primary_key, $this->by_user_id( $request ) );

		if ( ! $object->exists() ) {
			return $this->ERROR_RESOURCE_NOT_FOUND();
		}

		return self::SUCCESS_RESPONSE( [ 'item' => $object ] );
	}

	/**
	 * Create a contact
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function create_single( WP_REST_Request $request ) {

		// First order stuff
		if ( $request->has_param( 'email' ) ) {
			$fields  = $request->get_json_params();
			$existed = is_email_address_in_use( $request->get_param( 'email' ) );

			try {
				$contact = generate_contact_with_map( $fields, [], [
					'type' => 'api',
					'name' => __( 'REST API', 'groundhogg' )
				] );
			} catch ( \Exception $e ) {
				return self::ERROR_500( 'error', $e->getMessage() );
			}

			if ( ! is_a_contact( $contact ) ) {
				return self::ERROR_500( 'error', 'Unable to create contact record.' );
			}

			if ( $existed ) {
				$this->do_object_updated_action( $contact );
			} else {
				$this->do_object_created_action( $contact );
			}

			return self::SUCCESS_RESPONSE( [
				'item' => $contact
			] );
		}

		$data = $request->get_param( 'data' );
		$meta = $request->get_param( 'meta' );
		$tags = $request->get_param( 'tags' );

		// get the email address
		$email_address = get_array_var( $data, 'email' );

		if ( ! $email_address ) {
			return self::ERROR_422( 'error', 'An email address is required.' );
		}

		if ( ! is_email( $email_address ) ) {
			return self::ERROR_400( 'invalid_email', 'The provided email address is not valid.' );
		}

		$existed = false;

		// If the email address is in use, treat as an update
		if ( is_email_address_in_use( $email_address ) ) {
			$existed = true;
			$contact = new Contact( $email_address );
			$contact->update( $data );
		} // Create new contact record
		else {
			$contact = new Contact( $data );
		}

		$contact->update_meta( $meta );

		$contact->apply_tag( $tags );

		if ( $existed ) {
			$this->do_object_updated_action( $contact );
		} else {
			$this->do_object_created_action( $contact );
		}

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

		$contact = get_contactdata( $ID, $this->by_user_id( $request ) );

		if ( ! is_a_contact( $contact ) ) {
			return self::ERROR_CONTACT_NOT_FOUND();
		}

		if ( ! current_user_can( 'edit_contact', $contact ) ) {
			return self::ERROR_INVALID_PERMISSIONS_CANT_EDIT( $contact );
		}

		$add_tag_params = [ 'tags', 'add_tags', 'apply_tags' ];
		$add_tags       = [];

		foreach ( $add_tag_params as $add_tag_param ) {
			if ( $request->has_param( $add_tag_param ) ) {
				$add_tags = array_merge( $add_tags, $request->get_param( $add_tag_param ) );
				break;
			}
		}

		$data        = $request->get_param( 'data' );
		$meta        = $request->get_param( 'meta' );
		$remove_tags = $request->get_param( 'remove_tags' );

		if ( empty( $data ) && empty( $meta ) && empty( $add_tags ) && empty( $remove_tags ) ) {
			return self::ERROR_401( 'no_changes', 'No changes were made.' );
		}

		if ( ! empty( $data ) ) {
			// get the email address
			$email_address = get_array_var( $data, 'email' );

			// will return false if the email address is not being used
			if ( $email_address && is_email_address_in_use( $email_address, $contact ) ) {
				return self::ERROR_409( 'error', 'Email address already in use.' );
			}

			$contact->update( $data );
		}

		if ( ! empty( $meta ) ) {
			foreach ( $meta as $key => $value ) {
				$contact->update_meta( sanitize_key( $key ), sanitize_object_meta( $value ) );
			}
		}

		if ( ! empty( $add_tags ) ) {
			$contact->apply_tag( $add_tags );
		}

		if ( ! empty( $remove_tags ) ) {
			$contact->remove_tag( $remove_tags );
		}

		$this->do_object_updated_action( $contact );

		return self::SUCCESS_RESPONSE( [ 'item' => $contact ] );
	}

	/**
	 * Delete a contact record
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_single( WP_REST_Request $request ) {
		$primary_key = absint( $request->get_param( $this->get_primary_key() ) );

		$object = new Contact( $primary_key, $this->by_user_id( $request ) );

		if ( ! $object->exists() ) {
			return $this->ERROR_RESOURCE_NOT_FOUND();
		}

		$object->delete();

		$this->do_object_deleted_action( $object );

		return self::SUCCESS_RESPONSE();
	}

	/**
	 * Delete contacts
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function delete( WP_REST_Request $request ) {

		$query_vars = $request->has_param( 'query' ) ? wp_parse_args( $request->get_param( 'query' ) ?: [] ) : wp_parse_args( $request->get_params() );

		$query_vars = wp_parse_args( $query_vars, [
			'orderby'       => $this->get_primary_key(),
			'order'         => 'ASC',
			'number'        => 500,
			'no_found_rows' => false,
		] );

		// Don't bother to check if there are any matching contacts
		// Just add the background task for deleting them
		if ( $request->has_param( 'bg' ) ) {
			unset( $query_vars['bg'] );
			Background_Tasks::delete_contacts( $query_vars );

			return self::SUCCESS_RESPONSE();
		}

		$query = new Contact_Query( $query_vars );

		$items = $query->query( null, true );
		$found = $query->found_items;

		if ( empty( $items ) ) {
			return self::ERROR_403( 'error', 'No items defined.' );
		}

		$deleted_item_ids = [];
		$deleted_items    = 0;

		/**
		 * @var $contact Contact
		 */
		foreach ( $items as $contact ) {
			$deleted_item_ids[] = $contact->get_id();

			$contact->delete();

			$this->do_object_deleted_action( $contact );

			$deleted_items ++;
		}

		return self::SUCCESS_RESPONSE( [
			'items'           => $deleted_item_ids,
			'items_deleted'   => $deleted_items,
			'items_remaining' => $found - $deleted_items,
		] );
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

		if ( ! current_user_can( 'edit_contact', $contact ) ) {
			return self::ERROR_INVALID_PERMISSIONS_CANT_EDIT( $contact );
		}

		$tags = $request->get_json_params();

		$contact->apply_tag( $tags );

		$this->do_object_created_action( $contact );

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

		if ( ! current_user_can( 'view_contact', $contact ) ) {
			return self::ERROR_401();
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

		if ( ! current_user_can( 'edit_contact', $contact ) ) {
			return self::ERROR_INVALID_PERMISSIONS_CANT_EDIT( $contact );
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

		if ( ! current_user_can( 'edit_contact', $contact ) ) {
			return self::ERROR_INVALID_PERMISSIONS_CANT_EDIT( $contact );
		}

		$others = $request->has_param( 'others' )
			? wp_parse_list( $request->get_param( 'others' ) )
			: $request->get_json_params();

		foreach ( $others as $other ) {

			if ( ! current_user_can( 'delete_contact', $other ) ) {
				continue;
			}

			$contact->merge( $other );
		}

		return $contact;
	}

	public function read_inbox( WP_REST_Request $request ) {

		$ID = absint( $request->get_param( 'ID' ) );

		$contact = get_contactdata( $ID );

		if ( ! is_a_contact( $contact ) ) {
			return self::ERROR_CONTACT_NOT_FOUND();
		}

		$msgs = Plugin::instance()->imap_inbox->fetch( $contact );

		return self::SUCCESS_RESPONSE( [
			'messages' => $msgs,
		] );
	}

	/**
	 * protect delete endpoint
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return bool|WP_Error
	 */
	public function delete_single_permissions_callback( WP_REST_Request $request ) {

		$contact = $this->get_object_from_request( $request );

		if ( ! $contact->exists() ) {
			return self::ERROR_404();
		}

		return current_user_can( 'delete_contact', $contact );

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
		return current_user_can( 'delete_files' );
	}

	/**
	 * Get the admin table row
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function admin_table_row( WP_REST_Request $request ) {
		$contact = get_contactdata( $request->get_param( 'contact' ) );

		if ( ! current_user_can( 'view_contact', $contact ) ) {
			return self::ERROR_401();
		}

		$contactTable = new Contacts_Table;

		ob_start();

		$contactTable->single_row( $contact );

		$row = ob_get_clean();

		return self::SUCCESS_RESPONSE( [
			'row' => $row
		] );
	}
}
