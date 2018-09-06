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

?>

<form method="post" class="">
    <?php wp_nonce_field( 'edit' ); ?>
    <h2><?php _e( 'Name' ) ?></h2>
    <table class="form-table">
        <tbody>
        <tr>
            <th><label for="first_name"><?php echo __( 'First Name', 'groundhogg' )?></label></th>
            <td><?php echo wpgh_admin_text_input_field( 'first_name', 'first_name' );?></td>
        </tr>
        <tr>
            <th><label for="last_name"><?php echo __( 'Last Name', 'groundhogg' )?></label></th>
            <td><?php echo wpgh_admin_text_input_field( 'last_name', 'last_name' );?></td>
        </tr>
        <?php do_action( 'wpgh_contact_add_new_name' ); ?>
        </tbody>
    </table>
    <h2><?php _e( 'Contact Info'); ?></h2>
    <table class="form-table">
        <tbody>
        <tr>
            <th><label for="email"><?php echo __( 'Email', 'groundhogg' )?></label></th>
            <td><?php echo wpgh_admin_email_input_field( 'email', 'email' );?></td>
        </tr>
        <tr>
            <th><label for="primary_phone"><?php echo __( 'Primary Phone', 'groundhogg' )?></label></th>
            <td><?php echo wpgh_admin_text_input_field( 'primary_phone', 'primary_phone' );?></td>
        </tr>
        <tr>
            <th><label for="phone_extension"><?php echo __( 'Phone Extension', 'groundhogg' )?></label></th>
            <td><?php echo wpgh_admin_text_input_field( 'primary_phone_extension', 'primary_phone_extension');?></td>
        </tr>
        <?php do_action( 'wpgh_contact_add_new_contact_info' ); ?>
        </tbody>
    </table>
    <h2><?php _e( 'Segmentation' ); ?></h2>
    <table class="form-table">
        <tbody>
        <tr>
            <th><label for="tags"><?php echo __( 'Tags', 'groundhogg' )?></label></th>
            <td><?php wpgh_dropdown_tags( array( 'width' => '400px', 'class' => 'hidden' ) );?></td>
        </tr>
        <?php do_action( 'wpgh_contact_add_new_tags' ); ?>
        </tbody>
    </table>
    <h2><?php _e( 'Notes' ); ?></h2>
    <table>
        <tbody>
        <tr>
            <td><textarea style="width: 700px" rows="6" name="notes" id="notes" placeholder="<?php esc_attr_e( 'Enter some details about this contact...', 'grounshogg' ); ?>"></textarea></td>
        </tr>
        <?php do_action( 'wpgh_contact_add_new_notes' ); ?>
        </tbody>
    </table>
    <?php do_action('wpgh_add_new_contact_form_after'); ?>

    <?php submit_button('Add Contact', 'primary', 'add_contact'); ?>
</form>


