<?php
/**
 * Email Step Funnel
 *
 * Html for the email funnel stp in the Funel builder
 *
 * @package     groundhogg
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
    $dropdown_args[ 'class' ] = 'hidden';

    $previously_selected = intval( wpfn_get_step_meta( $step_id, 'email_id', true ) );

    if ( $previously_selected )
        $dropdown_args['selected'] = $previously_selected;

    ?>

    <table class="form-table">
        <tbody>
            <tr>
                <th><?php echo esc_html__( 'Select an email to send:', 'groundhogg' ); ?></th>
                <td>
                    <?php wpfn_dropdown_emails( $dropdown_args ); ?>
                    <p><a id="<?php echo wpfn_prefix_step_meta( $step_id, 'edit_email' ); ?>" target="_blank" href="<?php echo admin_url( 'admin.php?page=gh_emails&action=edit&email=' . $previously_selected );?>"><?php esc_html_e( 'Edit Email', 'groundhogg' );?></a> | <a target="_blank" href="<?php echo admin_url( 'admin.php?page=gh_emails&action=add' );?>"><?php esc_html_e( 'Create New Email', 'groundhogg' );?></a></p>
                    <script>jQuery(function($){$('#<?php echo $email_dropdown_id;?>').change(function(){$('#<?php echo wpfn_prefix_step_meta( $step_id, 'edit_email' ); ?>').attr('href', '<?php echo admin_url( 'admin.php?page=gh_emails&action=edit&email=');?>' + $(this).val())})});</script>
                </td>
            </tr>
        </tbody>
    </table>

    <?php
}

add_action( 'wpfn_get_step_settings_send_email', 'wpfn_send_email_funnel_step_html' );

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


function wpfn_send_email_reporting( $step_id, $start, $end )
{

    $funnel = wpfn_get_step_funnel( $step_id );
    $email = wpfn_get_step_meta( $step_id, 'email_id', true );

    global $wpdb;

    $table = $wpdb->prefix . WPFN_ACTIVITY;

    $opens = $wpdb->get_var( $wpdb->prepare(
        "SELECT count(*) FROM $table
        WHERE funnel_id = %d AND step_id = %d AND object_id = %d AND %d <= timestamp AND timestamp <= %d AND activity_type = %s",
        $funnel, $step_id, $email, $start, $end, 'email_opened'
    ) );

    $clicks = $wpdb->get_var( $wpdb->prepare(
        "SELECT count(*) FROM $table
        WHERE funnel_id = %d AND step_id = %d AND object_id = %d AND %d <= timestamp AND timestamp <= %d AND activity_type = %s",
        $funnel, $step_id, $email, $start, $end, 'email_link_click'
    ) );

    ?>
    <hr>
    <p class="report">
        <span class="opens"><?php _e( 'Opens: '); ?><strong><?php echo $opens; ?></strong></span> | <span class="clicks"><?php _e( 'Clicks: ' ); ?><strong><?php echo $clicks; ?></strong></span> | <span class="ctr"><?php _e( 'CTR: '); ?><strong><?php echo round( ( $clicks / ( ( $opens > 0 )? $opens : 1 ) * 100 ), 2 ); ?></strong>%</span>
    </p>
    <?php

}

add_action( 'wpfn_get_step_report_send_email', 'wpfn_send_email_reporting', 10, 3 );