<?php

namespace Groundhogg\Api\V4;

use Groundhogg\Custom_Object;
use Groundhogg\Custom_Object_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom Objects API
 *
 * One instance per registered custom object type. Exposes the standard
 * Base_Object_Api surface (CRUD, meta, relationships, duplicate, merge) at
 * gh/v4/objects/{type}.
 *
 * @since   File available since Release 4.8
 * @package Api\V4
 */
class Custom_Objects_Api extends Base_Object_Api {

	/**
	 * @var Custom_Object_Type
	 */
	protected $object_type_config;

	/**
	 * @param Custom_Object_Type $object_type_config
	 */
	public function __construct( Custom_Object_Type $object_type_config ) {
		$this->object_type_config = $object_type_config;
		parent::__construct();
	}

	/**
	 * The DB Manager key / object type slug.
	 *
	 * @return string
	 */
	public function get_db_table_name() {
		return $this->object_type_config->get_slug();
	}

	/**
	 * Namespace custom object routes under /objects/ to avoid colliding with
	 * first-party routes.
	 *
	 * @return string
	 */
	protected function get_route() {
		return 'objects/' . $this->object_type_config->get_slug();
	}

	/**
	 * @return string
	 */
	protected function get_object_class() {
		return $this->object_type_config->get_object_class();
	}

	/**
	 * @param object|int $item
	 *
	 * @return Custom_Object
	 */
	public function map_raw_object_to_class( $item ) {
		$class = $this->get_object_class();

		return new $class( $item, null, $this->get_db_table_name() );
	}

	/**
	 * @param array|int $data
	 * @param array     $meta
	 * @param bool      $force
	 *
	 * @return Custom_Object
	 */
	public function create_new_object( $data, $meta = [], $force = false ) {

		$class = $this->get_object_class();
		$type  = $this->get_db_table_name();

		if ( $force ) {
			$object = new $class( 0, null, $type );
			$object->create( (array) $data );
		} else {
			$object = new $class( $data, null, $type );
		}

		if ( ! empty( $meta ) && method_exists( $object, 'update_meta' ) ) {
			$object->update_meta( $meta );
		}

		return $object;
	}

	/* ---------------------------------------------------------------------
	 * Permissions - custom types don't have their own capabilities, so gate
	 * every operation on a configurable capability (default edit_contacts /
	 * view_contacts for reads).
	 * ------------------------------------------------------------------- */

	public function read_permissions_callback() {
		return current_user_can( $this->object_type_config->get_capability( 'view' ) );
	}

	public function create_permissions_callback() {
		return current_user_can( $this->object_type_config->get_capability( 'create' ) );
	}

	public function update_permissions_callback() {
		return current_user_can( $this->object_type_config->get_capability( 'edit' ) );
	}

	public function delete_permissions_callback() {
		return current_user_can( $this->object_type_config->get_capability( 'delete' ) );
	}

	protected function current_user_can_read( $object ) {
		return current_user_can( $this->object_type_config->get_capability( 'view' ), $object );
	}

	protected function current_user_can_update( $object ) {
		return current_user_can( $this->object_type_config->get_capability( 'edit' ), $object );
	}

	protected function current_user_can_delete( $object ) {
		return current_user_can( $this->object_type_config->get_capability( 'delete' ), $object );
	}
}
