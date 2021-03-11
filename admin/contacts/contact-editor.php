<?php
namespace Groundhogg\Admin\Contacts;

use Groundhogg\Tag;
use function Groundhogg\action_url;
use function Groundhogg\admin_page_url;
use function Groundhogg\array_map_with_keys;
use function Groundhogg\dashicon_e;
use function Groundhogg\get_array_var;
use function Groundhogg\get_cookie;
use function Groundhogg\get_date_time_format;
use function Groundhogg\get_db;
use function Groundhogg\get_form_list;
use function Groundhogg\get_request_var;
use function Groundhogg\get_tag_name;
use function Groundhogg\html;
use Groundhogg\Plugin;
use Groundhogg\Contact;
use Groundhogg\Preferences;
use Groundhogg\Step;
use Groundhogg\Submission;
use function Groundhogg\modal_link_url;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @var $contact Contact
 */

//var_dump( $contact );

/* Auto link the account before we see the create account form. */
$contact->auto_link_account();

$tabs = array(
	'general' => _x( 'General', 'contact_record_tab', 'groundhogg' ),
);

$tabs = apply_filters( 'groundhogg/admin/contact/record/tabs', $tabs );

$tabs['meta_data'] = _x( 'Meta', 'contact_record_tab', 'groundhogg' );
$tabs['actions']   = _x( 'Actions', 'contact_record_tab', 'groundhogg' );
$tabs['activity']  = _x( 'Activity', 'contact_record_tab', 'groundhogg' );

$tabs = apply_filters( 'groundhogg/admin/contact/record/tabs_after', $tabs );

