<?php

namespace Groundhogg;

use Groundhogg\DB\DB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Temp does not require DB connection
 *
 * Class Temp_Step
 *
 * @package Groundhogg
 */
class DB_Object extends Base_Object {

	/**
	 * @var DB
	 */
	protected $table;

	public function __construct( $table, $identifier_or_args = 0, $field = null ) {
		$this->table = $table;
		parent::__construct( $identifier_or_args, $field );
	}

	protected function post_setup() {
		// TODO: Implement post_setup() method.
	}

	protected function get_db() {
		return $this->table;
	}
}