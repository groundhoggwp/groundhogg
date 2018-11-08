<?php
/**
 * Edit a contact record via the Admin
 *
 * This page is AWESOME. It has 3 main functions....
 * 1. Provide a simple UI for editing the import contact details.
 * 2. Provide a simple UI for editing contact meta data (custom fields)
 * 3. Provide a simple UI for managing funnel events related to the contact.
 *
 * To add your own settings section there are a multitude of hooks to choose from.
 * The api to add settings sections is not complicated, but as a result you will be responsible for your own CSS & HTML
 * Your best option would be to do something like this...
 *
 * add_action( 'wpgh_contact_edit_before_history', 'my_settings_section' ); ( $id )
 *
 * This will add your section right above the funnel events history section.
 *
 * To save your custom information you will need to hook into the save method which you do by...
 *
 * add_action( 'wpgh_admin_update_contact_after', 'my_save_function' ); ($id)
 *
 * And accessing the $_POST directly.
 *
 * @package     Admin
 * @subpackage  Admin/Contacts
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @see         WPGH_Contacts_Page::edit()
 * @since       File available since Release 0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

$id = intval( $_GET[ 'contact' ] );

$contact = new WPGH_Contact( $id );

if ( ! $contact->exists() ) {
    wp_die( __( 'This contact has been deleted.', 'groundhogg' ) );
}

include_once "class-wpgh-contact-activity-table.php";
include_once "class-wpgh-contact-events-table.php";

wp_enqueue_script( 'contact-editor', WPGH_ASSETS_FOLDER . 'js/admin/contact-editor.js' );

/* Quit if */
if ( in_array( 'sales_manager', wpgh_get_current_user_roles() ) ){
    if ( $contact->owner->ID !== get_current_user_id() ){

        wp_die( __( 'You are not the owner of this contact.', 'groundhogg' ) );

    }
}

?>

<?php if ( ! empty( $contact->full_name) ):?>
<!-- Title -->
<span class="hidden" id="new-title"><?php echo $contact->full_name; ?> &lsaquo; </span>
<script>
    document.title = jQuery( '#new-title' ).text() + document.title;
</script>
<!--/ Title -->
<?php endif; ?>

