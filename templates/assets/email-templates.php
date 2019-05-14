<?php
namespace Groundhogg\Templates;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Email Templates
 *
 * @package     Templates
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */

$email_templates = array();

/**
 * Return the theme logo URL.
 *
 * @return mixed
 */
function get_email_top_image_url()
{
    $image = wp_get_attachment_image_src( get_theme_mod( 'custom_logo' ), 'full' );

    if ( ! $image ){
        return 'https://via.placeholder.com/350x150';
    }

    return $image[0];
}

/* Call to action email */
ob_start();

include dirname( __FILE__ ) . '/emails/cta.php';

$email_templates['cta']['title'] = _x( "Call To Action", 'email_template_name', 'groundhogg' );
$email_templates['cta']['description'] = _x( "Use when you need contacts to take an action, and FAST!", 'email_template_description', 'groundhogg' );
$email_templates['cta']['content'] = ob_get_contents();

ob_clean();

/* Plain Text */

include dirname( __FILE__ ) . '/emails/plain.php';

$email_templates['plain']['title'] = _x( "Plain Text", 'email_template_name', 'groundhogg' );
$email_templates['plain']['description'] = _x( "Perfect for easy breezy communication with contacts.", 'email_template_description', 'groundhogg' );
$email_templates['plain']['content'] = ob_get_contents();

ob_clean();

/* Newsletter */
include dirname( __FILE__ ) . '/emails/newsletter.php';

$email_templates['newsletter']['title'] = _x( "Newsletter", 'email_template_name', 'groundhogg' );
$email_templates['newsletter']['description'] = _x( "Ideal for when you have a lot to share.", 'email_template_description', 'groundhogg' );
$email_templates['newsletter']['content'] = ob_get_contents();

ob_clean();

/* Hype Email */

include dirname( __FILE__ ) . '/emails/excitement.php';

$email_templates['hype']['title'] = _x( "Excitement Generator", 'email_template_name', 'groundhogg' );
$email_templates['hype']['description'] = _x( "Need people excited? This is what you need!", 'email_template_description', 'groundhogg' );
$email_templates['hype']['content'] = ob_get_contents();

ob_clean();

/* Welcome  Email*/

include dirname( __FILE__ ) . '/emails/welcome.php';

$email_templates['welcome']['title'] = _x( "Welcome Email", 'email_template_name', 'groundhogg' );
$email_templates['welcome']['description'] = _x( "New contact? Make the feel at home.", 'email_template_description', 'groundhogg' );
$email_templates['welcome']['content'] = ob_get_contents();

ob_clean();

/*  Confirmation Email */

include dirname( __FILE__ ) . '/emails/confirmation.php';

$email_templates['confirmation']['title'] = _x( "Confirmation Email", 'email_template_name', 'groundhogg' );
$email_templates['confirmation']['description'] = _x( "Send a confirmation request for receiving further communication.", 'email_template_description', 'groundhogg' );
$email_templates['confirmation']['content'] = ob_get_contents();

ob_clean();

/* Review Request */

include dirname( __FILE__ ) . '/emails/review.php';

$email_templates['review']['title'] = _x( "Review Request", 'email_template_name', 'groundhogg' );
$email_templates['review']['description'] = _x( "Want feedback? This is the easiest way to get it.", 'email_template_description', 'groundhogg' );
$email_templates['review']['content'] = ob_get_contents();

ob_clean();

/* value Email */

include dirname( __FILE__ ) . '/emails/appreciation.php';

$email_templates['value']['title'] = _x( "Appreciation Email", 'email_template_name', 'groundhogg' );
$email_templates['value']['description'] = _x( "Let your customers know you value them.", 'email_template_description', 'groundhogg' );
$email_templates['value']['content'] = ob_get_contents();

ob_end_clean();

$email_templates = apply_filters( 'groundhogg/templates/emails', $email_templates );