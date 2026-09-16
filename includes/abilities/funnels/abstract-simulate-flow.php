<?php

namespace Groundhogg\Abilities\Funnels;

use Groundhogg\Abilities\Ability;
use Groundhogg\Admin\Funnels\Simulator;
use Groundhogg\Contact;
use Groundhogg\Funnel;
use Groundhogg\Step;
use WP_Error;

/**
 * Shared traversal + trace-shaping for Simulate_Flow (always dry) and Live_Simulate_Flow (always
 * live) - two separate abilities rather than one with a dry_run toggle, so a caller sees the
 * blast-radius difference in which ability they picked, not in a boolean buried in the input.
 *
 * Wraps Groundhogg\Admin\Funnels\Simulator::simulate() - the same traversal already used by the
 * funnel editor's own "Simulate" panel and the `wp groundhogg-tests simulate` WP-CLI command -
 * rather than re-implementing the branch/timer/loop/ambiguous-trigger logic here. That class was
 * built for a human to resolve ambiguous forks one AJAX/CLI round-trip at a time (it called
 * wp_send_json()/WP_CLI::success() unconditionally, which would terminate or fatal outside those
 * two contexts); it's been patched to instead return the flow/options data structurally for any
 * other caller - these abilities included.
 *
 * Known, deliberate limitations of the underlying traversal (surfaced per step in `trace`),
 * unaffected by which subclass is used:
 * - Trigger (benchmark) steps can't have their real match condition evaluated for a hypothetical
 *   walk-through - Tag_Applied::can_complete_step() and friends only work off data set by the
 *   real WordPress hook that just fired (e.g. which tag was JUST applied), which doesn't exist
 *   here. Every benchmark encountered is simply assumed to match.
 * - Non-branch logic steps that route randomly or by rotation (e.g. weighted_distribution,
 *   evergreen_sequence) return one real, live evaluation each call - not every possible outcome.
 */
abstract class Abstract_Simulate_Flow extends Ability {

	/**
	 * @return bool
	 */
	abstract protected function is_dry_run(): bool;

	/**
	 * @return string
	 */
	abstract protected function get_label(): string;

	/**
	 * @return string
	 */
	abstract protected function get_description(): string;

	protected function get_args(): array {

		return [
			'label'       => $this->get_label(),
			'description' => $this->get_description(),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'funnel_id', 'contact_id', 'step_id' ],
				'properties'           => [
					'funnel_id'  => [
						'type'        => 'integer',
						'description' => __( 'The flow to simulate - see groundhogg/list-flows.', 'groundhogg' ),
					],
					'contact_id' => [
						'type'        => 'integer',
						'description' => __( 'The real, existing contact to run this for - their actual data is used to evaluate branch conditions.', 'groundhogg' ),
					],
					'step_id'    => [
						'type'        => 'integer',
						'description' => __( 'Which step of the flow to start tracing from. Required rather than defaulting to the flow\'s first step, since a flow can have more than one independent trigger.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'trace'             => [
						'type'        => 'array',
						'description' => __( 'One entry per step visited, in order.', 'groundhogg' ),
						'items'       => [
							'type'       => 'object',
							'properties' => [
								'step_id' => [ 'type' => 'integer' ],
								'title'   => [ 'type' => 'string' ],
								'type'    => [
									'type' => 'string',
									'enum' => [ 'benchmark', 'action', 'timer', 'logic' ],
								],
								'notes'   => [
									'type'        => 'array',
									'items'       => [ 'type' => 'string' ],
									'description' => __( 'Human-readable narration for this step - e.g. which branch matched, or the resolved wait-until time.', 'groundhogg' ),
								],
							],
						],
					],
					'stopped_reason'    => [
						'type' => 'string',
						'enum' => [ 'completed', 'ambiguous_trigger' ],
					],
					'ambiguous_options' => [
						'type'        => 'array',
						'description' => __( 'Only present when stopped_reason is "ambiguous_trigger" - candidate trigger steps the trace could not automatically choose between, since which one a real contact would enter through can\'t be evaluated for a hypothetical walk-through.', 'groundhogg' ),
						'items'       => [
							'type'       => 'object',
							'properties' => [
								'step_id' => [ 'type' => 'integer' ],
								'title'   => [ 'type' => 'string' ],
							],
						],
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$funnel = new Funnel( absint( $input['funnel_id'] ) );

		if ( ! $funnel->exists() ) {
			return new WP_Error( 'groundhogg_invalid_funnel', __( 'No flow exists with that id.', 'groundhogg' ) );
		}

		$contact = new Contact( absint( $input['contact_id'] ) );

		if ( ! $contact->exists() ) {
			return new WP_Error( 'groundhogg_invalid_contact', __( 'No contact exists with that id.', 'groundhogg' ) );
		}

		$start = new Step( absint( $input['step_id'] ) );

		if ( ! $start->exists() || absint( $start->get_funnel_id() ) !== $funnel->get_id() ) {
			return new WP_Error( 'groundhogg_invalid_step', __( 'No step with that id exists in this flow.', 'groundhogg' ) );
		}

		$result = Simulator::simulate( $start, $contact, $this->is_dry_run() );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$steps_by_id = [];
		foreach ( $funnel->get_steps() as $step ) {
			$steps_by_id[ $step->get_id() ] = $step;
		}

		$trace = [];

		// Structural markers, not narration about a specific step - stopped_reason already
		// conveys this.
		$structural_markers = [ '🟩 Starting simulation...', '🏁 Simulation complete!' ];

		// $result['flow'] is a flat log: an int (a visited step id) followed by zero or more
		// human-readable narration strings about that step, repeated per step visited.
		foreach ( (array) ( $result['flow'] ?? [] ) as $item ) {

			if ( in_array( $item, $structural_markers, true ) ) {
				continue;
			}

			if ( is_numeric( $item ) ) {

				$step = $steps_by_id[ absint( $item ) ] ?? null;

				$trace[] = [
					'step_id' => absint( $item ),
					'title'   => $step ? $step->get_title() : '',
					'type'    => $step ? $this->step_type( $step ) : 'action',
					'notes'   => [],
				];

			} else if ( ! empty( $trace ) ) {
				$trace[ count( $trace ) - 1 ]['notes'][] = sanitize_text_field( $item );
			}
		}

		$ambiguous_options = array_values( array_filter( array_map( function ( $step_id ) use ( $steps_by_id ) {
			$step = $steps_by_id[ absint( $step_id ) ] ?? null;

			return $step ? [ 'step_id' => $step->get_id(), 'title' => $step->get_title() ] : null;
		}, (array) ( $result['options'] ?? [] ) ) ) );

		$output = [
			'trace'          => $trace,
			'stopped_reason' => $ambiguous_options ? 'ambiguous_trigger' : 'completed',
		];

		if ( $ambiguous_options ) {
			$output['ambiguous_options'] = $ambiguous_options;
		}

		return $output;
	}

	/**
	 * @param Step $step
	 *
	 * @return string
	 */
	protected function step_type( Step $step ): string {

		if ( $step->is_benchmark() ) {
			return 'benchmark';
		}

		if ( $step->is_logic() ) {
			return 'logic';
		}

		if ( $step->is_timer() ) {
			return 'timer';
		}

		return 'action';
	}
}
