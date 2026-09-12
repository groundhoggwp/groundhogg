<?php

namespace Groundhogg\Abilities\Schemas;

use Groundhogg\Campaign;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared schema for a Groundhogg Campaign.
 */
class Campaign_Schema extends Schema {

	public static function get_schema(): array {

		return [
			'type'       => 'object',
			'properties' => [
				'id' => [
					'type' => 'integer',
				],
				'name' => [
					'type' => 'string',
				],
				'slug' => [
					'type' => 'string',
				],
				'description' => [
					'type' => 'string',
				],
				'visibility' => [
					'type' => 'string',
					'enum' => [ 'public', 'hidden' ],
				],
				'email_count' => [
					'type'        => 'integer',
					'description' => __( 'Only present when "email_count" is passed in expand. The number of emails tied to this campaign - costs an extra query per campaign.', 'groundhogg' ),
				],
				'broadcast_count' => [
					'type'        => 'integer',
					'description' => __( 'Only present when "broadcast_count" is passed in expand. The number of broadcasts tied to this campaign - costs an extra query per campaign.', 'groundhogg' ),
				],
				'funnel_count' => [
					'type'        => 'integer',
					'description' => __( 'Only present when "funnel_count" is passed in expand. The number of flows tied to this campaign - costs an extra query per campaign.', 'groundhogg' ),
				],
			],
		];
	}

	/**
	 * Accepts an existing Campaign, or anything Campaign::__construct() accepts (an ID, slug, or raw row).
	 *
	 * @param Campaign|int|string|object $object
	 * @param array                      $expand Optional extra sections: 'email_count', 'broadcast_count', 'funnel_count'.
	 *
	 * @return array
	 */
	public static function transform( $object, array $expand = [] ): array {

		if ( ! $object instanceof Campaign ) {
			$object = new Campaign( $object );
		}

		$data = [
			'id'          => $object->get_id(),
			'name'        => $object->get_name(),
			'slug'        => $object->get_slug(),
			'description' => $object->get_description(),
			'visibility'  => $object->is_public() ? 'public' : 'hidden',
		];

		if ( in_array( 'email_count', $expand, true ) ) {
			$data['email_count'] = $object->count_parents( 'email' );
		}

		if ( in_array( 'broadcast_count', $expand, true ) ) {
			$data['broadcast_count'] = $object->count_parents( 'broadcast' );
		}

		if ( in_array( 'funnel_count', $expand, true ) ) {
			$data['funnel_count'] = $object->count_parents( 'funnel' );
		}

		return $data;
	}
}
