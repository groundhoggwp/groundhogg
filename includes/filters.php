<?php

namespace Groundhogg;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-05-22
 * Time: 9:38 AM
 */

/**
 * GHSS doesn't link the <pwlink> format so we have to fix it by removing the gl & lt
 *
 * @param $message
 * @param $key
 * @param $user_login
 * @param $user_data
 *
 * @return string
 */
function fix_html_pw_reset_link( $message, $key, $user_login, $user_data ) {
	$message = preg_replace( '/<(https?:\/\/.*)>/', '$1', $message );

	return $message;
}

add_filter( 'retrieve_password_message', __NAMESPACE__ . '\fix_html_pw_reset_link', 10, 4 );

/**
 * Override the default from email
 *
 * @param $original_email_address
 *
 * @return mixed
 */
function sender_email( $original_email_address ) {

	// Might not be set.
	if ( ! isset_not_empty( $_SERVER, 'SERVER_NAME' ) && ! empty( $original_email_address ) ) {
		return $original_email_address;
	}

	// Get the site domain and get rid of www.
	$sitename = strtolower( $_SERVER['SERVER_NAME'] );

	if ( substr( $sitename, 0, 4 ) == 'www.' ) {
		$sitename = substr( $sitename, 4 );
	}

	$from_email = 'wordpress@' . $sitename;

	if ( $original_email_address === $from_email ) {
		$new_email_address = get_option( 'gh_override_from_email', $original_email_address );

		if ( ! empty( $new_email_address ) ) {
			$original_email_address = $new_email_address;
		}
	}

	return $original_email_address;
}

/**
 * Override the default from name
 *
 * @param $original_email_from
 *
 * @return mixed
 */
function sender_name( $original_email_from ) {

	if ( $original_email_from === 'WordPress' ) {
		$new_email_from = get_option( 'gh_override_from_name', $original_email_from );

		if ( ! empty( $new_email_from ) ) {
			$original_email_from = $new_email_from;
		}
	}

	return $original_email_from;
}

// Hooking up our functions to WordPress filters
add_filter( 'wp_mail_from', __NAMESPACE__ . '\sender_email' );
add_filter( 'wp_mail_from_name', __NAMESPACE__ . '\sender_name' );

/**
 * Remove the editing toolbar from the email content so it doesn't show up in the client's email.
 *
 * @param $content string the email content
 *
 * @return string the new email content.
 */
function remove_builder_toolbar( $content ) {
	return preg_replace( '/<wpgh-toolbar\b[^>]*>(.*?)<\/wpgh-toolbar>/', '', $content );
}

add_filter( 'groundhogg/email/the_content', __NAMESPACE__ . '\remove_content_editable' );

/**
 * Remove the content editable attribute from the email's html
 *
 * @param $content string email HTML
 *
 * @return string the filtered email content.
 */
function remove_content_editable( $content ) {
	return preg_replace( "/contenteditable=\"true\"/", '', $content );
}

add_filter( 'groundhogg/email/the_content', __NAMESPACE__ . '\remove_content_editable' );

/**
 * Remove script tags from the email content
 *
 * @param $content string the email content
 *
 * @return string, sanitized email content
 */
function strip_script_tags( $content ) {
	return preg_replace( '/<script\b[^>]*>(.*?)<\/script>/', '', $content );
}

add_filter( 'groundhogg/email/the_content', __NAMESPACE__ . '\strip_script_tags' );

/**
 * Ensure images have responsive styling
 *
 * @param $content
 *
 * @return string|string[]|null
 */
function responsive_tag_compat( $content ) {
	// Disable for advanced email editor
	if ( is_option_enabled( 'gh_use_advanced_email_editor' ) ) {
		return $content;
	}

	$tags = [
//        'figure',
		'img'
	];

	foreach ( $tags as $tag ) {
		$content = preg_replace_callback( "/<{$tag}[^>]+>/", __NAMESPACE__ . '\_responsive_tag_compat_callback', $content );
	}

	return $content;
}

