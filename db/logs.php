<?php

namespace Groundhogg\DB;

use function Groundhogg\Ymd_His;

class Logs extends DB {


	public function get_db_suffix() {
		return 'gh_logs';
	}

	public function get_primary_key() {
		return 'ID';
	}

	public function get_db_version() {
		return '1.0';
	}

	public function get_object_type() {
		return 'log';
	}

	public function get_date_key() {
		return 'date_created';
	}

	/**
	 * Get columns and formats
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function get_columns() {
		return array(
			'ID'           => '%d',
			'event'        => '%s',
			'name'         => '%s',
			'value'        => '%s',
			'date_created' => '%s',
		);
	}

	/**
	 * Get columns and formats
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function get_column_defaults() {
		return array(
			'ID'           => 0,
			'event'        => '',
			'name'         => '',
			'value'        => '',
			'date_created' => Ymd_His(),
		);
	}

	public function create_table() {

		global $wpdb;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE " . $this->table_name . " (
		ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		event varchar({$this->get_max_index_length()}) NOT NULL,
		name text NOT NULL,
		value text NOT NULL,
		date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		PRIMARY KEY (ID),
		KEY date_created (date_created)
		) {$this->get_charset_collate()};";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}
}
