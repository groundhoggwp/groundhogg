<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-09-28
 * Time: 4:21 PM
 */

$footer = apply_filters( 'wpgh_email_footer_css', "
    clear: both; 
    Margin-top: 10px; 
    text-align: center; 
    width: 100%;
" );

$footer_container = apply_filters( 'wpgh_email_footer_container_css', "
    border-collapse: separate;
    mso-table-lspace: 0pt; 
    mso-table-rspace: 0pt; 
    width: 100%;
" );

$footer_content = apply_filters( 'wpgh_email_footer_content_css', "
    font-family: sans-serif; 
    vertical-align: top; 
    padding-bottom: 10px; 
    padding-top: 10px; 
    font-size: 13px; 
    color: #999999; 
    text-align: center;
" );

$apple_link = apply_filters( 'wpgh_email_apple_link_css', "
    color: #999999; 
    font-size: 13px; 
    text-align: center;
" );
?>

                        <!-- START FOOTER -->
                        <div class="footer" style="<?php echo $footer; ?>">
                            <table border="0" cellpadding="0" cellspacing="0" style="<?php echo $footer_container; ?>">
                                <tr>
                                    <td class="content-block" style="<?php echo $footer_content; ?>">
                                        <span class="apple-link" style="<?php echo $apple_link; ?>">
                                            <?php echo apply_filters( 'wpgh_email_footer_text', '' ); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="content-block" style="<?php echo $footer_content; ?>">
                                        <span style="<?php echo $apple_link; ?>">
                                            <?php _e( apply_filters( 'gh_unsubscribe_footer_text', "Don't want these emails?" ), 'groundhogg'); ?> <a href="<?php echo esc_url_raw( apply_filters( 'wpgh_email_unsubscribe_link', site_url() ) ); ?>">
                                                <?php _e( apply_filters( 'gh_unsubscribe_text', "Unsubscribe." ), 'groundhogg'); ?>
                                            </a>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <!-- END FOOTER -->
                    </div>
                </td>
            </tr>
        </table>
        <img style="visibility: hidden" width="0" height="0" src="<?php echo esc_url_raw( apply_filters( 'wpgh_email_open_tracking_link', '' ) ); ?>">
    </body>
</html>
