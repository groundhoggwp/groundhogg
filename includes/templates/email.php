<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-07-18
 * Time: 2:00 PM
 */
?>
<!doctype html>
<html>
<head>
    <meta name="viewport" content="width=device-width">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title><?php echo $title;?></title>
</head>
<body class="" style="background-color: #FFFFFF; font-family: sans-serif; -webkit-font-smoothing: antialiased; font-size: 14px; line-height: 1.4; margin: 0; padding: 0; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%;">
<table border="0" cellpadding="0" cellspacing="0" class="body" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; background-color: #FFFFFF;">
    <tr>
        <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">&nbsp;</td>
        <td class="container" style="font-family: sans-serif; font-size: 14px; vertical-align: top; display: block; <?php echo $margins; ?> ; max-width: 580px; padding: 10px; width: 580px;">
            <div class="content" style="box-sizing: border-box; display: block; Margin: 0 auto; max-width: 580px; padding: 10px;">
                <!-- START PREHEADER -->
                <span class="preheader" style="color: transparent; display: none; height: 0; max-height: 0; max-width: 0; opacity: 0; overflow: hidden; mso-hide: all; visibility: hidden; width: 0;"><?php echo $pre_header; ?></span>
                <!-- END PREHEADER -->
                <!-- START CONTENT -->
                <?php echo $content; ?>
                <!-- END CONTENT -->
                <!-- START FOOTER -->
                <div class="footer" style="clear: both; Margin-top: 10px; text-align: center; width: 100%;">
                    <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;">
                        <tr>
                            <td class="content-block" style="font-family: sans-serif; vertical-align: top; padding-bottom: 10px; padding-top: 10px; font-size: 13px; color: #999999; text-align: center;">
                                <span class="apple-link" style="color: #999999; font-size: 13px; text-align: center;"><?php echo $email_footer_text; ?></span>
                            </td>
                        </tr>
                        <tr>
                            <td class="content-block" style="font-family: sans-serif; vertical-align: top; padding-bottom: 10px; padding-top: 10px; font-size: 13px; color: #999999; text-align: center;">
                                <span style="color: #999999; font-size: 13px; text-align: center;"><?php _e( "Don't want these emails?", 'groundhogg'); ?> <a href="<?php echo $unsubscribe_link; ?>"><?php _e( "Unsubscribe", 'groundhogg'); ?></a>.</span>
                            </td>
                        </tr>
                    </table>
                </div>
                <!-- END FOOTER -->
            </div>
        </td>
    </tr>
</table>
<img style="visibility: hidden" width="0" height="0" src="<?php echo $tracking_link; ?>">
</body>
</html>