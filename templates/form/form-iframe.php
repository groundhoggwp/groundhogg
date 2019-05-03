<?php

/**
 * Responsive Form Iframe Template
 *
 * @package     Templates
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 1.0.20
 */

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php do_action( 'wpgh_form_iframe_title' ); ?></title>
    <?php do_action( 'wp_head' ); ?>
    <script>
        window.addEventListener('message', function (event) {
            if ( typeof event.data.action !== "undefined" && event.data.action === "getFrameSize") {
                var body = document.body, html = document.documentElement;
                var height = Math.max(body.scrollHeight, body.offsetHeight,
                    html.clientHeight, html.scrollHeight, html.offsetHeight);
                var width = '100%';
                event.source.postMessage({ height: height, width: width, id:event.data.id }, "*");
            }
        });
    </script>
    <style>
        html,body{background-color: transparent !important;margin: 0!important}
    </style>
</head>
<body>
<div class="formPadding" style="padding: 20px">
    <?php do_action( 'wpgh_form_iframe_content' ); ?>
</div>
</body>
<footer>
<?php //do_action( 'wp_footer' ); ?>
</footer>
</html>

