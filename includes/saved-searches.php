<?php

namespace Groundhogg;

use \WP_Error;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Saved_Searches {

	/**
	 * @var Saved_Searches
	 */
	public static $instance = null;

	/**
	 * @var array
	 */
	protected $data;
	protected $option_name;

	public function __construct() {
		$this->option_name = 'gh_saved_searches';
		$this->data = get_option( $this->option_name, [] );
	}

	/**
	 * Instance.
	 *
	 * Ensures only one instance of the plugin class is loaded or can be loaded.
	 *
	 * @return Saved_Searches
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 */
	public static function instance() {

		$class = get_called_class();

		if ( is_null( $class::$instance ) ) {

			$class::$instance = new $class();
		}

		return $class::$instance;
	}

	protected function get_defaults() {
		return [
			'query' => [],
			'id'    => '',
			'name'  => ''
		];
	}

	/**
	 * @return array
	 */
	public function get_all() {
		if ( ! $this->data ) {
			return [];
		}

		return $this->data;
	}

	/**
	 * Get the searches in compatible format for select
	 *
	 * @return array
	 */
	public function get_for_select(){

		$options = [];

		foreach ( $this->data as $datum ){
			$options[ $datum[ 'id' ] ] = $datum[ 'name' ];
		}

		return $options;
	}

	/**
	 * @param $id string
	 *
	 * @return array|mixed
	 */
	public function get( $id ) {
		if ( ! $this->data ) {
			$this->data = [];
		}

		if ( ! key_exists( $id, $this->data ) ) {
			return false;
		}

		return $this->data[ $id ];
	}

	/**
	 * Whether we have the given id
	 *
	 * @param $id
	 *
	 * @return bool
	 */
	public function has( $id ) {
		return (bool) $this->get( $id );
	}

	/**
	 * Add a new tab id
	 *
	 * @param $id
	 * @param $args
	 *
	 * @return bool|WP_Error
	 */
	public function add( $id, $args = [] ) {

		if ( ! $this->data ) {
			$this->data = [];
		}

		if ( key_exists( $id, $this->data ) ) {
			return false;
		}

		$args['id'] = $id;

		$args = wp_parse_args( $args, $this->get_defaults() );

		$this->data[ $id ] = $args;

		return update_option( $this->option_name, $this->data );
	}

	/**
	 * Update an existing tab id
	 *
	 * @param $id string
	 * @param array $args
	 *
	 * @return bool
	 */
	public function update( $id, $args = [] ) {
		if ( ! $this->data ) {
			$this->data = [];
		}

		if ( ! key_exists( $id, $this->data ) ) {
			return self::add( $id, $args );
		}

		$current = $this->data[ $id ];

		$args = wp_parse_args( $args, $current );

		$this->data[ $id ] = $args;

		return update_option( $this->option_name, $this->data );
	}

	/**
	 * Delete an existing tab id
	 *
	 * @param $id
	 *
	 * @return bool
	 */
	public function delete( $id ) {
		unset( $this->data[ $id ] );

		return update_option( $this->option_name, $this->data );
	}

	final public function __clone() {
		trigger_error( "Singleton. No cloning allowed!", E_USER_ERROR );
	}

	final public function __wakeup() {
		trigger_error( "Singleton. No serialization allowed!", E_USER_ERROR );
	}
}
