<?php

namespace Groundhogg\Abilities\Funnels;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\Segment_Schema;
use Groundhogg\Background_Tasks;
use Groundhogg\Funnel;
use Groundhogg\Step;
use WP_Error;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;
use function Groundhogg\is_a_contact;

/**
 * Adds a contact, or a segment of contacts, to an active flow - the same
 * operation as the POST gh/v4/funnels/<id>/start route
 * (Funnels_Api::add_contacts()).
 *
 * - A single `contact_id` is enqueued straight away, and requires edit_contact
 *   on that contact.
 * - A segment (the shared Segment_Schema params, or send_to_all) is handed to a
 *   background task that enters the matching contacts in batches, and requires
 *   the schedule_flows capability on top of start_flows.
 *
 * Contacts enter at the flow's first action step unless step_id names another
 * step in the same flow (see groundhogg/list-flows with expand "steps").
 *
 * DESTRUCTIVE is true (contacts begin running through the flow - emails, tags,
 * and other step actions fire, and there is no single "remove from flow" undo);
 * IDEMPOTENT is false (Step::enqueue skips a contact already waiting at that
 * step, but a re-run can still re-enter a contact who has since moved on).
 */
class Add_To_Flow extends Ability {

	protected const NAME       = 'groundhogg/add-to-flow';
	protected const CATEGORY   = 'groundhogg-funnels';
	protected const CAPABILITY = 'start_flows';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = true;
	protected const IDEMPOTENT  = false;

	protected function get_args(): array {

		return [
			'label'       => __( 'Add To Flow', 'groundhogg' ),
			'description' => __( 'Add a contact, or a segment of contacts, to an active flow. Give either contact_id (one contact) or an audience (saved_search, tags_include, or send_to_all). Contacts enter at the first action step unless step_id is given.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'flow_id' ],
				'properties'           => array_merge( Segment_Schema::properties(), [
					'flow_id' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'The flow to add contacts to - see groundhogg/list-flows. Must be active.', 'groundhogg' ),
					],
					'step_id' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'Enter contacts at this step instead of the flow\'s first action step. Must belong to the flow - see groundhogg/list-flows with expand "steps".', 'groundhogg' ),
					],
					'contact_id' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'Add this single contact. Mutually exclusive with the segment params - when given, the segment params are ignored.', 'groundhogg' ),
					],
					'send_to_all' => [
						'type'        => 'boolean',
						'default'     => false,
						'description' => __( 'Explicitly add every contact. Required when no contact_id and no narrowing segment param (tags_include, saved_search, ...) is given.', 'groundhogg' ),
					],
				] ),
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'flow_id'    => [ 'type' => 'integer' ],
					'flow_title' => [ 'type' => 'string' ],
					'step'       => [
						'type'       => 'object',
						'properties' => [
							'id'    => [ 'type' => 'integer' ],
							'title' => [ 'type' => 'string' ],
						],
					],
					'mode' => [
						'type'        => 'string',
						'enum'        => [ 'contact', 'segment' ],
						'description' => __( '"contact" for a single enqueue, "segment" for a background batch.', 'groundhogg' ),
					],
					'added' => [
						'type'        => 'integer',
						'description' => __( 'For mode "contact": 1. Not present for a segment (that runs in the background).', 'groundhogg' ),
					],
					'estimated_contacts' => [
						'type'        => 'integer',
						'description' => __( 'For mode "segment": how many contacts currently match. The background task enters them shortly.', 'groundhogg' ),
					],
					'scheduled' => [
						'type'        => 'boolean',
						'description' => __( 'For mode "segment": true once the background task is queued.', 'groundhogg' ),
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
			return new WP_Error( 'groundhogg_flow_not_active', __( 'That flow is not active. Contacts can only be added to an active flow.', 'groundhogg' ) );
		}

		// Resolve the entry step.
		$step = ! empty( $input['step_id'] )
			? new Step( absint( $input['step_id'] ) )
			: new Step( $funnel->get_first_action_id() );

		if ( ! $step->exists() || $step->get_funnel_id() !== $funnel->get_id() ) {
			return new WP_Error( 'groundhogg_step_not_in_flow', __( 'The given step does not belong to this flow.', 'groundhogg' ) );
		}

		$step_summary = [
			'id'    => $step->get_id(),
			'title' => $step->get_title(),
		];

		// --- Single contact ---------------------------------------------------
		if ( ! empty( $input['contact_id'] ) ) {

			$contact = get_contactdata( absint( $input['contact_id'] ) );

			if ( ! is_a_contact( $contact ) ) {
				return new WP_Error( 'groundhogg_contact_not_found', __( 'Contact not found.', 'groundhogg' ) );
			}

			if ( ! current_user_can( 'edit_contact', $contact ) ) {
				return new WP_Error( 'groundhogg_cannot_edit_contact', __( 'You do not have permission to add this contact to a flow.', 'groundhogg' ) );
			}

			$step->enqueue( $contact );

			return [
				'flow_id'    => $funnel->get_id(),
				'flow_title' => $funnel->get_title(),
				'step'       => $step_summary,
				'mode'       => 'contact',
				'added'      => 1,
			];
		}

		// --- Segment --------------------------------------------------------
		// The route gates the segment path on schedule_flows on top of start_flows.
		if ( ! current_user_can( 'schedule_flows' ) ) {
			return new WP_Error( 'groundhogg_cannot_schedule_flows', __( 'Adding a segment to a flow requires the schedule flows capability.', 'groundhogg' ) );
		}

		if ( ! Segment_Schema::has_audience( $input ) && empty( $input['send_to_all'] ) ) {
			return new WP_Error(
				'groundhogg_no_audience',
				__( 'Give contact_id, a narrowing segment param (tags_include, saved_search, ...), or pass send_to_all.', 'groundhogg' )
			);
		}

		// The plain array form, not to_contact_query()'s live query object -
		// this gets stored and re-run in background batches (Background_Tasks::
		// add_contacts_to_funnel() below); a live query object (and anything a
		// filter attached to it) can't survive that round trip.
		$query = Segment_Schema::to_query( $input );

		if ( is_wp_error( $query ) ) {
			return $query;
		}

		$estimated = get_db( 'contacts' )->count( $query );

		if ( $estimated === 0 ) {
			return new WP_Error( 'groundhogg_no_contacts', __( 'No contacts match the given audience.', 'groundhogg' ) );
		}

		$scheduled = Background_Tasks::add_contacts_to_funnel( $step->get_id(), $query, 0, [] );

		if ( is_wp_error( $scheduled ) ) {
			return $scheduled;
		}

		return [
			'flow_id'            => $funnel->get_id(),
			'flow_title'         => $funnel->get_title(),
			'step'               => $step_summary,
			'mode'               => 'segment',
			'estimated_contacts' => $estimated,
			'scheduled'          => (bool) $scheduled,
		];
	}
}
