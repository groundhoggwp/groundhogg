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
$form_id = get_query_var( 'form_id' );

$step = \Groundhogg\Plugin::$instance->utils->get_step( $form_id );

if ( ! $step ){
    wp_die( 'No form found...' );
}

$title = $step->get_title();

$shortcode = sprintf( '[gh_form id="%d"]', $step->get_id() );

add_filter( 'show_admin_bar', '__return_false' );

set_query_var( 'doing_iframe', true );

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <base target="_parent">
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <title><?php echo $title; ?></title>
    <?php wp_head(); ?>
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
</head>
<body class="groundhogg-form-body" style="padding: 20px">
<div id="main">
    <?php echo do_shortcode( $shortcode ); ?>
</div>
<?php
wp_footer();
?>
<div class="clear"></div>
</body>
</html>

