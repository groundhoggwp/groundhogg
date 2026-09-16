<?php

namespace Groundhogg\Abilities\Funnels;

/**
 * Traces what would happen to a real contact starting from a given step in a flow, WITHOUT
 * enqueuing any real events, sending any real email/SMS, or otherwise touching the contact.
 * Always dry-run - see groundhogg/live-simulate-flow for the version that actually runs it.
 *
 * See Abstract_Simulate_Flow for the shared traversal/trace-shaping logic both this and
 * Live_Simulate_Flow use.
 */
class Simulate_Flow extends Abstract_Simulate_Flow {

	protected const NAME       = 'groundhogg/simulate-flow';
	protected const CATEGORY   = 'groundhogg-funnels';
	protected const CAPABILITY = 'view_funnels';

	protected const READONLY   = true;
	protected const IDEMPOTENT = true;

	protected function is_dry_run(): bool {
		return true;
	}

	protected function get_label(): string {
		return __( 'Simulate Flow', 'groundhogg' );
	}

	protected function get_description(): string {
		return __( 'Trace what would happen to a real contact starting from a given step in a flow - which branches they\'d take, roughly when timers would resolve - without enqueuing any real events or sending anything. Always a dry run. See groundhogg/live-simulate-flow to actually run it for real, and groundhogg/list-flows (expand: ["steps"]) to find a step_id to start from.', 'groundhogg' );
	}
}
