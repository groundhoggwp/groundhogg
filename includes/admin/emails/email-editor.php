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

wp_enqueue_script( 'jquery-ui-sortable' );
wp_enqueue_script( 'jquery-ui-draggable' );
wp_enqueue_script( 'funnel-editor', WPFN_ASSETS_FOLDER . '/js/admin/email-editor.js' );

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

    #email-content {
        width: 900px;
        margin-left: auto;
        margin-right: auto;
        box-sizing: border-box;
        background-color: #FFFFFF;
        margin-top: 50px;
    }

    #email-body{width: 100%;min-height: 20px;padding: 10px;}
    #editor-actions{border-right: 1px solid rgb(238, 238, 238);min-height: 50px;}
    #editor-actions .postbox{border: none; box-shadow: none;}
    .content-inside p {padding: 10px;margin:0;}
    .content-wrapper{border: 2px solid transparent;}
    .ui-sortable-helper .content-wrapper,.content-wrapper:hover{border: 2px dashed #EAEAEA;background-color: #FFF;}
    [contenteditable]:focus {outline: 0px solid transparent;}
    .sortable-placeholder{border-width: 2px;}
    .action-icons {display: none;}
    .ui-sortable-helper .action-icons,
    .content-wrapper:hover .action-icons{display: block;width:100px;margin-top:-30px;margin-right:-2px;float: right;background-color: #EAEAEA;text-align: center;vertical-align: center;}
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
                            <label class="screen-reader-text" id="title-prompt-text" for="subject"><?php echo __('Enter Subject Line Here', 'wp-funnels');?></label>
                            <input placeholder="<?php echo __('Enter Subject Line Here', 'wp-funnels');?>" type="text" name="subject" size="30" value="<?php echo  $email->subject; ?>" id="subject" spellcheck="true" autocomplete="off">
                            <label class="screen-reader-text" id="title-prompt-text" for="pre_header"><?php echo __('Enter Pre Header text Here', 'wp-funnels');?></label>
                            <input placeholder="<?php echo __('Enter Pre Header Line Here', 'wp-funnels');?>" type="text" name="pre_header" size="30" value="<?php echo  $email->pre_header; ?>" id="pre_header" spellcheck="true" autocomplete="off">
                            <table>
                                <tr>
                                    <th style="text-align: left;"><label for="from_name"><?php echo __( 'From Name', 'wp-funnels' )?></th>
                                    <th style="text-align: left;"><label for="from_email"><?php echo __( 'From Email', 'wp-funnels' )?></th>
                                </tr>
                                <tr>
                                    <td><?php echo wpfn_admin_text_input_field( 'from_name', 'from_name', $email->from_name );?></td>
                                    <td><?php echo wpfn_admin_email_input_field( 'from_email', 'from_email', $email->from_email );?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div id="email-content" class="postbox">
                        <h3 class="hndle">Email Editor</h3>
                        <div style="display: flex;">
                            <div id="editor-actions" style="display: inline-block;width: 255px;margin-right: 10px;">
                                <div class="postbox">
                                    <h3 class="hndle">Styling</h3>
                                    <div class="inside">
                                        <div id="text_block-editor">
                                            <table class="form-table">
                                                <tr>
                                                    <th>H1 Size:</th>
                                                    <td><input class="input" type="number" id="h1-size" min="10" max="40" value=""></td>
                                                </tr>
                                                <tr>
                                                    <th>H1 Font:</th>
                                                    <td>
                                                        <select name="h1-font" id="h1-font">
                                                            <option value="Arial, sans-serif">Arial</option>
                                                            <option value="Arial Black, Arial, sans-serif">Arial Black</option>
                                                            <option value="Century Gothic, Times, serif">Century Gothic</option>
                                                            <option value="Courier, monospace">Courier</option>
                                                            <option value="Courier New, monospace">Courier New</option>
                                                            <option value="Geneva, Tahoma, Verdana, sans-serif">Geneva</option>
                                                            <option value="Georgia, Times, Times New Roman, serif">Georgia</option>
                                                            <option value="Helvetica, Arial, sans-serif">Helvetica</option>
                                                            <option value="Lucida, Geneva, Verdana, sans-serif">Lucida</option>
                                                            <option value="Tahoma, Verdana, sans-serif">Tahoma</option>
                                                            <option value="Times, Times New Roman, Baskerville, Georgia, serif">Times</option>
                                                            <option value="Times New Roman, Times, Georgia, serif">Times New Roman</option>
                                                            <option value="Verdana, Geneva, sans-serif">Verdana</option>
                                                        </select>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>H2 Size:</th>
                                                    <td><input class="input" type="number" id="h2-size" min="10" max="40" value=""></td>
                                                </tr>
                                                <tr>
                                                    <th>H2 Font:</th>
                                                    <td><select name="h2-font" id="h2-font">
                                                            <option value="Arial, sans-serif">Arial</option>
                                                            <option value="Arial Black, Arial, sans-serif">Arial Black</option>
                                                            <option value="Century Gothic, Times, serif">Century Gothic</option>
                                                            <option value="Courier, monospace">Courier</option>
                                                            <option value="Courier New, monospace">Courier New</option>
                                                            <option value="Geneva, Tahoma, Verdana, sans-serif">Geneva</option>
                                                            <option value="Georgia, Times, Times New Roman, serif">Georgia</option>
                                                            <option value="Helvetica, Arial, sans-serif">Helvetica</option>
                                                            <option value="Lucida, Geneva, Verdana, sans-serif">Lucida</option>
                                                            <option value="Tahoma, Verdana, sans-serif">Tahoma</option>
                                                            <option value="Times, Times New Roman, Baskerville, Georgia, serif">Times</option>
                                                            <option value="Times New Roman, Times, Georgia, serif">Times New Roman</option>
                                                            <option value="Verdana, Geneva, sans-serif">Verdana</option>
                                                        </select></td>
                                                </tr>
                                                <tr>
                                                    <th>Paragraph Size:</th>
                                                    <td><input class="input" type="number" id="p-size" min="10" max="40" value=""></td>
                                                    <script>jQuery( '#p-size' ).on( 'change' , function () {
                                                            jQuery( '#email-body p' ).css( 'font-size', jQuery(this).val() + 'px' );
                                                        });</script>
                                                </tr>
                                                <tr>
                                                    <th>Paragraph Font:</th>
                                                    <td><select name="p-font" id="p-font">
                                                            <option value="Arial, sans-serif">Arial</option>
                                                            <option value="Arial Black, Arial, sans-serif">Arial Black</option>
                                                            <option value="Century Gothic, Times, serif">Century Gothic</option>
                                                            <option value="Courier, monospace">Courier</option>
                                                            <option value="Courier New, monospace">Courier New</option>
                                                            <option value="Geneva, Tahoma, Verdana, sans-serif">Geneva</option>
                                                            <option value="Georgia, Times, Times New Roman, serif">Georgia</option>
                                                            <option value="Helvetica, Arial, sans-serif">Helvetica</option>
                                                            <option value="Lucida, Geneva, Verdana, sans-serif">Lucida</option>
                                                            <option value="Tahoma, Verdana, sans-serif">Tahoma</option>
                                                            <option value="Times, Times New Roman, Baskerville, Georgia, serif">Times</option>
                                                            <option value="Times New Roman, Times, Georgia, serif">Times New Roman</option>
                                                            <option value="Verdana, Geneva, sans-serif">Verdana</option>
                                                        </select></td>
                                                    <script>jQuery( '#p-font' ).on( 'change' , function () {
                                                            jQuery( '#email-body p' ).css( 'font-family', jQuery(this).val() + 'px' );
                                                        });</script>
                                                </tr>
                                            </table>
                                        </div>
                                        <div id="button_block-editor">
                                            <table class="form-table">
                                                <tr>
                                                    <th>Font Size:</th>
                                                    <td><input class="input" type="number" id="button-font-size" min="10" max="40" value=""></td>
                                                </tr>
                                                <tr>
                                                    <th>H1 Font:</th>
                                                    <td>
                                                        <select name="h1-font" id="h1-font">
                                                            <option value="Arial, sans-serif">Arial</option>
                                                            <option value="Arial Black, Arial, sans-serif">Arial Black</option>
                                                            <option value="Century Gothic, Times, serif">Century Gothic</option>
                                                            <option value="Courier, monospace">Courier</option>
                                                            <option value="Courier New, monospace">Courier New</option>
                                                            <option value="Geneva, Tahoma, Verdana, sans-serif">Geneva</option>
                                                            <option value="Georgia, Times, Times New Roman, serif">Georgia</option>
                                                            <option value="Helvetica, Arial, sans-serif">Helvetica</option>
                                                            <option value="Lucida, Geneva, Verdana, sans-serif">Lucida</option>
                                                            <option value="Tahoma, Verdana, sans-serif">Tahoma</option>
                                                            <option value="Times, Times New Roman, Baskerville, Georgia, serif">Times</option>
                                                            <option value="Times New Roman, Times, Georgia, serif">Times New Roman</option>
                                                            <option value="Verdana, Geneva, sans-serif">Verdana</option>
                                                        </select>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>H2 Size:</th>
                                                    <td><input class="input" type="number" id="h2-size" min="10" max="40" value=""></td>
                                                </tr>
                                                <tr>
                                                    <th>H2 Font:</th>
                                                    <td><select name="h2-font" id="h2-font">
                                                            <option value="Arial, sans-serif">Arial</option>
                                                            <option value="Arial Black, Arial, sans-serif">Arial Black</option>
                                                            <option value="Century Gothic, Times, serif">Century Gothic</option>
                                                            <option value="Courier, monospace">Courier</option>
                                                            <option value="Courier New, monospace">Courier New</option>
                                                            <option value="Geneva, Tahoma, Verdana, sans-serif">Geneva</option>
                                                            <option value="Georgia, Times, Times New Roman, serif">Georgia</option>
                                                            <option value="Helvetica, Arial, sans-serif">Helvetica</option>
                                                            <option value="Lucida, Geneva, Verdana, sans-serif">Lucida</option>
                                                            <option value="Tahoma, Verdana, sans-serif">Tahoma</option>
                                                            <option value="Times, Times New Roman, Baskerville, Georgia, serif">Times</option>
                                                            <option value="Times New Roman, Times, Georgia, serif">Times New Roman</option>
                                                            <option value="Verdana, Geneva, sans-serif">Verdana</option>
                                                        </select></td>
                                                </tr>
                                                <tr>
                                                    <th>Paragraph Size:</th>
                                                    <td><input class="input" type="number" id="p-size" min="10" max="40" value=""></td>
                                                    <script>jQuery( '#p-size' ).on( 'change' , function () {
                                                            jQuery( '#email-body p' ).css( 'font-size', jQuery(this).val() + 'px' );
                                                        });</script>
                                                </tr>
                                                <tr>
                                                    <th>Paragraph Font:</th>
                                                    <td><select name="p-font" id="p-font">
                                                            <option value="Arial, sans-serif">Arial</option>
                                                            <option value="Arial Black, Arial, sans-serif">Arial Black</option>
                                                            <option value="Century Gothic, Times, serif">Century Gothic</option>
                                                            <option value="Courier, monospace">Courier</option>
                                                            <option value="Courier New, monospace">Courier New</option>
                                                            <option value="Geneva, Tahoma, Verdana, sans-serif">Geneva</option>
                                                            <option value="Georgia, Times, Times New Roman, serif">Georgia</option>
                                                            <option value="Helvetica, Arial, sans-serif">Helvetica</option>
                                                            <option value="Lucida, Geneva, Verdana, sans-serif">Lucida</option>
                                                            <option value="Tahoma, Verdana, sans-serif">Tahoma</option>
                                                            <option value="Times, Times New Roman, Baskerville, Georgia, serif">Times</option>
                                                            <option value="Times New Roman, Times, Georgia, serif">Times New Roman</option>
                                                            <option value="Verdana, Geneva, sans-serif">Verdana</option>
                                                        </select></td>
                                                    <script>jQuery( '#p-font' ).on( 'change' , function () {
                                                            jQuery( '#email-body p' ).css( 'font-family', jQuery(this).val() + 'px' );
                                                        });</script>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div id="spacer_block-editor"></div>
                                <div id="divider_block-editor"></div>
                                <div id="image_block-editor"></div>
                                <div id="button_block-editor"></div>
                                <div id="social_block-editor"></div>
                                <div id="video_block-editor"></div>
                            </div>
                            <div id="email-body" class="main-email-body" style="display: inline-block;width: 650px;vertical-align: top">
                                <div class="ui-sortable">
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

                    <?php // wp_editor( $email->content, 'content',  array( 'textarea_name' => 'content' ) ); ?>
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
                                            <th>
                                                <label for="test_email"><?php echo esc_html__('Send Test:', 'wp-funnels');?></label>
                                            </th>
                                            <td>
                                                <?php $prev_test_email = ( isset( $_POST['test_email'] ) )? $_POST['test_email'] : get_bloginfo( 'admin_email' ); ?>
                                                <input type="email" class="input" id="test_email" name="test_email" value="<?php echo $prev_test_email; ?>"/>
                                            </td>
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
                                        <?php submit_button('Update Email', 'primary', 'update_email', false ); ?>                                    </div>
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
                                    <td><div id='text_block' class="wpfn-element ui-draggable"><div class="dashicons dashicons-text"></div><p>Text</p></div></td>
                                    <td><div id='spacer_block' class="wpfn-element ui-draggable"><div class="dashicons dashicons-image-flip-vertical"></div><p>Spacer</p></div></td>
                                </tr>
                                <tr>
                                    <td><div id='divider_block' class="wpfn-element ui-draggable"><div class="dashicons dashicons-minus"></div><p>Divider</p></div></td>
                                    <td><div id='image_block' class="wpfn-element ui-draggable"><div class="dashicons dashicons-format-image"></div><p>Image</p></div></td>
                                </tr>
                                <tr>
                                    <td><div id='button_block' class="wpfn-element ui-draggable"><div class="dashicons dashicons-id-alt"></div><p>Button</p></div></td>
                                    <td><div id='social_block' class="wpfn-element ui-draggable"><div class="dashicons dashicons-networking"></div><p>Social</p></div></td>
                                </tr>
                                <tr>
                                    <td><div id='video_block' class="wpfn-element ui-draggable"><div class="dashicons dashicons-video-alt3"></div><p>Video</p></div></td>
                                    <td><div id='code_block' class="wpfn-element ui-draggable"><div class="dashicons dashicons-editor-code"></div><p>HTML</p></div></td>
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
