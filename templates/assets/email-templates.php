<?php
/**
 * Email Templates
 *
 * @package     Templates
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$email_templates = array();

/* Call to action email */
ob_start();

?>
    <div class="row"> <div class="content-wrapper image_block"> <div class="content-inside inner-content text-content" style="padding: 5px;"> <div class="image-wrapper" style="text-align: center"><a href=""><img src="https://via.placeholder.com/350x150" style="max-width: 100%;width: 50%;" title="" alt=""></a></div> </div> </div> </div> <div class="row" style=""> <div class="content-wrapper text_block"> <div class="" style="padding: 5px; font-family: Arial, sans-serif; font-size: 16px;" > <p style="text-align: left;">Hey {first},</p><p style="text-align: left;">Calls to action are what really make the world go round.</p><p style="text-align: left;">Emails like this should be short and sweet, and re-affirm a contact's desire to click the big red button below.</p> </div> </div> </div> <div class="row"> <div class="content-wrapper button_block"> <div class="content-inside inner-content text-content" style="padding: 5px;">  <table width="100%" border="0" cellspacing="0" cellpadding="0"> <tbody><tr> <td> <table border="0" cellspacing="0" cellpadding="0" style="margin-right: auto;margin-left: auto;"> <tbody><tr> <td class="email-button" bgcolor="#EB7035" style="padding: 12px 18px 12px 18px; border-radius:3px" align="center"><b><a href="https://www.groundhogg.io" target="_blank" style="font-size: 20px; font-family: Helvetica, Arial, sans-serif; font-weight: bold; color: rgb(255, 255, 255); text-decoration: none; display: inline-block;">CLICK ME NOW</a></b></td> </tr> </tbody></table> </td> </tr> </tbody></table> </div> </div> </div><div class="row" style=""> <div class="content-wrapper text_block"> <div class="" style="padding: 5px; font-family: Arial, sans-serif; font-size: 16px;" > <p style="text-align: left;">If they read past the button, that means you need to introduce some form of limitation, like <i>only available for the next few minutes.</i></p><p style="text-align: left;">And that's it. STOP WRITING</p><p style="text-align: left;"><b>Best of Luck!</b></p><p style="text-align: left;"><i>@ The {business_name} Team</i></p><p style="text-align: left;"><i>P.S Add your logo at the top so they know it's you!</i></p> </div> </div> </div>
<?php

$email_templates['cta']['title'] = _x( "Call To Action", 'email_template_name', 'groundhogg' );
$email_templates['cta']['description'] = _x( "Use when you need contacts to take an action, and FAST!", 'email_template_description', 'groundhogg' );
$email_templates['cta']['content'] = ob_get_contents();

ob_clean();

/* Plain Text */

?>
    <div class="row" style=""> <div class="content-wrapper text_block"> <div class="" style="padding: 5px; font-family: Arial, sans-serif; font-size: 16px;" > <p style="text-align: left;">Hey {first},</p><p style="text-align: left;">In my opinion, <b>plain text emails</b> are always the best performing emails.</p><p style="text-align: left;">It makes your readers feel as if you actually took the time to write out a full email to them vs. pretty HTML emails that are perceived as "what's going on."</p><p style="text-align: left;">Generally, a plain text email should not exceed a few hundred words, and reading time should be under a minute.</p><p style="text-align: left;">If you can accomplish all that, you should be good to write an awesome plain text email!</p><p style="text-align: left;"><b>Best of Luck!</b></p><p style="text-align: left;"><i>@ The {business_name} Team</i></p><p style="text-align: left;"><i>P.S You can get away with adding a logo.</i></p> </div> </div> </div>
<?php

$email_templates['plain']['title'] = _x( "Plain Text", 'email_template_name', 'groundhogg' );
$email_templates['plain']['description'] = _x( "Perfect for easy breezy communication with contacts.", 'email_template_description', 'groundhogg' );
$email_templates['plain']['content'] = ob_get_contents();

ob_clean();

/* Newsletter */

