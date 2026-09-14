<?php

namespace Groundhogg\Abilities\Funnels;

use Groundhogg\Abilities\Ability;
use Groundhogg\Funnel;
use WP_Error;

/**
 * Activates a flow (funnel) - the same status change as toggling it on in
 * wp-admin, or PUT gh/v4/funnels/<id> with {"status": "active"}
 * (Funnels_Api::update_single(), generic - this ability exists so activation
 * doesn't require constructing that generic update payload just to flip one
 * field). Once active, contacts can enter via its triggers, and
 * groundhogg/add-to-flow can enqueue contacts into it.
 *
 * Companion to groundhogg/create-flow, which always creates a flow inactive by
 * design (see that class's docblock) - this is the deliberate second step for a
 * caller that wants to go live without a human reviewing it in wp-admin first.
 *
 * Already-active is treated as success, not an error (IDEMPOTENT) - calling
 * this on a flow that's already active just confirms its current state rather
 * than failing.
 */
class Activate_Flow extends Ability {

	protected const NAME       = 'groundhogg/activate-flow';
	protected const CATEGORY   = 'groundhogg-funnels';
	protected const CAPABILITY = 'edit_funnels';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = false;
	protected const IDEMPOTENT  = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'Activate Flow', 'groundhogg' ),
			'description' => __( 'Activate a flow so contacts can start entering it and groundhogg/add-to-flow can enqueue contacts into it. A flow with no steps cannot be activated. Already-active is treated as success.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'flow_id' ],
				'properties'           => [
					'flow_id' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'The flow to activate - see groundhogg/list-flows.', 'groundhogg' ),
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
					'was_already_active' => [
						'type'        => 'boolean',
						'description' => __( 'True if the flow was already active and nothing changed.', 'groundhogg' ),
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

		if ( $funnel->is_active() ) {
			return [
				'id'                 => $funnel->get_id(),
				'title'              => $funnel->get_title(),
				'status'             => $funnel->get_status(),
				'admin_link'         => $funnel->admin_link(),
				'was_already_active' => true,
			];
		}

		if ( empty( $funnel->get_steps() ) ) {
			return new WP_Error( 'groundhogg_flow_no_steps', __( 'This flow has no steps yet - add at least one before activating it. See groundhogg/create-flow or the flow editor in wp-admin.', 'groundhogg' ) );
		}

		$funnel->update( [ 'status' => 'active' ] );

		// Mirrors the REST update path's own action for other code hooking flow
		// status changes (cache busting, integrations, etc.).
		do_action( 'groundhogg/api/funnel/updated', $funnel );

		return [
			'id'                 => $funnel->get_id(),
			'title'              => $funnel->get_title(),
			'status'             => $funnel->get_status(),
			'admin_link'         => $funnel->admin_link(),
			'was_already_active' => false,
		];
	}
}
