<?php

namespace Groundhogg\Admin\User;

use function Groundhogg\get_post_var;
use function Groundhogg\get_valid_contact_tabs;
use function Groundhogg\html;
use function Groundhogg\white_labeled_name;

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

		// Do not show for non-relevant users...
		if ( ! user_can( $profile_user, 'view_contacts' ) ) {
			return;
		}

//		add_filter( 'mce_css', function ( $mce_css ) {
//			return $mce_css . ', ' . GROUNDHOGG_ASSETS_URL . 'css/admin/email-wysiwyg-style.css';
//		} );

		?>
        <h2 id="groundhogg-options"><?php _e( white_labeled_name() ); ?></h2>
        <table class="form-table">
			<?php if ( user_can( $profile_user, 'view_reports' ) ): ?>
                <tr>
                    <th><?php _e( 'Email performance reports', 'groundhogg' ); ?></th>
                    <td>
						<?php

						echo html()->e( 'div', [
							'class' => 'display-flex column gap-10'
						], [
							html()->checkbox( [
								'label'   => 'Next-day broadcast results.',
								'name'    => 'gh_broadcast_results',
								'checked' => get_user_meta( $profile_user->ID, 'gh_broadcast_results', true )
							] ),
							html()->checkbox( [
								'label'   => 'Monthly & weekly overview.',
								'name'    => 'gh_weekly_overview',
								'checked' => get_user_meta( $profile_user->ID, 'gh_weekly_overview', true )
							] )
						] );

						?>
                        <p class="description"><?php _e( 'Get performance reports sent directly to your inbox.', 'groundhogg' ); ?></p>
                    </td>
                </tr>
			<?php endif; ?>
            <tr>
                <th><?php _e( 'Default Contact Tab', 'groundhogg' ); ?></th>
                <td>
					<?php echo html()->dropdown( [
						'name'        => 'gh_default_contact_tab',
						'options'     => get_valid_contact_tabs(),
						'option_none' => false,
						'selected'    => $profile_user->gh_default_contact_tab
					] ); ?>
                    <p class="description"><?php _ex( 'Which tab should be selected by default when opening a contact record.', 'settings', 'groundhogg' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php _e( 'Email Signature', 'groundhogg' ); ?></th>
                <td>
                    <div style="max-width: 800px">

						<?php

						$signature = get_user_meta( $profile_user->ID, 'signature', true );

						wp_editor( $signature, 'signature', [
							'textarea_rows' => 10
						] ); ?>
                    </div>
                    <p class="description"><?php _e( 'Accepts HTML. The signature can be merged using the <code>{owner_signature}</code> in any email.', 'groundhogg' ); ?></p>
                </td>
            </tr>
        </table>
		<?php

		do_action( 'groundhogg/user_profile', $profile_user );
	}

	/**
     * Save the Groundhogg user fields...
     *
	 * @param $user_id
	 */
	public function save_fields( $user_id ) {

		update_user_meta( $user_id, 'signature', wp_kses_post( get_post_var( 'signature' ) ) );
		update_user_meta( $user_id, 'gh_default_contact_tab', sanitize_text_field( get_post_var( 'gh_default_contact_tab' ) ) );

		if ( user_can( $user_id, 'view_reports' ) ) {
			update_user_meta( $user_id, 'gh_broadcast_results', boolval( get_post_var( 'gh_broadcast_results' ) ) );
			update_user_meta( $user_id, 'gh_weekly_overview', boolval( get_post_var( 'gh_weekly_overview' ) ) );
		} else {
			delete_user_meta( $user_id, 'gh_broadcast_results' );
			delete_user_meta( $user_id, 'gh_weekly_overview' );
		}
	}

}
