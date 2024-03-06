<?php

namespace Groundhogg\DB;

use Groundhogg\Contact;
use function Groundhogg\gh_cron_installed;

class Permissions_Keys extends DB {

	/**
	 * Get the DB suffix
	 *
	 * @return string
	 */
	public function get_db_suffix() {
		return 'gh_permissions_keys';
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
		return '1.0';
	}

	/**
	 * Get the object type we're inserting/updating/deleting.
	 *
	 * @return string
	 */
	public function get_object_type() {
		return 'permissions_key';
	}

	/**
	 * Clean up DB events when this happens.
	 */
	protected function add_additional_actions() {
		add_action( 'groundhogg/db/post_delete/contact', [ $this, 'contact_deleted' ] );
		parent::add_additional_actions();
	}


	/**
	 * Delete events for a contact that was just deleted...
	 *
	 * @param $id
	 *
	 * @return false|int
	 */
	public function contact_deleted( $id ) {
		return $this->bulk_delete( [ 'contact_id' => $id ] );
	}

	/**
	 * Get columns and formats
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function get_columns() {
		return array(
			'ID'               => '%d',
			'contact_id'       => '%d',
			'permissions_key'  => '%s',
			'usage_type'       => '%s',
			'date_created'     => '%s',
			'delete_after_use' => '%d',
			'expiration_date'  => '%s',
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
			'ID'               => 0,
			'contact_id'       => 0,
			'permissions_key'  => '',
			'usage_type'       => 'preferences',
			'delete_after_use' => 0,
			'date_created'     => date( 'Y-m-d H:i:s' ),
			'expiration_date'  => date( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS ),
		);
	}

	/**
	 * Create the table
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function create_table() {

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE " . $this->table_name . " (
		ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		contact_id bigint(20) unsigned NOT NULL,
		delete_after_use tinyint unsigned NOT NULL,
		permissions_key varchar({$this->get_max_index_length()}) NOT NULL,
		usage_type varchar({$this->get_max_index_length()}) NOT NULL,
		date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		expiration_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		PRIMARY KEY  (ID),
		KEY contact_id (contact_id)
		) {$this->get_charset_collate()};";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}
}
