<?php

namespace Groundhogg;

use Groundhogg\Steps\Actions\Send_Email;

class Main_Updater extends Old_Updater {

	/**
	 * New class for managing the *new* update format
	 *
	 * @return array[]
	 */
	protected function get_updates() {
		return [
			'2.7.11.3' => [
				'automatic'   => true,
				'description' => __( 'Refresh permalinks so that the new email archive feature works.', 'groundhogg' ),
				'callback'    => function () {
					install_custom_rewrites();

					$steps = get_db( 'steps' )->query( [
						'step_type'   => Send_Email::TYPE,
						'step_status' => 'active'
					] );

					array_map_to_step( $steps );

					foreach ( $steps as $step ) {
						$email_id = absint( $step->get_meta( 'email_id' ) );

						get_db( 'events' )->update( [
							'funnel_id'  => $step->get_funnel_id(),
							'step_id'    => $step->get_id(),
							'event_type' => Event::FUNNEL,
							'status'     => Event::COMPLETE,
						], [ 'email_id' => $email_id ] );
					}
				}
			],
			'2.7.11.7' => [
				'automatic'   => true,
				'description' => __( 'Refresh permalinks for shortened tracking URL structure.', 'groundhogg' ),
				'callback'    => function () {
					install_custom_rewrites();
				}
			],
			'3.0'      => [
				'automatic'   => true,
				'description' => __( 'Update the emails table.', 'groundhogg' ),
				'callback'    => function () {
					// Update the emails table to add `plain` and `type` as a column
					get_db( 'emails' )->create_table();
				}
			],
			'3.0.1'    => [
				'automatic'   => true,
				'description' => __( 'Disable the Advanced Email Editor automatically.', 'groundhogg' ),
				'callback'    => function () {
					delete_option( 'gh_use_advanced_email_editor' );
				}
			],
			'3.1'    => [
				'automatic'   => true,
				'description' => __( 'Enable tag mapping.', 'groundhogg' ),
				'callback'    => function () {
					update_option( 'gh_enable_tag_mapping', 'on' );
				}
			]
		];
	}

	/**
	 * Wrapper for new format
	 *
	 * @return array[]|string[]
	 */
	protected function get_available_updates() {
		return array_merge( parent::get_available_updates(), $this->get_updates() );
	}

}
