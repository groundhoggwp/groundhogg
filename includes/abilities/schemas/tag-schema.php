<?php

namespace Groundhogg\Abilities\Schemas;

use Groundhogg\Tag;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared schema for a Groundhogg Tag.
 */
class Tag_Schema extends Schema {

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
				'contact_count' => [
					'type'        => 'integer',
					'description' => __( 'Only present when "contact_count" is passed in include. The number of contacts with this tag - costs an extra query per tag.', 'groundhogg' ),
				],
			],
		];
	}

	/**
	 * Accepts an existing Tag, or anything Tag::__construct() accepts (a tag ID or raw row).
	 *
	 * @param Tag|int|object $object
	 * @param array           $include Optional sections to include: 'contact_count'.
	 *
	 * @return array
	 */
	public static function transform( $object, array $include = [] ): array {

		if ( ! $object instanceof Tag ) {
			$object = new Tag( $object );
		}

		$data = [
			'id'   => $object->get_id(),
			'name' => $object->get_name(),
			'slug' => $object->get_slug(),
		];

		if ( in_array( 'contact_count', $include, true ) ) {
			$data['contact_count'] = $object->get_contact_count();
		}

		return $data;
	}
}
