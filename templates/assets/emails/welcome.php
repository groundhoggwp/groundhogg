<?php
namespace Groundhogg\Templates;

/**
 * This is a Neswletter Email.
 *
 * Used to tell your customers to take some form of action.
 */
?>
<div class="row" style="">
    <div class="content-wrapper text_block">
        <div class="" style="padding: 5px; font-family: Arial, sans-serif; font-size: 16px;">
            <h1 style="text-align: center; font-family: Arial, sans-serif;">Thanks For Signing Up!</h1>
            <h2 style="text-align: center; font-family: Arial, sans-serif; font-size: 17px;">We're glad to have you.</h2>
            <p style="text-align: left;">Hey {first},</p>
            <p style="text-align: left;">If a contact just signed up for the first time, it's generally good practice to welcome them with open arms.</p>
            <p style="text-align: left;">Introduce yourself and your company in a short paragraph and let them know that you are there to support them no matter what the situation.</p>
            <p style="text-align: left;">Then move on! Let's get to what they wanted in the first place. Most likely you're providing a download, service, or quote. All are fine, just point them the button below to get started.</p>
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
                                <td class="email-button" bgcolor="#EB7035" style="padding: 12px 18px 12px 18px; border-radius:3px" align="center"><b><a href="<?php echo esc_url(site_url()); ?>" target="_blank" style="font-size: 20px; font-family: Helvetica, Arial, sans-serif; font-weight: bold; color: rgb(255, 255, 255); display: inline-block; text-decoration: none !important;">DO THE THING I WANTED!</a></b></td>
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
<div class="row" style="">
    <div class="content-wrapper text_block">
        <div class="" style="padding: 5px; font-family: Arial, sans-serif; font-size: 16px;">
            <p style="text-align: left;">If they read past the button that's totally cool. Just give them some more options.</p>
            <p style="text-align: left;">Not everyone wants to go through your process so make sure you can cater to those people by provided them a phone number or another way of communicating directly with you.</p>
            <ul>
                <li>Phone: {business_phone}</li>
                <li>Email: {business_email}</li>
            </ul>
            <p>Remember, as long as you're helping people you're golden!</p>
            <p style="text-align: left;"><b>Best of Luck!</b></p>
            <p style="text-align: left;"><i>@ The {business_name} Team</i></p>
            <p style="text-align: left;"><i>P.S you should seed here about other products, services, or emails they should be expecting to generate curiosity.</i></p>
        </div>
    </div>
</div>