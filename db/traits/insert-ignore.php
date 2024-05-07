<?php

namespace Groundhogg\DB\Traits;

trait Insert_Ignore {

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
	 * use insert ignore
	 *
	 * @return mixed
	 */
	public function commit_batch_insert() {
		add_filter( 'query', [ $this, '_insert_ignore' ] );
		$result = parent::commit_batch_insert();
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
}
