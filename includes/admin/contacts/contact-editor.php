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

$contact = wpgh_get_contact( $id );

if ( ! $contact->exists() ) {
    wp_die( _x( 'This contact has been deleted.', 'contact_record', 'groundhogg' ) );
}

include_once "class-wpgh-contact-activity-table.php";
include_once "class-wpgh-contact-events-table.php";

/* Quit if */
if ( in_array( 'sales_manager', wpgh_get_current_user_roles() ) ){
    if ( $contact->owner->ID !== get_current_user_id() ){

        wp_die( _x( 'You are not the owner of this contact.', 'contact_record', 'groundhogg' ) );

    }
}

/* Auto link the account before we see the create account form. */
$contact->auto_link_account();

$title = ! empty( $contact->first_name  ) ? $contact->full_name : $contact->email;

$tabs = array(
	'general'       => _x( 'General Info', 'contact_record_tab', 'groundhogg' ),
	'meta_data'     => _x( 'Custom Info', 'contact_record_tab', 'groundhogg' ),
	'segmentation'  => _x( 'Segmentation', 'contact_record_tab', 'groundhogg' ),
	'notes'         => _x( 'Notes', 'contact_record_tab', 'groundhogg' ),
	'files'         => _x( 'Files', 'contact_record_tab', 'groundhogg' ),
	'actions'       => _x( 'Actions', 'contact_record_tab', 'groundhogg' ),
	'activity'      => _x( 'Activity', 'contact_record_tab', 'groundhogg' ),
);
$tabs = apply_filters( 'wpgh_contact_record_tabs', $tabs );
$tabs = apply_filters( 'groundhogg/contact/record/tabs', $tabs );

$cookie_tab = isset( $_COOKIE[ 'gh_contact_tab' ] ) ? str_replace( 'tab_', '', sanitize_key( $_COOKIE[ 'gh_contact_tab' ] ) ): 'general';
$active_tab = isset( $_POST[ 'active_tab' ] ) && ! empty( $_POST[ 'active_tab' ] ) ? sanitize_key( $_POST[ 'active_tab' ] ) : $cookie_tab;
?>

<!-- Title -->
<span class="hidden" id="new-title"><?php echo $title ?> &lsaquo; </span>
<script>
    document.title = jQuery( '#new-title' ).text() + document.title;
</script>
<!--/ Title -->
<div class="local-time" style="float: right; padding: 10px;font-size: 18px;">
    <?php _ex( 'Local Time:', 'groundhogg' );?>
    <span style="font-family: Georgia, Times New Roman, Bitstream Charter, Times, serif;font-weight: 400;"><?php echo date_i18n( "D, F d h:i A", $contact->get_local_time() ); ?>
        </span>
</div>

<?php do_action( 'groundhogg/contact/record/nav/before', $contact ); ?>

<!-- BEGIN TABS -->
<h2 class="nav-tab-wrapper">
    <?php foreach ( $tabs as $id => $tab ): ?>
        <a href="javascript:void(0)" class="nav-tab <?php echo  $active_tab == $id ? 'nav-tab-active' : ''; ?>" id="<?php echo 'tab_' . esc_attr( $id ); ?>"><?php _e( $tab, 'groundhogg'); ?></a>
    <?php endforeach; ?>
</h2>

<?php do_action( 'groundhogg/contact/record/nav/after', $contact ); ?>

<!-- END TABS -->

<form method="post" class="" enctype="multipart/form-data">
    <?php wp_nonce_field( 'edit', '_edit_contact_nonce' ); ?>


<?php

add_action( 'wpgh_contact_record_tab_general', 'wpgh_contact_record_general_info' );

/**
 * Contact Info
 *
 * @param $contact WPGH_Contact
 */
