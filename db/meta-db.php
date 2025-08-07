<?php

namespace Groundhogg\DB;

// Exit if accessed directly
use Groundhogg\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Meta DB
 *
 * Allows for the use of metadata api usage
 *
 * @since       File available since Release 0.1
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
 */
abstract class Meta_DB extends DB {


	public function render_table_name() {
		parent::render_table_name();
		$this->alias = $this->get_object_type() . 'Meta';
	}

	/**
	 * Always return Meta ID
	 *
	 * @return string
	 */
	public function get_primary_key() {
		return 'meta_id';
	}

	/**
	 * Add meta to the cache group to avoid conflicts
	 *
	 * @return string
	 */
	public function get_cache_group() {
		return parent::get_cache_group() . '_meta';
	}

	protected function add_additional_actions() {

		add_action( 'groundhogg/db/pre_delete/' . $this->get_object_type(), [
			$this,
			'delete_associated_meta'
		], 10, 3 );

		add_action( 'groundhogg/db/delete_orphaned_meta/' . $this->get_object_type(), [
			$this,
			'_delete_orphaned_meta'
		], 10, 1 );

		parent::add_additional_actions();
	}

	/**
	 * Get the meta associative ID
	 *
	 * @return string
	 */
	public function get_object_id_col() {
		return $this->get_object_type() . '_id';
	}

	/**
	 * Get table columns and data types
	 *
	 * @access  public
	 * @since   1.7.18
	 */
	public function get_columns() {

		$object_id = $this->get_object_id_col();

		return [
			'meta_id'    => '%d',
			$object_id   => '%d',
			'meta_key'   => '%s',
			'meta_value' => '%s',
		];
	}

	/**
	 * Register the table with $wpdb so the metadata api can find it
	 *
	 * @access  public
	 * @since   2.6
	 */
	public function register_table() {

		global $wpdb;

		if ( $wpdb ) {
			$wpdb->__set( $this->get_object_type() . 'meta', $this->get_table_name() );

			if ( ! in_array( $this->get_db_suffix(), $wpdb->tables ) ) {
				$wpdb->tables[] = $this->get_db_suffix();
			}
		}
	}

	/**
	 * Temp storage for conflicting table names
	 *
	 * @var string
	 */
	protected $conflicting_table_name = '';

	/**
	 * Resolve meta table conflicts just in time by caching the conflicting table and replacing it temporarily with our own
	 * Does nothing if there is no table conflict
	 *
	 * Made for BuddyPress compatibility
	 *
	 * @return void
	 */
	public function maybe_resolve_table_conflict() {
		global $wpdb;
		$registered_table = _get_meta_table( $this->get_object_type() );

		// If the current table meta table is not equal to ours, set it to ours
		if ( $registered_table !== $this->get_table_name() ) {
			$wpdb->__set( $this->get_object_type() . 'meta', $this->get_table_name() );
			$this->conflicting_table_name = $registered_table;
		} // If a conflict table was removed, restore it
		else if ( $this->conflicting_table_name ) {
			$wpdb->__set( $this->get_object_type() . 'meta', $this->conflicting_table_name );
			// Reset the conflicting table flag
			$this->conflicting_table_name = '';
		}
	}

	/**
	 * Clean up associated Meta if object gets delete
	 *
	 * @param $where          array
	 * @param $formats        array
	 * @param $object_table   DB
	 *
	 * @return false|int
	 */
	public function delete_associated_meta( $where, $formats, $object_table ) {

		// Same table problem for meta tables with same object type
		if ( $object_table->table_name === $this->table_name ) {
			return false;
		}

		global $wpdb;

		if ( is_numeric( $where ) ) {

			$result = $wpdb->delete( $this->table_name, [ $this->get_object_id_col() => $where ], [ '%d' ] );

		} else {

			$w = [];

			foreach ( $where as $col => $value ) {
				if ( in_array( $col, $object_table->get_allowed_columns() ) ) {
					$w[] = [ 'col' => $col, 'val' => $value, 'compare' => is_array( $value ) ? 'IN' : '=' ];
				}
			}

			$object_query = $object_table->get_sql( [
				'where'   => $w,
				'select'  => $object_table->get_primary_key(),
				'order'   => false, // don't need
				'orderby' => false  // don't need
			] );

			$result = $wpdb->query( "DELETE FROM {$this->table_name} WHERE `{$this->get_object_id_col()}` IN ( $object_query);" );

		}

		$this->cache_set_last_changed();

		return $result;
	}

