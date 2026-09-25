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
 * mid-flow. Staged changes must be published or discarded first (pending_changes),
 * like the flow editor's Deactivate, see Flow_Changes.
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
			'description' => __( 'Deactivate a flow, pausing its waiting events and stopping new contacts from entering via its triggers. Does not remove contacts already in progress. Already-inactive is treated as success. If the flow has unpublished changes, pending_changes must say whether to publish or discard them first, like the flow editor asks.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'flow_id' ],
				'properties'           => [
					'flow_id'         => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'The flow to deactivate - see groundhogg/list-flows.', 'groundhogg' ),
					],
					'pending_changes' => [
						'type'        => 'string',
						'enum'        => [ 'publish', 'discard' ],
						'description' => __( 'Required if the flow has unpublished changes (see has_unpublished_changes in groundhogg/get-flow): publish them first, like groundhogg/publish-flow-changes, or discard them, like groundhogg/discard-flow-changes.', 'groundhogg' ),
					],
					'deleted_steps'   => Flow_Changes::deleted_steps_schema(),
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
					'deleted_steps' => Flow_Changes::outcomes_schema(),
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
				'deleted_steps'        => [],
			];
		}

		$outcomes = [];

		// staged changes are published or discarded first, like the flow editor's Deactivate
		if ( Flow_Changes::has_unpublished_changes( $funnel ) ) {

			$pending_changes = $input['pending_changes'] ?? '';

			if ( $pending_changes === 'publish' ) {

				$choices = Flow_Changes::get_choices( $funnel, (array) ( $input['deleted_steps'] ?? [] ) );

				if ( is_wp_error( $choices ) ) {
					return $choices;
				}

				$outcomes = Flow_Changes::outcomes( $funnel, $choices );

				Flow_Changes::publish( $funnel, $choices );

			} else if ( $pending_changes === 'discard' ) {
				$funnel->uncommit();
			} else {
				return new WP_Error( 'groundhogg_flow_has_unpublished_changes', __( 'This flow has unpublished changes. Pass pending_changes "publish" or "discard" to say what happens to them.', 'groundhogg' ) );
			}
		}

		$funnel->update( [
			'status'       => 'inactive',
			'last_updated' => current_time( 'mysql' ),
		] );

		// Mirrors the REST update path's own action for other code hooking flow
		// status changes (cache busting, integrations, etc.).
		do_action( 'groundhogg/api/funnel/updated', $funnel );

		return [
			'id'                   => $funnel->get_id(),
			'title'                => $funnel->get_title(),
			'status'               => $funnel->get_status(),
			'admin_link'           => $funnel->admin_link(),
			'was_already_inactive' => false,
			'deleted_steps'        => $outcomes,
		];
	}
}
