<?php

namespace Groundhogg;

use Groundhogg\Form\Form_Fields;
use Groundhogg\Utils\DateTimeHelper;

/**
 * Do replacements on the block content if replacements is enabled for the current post
 *
 * @param string $content
 *
 * @return string
 */
function do_replacements_when_rendering_blocks( $content, $parsed_block, \WP_Block $block ) {

	if ( isset_not_empty( $parsed_block['attrs'], 'ghReplacements' ) && ! empty( $content ) ) {
		$content = do_replacements( $content );
	}

	return $content;
}

add_filter( 'render_block', __NAMESPACE__ . '\do_replacements_when_rendering_blocks', 999, 3 );

/**
 * Maybe hide the block if the current contact can't see them.
 * Required the Restricted Content addon to be active.
 *
 * @param string    $content
 * @param array     $parsed_block
 * @param \WP_Block $block
 *
 * @return string
 */
function handle_conditional_content_block_filters( $content, $parsed_block, \WP_Block $block ) {

	// Content restriction is not enabled for this block
	if ( ! isset_not_empty( $parsed_block['attrs'], 'ghRestrictContent' )
	     || ! defined( 'GROUNDHOGG_CONTENT_RESTRICTION_VERSION' )
	) {
		return $content;
	}

	$contact = get_current_contact();

	// must be a contact if the block is restricted
	if ( ! $contact || ! $contact->exists() ) {
		return '';
	}

	$attrs = wp_parse_args( $parsed_block['attrs'], [
		'ghIncludeFilters' => '[]',
		'ghExcludeFilters' => '[]',
	] );

	$include_filters = json_decode( $attrs['ghIncludeFilters'], true );
	$exclude_filters = json_decode( $attrs['ghExcludeFilters'], true );

	// no filters, no query needed.
	if ( empty( $include_filters ) && empty( $exclude_filters ) ) {
		return $content;
	}

	// run the query
	$contactQuery = new Contact_Query( [
		'include'         => [ $contact->get_id() ],
		'filters'         => $include_filters,
		'exclude_filters' => $exclude_filters,
	] );

	$count = $contactQuery->count();

	// content is restricted if contact is not in the search
	if ( $count === 0 ) {
		return '';
	}

	return $content;
}

add_filter( 'render_block', __NAMESPACE__ . '\handle_conditional_content_block_filters', 998, 3 );


/**
 * Handle the skip if confirmed logic for the email confirmation step
 *
 * @param $enqueue bool
 * @param $contact Contact
 * @param $step    Step
 *
 * @return bool true if the step should be enqueued, otherwise false
 */
function handle_skip_if_confirmed( $enqueue, $contact, $step ) {

	// If the enqueue was already set to false ofr the step is not the send_email step
	if ( ! $enqueue || ! $step->type_is( 'send_email' ) ) {
		return $enqueue;
	}

	$email = new Email( $step->get_meta( 'email_id' ) );

	if ( $email->exists() && $email->is_confirmation_email() ) {
		// Contact is confirmed and thus the step should be skipped
		if ( $step->get_meta( 'skip_if_confirmed' ) && $contact->is_confirmed() ) {

			$next = $step->get_next_of_type( 'email_confirmed' );

			if ( $next ) {
				$next->enqueue( $contact );
			}

			return false;
		}
	}

	return true;
}

add_filter( 'groundhogg/steps/enqueue', __NAMESPACE__ . '\handle_skip_if_confirmed', 9, 3 );

/**
 * Swap out the sanitization callback
 *
 * @param $callback callable
 * @param $option   string
 * @param $value    mixed
 */
function filter_option_sanitize_callback( $callback, $option, $value ) {

	switch ( $option ) {
		case 'gh_task_outcomes':
		case 'gh_email_editor_color_palette':
		case 'gh_email_editor_global_social_accounts':
		case 'gh_email_editor_global_fonts':
			return function ( $value ) {
				return map_deep( $value, 'sanitize_text_field' );
			};
		case 'gh_contact_custom_properties':
			return [ Properties::class, 'sanitize' ];
		case 'gh_custom_reports':
			// todo implement proper sanitization here
			return function ( $props ) {
				return $props;
			};
		case 'gh_custom_profile_fields':
		case 'gh_custom_preference_fields':
			return [ Form_Fields::class, 'sanitize_form_and_map' ];
	}

	return $callback;

}

add_filter( 'groundhogg/api/v4/options_sanitize_callback', __NAMESPACE__ . '\filter_option_sanitize_callback', 10, 3 );

