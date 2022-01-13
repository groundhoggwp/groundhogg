<?php

namespace Groundhogg\DB;

// Exit if accessed directly
use Groundhogg\Contact;
use Groundhogg\Plugin;
use function Groundhogg\get_current_ip_address;
use function Groundhogg\get_db;
use function Groundhogg\is_option_enabled;

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
class Page_Visits extends DB {

	public function __construct() {
		parent::__construct();

		add_action( 'init', [ $this, 'init' ] );
	}

	/**
	 * Setup any cron stuffs
	 */
	public function init(){

		if ( ! is_option_enabled( 'gh_purge_page_visits' ) ){
			return;
		}

		$this->setup_cron();
		add_action( 'gh_purge_page_visits', [ $this, 'purge' ] );
	}

	/**
	 * Setup the CRON listener to purge old events
	 */
	public function setup_cron() {
		if ( ! wp_next_scheduled( 'gh_purge_page_visits' ) ) {
			wp_schedule_event( time(), 'daily', 'gh_purge_page_visits' );
		}
	}

	/**
	 * Purge old logs to conserve space
	 */
	public function purge() {

		if ( ! is_option_enabled( 'gh_purge_page_visits' ) ){
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
			'query'      => '%s',
			'fragment'   => '%s',
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
			'contact_id' => 0,
			'timestamp'  => time(),
			'path'       => '',
			'query'      => '',
			'fragment'   => '',
			'ip_address' => get_current_ip_address(),
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
			'contact_id' => $args['contact_id'],
			'ip_address' => $args['ip_address'],
			'path'       => $args['path'],
			'query'      => $args['query'],
			'fragment'   => $args['fragment'],
			'before'     => time(),
			'after'      => time() - HOUR_IN_SECONDS
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
        contact_id bigint(20) unsigned NOT NULL,
        ip_address varchar(15) NOT NULL,
        path varchar({$this->get_max_index_length()}) NOT NULL,
        query text NOT NULL,
        fragment text NOT NULL,
        views bigint(20) unsigned NOT NULL,
        PRIMARY KEY (ID),
        KEY timestamp (timestamp),
        KEY ip_address (ip_address),
        KEY contact_id (contact_id),
        KEY path (path)
		) $charset_collate;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}
}