<?php

/**
 * This is a template for the Form iframe functionality
 */
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php do_action( 'wpgh_form_iframe_title' ); ?></title>
    <?php do_action( 'wp_head' ); ?>
    <script>
        window.addEventListener('message', function (event) {

            // Need to check for safty as we are going to process only our messages
            // So Check whether event with data(which contains any object) contains our message here its "FrameHeight"
            if ( typeof event.data.action !== "undefined" && event.data.action === "getFrameSize") {

                //event.source contains parent page window object
                //which we are going to use to send message back to main page here "abc.com/page"
                //parentSourceWindow = event.source;
                //Calculate the maximum height of the page

                var body = document.body, html = document.documentElement;
                var height = Math.max(body.scrollHeight, body.offsetHeight,
                    html.clientHeight, html.scrollHeight, html.offsetHeight);

                var width = '100%';
                // Send height back to parent page "abc.com/page"
                event.source.postMessage({ height: height, width: width, id:event.data.id }, "*");
            }
        });
    </script>
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

