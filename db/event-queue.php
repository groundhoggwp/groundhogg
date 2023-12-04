<?php

namespace Groundhogg\DB;

// Exit if accessed directly
use Groundhogg\Broadcast;
use Groundhogg\Event;
use Groundhogg\Event_Queue_Item;
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
class Event_Queue extends DB {

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
	 * @return string
	 */
	public function get_date_key() {
		return 'time';
	}

	public function count_unprocessed() {
		return $this->count( [
			'where'  => [
				'relationship' => 'AND',
				// Event should have processed over a minute ago
				[ 'time', '<', time() - MINUTE_IN_SECONDS ],
				// Event is still waiting or in progress
				[ 'status', 'IN', [ Event::WAITING, Event::IN_PROGRESS ] ],
			]
		] );
	}

	/**
	 * Move events from the queue to the history table
	 *
	 * @param array $where
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
		];

		$history_columns = implode( ',', array_values( $column_map ) );
		$queue_columns   = implode( ',', array_keys( $column_map ) );

		$where = $this->generate_where( $where, $relationship );

		// added two different query because single query was not working on my localhost(says: ERROR in your SQL statement please review it.)
		// Move the events to the event queue
		$wpdb->query( "INSERT INTO $events ($history_columns)
			SELECT $queue_columns
			FROM $event_queue
			WHERE ( $where ) AND status != 'waiting'" );

		$wpdb->query( "DELETE FROM $event_queue WHERE ( $where ) AND status != 'waiting' ORDER BY ID;" );

		$this->cache_set_last_changed();
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
	 * Get all the queued events
	 */
	public function get_queued_event_ids() {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $this->table_name WHERE time <= %d AND status = %s"
				, time(), 'waiting' )
		);

		return wp_parse_id_list( wp_list_pluck( $results, 'ID' ) );
	}


	/**
	 * Clean up DB events when this happens.
	 */
	protected function add_additional_actions() {
		add_action( 'groundhogg/db/post_delete/contact', [ $this, 'contact_deleted' ] );
		add_action( 'groundhogg/db/post_delete/funnel', [ $this, 'funnel_deleted' ] );
		add_action( 'groundhogg/db/post_delete/step', [ $this, 'step_deleted' ] );
		parent::add_additional_actions();
	}

	protected function maybe_register_filters() {
		parent::maybe_register_filters();

		$this->query_filters->register_filter( 'funnel', function ( $filter, $where ) {
			$filter = wp_parse_args( $filter, [
				'funnel_id' => false,
				'step_id'   => false,
			] );

			if ( $filter['funnel_id'] ){
				$where->equals( 'funnel_id', absint( $filter['funnel_id'] ) );
			}

			if ( $filter['step_id'] ){
				$where->equals( 'step_id', absint( $filter['step_id'] ) );
			}

			$where->equals( 'event_type', Event::FUNNEL );
		} );

		$this->query_filters->register_filter( 'broadcast', function ( $filter, $where ) {
			$filter = wp_parse_args( $filter, [
				'broadcast_id' => false,
			] );

			if ( $filter['broadcast_id'] ){
				$where->equals( 'step_id', absint( $filter['broadcast_id'] ) );
			}

			$where->equals( 'event_type', Event::BROADCAST );
			$where->equals( 'funnel_id', Broadcast::FUNNEL_ID );

			var_dump( $filter );
		} );

		$this->query_filters->register_filter( 'contacts', function ( $filter, $where ) {
			$filter = wp_parse_args( $filter, [
				'contacts' => [],
			] );

			$where->in( 'contact_id', wp_parse_id_list( $filter['contacts'] ) );
		} );
	}

	/**
	 * Delete events for a contact that was just deleted...
	 *
	 * @param $id
	 *
	 * @return false|int
	 */
	public function contact_deleted( $id ) {

		if ( ! is_numeric( $id ) ) {
			return false;
		}

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

		if ( ! is_numeric( $id ) ) {
			return false;
		}

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

		if ( ! is_numeric( $id ) ) {
			return false;
		}

		return $this->bulk_delete( [ 'step_id' => $id ] );
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
        PRIMARY KEY (ID),
        KEY time (time),
        KEY time_scheduled (time_scheduled),
        KEY contact_id (contact_id),
        KEY funnel_id (funnel_id),
        KEY step_id (step_id),
        KEY priority (priority)
		) {$this->get_charset_collate()};";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}

	public function create_object( $object ) {
		return new Event_Queue_Item( $object );
	}
}
