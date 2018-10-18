<?php
/**
 * Email Editor
 *
 * Allow the user to edit the email
 *
 * @package     groundhogg
 * @subpackage  Includes/Emails
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

//for media picker
wp_enqueue_media();
//for link editor
wp_enqueue_editor();
wp_enqueue_script('wplink');
wp_enqueue_style('editor-buttons');
//for color picker
wp_enqueue_style( 'wp-color-picker' );
//for drag & drop
wp_enqueue_script( 'jquery-ui-sortable' );
wp_enqueue_script( 'jquery-ui-draggable' );
//custom scripts
wp_enqueue_script( 'email-editor', WPGH_ASSETS_FOLDER . 'js/admin/email-editor.js', array( 'wp-color-picker' ) );
wp_enqueue_style('email-editor', WPGH_ASSETS_FOLDER . 'css/admin/email-editor.css' );

wp_enqueue_script('media-picker', WPGH_ASSETS_FOLDER . 'js/admin/media-picker.js' );

wp_enqueue_script('simple-editor', WPGH_ASSETS_FOLDER . 'js/admin/simple-editor.js' );
wp_enqueue_style('simple-editor', WPGH_ASSETS_FOLDER . 'css/admin/simple-editor.css' );

wp_enqueue_script( 'sticky-sidebar', WPGH_ASSETS_FOLDER . '/lib/sticky-sidebar/sticky-sidebar.js' );
wp_enqueue_script( 'jquery-sticky-sidebar', WPGH_ASSETS_FOLDER . '/lib/sticky-sidebar/jquery.sticky-sidebar.js' );
//for select 2
wp_enqueue_style( 'select2' );
wp_enqueue_script( 'select2' );

$email_id = intval( $_GET['email'] );
$email = new WPGH_Email( $email_id );

?>

<!-- RETURN PATH NOTICE-->
<?php if ( isset( $_REQUEST['return_funnel'] ) ): ?>
    <div class="notice notice-info is-dismissible">
        <p><a href="<?php echo admin_url( 'admin.php?page=gh_funnels&action=edit&funnel=' . $_REQUEST['return_funnel'] . '#' . $_REQUEST['return_step'] ); ?>"><?php  _e( '&larr; Back to editing funnel' ); ?></a></p>
    </div>
<?php endif; ?>
<!-- /RETURN PATH -->

<!-- NEW EMAIL TAB TITLE -->
<span class="hidden" id="new-title"><?php echo $email->subject; ?> &lsaquo; </span>
<script>
    document.title = jQuery( '#new-title' ).text() + document.title;
</script>
<!-- /END TITLE -->


<form method="post">

    <!-- Before-->
    <?php wp_nonce_field(); ?>
    <?php do_action('wpgh_edit_email_form_before'); ?>

    <!-- Main -->
    <div id='poststuff' class="wpgh-funnel-builder" style="overflow: hidden">
        <div id="post-body" class="metabox-holder columns-2" style="clear: both">

            <div id="post-body-content">

                <!-- Title Content -->
                <div id="titlediv">
                    <div id="titlewrap">

                        <!-- Subject Line -->
                        <label class="screen-reader-text" id="title-prompt-text" for="subject"><?php echo __('Subject Line: Used to capture the attention of the reader.', 'groundhogg');?></label>
                        <input placeholder="<?php echo __('Subject Line: Used to capture the attention of the reader.', 'groundhogg');?>" type="text" name="subject" size="30" value="<?php echo  $email->subject; ?>" id="subject" spellcheck="true" autocomplete="off" required>

                        <!-- Pre Header-->
                        <label class="screen-reader-text" id="title-prompt-text" for="pre_header"><?php echo __('Pre Header Text: Used to summarize the content of the email.', 'groundhogg');?></label>
                        <input placeholder="<?php echo __('Pre Header Text: Used to summarize the content of the email.', 'groundhogg');?>" type="text" name="pre_header" size="30" value="<?php echo  $email->pre_header; ?>" id="pre_header" spellcheck="true" autocomplete="off">
                    </div>
                </div>
                <!-- / Title Content -->

                <!-- Editor -->
                <div id="email-content" class="postbox">
                    <h3 class="hndle"><?php _e( 'Email Editor'); ?></h3>
                    <div id="editor" class="editor" style="display: flex;">

                        <!-- Block Options -->
                        <div id="editor-actions" style="display: inline-block;width: 280px;float: left">
                            <div style="width: 280px;"></div>
                            <div class="editor-actions-inner">

                                <!-- Text Block Otions -->
                                <div id="text_block-editor" class="postbox hidden">
                                    <h3 class="hndle"><?php _e( 'Text'); ?></h3>
                                    <div class="inside">
                                        <div class="options">
                                            <table class="form-table">
                                                <tr>
                                                    <th><?php _e( 'H1 Size'); ?>:</th>
                                                    <td><input class="input" type="number" id="h1-size" min="10" max="40" value="30"></td>
                                                </tr>
                                                <tr>
                                                    <th><?php _e( 'H1 Font'); ?>:</th>
                                                    <td><?php echo WPGH()->html->font_picker( array( 'id' => 'h1-font', 'name' => 'h1-font' ) ); ?></td>
                                                </tr>
                                                <tr>
                                                    <th><?php _e( 'H2 Size'); ?>:</th>
                                                    <td><input class="input" type="number" id="h2-size" min="10" max="40" value="20"></td>
                                                </tr>
                                                <tr>
                                                    <th><?php _e( 'H2 Font'); ?>:</th>
                                                    <td><?php echo WPGH()->html->font_picker( array( 'name' => 'h2-font', 'id' => 'h2-font' ) ); ?></td>
                                                </tr>
                                                <tr>
                                                    <th><?php _e( 'Paragraph Size'); ?>:</th>
                                                    <td><input class="input" type="number" id="p-size" min="10" max="40" value="16"></td>
                                                </tr>
                                                <tr>
                                                    <th><?php _e( 'Paragraph Font'); ?>:</th>
                                                    <td><?php echo WPGH()->html->font_picker( array( 'name' => 'p-font', 'id' => 'p-font' ) ); ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Button Options -->
                                <div id="button_block-editor" class="postbox hidden">
                                    <h3 class="hndle"><?php _e( 'Button'); ?></h3>
                                    <div class="inside">
                                        <div class="options">
                                            <table class="form-table">
                                                <tr>
                                                    <th><?php _e( 'Button Text'); ?>:</th>
                                                    <td><input type="text" id="button-text" name="button-text" value=""></td>
                                                </tr>
                                                <tr>
                                                    <th><?php _e( 'Text Size'); ?>:</th>
                                                    <td><input class="input" type="number" id="button-size" min="10" max="40" value=""></td>
                                                </tr>
                                                <tr>
                                                    <th><?php _e( 'Button Font'); ?>:</th>
                                                    <td><?php echo WPGH()->html->font_picker( array( 'name' => 'button-font', 'id' => 'button-font' ) ); ?></td>
                                                </tr>
                                                <tr>
                                                    <th><?php _e( 'Button Color'); ?>:</th>
                                                    <td><?php echo WPGH()->html->color_picker( array( 'name' => 'button-color', 'id' => 'button-color', 'default' => '#dd9933' ) ); ?></td>
                                                </tr>
                                                <tr>
                                                    <th><?php _e( 'Text Color'); ?>:</th>
                                                    <td><?php echo WPGH()->html->color_picker( array( 'name' => 'button-text-color', 'id' => 'button-text-color', 'default' => '#FFFFFF' ) ); ?></td>
                                                </tr>
                                                <tr>
                                                    <th><?php _e( 'Button Link'); ?>:</th>
                                                    <td><input type="text" id="button-link" name="button-link" value="">
                                                        <!--
                                                    <p><a href="#" id="insert-link" data-target="button-link"><?php _e( 'Insert Link' ); ?></a></p>
                                                    <script>
                                                        jQuery( function($){
                                                            $( '#insert-link' ).linkPicker();
                                                        });
                                                    </script> -->
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Spacer Options -->
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

                                <!-- Divider Options -->
                                <div id="divider_block-editor" class="postbox hidden">
                                    <h3 class="hndle"><?php _e( 'Divider'); ?></h3>
                                    <div class="inside">
                                        <div class="options">
                                            <table class="form-table">
                                                <tr>
                                                    <th><?php _e( 'Divider width'); ?>:</th>
                                                    <td><input class="input" type="number" id="divider-width" min="10" max="100" value=""></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Image Options -->
                                <div id="image_block-editor" class="postbox hidden">
                                    <h3 class="hndle"><?php _e( 'Image'); ?></h3>
                                    <div class="inside">
                                        <div class="options">
                                            <table class="form-table">
                                                <tr>
                                                    <th><?php _e( 'Image'); ?>:</th>
                                                    <td>
                                                        <input id="upload_image_button" type="button" class="button" value="<?php _e( 'Upload Image' ); ?>" />

                                                        <input style="margin-top: 10px;" type='url' name='image-src' id='image-src'>
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
                                                    <td><input type="text" id="image-link" name="image-link" value=""></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Code Block Options -->
                                <div id="code_block-editor" class="postbox hidden">
                                    <h3 class="hndle"><?php _e( 'Custom HTML'); ?></h3>
                                    <div class="inside">
                                        <div class="options">
                                            <table class="form-table">
                                                <tr>
                                                    <th><?php _e( 'HTML Content'); ?>:</th>
                                                </tr>
                                                <tr>
                                                    <td><textarea class="input" rows="20" style="width: 100%;" id="custom-html-content"></textarea></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>

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
                        <div id="email-body" class="main-email-body" style="flex-grow: 100;width: auto; overflow:visible;">
                            <?php $alignment = $email->get_meta( 'alignment' );
                            if ( $alignment === 'center' ){
                                $margins = "margin-left:auto;margin-right:auto;";
                            } else {
                                $margins = "margin-left:0;margin-right:auto;";
                            } ?>

                            <!-- Editor Content -->
                            <div id="email-inside" class="email-sortable" style="max-width: 580px;margin-top:40px;<?php echo $margins;?>">
                                <?php if ( empty( $email->content ) ): ?>
                                    <!-- Empty content -->
                                    <?php wpgh_get_email_block( 'image_block' ); ?>
                                    <?php wpgh_get_email_block( 'text_block' ); ?>
                                    <?php wpgh_get_email_block( 'divider_block' ); ?>
                                    <?php wpgh_get_email_block( 'button_block') ; ?>
                                    <?php wpgh_get_email_block( 'divider_block' ); ?>
                                    <?php wpgh_get_email_block( 'text_block' ); ?>
                                <?php else: ?>
                                    <?php echo $email->content; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="clear: both;"></div>
                    </div>
                </div>

                <!-- Saved Content -->
                <div class="hidden">
                    <textarea id="content" name="content"><?php echo $email->content; ?></textarea>
                </div>

                <div class="postbox" id="replacements">
                    <h2 class="hndle"><?php _e( 'Replacements', 'groudhogg' ); ?></h2>
                    <div class="inside">

                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                            <tr>
                                <th><?php _e( 'Code' ); ?></th>
                                <th><?php _e( 'Description' ); ?></th>
                            </tr>
                            </thead>
                            <tbody>

                            <?php foreach ( WPGH()->replacements->get_replacements() as $replacement ): ?>
                                <tr>
                                    <td>
                                        <input style="border: none;outline: none;background: transparent;width: 100%;" onfocus="this.select();" value="{<?php echo $replacement[ 'code' ]; ?>}">
                                    </td>
                                    <td>
                                        <span><?php echo $replacement[ 'description' ]; ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
            <!-- begin elements area -->
            <div id="postbox-container-1" class="postbox-container sidebar">
                <div id="submitdiv" class="postbox">
                    <h3 class="hndle"><?php echo __( 'Email Actions', 'groundhogg' );?></h3>
                    <div class="inside">
                        <div class="submitbox">
                            <div id="minor-publishing-actions">
                                <?php do_action( 'wpgh_email_actions_before' ); ?>
                                <table class="form-table">
                                    <tbody>
                                    <tr>
                                        <th><?php _e( 'Status:' ); ?></th>
                                        <td>
                                            <input type="hidden" id="status" name="status" value="<?php echo $email->status; ?>" >
                                            <div class="onoffswitch" style="text-align: left;">
                                                <input type="checkbox" name="onoffswitch" class="onoffswitch-checkbox" id="status-toggle" <?php if ( $email->status === 'ready' ) echo 'checked'; ?> >
                                                <label class="onoffswitch-label" for="status-toggle">
                                                    <span class="onoffswitch-inner"></span>
                                                    <span class="onoffswitch-switch"></span>
                                                </label>
                                            </div>
                                            <script>
                                                jQuery(function($){$("#status-toggle").on( 'input', function(){
                                                    if ( $(this).is(':checked')){
                                                        $('#status').val('ready');
                                                    } else {
                                                        $('#status').val('draft');
                                                    }
                                                })});
                                            </script>
                                        </td>
                                    </tr>
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
                                        <?php $args = array( 'id' => 'test_email', 'name' => 'test_email', 'selected' => $email->get_meta( 'test_email' ), 'role' => 'administrator' ); ?>
                                        <td><?php wp_dropdown_users( $args ); ?><script>jQuery(document).ready(function(){jQuery( '#test_email' ).select2()});</script></td>
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
                            <div id="major-publishing-actions">
                                <div id="delete-action">
                                    <a class="submitdelete deletion" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=gh_emails&action=trash&email=' . $email_id ), 'trash' ) ); ?>"><?php echo esc_html__( 'Move To Trash' ); ?></a>
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
                <?php do_action( 'wpgh_email_side_actions_after' ); ?>
                <?php do_action( 'wpgh_email_blocks_before' ); ?>
                <div id='blocks' class="postbox">
                    <h2 class="hndle"><?php echo __( 'Blocks', 'groundhogg' );?></h2>
                    <div class="inside">
                        <table>
                            <tbody>
                            <tr>
                                <td><div id='text_block' class="wpgh-element email-draggable"><div class="builder-icon"><img src="<?php echo WPGH_ASSETS_FOLDER . '/images/email-icons/text-block.png'; ?>"></div><p><?php _e( 'Text', 'groundhogg' ); ?></p></div></td>
                                <td><div id='spacer_block' class="wpgh-element email-draggable"><div class="builder-icon"><img src="<?php echo WPGH_ASSETS_FOLDER . '/images/email-icons/spacer-block.png'; ?>"></div><p><?php _e( 'Spacer', 'groundhogg' ); ?></p></div></td>
                            </tr>
                            <tr>
                                <td><div id='divider_block' class="wpgh-element email-draggable"><div class="builder-icon"><img src="<?php echo WPGH_ASSETS_FOLDER . '/images/email-icons/divider.png'; ?>"></div><p><?php _e( 'Divider', 'groundhogg' ); ?></p></div></td>
                                <td><div id='image_block' class="wpgh-element email-draggable"><div class="builder-icon"><img src="<?php echo WPGH_ASSETS_FOLDER . '/images/email-icons/image-block.png'; ?>"></div><p><?php _e( 'Image', 'groundhogg' ); ?></p></div></td>
                            </tr>
                            <tr>
                                <td><div id='button_block' class="wpgh-element email-draggable"><div class="builder-icon"><img src="<?php echo WPGH_ASSETS_FOLDER . '/images/email-icons/button.png'; ?>"></div><p><?php _e( 'Button', 'groundhogg' ); ?></p></div></td>
                                <td><div id='code_block' class="wpgh-element email-draggable"><div class="builder-icon"><img src="<?php echo WPGH_ASSETS_FOLDER . '/images/email-icons/html-block.png'; ?>"></div><p><?php _e( 'HTML', 'groundhogg' ); ?></p></div></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="hidden">
                        <div class="text_block_template"><?php wpgh_get_email_block( 'text_block' ); ?></div>
                        <div class="spacer_block_template"><?php wpgh_get_email_block( 'spacer_block' ); ?></div>
                        <div class="divider_block_template"><?php wpgh_get_email_block( 'divider_block' ); ?></div>
                        <div class="image_block_template"><?php wpgh_get_email_block( 'image_block' ); ?></div>
                        <div class="button_block_template"><?php wpgh_get_email_block('button_block' ); ?></div>
                        <div class="code_block_template"><?php wpgh_get_email_block( 'code_block' ); ?></div>
                    </div>
                    <div id="temp-html" class="hidden"></div>
                </div>
            </div>
            <!-- HI -->
            <!-- End elements area-->
            <div style="clear: both;"></div>

        </div>
    </div>
</form>