<form method="post" class="">
    <?php wp_nonce_field( 'edit', '_edit_contact_nonce' ); ?>

    <!-- GENERAL NAME INFO -->
    <h2><?php _e( 'Name' ) ?></h2>
    <table class="form-table">
        <tbody>
        <tr>
            <th><label for="first_name"><?php echo __( 'First Name', 'groundhogg' )?></label></th>
            <td><?php $args = array(
                    'id'    => 'first_name',
                    'name'  => 'first_name',
                    'value' => $contact->first_name,
                );
            echo WPGH()->html->input( $args ); ?>
            </td>
        </tr>
        <tr>
            <th><label for="last_name"><?php echo __( 'Last Name', 'groundhogg' )?></label></th>
            <td><?php $args = array(
                    'id'    => 'last_name',
                    'name'  => 'last_name',
                    'value' => $contact->last_name,
                );
                echo WPGH()->html->input( $args ); ?></td>
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
            <td><?php $args = array(
                    'type'  => 'email',
                    'id'    => 'email',
                    'name'  => 'email',
                    'value' => $contact->email,
                );
                echo WPGH()->html->input( $args ); ?>
                <label><span class="row-actions"><a style="text-decoration: none" target="_blank" href="<?php echo esc_url(substr(  $contact->email, strpos( $contact->email, '@' ) ) ); ?>"><span class="dashicons dashicons-external"></span></a></span>
                <p class="submit"><?php echo '<b>' . __('Email Status', 'groundhogg') . ': </b>' . wpgh_get_optin_status_text( $contact->ID ); ?></p>
                <?php if ( $contact->optin_status !== WPGH_UNSUBSCRIBED ): ?>
                    <input type="checkbox" name="unsubscribe" value="1"><?php _e( 'Mark as unsubscribed.' )?></label>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th><label for="primary_phone"><?php echo __( 'Primary Phone', 'groundhogg' )?></label></th>
            <td><?php $args = array(
                    'type'  => 'tel',
                    'id'    => 'primary_phone',
                    'name'  => 'primary_phone',
                    'value' => $contact->get_meta( 'primary_phone' ),
                );
                echo WPGH()->html->input( $args ); ?></td>
        </tr>
        <tr>
            <th><label for="primary_phone_extension"><?php echo __( 'Phone Extension', 'groundhogg' )?></label></th>
            <td><?php $args = array(
                    'id'    => 'primary_phone_extension',
                    'name'  => 'primary_phone_extension',
                    'value' => $contact->get_meta( 'primary_phone_extension' ),
                );
                echo WPGH()->html->input( $args ); ?></td>
        </tr>
        <?php do_action( 'wpgh_contact_edit_contact_info', $id ); ?>
        </tbody>
    </table>

    <!-- ADDRESS -->
    <h2><?php _e( 'Address' ); ?></h2>
    <table class="form-table">
        <tbody>
        <tr>
            <th><label for="street_address_1"><?php echo __( 'Street Address 1', 'groundhogg' )?></label></th>
            <td><?php $args = array(
                    'id'    => 'street_address_1',
                    'name'  => 'street_address_1',
                    'value' => $contact->get_meta( 'street_address_1' ),
                );
                echo WPGH()->html->input( $args ); ?>
            </td>
        </tr>
        <tr>
            <th><label for="street_address_2"><?php echo __( 'Street Address 2', 'groundhogg' )?></label></th>
            <td><?php $args = array(
                    'id'    => 'street_address_2',
                    'name'  => 'street_address_2',
                    'value' => $contact->get_meta( 'street_address_2' ),
                );
                echo WPGH()->html->input( $args ); ?>
            </td>
        </tr>
        <tr>
            <th><label for="city"><?php echo __( 'City', 'groundhogg' )?></label></th>
            <td><?php $args = array(
                    'id'    => 'city',
                    'name'  => 'city',
                    'value' => $contact->get_meta( 'city' ),
                );
                echo WPGH()->html->input( $args ); ?>
            </td>
        </tr>
        <tr>
            <th><label for="postal_zip"><?php echo __( 'Postal/Zip Code', 'groundhogg' )?></label></th>
            <td><?php $args = array(
                    'id'    => 'postal_zip',
                    'name'  => 'postal_zip',
                    'value' => $contact->get_meta( 'postal_zip' ),
                );
                echo WPGH()->html->input( $args ); ?>
            </td>
        </tr>
        <tr>
            <th><label for="region"><?php echo __( 'State/Province', 'groundhogg' )?></label></th>
            <td><?php $args = array(
                    'id'    => 'region',
                    'name'  => 'region',
                    'value' => $contact->get_meta( 'region' ),
                );
                echo WPGH()->html->input( $args ); ?>
            </td>
        </tr>
        <tr>
            <th><label for="country"><?php echo __( 'Country', 'groundhogg' )?></label></th>
            <td><div style="max-width: 338px">
                    <?php $args = array(
                        'id'    => 'country',
                        'name'  => 'country',
                        'selected' => $contact->get_meta( 'country' ),
                        'data'  => wpgh_get_countries_list(),
                        'placeholder'   => __( 'Select a Country', 'groundhogg' ),
                    );
                    echo WPGH()->html->select2( $args ); ?>
                </div>
            </td>
        </tr>
        <?php do_action( 'wpgh_contact_edit_address', $id ); ?>
        </tbody>
    </table>

    <!-- MARKETING COMPLIANCE INFORMATION -->
    <h2><?php _e( 'Compliance' ); ?></h2>
    <table class="form-table">
        <tbody>
            <tr>
                <th><?php _e( 'Agreed To Terms' ); ?></th>
                <td><?php echo (  $contact->get_meta( 'terms_agreement') === 'yes' ) ? sprintf( "%s: %s",  __( 'Agreed' ),  $contact->get_meta( 'terms_agreement_date' ) ): '&#x2014;'; ?></td>
            </tr>
            <?php if ( wpgh_is_gdpr() ): ?>
                <tr>
                    <th><?php _e( 'GDPR Consent' ); ?></th>
                    <td><?php echo (  $contact->get_meta( 'gdpr_consent' ) === 'yes' ) ? sprintf( "%s: %s",  __( 'Agreed' ),  $contact->get_meta( 'gdpr_consent_date' ) ) : '&#x2014;'; ?></td>
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
            <td><?php echo WPGH()->html->dropdown_owners( array( 'selected' => ( $contact->owner )? $contact->owner->ID : 0 ) ); ?>
            </td>
        </tr>
        <tr>
            <th><?php _e( 'Source Page', 'groundhogg' ); ?></th>
            <td><?php $args = array(
                    'id'    => 'page_source',
                    'name'  => 'page_source',
                    'value' => $contact->get_meta( 'source_page' ),
                );
                echo WPGH()->html->input( $args ); ?>
                <span class="row-actions">
                    <a style="text-decoration: none" target="_blank" href="<?php echo esc_url( $contact->get_meta( 'source_page' ) ); ?>"><span class="dashicons dashicons-external"></span></a>
                </span>
                <p class="description">
                    <?php _e( "This is the page which the contact first submitted a form.", 'groundhogg' ); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th><?php _e( 'Lead Source', 'groundhogg' ); ?></th>
            <td><?php $args = array(
                    'id' => 'lead_source',
                    'name' => 'lead_source',
                    'value' => $contact->get_meta( 'lead_source' ),
                );
                echo WPGH()->html->input( $args ); ?>
                <span class="row-actions">
                    <a style="text-decoration: none" target="_blank" href="<?php echo esc_url( $contact->get_meta( 'lead_source' ) ); ?>"><span class="dashicons dashicons-external"></span></a>
                </span>
                <p class="description"><?php _e( "This is where the contact originated from.", 'groundhogg' ); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="tags"><?php echo __( 'Tags', 'groundhogg' )?></label></th>
            <td>
                <div style="max-width: 400px;">
                    <?php

                //print_r( $contact->tags );

                $args = array(
                    'id'        => 'tags',
                    'name'      => 'tags[]',
                    'selected'  => $contact->tags,
                ); echo WPGH()->html->tag_picker( $args ); ?>
                <p class="description"><?php _e( 'Add new tags by hitting [Enter] or by typing a [,].', 'groundhogg' ); ?></p>
                </div>
            </td>
        </tr>
        <?php do_action( 'wpgh_contact_edit_tags', $id ); ?>
        </tbody>
    </table>

    <!-- NOTES -->
    <h2><?php _e( 'Notes' ); ?></h2>
    <table>
        <tbody>
        <tr>
            <td>
                <?php $args = array(
                    'id'    => 'notes',
                    'name'  => 'notes',
                    'value' => $contact->get_meta( 'notes' ),
                );
                echo WPGH()->html->textarea( $args ); ?>
            </td>
        </tr>
        <?php do_action( 'wpgh_contact_edit_notes', $id ); ?>
        </tbody>
    </table>

    <!-- ACTIONS -->
    <h2><?php _e( 'Actions' ); ?></h2>
    <table class="form-table" >
        <tr>
            <th><?php _e( 'Send Email' ); ?></th>
            <td><div style="max-width: 400px">
                    <?php echo WPGH()->html->dropdown_emails( array() );?>
                    <div class="row-actions">
                        <button type="submit" name="send_email" value="send" class="button"><?php _e( 'Send' ); ?></button>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <th><?php _e( 'Add To Funnel' ); ?></th>
            <td><div style="max-width: 400px">
                    <?php

                    $steps = WPGH()->steps->get_steps();
                    $options = array();
                    foreach ( $steps as $step ){
                        $step = new WPGH_Step( $step->ID );
                        if ($step->is_active() ){
                            $funnel_name = WPGH()->funnels->get_column_by( 'title', 'ID', $step->funnel_id );
                            $options[ $funnel_name ][ $step->ID ] = sprintf( "%d. %s (%s)", $step->order, $step->title, str_replace( '_', ' ', $step->type ) );
                        }
                    }

