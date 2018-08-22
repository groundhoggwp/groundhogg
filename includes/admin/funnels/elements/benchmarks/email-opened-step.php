<?php
/**
 * Email Opened Funnel Step
 *
 * Html for the page visited funnel step in the Funnel builder
 *
 * @package     groundhogg
 * @subpackage  Includes/Funnels/Steps
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

function wpfn_email_opened_funnel_step_html( $step_id )
{
    global $wpdb;

    $table = $wpdb->prefix . WPFN_FUNNELSTEPS;

    $email_steps = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM $table
        WHERE funnel_id = %d AND funnelstep_order < %d AND funnelstep_type = %s
        ORDER BY funnelstep_order DESC",
        wpfn_get_step_funnel( $step_id ), wpfn_get_step_order( $step_id ), 'send_email'
    ), ARRAY_A );

    $selected_emails = wpfn_get_step_meta( $step_id, 'emails', true );

    if ( ! $selected_emails )
        $selected_emails = array();

	?>

    <table class="form-table">
        <tbody>
        <tr>
            <th><?php esc_html_e( 'Email being opened:', 'groundhogg' ); ?></th>
            <td>
                <select id="<?php wpfn_prefix_step_meta_e( $step_id, 'emails' ); ?>" name="<?php wpfn_prefix_step_meta_e( $step_id, 'emails[]' ); ?>" multiple>
                    <?php foreach ( $email_steps as $step ):

                        ?><option value="<?php echo $step['ID']; ?>" <?php if ( in_array( $step['ID'], $selected_emails ) ) echo 'selected="selected"'; ?> ><?php esc_html_e( $step[ 'funnelstep_title' ] ); ?></option><?php

                     endforeach; ?>
                </select>
                <script>
                    jQuery(function($){$( "#<?php wpfn_prefix_step_meta_e( $step_id, 'emails' ); ?>" ).select2() })
                </script>
            </td>
        </tr>
        </tbody>
    </table>
	<?php
}

add_action( 'wpfn_get_step_settings_email_opened', 'wpfn_email_opened_funnel_step_html' );

function wpfn_save_email_opened_step( $step_id )
{
    if ( isset( $_POST[ wpfn_prefix_step_meta( $step_id, 'emails' ) ] ) ){
        wpfn_update_step_meta( $step_id, 'emails', $_POST[ wpfn_prefix_step_meta( $step_id, 'emails' ) ] );
    }
}

add_action( 'wpfn_save_step_email_opened', 'wpfn_save_email_opened_step' );