/**
 * Sanitize step meta
 *
 * @param $meta_value
 * @param $meta_key
 * @param $object_id
 * @param $prev_value
 *
 * @return mixed|string
 */
function sanitize_step_meta( $meta_value, $meta_key, $object_id, $prev_value ) {

	switch ( $meta_key ) {
		case 'note_text':
			$meta_value = wp_kses_post( $meta_value );
			break;
	}

	return $meta_value;
}

add_filter( 'groundhogg/meta/step/update/filter_value', __NAMESPACE__ . '\sanitize_step_meta', 10, 4 );

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
	return preg_replace( '/<(https?:\/\/.*)>/', '$1', $message );
}

add_filter( 'retrieve_password_message', __NAMESPACE__ . '\fix_html_pw_reset_link', 10, 4 );

/**
 * Override the default from email
 *
 * @param $from_email
 *
 * @return mixed
 */
function sender_email( $from_email ) {

	$wp_from_email = 'wordpress@' . get_hostname();

	// if from email starts with WP, or not a valid email address
	if ( ! is_email( $from_email ) || $from_email === $wp_from_email ) {
		return get_default_from_email();
	}

	return $from_email;
}

/**
 * Override the default from name
 *
 * @param $from_name
 *
 * @return mixed
 */
function sender_name( $from_name ) {

	$wp_from_name = 'WordPress';

	if ( empty( $from_name ) || $from_name === $wp_from_name ) {
		return get_default_from_name();
	}

	return $from_name;
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

	$tags = [//        'figure',
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

	$classes = explode( ' ', get_array_var( $atts, 'class' ) );
	$style   = get_array_var( $atts, 'style', [] );

	$given_width = absint( get_array_var( $atts, 'width', $default_email_width ) );

	if ( $given_width <= 0 ) {
		$given_width = $default_email_width;
	}

	$img_width = min( $given_width, $default_email_width );

	$style = array_merge( $style, [ 'width' => $img_width . 'px', 'height' => 'auto', 'max-width' => '100%', ] );

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

	$self_closing = [ 'img' ];

	if ( in_array( $tag_name, $self_closing ) ) {
		return html()->e( $tag_name, $atts );
	}

	return sprintf( "<%s %s>", $tag_name, array_to_atts( $atts ) );
}

/**
 * Add a link to the review Groundhogg in footer.
 *
 * @param $text
 *
 * @return string|string[]|null
 */
function add_review_link_in_footer( $text ) {

	if ( ! is_string( $text ) || is_white_labeled() || ! is_admin_groundhogg_page() || ! apply_filters( 'groundhogg/footer/show_text', true ) ){
		return $text;
	}

	return preg_replace( "/<\/span>/", sprintf( __( ' | Like Groundhogg? <a target="_blank" href="%s">Leave a Review</a>!</span>' ), __( 'https://wordpress.org/support/plugin/groundhogg/reviews/#new-post' ) ), $text );
}

add_filter( 'admin_footer_text', __NAMESPACE__ . '\add_review_link_in_footer' );

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
function kses_wrapper( $content ) {
	return email_kses( $content );
}

/**
 * Add more specialized attributes to the allowed css filter
 *
 * @param $attr
 *
 * @return mixed
 */
function more_allowed_css( $attr ) {

	$attr[] = 'display';
	$attr[] = 'outline';
	$attr[] = 'Margin';
	$attr[] = '-webkit-text-size-adjust';
	$attr[] = '-ms-text-size-adjust';
	$attr[] = 'mso-line-height-rule';
	$attr[] = 'mso-text-raise';
	$attr[] = 'mso-padding-alt';
	$attr[] = 'mso-border-alt';
	$attr[] = 'mso-table-lspace';
	$attr[] = 'mso-table-rspace';
	$attr[] = 'mso-style-priority';
	$attr[] = 'v-text-anchor';
	$attr[] = '-ms-interpolation-mode';
	$attr[] = 'outline';
	$attr[] = 'table-layout';
	$attr[] = 'background-repeat';

	return $attr;
}

/**
 * More tags for the kses
 *
 * @param $tags
 *
 * @return mixed
 */
function more_allowed_tags( $tags ) {
//	$tags['!DOCTYPE'] = [
//		'html' => true,
//		'PUBLIC' => true,
//	];
	$tags['html']             = [];
	$tags['head']             = [ 'xlmns' => true, 'xmlns:o' => true, ];
	$tags['meta']             = [ 'charset' => true, 'content' => true, 'name' => true, 'http-equiv' => true, ];
	$tags['title']            = [];
	$tags['style']            = [ 'type' => true ];
	$tags['link']             = [ 'href' => true ];
	$tags['center']           = [ 'id' => true, 'class' => true, 'style' => true, ];
	$tags['body']             = [ 'id' => true, 'class' => true, 'style' => true, ];
	$tags['td']['background'] = true;
	$tags['table']['role']    = true;

	// MSO
	$tags['xml'] = [];
	$tags['w']   = [];
	$tags['o']   = [];
	$tags['v']   = [ 'xmlns:v' => true, 'xmlns:w' => true, 'esdevVmlButton' => true, 'arcsize' => true, 'stroke' => true, 'fillcolor' => true, ];

	// Common unsupported tags
	unset( $tags['script'] );
	unset( $tags['embed'] );
	unset( $tags['audio'] );
	unset( $tags['video'] );
	unset( $tags['form'] );
	unset( $tags['select'] );
	unset( $tags['input'] );
	unset( $tags['button'] );
	unset( $tags['iframe'] );
	unset( $tags['menu'] );

	return $tags;
}

/**
 * Compat for email links and replacements
 *
 * @param $content
 *
 * @return string
 */
function email_kses( $content ) {

	// KSES does not like RBG values...
	$content = safe_css_filter_rgb_to_hex( $content );
	$content = safe_css_font_quotes( $content );

	add_filter( 'wp_kses_allowed_html', __NAMESPACE__ . '\more_allowed_tags' );
	add_filter( 'safe_style_css', __NAMESPACE__ . '\more_allowed_css' );

	// Basic protocols
	$basic_protocols = [ 'http', 'https', 'mailto', 'mms', 'sms', 'svn', 'tel', 'fax' ];

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
		'replacement', // hacky trick to allow replacements in URI elements
	];

	include_once __DIR__ . '/kses.php';

	$content = kses( $content, 'post', array_merge( $basic_protocols, $wacky_protocols ) );

	remove_filter( 'wp_kses_allowed_html', __NAMESPACE__ . '\more_allowed_tags' );
	remove_filter( 'safe_style_css', __NAMESPACE__ . '\more_allowed_css' );

	// remove replacement protocols, we know they're safe(ish).
	$content = preg_replace(
		'/(src|href)=("|\')replacement:(.*?)\2/i',
		'$1=$2$3$2',
		$content
	);

	return $content;
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
	return preg_replace_callback( '/rgb\((\d{1,3}), ?(\d{1,3}), ?(\d{1,3})\)/', __NAMESPACE__ . '\_safe_css_filter_rgb_to_hex_callback', $content );
}

