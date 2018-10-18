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
    <?php wp_nonce_field( 'add' ); ?>
    <h2><?php _e( 'Name' ) ?></h2>
    <table class="form-table">
        <tbody>
        <tr>
            <th><label for="first_name"><?php echo __( 'First Name', 'groundhogg' )?></label></th>
            <td><?php $args = array(
                    'id'    => 'first_name',
                    'name'  => 'first_name',
                );
                echo WPGH()->html->input( $args ); ?></td>
        </tr>
        <tr>
            <th><label for="last_name"><?php echo __( 'Last Name', 'groundhogg' )?></label></th>
            <td><?php $args = array(
                    'id'    => 'last_name',
                    'name'  => 'last_name',
                );
                echo WPGH()->html->input( $args ); ?></td>
        </tr>
        <?php do_action( 'wpgh_contact_add_new_name' ); ?>
        </tbody>
    </table>
    <h2><?php _e( 'Contact Info'); ?></h2>
    <table class="form-table">
        <tbody>
        <tr>
            <th><label for="email"><?php echo __( 'Email', 'groundhogg' )?></label></th>
            <td><?php $args = array(
                    'type'  => 'email',
                    'id'    => 'email',
                    'name'  => 'email',
                );
                echo WPGH()->html->input( $args ); ?></td>
        </tr>
        <tr>
            <th><label for="primary_phone"><?php echo __( 'Primary Phone', 'groundhogg' )?></label></th>
            <td><?php $args = array(
                    'type'  => 'tel',
                    'id'    => 'primary_phone',
                    'name'  => 'primary_phone',
                );
                echo WPGH()->html->input( $args ); ?></td>
        </tr>
        <tr>
            <th><label for="phone_extension"><?php echo __( 'Phone Extension', 'groundhogg' )?></label></th>
            <td><?php $args = array(
                    'id'    => 'primary_phone_extension',
                    'name'  => 'primary_phone_extension',
                );
                echo WPGH()->html->input( $args ); ?></td>
        </tr>
        <?php do_action( 'wpgh_contact_add_new_contact_info' ); ?>
        </tbody>
    </table>
    <h2><?php _e( 'Segmentation' ); ?></h2>
    <table class="form-table">
        <tbody>
        <tr>
            <th><?php _e( 'Owner', 'groundhogg' ); ?></th>
            <td><?php $args = array(
                    'show_option_none'  => __( 'Select an owner' ),
                    'id'                => 'owner',
                    'name'              => 'owner',
                    'role'              => 'administrator',
                    'class'             => 'cowner',
                ); wp_dropdown_users( $args ); ?>
            </td>
        </tr>
        <tr>
            <th><label for="tags"><?php echo __( 'Tags', 'groundhogg' )?></label></th>
            <td>
                <div style="max-width: 400px;">
                    <?php $args = array(); echo WPGH()->html->tag_picker( $args ); ?>
                </div>

            </td>
        </tr>
        <?php do_action( 'wpgh_contact_add_new_tags' ); ?>
        </tbody>
    </table>
    <h2><?php _e( 'Notes' ); ?></h2>
    <table>
        <tbody>
        <tr>
            <td><?php $args = array(
                    'id'    => 'notes',
                    'name'  => 'notes',
                    'value' => '',
                );
                echo WPGH()->html->textarea( $args ); ?></td>
        </tr>
        <?php do_action( 'wpgh_contact_add_new_notes' ); ?>
        </tbody>
    </table>
    <?php do_action('wpgh_add_new_contact_form_after'); ?>

    <?php submit_button('Add Contact', 'primary', 'add_contact'); ?>
</form>


