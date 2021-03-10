<?php


namespace Groundhogg\DB;

// Exit if accessed directly
use Groundhogg\Preferences;
use function Groundhogg\isset_not_empty;
use Groundhogg\Contact_Query;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Contact_Notes extends DB {
	public function get_db_suffix() {
		return 'gh_contactnotes';
	}

	public function get_primary_key() {
		return 'ID';
	}

	public function get_db_version() {
		return '2.0';
	}

	public function get_object_type() {
		return 'note';
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
			'contact_id'   => '%d',
			'context'      => '%s',
			'user_id'      => '%d',
			'content'      => '%s',
			'date_created' => '%s',
			'timestamp'    => '%d',
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
			'ID'           => 0,
			'contact_id'   => 0,
			'context'      => '',
			'user_id'      => 0,
			'content'      => '',
			'date_created' => current_time( 'mysql' ),
			'timestamp'    => time(),
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
        user_id bigint(20) unsigned NOT NULL,
        context VARCHAR(50) NOT NULL,    
        content longtext NOT NULL,
        timestamp bigint(12) unsigned NOT NULL,
        date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY (ID)
		) {$this->get_charset_collate()};";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}
}