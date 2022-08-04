<?php

if ( ! defined( 'ABSPATH' ) ) exit;

use Groundhogg\Email;
use function Groundhogg\the_email;

$email = the_email();

/**
 * @var $email Email
 */

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

        .template-framed {
            background: rgba(0, 117, 255, 0.07);
            padding: 60px 0;
        }
        .template-framed .template-logo {
            margin: 0 auto 30px auto;
            /*display: block;*/
        }
        .template-framed .inner-content {
            margin: 0 auto;
            padding: 30px;
            box-shadow: 5px 5px 30px 0 rgba(24, 45, 70, 0.05);
            border-radius: 20px;
            background: #ffffff;
        }

        .footer p {
            text-align: center;
        }
	</style>
	<?php do_action( 'groundhogg/templates/email/boxed/head' ); ?>
</head>
<body class="email template-framed">
<table style="width: 100%;">
    <tr>
        <td align="center"><img class="template-logo" src="<?php echo esc_url( $email->get_meta( 'logo' ) ) ?>" title="logo" alt="logo" width="<?php echo absint( $email->get_meta( 'logo_width' ) ) ?: 360 ?>"></td>
    </tr>
    <tr>
        <td align="center">
            <table>
                <tr>
                    <td style="width: <?php echo absint( $email->get_meta( 'width' ) ) ?>px">
                        <div class="body-content inner-content" style="text-align: left;">
		                    <?php do_action( 'groundhogg/templates/email/boxed/content/before' ); ?>

                            <!-- START CONTENT -->
		                    <?php echo $email->get_merged_content(); ?>
                            <!-- END CONTENT -->

		                    <?php do_action( 'groundhogg/templates/email/boxed/content/after' ); ?>
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<?php include __DIR__ . '/parts/footer.php' ?>
</body>
</html>
