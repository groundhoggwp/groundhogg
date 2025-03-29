<?php

namespace Groundhogg\DB\Query;

use function Groundhogg\ensure_array;
use function Groundhogg\get_array_var;
use function Groundhogg\maybe_implode_in_quotes;

class Where {

	/**
	 * List of all the clauses in this where statement, can be string or Where
	 *
	 * @var string[]|Where[]
	 */
	protected array $conditions = [];

	/**
	 * How the clauses are evaluated ion relation to each other
	 *
	 * @var string
	 */
	protected string $relation = 'AND';

	/**
	 * The query this Where is attached to
	 *
	 * @var Query
	 */
	protected Query $query;

	/**
	 * If this Where has no clauses
	 *
	 * @return bool
	 */
	public function isEmpty() {
		return empty( $this->conditions );
	}

	public function esc_like( $stuff ) {
		return $this->query->db->esc_like( $stuff );
	}

	/**
	 * *** MAGIC ***
	 *
	 * @param $name
	 *
	 * @return mixed
	 */
	public function __get( $name ) {
		return $this->$name;
	}

	/**
	 * Constructor
	 *
	 * @param $query Query
	 * @param $relation
	 */
	public function __construct( $query, $relation = 'AND' ) {
		$this->relation       = $relation;
		$this->query          = $query;
	}

	public function __serialize(): array {
		return [
			'relation'   => $this->relation,
			'conditions' => $this->conditions
		];
	}

	/**
	 * Maybe symbolized the comparison
	 *
	 * @param $str
	 *
	 * @return bool|mixed
	 */
	public function symbolize_comparison( $str ) {

		$symbols = [
			'equals'                   => '=',
			'not_equals'               => '!=',
			'less_than'                => '<',
			'greater_than'             => '>',
			'more_than'                => '>',
			'less_than_or_equal_to'    => '<=',
			'greater_than_or_equal_to' => '>=',
			'in'                       => 'IN',
			'not_in'                   => 'NOT IN',
			'like'                     => 'LIKE',
			'not_like'                 => 'NOT LIKE',
			'rlike'                    => 'RLIKE',
		];

		if ( in_array( $str, $symbols ) ) {
			return $str;
		}

		return get_array_var( $symbols, $str );
	}

	/**
	 * List of the allowed comparisons
	 *
	 * @return string[]
	 */
	public function get_allowed_comparisons() {
		return [
			'=',
			'!=',
			'>',
			'>=',
			'<',
			'<=',
			'<>',
			'LIKE',
			'RLIKE',
			'IN',
			'NOT IN',
		];
	}

	/**
	 * If this where contains a specific condition
	 * Basically just a phrase match for now...
	 *
	 * @param $condition
	 *
	 * @return bool
	 */
	public function hasCondition( $condition ) {
		return str_contains( "$this", $condition );
	}

	/**
	 * Whether to prefix the next condition with NOT
	 *
	 * @var bool
	 */
	protected bool $prefix_condition_with_not = false;

	/**
	 * Adds a clause to the list of clauses
	 *
	 * @param $condition
	 *
	 * @return $this
	 */
	public function addCondition( $condition ) {

		if ( empty( $condition ) ) {
			return $this;
		}

		if ( $this->prefix_condition_with_not && is_string( $condition ) ){
			$condition = "NOT $condition";
			$this->prefix_condition_with_not = false;
		}

		$this->conditions[] = $condition;

		return $this;
	}

	/**
	 * Sanitize a column
	 *
	 * @param $column
	 *
	 * @return string
	 */
	protected function sanitize_column( $column ) {
		// It'll check anyway *shrug*
		return $this->query->maybe_sanitize_aggregate_column( $column );
	}

	protected array $columnFormats = [];

	/**
	 * Sets a format for a specific column
	 *
	 * @param string $column
	 * @param string $format
	 *
	 * @return void
	 */
	public function setColumnFormat( string $column, string $format ) {
		$this->columnFormats[ $column ] = $format;
	}

	/**
	 * Get the merge format for a specific column for $wpdb->prepare()
	 *
	 * @param $column
	 * @param $value
	 *
	 * @return bool|mixed
	 */
	public function getColumnFormat( $column, $value = false ) {

		if ( key_exists( $column, $this->columnFormats ) ) {
			return $this->columnFormats[ $column ];
		}

		// We have access to column format
		if ( is_a( $this->query, Table_Query::class ) ) {
			$column_formats = $this->query->db_table->get_columns();

			if ( Query::isAliased( $column ) ) {
				$column = substr( $column, strpos( $column, '.' ) + 1 );
			}

			return get_array_var( $column_formats, $column, is_numeric( $value ) ? '%d' : '%s' );
		}

		if ( is_numeric( $value ) ) {
			return str_contains( $value, '.' ) ? '%f' : '%d';
		}

		return '%s';
	}

