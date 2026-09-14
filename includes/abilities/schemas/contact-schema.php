<?php

namespace Groundhogg\Abilities\Schemas;

use DateTime;
use Exception;
use Groundhogg\Abilities\Traits\Extensible_Schema;
use Groundhogg\Contact;
use Groundhogg\Preferences;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared schema for a Groundhogg Contact, as returned by abilities like
 * groundhogg/get-contact and groundhogg/search-contacts.
 *
 * Standard fields (id, email, name, date created, opt-in status) are cheap, single-row
 * data and are always included. 'tags' and 'meta' are bounded-but-optional — they cost
 * an extra query and (for meta especially) can be arbitrarily shaped, so they're only
 * fetched and included when requested via the $include argument to transform().
 *
 * Meta is returned as a raw key => value map, since custom field names are user-defined
 * per install. Use the groundhogg/list-custom-fields ability to look up what each meta
 * key actually means (label, type, group).
 *
 * Extensible via the Extensible_Schema trait, so an add-on can expose additional
 * read-only fields on groundhogg/get-contact and groundhogg/search-contacts
 * results without modifying this class or either of those abilities - one call
 * registers a new bounded-but-optional property (schema, expand option, and the
 * callback that computes it), the same "only when asked for" treatment
 * 'tags'/'meta' get above:
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
 * Call this once (e.g. on `init`, after confirming both Groundhogg and the
 * dependency it needs are active) - before any ability builds its input/output
 * schema. See Extensible_Schema's own docblock for what extend() can't express
 * and the three underlying filters (`groundhogg/contact_schema/properties`,
 * `groundhogg/contact_schema/transform`, `groundhogg/contact_schema/expand_options`)
 * available for those cases.
 */
class Contact_Schema extends Schema {

	use Extensible_Schema;

	protected static function extension_hook_prefix(): string {
		return 'contact_schema';
	}

	protected static function builtin_property_keys(): array {
		return [ 'id', 'email', 'first_name', 'last_name', 'date_created', 'optin_status', 'tags', 'meta' ];
	}

	public static function get_schema(): array {

		$properties = [
			'id' => [
				'type' => 'integer',
			],
			'email' => [
				'type'   => 'string',
				'format' => 'email',
			],
			'first_name' => [
				'type' => 'string',
			],
			'last_name' => [
				'type' => 'string',
			],
			'date_created' => [
				'type'        => 'string',
				'format'      => 'date-time',
				'description' => __( 'When the contact was created.', 'groundhogg' ),
			],
			'optin_status' => [
				'type'        => 'object',
				'description' => __( 'Whether/how the contact can be marketed to.', 'groundhogg' ),
				'properties'  => [
					'value' => [
						'type'        => 'integer',
						'description' => __( 'The raw Groundhogg\Preferences status constant.', 'groundhogg' ),
					],
					'label' => [
						'type' => 'string',
					],
				],
			],
			'tags' => [
				'type'        => 'array',
				'description' => __( 'Only present when "tags" is passed in include.', 'groundhogg' ),
				'items'       => Tag_Schema::get_schema(),
			],
			'meta' => [
				'type'                 => 'object',
				'description'          => __( 'Only present when "meta" is passed in include. Raw meta key => value pairs, including custom fields. See groundhogg/list-custom-fields to map keys to labels.', 'groundhogg' ),
				'additionalProperties' => true,
			],
		];

		return [
			'type'       => 'object',
			'properties' => self::extend_properties( $properties ),
		];
	}

	/**
	 * The valid values for groundhogg/get-contact's and groundhogg/search-contacts'
	 * own `expand` input parameter (their `enum`, not just this class's $include).
	 * extend()'d keys are included automatically - see the class docblock for
	 * the escape hatch when an add-on isn't going through extend().
	 *
	 * @return string[]
	 */
	public static function expand_options(): array {
		return self::extend_expand_options( [ 'tags', 'meta' ] );
	}

	/**
	 * Accepts an existing Contact, or anything Contact::__construct() accepts
	 * (a contact ID, email, or raw DB row), and returns the schema shape.
	 *
	 * @param Contact|int|string|object $object
	 * @param array                     $include Optional sections to include: 'tags', 'meta',
	 *                                           plus anything an add-on has registered via
	 *                                           Contact_Schema::extend() (see the class docblock).
	 *
	 * @return array
	 */
	public static function transform( $object, array $include = [] ): array {

		if ( ! $object instanceof Contact ) {
			$object = new Contact( $object );
		}

		$optin_status = $object->get_optin_status();

		$data = [
			'id'           => $object->get_id(),
			'email'        => $object->get_email(),
			'first_name'   => $object->get_first_name(),
			'last_name'    => $object->get_last_name(),
			'date_created' => self::format_date_created( $object ),
			'optin_status' => [
				'value' => $optin_status,
				'label' => Preferences::get_preference_pretty_name( $optin_status ),
			],
		];

		if ( in_array( 'tags', $include, true ) ) {
			// array_values(): get_tags(true) => array_map() over $this->tags, which can
			// be a non-sequential-keyed array - e.g. right after Contact::remove_tag()
			// removes one, since that method uses array_diff() and never reindexes.
			// array_map() preserves whatever keys it's given, and json_encode() then
			// serializes a non-sequential array as a JSON *object* instead of an array,
			// breaking this property's declared `tags: array` shape. Confirmed live via
			// groundhogg/update-contact's tags_remove (removing one tag, then expanding
			// tags in the same response, produced {"1":{...},"2":{...}} instead of
			// [{...},{...}]) - re-index explicitly so the output always matches the
			// schema regardless of what the contact's tags happened to go through.
			$data['tags'] = array_values( array_map( [ Tag_Schema::class, 'transform' ], $object->get_tags( true ) ) );
		}

		if ( in_array( 'meta', $include, true ) ) {
			$data['meta'] = $object->get_all_meta();
		}

		return self::extend_transform( $data, $object, $include );
	}

	/**
	 * @param Contact $object
	 *
	 * @return string
	 */
	private static function format_date_created( Contact $object ): string {

		try {
			return $object->get_date_created( true )->format( DateTime::ATOM );
		} catch ( Exception $e ) {
			return (string) $object->get_date_created();
		}
	}
}
