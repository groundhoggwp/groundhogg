<?php
/**
 * Submit a form manually via the admin
 *
 * @package     Admin
 * @subpackage  Admin/Contacts
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @see         WPGH_Contacts_Page::edit()
 * @since       File available since Release 1.1.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! isset( $_GET[ 'form' ] ) ){
    return;
}

$form_id = intval( $_GET[ 'form' ] );
$step = wpgh_get_funnel_step( $form_id );
$contact_id = intval( $_GET[ 'contact' ] );

?>
<!-- Title -->
<span class="hidden" id="new-title"><?php echo $step->title ?> &lsaquo; </span>
<script>
    document.title = jQuery( '#new-title' ).text() + document.title;
</script>
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

                if ( $_GET[ 'form' ] ){
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
                    <script>var WPGHFormSubmitBaseUrl = '<?php printf( 'admin.php?page=gh_contacts&action=form&contact=%d&form=', $contact_id ); ?>';</script>
                    <a id="form-submit-link" class="button button-secondary" href="<?php echo admin_url( sprintf( 'admin.php?page=gh_contacts&action=form&contact=%d&form=%d', $contact_id, $default ) ); ?>"><?php _ex( 'Change Form', 'action', 'groundhogg' ) ?></a>
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