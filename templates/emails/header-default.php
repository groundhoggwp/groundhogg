<?php
/**
 * Email Header
 *
 * @package     Templates/Emails
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$body = apply_filters( 'wpgh_email_body_css', "
	background-color: #FFFFFF; 
	font-family: sans-serif; 
	-webkit-font-smoothing: antialiased; 
	font-size: 14px; 
	line-height: 1.4; 
	margin: 0; 
	padding: 0; 
	-ms-text-size-adjust: 100%; 
	-webkit-text-size-adjust: 100%;" );

$wrapper = apply_filters( 'wpgh_email_wrapper_css', "
	border-collapse: separate; 
	mso-table-lspace: 0pt; 
	mso-table-rspace: 0pt; 
	width: 100%; 
	background-color: #FFFFFF;" );

$template_container = apply_filters( 'wpgh_email_container_css', "
	font-family: sans-serif; 
	font-size: 14px; 
	vertical-align: top; 
	display: block; 
	width: 580px;
	max-width: 580px; 
	padding: 0px; " );

$email_width = apply_filters( 'wpgh_email_width', 580 );
$alignment = apply_filters( 'wpgh_email_alignment', 'center' );

$template_content = apply_filters( 'wpgh_email_content_css', "
    box-sizing: border-box; 
    display: block; 
    Margin: 0 auto;
    width:580px;
    max-width: 580px; 
    padding: 5px;" );

$preheader = apply_filters( 'wpgh_email_preheader_css', "
    color: transparent; 
    display: none; 
    height: 0; 
    max-height: 0; 
    max-width: 0; 
    opacity: 0; 
    overflow: hidden; 
    mso-hide: all; 
    visibility: hidden; 
    width: 0;" );

$apple_link = apply_filters( 'wpgh_email_apple_link_css', "
    color: #999999; 
    font-size: 13px; 
    text-align: center;");

?>
<!doctype html>
<html>

<!-- HEAD -->
<head>
    <meta name="viewport" content="width=device-width">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title><?php echo get_bloginfo( 'name' );?></title>
</head>
<!-- /HEAD -->

<!-- BODY -->
<body class="email" style="<?php echo $body; ?>">
<table border="0" cellpadding="0" cellspacing="0" class="body" style="<?php echo $wrapper; ?>">
    <tr>
        <td class="container" style="<?php echo $template_container; ?>" width="<?php echo $email_width; ?>" align="<?php echo $alignment; ?>">
            <table border="0" cellpadding="0" cellspacing="0" class="body" width="<?php echo $email_width; ?>">
                <tr>
                    <td width="<?php echo $email_width; ?>" align="center">
                        <div class="content" style="<?php echo $template_content; ?>">

                            <!-- PREHEADER -->
                            <span class="preheader" style="<?php echo $preheader; ?>"><?php echo apply_filters( 'wpgh_email_pre_header_text', '' ); ?></span>
                            <!-- /PREHEADER -->

                            <!-- BROWSER VIEW -->
                            <?php if ( apply_filters( 'wpgh_email_browser_view', false ) ): ?>
                                <div class="header" style="text-align: center;margin-bottom: 25px;">
                                    <span class="apple-link" style="<?php echo $apple_link; ?>">
                                        <a href="<?php echo esc_url_raw( apply_filters( 'wpgh_email_browser_link', site_url() ) ); ?>">
                                            <?php _e( apply_filters( 'gh_view_in_browser_text', __( 'View In Browser...', 'groundhogg' ) ), 'groundhogg' ); ?>
                                        </a>
                                    </span>
                                </div>
                            <!-- /BROWSER VIEW -->
                            <?php endif; ?>
