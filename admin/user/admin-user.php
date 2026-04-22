<?php

namespace Groundhogg\Admin\User;

use Groundhogg\Main_Roles;
use WP_User;
use function Groundhogg\andList;
use function Groundhogg\bold_it;
use function Groundhogg\get_owners;
use function Groundhogg\get_post_var;
use function Groundhogg\get_team;
use function Groundhogg\get_team_ids;
use function Groundhogg\get_valid_contact_tabs;
use function Groundhogg\html;
use function Groundhogg\kses_e;
use function Groundhogg\white_labeled_name;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

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
	 * @param $profile_user WP_User
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
        <h2 id="groundhogg-options"><?php echo esc_html( white_labeled_name() ); ?></h2>
        <table class="form-table">
			<?php if ( user_can( $profile_user, 'view_reports' ) ): ?>
                <tr>
                    <th><?php esc_html_e( 'Email performance reports', 'groundhogg' ); ?></th>
                    <td>
						<?php

						html( 'div', [
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
                        <p class="description"><?php esc_html_e( 'Get performance reports sent directly to your inbox.', 'groundhogg' ); ?></p>
                    </td>
                </tr>
			<?php endif; ?>
	        <?php if ( Main_Roles::is_sales_manager( $profile_user ) ):
                if ( current_user_can( 'delete_users' ) ) : ?>
                <tr>
                    <th><?php esc_html_e( 'Sales Team', 'groundhogg' ); ?></th>
                    <td>
				        <?php

                        wp_enqueue_style( 'groundhogg-admin-element' );

                        $owners = get_owners();
                        $reps = array_filter( $owners, fn( $user ) => ! user_can( $user, 'view_others_contacts' ) );
                        $rep_options = [];
                        foreach ( $reps as $rep ) {
	                        $rep_options[$rep->ID] = sprintf( '%s (%s)', $rep->display_name, $rep->user_email );
                        }

                        $team = get_team_ids( $profile_user->ID );

				        html( 'div', [ 'style' => [ 'max-width' => '700px'] ], html()->select2( [
					        'name'              => 'sales_team[]',
					        'id'                => 'sales_team',
                            'multiple'          => true,
                            'options'           => $rep_options,
                            'selected'          => $team,
                            'placeholder'       => 'Assign reps to this manager.'
                        ] ) );

				        ?>
                        <p class="description"><?php esc_html_e( 'Create a sales team by assigning sales representatives to this manager. If no team is assigned this user will see all contacts.', 'groundhogg' ); ?></p>
                    </td>
                </tr>
            <?php else: ?>
                    <tr>
                        <th><?php esc_html_e( 'Sales Team', 'groundhogg' ); ?></th>
                        <td>
			                <?php

                            $team      = get_team( $profile_user->ID );
                            $team      = array_filter( $team, fn( $user ) => $user->ID !== $profile_user->ID ); // remove the current user from the team list
                            $team_list = andList( array_map( fn( $user ) => bold_it( esc_html( $user->display_name ) ), $team ) );

                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- we're escaping earlier
                            echo $team_list;

			                ?>
                            <p class="description"><?php esc_html_e( 'Your team can only be edited by an administrator.', 'groundhogg' ); ?></p>
                        </td>
                    </tr>
	        <?php endif;
            endif; ?>
            <tr>
                <th><?php esc_html_e( 'Default Contact Tab', 'groundhogg' ); ?></th>
                <td>
					<?php
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated HTML
                    echo html()->dropdown( [
						'name'        => 'gh_default_contact_tab',
	                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped downstream
						'options'     => get_valid_contact_tabs(),
						'option_none' => false,
	                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped downstream
						'selected'    => $profile_user->gh_default_contact_tab
					] ); ?>
                    <p class="description"><?php echo esc_html_x( 'Which tab should be selected by default when opening a contact record.', 'settings', 'groundhogg' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Email Signature', 'groundhogg' ); ?></th>
                <td>
                    <div style="max-width: 800px">

						<?php

						$signature = get_user_meta( $profile_user->ID, 'signature', true );

						wp_editor( $signature, 'signature', [
							'textarea_rows' => 10
						] ); ?>
                    </div>
                    <p class="description"><?php kses_e( __( 'Accepts HTML. The signature can be merged using the <code>{owner_signature}</code> in any email.', 'groundhogg' ) ); ?></p>
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

        if ( current_user_can(  'delete_users' ) && Main_Roles::is_sales_manager( $user_id ) ) {

            $sales_team = wp_parse_id_list( get_post_var( 'sales_team' ) );
            if ( empty( $sales_team ) ){
                delete_user_meta( $user_id, 'gh_sales_team_ids' );
            } else {
	            update_user_meta( $user_id, 'gh_sales_team_ids', $sales_team );
            }
        }
	}

}
