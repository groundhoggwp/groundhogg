<?php
namespace Groundhogg\Admin\Contacts;

use function Groundhogg\get_form_list;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use Groundhogg\Plugin;
use function Groundhogg\isset_not_empty;

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
<?php $active_tab = isset( $_GET[ 'tab' ] ) ?  $_GET[ 'tab' ] : 'default'; ?>
<h2 class="nav-tab-wrapper">
    <a href="?page=gh_contacts&action=add&tab=default" class="nav-tab <?php echo $active_tab == 'default' ? 'nav-tab-active' : ''; ?>"><?php _ex( 'Quick Add', 'tab', 'groundhogg'); ?></a>
    <a href="?page=gh_contacts&action=add&tab=form" class="nav-tab <?php echo $active_tab == 'form' ? 'nav-tab-active' : ''; ?>"><?php _ex( 'Internal Form', 'tab', 'groundhogg'); ?></a>
</h2>

<?php if ( $active_tab === 'default' ): ?>
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
                    'value' => esc_attr( get_request_var( 'first_name' ) ),
                );

                echo Plugin::$instance->utils->html->input( $args ); ?></td>
        </tr>
        <tr>
            <th><label for="last_name"><?php echo __( 'Last Name', 'groundhogg' )?></label></th>
            <td><?php $args = array(
                    'id'    => 'last_name',
                    'name'  => 'last_name',
                    'value' => esc_attr( get_request_var( 'last_name' ) ),
                );
                echo Plugin::$instance->utils->html->input( $args ); ?></td>
        </tr>
        <?php do_action( 'groundhogg/admin/contacts/add/form/name' ); ?>
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
                    'value' => esc_attr( get_request_var( 'email' ) ),

                );
                echo Plugin::$instance->utils->html->input( $args ); ?></td>
        </tr>
        <tr>
            <th><label for="primary_phone"><?php echo __( 'Primary Phone', 'groundhogg' )?></label></th>
            <td><?php $args = array(
                    'type'  => 'tel',
                    'id'    => 'primary_phone',
                    'name'  => 'primary_phone',
                    'value' => esc_attr( get_request_var( 'primary_phone' ) ),

                );
                echo Plugin::$instance->utils->html->input( $args ); ?></td>
        </tr>
        <tr>
            <th><label for="phone_extension"><?php echo __( 'Phone Extension', 'groundhogg' )?></label></th>
            <td><?php $args = array(
                    'id'    => 'primary_phone_extension',
                    'name'  => 'primary_phone_extension',
                    'value' => esc_attr( get_request_var( 'primary_phone_extension' ) ),
                );
                echo Plugin::$instance->utils->html->input( $args ); ?></td>
        </tr>
        <?php do_action( 'groundhogg/admin/contacts/add/form/contact_info' ); ?>
        </tbody>
    </table>
    <h2><?php _e( 'Segmentation' ); ?></h2>
    <table class="form-table">
        <tbody>
        <tr>
            <th><?php _e( 'Owner', 'groundhogg' ); ?></th>
            <td><?php echo Plugin::$instance->utils->html->dropdown_owners( [
                    'selected' => absint( get_request_var( 'owner_id' ) )
                ] ); ?>
            </td>
        </tr>
        <tr>
            <th><label for="tags"><?php echo __( 'Tags', 'groundhogg' )?></label></th>
            <td>
                <div style="max-width: 400px;">
                    <?php $args = [ 'selected' => wp_parse_id_list( get_request_var( 'tags' ) ) ];
                    echo Plugin::$instance->utils->html->tag_picker( $args ); ?>
                </div>
            </td>
        </tr>
        <?php do_action( 'groundhogg/admin/contacts/add/form/tags' ); ?>
        </tbody>
    </table>
    <h2><?php _e( 'Notes' ); ?></h2>
    <table>
        <tbody>
        <tr>
            <td><?php $args = array(
                    'id'    => 'notes',
                    'name'  => 'notes',
                    'value' => esc_attr( get_request_var( 'notes' ) ),
                );
                echo Plugin::$instance->utils->html->textarea( $args ); ?></td>
        </tr>
        <?php do_action( 'groundhogg/admin/contacts/add/form/notes' ); ?>
        </tbody>
    </table>
    <?php do_action( 'groundhogg/admin/contacts/add/form/after' ); ?>

    <?php submit_button( _x( 'Add Contact', 'action', 'groundhogg' ), 'primary', 'add_contact'); ?>
</form>
<?php endif;

if ( $active_tab === 'form' ): ?>
<table class="form-table">
    <tr>
        <th><?php _ex( 'Internal Form', 'contact_record', 'groundhogg' ); ?></th>
        <td>
            <div style="max-width: 400px;">
                <form method="get">
                    <?php html()->hidden_GET_inputs(); ?>
                    <?php wp_nonce_field( 'switch_form', '_wpnonce', false ); ?>
                    <?php

                    $forms = get_form_list();
                    $form_id = absint( get_request_var( 'form' ) );

                    echo Plugin::$instance->utils->html->select2( [
                        'name'              => 'form',
                        'id'                => 'manual_form_submission',
                        'class'             => 'manual-submission gh-select2',
                        'data'              => $forms,
                        'multiple'          => false,
                        'selected'          => $form_id,
                        'placeholder'       => __( 'Please select a form', 'groundhogg' ),
                    ] );

                    submit_button( __( 'Switch Form', 'groundhogg' ) );
                    ?>
                </form>
            </div>
        </td>
    </tr>
</table>
<hr>
<div>
    <div style="max-width: 800px; margin: 100px auto">
        <?php

        if ( ! $form_id ){
            $ids = array_keys( $forms );
            $form_id = array_shift( $ids );
        }

        echo do_shortcode( sprintf( '[gh_form id="%d"]', $form_id ) ); ?>
    </div>
</div>
<?php endif;


