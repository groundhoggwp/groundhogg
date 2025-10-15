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
$alignment  = $email->get_alignment(); // 'left' or 'center'
$direction  = $email->get_meta( 'direction' ) ?: 'ltr'; // 'ltr' or 'rtl'

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

?>
<!doctype html>
<html>
<?php load_part('head' );?>
<body class="email template-full-width" dir="<?php echo esc_attr( $direction ); ?>">
<table class="body-content" cellspacing="0" cellpadding="0" role="presentation" width="100%">
    <tr>
        <td bgcolor="<?php echo esc_attr( $bgColor ); ?>" background="<?php echo esc_url( $bgImage ); ?>" style="<?php echo esc_attr( Groundhogg\array_to_css( $bodyStyle ) ) ?>">
			<?php load_part( 'preview-text' ); ?>
			<?php load_part( 'browser-view' ); ?>
			<?php do_action( 'groundhogg/templates/email/full-width/content/before' ); ?>
			<?php
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitize and escaped upstream
            echo $email->get_merged_content();
            ?>
			<?php do_action( 'groundhogg/templates/email/full-width/content/after' ); ?>
			<?php load_part( 'footer' ); ?>
        </td>
    </tr>
</table>
<?php load_part( 'open-tracking' ); ?>
</body>
</html>
