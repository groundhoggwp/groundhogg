<?php

namespace Groundhogg\DB;

// Exit if accessed directly
use function Groundhogg\map_func_to_attr;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Steps DB
 *
 * store steps that belong to funnels
 *
 * @since       File available since Release 0.1
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
 */
class Steps extends DB {

	/**
	 * The metadata type.
	 *
	 * @access public
	 * @since  2.8
	 * @var string
	 */
	public $meta_type = 'step';

	/**
	 * Get the DB suffix
	 *
	 * @return string
	 */
	public function get_db_suffix() {
		return 'gh_steps';
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
		return 'step';
	}

	protected function add_additional_actions() {
		add_action( 'groundhogg/db/post_delete/funnel', [ $this, 'delete_steps' ] );
		parent::add_additional_actions();
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
			'funnel_id'      => '%d',
			'step_title'     => '%s',
			'step_status'    => '%s',
			'step_type'      => '%s',
			'step_group'     => '%s',
			'last_edited_by' => '%s',
			'last_edited'    => '%s',
			'date_created'   => '%s',

			'branch'         => '%s', // This is essentially another step ID or main
			'path'           => '%s', // Which path the step is in
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
			'funnel_id'      => 0,
			'step_title'     => __( 'New Step' ),
			'step_status'    => 'ready',
			'step_type'      => 'send_email',
			'step_group'     => 'action',
			'last_edited_by' => '',
			'last_edited'    => current_time( 'mysql' ),
			'date_created'   => current_time( 'mysql' ),

			'branch'         => '', // This is essentially another step ID or main
			'path'           => '', // Which path the step is in
		);
	}

	/**
	 * Delete steps when a funnel is deleted...
	 *
	 * @param bool|int $id Funnel ID
	 *
	 * @return bool|false|int
	 */
	public function delete_steps( $id = false ) {
		if ( empty( $id ) ) {
			return false;
		}

		$steps = $this->query( array( 'funnel_id' => $id ) );

		$result = 0;

		if ( $steps ) {
			foreach ( $steps as $step ) {
				$result = $this->delete( $step->ID );
			}
		}

		return $result;
	}

	/**
	 * Create the table
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function create_table() {

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE " . $this->table_name . " (
		ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		funnel_id bigint(20) unsigned NOT NULL,
		step_title mediumtext NOT NULL,
		step_type varchar(50) NOT NULL,
		step_group varchar(20) NOT NULL,
		step_status varchar(20) NOT NULL,
		last_edited_by varchar(20) NOT NULL,
		date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		last_edited datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		branch varchar({$this->get_max_index_length()}) NOT NULL,
		path varchar({$this->get_max_index_length()}) NOT NULL,
		PRIMARY KEY  (ID)
		) {$this->get_charset_collate()};";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}
}