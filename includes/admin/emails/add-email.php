<?php
/**
 * Add Email
 *
 * Allows the easy addition of emails from the admin menu.
 *
 * @package     wp-funnels
 * @subpackage  Includes/Emails
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( isset( $_POST['add_new_email_nonce'] ) && wp_verify_nonce( $_POST['add_new_email_nonce'], 'add_new_email' ) && current_user_can( 'manage_options' ) )
{
    do_action( 'wpfn_add_new_email_before' );

    $from_name =  ( isset( $_POST['from_name'] ) )? sanitize_text_field( $_POST['from_name'] ): '';

    $from_email =  ( isset( $_POST['from_email'] ) )? sanitize_email( $_POST['from_email'] ): '';

    $subject =  ( isset( $_POST['subject'] ) )? sanitize_text_field( $_POST['subject'] ): '';

    $pre_header =  ( isset( $_POST['pre_header'] ) )? sanitize_text_field( $_POST['pre_header'] ): '';

    $content =  ( isset( $_POST['content'] ) )? wp_kses( $_POST['content'], wpfn_emails_allowed_html() ): '';

    $ID = wpfn_insert_new_email( $content, $subject, $pre_header, $from_name, $from_email);

    do_action( 'wpfn_add_new_email_after' );

    if ( $ID ){

        wp_redirect( admin_url( 'admin.php?page=emails&ID=' . $ID . '&notice=success') );

    } else {
        ?><div class="notice notice-error"><p>Failed to create email.</p></div><?php
    }
}
?>
	<div class="wrap">
		<h1 class="wp-heading-inline"><?php echo __('Add New Email', 'wp-funnels');?></h1>
		<form method="post" >
			<!-- search form -->
			<?php do_action('wpfn_add_new_email_form_before'); ?>
			<?php wp_nonce_field( 'add_new_email', 'add_new_email_nonce' ); ?>
			<table class="form-table">
				<tbody>
				<tr>
					<th><label for="from_name"><?php echo __( 'From Name', 'wp-funnels' )?></label></th>
                    <?php $prev_from_name = ( isset( $_POST['from_name'] ) )? $_POST['from_name'] : ''; ?>
					<td><?php echo wpfn_admin_text_input_field( 'from_name', 'from_name', $prev_from_name );?><p>The name which the email sends from. Should be something recognizable, such as your company name or a person the contact is familiar with.</p></td>
				</tr>
				<tr>
					<th><label for="from_email"><?php echo __( 'From Email', 'wp-funnels' )?></label></th>
					<?php $prev_from_email = ( isset( $_POST['from_email'] ) )? $_POST['from_email'] : get_bloginfo( 'admin_email' ); ?>
                    <td><?php echo wpfn_admin_email_input_field( 'from_email', 'from_email', $prev_from_email );?><p>The email address which the email sends from. Will also double as the reply-to email.</p></td>
				</tr>
				<tr>
					<th><label for="subject"><?php echo __( 'Subject Line', 'wp-funnels' )?></label></th>
					<?php $prev_subject = ( isset( $_POST['subject'] ) )? $_POST['subject'] : ''; ?>
                    <td><?php echo wpfn_admin_text_input_field( 'subject', 'subject', $prev_subject );?><p>Can't think of a good subject line? <a target="_blank" href="https://optinmonster.com/101-email-subject-lines-your-subscribers-cant-resist/">Try one of these.</a></p></td>
				</tr>
				<tr>
					<th><label for="pre_header"><?php echo __( 'Pre-Header Text', 'wp-funnels' )?></label></th>
					<?php $prev_pre_header = ( isset( $_POST['pre_header'] ) )? $_POST['pre_header'] : ''; ?>
                    <td><?php echo wpfn_admin_text_input_field( 'pre_header', 'pre_header', $prev_pre_header );?><p>This is special text that can be seen in some email clients before the main content in the preview, but is not shown in the actual email.</p></td>
				</tr>
				<tr>
					<th><label for="content"><?php echo __( 'Email Content', 'wp-funnels' )?></label></th>
                    <td>
	                    <?php $prev_content = ( isset( $_POST['content'] ) )? $_POST['content'] : ''; ?>
	                    <?php wp_editor( $prev_content, 'content',  array( 'textarea_name' => 'content' ) ); ?>
                    </td>
				</tr>
				<tr>
					<td><?php submit_button('Create Email', 'primary', 'add_email'); ?></td>
				</tr>
				</tbody>
			</table>
			<?php do_action('wpfn_add_new_email_form_after'); ?>
		</form>
	</div>
<?php


