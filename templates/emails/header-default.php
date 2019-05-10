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

$body = [
    "background-color"          => "#FFFFFF",
	'font-family'               => 'sans-serif',
	'-webkit-font-smoothing'    => 'antialiased',
	'font-size'                 => '14px',
	'line-height'               => '1.4',
	'margin'                    => '0',
	'padding'                   => '0',
	'-ms-text-size-adjust'      => '100%',
	'-webkit-text-size-adjust'  => '100%'
];

$body = apply_filters( 'groundhogg/email_template/body_css', $body );
$body = \Groundhogg\array_to_css( $body );

$wrapper = apply_filters( 'groundhogg/email_template/wrapper_css', [
	'border-collapse' => 'separate',
	'mso-table-lspace' => '0pt',
	'mso-table-rspace' => '0pt',
	'width' => '100%',
	'background-color' => '#FFFFFF'
] );

$wrapper = \Groundhogg\array_to_css( $wrapper );

$template_container = apply_filters( 'groundhogg/email_template/container_css', [
	'font-family' => 'sans-serif',
	'font-size' => '14px',
	'vertical-align' => 'top',
	'display' => 'block',
	'width' => '100%',
	'max-width' => '580px',
	'padding' => '0px',
] );

$template_container = \Groundhogg\array_to_css( $template_container );

$email_width = apply_filters( 'groundhogg/email_template/width', 580 );

$alignment = apply_filters( 'groundhogg/email_template/alignment', 'center' );

$template_content = apply_filters( 'groundhogg/email_template/content_css', [
    'box-sizing' => 'border-box',
    'display' => 'block',
    'Margin' => '0 auto',
    'width' => '100%',
    'max-width' => '580px',
   ' padding' => '5px',
] );

$template_content = \Groundhogg\array_to_css( $template_content );

$preheader = apply_filters( 'groundhogg/email_template/preheader_css', [
    'color' => 'transparent',
    'display' => 'none',
    'height' => '0',
    'max-height' => '0',
    'max-width' => '0',
    'opacity' => '0',
    'overflow' => 'hidden',
    'mso-hide' => 'all',
    'visibility' => 'hidden',
    'width' => '0',
] );

$preheader = \Groundhogg\array_to_css( $preheader );

$apple_link = apply_filters( 'groundhogg/email_template/apple_link_css', [
    'color' => '#999999',
    'font-size' => '13px',
    'text-align' => 'center',
]);

$apple_link = \Groundhogg\array_to_css( $apple_link );

$email_title = get_bloginfo( 'name', 'display' );

/* translators: Login screen title. 1: Login screen name, 2: Network or site name */
$email_title = sprintf( __( '%1$s &lsaquo; %2$s' ), apply_filters( 'groundhogg/email_template/title', 'Email' ), $email_title );
$email_title = apply_filters( 'groundhogg/email_template/title', $email_title, $title );

$is_showing_in_iframe = get_query_var( 'pagenow' ) === 'emails';
$email_width = $is_showing_in_iframe ? '100%' : $email_width;

?>
<!doctype html>
<html>

<!-- HEAD -->
<head>
    <meta name="viewport" content="width=device-width">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title><?php echo $email_title; ?></title>
    <base target="_parent">
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
                            <span class="preheader" style="<?php echo $preheader; ?>"><?php echo apply_filters( 'groundhogg/email_template/pre_header_text', '' ); ?></span>
                            <!-- /PREHEADER -->

                            <!-- BROWSER VIEW -->
                            <?php if ( apply_filters( 'groundhogg/email_template/show_browser_view', false ) ): ?>
                                <div class="header" style="text-align: center;margin-bottom: 25px;">
                                    <span class="apple-link" style="<?php echo $apple_link; ?>">
                                        <a href="<?php echo esc_url_raw( apply_filters( 'groundhogg/email_template/browser_view_link', site_url() ) ); ?>">
                                            <?php _e( apply_filters( 'groundhogg/email_template/browser_view_text', __( 'View In Browser...', 'groundhogg' ) ), 'groundhogg' ); ?>
                                        </a>
                                    </span>
                                </div>
                            <!-- /BROWSER VIEW -->
                            <?php endif; ?>
