<?php

namespace Groundhogg\Abilities\Funnels;

use Groundhogg\Abilities\Ability;
use Groundhogg\Funnel;
use WP_Error;

/**
 * Deactivates a flow (funnel) - the same status change as toggling it off in
 * wp-admin, or PUT gh/v4/funnels/<id> with {"status": "inactive"}
 * (Funnels_Api::update_single(), generic - this ability exists so
 * deactivation doesn't require constructing that generic update payload just
 * to flip one field). Stops new contacts from entering via its triggers and
 * pauses any of its events still waiting to run (Funnel::update() handles both
 * as a side effect of the status change) - it does not remove contacts already
 * mid-flow, and does not delete anything.
 *
 * Companion to groundhogg/activate-flow. Already-inactive is treated as
 * success, not an error (IDEMPOTENT) - also true for an archived flow, which
 * this treats the same as inactive (no separate "unarchive" concept here).
 */
class Deactivate_Flow extends Ability {

	protected const NAME       = 'groundhogg/deactivate-flow';
	protected const CATEGORY   = 'groundhogg-funnels';
	protected const CAPABILITY = 'edit_funnels';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = false;
	protected const IDEMPOTENT  = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'Deactivate Flow', 'groundhogg' ),
			'description' => __( 'Deactivate a flow, pausing its waiting events and stopping new contacts from entering via its triggers. Does not remove contacts already in progress, and does not delete anything. Already-inactive is treated as success.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'flow_id' ],
				'properties'           => [
					'flow_id' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'The flow to deactivate - see groundhogg/list-flows.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'id' => [
						'type' => 'integer',
					],
					'title' => [
						'type' => 'string',
					],
					'status' => [
						'type' => 'string',
					],
					'admin_link' => [
						'type' => 'string',
					],
					'was_already_inactive' => [
						'type'        => 'boolean',
						'description' => __( 'True if the flow was already inactive (or archived) and nothing changed.', 'groundhogg' ),
					],
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
			return [
				'id'                   => $funnel->get_id(),
				'title'                => $funnel->get_title(),
				'status'               => $funnel->get_status(),
				'admin_link'           => $funnel->admin_link(),
				'was_already_inactive' => true,
			];
		}

		$funnel->update( [ 'status' => 'inactive' ] );

		// Mirrors the REST update path's own action for other code hooking flow
		// status changes (cache busting, integrations, etc.).
		do_action( 'groundhogg/api/funnel/updated', $funnel );

		return [
			'id'                   => $funnel->get_id(),
			'title'                => $funnel->get_title(),
			'status'               => $funnel->get_status(),
			'admin_link'           => $funnel->admin_link(),
			'was_already_inactive' => false,
		];
	}
}
