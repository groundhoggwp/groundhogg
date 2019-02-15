<?php
/**
 * Add a contact via the Admin "ADD NEW" button
 *
 * I recommend leaving this file alone and adding any custom sections to the edit screen rather than this screen
 * But if you MUST then what you can do is the following.
 *
 * add_action( 'wpgh_add_new_contact_form_after', 'my_custom_section' );
 *
 * To output your custom settings.
 *
 * To do something with those settings you will need to access the save api method...
 *
 * add_action( 'wpgh_admin_add_contact_after', 'my_add_function' ); ($id)
 *
 * and access the $_POST directly. By that point the contact will have already been added
 * to the DB so the hook passes the $id of the contact
 *
 * @package     Admin
 * @subpackage  Admin/Contacts
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @see         WPGH_Contacts_Page::add()
 * @since       File available since Release 0.1
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
    <h2><?php _e( 'Contact Info' ); ?></h2>
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
            <td><?php echo WPGH()->html->dropdown_owners(); ?>
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

    <?php submit_button( _x( 'Add Contact', 'action', 'groundhogg' ), 'primary', 'add_contact'); ?>
</form>


