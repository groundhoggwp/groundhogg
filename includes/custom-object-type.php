<?php

namespace Groundhogg;

use Groundhogg\Api\V4\Custom_Objects_Api;
use Groundhogg\DB\Custom_Object_Table;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom Object Type
 *
 * A registry of custom object types, roughly analogous to register_post_type().
 * Each registered type gets:
 *
 *  - a type-scoped DB proxy (Custom_Object_Table) registered with the DB Manager
 *    so get_db(), create_object_from_type() and relationships all resolve it
 *  - a REST controller at gh/v4/objects/{type}, unless 'show_in_rest' is false
 *
 * Types may be registered at any point up to rest_api_init; registration is
 * deferred until the DB Manager is ready.
 *
 * @since   File available since Release 4.8
 * @package Includes
 */
class Custom_Object_Type {

	/**
	 * @var Custom_Object_Type[]
	 */
	protected static $types = [];

	/**
	 * @var string
	 */
	protected $type;

	/**
	 * @var array
	 */
	protected $args;

	/**
	 * @var bool
	 */
	protected $booted = false;

	/**
	 * @var Custom_Objects_Api|null
	 */
	protected $api = null;

	/**
	 * @param string $type
	 * @param array  $args
	 */
	public function __construct( string $type, array $args = [] ) {

		$this->type = $type;

		$words = key_to_words( $type );

		$this->args = wp_parse_args( $args, [
			// Human readable labels
			'singular'     => $words,
			'plural'       => $words . 's',
			// Runtime class, must extend Custom_Object
			'object_class' => Custom_Object::class,
			// Default capability for all REST operations
			'capability'   => 'edit_contacts',
			// Optional per-operation capability overrides:
			// [ 'view' => '', 'create' => '', 'edit' => '', 'delete' => '' ]
			'capabilities' => [],
			// Register the REST controller at gh/v4/objects/{type}. Set false to
			// keep the type PHP-only (DB proxy, query builder, relationships).
			'show_in_rest' => true,
		] );
	}

	/**
	 * Register a custom object type.
	 *
	 * @param string $type kebab/snake case slug, e.g. "project"
	 * @param array  $args
	 *
	 * @return Custom_Object_Type|null
	 */
	public static function register( string $type, array $args = [] ) {

		$type = sanitize_key( $type );

		if ( empty( $type ) ) {
			return null;
		}

		// Already registered, return the existing definition
		if ( isset( self::$types[ $type ] ) ) {
			return self::$types[ $type ];
		}

		// Don't clobber a first-party table or the generic type
		if ( self::is_reserved( $type ) ) {
			return null;
		}

		$object_type = new self( $type, $args );

		self::$types[ $type ] = $object_type;

		// Boot now if the DBs are ready, otherwise wait for them
		$object_type->maybe_bootstrap();
		add_action( 'groundhogg/db/manager/init', [ $object_type, 'maybe_bootstrap' ] );

		return $object_type;
	}

	/**
	 * @param string $type
	 *
	 * @return Custom_Object_Type|null
	 */
	public static function get( string $type ) {
		return self::$types[ $type ] ?? null;
	}

	/**
	 * @return Custom_Object_Type[]
	 */
	public static function all() {
		return self::$types;
	}

	/**
	 * @param string $type
	 *
	 * @return bool
	 */
	public static function exists( string $type ) {
		return isset( self::$types[ $type ] );
	}

	/**
	 * A slug is reserved if a DB is already registered under it, or it's the
	 * generic custom object type.
	 *
	 * @param string $type
	 *
	 * @return bool
	 */
	protected static function is_reserved( string $type ) {

		if ( in_array( $type, [ 'custom_object', 'custom_objects', 'custom_object_meta' ], true ) ) {
			return true;
		}

		return Plugin::$instance->dbs && (bool) Plugin::$instance->dbs->get_db( $type );
	}

	/**
	 * Register the DB proxy, and the REST controller unless 'show_in_rest' is
	 * false, once the DB Manager is ready. Safe to call repeatedly.
	 */
	public function maybe_bootstrap() {

		if ( $this->booted ) {
			return;
		}

		$dbs = Plugin::$instance->dbs ?? null;

		if ( ! $dbs || ! $dbs->is_initialized() ) {
			return;
		}

		$existing = $dbs->get_db( $this->type );

		if ( ! $existing ) {
			$dbs->{$this->type} = new Custom_Object_Table( $this->type );
		} else if ( ! $existing instanceof Custom_Object_Table ) {
			// The slug collides with a first-party table. Refuse to boot so we
			// never shadow or overwrite it.
			unset( self::$types[ $this->type ] );

			return;
		}

		$this->booted = true;

		if ( $this->rest_enabled() ) {
			$this->api = new Custom_Objects_Api( $this );
		}
	}

	/**
	 * Whether this type exposes a REST controller at gh/v4/objects/{type}.
	 *
	 * @return bool
	 */
	public function rest_enabled() {
		return (bool) $this->args['show_in_rest'];
	}

	/**
	 * @return string
	 */
	public function get_slug() {
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function get_singular() {
		return $this->args['singular'];
	}

	/**
	 * @return string
	 */
	public function get_plural() {
		return $this->args['plural'];
	}

	/**
	 * @return string the class name, guaranteed to extend Custom_Object
	 */
	public function get_object_class() {

		$class = $this->args['object_class'];

		if ( is_string( $class ) && class_exists( $class )
		     && ( $class === Custom_Object::class || is_subclass_of( $class, Custom_Object::class ) ) ) {
			return $class;
		}

		return Custom_Object::class;
	}

	/**
	 * Capability for a given REST operation.
	 *
	 * @param string $context one of view|create|edit|delete
	 *
	 * @return string
	 */
	public function get_capability( $context = 'view' ) {

		if ( ! empty( $this->args['capabilities'][ $context ] ) ) {
			return $this->args['capabilities'][ $context ];
		}

		// A sensible view default that most CRM users have
		if ( $context === 'view' && empty( $this->args['capabilities'] ) && $this->args['capability'] === 'edit_contacts' ) {
			return 'view_contacts';
		}

		return $this->args['capability'];
	}

	/**
	 * @return array raw args
	 */
	public function get_args() {
		return $this->args;
	}
}

/**
 * Register a custom object type.
 *
 * @param string $type
 * @param array  $args
 *
 * @return Custom_Object_Type|null
 */
function register_custom_object( string $type, array $args = [] ) {
	return Custom_Object_Type::register( $type, $args );
}

/**
 * Fetch a single custom object.
 *
 * @param int|string $id
 * @param string     $type
 *
 * @return Custom_Object|DB_Object|DB_Object_With_Meta|null
 */
function get_custom_object( $id, string $type ) {
	return create_object_from_type( absint( $id ), $type );
}

/**
 * @return Custom_Object_Type[]
 */
function get_custom_object_types() {
	return Custom_Object_Type::all();
}
