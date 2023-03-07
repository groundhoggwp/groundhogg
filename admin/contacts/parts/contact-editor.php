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

$tabs = [
	'general' => _x( 'General', 'contact_record_tab', 'groundhogg' ),
];

$tabs = apply_filters( 'groundhogg/admin/contact/record/tabs', $tabs );
$tabs = apply_filters( 'groundhogg/admin/contact/record/tabs_after', $tabs );

$cookie_tab = str_replace( 'tab_', '', get_cookie( 'gh_contact_tab', 'general' ) );
$active_tab = sanitize_key( get_request_var( 'active_tab', $cookie_tab ) );
?>
<div class="align-left-space-between align-top two-columns">
	<div id="primary-contact-stuff">
		<div class="gh-panel contact-details">
			<?php include __DIR__ . '/details-card.php'; ?>
			<div id="contact-more-actions" class="align-center-space-between" style="padding-bottom: 20px">

			</div>
		</div>
		<div class="gh-panel tags-panel">
			<div class="gh-panel-header">
				<h2 class="hndle"><?php dashicon_e( 'tag' ); ?><?php _e( 'Tags' ); ?></h2>
                <button type="button" class="toggle-indicator" aria-expanded="true"></button>
            </div>
			<div class="inside">
				<div id="tags-here"></div>
			</div>
		</div>
		<div id="primary-tabs-wrap">
			<form id="primary-form" method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'edit' ); ?>

				<?php do_action( 'groundhogg/contact/record/nav/before', $contact ); ?>

				<!-- BEGIN TABS -->
				<h2 class="nav-tab-wrapper primary gh no-margin">
					<?php foreach ( $tabs as $id => $tab ): ?>
						<a href="javascript:void(0)"
						   class="nav-tab <?php echo $active_tab == $id ? 'nav-tab-active' : ''; ?>"
						   id="<?php esc_attr_e( $id ); ?>"><?php _e( $tab ); ?></a>
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
					<h2><?php _e( 'Contact Information', 'groundhogg' ); ?></h2>
					<!-- GENERAL NAME INFO -->
					<div class="gh-rows-and-columns">
						<div class="gh-row">
							<div class="gh-col">
								<label for="first_name"><?php _e( 'First Name', 'groundhogg' ) ?></label>
								<?php
								echo html()->input( [
									'name'        => 'first_name',
									'title'       => __( 'First Name' ),
									'value'       => $contact->get_first_name(),
									'class'       => 'auto-copy regular-text',
									'placeholder' => __( 'First Name' )
								] );
								?>
							</div>
							<div class="gh-col">
								<label for="last_name"><?php _e( 'Last Name', 'groundhogg' ) ?></label>
								<?php
								echo html()->input( [
									'name'        => 'last_name',
									'title'       => __( 'Last Name' ),
									'value'       => $contact->get_last_name(),
									'class'       => 'auto-copy regular-text',
									'placeholder' => __( 'Last Name' )
								] );
								?>
							</div>
						</div>
						<div class="gh-row">
							<div class="gh-col">
								<label for="email"><?php _e( 'Email Address', 'groundhogg' ) ?></label> <?php
								echo html()->input( [
									'type'  => 'email',
									'id'    => 'email',
									'name'  => 'email',
									'value' => $contact->get_email(),
								] ); ?>
							</div>
							<div class="gh-col">
								<label for="optin_status"><?php _e( 'Opt-in Status', 'groundhogg' ) ?></label>
								<?php
								echo html()->dropdown( [
									'name'     => 'optin_status',
									'title'    => __( 'Opt-in Status', 'groundhogg' ),
									'selected' => $contact->get_optin_status(),
									'options'  => Preferences::get_preference_names()
								] );
								?>
							</div>
						</div>
						<div class="gh-row">
							<div class="gh-col">
								<div class="gh-row">
									<div class="gh-col">
										<label
											for="primary_phone"><?php _e( 'Primary Phone & Ext.', 'groundhogg' ) ?></label>
										<div class="gh-input-group">
											<?php echo html()->input( [
												'type'  => 'tel',
												'class' => 'input',
												'id'    => 'primary_phone',
												'name'  => 'primary_phone',
												'value' => $contact->get_meta( 'primary_phone' ),
												'placeholder' => __( '+1 (555) 555-5555', 'groundhogg' )

											] ); ?>
											<?php echo html()->input( [
												'id'    => 'primary_phone_extension',
												'name'  => 'primary_phone_extension',
												'class' => 'phone-ext',
												'value' => $contact->get_meta( 'primary_phone_extension' ),
												'style'       => [
													'width' => '60px'
												],
												'placeholder' => __( '1234', 'groundhogg' )
											] ); ?>
										</div>
									</div>
								</div>
							</div>
							<div class="gh-col">
								<label for="mobile_phone"><?php _e( 'Mobile Phone', 'groundhogg' ) ?></label>
								<?php
								echo html()->input( [
									'type'  => 'tel',
									'class' => 'input',
									'id'    => 'mobile_phone',
									'name'  => 'mobile_phone',
									'value' => $contact->get_meta( 'mobile_phone' ),
								] ); ?>
							</div>
						</div>
						<div class="gh-row">
							<div class="gh-col">
								<label for="owner_id"><?php _e( 'Contact Owner', 'groundhogg' ) ?></label>
								<?php echo html()->dropdown_owners( array( 'selected' => ( $contact->get_ownerdata() ) ? $contact->get_owner_id() : 0 ) ); ?>
							</div>
							<div class="gh-col">
								<label><?php
									_e( 'Birthday', 'groundhogg' );

									if ( $contact->get_age() ) :
										?><span style="margin-left: 10px"
										class="pill green"><?php printf( __( '%d years old', 'groundhogg' ), $contact->get_age() ); ?></span>
									<?php
									endif; ?>
									</span>
								</label>
								<?php

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

								echo html()->e( 'div', [
									'class' => 'gh-input-group',
								], [
									// Year
									html()->dropdown( [
										'name'        => 'birthday[year]',
										'id'          => 'birthday_year',
										'options'     => $years,
										'multiple'    => false,
										'option_none' => __( 'Year', 'groundhogg' ),
										'selected'    => get_array_var( $birthday_parts, 0 ),
									] ),
									html()->dropdown( [
										'name'        => 'birthday[month]',
										'id'          => 'birthday_month',
										'options'     => $months,
										'multiple'    => false,
										'option_none' => __( 'Month', 'groundhogg' ),
										'selected'    => get_array_var( $birthday_parts, 1 ),
									] ),
									html()->dropdown( [
										'name'        => 'birthday[day]',
										'id'          => 'birthday_day',
										'options'     => $days,
										'multiple'    => false,
										'option_none' => __( 'Day', 'groundhogg' ),
										'selected'    => get_array_var( $birthday_parts, 2 ),
									] ),
								] );

								?>
							</div>
						</div>
						<?php do_action( 'groundhogg/contact/record/general_info', $contact ); ?>
                    </div>

					<?php do_action( 'groundhogg/contact/record/contact_info/after', $contact ); ?>
					<?php do_action( 'groundhogg/contact/record/email_status/after', $contact ); ?>
					<?php do_action( 'groundhogg/contact/record/email/after', $contact ); ?>
					<?php do_action( 'groundhogg/admin/contact/record/user/after', $contact ); ?>
					<?php do_action( 'groundhogg/contact/record/company_info/after', $contact ); ?>

					<!-- ADDRESS -->
					<h2><?php _ex( 'Location', 'contact_record', 'groundhogg' ); ?></h2>
					<div class="gh-rows-and-columns">
						<div class="gh-row">
							<div class="gh-col">
								<label for="street_address_1"><?php _e( 'Line 1', 'groundhogg' ) ?></label>
								<?php echo html()->input( [
									'id'    => 'street_address_1',
									'name'  => 'street_address_1',
									'value' => $contact->get_meta( 'street_address_1' ),
								] ); ?>
							</div>
							<div class="gh-col">
								<label
									for="street_address_2"><?php _e( 'Line 2', 'groundhogg' ) ?></label>
								<?php echo html()->input( [
									'id'    => 'street_address_2',
									'name'  => 'street_address_2',
									'value' => $contact->get_meta( 'street_address_2' ),
								] ); ?>
							</div>
						</div>
						<div class="gh-row">
							<div class="gh-col">
								<label for="city"><?php _e( 'City', 'groundhogg' ) ?></label>
								<?php echo html()->input( [
									'id'    => 'city',
									'name'  => 'city',
									'value' => $contact->get_meta( 'city' ),
								] ); ?>
							</div>
							<div class="gh-col">
								<label for="postal_zip"><?php _e( 'Postal/Zip Code', 'groundhogg' ) ?></label>
								<?php echo html()->input( [
									'id'    => 'postal_zip',
									'name'  => 'postal_zip',
									'value' => $contact->get_meta( 'postal_zip' ),
								] ); ?>
							</div>
						</div>
						<div class="gh-row">
							<div class="gh-col">
								<label for="region"><?php _e( 'State', 'groundhogg' ) ?></label>
								<?php echo html()->input( [
									'id'    => 'region',
									'name'  => 'region',
									'value' => $contact->get_meta( 'region' ),
								] ); ?>
							</div>
							<div class="gh-col">
								<label for="country"><?php _e( 'Country', 'groundhogg' ) ?></label>
								<?php echo html()->select2( [
									'id'          => 'country',
									'name'        => 'country',
									'selected'    => $contact->get_meta( 'country' ),
									'data'        => Plugin::$instance->utils->location->get_countries_list(),
									'placeholder' => __( 'Select a Country', 'groundhogg' ),
									'style'       => []
								] ); ?>
							</div>
						</div>
						<div class="gh-row">
							<div class="gh-col">
								<label
									for="ip_address"><?php _e( 'IP Address', 'groundhogg' ) ?></label>
								<?php echo html()->input( [
									'id'    => 'ip_address',
									'name'  => 'ip_address',
									'value' => $contact->get_meta( 'ip_address' ),
								] ); ?>
							</div>
							<div class="gh-col">
								<label
									for="time_zone"><?php _e( 'Time Zone', 'groundhogg' ) ?></label>
								<?php echo html()->select2( [
									'id'       => 'time_zone',
									'name'     => 'time_zone',
									'data'     => Plugin::$instance->utils->location->get_time_zones(),
									'selected' => $contact->get_meta( 'time_zone' ),
									'style'    => []
								] ); ?>
							</div>
						</div>
						<div class="gh-row">
							<div class="gh-col">
								<label
									for="ip_address"><?php _e( 'Locale', 'groundhogg' ) ?></label>
								<?php wp_dropdown_languages([
									'selected' => $contact->get_locale()
								]) ?>
							</div>
							<div class="gh-col">
							</div>
						</div>
					</div>
					<!-- SEGMENTATION AND LEADSOURCE -->
					<h2><?php _ex( 'Source', 'contact_record', 'groundhogg' ); ?></h2>
					<div class="gh-rows-and-columns">
						<div class="gh-row">
							<div class="gh-col">
								<label for="source_page"><?php _e( 'Source Page', 'groundhogg' ); ?></label>
								<?php echo html()->input( [
									'id'    => 'source_page',
									'name'  => 'source_page',
									'value' => $contact->get_meta( 'source_page' ),
								] ); ?>
							</div>
							<div class="gh-col">
								<label for="source_page"><?php _e( 'Lead Source', 'groundhogg' ); ?></label>
								<?php echo html()->input( [
									'id'    => 'lead_source',
									'name'  => 'lead_source',
									'value' => $contact->get_meta( 'lead_source' ),
								] ); ?>
							</div>
						</div>
					</div>
					<!-- MARKETING COMPLIANCE INFORMATION -->
					<h2><?php _ex( 'Compliance', 'contact_record', 'groundhogg' ); ?></h2>
					<table class="compliance-table">
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
					<p>
						<button class="gh-button primary" id="save-primary"><?php _e( 'Save Changes' ) ?></button>
					</p>

					<?php
				}

				foreach ( $tabs as $tab => $tab_name ):
					?>
					<div class="tab-content-wrapper gh-panel top-left-square"
					     data-tab-content="<?php esc_attr_e( $tab ); ?>">
						<div class="inside">
							<?php

							/**
							 * @param $contact Contact the contact
							 * @param $tab     string if of the current tab
							 */
							do_action( "groundhogg/admin/contact/record/tab/{$tab}", $contact, $tab ); ?>
						</div>
					</div>
				<?php

				endforeach;

				?>
			</form>
		</div>
	</div>
	<div id="other-contact-stuff">
	</div>
</div>
<?php do_action( 'groundhogg/contact/record/after', $contact ); ?>