/**
 * Using inline styles automatically adds &quot; to font families containing a space
 * This will convert them to single quotes so that the kses filter actually works
 *
 * @param $content
 *
 * @return array|string|string[]|null
 */
function safe_css_font_quotes( $content ) {
	return preg_replace( '/&quot;(Arial Black|Arial Narrow|Times New Roman|Courier New|Trebuchet MS|Century Gothic|Book Antiqua|Lucida Grande|Lucida Sans|Copperplate Gothic Light)&quot;/', "'$1'", $content );
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

add_filter( 'user_phone_label', function ( $label ) {
	return 'Mobile Number </label> <div style="font-weight: 400">Include <code>+</code> and country code.</div><label>';
} );

/**
 * Removes the dimensions from thumbnails outputted by get_the_post_thumbnail()
 *
 * @param $html
 *
 * @return array|string|string[]|null
 */
function remove_thumbnail_dimensions( $html ) {
	return preg_replace( '/(width|height)=\"\d*\"\s/', "", $html );
}

/**
 * Pass back a new local time for the contact details card
 *
 * @param array $response
 * @param array $data
 * @param       $screen_id
 *
 * @return array
 */
function maybe_refresh_local_time( array $response, array $data, $screen_id ) {

	if ( ! isset_not_empty( $data, 'groundhogg-refresh-local-time' ) ) {
		return $response;
	}

	$contact_id = absint( $data['groundhogg-refresh-local-time'] );
	$contact    = new Contact( $contact_id );

	if ( ! $contact->exists() ) {
		return $response;
	}

	$today   = new DateTimeHelper();
	$local   = new DateTimeHelper( 'now', $contact->get_time_zone( false ) );
	$display = $today->wpDateFormat() === $local->wpDateFormat() ? $local->wpTimeFormat() : $local->wpDateTimeFormat();

	$display = html()->e( 'abbr', [ 'title' => $local->wpDateTimeFormat() ], $display );

	$response['groundhogg-refresh-local-time'] = [ 'local_time' => $display ];

	return $response;
}

add_filter( 'heartbeat_received', __NAMESPACE__ . '\maybe_refresh_local_time', 10, 3 );
