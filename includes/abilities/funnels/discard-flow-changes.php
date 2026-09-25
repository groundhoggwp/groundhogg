<?php

namespace Groundhogg\Abilities\Funnels;

use Groundhogg\Abilities\Ability;
use Groundhogg\Funnel;
use WP_Error;

/**
 * Discards an active flow's staged changes, back to what contacts go through:
 * new steps are removed, and edits, moves, and deletes are dropped
 * (Funnel::uncommit()).
 *
 * Inactive flows have nothing to discard, edits to them are written directly.
 */
class Discard_Flow_Changes extends Ability {

	protected const NAME       = 'groundhogg/discard-flow-changes';
	protected const CATEGORY   = 'groundhogg-funnels';
	protected const CAPABILITY = 'edit_funnels';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = true;
	protected const IDEMPOTENT  = true;

	protected function get_args(): array {

		$flow_schema = Get_Flow::flow_schema();

		return [
			'label'       => __( 'Discard Flow Changes', 'groundhogg' ),
			'description' => __( 'Throw away the changes staged on an active flow (by groundhogg/edit-flow or the flow editor), so the draft matches what contacts go through again. Steps added since the last publish are removed. Only for active flows, edits to inactive flows are saved directly and can\'t be discarded.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'flow_id' ],
				'properties'           => [
					'flow_id' => [
						'type'    => 'integer',
						'minimum' => 1,
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'definitions' => $flow_schema['definitions'],
				'properties' => [
					'had_changes' => [
						'type'        => 'boolean',
						'description' => __( 'False if there was nothing to discard.', 'groundhogg' ),
					],
					'flow'        => array_diff_key( $flow_schema, [ 'definitions' => true ] ),
				],
			],
		];
	}

	public function __invoke( $input ) {

		$funnel = new Funnel( absint( $input['flow_id'] ) );

		if ( ! $funnel->exists() ) {
			return new WP_Error( 'groundhogg_flow_not_found', __( 'Flow not found.', 'groundhogg' ) );
		}

		if ( ! $funnel->is_active() ) {
			return new WP_Error( 'groundhogg_flow_not_active', __( 'Only active flows have staged changes to discard, edits to an inactive flow are saved directly.', 'groundhogg' ) );
		}

		$had_changes = Flow_Changes::has_unpublished_changes( $funnel );

		if ( $had_changes ) {
			$funnel->uncommit();

			do_action( 'groundhogg/api/funnel/updated', $funnel );
		}

		return [
			'had_changes' => $had_changes,
			'flow'        => Get_Flow::describe( $funnel ),
		];
	}
}
