<?php

namespace Groundhogg\DB;

use Groundhogg\Custom_Object;
use Groundhogg\Custom_Object_Type;
use function Groundhogg\get_db;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom Object Table
 *
 * A lightweight, type-scoped view onto the shared gh_objects table. One instance
 * is registered with the DB Manager for every registered custom object type
 * (@see \Groundhogg\Custom_Object_Type), which lets get_db( $type ),
 * create_object_from_type() and the REST API resolve a custom type exactly the
 * same way they resolve a first-party table.
 *
 * Every read, write and query is constrained to this instance's object_type so
 * that ids belonging to other types are never touched.
 *
 * @since       File available since Release 4.8
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
 */
class Custom_Object_Table extends Custom_Objects {

	/**
	 * @var string the registered object type slug
	 */
	protected $object_type;

	/**
	 * @var array|null human readable labels, consumed by DB::__get()
	 */
	protected $labels = null;

	/**
	 * @param string $object_type the registered object type slug
	 */
	public function __construct( string $object_type ) {

		$this->object_type = $object_type;

		parent::__construct();

		$type = Custom_Object_Type::get( $object_type );

		if ( $type ) {
			$this->labels = [
				'singular' => $type->get_singular(),
				'plural'   => $type->get_plural(),
			];
		}
	}

	/**
	 * The registered slug, e.g. "project"
	 *
	 * @return string
	 */
	public function get_object_type() {
		return $this->object_type;
	}

	/**
	 * All custom object types share one meta table.
	 *
	 * @return Custom_Object_Meta
	 */
	public function get_meta_table() {
		return get_db( 'custom_object_meta' );
	}

	/**
	 * The shared table is installed once by Custom_Objects, registered directly
	 * with the DB Manager. The per-type proxies must not try to (re)create,
	 * drop or truncate it.
	 */
	public function create_table() {
	}

	public function update_db() {
	}

	public function drop() {
	}

	public function truncate() {
	}

	/**
	 * Force the object type on insert.
	 *
	 * @param array $data
	 *
	 * @return int
	 */
	public function insert( $data ) {
		$data['object_type'] = $this->object_type;

		return parent::insert( $data );
	}

	/**
	 * Force the object type on batch insert.
	 *
	 * @param array $data
	 *
	 * @return void
	 */
	public function batch_insert( $data ) {
		$data['object_type'] = $this->object_type;

		parent::batch_insert( $data );
	}

	/**
	 * Constrain every query to this type.
	 *
	 * @param array  $query_vars
	 * @param string $ORDER_BY
	 * @param bool   $from_cache
	 *
	 * @return array|object|int
	 */
	public function query( $query_vars = [], $ORDER_BY = '', $from_cache = true ) {
		$query_vars['object_type'] = $this->object_type;

		return parent::query( $query_vars, $ORDER_BY, $from_cache );
	}

	/**
	 * Type aware get_by. Lookups on the primary key are safe because ids are
	 * globally unique, but we still reject a row that belongs to another type.
	 * Non primary lookups are routed through a scoped query.
	 *
	 * @param string $column
	 * @param mixed  $row_id
	 *
	 * @return object|null
	 */
	public function get_by( $column, $row_id ) {

		if ( $column === $this->get_primary_key() ) {
			$row = parent::get_by( $column, $row_id );

			return ( $row && $row->object_type === $this->object_type ) ? $row : null;
		}

		$results = $this->query( [
			$column => $row_id,
			'limit' => 1,
		] );

		return empty( $results ) ? null : array_shift( $results );
	}

	/**
	 * Type aware delete. Deletes by primary key so that the standard meta and
	 * relationship cleanup hooks (which only fire for integer ids) still run,
	 * but first verifies the row actually belongs to this type.
	 *
	 * @param int|array $id
	 *
	 * @return bool
	 */
	public function delete( $id = null ) {

		if ( is_numeric( $id ) ) {

			$id  = absint( $id );
			$row = parent::get_by( $this->get_primary_key(), $id );

			if ( ! $row || $row->object_type !== $this->object_type ) {
				return false;
			}

			return parent::delete( $id );
		}

		if ( is_array( $id ) ) {
			$id['object_type'] = $this->object_type;

			return parent::delete( $id );
		}

		return false;
	}

	/**
	 * Bulk delete, scoped to this type.
	 *
	 * @param array $where
	 *
	 * @return false|int
	 */
	public function bulk_delete( $where = [] ) {

		if ( empty( $where ) ) {
			return false;
		}

		$where['object_type'] = $this->object_type;

		return parent::bulk_delete( $where );
	}

	/**
	 * Map a raw row (or an id) to the configured object class for this type.
	 *
	 * @param object|int $object
	 *
	 * @return Custom_Object
	 */
	public function create_object( $object ) {

		$type = Custom_Object_Type::get( $this->object_type );

		$class = $type ? $type->get_object_class() : Custom_Object::class;

		return new $class( $object, null, $this->object_type );
	}
}
