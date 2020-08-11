<?php

namespace Groundhogg;

class Contact_Query_Filters {

	protected static $filters = [];

	/**
	 * Add a filter for the contact query
	 * The filter require a function to parse when given details
	 * The SQL callback will provide an SQL statement given the return value of the parse callback
	 *
	 * @param $type           string
	 * @param $parse_callback callable
	 * @param $sql_callback   callable
	 */
	public function add_filter( $type, $parse_callback, $sql_callback ) {

		// We will not prevent filters from overriding each other...
		if ( is_string( $type ) && is_callable( $parse_callback ) && is_callable( $sql_callback ) ) {
			self::$filters[ $type ] = [
				'type'           => $type,
				'parse_callback' => $parse_callback,
				'sql_callback'   => $sql_callback
			];
		}
	}

	/**
	 * List of standard string conditions
	 *
	 * @return array
	 */
	public function standard_string_comparisons() {
		return [
			'eq',
			'neq',
			'sw',
			'ew',
			'in',
			'nin',
			'empty',
			'filled',
		];
	}

	/**
	 * Parse standard string filters
	 * Use for:
	 *  - First
	 *  - Last
	 *  - Email
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function parse_standard_string_args( $args = [] ) {
		global $wpdb;

		$args['comparison'] = in_array( $args['comparison'], self::standard_string_comparisons() ) ? $args['comparison'] : 'eq';
		$args['value']      = $wpdb->esc_like( sanitize_text_field( $args['value'] ) );

		return $args;
	}

	/**
	 * Standard function which will return an sql statement to filter contacts
	 * based on a
	 *
	 * @param array  $args
	 * @param string $field
	 */
	public function standard_string_filter( $args = [], $field = '' ) {
		extract( $args );

		switch ( $field ){

		}
	}

}
