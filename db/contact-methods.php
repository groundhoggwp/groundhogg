<?php


namespace Groundhogg\DB;

use Groundhogg\Contact;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Contact_Methods extends DB {
	public function get_db_suffix() {
		return 'gh_contact_methods';
	}

	public function get_primary_key() {
		return 'ID';
	}

	public function get_db_version() {
		return '2.0';
	}

	public function get_object_type() {
		return 'contact_method';
	}

	/**
	 * Get columns and formats
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function get_columns() {
		return array(
			'ID'         => '%d',
			'contact_id' => '%d',
			'type'       => '%s',
			'status'     => '%s',
			'method'     => '%s',
			'extra'      => '%s'
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
			'ID'         => 0,
			'contact_id' => 0,
			'type'       => '',
			'status'     => '',
			'method'     => '',
			'extra'      => ''
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
		ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        contact_id bigint(20) unsigned NOT NULL,
        type VARCHAR(20) NOT NULL,    
        status VARCHAR(20) NOT NULL,    
        method VARCHAR(50) NOT NULL,    
        extra mediumtext NOT NULL,    
        PRIMARY KEY (ID)
		) {$this->get_charset_collate()};";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}
}