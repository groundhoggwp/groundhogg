<?php
/**
 * Email Editor
 *
 * Allow the user to edit the email
 *
 * @package     wp-funnels
 * @subpackage  Includes/Emails
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! isset( $_GET['ID'] ) || ! is_numeric( $_GET['ID'] ) )
{
    wp_die( __( 'Email ID not supplied. Please try again', 'wp-funnels' ), __( 'Error', 'wp-funnels' ) );
}

if ( isset( $_GET['notice'] ) && $_GET['notice'] == 'success' ){
    ?><div class="notice notice-success"><p>Successfully created email!</p></div><?php
}

$email_id = intval( $_GET['ID'] );

wp_enqueue_media();
wp_enqueue_style( 'wp-color-picker' );
wp_enqueue_script( 'jquery-ui-sortable' );
wp_enqueue_script( 'jquery-ui-draggable' );
wp_enqueue_script( 'funnel-editor', WPFN_ASSETS_FOLDER . '/js/admin/email-editor.js' );
wp_enqueue_script('media-picker', WPFN_ASSETS_FOLDER . '/js/admin/media-picker.js' );
wp_enqueue_style( 'select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css' );
wp_enqueue_script( 'select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js' );

do_action( 'wpfn_email_editor_before_everything', $email_id );

$email = wpfn_get_email_by_id( $email_id );
?>

<style>
    select {vertical-align: top;}
    #titlediv #subject,
    #titlediv #pre_header {
        padding: 3px 8px;
        font-size: 1.7em;
        line-height: 100%;
        height: 1.7em;
        width: 100%;
        outline: 0;
        margin: 0 0 3px;
        background-color: #fff;
    }

    .wpfn-element div{
        box-sizing: border-box;
        display: inline-block;
        height: 70px;
        width: 100%;
        padding-top: 10px;
        /*padding-bottom: 70px;*/
    }

    .hndle label {
        margin: 0 10px 0 0;
    }

    .wpfn-element.ui-draggable-dragging .dashicons,
    #blocks .dashicons{
        font-size: 60px;
    }

    #blocks table{
        box-sizing: border-box;
        width: 100%;
        border-spacing: 7px;
    }

    #blocks table td{
        text-align: center;
        border: 1px solid #F1F1F1;

    }

    #blocks table td .wpfn-element,
    .wpfn-element p{
        text-align: center;
        cursor: move;
    }

    .wpfn-element.ui-draggable-dragging {
        font-size: 60px;
        width: 120px;
        height: 120px;
        background: #FFFFFF;
        border: 1px solid #F1F1F1;
    }

    select {
        vertical-align: top;
    }

    #email-content {margin-left: auto;margin-right: auto;box-sizing: border-box;background-color: #FFFFFF;}
    #email-body{width: 100%;min-height: 20px;padding: 10px;}
    #editor-actions{border-right: 1px solid rgb(238, 238, 238);min-height: 50px;}
    #editor-actions .postbox{border: none; box-shadow: none;}
    .content-inside p {padding: 10px;margin:0;}
    .content-wrapper{border: 2px solid transparent;}
    .active .content-wrapper{ border: 2px solid #35afe6 !important;}
    .active .content-wrapper .action-icons{ background-color: #35afe6 !important;}
    .ui-sortable-helper .action-icons,.content-wrapper:hover .action-icons{display: block;width:100px;margin-top:-30px;margin-right:-2px;float: right;background-color: #EAEAEA;text-align: center;vertical-align: center;}
    .ui-sortable-helper .content-wrapper,.content-wrapper:hover{border: 2px dashed #EAEAEA;background-color: #FFF;}
    [contenteditable]:focus {outline: 0px solid transparent;}
    .sortable-placeholder{border-width: 2px;}
    .action-icons {display: none;}
</style>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo __('Edit Email', 'wp-funnels');?></h1>
    <form method="post">
        <?php wp_nonce_field( 'edit_email', 'edit_email_nonce' ); ?>
        <?php do_action('wpfn_edit_email_form_before'); ?>

        <div id='poststuff' class="wpfn-funnel-builder">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <div id="titlediv">
                        <div id="titlewrap">
                            <label class="screen-reader-text" id="title-prompt-text" for="subject"><?php echo __('Subject Line: Used to capture the attention of the reader.', 'wp-funnels');?></label>
                            <input placeholder="<?php echo __('Subject Line: Used to capture the attention of the reader.', 'wp-funnels');?>" type="text" name="subject" size="30" value="<?php echo  $email->subject; ?>" id="subject" spellcheck="true" autocomplete="off">
                            <label class="screen-reader-text" id="title-prompt-text" for="pre_header"><?php echo __('Pre Header Text: Used to summarize the content of the email.', 'wp-funnels');?></label>
                            <input placeholder="<?php echo __('Pre Header Text: Used to summarize the content of the email.', 'wp-funnels');?>" type="text" name="pre_header" size="30" value="<?php echo  $email->pre_header; ?>" id="pre_header" spellcheck="true" autocomplete="off">
                        </div>
                    </div>
                    <div id="email-content" class="postbox">
                        <h3 class="hndle"><?php _e( 'Email Editor'); ?></h3>
                        <div id="editor" class="editor" style="display: flex;">
                            <div id="editor-actions" style="display: inline-block;width: 280px;margin-right: 10px;">
                                <div class="editor-actions-inner">
                                    <div id="text_block-editor" class="postbox">
                                        <h3 class="hndle"><?php _e( 'Text'); ?></h3>
                                        <div class="inside">
                                            <div class="options">
                                                <table class="form-table">
                                                    <tr>
                                                        <th><?php _e( 'H1 Size'); ?>:</th>
                                                        <td><input class="input" type="number" id="h1-size" min="10" max="40" value=""></td>
                                                    </tr>
                                                    <tr>
                                                        <th><?php _e( 'H1 Font'); ?>:</th>
                                                        <td><?php wpfn_font_select( 'h1-font', 'h1-font' ); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th><?php _e( 'H2 Size'); ?>:</th>
                                                        <td><input class="input" type="number" id="h2-size" min="10" max="40" value=""></td>
                                                    </tr>
                                                    <tr>
                                                        <th><?php _e( 'H2 Font'); ?>:</th>
                                                        <td><?php wpfn_font_select( 'h2-font', 'h2-font' ); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th><?php _e( 'Paragraph Size'); ?>:</th>
                                                        <td><input class="input" type="number" id="p-size" min="10" max="40" value=""></td>
                                                    </tr>
                                                    <tr>
                                                        <th><?php _e( 'Paragraph Font'); ?>:</th>
                                                        <td><?php wpfn_font_select( 'p-font', 'p-font' ); ?></td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="button_block-editor" class="postbox hidden">
                                        <h3 class="hndle"><?php _e( 'Button'); ?></h3>
                                        <div class="inside">
                                            <div class="options">
                                                <table class="form-table">
                                                    <tr>
                                                        <th><?php _e( 'Text Size'); ?>:</th>
                                                        <td><input class="input" type="number" id="button-size" min="10" max="40" value=""></td>
                                                    </tr>
                                                    <tr>
                                                        <th><?php _e( 'Button Font'); ?>:</th>
                                                        <td><?php wpfn_font_select( 'button-font', 'button-font' ); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th><?php _e( 'Button Color'); ?>:</th>
                                                        <td><?php wpfn_color_select( 'button-color', 'button-color', '#dd9933' ); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th><?php _e( 'Text Color'); ?>:</th>
                                                        <td><?php wpfn_color_select( 'button-text-color', 'button-text-color', '#FFFFFF' ); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th><?php _e( 'Button Link'); ?>:</th>
                                                        <td><input type="url" id="button-link" name="button-link" value=""></td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="spacer_block-editor" class="postbox hidden">
                                        <h3 class="hndle"><?php _e( 'Spacer'); ?></h3>
                                        <div class="inside">
                                            <div class="options">
                                                <table class="form-table">
                                                    <tr>
                                                        <th><?php _e( 'Spacer Height'); ?>:</th>
                                                        <td><input class="input" type="number" id="spacer-size" min="10" max="40" value=""></td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="divider_block-editor"></div>
                                    <div id="image_block-editor" class="postbox hidden">
                                        <h3 class="hndle"><?php _e( 'Image'); ?></h3>
                                        <div class="inside">
                                            <div class="options">
                                                <table class="form-table">
                                                    <tr>
                                                        <th><?php _e( 'Image'); ?>:</th>
                                                        <td>
                                                            <input id="upload_image_button" type="button" class="button" value="<?php _e( 'Upload Image' ); ?>" />
                                                            <input type='hidden' name='image-src' id='image-src'>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th><?php _e( 'Image Width'); ?></th>
                                                        <td><input class="input" type="number" id="image-width" name="image-width" min="0" max="100"></td>
                                                    </tr>
                                                    <tr>
                                                        <th><?php _e( 'Alignment'); ?></th>
                                                        <td>
                                                            <select id="image-align" name="image-align">
                                                                <option value="left"><?php _e('Left'); ?></option>
                                                                <option value="center"><?php _e('Center'); ?></option>
                                                                <option value="right"><?php _e('Right'); ?></option>
                                                            </select>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th><?php _e( 'Alt Text'); ?></th>
                                                        <td><input class="input" type="text" id="image-alt" name="image-alt"></td>
                                                    </tr>
                                                    <tr>
                                                        <th><?php _e( 'Image title'); ?></th>
                                                        <td><input class="input" type="text" id="image-title" name="image-title"></td>
                                                    </tr>
                                                    <tr>
                                                        <th><?php _e( 'Image Link'); ?></th>
                                                        <td><input type="url" id="image-link" name="image-link" value=""></td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="button_block-editor"></div>
                                    <div id="social_block-editor"></div>
                                    <div id="video_block-editor"></div>
                                </div>
                            </div>
                            <div id="email-body" class="main-email-body" style="display: inline-block;max-width: 650px;vertical-align: top">

                                <div id="email-inside" class="email-sortable">
                                    <?php if ( empty( $email->content ) ): ?>
                                        <?php wpfn_get_email_block( 'image_block' ); ?>
                                        <?php wpfn_get_email_block( 'text_block' ); ?>
                                        <?php wpfn_get_email_block( 'divider_block' ); ?>
                                        <?php wpfn_get_email_block( 'button_block') ; ?>
                                        <?php wpfn_get_email_block( 'divider_block' ); ?>
                                        <?php wpfn_get_email_block( 'text_block' ); ?>
                                    <?php else: ?>
                                        <?php echo $email->content; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="hidden">
                        <textarea id="content" name="content"><?php echo $email->content; ?></textarea>
                    </div>
                </div>
                <!-- begin elements area -->
                <div id="postbox-container-1" class="postbox-container sticky">
                    <div id="submitdiv" class="postbox">
                        <h3 class="hndle"><?php echo __( 'Email Actions', 'wp-funnels' );?></h3>
                        <div class="inside">
                            <div class="submitbox">
                                <div id="minor-publishing-actions">
                                    <?php do_action( 'wpfn_email_actions_before' ); ?>
                                    <table class="form-table">
                                        <tbody>
                                        <tr>
                                            <th><?php _e( 'From User:' ); ?></th>
                                            <?php $args = array( 'id' => 'from_user', 'name' => 'from_user', 'selected' => wpfn_get_email_meta( $email_id, 'from_user', true ) ); ?>
                                            <td><?php wp_dropdown_users( $args); ?><script>jQuery(document).ready(function(){jQuery( '#from_user' ).select2()});</script></td>
                                        </tr>
                                        <tr>
                                            <th><?php _e( 'Send Test:' ); ?></th>
                                            <?php $args = array( 'id' => 'test_email', 'name' => 'test_email', 'selected' => wpfn_get_email_meta( $email_id, 'test_email', true ) ); ?>
                                            <td><?php wp_dropdown_users( $args); ?><script>jQuery(document).ready(function(){jQuery( '#test_email' ).select2()});</script></td>
                                        </tr>
                                        </tbody>
                                    </table>
                                    <?php do_action( 'wpfn_email_actions_after' ); ?>
                                </div>
                                <div id="major-publishing-actions">
                                    <div id="delete-action">
                                        <a class="submitdelete deletion" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=emails' ), 'delete_email', 'wpfn_nonce' ) ); ?>"><?php echo esc_html__( 'Delete Email', 'wp-funnels' ); ?></a>
                                    </div>
                                    <div id="publishing-action">
                                        <span class="spinner"></span>
                                        <?php submit_button('Update Email', 'primary', 'update_email', false ); ?>
                                    </div>
                                    <div class="clear"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php do_action( 'wpfn_email_side_actions_after' ); ?>
                    <?php do_action( 'wpfn_email_blocks_before' ); ?>
                    <div id='blocks' class="postbox">
                        <h2 class="hndle"><?php echo __( 'Blocks', 'wp-funnels' );?></h2>
                        <div class="inside">
                            <table>
                                <tbody>
                                <tr>
                                    <td><div id='text_block' class="wpfn-element email-draggable"><div class="dashicons dashicons-text"></div><p>Text</p></div></td>
                                    <td><div id='spacer_block' class="wpfn-element email-draggable"><div class="dashicons dashicons-image-flip-vertical"></div><p>Spacer</p></div></td>
                                </tr>
                                <tr>
                                    <td><div id='divider_block' class="wpfn-element email-draggable"><div class="dashicons dashicons-minus"></div><p>Divider</p></div></td>
                                    <td><div id='image_block' class="wpfn-element email-draggable"><div class="dashicons dashicons-format-image"></div><p>Image</p></div></td>
                                </tr>
                                <tr>
                                    <td><div id='button_block' class="wpfn-element email-draggable"><div class="dashicons dashicons-id-alt"></div><p>Button</p></div></td>
                                    <td><div id='social_block' class="wpfn-element email-draggable"><div class="dashicons dashicons-networking"></div><p>Social</p></div></td>
                                </tr>
                                <tr>
                                    <td><div id='video_block' class="wpfn-element email-draggable"><div class="dashicons dashicons-video-alt3"></div><p>Video</p></div></td>
                                    <td><div id='code_block' class="wpfn-element email-draggable"><div class="dashicons dashicons-editor-code"></div><p>HTML</p></div></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div
                </div>
                <!-- End elements area-->

                <!-- main email editing area -->
                <div id="postbox-container-2" class="postbox-container funnel-editor">

                </div>
                <!-- end main email editing area -->
            </div>
        </div>
    </form>
</div>