add_filter( 'groundhogg/email_template/content', __NAMESPACE__ . '\responsive_tag_compat', 99 );

/**
 * @param $tag
 *
 * @return string
 */
function _responsive_tag_compat_callback( $matches ) {
	$tag = $matches[0];

	$tag_name = get_tag_name( $tag );
	$atts     = get_tag_attributes( $tag );

	if ( empty( $atts ) || empty( $tag_name ) ) {
		return $tag;
	}

	$default_email_width = get_default_email_width();

//	wp_send_json( $default_email_width );

	$classes = explode( ' ', get_array_var( $atts, 'class' ) );
	$style   = get_array_var( $atts, 'style', [] );

	$given_width = absint( get_array_var( $atts, 'width', $default_email_width ) );

	if ( $given_width <= 0 ){
		$given_width = $default_email_width;
	}

	$img_width = min( $given_width, $default_email_width );

	$style   = array_merge( $style, [
		'width'     => $img_width . 'px' ,
		'height'    => 'auto',
		'max-width' => '100%',
	] );

	foreach ( $classes as $class ) {
		switch ( $class ) {
			case 'aligncenter':
				$style['display'] = 'block';
				$style['margin']  = '0.5em auto';
				break;
			case 'alignleft':
				$style['float']  = 'left';
				$style['margin'] = '0.5em 1em 0.5em 0';
				break;
			case 'alignright':
				$style['float']  = 'right';
				$style['margin'] = '0.5em 0 0.5em 1em';
				break;
		}
	}

	unset( $atts['height'] );
	unset( $atts['width'] );

	$atts['style'] = $style;
	$atts['width'] = $img_width;

	$self_closing = [
		'img'
	];

	if ( in_array( $tag_name, $self_closing ) ) {
		return html()->e( $tag_name, $atts );
	}

	return sprintf( "<%s %s>", $tag_name, array_to_atts( $atts ) );
}

/**
 * Add a link to the FB group in the admin footer.
 *
 * @param $text
 *
 * @return string|string[]|null
 */
function add_bug_report_prompt( $text ) {
	if ( ! is_white_labeled() ) {
		if ( is_admin_groundhogg_page() && apply_filters( 'groundhogg/footer/show_text', true ) ) {
			return preg_replace( "/<\/span>/", sprintf( __( ' | Like Groundhogg? <a target="_blank" href="%s">Leave a Review</a>!</span>' ), __( 'https://wordpress.org/support/plugin/groundhogg/reviews/#new-post' ) ), $text );
		}
	}

	return $text;
}

add_filter( 'admin_footer_text', __NAMESPACE__ . '\add_bug_report_prompt' );

add_filter( 'groundhogg/admin/emails/sanitize_email_content', __NAMESPACE__ . '\safe_css_filter_rgb_to_hex', 10 );
add_filter( 'groundhogg/admin/emails/sanitize_email_content', __NAMESPACE__ . '\add_safe_style_attributes_to_email', 10 );
add_filter( 'groundhogg/admin/emails/sanitize_email_content', __NAMESPACE__ . '\kses_wrapper', 11 );

/**
 * Backwards compate
 *
 * @param $content
 *
 * @return string
 */
function kses_wrapper( $content ){
	return email_kses( $content );
}

/**
 * Compat for email links and replacements
 *
 * @param $content
 *
 * @return string
 */
function email_kses( $content ){

	// Basic protocols
	$basic_protocols = [ 'http', 'https', 'mailto','mms', 'sms', 'svn', 'tel', 'fax' ];

	// Weird protocols for replacements compatibility
	$wacky_protocols = [
		'{confirmation_link_raw.{auto_login_link.https',
		'{confirmation_link_raw.{auto_login_link.https',
		'{confirmation_link.{auto_login_link.http',
		'{confirmation_link.{auto_login_link.http',
		'{confirmation_link_raw.https',
		'{confirmation_link_raw.http',
		'{confirmation_link.https',
		'{confirmation_link.http',
		'{auto_login_link.{confirmation_link_raw.https',
		'{auto_login_link.{confirmation_link_raw.http',
		'{auto_login_link.https',
		'{auto_login_link.http',
	];

	return wp_kses( $content, 'post', array_merge( $basic_protocols, $wacky_protocols ) );
}

