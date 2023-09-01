<?php

if ( ! defined( 'ABSPATH' ) ) exit;

use Groundhogg\Email;
use function Groundhogg\get_default_email_width;
use function Groundhogg\the_email;

include_once __DIR__ . '/template-functions.php';

$email = the_email();

/**
 * @var $email Email
 */

$email_title = get_bloginfo( 'name', 'display' );

/* translators: Login screen title. 1: Login screen name, 2: Network or site name */
$email_title = sprintf( __( '%1$s &lsaquo; %2$s' ), $email->get_merged_subject_line(), $email_title );

?>
<!doctype html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting"/>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

    <title><?php echo $email_title; ?></title>
    <base target="_blank">
    <style id="global-style">
	    <?php load_css( 'email' ); ?>
	    <?php do_action( 'groundhogg/templates/email/full-width/style' ); ?>
    </style>
	<style id="responsive">
		<?php load_css( 'responsive' ); ?>
	</style>
	<style id="block-styles">
		<?php echo $email->get_css() ?>
	</style>
	<?php do_action( 'groundhogg/templates/email/full-width/head' ); ?>
</head>
<body class="email responsive">
<?php load_part( 'preview-text' ); ?>
<?php load_part( 'browser-view' ); ?>
<div class="body-content">
    <?php do_action( 'groundhogg/templates/email/full-width/content/before' ); ?>
    <?php echo $email->get_merged_content(); ?>
    <?php do_action( 'groundhogg/templates/email/full-width/content/after' ); ?>
</div>
<?php load_part( 'footer' ); ?>
<?php load_part( 'open-tracking' ); ?>
</body>
</html>
