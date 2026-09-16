<?php

namespace Groundhogg\Abilities\Funnels;

/**
 * Actually runs a flow for a real contact starting from a given step: real events are created,
 * and real actions (send_email, apply_tag, ...) actually execute against the real contact,
 * exactly as if they'd reached that step for real. This is NOT a preview - see
 * groundhogg/simulate-flow for the dry-run version that traces without any side effects.
 *
 * Gated on `edit_funnels` rather than the read-only `view_funnels` groundhogg/simulate-flow
 * uses, matching the capability the funnel editor's own "Simulate" AJAX handler already requires
 * for this identical live-execution operation.
 *
 * See Abstract_Simulate_Flow for the shared traversal/trace-shaping logic both this and
 * Simulate_Flow use.
 */
class Live_Simulate_Flow extends Abstract_Simulate_Flow {

	protected const NAME       = 'groundhogg/live-simulate-flow';
	protected const CATEGORY   = 'groundhogg-funnels';
	protected const CAPABILITY = 'edit_funnels';

	protected const DESTRUCTIVE = true;

	protected function is_dry_run(): bool {
		return false;
	}

	protected function get_label(): string {
		return __( 'Live Simulate Flow', 'groundhogg' );
	}

	protected function get_description(): string {
		return __( 'Actually run a flow for a real contact starting from a given step - real events are created and real actions (send_email, apply_tag, ...) actually execute against the real contact. This is NOT a preview - it really happens. Use groundhogg/simulate-flow first to see what would happen before running this. See groundhogg/list-flows (expand: ["steps"]) to find a step_id to start from.', 'groundhogg' );
	}
}
