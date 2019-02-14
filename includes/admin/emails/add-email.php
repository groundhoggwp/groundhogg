<?php
/**
 * Add Email
 *
 * This provides a screen of email templates to choose from when creating a new email.
 * You can add your own email templates if you want, but they should obey the markup of the email editor or they wont be very useful.
 * The easiest way to ensure that is to design the email in the editor first, then add it to the templates.
 *
 * To add your own email templates see templates/email-templates.php
 *
 * Alternatively we provide a tab to view all your previously written emails and allow you to copy the content from it to your new email.
 * //todo
 * Create pagination so that 100s of emails do not bog down the process.
 *
 * @package     Admin
 * @subpackage  Admin/Emails
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

?>
<style>
    .email-container ul{ list-style-type: disc;margin-left: 2em; }
    .email-container p{ font-size: inherit; }
    .email-container h1{ font-weight: bold; padding: 0;margin: 0.67em 0 0.67em 0;}
    .email-container h2{ font-weight: bold; padding: 0;margin: 0.83em 0 0.83em 0;}
</style>
<?php $from_funnel = ( isset( $_GET['return_funnel'] ) )? '&return_funnel=' . $_GET['return_funnel']: ''; ?>
<?php $from_funnel .= ( isset( $_GET['return_step'] ) )? '&return_step=' . $_GET['return_step']: ''; ?>
<?php $active_tab = isset( $_GET[ 'tab' ] ) ?  $_GET[ 'tab' ] : 'templates'; ?>
<h2 class="nav-tab-wrapper">
    <a href="?page=gh_emails&action=add&tab=templates<?php echo $from_funnel; ?>" class="nav-tab <?php echo $active_tab == 'templates' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Email Templates', 'groundhogg'); ?></a>
    <a href="?page=gh_emails&action=add&tab=my-emails<?php echo $from_funnel; ?>" class="nav-tab <?php echo $active_tab == 'my-emails' ? 'nav-tab-active' : ''; ?>"><?php _e( 'My Emails', 'groundhogg'); ?></a>
</h2>
<form method="post" id="poststuff" >
    <!-- search form -->
    <?php do_action('wpgh_add_new_email_form_before'); ?>
    <?php wp_nonce_field(); ?>

    <?php if ( $active_tab === 'templates' ): ?>

    <?php include WPGH_PLUGIN_DIR . 'templates/email-templates.php'; ?>

    <?php foreach ( $email_templates as $id => $email_args ): ?>

    <div class="postbox" style="margin-right:20px;width: calc( 95% / 2 );max-width: 550px;display: inline-block;">
        <h2 class="hndle"><?php echo $email_args['title']; ?></h2>
        <div class="inside">
            <p><?php echo $email_args['description']; ?></p>
            <div style="zoom: 85%;height: 500px;overflow: auto;padding: 10px;" id="<?php echo $id; ?> " class="email-container postbox">
                <?php echo $email_args['content']; ?>
            </div>
            <button class="choose-template button-primary" name="email_template" value="<?php echo $id ?>"><?php _e('Start Writing', 'groundhogg' ); ?></button>
        </div>
    </div>

    <?php endforeach; ?>

    <?php else: ?>

    <?php $emails = WPGH()->emails->get_emails(); ?>

    <?php foreach ( $emails as $email ): ?>

        <div class="postbox" style="margin-right:20px;width: calc( 95% / 2 );max-width: 550px;display: inline-block;">
            <h2 class="hndle"><?php echo $email->subject; ?></h2>
            <div class="inside">
                <p><?php echo empty( $email->pre_header )? __( 'Custom Email', 'groundhogg' ) :  $email->pre_header; ?></p>
                <div style="zoom: 85%;height: 500px;overflow: auto;padding: 10px;" id="<?php echo $id; ?> " class="email-container postbox">
                    <?php echo $email->content; ?>
                </div>
                <button class="choose-template button-primary" name="email_id" value="<?php echo $email->ID; ?>"><?php _e( 'Start Writing', 'groundhogg' ); ?></button>
            </div>
        </div>

    <?php endforeach; ?>

    <?php endif; ?>

    <?php do_action('wpgh_add_new_email_form_after'); ?>
</form>
<?php