?>
    <div class="row"> <div class="content-wrapper image_block"> <div class="content-inside inner-content text-content" style="padding: 5px;"> <div class="image-wrapper" style="text-align: center"><a href=""><img src="https://via.placeholder.com/350x150" style="max-width: 100%;width: 50%;" title="" alt=""></a></div> </div> </div> </div> <div class="row" style=""> <div class="content-wrapper text_block"> <div class="" style="padding: 5px; font-family: Arial, sans-serif; font-size: 16px;" > <p style="text-align: left;">Hey {first},</p><p style="text-align: left;">Sometimes you just need to send a news letter. So let's make sure it's relevant! Follow the steps below to make your readers go WOW!</p> </div> </div> </div><div class="row" style=""> <div class="content-wrapper text_block"> <div class="" style="padding: 5px; font-family: Arial, sans-serif; font-size: 16px;" > <h1 style="text-align: left;">Don't waste good space...</h1><p style="text-align: left;">Headlines that make people curious should be used in tandem with short excerpts of content. Do <b>NOT</b> put your whole article in the email as it will take too long to read.&nbsp;<a href="#">What happened?</a></p> </div> </div> </div><div class="row" style=""> <div class="content-wrapper text_block"> <div class="" style="padding: 5px; font-family: Arial, sans-serif; font-size: 16px;" > <h1 style="text-align: left;">Did you know?</h1><p style="text-align: left;">That most people are skimmers? That about <b>90%</b> of the population ONLY read what's bold because that's <b>whats usually relevant</b>. So bold all curiosity building phrases. <a href="#">I didn't know!</a></p> </div> </div> </div><div class="row" style=""> <div class="content-wrapper text_block"> <div class="" style="padding: 5px; font-family: Arial, sans-serif; font-size: 16px;" > <h1 style="text-align: left;">Don't write small!</h1><p style="text-align: left;">Most email consumption is now on the phone. So make sure your text is nice and big so that readers don't have to squint to consume the content. <a href="#">Who knew?</a></p> </div> </div> </div><div class="row" style=""> <div class="content-wrapper text_block"> <div class="" style="padding: 5px; font-family: Arial, sans-serif; font-size: 16px;" > <h1 style="text-align: left;">I'm sick of reading more...</h1><p style="text-align: left;">The initial thought to link to an article through your newsletter is to add the <a href="#">read more link?</a> DON'T! <b>"It's a trap."</b>&nbsp;You should always link to your article with a statement or a question such as, "That's a great Idea," or "Want to know more?." <a href="#">Pretty cool right?</a></p> </div> </div> </div><div class="row" style=""> <div class="content-wrapper text_block"> <div class="" style="padding: 5px; font-family: Arial, sans-serif; font-size: 16px;" ><p>Anyway... That's about it. I'd limit it to 5 relevant things or less if possible.</p><p>Say your parting words and bugger off already!</p><p><b>Best of luck!</b></p><p><i style="">@ The {business_name} Team</i></p></div> </div> </div>
<?php

$email_templates['newsletter']['title'] = _x( "Newsletter", 'email_template_name', 'groundhogg' );
$email_templates['newsletter']['description'] = _x( "Ideal for when you have a lot to share.", 'email_template_description', 'groundhogg' );
$email_templates['newsletter']['content'] = ob_get_contents();

ob_clean();

/* Hype Email */

?>
    <div class="row" style=""> <div class="content-wrapper text_block"> <div class="" style="padding: 5px; font-family: Arial, sans-serif; font-size: 16px;" > <h1 style="text-align: center; font-family: Arial, sans-serif;">SOMETHING BIG IS COMING!</h1><h2 style="text-align: center; font-family: Arial, sans-serif;">Stay tuned or you'll miss it.</h2><p style="text-align: left;">Hey {first},</p><p style="text-align: left;">If you need to create hype for your next product or event, then this is the email template you need.</p><p style="text-align: left;">All you really need to do is have a super easy to read and engaging headline like the one above and a nice big red button.</p><p style="text-align: left;">Keep it short, you don't need to explain all the details here. That's what your website is for. So hurry up and get them to click the red button.</p> </div> </div> </div><div class="row"> <div class="content-wrapper button_block"> <div class="content-inside inner-content text-content" style="padding: 5px;">  <table width="100%" border="0" cellspacing="0" cellpadding="0"> <tbody><tr> <td> <table border="0" cellspacing="0" cellpadding="0" style="margin-right: auto;margin-left: auto;"> <tbody><tr> <td class="email-button" bgcolor="#EB7035" style="padding: 12px 18px 12px 18px; border-radius:3px" align="center"><b><a href="https://www.groundhogg.io" target="_blank" style="font-size: 20px; font-family: Arial, sans-serif; font-weight: bold; color: rgb(255, 255, 255); text-decoration: none; display: inline-block;">WHAT'S THE BIG THING?</a></b></td> </tr> </tbody></table> </td> </tr> </tbody></table> </div> </div> </div> <div class="row" style=""> <div class="content-wrapper text_block"> <div class="" style="padding: 5px; font-family: Arial, sans-serif; font-size: 16px;" > <p style="text-align: left;">If they read past the button, then you need to get them back to the button. When something is being hyped, there usually a chance that people will be left out.</p><p style="text-align: left;">Use FOMO (fear of missing out) to drive them back to the button.</p><p style="text-align: left;">And that's it. STOP WRITING</p><p style="text-align: left;"><b>Best of Luck!</b></p><p style="text-align: left;"><i>@ The {business_name} Team</i></p><p style="text-align: left;"><i>P.S use this area to generate more curiosity&nbsp;about your big thing. tell them to click the link above or be left out.</i></p> </div> </div> </div>
