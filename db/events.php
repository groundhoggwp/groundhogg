<?php

namespace Groundhogg\DB;

// Exit if accessed directly
use Groundhogg\DB\Traits\Event_Log;
use Groundhogg\DB\Traits\Event_Log_Filters;
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

	use Event_Log;
	use Event_Log_Filters;

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
	 * Get the object type we're inserting/updating/deleting.
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
	 * Create an event object from a raw one
	 *
	 * @param $object
	 *
	 * @return Event
	 */
	public function create_object( $object ) {
		return new Event( $object );
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
			'email_id'       => '%d',
			'contact_id'     => '%d',
			'event_type'     => '%d',
			'error_code'     => '%s',
			'error_message'  => '%s',
			'status'         => '%s',
			'priority'       => '%d',
			'queued_id'      => '%d',
			'args'           => '%s',
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
			'email_id'       => 0,
			'contact_id'     => 0,
			'event_type'     => Event::FUNNEL,
			'error_code'     => '',
			'error_message'  => '',
			'status'         => 'waiting',
			'priority'       => 10,
			'queued_id'      => 0,
			'args'           => '',
		);
	}

	/**
	 * Move events from this table to the event queue
	 *
	 * @param array $where
	 * @param bool  $delete_from_history whether to delete the records from the history table
	 */
	public function move_events_to_queue( $where = [], $delete_from_history = false, $column_map = [] ) {

		global $wpdb;

		// Move waiting events from the legacy queue to new queue
		$event_queue = get_db( 'event_queue' )->get_table_name();
		$events      = $this->get_table_name();

		$column_map = wp_parse_args( $column_map, [
			'time'           => 'time',
			'micro_time'     => 'micro_time',
			'time_scheduled' => 'time_scheduled',
			'contact_id'     => 'contact_id',
			'funnel_id'      => 'funnel_id',
			'step_id'        => 'step_id',
			'email_id'       => 'email_id',
			'event_type'     => 'event_type',
			'priority'       => 'priority',
			'status'         => 'status',
			'args'           => 'args',
		] );

		$history_columns = array_values( $column_map );

		foreach ( $history_columns as &$column ) {
			if ( ! $this->has_column( $column ) ) {
				$column = is_numeric( $column ) ? $column : "'$column'";
			}
		}

		$history_columns = implode( ',', $history_columns );
		$queue_columns   = implode( ',', array_keys( $column_map ) );

		$where = $this->generate_where( $where );

		// added two different query because single query was not working on my localhost(says: ERROR in your SQL statement please review it.)
		// Move the events to the event queue
		$inserted = $wpdb->query( "INSERT INTO $event_queue ($queue_columns)
			SELECT $history_columns
			FROM $events
			WHERE $where" );

		// Only moved one event
		if ( $inserted === 1 ) {
			$inserted = $wpdb->insert_id;
		}

		// Optionally delete these events for backwards compatibility...
		// this way we can retain a record of retires as well...
		if ( $delete_from_history === true ) {
			$wpdb->query( "DELETE FROM $events WHERE $where;" );
		}

		$this->cache_set_last_changed();
		get_db( 'event_queue' )->cache_set_last_changed();

		return $inserted;
	}

	/**
	 * The columns two history rows must share to be copies of one queued event. Everything the move copies apart from
	 * error details and args, so a different event that got a queue ID that was used before is never matched.
	 */
	const DUPLICATE_MATCH_COLUMNS = [
		'queued_id',
		'contact_id',
		'funnel_id',
		'step_id',
		'email_id',
		'event_type',
		'time',
		'micro_time',
		'time_scheduled',
		'status',
	];

	/**
	 * SQL joining a copy (dup) to an earlier row of the same queued event (keep)
	 *
	 * @return string
	 */
	protected function duplicate_join_sql() {

		$conditions = [ 'keep.ID < dup.ID' ];

		foreach ( self::DUPLICATE_MATCH_COLUMNS as $column ) {
			$conditions[] = "keep.$column = dup.$column";
		}

		return "INNER JOIN $this->table_name keep ON " . implode( ' AND ', $conditions );
	}

	/**
	 * Delete the copies of queued events that a move to the history table added more than once, keeping the row with the
	 * lowest ID. Only looks at rows with an ID in ( $after_id, $up_to_id ], so it can be run in batches over a large table.
	 * Legacy rows without a queued_id are left alone.
	 *
	 * @param int $after_id
	 * @param int $up_to_id
	 *
	 * @return array{deleted:int,broadcast_ids:int[]} how many were deleted, and the broadcasts whose events were among them
	 */
	public function delete_duplicate_events( int $after_id, int $up_to_id ) {
		global $wpdb;

		$join = $this->duplicate_join_sql();

		// So that their cached reports can be cleared
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name and join built from constants
		$broadcast_ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT dup.step_id FROM $this->table_name dup $join
			WHERE dup.ID > %d AND dup.ID <= %d AND dup.queued_id > 0 AND dup.event_type = %d", $after_id, $up_to_id, Event::BROADCAST ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name and join built from constants
		$deleted = $wpdb->query( $wpdb->prepare( "DELETE dup FROM $this->table_name dup $join
			WHERE dup.ID > %d AND dup.ID <= %d AND dup.queued_id > 0", $after_id, $up_to_id ) );

		if ( $deleted ) {
			$this->cache_set_last_changed();
		}

		return [
			'deleted'       => absint( $deleted ),
			'broadcast_ids' => wp_parse_id_list( $broadcast_ids ),
		];
	}

	/**
	 * For some reason, queued_id is getting duplicated on large installs
	 * So let's order the table by ID DESC so we get the most recent event...
	 *
	 * @access  public
	 *
	 * @since   2.1
	 *
	 * @param $row_id
	 *
	 * @param $column
	 *
	 * @return  object
	 */
	public function get_by( $column, $row_id ) {

		if ( $column === $this->primary_key ) {
			return parent::get_by( $column, $row_id );
		}

		global $wpdb;
		$column = esc_sql( $column );

		$cache_key   = "get_by:$column:$row_id";
		$cache_value = $this->cache_get( $cache_key );

		if ( $cache_value ) {
			return $cache_value;
		}

		$results = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE `$column` = %s ORDER BY `ID` DESC LIMIT 1;", $row_id ) );

		$this->cache_set( $cache_key, $results );

		return apply_filters( 'groundhogg/db/get/' . $this->get_object_type(), $results );
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
	 * Drop and recreate indexes
	 *
	 * @return void
	 */
	public function update_3_4_2() {

		$this->drop_indexes( [
			'time',
			'time_scheduled',
			'time_and_micro_time',
			'contact_id',
			'queued_id',
			'funnel_id',
			'step_id',
			'priority'
		] );

		$this->create_table();
	}

	/**
	 * Create the table
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function create_table_sql_command() {

		return "CREATE TABLE " . $this->table_name . " (
        ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        time bigint(20) unsigned NOT NULL,
        micro_time float(8) unsigned NOT NULL,
        time_scheduled bigint(20) unsigned NOT NULL,
        queued_id bigint(20) unsigned NOT NULL,
        contact_id bigint(20) unsigned NOT NULL,
        funnel_id bigint(20) unsigned NOT NULL,
        step_id bigint(20) unsigned NOT NULL,
        email_id bigint(20) unsigned NOT NULL,
        event_type int unsigned NOT NULL,
        error_code tinytext NOT NULL,
        error_message tinytext NOT NULL, 
        priority int unsigned NOT NULL,
        status varchar(20) NOT NULL,
        args text NOT NULL,
        PRIMARY KEY (ID),
        KEY queued_idx (queued_id),
        KEY contact_idx (contact_id),
        KEY time_micro_time_idx (time, micro_time),
        KEY funnel_step_email_idx (funnel_id,step_id,email_id)
		) {$this->get_charset_collate()};";
	}
}
