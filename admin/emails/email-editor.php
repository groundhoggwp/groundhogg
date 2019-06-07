<?php

namespace Groundhogg\Admin\Emails;

use Groundhogg\Email_Parser;
use function Groundhogg\html;
use Groundhogg\Plugin;
use Groundhogg\Email;

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
$email = new Email( $email_id );

$blocks = apply_filters( 'groundhogg/admin/emails/blocks', [] );

?>

<!-- NEW EMAIL TAB TITLE -->
<?php if ( ! empty( $email->get_subject_line() ) ): ?>
<span class="hidden" id="new-title"><?php echo $email->get_subject_line(); ?> &lsaquo; </span>
<script>
    document.title = jQuery( '#new-title' ).text() + document.title;
</script>
<?php endif; ?>
<!-- /END TITLE -->


<form method="post">

    <!-- Before-->
    <?php wp_nonce_field(); ?>
    <?php do_action('wpgh_edit_email_form_before'); ?>
    <?php echo Plugin::$instance->utils->html->input( [ 'type' => 'hidden', 'name' => 'email', 'value' => $email_id ] ); ?>

    <div class="header-wrap">
        <div class="editor-header">
            <div class="title-wrap">
                <span id="title"><?php _e( 'Edit Email', 'groundhogg'  ); ?></span><a class="button" href="<?php echo admin_url( 'admin.php?page=gh_emails&action=add' ); ?>"><?php _e( 'Add New', 'groundhogg'  ); ?></a>
            </div>
            <div class="status-options">
                <div id="status">
                    <div id="template-save" style="margin: 3px 10px 0 0;">
                        <?php echo Plugin::$instance->utils->html->checkbox( [
                            'label'         => __( 'Save as template', 'groundhogg' ),
                            'name'          => 'save_as_template',
                            'id'            => 'save_as_template',
                            'class'         => '',
                            'value'         => '1',
                            'checked'       => $email->is_template(),
                        ] ); ?>
                    </div>
                    <?php Plugin::$instance->replacements->show_replacements_button(); ?>&nbsp;
                    <?php echo Plugin::$instance->utils->html->toggle( [
                        'name'          => 'editor_view',
                        'id'            => 'editor-toggle',
                        'value'         => 'ready',
                        'checked'       => false,
                        'on'            => 'HTML',
                        'off'           => 'Visual',
                    ]); ?>
                    <?php echo Plugin::$instance->utils->html->toggle( [
                        'name'          => 'email_status',
                        'id'            => 'status-toggle',
                        'value'         => 'ready',
                        'checked'       => $email->get_status() === 'ready',
                        'on'            => 'Ready',
                        'off'           => 'Draft',
                    ]); ?>
                </div>
                <div id="save">
                    <span class="spinner" style="float: left"></span>
                    <?php submit_button( __( 'Update', 'groundhogg' ), 'primary', 'update', false ); ?>
                    <?php submit_button( __( 'Update & Test', 'groundhogg' ), 'secondary', 'update_and_test', false ); ?>
                    <?php echo Plugin::$instance->utils->html->input( [
                        'type' => 'hidden',
                        'id' => 'send-test',
                        'name' => 'update_and_test'
                    ] ); ?>
                </div>
            </div>
        </div>
    </div>
    <!-- Main -->
    <div id='poststuff' class="wpgh-funnel-builder" style="overflow: hidden">

        <div id="post-body" class="metabox-holder columns-2" style="clear: both">

            <!-- begin elements area -->
            <div id="postbox-container-1" class="postbox-container sidebar">
                <div id="editor-panel">
                    <?php

                    foreach ( $blocks as $block_type => $block ){
                        do_action( "groundhogg/admin/emails/blocks/$block_type/settings_panel" );
                    }

                    ?>
                </div>
                <div id="settings-panel">
                    <div id="submitdiv" class="postbox">
                        <h3 class="hndle"><?php _e( 'Email Options' , 'groundhogg' ); ?></h3>
                        <div class="inside">
                            <div class="submitbox">
                                <div id="minor-publishing-actions">
                                    <table class="form-table">
                                        <tbody>
                                        <tr>
                                            <th><?php _e( 'From:', 'groundhogg'  ); ?></th>
                                            <?php $args = array( 'option_none' => __( 'The Contact\'s Owner' ) , 'id' => 'from_user', 'name' => 'from_user', 'selected' => $email->from_user ); ?>
                                            <td><?php echo Plugin::$instance->utils->html->dropdown_owners( $args ); ?>
                                                <?php echo html()->description( __( 'Choose who this email comes from.' ) ); ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php _e( 'Reply To:', 'groundhogg'  ); ?></th>
                                            <?php $args = [ 'type' => 'email', 'name' => 'reply_to_override', 'value' => $email->get_meta( 'reply_to_override' ) ]; ?>
                                            <td><?php echo Plugin::$instance->utils->html->input( $args ); ?>
                                                <?php echo html()->description( __( 'Override the email address replies are sent to. Leave empty to default to the sender address.' ) ); ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php _e( 'Alignment' ); ?></th>
                                            <td>
                                                <select id="email-align" name="email_alignment">
                                                    <option value="left" <?php if ( $email->get_meta( 'alignment' ) === 'left' ) echo 'selected' ; ?> ><?php _e('Left'); ?></option>
                                                    <option value="center" <?php if ( $email->get_meta( 'alignment' ) === 'center' ) echo 'selected' ; ?>><?php _e('Center'); ?></option>
                                                </select>
                                            </td>
                                        </tr>
                                        <script>
                                            jQuery(function($){$("#send_test").on( 'input', function(){
                                                $("#send-to").toggleClass( 'hidden' );
                                            })});
                                        </script>
                                        </tbody>
                                    </table>
                                    <table class="form-table">
                                        <tbody>
                                        <tr>
                                            <th><?php _e( 'Enable Browser View' , 'groundhogg' ); ?></th>
                                            <td><input type="checkbox" name="browser_view" value="1" <?php if ( $email->get_meta( 'browser_view' ) == 1 ) echo 'checked' ; ?>></td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id='blocks' class="postbox">
                        <h2 class="hndle"><?php echo __( 'Blocks', 'groundhogg' );?></h2>
                        <div class="inside">
                            <table>
                                <tbody>

                                <?php

                                $i = 0;

                                ?><tr><?php

                                    foreach ( $blocks as $block_type => $block ):

                                    if ( ( $i % 3 ) == 0 ):
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
                                    do_action( "groundhogg/admin/emails/blocks/$block_type/html" );
                                    ?></div> <?php

                                }

                                ?>
                                <div id="temp-html" class="hidden"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div id="post-body-content">

                <!-- Title Content -->
                <div id="titlediv">
                    <div id="titlewrap">

                        <!-- Subject Line -->
                        <label class="screen-reader-text" id="title-prompt-text" for="subject"><?php echo __('Subject Line: Used to capture the attention of the reader.', 'groundhogg');?></label>
                        <input placeholder="<?php echo __('Subject Line: Used to capture the attention of the reader.', 'groundhogg');?>" type="text" name="subject" size="30" value="<?php echo esc_attr( $email->get_subject_line() ); ?>" id="subject" spellcheck="true" autocomplete="off" required>

                        <!-- Pre Header-->
                        <label class="screen-reader-text" id="title-prompt-text" for="pre_header"><?php echo __('Pre Header Text: Used to summarize the content of the email.', 'groundhogg');?></label>
                        <input placeholder="<?php echo __('Pre Header Text: Used to summarize the content of the email.', 'groundhogg');?>" type="text" name="pre_header" size="30" value="<?php echo esc_attr( $email->get_pre_header() ); ?>" id="pre_header" spellcheck="true" autocomplete="off">
                    </div>
                </div>
                <?php Plugin::$instance->notices->print_notices(); ?>
                <!-- Editor -->
                <div id="email-content">
                    <div id="editor" class="editor" style="display: flex;">
                        <div id="email-body" class="main-email-body" style="flex-grow: 100;width: auto;">

                            <?php $alignment = $email->get_meta( 'alignment' );
                            //todo check
                            if ( $alignment === 'center' ){
                                $margins = "margin-left:auto;margin-right:auto;";
                            } else {
                                $margins = "margin-left:0;margin-right:auto;";
                            } ?>

                            <!-- Editor Content -->
                            <div id="email-inside" class="email-sortable email-content-wrapper" style="max-width: 580px;margin-top:40px;<?php echo $margins;?>">
                                <?php echo $email->get_content(); ?>
                            </div>
                            <div id="example-footer">
                                <?php ?>
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
                    <textarea id="content" name="content"><?php echo $email->get_content(); ?></textarea>
                </div>

            </div>

            <!-- Clearfix-->
            <div style="clear: both;"></div>
        </div>
    </div>
</form>
