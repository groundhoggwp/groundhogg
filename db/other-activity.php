<?php

namespace Groundhogg\DB;

// Exit if accessed directly
use Groundhogg\Contact;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Activity DB
 *
 * Stores information about a contact's site activity.
 *
 * @package     Includes
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */
class Other_Activity extends DB {

	/**
	 * Get the DB suffix
	 *
	 * @return string
	 */
	public function get_db_suffix() {
		return 'gh_other_activity';
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
		return '2.1';
	}


	/**
	 * Get the object type we're inserting/updateing/deleting.
	 *
	 * @return string
	 */
	public function get_object_type() {
		return 'other_activity';
	}

	/**
	 * Get columns and formats
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function get_columns() {
		return [
			'ID'            => '%d',
			'timestamp'     => '%d',
			'object_id'     => '%d',
			'object_type'   => '%d',
			'activity_type' => '%s',
		];
	}

	/**
	 * Get default column values
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function get_column_defaults() {
		return array(
			'ID'            => 0,
			'timestamp'     => 0,
			'object_id'     => 0,
			'object_type'   => '',
			'activity_type' => '',
		);
	}

	public function get_date_key() {
		return 'timestamp';
	}

	protected function add_additional_actions() {
		add_action( 'groundhogg/contact/merge', [ $this, 'contacts_merged' ], 10, 2 );
	}

	/**
	 * Change other's contact_id to primary's contact_id
	 *
	 * @param $primary Contact
	 * @param $other   Contact
	 */
	public function contact_merged( $primary, $other ) {

		$this->update( [
			'object_id'   => $other->get_id(),
			'object_type' => 'contact'
		], [
			'object_id' => $primary->get_id()
		] );
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

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE " . $this->table_name . " (
        ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        timestamp bigint(20) unsigned NOT NULL,
        object_id bigint(20) unsigned NOT NULL,
        object_type VARCHAR({$this->get_max_index_length()}) NOT NULL,
        activity_type VARCHAR({$this->get_max_index_length()}) NOT NULL,
        PRIMARY KEY (ID),
        KEY timestamp (timestamp),
        KEY object_id_and_type (object_id,object_type)
		) $charset_collate;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}
}
