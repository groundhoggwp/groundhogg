<?php
namespace Groundhogg\Admin\Contacts;

use function Groundhogg\admin_page_url;
use function Groundhogg\convert_to_local_time;
use function Groundhogg\current_user_is;
use function Groundhogg\get_array_var;
use function Groundhogg\get_cookie;
use function Groundhogg\get_date_time_format;
use function Groundhogg\get_db;
use function Groundhogg\get_form_list;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use Groundhogg\Plugin;
use Groundhogg\Contact;
use Groundhogg\Preferences;
use Groundhogg\Step;
use Groundhogg\Submission;
use function Groundhogg\key_to_words;

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
 * add_action( 'contact_edit_before_history', 'my_settings_section' ); ( $id )
 *
 *
 * This will add your section right above the funnel events history section.
 *
 * To save your custom information you will need to hook into the save method which you do by...
 *
 * add_action( 'admin_update_contact_after', 'my_save_function' ); ($id)
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
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$id = absint( get_request_var( 'contact' ) );

$contact = Plugin::$instance->utils->get_contact( $id );

if ( ! $contact || ! $contact->exists() ) {
	wp_die( _x( 'This contact has been deleted.', 'contact_record', 'groundhogg' ) );
}

//include_once "class-wpgh-contact-activity-table.php";
//include_once "class-wpgh-contact-events-table.php";

/* Quit if */
if ( current_user_is( 'sales_manager' ) ) {
	if ( $contact->get_owner_id() !== get_current_user_id() ) {

		wp_die( _x( 'You are not the owner of this contact.', 'contact_record', 'groundhogg' ) );

	}
}

//var_dump( $contact );

/* Auto link the account before we see the create account form. */
$contact->auto_link_account();

$tabs = array(
	'general'      => _x( 'General Info', 'contact_record_tab', 'groundhogg' ),
	'meta_data'    => _x( 'Custom Info', 'contact_record_tab', 'groundhogg' ),
	'segmentation' => _x( 'Segmentation', 'contact_record_tab', 'groundhogg' ),
	'notes'        => _x( 'Notes', 'contact_record_tab', 'groundhogg' ),
	'files'        => _x( 'Files', 'contact_record_tab', 'groundhogg' ),
	'actions'      => _x( 'Actions', 'contact_record_tab', 'groundhogg' ),
	'activity'     => _x( 'Activity', 'contact_record_tab', 'groundhogg' ),
);

$tabs       = apply_filters( 'groundhogg/admin/contact/record/tabs', $tabs );
$cookie_tab = str_replace( 'tab_', '', get_cookie( 'gh_contact_tab', 'general' ) );
$active_tab = sanitize_key( get_request_var( 'active_tab', $cookie_tab ) );
?>
<div class="local-time" style="float: right; padding: 10px;font-size: 18px;">
	<?php _ex( 'Local Time:', 'groundhogg' ); ?>
    <span style="font-family: Georgia, Times New Roman, Bitstream Charter, Times, serif;font-weight: 400;"><?php echo date_i18n( get_date_time_format(), $contact->get_local_time() ); ?>
        </span>
</div>

