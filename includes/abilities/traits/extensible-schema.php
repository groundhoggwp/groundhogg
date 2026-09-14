<?php

namespace Groundhogg\Abilities\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lets a Schema class expose bounded-but-optional properties an add-on can register
 * at runtime, computed only when actually requested - the same "only when asked for"
 * treatment Contact_Schema's built-in 'tags'/'meta' sections get - without the add-on
 * needing to hook multiple filters by hand.
 *
 * A class using this trait gets one new public method, extend(), plus three
 * protected helpers it calls from its own get_schema() / expand_options() /
 * transform() to fold registered extensions in at the right point. It must
 * implement two small abstract methods so extend() and those helpers know this
 * schema's hook prefix and which property names are already taken:
 *
 *     class Contact_Schema extends Schema {
 *         use Extensible_Schema;
 *
 *         protected static function extension_hook_prefix(): string {
 *             return 'contact_schema';
 *         }
 *
 *         protected static function builtin_property_keys(): array {
 *             return [ 'id', 'email', 'first_name', 'last_name', 'date_created', 'optin_status', 'tags', 'meta' ];
 *         }
 *
 *         public static function get_schema(): array {
 *             $properties = [ ... ];
 *             return [ 'type' => 'object', 'properties' => self::extend_properties( $properties ) ];
 *         }
 *
 *         public static function expand_options(): array {
 *             return self::extend_expand_options( [ 'tags', 'meta' ] );
 *         }
 *
 *         public static function transform( $object, array $include = [] ): array {
 *             $data = [ ... ];
 *             return self::extend_transform( $data, $object, $include );
 *         }
 *     }
 *
 * An add-on then extends it with one call instead of hooking three filters:
 *
 *     Contact_Schema::extend(
 *         'ltv',
 *         __( 'Lifetime value: total spend across all WooCommerce orders.', 'my-plugin' ),
 *         function ( Contact $contact ) {
 *             return (float) wc_get_customer_total_spent( $contact->get_user_id() );
 *         },
 *         'number'
 *     );
 *
 * Call extend() once (e.g. on `init`, after confirming both Groundhogg and
 * whatever the callback depends on are active) - before any ability builds its
 * input/output schema.
 *
 * For anything extend() can't express (a nested object, an enum, multiple
 * related properties from one computation), the three filters it's built on are
 * also available directly - `groundhogg/{prefix}/properties`,
 * `groundhogg/{prefix}/transform`, and `groundhogg/{prefix}/expand_options`,
 * where {prefix} is extension_hook_prefix()'s value for this class.
 */
trait Extensible_Schema {

	/**
	 * Add-on-registered properties, keyed by property/expand name. A static
	 * property declared in a trait is a distinct copy per class that uses the
	 * trait, so Contact_Schema's registrations never leak into another schema
	 * that also uses this trait.
	 *
	 * @var array<string, array{schema: array, callback: callable}>
	 */
	private static array $extensions = [];

	/**
	 * The hook prefix for this schema's three extension filters, e.g.
	 * "contact_schema" for groundhogg/contact_schema/properties,
	 * groundhogg/contact_schema/transform, and groundhogg/contact_schema/expand_options.
	 *
	 * @return string
	 */
	abstract protected static function extension_hook_prefix(): string;

	/**
	 * This schema's own property/expand names - extend() refuses to register
	 * any of these, so a misnamed add-on property can't silently clobber a core
	 * field.
	 *
	 * @return string[]
	 */
	abstract protected static function builtin_property_keys(): array;

	/**
	 * Register an additional bounded-but-optional property on this schema - see
	 * the trait docblock for the full picture. Wires up the output schema
	 * property, the `expand` option, and the value callback in one call.
	 *
	 * @param string   $key         The property/expand name, e.g. "ltv". Must not
	 *                              collide with a value from builtin_property_keys()
	 *                              or an existing extend()'d one - either is
	 *                              refused with _doing_it_wrong() rather than
	 *                              silently overwriting something else already
	 *                              relying on that key.
	 * @param string   $description Human-readable description of the property.
	 *                              "Only present when "$key" is passed in expand."
	 *                              is prefixed automatically.
	 * @param callable $callback    Receives the object being transformed and
	 *                              returns the property's value. Only called when
	 *                              $key is actually requested in `expand`/$include.
	 * @param string   $type        The JSON schema `type` for this property
	 *                              (`string`, `integer`, `number`, `boolean`,
	 *                              `object`, or `array`). Default `string`.
	 *
	 * @return void
	 */
	public static function extend( string $key, string $description, callable $callback, string $type = 'string' ) {

		if ( in_array( $key, static::builtin_property_keys(), true ) || isset( self::$extensions[ $key ] ) ) {
			_doing_it_wrong(
				static::class . '::extend',
				sprintf( 'A %s property named "%s" already exists.', static::class, $key ),
				'4.8'
			);

			return;
		}

		self::$extensions[ $key ] = [
			'schema'   => [
				'type'        => $type,
				// Translators: %s is the expand/property key (e.g. "ltv").
				'description' => sprintf( __( 'Only present when "%s" is passed in expand. ', 'groundhogg' ), $key ) . $description,
			],
			'callback' => $callback,
		];
	}

	/**
	 * Fold extend()'d properties into an output schema's `properties`, then run
	 * the `groundhogg/{prefix}/properties` filter. Call from get_schema().
	 *
	 * @param array $properties
	 *
	 * @return array
	 */
	protected static function extend_properties( array $properties ): array {

		foreach ( self::$extensions as $key => $extension ) {
			$properties[ $key ] = $extension['schema'];
		}

		return apply_filters( 'groundhogg/' . static::extension_hook_prefix() . '/properties', $properties );
	}

	/**
	 * Add extend()'d keys to the base list of valid `expand` values, then run
	 * the `groundhogg/{prefix}/expand_options` filter. Call from wherever this
	 * schema publishes its own expand_options()-equivalent.
	 *
	 * @param string[] $options The schema's own built-in expand values.
	 *
	 * @return string[]
	 */
	protected static function extend_expand_options( array $options ): array {
		return apply_filters(
			'groundhogg/' . static::extension_hook_prefix() . '/expand_options',
			array_merge( $options, array_keys( self::$extensions ) )
		);
	}

	/**
	 * Compute and merge in any requested extend()'d properties, then run the
	 * `groundhogg/{prefix}/transform` filter. Call as the last step of
	 * transform(), right before returning.
	 *
	 * @param array $data    The data built so far.
	 * @param mixed $object  The object being transformed (passed through to
	 *                       extend()'d callbacks and the filter).
	 * @param array $include The $include/expand list transform() was called with.
	 *
	 * @return array
	 */
	protected static function extend_transform( array $data, $object, array $include ): array {

		foreach ( self::$extensions as $key => $extension ) {
			if ( in_array( $key, $include, true ) ) {
				$data[ $key ] = call_user_func( $extension['callback'], $object );
			}
		}

		return apply_filters( 'groundhogg/' . static::extension_hook_prefix() . '/transform', $data, $object, $include );
	}
}