	/**
	 * Retrieve object meta field for a object.
	 *
	 * For internal use only. Use EDD_Contact->get_meta() for public usage.
	 *
	 * @since   2.6
	 *
	 * @param string $meta_key  The meta key to retrieve.
	 * @param bool   $single    Whether to return a single value.
	 *
	 * @param int    $object_id Object ID.
	 *
	 * @return  mixed                 Will be an array if $single is false. Will be value of meta data field if $single is true.
	 *
	 * @access  private
	 */
	public function get_meta( $object_id = 0, $meta_key = '', $single = false ) {

		$object_id = $this->sanitize_id( $object_id );

		if ( false === $object_id ) {
			return false;
		}

		$this->maybe_resolve_table_conflict();

		$getted = get_metadata( $this->get_object_type(), $object_id, $meta_key, $single );

		$this->maybe_resolve_table_conflict();

		if ( $getted ) {
			do_action( "groundhogg/meta/{$this->get_object_type()}/get", $object_id, $meta_key, $single, $getted );
		}

		return $getted;
	}

	/**
	 * Add meta data field to a object.
	 *
	 * For internal use only. Use EDD_Contact->add_meta() for public usage.
	 *
	 * @since   2.6
	 *
	 * @param string $meta_key   Metadata name.
	 * @param mixed  $meta_value Metadata value.
	 * @param bool   $unique     Optional, default is false. Whether the same key should not be added.
	 *
	 * @param int    $object_id  Contact ID.
	 *
	 * @return  bool                  False for failure. True for success.
	 *
	 * @access  private
	 */
	public function add_meta( $object_id = 0, $meta_key = '', $meta_value = '', $unique = false ) {
		$object_id = $this->sanitize_id( $object_id );

		if ( false === $object_id ) {
			return false;
		}

		$this->maybe_resolve_table_conflict();

		/**
		 * Filter the meta value
		 *
		 * @param $meta_value mixed
		 * @param $meta_key   string
		 * @param $object_id  int
		 * @param $prev_value mixed
		 */
		$meta_value = apply_filters( "groundhogg/meta/{$this->get_object_type()}/add/filter_value", $meta_value, $meta_key, $object_id );

		$added = add_metadata( $this->get_object_type(), $object_id, $meta_key, $meta_value, $unique );

		$this->maybe_resolve_table_conflict();

		if ( $added ) {
			do_action( "groundhogg/meta/{$this->get_object_type()}/add", $object_id, $meta_key, $meta_value, $unique );
		}

		return $added;
	}

	/**
	 * Update object meta field based on Contact ID.
	 *
	 * For internal use only. Use EDD_Contact->update_meta() for public usage.
	 *
	 * Use the $prev_value parameter to differentiate between meta fields with the
	 * same key and Contact ID.
	 *
	 * If the meta field for the object does not exist, it will be added.
	 *
	 * @since   2.6
	 *
	 * @param string $meta_key   Metadata key.
	 * @param mixed  $meta_value Metadata value.
	 * @param mixed  $prev_value Optional. Previous value to check before removing.
	 *
	 * @param int    $object_id  Contact ID.
	 *
	 * @return  bool                  False on failure, true if success.
	 *
	 * @access  private
	 */
	public function update_meta( $object_id = 0, $meta_key = '', $meta_value = '', $prev_value = '' ) {

		$object_id = $this->sanitize_id( $object_id );

		if ( false === $object_id ) {
			return false;
		}

		$this->maybe_resolve_table_conflict();

		/**
		 * Filter the meta value
		 *
		 * @param $meta_value mixed
		 * @param $meta_key   string
		 * @param $object_id  int
		 * @param $prev_value mixed
		 */
		$meta_value = apply_filters( "groundhogg/meta/{$this->get_object_type()}/update/filter_value", $meta_value, $meta_key, $object_id, $prev_value );

		$updated = update_metadata( $this->get_object_type(), $object_id, $meta_key, $meta_value, $prev_value );

		$this->maybe_resolve_table_conflict();

		if ( $updated ) {
			do_action( "groundhogg/meta/{$this->get_object_type()}/update", $object_id, $meta_key, $meta_value, $prev_value );
		}

		return $updated;
	}

