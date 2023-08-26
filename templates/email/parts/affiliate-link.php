<?php

use function Groundhogg\html;
use function Groundhogg\is_option_enabled;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( is_option_enabled( 'gh_affiliate_link_in_email' ) ) {
	/** @var callable $_p */
	$_p( sprintf( __( "This email was sent with %s", 'groundhogg' ), html()->e( 'a', [
		'href' => add_query_arg( [
			'utm_source'   => 'email',
			'utm_medium'   => 'footer-link',
			'utm_campaign' => 'email-affiliate',
			'aff'          => absint( get_option( 'gh_affiliate_id' ) ),
		], 'https://www.groundhogg.io/pricing/' )
	], html()->e( 'img', [
		'src'    => GROUNDHOGG_ASSETS_URL . 'images/groundhogg-logo-email-footer.png',
		'height' => 18.33,
		'width'  => 100,
		'background-color' => '#fff',
		'style'  => [
			'vertical-align' => 'middle'
		]
	] ) ) ), ['margin-top' => '20px'] );
}
