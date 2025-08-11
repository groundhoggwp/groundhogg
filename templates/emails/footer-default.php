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

use function Groundhogg\array_to_css;
use function Groundhogg\html;

if ( ! defined( 'ABSPATH' ) ) exit;

$footer_alignment = get_option( 'gh_email_footer_alignment', 'left' );

$footer = apply_filters( 'groundhogg/email_template/footer_css', [
    'clear' => 'both',
    'margin-top' => '10px',
    'text-align' => $footer_alignment,
    'width' => '100%',
] );

$footer = array_to_css( $footer );

$footer_container = apply_filters( 'groundhogg/email_template/footer_container_css', [
    'border-collapse' => 'separate',
    'mso-table-lspace' => '0pt',
    'mso-table-rspace' => '0pt',
    'width' => '100%',
] );

$footer_container = array_to_css( $footer_container );

$footer_css = apply_filters( 'groundhogg/email_template/footer_content_css', [
    'font-family' => 'sans-serif',
    'vertical-align' => 'top',
    'padding-bottom' => '10px',
    'padding-top' => '10px',
    'font-size' => '12px',
    'color' => '#999999',
    'text-align' => $footer_alignment
] );

$footer_css = array_to_css( $footer_css );

$apple_link = apply_filters( 'groundhogg/email_template/apple_link_css', [
    'color' => '#999999',
    'font-size' => '12px',
    'text-align' => $footer_alignment
]);

$apple_link = array_to_css( $apple_link );

do_action( 'groundhogg/templates/email/footer/before' );

$footer_info = [
    html()->e( 'span', [], [
	    apply_filters( 'groundhogg/email_template/pre_unsubscribe_text', esc_html__( "Don't want these emails?", 'groundhogg' ) ),
        " ",
        html()->e( 'a', [
            'href' => apply_filters( 'groundhogg/email_template/unsubscribe_link', home_url() ),
        ], apply_filters( 'groundhogg/email_template/unsubscribe_text', esc_html__( "Unsubscribe", 'groundhogg' ) ) ) . '.'
    ] ),
];

$custom_text = get_option( 'gh_custom_email_footer_text' );

if ( $custom_text ): ?>
                                <div class="pre-footer">
                                    <table border="0" cellpadding="0" cellspacing="0" style="<?php echo esc_attr( $footer_container ); ?>">
                                        <tr>
                                            <td class="content-block" style="">
                                                <?php
                                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- using wp_kses
                                                echo \Groundhogg\email_kses( wpautop( $custom_text ) ); ?>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <?php endif; ?>
                                <!-- START FOOTER -->
                                <div class="footer" style="<?php echo esc_attr( $footer ); ?>">
                                    <table border="0" cellpadding="0" cellspacing="0" style="<?php echo esc_attr( $footer_container ); ?>">
                                        <tr>
                                            <td class="content-block" style="<?php echo esc_attr( $footer_css ); ?>">
                                                <span class="apple-link" style="<?php echo esc_attr( $apple_link ); ?>">
                                                    <?php
                                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- using wp_kses
                                                    echo \Groundhogg\email_kses( apply_filters( 'groundhogg/email_template/footer_text', '' ) ); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="content-block" style="<?php echo esc_attr( $footer_css ); ?>">
                                                <span style="<?php echo esc_attr( $apple_link ); ?>">
                                                    <?php
                                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- handled upstream
                                                    echo implode( ' | ', $footer_info ); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php if(\Groundhogg\is_option_enabled( 'gh_affiliate_link_in_email')) : ?>
                                            <tr>
                                                <td style="padding: 20px" style="<?php echo esc_attr( $footer_css ); ?>">
                                                    <p style="<?php echo esc_attr( $apple_link ); ?>">
                                                        <?php esc_html_e("This email was sent with" , 'groundhogg');; ?>
                                                        <a href="<?php echo esc_url( add_query_arg( [
                                                            'utm_source'    => 'email',
                                                            'utm_medium'    => 'footer-link',
                                                            'utm_campaign'  => 'email-affiliate',
                                                            'aff'           => absint( get_option( 'gh_affiliate_id' ) ),
                                                        ], 'https://www.groundhogg.io/pricing/' ) ); ?>" target="_blank">
                                                            <img style="vertical-align: middle" height="18.33" width="100" src="<?php echo esc_url( GROUNDHOGG_ASSETS_URL . 'images/groundhogg-logo-email-footer.png' ); ?>"/>
                                                        </a>
                                                    </p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
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
<?php if ( ! \Groundhogg\is_option_enabled( 'gh_disable_open_tracking' ) ): ?>
        <img alt="" style="visibility: hidden" width="0" height="0" src="<?php echo esc_url( apply_filters( 'groundhogg/email_template/open_tracking_link', '' ) ); ?>">
<?php endif; ?>
    </body>
</html>
