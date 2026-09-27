<?php

namespace Groundhogg\Abilities\Funnels;

use Groundhogg\Funnel;
use Groundhogg\Step;
use WP_Error;

/**
 * Publishing a flow's staged changes, and deciding what happens to contacts waiting at deleted steps,
 * shared by the flow abilities that publish, discard, activate, and deactivate.
 *
 * Deleted steps are soft deleted until the changes are published (Funnel::commit()) on an active flow,
 * or the flow is activated (Funnel::remove_deleted_steps()) on an inactive one. Contacts waiting at them
 * are cancelled then, unless they're moved to another action, the same choice the flow editor asks for.
 */
class Flow_Changes {

	/**
	 * The JSON Schema for the choices about contacts waiting at deleted steps
	 *
	 * @return array
	 */
	public static function deleted_steps_schema(): array {
		return [
			'type'        => 'array',
			'description' => __( 'What to do with contacts waiting at deleted steps (see deleted_steps in groundhogg/edit-flow, or waiting_contacts in groundhogg/get-flow). Deleted steps not listed are cancelled. "move" sends them to another action in the flow, they continue from there.', 'groundhogg' ),
			'items'       => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'step', 'action' ],
				'properties'           => [
					'step'   => [
						'type'        => 'integer',
						'description' => __( 'The deleted step.', 'groundhogg' ),
					],
					'action' => [
						'type' => 'string',
						'enum' => [ 'cancel', 'move' ],
					],
					'to'     => [
						'type'        => 'integer',
						'description' => __( 'For "move": an action step in the flow that isn\'t deleted.', 'groundhogg' ),
					],
				],
			],
		];
	}

	/**
	 * The JSON Schema for what happened to contacts waiting at deleted steps, see outcomes()
	 *
	 * @return array
	 */
	public static function outcomes_schema(): array {
		return [
			'type'        => 'array',
			'description' => __( 'The deleted steps contacts were waiting at, and what happened to them.', 'groundhogg' ),
			'items'       => [
				'type'       => 'object',
				'properties' => [
					'id'               => [ 'type' => 'integer' ],
					'title'            => [ 'type' => 'string' ],
					'waiting_contacts' => [ 'type' => 'integer' ],
					'action'           => [
						'type' => 'string',
						'enum' => [ 'cancel', 'move' ],
					],
					'moved_to'         => [ 'type' => 'integer' ],
				],
			],
		];
	}

	/**
	 * Turn deleted_steps input into the choices Funnel::resolve_deleted_step_events() takes
	 *
	 * @param Funnel $funnel
	 * @param array  $deleted_steps
	 *
	 * @return array|WP_Error step ID => [ 'action' => 'cancel'|'move', 'to' => step ID ]
	 */
	public static function get_choices( Funnel $funnel, array $deleted_steps ) {

		$deleted = array_map( 'absint', wp_list_pluck( $funnel->get_deleted_steps(), 'ID' ) );
		$targets = array_map( 'absint', wp_list_pluck( $funnel->get_move_targets(), 'ID' ) );

		$choices = [];

		foreach ( $deleted_steps as $choice ) {

			$choice  = (array) $choice;
			$step_id = absint( $choice['step'] ?? 0 );
			$action  = ( $choice['action'] ?? '' ) === 'move' ? 'move' : 'cancel';
			$to      = absint( $choice['to'] ?? 0 );

			if ( ! in_array( $step_id, $deleted, true ) ) {
				/* translators: %d: the step ID */
				return new WP_Error( 'groundhogg_step_not_deleted', sprintf( __( 'Step %d isn\'t a deleted step in this flow.', 'groundhogg' ), $step_id ) );
			}

			if ( $action === 'move' && ! in_array( $to, $targets, true ) ) {
				/* translators: 1: the step ID, 2: the step to move to */
				return new WP_Error( 'groundhogg_invalid_move_target', sprintf( __( 'Contacts at step %1$d can\'t be moved to %2$d, it must be an action in this flow that isn\'t deleted.', 'groundhogg' ), $step_id, $to ) );
			}

			$choices[ $step_id ] = [ 'action' => $action, 'to' => $to ];
		}

		return $choices;
	}

	/**
	 * What will happen to the contacts waiting at deleted steps, call before resolving them
	 *
	 * @param Funnel $funnel
	 * @param array  $choices from get_choices()
	 *
	 * @return array
	 */
	public static function outcomes( Funnel $funnel, array $choices ) {

		$outcomes = [];

		foreach ( $funnel->get_deleted_steps() as $step ) {

			$waiting = $funnel->count_pending_events( $step );

			if ( ! $waiting ) {
				continue;
			}

			$choice  = $choices[ $step->get_id() ] ?? [ 'action' => 'cancel' ];
			$outcome = [
				'id'               => $step->get_id(),
				'title'            => $step->get_title(),
				'waiting_contacts' => $waiting,
				'action'           => $choice['action'],
			];

			if ( $choice['action'] === 'move' ) {
				$outcome['moved_to'] = $choice['to'];
			}

			$outcomes[] = $outcome;
		}

		return $outcomes;
	}

	/**
	 * Whether an active flow has changes contacts don't go through yet
	 *
	 * @param Funnel $funnel
	 *
	 * @return bool
	 */
	public static function has_unpublished_changes( Funnel $funnel ): bool {

		if ( ! $funnel->is_active() ) {
			return false;
		}

		return $funnel->while_editing( function () use ( $funnel ) {
			return $funnel->has_changes();
		} );
	}

	/**
	 * Publish an active flow's staged changes, like the flow editor's Update button
	 *
	 * @param Funnel $funnel
	 * @param array  $choices from get_choices()
	 *
	 * @return void
	 */
	public static function publish( Funnel $funnel, array $choices ) {
		$funnel->commit( $choices );
		$funnel->update( [ 'last_updated' => current_time( 'mysql' ) ] );
	}

	/**
	 * Before activating an inactive flow, handle the contacts at steps deleted while it was inactive and remove them,
	 * like the flow editor's Activate button
	 *
	 * @param Funnel $funnel
	 * @param array  $choices from get_choices()
	 *
	 * @return void
	 */
	public static function remove_deleted_steps( Funnel $funnel, array $choices ) {
		$funnel->resolve_deleted_step_events( $choices );
		$funnel->remove_deleted_steps();
	}
}
