<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Groundhogg\background\Delete_Objects;
use Groundhogg\Background_Tasks;
use Groundhogg\Base_Object;
use Groundhogg\Base_Object_With_Meta;
use Groundhogg\Broadcast;
use Groundhogg\Campaign;
use Groundhogg\Classes\Activity;
use Groundhogg\Classes\Note;
use Groundhogg\Contact;
use Groundhogg\DB_Object;
use Groundhogg\Email;
use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Step;
use Groundhogg\Tag;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function Groundhogg\create_object_from_type;
use function Groundhogg\get_array_var;
use function Groundhogg\get_db;
use function Groundhogg\isset_not_empty;

//use Groundhogg\Webhook;

abstract class Base_Object_Api extends Base_Api {

	/**
	 * Maps the resource to a class based on object type.
	 * This function can be overridden my child classes to simply return the correct class instead...
	 *
	 * @return Base_Object|Base_Object_With_Meta
	 * @todo make this more intuitive, maybe add a get_object_class func to the data table?
	 *
	 */
	protected function get_object_class() {

		$object_type_class_map = apply_filters( 'groundhogg/api/v4/class_map', [
			'contact'   => Contact::class,
			'tag'       => Tag::class,
			'note'      => Note::class,
			'email'     => Email::class,
			'step'      => Step::class,
			'funnel'    => Funnel::class,
			'broadcast' => Broadcast::class,
			'event'     => Event::class,
			'activity'  => Activity::class,
			'campaign'  => Campaign::class
		] );

		$class = get_array_var( $object_type_class_map, $this->get_object_type() );

		if ( ! $class ) {
			return DB_Object::class;
		}

		return $class;
	}

	/**
	 * Action when object is created via the API
	 *
	 * @param $object
	 *
	 * @return void
	 */
	protected function do_object_created_action( $object ) {
		do_action( "groundhogg/api/{$this->get_object_type()}/created", $object );
	}

	/**
	 * Action when object is updated via the API
	 *
	 * @param $object
	 *
	 * @return void
	 */
	protected function do_object_updated_action( $object ) {
		do_action( "groundhogg/api/{$this->get_object_type()}/updated", $object );
	}

	/**
	 * Action when object is deleted via the API
	 *
	 * @param $object
	 *
	 * @return void
	 */
	protected function do_object_deleted_action( $object ) {
		do_action( "groundhogg/api/{$this->get_object_type()}/deleted", $object );
	}

	/**
	 * The name of the table resource to use
	 *
	 * @return string
	 */
	abstract public function get_db_table_name();

	/**
	 * Permissions callback for read
	 *
	 * @return bool
	 */
	public function read_permissions_callback() {
		return current_user_can( sprintf( 'view_%ss', $this->get_object_type() ) );
	}

	/**
	 * Permissions callback for read
	 *
	 * @return bool
	 */
	public function read_single_permissions_callback( WP_REST_Request $request ) {
		return $this->read_permissions_callback();
	}

	/**
	 * Permissions callback for update
	 *
	 * @return mixed
	 */
	public function update_permissions_callback() {
		return current_user_can( sprintf( 'edit_%ss', $this->get_object_type() ) );
	}

	/**
	 * Permissions callback for read
	 *
	 * @return bool
	 */
	public function update_single_permissions_callback( WP_REST_Request $request ) {
		return $this->update_permissions_callback();
	}

	/**
	 * Permissions callback for create
	 *
	 * @return mixed
	 */
	public function create_permissions_callback() {
		return current_user_can( sprintf( 'add_%ss', $this->get_object_type() ) );
	}

	/**
	 * Permissions callback for delete
	 *
	 * @return mixed
	 */
	public function delete_permissions_callback() {
		return current_user_can( sprintf( 'delete_%ss', $this->get_object_type() ) );
	}

	/**
	 * Permissions callback for read
	 *
	 * @return bool
	 */
	public function delete_single_permissions_callback( WP_REST_Request $request ) {
		return $this->delete_permissions_callback();
	}

	/**
	 * Returns the resource data table
	 *
	 * @return \Groundhogg\DB\DB|\Groundhogg\DB\Meta_DB|\Groundhogg\DB\Tags
	 */
	protected function get_db_table() {
		return get_db( $this->get_db_table_name() );
	}

	protected function get_route() {
		return $this->get_db_table_name();
	}

