<?php

namespace Groundhogg\DB;

use function Groundhogg\get_db;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Object relationships DB
 *
 * Store the relationships between arbitrary objects in Groundhogg
 *
 * @package     Includes
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */
class Object_Relationships extends DB {

	/**
	 * Get the DB suffix
	 *
	 * @return string
	 */
	public function get_db_suffix() {
		return 'gh_object_relationships';
	}

	/**
	 * Get the DB primary key
	 *
	 * @return string
	 */
	public function get_primary_key() {
		return '';
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
		return 'object_relationship';
	}

	/**
	 * Clean up after tag/contact is deleted.
	 */
	protected function add_additional_actions() {
		add_action( 'groundhogg/db/post_delete/contact', [ $this, 'contact_deleted' ], 10, 3 );
		add_action( 'groundhogg/db/post_delete/tag', [ $this, 'tag_deleted' ], 10, 3 );
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
			'primary_object_id'     => '%d',
			'primary_object_type'   => '%s',
			'secondary_object_id'   => '%d',
			'secondary_object_type' => '%s',
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
			'primary_object_id'     => 0,
			'primary_object_type'   => '',
			'secondary_object_id'   => 0,
			'secondary_object_type' => '',
		);
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
		primary_object_id bigint(20) unsigned NOT NULL,
		primary_object_type varchar({$this->get_max_index_length()}) NOT NULL,
		secondary_object_id bigint(20) unsigned NOT NULL,
		secondary_object_type varchar({$this->get_max_index_length()}) NOT NULL,
		PRIMARY KEY (primary_object_id,primary_object_type,secondary_object_id,secondary_object_type),
		KEY primary_object (primary_object_id,primary_object_type),
		KEY secondary_object (secondary_object_id,secondary_object_type)
		) {$this->get_charset_collate()};";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}
}