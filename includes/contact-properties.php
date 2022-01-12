<?php

namespace Groundhogg;

use function Groundhogg\array_find;

class Contact_Properties {

	protected $option_name = 'gh_contact_custom_properties';

	/**
	 * @var Contact_Properties
	 */
	public static $instance = null;

	/**
	 * @var array
	 */
	protected $fields;
	protected $tabs;
	protected $groups;
	protected $all;

	public function __construct() {
		$all_data     = get_option( $this->option_name );
		$this->all    = $all_data ?: [
			'fields' => [],
			'tabs'   => [],
			'groups' => [],
		];
		$this->fields = $all_data['fields'] ?: [];
		$this->tabs   = $all_data['tabs'] ?: [];
		$this->groups = $all_data['groups'] ?: [];
	}

	/**
	 * Instance.
	 *
	 * Ensures only one instance of the plugin class is loaded or can be loaded.
	 *
	 * @return Contact_Properties
	 * @since  1.0.0
	 * @access public
	 * @static
	 *
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {

			self::$instance = new Contact_Properties();
		}

		return self::$instance;
	}

	public function get_all() {
		return $this->all;
	}

	/**
	 * @return array
	 */
	public function get_fields() {
		if ( ! $this->fields ) {
			return [];
		}

		return $this->fields;
	}

	/**
	 * @param $id string
	 *
	 * @return array|mixed
	 */
	public function get_field( $id ) {
		if ( ! $this->fields ) {
			$this->fields = [];
		}

		$field = array_find( $this->fields, function ( $f ) use ( $id ) {
			return $f['id'] === $id;
		} );

		if ( ! $field ) {
			return false;
		}

		return $field;
	}


	final public function __clone() {
		trigger_error( "Singleton. No cloning allowed!", E_USER_ERROR );
	}

	final public function __wakeup() {
		trigger_error( "Singleton. No serialization allowed!", E_USER_ERROR );
	}

}