<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Groundhogg\Base_Object;
use Groundhogg\Base_Object_With_Meta;
use Groundhogg\Broadcast;
use Groundhogg\Classes\Note;
use Groundhogg\Contact;
use Groundhogg\Email;
use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Step;
use Groundhogg\Tag;
use function Groundhogg\get_array_var;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use function Groundhogg\get_db;

abstract class Base_Object_Api extends Base_Api {

	/**
	 * Maps the resource to a class based on object type.
	 * This function can be overridden my child classes to simply return the correct class instead...
	 *
	 * @return mixed|string
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
		] );

		$class = get_array_var( $object_type_class_map, $this->get_object_type() );

		if ( ! $class ) {
			return Base_Object::class;
		}

		return $class;
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
	abstract public function read_permissions_callback();

	/**
	 * Permissions callback for update
	 *
	 * @return mixed
	 */
	abstract public function update_permissions_callback();

	/**
	 * Permissions callback for create
	 *
	 * @return mixed
	 */
	abstract public function create_permissions_callback();

	/**
	 * Permissions callback for delete
	 *
	 * @return mixed
	 */
	abstract public function delete_permissions_callback();

	/**
	 * Returns the resource data table
	 *
	 * @return \Groundhogg\DB\DB|\Groundhogg\DB\Meta_DB|\Groundhogg\DB\Tags
	 */
	protected function get_db_table() {
		return get_db( $this->get_db_table_name() );
	}

