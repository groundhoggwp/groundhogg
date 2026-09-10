<?php

namespace Groundhogg\Abilities\Schemas;

use Groundhogg\Utils\DateTimeHelper;
use Throwable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base class for reusable ability schemas.
 *
 * A Schema knows two things about a single "shape" of data (a Contact, a Tag, a Funnel, etc.):
 *
 * 1. How to describe that shape as a JSON schema, for use as an ability's output_schema
 *    (or nested inside another schema, e.g. as the `items` of an array).
 * 2. How to transform a real object (or raw row) of that shape into the plain array
 *    matching the schema, for use as an ability's actual return value.
 *
 * Both are static, so a Schema is used directly by class name without instantiating it:
 *
 *     Contact_Schema::get_schema();          // => the JSON schema array
 *     Contact_Schema::transform( $contact ); // => [ 'id' => ..., 'email' => ..., ... ]
 *
 * transform() takes an optional $include list of extra, bounded-but-optional sections a
 * caller can opt into (e.g. 'tags', 'meta' on Contact_Schema). Cheap standard fields are
 * always returned; anything gated behind $include is only computed (extra queries and
 * all) when actually requested. A Schema with nothing optional can just ignore $include.
 *
 * transform() is also usable directly as an array_map() callback when no $include is
 * needed:
 *
 *     array_map( [ Contact_Schema::class, 'transform' ], $rows );
 */
abstract class Schema {

	/**
	 * The JSON schema describing this shape. Suitable as an output_schema, or as the
	 * `items` of an array_schema property.
	 *
	 * @return array
	 */
	abstract public static function get_schema(): array;

	/**
	 * Transform an object (or raw row) into an array matching get_schema().
	 *
	 * @param mixed $object
	 * @param array $include Optional section names to include, for schemas that have
	 *                        bounded-but-optional data. Ignored by schemas with nothing
	 *                        optional to offer.
	 *
	 * @return array
	 */
	abstract public static function transform( $object, array $include = [] ): array;

	/**
	 * The JSON schema fragment for a moment in time rendered by datetime():
	 * the same instant as UTC and in the site's timezone, plus the zone name.
	 *
	 * @return array
	 */
	public static function datetime_schema(): array {
		return [
			'type'        => [ 'object', 'null' ],
			'description' => __( 'A moment in time, given as both UTC and the site timezone. Null if not set.', 'groundhogg' ),
			'properties'  => [
				'utc' => [
					'type'        => 'string',
					'description' => __( 'ISO 8601, UTC (e.g. 2026-07-10T14:00:00Z).', 'groundhogg' ),
				],
				'local' => [
					'type'        => 'string',
					'description' => __( 'ISO 8601 in the site timezone, with offset (e.g. 2026-07-10T10:00:00-04:00).', 'groundhogg' ),
				],
				'timezone' => [
					'type'        => 'string',
					'description' => __( 'The site timezone name or offset (e.g. "America/New_York").', 'groundhogg' ),
				],
			],
		];
	}

	/**
	 * Render a moment in time as both UTC and the site's local timezone, so a
	 * caller sees the exact instant and the wall-clock time an admin would read.
	 *
	 * @param int|string|\DateTimeInterface $when A unix timestamp, a datetime
	 *                                            string (assumed site timezone if
	 *                                            it has no offset), or a DateTime.
	 *
	 * @return array{utc: string, local: string, timezone: string}|null Null for an
	 *                                            empty or unparseable value.
	 */
	public static function datetime( $when ): ?array {

		if ( $when === null || $when === '' || $when === 0 || $when === '0' ) {
			return null;
		}

		try {
			$dt = new DateTimeHelper( is_numeric( $when ) ? (int) $when : $when );
		} catch ( Throwable $e ) {
			return null;
		}

		return [
			'utc'      => gmdate( 'Y-m-d\TH:i:s\Z', $dt->getTimestamp() ),
			'local'    => $dt->setTimezone( wp_timezone() )->format( 'Y-m-d\TH:i:sP' ),
			'timezone' => wp_timezone()->getName(),
		];
	}
}