<form method="post" class="" enctype="multipart/form-data">
	<?php wp_nonce_field( 'edit' ); ?>

    <div class="contact-details">

        <!-- Photo -->
        <div class="contact-picture">
			<?php echo html()->e( 'img', [
				'class'  => 'profile-picture',
				'title'  => __( 'Profile Picture' ),
				'width'  => 150,
				'height' => 150,
				'src'    => $contact->get_profile_picture()
			] ); ?>
        </div>
        <div class="basic-details">
            <!-- FIRST -->
			<?php

			echo html()->e( 'div', [ 'class' => 'details' ], html()->input( [
				'name'        => 'first_name',
				'title'       => __( 'First Name' ),
				'value'       => $contact->get_first_name(),
//            'readonly' => true,
				'class'       => 'auto-copy regular-text',
				'placeholder' => __( 'First Name' )
			] ) );

			echo html()->e( 'div', [ 'class' => 'details' ], html()->input( [
				'name'        => 'last_name',
				'title'       => __( 'Last Name' ),
				'value'       => $contact->get_last_name(),
//            'readonly' => true,
				'class'       => 'auto-copy regular-text',
				'placeholder' => __( 'Last Name' )
			] ) );

			echo html()->e( 'div', [ 'class' => 'details' ], [
				html()->input( [
					'title'       => __( 'Email' ),
					'name'        => 'email_readonly',
					'value'       => $contact->get_email(),
					'readonly'    => true,
					'placeholder' => __( 'Email' ),
					'class'       => 'auto-copy regular-text',
					'style'       => [
						'max-width' => '18em'
					]
				] ),
				html()->e( 'a', [
					'class' => 'button',
					'title' => __( 'Send Email', 'groundhogg' ),
					'href'  => sprintf( 'mailto:%s', $contact->get_email() )
				], '<span class="dashicons dashicons-email"></span>' )
			] );

			if ( $contact->get_phone_number() ) {
				echo html()->e( 'div', [ 'class' => 'details' ], [
					html()->input( [
						'name'        => 'primary_phone_readonly',
						'title'       => __( 'Phone' ),
						'value'       => $contact->get_phone_number(),
						'placeholder' => __( 'Phone Number' ),
						'readonly'    => true,
						'class'       => 'auto-copy regular-text',
						'style'       => [
							'max-width' => '18em'
						]
					] ),
					html()->e( 'a', [
						'class' => 'button',
						'title' => __( 'Call Now', 'groundhogg' ),
						'href'  => sprintf( 'tel:%s', $contact->get_phone_number() )
					], '<span class="dashicons dashicons-phone"></span>' )
				] );
			}


			?>
            <!-- LAST -->
            <!-- EMAIL -->
            <!-- PHONE -->
        </div>
        <div class="wp-clearfix"></div>
    </div>
    <div class="wp-clearfix"></div>

	<?php do_action( 'groundhogg/contact/record/nav/before', $contact ); ?>

    <!-- BEGIN TABS -->
    <h2 class="nav-tab-wrapper">
		<?php foreach ( $tabs as $id => $tab ): ?>
            <a href="javascript:void(0)" class="nav-tab <?php echo $active_tab == $id ? 'nav-tab-active' : ''; ?>"
               id="<?php echo 'tab_' . esc_attr( $id ); ?>"><?php _e( $tab, 'groundhogg' ); ?></a>
		<?php endforeach; ?>
		<?php do_action( 'groundhogg/contact/record/nav/inside', $contact ); ?>
    </h2>

	<?php do_action( 'groundhogg/contact/record/nav/after', $contact ); ?>

    <!-- END TABS -->
	<?php

	add_action( 'groundhogg/admin/contact/record/tab/general', '\Groundhogg\Admin\Contacts\contact_record_general_info' );

	/**
	 * Contact Info
	 *
	 * @param $contact Contact
	 *
	 * @throws \Exception
	 */
	function contact_record_general_info( $contact ) {
		?>
        <h2><?php _e( 'Contact Information' ); ?></h2>
        <!-- GENERAL NAME INFO -->
        <table class="form-table">
            <tbody>
            <tr>
                <th><label for="email"><?php echo _x( 'Email', 'contact_record', 'groundhogg' ) ?></label></th>

                <td><?php $args = array(
						'type'  => 'email',
						'id'    => 'email',
						'name'  => 'email',
						'value' => $contact->get_email(),
					);
					echo Plugin::$instance->utils->html->input( $args ); ?>
                    <span class="row-actions"><a
                                title="<?php printf( esc_attr__( 'Visit %s', 'groundhogg' ), substr( $contact->get_email(), strpos( $contact->get_email(), '@' ) + 1 ) ); ?>"
                                style="text-decoration: none" target="_blank"
                                href="<?php echo esc_url( substr( $contact->get_email(), strpos( $contact->get_email(), '@' ) + 1 ) ); ?>"><span
                                    class="dashicons dashicons-external"></span></a>
                <a title="<?php esc_attr_e( 'Send email.', 'groundhogg' ); ?>" style="text-decoration: none"
                   target="_blank" href="mailto:<?php echo $contact->get_email(); ?>"><span
                            class="dashicons dashicons-email"></span></a></span>
                    <div class="email-status">
                        <p><?php echo '<b>' . _x( 'Email Status', 'contact_record', 'groundhogg' ) . ': </b>' . Plugin::$instance->preferences->get_optin_status_text( $contact->get_id() ); ?></p>
						<?php do_action( 'groundhogg/contact/record/email_status/after', $contact ); ?>
                    </div>
					<?php if ( $contact->get_optin_status() !== Preferences::UNSUBSCRIBED ): ?>
                        <div id="manual-unsubscribe" style="margin-bottom: 10px;">
                            <label><input type="checkbox" name="unsubscribe"
                                          value="1"><?php _ex( 'Mark as unsubscribed.', 'contact_record', 'groundhogg' ); ?>
                            </label>
                        </div>
					<?php endif; ?>
					<?php if ( $contact->get_optin_status() !== Preferences::CONFIRMED ): ?>
                        <div id="manual-confirmation">
                            <label><input type="checkbox" name="manual_confirm" id="manual-confirm"
                                          value="1"><?php _ex( 'Manually confirm this email address.', 'contact_record', 'groundhogg' ); ?>
                            </label>
                            <div id="confirmation-reason" class="hidden">
								<?php echo Plugin::$instance->utils->html->textarea( [
									'name'        => 'confirmation_reason',
									'cols'        => 50,
									'rows'        => 2,
									'placeholder' => __( 'Confirmation reason...', 'groundhogg' )
								] ); ?>
                            </div>
                        </div>
                        <script>jQuery(function ($) {
                                $("#manual-confirm").on("change", function () {
                                    $("#confirmation-reason").toggleClass("hidden");
                                });
                            });</script>
					<?php endif;

					do_action( 'groundhogg/contact/record/email/after', $contact );

					?>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="primary_phone"><?php echo _x( 'Primary Phone', 'contact_record', 'groundhogg' ) ?></label>
                </th>
                <td><?php $args = array(
						'type'  => 'tel',
						'id'    => 'primary_phone',
						'name'  => 'primary_phone',
						'value' => $contact->get_meta( 'primary_phone' ),
					);
					echo Plugin::$instance->utils->html->input( $args ); ?></td>
            </tr>
            <tr>
                <th>
                    <label for="primary_phone_extension"><?php echo _x( 'Phone Extension', 'contact_record', 'groundhogg' ) ?></label>
                </th>
                <td><?php $args = array(
						'id'    => 'primary_phone_extension',
						'name'  => 'primary_phone_extension',
						'value' => $contact->get_meta( 'primary_phone_extension' ),
					);
					echo Plugin::$instance->utils->html->input( $args ); ?></td>
            </tr>
            </tbody>
        </table>
        <h2><?php _e( 'User Account' ); ?></h2>
        <table class="form-table">
			<?php if ( $contact->get_userdata() ): ?>

                <tr>
                    <th><label for="username"><?php _e( 'Username' ) ?></label></th>
                    <td><?php printf( "<a href='%s'>%s</a>", admin_url( 'user-edit.php?user_id=' . $contact->get_user_id() ), $contact->get_userdata()->user_login ); ?>
                        <span class="row-actions">
                    <?php submit_button( _x( 'Unlink', 'action', 'groundhogg' ), 'secondary', 'unlink_user', false ); ?>
                </span>
                    </td>
                </tr>
			<?php else: ?>
                <tr>
                    <th>
                        <label for="create_account"><?php echo _x( 'Create New Account?', 'contact_record', 'groundhogg' ) ?></label>
                    </th>
                    <td>
                        <button type="button"
                                class="button button-secondary create-user-account"><?php _e( 'Create User Account' ); ?></button>
                        <p class="description"><?php _ex( 'This contact does not have an associated user account? Would you like to create one?', 'contact_record', 'groundhogg' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="link_existing"><?php echo _x( 'Link Existing Account?', 'contact_record', 'groundhogg' ) ?></label>
                    </th>
                    <td><?php wp_dropdown_users( array(
							'show_option_none'  => _x( 'Select a User Account (optional)', 'contact_record', 'groundhogg' ),
							'option_none_value' => 0
						) ); ?>
                        <p class="description"><?php _ex( 'You can link an existing user account to this contact.', 'contact_record', 'groundhogg' ); ?></p>
                    </td>
                </tr>
			<?php endif; ?>
        </table>
		<?php do_action( 'groundhogg/admin/contact/record/user/after', $contact ); ?>
        <!-- GENERAL CONTACT INFO -->
        <h2><?php _e( 'Personal Info' ); ?></h2>
        <table class="form-table">
            <tbody>
            <tr>
                <th><?php echo _x( 'Birthday', 'contact_record', 'groundhogg' ) ?></th>
                <td><?php

					$years  = array_reverse( range( date( 'Y' ) - 100, date( 'Y' ) ) );
					$years  = array_combine( $years, $years );
					$days   = range( 1, 31 );
					$days   = array_combine( $days, $days );
					$months = [];

					for ( $i = 1; $i <= 12; $i ++ ) {
						$timestamp    = mktime( 0, 0, 0, $i, 1, date( 'Y' ) );
						$months[ $i ] = date_i18n( "F", $timestamp );
					}

					$birthday       = $contact->get_meta( 'birthday' );
					$birthday_parts = [];

					if ( $birthday ) {
						$birthday_parts = explode( '-', $birthday );
					}

					echo html()->e( 'span', [], [
						// Year
						html()->dropdown( [
							'name'        => 'birthday[year]',
							'id'          => 'birthday_year',
							'options'     => $years,
							'multiple'    => false,
							'option_none' => __( 'Year', 'groundhogg' ),
							'selected'    => get_array_var( $birthday_parts, 0 ),
							'class'       => 'gh-input'
						] ),
						html()->dropdown( [
							'name'        => 'birthday[month]',
							'id'          => 'birthday_month',
							'options'     => $months,
							'multiple'    => false,
							'option_none' => __( 'Month', 'groundhogg' ),
							'selected'    => get_array_var( $birthday_parts, 1 ),
							'class'       => 'gh-input'
						] ),
						html()->dropdown( [
							'name'        => 'birthday[day]',
							'id'          => 'birthday_day',
							'options'     => $days,
							'multiple'    => false,
							'option_none' => __( 'Day', 'groundhogg' ),
							'selected'    => get_array_var( $birthday_parts, 2 ),
							'class'       => 'gh-input'
						] ),
					] );

					if ( $contact->get_age() ) {
						printf( __( ' %d years old.', 'groundhogg' ), $contact->get_age() );
					}

					?></td>
            </tr>
            </tbody>
        </table>

		<?php do_action( 'groundhogg/contact/record/contact_info/after', $contact ); ?>

        <!-- Company info -->
        <h2><?php _ex( 'Company Info', 'contact_record', 'groundhogg' ); ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="company_name"><?php echo _x( 'Company Name', 'contact_record', 'groundhogg' ) ?></label>
                </th>
                <td><?php $args = array(
						'id'    => 'company_name',
						'name'  => 'company_name',
						'value' => $contact->get_meta( 'company_name' ),
					);
					echo Plugin::$instance->utils->html->input( $args ); ?>
                </td>
            </tr>
            <tr>
                <th><label for="job_title"><?php echo _x( 'Job Title', 'contact_record', 'groundhogg' ) ?></label></th>
                <td><?php $args = array(
						'id'    => 'job_title',
						'name'  => 'job_title',
						'value' => $contact->get_meta( 'job_title' ),
					);
					echo Plugin::$instance->utils->html->input( $args ); ?>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="company_address"><?php echo _x( 'Full Company Address', 'contact_record', 'groundhogg' ) ?></label>
                </th>
                <td><?php $args = array(
						'id'    => 'company_address',
						'name'  => 'company_address',
						'value' => $contact->get_meta( 'company_address' ),
					);
					echo Plugin::$instance->utils->html->input( $args ); ?>
                </td>
            </tr>
        </table>

		<?php do_action( 'groundhogg/contact/record/company_info/after', $contact ); ?>

        <!-- ADDRESS -->
        <h2><?php _ex( 'Location', 'contact_record', 'groundhogg' ); ?></h2>
        <table class="form-table">
            <tbody>
            <tr>
                <th>
                    <label for="street_address_1"><?php echo _x( 'Street Address 1', 'contact_record', 'groundhogg' ) ?></label>
                </th>
                <td><?php $args = array(
						'id'    => 'street_address_1',
						'name'  => 'street_address_1',
						'value' => $contact->get_meta( 'street_address_1' ),
					);
					echo Plugin::$instance->utils->html->input( $args ); ?>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="street_address_2"><?php echo _x( 'Street Address 2', 'contact_record', 'groundhogg' ) ?></label>
                </th>
                <td><?php $args = array(
						'id'    => 'street_address_2',
						'name'  => 'street_address_2',
						'value' => $contact->get_meta( 'street_address_2' ),
					);
					echo Plugin::$instance->utils->html->input( $args ); ?>
                </td>
            </tr>
            <tr>
                <th><label for="city"><?php echo _x( 'City', 'contact_record', 'groundhogg' ) ?></label></th>
                <td><?php $args = array(
						'id'    => 'city',
						'name'  => 'city',
						'value' => $contact->get_meta( 'city' ),
					);
					echo Plugin::$instance->utils->html->input( $args ); ?>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="postal_zip"><?php echo _x( 'Postal/Zip Code', 'contact_record', 'groundhogg' ) ?></label>
                </th>
                <td><?php $args = array(
						'id'    => 'postal_zip',
						'name'  => 'postal_zip',
						'value' => $contact->get_meta( 'postal_zip' ),
					);
					echo Plugin::$instance->utils->html->input( $args ); ?>
                </td>
            </tr>
            <tr>
                <th><label for="region"><?php echo _x( 'State/Province', 'contact_record', 'groundhogg' ) ?></label>
                </th>
                <td><?php $args = array(
						'id'    => 'region',
						'name'  => 'region',
						'value' => $contact->get_meta( 'region' ),
					);
					echo Plugin::$instance->utils->html->input( $args ); ?>
                </td>
            </tr>
            <tr>
                <th><label for="country"><?php echo _x( 'Country', 'contact_record', 'groundhogg' ) ?></label></th>
                <td>
                    <div style="max-width: 338px">
						<?php $args = array(
							'id'          => 'country',
							'name'        => 'country',
							'selected'    => $contact->get_meta( 'country' ),
							'data'        => Plugin::$instance->utils->location->get_countries_list(),
							'placeholder' => _x( 'Select a Country', 'contact_record', 'groundhogg' ),
						);
						echo Plugin::$instance->utils->html->select2( $args ); ?>
                    </div>
                </td>
            </tr>
            <tr>
                <th><label for="ip_address"><?php echo _x( 'IP Address', 'contact_record', 'groundhogg' ) ?></label>
                </th>
                <td><?php $args = array(
						'id'    => 'ip_address',
						'name'  => 'ip_address',
						'value' => $contact->get_meta( 'ip_address' ),
					);
					echo Plugin::$instance->utils->html->input( $args ); ?>
					<?php if ( $contact->get_ip_address() && $contact->get_ip_address() !== '::1' ): ?>
                        <span class="row-actions">
                    <?php submit_button( _x( 'Extrapolate Location', 'action', 'groundhogg' ), 'secondary', 'extrapolate_location', false ); ?>
                </span>
						<?php ?>
					<?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><label for="time_zone"><?php echo _x( 'Time Zone', 'contact_record', 'groundhogg' ) ?></label></th>
                <td>
                    <div style="max-width: 338px">
						<?php $args = array(
							'id'       => 'time_zone',
							'name'     => 'time_zone',
							'data'     => Plugin::$instance->utils->location->get_time_zones(),
							'selected' => $contact->get_meta( 'time_zone' ),
						);
						echo Plugin::$instance->utils->html->select2( $args ); ?></div>
                </td>
            </tr>
            </tbody>
        </table>

        <!-- MARKETING COMPLIANCE INFORMATION -->
        <h2><?php _ex( 'Compliance', 'contact_record', 'groundhogg' ); ?></h2>
        <table class="form-table">
            <tbody>
            <tr>
                <th><?php _ex( 'Agreed To Terms', 'contact_record', 'groundhogg' ); ?></th>
                <td><?php echo ( $contact->get_meta( 'terms_agreement' ) === 'yes' ) ? sprintf( "%s: %s", __( 'Agreed' ), $contact->get_meta( 'terms_agreement_date' ) ) : '&#x2014;'; ?></td>
            </tr>
			<?php if ( Plugin::$instance->preferences->is_gdpr_enabled() ): ?>
                <tr>
                    <th><?php _e( 'GDPR Consent' ); ?></th>
                    <td><?php echo ( $contact->get_meta( 'gdpr_consent' ) === 'yes' ) ? sprintf( "%s: %s", __( 'Agreed' ), $contact->get_meta( 'gdpr_consent_date' ) ) : '&#x2014;'; ?></td>
                </tr>
			<?php endif; ?>
            </tbody>
        </table>

		<?php
	}

	add_action( 'groundhogg/admin/contact/record/tab/segmentation', '\Groundhogg\Admin\Contacts\contact_record_section_segmentation' );

	/**
	 * @param $contact Contact
	 */
	function contact_record_section_segmentation( $contact ) {
		?>

        <!-- SEGMENTATION AND LEADSOURCE -->
        <h2><?php _ex( 'Segmentation', 'contact_record', 'groundhogg' ); ?></h2>
        <table class="form-table">
            <tbody>
            <tr>
                <th><?php _ex( 'Owner', 'contact_record', 'groundhogg' ); ?></th>
                <td><?php echo Plugin::$instance->utils->html->dropdown_owners( array( 'selected' => ( $contact->get_ownerdata() ) ? $contact->get_owner_id() : 0 ) ); ?>
                </td>
            </tr>
            <tr>
                <th><?php _ex( 'Source Page', 'contact_record', 'groundhogg' ); ?></th>
                <td><?php $args = array(
						'id'    => 'source_page',
						'name'  => 'source_page',
						'value' => $contact->get_meta( 'source_page' ),
					);
					echo Plugin::$instance->utils->html->input( $args ); ?>
                    <span class="row-actions">
                    <a style="text-decoration: none" target="_blank"
                       href="<?php echo esc_url( $contact->get_meta( 'source_page' ) ); ?>"><span
                                class="dashicons dashicons-external"></span></a>
                </span>
                    <p class="description">
						<?php _e( "This is the page which the contact first submitted a form.", 'groundhogg' ); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><?php _ex( 'Lead Source', 'contact_record', 'groundhogg' ); ?></th>
                <td><?php $args = array(
						'id'    => 'lead_source',
						'name'  => 'lead_source',
						'value' => $contact->get_meta( 'lead_source' ),
					);
					echo Plugin::$instance->utils->html->input( $args ); ?>
                    <span class="row-actions">
                    <a style="text-decoration: none" target="_blank"
                       href="<?php echo esc_url( $contact->get_meta( 'lead_source' ) ); ?>"><span
                                class="dashicons dashicons-external"></span></a>
                </span>
                    <p class="description"><?php _e( "This is where the contact originated from.", 'groundhogg' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="tags"><?php echo _x( 'Tags', 'contact_record', 'groundhogg' ) ?></label></th>
                <td>
                    <div style="max-width: 400px;">
						<?php

						//print_r( $contact->tags );

						$args = array(
							'id'       => 'tags',
							'name'     => 'tags[]',
							'selected' => $contact->get_tags(),
						);
						echo Plugin::$instance->utils->html->tag_picker( $args ); ?>
                        <p class="description"><?php _ex( 'Add new tags by hitting [Enter] or by typing a [,].', 'contact_record', 'groundhogg' ); ?></p>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
		<?php
	}

	add_action( 'groundhogg/admin/contact/record/tab/notes', '\Groundhogg\Admin\Contacts\contact_record_section_notes' );

	/**
	 * @param $contact Contact
	 */
	function contact_record_section_notes( $contact ) {
		?>
        <!-- NOTES -->
        <h2><?php _e( 'Notes' ); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php _ex( 'Add Note', 'contact_record', 'groundhogg' ); ?></th>
                <td><?php $args = array(
						'id'         => 'add-new-note',
						'name'       => 'add_new_note',
						'value'      => '',
						'rows'       => 3,
						'cols'       => 64,
						'attributes' => ''
					);
					echo html()->textarea( $args );


					echo html()->wrap( html()->button( [
						'id'    => 'add-note',
						'name'  => 'add_note',
						'text' => __( 'Add Note', 'groundhogg' ),

					] ), 'p' );

					?>
                </td>
            </tr>
        </table>
        <div class="add-new-notes"></div>

		<?php
		$notes = $contact->get_notes_array();

		if ( $notes ) {

			foreach ( $notes as $note ) {

				$context = key_to_words( $note->context );
				if ( absint( $note->user_id ) ) {
					$user    = get_userdata( absint( $note->user_id ) );
					$context = sprintf( '%s %s', $user->first_name, $user->last_name );
				}

				$label = __( "Added", 'groundhogg' );
				if ( $note->date_created !== date( 'Y-m-d H:i:s', convert_to_local_time( absint( $note->timestamp ) ) ) ) {
					$label = __( 'Last edited', 'groundhogg' );
				}

				?>
                <div class="gh-notes-wrap">

                    <div class="display-notes gh-notes-container" data-note-id="<?php echo $note->ID; ?>">
						<?php echo wpautop( esc_html( $note->content ) ); ?>
                    </div>


                    <div class="edit-note-module "></div>
                    <div class='notes-time-right'>
                        <span class="note-date">
                            <?php _e( sprintf( '%s By %s on %s', $label, $context, date( get_date_time_format(), absint( convert_to_local_time( absint( $note->timestamp ) ) ) ) ), 'groundhogg' ) ?>
                        </span>
                        &nbsp;|&nbsp;
                        <span class="edit-notes">
                                <a style="text-decoration: none" href="javascript:void(0)">
                                    <span class="dashicons dashicons-edit"></span>
                                </a>
                            </span>
                        &nbsp;|&nbsp;
                        <span class="delete-note">
                                <a style="text-decoration: none" href="javascript:void(0)">
                                    <span class="dashicons dashicons-trash delete"></span>
                                </a>
                            </span>
                    </div>
                    <div class="wp-clearfix"></div>
                </div>
				<?php
			}
		}
		if ( $contact->get_meta( 'notes' ) ) {
			?>


            <table>
                <tbody>
                <tr>
                    <td>
						<?php $args = array(
							'id'       => 'notes',
							'name'     => 'notes',
							'value'    => $contact->get_meta( 'notes' ),
							'rows'     => 30,
							'readonly' => true,
							'style'    => [ 'width' => '820px' ]
						);
						echo Plugin::$instance->utils->html->textarea( $args ); ?>
                    </td>
                </tr>
                </tbody>
            </table>
			<?php
		}
	}

	add_action( 'groundhogg/admin/contact/record/tab/actions', '\Groundhogg\Admin\Contacts\contact_record_section_actions' );

	/**
	 * @param $contact contact
	 */
	function contact_record_section_actions( $contact ) {
		?>
        <!-- ACTIONS -->
        <h2><?php _e( 'Actions' ); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php _ex( 'Send Email', 'contact_record', 'groundhogg' ); ?></th>
                <td>
                    <div style="max-width: 400px">
						<?php echo Plugin::$instance->utils->html->dropdown_emails( array() ); ?>
                        <div class="row-actions">
                            <button type="submit" name="send_email" value="send"
                                    class="button"><?php _e( 'Send' ); ?></button>
                        </div>
                    </div>
                </td>
            </tr>

            <tr>
                <th><?php _ex( 'Add To Funnel', 'contact_record', 'groundhogg' ); ?></th>
                <td>
                    <div style="max-width: 400px">
						<?php

						$steps   = Plugin::$instance->dbs->get_db( 'steps' )->query( [
							'orderby' => 'step_order',
							'order'   => 'asc'
						] );
						$options = array();
						foreach ( $steps as $step ) {
							$step = Plugin::$instance->utils->get_step( $step->ID );
							if ( $step && $step->is_active() ) {

								$funnel_name                          = $step->get_funnel()->get_title();
								$options[ $funnel_name ][ $step->ID ] = sprintf( "%d. %s (%s)", $step->get_order(), $step->get_title(), str_replace( '_', ' ', $step->get_type() ) );
							}
						}

						echo Plugin::$instance->utils->html->select2( [
							'name'     => 'add_contacts_to_funnel_step_picker',
							'id'       => 'add_contacts_to_funnel_step_picker',
							'data'     => $options,
							'multiple' => false,
						] );

						?>
                        <div class="row-actions">
                            <button type="submit" name="start_funnel" value="start"
                                    class="button"><?php _e( 'Start' ); ?></button>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <th><?php _ex( 'Internal Form', 'contact_record', 'groundhogg' ); ?></th>
                <td>
                    <div style="max-width: 400px;">

						<?php

						$form_options = get_form_list();

						$default = get_array_var( array_keys( $form_options ), 0 );

						echo Plugin::$instance->utils->html->select2( [
							'name'        => 'manual_form_submission',
							'id'          => 'manual_form_submission',
							'class'       => 'manual-submission gh-select2',
							'data'        => $form_options,
							'multiple'    => false,
							'selected'    => [ $default ],
							'placeholder' => __( 'Please select a form', 'groundhogg' ),
						] );

						?>
                        <div class="row-actions">
                            <button type="submit" name="switch_form" value="switch_form"
                                    class="button"><?php _e( 'Submit Form', 'groundhogg' ); ?></button>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
		<?php
	}

	add_action( 'groundhogg/admin/contact/record/tab/files', '\Groundhogg\Admin\Contacts\contact_record_section_files' );

	/**
	 * @param $contact Contact
	 */
	function contact_record_section_files( $contact ) {
		?>
        <!-- BEGIN FILES -->
        <h2><?php _ex( 'Files', 'contact_record', 'groundhogg' ); ?></h2>
        <div style="max-width: 800px;">
            <style>
                .wp-admin .gh-file-uploader {
                    width: 100%;
                    margin: auto;
                    padding: 30px !important;
                    box-sizing: border-box;
                    background: #F9F9F9;
                    border: 2px dashed #e5e5e5;
                    text-align: center;
                    margin-top: 10px;
                }
            </style>

			<?php

			$files = $contact->get_files();

			$rows = [];

			foreach ( $files as $key => $item ) {

				$info = pathinfo( $item['file_path'] );

				$rows[] = [
					sprintf( "<a href='%s' target='_blank'>%s</a>", esc_url( $item['file_url'] ), esc_html( $info['basename'] ) ),
					esc_html( size_format( filesize( $item['file_path'] ) ) ),
					esc_html( $info['extension'] ),
					html()->e( 'span', [ 'class' => 'row-actions' ], [
						html()->e( 'span', [ 'class' => 'delete' ],
							html()->e( 'a', [
								'class' => 'delete',
								'href'  => admin_page_url( 'gh_contacts', [
									'action'   => 'remove_file',
									'file'     => $info['basename'],
									'contact'  => $contact->get_id(),
									'_wpnonce' => wp_create_nonce( 'remove_file' )
								] )
							], __( 'Delete' ) ) ),
					] )
				];

			}

			html()->list_table( [ 'class' => 'files', 'id' => 'files' ], [
				_x( 'Name', 'contact_record', 'groundhogg' ),
				_x( 'Size', 'contact_record', 'groundhogg' ),
				_x( 'Type', 'contact_record', 'groundhogg' ),
				_x( 'Actions', 'contact_record', 'groundhogg' ),
			], $rows );
			?>
            <div>
                <input class="gh-file-uploader" type="file" name="files[]" multiple>
            </div>
        </div>
        <!-- END FILES -->
		<?php
	}

	add_action( 'groundhogg/admin/contact/record/tab/meta_data', '\Groundhogg\Admin\Contacts\contact_record_section_custom_meta' );

	/**
	 * @param $contact Contact
	 */
	function contact_record_section_custom_meta( $contact ) {
		?>
        <!-- META -->
        <h2><?php _ex( 'Custom Meta', 'contact_record', 'groundhogg' ); ?></h2>
        <table id='meta-table' class="form-table">
            <tbody>
            <tr>
                <th>
                    <button type="button"
                            class="button-secondary addmeta"><?php _ex( 'Add Meta', 'contact_record', 'groundhogg' ); ?></button>
                    <div class="hidden">
                        <span class="metakeyplaceholder"><?php esc_attr_e( 'Key' ); ?></span>
                        <span class="metavalueplaceholder"><?php esc_attr_e( 'Value' ); ?></span>
                    </div>
                </th>
            </tr>
			<?php

			//this meta data will not be shown in the meta data section.
			$meta_exclude_list = Plugin::$instance->admin->get_page( 'contacts' )->get_meta_key_exclusions();

			$meta = $contact->get_all_meta();

			foreach ( $meta as $meta_key => $value ):

				// Exclude serialized values...
				if ( ! in_array( $meta_key, $meta_exclude_list ) ): ?>

                    <tr id="meta-<?php esc_attr_e( $meta_key ) ?>">
                        <th>
							<?php esc_html_e( $meta_key ); ?>
                            <p>
                                <code class="meta-replacement-code"
                                      title="<?php esc_attr_e( 'Replacement code', 'groundhogg' ); ?>">
                                    {_<?php esc_html_e( $meta_key ); ?>}
                                </code>
                            </p>
                        </th>
                        <td>
							<?php

							if ( is_serialized( $value ) || is_array( $value ) || is_object( $value ) ) {
								$args = array(
									'name'     => 'meta[' . $meta_key . ']',
									'id'       => $meta_key,
									'value'    => 'SERIALIZED DATA',
									'type'     => 'text',
									'readonly' => true
								);
								echo Plugin::$instance->utils->html->input( $args );
							} elseif ( strpos( $value, PHP_EOL ) !== false ) {
								$args = array(
									'name'  => 'meta[' . $meta_key . ']',
									'id'    => $meta_key,
									'value' => $value
								);
								echo Plugin::$instance->utils->html->textarea( $args );
							} else {
								$args = array(
									'name'  => 'meta[' . $meta_key . ']',
									'id'    => $meta_key,
									'value' => $value
								);
								echo Plugin::$instance->utils->html->input( $args );
							}
							?>
                            <span class="row-actions"><span class="delete"><a style="text-decoration: none"
                                                                              href="javascript:void(0)"
                                                                              class="deletemeta"><span
                                                class="dashicons dashicons-trash"></span></a></span></span>
                        </td>
                    </tr>
				<?php endif;
			endforeach; ?>
            </tbody>
        </table>

		<?php
	}

	add_action( 'groundhogg/admin/contact/record/tab/activity', '\Groundhogg\Admin\Contacts\contact_record_section_activity' );


	/**
	 * @param $contact Contact
	 */
	function contact_record_section_activity( $contact ) {
		?>
        <!-- UPCOMING EVENTS -->
        <div style="max-width: 800px">
            <h2><?php _ex( 'Upcoming Events', 'contact_record', 'groundhogg' ); ?></h2>
            <p class="description"><?php _ex( 'Any upcoming funnel steps will show up here. you can choose to cancel them or to run them immediately.', 'contact_record', 'groundhogg' ); ?></p>
			<?php

			$table = new Tables\Contact_Events_Table( 'waiting' );
			$table->prepare_items();
			$table->display(); ?>
            <!-- FUNNNEL HISTORY -->
            <h2><?php _ex( 'Recent Funnel History', 'contact_record', 'groundhogg' ); ?></h2>
            <p class="description"><?php _ex( 'Any previous funnel steps will show up here. You can choose run them again.<br/>This report only shows the 10 most recent events, to see more you can see all this contact\'s history in the event queue.', 'contact_record', 'groundhogg' ); ?></p>
			<?php
			$table = new Tables\Contact_Events_Table( 'complete' );
			$table->prepare_items();
			$table->display(); ?>
        </div>
        <!-- EMAIL HISTORY -->
        <h2><?php _ex( 'Recent Email History', 'contact_record', 'groundhogg' ); ?></h2>
        <div style="max-width: 800px">
            <p class="description"><?php _ex( 'This is where you can check if this contact is interacting with your emails.', 'contact_record', 'groundhogg' ); ?></p>
			<?php $table = new Tables\Contact_Activity_Table();
			$table->prepare_items();
			$table->display(); ?>
        </div>
        <!-- Form Submissions -->
        <h2><?php _ex( 'Form Submissions', 'contact_record', 'groundhogg' ); ?></h2>
        <div style="max-width: 800px">
            <p class="description"><?php _ex( 'Any previous form submissions from this contact will show below as of version 2.0.', 'contact_record', 'groundhogg' ); ?></p>
			<?php

			$submission_ids = wp_parse_id_list( wp_list_pluck( get_db( 'submissions' )->query( [ 'contact_id' => $contact->get_id() ] ), 'ID' ) );

			$rows = [];

			foreach ( $submission_ids as $id ) {

				$submission = new Submission( $id );

				$row = [];

				$step = new Step( $submission->get_step_id() );

				$row[] = html()->e( 'a', [
					'href' => admin_page_url( 'gh_funnels', [
						'action' => 'edit',
						'funnel' => $step->get_funnel_id()
					] )
				], $step->get_step_title() );
				$row[] = $submission->get_date_created();
				$row[] = html()->textarea( [
					'style'    => [ 'width' => '100%' ],
					'rows'     => 5,
					'onfocus'  => "this.select()",
					'readonly' => true,
					'value'    => wp_json_encode( $submission->get_meta(), JSON_PRETTY_PRINT ),
				] );

				$rows[] = $row;
			}

			html()->list_table(
				[],
				[
					__( 'Form Name' ),
					__( 'Date Submitted' ),
					__( 'Submission Data' ),
				],
				$rows
			);

			?>
        </div>
		<?php
	}

	foreach ( $tabs as $tab => $tab_name ):

		?>
    <div class="tab-content-wrapper <?php if ( $tab !== $active_tab ) {
		echo 'hidden';
	}; ?>" id="<?php echo 'tab_' . esc_attr( $tab ) . '_content'; ?>">
		<?php


		/**
		 * @param $contact Contact the contact
		 * @param $tab     string if of the current tab
		 */
		do_action( "groundhogg/admin/contact/record/tab/{$tab}", $contact, $tab ); ?>
        </div><?php

	endforeach;

	?>
    <!-- THE END -->
    <div class="edit-contact-actions">
        <p class="submit">
			<?php \submit_button( _x( 'Update Contact', 'action', 'groundhogg' ), 'primary', 'update', false ); ?>
            <span id="delete-link"><a class="delete"
                                      href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=gh_contacts&action=delete&contact=' . $contact->get_id() ), 'delete' ) ?>"><?php _e( 'Delete' ); ?></a></span>
        </p>
    </div>

	<?php echo Plugin::$instance->utils->html->input( array(
		'type' => 'hidden',
		'name' => 'active_tab',
		'id'   => 'active-tab'
	) ); ?>

</form>
<?php if ( ! $contact->get_userdata() ): ?>
    <form id="create-user-form" action="<?php echo admin_url( 'user-new.php' ); ?>" method="post">
        <input type="hidden" name="createuser" value="1">
        <input type="hidden" name="first_name" value="<?php esc_attr_e( $contact->get_first_name() ); ?>">
        <input type="hidden" name="last_name" value="<?php esc_attr_e( $contact->get_last_name() ); ?>">
        <input type="hidden" name="email" value="<?php esc_attr_e( $contact->get_email() ); ?>">
        <input type="hidden" name="user_login" value="<?php esc_attr_e( $contact->get_email() ); ?>">
    </form>
    <div id="manual-submission-container" class="hidden">
        <!-- Form Content -->
        hi
    </div>
<?php endif;

do_action( 'groundhogg/contact/record/after', $contact );
?>
