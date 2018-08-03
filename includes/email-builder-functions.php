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
    <p contenteditable="true"><?php echo esc_html__( 'Customize this section by editing the text, adding your own copy, using the options above to bold, italicize, or create links and bullets, or use the options in the "Design" panel on the left to change the font styles of your email.', 'wp-funnels' );?></p>
    <?php
}

add_action( 'wpfn_email_block_html_text_block', 'wpfn_email_text_block' );

function wpfn_email_spacer_block()
{
    ?>
    <div style="margin: 5px 0 5px 0"></div>
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
    <div class="image-wrapper" style="text-align: center"><img src="<?php echo $src;?>" style="max-width: 100%;width: 50%;"></div>
    <?php
}

add_action( 'wpfn_email_block_html_image_block', 'wpfn_email_image_block' );

function wpfn_email_button_block()
{
    ?>
    <!--Button-->
    <table align="center" cellspacing="0" cellpadding="0" width="100%">
        <tr>
            <td align="center" style="padding: 10px;">
                <table border="0" class="mobile-button" cellspacing="0" cellpadding="0">
                    <tr>
                        <td align="center" bgcolor="#2b3138" style="background-color: #2b3138; margin: auto; max-width: 600px; -webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; padding: 15px 20px; " width="100%">
                            <!--[if mso]>&nbsp;<![endif]-->
                            <a class="prevent-default" href="#" target="_blank" style="16px; font-family: Helvetica, Arial, sans-serif; color: #ffffff; font-weight:normal; text-align:center; background-color: #2b3138; text-decoration: none; border: none; -webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; display: inline-block;">
                                <span contenteditable="true" style="font-size: 16px; font-family: Helvetica, Arial, sans-serif; color: #ffffff; font-weight:normal; line-height:1.5em; text-align:center;">Click Here</span>
                            </a>
                            <!--[if mso]>&nbsp;<![endif]-->
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <?php
}

add_action( 'wpfn_email_block_html_button_block', 'wpfn_email_button_block' );


function wpfn_get_email_block( $type )
{
    ?>
    <div class="email-row">
        <div class="row-content">
            <div class="content-wrapper <?php echo $type; ?>">
                <div class="action-icons"><div style="margin: 5px 3px 5px 3px;"><span class="dashicons dashicons-admin-page"></span> | <span class="dashicons dashicons-move handle"></span> | <span class="dashicons dashicons-trash"></span></div></div>
                <div class="content-inside">
                    <?php do_action( 'wpfn_email_block_html_' . $type ); ?>
                </div>
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