<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Groundhogg\Email;
use function Groundhogg\the_email;

$email = the_email();

/**
 * @var $email Email
 */

$email_title = get_bloginfo( 'name', 'display' );

/* translators: Login screen title. 1: Login screen name, 2: Network or site name */
$email_title = sprintf( __( '%1$s &lsaquo; %2$s' ), $email->get_merged_subject_line(), $email_title );

$bgcolor = $email->get_meta( 'backgroundColor' ) ?: ''

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

        .footer p {
            font-size: 13px;
            color: #999999;
            margin: .5em 0;
        }

        .footer p a {
            text-decoration: none;
        }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

		<?php echo file_get_contents( __DIR__ . '/assets/posts.css' ); ?>

		<?php echo $email->get_css() ?>

		<?php

		$alignment = $email->get_alignment(); // 'left' or 'center'

		switch ( $alignment ):
			default:
			case 'left';
				break;
			case 'center';
				?>
        .footer p {
            text-align: center;
        }

        table.content-container {
            margin: auto;
        }

		<?php

		break;
endswitch;?>
		<?php do_action( 'groundhogg/templates/email/boxed/style' ); ?>
	</style>
	<?php do_action( 'groundhogg/templates/email/boxed/head' ); ?>
</head>
<body class="email" style="background-color: <?php esc_attr_e( $bgcolor ); ?>">
<table class="alignment-container" style="width: 100%;border-collapse: collapse;" cellpadding="0" cellspacing="0">
	<tr>
		<td align="<?php esc_attr_e( $alignment ); ?>" bgcolor="<?php esc_attr_e( $bgcolor ); ?>"
		    style="background-color: <?php esc_attr_e( $bgcolor ); ?>">
			<table class="content-container" cellpadding="0" cellspacing="0" style="border-collapse: collapse">
				<tr>
					<td width="<?php esc_attr_e( $email->get_width() ); ?>"
					    style="width: <?php esc_attr_e( $email->get_width() ); ?>px">

						<?php include __DIR__ . '/parts/browser-view.php' ?>

						<div class="body-content" style="text-align: left;">
							<?php do_action( 'groundhogg/templates/email/boxed/content/before' ); ?>

							<!-- START CONTENT -->
							<?php echo $email->get_merged_content(); ?>
							<!-- END CONTENT -->

							<?php do_action( 'groundhogg/templates/email/boxed/content/after' ); ?>
						</div>

						<?php include __DIR__ . '/parts/footer.php' ?>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<?php include __DIR__ . '/parts/open-tracking.php' ?>
</body>
</html>
