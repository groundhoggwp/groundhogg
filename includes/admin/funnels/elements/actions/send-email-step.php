<?php
/**
 * Email Step Funnel
 *
 * Html for the email funnel stp in the Funel builder
 *
 * @package     wp-funnels
 * @subpackage  Includes/Funnels/Steps
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

/**
 * Output the HTML fpr the email step in the funnel builder
 *
 * @param $step_id
 */
function wpfn_send_email_funnel_step_html( $step_id )
{

    $email_dropdown_id = $step_id . '_email_id';
    $email_dropdown_name = $step_id . '_email_id';

    $dropdown_args = array();
    $dropdown_args[ 'id' ] = $email_dropdown_id;
    $dropdown_args[ 'name' ] = $email_dropdown_name;

    $previously_selected = intval( wpfn_get_step_meta( $step_id, 'email_id', true ) );

    if ( $previously_selected )
        $dropdown_args['selected'] = $previously_selected;

    ?>

    <table class="form-table">
        <tbody>
            <tr>
                <th><?php echo esc_html__( 'Select an email to send:', 'wp-funnels' ); ?></th>
                <td>
                    <?php wpfn_dropdown_emails( $dropdown_args ); ?>
                    <p><a target="_blank" href="<?php echo admin_url( 'admin.php?page=emails&ID=' . $previously_selected );?>"><?php echo esc_html__( 'Edit Email', 'wp-funnels' );?></a> | <a target="_blank" href="<?php echo admin_url( 'admin.php?page=add_email' );?>"><?php echo esc_html__( 'Create New Email', 'wp-funnels' );?></a></p>
                </td>
            </tr>
        </tbody>
    </table>

    <?php
}

add_action( 'wpfn_get_step_settings_send_email', 'wpfn_send_email_funnel_step_html' );

function wpfn_send_email_icon_html()
{
    ?>
    <div class="dashicons dashicons-email-alt"></div><p><?php echo esc_html__( 'Send Email', 'wp-funnels' ); ?></p>
    <?php
}

add_action( 'wpfn_action_element_icon_html_send_email', 'wpfn_send_email_icon_html' );

/**
 * Save the email type step
 *
 * @param $step_id int ID of the step we're saving.
 */
function wpfn_save_send_email_step( $step_id )
{
    //no need to check the validation as it's already been done buy the main funnel.
    $email_id = intval( $_POST[ wpfn_prefix_step_meta( $step_id, 'email_id' ) ] );
    wpfn_update_step_meta( $step_id, 'email_id', $email_id );
}

add_action( 'wpfn_save_step_send_email', 'wpfn_save_send_email_step' );
