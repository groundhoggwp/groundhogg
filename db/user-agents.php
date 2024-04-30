<?php

namespace Groundhogg\DB;

class User_Agents extends DB {

	public function get_db_suffix() {
		return 'gh_user_agents';
	}

	public function get_primary_key() {
		return 'ID';
	}

	public function get_db_version() {
		return '1.0';
	}

	public function get_object_type() {
		return 'user_agent';
	}

	/**
	 * Make sure the hash is set
	 *
	 * @param $data
	 *
	 * @return int
	 */
	public function add( $data = array() ) {

		// UA passed as string directly
		if ( is_string( $data ) ){
			$data = [
				'user_agent' => $data
			];
		}

		if ( empty( $data[ 'user_agent' ] ) ){
			return false;
		}

		if ( ! isset( $data['user_agent_hash'] ) ) {
			$data['user_agent_hash'] = hex2bin( hash( 'sha256', $data['user_agent'] ) );
		}

		return parent::add( $data );
	}

	/**
	 * Wrapper for insert to use INSERT IGNORE
	 *
	 * @param $data
	 *
	 * @return int
	 */
	public function insert( $data ) {

		add_filter( 'query', [ $this, '_insert_ignore' ] );
		$result = parent::insert( $data );
		remove_filter( 'query', [ $this, '_insert_ignore' ] );

		return $result;
	}

	/**
	 * Replace the INSERT statement with an INSERT IGNORE
	 *
	 * @param $query
	 *
	 * @return array|string|string[]
	 */
	public function _insert_ignore( $query ) {
		return str_replace( 'INSERT', 'INSERT IGNORE', $query );
	}

	public function get_columns() {
		return [
			'ID'              => '%d',
			'user_agent'      => '%s',
			'user_agent_hash' => '%s',
		];
	}

	public function get_column_defaults() {
		return [
			'ID'              => 0,
			'user_agent'      => '',
			'user_agent_hash' => '',
		];
	}

	public function create_table_sql_command() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		return "CREATE TABLE " . $this->table_name . " (
        ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_agent TEXT NOT NULL,
        user_agent_hash BINARY(32) NOT NULL,
        PRIMARY KEY (ID),
        UNIQUE KEY (user_agent_hash)
		) $charset_collate;";
	}
}
