<?php

namespace Groundhogg\DB;

use Groundhogg\Campaign;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tags DB
 *
 * Store campaigns
 *
 * @since       File available since Release 0.1
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
 */
class Campaigns extends DB {

	/**
	 * Get the DB suffix
	 *
	 * @return string
	 */
	public function get_db_suffix() {
		return 'gh_campaigns';
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
		return 'campaign';
	}

	/**
	 * @param \Groundhogg\Base_Object $object
	 *
	 * @return Campaign
	 */
	public function create_object( $object ) {
		return new Campaign( $object );
	}

	protected function add_additional_actions() {
	}

	/**
	 * Get columns and formats
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function get_columns() {
		return array(
			'ID'          => '%d',
			'name'        => '%s',
			'slug'        => '%s',
			'description' => '%s',
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
			'ID'          => 0,
			'name'        => '',
			'slug'        => '',
			'description' => '',
		);
	}

	/**
	 * Add a campaign
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function add( $data = array() ) {

		$args = wp_parse_args(
			$data,
			$this->get_column_defaults()
		);

		if ( empty( $args['name'] ) ) {
			return false;
		}

		$args['slug'] = sanitize_title( $args['name'] );
		if ( $this->exists( [ 'slug' => $args['slug'] ]  ) ) {
			$campaign = $this->get_by( 'slug', $args['slug'] );

			return $campaign->ID;
		}

		return $this->insert( $args );
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
        slug varchar({$this->get_max_index_length()}) NOT NULL,
        name mediumtext NOT NULL,
        description text NOT NULL,
        PRIMARY KEY (ID),
        UNIQUE KEY slug (slug)
		) {$this->get_charset_collate()};";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}
}