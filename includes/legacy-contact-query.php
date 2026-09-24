<?php

namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * The legacy contact query engine has been removed. This stub only exists so outdated code that still
 * references it gets a deprecation notice instead of a fatal error.
 *
 * Queries are passed on to Contact_Query. Filters registered with register_filter() are not applied, and
 * any filter of that type will match nothing. Static helpers that used to return an SQL clause return
 * one that matches nothing, so they can never widen a query.
 *
 * @deprecated 3.2 use Contact_Query instead
 */
class Legacy_Contact_Query {

	public $query_vars = [];
	public $request = '';
	public $items = [];
	public $found_items = 0;
	public $table_name;
	public $primary_key;
	public $date_key;
	public $meta_type;

	/**
	 * @var Contact_Query
	 */
	protected $query;

	public function __construct( $query = [] ) {
		_deprecated_class( __CLASS__, '3.2', Contact_Query::class );

		$contacts = get_db( 'contacts' );

		$this->table_name  = $contacts->get_table_name();
		$this->primary_key = $contacts->get_primary_key();
		$this->date_key    = $contacts->get_date_key();
		$this->meta_type   = $contacts->get_object_type();
		$this->query_vars  = wp_parse_args( $query );
		$this->query       = new Contact_Query( $this->query_vars );
	}

	/**
	 * @param array $query
	 * @param bool  $as_contact_object
	 *
	 * @return Contact[]|object[]
	 */
	public function query( $query = [], $as_contact_object = false ) {
		$this->items       = $this->query->query( $query, $as_contact_object );
		$this->found_items = $this->query->found_items;

		return $this->items;
	}

	/**
	 * @param array $query
	 *
	 * @return int
	 */
	public function count( $query = [] ) {
		return $this->query->count( $query );
	}

	/**
	 * @param array $query
	 *
	 * @return string
	 */
	public function get_sql( $query = [] ) {
		$this->request = $this->query->get_sql( $query );

		return $this->request;
	}

	public function set_query_var( string $var, $value ) {
		$this->query_vars[ $var ] = $value;
		$this->query->set_query_var( $var, $value );
	}

	public function set_date_key( $key ) {
		$this->date_key = $key;
	}

	/**
	 * Filters registered this way are no longer applied.
	 *
	 * @deprecated 3.2 use Contact_Query::filters()->register() instead
	 *
	 * @return bool always false
	 */
	public static function register_filter( string $type, callable $filter_callback ): bool {
		_deprecated_function( __METHOD__, '3.2', 'Contact_Query::filters()->register()' );

		return false;
	}

	/**
	 * Any other property of the old class
	 *
	 * @param $name
	 *
	 * @return null
	 */
	public function __get( $name ) {
		return null;
	}

	public function __call( $name, $arguments ) {
		return self::removed_method( $name );
	}

	public static function __callStatic( $name, $arguments ) {
		return self::removed_method( $name );
	}

	/**
	 * The old filter_*() and *_compare() helpers returned an SQL clause, so return one that matches nothing.
	 * The date range helpers returned a before and after.
	 *
	 * @param string $name
	 *
	 * @return string|array
	 */
	protected static function removed_method( string $name ) {
		_deprecated_function( esc_html( __CLASS__ . '::' . $name ), '3.2', 'Contact_Query' );

		if ( str_contains( $name, 'before_and_after' ) ) {
			return [ 'before' => '', 'after' => '' ];
		}

		return '1=0';
	}
}
