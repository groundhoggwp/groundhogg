<?php

namespace Groundhogg\Admin\User;

use function Groundhogg\get_post_var;

class Admin_User {

	public function __construct() {
		add_action( 'edit_user_profile', [ $this, 'show_fields' ] );
		add_action( 'show_user_profile', [ $this, 'show_fields' ] );

		add_action( 'edit_user_profile_update', [ $this, 'save_fields' ] );
		add_action( 'personal_options_update', [ $this, 'save_fields' ] );
	}

	/**
	 * Show signature area
	 *
	 * @param $profile_user \WP_User
	 */
	public function show_fields( $profile_user ) {

		// Do nto show for non relevant users...
		if ( ! user_can( $profile_user, 'view_contacts' ) ) {
			return;
		}

		?>
		<h2><?php _e( 'Groundhogg', 'groundhogg' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php _e( 'Signature', 'groundhogg' ); ?></th>
				<td>
					<textarea name="signature" rows="4"
					          cols="20"><?php esc_html_e( $profile_user->signature ); ?></textarea>
					<p class="description"><?php _e( 'Accepts HTML. The signature can be merged using the <code>{owner_signature}</code> in any email.', 'groundhogg' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * @param $user_id
	 *
	 * Save the Groundhogg user fields...
	 */
	public function save_fields( $user_id ) {

		$signature = wp_kses_post( get_post_var( 'signature' ) );
		update_user_meta( $user_id, 'signature', $signature );

	}

}
