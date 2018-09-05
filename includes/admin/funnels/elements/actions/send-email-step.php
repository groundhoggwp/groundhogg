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
function wpgh_send_email_funnel_step_html( $step_id )
{

    $email_dropdown_id = $step_id . '_email_id';
    $email_dropdown_name = $step_id . '_email_id';

    $dropdown_args = array();
    $dropdown_args[ 'id' ] = $email_dropdown_id;
    $dropdown_args[ 'name' ] = $email_dropdown_name;
    $dropdown_args[ 'class' ] = 'hidden';

    $previously_selected = intval( wpgh_get_step_meta( $step_id, 'email_id', true ) );

    if ( $previously_selected )
        $dropdown_args['selected'] = $previously_selected;

    $return_funnel = wpgh_get_step_funnel( $step_id );

    $editPath = admin_url( 'admin.php?page=gh_emails&action=edit&return_funnel=' . $return_funnel . '&return_step=' . $step_id . '&email=' );

    ?>

    <table class="form-table">
        <tbody>
            <tr>
                <th><?php echo esc_html__( 'Select an email to send:', 'groundhogg' ); ?></th>
                <td>
                    <?php wpgh_dropdown_emails( $dropdown_args ); ?>
                    <div class="row-actions">
                        <a class="editinline" id="<?php echo wpgh_prefix_step_meta( $step_id, 'edit_email' ); ?>" target="_blank" href="<?php echo $editPath . $previously_selected;?>"><?php esc_html_e( 'Edit Email', 'groundhogg' );?></a> | <a href="<?php echo admin_url( 'admin.php?page=gh_emails&action=add&step=' . $step_id );?>"><?php esc_html_e( 'Create New Email', 'groundhogg' );?></a>
                    </div>
                    <script>jQuery(function($){$('#<?php echo $email_dropdown_id;?>').change(function(){$('#<?php echo wpgh_prefix_step_meta( $step_id, 'edit_email' ); ?>').attr('href', '<?php echo $editPath ?>' + $(this).val())})});</script>
                </td>
            </tr>
        </tbody>
    </table>

    <?php
}

add_action( 'wpgh_get_step_settings_send_email', 'wpgh_send_email_funnel_step_html' );

/**
 * Save the email type step
 *
 * @param $step_id int ID of the step we're saving.
 */
function wpgh_save_send_email_step( $step_id )
{
    //no need to check the validation as it's already been done buy the main funnel.
    $email_id = intval( $_POST[ wpgh_prefix_step_meta( $step_id, 'email_id' ) ] );
    wpgh_update_step_meta( $step_id, 'email_id', $email_id );
}

add_action( 'wpgh_save_step_send_email', 'wpgh_save_send_email_step' );


function wpgh_send_email_reporting( $step_id, $start, $end )
{

    $funnel = wpgh_get_step_funnel( $step_id );
    $email = wpgh_get_step_meta( $step_id, 'email_id', true );

    global $wpdb;

    $table = $wpdb->prefix . WPGH_ACTIVITY;

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

add_action( 'wpgh_get_step_report_send_email', 'wpgh_send_email_reporting', 10, 3 );