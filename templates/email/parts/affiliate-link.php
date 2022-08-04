<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( \Groundhogg\is_option_enabled( 'gh_affiliate_link_in_email' ) ) : ?>
    <tr>
        <td style="padding: 20px" >
            <p>
				<?php _e( "This email was sent with", 'groundhogg' ); ?>
                <a href="<?php echo add_query_arg( [
					'utm_source'   => 'email',
					'utm_medium'   => 'footer-link',
					'utm_campaign' => 'email-affiliate',
					'aff'          => absint( get_option( 'gh_affiliate_id' ) ),
				], 'https://www.groundhogg.io/pricing/' ); ?>" target="_blank">
                    <img alt="Sent by Groundhogg" style="vertical-align: middle" height="18.33" width="100"
                         src="<?php echo GROUNDHOGG_ASSETS_URL . 'images/groundhogg-logo-email-footer.png'; ?>"/>
                </a>
            </p>
        </td>
    </tr>
<?php endif; ?>
