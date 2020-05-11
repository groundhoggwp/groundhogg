<?php

namespace Groundhogg\DB;

// Exit if accessed directly
use Groundhogg\Event;
use function Groundhogg\get_array_var;
use function Groundhogg\get_db;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Events DB
 *
 * Store automation events
 *
 * @since       File available since Release 0.1
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
 */
class Events extends DB {

	/**
	 * Get the DB suffix
	 *
	 * @return string
	 */
	public function get_db_suffix() {
		return 'gh_events';
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
		return 'event';
	}

	/**
	 * @return string
	 */
	public function get_date_key() {
		return 'time';
	}

	/**
	 * Get columns and formats
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function get_columns() {
		return array(
			'ID'             => '%d',
			'time'           => '%d',
			'micro_time'     => '%s',
			'time_scheduled' => '%d',
			'funnel_id'      => '%d',
			'step_id'        => '%d',
			'contact_id'     => '%d',
			'event_type'     => '%d',
			'error_code'     => '%s',
			'error_message'  => '%s',
			'status'         => '%s',
			'priority'       => '%d',
			'claim'          => '%s',
			'queued_id'      => '%d',
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
			'ID'             => 0,
			'time'           => time(),
			'micro_time'     => 0,
			'time_scheduled' => time(),
			'funnel_id'      => 0,
			'step_id'        => 0,
			'contact_id'     => 0,
			'event_type'     => Event::FUNNEL,
			'error_code'     => '',
			'error_message'  => '',
			'status'         => 'waiting',
			'priority'       => 10,
			'claim'          => '',
			'queued_id'      => 0,
		);
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

		if ( empty( $args['time'] ) ) {
			return false;
		}

		return $this->insert( $args );
	}

	/**
	 * Move events from this table to the event queue
	 *
	 * @param array $where
	 * @param bool $delete_from_history whether to delete the records from the history table
	 */
	public function move_events_to_queue( $where = [], $delete_from_history = false ) {

		global $wpdb;

		// Move waiting events from the legacy queue to new queue
		$event_queue = get_db( 'event_queue' )->get_table_name();
		$events      = $this->get_table_name();

		$queue_columns   = get_db( 'event_queue' )->get_columns();
		$history_columns = $this->get_columns(); // queue_id will be last

		unset( $history_columns['ID'] );
		unset( $history_columns['queued_id'] );
		unset( $queue_columns['ID'] );

		$history_columns = implode( ',', array_keys( $history_columns ) );
		$queue_columns   = implode( ',', array_keys( $queue_columns ) );

		$where = $this->generate_where( $where );

		// added two different query because single query was not working on my localhost(says: ERROR in your SQL statement please review it.)
		// Move the events to the event queue
		$wpdb->query( "INSERT INTO $event_queue ($queue_columns)
			SELECT $history_columns
			FROM $events
			WHERE $where" );

		// Optionally delete these events for backwards compatibility...
		// this way we can retain a record of retires as well...
		if ( $delete_from_history === true ) {
			$wpdb->query( "DELETE FROM $events WHERE $where;" );
		}
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
	 * Delete events for a funnel that was just deleted...
	 *
	 * @param $id
	 *
	 * @return false|int
	 */
	public function funnel_deleted( $id ) {
		return $this->bulk_delete( [ 'funnel_id' => $id ] );
	}

	/**
	 * Delete events for a step that was just deleted...
	 *
	 * @param $id
	 *
	 * @return false|int
	 */
	public function step_deleted( $id ) {
		// This is only used when funnel steps are deleted...
		return $this->bulk_delete( [ 'step_id' => $id, 'event_type' => Event::FUNNEL ] );
	}

	/**
	 * Add micro time to table ordering.
	 *
	 * @param array $query_vars
	 *
	 * @return string|string[]
	 */
	public function get_sql( $query_vars = [] ) {

		$sql = parent::get_sql( $query_vars );

		// Double compare to better display completion order
		if ( get_array_var( $query_vars, 'orderby' ) === 'time' ) {
			$sql = str_replace( 'ORDER BY time DESC', 'ORDER BY `time` DESC, `micro_time` DESC', $sql );
			$sql = str_replace( 'ORDER BY time ASC', 'ORDER BY `time` ASC, `micro_time` ASC', $sql );
		}

		return $sql;
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
        time bigint(20) unsigned NOT NULL,
        micro_time float(8) unsigned NOT NULL,
        time_scheduled bigint(20) unsigned NOT NULL,
        queued_id bigint(20) unsigned NOT NULL,
        contact_id bigint(20) unsigned NOT NULL,
        funnel_id bigint(20) unsigned NOT NULL,
        step_id bigint(20) unsigned NOT NULL,
        event_type int unsigned NOT NULL,
        error_code tinytext NOT NULL,
        error_message tinytext NOT NULL, 
        priority int unsigned NOT NULL,
        status varchar(20) NOT NULL,
        claim varchar(20) NOT NULL,
        PRIMARY KEY (ID),
        KEY time (time),
        KEY time_scheduled (time_scheduled),
        KEY time_and_micro_time (time, micro_time),
        KEY contact_id (contact_id),
        KEY queued_id (queued_id),
        KEY funnel_id (funnel_id),
        KEY step_id (step_id),
        KEY claim (claim),
        KEY priority (priority)
		) {$this->get_charset_collate()};";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}

	/**
	 * Clean up DB events when this happens.
	 */
	protected function add_additional_actions() {
		add_action( 'groundhogg/db/post_delete/contact', [ $this, 'contact_deleted' ] );
		add_action( 'groundhogg/db/post_delete/funnel', [ $this, 'funnel_deleted' ] );
		add_action( 'groundhogg/db/post_delete/step', [ $this, 'step_deleted' ] );
		parent::add_additional_actions(); // TODO: Change the autogenerated stub
	}
}