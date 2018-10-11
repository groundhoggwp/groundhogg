<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-08-03
 * Time: 3:43 PM
 */

function wpgh_email_text_block()
{
    ?>
    <p><?php echo esc_html__( 'Customize this section by editing the text, adding your own copy, using the options above to bold, italicize, or create superlinks and bullets, or use the options in the "Design" panel on the left to change the font styles of your email.', 'groundhogg' );?></p>
    <?php
}

add_action( 'wpgh_email_block_html_text_block', 'wpgh_email_text_block' );

function wpgh_email_spacer_block()
{
    ?>
    <div class="spacer" style="margin: 5px 0 5px 0; height: 15px;"></div>
    <?php
}

add_action( 'wpgh_email_block_html_spacer_block', 'wpgh_email_spacer_block' );

function wpgh_email_divider_block()
{
    ?>
    <div style="margin: 5px 0 5px 0"><hr style="width:80%;"/></div>
    <?php
}

add_action( 'wpgh_email_block_html_divider_block', 'wpgh_email_divider_block' );

function wpgh_email_image_block()
{
    $src = 'https://via.placeholder.com/350x150';
    ?>
    <div class="image-wrapper" style="text-align: center"><a href=""><img src="<?php echo $src;?>" style="max-width: 100%;width: 50%;" title="" alt=""></a></div>
    <?php
}

add_action( 'wpgh_email_block_html_image_block', 'wpgh_email_image_block' );

function wpgh_email_button_block()
{
    ?>
    <!--Button-->
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td>
                <table border="0" cellspacing="0" cellpadding="0" style="margin-right: auto;margin-left: auto;">
                    <tr>
                        <td class="email-button" bgcolor="#EB7035" style="padding: 12px 18px 12px 18px; border-radius:3px" align="center"><b><a href="<?php echo site_url(); ?>" target="_blank" style="font-size: 16px; font-family: Helvetica, Arial, sans-serif; font-weight: bold; color: #ffffff; text-decoration: none; display: inline-block;"><?php _e('I am a button &rarr;'); ?></a></b></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <?php
}

add_action( 'wpgh_email_block_html_button_block', 'wpgh_email_button_block' );

function wpgh_email_code_block()
{
    ?>
    <div><p><?php _e('This is some custom HTML which you can edit on the right. You may enter any valid HTML tags, but they may get filtered out as some email browsers to not support certain HTML.', 'groundhogg'); ?></p></div>
    <?php
}

add_action( 'wpgh_email_block_html_code_block', 'wpgh_email_code_block' );

function wpgh_get_email_block( $type )
{
    ?>
    <div class="row">
        <div class="content-wrapper <?php echo $type; ?>">
            <div class="content-inside inner-content text-content" style="padding: 5px;">
                <?php do_action( 'wpgh_email_block_html_' . $type ); ?>
            </div>
        </div>
    </div>
    <?php
}