<?php

$email_templates['hype']['title'] = _x( "Excitement Generator", 'email_template_name', 'groundhogg' );
$email_templates['hype']['description'] = _x( "Need people excited? This is what you need!", 'email_template_description', 'groundhogg' );
$email_templates['hype']['content'] = ob_get_contents();

ob_clean();

/* Welcome  Email*/

?>
    <div class="row" style=""> <div class="content-wrapper text_block"> <div class="" style="padding: 5px; font-family: Arial, sans-serif; font-size: 16px;" > <h1 style="text-align: center; font-family: Arial, sans-serif;">Thanks For Signing Up!</h1><h2 style="text-align: center; font-family: Arial, sans-serif; font-size: 17px;">We're glad to have you.</h2><p style="text-align: left;">Hey {first},</p><p style="text-align: left;">If a contact just signed up for the first time, it's generally good practice to welcome them with open arms.</p><p style="text-align: left;">Introduce yourself and your company in a short paragraph and let them know that you are there to support them no matter what the situation.</p><p style="text-align: left;">Then move on! Let's get to what they wanted in the first place. Most likely you're providing a download, service, or quote. All are fine, just point them the button below to get started.</p> </div> </div> </div><div class="row"> <div class="content-wrapper button_block"> <div class="content-inside inner-content text-content" style="padding: 5px;">  <table width="100%" border="0" cellspacing="0" cellpadding="0"> <tbody><tr> <td> <table border="0" cellspacing="0" cellpadding="0" style="margin-right: auto;margin-left: auto;"> <tbody><tr> <td class="email-button" bgcolor="#EB7035" style="padding: 12px 18px 12px 18px; border-radius:3px" align="center"><b><a href="https://www.groundhogg.io" target="_blank" style="font-size: 20px; font-family: Helvetica, Arial, sans-serif; font-weight: bold; color: rgb(255, 255, 255); text-decoration: none; display: inline-block;">DO THE THING I WANTED!</a></b></td> </tr> </tbody></table> </td> </tr> </tbody></table> </div> </div> </div> <div class="row" style=""> <div class="content-wrapper text_block"> <div class="" style="padding: 5px; font-family: Arial, sans-serif; font-size: 16px;" > <p style="text-align: left;">If they read past the button that's totally cool. Just give them some more options.</p><p style="text-align: left;">Not everyone wants to go through your process so make sure you can cater to those people by provided them a phone number or another way of communicating directly with you.</p><ul><li>Phone: 555-555-5555</li><li>Email: my@email.com</li></ul><p>Remember, as long as you're helping people you're golden!</p><p style="text-align: left;"><b>Best of Luck!</b></p><p style="text-align: left;"><i>@ The {business_name} Team</i></p><p style="text-align: left;"><i>P.S you should seed here about other products, services, or emails they should be expecting to generate curiosity.</i></p> </div> </div> </div>
<?php

$email_templates['welcome']['title'] = _x( "Welcome Email", 'email_template_name', 'groundhogg' );
$email_templates['welcome']['description'] = _x( "New contact? Make the feel at home.", 'email_template_description', 'groundhogg' );
$email_templates['welcome']['content'] = ob_get_contents();

ob_clean();

/*  Confirmation Email */

?>
    <div class="row"> <div class="content-wrapper image_block"> <div class="content-inside inner-content text-content" style="padding: 5px;"> <div class="image-wrapper" style="text-align: center"><a href=""><img src="https://via.placeholder.com/350x150" style="max-width: 100%;width: 50%;" title="" alt=""></a></div> </div> </div> </div> <div class="row" style=""> <div class="content-wrapper text_block"> <div class="" style="padding: 5px; font-family: Arial, sans-serif; font-size: 16px;" > <p style="text-align: left;">Hey {first},</p><p style="text-align: left;">If you have a new contact on your hands, you can increase their trust factor, and cover your butt legally by requesting that they confirm their email before sending more emails!</p><p style="text-align: left;">To include a confirmation link in your email, just use the following replacement code.</p><p style="text-align: left;">{confirmation_link}</p><p style="text-align: left;">That's it, once they click the link your butt is covered legally, and the contact can feel good about receiving emails from you.</p><p style="text-align: left;"><b>Best of luck!</b></p><p style="text-align: left;"><i>@ The {business_name} Team</i></p> </div> </div> </div>
<?php

$email_templates['confirmation']['title'] = _x( "Confirmation Email", 'email_template_name', 'groundhogg' );
$email_templates['confirmation']['description'] = _x( "Send a confirmation request for receiving further communication.", 'email_template_description', 'groundhogg' );
$email_templates['confirmation']['content'] = ob_get_contents();

ob_clean();