	/**
	 * Converts this clause and all sub clauses to a string
	 *
	 * @return string
	 */
	public function __toString() {

		if ( $this->isEmpty() ) {
			return '1=1';
		}

		$conditions = array_filter( array_map( function ( $condition ) {
			return "$condition";
		}, $this->conditions ) );

		$numConditions = count( $conditions );

		$conditions = implode( " $this->relation ", $conditions );

		if ( $numConditions > 1 ) {
			$conditions = "( $conditions )";
		}

		return $conditions;
	}

	/**
	 * Generic comparison wrapper for most statements
	 *
	 * @throws \Exception
	 *
	 * @param $value
	 * @param $compare
	 *
	 * @param $column
	 *
	 * @return $this
	 */
	public function compare( $column, $value, $compare = '=', $format = false ) {

		$column  = $this->sanitize_column( $column );
		$compare = $this->symbolize_comparison( $compare );

		if ( ! in_array( $compare, $this->get_allowed_comparisons() ) ) {
			throw new \Exception( "$compare is not an allowed comparison symbol" );
		}

		switch ( strtoupper( $compare ) ) {
			case 'IN':
				return $this->in( $column, $value );
			case 'NOT IN':
				return $this->notIn( $column, $value );
			case 'LIKE':
				return $this->like( $column, $value );
			case 'NOT LIKE':
				return $this->notLike( $column, $value );
		}

		if ( $format === false ) {
			$format = $this->getColumnFormat( $column, $value );
		}

//		var_dump( $column, $compare, $format, $value );

		$condition = $this->prepare( "$column $compare $format", $value );

//		var_dump( $condition );

		return $this->addCondition( $condition );
	}

	/**
	 * a = b
	 *
	 * @param $column
	 * @param $value
	 *
	 * @return $this
	 */
	public function equals( $column, $value ) {
		return $this->compare( $column, $value );
	}

	/**
	 * a != b
	 *
	 * @param $column
	 * @param $value
	 *
	 * @return $this
	 */
	public function notEquals( $column, $value ) {
		return $this->compare( $column, $value, '!=' );
	}

	/**
	 * a <= c
	 *
	 * @param $column
	 * @param $value
	 *
	 * @return $this
	 */
	public function lessThan( $column, $value ) {
		return $this->compare( $column, $value, '<' );
	}

	/**
	 * a <= b
	 *
	 * @param $column
	 * @param $value
	 *
	 * @return $this
	 */
	public function lessThanEqualTo( $column, $value ) {
		return $this->compare( $column, $value, '<=' );
	}

	/**
	 * a > b
	 *
	 * @param $column
	 * @param $value
	 *
	 * @return $this
	 */
	public function greaterThan( $column, $value ) {
		return $this->compare( $column, $value, '>' );
	}

	/**
	 * a >= b
	 *
	 * @param $column
	 * @param $value
	 *
	 * @return $this
	 */
	public function greaterThanEqualTo( $column, $value ) {
		return $this->compare( $column, $value, '>=' );
	}

	/**
	 * EXISTS (SELECT ...)
	 *
	 * @param Query $query
	 *
	 * @return $this
	 */
	public function exists( Query $query ) {
		return $this->addCondition( "EXISTS ($query)" );
	}

	/**
	 * IN (1,2,3)
	 *
	 * @param $column
	 * @param $values
	 *
	 * @return $this
	 */
	public function in( $column, $values ) {

		$column = $this->sanitize_column( $column );

		if ( ( is_string( $values ) && str_starts_with( $values, 'SELECT' ) ) || is_a( $values, Query::class ) ) {
			$this->addCondition( "$column IN ( $values )" );

			return $this;
		}

		$values = array_values( ensure_array( $values ) );
		$values = map_deep( $values, 'sanitize_text_field' );

		if ( count( $values ) === 1 ) {
			return $this->equals( $column, $values[0] );
		}

		$values = maybe_implode_in_quotes( $values );

		return $this->addCondition( "$column IN ( $values )" );
	}

