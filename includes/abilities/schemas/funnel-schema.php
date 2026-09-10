<?php

namespace Groundhogg\Abilities\Schemas;

use Groundhogg\Funnel;
use Groundhogg\Step;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared schema for a Groundhogg Funnel ("flow"), as returned by
 * groundhogg/list-flows.
 *
 * Standard fields (title, status, step count, dates) are cheap. The `steps`
 * section loads and builds every step object, so it is only included when
 * requested through the $include argument to transform().
 */
class Funnel_Schema extends Schema {

	public static function get_schema(): array {

		return [
			'type'       => 'object',
			'properties' => [
				'id' => [
					'type' => 'integer',
				],
				'title' => [
					'type' => 'string',
				],
				'status' => [
					'type'        => 'string',
					'description' => __( '"active", "inactive", or "archived". Contacts can only be added to an active flow.', 'groundhogg' ),
				],
				'is_active' => [
					'type' => 'boolean',
				],
				'num_steps' => [
					'type' => 'integer',
				],
				'date_created'  => self::datetime_schema(),
				'last_updated'  => self::datetime_schema(),
				'steps' => [
					'type'        => 'array',
					'description' => __( 'Only present when "steps" is passed in expand. The flow\'s steps in order; pass a step id as step_id to groundhogg/add-to-flow to enter contacts partway through.', 'groundhogg' ),
					'items'       => [
						'type'       => 'object',
						'properties' => [
							'id'        => [ 'type' => 'integer' ],
							'title'     => [ 'type' => 'string' ],
							'type'      => [
								'type'        => 'string',
								'description' => __( 'Machine step type, e.g. "send_email", "delay_timer", "tag_applied".', 'groundhogg' ),
							],
							'type_name' => [
								'type'        => 'string',
								'description' => __( 'Human label for the step type.', 'groundhogg' ),
							],
							'group'     => [
								'type'        => 'string',
								'description' => __( '"benchmark" (an entry / trigger step) or "action" (something the flow does).', 'groundhogg' ),
							],
						],
						'required'   => [ 'id', 'title', 'type', 'group' ],
					],
				],
			],
		];
	}

	/**
	 * Accepts an existing Funnel, or anything Funnel::__construct() accepts (an ID
	 * or raw DB row).
	 *
	 * @param Funnel|int|object $object
	 * @param array             $include Optional sections: 'steps'.
	 *
	 * @return array
	 */
	public static function transform( $object, array $include = [] ): array {

		if ( ! $object instanceof Funnel ) {
			$object = new Funnel( $object );
		}

		$data = [
			'id'           => $object->get_id(),
			'title'        => $object->get_title(),
			'status'       => $object->get_status(),
			'is_active'    => $object->is_active(),
			'num_steps'    => $object->get_num_steps(),
			'date_created' => self::datetime( $object->date_created ),
			'last_updated' => self::datetime( $object->last_updated ),
		];

		if ( in_array( 'steps', $include, true ) ) {

			$data['steps'] = array_values( array_map( function ( Step $step ) {
				return [
					'id'        => $step->get_id(),
					'title'     => $step->get_title(),
					'type'      => $step->get_type(),
					'type_name' => $step->get_type_name(),
					'group'     => $step->is_benchmark() ? 'benchmark' : 'action',
				];
			}, $object->get_steps() ) );
		}

		return $data;
	}
}
