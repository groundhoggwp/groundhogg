<?php
/**
 * Apply Note Funnel Step
 *
 * Html for the apply note funnel step in the Funnel builder
 *
 * @package     groundhogg
 * @subpackage  Includes/Funnels/Steps
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

function wpgh_notification_funnel_step_html( $step_id )
{
    $note = wpgh_get_step_meta( $step_id, 'note_text', true );
    $email = wpgh_get_step_meta( $step_id, 'send_to', true );
    $subject = wpgh_get_step_meta( $step_id, 'subject', true );

    if ( ! $note )
        $note = __( "Follow up with {first} {last} tomorrow.", 'groundhogg' );

    if ( ! $email )
        $email = get_bloginfo( 'admin_email' );

    if ( ! $subject )
        $title = __( "Admin Notification for {first}", 'groundhogg' );

    ?>

    <table class="form-table">
        <tbody>
        <tr>
            <th><?php echo esc_html__( 'Send to', 'groundhogg' ); ?></th>
            <td><input type="text" class="regular-text" id="<?php echo wpgh_prefix_step_meta( $step_id, 'send_to'); ?>" name="<?php echo wpgh_prefix_step_meta( $step_id, 'send_to'); ?>" value="<?php echo $email; ?>" >
            <p class="description"><?php _e( 'Use any email address or the {owner_email} replacement code.' ) ?></p></td>
        </tr>
        <tr>
            <th><?php echo esc_html__( 'Subject', 'groundhogg' ); ?></th>
            <td><input type="text" class="regular-text" id="<?php echo wpgh_prefix_step_meta( $step_id, 'subject'); ?>" name="<?php echo wpgh_prefix_step_meta( $step_id, 'subject'); ?>" value="<?php echo $subject; ?>" >
            <p class="description"><?php _e( 'Accepts replacement codes.' ) ?></p></td>
        </tr>
        <tr>
            <th><?php echo esc_html__( 'Notification', 'groundhogg' ); ?></th>
            <td><textarea cols="64" rows="4" id="<?php echo wpgh_prefix_step_meta( $step_id, 'note_text'); ?>" name="<?php echo wpgh_prefix_step_meta( $step_id, 'note_text'); ?>"><?php echo $note; ?></textarea>
            <p class="description"><?php _e( 'Use any valid replacement codes.' ) ?></p></td>
        </tr>
        </tbody>
    </table>

    <?php
}

add_action( 'wpgh_get_step_settings_notification', 'wpgh_notification_funnel_step_html' );

function wpgh_save_notification_step( $step_id )
{
    if ( isset( $_POST[ wpgh_prefix_step_meta( $step_id, 'send_to') ] ) ){
        $send_to = sanitize_text_field( $_POST[ wpgh_prefix_step_meta( $step_id, 'send_to') ] );

        if ( strpos( $send_to, ',' ) !== false ){
            $emails = array_map( 'trim', explode( ',', $send_to ) );

            $sanitized_emails = array();

            foreach ( $emails as $email ){
                $sanitized_emails[] = ( $email === '{owner_email}' )? '{owner_email}' : sanitize_email( $email );
            }

            $send_to = implode( ',', $sanitized_emails );

        } else {
            $send_to = ( $send_to === '{owner_email}' )? '{owner_email}' : sanitize_email( $send_to );
        }


        wpgh_update_step_meta( $step_id, 'send_to', $send_to );
    }

    if ( isset( $_POST[ wpgh_prefix_step_meta( $step_id, 'subject') ] ) ){
        wpgh_update_step_meta( $step_id, 'subject', sanitize_text_field( $_POST[ wpgh_prefix_step_meta( $step_id, 'subject') ] ) );
    }
    if ( isset( $_POST[ wpgh_prefix_step_meta( $step_id, 'note_text') ] ) ){
        wpgh_update_step_meta( $step_id, 'note_text', sanitize_textarea_field( $_POST[ wpgh_prefix_step_meta( $step_id, 'note_text') ] ) );
    }
}

add_action( 'wpgh_save_step_notification', 'wpgh_save_notification_step' );