//                    sort( $options );

                    echo WPGH()->html->select2( array(
                        'name'              => 'add_contacts_to_funnel_step_picker',
                        'id'                => 'add_contacts_to_funnel_step_picker',
                        'data'              => $options,
                        'multiple'          => false,
                    ) );

                    ?>
                    <div class="row-actions">
                        <button type="submit" name="start_funnel" value="start" class="button"><?php _e( 'Start' ); ?></button>
                    </div>
                </div>
            </td>
        </tr>
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
            $meta = WPGH()->contact_meta->get_meta( $contact->ID );
            foreach ( $meta as $meta_key => $value ):
                $value = $value[ 0 ]; ?>
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
            <?php endforeach; ?>
        <?php do_action( 'wpgh_contact_edit_meta', $id ); ?>
        </tbody>
    </table>

    <?php do_action( 'wpgh_contact_edit_before_history', $id ); ?>

    <!-- UPCOMING EVENTS -->
    <h2><?php _e( 'Upcoming Events' ); ?></h2>
    <div style="max-width: 800px">

    <?php $events = WPGH()->events->get_events( array( 'contact_id' => $contact->ID, 'status' => 'waiting' ) );

    $table = new WPGH_Contact_Events_Table();
    $table->data = $events;

    $table->prepare_items();
    $table->display(); ?>

    <p class="description"><?php _e( 'Any upcoming funnel steps will show up here. you can choose to cancel them or to run them immediately.', 'groundhogg' ); ?></p>

    <!-- FUNNNEL HISTORY -->
    <h2><?php _e( 'Recent Funnel History' ); ?></h2>
    <div style="max-width: 800px">
    </div>
    <?php $events = WPGH()->events->get_events( array( 'contact_id' => $contact->ID, 'status' => 'complete' ) );

    $table = new WPGH_Contact_Events_Table();
    $table->data = $events;

    $table->prepare_items();
    $table->display(); ?>

    <p class="description"><?php _e( 'Any previous funnel steps will show up here. You can choose run them again.<br/>
    This report only shows the 20 most recent events, to see more you can see all this contact\'s history in the event queue.', 'groundhogg' ); ?></p>
    </div>
    <!-- EMAIL HISTORY -->
    <h2><?php _e( 'Recent Email History' ); ?></h2>
    <div style="max-width: 800px">
    <?php $table = new WPGH_Contact_Activity_Table();
        $table->prepare_items();
        $table->display(); ?>
    <p class="description"><?php _e( 'This is where you can check if this contact is interacting with your emails.', 'groundhogg' ); ?></p>
    </div>
    <!-- THE END -->
    <?php do_action( 'wpgh_contact_edit_after', $id ); ?>
    <div class="edit-contact-actions">
        <p class="submit">
            <?php submit_button('Update Contact', 'primary', 'update', false ); ?>
            <span id="delete-link"><a class="delete" href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=gh_contacts&action=delete&contact='. $id ), 'delete'  ) ?>"><?php _e( 'Delete' ); ?></a></span>
        </p>
    </div>
</form>
