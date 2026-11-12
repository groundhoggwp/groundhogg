<?php

namespace Groundhogg\Abilities\Contacts;

use Groundhogg\Abilities\Ability;
use Groundhogg\Background\Sync_Users as Sync_Users_Task;
use Groundhogg\Background\Sync_Users_Last_Id;
use Groundhogg\Background_Tasks;
use function Groundhogg\create_contact_from_user;
use function Groundhogg\is_option_enabled;

/**
 * Creates a contact for every WordPress user that doesn't have one, and links the ones that do - what Tools › Misc ›
 * Sync Users and the guided setup's "sync users" card do. New users are synced as they register (unless
 * gh_disable_user_sync is on), so this is for the users a site had before the plugin was installed.
 *
 * A site with up to one batch of users (Background\Sync_Users::BATCH_LIMIT) is synced straight away. A bigger one
 * gets the same Sync_Users_Last_Id background task the Tools page adds, which runs on WordPress cron.
 */
class Sync_Users extends Ability {

	protected const NAME     = 'groundhogg/sync-users';
	protected const CATEGORY = 'groundhogg-contacts';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = false;
	protected const IDEMPOTENT  = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'Sync Users', 'groundhogg' ),
			'description' => __( 'Create a contact for every WordPress user that doesn\'t have one yet, and link existing contacts to their users by email address. New users become contacts automatically when they register, so this is for the users a site already had. A small site is synced straight away; a big one is synced in the background on WordPress cron. Whether all user meta is copied too depends on the sync_user_meta setting.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'users'      => [
						'type'        => 'integer',
						'description' => __( 'How many WordPress users the site has.', 'groundhogg' ),
					],
					'synced'     => [
						'type'        => 'integer',
						'description' => __( 'How many were synced now. 0 when the sync runs in the background.', 'groundhogg' ),
					],
					'background' => [
						'type'        => 'boolean',
						'description' => __( 'Whether the sync runs in the background. It needs WordPress cron, see groundhogg/get-cron-status.', 'groundhogg' ),
					],
				],
			],
		];
	}

	/**
	 * Making contacts from users needs both
	 *
	 * @param mixed $input
	 *
	 * @return bool
	 */
	public function can_execute( $input = null ) {
		return current_user_can( 'edit_users' ) && current_user_can( 'add_contacts' );
	}

	public function __invoke( $input ) {

		$users = (int) count_users()['total_users'];

		if ( $users > Sync_Users_Task::BATCH_LIMIT ) {

			$added = Background_Tasks::add( new Sync_Users_Last_Id() );

			if ( is_wp_error( $added ) ) {
				return $added;
			}

			return [
				'users'      => $users,
				'synced'     => 0,
				'background' => true,
			];
		}

		$sync_meta = is_option_enabled( 'gh_sync_user_meta' );
		$synced    = 0;

		foreach ( get_users( [ 'number' => Sync_Users_Task::BATCH_LIMIT, 'orderby' => 'ID' ] ) as $user ) {
			if ( create_contact_from_user( $user, $sync_meta ) ) {
				$synced ++;
			}
		}

		return [
			'users'      => $users,
			'synced'     => $synced,
			'background' => false,
		];
	}
}
