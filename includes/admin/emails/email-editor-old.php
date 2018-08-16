<?php
/**
 * Email Editor
 *
 * Allow the user to edit the email
 *
 * @package     groundhogg
 * @subpackage  Includes/Emails
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

// Exit if accessed directly

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! isset( $_GET['ID'] ) || ! is_numeric( $_GET['ID'] ) )
{
	wp_die( __( 'Email ID not supplied. Please try again', 'groundhogg' ), __( 'Error', 'groundhogg' ) );
}

if ( isset( $_GET['notice'] ) && $_GET['notice'] == 'success' ){
	?><div class="notice notice-success"><p>Successfully created email!</p></div><?php
}

$email_id = intval( $_GET['ID'] );

do_action( 'wpfn_email_editor_before_everything', $email_id );

$email = wpfn_get_email_by_id( $email_id );

?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php echo __('Edit Email', 'groundhogg');?></h1>
	<form method="post" >
		<!-- search form -->
		<?php do_action('wpfn_edit_email_form_before'); ?>
		<?php wp_nonce_field( 'edit_email', 'edit_email_nonce' ); ?>
		<table class="form-table">
			<tbody>
            <tr>
                <th><label for="from_name"><?php echo __( 'From Name', 'groundhogg' )?></label></th>
                <td><?php echo wpfn_admin_text_input_field( 'from_name', 'from_name', $email->from_name );?><p>The name which the email sends from. Should be something recognizable, such as your company name or a person the contact is familiar with.</p></td>
            </tr>
            <tr>
                <th><label for="from_email"><?php echo __( 'From Email', 'groundhogg' )?></label></th>
                <td><?php echo wpfn_admin_email_input_field( 'from_email', 'from_email', $email->from_email );?><p>The email address which the email sends from. Will also double as the reply-to email.</p></td>
            </tr>
            <tr>
                <th><label for="subject"><?php echo __( 'Subject Line', 'groundhogg' )?></label></th>
                <td><?php echo wpfn_admin_text_input_field( 'subject', 'subject', $email->subject );?><p>Can't think of a good subject line? <a target="_blank" href="https://optinmonster.com/101-email-subject-lines-your-subscribers-cant-resist/">Try one of these.</a></p></td>
            </tr>
            <tr>
                <th><label for="pre_header"><?php echo __( 'Pre-Header Text', 'groundhogg' )?></label></th>
                <td><?php echo wpfn_admin_text_input_field( 'pre_header', 'pre_header', $email->pre_header );?><p>This is special text that can be seen in some email clients before the main content in the preview, but is not shown in the actual email.</p></td>
            </tr>
            <tr>
                <th><label for="content"><?php echo __( 'Email Content', 'groundhogg' )?></label></th>
                <td><?php wp_editor( $email->content, 'content',  array( 'textarea_name' => 'content' ) ); ?></td>
            </tr>
			<tr>
				<td><?php submit_button('Update Email', 'primary', 'update_email'); ?></td>
			</tr>
            <tr>
                <th><label for="test_email"><?php echo __( 'Send Test Email', 'groundhogg' )?></label></th>
	            <?php $prev_test_email = ( isset( $_POST['test_email'] ) )? $_POST['test_email'] : get_bloginfo( 'admin_email' ); ?>
                <td><?php echo wpfn_admin_email_input_field( 'test_email', 'test_email', $prev_test_email );?><p>Enter an email you'd like to send this test to.</p></td>
            </tr>
            <tr>
                <td><?php submit_button('Update & Send Test', 'primary', 'send_test_email'); ?></td>
            </tr>
			</tbody>
		</table>
		<?php do_action('wpfn_edit_email_form_after'); ?>
	</form>
</div>
<?php

