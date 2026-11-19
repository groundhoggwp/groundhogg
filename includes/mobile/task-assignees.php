<?php

namespace Groundhogg\Mobile;

use WP_REST_Response;
use WP_REST_Server;
use function Groundhogg\get_owners;
use function Groundhogg\get_team_ids;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The people a task can be assigned to, for the mobile app's "Assign to" on a new task: the site's owners, or just the
 * team for someone who has one, the same people the desktop offers. Only for those who can edit other people's tasks.
 *
 * `GET gh/v4/mobile/task-assignees` returns { items: [ { id, name } ] }, the person asking first.
 */
class Task_Assignees {

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes() {
		register_rest_route( 'gh/v4', '/mobile/task-assignees', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read' ],
				'permission_callback' => function () {
					return current_user_can( 'add_tasks' ) && current_user_can( 'edit_others_tasks' );
				},
			],
		] );
	}

	/**
	 * @return array[] { id, name }
	 */
	public function people(): array {

		$me      = get_current_user_id();
		$team    = array_map( 'absint', get_team_ids( $me ) );
		$owners  = get_owners();
		$people  = [];

		foreach ( $owners as $user ) {

			if ( $team && ! in_array( (int) $user->ID, $team, true ) && (int) $user->ID !== $me ) {
				continue;
			}

			$people[ (int) $user->ID ] = [ 'id' => (int) $user->ID, 'name' => $user->display_name ];
		}

		// the person asking is always one of them, first
		if ( ! isset( $people[ $me ] ) && ( $user = get_userdata( $me ) ) ) {
			$people[ $me ] = [ 'id' => $me, 'name' => $user->display_name ];
		}

		$mine = $people[ $me ];
		unset( $people[ $me ] );

		return array_merge( [ $mine ], array_values( $people ) );
	}

	/**
	 * @return WP_REST_Response
	 */
	public function read() {
		return new WP_REST_Response( [ 'items' => $this->people() ] );
	}
}
