<?php

namespace Groundhogg\Templates\Notifications;

use Groundhogg\Utils\Replacer;
use function Groundhogg\is_white_labeled;

class Notification_Builder {

	protected static function get_template_part( $template = '' ) {
		$file = __DIR__ . "/{$template}.html";
		if ( ! file_exists( $file ) ) {
			return '';
		}

		return file_get_contents( $file );
	}

	/**
	 * Get the full template with headers and footers, along with the correct content template
	 *
	 * @param string $content_template
	 *
	 * @return string
	 */
	protected static function get_general_notification_template_html( $content_template = '' ) {

		$replacer = new Replacer( [
			'the_header'  => self::get_template_part( ! is_white_labeled() ? 'branded-header' : 'generic-header' ),
			'the_content' => self::get_template_part( $content_template ),
			'the_footer'  => self::get_template_part( ! is_white_labeled() ? 'branded-footer' : 'generic-footer' ),
			'assets_url'  => GROUNDHOGG_ASSETS_URL,
			'site_url'    => home_url(),
			'site_name'   => get_bloginfo(),
			'home_url'    => home_url(),
			'admin_url'   => admin_url(),
			'profile_url' => admin_url( 'profile.php' )
		] );

		return $replacer->replace( self::get_template_part( 'general-template' ) );
	}
}
