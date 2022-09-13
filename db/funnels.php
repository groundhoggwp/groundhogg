<?php

namespace Groundhogg\DB;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Funnels DB
 *
 * Store information about funnels
 *
 * @package     Includes
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */
class Funnels extends DB {

	/**
	 * Get the DB suffix
	 *
	 * @return string
	 */
	public function get_db_suffix() {
		return 'gh_funnels';
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
	 * Get the object type we're inserting/updating/deleting.
	 *
	 * @return string
	 */
	public function get_object_type() {
		return 'funnel';
	}

	protected function add_additional_actions() {
		parent::add_additional_actions();
		add_action( 'groundhogg/owner_deleted', [ $this, 'owner_deleted' ], 10, 2 );
	}

	public function owner_deleted( $prev, $new ){
		$this->update([
			'author' => $prev,
		], [
			'author' => $new,
		]);
	}

	/**
	 * Get columns and formats
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function get_columns() {
		return array(
			'ID'              => '%d',
			'author'          => '%d',
			'title'           => '%s',
			'status'          => '%s',
			'date_created'    => '%s',
			'last_updated'    => '%s',
			'conversion_step' => '%d'
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
			'ID'              => 0,
			'author'          => get_current_user_id(),
			'title'           => '',
			'status'          => 'inactive',
			'date_created'    => current_time( 'mysql' ),
			'last_updated'    => current_time( 'mysql' ),
			'conversion_step' => 0
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
        title text NOT NULL,
        status varchar(20) NOT NULL,
        author bigint(20) unsigned NOT NULL,
        active_contacts bigint(20) unsigned NOT NULL,
        last_updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        conversion_step bigint(20) unsigned NOT NULL,
        PRIMARY KEY (ID)
		) {$this->get_charset_collate()};";

		dbDelta( $sql );

		$wpdb->query( "ALTER TABLE $this->table_name AUTO_INCREMENT = 2" );

		update_option( $this->table_name . '_db_version', $this->version );
	}
}
