<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-08-03
 * Time: 3:43 PM
 */

/**
 * Get list of email building blocks
 *
 * @return array list of html building blocks.
 */
function wpfn_get_email_html_blocks()
{
    $blocks = array();

    $blocks['text_block'] = 'dashicons-text';
    $blocks['spacer_block'] = 'dashicons-image-flip-vertical';
    $blocks['seperator_block'] = 'dashicons-minus';
    $blocks['image_block'] = 'dashicons-format-image';
    $blocks['button_block'] = 'dashicons-id-alt';
    $blocks['social_block'] = 'dashicons-networking';
    $blocks['video_block'] = 'dashicons-video-alt3';
    $blocks['html_block'] = 'dashicons-editor-code';

    return apply_filters( 'wpfn_email_blocks', $blocks );
}

function wpfn_email_text_block()
{
    ?>
    <p><?php echo esc_html__( 'Customize this section by editing the text, adding your own copy, using the options above to bold, italicize, or create links and bullets, or use the options in the "Design" panel on the left to change the font styles of your email.', 'wp-funnels' );?></p>
    <?php
}

add_action( 'wpfn_email_block_html_text_block', 'wpfn_email_text_block' );

function wpfn_email_spacer_block()
{
    ?>
    <div class="spacer" style="margin: 5px 0 5px 0; height: 15px;"></div>
    <?php
}

add_action( 'wpfn_email_block_html_spacer_block', 'wpfn_email_spacer_block' );

function wpfn_email_divider_block()
{
    ?>
    <div style="margin: 5px 0 5px 0"><hr style="width:80%;"/></div>
    <?php
}

add_action( 'wpfn_email_block_html_divider_block', 'wpfn_email_divider_block' );

function wpfn_email_image_block()
{
    $src = 'https://via.placeholder.com/350x150';
    ?>
    <div class="image-wrapper" style="text-align: center"><a href=""><img src="<?php echo $src;?>" style="max-width: 100%;width: 50%;" title="" alt=""></a></div>
    <?php
}

add_action( 'wpfn_email_block_html_image_block', 'wpfn_email_image_block' );

function wpfn_email_button_block()
{
    ?>
    <!--Button-->
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td>
                <table border="0" cellspacing="0" cellpadding="0" style="margin-right: auto;margin-left: auto;">
                    <tr>
                        <td class="email-button" bgcolor="#EB7035" style="padding: 12px 18px 12px 18px; border-radius:3px" align="center"><a contenteditable="true" href="http://litmus.com" target="_blank" style="font-size: 16px; font-family: Helvetica, Arial, sans-serif; font-weight: normal; color: #ffffff; text-decoration: none; display: inline-block;">I am a button &rarr;</a></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <?php
}

add_action( 'wpfn_email_block_html_button_block', 'wpfn_email_button_block' );

function wpfn_email_code_block()
{
    ?>
    <div><p>This is some custom HTML which you can edit on the right. You may enter any valid HTML tags, but they may get filtered out as some email browsers to not support certain HTML.</p></div>
    <?php
}

add_action( 'wpfn_email_block_html_code_block', 'wpfn_email_code_block' );

function wpfn_get_email_block( $type )
{
    ?>
    <div class="row">
        <wpfn-toolbar class="action-icons"><div style="margin: 5px 3px 5px 3px;"><span class="dashicons dashicons-admin-page"></span> | <span class="dashicons dashicons-move handle"></span> | <span class="dashicons dashicons-trash"></span></div></wpfn-toolbar>
        <div class="content-wrapper <?php echo $type; ?>">
            <div class="content-inside inner-content text-content" style="padding: 5px;">
                <?php do_action( 'wpfn_email_block_html_' . $type ); ?>
            </div>
        </div>
    </div>

    <?php
}

function wpfn_get_email_block_ajax()
{

    $block_type = $_POST['block_type'];

    ob_start();

    wpfn_get_email_block( $block_type );

    $content = ob_get_contents();

    ob_end_clean();

    wp_die( $content );
}

add_action( 'wp_ajax_get_email_block_html', 'wpfn_get_email_block_ajax' );