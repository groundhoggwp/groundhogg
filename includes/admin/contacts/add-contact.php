<?php
/**
 * Add contact
 *
 * Allows the easy addition of contacts from the admin menu.
 *
 * @package     groundhogg
 * @subpackage  includes/admin/Contacts
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( isset( $_POST['add_new_contact_nonce'] ) && wp_verify_nonce( $_POST['add_new_contact_nonce'], 'add_new_contact' ) && current_user_can( 'manage_options' ) )
{
	if ( ! isset( $_POST['email'] ) ){

        ?><div class="notice notice-error"><p>An email is required to create a new contact.</p></div><?php

	} else {

		$email = sanitize_text_field( $_POST['email'] );

		$first_name = ( isset($_POST['first_name']) )? sanitize_text_field( $_POST['first_name'] ) : '';

		$last_name =  ( isset($_POST['last_name']) )? sanitize_text_field( $_POST['last_name'] ): '';

		$primary_phone =  ( isset($_POST['primary_phone']) )? sanitize_text_field( $_POST['primary_phone'] ) : '';

		$phone_extension = ( isset($_POST['primary_phone']) )? sanitize_text_field( $_POST['primary_phone_extension'] ) : '';

		$ID = wpfn_quick_add_contact( $email, $first_name, $last_name, $primary_phone, $phone_extension );

		if ( $ID ){

            ?><div class="notice notice-success"><p>Successfully added new contact! <a href="<?php echo admin_url( 'admin.php?page=contacts&ID=' . $ID ); ?>">Click here to view contact record.</a></p></div><?php

		} else {

            ?><div class="notice notice-error"><p>Failed to create contact. Email already belongs to an existing contact.</p></div><?php

		}
	}
}
?>

<div class="wrap">
	<h1 class="wp-heading-inline"><?php echo __('Add New Contact', 'groundhogg');?></h1>
	<form method="post" >
		<!-- search form -->
		<?php do_action('wpfn_add_new_contact_form_before'); ?>
		<?php wp_nonce_field( 'add_new_contact', 'add_new_contact_nonce' ); ?>
			<table class="form-table">
			<tbody>
			<tr>
				<th><label for="first_name"><?php echo __( 'First Name', 'groundhogg' )?></label></th>
				<td><?php echo wpfn_admin_text_input_field( 'first_name', 'first_name', '' );?></td>
			</tr>
			<tr>
				<th><label for="last_name"><?php echo __( 'Last Name', 'groundhogg' )?></label></th>
				<td><?php echo wpfn_admin_text_input_field( 'last_name', 'last_name', '' );?></td>
			</tr>
			<tr>
				<th><label for="email"><?php echo __( 'Email', 'groundhogg' )?></label></th>
				<td><?php echo wpfn_admin_email_input_field( 'email', 'email', '' );?></td>
			</tr>
			<tr>
				<th><label for="primary_phone"><?php echo __( 'Primary Phone', 'groundhogg' )?></label></th>
				<td><?php echo wpfn_admin_text_input_field( 'primary_phone', 'primary_phone', '' );?></td>
			</tr>
			<tr>
				<th><label for="phone_extension"><?php echo __( 'Phone Extension', 'groundhogg' )?></label></th>
				<td><?php echo wpfn_admin_text_input_field( 'primary_phone_extension', 'primary_phone_extension', '' );?></td>
			</tr>
			</tbody>
		</table>
		<?php do_action('wpfn_add_new_contact_form_after'); ?>

        <?php submit_button('Add Contact', 'primary', 'add_contact'); ?>
	</form>
</div>
<?php