/* Review Request */

?>
    <div class="row"> <div class="content-wrapper image_block"> <div class="content-inside inner-content text-content" style="padding: 5px;"> <div class="image-wrapper" style="text-align: left;"><a href=""><img src="https://via.placeholder.com/350x150" style="max-width: 100%;width: 50%;" title="" alt=""></a></div> </div> </div> </div> <div class="row" style=""> <div class="content-wrapper text_block"> <div class="" style="padding: 5px; font-family: Arial, sans-serif; font-size: 16px;" > <p style="text-align: left;">Hey {first},</p><p style="text-align: left;">Asking for a review is always iffy... You can get good mixed in with the bad.</p><p style="text-align: left;">But 82% of consumers say that they check reviews before purchasing a service. So you best get on with it.</p><p style="text-align: left;">The easiest way to collect reviews is to have your Google listing setup, and link it to there since that's where the majority of reviewers look. Alternatively you can link to the Facebook review site as well which ranks second.</p> </div> </div> </div><div class="row"> <div class="content-wrapper button_block"> <div class="content-inside inner-content text-content" style="padding: 5px;">  <table width="100%" border="0" cellspacing="0" cellpadding="0"> <tbody><tr> <td> <table border="0" cellspacing="0" cellpadding="0" style="margin-right: auto;margin-left: auto;"> <tbody><tr> <td class="email-button" bgcolor="#EB7035" style="padding: 12px 18px 12px 18px; border-radius:3px" align="center"><b><a href="https://www.groundhogg.io" target="_blank" style="font-size: 16px; font-family: Helvetica, Arial, sans-serif; font-weight: bold; color: #ffffff; text-decoration: none; display: inline-block;">LEAVE A REVIEW</a></b></td> </tr> </tbody></table> </td> </tr> </tbody></table> </div> </div> </div><div class="row" style=""> <div class="content-wrapper text_block"> <div class="" style="padding: 5px; font-family: Arial, sans-serif; font-size: 16px;" > <p style="text-align: left;"><span style="font-family: inherit; font-size: inherit;">If you want to increase engagement, you can also provide an incentive for a review, like a discount on their next purchase.</span><br></p><p style="text-align: left;"><b>Best of Luck!</b></p><p style="text-align: left;"><i>@ The {business_name} Team</i></p><p style="text-align: left;"><i>P.S. Receiving reviews on your on site is not recommended as their trust factor is much less than neutral platforms such as Google and facebook.</i></p> </div> </div> </div>
<?php

$email_templates['review']['title'] = _x( "Review Request", 'email_template_name', 'groundhogg' );
$email_templates['review']['description'] = _x( "Want feedback? This is the easiest way to get it.", 'email_template_description', 'groundhogg' );
$email_templates['review']['content'] = ob_get_contents();

ob_clean();

/* value Email */

?>
    <div class="row" style=""> <div class="content-wrapper text_block"> <div class="" style="padding: 5px; font-family: Arial, sans-serif; font-size: 16px;" > <h2 style="text-align: center; font-size: 21px; font-family: Arial, sans-serif;">We just wanted you to know...</h2><h1 style="text-align: center; font-size: 30px; font-family: Arial, sans-serif;">You're Awesome.</h1><p style="text-align: left;">Hey {first},</p><p style="text-align: left;">Sometimes you just need to send a reminder to your people that you're not just here to take their money.</p><p style="text-align: left;">You need to remind them that they are the reason you exist, and that their existence is helping thousands more achieve their goals.</p><p style="text-align: left;">A great way to make your list feel good about being your customer is to tell them that their patronage has been responsible for...</p><h2 style="text-align: left; font-size: 21px; font-family: Arial, sans-serif;">Amazing growth!</h2><p>Their contribution has allowed you to help thousands of people.</p><h2 style="font-size: 21px; font-family: Arial, sans-serif;">Awesome turnout!</h2><p>If you had an event recently you can tell them about how you affect your turnout.</p><h2 style="font-family: Arial, sans-serif; font-size: 21px;">Awesome Feedback!</h2><p>If they've recently supplied some feedback, then you can let them know that you are super grateful for it.</p><p>Value emails shouldn't be about selling. Just about making the customer feel good about themselves and your business.</p><p><b>Best of luck!</b></p><p><i>@ The {business_name} Team</i></p> </div> </div> </div>
<?php

$email_templates['value']['title'] = _x( "Appreciation Email", 'email_template_name', 'groundhogg' );
$email_templates['value']['description'] = _x( "Let your customers know you value them.", 'email_template_description', 'groundhogg' );
$email_templates['value']['content'] = ob_get_contents();

ob_end_clean();

$email_templates = apply_filters( 'wpgh_email_templates', $email_templates );
$email_templates = apply_filters( 'groundhogg/templates/emails', $email_templates );