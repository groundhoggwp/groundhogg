<?php
/**
 * Responsive Form Iframe Template
 *
 * @since       File available since Release 1.0.20
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Templates
 */

use Groundhogg\Step;

$form_id = get_query_var( 'slug' );

$step = new Step( $form_id );

if ( ! $step ) {
	wp_die( 'No form found...' );
}

$title = $step->get_title();

$shortcode = sprintf( '[gh_form id="%d"]', $step->get_id() );

add_filter( 'show_admin_bar', '__return_false' );

set_query_var( 'doing_iframe', true );

status_header( 200 );
header( 'Content-Type: text/html; charset=utf-8' );
nocache_headers();

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'groundhogg-form' );
	wp_enqueue_style( 'groundhogg-loader' );
} );

?><!DOCTYPE html>
<html <?php language_attributes(); ?> style="margin-top: 0 !important;">
<head>
    <base target="_parent">
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <title><?php echo $title; ?></title>
	<?php wp_head(); ?>
    <script>

      let source = null
      let formId = 0

      const postResizeData = () => {

        if ( source === null ){
          return
        }

        let body = document.body, html = document.documentElement
        let height = Math.max(body.scrollHeight, body.offsetHeight,
          html.clientHeight, html.scrollHeight, html.offsetHeight)
        let width = '100%'

        source.postMessage({ height: height, width: width, id: formId }, '*')
      }

      window.addEventListener('message', function (event) {
        if (typeof event.data.action !== 'undefined' && event.data.action === 'getFrameSize') {

          source = event.source
          formId = event.data.id

          postResizeData()
        }
      })

      window.addEventListener('load', () => {
        ['submit', 'reset', 'ajaxfinished', 'ghformsubmitted'].forEach(evt => {
          document.querySelector('form.gh-form').addEventListener(evt, () => {
            postResizeData()
          })
        })
      })
    </script>
</head>
<body class="groundhogg-form-body" style="padding: 20px">
<div id="main">
	<?php echo do_shortcode( $shortcode ); ?>
</div>
<?php
wp_print_scripts( [
	'groundhogg-ajax-form',
	'groundhogg-form-v2'
] )
?>
<div class="clear"></div>
</body>
</html>

