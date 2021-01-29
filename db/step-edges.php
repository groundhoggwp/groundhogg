<?php

namespace Groundhogg\DB;

use Groundhogg\Contact;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * tag relationships DB
 *
 * Store the relationships between tags and contacts
 *
 * @since       File available since Release 0.1
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
 */
class Step_Edges extends DB {
	/**
	 * The name of the cache group.
	 *
	 * @access public
	 * @since  2.8
	 * @var string
	 */
	public $cache_group = 'step_edges';

	/**
	 * Get the DB suffix
	 *
	 * @return string
	 */
	public function get_db_suffix() {
		return 'gh_step_edges';
	}

	/**
	 * Get the DB primary key
	 *
	 * @return string
	 */
	public function get_primary_key() {
		return 'from_id';
	}

	/**
	 * Get the DB version
	 *
	 * @return mixed
	 */
	public function get_db_version() {
		return '2.0';
	}

	/**
	 * Get the object type we're inserting/updateing/deleting.
	 *
	 * @return string
	 */
	public function get_object_type() {
		return 'step_edge';
	}

	/**
	 * Clean up after tag/contact is deleted.
	 */
	protected function add_additional_actions() {
		add_action( 'groundhogg/db/post_delete/step', [ $this, 'step_deleted' ] );
		add_action( 'groundhogg/db/post_delete/funnel', [ $this, 'funnel_deleted' ] );
		parent::add_additional_actions();
	}

	/**
	 * Get columns and formats
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function get_columns() {
		return array(
			'from_id'   => '%d',
			'to_id'     => '%d',
			'funnel_id' => '%d',
		);
	}

	/**
	 * Get default column values
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function get_column_defaults() {
		return array(
			'from_id'   => 0,
			'to_id'     => 0,
			'funnel_id' => 0,
		);
	}

	/**
	 * A step was deleted, delete all edges
	 *
	 * @param $tag_id
	 */
	public function step_deleted( $step_id ) {
		$this->delete( [ 'from_id' => $step_id ] );
		$this->delete( [ 'to_id' => $step_id ] );
	}

	/**
	 * A step was deleted, delete all edges
	 *
	 * @param $tag_id
	 */
	public function funnel_deleted( $step_id ) {
		$this->delete( [ 'funnel_id' => $step_id ] );
	}

	/**
	 * Delete a tag relationship
	 *
	 * @access  public
	 * @since   2.3.1
	 */
	public function delete( $args = array() ) {

		global $wpdb;

		// Initialise column format array
		$column_formats = $this->get_columns();

		// Force fields to lower case
		$data = array_change_key_case( $args );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data
		$data_keys      = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		if ( false === $wpdb->delete( $this->table_name, $data, $column_formats ) ) {
			return false;
		}

		do_action( 'groundhogg/db/post_delete/tag_relationship', $args );

		return true;

	}

	/**
	 * Create the table
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function create_table() {

		global $wpdb;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE " . $this->table_name . " (
		from_id bigint(20) unsigned NOT NULL,
		to_id bigint(20) unsigned NOT NULL,
		funnel_id bigint(20) unsigned NOT NULL,
		PRIMARY KEY (from_id,to_id,path),
		KEY from_id (from_id),
		KEY to_id (to_id),
		KEY funnel_id (funnel_id)
		) {$this->get_charset_collate()};";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}
}