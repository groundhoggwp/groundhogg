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
			'child_steps'    => '%s',
			'parent_steps'   => '%s',
			'step_order'     => '%d',
			'last_edited_by' => '%s',
			'last_edited'    => '%s',
			'date_created'   => '%s',
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
			'child_steps'    => [],
			'parent_steps'   => [],
			'step_order'     => 0,
			'last_edited_by' => '',
			'last_edited'    => current_time( 'mysql' ),
			'date_created'   => current_time( 'mysql' ),
		);
	}

	/**
	 * Add a step
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function add( $data = array() ) {

		$args = wp_parse_args(
			$data,
			$this->get_column_defaults()
		);

		if ( empty( $args['step_type'] ) ) {
			return false;
		}

		map_func_to_attr( $data, 'child_steps', 'maybe_serialize' );
		map_func_to_attr( $data, 'parent_steps', 'maybe_serialize' );

//		wp_send_json( $data );

		return $this->insert( $args );
	}

	/**
	 * Unserialize any args
	 *
	 * @param $row_id
	 *
	 * @return object
	 */
	public function get( $row_id ) {
		$data = parent::get( $row_id );
		map_func_to_attr( $data, 'child_steps', 'maybe_unserialize' );
		map_func_to_attr( $data, 'parent_steps', 'maybe_unserialize' );

		return $data;
	}

	public function update( $row_id = 0, $data = [], $where = [] ) {
		map_func_to_attr( $data, 'child_steps', 'maybe_serialize' );
		map_func_to_attr( $data, 'parent_steps', 'maybe_serialize' );

		return parent::update( $row_id, $data, $where );
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

		global $wpdb;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE " . $this->table_name . " (
		ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		funnel_id bigint(20) unsigned NOT NULL,
		step_title mediumtext NOT NULL,
		step_type varchar(50) NOT NULL,
		step_group varchar(20) NOT NULL,
		step_status varchar(20) NOT NULL,
		child_steps text,
		parent_steps text,
		last_edited_by varchar(20) NOT NULL,
		step_order int unsigned NOT NULL,
		date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		last_edited datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		PRIMARY KEY  (ID)
		) {$this->get_charset_collate()};";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}
}