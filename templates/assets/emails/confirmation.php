<?php
namespace Groundhogg\Templates;

/**
 * This is a Confirmation Email.
 */
?>
<div class="row">
    <div class="content-wrapper image_block">
        <div class="content-inside inner-content text-content" style="padding: 5px;">
            <div class="image-wrapper" style="text-align: center"><a href=""><img src="<?php echo esc_url( get_email_top_image_url() ); ?>" style="max-width: 100%;width: 50%;" title="" alt=""></a></div>
        </div>
    </div>
</div>
<div class="row" style="">
    <div class="content-wrapper text_block">
        <div class="" style="padding: 5px; font-family: Arial, sans-serif; font-size: 16px;">
            <p style="text-align: left;">Hey {first},</p>
            <p style="text-align: left;">If you have a new contact on your hands, you can increase their trust factor, and cover your butt legally by requesting that they confirm their email before sending more emails!</p>
            <p style="text-align: left;">To include a confirmation link in your email, just use the following replacement code.</p>
            <p style="text-align: left;">{confirmation_link}</p>
            <p style="text-align: left;">That's it, once they click the link your butt is covered legally, and the contact can feel good about receiving emails from you.</p>
            <p style="text-align: left;"><b>Best of luck!</b></p>
            <p style="text-align: left;"><i>@ The {business_name} Team</i></p>
        </div>
    </div>
</div>