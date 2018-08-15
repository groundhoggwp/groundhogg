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
		<form method="post" id="poststuff" >
			<!-- search form -->
			<?php do_action('wpfn_add_new_email_form_before'); ?>
			<?php wp_nonce_field( 'add_new_email', 'add_new_email_nonce' ); ?>

            <p>Select an email template below or an existing email to copy.</p>

            <?php include dirname( __FILE__ ) . '/../../templates/pre-written.php'; ?>

            <?php foreach ( $pre_written_emails as $id => $email_args ): ?>

            <div class="postbox" style="margin-right:20px;width: 400px;display: inline-block;">
                <h2 class="hndle"><?php echo $email_args['title']; ?></h2>
                <div class="inside">
                    <p><?php echo $email_args['description']; ?></p>
                    <div style="zoom: 75%;height: 500px;overflow: auto;padding: 10px;" id="<?php echo $id; ?> " class="email-container postbox">
                        <?php echo $email_args['content']; ?>
                    </div>
                    <div></div>
                </div>
            </div>

            <?php endforeach; ?>

            <div class="postbox" style="margin-right:20px;width: 400px;display: inline-block;vertical-align: top">
                <h2 class="hndle"><?php _e( 'Copy Existing' ); ?></h2>
                <div class="inside">
                    <table class="form-table">
                        <tbody>
                        <tr>
                            <th><?php _e( 'Copy content from existing email?', 'wp-funnels' ); ?></th>
                            <td><?php $args=array( 'name' => 'copy_email_id', 'id' => 'copy_email_id' ); ?>
                                <?php wpfn_dropdown_emails( $args ); ?></td>
                        </tr>
                        </tbody>
                    </table>
                    <?php submit_button( 'Create Email', 'primary', 'create_email', '' ); ?>
                </div>
            </div>

			<?php do_action('wpfn_add_new_email_form_after'); ?>
        </form>
	</div>
<?php


