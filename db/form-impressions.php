<?php

namespace Groundhogg\DB;

// Exit if accessed directly
use Groundhogg\DB\Traits\IP_Address;

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
class Form_Impressions extends DB {

	use IP_Address;

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

		$records = $this->query( [
			'ip_address' => $args['ip_address'],
			'form_id'    => $args['form_id'],
			'after'      => time() - DAY_IN_SECONDS,
			'limit'      => 1
		] );

		if ( ! empty( $records ) ) {

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
	public function update_2_2() {
		global $wpdb;
		$result = $wpdb->query( "UPDATE `{$this->get_table_name()}` SET `views` = `count` WHERE `count` > 0 ;" );
		$result = $wpdb->query( "ALTER TABLE `{$this->get_table_name()}` DROP `count`;" );
	}

	public function update_3_4_2() {
		$this->drop_indexes( [ 'timestamp', 'ip_address', 'form_id', 'views' ] ); // remove old indexes
		$this->convert_ip_address_to_varbinary(); // convert the table
		$this->create_table(); // recreates indexes
	}

	/**
	 * Create the table
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function create_table_sql_command() {

		$charset_collate = $this->get_charset_collate();

		return "CREATE TABLE " . $this->table_name . " (
        ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        timestamp bigint(20) unsigned NOT NULL,
        form_id bigint(20) unsigned NOT NULL,
        ip_address varbinary(16) NOT NULL,
        views bigint(20) unsigned NOT NULL,
        PRIMARY KEY (ID),
        KEY time_idx (timestamp),
        KEY form_ip_idx (form_id,ip_address)
		) $charset_collate;";
	}
}
