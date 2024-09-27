<?php


namespace Groundhogg\DB;

use Groundhogg\Classes\Background_Task;
use Groundhogg\DB\Query\Where;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Background_Tasks extends DB {

	public function get_db_suffix() {
		return 'gh_background_tasks';
	}

	public function get_object_type() {
		return 'background_task';
	}

	public function create_object( $object ) {
		return new Background_Task( $object );
	}

	protected function maybe_register_filters() {
		parent::maybe_register_filters();

		$this->query_filters->register( 'task_type', function ( array $filter, Where $where ) {
			$type = $filter['value'];
			$where->contains( 'task', $type );
		} );
	}

	/**
	 * Get columns and formats
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function get_columns() {
		return [
			'ID'           => '%d',
			'user_id'      => '%d',
			'task'         => '%s',
			'time'         => '%d',
			'claim'        => '%s',
			'time_claimed' => '%d',
			'status'       => '%s',
			'date_created' => '%s',
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
			'ID'           => 0,
			'user_id'      => get_current_user_id(),
			'task'         => '',
			'time'         => time(),
			'claim'        => '',
			'time_claimed' => 0,
			'status'       => 'pending',
			'date_created' => current_time( 'mysql' ),
		];
	}

	public function create_table_sql_command() {
		return "CREATE TABLE " . $this->table_name . " (
		ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        task longtext NOT NULL,
        time bigint(12) unsigned NOT NULL,
        status varchar(20) NOT NULL,
        claim varchar(20) NOT NULL,
        time_claimed unsigned bigint(20) NOT NULL DEFAULT 0,
        date_created datetime NOT NULL,
        PRIMARY KEY (ID)
		) {$this->get_charset_collate()};";
	}

	public function get_primary_key() {
		return 'ID';
	}

	public function get_db_version() {
		return '1.0';
	}
}
