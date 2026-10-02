<?php

namespace Groundhogg\Abilities\Funnels;

use Groundhogg\Utils\DateTimeHelper;
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
	 * Flows being edited by someone else in the flow editor can't be changed until they're done
	 *
	 * @param Funnel $funnel
	 *
	 * @return true|WP_Error
	 */
	public static function check_lock( Funnel $funnel ) {

		$locked_by = \Groundhogg\check_lock( $funnel );

		if ( ! $locked_by ) {
			return true;
		}

		return new WP_Error( 'groundhogg_flow_locked', sprintf(
		/* translators: %s: the name of the user editing the flow */
			__( '%s is editing this flow in the flow editor, so it can\'t be changed until they\'re done.', 'groundhogg' ),
			get_userdata( $locked_by )->display_name
		) );
	}

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
					'date'   => [
						'type'        => 'string',
						'description' => __( 'For "move", with time: the date, Y-m-d in the site\'s timezone, that the contacts run at the step they\'re moved to. Without date and time they run when the step would normally run, as of now.', 'groundhogg' ),
					],
					'time'   => [
						'type'        => 'string',
						'description' => __( 'For "move", with date: the time of day, H:i or H:i:s in the site\'s timezone.', 'groundhogg' ),
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
					'run_at'           => [
						'type'        => 'integer',
						'description' => __( 'For "move": the Unix timestamp that contacts were set to run at, when one was given.', 'groundhogg' ),
					],
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
	 * @return array|WP_Error step ID => [ 'action' => 'cancel'|'move', 'to' => step ID, 'time' => timestamp, when it was given ]
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

			if ( $action === 'move' ) {

				$time = self::parse_run_time( $choice['date'] ?? '', $choice['time'] ?? '' );

				if ( is_wp_error( $time ) ) {
					return $time;
				}

				if ( $time ) {
					$choices[ $step_id ]['time'] = $time;
				}
			}
		}

		return $choices;
	}

	/**
	 * When contacts that are moved are to run, as a date and a time of day in the site's timezone, like when contacts are
	 * added to a flow later
	 *
	 * @param mixed $date Y-m-d
	 * @param mixed $time H:i or H:i:s
	 *
	 * @return int|WP_Error the timestamp, 0 when there isn't a date and a time, they run when the step they're moved to normally would
	 */
	public static function parse_run_time( $date, $time ) {

		$date = is_scalar( $date ) ? trim( sanitize_text_field( (string) $date ) ) : '';
		$time = is_scalar( $time ) ? trim( sanitize_text_field( (string) $time ) ) : '';

		if ( $date === '' || $time === '' ) {
			return 0;
		}

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) || ! preg_match( '/^\d{1,2}:\d{2}(:\d{2})?$/', $time ) ) {
			return new WP_Error( 'groundhogg_invalid_run_time', __( 'The date to run moved contacts must be Y-m-d, and the time H:i or H:i:s.', 'groundhogg' ) );
		}

		try {
			$when = new DateTimeHelper( "$date $time", wp_timezone() );
		} catch ( \Exception $e ) {
			return new WP_Error( 'groundhogg_invalid_run_time', $e->getMessage() );
		}

		return $when->getTimestamp();
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

				if ( ! empty( $choice['time'] ) ) {
					$outcome['run_at'] = (int) $choice['time'];
				}
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
