<?php

namespace Groundhogg\DB\Traits;

use function Groundhogg\isset_not_empty;

trait IP_Address {

	public function convert_ip_address_to_varbinary() {
		global $wpdb;

		$queries = [
			// Create the varbinary column
			"ALTER TABLE {$this->table_name} ADD COLUMN ip_binary VARBINARY(16);",
			// Copy the data from ip_address to ip_binary
			"UPDATE {$this->table_name} SET ip_binary = INET6_ATON(ip_address);",
			// Drop the OG ip_address column
			"ALTER TABLE {$this->table_name} DROP COLUMN ip_address;",
			// Rename the column to ip_address
			"ALTER TABLE {$this->table_name} RENAME COLUMN ip_binary TO ip_address;",
		];

		mysqli_multi_query( $wpdb->dbh, implode( ' ', $queries ) );
	}

	/**
	 * If IP address is in the array, pack it.
	 *
	 * @param array $data
	 *
	 * @return void
	 */
	protected function packIP( array &$data ) {
		// Convert IP Address to binary
		if ( isset_not_empty( $data, 'ip_address' ) ) {
			$data['ip_address'] = inet_pton( $data['ip_address'] );
		}
	}

	/**
	 * If IP address is in the array, unpack it.
	 *
	 * @param array $data
	 *
	 * @return void
	 */
	protected function unpackIP( array &$data ) {
		// Convert IP Address to binary
		if ( isset_not_empty( $data, 'ip_address' ) ) {
			$data['ip_address'] = inet_ntop( $data['ip_address'] );
		}
	}

	/**
	 * Convert IP address to binary format
	 *
	 * @param $data
	 *
	 * @return int
	 */
	public function insert( $data ) {

		// Convert IP Address to binary
		$this->packIP( $data );

		return parent::insert( $data );
	}

	public function update( $id_or_where = 0, $data = [], $where = [] ) {
		if ( is_array( $id_or_where ) ){
			$this->packIP( $id_or_where );
		}

		if ( is_array( $where ) && ! empty( $where ) ){
			$this->packIP( $where );
		}

		$this->packIP( $data );

		return $this->update( $id_or_where, $data, $where );
	}

	/**
	 * When performing queries, query IP address in binary format
	 *
	 * @throws \Groundhogg\DB\Query\FilterException
	 *
	 * @param $ORDER_BY
	 * @param $from_cache
	 * @param $query_vars
	 *
	 * @return array|bool|object|null
	 */
	public function query( $query_vars = [], $ORDER_BY = '', $from_cache = true ) {

		// Convert IP Address to binary
		$this->packIP( $query_vars );

		return parent::query( $query_vars, $ORDER_BY, $from_cache );
	}

}
