<?php

namespace Groundhogg\DB;

use Groundhogg\DB\Traits\Event_Log;
use Groundhogg\DB\Traits\Event_Log_Filters;
use Groundhogg\Event;
use Groundhogg\Event_Queue_Item;
use function Groundhogg\get_db;

// Exit if accessed directly
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
class Event_Queue extends DB {

	use Event_Log;
	use Event_Log_Filters;

	public function __construct() {
		parent::__construct();
		wp_cache_add_non_persistent_groups( $this->get_cache_group() );
	}

	/**
	 * Get the DB suffix
	 *
	 * @return string
	 */
	public function get_db_suffix() {
		return 'gh_event_queue';
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
		return 'event_queue_item';
	}

	/**
	 * The date key for before/after queries
	 *
	 * @return string
	 */
	public function get_date_key() {
		return 'time';
	}

	public function create_object( $object ) {
		return new Event_Queue_Item( $object );
	}

	public function count_unprocessed() {
		return $this->count( [
			'where' => [
				'relationship' => 'AND',
				// Event should have processed over a minute ago
				[ 'time', '<', time() - MINUTE_IN_SECONDS ],
				// Event is still waiting or in progress
				[ 'status', 'IN', [ Event::WAITING, Event::IN_PROGRESS ] ],
			]
		] );
	}

	/**
	 * How long a move waits for another one to finish before going ahead anyway, in seconds.
	 */
	const MOVE_LOCK_TIMEOUT = 15;

	/**
	 * The columns an event keeps when it moves to the history table. A copy that matches the queued event on all of these
	 * is the same event, not a later run of an event whose ID was used again.
	 *
	 * Queue IDs aren't unique in the history table forever, older MySQL versions start the AUTO_INCREMENT of an empty
	 * table again from 1 after a restart, so the queued_id alone can't tell a copy from another event.
	 */
	const MOVE_MATCH_COLUMNS = [
		'ID'         => 'queued_id',
		'contact_id' => 'contact_id',
		'funnel_id'  => 'funnel_id',
		'step_id'    => 'step_id',
		'event_type' => 'event_type',
		'time'       => 'time',
		'status'     => 'status',
	];

	/**
	 * SQL that is true when the queued row {$queue_ref} already has its copy in the history table.
	 *
	 * @param string $queue_ref what the queue table is called in the outer query
	 *
	 * @return string
	 */
	protected function in_history_sql( string $queue_ref ) {

		$events     = get_db( 'events' )->get_table_name();
		$conditions = [];

		foreach ( self::MOVE_MATCH_COLUMNS as $queue_col => $history_col ) {
			$conditions[] = "h.$history_col = $queue_ref.$queue_col";
		}

		// the queued_id comes first so the lookup uses queued_idx
		return "EXISTS ( SELECT 1 FROM $events h WHERE " . implode( ' AND ', $conditions ) . ' )';
	}

