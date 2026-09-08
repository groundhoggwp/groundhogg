<?php

namespace Groundhogg;

use Groundhogg\DB\Meta_DB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom Object
 *
 * The runtime object for a row in the shared gh_objects table. Concrete so it can
 * be used directly for types that don't ship their own class:
 *
 *     $object = new Custom_Object( 1234, null, 'project' );
 *
 * Or extended, in which case the subclass declares its type and instances need no
 * type argument:
 *
 *     class Project extends Custom_Object {
 *         public static function object_type() { return 'project'; }
 *     }
 *
 *     $project = new Project( 1234 );
 *
 * @since       File available since Release 4.8
 * @package     Includes
 */
class Custom_Object extends Base_Object_With_Meta {

	/**
	 * The object type for this instance. Set from the constructor argument, or
	 * falls back to static::object_type() for subclasses.
	 *
	 * @var string
	 */
	protected $custom_object_type = '';

	/**
	 * @param int|string|object|array $identifier_or_args
	 * @param string|null             $field
	 * @param string                  $type the registered object type slug
	 */
	public function __construct( $identifier_or_args = 0, $field = null, string $type = '' ) {

		if ( $type ) {
			$this->custom_object_type = $type;
		}

		parent::__construct( $identifier_or_args, $field );
	}

	/**
	 * Subclasses override this to declare their type.
	 *
	 * @return string
	 */
	public static function object_type() {
		return '';
	}

	/**
	 * The resolved object type for this instance.
	 *
	 * @return string
	 */
	public function get_object_type_slug() {
		return $this->custom_object_type ?: static::object_type();
	}

	/**
	 * @return \Groundhogg\DB\Custom_Objects|\Groundhogg\DB\Custom_Object_Table
	 */
	protected function get_db() {

		$slug = $this->get_object_type_slug();
		$db   = $slug ? get_db( $slug ) : false;

		// Unknown/unset type: fall back to the generic (unscoped) table so we
		// degrade gracefully instead of fatal-ing.
		if ( ! $db instanceof \Groundhogg\DB\Custom_Objects ) {
			return get_db( 'custom_objects' );
		}

		return $db;
	}

	/**
	 * @return Meta_DB
	 */
	protected function get_meta_db() {
		return get_db( 'custom_object_meta' );
	}

	/**
	 * The object type, used for hook names and capability checks.
	 *
	 * @return string
	 */
	protected function get_object_type() {
		return $this->get_object_type_slug();
	}

	protected function post_setup() {
	}

	/**
	 * Basic sanitization for the shared columns. Subclasses can extend this for
	 * their own meta/columns.
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	protected function sanitize_columns( $data = [] ) {

		foreach ( $data as $col => &$val ) {
			switch ( $col ) {
				case 'title':
					$val = sanitize_text_field( $val );
					break;
				case 'status':
					$val = sanitize_key( $val );
					break;
				case 'object_type':
					$val = sanitize_key( $val );
					break;
				case 'author_id':
					$val = absint( $val );
					break;
			}
		}

		return $data;
	}

	/**
	 * Guard against id collisions across types. Because every type shares one
	 * auto-incrementing table an id resolves to exactly one row, but that row
	 * might belong to a different type - reject it if so.
	 *
	 * @param object|array $object
	 *
	 * @return bool
	 */
	protected function setup_object( $object ) {

		$object = (object) $object;
		$type   = $this->get_object_type_slug();

		if ( $type && isset( $object->object_type ) && $object->object_type !== $type ) {
			return false;
		}

		return parent::setup_object( $object );
	}

	/**
	 * Make sure new rows are stamped with the correct type even if the caller
	 * didn't include it in the data.
	 *
	 * @param array $data
	 *
	 * @return bool|int
	 */
	public function create( $data = [] ) {

		if ( is_array( $data ) && empty( $data['object_type'] ) ) {
			$data['object_type'] = $this->get_object_type_slug();
		}

		return parent::create( $data );
	}
}
