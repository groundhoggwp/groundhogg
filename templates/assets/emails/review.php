<?php
namespace Groundhogg\Templates;

/**
 * This is a Neswletter Email.
 *
 * Used to tell your customers to take some form of action.
 */
?>
<div class="row">
    <div class="content-wrapper image_block">
        <div class="content-inside inner-content text-content" style="padding: 5px;">
            <div class="image-wrapper" style="text-align: center;"><a href=""><img src="<?php echo esc_url( get_email_top_image_url() ); ?>" style="max-width: 100%;width: 50%;" title="" alt=""></a></div>
        </div>
    </div>
</div>
<div class="row" style="">
    <div class="content-wrapper text_block">
        <div class="" style="padding: 5px; font-family: Arial, sans-serif; font-size: 16px;">
            <p style="text-align: left;">Hey {first},</p>
            <p style="text-align: left;">Asking for a review is always iffy... You can get good mixed in with the bad.</p>
            <p style="text-align: left;">But 82% of consumers say that they check reviews before purchasing a service. So you best get on with it.</p>
            <p style="text-align: left;">The easiest way to collect reviews is to have your Google listing setup, and link it to there since that's where the majority of reviewers look. Alternatively you can link to the Facebook review site as well which ranks second.</p>
        </div>
    </div>
</div>
<div class="row" data-block="button" style="">
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
                                <td class="email-button" bgcolor="#EB7035" style="padding: 12px 18px 12px 18px; border-radius:3px" align="center"><b><a href="<?php echo esc_url( site_url() ); ?>" target="_blank" style="font-size: 18px; font-family: Helvetica, Arial, sans-serif; font-weight: bold; color: rgb(255, 255, 255); display: inline-block; text-decoration: none !important;">LEAVE A REVIEW?</a></b></td>
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
            <p style="text-align: left;"><span style="font-family: inherit; font-size: inherit;">If you want to increase engagement, you can also provide an incentive for a review, like a discount on their next purchase.</span><br></p>
            <p style="text-align: left;"><b>Best of Luck!</b></p>
            <p style="text-align: left;"><i>@ The {business_name} Team</i></p>
            <p style="text-align: left;"><i>P.S. Receiving reviews on your on site is not recommended as their trust factor is much less than reviews on neutral platforms such as Google, Trust Pilot and Facebook.</i></p>
        </div>
    </div>
</div>