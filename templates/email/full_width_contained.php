<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

$bgColor    = $email->get_meta( 'backgroundColor' ) ?: '';
$bgImage    = $email->get_meta( 'backgroundImage' ) ?: '';
$bgPosition = $email->get_meta( 'backgroundPosition' ) ?: 'center center';
$bgRepeat   = $email->get_meta( 'backgroundRepeat' ) ?: 'no-repeat';
$bgSize     = $email->get_meta( 'backgroundSize' ) ?: 'auto';
$direction  = $email->get_meta( 'direction' ) ?: 'ltr';
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
<body class="email template-full-width-contained" dir="<?php esc_attr_e( $direction ); ?>">
<table class="body-content" cellspacing="0" cellpadding="0" role="presentation" width="100%">
	<tr>
		<td bgcolor="<?php esc_attr_e( $bgColor ); ?>" background="<?php echo esc_url( $bgImage ); ?>"
		    style="<?php echo \Groundhogg\array_to_css( $bodyStyle ) ?>">
			<?php load_part( 'preview-text' ); ?>
			<?php load_part( 'browser-view' ); ?>
			<?php do_action( 'groundhogg/templates/email/full-width/content/before' ); ?>
			<?php echo $email->get_merged_content(); ?>
			<?php do_action( 'groundhogg/templates/email/full-width/content/after' ); ?>
			<?php if ( ! $email->has_footer_block() ): ?>
			<table cellspacing="0" cellpadding="0" role="presentation" align="center">
				<tr>
					<td width="<?php esc_attr_e( $email->get_width() ) ?>" style="width: <?php esc_attr_e( $email->get_width() ) ?>px">
						<?php load_part( 'footer' ); ?>
					</td>
				</tr>
			</table>
			<?php endif; ?>
		</td>
	</tr>
</table>
<?php load_part( 'open-tracking' ); ?>
</body>
</html>
