<?php

namespace Groundhogg\DB;

use Groundhogg\Base_Object;
use Groundhogg\Contact;
use function Groundhogg\get_db;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * tag relationships DB
 *
 * Store the relationships between tags and contacts
 *
 * @package     Includes
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */
class Tag_Relationships extends DB {

	/**
	 * Get the DB suffix
	 *
	 * @return string
	 */
	public function get_db_suffix() {
		return 'gh_tag_relationships';
	}

	/**
	 * Get the DB primary key
	 *
	 * @return string
	 */
	public function get_primary_key() {
		return 'tag_id';
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
		return 'tag_relationship';
	}

	/**
	 * Clean up after tag/contact is deleted.
	 */
	protected function add_additional_actions() {
		add_action( 'groundhogg/db/post_delete/contact', [ $this, 'contact_deleted' ], 10, 3 );
		add_action( 'groundhogg/db/post_delete/tag', [ $this, 'tag_deleted' ], 10, 3 );
		parent::add_additional_actions();
	}

	/**
	 * Get columns and formats
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function get_columns() {
		return [
			'tag_id'     => '%d',
			'contact_id' => '%d',
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
			'tag_id'     => 0,
			'contact_id' => 0,
		);
	}

	/**
	 * Add a tag relationship
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function add( $tag_id = 0, $contact_id = 0 ) {

		if ( ! $tag_id || ! $contact_id ) {
			return false;
		}

		$data = array(
			'tag_id'     => absint( $tag_id ),
			'contact_id' => absint( $contact_id )
		);

		return $this->insert( $data );
	}

	/**
	 * Wrapper for insert to use INSERT IGNORE
	 *
	 * @param $data
	 *
	 * @return int
	 */
	public function insert( $data ) {

		add_filter( 'query', [ $this, '_insert_ignore' ] );
		$result = parent::insert( $data );
		remove_filter( 'query', [ $this, '_insert_ignore' ] );

		return $result;
	}

	public function commit_batch_insert() {
		add_filter( 'query', [ $this, '_insert_ignore' ] );
		$result = parent::commit_batch_insert();
		remove_filter( 'query', [ $this, '_insert_ignore' ] );
		return $result;
	}

	/**
	 * Replace the INSERT statement with an INSERT IGNORE
	 *
	 * @param $query
	 *
	 * @return array|string|string[]
	 */
	public function _insert_ignore( $query ) {
		return str_replace( 'INSERT', 'INSERT IGNORE', $query );
	}

	/**
	 * A tag was delete, delete all tag relationships
	 *
	 * @param $where
	 * @param $formats
	 * @param $table
	 *
	 * @return bool
	 */
	public function tag_deleted( $where, $formats, $table ) {

		if ( is_numeric( $where ) ) {
			return $this->delete( [ 'tag_id' => $where ] );
		}

		return false;
	}

	/**
	 * A contact was merged
	 *
	 * Update the relationship to the new contact only where the contact does
	 * not already have that relationship to avoid collisions
	 *
	 * @param $contact Contact
	 * @param $other Contact
	 */
	public function contact_merged( $contact, $other ) {

		global $wpdb;

		$tag_ids = implode( ',', $contact->get_tag_ids() );

		$wpdb->query( "UPDATE $this->table_name SET contact_id = $contact->ID WHERE contact_id = $other->ID AND tag_id NOT IN ($tag_ids)" );

	}

	/**
	 * A contact was deleted, delete all tag relationships
	 *
	 * @param $where
	 * @param $formats
	 * @param $table
	 *
	 * @return bool
	 */
	public function contact_deleted( $where, $formats, $table ) {
		if ( is_numeric( $where ) ) {

			$tags = $this->get_tags_by_contact( $where );

			if ( empty( $tags ) ) {
				return false;
			}

			do_action( 'groundhogg/db/pre_bulk_delete/tag_relationships', wp_parse_id_list( $tags ) );

			return $this->delete( [ 'contact_id' => $where ] );
		}

		return false;
	}

	/**
	 * Retrieve tags from the database
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function get_relationships( $value, $column = 'contact_id', $return = "tag_id" ) {

		if ( empty( $value ) || ! is_numeric( $value ) ) {
			return false;
		}

		$results = $this->query( [
			'select'  => $return,
			$column   => $value,
			'orderby' => $this->primary_key,
			'order'   => 'DESC'
		] );

		if ( empty( $results ) ) {
			return false;
		}

		return wp_list_pluck( $results, $return );
	}

	/**
	 * Get a list of tags associated with a particular contact
	 *
	 * @param int $contact_id
	 *
	 * @return array|bool|null|object
	 */
	public function get_tags_by_contact( $contact_id = 0 ) {
		return $this->get_relationships( $contact_id, 'contact_id', 'tag_id' );
	}


	/**
	 * Get a list of contacts associated with a particular tag
	 *
	 * @param int $tag_id
	 *
	 * @return array|bool|null|object
	 */
	public function get_contacts_by_tag( $tag_id = 0 ) {
		return $this->get_relationships( $tag_id, 'tag_id', 'contact_id' );
	}

	/**
	 * Deletes any relationships that ar no longer available
	 */
	public function delete_orphaned_relationships() {

		$tags_table     = get_db( 'tags' );
		$contacts_table = get_db( 'contacts' );

		global $wpdb;

		$tag_query      = "SELECT tags.tag_id FROM {$tags_table->table_name} AS tags";
		$contacts_query = "SELECT contacts.ID FROM {$contacts_table->table_name} as contacts";

		$wpdb->query( "DELETE FROM {$this->table_name} WHERE tag_id NOT IN ( $tag_query ) OR contact_id NOT IN ( $contacts_query )" );

		$this->cache_set_last_changed();
	}

	/**
	 * Create table command
	 *
	 * @return string
	 */
	public function create_table_sql_command() {
		return "CREATE TABLE " . $this->table_name . " (
		tag_id bigint(20) unsigned NOT NULL,
		contact_id bigint(20) unsigned NOT NULL,
		PRIMARY KEY (tag_id,contact_id),
		KEY tag_id (tag_id),
		KEY contact_id (contact_id)
		) {$this->get_charset_collate()};";
	}
}
