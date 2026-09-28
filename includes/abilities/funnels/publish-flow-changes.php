<?php

namespace Groundhogg\Abilities\Funnels;

use Groundhogg\Abilities\Ability;
use Groundhogg\Funnel;
use WP_Error;

/**
 * Publishes an active flow's staged changes, the same as the flow editor's
 * Update button: edited settings, new steps, and moves go live, deleted steps are
 * removed (or archived, if they have history), and contacts waiting at deleted
 * steps are cancelled or moved to another action. See Flow_Changes.
 *
 * Inactive flows have nothing to publish, edits to them are written directly,
 * they go live with groundhogg/activate-flow.
 */
class Publish_Flow_Changes extends Ability {

	protected const NAME       = 'groundhogg/publish-flow-changes';
	protected const CATEGORY   = 'groundhogg-funnels';
	protected const CAPABILITY = 'edit_funnels';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = true;
	protected const IDEMPOTENT  = false;

	protected function get_args(): array {

		$flow_schema = Get_Flow::flow_schema();

		return [
			'label'       => __( 'Publish Flow Changes', 'groundhogg' ),
			'description' => __( 'Publish the changes staged on an active flow (by groundhogg/edit-flow or the flow editor) so contacts go through them, like the flow editor\'s Update button. Contacts waiting at deleted steps are cancelled unless deleted_steps moves them to another action. Review the draft with groundhogg/get-flow first. For an inactive flow use groundhogg/activate-flow instead.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'flow_id' ],
				'properties'           => [
					'flow_id'           => [
						'type'    => 'integer',
						'minimum' => 1,
					],
					'expected_revision' => [
						'type'        => 'string',
						'description' => __( 'The revision groundhogg/get-flow returned for the draft that was reviewed. If the draft was changed since, nothing is published and an error is returned.', 'groundhogg' ),
					],
					'deleted_steps'     => Flow_Changes::deleted_steps_schema(),
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'definitions' => $flow_schema['definitions'],
				'properties' => [
					'had_changes'   => [
						'type'        => 'boolean',
						'description' => __( 'False if there was nothing to publish.', 'groundhogg' ),
					],
					'flow'          => array_diff_key( $flow_schema, [ 'definitions' => true ] ),
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

		$unlocked = Flow_Changes::check_lock( $funnel );

		if ( is_wp_error( $unlocked ) ) {
			return $unlocked;
		}

		if ( ! $funnel->is_active() ) {
			return new WP_Error( 'groundhogg_flow_not_active', __( 'Only active flows have changes to publish. Edits to an inactive flow are already saved, use groundhogg/activate-flow to make it live.', 'groundhogg' ) );
		}

		if ( ! empty( $input['expected_revision'] ) && Get_Flow::revision( $funnel ) !== $input['expected_revision'] ) {
			return new WP_Error( 'groundhogg_flow_changed', __( 'The draft was changed since it was read. Review it again with groundhogg/get-flow and retry.', 'groundhogg' ) );
		}

		$choices = Flow_Changes::get_choices( $funnel, (array) ( $input['deleted_steps'] ?? [] ) );

		if ( is_wp_error( $choices ) ) {
			return $choices;
		}

		$had_changes = Flow_Changes::has_unpublished_changes( $funnel );
		$outcomes    = Flow_Changes::outcomes( $funnel, $choices );

		if ( $had_changes ) {
			Flow_Changes::publish( $funnel, $choices );

			do_action( 'groundhogg/api/funnel/updated', $funnel );
		}

		return [
			'had_changes'   => $had_changes,
			'flow'          => Get_Flow::describe( $funnel, 'live' ),
			'deleted_steps' => $outcomes,
		];
	}
}
