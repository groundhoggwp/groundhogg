<?php
/**
 * Contact Record
 *
 * Allow the user to edit the contact details and contact fields
 *
 * @package     groundhogg
 * @subpackage  Includes/Contacts
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

$id = intval( $_GET[ 'contact' ] );

$contact = new WPGH_Contact( $id );

if ( ! $contact->get_email() )
{
    wp_die( __( 'This contact no has been deleted.', 'groundhogg' ) );
}

wp_enqueue_script( 'contact-editor', WPGH_ASSETS_FOLDER . '/js/admin/contact-editor.js' )
?>
<span class="hidden" id="new-title"><?php echo $contact->get_full(); ?> &lsaquo; </span>
<script>
    document.title = jQuery( '#new-title' ).text() + document.title;
</script>
<form method="post" class="">
    <?php wp_nonce_field( 'edit' ); ?>

    <!-- GENERAL NAME INFO -->
    <h2><?php _e( 'Name' ) ?></h2>
    <table class="form-table">
        <tbody>
        <tr>
            <th><label for="first_name"><?php echo __( 'First Name', 'groundhogg' )?></label></th>
            <td><?php echo wpgh_admin_text_input_field( 'first_name', 'first_name', $contact->get_first() );?></td>
        </tr>
        <tr>
            <th><label for="last_name"><?php echo __( 'Last Name', 'groundhogg' )?></label></th>
            <td><?php echo wpgh_admin_text_input_field( 'last_name', 'last_name', $contact->get_last() );?></td>
        </tr>
        <?php do_action( 'wpgh_contact_edit_name', $id ); ?>
        </tbody>
    </table>

    <!-- GENERAL CONTACT INFO -->
    <h2><?php _e( 'Contact Info' ); ?></h2>
    <table class="form-table">
        <tbody>
        <tr>
            <th><label for="email"><?php echo __( 'Email', 'groundhogg' )?></label></th>
            <td><?php echo wpgh_admin_email_input_field( 'email', 'email', $contact->get_email() );?><label>
                <p class="submit"><?php echo '<b>' . __('Email Status', 'groundhogg') . ': </b>' . wpgh_get_optin_status_text( $contact->get_optin_status() ); ?></p>
                <?php if ( $contact->get_optin_status() !== WPGH_UNSUBSCRIBED ): ?>
                    <input type="checkbox" name="unsubscribe" value="1"><?php _e( 'Mark as unsubscribed.' )?></label>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th><label for="primary_phone"><?php echo __( 'Primary Phone', 'groundhogg' )?></label></th>
            <td><?php echo wpgh_admin_text_input_field( 'primary_phone', 'primary_phone', $contact->get_phone() );?></td>
        </tr>
        <tr>
            <th><label for="phone_extension"><?php echo __( 'Phone Extension', 'groundhogg' )?></label></th>
            <td><?php echo wpgh_admin_text_input_field( 'primary_phone_extension', 'primary_phone_extension', $contact->get_phone_extension() );?></td>
        </tr>
        <?php do_action( 'wpgh_contact_edit_contact_info', $id ); ?>
        </tbody>
    </table>

    <!-- MARKETING COMPLIANCE INFORMATION -->
    <h2><?php _e( 'Compliance' ); ?></h2>
    <table class="form-table">
        <tbody>
            <tr>
                <th><?php _e( 'Agreed To Terms' ); ?></th>
                <td><?php echo ( wpgh_get_contact_meta( $contact->get_id(), 'terms_agreement', true ) === 'yes' ) ? sprintf( "%s: %s",  __( 'Agreed' ), wpgh_get_contact_meta( $id, 'terms_agreement_date' , true ) ): '&#x2014;'; ?></td>
            </tr>
            <?php if ( wpgh_is_gdpr() ): ?>
                <tr>
                    <th><?php _e( 'GDPR Consent' ); ?></th>
                    <td><?php echo ( wpgh_get_contact_meta( $contact->get_id(), 'gdpr_consent', true ) === 'yes' ) ? sprintf( "%s: %s",  __( 'Agreed' ), wpgh_get_contact_meta( $id, 'gdpr_consent_date' , true ) ) : '&#x2014;'; ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- SEGMENTATION AND LEADSOURCE -->
    <h2><?php _e( 'Segmentation' ); ?></h2>
    <table class="form-table">
        <tbody>
        <tr>
            <th><?php _e( 'Owner', 'groundhogg' ); ?></th>
            <td><?php $args = array( 'show_option_none' => __( 'Select an owner' ), 'id' => 'owner', 'name' => 'owner', 'role' => 'administrator', 'class' => 'cowner', 'selected' => $contact->get_owner() ); ?>
                <?php wp_dropdown_users( $args ) ?></td>
        </tr>
        <tr>
            <th><?php _e( 'Source Page', 'groundhogg' ); ?></th>
            <td><?php $source = wpgh_get_contact_meta( $id, 'source_page', true );?>
            <input type="text" class="regular-text" name="page_source" id="page_source" title="<?php esc_attr_e( 'Page Source' );?>" value="<?php echo esc_url( $source ); ?>"><span class="row-actions"><a style="text-decoration: none" target="_blank" href="<?php echo esc_url( $source ); ?>"><span class="dashicons dashicons-external"></span></a></span>
            <p class="description"><?php _e( "This is the page which the contact first submitted a form.", 'groundhogg' ); ?></p></td>
        </tr>
        <tr>
            <th><?php _e( 'Lead Source', 'groundhogg' ); ?></th>
            <td><?php $source = wpgh_get_contact_meta( $id, 'leadsource', true );?>
                <input type="text" class="regular-text" name="leadsource" id="leadsource" title="<?php esc_attr_e( 'Lead Source' );?>" value="<?php echo esc_url( $source ); ?>"><span class="row-actions"><a style="text-decoration: none" target="_blank" href="<?php echo esc_url( $source ); ?>"><span class="dashicons dashicons-external"></span></a></span>
                <p class="description"><?php _e( "This is where the contact originated from.", 'groundhogg' ); ?></p></td>
        </tr>
        <tr>
            <th><label for="tags"><?php echo __( 'Tags', 'groundhogg' )?></label></th>
            <td><?php wpgh_dropdown_tags( array( 'width' => '400px', 'class' => 'hidden', 'selected' => $contact->get_tags() ) );?></td>
        </tr>
        <?php do_action( 'wpgh_contact_edit_tags', $id ); ?>
        </tbody>
    </table>

    <!-- NOTES -->
    <h2><?php _e( 'Notes' ); ?></h2>
    <table>
        <tbody>
        <tr>
            <?php $notes = wpgh_get_contact_meta( $id, 'notes', 'true' ); ?>
            <td><textarea style="width: 700px" rows="6" name="notes" id="notes" placeholder="<?php esc_attr_e( 'Enter some details about this contact...', 'grounshogg' ); ?>"><?php echo esc_html( $notes ); ?></textarea></td>
        </tr>
        <?php do_action( 'wpgh_contact_edit_notes', $id ); ?>
        </tbody>
    </table>

    <!-- META -->
    <h2><?php _e( 'Custom Meta' ); ?></h2>
    <table class="form-table" >
        <tr>
            <th><label for="edit_meta"><?php _e( 'Edit Meta' ); ?></label></th>
            <td><input type="checkbox" name="edit_meta" id="edit_meta" value="1"></td>
        </tr>
    </table>
    <script>
        jQuery(function($){
            $('#edit_meta').change(function(){
                $('#meta-table').toggleClass( 'hidden' );
            })
        });
    </script>
    <table id='meta-table' class="form-table hidden" >
        <tbody>
        <tr>
            <th>
                <button type="button" class="button-secondary addmeta"><?php _e( 'Add Meta' ); ?></button>
                <div class="hidden">
                    <span class="metakeyplaceholder"><?php esc_attr_e( 'Key' ); ?></span>
                    <span class="metavalueplaceholder"><?php esc_attr_e( 'Value' ); ?></span>
                </div>
            </th>
        </tr>
            <?php
            $meta = wpgh_get_contact_meta( $contact->get_id() );
            foreach ( $meta as $meta_key => $value )
            {
                $value = $value[ 0 ];
                ?>
            <tr id="meta-<?php esc_attr_e( $meta_key )?>">
                <th>
                   <?php esc_html_e( $meta_key ); ?>
                    <p class="description">{_<?php esc_html_e( $meta_key ); ?>}</p>
                </th>
                <td>
                    <input type="text" id="<?php esc_attr_e( $meta_key )?>" name="meta[<?php esc_attr_e( $meta_key ); ?>]" class="regular-text" value="<?php esc_attr_e( $value ); ?>">
                    <span class="row-actions"><span class="delete"><a style="text-decoration: none" href="javascript:void(0)" class="deletemeta"><span class="dashicons dashicons-trash"></span></a></span></span>
                </td>
            </tr>
                <?php
            }
            ?>

        <?php do_action( 'wpgh_contact_edit_meta', $id ); ?>
        </tbody>
    </table>
    <?php do_action( 'wpgh_contact_edit_before_history', $id ); ?>

    <!-- UPCOMING EVENTS -->
    <h2><?php _e( 'Upcoming Events' ); ?></h2>
    <table style="width: 700px" class="wp-list-table widefat fixed striped active-funnels">
        <thead>
            <tr>
               <th><?php _e( 'Funnel', 'grundhogg' ); ?></th>
               <th><?php _e( 'Step', 'grundhogg' ); ?></th>
               <th><?php _e( 'Next Action', 'grundhogg' ); ?></th>
               <th><?php _e( 'Actions', 'grundhogg' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php

            global $wpdb;
            $table = $wpdb->prefix . WPGH_EVENTS;
            $active_events = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE contact_id = %d AND status = %s ORDER BY time ASC LIMIT 20", $id, 'waiting' ) );

            if ( empty( $active_events ) ){
                ?> <tr><td colspan="4"><?php _e( 'This contact is not currently active in any funnels.', 'groundhogg' ) ?></td></tr> <?php
            } else {
                foreach ( $active_events as $active_event ) {
                    ?><tr>
                        <td><?php $funnel_id = intval( $active_event->funnel_id );
                            if ( $funnel_id === WPGH_BROADCAST ) {
                                $funnel_title = __( 'Broadcast Email' );
                            } else {
                                $funnel = wpgh_get_funnel_by_id( $funnel_id );
                                $funnel_title = $funnel->funnel_title;
                            }
                            echo sprintf( "<a href='%s' target='_blank'>%s</a>", admin_url( 'admin.php?page=gh_funnels&action=edit&funnel=' . $funnel_id ) ,$funnel_title);?></td>
                        <td><?php $step_id = intval( $active_event->step_id );
                            if ( $funnel_id === WPGH_BROADCAST ) {
                                $broadcast = wpgh_get_broadcast_by_id( $step_id );
                                $email = wpgh_get_email_by_id( intval( $broadcast['email_id'] ) );
                                $step_title = $email->subject;
                            } else {
                                $step_title = wpgh_get_step_hndle( $step_id );
                            }
                            if ( ! $step_title )
                                echo sprintf( "<strong>%s</strong>", __( '(step deleted)' ) );
                            else
                                echo sprintf( "<a href='%s' target='_blank'>%s</a>", admin_url( 'admin.php?page=gh_funnels&action=edit&funnel=' . $funnel_id . '#' . $step_id ) , $step_title );?></td>
                        <td><?php
                            $p_time = intval( $active_event->time ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
                            $cur_time = (int) current_time( 'timestamp' );
                            $time_diff = $p_time - $cur_time;
                            if ( absint( $time_diff ) > 24 * HOUR_IN_SECONDS ){
                                $time = sprintf( "On %s", date_i18n( 'jS F, Y \@ h:i A', intval( $p_time )  ) );
                            } else {
                                $time = sprintf( "In %s", human_time_diff( $p_time, $cur_time ) );
                            }

                            echo '<abbr title="' . date_i18n( DATE_ISO8601, intval( $p_time ) ) . '">' . $time . '</abbr>'; ?></td>
                        <td><div class="row-actions">
                                <?php $item = (array) $active_event;
                                $run = esc_url( wp_nonce_url( admin_url('admin.php?page=gh_events&event='. $active_event->ID . '&action=execute' ), 'execute' ) );
                                $cancel = esc_url( wp_nonce_url( admin_url('admin.php?page=gh_events&event='. $active_event->ID . '&action=cancel' ), 'cancel' ) ); ?>
                                <span class="run"><a href="<?php echo $run; ?>" class="run"><?php _e( 'Run now', 'groundhogg' ); ?></a></span> |
                                <span class="delete"><a href="<?php echo $cancel; ?>" class="delete"><?php _e( 'Cancel', 'groundhogg' ); ?></a></span>
                            </div></td>
                    </tr><?php }} ?>
        </tbody>
    </table>
    <p class="description"><?php _e( 'Any upcoming funnel steps will show up here. you can choose to cancel them or to run them immediately.', 'groundhogg' ); ?></p>

    <!-- FUNNNEL HISTORY -->
    <h2><?php _e( 'Recent Funnel History' ); ?></h2>
    <table style="width: 700px" class="wp-list-table widefat fixed striped funnels-history">
        <thead>
        <tr>
            <th><?php _e( 'Funnel', 'grundhogg' ); ?></th>
            <th><?php _e( 'Step', 'grundhogg' ); ?></th>
            <th><?php _e( 'Completed', 'grundhogg' ); ?></th>
            <th><?php _e( 'Actions', 'grundhogg' ); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php global $wpdb;
        $table = $wpdb->prefix . WPGH_EVENTS;
        $active_events = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE contact_id = %d AND status = %s ORDER BY time DESC LIMIT 20", $id, 'complete' ) );
        if ( empty( $active_events ) ){
            ?> <tr><td colspan="4"><?php _e( 'This contact has no funnel history.', 'groundhogg' ) ?></td></tr> <?php
        } else {
            foreach ( $active_events as $active_event ) {
                ?><tr>
                    <td>
                        <?php $funnel_id = intval( $active_event->funnel_id );
                            if ( $funnel_id === WPGH_BROADCAST ) {
                                $funnel_title = __( 'Broadcast Email' );
                            } else {
                                $funnel = wpgh_get_funnel_by_id( $funnel_id );
                                $funnel_title = $funnel->funnel_title;
                            }
                        echo sprintf( "<a href='%s' target='_blank'>%s</a>", admin_url( 'admin.php?page=gh_funnels&action=edit&funnel=' . $funnel_id ) ,$funnel_title);?></td>
                    <td><?php
                        $step_id = intval( $active_event->step_id );
                        if ( $funnel_id === WPGH_BROADCAST ) {
                            $broadcast = wpgh_get_broadcast_by_id( $step_id );
                            $email = wpgh_get_email_by_id( intval( $broadcast['email_id'] ) );
                            $step_title = $email->subject;
                        } else {
                            $step_title = wpgh_get_step_hndle( $step_id );
                        }
                        if ( ! $step_title )
                            echo sprintf( "<strong>%s</strong>", __( '(step deleted)' ) );
                        else
                            echo sprintf( "<a href='%s' target='_blank'>%s</a>", admin_url( 'admin.php?page=gh_funnels&action=edit&funnel=' . $funnel_id . '#' . $step_id ) , $step_title );?></td>
                    <td><?php $p_time = intval( $active_event->time ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
                        $cur_time = (int) current_time( 'timestamp' );
                        $time_diff = $p_time - $cur_time;
                        if ( absint( $time_diff ) > 24 * HOUR_IN_SECONDS ){
                            $time = sprintf( "On %s", date_i18n( 'jS F, Y \@ h:i A', intval( $p_time )  ) );
                        } else {
                            $time = sprintf( "%s ago", human_time_diff( $p_time, $cur_time ) );
                        }
                        echo '<abbr title="' . date_i18n( DATE_ISO8601, intval( $p_time ) ) . '">' . $time . '</abbr>';
                        ?></td>
                    <td><div class="row-actions">
                            <?php $item = (array) $active_event;
                            $action = esc_url( wp_nonce_url( admin_url('admin.php?page=gh_events&event='. $active_event->ID . '&action=execute' ), 'execute' ) ); ?>
                            <span class="run"><a href="<?php echo $action; ?>" class="run"><?php _e( 'Run again', 'groundhogg' ); ?></a></span>
                        </div>
                    </td>
                </tr><?php }} ?>
        </tbody>
    </table>
    <p class="description"><?php _e( 'Any previous funnel steps will show up here. You can choose run them again.<br/>
    This report only shows the 20 most recent events, to see more you can see all this contact\'s history in the event queue.', 'groundhogg' ); ?></p>

    <!-- EMAIL HISTORY -->
    <h2><?php _e( 'Recent Email History' ); ?></h2>
    <table style="width: 700px" class="wp-list-table widefat fixed striped funnels-history">
        <thead>
        <tr>
            <th><?php _e( 'Email', 'grundhogg' ); ?></th>
            <th><?php _e( 'Opened', 'grundhogg' ); ?></th>
            <th><?php _e( 'Clicked', 'grundhogg' ); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php global $wpdb;
        $events = $wpdb->prefix . WPGH_EVENTS;
        $steps = $wpdb->prefix . WPGH_FUNNELSTEPS;
        $email_events = $wpdb->get_results( $wpdb->prepare(
                "SELECT e.*,s.funnelstep_type FROM $table e 
                        LEFT JOIN $steps s ON e.step_id = s.ID 
                        WHERE e.contact_id = %d AND e.status = %s AND ( s.funnelstep_type = %s OR e.funnel_id = %d )
                        ORDER BY time DESC LIMIT 20"
                , $id, 'complete', 'send_email', WPGH_BROADCAST ));

        if ( empty( $email_events ) ){
            ?> <tr><td colspan="4"><?php _e( 'This contact has no email history.', 'groundhogg' ) ?></td></tr> <?php
        } else {
            foreach ( $email_events as $email_event ) {
                ?><tr>
                    <td><?php
                        $funnel_id = intval( $email_event->funnel_id );
                        if ( $funnel_id === WPGH_BROADCAST ) {
                            $broadcast = wpgh_get_broadcast_by_id( $email_event->step_id );
                            $email_id = intval( $broadcast['email_id'] );
                        } else {
                            $email_id = wpgh_get_step_meta( $email_event->step_id, 'email_id', true );
                        }
                        $email = wpgh_get_email_by_id( intval( $email_id ) );
                        echo $email->subject; ?></td>
                    <td><?php if ( $activity = wpgh_get_activity( $contact->get_id(), $email_event->funnel_id, $email_event->step_id, 'email_opened', $email_id ) ):
                            $p_time = intval( $activity->timestamp ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
                            $cur_time = (int) current_time( 'timestamp' );
                            $time_diff = $p_time - $cur_time;
                            if ( absint( $time_diff ) > 24 * HOUR_IN_SECONDS ){
                                $time = sprintf( "On %s", date_i18n( 'jS F, Y \@ h:i A', intval( $p_time )  ) );
                            } else {
                                $time = sprintf( "%s ago", human_time_diff( $p_time, $cur_time ) );
                            }
                            echo '<abbr title="' . date_i18n( DATE_ISO8601, intval( $p_time ) ) . '">' . $time . '</abbr>';
                        else:
                            echo '&#x2014;';
                        endif;?></td>
                    <td><?php $activity = wpgh_get_activity( $contact->get_id(), $email_event->funnel_id, $email_event->step_id, 'email_link_click', $email_id );
                        if ( $activity )
                            echo '<a target="_blank" href="' . esc_url( $activity->referer ) . '">' . esc_url( $activity->referer ) . '</a>';
                        else
                            echo '&#x2014;'; ?></td>
                </tr><?php }} ?>
        </tbody>
    </table>
    <p class="description"><?php _e( 'This is where you can check if this contact is interacting with your emails.', 'groundhogg' ); ?></p>

    <!-- THE END -->
    <?php do_action( 'wpgh_contact_edit_after', $id ); ?>
    <div class="edit-contact-actions">
        <p class="submit">
            <?php submit_button('Update Contact', 'primary', null, false ); ?>
            <span id="delete-link"><a class="delete" href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=gh_contacts&action=delete&contact='. $id ), 'delete'  ) ?>"><?php _e( 'Delete' ); ?></a></span>
        </p>
    </div>
</form>