function wpgh_contact_record_general_info( $contact )
{
    ?>
    <!-- GENERAL NAME INFO -->
    <table class="form-table">
        <tbody>
        <tr>
            <th><h2><?php _e('Name' ) ?></h2></th>
            <td><?php echo get_avatar( $contact->email ); ?>
            </td>
        </tr>
        <tr>
            <th><label for="first_name"><?php echo _x( 'First Name', 'contact_record', 'groundhogg' ) ?></label></th>
            <td><?php $args = array(
                    'id' => 'first_name',
                    'name' => 'first_name',
                    'value' => $contact->first_name,
                );
                echo WPGH()->html->input($args); ?>
            </td>
        </tr>
        <tr>
            <th><label for="last_name"><?php echo _x( 'Last Name', 'contact_record', 'groundhogg' ) ?></label></th>
            <td><?php $args = array(
                    'id' => 'last_name',
                    'name' => 'last_name',
                    'value' => $contact->last_name,
                );
                echo WPGH()->html->input($args); ?></td>
        </tr>
        <?php if (isset($contact->user->user_login)): ?>

            <tr>
                <th><label for="username"><?php _e('Username') ?></label></th>
                <td><?php printf("<a href='%s'>%s</a>", admin_url('user-edit.php?user_id=' . $contact->user->ID), $contact->user->user_login); ?>
                <span class="row-actions">
                    <?php submit_button( _x( 'Unlink', 'action', 'groundhogg'), 'secondary', 'unlink_user', false ); ?>
                </span>
                </td>
            </tr>

        <?php endif; ?>
        <?php do_action('wpgh_contact_edit_name', $contact->ID ); ?>
        </tbody>
    </table>

    <?php do_action( 'groundhogg/contact/record/name/after', $contact ); ?>

    <?php if (!$contact->user): ?>

    <h2><?php _e('Create User Account'); ?></h2>
    <table class="form-table">
        <tr>
            <th><label for="create_account"><?php echo _x( 'Create New Account?', 'contact_record', 'groundhogg' ) ?></label></th>
            <td>
                <button type="button"
                        class="button button-secondary create-user-account"><?php _e('Create User Account'); ?></button>
                <p class="description"><?php _ex( 'This contact does not have an associated user account? Would you like to create one?', 'contact_record', 'groundhogg' ); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="link_existing"><?php echo _x( 'Link Existing Account?', 'contact_record', 'groundhogg' ) ?></label></th>
            <td><?php wp_dropdown_users(array('show_option_none' => _x( 'Select a User Account (optional)', 'contact_record', 'groundhogg' ))); ?>
                <p class="description"><?php _ex( 'You can link an existing user account to this contact.', 'contact_record', 'groundhogg' ); ?></p>
            </td>
        </tr>

    </table>

    <?php do_action( 'groundhogg/contact/record/user/after', $contact ); ?>

<?php endif; ?>

    <!-- GENERAL CONTACT INFO -->
    <h2><?php _e('Contact Info' ); ?></h2>
    <table class="form-table">
        <tbody>
        <tr>
            <th><label for="email"><?php echo _x( 'Email', 'contact_record', 'groundhogg' ) ?></label></th>
            <td><?php $args = array(
                    'type' => 'email',
                    'id' => 'email',
                    'name' => 'email',
                    'value' => $contact->email,
                );
                echo WPGH()->html->input($args); ?>
                <span class="row-actions"><a style="text-decoration: none" target="_blank"
                                                    href="<?php echo esc_url(substr($contact->email, strpos($contact->email, '@'))); ?>"><span class="dashicons dashicons-external"></span></a></span>
                    <p class="submit"><?php echo '<b>' . _x( 'Email Status', 'contact_record', 'groundhogg' ) . ': </b>' . wpgh_get_optin_status_text($contact->ID); ?></p>
                <?php if ($contact->optin_status !== WPGH_UNSUBSCRIBED): ?>
                    <div id="manual-unsubscribe" style="margin-bottom: 10px;">
                        <label><input type="checkbox" name="unsubscribe" value="1"><?php _ex( 'Mark as unsubscribed.', 'contact_record', 'groundhogg' ); ?></label>
                </div>
                <?php endif; ?>
                <?php if ($contact->optin_status !== WPGH_CONFIRMED): ?>
                <div id="manual-confirmation">
                    <label><input type="checkbox" name="manual_confirm" id="manual-confirm" value="1"><?php _ex( 'Manually confirm this email address.', 'contact_record', 'groundhogg' ); ?></label>
                    <div id="confirmation-reason" class="hidden">
                        <?php echo WPGH()->html->textarea( [ 'name' => 'confirmation_reason', 'cols' => 50, 'rows' => 2,  'placeholder' => __( 'Confirmation reason...', 'groundhogg' ) ] ); ?>
                    </div>
                </div>
                <script>jQuery(function ($) {
                        $( '#manual-confirm' ).on( 'change', function () {
                            $( '#confirmation-reason' ).toggleClass( 'hidden' );
                        } );
                    });</script>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th><label for="primary_phone"><?php echo _x( 'Primary Phone', 'contact_record', 'groundhogg' ) ?></label></th>
            <td><?php $args = array(
                    'type' => 'tel',
                    'id' => 'primary_phone',
                    'name' => 'primary_phone',
                    'value' => $contact->get_meta('primary_phone'),
                );
                echo WPGH()->html->input($args); ?></td>
        </tr>
        <tr>
            <th><label for="primary_phone_extension"><?php echo _x( 'Phone Extension', 'contact_record', 'groundhogg' ) ?></label></th>
            <td><?php $args = array(
                    'id' => 'primary_phone_extension',
                    'name' => 'primary_phone_extension',
                    'value' => $contact->get_meta('primary_phone_extension'),
                );
                echo WPGH()->html->input($args); ?></td>
        </tr>
        <?php do_action('wpgh_contact_edit_contact_info', $contact->ID ); ?>
        </tbody>
    </table>

    <?php do_action( 'groundhogg/contact/record/contact_info/after', $contact ); ?>

    <!-- Company info -->
    <h2><?php _ex( 'Company Info', 'contact_record', 'groundhogg' ); ?></h2>
    <table class="form-table">
        <tr>
            <th><label for="company_name"><?php echo _x( 'Company Name', 'contact_record', 'groundhogg' ) ?></label></th>
            <td><?php $args = array(
                    'id' => 'company_name',
                    'name' => 'company_name',
                    'value' => $contact->get_meta('company_name'),
                );
                echo WPGH()->html->input($args); ?>
            </td>
        </tr>
        <tr>
            <th><label for="job_title"><?php echo _x( 'Job Title', 'contact_record', 'groundhogg' ) ?></label></th>
            <td><?php $args = array(
                    'id' => 'job_title',
                    'name' => 'job_title',
                    'value' => $contact->get_meta('job_title'),
                );
                echo WPGH()->html->input($args); ?>
            </td>
        </tr>
        <tr>
            <th><label for="company_address"><?php echo _x( 'Full Company Address', 'contact_record', 'groundhogg' ) ?></label></th>
            <td><?php $args = array(
                    'id' => 'company_address',
                    'name' => 'company_address',
                    'value' => $contact->get_meta('company_address'),
                );
                echo WPGH()->html->input($args); ?>
            </td>
        </tr>
    </table>

    <?php do_action( 'groundhogg/contact/record/company_info/after', $contact ); ?>


    <!-- ADDRESS -->
    <h2><?php _ex( 'Location', 'contact_record', 'groundhogg' ); ?></h2>
    <table class="form-table">
        <tbody>
        <tr>
            <th><label for="street_address_1"><?php echo _x( 'Street Address 1', 'contact_record', 'groundhogg' ) ?></label></th>
            <td><?php $args = array(
                    'id' => 'street_address_1',
                    'name' => 'street_address_1',
                    'value' => $contact->get_meta('street_address_1'),
                );
                echo WPGH()->html->input($args); ?>
            </td>
        </tr>
        <tr>
            <th><label for="street_address_2"><?php echo _x( 'Street Address 2', 'contact_record', 'groundhogg' ) ?></label></th>
            <td><?php $args = array(
                    'id' => 'street_address_2',
                    'name' => 'street_address_2',
                    'value' => $contact->get_meta('street_address_2'),
                );
                echo WPGH()->html->input($args); ?>
            </td>
        </tr>
        <tr>
            <th><label for="city"><?php echo _x( 'City', 'contact_record', 'groundhogg' ) ?></label></th>
            <td><?php $args = array(
                    'id' => 'city',
                    'name' => 'city',
                    'value' => $contact->get_meta('city'),
                );
                echo WPGH()->html->input($args); ?>
            </td>
        </tr>
        <tr>
            <th><label for="postal_zip"><?php echo _x( 'Postal/Zip Code', 'contact_record', 'groundhogg' ) ?></label></th>
            <td><?php $args = array(
                    'id' => 'postal_zip',
                    'name' => 'postal_zip',
                    'value' => $contact->get_meta('postal_zip'),
                );
                echo WPGH()->html->input($args); ?>
            </td>
        </tr>
        <tr>
            <th><label for="region"><?php echo _x( 'State/Province', 'contact_record', 'groundhogg' ) ?></label></th>
            <td><?php $args = array(
                    'id' => 'region',
                    'name' => 'region',
                    'value' => $contact->get_meta('region'),
                );
                echo WPGH()->html->input($args); ?>
            </td>
        </tr>
        <tr>
            <th><label for="country"><?php echo _x( 'Country', 'contact_record', 'groundhogg' ) ?></label></th>
            <td>
                <div style="max-width: 338px">
                    <?php $args = array(
                        'id' => 'country',
                        'name' => 'country',
                        'selected' => $contact->get_meta('country'),
                        'data' => wpgh_get_countries_list(),
                        'placeholder' => _x( 'Select a Country', 'contact_record', 'groundhogg' ),
                    );
                    echo WPGH()->html->select2($args); ?>
                </div>
            </td>
        </tr>
        <tr>
            <th><label for="ip_address"><?php echo _x( 'IP Address', 'contact_record', 'groundhogg' ) ?></label></th>
            <td><?php $args = array(
                    'id' => 'ip_address',
                    'name' => 'ip_address',
                    'value' => $contact->get_meta('ip_address' ),
                );
                echo WPGH()->html->input($args); ?>
                <?php if ( $contact->ip_address && $contact->ip_address !== '::1' ): ?>
                    <span class="row-actions">
                    <?php submit_button( _x( 'Extrapolate Location', 'action', 'groundhogg'), 'secondary', 'extrapolate_location', false ); ?>
                </span>
                <?php ?>
<?php endif; ?>
            </td>
        </tr>
        <tr>
            <th><label for="time_zone"><?php echo _x( 'Time Zone', 'contact_record', 'groundhogg' ) ?></label></th>
            <td><div style="max-width: 338px">
                    <?php $args = array(
                    'id' => 'time_zone',
                    'name' => 'time_zone',
                    'data' => wpgh_get_time_zones(),
                    'selected' => $contact->get_meta('time_zone'),
                );
                    echo WPGH()->html->select2($args); ?></div>
            </td>
        </tr>
        <?php do_action('wpgh_contact_edit_address', $contact->ID ); ?>
        </tbody>
    </table>

    <!-- MARKETING COMPLIANCE INFORMATION -->
    <h2><?php _ex( 'Compliance', 'contact_record', 'groundhogg' ); ?></h2>
    <table class="form-table">
        <tbody>
        <tr>
            <th><?php _ex( 'Agreed To Terms', 'contact_record', 'groundhogg' ); ?></th>
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

    <?php
}