	/**
	 * Move events from the queue to the history table
	 *
	 * Safe to run from several processes at once, and again over the same rows. Every queue run ends with a move of all
	 * the finished events in the table, not only the ones it ran, and the queue runs from WP-Cron, gh-cron.php, the
	 * heartbeat and when a contact's events are processed right away, so moves overlap. The copy and the delete are two
	 * statements, and a second move that copied the rows before the first one deleted them added every event to the
	 * history table twice. Now:
	 *
	 * - moves wait for each other with a named lock, so one copies and deletes before the next one looks
	 * - an event that is in the history table already isn't copied again, for when the lock isn't there or a move
	 *   stopped between the copy and the delete
	 * - an event is only deleted from the queue once its copy is in the history table, so a failed copy loses nothing
	 *
	 * @param array  $where
	 * @param string $relationship
	 */
	public function move_events_to_history( $where = [], $relationship = 'OR' ) {

		global $wpdb;

		// Move waiting events from the legacy queue to new queue
		$event_queue = $this->get_table_name();
		$events      = get_db( 'events' )->get_table_name();

		$column_map = [
			'ID'             => 'queued_id',
			'time'           => 'time',
			'micro_time'     => 'micro_time',
			'time_scheduled' => 'time_scheduled',
			'contact_id'     => 'contact_id',
			'funnel_id'      => 'funnel_id',
			'step_id'        => 'step_id',
			'email_id'       => 'email_id',
			'event_type'     => 'event_type',
			'error_code'     => 'error_code',
			'error_message'  => 'error_message',
			'priority'       => 'priority',
			'status'         => 'status',
			'args'           => 'args',
		];

		$history_columns = implode( ',', array_values( $column_map ) );
		$queue_columns   = implode( ',', array_keys( $column_map ) );

		$where = $this->generate_where( $where, $relationship );

		$locked = $this->get_move_lock();

		// phpcs:disable WordPress.DB.PreparedSQL -- table names, column names and a where clause built by generate_where()

		// Copy the events to the history table, apart from any that are there already
		$wpdb->query( "INSERT INTO $events ($history_columns)
			SELECT $queue_columns
			FROM $event_queue q
			WHERE ( $where ) AND status != 'waiting' AND NOT " . $this->in_history_sql( 'q' ) );

		// Then delete from the queue only the events that made it to the history table
		$wpdb->query( "DELETE FROM $event_queue WHERE ( $where ) AND status != 'waiting' AND " . $this->in_history_sql( $event_queue ) . ' ORDER BY ID;' );

		// phpcs:enable WordPress.DB.PreparedSQL

		if ( $locked ) {
			$this->release_move_lock();
		}

		$this->cache_set_last_changed();
		get_db( 'events' )->cache_set_last_changed();
	}

	/**
	 * The name of the lock moves wait on. Lock names are shared by every database on the server, so it's per table.
	 *
	 * @return string
	 */
	protected function get_move_lock_name() {
		return 'gh_move_events_' . md5( $this->get_table_name() );
	}

	/**
	 * Wait for any other move to finish. The lock belongs to the database connection, so it goes away by itself if the
	 * request dies.
	 *
	 * If it can't be had, because another move is taking longer than MOVE_LOCK_TIMEOUT or the database doesn't support
	 * GET_LOCK(), the move goes ahead anyway and the checks in the queries keep it from adding copies.
	 *
	 * @return bool whether the lock was acquired
	 */
	protected function get_move_lock() {
		global $wpdb;

		$suppress = $wpdb->suppress_errors();
		$locked   = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK( %s, %d )', $this->get_move_lock_name(), self::MOVE_LOCK_TIMEOUT ) );
		$wpdb->suppress_errors( $suppress );

		return absint( $locked ) === 1;
	}

	/**
	 * Let the next move go ahead
	 *
	 * @return void
	 */
	protected function release_move_lock() {
		global $wpdb;

		$suppress = $wpdb->suppress_errors();
		$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK( %s )', $this->get_move_lock_name() ) );
		$wpdb->suppress_errors( $suppress );
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
			'claim'          => '%s',
			'time_claimed'   => '%d',
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
			'status'         => Event::WAITING,
			'priority'       => 10,
			'claim'          => '',
			'time_claimed'   => 0,
			'args'           => serialize( [] ),
		);
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
        contact_id bigint(20) unsigned NOT NULL,
        funnel_id bigint(20) unsigned NOT NULL,
        step_id bigint(20) unsigned NOT NULL,
        email_id bigint(20) unsigned NOT NULL,
        event_type int unsigned NOT NULL,
        error_code tinytext NOT NULL,
        error_message tinytext NOT NULL, 
        priority int unsigned NOT NULL,
        status varchar(20) NOT NULL,
        claim varchar(20) NOT NULL,
        time_claimed bigint(20) unsigned NOT NULL DEFAULT 0,
        args text NOT NULL,
        PRIMARY KEY (ID),
        KEY time_idx (time),
        KEY contact_idx (contact_id),
        KEY funnel_idx (funnel_id),
        KEY step_idx (step_id),
        KEY claim_idx (claim)
		) {$this->get_charset_collate()};";
	}
}
