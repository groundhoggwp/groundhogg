<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Groundhogg\Email;
use Groundhogg\Plugin;
use function Groundhogg\do_replacements;
use function Groundhogg\html;
use function Groundhogg\the_email;

$email = the_email();

$email_title = get_bloginfo( 'name', 'display' );

/* translators: Login screen title. 1: Login screen name, 2: Network or site name */
$email_title = sprintf( __( '%1$s &lsaquo; %2$s' ), $email->get_title(), $email_title );

?>
<!doctype html>
<html>

<!-- HEAD -->
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting"/>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

    <title><?php echo $email_title; ?></title>
    <base target="_blank">
    <style>

        img {
            max-width: 100% !important;
        }

        body {
            font-size: 14px;
            font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
            font-weight: 400;
            margin-bottom: 50px;
        }

        .aligncenter {
            display: block;
            float: none;
            margin-left: auto;
            margin-right: auto;
        }

        .alignleft {
            float: left;
            margin: 0.5em 1em 0.5em 0;
        }

        .alignright {
            float: right;
            margin: 0.5em 0 0.5em 1em;
        }

        @media only screen and (max-width: 480px) {

            .alignright:not(.keep-float),
            .alignleft:not(.keep-float) {
                display: block !important;
                float: none !important;
                margin-left: auto !important;
                margin-right: auto !important;
                margin-bottom: 20px !important;
            }

        }

        <?php include __DIR__ . '/parts/blocks-css.php'; ?>

        <?php echo $email->get_meta('css' ); ?>

    </style>
	<?php do_action( 'groundhogg/templates/email/full-width/head' ); ?>
</head>
<body class="email">
<div class="body-content">
	<?php do_action( 'groundhogg/templates/email/full-width/content/before' ); ?>

    <!-- START CONTENT -->
	<?php echo $email->get_merged_content(); ?>
    <!-- END CONTENT -->

	<?php do_action( 'groundhogg/templates/email/full-width/content/after' ); ?>
</div>
<?php include __DIR__ . '/parts/footer.php' ?>
</body>
</html>