$cookie_tab = str_replace( 'tab_', '', get_cookie( 'gh_contact_tab', 'general' ) );
$active_tab = sanitize_key( get_request_var( 'active_tab', $cookie_tab ) );
?>
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
			<div class="full-name"><?php dashicon_e( 'admin-users' ); ?><?php echo $contact->get_full_name(); ?></div>
			<div class="email">
				<?php dashicon_e( 'email' ); ?><?php echo html()->e( 'a', [
					'class' => 'trigger-popup',
					'href'  => modal_link_url( [
						'title'              => __( 'Send Email', 'groundhogg' ),
						'footer_button_text' => __( 'Save Changes' ),
						'source'             => 'email-form-wrap',
						'height'             => 600,
						'width'              => 500,
						'footer'             => 'false',
						'preventSave'        => 'true',
					] )
				], $contact->get_email() ) ?>
				<span
					class="status <?php echo $contact->is_marketable() ? 'green' : 'red'; ?>"><?php echo Preferences::get_preference_pretty_name( $contact->get_optin_status() ); ?></span>
			</div>
			<?php if ( $contact->get_phone_number() ): ?>
				<div
					class="phone"><?php dashicon_e( 'phone' ); ?><?php echo html()->e( 'a', [ 'href' => 'tel:' . $contact->get_phone_number() ], $contact->get_phone_number() ) ?>
					<?php if ( $contact->get_phone_extension() ): ?>
						<span
							class="extension"><?php printf( __( 'ext. %s', 'groundhogg' ), $contact->get_phone_extension() ) ?></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<?php if ( $contact->get_mobile_number() ): ?>
				<div class="mobile"><?php dashicon_e( 'smartphone' ); ?>
					<?php echo html()->e( 'a', [ 'href' => 'tel:' . $contact->get_mobile_number() ], $contact->get_mobile_number() ) ?>
				</div>
			<?php endif; ?>
			<?php if ( count( $contact->get_address() ) > 0 ): ?>
				<div class="location" title="<?php esc_attr_e( 'Location', 'groundhogg' ); ?>">
					<?php dashicon_e( 'admin-site' ); ?>
					<div class="address">
						<?php echo html()->e( 'a', [
							'href'   => 'https://www.google.com/maps/place/' . implode( ',+', $contact->get_address() ),
							'target' => '_blank'
						], implode( ', ', $contact->get_address() ) ) ?>
					</div>
				</div>
			<?php endif; ?>
			<div class="localtime" title="<?php esc_attr_e( 'Local time', 'groundhogg' ); ?>">
				<?php dashicon_e( 'clock' ); ?><?php echo date_i18n( get_date_time_format(), $contact->get_local_time() ) ?>
			</div>
			<?php do_action( 'groundhogg/admin/contact/basic_details', $contact ); ?>
		</div>
		<div class="wp-clearfix"></div>
		<div class="tags" title="<?php esc_attr_e( 'Tags' ); ?>"><?php dashicon_e( 'tag' ); ?>
			<?php foreach ( $contact->get_tags() as $tag ):
				$tag = new Tag( $tag ) ?><span
				class="tag"><?php esc_html_e( $tag->get_name() ); ?></span><?php endforeach; ?>
		</div>
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

	add_action( 'groundhogg/admin/contact/record/tab/general', __NAMESPACE__ . '\contact_record_general_info' );

	/**
	 * Contact Info
	 *
	 * @param $contact Contact
	 *
	 * @throws \Exception
	 *
	 */
	function contact_record_general_info( $contact ) {
		?>
		<h2><?php _e( 'Contact Information' ); ?></h2>
		<!-- GENERAL NAME INFO -->
		<table class="form-table">
			<tbody>
			<tr>
				<th><label for="first_name"><?php echo _x( 'First Name', 'contact_record', 'groundhogg' ) ?></label>
				</th>
				<td><?php
					echo html()->input( [
						'name'        => 'first_name',
						'title'       => __( 'First Name' ),
						'value'       => $contact->get_first_name(),
						'class'       => 'auto-copy regular-text',
						'placeholder' => __( 'First Name' )
					] );
					?></td>
			</tr>
			<tr>
				<th><label for="last_name"><?php echo _x( 'Last Name', 'contact_record', 'groundhogg' ) ?></label></th>
				<td><?php
					echo html()->input( [
						'name'        => 'last_name',
						'title'       => __( 'Last Name' ),
						'value'       => $contact->get_last_name(),
						'class'       => 'auto-copy regular-text',
						'placeholder' => __( 'Last Name' )
					] );
					?></td>
			</tr>
			<tr>
				<th><label for="email"><?php echo _x( 'Email', 'contact_record', 'groundhogg' ) ?></label></th>

				<td><?php $args = array(
						'type'  => 'email',
						'id'    => 'email',
						'name'  => 'email',
						'value' => $contact->get_email(),
					);
					echo html()->input( $args ); ?>
					<span class="row-actions"><a
							title="<?php printf( esc_attr__( 'Visit %s', 'groundhogg' ), substr( $contact->get_email(), strpos( $contact->get_email(), '@' ) + 1 ) ); ?>"
							style="text-decoration: none" target="_blank"
							href="<?php echo esc_url( substr( $contact->get_email(), strpos( $contact->get_email(), '@' ) + 1 ) ); ?>"><span
								class="dashicons dashicons-external"></span></a>
                <a class="trigger-popup" title="<?php esc_attr_e( 'Send email.', 'groundhogg' ); ?>"
                   style="text-decoration: none"
                   target="_blank" href="<?php echo modal_link_url( [
	                'title'              => __( 'Send Email', 'groundhogg' ),
	                'footer_button_text' => __( 'Save Changes' ),
	                'source'             => 'email-form-wrap',
	                'height'             => 600,
	                'width'              => 500,
	                'footer'             => 'false',
	                'preventSave'        => 'true',
                ] ) ?>">
                    <span class="dashicons dashicons-email"></span></a></span>
					<div class="email-status">
						<p><?php echo '<b>' . _x( 'Email Status', 'contact_record', 'groundhogg' ) . ': </b>' . Plugin::$instance->preferences->get_optin_status_text( $contact->get_id() ); ?></p>
						<?php do_action( 'groundhogg/contact/record/email_status/after', $contact ); ?>
					</div>

					<?php

					$status_actions = [];

					switch ( $contact->get_optin_status() ) {
						default:
						case Preferences::UNCONFIRMED:
							$status_actions[ Preferences::CONFIRMED ]    = __( 'Confirmed', 'groundhogg' );
							$status_actions[ Preferences::UNSUBSCRIBED ] = __( 'Unsubscribe', 'groundhogg' );
							$status_actions[ Preferences::SPAM ]         = __( 'Spam', 'groundhogg' );
							$status_actions[ Preferences::HARD_BOUNCE ]  = __( 'Bounced', 'groundhogg' );
							$status_actions[ Preferences::COMPLAINED ]   = __( 'Complained', 'groundhogg' );
							break;
						case Preferences::CONFIRMED:
							$status_actions[ Preferences::UNSUBSCRIBED ] = __( 'Unsubscribe', 'groundhogg' );
							$status_actions[ Preferences::SPAM ]         = __( 'Spam', 'groundhogg' );
							$status_actions[ Preferences::HARD_BOUNCE ]  = __( 'Bounced', 'groundhogg' );
							$status_actions[ Preferences::COMPLAINED ]   = __( 'Complained', 'groundhogg' );
							break;
						case Preferences::UNSUBSCRIBED:
						case Preferences::COMPLAINED:
						case Preferences::SPAM:
						case Preferences::HARD_BOUNCE:
							$status_actions[ Preferences::UNCONFIRMED ] = __( 'Re-subscribe', 'groundhogg' );
							break;
					}

					?>
					<span class="status-actions">
                        <?php _e( 'Change status:', 'groundhogg' ); ?>
                        <?php echo implode( ' | ', array_map_with_keys( $status_actions, function ( $text, $status ) use ( $contact ) {
	                        return html()->e( 'a', [
		                        'href'  => action_url( 'status_change', [
			                        'contact' => $contact->get_id(),
			                        'status'  => $status
		                        ] ),
		                        'class' => 'change-status ' . strtolower( Preferences::get_preference_pretty_name( $status ) )
	                        ], $text );
                        } ) ); ?>
                    </span>
					<?php

					do_action( 'groundhogg/contact/record/email/after', $contact );

					?>
				</td>
			</tr>
			<tr>
				<th>
					<label
						for="primary_phone"><?php echo _x( 'Primary Phone', 'contact_record', 'groundhogg' ) ?></label>
				</th>
				<td><?php $args = array(
						'type'  => 'tel',
						'class' => 'input',
						'id'    => 'primary_phone',
						'name'  => 'primary_phone',
						'value' => $contact->get_meta( 'primary_phone' ),
					);
					echo html()->input( $args ); ?>
					<?php _e( 'ext.', 'groundhogg' ) ?>
					<?php $args = array(
						'id'    => 'primary_phone_extension',
						'name'  => 'primary_phone_extension',
						'class' => 'phone-ext',
						'value' => $contact->get_meta( 'primary_phone_extension' ),
					);
					echo html()->input( $args ); ?>
				</td>
			</tr>
			<tr>
				<th>
					<label for="mobile_phone"><?php echo _x( 'Mobile Phone', 'contact_record', 'groundhogg' ) ?></label>
				</th>
				<td><?php $args = array(
						'type'  => 'tel',
						'class' => 'input',
						'id'    => 'mobile_phone',
						'name'  => 'mobile_phone',
						'value' => $contact->get_meta( 'mobile_phone' ),
					);
					echo html()->input( $args ); ?>
				</td>
			</tr>
			<tr>
				<th><?php _ex( 'Contact Owner', 'contact_record', 'groundhogg' ); ?></th>
				<td><?php echo html()->dropdown_owners( array( 'selected' => ( $contact->get_ownerdata() ) ? $contact->get_owner_id() : 0 ) ); ?>
				</td>
			</tr>
			</tbody>
		</table>
		<h2><?php _e( 'Tags' ); ?></h2>
		<div style="max-width: 600px;">
			<?php

			//print_r( $contact->tags );

			$args = array(
				'id'       => 'tags',
				'name'     => 'tags[]',
				'selected' => $contact->get_tags(),
				'style'    => [ 'min-width' => '600px' ]

			);
			echo html()->tag_picker( $args ); ?>
			<p class="description"><?php _ex( 'Add new tags by hitting <code>Enter</code> or by typing a <code>,</code>.', 'contact_record', 'groundhogg' ); ?></p>
		</div>
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
						<label
							for="create_account"><?php echo _x( 'Create New Account?', 'contact_record', 'groundhogg' ) ?></label>
					</th>
					<td>
						<button type="button"
						        class="button button-secondary create-user-account"><?php _e( 'Create User Account' ); ?></button>
						<p class="description"><?php _ex( 'This contact does not have an associated user account? Would you like to create one?', 'contact_record', 'groundhogg' ); ?></p>
					</td>
				</tr>
				<tr>
					<th>
						<label
							for="link_existing"><?php echo _x( 'Link Existing Account?', 'contact_record', 'groundhogg' ) ?></label>
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
					echo html()->input( $args ); ?>
				</td>
			</tr>
			<tr>
				<th><label for="job_title"><?php echo _x( 'Job Title', 'contact_record', 'groundhogg' ) ?></label></th>
				<td><?php $args = array(
						'id'    => 'job_title',
						'name'  => 'job_title',
						'value' => $contact->get_meta( 'job_title' ),
					);
					echo html()->input( $args ); ?>
				</td>
			</tr>
			<tr>
				<th>
					<label
						for="company_address"><?php echo _x( 'Full Company Address', 'contact_record', 'groundhogg' ) ?></label>
				</th>
				<td><?php $args = array(
						'id'    => 'company_address',
						'name'  => 'company_address',
						'value' => $contact->get_meta( 'company_address' ),
					);
					echo html()->input( $args ); ?>
				</td>
			</tr>
			<tr>
				<th>
					<label
						for="company_phone"><?php echo _x( 'Company Phone', 'contact_record', 'groundhogg' ) ?></label>
				</th>
				<td><?php $args = array(
						'type'  => 'tel',
						'class' => 'input',
						'id'    => 'company_phone',
						'name'  => 'company_phone',
						'value' => $contact->get_meta( 'company_phone' ),
					);
					echo html()->input( $args ); ?>
					<?php _e( 'ext.', 'groundhogg' ) ?>
					<?php $args = array(
						'id'    => 'company_phone_extension',
						'name'  => 'company_phone_extension',
						'class' => 'phone-ext',
						'value' => $contact->get_meta( 'company_phone_extension' ),
					);
					echo html()->input( $args ); ?>
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
					<label
						for="street_address_1"><?php echo _x( 'Street Address 1', 'contact_record', 'groundhogg' ) ?></label>
				</th>
				<td><?php $args = array(
						'id'    => 'street_address_1',
						'name'  => 'street_address_1',
						'value' => $contact->get_meta( 'street_address_1' ),
					);
					echo html()->input( $args ); ?>
				</td>
			</tr>
			<tr>
				<th>
					<label
						for="street_address_2"><?php echo _x( 'Street Address 2', 'contact_record', 'groundhogg' ) ?></label>
				</th>
				<td><?php $args = array(
						'id'    => 'street_address_2',
						'name'  => 'street_address_2',
						'value' => $contact->get_meta( 'street_address_2' ),
					);
					echo html()->input( $args ); ?>
				</td>
			</tr>
			<tr>
				<th><label for="city"><?php echo _x( 'City', 'contact_record', 'groundhogg' ) ?></label></th>
				<td><?php $args = array(
						'id'    => 'city',
						'name'  => 'city',
						'value' => $contact->get_meta( 'city' ),
					);
					echo html()->input( $args ); ?>
				</td>
			</tr>
			<tr>
				<th>
					<label
						for="postal_zip"><?php echo _x( 'Postal/Zip Code', 'contact_record', 'groundhogg' ) ?></label>
				</th>
				<td><?php $args = array(
						'id'    => 'postal_zip',
						'name'  => 'postal_zip',
						'value' => $contact->get_meta( 'postal_zip' ),
					);
					echo html()->input( $args ); ?>
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
					echo html()->input( $args ); ?>
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
						echo html()->select2( $args ); ?>
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
					echo html()->input( $args ); ?>
					<?php if ( $contact->get_ip_address() && $contact->get_ip_address() !== '::1' ): ?>
						<span class="button-actions">
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
						echo html()->select2( $args ); ?></div>
				</td>
			</tr>
			</tbody>
		</table>
		<?php contact_record_section_source( $contact ); ?>
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
					<th><?php _e( 'Data Processing Consent' ); ?></th>
					<td><?php echo ( $contact->get_meta( 'gdpr_consent' ) === 'yes' ) ? sprintf( "%s: %s", __( 'Agreed' ), $contact->get_meta( 'gdpr_consent_date' ) ) : '&#x2014;'; ?></td>
				</tr>
				<tr>
					<th><?php _e( 'Marketing Consent' ); ?></th>
					<td><?php echo ( $contact->get_meta( 'marketing_consent' ) === 'yes' ) ? sprintf( "%s: %s", __( 'Agreed' ), $contact->get_meta( 'marketing_consent_date' ) ) : '&#x2014;'; ?></td>
				</tr>
			<?php endif; ?>
			</tbody>
		</table>

		<?php
	}

	add_action( 'groundhogg/admin/contact/record/tab/segmentation', __NAMESPACE__ . '\contact_record_section_segmentation' );

	/**
	 * @param $contact Contact
	 */
	function contact_record_section_source( $contact ) {
		?>

		<!-- SEGMENTATION AND LEADSOURCE -->
		<h2><?php _ex( 'Source', 'contact_record', 'groundhogg' ); ?></h2>
		<table class="form-table">
			<tbody>
			<tr>
				<th><?php _ex( 'Source Page', 'contact_record', 'groundhogg' ); ?></th>
				<td><?php $args = array(
						'id'    => 'source_page',
						'name'  => 'source_page',
						'value' => $contact->get_meta( 'source_page' ),
					);
					echo html()->input( $args ); ?>
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
					echo html()->input( $args ); ?>
					<span class="row-actions">
                    <a style="text-decoration: none" target="_blank"
                       href="<?php echo esc_url( $contact->get_meta( 'lead_source' ) ); ?>"><span
		                    class="dashicons dashicons-external"></span></a>
                </span>
					<p class="description"><?php _e( "This is where the contact originated from.", 'groundhogg' ); ?></p>
				</td>
			</tr>
			</tbody>
		</table>
		<?php
	}

	add_action( 'groundhogg/admin/contact/record/tab/notes', __NAMESPACE__ . '\contact_record_section_notes' );

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
						'id'   => 'add-note',
						'name' => 'add_note',
						'text' => __( 'Add Note', 'groundhogg' ),

					] ), 'p' );

					?>
				</td>
			</tr>
		</table>
		<div id="gh-notes"><?php

		$notes = $contact->get_all_notes();

		foreach ( $notes as $note ) {
			include __DIR__ . '/note.php';
		}

		?></div><?php

		// Legacy notes...
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
						echo html()->textarea( $args ); ?>
					</td>
				</tr>
				</tbody>
			</table>
			<?php
		}
	}

	add_action( 'groundhogg/admin/contact/record/tab/actions', __NAMESPACE__ . '\contact_record_section_actions' );

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
						<?php echo html()->dropdown_emails( array() ); ?>
						<div class="button-actions">
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

						$steps = get_db( 'steps' )->query( [
							'orderby' => 'step_order',
							'order'   => 'asc'
						] );

						$options = array();

						foreach ( $steps as $step ) {
							$step = new Step( $step->ID );
							if ( $step && $step->is_active() ) {

								$funnel_name                          = $step->get_funnel()->get_title();
								$options[ $funnel_name ][ $step->ID ] = sprintf( "%d. %s (%s)", $step->get_order(), $step->get_title(), str_replace( '_', ' ', $step->get_type() ) );
							}
						}

						echo html()->select2( [
							'name'     => 'add_contacts_to_funnel_step_picker',
							'id'       => 'add_contacts_to_funnel_step_picker',
							'data'     => $options,
							'multiple' => false,
						] );

						?>
						<div class="button-actions">
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

						echo html()->select2( [
							'name'        => 'manual_form_submission',
							'id'          => 'manual_form_submission',
							'class'       => 'manual-submission gh-select2',
							'data'        => $form_options,
							'multiple'    => false,
							'selected'    => [ $default ],
							'placeholder' => __( 'Please select a form', 'groundhogg' ),
						] );

						?>
						<div class="button-actions">
							<button type="submit" name="switch_form" value="switch_form"
							        class="button"><?php _e( 'Submit Form', 'groundhogg' ); ?></button>
						</div>
					</div>
				</td>
			</tr>
		</table>
		<?php
	}

	add_action( 'groundhogg/admin/contact/record/tab/files', __NAMESPACE__ . '\contact_record_section_files' );

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

	add_action( 'groundhogg/admin/contact/record/tab/meta_data', __NAMESPACE__ . '\contact_record_section_custom_meta' );

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
								echo html()->input( $args );
							} else if ( strpos( $value, PHP_EOL ) !== false ) {
								$args = array(
									'name'  => 'meta[' . $meta_key . ']',
									'id'    => $meta_key,
									'value' => $value
								);
								echo html()->textarea( $args );
							} else {
								$args = array(
									'name'  => 'meta[' . $meta_key . ']',
									'id'    => $meta_key,
									'value' => $value
								);
								echo html()->input( $args );
							}
							?>
							<span class="row-actions"><span class="delete">
                                    <a style="text-decoration: none"
                                       href="javascript:void(0)"
                                       class="deletemeta">
                                        <span class="dashicons dashicons-trash"></span>
                                    </a>
                                </span></span>
						</td>
					</tr>
				<?php endif;
			endforeach; ?>
			</tbody>
		</table>

		<?php
	}

	add_action( 'groundhogg/admin/contact/record/tab/activity', __NAMESPACE__ . '\contact_record_section_activity' );


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

	<?php echo html()->input( array(
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
