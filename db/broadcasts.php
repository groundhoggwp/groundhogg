<?php

namespace Groundhogg\DB;

// Exit if accessed directly
use function Groundhogg\isset_not_empty;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Broadcasts DB
 *
 * Stores information about broadcasts
 *
 * @since       File available since Release 0.1
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
 */
class Broadcasts extends DB {

	/**
	 * Get the DB suffix
	 *
	 * @return string
	 */
	public function get_db_suffix() {
		return 'gh_broadcasts';
	}

	/**
	 * Get the DB primary key
	 *
	 * @return string
	 */
	public function get_primary_key() {
		return 'ID';
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
		return 'broadcast';
	}

	public function get_date_key() {
		return 'send_time';
	}

	/**
	 * Get columns and formats
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function get_columns() {
		return [
			'ID'             => '%d',
			'object_id'      => '%d',
			'object_type'    => '%s',
			'scheduled_by'   => '%d',
			'send_time'      => '%d',
			'query'          => '%s',
			'status'         => '%s',
			'date_scheduled' => '%s',
		];
	}

	/**
	 * Get default column values
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function get_column_defaults() {
		return [
			'ID'             => 0,
			'object_id'      => 0,
			'object_type'    => 'email',
			'scheduled_by'   => get_current_user_id(),
			'send_time'      => 0,
			'query'          => [],
			'status'         => 'pending',
			'date_scheduled' => current_time( 'mysql' ),
		];
	}

	/**
	 * @param array $data
	 *
	 * @return int
	 */
	public function add( $data = array() ) {
		$data['query'] = maybe_serialize( $data['query'] );

		return parent::add( $data );
	}

	/**
	 * @param $row_id
	 *
	 * @return object
	 */
	public function get( $row_id ) {
		$data        = parent::get( $row_id );
		$data->query = maybe_unserialize( $data->query );

		return $data;
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
		ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        object_id bigint(20) unsigned NOT NULL,
        object_type VARCHAR(20) NOT NULL,
        scheduled_by bigint(20) unsigned NOT NULL,
        send_time bigint(20) unsigned NOT NULL,
        query longtext NOT NULL,
        status VARCHAR(20) NOT NULL,
        date_scheduled datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY (ID),
        KEY send_time (send_time)
		) {$this->get_charset_collate()};";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}
}
