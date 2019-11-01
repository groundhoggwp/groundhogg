<?php

namespace GroundhoggPro\Admin\Emails;

use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use Groundhogg\Plugin;
use Groundhogg\Email;
use function Groundhogg\managed_page_url;

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
if (!defined('ABSPATH')) exit;

?>
    <style>
        .email-container ul {
            list-style-type: disc;
            margin-left: 2em;
        }

        .email-container p {
            font-size: inherit;
        }

        .email-container h1 {
            font-weight: bold;
            padding: 0;
            margin: 0.67em 0 0.67em 0;
        }

        .email-container h2 {
            font-weight: bold;
            padding: 0;
            margin: 0.83em 0 0.83em 0;
        }
    </style>
    <?php

$custom_templates = Plugin::$instance->dbs->get_db('emails')->query(['is_template' => 1]);

if (count($custom_templates) > 0) {
    $active_tab = sanitize_text_field(get_request_var('tab', 'my-templates'));
} else {
    $active_tab = sanitize_text_field(get_request_var('tab', 'templates'));
}

?>

    <h2 class="nav-tab-wrapper">
        <?php if (count($custom_templates) > 0): ?>
            <a id="my-templates" href="?page=gh_emails&action=add&tab=my-templates"
               class="nav-tab <?php echo $active_tab == 'my-templates' ? 'nav-tab-active' : ''; ?>"><?php _e('My Templates', 'groundhogg-pro'); ?></a>
        <?php endif; ?>
        <a id="default-templates" href="?page=gh_emails&action=add&tab=templates"
           class="nav-tab <?php echo $active_tab == 'templates' ? 'nav-tab-active' : ''; ?>"><?php _e('Default Templates', 'groundhogg-pro'); ?></a>
        <a id="my-emails" href="?page=gh_emails&action=add&tab=my-emails"
           class="nav-tab <?php echo $active_tab == 'my-emails' ? 'nav-tab-active' : ''; ?>"><?php _e('My Emails', 'groundhogg-pro'); ?></a>
    </h2>
    <form method="post" id="poststuff">
        <!-- search form -->
        <?php do_action('wpgh_add_new_email_form_before'); ?>
        <?php wp_nonce_field(); ?>
        <?php if ($active_tab === 'templates'):
            ?>
        <div class="post-box-grid">
            <?php
            $email_templates = [];

            include GROUNDHOGG_PRO_PATH . 'templates/assets/email-templates.php';

            /**
             * @param $email_templates array
             */
            foreach ($email_templates as $id => $email_args): ?>

                <div class="postbox">
                    <h2 class="hndle"><?php echo $email_args['title']; ?></h2>
                    <div class="inside">
                        <p><?php echo $email_args['description']; ?></p>
                        <div style="zoom: 85%;height: 500px;overflow: auto;padding: 10px;border: 1px solid #e5e5e5;margin-bottom: 10px;"
                             id="<?php echo $id; ?> " class="email-container">
                            <?php echo $email_args['content']; ?>
                        </div>
                        <button class="choose-template button-primary" name="email_template"
                                value="<?php echo $id ?>"><?php _e('Start Writing', 'groundhogg-pro'); ?></button>
                    </div>
                </div>
            <?php endforeach;

            ?></div><?php
        elseif ($active_tab === 'my-emails'): ?>
            <style>
                .wp-filter-search {
                    box-sizing: border-box;
                    width: 100%;
                    font-size: 16px;
                    padding: 6px;
                }
            </style>
            <div class="postbox">
                <div class="inside">
                    <p style="float: left"><?php _e('Search your previous emails and use them as a starting point.', 'groundhogg-pro'); ?></p>
                    <input type="text" id="search_emails"
                           placeholder="<?php esc_attr_e('Type in a search term like "Special"...', 'groundhogg-pro'); ?>"
                           class="wp-filter-search"/>
                </div>
            </div>
            <div style="text-align: center;" id="spinner">
                <span class="spinner" style="visibility: visible;float: none;"></span>
            </div>
            <div id="emails" class="post-box-grid">
                <!-- Only retrieve previous 20 emails.. -->
                <?php
                $emails = get_db('emails')->query(['limit' => 10]);
                foreach ($emails as $email):
                    $email = new Email(absint($email->ID)); ?>
                    <div class="postbox">
                        <h2 class="hndle"><?php echo $email->get_title(); ?></h2>
                        <div class="inside">
                            <p><?php echo __('Subject: ', 'groundhogg-pro') . $email->get_subject_line(); ?></p>
                            <p><?php echo __('Pre-Header: ', 'groundhogg-pro') . $email->get_pre_header(); ?></p>
                            <iframe class="email-container" style="margin-bottom: 10px; border: 1px solid #e5e5e5;" width="100%"
                                    height="500"
                                    src="<?php echo managed_page_url('emails/' . $email->get_id()); ?>"></iframe>
                            <button class="choose-template button-primary" name="email_id"
                                    value="<?php echo $email->get_id(); ?>"><?php _e('Start Writing', 'groundhogg-pro'); ?></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <script type="text/javascript">
                (function ($) {

                    //setup before functions
                    var typingTimer;                //timer identifier
                    var doneTypingInterval = 500;  //time in ms, 5 second for example
                    var $search = $('#search_emails');
                    var $downloads = $('#emails');
                    var $spinner = $('#spinner');

                    $spinner.hide();

                    $search.keyup(function () {
                        clearTimeout(typingTimer);
                        typingTimer = setTimeout(ajaxCall, doneTypingInterval);
                    });

                    $search.keydown(function () {
                        clearTimeout(typingTimer);
                    });

                    function ajaxCall() {
                        $downloads.hide();
                        $spinner.show();
                        var ajaxCall = $.ajax({
                            type: "post",
                            url: ajaxurl,
                            dataType: 'json',
                            data: {action: 'get_my_emails_search_results', s: $('#search_emails').val()},
                            success: function (response) {
                                $spinner.hide();
                                $downloads.show();
                                $downloads.html(response.html);
                            }
                        });
                    }
                })(jQuery);
            </script>
        <?php else:

        ?>
            <div class="post-box-grid">
                <?php
                foreach ($custom_templates as $id => $email):
                    $email = new Email(absint($email->ID)); ?>
                    <div class="postbox">
                        <h2 class="hndle"><?php esc_html_e($email->get_subject_line()); ?></h2>
                        <div class="inside">
                            <p><?php
                                echo (!empty($email->get_pre_header())) ? esc_html($email->get_pre_header()) : '&#x2014;';
                                ?></p>
                            <iframe class="email-container" style="margin-bottom: 10px;border: 1px solid #e5e5e5;"
                                    width="100%" height="500"
                                    src="<?php echo managed_page_url('emails/' . $email->get_id()); ?>"></iframe>
                            <button class="choose-template button-primary" name="email_id"
                                    value="<?php echo $email->get_id(); ?>"><?php _e('Start Writing', 'groundhogg-pro'); ?></button>
                            <a class="button-secondary"
                               href="<?php printf(admin_url('admin.php?page=gh_emails&action=edit&email=%d'), $email->get_id()); ?>"><?php _e('Edit Template', 'groundhogg-pro'); ?></a>
                        </div>
                    </div>
                <?php endforeach;
                ?>
            </div>
        <?php
        endif;
        do_action('wpgh_add_new_email_form_after'); ?>
    </form>
<?php


