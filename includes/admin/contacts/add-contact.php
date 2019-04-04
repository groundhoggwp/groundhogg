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
<?php endif;

if ( $active_tab === 'form' ): ?>
<table class="form-table">
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

                if ( gisset_not_empty( $_GET, 'form' ) ){
                    $default = intval( $_GET[ 'form' ] );
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

                ?><div class="actions" style="padding: 2px 0 0;">
                    <script>var WPGHFormSubmitBaseUrl = '<?php printf( 'admin.php?page=gh_contacts&action=add&tab=form&form=' ); ?>';</script>
                    <a id="form-submit-link" class="button button-secondary" href="<?php echo admin_url( sprintf( 'admin.php?page=gh_contacts&action=add&tab=form&form=%d', $default ) ); ?>"><?php _ex( 'Change Form', 'action', 'groundhogg' ) ?></a>
                </div>
            </div>
        </td>
    </tr>
</table>
<hr>
<div>
    <div style="max-width: 800px; margin: 100px auto">
        <?php echo do_shortcode( sprintf( '[gh_form id="%d"]', $default ) ); ?>
    </div>
</div>
<?php endif;


