<?php
/**
 * Add Email
 *
 * Allows the easy addition of emails from the admin menu.
 *
 * @package     groundhogg
 * @subpackage  Includes/Emails
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

do_action( 'wpfn_before_new_email' );

?>
<style>
    .email-container ul{ list-style-type: disc;margin-left: 2em; }
    .email-container p{ font-size: inherit; }
    .email-container h1{ font-weight: bold; padding: 0;margin: 0.67em 0 0.67em 0;}
    .email-container h2{ font-weight: bold; padding: 0;margin: 0.83em 0 0.83em 0;}
</style>
	<div class="wrap">
		<h1 class="wp-heading-inline"><?php echo __('Add New Email', 'groundhogg');?></h1>
		<form method="post" id="poststuff" >
			<!-- search form -->
			<?php do_action('wpfn_add_new_email_form_before'); ?>
			<?php wp_nonce_field( 'add_new_email', 'add_new_email_nonce' ); ?>

            <p>Select an email template below or an existing email to copy.</p>

            <?php include dirname(__FILE__) . '/../../templates/email-templates.php'; ?>

            <?php foreach ( $email_templates as $id => $email_args ): ?>

            <div class="postbox" style="margin-right:20px;width: 400px;display: inline-block;">
                <h2 class="hndle"><?php echo $email_args['title']; ?></h2>
                <div class="inside">
                    <p><?php echo $email_args['description']; ?></p>
                    <div style="zoom: 75%;height: 500px;overflow: auto;padding: 10px;" id="<?php echo $id; ?> " class="email-container postbox">
                        <?php echo $email_args['content']; ?>
                    </div>
                    <button class="button-primary" name="email_template" value="<?php echo $id ?>">Start Writing</button>
                </div>
            </div>

            <?php endforeach; ?>

            <?php $emails = wpfn_get_emails(); ?>

            <?php foreach ( $emails as $email ): ?>

                <div class="postbox" style="margin-right:20px;width: 400px;display: inline-block;">
                    <h2 class="hndle"><?php echo $email['subject']; ?></h2>
                    <div class="inside">
                        <p><?php echo empty( $email['pre_header'] )? __( 'Custom Email', 'groundhogg' ) :  $email['pre_header']; ?></p>
                        <div style="zoom: 75%;height: 500px;overflow: auto;padding: 10px;" id="<?php echo $id; ?> " class="email-container postbox">
                            <?php echo $email['content']; ?>
                        </div>
                        <button class="button-primary" name="email_id" value="<?php echo $email[ 'ID' ]; ?>">Start Writing</button>
                    </div>
                </div>

            <?php endforeach; ?>

			<?php do_action('wpfn_add_new_email_form_after'); ?>
        </form>
	</div>
<?php


