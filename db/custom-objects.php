<?php

namespace Groundhogg\DB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom Objects DB
 *
 * A single shared table (gh_objects) that backs any number of registered custom
 * object types. Individual types are accessed through a per-type, type-scoped
 * proxy (@see Custom_Object_Table) so that every read/write is constrained to
 * the correct object_type. This class handles the physical table: install,
 * columns and the shared behaviour inherited by the per-type proxies.
 *
 * @since       File available since Release 4.8
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
 */
class Custom_Objects extends DB {

	public function get_db_suffix() {
		return 'gh_objects';
	}

	public function get_primary_key() {
		return 'ID';
	}

	public function get_db_version() {
		return '1.0';
	}

	public function get_object_type() {
		return 'custom_object';
	}

	/**
	 * The generic custom object table isn't tied to a single type, so there's
	 * nothing type specific to search. The per-type proxy narrows this down.
	 *
	 * @return string[]
	 */
	public function get_searchable_columns() {
		return [ 'title' ];
	}

	/**
	 * Get columns and formats
	 *
	 * @return array
	 */
	public function get_columns() {
		return [
			'ID'           => '%d',
			'object_type'  => '%s',
			'status'       => '%s',
			'title'        => '%s',
			'author_id'    => '%d',
			'date_created' => '%s',
			'date_updated' => '%s',
		];
	}

	/**
	 * Get default column values
	 *
	 * @return array
	 */
	public function get_column_defaults() {
		return [
			'ID'           => 0,
			'object_type'  => '',
			'status'       => 'active',
			'title'        => '',
			'author_id'    => get_current_user_id(),
			'date_created' => current_time( 'mysql', true ),
			'date_updated' => current_time( 'mysql', true ),
		];
	}

	/**
	 * Touch date_updated on every update unless it was explicitly provided.
	 *
	 * @param int|array $row_id_or_where
	 * @param array     $data
	 * @param array     $where
	 *
	 * @return bool
	 */
	public function update( $row_id_or_where = 0, $data = [], $where = [] ) {

		if ( ! empty( $data ) && ! array_key_exists( 'date_updated', $data ) ) {
			$data['date_updated'] = current_time( 'mysql', true );
		}

		return parent::update( $row_id_or_where, $data, $where );
	}

	/**
	 * The command to create the table
	 *
	 * @return string
	 */
	public function create_table_sql_command() {
		return "CREATE TABLE " . $this->table_name . " (
        ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        object_type varchar({$this->get_max_index_length()}) NOT NULL,
        status varchar(20) NOT NULL,
        title text NOT NULL,
        author_id bigint(20) unsigned NOT NULL,
        date_created datetime NOT NULL,
        date_updated datetime NOT NULL,
        PRIMARY KEY (ID),
        KEY object_type (object_type),
        KEY object_type_status (object_type,status),
        KEY author_id (author_id),
        KEY date_created (date_created)
		) {$this->get_charset_collate()} ENGINE=InnoDB;";
	}

	/**
	 * Create the table
	 */
	public function create_table() {

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		dbDelta( $this->create_table_sql_command() );

		update_option( $this->table_name . '_db_version', $this->version );
	}
}
