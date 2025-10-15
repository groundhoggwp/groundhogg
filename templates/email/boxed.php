<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Groundhogg\Email;
use function Groundhogg\the_email;

include_once __DIR__ . '/template-functions.php';

$email = the_email();

/**
 * @var $email Email
 */


$email_title = $email->get_html_head_title();

$bgColor    = $email->get_meta( 'backgroundColor' ) ?: '';
$bgImage    = $email->get_meta( 'backgroundImage' ) ?: '';
$bgPosition = $email->get_meta( 'backgroundPosition' ) ?: 'center center';
$bgRepeat   = $email->get_meta( 'backgroundRepeat' ) ?: 'no-repeat';
$bgSize     = $email->get_meta( 'backgroundSize' ) ?: 'auto';
$direction  = $email->get_meta( 'direction' ) ?: 'ltr'; // 'ltr' or 'rtl'
$alignment  = $email->get_alignment(); // 'left' or 'center'

$bodyStyle = [
	'background-color' => $bgColor
];

if ( $bgImage ) {
	$bodyStyle = array_merge( $bodyStyle, [
		'background-image'    => $bgImage,
		'background-position' => $bgPosition,
		'background-repeat'   => $bgRepeat,
		'background-size'     => $bgSize,
	] );
}

$bodyStyle = array_filter( $bodyStyle );

?>
<!doctype html>
<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="x-apple-disable-message-reformatting"/>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title><?php echo esc_html( $email_title ); ?></title>
	<base target="_blank">
	<style id="global-styles">
		<?php load_css( 'email' ); ?>
		<?php do_action( 'groundhogg/templates/email/boxed/style' ); ?>
	</style>
	<style id="responsive">
		<?php load_css( 'responsive' ); ?>
	</style>
	<style id="block-styles">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_html() breaks `div > span` selectors
		echo wp_strip_all_tags( $email->get_css() ); ?>
	</style>
	<?php do_action( 'groundhogg/templates/email/boxed/head' ); ?>
	<?php load_part( 'head-close' ) ?>
</head>
<body class="email responsive template-boxed" dir="<?php echo esc_attr( $direction ); ?>" style="background-color: <?php echo esc_attr( $bgColor ); ?>">
<?php load_part( 'body-open' ); ?>
<?php load_part( 'preview-text' ); ?>
<table class="alignment-container" style="width: 100%;border-collapse: collapse;" cellpadding="0" cellspacing="0" role="presentation">
	<tr>
        <td align="<?php echo esc_attr( $alignment ); ?>" bgcolor="<?php echo esc_attr( $bgColor ); ?>" background="<?php echo esc_url( $bgImage ); ?>" style="<?php echo esc_attr( \Groundhogg\array_to_css( $bodyStyle ) ); ?>">
			<table class="content-container" cellpadding="0" cellspacing="0" style="border-collapse: collapse" role="presentation">
				<tr>
                    <td width="<?php echo esc_attr( $email->get_width() ); ?>" style="width: <?php echo esc_attr( $email->get_width() ); ?>px">
						<?php

                        load_part( 'browser-view' );
                        do_action( 'groundhogg/templates/email/boxed/content/before' );

                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- handled upstream
                        echo $email->get_merged_content();

						do_action( 'groundhogg/templates/email/boxed/content/after' );
						load_part( 'footer' ); ?>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<?php load_part( 'open-tracking' ); ?>
</body>
</html>
