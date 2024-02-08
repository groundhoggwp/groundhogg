<?php

namespace Groundhogg\DB\Query;

use Groundhogg\DB\DB;
use function Groundhogg\get_db;

class Join {
	// Left or Right
	public string $table = '';
	public string $alias = '';
	public string $direction = '';
	public Query $query;
	public Where $conditions;

	/**
	 * Create a new JOIN
	 *
	 * @throws \Exception
	 *
	 * @param array|string|DB $maybe_table
	 * @param Query           $query
	 * @param string          $direction
	 */
	public function __construct( string $direction, $maybe_table, Query $query ) {

		if ( is_string( $maybe_table ) && get_db( $maybe_table ) ) {
			$table = get_db( $maybe_table )->table_name;
			$alias = get_db( $maybe_table )->alias;
		} else if ( is_a( $maybe_table, DB::class ) ) {
			$table = $maybe_table->table_name;
			$alias = $maybe_table->alias;
		} else if ( is_array( $maybe_table ) && count( $maybe_table ) === 2 ) {
			[ 0 => $table, 1 => $alias ] = $maybe_table;
			if ( get_db( $table ) ) {
				$table = get_db( $table )->table_name;
			}
		} else if ( is_string( $maybe_table ) ) {
			$table = $maybe_table;
			$alias = str_replace( $query->db->prefix, '', $table );
		} else if ( is_a( $maybe_table, Query::class ) ) {
			$table = $maybe_table;
			$alias = uniqid( 'join_' );
		} else {
			throw new \Exception( 'Invalid table specified for join clause' );
		}

		$this->direction  = $direction;
		$this->table      = trim( "$table" );
		$this->alias      = $alias;
		$this->query      = $query;
		$this->conditions = new Where( $this->query, 'AND' );
	}

	/**
	 * Adds an a.column = b.column condition
	 *
	 * @param string $a
	 * @param string $b
	 *
	 * @return Where
	 */
	public function onColumn( string $a, string $b = '' ): Where {

		// Assume primary key for simplicity
		if ( empty( $b ) ) {
			$b = "{$this->query->alias}.{$this->query->db_table->primary_key}";
		}

		if ( ! Query::isAliased( $b ) ) {
			$b = "{$this->query->alias}.$b";
		}

		$this->conditions->addCondition( "$this->alias.$a = $b" );

		return $this->conditions;
	}

	public function __toString(): string {

		if ( str_starts_with( $this->table, 'SELECT' ) ) {
			return "$this->direction JOIN ( $this->table ) $this->alias ON $this->conditions";
		}

		return "$this->direction JOIN $this->table $this->alias ON $this->conditions";
	}

	public function __serialize(): array {
		return [
			'table_name' => $this->table,
			'alias'      => $this->alias,
			'direction'  => $this->direction,
			'where'      => $this->conditions
		];
	}
}