	/**
	 * Returns the data type of the resource
	 */
	protected function get_object_type() {
		return $this->get_db_table()->get_object_type();
	}

	/**
	 * Returns the primary key for the data type
	 */
	protected function get_primary_key() {
		return $this->get_db_table()->get_primary_key();
	}

	/**
	 * Returns a contact not found error
	 *
	 * @return WP_Error
	 */
	protected function ERROR_RESOURCE_NOT_FOUND() {
		return self::ERROR_404( 'error', sprintf( '%s not found.', $this->get_object_type() ) );
	}

	/**
	 * Create a new object from data
	 *
	 * @param array|int $data
	 * @param array     $meta
	 * @param bool      $force whether to force a new object to be created, otherwise it may return one that matches the given data
	 *
	 * @return Base_Object | Base_Object_With_Meta
	 */
	public function create_new_object( $data, $meta = [], $force = false ) {
		$class_name = $this->get_object_class();

		if ( $force ) {
			$object = new $class_name;
			$object->create( $data );
		} else {
			$object = new $class_name( $data );
		}

		if ( method_exists( $class_name, 'update_meta' ) && ! empty( $meta ) ) {
			$object->update_meta( $meta );
		}

		return $object;
	}

	/**
	 * Map items to their intended object
	 *
	 * @param $item object
	 *
	 * @return Base_Object_With_Meta|Base_Object
	 */
	public function map_raw_object_to_class( $item ) {

		$class_name = $this->get_object_class();

		return new $class_name( $item );
	}

	public function supports_meta() {
		return method_exists( $this->get_object_class(), 'update_meta' );
	}