/**
 * Add some filters....
 *
 * @param $content
 *
 * @return mixed
 */
function add_safe_style_attributes_to_email( $content ) {
	add_filter( 'safe_style_css', __NAMESPACE__ . '\_safe_display_css' );

	return $content;
}

/**
 * Add display to list of allowed attributes
 *
 * @param $attributes
 *
 * @return array
 */
function _safe_display_css( $attributes ) {
	$attributes[] = 'display';

	return $attributes;
}

/**
 * Convert all RGB to HEX in content.
 *
 * @param $content
 *
 * @return mixed
 */
function safe_css_filter_rgb_to_hex( $content ) {
	$content = preg_replace_callback( '/rgb\((\d{1,3}), ?(\d{1,3}), ?(\d{1,3})\)/', __NAMESPACE__ . '\_safe_css_filter_rgb_to_hex_callback', $content );

	return $content;
}

/**
 * @param $matches
 *
 * @return string
 */
function _safe_css_filter_rgb_to_hex_callback( $matches ) {
	return rgb2hex( $matches[1], $matches[2], $matches[3] );
}

/**
 * Convert RGB to HEX.
 *
 * @param     $r
 * @param int $g
 * @param int $b
 *
 * @return string
 */
function rgb2hex( $r, $g = - 1, $b = - 1 ) {
	if ( is_array( $r ) && sizeof( $r ) == 3 ) {
		list( $r, $g, $b ) = $r;
	}

	$r = intval( $r );
	$g = intval( $g );
	$b = intval( $b );

	$r = dechex( $r < 0 ? 0 : ( $r > 255 ? 255 : $r ) );
	$g = dechex( $g < 0 ? 0 : ( $g > 255 ? 255 : $g ) );
	$b = dechex( $b < 0 ? 0 : ( $b > 255 ? 255 : $b ) );

	$color = ( strlen( $r ) < 2 ? '0' : '' ) . $r;
	$color .= ( strlen( $g ) < 2 ? '0' : '' ) . $g;
	$color .= ( strlen( $b ) < 2 ? '0' : '' ) . $b;

	return '#' . $color;
}

/**
 * Strip the hieght attribute from any images since
 *
 * @param $content
 *
 * @return string|string[]|null
 */
function remove_image_width( $content ) {
	return preg_replace( "/<img(.*) width=\"[0-9]+\"(.*)\/>/", "<img$1$2/>", $content );
}

/**
 * Strip the hieght attribute from any images since
 *
 * @param $content
 *
 * @return string|string[]|null
 */
function remove_image_height( $content ) {
	return preg_replace( "/<img(.*) height=\"[0-9]+\"(.*)\/>/", "<img$1$2/>", $content );
}

add_filter( 'tiny_mce_before_init', __NAMESPACE__ . '\tiny_mce_before_init' );

// Add listener for on lick event
function tiny_mce_before_init( $initArray ) {
	$initArray['setup'] = <<<JS
[function(ed) {
    ed.on( 'click', function(ed, e) {
        jQuery(document).trigger( 'to_mce' );
    });

}][0]
JS;

	return $initArray;
}

// Add the phone to the contact methods!
add_filter( 'user_contactmethods', __NAMESPACE__ . '\add_phone_contact_method', 99, 2 );

/**
 * Add a user contact method
 *
 * @param $methods
 * @param $user
 *
 * @return mixed
 */
function add_phone_contact_method( $methods, $user ) {
	$methods['phone'] = __( 'Phone', 'groundhogg' );

	return $methods;
}