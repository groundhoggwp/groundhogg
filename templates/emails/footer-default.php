<?php
/**
 * Email Footer
 *
 * @package     Templates/Emails
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$footer = apply_filters( 'groundhogg/email_template/footer_css', [
    'clear' => 'both', 
    'margin-top' => '10px', 
    'text-align' => 'center', 
    'width' => '100%',
] );

$footer = \Groundhogg\array_to_css( $footer );

$footer_container = apply_filters( 'groundhogg/email_template/footer_container_css', [
    'border-collapse' => 'separate',
    'mso-table-lspace' => '0pt', 
    'mso-table-rspace' => '0pt', 
    'width' => '100%',
] );

$footer_container = \Groundhogg\array_to_css( $footer_container );

$footer_css = apply_filters( 'groundhogg/email_template/footer_content_css', [
    'font-family' => 'sans-serif', 
    'vertical-align' => 'top',
    'padding-bottom' => '10px',
    'padding-top' => '10px',
    'font-size' => '13px',
    'color' => '#999999',
    'text-align' => 'center'
] );

$footer_css = \Groundhogg\array_to_css( $footer_css );

$apple_link = apply_filters( 'groundhogg/email_template/apple_link_css', [
    'color' => '#999999',
    'font-size' => '13px',
    'text-align' => 'center',
]);

$apple_link = \Groundhogg\array_to_css( $apple_link );
?>
                                <!-- START FOOTER -->
                                <div class="footer" style="<?php echo $footer; ?>">
                                    <table border="0" cellpadding="0" cellspacing="0" style="<?php echo $footer_container; ?>">
                                        <tr>
                                            <td class="content-block" style="<?php echo $footer_css; ?>">
                                                <span class="apple-link" style="<?php echo $apple_link; ?>">
                                                    <?php echo apply_filters( 'groundhogg/email_template/footer_text', '' ); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="content-block" style="<?php echo $footer_css; ?>">
                                                <span style="<?php echo $apple_link; ?>">
                                                    <?php _e( apply_filters( 'groundhogg/email_template/pre_unsubscribe_text', __( "Don't want these emails?", 'groundhogg' ) ), 'groundhogg'); ?> <a href="<?php echo esc_url_raw( apply_filters( 'groundhogg/email_template/unsubscribe_link', site_url() ) ); ?>">
                                                        <?php _e( apply_filters( 'groundhogg/email_template/unsubscribe_text', __( "Unsubscribe.", 'groundhogg' ) ), 'groundhogg'); ?>
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
                </td>
            </tr>
        </table>
        <img alt="" style="visibility: hidden" width="0" height="0" src="<?php echo esc_url( apply_filters( 'groundhogg/email_template/open_tracking_link', '' ) ); ?>">
    </body>
</html>
