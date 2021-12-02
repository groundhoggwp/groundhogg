<?php

use Groundhogg\Plugin;
use Groundhogg\Preferences;
use function Groundhogg\get_db;
use function Groundhogg\get_url_var;
use function Groundhogg\html;

$query = get_url_var( 'query' );
$count = get_db( 'contacts' )->count( $query );

?>
<form method="post">
	<?php wp_nonce_field() ?>
	<?php html()->hidden_inputs( $query, 'query' ); ?>
	<h3><?php _e( 'General' ); ?></h3>
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
					'class'       => 'auto-copy regular-text',
					'placeholder' => __( 'Last Name' )
				] );
				?></td>
		</tr>
		<tr>
			<th>
				<label for="primary_phone"><?php echo _x( 'Primary Phone', 'contact_record', 'groundhogg' ) ?></label>
			</th>
			<td><?php
				echo html()->input( [
					'type'  => 'tel',
					'class' => 'input',
					'id'    => 'primary_phone',
					'name'  => 'primary_phone',
				] ); ?>
				<?php _e( 'ext.', 'groundhogg' ) ?>
				<?php
				echo html()->input( [
					'id'    => 'primary_phone_extension',
					'name'  => 'primary_phone_extension',
					'class' => 'phone-ext',
				] ); ?>
			</td>
		</tr>
		<tr>
			<th>
				<label for="mobile_phone"><?php echo _x( 'Mobile Phone', 'contact_record', 'groundhogg' ) ?></label>
			</th>
			<td><?php
				echo html()->input( [
					'type'  => 'tel',
					'class' => 'input',
					'id'    => 'mobile_phone',
					'name'  => 'mobile_phone',
				] ); ?>
			</td>
		</tr>
		<tr>
			<th><?php _ex( 'Email Status', 'contact_record', 'groundhogg' ); ?></th>
			<td><?php echo html()->dropdown( [
					'id'      => 'optin_status',
					'name'    => 'optin_status',
					'options' => Preferences::get_preference_names()
				] ); ?>
			</td>
		</tr>
		<tr>
			<th><?php _ex( 'Contact Owner', 'contact_record', 'groundhogg' ); ?></th>
			<td><?php echo html()->dropdown_owners(); ?>
			</td>
		</tr>
		</tbody>
	</table>
	<h3><?php _e( 'Add Tags' ); ?></h3>
	<div style="max-width: 600px;">
		<?php

		$args = array(
			'id'    => 'tags',
			'name'  => 'tags[]',
			'style' => [ 'min-width' => '600px' ]

		);
		echo html()->tag_picker( $args ); ?>
		<p class="description"><?php _ex( 'Add new tags by hitting <code>Enter</code> or by typing a <code>,</code>.', 'bulk_edit', 'groundhogg' ); ?></p>
	</div>
	<h3><?php _e( 'Remove Tags' ); ?></h3>
	<div style="max-width: 600px;">
		<?php

		$args = array(
			'id'    => 'remove_tags',
			'name'  => 'remove_tags[]',
			'style' => [ 'min-width' => '600px' ]

		);
		echo html()->tag_picker( $args ); ?>
		<p class="description"><?php _ex( 'Add new tags by hitting <code>Enter</code> or by typing a <code>,</code>.', 'bulk_edit', 'groundhogg' ); ?></p>
	</div>
	<h3><?php _e( 'Personal Info' ); ?></h3>
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

				echo html()->e( 'div', [
					'class' => 'gh-input-group'
				], [
					// Year
					html()->dropdown( [
						'name'        => 'birthday[year]',
						'id'          => 'birthday_year',
						'options'     => $years,
						'multiple'    => false,
						'option_none' => __( 'Year', 'groundhogg' ),
					] ),
					html()->dropdown( [
						'name'        => 'birthday[month]',
						'id'          => 'birthday_month',
						'options'     => $months,
						'multiple'    => false,
						'option_none' => __( 'Month', 'groundhogg' ),
					] ),
					html()->dropdown( [
						'name'        => 'birthday[day]',
						'id'          => 'birthday_day',
						'options'     => $days,
						'multiple'    => false,
						'option_none' => __( 'Day', 'groundhogg' ),
					] ),
				] );

				?></td>
		</tr>
		</tbody>
	</table>
	<!-- Company info -->
	<h2><?php _ex( 'Company Info', 'contact_record', 'groundhogg' ); ?></h2>
	<table class="form-table">
		<tr>
			<th><label for="company_name"><?php echo _x( 'Company Name', 'contact_record', 'groundhogg' ) ?></label>
			</th>
			<td><?php
				echo html()->input( [
					'id'   => 'company_name',
					'name' => 'company_name',
				] ); ?>
			</td>
		</tr>
		<tr>
			<th><label for="job_title"><?php echo _x( 'Job Title', 'contact_record', 'groundhogg' ) ?></label></th>
			<td><?php
				echo html()->input( [
					'id'   => 'job_title',
					'name' => 'job_title',
				] ); ?>
			</td>
		</tr>
		<tr>
			<th>
				<label
					for="company_address"><?php echo _x( 'Full Company Address', 'contact_record', 'groundhogg' ) ?></label>
			</th>
			<td><?php
				echo html()->input( [
					'id'   => 'company_address',
					'name' => 'company_address',
				] ); ?>
			</td>
		</tr>
		<tr>
			<th>
				<label for="company_phone"><?php echo _x( 'Company Phone', 'contact_record', 'groundhogg' ) ?></label>
			</th>
			<td><?php
				echo html()->input( [
					'type'  => 'tel',
					'class' => 'input',
					'id'    => 'company_phone',
					'name'  => 'company_phone',
				] ); ?>
				<?php _e( 'ext.', 'groundhogg' ) ?>
				<?php
				echo html()->input( [
					'id'    => 'company_phone_extension',
					'name'  => 'company_phone_extension',
					'class' => 'phone-ext',
				] ); ?>
			</td>
		</tr>
	</table>
	<h3><?php _ex( 'Location', 'bulk_edit', 'groundhogg' ); ?></h3>
	<table class="form-table">
		<tbody>
		<tr>
			<th>
				<label for="street_address_1"><?php echo _x( 'Street Address 1', 'bulk_edit', 'groundhogg' ) ?></label>
			</th>
			<td><?php
				echo html()->input( [
					'id'   => 'street_address_1',
					'name' => 'street_address_1',
				] ); ?>
			</td>
		</tr>
		<tr>
			<th>
				<label for="street_address_2"><?php echo _x( 'Street Address 2', 'bulk_edit', 'groundhogg' ) ?></label>
			</th>
			<td><?php
				echo html()->input( [
					'id'   => 'street_address_2',
					'name' => 'street_address_2',
				] ); ?>
			</td>
		</tr>
		<tr>
			<th><label for="city"><?php echo _x( 'City', 'bulk_edit', 'groundhogg' ) ?></label></th>
			<td><?php
				echo html()->input( [
					'id'   => 'city',
					'name' => 'city',
				] ); ?>
			</td>
		</tr>
		<tr>
			<th>
				<label for="postal_zip"><?php echo _x( 'Postal/Zip Code', 'bulk_edit', 'groundhogg' ) ?></label>
			</th>
			<td><?php
				echo html()->input( [
					'id'   => 'postal_zip',
					'name' => 'postal_zip',
				] ); ?>
			</td>
		</tr>
		<tr>
			<th><label for="region"><?php echo _x( 'State/Province', 'bulk_edit', 'groundhogg' ) ?></label>
			</th>
			<td><?php
				echo html()->input( [
					'id'   => 'region',
					'name' => 'region',
				] ); ?>
			</td>
		</tr>
		<tr>
			<th><label for="country"><?php echo _x( 'Country', 'bulk_edit', 'groundhogg' ) ?></label></th>
			<td>
				<div style="max-width: 338px">
					<?php
					echo html()->select2( [
						'id'          => 'country',
						'name'        => 'country',
						'data'        => Plugin::$instance->utils->location->get_countries_list(),
						'placeholder' => _x( 'Select a Country', 'bulk_edit', 'groundhogg' ),
					] ); ?>
				</div>
			</td>
		</tr>
		<tr>
			<th><label for="ip_address"><?php echo _x( 'IP Address', 'bulk_edit', 'groundhogg' ) ?></label>
			</th>
			<td><?php
				echo html()->input( [
					'id'   => 'ip_address',
					'name' => 'ip_address',
				] ); ?>
			</td>
		</tr>
		<tr>
			<th><label for="time_zone"><?php echo _x( 'Time Zone', 'bulk_edit', 'groundhogg' ) ?></label></th>
			<td>
				<div style="max-width: 338px">
					<?php
					echo html()->select2( [
						'id'   => 'time_zone',
						'name' => 'time_zone',
						'data' => Plugin::$instance->utils->location->get_time_zones(),
					] ); ?></div>
			</td>
		</tr>
		</tbody>
	</table>

	<?php do_action( 'groundhogg/admin/contacts/bulk_edit/after' ) ?>

	<?php submit_button( sprintf( _n( 'Edit %s contact', 'Edit %s contacts', $count, 'groundhogg' ), number_format_i18n( $count ) ) ); ?>
</form>