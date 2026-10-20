<?php
/**
 * The mobile app shell. Deliberately not the theme and not wp-admin: just the document the app mounts into
 * and the manifest it boots from.
 *
 * @var array $manifest the per-user manifest from Groundhogg\Mobile\Manifest::build()
 */

namespace Groundhogg\Mobile;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ver = App::asset_version();

// The manifest sits in an inert JSON block, but still can't be allowed to close the tag or open a comment
$manifest_json = wp_json_encode( $manifest, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#14161a" media="(prefers-color-scheme: dark)">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="<?php echo esc_attr( $manifest['app']['name'] ); ?>">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="manifest" href="<?php echo esc_url( App::url() . 'manifest.webmanifest' ); ?>">
	<?php if ( App::touch_icon_url() ) : ?>
    <link rel="apple-touch-icon" href="<?php echo esc_url( App::touch_icon_url() ); ?>">
	<?php endif; ?>
    <title><?php echo esc_html( $manifest['app']['name'] ); ?></title>
    <link rel="stylesheet" href="<?php echo esc_url( App::core_style_url() . '?ver=' . $ver ); ?>">
</head>
<body class="ghm">
<div id="ghm-root"></div>
<noscript><p class="ghm-noscript"><?php esc_html_e( 'This app needs JavaScript.', 'groundhogg' ); ?></p></noscript>
<script type="application/json" id="ghm-manifest"><?php echo $manifest_json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON with HEX flags ?></script>
<script src="<?php echo esc_url( App::core_script_url() . '?ver=' . $ver ); ?>" defer></script>
</body>
</html>