	/**
	 * Register the REST routes
	 *
	 * @return mixed|void
	 */
	public function register_routes() {

		$route = $this->get_route();
		$key   = $this->get_primary_key();

		register_rest_route( self::NAME_SPACE, "/{$route}", [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create' ],
				'permission_callback' => [ $this, 'create_permissions_callback' ]
			],
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read' ],
				'permission_callback' => [ $this, 'read_permissions_callback' ]
			],
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update' ],
				'permission_callback' => [ $this, 'update_permissions_callback' ]
			],
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete' ],
				'permission_callback' => [ $this, 'delete_permissions_callback' ]
			],
		] );

		register_rest_route( self::NAME_SPACE, "/{$route}/(?P<{$key}>\d+)", [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read_single' ],
				'permission_callback' => [ $this, 'read_single_permissions_callback' ]
			],
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update_single' ],
				'permission_callback' => [ $this, 'update_single_permissions_callback' ]
			],
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_single' ],
				'permission_callback' => [ $this, 'delete_single_permissions_callback' ]
			],
		] );

		if ( $this->supports_meta() ) {

			register_rest_route( self::NAME_SPACE, "/{$route}/(?P<{$key}>\d+)/meta", [
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_meta' ],
					'permission_callback' => [ $this, 'update_single_permissions_callback' ]
				],
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'read_meta' ],
					'permission_callback' => [ $this, 'read_single_permissions_callback' ]
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_meta' ],
					'permission_callback' => [ $this, 'update_single_permissions_callback' ]
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_meta' ],
					'permission_callback' => [ $this, 'delete_single_permissions_callback' ]
				],
			] );

		}

		register_rest_route( self::NAME_SPACE, "/{$route}/(?P<{$key}>\d+)/relationships", [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_relationships' ],
				'permission_callback' => [ $this, 'update_single_permissions_callback' ]
			],
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read_relationships' ],
				'permission_callback' => [ $this, 'read_single_permissions_callback' ]
			],
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_relationships' ],
				'permission_callback' => [ $this, 'delete_single_permissions_callback' ]
			],
		] );

		register_rest_route( self::NAME_SPACE, "/{$route}/(?P<{$key}>\d+)/relationship", [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_single_relationship' ],
				'permission_callback' => [ $this, 'update_single_permissions_callback' ]
			],
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read_relationships' ],
				'permission_callback' => [ $this, 'read_single_permissions_callback' ]
			],
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_single_relationship' ],
				'permission_callback' => [ $this, 'delete_single_permissions_callback' ]
			],
		] );

		register_rest_route( self::NAME_SPACE, "/{$route}/(?P<{$key}>\d+)/duplicate", [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'duplicate_single' ],
				'permission_callback' => [ $this, 'create_permissions_callback' ]
			],
		] );

		register_rest_route( self::NAME_SPACE, "/{$route}/(?P<{$key}>\d+)/merge", [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'merge' ],
				'permission_callback' => [ $this, 'delete_permissions_callback' ]
			],
		] );
	}

	/**
	 * Given an arbitrary, if first party data is detected, group it into the data object
	 * Anything else should be added to the meta object
	 *
	 * @param array|WP_REST_Request $request The item
	 * @param array                 $ignore  keys to omit from both data and meta, leave where it is
	 *
	 * @return void
	 */
	protected function maybe_group_into_data_and_meta( &$request, array $ignore = [] ) {

		// Using data already, assume well-formed
		if ( isset( $request['data'] ) ) {
			return;
		}

		// if first party keys are detected
		// data keys are just basically the table columns
		$data = array_intersect_key( $request, $this->get_db_table()->get_columns() );

		if ( ! empty( $data ) ) {
			$request['data'] = $data;

			foreach ( $data as $key => $val ) {
				unset( $request[ $key ] );
			}
		}

		if ( $this->supports_meta() ) {

			if ( ! isset( $request['meta'] ) ) {
				$request['meta'] = [];

				return;
			}

			$ignore_keys = array_merge( $ignore, [ 'meta', 'data' ] );
			$ignore_keys = array_combine( $ignore_keys, $ignore_keys );

			// move anything else into meta...
			$meta = array_diff_key( $request, $ignore_keys );

			foreach ( $meta as $key => $value ) {
				unset( $request[ $key ] );
				$request['meta'][ $key ] = $value;
			}
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
			return self::ERROR_422( 'error', 'No data provided.' );
		}

		// If not passing multiple items, create a single resource
		if ( ! array_is_list( $items ) ) {
			return $this->create_single( $request );
		}

		$added = [];

		foreach ( $items as $item ) {
			$item = (array) $item;
			$this->maybe_group_into_data_and_meta( $item );

			$data = get_array_var( $item, 'data' ) ?: $item;
			$meta = get_array_var( $item, 'meta' );

			$object = $this->create_new_object( $data, $meta, $request->has_param( 'force' ) );

			if ( ! $object->exists() ) {
				continue;
			}

			$added[] = $object;

			$this->do_object_created_action( $object );
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

		$query = $request->get_params();

		$query = wp_parse_args( $query, [
			'limit'      => 25,
			'found_rows' => true,
		] );

		if ( isset_not_empty( $query, 'count' ) ) {
			$total = $this->get_db_table()->count( $query );

			return self::SUCCESS_RESPONSE( [
				'total_items' => $total,
			] );
		}

		$items = $this->get_db_table()->query( $query );
		$total = $query['found_rows'] ? $this->get_db_table()->found_rows() : count( $items );

		$items = array_map( [ $this, 'map_raw_object_to_class' ], $items );

		return self::SUCCESS_RESPONSE( [
			'total_items' => $total,
			'items'       => $items
		] );
	}

	/**
	 * Updates an object given an query and new data/meta
	 * Or updates given an array of objects
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function update( WP_REST_Request $request ) {

		$query = $request->get_param( 'query' ) ?: [];
		$data  = $request->get_param( 'data' ) ?: [];
		$meta  = $request->get_param( 'meta' ) ?: [];

		// assume updating in other format
		if ( empty( $query ) && empty( $data ) ) {

			$items = $request->get_json_params();

			if ( empty( $items ) ) {
				return self::ERROR_422();
			}

			$updated = [];

			foreach ( $items as $item ) {

				$id     = get_array_var( $item, $this->get_primary_key() );
				$object = $this->create_new_object( $id );

				if ( ! $object->exists() ) {
					continue;
				}

				$data = get_array_var( $item, 'data', [] );
				$meta = get_array_var( $item, 'meta', [] );

				$object->update( $data );

				// If the current object supports meta data...
				if ( method_exists( $object, 'update_meta' ) ) {
					$object->update_meta( $meta );
				}

				$updated[] = $object;

				$this->do_object_updated_action( $object );
			}

			return self::SUCCESS_RESPONSE( [
				'total_items' => count( $updated ),
				'items'       => $updated,
			] );
		}

		$items = $this->get_db_table()->query( $query );
		$items = array_map( [ $this, 'map_raw_object_to_class' ], $items );

		/**
		 * @var $object Base_Object|Base_Object_With_Meta
		 */
		foreach ( $items as $object ) {

			$object->update( $data );

			// If the current object supports meta data...
			if ( method_exists( $object, 'update_meta' ) ) {
				$object->update_meta( $meta );
			}

			$this->do_object_updated_action( $object );
		}

		return self::SUCCESS_RESPONSE( [
			'total_items' => count( $items ),
			'items'       => $items,
		] );
	}

	/**
	 * Delete items
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function delete( WP_REST_Request $request ) {

		$query = wp_parse_args( $request->get_params() );

		$query = wp_parse_args( $query, [
			'orderby'    => $this->get_primary_key(),
			'order'      => 'ASC', // OLDEST FIRST
			'limit'      => 100,
			'found_rows' => false,
		] );

		// Don't bother to check if there are any matching objects
		// Just add the background task for deleting them
		if ( $request->has_param( 'bg' ) ) {
			unset( $query['bg'] );
			Background_Tasks::add( new Delete_Objects( $this->get_db_table_name(), $query ) );

			return self::SUCCESS_RESPONSE();
		}

		if ( ! empty( $query ) ) {
			$items = $this->get_db_table()->query( $query );
		} else {
			$items = $request->get_json_params();
		}

		if ( empty( $items ) ) {
			return self::ERROR_403( 'error', 'No items defined.' );
		}

		$items = array_map( [ $this, 'map_raw_object_to_class' ], $items );

		$deleted_item_ids = [];

		/**
		 * @var $object Base_Object
		 */
		foreach ( $items as $object ) {
			$deleted_item_ids[] = $object->get_id();
			$object->delete();

			$this->do_object_deleted_action( $object );
		}

		return self::SUCCESS_RESPONSE( [
			'items'       => $deleted_item_ids,
			'total_items' => count( $items ),
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

		$this->maybe_group_into_data_and_meta( $request );

		$data = $request->get_param( 'data' );
		$meta = $request->get_param( 'meta' );

		$object = $this->create_new_object( $data, $meta, $request->has_param( 'force' ) );

		if ( ! $object->exists() ) {

			global $wpdb;

			return self::ERROR_400( 'error', 'Bad request.', [
				'data' => $data,
				'meta' => $meta,
				'wpdb' => $wpdb->last_error
			] );
		}

		$this->do_object_created_action( $object );

		return self::SUCCESS_RESPONSE( [
			'item' => $object
		] );

	}

	/**
	 * Get the object based on a primary key usage
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return Base_Object|Base_Object_With_Meta
	 */
	public function get_object_from_request( WP_REST_Request $request ) {
		$primary_key = absint( $request->get_param( $this->get_primary_key() ) );

		return $this->create_new_object( $primary_key );
	}

	/**
	 * Fetches a single contact record
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function read_single( WP_REST_Request $request ) {
		$primary_key = absint( $request->get_param( $this->get_primary_key() ) );

		$object = $this->create_new_object( $primary_key );

		if ( ! $object->exists() ) {
			return $this->ERROR_RESOURCE_NOT_FOUND();
		}

		return self::SUCCESS_RESPONSE( [ 'item' => $object ] );
	}

	/**
	 * Updates a contact given a contact array
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_single( WP_REST_Request $request ) {
		$primary_key = absint( $request->get_param( $this->get_primary_key() ) );

		$object = $this->create_new_object( $primary_key );

		if ( ! $object->exists() ) {
			return $this->ERROR_RESOURCE_NOT_FOUND();
		}

		$data = $request->get_param( 'data' );
		$meta = $request->get_param( 'meta' );

		$object->update( $data );

		// If the current object supports meta data...
		if ( method_exists( $object, 'update_meta' ) ) {
			$object->update_meta( $meta );
		}

		$this->do_object_updated_action( $object );

		return self::SUCCESS_RESPONSE( [ 'item' => $object ] );
	}

	/**
	 * Merge one or more objects
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function merge( WP_REST_Request $request ) {
		$primary_key = absint( $request->get_param( $this->get_primary_key() ) );

		$object = $this->create_new_object( $primary_key );

		if ( ! $object->exists() ) {
			return $this->ERROR_RESOURCE_NOT_FOUND();
		}

		if ( ! current_user_can( "" ) ) {
			$others = $request->has_param( 'others' )
				? wp_parse_list( $request->get_param( 'others' ) )
				: $request->get_json_params();
		}

		foreach ( $others as $other ) {

			$other = $this->create_new_object( $other );

			// unable to delete this one
			if ( ! current_user_can( "delete_{$this->get_object_type()}", $object ) ) {
				continue;
			}

			$object->merge( $other );
		}

		return $object;
	}

	/**
	 * Delete a contact
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_single( WP_REST_Request $request ) {
		$primary_key = absint( $request->get_param( $this->get_primary_key() ) );

		$object = $this->create_new_object( $primary_key );

		if ( ! $object->exists() ) {
			return $this->ERROR_RESOURCE_NOT_FOUND();
		}

		$object->delete();

		$this->do_object_deleted_action( $object );

		return self::SUCCESS_RESPONSE();
	}

	/**
	 * Dupliacte an object
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function duplicate_single( WP_REST_Request $request ) {
		$primary_key = absint( $request->get_param( $this->get_primary_key() ) );

		$object = $this->create_new_object( $primary_key );

		if ( ! $object->exists() ) {
			return $this->ERROR_RESOURCE_NOT_FOUND();
		}

		$data = $request->get_param( 'data' ) ?: [];
		$meta = $request->get_param( 'meta' ) ?: [];

		// If the current object supports meta data...
		if ( method_exists( $object, 'update_meta' ) ) {
			$newObject = $object->duplicate( $data, $meta );
		} // Otherwise
		else {
			$newObject = $object->duplicate( $data );
		}

		return self::SUCCESS_RESPONSE( [ 'item' => $newObject ] );
	}

	/**
	 * Create object meta
	 * accepts key value pairs...
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function create_meta( WP_REST_Request $request ) {
		return $this->update_meta( $request );
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function read_meta( WP_REST_Request $request ) {
		$primary_key = absint( $request->get_param( $this->get_primary_key() ) );

		$object = $this->create_new_object( $primary_key );

		if ( ! $object->exists() ) {
			return $this->ERROR_RESOURCE_NOT_FOUND();
		}

		return self::SUCCESS_RESPONSE( [
			'meta' => $object->get_meta()
		] );
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_meta( WP_REST_Request $request ) {
		$primary_key = absint( $request->get_param( $this->get_primary_key() ) );

		$object = $this->create_new_object( $primary_key );

		if ( ! $object->exists() ) {
			return $this->ERROR_RESOURCE_NOT_FOUND();
		}

		$meta = $request->get_json_params();

		$object->update_meta( $meta );

		$this->do_object_updated_action( $object );

		return self::SUCCESS_RESPONSE( [
			'item' => $object
		] );
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function delete_meta( WP_REST_Request $request ) {
		$primary_key = absint( $request->get_param( $this->get_primary_key() ) );

		$object = $this->create_new_object( $primary_key );

		if ( ! $object->exists() ) {
			return $this->ERROR_RESOURCE_NOT_FOUND();
		}

		$meta = $request->get_json_params();

		$object->delete_meta( $meta );

		$this->do_object_updated_action( $object );

		return self::SUCCESS_RESPONSE( [
			'item' => $object
		] );
	}

	/**
	 * Create many relationships
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_relationships( WP_REST_Request $request ) {
		$primary_key = absint( $request->get_param( $this->get_primary_key() ) );

		$object = $this->create_new_object( $primary_key );

		if ( ! $object->exists() ) {
			return $this->ERROR_RESOURCE_NOT_FOUND();
		}

		if ( $request->has_param( 'other_id' ) || $request->has_param( 'parent_id' ) || $request->has_param( 'child_id' ) ) {
			return $this->create_single_relationship( $request );
		}

		$relationships = $request->get_params();

		foreach ( $relationships as $relationship ) {

			$child_id    = get_array_var( $relationship, 'child_id', get_array_var( $relationship, 'other_id' ) );
			$child_type  = get_array_var( $relationship, 'child_type', get_array_var( $relationship, 'other_type' ) );
			$parent_id   = get_array_var( $relationship, 'parent_id' );
			$parent_type = get_array_var( $relationship, 'parent_type' );

			if ( $child_id && $child_type ) {

				// Add a child relationship
				$other = create_object_from_type( $child_id, $child_type );
				$object->create_relationship( $other );

			} else if ( $parent_id && $parent_type ) {

				// parent relationship
				$other = create_object_from_type( $parent_id, $parent_type );
				$object->create_relationship( $other, false );

			}

		}

		return self::SUCCESS_RESPONSE( [
			'item' => $object
		] );
	}

	/**
	 * Create a relationship between the given object and another object
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_single_relationship( WP_REST_Request $request ) {

		$primary_key = absint( $request->get_param( $this->get_primary_key() ) );

		$object = $this->create_new_object( $primary_key );

		if ( ! $object->exists() ) {
			return $this->ERROR_RESOURCE_NOT_FOUND();
		}

		$child_id   = $request->get_param( 'child_id' ) ?: $request->get_param( 'other_id' );
		$child_type = $request->get_param( 'child_type' ) ?: $request->get_param( 'other_type' );

		$parent_id   = $request->get_param( 'parent_id' );
		$parent_type = $request->get_param( 'parent_type' );

		if ( $child_id && $child_type ) {

			$other = create_object_from_type( $child_id, $child_type );
			$object->create_relationship( $other );

		} else if ( $parent_id && $parent_type ) {

			$other = create_object_from_type( $parent_id, $parent_type );
			$object->create_relationship( $other, false );
		}

		return self::SUCCESS_RESPONSE( [
			'item' => $object
		] );
	}

	/**
	 * Create a relationship between the given object and another object
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function read_relationships( WP_REST_Request $request ) {

		$primary_key = absint( $request->get_param( $this->get_primary_key() ) );

		$object = $this->create_new_object( $primary_key );

		if ( ! $object->exists() ) {
			return $this->ERROR_RESOURCE_NOT_FOUND();
		}

		$child_type  = $request->get_param( 'child_type' ) ?: $request->get_param( 'other_type' );
		$parent_type = $request->get_param( 'parent_type' );

		if ( $parent_type ) {
			$items = $object->get_related_objects( $parent_type, false );
		} else {
			$items = $object->get_related_objects( $child_type );
		}

		return self::SUCCESS_RESPONSE( [
			'items' => $items
		] );
	}

	/**
	 * Delete many relationships
	 *f
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_relationships( WP_REST_Request $request ) {
		$primary_key = absint( $request->get_param( $this->get_primary_key() ) );

		$object = $this->create_new_object( $primary_key );

		if ( ! $object->exists() ) {
			return $this->ERROR_RESOURCE_NOT_FOUND();
		}

		if ( $request->has_param( 'other_id' ) || $request->has_param( 'parent_id' ) || $request->has_param( 'child_id' ) ) {
			return $this->delete_single_relationship( $request );
		}

		$relationships = $request->get_params();

		foreach ( $relationships as $relationship ) {

			$child_id    = get_array_var( $relationship, 'child_id', get_array_var( $relationship, 'other_id' ) );
			$child_type  = get_array_var( $relationship, 'child_type', get_array_var( $relationship, 'other_type' ) );
			$parent_id   = get_array_var( $relationship, 'parent_id' );
			$parent_type = get_array_var( $relationship, 'parent_type' );

			if ( $child_id && $child_type ) {

				// Add a child relationship
				$other = create_object_from_type( $child_id, $child_type );
				$object->delete_relationship( $other );

			} else if ( $parent_id && $parent_type ) {

				// parent relationship
				$other = create_object_from_type( $parent_id, $parent_type );
				$object->delete_relationship( $other, false );

			}
		}

		return self::SUCCESS_RESPONSE( [
			'item' => $object
		] );
	}

	/**
	 * Create a relationship between the given object and another object
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_single_relationship( WP_REST_Request $request ) {

		$primary_key = absint( $request->get_param( $this->get_primary_key() ) );

		$object = $this->create_new_object( $primary_key );

		if ( ! $object->exists() ) {
			return $this->ERROR_RESOURCE_NOT_FOUND();
		}

		$child_id   = $request->get_param( 'child_id' ) ?: $request->get_param( 'other_id' );
		$child_type = $request->get_param( 'child_type' ) ?: $request->get_param( 'other_type' );

		$parent_id   = $request->get_param( 'parent_id' );
		$parent_type = $request->get_param( 'parent_type' );

		if ( $child_id && $child_type ) {

			$other = create_object_from_type( $child_id, $child_type );
			$object->delete_relationship( $other );

		} else if ( $parent_id && $parent_type ) {

			$other = create_object_from_type( $parent_id, $parent_type );
			$object->delete_relationship( $other, false );
		}

		return self::SUCCESS_RESPONSE( [
			'item' => $object
		] );
	}

}
