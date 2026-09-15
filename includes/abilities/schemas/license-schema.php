<?php

namespace Groundhogg\Abilities\Schemas;

use Groundhogg\License;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared schema for a Groundhogg License (includes/license-manager.php).
 *
 * Doesn't extend the generic Schema base class - License is always looked up by its key (an
 * external string, not a property on the object itself), and has no optional "include" sections
 * to request, so the base class's transform( $object, array $include ) shape doesn't fit cleanly.
 */
class License_Schema {

	public static function get_schema(): array {

		return [
			'type'       => 'object',
			'properties' => [
				'license_key'           => [ 'type' => 'string' ],
				'valid'                 => [ 'type' => 'boolean' ],
				'status'                => [
					'type'        => 'string',
					'enum'        => [ 'valid', 'invalid', 'expired', 'disabled', 'site_inactive' ],
					'description' => __( 'The raw status from the last activate/check call.', 'groundhogg' ),
				],
				'item_id'               => [ 'type' => 'integer' ],
				'item_name'             => [ 'type' => 'string' ],
				'description'           => [ 'type' => 'string' ],
				'expires'               => [
					'type'        => 'string',
					'description' => __( '"lifetime" for a license that never expires, otherwise a date.', 'groundhogg' ),
				],
				'license_limit'         => [
					'type'        => 'integer',
					'description' => __( 'Total site activations this license allows. 0 means unlimited.', 'groundhogg' ),
				],
				'site_count'            => [ 'type' => 'integer' ],
				'activations_left'      => [ 'type' => 'integer' ],
				'has_activations_left'  => [ 'type' => 'boolean' ],
				'items'                 => [
					'type'        => 'array',
					'items'       => [ 'type' => 'integer' ],
					'description' => __( 'Extension item ids this license grants access to - see groundhogg/list-extensions.', 'groundhogg' ),
				],
			],
		];
	}

	/**
	 * @param string  $license_key
	 * @param License $license
	 *
	 * @return array
	 */
	public static function transform( string $license_key, License $license ): array {

		return [
			'license_key'          => $license_key,
			'valid'                => $license->is_valid(),
			'status'               => $license->license,
			'item_id'              => $license->item_id,
			'item_name'            => $license->item_name,
			'description'          => $license->description,
			'expires'              => $license->is_lifetime() ? 'lifetime' : $license->expires,
			'license_limit'        => $license->license_limit,
			'site_count'           => $license->site_count,
			'activations_left'     => $license->activations_left,
			'has_activations_left' => $license->has_activations_left(),
			'items'                => $license->items,
		];
	}
}
