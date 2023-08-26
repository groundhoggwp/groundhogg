<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Groundhogg\Email;
use function Groundhogg\array_to_css;
use function Groundhogg\hex_is_lighter_than;
use function Groundhogg\the_email;
use function Groundhogg\white_labeled_name;

$email = the_email();

/**
 * @var $email Email
 */

$email_title = get_bloginfo( 'name', 'display' );

/* translators: Login screen title. 1: Login screen name, 2: Network or site name */
$email_title = sprintf( __( '%1$s &lsaquo; %2$s' ), $email->get_title(), $email_title );

$background_color = $email->get_meta( 'backgroundColor' ) ?: '#EDF5FF';
$frame_color      = $email->get_meta( 'frameColor' ) ?: '#fff';

$inner_content_style = [
	'margin'        => '0 auto',
	'padding'       => '30px',
	'box-shadow'    => '5px 5px 30px 0 rgba(24, 45, 70, 0.05)',
	'border-radius' => '20px',
	'background'    => $frame_color,
];

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
	<style type="text/css">

        img {
            max-width: 100% !important;
        }

        body {
            font-size: 14px;
            font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
            font-weight: 400;
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

		<?php include __DIR__ . '/parts/posts-css.php'; ?>

		<?php echo $email->get_css(); ?>

		<?php $background_color = $email->get_meta( 'backgroundColor' ) ?: '#EDF5FF' ?>
		<?php $frame_color = $email->get_meta( 'frameColor' ) ?: '#fff' ?>

        .template-framed {
            background: <?php esc_attr_e($background_color);?>;
            padding: 60px 0;
        }

        .template-framed .template-logo {
            margin: 0 auto 30px auto;
        }

        .template-framed .inner-content {
            margin: 0 auto;
            padding: 30px;
            box-shadow: 5px 5px 30px 0 rgba(24, 45, 70, 0.05);
            border-radius: 20px;
            background: <?php esc_attr_e($frame_color);?>;
        }

        .footer p {
            text-align: center;
        }

		<?php
		if ( ! hex_is_lighter_than( $background_color, 200 ) ): ?>
        .footer, .pre-footer-content {
            /*filter: invert(1);*/
        }

		<?php endif;?>
	</style>
	<?php do_action( 'groundhogg/templates/email/framed/head' ); ?>
</head>
<body class="email template-framed" style="background-color: <?php esc_attr_e( $background_color ); ?>;">
<table style="width: 100%;">
	<tr>
		<?php include __DIR__ . '/parts/browser-view.php' ?>
		<?php

		$logo = $email->get_meta( 'logo' );
		$logo = wp_parse_args( $logo, [
			'src'   => '',
			'width' => 320,
			'alt'   => 'logo',
			'title' => white_labeled_name()
		] )

		?>
		<td align="center" style="padding: 30px 0">
			<img class="template-logo" src="<?php echo esc_url( $logo['src'] ) ?>"
			     title="<?php esc_attr_e( $logo['title'] ); ?>" alt="<?php esc_attr_e( $logo['alt'] ); ?>"
			     width="<?php echo absint( $logo['width'] ) ?>">
		</td>
	</tr>
	<tr>
		<td align="center">
			<table>
				<tr>
					<td style="width: <?php echo $email->get_width() ?>px">
						<div class="body-content inner-content"
						     style="<?php echo array_to_css( $inner_content_style ) ?>">
							<?php do_action( 'groundhogg/templates/email/framed/content/before' ); ?>

							<!-- START CONTENT -->
							<?php echo $email->get_merged_content(); ?>
							<!-- END CONTENT -->

							<?php do_action( 'groundhogg/templates/email/framed/content/after' ); ?>
						</div>
					</td>
				</tr>
				<tr>
					<td style="width: <?php echo $email->get_width() ?>px">
						<?php

						$invert_footer_text_color = function ( $style ) use ( $background_color ) {
							if ( ! hex_is_lighter_than( $background_color, 150 ) ){
								$style['filter'] = 'invert(1)';
							}

							return $style;
						};

						add_filter( 'groundhogg/templates/email/parts/footer/custom_footer_text_style', $invert_footer_text_color );

						include __DIR__ . '/parts/footer.php';

						remove_filter( 'groundhogg/templates/email/parts/footer/custom_footer_text_style', $invert_footer_text_color );

						?>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<?php include __DIR__ . '/parts/open-tracking.php' ?>
</body>
</html>
