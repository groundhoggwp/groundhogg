<?php

namespace Groundhogg\DB;

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
		add_action( 'groundhogg/db/post_delete', [ $this, 'object_deleted' ], 10, 4 );
		parent::add_additional_actions();
	}

	public function object_deleted( $object_type, $id_or_where, $formats, $table ) {

		if ( is_int( $id_or_where ) ) {
			$this->delete( [
				'primary_object_type' => $object_type,
				'primary_object_id'   => $id_or_where
			] );

			$this->delete( [
				'secondary_object_type' => $object_type,
				'secondary_object_id'   => $id_or_where
			] );
		}

	}

	/**
	 * update the secondary and primary based on non-existing relationships
	 *
	 * @param \Groundhogg\Contact $contact
	 * @param \Groundhogg\Contact $other
	 */
	public function contact_merged( $contact, $other ) {

		global $wpdb;

		// update primary
		$wpdb->query( "UPDATE $this->table_name SET primary_object_id = $contact->ID WHERE 
primary_object_type = 'contact' AND primary_object_id = $other->ID AND (secondary_object_id,secondary_object_type) NOT IN (
    SELECT secondary_object_id,secondary_object_type FROM $this->table WHERE primary_object_id = $contact->ID AND primary_object_type = 'contact'
) " );

		// Update Secondary
		$wpdb->query( "UPDATE $this->table_name SET secondary_object_id = $contact->ID WHERE 
secondary_object_type = 'contact' AND secondary_object_id = $other->ID AND (primary_object_id,primary_object_type) NOT IN (
    SELECT primary_object_id,primary_object_type FROM $this->table WHERE secondary_object_id = $contact->ID AND secondary_object_type = 'contact'
) " );

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
		) {$this->get_charset_collate()} ENGINE=InnoDB;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}
}