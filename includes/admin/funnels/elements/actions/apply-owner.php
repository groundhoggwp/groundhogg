<?php
/**
 * Email Step Funnel
 *
 * Html for the owner funnel stp in the Funel builder
 *
 * @package     groundhogg
 * @subpackage  Includes/Funnels/Steps
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

/**
 * Output the HTML fpr the owner step in the funnel builder
 *
 * @param $step_id
 */
function wpgh_apply_owner_funnel_step_html( $step_id )
{

    $owner_dropdown_id = wpgh_prefix_step_meta( $step_id, 'owner_id' );
    $owner_dropdown_name = wpgh_prefix_step_meta( $step_id, 'owner_id' );

    $owner = wpgh_get_step_meta( $step_id, 'owner_id', true ); ?>
    <table class="form-table">
        <tbody>
            <tr>
                <th><?php echo esc_html__( 'Select an owner to send:', 'groundhogg' ); ?></th>
                <td>
                    <?php $args = array( 'show_option_none' => __( 'Select an owner' ), 'id' => $owner_dropdown_id, 'name' => $owner_dropdown_name, 'role' => 'administrator', 'selected' => $owner ); ?>
                    <?php wp_dropdown_users( $args ) ?>
                </td>
            </tr>
        </tbody>
    </table>

    <?php
}

add_action( 'wpgh_get_step_settings_apply_owner', 'wpgh_apply_owner_funnel_step_html' );

/**
 * Save the owner type step
 *
 * @param $step_id int ID of the step we're saving.
 */
function wpgh_save_apply_owner_step( $step_id )
{
    //no need to check the validation as it's already been done buy the main funnel.
    if ( isset( $_POST[ wpgh_prefix_step_meta( $step_id, 'owner_id' ) ] ) ){
        $owner_id = intval( $_POST[ wpgh_prefix_step_meta( $step_id, 'owner_id' ) ] );
        wpgh_update_step_meta( $step_id, 'owner_id', $owner_id );
    }
}

add_action( 'wpgh_save_step_apply_owner', 'wpgh_save_apply_owner_step' );