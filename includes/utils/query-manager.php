<?php

namespace Groundhogg\Utils;

class Query_Manager {

	public static $queries = [];

	/**
	 * Add a query to the list
	 *
	 * @param $sql
	 */
	public static function add_query( $sql ) {
		self::$queries[] = $sql;
	}

	/**
	 * Commit all the queries provided
	 */
	public static function commit() {

		global $wpdb;
		$results = [];

		if ( ! function_exists( 'mysqli_multi_query' ) ){
			foreach ( self::$queries as $query ) {
				$results[] = $wpdb->query( $query );
			}

			self::$queries = [];

			return true;
		}

		$sql = "";

		foreach ( self::$queries as $query ){

			// Make sure that a semicolon is present in each query
			if ( substr( $query, strlen( $query ) - 1, 1 ) !== ';' ){
				$query .= ';';
			}

			$sql .= $query;

		}

		$result = false;

		// Perform all the querys
		if ( function_exists( 'mysqli_multi_query' ) ){
			$result = mysqli_multi_query( $wpdb->dbh, $sql );
		}

		// Empty the query list
		self::$queries = [];

		return $result;
	}

}
