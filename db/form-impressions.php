<?php

namespace Groundhogg\DB;

// Exit if accessed directly
use Groundhogg\Plugin;

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
class Form_Impressions extends DB {

	/**
	 * Get the DB suffix
	 *
	 * @return string
	 */
	public function get_db_suffix() {
		return 'gh_form_impressions';
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
	 * Get the object type we're inserting/updateing/deleting.
	 *
	 * @return string
	 */
	public function get_object_type() {
		return 'impression';
	}

	/**
	 * Get columns and formats
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function get_columns() {
		return [
			'ID'         => '%d',
			'timestamp'  => '%d',
			'form_id'    => '%d',
			'ip_address' => '%s',
			'views'      => '%d'
		];
	}

	/**
	 * Get default column values
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function get_column_defaults() {
		return [
			'ID'         => 0,
			'timestamp'  => time(),
			'form_id'    => 0,
			'ip_address' => '',
			'views'      => 1
		];
	}

	/**
	 * Add a activity
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function add( $data = array() ) {

		$args = wp_parse_args(
			$data,
			$this->get_column_defaults()
		);

		if ( $records = $this->query( [ 'ip_address' => $args['ip_address'], 'form_id' => $args['form_id'] ] ) ) {

			$record = array_shift( $records );
			$id     = absint( $record->ID );

			if ( $this->update( $id, [ 'views' => absint( $record->views ) + absint( $args['views'] ) ] ) ) {
				return $id;
			}

		}

		return $this->insert( $args );
	}

	public function get_date_key() {
		return 'timestamp';
	}

	/**
	 * Set the referer hash as aan easier method to search thru the activity
	 */
	public function update_2_2(){
		global $wpdb;
		$result = $wpdb->query( "UPDATE `{$this->get_table_name()}` SET `views` = `count` WHERE `count` > 0 ;" );
		$result = $wpdb->query( "ALTER TABLE `{$this->get_table_name()}` DROP `count`;" );
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
        form_id bigint(20) unsigned NOT NULL,
        ip_address varchar(15) NOT NULL,
        views bigint(20) unsigned NOT NULL,
        PRIMARY KEY (ID),
        KEY timestamp (timestamp),
        KEY ip_address (ip_address),
        KEY form_id (form_id),
        KEY views (views)
		) $charset_collate;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}
}