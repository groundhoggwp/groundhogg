<?php

namespace Groundhogg\DB;

// Exit if accessed directly
use function Groundhogg\Ymd_His;

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
class Webhooks extends DB {

	/**
	 * The metadata type.
	 *
	 * @access public
	 * @since  2.8
	 * @var string
	 */
	public $meta_type = 'webhook';

	/**
	 * Get the DB suffix
	 *
	 * @return string
	 */
	public function get_db_suffix() {
		return 'gh_webhooks';
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
		return 'webhook';
	}

	/**
	 * Get columns and formats
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function get_columns() {
		return array(
			'ID'              => '%d',
			'endpoint'        => '%s',
			'initiation'      => '%s',
			'events'          => '%s',
			'status'          => '%s',
			'last_event'      => '%s',
			'date_created'    => '%s',
			'date_last_event' => '%s',
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
			'ID'              => 0,
			'endpoint'        => '',
			'initiation'      => '',
			'events'          => '',
			'status'          => 'draft',
			'last_event'      => '',
			'date_created'    => Ymd_His(),
			'date_last_event' => Ymd_His(),
		);
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
		endpoint longtext NOT NULL,
		initiation longtext NOT NULL,
		events longtext NOT NULL,
		status varchar(20) NOT NULL,
		last_event longtext NOT NULL,
		date_last_event datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		PRIMARY KEY  (ID)
		) {$this->get_charset_collate()};";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}
}