<?php

namespace Groundhogg;

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
		$this->fields = $this->all['fields'] ?: [];
		$this->tabs   = $this->all['tabs'] ?: [];
		$this->groups = $this->all['groups'] ?: [];
	}

	/**
	 * Instance.
	 *
	 * Ensures only one instance of the plugin class is loaded or can be loaded.
	 *
	 * @since  1.0.0
	 * @access public
	 * @static
	 *
	 * @return Contact_Properties
	 */
	public static function instance() {

		$class = get_called_class();

		if ( is_null( $class::$instance ) ) {

			$class::$instance = new $class();
		}

		return $class::$instance;
	}

	public function get_all() {
		return $this->all;
	}

	/**
	 * @return array
	 */
	public function get_fields( $group_id = false ) {
		if ( ! $this->fields ) {
			return [];
		}

		if ( ! $group_id ) {
			return $this->fields;
		}

		return array_filter( $this->fields, function ( $f ) use ( $group_id ) {
			return $f['group'] == $group_id;
		} );

	}

	/**
	 * @return array
	 */
	public function get_tabs() {
		if ( ! $this->tabs ) {
			return [];
		}

		return $this->tabs;
	}

	/**
	 * @param $group_id
	 *
	 * @return array|false|mixed
	 */
	public function get_group_tab( $group_id ) {

		$group = $this->get_group( $group_id );

		if ( ! $group ) {
			return false;
		}

		return $this->get_tab( $group['tab'] );
	}

	/**
	 * @return array
	 */
	public function get_groups( $tab_id = false ) {
		if ( ! $this->groups ) {
			return [];
		}

		if ( ! $tab_id ) {
			return $this->groups;
		}

		return array_filter( $this->groups, function ( $g ) use ( $tab_id ) {
			return $g['tab'] == $tab_id;
		} );

	}

	/**
	 * @param $id_or_name string
	 *
	 * @return array|mixed
	 */
	public function get_field( $id_or_name ) {
		if ( ! $this->fields ) {
			$this->fields = [];
		}

		$field = array_find( $this->fields, function ( $f ) use ( $id_or_name ) {
			return $f['id'] === $id_or_name || $f['name'] === $id_or_name;
		} );

		if ( ! $field ) {
			return false;
		}

		return $field;
	}

	/**
	 * @param $id string
	 *
	 * @return array|mixed
	 */
	public function get_group( $id ) {
		if ( ! $this->groups ) {
			$this->groups = [];
		}

		$group = array_find( $this->groups, function ( $g ) use ( $id ) {
			return $g['id'] === $id;
		} );

		if ( ! $group ) {
			return false;
		}

		return $group;
	}

	/**
	 * @param $id string
	 *
	 * @return array|mixed
	 */
	public function get_tab( $id ) {
		if ( ! $this->tabs ) {
			$this->tabs = [];
		}

		$tab = array_find( $this->tabs, function ( $t ) use ( $id ) {
			return $t['id'] === $id;
		} );

		if ( ! $tab ) {
			return false;
		}

		return $tab;
	}


	final public function __clone() {
		trigger_error( "Singleton. No cloning allowed!", E_USER_ERROR );
	}

	final public function __wakeup() {
		trigger_error( "Singleton. No serialization allowed!", E_USER_ERROR );
	}

}