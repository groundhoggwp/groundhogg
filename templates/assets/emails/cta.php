<?php
namespace Groundhogg\Templates;
use function Groundhogg\get_email_top_image_url;

/**
 * This is a CTA Email.
 *
 * Used to tell your customers to take some form of action.
 */
?>
<div class="row">
    <div class="content-wrapper image_block">
        <div class="content-inside inner-content text-content" style="padding: 5px;">
            <div class="image-wrapper" style="text-align: center"><a href=""><img src="<?php echo esc_url( get_email_top_image_url() ); ?>" style="max-width: 100%;width: 50%;" title="" alt=""></a></div>
        </div>
    </div>
</div>
<div class="row" data-block="text">
    <div class="content-wrapper text_block">
        <div class="content-inside inner-content text-content" style="padding: 5px; font-family: Arial, sans-serif; font-size: 16px;">
            <p>Hey {first},</p>
            <p>Calls to action are what really make the world go round.</p>
            <p>Emails like this should be short and sweet, and re-affirm a contact's desire to click the big red button below.</p>
        </div>
    </div>
</div>
<div class="row" data-block="button">
    <div class="content-wrapper button_block">
        <div class="content-inside inner-content text-content" style="padding: 5px;">
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tbody>
                <tr>
                    <td height="10"></td>
                </tr>
                <tr>
                    <td align="center">
                        <table border="0" cellspacing="0" cellpadding="0" style="margin-right: auto;margin-left: auto;">
                            <tbody>
                            <tr>
                                <td class="email-button" bgcolor="#EB7035" style="padding: 12px 18px 12px 18px; border-radius:3px" align="center"><b><a href="<?php echo esc_url( site_url() ); ?>" target="_blank" style="font-size: 20px; font-family: Helvetica, Arial, sans-serif; font-weight: bold; color: rgb(255, 255, 255); display: inline-block; text-decoration: none !important;">Click Me Now!</a></b></td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td height="10"></td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="row" data-block="text">
    <div class="content-wrapper text_block">
        <div class="content-inside inner-content text-content" style="padding: 5px;font-family:Arial, sans-serif;font-size:16px;">
            <p style="font-size: 16px; font-family: Arial, sans-serif;">If they read past the button, that means you need to introduce some form of limitation, like&nbsp;<i>only available for the next few minutes.</i></p>
            <p style="font-size: 16px; font-family: Arial, sans-serif;">And that's it. STOP WRITING</p>
            <p style="font-size: 16px; font-family: Arial, sans-serif;"><span style="font-weight: 600;">Best of Luck!</span></p>
            <p style="font-size: 16px; font-family: Arial, sans-serif;"><i>@ The {business_name} Team</i></p>
        </div>
    </div>
</div>
