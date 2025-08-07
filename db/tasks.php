<?php


namespace Groundhogg\DB;

use Groundhogg\Classes\Task;
use function Groundhogg\isset_not_empty;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tasks extends Notes {

	public function get_db_suffix() {
		return 'gh_tasks';
	}

	public function get_object_type() {
		return 'task';
	}

	public function create_object( $object ) {
		return new Task( $object );
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
			'object_id'      => '%d',
			'object_type'    => '%s',
			'step_id'        => '%d',
			'funnel_id'      => '%d',
			'timestamp'      => '%d',
			'context'        => '%s', // added by
			'user_id'        => '%d',
			'content'        => '%s',
			'summary'        => '%s',
			'type'           => '%s',
			'due_date'       => '%s', // local time
			'date_completed' => '%s', // UTC-0
			'date_created'   => '%s', // UTC-0
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
			'object_id'      => 0,
			'step_id'        => 0,
			'funnel_id'      => 0,
			'timestamp'      => time(),
			'object_type'    => '',
			'context'        => 'user', // added by
			'user_id'        => get_current_user_id(),
			'content'        => '',
			'summary'        => '',
			'type'           => 'task',
			'due_date'       => '', // local time
			'date_created'   => current_time( 'mysql', true ), // UTC-0
			'date_completed' => '', // UTC-0
		);
	}

	/**
	 * Map easy args to actual queryable clauses
	 *
	 * @param array  $data
	 * @param string $ORDER_BY
	 * @param bool   $from_cache
	 *
	 * @return array|array[]|bool|int|object|object[]|null
	 */
	public function query( $data = [], $ORDER_BY = '', $from_cache = true ) {

		// Only show tasks belonging to the current user
		if ( current_user_can( 'view_tasks' ) && ! current_user_can( 'view_others_tasks' ) ) {
			$data['user_id'] = get_current_user_id();
		}

		if ( isset_not_empty( $data, 'incomplete' ) ) {
			$data['date_completed'] = '0000-00-00 00:00:00';
			unset( $data['incomplete'] );
		}

		if ( isset_not_empty( $data, 'complete' ) ) {
			$data['date_completed'] = [ '!=', '0000-00-00 00:00:00' ];
			unset( $data['complete'] );
		}

		if ( isset_not_empty( $data, 'mine' ) ) {
			$data['user_id'] = get_current_user_id();
			unset( $data['mine'] );
		}

		return DB::query( $data, $ORDER_BY, $from_cache );
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
        object_id bigint(20) unsigned NOT NULL,
        object_type VARCHAR({$this->get_max_index_length()}) NOT NULL,    
        step_id bigint(20) unsigned NOT NULL,
        funnel_id bigint(20) unsigned NOT NULL,
        user_id bigint(20) unsigned NOT NULL,
        context VARCHAR(50) NOT NULL,    
        type VARCHAR(50) NOT NULL,    
        summary text NOT NULL,
        content longtext NOT NULL,
        timestamp bigint(12) unsigned NOT NULL,
        date_created datetime NOT NULL,
        date_completed datetime NOT NULL,
        due_date datetime NOT NULL,
        PRIMARY KEY (ID)
		) {$this->get_charset_collate()};";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}
}
