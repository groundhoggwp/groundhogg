<?php

namespace Groundhogg\DB;

// Exit if accessed directly
use Groundhogg\Base_Object;
use Groundhogg\Contact;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Activity DB
 *
 * Stores information about a contact's site activity.
 *
 * @since       File available since Release 0.1
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
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
	 * Listen for deletions for other objects since we don't want to hold clutter for previous things
	 * to keep the DB small.
	 */
	protected function add_additional_actions() {
		add_action( 'groundhogg/db/post_delete', [ $this, 'object_deleted' ], 10, 4 );
		add_action( 'groundhogg/object_merged', [ $this, 'object_merged' ], 10, 3 );
		parent::add_additional_actions();
	}

	public function object_deleted( $object_type, $id_or_where, $formats, $table ) {
		if ( is_int( $id_or_where ) ) {
			$this->delete( [
				'object_type' => $object_type,
				'object_id'   => $id_or_where
			] );
		}
	}

	/**
	 * When an object is merged, swap the relationships for it
	 *
	 * @param Base_Object $to
	 *
	 * @param Base_Object $from
	 * @param string      $type the object type
	 *
	 * @return void
	 */
	public function object_merged( Base_Object $to, Base_Object $from, $type ) {
		$this->update( [
			'object_type' => $type,
			'object_id'   => $from->ID,
		], [
			'object_type' => $type,
			'object_id'   => $to->ID,
		] );
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
			'object_type'   => '%s',
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
			'timestamp'     => time(),
			'object_id'     => 0,
			'object_type'   => '',
			'activity_type' => '',
		);
	}

	/**
	 * @param Contact $contact
	 * @param Contact $other
	 */
	public function contact_merged( $contact, $other ) {

		$this->update( [
			'object_id'   => $other->get_id(),
			'object_type' => 'contact',
		], [
			'object_id'   => $contact->get_id(),
			'object_type' => 'contact',
		] );

	}

	public function get_date_key() {
		return 'timestamp';
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
