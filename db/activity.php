<?php

namespace Groundhogg\DB;

// Exit if accessed directly
use Groundhogg\DB\Traits\IP_Address;
use function Groundhogg\generate_referer_hash;
use function Groundhogg\isset_not_empty;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Activity DB
 *
 * Stores information about a contact's site activity.
 *
 * @since       File available since Release 0.1
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
 */
class Activity extends DB {

	use IP_Address;

	/**
	 * Get the DB suffix
	 *
	 * @return string
	 */
	public function get_db_suffix() {
		return 'gh_activity';
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
	 * Listen for deletions for other objects since we don't want to hold clutter for previous things
	 * to keep the DB small.
	 */
	protected function add_additional_actions() {
		add_action( 'groundhogg/db/post_delete/contact', [ $this, 'contact_deleted' ] );
//		add_action( 'groundhogg/db/post_delete/funnel', [ $this, 'funnel_deleted' ] );
//		add_action( 'groundhogg/db/post_delete/step', [ $this, 'step_deleted' ] );
	}

	/**
	 * Get the object type we're inserting/updateing/deleting.
	 *
	 * @return string
	 */
	public function get_object_type() {
		return 'activity';
	}

	/**
	 * @param $object
	 *
	 * @return \Groundhogg\Classes\Activity
	 */
	public function create_object( $object ) {
		return new \Groundhogg\Classes\Activity( $object );
	}

	/**
	 * Get columns and formats
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function get_columns() {
		return [
			'ID'            => '%d',
			'timestamp'     => '%d',
			'funnel_id'     => '%d',
			'step_id'       => '%d',
			'contact_id'    => '%d',
			'email_id'      => '%d',
			'event_id'      => '%d',
			'activity_type' => '%s',
			'referer'       => '%s',
			'referer_hash'  => '%s',
			'value'         => '%f',
			'ip_address'    => '%s',
			'user_agent'    => '%d',
		];
	}

	/**
	 * Get default column values
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function get_column_defaults() {
		return array(
			'ID'            => 0,
			'timestamp'     => time(),
			'funnel_id'     => 0,
			'step_id'       => 0,
			'contact_id'    => 0,
			'email_id'      => 0,
			'event_id'      => 0,
			'activity_type' => '',
			'referer'       => '',
			'referer_hash'  => '',
			'value'         => 0,
			'ip_address'    => '',
			'user_agent'    => 0,
		);
	}

	/**
	 * Add a activity
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function add( $data = [] ) {

		$data = wp_parse_args(
			$data,
			$this->get_column_defaults()
		);

		if ( isset_not_empty( $data, 'referer' ) ) {
			$data['referer_hash'] = generate_referer_hash( $data['referer'] );
		}

		return $this->insert( $data );
	}

	/**
	 * @param int   $row_id
	 * @param array $data
	 * @param array $where
	 *
	 * @return bool
	 */
	public function update( $row_id = 0, $data = [], $where = [] ) {

		if ( isset_not_empty( $data, 'referer' ) ) {
			$data['referer_hash'] = generate_referer_hash( $data['referer'] );
		}

		$this->packIP( $data );

		if ( is_array( $row_id ) && ! empty( $row_id ) ){
			$this->packIP( $row_id );
		}

		if ( ! empty( $where ) ){
			$this->packIP( $where );
		}

		return parent::update( $row_id, $data, $where );
	}

	public function get_date_key() {
		return 'timestamp';
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
		return $this->bulk_delete( [ 'step_id' => $id ] );
	}

	/**
	 * Set the referer hash as aan easier method to search thru the activity
	 */
	public function update_2_2() {
		global $wpdb;
		$result = $wpdb->query( "UPDATE `{$this->get_table_name()}` SET `referer_hash` = SUBSTR( MD5(`referer`), 1, 20) WHERE `referer` != '';" );
	}

	/**
	 * Convert the IP address column and recreate indexes
	 *
	 * @return void
	 */
	public function update_3_4_2(){
		$this->drop_indexes( [
			'timestamp',
			'ip_address',
			'from_id',
			'views'
		] ); // remove old indexes

		$this->convert_ip_address_to_varbinary(); // convert the table
		$this->create_table(); // recreates indexes
	}

	/**
	 * Create the table
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function create_table_sql_command() {

		$charset_collate = $this->get_charset_collate();

		return "CREATE TABLE " . $this->table_name . " (
        ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        timestamp bigint(20) unsigned NOT NULL,
        contact_id bigint(20) unsigned NOT NULL,
        funnel_id bigint(20) unsigned NOT NULL,
        step_id bigint(20) unsigned NOT NULL,
        email_id bigint(20) unsigned NOT NULL,
        event_id bigint(20) unsigned NOT NULL,
        activity_type VARCHAR({$this->get_max_index_length()}) NOT NULL,
        referer text NOT NULL,
        referer_hash varchar(20) NOT NULL,
        value decimal(10,2) unsigned NOT NULL DEFAULT 0,
        ip_address varbinary(16) NOT NULL,
        user_agent bigint(20) unsigned NOT NULL,
        PRIMARY KEY (ID),
        KEY event_idx (event_id),
        KEY contact_idx (contact_id),
        KEY time_idx (timestamp),
        KEY funnel_step_email_idx (funnel_id,step_id,email_id)
		) $charset_collate;";
	}
}
