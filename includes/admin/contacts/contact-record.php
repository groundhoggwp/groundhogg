<?php
/**
 * Funnel Builder
 *
 * Drag and drop builder for marketing automation
 *
 * @package     groundhogg
 * @subpackage  Includes/Funnels
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

if ( ! isset( $_GET['ID'] ) || ! is_numeric( $_GET['ID'] ) )
{
    wp_die( __( 'Contact ID not supplied. Please try again', 'groundhogg' ), __( 'Error', 'groundhogg' ) );
}

$contact_id = intval( $_GET['ID'] );
$contact = new WPGH_Contact( $contact_id );

do_action( 'wpgh_contact_record_before_everything', $contact_id );

?>

<style>select {vertical-align: top;}</style>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo __('Edit Contact', 'groundhogg');?></h1>
    <form method="post">
        <div id='poststuff' class="wpgh-funnel-builder">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <div id="titlediv">
                        <div id="titlewrap">
                            <label class="screen-reader-text" id="title-prompt-text" for="title"><?php echo __('Enter Contact Name Here', 'groundhogg');?></label>
                            <input placeholder="<?php echo __('Enter Contact Name Here', 'groundhogg');?>" type="text" name="contact_name" size="30" value="<?php echo $contact->get_full(); ?>" id="title" spellcheck="true" autocomplete="off">
                        </div>
                    </div>
                </div>
                <!-- begin elements area -->
                <div id="postbox-container-1" class="postbox-container sticky">
                    <div id="submitdiv" class="postbox">
                        <h3 class="hndle"><?php echo __( 'Contact Actions', 'groundhogg' );?></h3>
                        <div class="inside">
                            <div class="submitbox">
                                <div id="minor-publishing-actions">
                                    <?php do_action( 'wpgh_contact_actions_before' ); ?>
                                    <table>
                                        <tbody>
                                        <tr>
                                            <th><label for="date_created"><?php echo __( 'Dated Created', 'groundhogg' );?></label></th>
                                            <td><?php echo date( 'Y-m-d', strtotime( $contact->date_created ) ); ?></td>
                                        </tr>
                                        </tbody>
                                    </table>
                                    <?php do_action( 'wpgh_contact_actions_after' ); ?>
                                </div>
                                <div id="major-publishing-actions">
                                    <div id="delete-action">
                                        <a class="submitdelete deletion" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=gh_contacts' ), 'delete_contact', 'wpgh_nonce' ) ); ?>"><?php echo esc_html__( 'Delete Contact', 'groundhogg' ); ?></a>
                                    </div>
                                    <div id="publishing-action">
                                        <span class="spinner"></span>
                                        <input name="original_publish" type="hidden" id="original_publish" value="Update">
                                        <input name="save" type="submit" class="button button-primary button-large" id="publish" value="Update">
                                    </div>
                                    <div class="clear"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php do_action( 'wpgh_contact_side_actions_after' ); ?>
                </div>
                <!-- End elements area-->

                <!-- main funnel editing area -->
                <div id="postbox-container-2" class="postbox-container funnel-editor">
                    <?php do_action('wpgh_contact_boxes_before' ); ?>

                    <?php do_action('wpgh_contact_general_box_before' ); ?>
                    <div id="general" class="postbox">
                        <h2 class="hndle ui-sortable-handle"><?php echo __( 'General Info', 'groundhogg' )?></h2>
                        <div class="inside">
                            <table class="form-table">
                                <?php do_action( 'wpgh_contact_general_settings_before' ); ?>
                                <tbody>
                                <tr>
                                    <th><label for="first_name"><?php echo __( 'First Name', 'groundhogg' )?></label></th>
                                    <td><?php echo wpgh_admin_text_input_field( 'first_name', 'first_name', $contact->get_first() );?></td>
                                </tr>
                                <tr>
                                    <th><label for="last_name"><?php echo __( 'Last Name', 'groundhogg' )?></label></th>
                                    <td><?php echo wpgh_admin_text_input_field( 'last_name', 'last_name', $contact->get_last() );?></td>
                                </tr>
                                <tr>
                                    <th><label for="email"><?php echo __( 'Email', 'groundhogg' )?></label></th>
                                    <td>
                                        <?php echo wpgh_admin_text_input_field( 'email', 'email', $contact->get_email() );?>
                                        <p><?php echo '<b>' . __('Email Status', 'groundhogg') . ': </b>' . wpgh_get_optin_status_text( $contact->get_optin_status() ); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="primary_phone"><?php echo __( 'Primary Phone', 'groundhogg' )?></label></th>
                                    <td><?php echo wpgh_admin_text_input_field( 'primary_phone', 'meta[primary_phone]', $contact->get_phone() );?></td>
                                </tr>
                                <tr>
                                    <th><label for="primary_phone_extension"><?php echo __( 'Phone Extension', 'groundhogg' )?></label></th>
                                    <td><?php echo wpgh_admin_text_input_field( 'primary_phone_extension', 'meta[primary_phone_extension]', $contact->get_phone_extension() );?></td>
                                </tr>
                                </tbody>
                            </table>
                            <?php do_action( 'wpgh_contact_general_settings_after' ); ?>
                        </div>
                    </div>
                    <?php do_action('wpgh_contact_general_box_after' ); ?>

                    <?php do_action('wpgh_contact_activity_box_before' ); ?>
                    <div id="activity" class="postbox">
                        <h2 class="hndle ui-sortable-handle"><?php echo __( 'Recent Activity', 'groundhogg' )?></h2>
                        <div class="inside">
                            <table class="wp-list-table widefat striped contact-activity">
                                <thead>
                                <tr>
                                    <th><?php echo __('Date', 'groundhogg');?></th>
                                    <th><?php echo __('Entry', 'groundhogg');?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php $entries = $contact->get_parsed_activity(); ?>
                                <?php if ( $entries ): foreach ( $entries as $entry ): ?>
                                    <tr>
                                        <?php if ( isset( $entry[0] ) && isset( $entry[1] ) ): ?>
                                            <td><?php echo $entry[0];?></td>
                                            <td><?php echo $entry[1];?></td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; endif;?>
                                <?php if ( empty( $entries ) ):?>
                                    <tr>
                                        <td colspan="2">
                                            <?php echo __( 'No Recent Activity Recorded...', 'groundhogg' ); ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php do_action('wpgh_contact_activity_box_after' ); ?>


                    <?php do_action('wpgh_contact_boxes_after' ); ?>
                </div>
                <!-- end main funnel editing area -->
            </div>
        </div>
    </form>
</div>
