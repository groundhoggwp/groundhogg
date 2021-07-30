<?php

namespace Groundhogg;

use Groundhogg\DB\DB;
use Groundhogg\DB\Meta_DB;

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
class DB_Object_With_Meta extends Base_Object_With_Meta {

	/**
	 * @var DB
	 */
	protected $table;

	/**
	 * @var Meta_DB
	 */
	protected $meta_table;

	public function __construct( $table, $meta_table, $identifier_or_args = 0, $field = null ) {
		$this->table      = $table;
		$this->meta_table = $meta_table;
		parent::__construct( $identifier_or_args, $field );
	}

	protected function post_setup() {
		// TODO: Implement post_setup() method.
	}

	protected function get_db() {
		return $this->table;
	}

	protected function get_meta_db() {
		return $this->meta_table;
	}
}