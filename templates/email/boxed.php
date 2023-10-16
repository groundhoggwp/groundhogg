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

$email_title = get_bloginfo( 'name', 'display' );

/* translators: Login screen title. 1: Login screen name, 2: Network or site name */
$email_title = sprintf( __( '%1$s &lsaquo; %2$s' ), $email->get_merged_subject_line(), $email_title );

$bgColor    = $email->get_meta( 'backgroundColor' ) ?: '';
$bgImage    = $email->get_meta( 'backgroundImage' ) ?: '';
$bgPosition = $email->get_meta( 'backgroundPosition' ) ?: 'center center';
$bgRepeat   = $email->get_meta( 'backgroundRepeat' ) ?: 'no-repeat';
$bgSize     = $email->get_meta( 'backgroundSize' ) ?: 'auto';
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
	<title><?php echo $email_title; ?></title>
	<base target="_blank">
	<style id="global-styles">
		<?php load_css( 'email' ); ?>
		<?php do_action( 'groundhogg/templates/email/boxed/style' ); ?>
	</style>
	<style id="responsive">
		<?php load_css( 'responsive' ); ?>
	</style>
	<style id="block-styles">
		<?php echo $email->get_css() ?>
	</style>
	<?php do_action( 'groundhogg/templates/email/boxed/head' ); ?>
</head>
<body class="email responsive template-boxed" style="background-color: <?php esc_attr_e( $bgColor ); ?>">
<?php load_part( 'preview-text' ); ?>
<table class="alignment-container" style="width: 100%;border-collapse: collapse;" cellpadding="0" cellspacing="0" role="presentation">
	<tr>
		<td align="<?php esc_attr_e( $alignment ); ?>" bgcolor="<?php esc_attr_e( $bgColor ); ?>"
		    background="<?php echo esc_url( $bgImage ); ?>" style="<?php echo \Groundhogg\array_to_css( $bodyStyle )?>">
			<table class="content-container" cellpadding="0" cellspacing="0" style="border-collapse: collapse" role="presentation">
				<tr>
					<td width="<?php esc_attr_e( $email->get_width() ); ?>"
					    style="width: <?php esc_attr_e( $email->get_width() ); ?>px">

						<?php load_part( 'browser-view' ); ?>

						<div class="body-content" style="text-align: left;">
							<?php do_action( 'groundhogg/templates/email/boxed/content/before' ); ?>
							<?php echo $email->get_merged_content(); ?>
							<?php do_action( 'groundhogg/templates/email/boxed/content/after' ); ?>
						</div>

						<?php load_part( 'footer' ); ?>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<?php load_part( 'open-tracking' ); ?>
</body>
</html>