	/**
	 * NOT IN (1,2,3)
	 *
	 * @param $column
	 * @param $values
	 *
	 * @return $this
	 */
	public function notIn( $column, $values ) {

		$column = $this->sanitize_column( $column );

		if ( is_string( $values ) && str_starts_with( $values, 'SELECT' ) ) {
			$this->addCondition( "$column NOT IN ( $values )" );

			return $this;
		}

		$values = array_values( ensure_array( $values ) );
		$values = map_deep( $values, 'sanitize_text_field' );

		if ( count( $values ) === 1 ) {
			return $this->notEquals( $column, $values[0] );
		}

		$values = maybe_implode_in_quotes( $values );

		return $this->addCondition( "$column NOT IN ( $values )" );
	}

	/**
	 * LIKE %string%
	 *
	 * @param $column
	 * @param $string
	 *
	 * @return $this
	 */
	public function like( $column, $string ) {
		$column = $this->sanitize_column( $column );

		return $this->addCondition( $this->prepare( "$column LIKE %s", $string ) );
	}

	/**
	 * LIKE %string%
	 *
	 * @param $column
	 * @param $string
	 *
	 * @return $this
	 */
	public function wLike( $column, $string ) {
		return $this->contains( $column, $string );
	}

	public function contains( $column, $string  ){
		return $this->like( $column, '%' . $this->esc_like( $string ) . '%' );
	}

	public function startsWith( $column, $string  ){
		return $this->like( $column, $this->esc_like( $string ) . '%' );
	}

	public function endsWith( $column, $string  ){
		return $this->like( $column, '%' . $this->esc_like( $string ) );
	}

	/**
	 * NOT LIKE %string%
	 *
	 * @param $column
	 * @param $string
	 *
	 * @return $this
	 */
	public function notLike( $column, $string ) {
		$column = $this->sanitize_column( $column );

		return $this->addCondition( $this->prepare( "$column NOT LIKE %s", $string ) );
	}

	/**
	 * NOT LIKE %string%
	 *
	 * @param $column
	 * @param $string
	 *
	 * @return $this
	 */
	public function wNotLike( $column, $string ) {
		return $this->notContains( $column, $string );
	}

	public function notContains( $column, $string ) {
		return $this->notLike( $column, '%' . $this->esc_like( $string ) . '%' );
	}

	/**
	 * Compare if value is between two values
	 * value BETWEEN a AND b
	 *
	 * @param $column
	 * @param $a
	 * @param $b
	 *
	 * @return $this
	 */
	public function between( $column, $a, $b ) {

		$format = $this->getColumnFormat( $column, $a );
		$column = $this->sanitize_column( $column );

		return $this->addCondition( $this->prepare( "$column BETWEEN $format AND $format", $a, $b ) );
	}

	public function notEmpty( $column ) {
		return $this->compare( $column, '', '!=' );
	}

	public function isNotNull( $column ) {
		$column = $this->sanitize_column( $column );
		return $this->addCondition( "$column IS NOT NULL" );
	}

	public function isNull( $column ) {
		$column = $this->sanitize_column( $column );
		return $this->addCondition( "$column IS NULL" );
	}

	public function empty( $column ) {
		return $this->compare( $column, '' );
	}

	/**
	 * Adds a sub where clause, in brackets
	 *
	 * @param string $relation 'OR' or 'AND'
	 *
	 * @return Where
	 */
	public function subWhere( string $relation = 'OR' ) {
		$where = new Where( $this->query, $relation );
		$this->addCondition( $where );
		return $where;
	}

	public function subOr(){
		return $this->subWhere( 'OR' );
	}

	public function subAnd(){
		return $this->subWhere( 'AND' );
	}

	/**
	 * Wrapper for $wpdb->prepare
	 *
	 * @param ...$args
	 *
	 * @return string|null
	 */
	public function prepare( ...$args ) {
		return $this->query->db->prepare( ...$args );
	}

	/**
	 * Prefixes the next condition added with NOT
	 *
	 * @return Where
	 */
	public function not() {
		$this->prefix_condition_with_not = true;
		return $this;
	}

	/**
	 * Use the not exists function
	 *
	 * @throws \Exception
	 *
	 * @param $query
	 *
	 * @return $this
	 */
	public function notExists( $query ) {

		if ( ! is_a( $query, Query::class ) || ! is_string( $query ) ) {
			throw new \Exception( 'Must use string or Query' );
		}

		$this->addCondition( "NOT EXISTS ($query)" );
		return $this;
	}
}
