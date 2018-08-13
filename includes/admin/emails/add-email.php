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

do_action( 'wpfn_before_new_email' );

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
                        <th><?php _e( 'Copy content from exitsing email?', 'wp-funnels' ); ?></th>
                        <td><?php $args=array( 'name' => 'copy_email_id', 'id' => 'copy_email_id' ); ?>
                            <?php wpfn_dropdown_emails( $args ); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Or...' ); ?></th>
                    </tr>
                    <tr>
                        <th><label for="scratch_email"><?php _e( 'Start from scratch', 'wp-funnels' ); ?></label></th>
                        <td><input type="checkbox" class="" id="scratch_email" name="scratch_email" value="yes"></td>
                    </tr>
                </tbody>
            </table>
			<?php do_action('wpfn_add_new_email_form_after'); ?>

            <?php submit_button( __('Create Email', 'wp-funnels'), 'primary', 'create_email' ) ?>
		</form>
	</div>
<?php


