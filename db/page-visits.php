<?php

namespace Groundhogg\DB;

// Exit if accessed directly
use Groundhogg\DB\Traits\IP_Address;
use function Groundhogg\is_option_enabled;

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
class Page_Visits extends DB {

	use IP_Address;

	public function __construct() {
		parent::__construct();

		add_action( 'init', [ $this, 'init' ] );
	}

	/**
	 * Setup any cron stuffs
	 */
	public function init() {

		if ( ! is_option_enabled( 'gh_purge_page_visits' ) ) {
			return;
		}

		add_action( 'groundhogg/daily', [ $this, 'purge' ] );
	}

	/**
	 * Purge old logs to conserve space
	 */
	public function purge() {

		if ( ! is_option_enabled( 'gh_purge_page_visits' ) ) {
			return;
		}

		global $wpdb;

		$retention_in_days = get_option( 'gh_page_visits_log_retention', 90 ) ?: 90;
		$compare_date      = time() - ( $retention_in_days * DAY_IN_SECONDS );

		$wpdb->query( "DELETE from {$this->table_name} WHERE timestamp <= $compare_date" );
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
	 * Get the DB suffix
	 *
	 * @return string
	 */
	public function get_db_suffix() {
		return 'gh_page_visits';
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
		return 'page_visit';
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
			'contact_id' => '%d',
			'timestamp'  => '%d',
			'path'       => '%s',
			'hostname'   => '%s',
			'query'      => '%s',
			'fragment'   => '%s',
			'views'      => '%d',
			'ip_address' => '%s',
			'user_agent' => '%d'
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
			'contact_id' => 0,
			'timestamp'  => time(),
			'hostname'   => '',
			'path'       => '',
			'query'      => '',
			'fragment'   => '',
			'views'      => 1,
			'ip_address' => '',
			'user_agent' => 0
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
			'contact_id' => $args['contact_id'],
			'ip_address' => $args['ip_address'],
			'path'       => $args['path'],
			'after'      => time() - HOUR_IN_SECONDS,
			'limit'      => 1,
			'orderby'    => 'timestamp',
			'order'      => 'DESC'
		] );

		// Record for this visit already exists
		if ( ! empty( $records ) ) {

			$record = array_shift( $records );
			$id     = absint( $record->ID );

			// increment page views
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
	 * Improve table for binary storage of IP addresses
	 *
	 * @return void
	 */
	public function update_3_4_2() {
		$this->drop_indexes( [
			'timestamp',
			'ip_address',
			'contact_id',
			'path'
		] ); // remove old indexes
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
        contact_id bigint(20) unsigned NOT NULL,
        hostname varchar({$this->get_max_index_length()}) NOT NULL,
        path varchar({$this->get_max_index_length()}) NOT NULL,
        query text NOT NULL,
        fragment text NOT NULL,
        views bigint(20) unsigned NOT NULL,
        ip_address varbinary(16) NOT NULL,
        user_agent bigint(20) unsigned NOT NULL,
        PRIMARY KEY (ID),
        KEY contact_ip_path_idx (contact_id,ip_address,path),
        KEY time_idx (timestamp)
		) $charset_collate;";
	}
}