add_action( 'wpgh_contact_record_tab_segmentation', 'wpgh_contact_record_section_segmentation' );

/**
 * @param $contact WPGH_Contact
 */
function wpgh_contact_record_section_segmentation( $contact )
{
    ?>

    <!-- SEGMENTATION AND LEADSOURCE -->
    <h2><?php _ex( 'Segmentation', 'contact_record', 'groundhogg' ); ?></h2>
    <table class="form-table">
        <tbody>
        <tr>
            <th><?php _ex( 'Owner', 'contact_record', 'groundhogg' ); ?></th>
            <td><?php echo WPGH()->html->dropdown_owners( array( 'selected' => ( $contact->owner )? $contact->owner->ID : 0 ) ); ?>
            </td>
        </tr>
        <tr>
            <th><?php _ex( 'Source Page', 'contact_record', 'groundhogg' ); ?></th>
            <td><?php $args = array(
                    'id'    => 'source_page',
                    'name'  => 'source_page',
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
            <th><?php _ex( 'Lead Source', 'contact_record', 'groundhogg' ); ?></th>
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
            <th><label for="tags"><?php echo _x( 'Tags', 'contact_record', 'groundhogg' )?></label></th>
            <td>
                <div style="max-width: 400px;">
                    <?php

                //print_r( $contact->tags );

                $args = array(
                    'id'        => 'tags',
                    'name'      => 'tags[]',
                    'selected'  => $contact->tags,
                ); echo WPGH()->html->tag_picker( $args ); ?>
                <p class="description"><?php _ex( 'Add new tags by hitting [Enter] or by typing a [,].', 'contact_record', 'groundhogg' ); ?></p>
                </div>
            </td>
        </tr>
        <?php do_action( 'wpgh_contact_edit_tags', $contact->ID ); ?>
        </tbody>
    </table>
<?php
}

add_action( 'wpgh_contact_record_tab_notes', 'wpgh_contact_record_section_notes' );

/**
 * @param $contact WPGH_Contact
 */
function wpgh_contact_record_section_notes( $contact )
{
    ?>
    <!-- NOTES -->
    <h2><?php _e( 'Notes' ); ?></h2>
    <table class="form-table">
        <tr>
            <th><?php _ex( 'Add Note', 'contact_record', 'groundhogg' ); ?></th>
            <td><?php $args = array(
                    'id'    => 'add_note',
                    'name'  => 'add_note',
                    'value' => '',
                    'rows'  => 3,
                    'cols'  => 64,
                    'attributes' => ''
                );
                echo WPGH()->html->textarea( $args ); ?>
                <?php submit_button( _x( 'Add Note', 'action', 'groundhogg' ), 'secondary', 'add_new_note' ); ?>
            </td>
        </tr>
    </table>
    <table>
        <tbody>
        <tr>
            <td>
                <?php $args = array(
                    'id'    => 'notes',
                    'name'  => 'notes',
                    'value' => $contact->get_meta( 'notes' ),
                    'rows'  => 30,
                    'attributes' => 'readonly'
                );
                echo WPGH()->html->textarea( $args ); ?>
            </td>
        </tr>
        <?php do_action( 'wpgh_contact_edit_notes', $contact->ID ); ?>
        </tbody>
    </table>
    <?php
}

add_action( 'wpgh_contact_record_tab_actions', 'wpgh_contact_record_section_actions' );

/**
 * @param $contact WPGH_contact
 */
function wpgh_contact_record_section_actions( $contact )
{
    ?>
    <!-- ACTIONS -->
    <h2><?php _e('Actions'); ?></h2>
    <table class="form-table">
        <tr>
            <th><?php _ex( 'Send Email', 'contact_record', 'groundhogg' ); ?></th>
            <td>
                <div style="max-width: 400px">
                    <?php echo WPGH()->html->dropdown_emails(array()); ?>
                    <div class="row-actions">
                        <button type="submit" name="send_email" value="send" class="button"><?php _e('Send' ); ?></button>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <th><?php _ex( 'Send SMS', 'contact_record', 'groundhogg' ); ?></th>
            <td>
                <div style="max-width: 400px">
                    <?php
                    $args = array(
                        'id'    => 'sms_id',
                        'name'  => 'sms_id',
                        'data'  => WPGH()->sms->get_sms_select()
                    );
                    echo WPGH()->html->select2( $args ); ?>
                    <div class="row-actions">
                        <button type="submit" name="send_sms" value="send" class="button"><?php _e('Send'); ?></button>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <th><?php _ex( 'Add To Funnel', 'contact_record', 'groundhogg' ); ?></th>
            <td>
                <div style="max-width: 400px">
                    <?php

                    $steps = WPGH()->steps->get_steps();
                    $options = array();
                    foreach ($steps as $step) {
                        $step = wpgh_get_funnel_step($step->ID);
                        if ($step->is_active()) {
                            $funnel_name = WPGH()->funnels->get_column_by('title', 'ID', $step->funnel_id);
                            $options[$funnel_name][$step->ID] = sprintf("%d. %s (%s)", $step->order, $step->title, str_replace('_', ' ', $step->type));
                        }
                    }

                    echo WPGH()->html->select2(array(
                        'name' => 'add_contacts_to_funnel_step_picker',
                        'id' => 'add_contacts_to_funnel_step_picker',
                        'data' => $options,
                        'multiple' => false,
                    ));

                    ?>
                    <div class="row-actions">
                        <button type="submit" name="start_funnel" value="start" class="button"><?php _e('Start'); ?></button>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <th><?php _ex( 'Internal Form', 'contact_record', 'groundhogg' ); ?></th>
            <td>
                <div style="max-width: 400px;">
                    <?php $forms = WPGH()->steps->get_steps( array(
                        'step_type' => 'form_fill'
                    ) );

                    $form_options = array();
                    $default = 0;
                    foreach ( $forms as $form ){
                        if ( ! $default ){$default = $form->ID;}
                        $step = wpgh_get_funnel_step( $form->ID );
                        if ( $step->is_active() ){$form_options[ $form->ID ] = $form->step_title;}
                    }

                    echo WPGH()->html->select2( array(
                        'name'              => 'manual_form_submission',
                        'id'                => 'manual_form_submission',
                        'class'             => 'manual-submission gh-select2',
                        'data'              => $form_options,
                        'multiple'          => false,
                        'selected'          => [ $default ],
                        'placeholder'       => 'Please Select a Form',
                    ) );

                    ?><div class="row-actions">
                        <script>var WPGHFormSubmitBaseUrl = '<?php echo admin_url( sprintf( 'admin.php?page=gh_contacts&action=form&contact=%d&form=', $contact->ID ) ); ?>';</script>
                        <a id="form-submit-link" class="button button-secondary" href="<?php echo admin_url( sprintf( 'admin.php?page=gh_contacts&action=form&contact=%d&form=%d', $contact->ID, $default ) ); ?>"><?php _ex( 'Submit Form', 'action', 'groundhogg' ) ?></a>
                    </div>
                </div>
            </td>
        </tr>
    </table>
    <?php
}

add_action( 'wpgh_contact_record_tab_files', 'wpgh_contact_record_section_files' );

/**
 * @param $contact WPGH_Contact
 */
function wpgh_contact_record_section_files( $contact )
{
    ?>
    <!-- BEGIN FILES -->
    <h2><?php _ex( 'Files', 'contact_record', 'groundhogg' ); ?></h2>
    <div style="max-width: 800px;">
        <table class="wp-list-table widefat fixed striped files">
            <thead>
            <tr>
                <th><?php _ex( 'Name', 'contact_record', 'groundhogg' ); ?></th>
                <th><?php _ex( 'Size', 'contact_record', 'groundhogg' ); ?></th>
                <th><?php _ex( 'Type', 'contact_record', 'groundhogg' ); ?></th>
                <th><?php _ex( 'Replacement Code', 'contact_record', 'groundhogg' ); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php

            $files = $contact->get_meta('files');

            if (empty($files)):
                ?>
                <tr>
                    <td colspan="4"><?php _ex( 'This contact has no files...', 'contact_record', 'groundhogg' ); ?></td>
                </tr>
            <?php
            else:

                foreach ($files as $key => $item):

                    if (!isset($item['file'])) {
                        continue;
                    }

                    $info = pathinfo($item['file']);
                    ?>
                    <tr>
                        <td><?php printf("<a href='%s' target='_blank'>%s</a>", $item['url'], esc_html($info['basename'])); ?></td>
                        <td><?php esc_html_e(size_format(filesize($item['file']))); ?></td>
                        <td><?php esc_html_e($info['extension']); ?></td>
                        <td><?php echo '{files.' . intval( $key ) . '}'; ?></td>
                    </tr>
                <?php
                endforeach;
            endif;
            ?>
            </tbody>
            <tfoot>
            <tr>
                <th><?php _ex( 'Name', 'contact_record', 'groundhogg' ); ?></th>
                <th><?php _ex( 'Size', 'contact_record', 'groundhogg' ); ?></th>
                <th><?php _ex( 'Type', 'contact_record', 'groundhogg' ); ?></th>
                <th><?php _ex( 'Replacement Code', 'contact_record', 'groundhogg' ); ?></th>
            </tr>
            </tfoot>
        </table>
        <div>
            <p class="description"><?php _ex( 'Upload files: ', 'contact_record', 'groundhogg' ); ?><input type="file" name="files[]" multiple></p>
            <p class="description"><?php _ex( 'Click <b>Update Contact</b> when ready to upload.', 'contact_record', 'groundhogg' ); ?></p>
        </div>
    </div>
    <!-- END FILES -->

    <?php
}

add_action( 'wpgh_contact_record_tab_meta_data', 'wpgh_contact_record_section_custom_meta' );

/**
 * @param $contact WPGH_Contact
 */
function wpgh_contact_record_section_custom_meta( $contact ){
    ?>
    <?php do_action( 'wpgh_contact_edit_before_meta', $contact->ID ); ?>
    <!-- META -->
    <h2><?php _ex( 'Custom Meta', 'contact_record', 'groundhogg' ); ?></h2>
    <table id='meta-table' class="form-table" >
        <tbody>
        <tr>
            <th>
                <button type="button" class="button-secondary addmeta"><?php _ex( 'Add Meta', 'contact_record', 'groundhogg' ); ?></button>
                <div class="hidden">
                    <span class="metakeyplaceholder"><?php esc_attr_e( 'Key' ); ?></span>
                    <span class="metavalueplaceholder"><?php esc_attr_e( 'Value' ); ?></span>
                </div>
            </th>
        </tr>
        <?php

        //this meta data will not be shown in the meta data section.
        $meta_exclude_list = apply_filters( 'wpgh_exclude_meta_list', array(
            'lead_source',
            'source_page',
            'page_source',
            'terms_agreement',
            'terms_agreement_date',
            'gdpr_consent',
            'gdpr_consent_date',
            'primary_phone',
            'primary_phone_extension',
            'street_address_1',
            'street_address_2',
            'time_zone',
            'city',
            'postal_zip',
            'region',
            'country',
            'notes',
            'files',
            'company_name',
            'company_address',
            'job_title',
            'ip_address',
            'last_optin',
            'last_sent',
            'country_name',
            'region_code',
        ) );

        $meta = WPGH()->contact_meta->get_meta( $contact->ID );

        foreach ( $meta as $meta_key => $value ):

            if ( ! in_array( $meta_key, $meta_exclude_list ) ):
                $value = $value[ 0 ]; ?>
                <tr id="meta-<?php esc_attr_e( $meta_key )?>">
                    <th>
                        <?php esc_html_e( $meta_key ); ?>
                        <p class="description">{_<?php esc_html_e( $meta_key ); ?>}</p>
                    </th>
                    <td>
                        <?php

                        if ( strpos( $value, PHP_EOL  ) !== false ){

                            $args = array(
                                'name' => 'meta[' . $meta_key . ']',
                                'id'   => $meta_key,
                                'value' => $value
                            );

                            echo WPGH()->html->textarea( $args );

                        } else {

                            $args = array(
                                'name' => 'meta[' . $meta_key . ']',
                                'id'   => $meta_key,
                                'value' => $value
                            );

                            echo WPGH()->html->input( $args );

                        }
                        ?>

                        <span class="row-actions"><span class="delete"><a style="text-decoration: none" href="javascript:void(0)" class="deletemeta"><span class="dashicons dashicons-trash"></span></a></span></span>
                    </td>
                </tr>
            <?php endif;
        endforeach; ?>
        <?php do_action( 'wpgh_contact_edit_meta', $contact->ID ); ?>
        </tbody>
    </table>

    <?php
}

add_action( 'wpgh_contact_record_tab_activity', 'wpgh_contact_record_section_activity' );


/**
 * @param $contact WPGH_Contact
 */
function wpgh_contact_record_section_activity( $contact )
{
    ?>
    <?php do_action('wpgh_contact_edit_before_history', $contact->ID ); ?>
    <!-- UPCOMING EVENTS -->
    <div style="max-width: 800px">
        <h2><?php _ex( 'Upcoming Events', 'contact_record', 'groundhogg' ); ?></h2>
        <p class="description"><?php _ex( 'Any upcoming funnel steps will show up here. you can choose to cancel them or to run them immediately.', 'contact_record', 'groundhogg' ); ?></p>
        <?php
        $table = new WPGH_Contact_Events_Table( 'waiting' );
        $table->prepare_items();
        $table->display(); ?>
        <!-- FUNNNEL HISTORY -->
        <h2><?php _ex( 'Recent Funnel History', 'contact_record', 'groundhogg' ); ?></h2>
        <p class="description"><?php _ex( 'Any previous funnel steps will show up here. You can choose run them again.<br/>This report only shows the 10 most recent events, to see more you can see all this contact\'s history in the event queue.', 'contact_record', 'groundhogg' ); ?></p>
        <?php
        $table = new WPGH_Contact_Events_Table( 'complete' );
        $table->prepare_items();
        $table->display(); ?>
    </div>
    <!-- EMAIL HISTORY -->
    <h2><?php _ex( 'Recent Email History', 'contact_record', 'groundhogg' ); ?></h2>
    <div style="max-width: 800px">
        <?php $table = new WPGH_Contact_Activity_Table( );
        $table->prepare_items();
        $table->display(); ?>
        <p class="description"><?php _ex( 'This is where you can check if this contact is interacting with your emails.', 'contact_record', 'groundhogg' ); ?></p>
    </div>

    <?php
}

foreach ( $tabs as $tab => $tab_name ):

    ?><div class="tab-content-wrapper <?php if ( $tab !== $active_tab ){ echo 'hidden'; }; ?>" id="<?php echo 'tab_' . esc_attr( $tab ) . '_content'; ?>">
    <?php do_action('wpgh_contact_record_tab_' . $tab, $contact); ?>
    <?php do_action("groundhogg/contact/record/tab/{$tab}", $contact); ?>
    </div><?php

endforeach;

    ?>
    <!-- THE END -->
    <?php do_action( 'wpgh_contact_edit_after', $id ); ?>
    <div class="edit-contact-actions">
        <p class="submit">
            <?php submit_button(_x( 'Update Contact', 'action', 'groundhogg' ), 'primary', 'update', false ); ?>
            <span id="delete-link"><a class="delete" href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=gh_contacts&action=delete&contact='. $id ), 'delete'  ) ?>"><?php _e( 'Delete' ); ?></a></span>
        </p>
    </div>

    <?php echo WPGH()->html->input( array( 'type' => 'hidden', 'name' => 'active_tab', 'id' => 'active-tab' ) ); ?>

</form>
<?php if ( ! $contact->user ): ?>
<form id="create-user-form" action="<?php echo admin_url( 'user-new.php' ); ?>" method="post">
    <input type="hidden" name="createuser" value="1">
    <input type="hidden" name="first_name" value="<?php esc_attr_e( $contact->first_name ); ?>">
    <input type="hidden" name="last_name" value="<?php esc_attr_e( $contact->last_name ); ?>">
    <input type="hidden" name="email" value="<?php esc_attr_e( $contact->email ); ?>">
    <input type="hidden" name="user_login" value="<?php esc_attr_e( $contact->email ); ?>">
</form>
<div id="manual-submission-container" class="hidden">
    <!-- Form Content -->
    hi
</div>
<?php endif;

do_action( 'groundhogg/contact/record/after/', $contact );
?>
