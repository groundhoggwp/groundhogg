<?php

namespace Groundhogg\DB;

use Groundhogg\Base_Object;
use Groundhogg\DB\Traits\Insert_Ignore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Object relationships DB
 *
 * Store the relationships between arbitrary objects in Groundhogg
 *
 * @since       File available since Release 0.1
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
 */
class Object_Relationships extends DB {

	use Insert_Ignore;

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
		add_action( 'groundhogg/object_merged', [ $this, 'object_merged' ], 10, 3 );
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

	public function swap_relationships( string $which, string $type, int $old, int $new ) {

		$object_id   = $which . '_object_id';
		$object_type = $which . '_object_type';

		$relationships = $this->query( [
			$object_id   => $old,
			$object_type => $type
		] );

		foreach ( $relationships as $relationship ) {
			$relationship->$object_id = $new;
			$this->insert( (array) $relationship );
		}
	}

	/**
	 * $this->delete( [
	 * $object_id   => $old,
	 * $object_type => 'contact'
	 * ] );
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
		$this->swap_relationships( 'primary', $type, $from->get_id(), $to->get_id() );
		$this->swap_relationships( 'secondary', $type, $from->get_id(), $to->get_id() );
	}

	/**
	 * update the secondary and primary based on non-existing relationships
	 *
	 * @param \Groundhogg\Contact $contact
	 * @param \Groundhogg\Contact $other
	 */
	public function contact_merged( $contact, $other ) {
		$this->swap_relationships( 'primary', 'contact', $other->ID, $contact->ID );
		$this->swap_relationships( 'secondary', 'contact', $other->ID, $contact->ID );
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
	 * Create table command
	 *
	 * @return string
	 */
	public function create_table_sql_command() {
		return "CREATE TABLE " . $this->table_name . " (
		primary_object_id bigint(20) unsigned NOT NULL,
		primary_object_type varchar({$this->get_max_index_length()}) NOT NULL,
		secondary_object_id bigint(20) unsigned NOT NULL,
		secondary_object_type varchar({$this->get_max_index_length()}) NOT NULL,
		PRIMARY KEY (primary_object_id,primary_object_type,secondary_object_id,secondary_object_type),
		KEY primary_object (primary_object_id,primary_object_type),
		KEY secondary_object (secondary_object_id,secondary_object_type)
		) {$this->get_charset_collate()} ENGINE=InnoDB;";
	}
}