	/**
	 * Remove metadata matching criteria from a object.
	 *
	 * For internal use only. Use EDD_Contact->delete_meta() for public usage.
	 *
	 * You can match based on the key, or key and value. Removing based on key and
	 * value, will keep from removing duplicate metadata with the same key. It also
	 * allows removing all metadata matching key, if needed.
	 *
	 * @since   2.6
	 *
	 * @param string $meta_key   Metadata name.
	 * @param mixed  $meta_value Optional. Metadata value.
	 *
	 * @param int    $object_id  Contact ID.
	 *
	 * @return  bool                  False for failure. True for success.
	 *
	 * @access  private
	 */
	public function delete_meta( $object_id = 0, $meta_key = '', $meta_value = '' ) {

		$object_id = $this->sanitize_id( $object_id );

		if ( false === $object_id ) {
			return false;
		}

		$this->maybe_resolve_table_conflict();

		$deleted = delete_metadata( $this->get_object_type(), $object_id, $meta_key, $meta_value );

		$this->maybe_resolve_table_conflict();

		if ( $deleted ) {
			do_action( "groundhogg/meta/{$this->get_object_type()}/delete", $object_id, $meta_key, $meta_value );
		}

		return $deleted;
	}

	/**
	 * Returns an array of all the meta keys in a table.
	 *
	 * @return array
	 */
	public function get_keys() {
		global $wpdb;

		$keys = $wpdb->get_col(
			"SELECT DISTINCT meta_key FROM $this->table_name ORDER BY meta_key ASC"
		);

		$key_array = array_combine( $keys, $keys );

		return $key_array;
	}

	/**
	 * Create the table
	 *
	 * @access  public
	 * @since   2.6
	 */
	public function create_table() {

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE {$this->table_name} (
		meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		{$this->get_object_id_col()} bigint(20) unsigned NOT NULL,
		meta_key varchar({$this->get_max_index_length()}) DEFAULT NULL,
		meta_value longtext,
		PRIMARY KEY  (meta_id),
		KEY {$this->get_object_id_col()} ({$this->get_object_id_col()}),
		KEY meta_key (meta_key)
		) {$this->get_charset_collate()};";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}

	/**
	 * Given a object ID, make sure it's a positive number, greater than zero before inserting or adding.
	 *
	 * @since  2.6
	 *
	 * @param int|string $object_id A passed object ID.
	 *
	 * @return int|bool                The normalized object ID or false if it's found to not be valid.
	 */
	private function sanitize_id( $object_id ) {
		if ( ! is_numeric( $object_id ) ) {
			return false;
		}

		$object_id = (int) $object_id;

		// We were given a non positive number
		if ( absint( $object_id ) !== $object_id ) {
			return false;
		}

		if ( empty( $object_id ) ) {
			return false;
		}

		return absint( $object_id );

	}

	/**
	 * Deletes orphaned meta where an object ID does not exist
	 *
	 * @param $object_table DB;
	 */
	public function _delete_orphaned_meta( $object_table ) {

		global $wpdb;

		$object_query = "SELECT {$object_table->primary_key} FROM {$object_table->table_name}";

		$wpdb->query( "DELETE FROM {$this->table_name} WHERE {$this->get_object_id_col()} NOT IN ( $object_query )" );

		$this->cache_set_last_changed();
	}

	public function delete_orphaned_meta() {
		$db = Plugin::instance()->dbs->get_object_db_by_object_type( $this->get_object_type() );
		$this->_delete_orphaned_meta( $db );
	}
}
