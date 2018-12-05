<?php
/**
 * Email Editor
 *
 * Allow the user to edit the email
 * rather than just hardcoded.
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


$email_id = intval( $_GET['email'] );
$email = new WPGH_Email( $email_id );

$blocks = apply_filters( 'wpgh_email_blocks', array() );

?>

<!-- NEW EMAIL TAB TITLE -->
<?php if ( ! empty( $email->subject ) ): ?>
<span class="hidden" id="new-title"><?php echo $email->subject; ?> &lsaquo; </span>
<script>
    document.title = jQuery( '#new-title' ).text() + document.title;
</script>
<?php endif; ?>
<!-- /END TITLE -->


<form method="post">

    <!-- Before-->
    <?php wp_nonce_field(); ?>
    <?php do_action('wpgh_edit_email_form_before'); ?>

    <?php echo WPGH()->html->input( array( 'type' => 'hidden', 'name' => 'email', 'value' => $email_id ) ); ?>

    <div class="header-wrap">
        <div class="funnel-editor-header">
            <div class="title-wrap">
                <span id="title"><?php _e( 'Edit Email' ); ?></span><a class="button" href="<?php echo admin_url( 'admin.php?page=gh_emails&action=add' ); ?>"><?php _e( 'Add New' ); ?></a>
            </div>
            <div class="status-options">
                <div id="status">
                    <div id="editor-toggle-switch" class="onoffswitch" style="text-align: left">
                        <input type="checkbox" name="editor_view" class="onoffswitch-checkbox" value="ready" id="editor-toggle">
                        <label class="onoffswitch-label" for="editor-toggle">
                            <span class="onoffswitch-inner"></span>
                            <span class="onoffswitch-switch"></span>
                        </label>
                    </div>
                    <div id="status-toggle-switch" class="onoffswitch" style="text-align: left">
                        <input type="checkbox" name="email_status" class="onoffswitch-checkbox" value="ready" id="status-toggle" <?php if ( $email->status == 'ready' ) echo 'checked'; ?>>
                        <label class="onoffswitch-label" for="status-toggle">
                            <span class="onoffswitch-inner"></span>
                            <span class="onoffswitch-switch"></span>
                        </label>
                    </div>
                </div>
                <div id="save">
                    <span class="spinner" style="float: left"></span>
                    <?php submit_button( 'Update', 'primary', 'update', false ) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Main -->
    <div id='poststuff' class="wpgh-funnel-builder" style="overflow: hidden">
        <div id="post-body" class="metabox-holder columns-2" style="clear: both">


            <div id="post-body-content">

                <!-- Title Content -->
                <div id="titlediv">
                    <div id="titlewrap">

                        <!-- Subject Line -->
                        <label class="screen-reader-text" id="title-prompt-text" for="subject"><?php echo __('Subject Line: Used to capture the attention of the reader.', 'groundhogg');?></label>
                        <input placeholder="<?php echo __('Subject Line: Used to capture the attention of the reader.', 'groundhogg');?>" type="text" name="subject" size="30" value="<?php echo esc_attr( $email->subject ); ?>" id="subject" spellcheck="true" autocomplete="off" required>

                        <!-- Pre Header-->
                        <label class="screen-reader-text" id="title-prompt-text" for="pre_header"><?php echo __('Pre Header Text: Used to summarize the content of the email.', 'groundhogg');?></label>
                        <input placeholder="<?php echo __('Pre Header Text: Used to summarize the content of the email.', 'groundhogg');?>" type="text" name="pre_header" size="30" value="<?php echo esc_attr( $email->pre_header ); ?>" id="pre_header" spellcheck="true" autocomplete="off">
                    </div>
                </div>
                <!-- RETURN PATH NOTICE-->
                <?php if ( isset( $_REQUEST['return_funnel'] ) ): ?>
                    <div class="notice notice-info is-dismissible">
                        <p><a href="<?php echo admin_url( 'admin.php?page=gh_funnels&action=edit&funnel=' . $_REQUEST['return_funnel'] . '#' . $_REQUEST['return_step'] ); ?>"><?php  _e( '&larr; Back to editing funnel' ); ?></a></p>
                    </div>
                <?php endif; ?>
                <!-- /RETURN PATH -->
                <div id="notices">

                </div>
                <!-- / Title Content -->

                <!-- Editor -->
                <div id="email-content">
                    <div id="editor" class="editor" style="display: flex;">

                        <!-- Block Options -->
                        <div id="editor-actions" style="display: inline-block;width: 280px;float: left">
                            <div style="width: 280px;"></div>
                            <div class="editor-actions-inner">

                                <?php

                                foreach ( $blocks as $block_type => $block ){

                                    do_action( 'wpgh_' . $block_type . '_block_settings' );

                                }

                                ?>

                                <!-- Main Content Options -->
                                <div id="email-editor" class="postbox">
                                    <h3 class="hndle"><?php _e( 'Email Options'); ?></h3>
                                    <div class="inside">
                                        <div class="options">
                                            <table class="form-table">
                                                <tr>
                                                    <th><?php _e( 'Alignment'); ?></th>
                                                    <td>
                                                        <select id="email-align" name="email_alignment">
                                                            <option value="left" <?php if ( $email->get_meta( 'alignment' ) === 'left' ) echo 'selected' ; ?> ><?php _e('Left'); ?></option>
                                                            <option value="center" <?php if ( $email->get_meta( 'alignment' ) === 'center' ) echo 'selected' ; ?>><?php _e('Center'); ?></option>
                                                        </select>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th><?php _e( 'Enable Browser View'); ?></th>
                                                    <td><input type="checkbox" name="browser_view" value="1" <?php if ( $email->get_meta( 'browser_view' ) == 1 ) echo 'checked' ; ?>></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="email-body" class="main-email-body" style="flex-grow: 100;width: auto;">

                            <?php $alignment = $email->get_meta( 'alignment' );
                            if ( $alignment === 'center' ){
                                $margins = "margin-left:auto;margin-right:auto;";
                            } else {
                                $margins = "margin-left:0;margin-right:auto;";
                            } ?>

                            <!-- Editor Content -->
                            <div id="email-inside" class="email-sortable" style="max-width: 580px;margin-top:40px;<?php echo $margins;?>">
                                <?php echo $email->content; ?>
                            </div>

                        </div>
                        <div style="clear: both;"></div>
                    </div>
                </div>
                <div id="html-editor">
                    <textarea style="width: 100%;"  id="html-code" ></textarea>
                </div>
                <!-- Saved Content -->
                <div class="hidden">
                    <textarea id="content" name="content"><?php echo $email->content; ?></textarea>
                </div>

            </div>

            <!-- begin elements area -->
            <div id="postbox-container-1" class="postbox-container sidebar">
                <div id="submitdiv" class="postbox">
                    <h3 class="hndle"><?php echo __( 'Sender', 'groundhogg' );?></h3>
                    <div class="inside">
                        <div class="submitbox">
                            <div id="minor-publishing-actions">
                                <?php do_action( 'wpgh_email_actions_before' ); ?>
                                <table class="form-table">
                                    <tbody>
                                    <tr>
                                        <th><?php _e( 'From User:' ); ?></th>
                                        <?php $args = array( 'option_none' => __( 'The Contact\'s Owner' ) , 'id' => 'from_user', 'name' => 'from_user', 'selected' => $email->from_user ); ?>
                                        <td><?php echo WPGH()->html->dropdown_owners( $args ); ?></td>
                                    </tr>
                                    <tr>
                                        <th><label for="send_test"><?php _e( 'Send Test:' ); ?></label></th>
                                        <td><input type="checkbox" id="send_test" name="send_test"></td>
                                    </tr>
                                    <tr id="send-to" class="hidden">
                                        <th><?php _e( 'To:' ); ?></th>
                                        <?php $args = array( 'option_none' => __( 'The Contact\'s Owner' ) , 'id' => 'test_email', 'name' => 'test_email', 'selected' => $email->get_meta( 'test_email' ) ); ?>
                                        <td><?php echo WPGH()->html->dropdown_owners( $args ); ?></td>
                                    </tr>
                                    <script>
                                        jQuery(function($){$("#send_test").on( 'input', function(){
                                            $("#send-to").toggleClass( 'hidden' );
                                        })});
                                    </script>
                                    </tbody>
                                </table>
                                <?php do_action( 'wpgh_email_actions_after' ); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php do_action( 'wpgh_email_side_actions_after' ); ?>
                <?php do_action( 'wpgh_email_blocks_before' ); ?>
                <div id='blocks' class="postbox">
                    <h2 class="hndle"><?php echo __( 'Blocks', 'groundhogg' );?></h2>
                    <div class="inside">
                        <table>
                            <tbody>

                            <?php

                            $i = 0;

                            ?><tr><?php

                                foreach ( $blocks as $block_type => $block ):

                                if ( ( $i % 2 ) == 0 ):
                                ?></tr><tr><?php
                                endif;

                                ?>
                                <td>
                                    <div id='<?php echo $block[ 'name' ]; ?>-block' data-block-type="<?php echo $block[ 'name' ]; ?>" class="wpgh-element email-draggable">
                                        <div class="builder-icon">
                                            <img src="<?php echo $block[ 'icon' ]; ?>"></div>
                                        <p><?php echo $block[ 'title' ]; ?></p>
                                    </div>
                                </td>
                                <?php

                                $i++;

                                endforeach;

                                ?></tr><?php

                            ?>
                            </tbody>
                        </table>
                        <div class="hidden">

                            <?php

                            foreach ( $blocks as $block_type => $block ){

                                ?><div class="<?php echo $block[ 'name' ]; ?>-template"><?php
                                do_action( 'wpgh_' . $block_type . '_block_html' );
                                ?></div> <?php

                            }

                            ?>
                            <div id="temp-html" class="hidden"></div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- HI -->
            <!-- End elements area-->
            <div style="clear: both;"></div>
        </div>
        <?php WPGH()->replacements->get_table(); ?>
    </div>
</form>
