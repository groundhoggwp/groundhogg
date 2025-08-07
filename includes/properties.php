<?php

namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Properties {

	/**
	 * @var Properties
	 */
	public static $instance = null;

	/**
	 * @var array
	 */
	protected $fields;
	protected $tabs;
	protected $groups;
	protected $all;
	protected $option;

	public function __construct( $option = 'gh_contact_custom_properties' ) {

		$this->option = $option;
		$all_data     = get_option( $option );
		$this->all    = wp_parse_args( $all_data ?: [], [
			'fields' => [],
			'tabs'   => [],
			'groups' => [],
		] );
		$this->fields = $this->all['fields'];
		$this->tabs   = $this->all['tabs'];
		$this->groups = $this->all['groups'];
	}

	/**
	 * Sanitize an object properties setup
	 *
	 * @param $all
	 *
	 * @return array
	 */
	public static function sanitize( $all ) {

		$all = wp_parse_args( $all, [
			'fields' => [],
			'groups' => [],
			'tabs'   => [],
		] );

		return [
			'fields' => self::sanitize_properties( $all['fields'] ),
			'groups' => self::sanitize_groups( $all['groups'] ),
			'tabs'   => self::sanitize_tabs( $all['tabs'] ),
		];
	}

	protected static function sanitize_stuff( $stuff, $callbacks ) {

		return array_map( function ( $thing ) use ( $callbacks ) {

			$thing = array_intersect_key( $thing, $callbacks );
			$thing = array_apply_callbacks( $thing, $callbacks );

			return $thing;
		}, $stuff );
	}

	public static function sanitize_tabs( $tabs ) {

		$callbacks = [
			'id'   => 'sanitize_key',
			'name' => 'sanitize_text_field',
		];

		return self::sanitize_stuff( $tabs, $callbacks );
	}

	public static function sanitize_groups( $groups ) {
		$callbacks = [
			'id'   => 'sanitize_key',
			'tab'  => 'sanitize_key',
			'name' => 'sanitize_text_field',
		];

		return self::sanitize_stuff( $groups, $callbacks );
	}

	public static function sanitize_properties( $properties ) {
		$callbacks = [
			'id'      => 'sanitize_key',
			'group'   => 'sanitize_key',
			'label'   => 'sanitize_text_field',
			'name'    => 'sanitize_key',
			'type'    => 'sanitize_key',
			'order'   => 'absint',
			'width'   => 'absint',
			'multiple' => 'boolval',
			'options' => function ( $array ) {
				return array_map( 'sanitize_text_field', $array );
			},
		];

		return self::sanitize_stuff( $properties, $callbacks );

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
	 * @return Properties
	 */
	public static function instance() {

		$class = get_called_class();

		if ( is_null( $class::$instance ) ) {

			$class::$instance = new $class();
		}

		return $class::$instance;
	}

	public function get_option() {
		return self::$option_name;
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
}
