<?php

namespace Groundhogg\Query;

use function Groundhogg\get_array_var;
use function Groundhogg\get_db;
use function Groundhogg\maybe_implode_in_quotes;

abstract class Query {

	protected $query_vars = [];
	protected $joins = [];
	protected $where_clauses = [];
	protected $primary_key;
	protected $filters_registry;

	public function left_join() {

	}

	/**
	 * Get the name of the primary table for the current query
	 *
	 * @return string
	 */
	abstract public function get_table_name();

	/**
	 * Get the table
	 *
	 * @return \Groundhogg\DB\DB|\Groundhogg\DB\Meta_DB
	 */
	public function get_table() {
		return get_db( $this->get_table_name() );
	}

	public function __get( $var ) {
		return get_array_var( $this->query_vars, $var );
	}

	public function __construct( $query = [] ) {

		$this->primary_key = $this->get_table()->get_primary_key();

		$this->query_vars = wp_parse_args( $query, [
			'operation'       => 'SELECT',
			'select'          => '*',
			'limit'           => 20,
			'offset'          => 0,
			'orderby'         => $this->primary_key,
			'order'           => 'desc',
		] );

	}

	public function build_query() {

		$SQL = [];

		switch ( $this->operation ) {
			case 'SELECT':
				$SQL[] = "SELECT pm.{$this->select} FROM {$this->get_table_name()} as pm";
				break;
			case 'UPDATE':
				$SQL[] = "UPDATE {$this->get_table_name()} as pm";
				break;
			case 'DELETE':
				$SQL[] = "DELETE FROM FROM {$this->get_table_name()} as pm";
				break;
		}


		if ( $this->limit ) {
			$SQL[] = "LIMIT $this->limit";
		}

		if ( $this->offset ) {
			$SQL[] = "OFFSET $this->offset";
		}

		return implode( PHP_EOL, $SQL );
	}

	public function add_where( $clause ){
		$this->where_clauses[] = $clause;
	}

	public function build_where() {

		foreach ( $this->query_vars as $key => $value ){

			switch ( $key ) {

				case 'include':
					$ids = maybe_implode_in_quotes( wp_parse_id_list( $value ) );
					$this->add_where( "pm.$this->primary_key IN ( $ids )" );
					break;
				case 'exclude':
					$ids = maybe_implode_in_quotes( wp_parse_id_list( $value ) );
					$this->add_where( "pm.$this->primary_key NOT IN ( $ids )" );
					break;
				case 'filters':
				case 'exclude_filters':



					break;

			}

		}

	}

	public function query() {

	}

}