	protected function get_endpoint() {
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
	 * @param $data
	 *
	 * @return Base_Object | Base_Object_With_Meta
	 */
	public function create_new_object( $data ) {
		$class_name = $this->get_object_class();

		return new $class_name( $data );
	}

	/**
	 * Map items to their intended object
	 *
	 * @param $item object
	 *
	 * @return Base_Object_With_Meta|Base_Object
	 */
	public function map_raw_object_to_class( $item ) {

		$class_name  = $this->get_object_class();
		$primary_key = $this->get_primary_key();

		return is_object( $item ) ? new $class_name( $item->$primary_key ) : new $class_name( $item );
	}

	/**
	 * Register the REST routes
	 *
	 * @return mixed|void
	 */
	public function register_routes() {

		$endpoint = $this->get_endpoint();
		$key      = $this->get_primary_key();

		register_rest_route( self::NAME_SPACE, "/{$endpoint}", [
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

		register_rest_route( self::NAME_SPACE, "/{$endpoint}/(?P<{$key}>\d+)", [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read_single' ],
				'permission_callback' => [ $this, 'read_permissions_callback' ]
			],
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update_single' ],
				'permission_callback' => [ $this, 'update_permissions_callback' ]
			],
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_single' ],
				'permission_callback' => [ $this, 'delete_permissions_callback' ]
			],
		] );

		if ( method_exists( $this->get_object_class(), 'update_meta' ) ) {

			register_rest_route( self::NAME_SPACE, "/{$endpoint}/(?P<{$key}>\d+)/meta", [
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_meta' ],
					'permission_callback' => [ $this, 'create_permissions_callback' ]
				],
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'read_meta' ],
					'permission_callback' => [ $this, 'read_permissions_callback' ]
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_meta' ],
					'permission_callback' => [ $this, 'update_permissions_callback' ]
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_meta' ],
					'permission_callback' => [ $this, 'delete_permissions_callback' ]
				],
			] );

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

		// Create single resource
		if ( get_array_var( $items, 'data' ) ) {
			return $this->create_single( $request );
		}

		$added = [];

		foreach ( $items as $item ) {

			$data = get_array_var( $item, 'data' ) ?: $item;
			$meta = get_array_var( $item, 'meta' );

			$object = $this->create_new_object( $data );

			if ( ! $object->exists() ) {
				continue;
			}

			// If the current object supports meta data...
			if ( method_exists( $object, 'update_meta' ) && ! empty( $meta ) && is_array( $meta ) ) {
				foreach ( $meta as $key => $value ) {
					$object->update_meta( $key, $value );
				}
			}

			$added[] = $object;
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

		$args = array(
			'where'   => $request->get_param( 'where' ) ?: [],
			'limit'   => absint( $request->get_param( 'limit' ) ) ?: 25,
			'offset'  => absint( $request->get_param( 'offset' ) ) ?: 0,
			'order'   => strtoupper( sanitize_text_field( $request->get_param( 'order' ) ) ) ?: 'DESC',
			'orderBy' => sanitize_text_field( $request->get_param( 'orderBy' ) ) ?: $this->get_primary_key(),
			'select'  => sanitize_text_field( $request->get_param( 'select' ) ) ?: '*',
			'search'  => sanitize_text_field( $request->get_param( 'search' ) ),
		);

		$total = $this->get_db_table()->count( $args );
		$items = $this->get_db_table()->query( $args );

		$items = array_map( [ $this, 'map_raw_object_to_class' ], $items );

		return self::SUCCESS_RESPONSE( [ 'items' => $items, 'total_items' => $total ] );
	}

	/**
	 * Updates a contact given a contact array
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function update( WP_REST_Request $request ) {

		$where = $request->get_param( 'where' );
		$data  = $request->get_param( 'data' ) ?: [];
		$meta  = $request->get_param( 'meta' ) ?: [];

		// assume updating in other format
		if ( empty( $data ) || empty( $where ) ) {

			$items = $request->get_json_params();

			if ( empty( $items ) ){
				return self::ERROR_422();
			}

			$updated = [];

			foreach ( $items as $item ) {

				$id     = get_array_var( $item, $this->get_primary_key() );
				$object = $this->create_new_object( $id );

				if ( ! $object->exists() ){
					continue;
				}

				$data = get_array_var( $item, 'data', [] );
				$meta = get_array_var( $item, 'meta', [] );

				$object->update( $data );

				// If the current object supports meta data...
				if ( method_exists( $object, 'update_meta' ) && ! empty( $meta ) && is_array( $meta ) ) {
					foreach ( $meta as $key => $value ) {
						$object->update_meta( $key, $value );
					}
				}

				$updated[] = $object;
			}

			return self::SUCCESS_RESPONSE( [
				'items'       => $updated,
				'total_items' => count( $updated ),
			] );
		}

		$args = array(
			'where' => $where,
		);

		$items = $this->get_db_table()->query( $args );
		$items = array_map( [ $this, 'map_raw_object_to_class' ], $items );

		/**
		 * @var $object Base_Object
		 */
		foreach ( $items as $object ) {

			$object->update( $data );

			// If the current object supports meta data...
			if ( method_exists( $object, 'update_meta' ) && ! empty( $meta ) && is_array( $meta ) ) {
				foreach ( $meta as $key => $value ) {
					$object->update_meta( $key, $value );
				}
			}
		}

		return self::SUCCESS_RESPONSE( [
			'items'       => $items,
			'total_items' => count( $items ),
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

		$where = $request->get_param( 'where' );

		if ( ! empty( $where ) ) {
			$args = array(
				'where' => $where,
			);

			$items = $this->get_db_table()->query( $args );
			$items = array_map( [ $this, 'map_raw_object_to_class' ], $items );
		} else {
			$items = $request->get_json_params();
			$items = array_map( [ $this, 'create_new_object' ], $items );
		}

		/**
		 * @var $object Base_Object
		 */
		foreach ( $items as $object ) {
			$object->delete();
		}

		return self::SUCCESS_RESPONSE( [
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

		$data = $request->get_param( 'data' );
		$meta = $request->get_param( 'meta' );

//		wp_send_json( [
//			$data,
//			$meta
//		] );

		$object = $this->create_new_object( $data );

		if ( ! $object->exists() ) {

			global $wpdb;

			return self::ERROR_400( 'error', 'Bad request.', [
				'data' => $data,
				'meta' => $meta,
				'wpdb' => $wpdb->last_error
			] );
		}

		// If the current object supports meta data...
		if ( method_exists( $object, 'update_meta' ) && ! empty( $meta ) && is_array( $meta ) ) {
			foreach ( $meta as $key => $value ) {
				$object->update_meta( $key, $value );
			}
		}

		return self::SUCCESS_RESPONSE( [
			'item' => $object
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
		$primary_key = absint( $request->get_param( $this->get_primary_key() ) );

		$object = $this->create_new_object( $primary_key );

		if ( ! $object->exists() ) {
			return self::ERROR_404();
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
			return self::ERROR_404();
		}

		$data = $request->get_param( 'data' );
		$meta = $request->get_param( 'meta' );

		$object->update( $data );

		// If the current object supports meta data...
		if ( method_exists( $object, 'update_meta' ) && ! empty( $meta ) && is_array( $meta ) ) {
			foreach ( $meta as $key => $value ) {
				$object->update_meta( $key, $value );
			}
		}

		return self::SUCCESS_RESPONSE( [ 'item' => $object ] );
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
			return self::ERROR_404();
		}

		$object->delete();

		return self::SUCCESS_RESPONSE();
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
			return self::ERROR_404();
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
			return self::ERROR_404();
		}

		$meta = $request->get_json_params();

		foreach ( $meta as $key => $value ) {
			$object->update_meta( $key, $value );
		}

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
			return self::ERROR_404();
		}

		$meta = $request->get_json_params();

		foreach ( $meta as $key ) {
			$object->delete_meta( $key );
		}

		return self::SUCCESS_RESPONSE( [
			'item' => $object
		] );
	}

}