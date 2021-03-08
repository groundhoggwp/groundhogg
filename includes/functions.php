<?php

namespace Groundhogg;

use Groundhogg\Classes\Activity;
use Groundhogg\Lib\Mobile\Iso3166;
use Groundhogg\Lib\Mobile\Mobile_Validator;
use Groundhogg\Queue\Event_Queue;
use WP_Error;
use function foo\func;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Wrapper function
 *
 * @return false|Contact
 */
function get_current_contact() {
	return get_contactdata();
}

/**
 * Wrapper function for Utils function.
 *
 * @param $contact_id_or_email
 * @param $by_user_id
 *
 * @return false|Contact
 */
function get_contactdata( $contact_id_or_email = false, $by_user_id = false ) {
	if ( ! $contact_id_or_email ) {

		if ( Event_Queue::is_processing() ) {
			return Plugin::instance()->event_queue->get_current_contact();
		}

		return Plugin::$instance->tracking->get_current_contact();
	}

	$contact = new Contact( $contact_id_or_email, $by_user_id );

	if ( $contact->exists() ) {
		return $contact;
	}

	return false;
}

/**
 * Check whether the current use is the given role.
 *
 * @param string $role
 *
 * @return bool
 */
function current_user_is( $role = 'subscriber' ) {
	if ( is_user_logged_in() ) {
		$user  = wp_get_current_user();
		$roles = ( array ) $user->roles;

		return in_array( $role, $roles );
	} else {
		return false;
	}
}

/**
 * Internal URL builder.
 *
 * @param string $page
 * @param array  $args
 *
 * @return string
 */
function groundhogg_url( $page = '', $args = [] ) {
	return Plugin::$instance->admin->get_page( $page )->admin_url( $args );
}

/**
 * Easier url builder.
 *
 * @param array|string $page
 * @param array|string $args
 * @param string $fragment
 *
 * @return string
 */
function admin_page_url( $page, $args = [], $fragment = '' ) {

    if ( is_array( $page ) ){
        $url = admin_page_url( get_url_var( 'page' ), $page );

        if ( is_string( $args ) ){
	        $url .= '#' . $args;
        }
    } else {
	    $args = wp_parse_args( $args, [ 'page' => $page ] );
	    $url  = add_query_arg( $args, admin_url( 'admin.php' ) );

	    if ( $fragment ) {
		    $url .= '#' . $fragment;
	    }
    }

	return $url;
}

/**
 * Provides a modal link URL
 *
 * @param $args
 *
 * @return string
 */
function modal_link_url( $args ) {

	$atts = wp_parse_args( $args, array(
		'title'              => 'Modal',
		'footer_button_text' => __( 'Save Changes' ),
		'source'             => '',
		'height'             => 500,
		'width'              => 500,
		'footer'             => 'true',
		'preventSave'        => 'true',
	) );

	enqueue_groundhogg_modal();

	return sprintf( "#source=%s&footer=%s&width=%d&height=%d&footertext=%s&preventSave=%s",
		urlencode( $atts['source'] ),
		esc_attr( $atts['footer'] ),
		intval( $atts['width'] ),
		intval( $atts['height'] ),
		urlencode( $atts['footer_button_text'] ),
		esc_attr( $atts['preventSave'] ) );
}

/**
 * Similar to wp_list_pluck in that we take the ID and the title and match them up.
 *
 * @param array  $data      array[]
 * @param string $id_col    string
 * @param string $title_col string
 *
 * @return array
 */
function parse_select2_results( $data = [], $id_col = 'ID', $title_col = 'title' ) {
	$ids     = wp_parse_id_list( wp_list_pluck( $data, $id_col ) );
	$names   = wp_list_pluck( $data, $title_col );
	$results = array_combine( $ids, $names );

	return $results;
}

/**
 * Get DB
 *
 * @param $name
 *
 * @return DB\DB|DB\Meta_DB|DB\Tags
 */
function get_db( $name ) {
	return Plugin::$instance->dbs->get_db( $name );
}

/**
 * Can the dbs be used?
 *
 * @return bool
 */
function are_dbs_initialised() {
	return Plugin::$instance->dbs->is_initialized();
}

/**
 * Emergency initialize the dbs.
 */
function emergency_init_dbs() {
	Plugin::$instance->dbs->init_dbs();
}

/**
 * Wrapper
 *
 * @param string $option
 *
 * @return bool
 */
function is_option_enabled( $option = '' ) {
	$option = get_option( $option );

	if ( ! is_array( $option ) && $option ) {
		return true;
	}

	/**
	 * Whether the option is enabled or not.
	 *
	 * @param $enabled bool
	 * @param $option  string
	 */
	return apply_filters( 'groundhogg/io_option_enabled', is_array( $option ) && in_array( 'on', $option ), $option );
}

/**
 * Shorthand;
 *
 * @return HTML
 */
function html() {
	return Plugin::$instance->utils->html;
}

/**
 * Shorthand
 *
 * @return Notices
 */
function notices() {
	return Plugin::$instance->notices;
}

/**
 * @return Tracking
 */
function tracking() {
	return Plugin::$instance->tracking;
}

/**
 * @return Utils
 */
function utils(){
    return Plugin::$instance->utils;
}

/**
 * @return Files
 */
function files(){
	return utils()->files;
}

/**
 * @return Event_Queue
 */
function event_queue() {
	return Plugin::instance()->event_queue;
}

/**
 * Return if a value in an array isset and is not empty
 *
 * @param $array
 * @param $key
 *
 * @return bool
 */
function isset_not_empty( $array, $key = '' ) {
	if ( is_object( $array ) ) {
		return isset( $array->$key ) && ! empty( $array->$key );
	} else if ( is_array( $array ) ) {
		return isset( $array[ $key ] ) && ! empty( $array[ $key ] );
	}

	return false;
}

/**
 * Get a variable from the $_REQUEST global
 *
 * @param string $key
 * @param bool   $default
 * @param bool   $post_only
 *
 * @return mixed
 */
function get_request_var( $key = '', $default = false, $post_only = false ) {
	$global = $post_only ? $_POST : $_REQUEST;

	return wp_unslash( get_array_var( $global, $key, $default ) );
}

/**
 * Set the $_REQUEST param
 *
 * @param $key
 * @param $value
 */
function set_request_var( $key, $value ) {
	$_REQUEST[ $key ] = $value;
}

/**
 * Get a variable from the $_POST global
 *
 * @param string $key
 * @param bool   $default
 *
 * @return mixed
 */
function get_post_var( $key = '', $default = false ) {
	return wp_unslash( get_array_var( $_POST, $key, $default ) );
}

/**
 * Get a variable from the $_GET global
 *
 * @param string $key
 * @param bool   $default
 *
 * @return mixed
 */
function get_url_var( $key = '', $default = false ) {
	return urldecode_deep( get_array_var( $_GET, $key, $default ) );
}

/**
 * Get a variable from the $_GET global
 *
 * @param string $key
 * @param bool   $default
 *
 * @return mixed
 */
function get_url_param( $key = '', $default = false ) {
	return get_url_var( $key, $default );
}

/**
 * Get a db query from the URL.
 *
 * @param array $default       a default query if the given is empty
 * @param array $force         for the query to include the given
 * @param array $accepted_keys for the query to include the given
 *
 * @return array|string
 */
function get_request_query( $default = [], $force = [], $accepted_keys = [] ) {
	$query = $_GET;

	$ignore = apply_filters( 'groundhogg/get_request_query/ignore', [
		'page',
		'paged',
		'ids',
		'tab',
		'view',
		'action',
		'bulk_action',
		'_wpnonce',
		'submit'
	] );

	foreach ( $ignore as $key ) {
		unset( $query[ $key ] );
	}

	$query = urldecode_deep( $query );

	if ( $search = get_request_var( 's' ) ) {
		$query['search'] = $search;
	}

	$query = array_merge( $query, $force );
	$query = wp_parse_args( $query, $default );

	if ( ! empty( $accepted_keys ) ) {

		$new_query = [];

		foreach ( $accepted_keys as $key ) {
			$val               = get_array_var( $query, $key );
			$new_query[ $key ] = $val;
		}

		$query = $new_query;
	}

	$query = map_deep( $query, 'sanitize_text_field' );

	return wp_unslash( array_filter( $query ) );
}

/**
 * Ensures an array
 *
 * @param $array
 *
 * @return array
 */
function ensure_array( $array ) {
	if ( is_array( $array ) ) {
		return $array;
	}

	return [ $array ];
}

/**
 * Wrapper for validating tags...
 *
 * @param $maybe_tags
 *
 * @return array
 */
function validate_tags( $maybe_tags ) {
	return get_db( 'tags' )->validate( $maybe_tags );
}

/**
 * Replacements Wrapper.
 *
 * @param string      $content
 * @param int|Contact $contact_id
 *
 * @return string
 */
function do_replacements( $content = '', $contact_id = 0 ) {
	return Plugin::$instance->replacements->process( $content, $contact_id );
}

/**
 * Encrypt a string.
 *
 * @param $data
 *
 * @return bool|string
 */
function encrypt( $data ) {
	return Plugin::$instance->utils->encrypt_decrypt( $data, 'e' );
}

/**
 * If WordPress is executing a REST request
 *
 * @return bool
 */
function doing_rest() {
	return ( defined( 'REST_REQUEST' ) && REST_REQUEST );
}

/**
 * Decrypt a string
 *
 * @param $data
 *
 * @return bool|string
 */
function decrypt( $data ) {
	return Plugin::$instance->utils->encrypt_decrypt( $data, 'd' );
}

/**
 * Get a variable from an array or default if it doesn't exist.
 *
 * @param        $array
 * @param string $key
 * @param bool   $default
 *
 * @return mixed
 */
function get_array_var( $array, $key = '', $default = false ) {
	if ( isset_not_empty( $array, $key ) ) {
		if ( is_object( $array ) ) {
			return $array->$key;
		} else if ( is_array( $array ) ) {
			return $array[ $key ];
		}
	}

	return $default;
}

/**
 * convert a key to words.
 *
 * @param $key
 *
 * @return string
 */
function key_to_words( $key ) {
	return ucwords( preg_replace( '/[-_]/', ' ', $key ) );
}

/**
 * Term parser helper.
 *
 * @param $term
 *
 * @return array
 */
function get_terms_for_select( $term ) {
	$terms   = get_terms( $term );
	$options = [];

	foreach ( $terms as $term ) {
		$options[ absint( $term->term_id ) ] = esc_html( $term->name );
	}

	return $options;
}

/**
 * @param $post_type string|array
 *
 * @return array
 */
function get_posts_for_select( $post_type ) {
	$posts = get_posts( array(
		'post_type'   => $post_type,
		'post_status' => 'publish',
		'numberposts' => - 1
	) );

	$options = [];

	foreach ( $posts as $i => $post ) {
		$options[ $post->ID ] = $post->post_title;
	}

	return $options;
}

/**
 * Convert words to a key
 *
 * @param $words
 *
 * @return string
 */
function words_to_key( $words ) {
	return sanitize_key( str_replace( ' ', '_', $words ) );
}

/**
 * Return the percentage to the second degree.
 *
 * @param     $a
 * @param     $b
 *
 * @param int $precision
 *
 * @return float
 */
function percentage( $a, $b, $precision = 2 ) {
	$a = intval( $a );
	$b = intval( $b );

	if ( ! $a ) {
		return 0;
	}

	return round( ( $b / $a ) * 100, $precision );
}

function sort_by_string_in_array( $key ) {
	return function ( $a, $b ) use ( $key ) {
		return strnatcmp( get_array_var( $a, $key ), get_array_var( $b, $key ) );
	};
}

/**
 * If the JSON is your typical error response
 *
 * @param $json
 *
 * @return bool
 */
function is_json_error( $json ) {
	return isset_not_empty( $json, 'code' ) && isset_not_empty( $json, 'message' ) && get_array_var( $json, 'code' ) !== 'success';
}

/**
 * Convert JSON to a WP_Error
 *
 * @param $json
 *
 * @return bool|WP_Error
 */
function get_json_error( $json ) {
	if ( is_json_error( $json ) ) {
		return new WP_Error( get_array_var( $json, 'code' ), get_array_var( $json, 'message' ), get_array_var( $json, 'data' ) );
	}

	return false;
}

/**
 * Normalize multiple files.
 *
 * @param $files
 *
 * @return array
 */
function normalize_files( &$files ) {
	$_files       = [];
	$_files_count = count( $files['name'] );
	$_files_keys  = array_keys( $files );

	for ( $i = 0; $i < $_files_count; $i ++ ) {
		foreach ( $_files_keys as $key ) {
			$_files[ $i ][ $key ] = $files[ $key ][ $i ];
		}
	}

	return $_files;
}

/**
 * Dequeue Theme styles for compatibility
 */
function dequeue_theme_css_compat() {
	$theme_name = basename( get_stylesheet_directory() );

	// Dequeue Theme Support.
	wp_dequeue_style( $theme_name . '-style' );
	wp_dequeue_style( $theme_name );
	wp_dequeue_style( 'style' );

	$wp_styles  = wp_styles();
	$themes_uri = get_theme_root_uri();

	foreach ( $wp_styles->registered as $wp_style ) {
		if ( strpos( $wp_style->src, $themes_uri ) !== false ) {
			wp_dequeue_style( $wp_style->handle );
		}
	}

	// Additional compat
	wp_dequeue_style( 'fusion-dynamic-css' );
}

/**
 * Dequeue WooCommerce style for compatibility
 */
function dequeue_wc_css_compat() {
	global $wp_styles;
	$maybe_dequeue = $wp_styles->queue;
	foreach ( $maybe_dequeue as $style ) {
		if ( strpos( $style, 'woocommerce' ) !== false ) {
			wp_dequeue_style( $style );
		}
	}
}

/**
 * Enqueue any iframe compat scripts
 */
function iframe_compat() {
	wp_enqueue_script( 'groundhogg-admin-iframe' );
	wp_enqueue_style( 'groundhogg-admin-iframe' );
}

/**
 * Enqueues the modal scripts
 *
 * @since 1.0.5
 * @return Modal
 *
 */
function enqueue_groundhogg_modal() {
	return Modal::instance();
}

/**
 * Replace any other domain name with the one of the website.
 *
 * @param $string
 *
 * @return string|string[]|null
 */
function search_and_replace_domain( $string ) {
	return preg_replace( '#https?:\/\/[^\\/\s]+#', home_url(), $string );
}

/**
 * Convert array to HTML tag attributes
 *
 * @param $atts
 *
 * @return string
 */
function array_to_atts( $atts ) {
	$tag = '';

	foreach ( $atts as $key => $value ) {

		if ( empty( $value ) ) {
			continue;
		}

		$key = strtolower( $key );

		switch ( $key ) {
			case 'style':
				$value = array_to_css( $value );
				break;
			case 'href':
			case 'action':
			case 'src':
				$value = esc_url( $value );
				break;
			default:
				if ( is_array( $value ) ) {
					$value = implode( ' ', $value );
				}

				$value = esc_attr( $value );
				break;

		}

		$tag .= sanitize_key( $key ) . '="' . $value . '" ';
	}

	return $tag;
}

/**
 * Convert array to CSS style attributes
 *
 * @param $atts
 *
 * @return string
 */
function array_to_css( $atts ) {

	if ( ! is_array( $atts ) ) {
		return $atts;
	}

	$css = '';
	foreach ( $atts as $key => $value ) {

		if ( is_array( $value ) ) {
			$value = implode( ' ', $value );
		}

		$css .= sanitize_key( $key ) . ':' . esc_attr( $value ) . ';';
	}

	return $css;
}

/**
 * Get a cookie value
 *
 * @param string $cookie
 * @param bool   $default
 *
 * @return mixed
 */
function get_cookie( $cookie = '', $default = false ) {
	return get_array_var( $_COOKIE, $cookie, $default );
}

/**
 * Set a cookie the WP way
 *
 * @param string $cookie
 * @param mixed  $value
 * @param int    $expiration
 *
 * @return bool
 */
function set_cookie( $cookie = '', $value = '', $expiration = 3600 ) {
	return setcookie( $cookie, $value, time() + $expiration, COOKIEPATH, COOKIE_DOMAIN );
}

/**
 * Delete a cookie
 *
 * @param string $cookie
 *
 * @return bool
 */
function delete_cookie( $cookie = '' ) {
	unset( $_COOKIE[ $cookie ] );

	// empty value and expiration one hour before
	return setcookie( $cookie, '', time() - 3600 );
}

/**
 * Get the default from name
 *
 * @return string
 */
function get_default_from_name() {
	$from = get_option( 'gh_override_from_name' );

	if ( empty( $from ) ) {
		$from = get_bloginfo( 'name' );
	}

	return apply_filters( 'groundhogg/get_default_from_name', $from );
}

/**
 * Get the default from email
 *
 * @return string
 */
function get_default_from_email() {
	$from = get_option( 'gh_override_from_email' );

	if ( empty( $from ) ) {
		$from = get_bloginfo( 'admin_email' );
	}

	return apply_filters( 'groundhogg/get_default_from_email', $from );
}

/**
 * Get the return path email
 *
 * @return mixed|void
 */
function get_return_path_email() {
	$return = get_option( 'gh_bounce_inbox' );

	return apply_filters( 'groundhogg/get_return_path_email', $return );
}


/**
 * Overwrite the regular WP_Mail with an identical function but use our modified PHPMailer class instead
 * which sends the email to the Groundhogg Sending Service.
 *
 * @throws \Exception
 *
 * @since      1.2.10
 * @deprecated 2.1.11
 *
 * @param string|array $to          Array or comma-separated list of email addresses to send message.
 *
 * @param string       $subject     Email subject
 *
 * @param string       $message     Message contents
 *
 * @param string|array $headers     Optional. Additional headers.
 *
 * @param string|array $attachments Optional. Files to attach.
 *
 * @return bool Whether the email contents were sent successfully.
 */
function gh_ss_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
	// Compact the input, apply the filters, and extract them back out

	/**
	 * Filters the wp_mail() arguments.
	 *
	 * @since 2.2.0
	 *
	 * @param array $args A compacted array of wp_mail() arguments, including the "to" email,
	 *                    subject, message, headers, and attachments values.
	 *
	 */
	$atts = apply_filters( 'wp_mail', compact( 'to', 'subject', 'message', 'headers', 'attachments' ) );

	if ( isset( $atts['to'] ) ) {
		$to = $atts['to'];
	}

	if ( ! is_array( $to ) ) {
		$to = explode( ',', $to );
	}

	if ( isset( $atts['subject'] ) ) {
		$subject = $atts['subject'];
	}

	if ( isset( $atts['message'] ) ) {
		$message = $atts['message'];
	}

	if ( isset( $atts['headers'] ) ) {
		$headers = $atts['headers'];
	}

	if ( isset( $atts['attachments'] ) ) {
		$attachments = $atts['attachments'];
	}

	if ( ! is_array( $attachments ) ) {
		$attachments = explode( "\n", str_replace( "\r\n", "\n", $attachments ) );
	}

	global $phpmailer;

	/* Use the GH SS Mailer class instead */
	if ( ! ( $phpmailer instanceof GH_SS_Mailer ) ) {
//        require_once dirname(__FILE__) . '/gh-ss-mailer.php';
		$phpmailer = new GH_SS_Mailer( true );
	}

	// Headers
	$cc = $bcc = $reply_to = array();

	if ( empty( $headers ) ) {
		$headers = array();
	} else {
		if ( ! is_array( $headers ) ) {
			// Explode the headers out, so this function can take both
			// string headers and an array of headers.
			$tempheaders = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
		} else {
			$tempheaders = $headers;
		}
		$headers = array();

		// If it's actually got contents
		if ( ! empty( $tempheaders ) ) {
			// Iterate through the raw headers
			foreach ( (array) $tempheaders as $header ) {
				if ( strpos( $header, ':' ) === false ) {
					if ( false !== stripos( $header, 'boundary=' ) ) {
						$parts    = preg_split( '/boundary=/i', trim( $header ) );
						$boundary = trim( str_replace( array( "'", '"' ), '', $parts[1] ) );
					}
					continue;
				}
				// Explode them out
				list( $name, $content ) = explode( ':', trim( $header ), 2 );

				// Cleanup crew
				$name    = trim( $name );
				$content = trim( $content );

				switch ( strtolower( $name ) ) {
					// Mainly for legacy -- process a From: header if it's there
					case 'from':
						$bracket_pos = strpos( $content, '<' );
						if ( $bracket_pos !== false ) {
							// Text before the bracketed email is the "From" name.
							if ( $bracket_pos > 0 ) {
								$from_name = substr( $content, 0, $bracket_pos - 1 );
								$from_name = str_replace( '"', '', $from_name );
								$from_name = trim( $from_name );
							}

							$from_email = substr( $content, $bracket_pos + 1 );
							$from_email = str_replace( '>', '', $from_email );
							$from_email = trim( $from_email );

							// Avoid setting an empty $from_email.
						} else if ( '' !== trim( $content ) ) {
							$from_email = trim( $content );
						}
						break;
					case 'mime-version':
						// Ensure mime-version does not survive do avoid duplicate header.
						break;
					case 'content-type':
						if ( strpos( $content, ';' ) !== false ) {
							list( $type, $charset_content ) = explode( ';', $content );
							$content_type = trim( $type );
							if ( false !== stripos( $charset_content, 'charset=' ) ) {
								$charset = trim( str_replace( array( 'charset=', '"' ), '', $charset_content ) );
							} else if ( false !== stripos( $charset_content, 'boundary=' ) ) {
								$boundary = trim( str_replace( array(
									'BOUNDARY=',
									'boundary=',
									'"'
								), '', $charset_content ) );
								$charset  = '';
							}

							// Avoid setting an empty $content_type.
						} else if ( '' !== trim( $content ) ) {
							$content_type = trim( $content );
						}
						break;
					case 'cc':
						$cc = array_merge( (array) $cc, explode( ',', $content ) );
						break;
					case 'bcc':
						$bcc = array_merge( (array) $bcc, explode( ',', $content ) );
						break;
					case 'reply-to':
						$reply_to = array_merge( (array) $reply_to, explode( ',', $content ) );
						break;
					default:
						// Add it to our grand headers array
						$headers[ trim( $name ) ] = trim( $content );
						break;
				}
			}
		}
	}

	// Empty out the values that may be set
	$phpmailer->clearAllRecipients();
	$phpmailer->clearAttachments();
	$phpmailer->clearCustomHeaders();
	$phpmailer->clearReplyTos();
	$phpmailer->AltBody = null;

	// From email and name
	// If we don't have a name from the input headers
	if ( ! isset( $from_name ) ) {
		$from_name = 'WordPress';
	}

	/* If we don't have an email from the input headers default to wordpress@$sitename
     * Some hosts will block outgoing mail from this address if it doesn't exist but
     * there's no easy alternative. Defaulting to admin_email might appear to be another
     * option but some hosts may refuse to relay mail from an unknown domain. See
     * https://core.trac.wordpress.org/ticket/5007.
     */

	if ( ! isset( $from_email ) ) {
		// Get the site domain and get rid of www.
		$sitename = strtolower( $_SERVER['SERVER_NAME'] );
		if ( substr( $sitename, 0, 4 ) == 'www.' ) {
			$sitename = substr( $sitename, 4 );
		}

		$from_email = 'wordpress@' . $sitename;
	}

	/**
	 * Filters the email address to send from.
	 *
	 * @since 2.2.0
	 *
	 * @param string $from_email Email address to send from.
	 *
	 */
	$from_email = apply_filters( 'wp_mail_from', $from_email );

	/**
	 * Filters the name to associate with the "from" email address.
	 *
	 * @since 2.3.0
	 *
	 * @param string $from_name Name associated with the "from" email address.
	 *
	 */
	$from_name = apply_filters( 'wp_mail_from_name', $from_name );

	try {
		$phpmailer->setFrom( $from_email, $from_name, false );
	} catch ( \phpmailerException $e ) {
		$mail_error_data                             = compact( 'to', 'subject', 'message', 'headers', 'attachments' );
		$mail_error_data['set_from_name']            = $from_name;
		$mail_error_data['set_from_email']           = $from_email;
		$mail_error_data['phpmailer_exception_code'] = $e->getCode();

		/** This filter is documented in wp-includes/pluggable.php */
		do_action( 'wp_mail_failed', new WP_Error( 'wp_mail_failed', $e->getMessage(), $mail_error_data ) );

		return false;
	}

	// Set destination addresses, using appropriate methods for handling addresses
	$address_headers = compact( 'to', 'cc', 'bcc', 'reply_to' );

	foreach ( $address_headers as $address_header => $addresses ) {
		if ( empty( $addresses ) ) {
			continue;
		}

		foreach ( (array) $addresses as $address ) {
			try {
				// Break $recipient into name and address parts if in the format "Foo <bar@baz.com>"
				$recipient_name = '';

				if ( preg_match( '/(.*)<(.+)>/', $address, $matches ) ) {
					if ( count( $matches ) == 3 ) {
						$recipient_name = $matches[1];
						$address        = $matches[2];
					}
				}

				switch ( $address_header ) {
					case 'to':
						$phpmailer->addAddress( $address, $recipient_name );
						break;
					case 'cc':
						$phpmailer->addCc( $address, $recipient_name );
						break;
					case 'bcc':
						$phpmailer->addBcc( $address, $recipient_name );
						break;
					case 'reply_to':
						$phpmailer->addReplyTo( $address, $recipient_name );
						break;
				}
			} catch ( \phpmailerException $e ) {
				continue;
			}
		}
	}

	// Set Content-Type and charset
	// If we don't have a content-type from the input headers
	if ( ! isset( $content_type ) || empty( $content_type ) ) {
		$content_type = 'text/plain';
	}

	/**
	 * Filters the wp_mail() content type.
	 *
	 * @since 2.3.0
	 *
	 * @param string $content_type Default wp_mail() content type.
	 *
	 */
	$content_type = apply_filters( 'wp_mail_content_type', $content_type );

	$phpmailer->ContentType = $content_type;

	// Set the content-type and charset
	// Set whether it's plaintext, depending on $content_type
	// GHSS can only send HTML emails apparently. So convert all emails to HTML
	if ( 'text/html' == $content_type ) {
		$phpmailer->isHTML( true );
	}

	// Set mail's subject and body
	$phpmailer->Subject = $subject;
	$phpmailer->Body    = $message;

	// If we don't have a charset from the input headers
	if ( ! isset( $charset ) ) {
		$charset = get_bloginfo( 'charset' );
	}


	/**
	 * Filters the default wp_mail() charset.
	 *
	 * @since 2.3.0
	 *
	 * @param string $charset Default email charset.
	 *
	 */
	$phpmailer->CharSet = apply_filters( 'wp_mail_charset', $charset );

	// Set custom headers
	if ( ! empty( $headers ) ) {
		foreach ( (array) $headers as $name => $content ) {
			$phpmailer->addCustomHeader( sprintf( '%1$s: %2$s', $name, $content ) );
		}

		if ( false !== stripos( $content_type, 'multipart' ) && ! empty( $boundary ) ) {
			$phpmailer->addCustomHeader( sprintf( "Content-Type: %s;\n\t boundary=\"%s\"", $content_type, $boundary ) );
		}
	}

	if ( ! empty( $attachments ) ) {
		foreach ( $attachments as $attachment ) {
			try {
				$phpmailer->addAttachment( $attachment );
			} catch ( \phpmailerException $e ) {
				continue;
			}
		}
	}

	/**
	 * Fires after PHPMailer is initialized.
	 *
	 * @since 2.2.0
	 *
	 * @param \PHPMailer $phpmailer The PHPMailer instance (passed by reference).
	 *
	 */
	do_action_ref_array( 'phpmailer_init', array( &$phpmailer ) );

	// Hard set X-Mailer cuz we taking credit for this.
	$phpmailer->XMailer = sprintf( 'Groundhogg %s (https://www.groundhogg.io)', GROUNDHOGG_VERSION );

	if ( $content_type === 'text/html' && empty( $phpmailer->AltBody ) ) {
		$phpmailer->AltBody = wp_strip_all_tags( $message );
	}

	// Send!
	try {

		return $phpmailer->send();

	} catch ( \phpmailerException $e ) {

		$mail_error_data                             = compact( 'to', 'subject', 'message', 'headers', 'attachments' );
		$mail_error_data['phpmailer_exception_code'] = $e->getCode();
		$mail_error_data['mime_message']             = $phpmailer->getSentMIMEMessage();
		$mail_error_data['set_from_name']            = $from_name;
		$mail_error_data['set_from_email']           = $from_email;

		if ( Plugin::$instance->sending_service->has_errors() ) {
			$mail_error_data['orig_error_data']    = Plugin::$instance->sending_service->get_last_error()->get_error_data();
			$mail_error_data['orig_error_message'] = Plugin::$instance->sending_service->get_last_error()->get_error_message();
			$mail_error_data['orig_error_code']    = Plugin::$instance->sending_service->get_last_error()->get_error_code();
		}

		/**
		 * Fires after a phpmailerException is caught.
		 *
		 * @since 4.4.0
		 *
		 * @param WP_Error $error A WP_Error object with the phpmailerException message, and an array
		 *                        containing the mail recipient, subject, message, headers, and attachments.
		 *
		 */
		do_action( 'wp_mail_failed', new WP_Error( 'wp_mail_failed', $e->getMessage(), $mail_error_data ) );

		return false;
	}
}

/**
 * handle a wp_mail_failed event.
 *
 * @param $error WP_Error
 */
function listen_for_complaint_and_bounce_emails( $error ) {
	$data = (array) $error->get_error_data();

	if ( ! isset_not_empty( $data, 'orig_error_data' ) ) {
		return;
	}

	$code = $data['orig_error_code'];
	$data = $data['orig_error_data'];

	if ( $code === 'invalid_recipients' ) {

		/* handle bounces */
		$bounces = isset_not_empty( $data, 'bounces' ) ? $data['bounces'] : [];

		if ( ! empty( $bounces ) ) {
			foreach ( $bounces as $email ) {
				if ( $contact = get_contactdata( $email ) ) {
					$contact->change_marketing_preference( Preferences::HARD_BOUNCE );
				}
			}

		}

		$complaints = isset_not_empty( $data, 'complaints' ) ? $data['complaints'] : [];

		if ( ! empty( $complaints ) ) {
			foreach ( $complaints as $email ) {
				if ( $contact = get_contactdata( $email ) ) {
					$contact->change_marketing_preference( Preferences::COMPLAINED );
				}
			}
		}
	}
}

add_action( 'wp_mail_failed', __NAMESPACE__ . '\listen_for_complaint_and_bounce_emails' );

/**
 * Return the FULL URI from wp_get_referer for string comparisons
 *
 * @return string
 */
function wpgh_get_referer() {
	if ( ! isset( $_POST['_wp_http_referer'] ) ) {
		return wp_get_referer();
	}

	return ( is_ssl() ? "https" : "http" ) . "://{$_SERVER['HTTP_HOST']}" . $_REQUEST['_wp_http_referer'];
}

/**
 * Recount the contacts per tag...
 */
function recount_tag_contacts_count() {
	/* Recount tag relationships */
	$tags = Plugin::$instance->dbs->get_db( 'tags' )->query();

	if ( ! empty( $tags ) ) {
		foreach ( $tags as $tag ) {
			$count = Plugin::$instance->dbs->get_db( 'tag_relationships' )->count( [ 'tag_id' => absint( $tag->tag_id ) ] );
			Plugin::$instance->dbs->get_db( 'tags' )->update( absint( $tag->tag_id ), [ 'contact_count' => $count ] );
		}
	}
}

/**
 * Create a contact quickly from a user account.
 *
 * @param $user      \WP_User|int
 * @param $sync_meta bool whether to copy the meta data over.
 *
 * @return Contact|false|WP_Error the new contact, false on failure, or WP_Error on error
 */
function create_contact_from_user( $user, $sync_meta = false ) {

	if ( is_int( $user ) ) {
		$user = get_userdata( $user );
		if ( ! $user ) {
			return false;
		}
	}

	if ( ! $user instanceof \WP_User ) {
		return false;
	}

	$contact = get_contactdata( $user->ID, true );

	// If not available by user ID try by email
	if ( ! is_a_contact( $contact ) ) {
		$contact = get_contactdata( $user->user_email );
	}

	if ( is_a_contact( $contact ) ) {

		/**
		 * Setup the initial args..
		 */
		$args = array(
			'first_name' => $user->first_name,
			'last_name'  => $user->last_name,
			'email'      => $user->user_email,
			'user_id'    => $user->ID,
		);

		if ( empty( $args['first_name'] ) ) {
			$args['first_name'] = $user->display_name;
		}

		$contact->update( $args );

	} else {

		/**
		 * Setup the initial args..
		 */
		$args = array(
			'first_name'   => $user->first_name,
			'last_name'    => $user->last_name,
			'email'        => $user->user_email,
			'user_id'      => $user->ID,
			'optin_status' => Preferences::UNCONFIRMED
		);

		if ( empty( $args['first_name'] ) ) {
			$args['first_name'] = $user->display_name;
		}

		$contact = new Contact();

		$id = $contact->create( $args );

		if ( ! $id ) {
			return new \WP_Error( 'db_error', __( 'Could not create contact.', 'groundhogg' ) );
		}
	}

	// Save the login
	$contact->update_meta( 'user_login', $user->user_login );

	if ( $sync_meta ) {

		$user_meta = get_user_meta( $user->ID );

		// Note: $values will be an array as single is false
		foreach ( $user_meta as $key => $values ) {

			// Don't sync some keys
			if ( User_Syncing::is_meta_ignored( $key ) ) {
				continue;
			}

			$contact->update_meta( $key, array_shift( $values ) );
		}

	}

	/**
	 * Runs after the contact is created from the create user function
	 *
	 * @param $contact Contact
	 * @param $user    \WP_User
	 */
	do_action( 'groundhogg/create_contact_from_user', $contact, $user );

	return $contact;
}

/**
 * Create a user from a contact
 *
 * @param        $contact       Contact
 * @param string $role          string
 * @param string $notifications string|bool
 *
 * @return int|false
 */
function create_user_from_contact( $contact, $role = 'subscriber', $notifications = 'both' ) {
	// Remove this action to avoid looping when creating a the user
	remove_action( 'user_register', __NAMESPACE__ . '\convert_user_to_contact_when_user_registered' );

	$user_id = wp_insert_user( [
		'user_pass'     => wp_generate_password(),
		'user_email'    => $contact->get_email(),
		'user_login'    => $contact->get_email(),
		'user_nicename' => $contact->get_full_name(),
		'display_name'  => $contact->get_full_name(),
		'first_name'    => $contact->get_first_name(),
		'last_name'     => $contact->get_last_name(),
		'role'          => $role
	] );

	// May need this action, restore it.
	add_action( 'user_register', __NAMESPACE__ . '\convert_user_to_contact_when_user_registered' );

	if ( ! $user_id ) {
		return false;
	}

	if ( $notifications ) {
		wp_send_new_user_notifications( $user_id, $notifications );
	}

	$contact->update( [ 'user_id' => $user_id ] );

	/**
	 * Runs after a user is successfully registered.
	 *
	 * @param $user_id int
	 * @param $contact Contact
	 */
	do_action( 'groundhogg/create_user_from_contact', $user_id, $contact );

	return $user_id;
}

/**
 * Provides a global hook not requireing the benchmark anymore.
 *
 * @param $userId int the Id of the user
 */
function convert_user_to_contact_when_user_registered( $userId ) {
	$user = get_userdata( $userId );

	if ( ! $user || is_wp_error( $user ) ) {
		return;
	}

	$contact = create_contact_from_user( $user, is_option_enabled( 'gh_sync_user_meta' ) );

	if ( ! $contact || is_wp_error( $contact ) ) {
		return;
	}

	// Do not run when in admin or QUEUE is proccessing
	if ( ! is_admin() && ! Event_Queue::is_processing() ) {

		/* register front end which is technically an optin */
		after_form_submit_handler( $contact );
	}

	/**
	 * Provide hook for the Account Created benchmark and other functionality
	 *
	 * @param $user    \WP_User
	 * @param $contact Contact
	 */
	do_action( 'groundhogg/contact_created_from_user', $user, $contact );
}

/**
 * Used for blocks...
 *
 * @return array
 */
function get_form_list() {

	$forms = get_db( 'steps' )->query( [
		'step_type' => 'form_fill'
	] );

	$form_options = array();

	foreach ( $forms as $form ) {
		$step = new Step( $form->ID );
		if ( $step->is_active() ) {
			$form_options[ $form->ID ] = $form->step_title;
		}
	}

	return $form_options;
}


/**
 * Schedule a 1 off email notification
 *
 * @param int        $email_id            the ID of the email to send
 * @param int|string $contact_id_or_email the ID of the contact to send to
 * @param int        $time                time time to send at, defaults to time()
 *
 * @return bool whether the scheduling was successful.
 */
function send_email_notification( $email_id, $contact_id_or_email, $time = 0 ) {

	$contact = Plugin::$instance->utils->get_contact( $contact_id_or_email );
	$email   = Plugin::$instance->utils->get_email( $email_id );

	if ( ! $contact || ! $email ) {
		return false;
	}

	if ( ! $time ) {
		$time = time();
	}

	$event = [
		'time'       => $time,
		'funnel_id'  => 0,
		'step_id'    => $email->get_id(),
		'contact_id' => $contact->get_id(),
		'event_type' => Event::EMAIL_NOTIFICATION,
		'priority'   => 5,
		'status'     => Event::WAITING,
	];

	if ( enqueue_event( $event ) ) {
		return true;
	}

	return false;
}


/**
 * Parse the headers and return things like from/to etc...
 *
 * @param $headers string|string[]
 *
 * @return array|false
 */
function parse_email_headers( $headers ) {
	$headers = is_array( $headers ) ? implode( PHP_EOL, $headers ) : $headers;
	if ( ! is_string( $headers ) ) {
		return false;
	}

	$parsed = imap_rfc822_parse_headers( $headers );

	if ( ! $parsed ) {
		return false;
	}

	$map = [];

	if ( $parsed->sender && ! is_array( $parsed->sender ) ) {
		$map['sender'] = sprintf( '%s@%s', $parsed->sender->mailbox, $parsed->sender->host );
		$map['from']   = $parsed->sender->personal;
	} else if ( is_array( $parsed->sender ) ) {
		$map['sender'] = sprintf( '%s@%s', $parsed->sender[0]->mailbox, $parsed->sender[0]->host );
		$map['from']   = $parsed->sender[0]->personal;
	}

	return $map;
}

/**
 * AWS Doesn't like special chars in the from name so we'll strip them out here.
 *
 * @param $name
 *
 * @return string
 */
function sanitize_from_name( $name ) {
	return sanitize_text_field( preg_replace( '/[^A-z0-9 ]/', '', $name ) );
}

/**
 * This function is for use by any form or eccom extensions which is essentially a copy of the PROCESS method in the submission handler.
 *
 * @param $contact Contact
 */
function after_form_submit_handler( &$contact ) {
	if ( ! $contact instanceof Contact ) {
		return;
	}

	if ( $contact->update_meta( 'ip_address', Plugin::$instance->utils->location->get_real_ip() ) ) {
		$contact->extrapolate_location();
	}

	if ( ! $contact->get_meta( 'lead_source' ) ) {
		$contact->update_meta( 'lead_source', Plugin::$instance->tracking->get_leadsource() );
	}

	if ( ! $contact->get_meta( 'source_page' ) ) {
		$contact->update_meta( 'source_page', wpgh_get_referer() );
	}

	if ( ! $contact->is_marketable() ) {
		$contact->change_marketing_preference( Preferences::UNCONFIRMED );
	}

	$contact->update_meta( 'last_optin', time() );

	/**
	 * Helper function.
	 *
	 * @param $contact Contact
	 */
	do_action( 'groundhogg/after_form_submit', $contact );
}

/**
 * Whether the given email address has the same hostname as the current site.
 *
 * @param $email
 *
 * @return bool
 */
function email_is_same_domain( $email ) {
	$email_domain = substr( $email, strrpos( $email, '@' ) + 1 );
	$site_domain  = home_url();
	$is_same      = strpos( $site_domain, $email_domain ) !== false;

	return apply_filters( 'groundhogg/email_is_same_domain', $is_same, $email, $site_domain );
}

/**
 * Send event failure notification.
 *
 * @param $event Event
 */
function send_event_failure_notification( $event ) {
	if ( ! is_option_enabled( 'gh_send_notifications_on_event_failure' ) || get_transient( 'gh_hold_failed_event_notification' ) ) {
		return;
	}

	$subject = sprintf( "Event (%s) failed for %s on %s", $event->get_step_title(), $event->get_contact()->get_email(), esc_html( get_bloginfo( 'title' ) ) );
	$message = sprintf( "This is to let you know that an event \"%s\" in funnel \"%s\" has failed for \"%s (%s)\"", $event->get_step_title(), $event->get_funnel_title(), $event->get_contact()->get_full_name(), $event->get_contact()->get_email() );
	$message .= sprintf( "\nFailure Reason: %s", $event->get_failure_reason() );
	$message .= sprintf( "\nManage Failed Events: %s", admin_url( 'admin.php?page=gh_events&view=status&status=failed' ) );
	$to      = Plugin::$instance->settings->get_option( 'event_failure_notification_email', get_option( 'admin_email' ) );

	do_action( 'groundhogg/send_event_failure_notification/before' );

	if ( \Groundhogg_Email_Services::send_wordpress( $to, $subject, $message ) ) {
		set_transient( 'gh_hold_failed_event_notification', true, MINUTE_IN_SECONDS );
	}

	do_action( 'groundhogg/send_event_failure_notification/after' );
}

add_action( 'groundhogg/event/failed', __NAMESPACE__ . '\send_event_failure_notification' );


/**
 * Split a name into first and last.
 *
 * @param $name
 *
 * @return array
 */
function split_name( $name ) {
	$name       = trim( $name );
	$last_name  = ( strpos( $name, ' ' ) === false ) ? '' : preg_replace( '#.*\s([\w-]*)$#', '$1', $name );
	$first_name = trim( preg_replace( '#' . $last_name . '#', '', $name ) );

	return array( $first_name, $last_name );
}

/**
 * Detect the CSV delimiter of a CSV file.
 *
 * @param $file_path
 *
 * @return string
 */
function get_csv_delimiter( $file_path ) {

	$handle = fopen( $file_path, 'r' );

	if ( ! $handle ) {
		return ',';
	}

	$delimiters = [ "\t", ";", "|", "," ];
	$data_1     = [];
	$data_2     = [];
	$delimiter  = $delimiters[0];

	foreach ( $delimiters as $d ) {
		$data_1 = fgetcsv( $handle, 4096, $d );

		if ( count( $data_1 ) > count( $data_2 ) ) {
			$delimiter = $d;
			$data_2    = $data_1;
		}

		rewind( $handle );
	}

	fclose( $handle );

	return $delimiter;

}

/**
 * Get a list of items from a file path, if file does not exist of there are no items return an empty array.
 *
 * @param string $file_path
 *
 * @param bool   $delimiter
 *
 * @return array
 */
function get_items_from_csv( $file_path = '', $delimiter = false ) {

	if ( ! file_exists( $file_path ) ) {
		return [];
	}

	// If a delimiter is not provided, make a guess.
	if ( ! $delimiter ) {
		$delimiter = get_csv_delimiter( $file_path ) ?: ',';
	}

	$header       = null;
	$header_count = 0;
	$data         = array();

	if ( ( $handle = fopen( $file_path, 'r' ) ) !== false ) {
		while ( ( $row = fgetcsv( $handle, 0, $delimiter ) ) !== false ) {
			if ( ! $header ) {
				$header       = $row;
				$header_count = count( $header );
			} else {

				if ( count( $row ) > $header_count ) {

					$row = array_slice( $row, 0, $header_count );
				} else if ( count( $row ) < $header_count ) {

					$row = array_pad( $row, $header_count, '' );
				}

				$data[] = array_combine( $header, $row );
			}
		}
		fclose( $handle );
	}

	return $data;

}

/**
 * Get the pretty name for the header in the export file
 *
 * @param string $key
 *
 * @return mixed|string
 */
function export_header_pretty_name( $key = '' ) {
	static $keys;

	if ( empty( $keys ) ) {
		$keys = get_exportable_fields();
	}

	if ( isset_not_empty( $keys, $key ) ) {
		return $keys[ $key ];
	}

	return key_to_words( $key );
}

/**
 * Get a list of mappable fields as well as extra fields
 *
 * @param array $extra
 *
 * @return array
 */
function get_exportable_fields( $extra = [] ) {

	$defaults = [
		'ID'                        => __( 'Contact ID', 'groundhogg' ),
		'full_name'                 => __( 'Full Name', 'groundhogg' ),
		'first_name'                => __( 'First Name', 'groundhogg' ),
		'last_name'                 => __( 'Last Name', 'groundhogg' ),
		'email'                     => __( 'Email Address', 'groundhogg' ),
		'optin_status'              => __( 'Optin Status', 'groundhogg' ),
		'user_id'                   => __( 'User Id', 'groundhogg' ),
		'owner_id'                  => __( 'Owner Id', 'groundhogg' ),
		'date_created'              => __( 'Date Created', 'groundhogg' ),
		'date_optin_status_changed' => __( 'Date Optin Status Changed', 'groundhogg' ),
		'birthday'                  => __( 'Birthday', 'groundhogg' ),
		'mobile_phone'              => __( 'Mobile Phone Number', 'groundhogg' ),
		'primary_phone'             => __( 'Primary Phone Number', 'groundhogg' ),
		'primary_phone_extension'   => __( 'Primary Phone Number Extension', 'groundhogg' ),
		'company_phone'             => __( 'Company Phone Number', 'groundhogg' ),
		'company_phone_extension'   => __( 'Company Phone Number Extension', 'groundhogg' ),
		'street_address_1'          => __( 'Street Address 1', 'groundhogg' ),
		'street_address_2'          => __( 'Street Address 2', 'groundhogg' ),
		'city'                      => __( 'City', 'groundhogg' ),
		'postal_zip'                => __( 'Postal/Zip', 'groundhogg' ),
		'region'                    => __( 'Province/State/Region', 'groundhogg' ),
		'country'                   => __( 'Country', 'groundhogg' ),
		'company_name'              => __( 'Company Name', 'groundhogg' ),
		'company_address'           => __( 'Full Company Address', 'groundhogg' ),
		'job_title'                 => __( 'Job Title', 'groundhogg' ),
		'time_zone'                 => __( 'Time Zone', 'groundhogg' ),
		'ip_address'                => __( 'IP Address', 'groundhogg' ),
		'lead_source'               => __( 'Lead Source', 'groundhogg' ),
		'source_page'               => __( 'Source Page', 'groundhogg' ),
		'terms_agreement'           => __( 'Terms Agreement', 'groundhogg' ),
		'gdpr_consent'              => __( 'Data Processing Consent', 'groundhogg' ),
		'gdpr_consent_date'         => __( 'Data Processing Consent Data', 'groundhogg' ),
		'marketing_consent'         => __( 'Marketing Consent', 'groundhogg' ),
		'marketing_consent_date'    => __( 'Marketing Consent Date', 'groundhogg' ),
//		'notes'                     => __( 'Notes', 'groundhogg ),
		'tags'                      => __( 'Tags', 'groundhogg' ),
		'utm_campaign'              => __( 'UTM Campaign', 'groundhogg' ),
		'utm_content'               => __( 'UTM Content', 'groundhogg' ),
		'utm_medium'                => __( 'UTM Medium', 'groundhogg' ),
		'utm_term'                  => __( 'UTM Term', 'groundhogg' ),
		'utm_source'                => __( 'UTM Source', 'groundhogg' ),
	];

	$fields = array_merge( $defaults, $extra );

	return apply_filters( 'groundhogg/exportable_fields', $fields );

}

/**
 * Export a field for the contact exporter
 *
 * @param Contact $contact
 * @param string  $field
 *
 * @return mixed
 */
function export_field( $contact, $field = '' ) {

	$return = '';

	switch ( $field ) {
		default:
			$return = $contact->$field;
			break;
		case 'notes':
			$return = '';
			break;
		case 'tags':
			$raw_tags = get_db( 'tags' )->query( [ 'tag_id' => $contact->get_tags() ] );

			if ( $raw_tags ) {
				$names = wp_list_pluck( $raw_tags, 'tag_name' );

				$return = implode( ',', $names );
			}

			break;
	}

	/**
	 * Filter the exported data from a field in the contact record.
	 *
	 * @param $return  mixed
	 * @param $contact Contact
	 * @param $field   string
	 */
	return apply_filters( 'groundhogg/export_field', $return, $contact, $field );
}

/**
 * Get a list of mappable fields as well as extra fields
 *
 * @param array $extra
 *
 * @return array
 */
function get_mappable_fields( $extra = [] ) {

	$defaults = [
		'full_name'                 => __( 'Full Name', 'groundhogg' ),
		'first_name'                => __( 'First Name', 'groundhogg' ),
		'last_name'                 => __( 'Last Name', 'groundhogg' ),
		'email'                     => __( 'Email Address', 'groundhogg' ),
		'optin_status'              => __( 'Optin Status', 'groundhogg' ),
		'user_id'                   => __( 'User Id', 'groundhogg' ),
		'owner_id'                  => __( 'Owner Id', 'groundhogg' ),
		'date_created'              => __( 'Date Created', 'groundhogg' ),
		'date_optin_status_changed' => __( 'Date Optin Status Changed', 'groundhogg' ),
		'birthday'                  => __( 'Birthday', 'groundhogg' ),
		'mobile_phone'              => __( 'Mobile Phone Number', 'groundhogg' ),
		'primary_phone'             => __( 'Primary Phone Number', 'groundhogg' ),
		'primary_phone_extension'   => __( 'Primary Phone Number Extension', 'groundhogg' ),
		'company_phone'             => __( 'Company Phone Number', 'groundhogg' ),
		'company_phone_extension'   => __( 'Company Phone Number Extension', 'groundhogg' ),
		'street_address_1'          => __( 'Street Address 1', 'groundhogg' ),
		'street_address_2'          => __( 'Street Address 2', 'groundhogg' ),
		'city'                      => __( 'City', 'groundhogg' ),
		'postal_zip'                => __( 'Postal/Zip', 'groundhogg' ),
		'region'                    => __( 'Province/State/Region', 'groundhogg' ),
		'country'                   => __( 'Country', 'groundhogg' ),
		'company_name'              => __( 'Company Name', 'groundhogg' ),
		'company_address'           => __( 'Full Company Address', 'groundhogg' ),
		'job_title'                 => __( 'Job Title', 'groundhogg' ),
		'time_zone'                 => __( 'Time Zone', 'groundhogg' ),
		'ip_address'                => __( 'IP Address', 'groundhogg' ),
		'lead_source'               => __( 'Lead Source', 'groundhogg' ),
		'source_page'               => __( 'Source Page', 'groundhogg' ),
		'terms_agreement'           => __( 'Terms Agreement', 'groundhogg' ),
		'gdpr_consent'              => __( 'Data Processing Consent', 'groundhogg' ),
		'marketing_consent'         => __( 'Marketing Consent', 'groundhogg' ),
		'notes'                     => __( 'Add To Notes', 'groundhogg' ),
		'tags'                      => __( 'Apply Value as Tag', 'groundhogg' ),
		'meta'                      => __( 'Add as Custom Meta', 'groundhogg' ),
		'copy_file'                 => __( 'Add as File', 'groundhogg' ),
		'utm_campaign'              => __( 'UTM Campaign', 'groundhogg' ),
		'utm_content'               => __( 'UTM Content', 'groundhogg' ),
		'utm_medium'                => __( 'UTM Medium', 'groundhogg' ),
		'utm_term'                  => __( 'UTM Term', 'groundhogg' ),
		'utm_source'                => __( 'UTM Source', 'groundhogg' ),
	];

	$fields = array_merge( $defaults, $extra );

	return apply_filters( 'groundhogg/mappable_fields', $fields );

}

/**
 * Generate a contact from given associative array and a field map.
 *
 * @throws \Exception
 *
 * @param $map    array map of field_ids to contact keys
 *
 * @param $fields array the raw data from the source
 *
 * @return Contact|false
 */
function generate_contact_with_map( $fields, $map = [] ) {

	if ( empty( $map ) ) {
		$keys = array_keys( $fields );
		$map  = array_combine( $keys, $keys );
	}

	$meta  = [];
	$tags  = [];
	$notes = [];
	$args  = [];
	$files = [];
	$copy  = [];

	foreach ( $fields as $column => $value ) {

		// ignore if we are not mapping it.
		if ( ! key_exists( $column, $map ) ) {
			continue;
		}

		$value = wp_unslash( $value );

		$field = $map[ $column ];

		switch ( $field ) {
			default:

				/**
				 * Default filter for unknown contact fields
				 *
				 * @param $field  string the field in question
				 * @param $value  mixed the value to store
				 * @param &$args  array general contact information
				 * @param &$meta  array list of contact data
				 * @param &$tags  array list of tags to add to the contact
				 * @param &$notes array add notes to the contact
				 * @param &$files array files to upload to the contact record
				 */
				do_action_ref_array( 'groundhogg/generate_contact_with_map/default', [
					$field,
					$value,
					&$args,
					&$meta,
					&$tags,
					&$notes,
					&$files
				] );

				break;
			case 'full_name':
				$parts              = split_name( $value );
				$args['first_name'] = sanitize_text_field( $parts[0] );
				$args['last_name']  = sanitize_text_field( $parts[1] );
				break;
			case 'first_name':
			case 'last_name':
				$args[ $field ] = sanitize_text_field( $value );
				break;
			case 'email':
				$args[ $field ] = sanitize_email( $value );
				break;
			case 'date_created':
			case 'date_optin_status_changed':
				$args[ $field ] = date( 'Y-m-d H:i:s', strtotime( $value ) );
				break;
			case 'optin_status':

				// Will default to unconfirmed
				if ( ! is_numeric( $value ) ) {
					$value = Preferences::string_to_preference( $value );
				}

				$args[ $field ] = absint( $value );
				break;
			case 'user_id':
			case 'owner_id':

				// Email Passed
				if ( is_email( $value ) ) {
					$by = 'email';
					// Username passed
				} else if ( is_string( $value ) && ! is_numeric( $value ) ) {
					$by = 'login';
					// ID Passed
				} else {
					$by    = 'id';
					$value = absint( $value );
				}

				$user = get_user_by( $by, $value );

				// Make sure User exists
				if ( $user ) {
					// Check the mapped owner can actually own contacts.
					if ( $field !== 'owner_id' || user_can( $user->ID, 'edit_contacts' ) ) {
						$args[ $field ] = $user->ID;
					}
				}

				break;
			case 'mobile_phone':
			case 'primary_phone':
			case 'primary_phone_extension':
			case 'company_phone':
			case 'company_phone_extension':
			case 'street_address_1' :
			case 'street_address_2':
			case 'city':
			case 'postal_zip':
			case 'region':
			case 'company_name':
			case 'company_address':
			case 'job_title':
			case 'lead_source':
			case 'source_page':
			case 'utm_campaign':
			case 'utm_medium':
			case 'utm_content':
			case 'utm_term':
			case 'utm_source':
				$meta[ $field ] = sanitize_text_field( $value );
				break;
			// Only checks whether value is not empty.
			case 'terms_agreement':
				if ( ! empty( $value ) ) {
					$meta['terms_agreement']      = 'yes';
					$meta['terms_agreement_date'] = date_i18n( get_date_time_format() );
				}
				break;
			// Only checks whether value is not empty.
			case 'gdpr_consent':
				if ( ! empty( $value ) ) {
					$meta['gdpr_consent']      = 'yes';
					$meta['gdpr_consent_date'] = date_i18n( get_date_time_format() );
				}
				break;
			case 'marketing_consent':
				if ( ! empty( $value ) ) {
					$meta['marketing_consent']      = 'yes';
					$meta['marketing_consent_date'] = date_i18n( get_date_time_format() );
				}
				break;
			case 'country':
				if ( strlen( $value ) !== 2 ) {
					$countries = Plugin::$instance->utils->location->get_countries_list();
					$code      = array_search( $value, $countries );
					if ( $code ) {
						$value = $code;
					}
				}
				$meta[ $field ] = $value;
				break;
			case 'tags':
				$maybe_tags = explode( ',', $value );
				$tags       = array_merge( $tags, $maybe_tags );
				break;
			case 'meta':
				$meta[ get_key_from_column_label( $column ) ] = sanitize_text_field( $value );
				break;
			case 'files':
				if ( isset_not_empty( $_FILES, $column ) ) {
					$files[ $column ] = wp_unslash( get_array_var( $_FILES, $column ) );
				}
				break;

			case 'copy_file':
				// used to copy file uploaded using form builder
				if ( ! function_exists( 'download_url' ) ) {
					require_once( ABSPATH . '/wp-admin/includes/file.php' );
				}
				if ( download_url( $value ) ) {
					$copy [] = $value;
				}

				break;
			case 'notes':
				$notes[] = sanitize_textarea_field( $value );
				break;
			case 'time_zone':
				$zones = Plugin::$instance->utils->location->get_time_zones();
				$code  = array_search( $value, $zones );
				if ( $code ) {
					$meta[ $field ] = $code;
				}
				break;
			case 'ip_address':
				$ip = filter_var( $value, FILTER_VALIDATE_IP );
				if ( $ip ) {
					$meta[ $field ] = $ip;
				}
				break;
			case 'birthday':

				$date  = date( 'Y-m-d', strtotime( $value ) );
				$parts = map_deep( explode( '-', $date ), 'absint' );

				$meta['birthday_year']  = $parts[0];
				$meta['birthday_month'] = $parts[1];
				$meta['birthday_day']   = $parts[2];
				$meta['birthday']       = $date;
				break;
		}

	}

	$contact = false;

	// If the current user can add a contact and a contact owner has not been explicitly defined.
	if ( current_user_can( 'add_contacts' ) && ! isset_not_empty( $args, 'owner_id' ) ) {
		$args['owner_id'] = get_current_user_id();
	}

	// No point in trying if there is no email field
	if ( isset( $args['email'] ) ) {

		if ( ! is_email( $args['email'] ) ) {
			return false;
		}

		$contact = get_contactdata( $args['email'] );

		// update existing
		if ( $contact !== false && $contact->exists() ) {
			$contact->update( $args );
			// create new
		} else {
			$contact = new Contact( $args );
		}

		// We do NOT want to process this in the event the user is logged is as
		// a GH user
		// There is no email field in this case!
	} else if ( ! current_user_can( 'view_contacts' ) ) {

		// Is there an active contact record?
		$contact = get_contactdata();

		// Update based on the current args...
		if ( $contact !== false && $contact->exists() ) {
			$contact->update( $args );
		}
	}


	if ( ! $contact ) {
		return false;
	}

	// Add Tags
	if ( ! empty( $tags ) ) {
		$contact->apply_tag( $tags );
	}

	// Add notes
	if ( ! empty( $notes ) ) {
		foreach ( $notes as $note ) {
			$contact->add_note( $note, 'system' );
		}
	}

	// update meta data
	if ( ! empty( $meta ) ) {
		foreach ( $meta as $key => $value ) {
			$contact->update_meta( $key, $value );
		}
	}

	if ( ! empty( $files ) ) {
		foreach ( $files as $file ) {
			$contact->upload_file( $file );
		}
	}

	// copy files
	if ( ! empty( $copy ) ) {
		foreach ( $copy as $url ) {
			$contact->copy_file( $url );
		}
	}

	$contact->update_meta( 'last_optin', time() );

	/**
	 * @param $contact Contact the contact record
	 * @param $map     array the map of given data to contact data
	 * @param $fields  array the values of the given fields
	 */
	do_action( 'groundhogg/generate_contact_with_map/after', $contact, $map, $fields );

	return $contact;
}

if ( ! function_exists( 'get_key_from_column_label' ) ):

	/**
	 * Get a key from a column label
	 *
	 * @param $column
	 *
	 * @return string
	 */
	function get_key_from_column_label( $column ) {
		return words_to_key( $column );
	}

endif;

if ( ! function_exists( 'multi_implode' ) ):
	function multi_implode( $glue, $array ) {
		$ret = '';

		foreach ( $array as $item ) {
			if ( is_array( $item ) ) {
				$ret .= multi_implode( $glue, $item ) . $glue;
			} else {
				$ret .= $item . $glue;
			}
		}

		$ret = substr( $ret, 0, 0 - strlen( $glue ) );

		return $ret;
	}
endif;

/**
 * Get a time string representing when something should be completed.
 *
 * @param        $time
 *
 * @param string $date_prefix
 *
 * @return string
 */
function scheduled_time( $time, $date_prefix = 'on' ) {
	// convert to local time.
	$p_time = Plugin::$instance->utils->date_time->convert_to_local_time( $time );

	// Get the current time.
	$cur_time = (int) current_time( 'timestamp' );

	$time_diff = $p_time - $cur_time;

	if ( absint( $time_diff ) > DAY_IN_SECONDS ) {

		if ( $date_prefix ) {
			$time = sprintf( "%s %s", $date_prefix, date_i18n( get_date_time_format(), intval( $p_time ) ) );
		} else {
			$time = date_i18n( get_date_time_format(), intval( $p_time ) );
		}

	} else {
		$format = $time_diff <= 0 ? _x( "%s ago", 'status', 'groundhogg' ) : _x( "in %s", 'status', 'groundhogg' );
		$time   = sprintf( $format, human_time_diff( $p_time, $cur_time ) );
	}

	return $time;
}

/**
 * Get a time string representing when something should be completed.
 *
 * @param        $time
 *
 * @return string
 */
function time_ago( $time ) {

	if ( is_string( $time ) ) {
		$time = strtotime( $time );
	}

	// Get the current time.
	$cur_time = (int) current_time( 'timestamp' );

	$time_diff = $time - $cur_time;

	if ( absint( $time_diff ) > DAY_IN_SECONDS ) {
		$time = date_i18n( get_date_time_format(), intval( $time ) );
	} else {
		$format = $time_diff <= 0 ? _x( "%s ago", 'status', 'groundhogg' ) : _x( "in %s", 'status', 'groundhogg' );
		$time   = sprintf( $format, human_time_diff( $time, $cur_time ) );
	}

	return $time;
}

/**
 * Render html for a time column with an associated contact
 *
 * @param int          $time            the time to display
 * @param bool         $show_local_time whether to also show local time
 * @param bool|Contact $contact         the contact to get the local time from.
 * @param string       $date_prefix
 *
 * @return string
 */
function scheduled_time_column( $time = 0, $show_local_time = false, $contact = false, $date_prefix = 'on' ) {

	if ( is_string( $time ) ) {
		$time = strtotime( $time );
	}

	$s_time = scheduled_time( $time, $date_prefix );
	$l_time = Plugin::instance()->utils->date_time->convert_to_local_time( $time );

	$html = '<abbr title="' . date_i18n( get_date_time_format(), $l_time ) . '">' . $s_time . '</abbr>';

	if ( $show_local_time && is_a( $contact, 'Groundhogg\Contact' ) ) {
		$html .= sprintf( '<br><i>(%s %s)', date_i18n( get_option( 'time_format' ), $contact->get_local_time( $time ) ), __( 'local time', 'groundhogg' ) ) . '</i>';
	}

	return $html;
}

/**
 * Get products from the Groundhogg store.
 *
 * @param array $args
 *
 * @return mixed|string
 */
function get_store_products( $args = [] ) {
	$args = wp_parse_args( $args, array(
		//'category' => 'templates',
		'category' => '',
		'tag'      => '',
		's'        => '',
		'page'     => '',
		'number'   => '-1'
	) );

	$url = 'https://www.groundhogg.io/edd-api/v2/products/';

	$response = wp_remote_get( add_query_arg( $args, $url ) );

	if ( is_wp_error( $response ) ) {
		return $response->get_error_message();
	}

	$products = json_decode( wp_remote_retrieve_body( $response ) );

	return $products;
}

/**
 * Whether to show Groundhogg Branding. Compat with white label options.
 *
 * @return bool
 */
function show_groundhogg_branding() {
	return apply_filters( 'groundhogg/show_branding', true );
}

/**
 * Show a floating phil on the page!
 *
 * @return void;
 */
function floating_phil() {
	?><img style="position: fixed;bottom: -80px;right: -80px;transform: rotate(-20deg);" class="phil"
           src="<?php echo GROUNDHOGG_ASSETS_URL . 'images/phil-340x340.png'; ?>" width="340" height="340"><?php
}

/**
 * Show the logo.
 *
 * @param string $color
 * @param int    $width
 *
 * @return string|bool
 */
function groundhogg_logo( $color = 'black', $width = 300, $echo = true ) {

	switch ( $color ) {
		default:
		case 'black':
			$link = 'logo-black-1000x182.png';
			break;
		case 'white':
			$link = 'logo-white-1000x182.png';
			break;
	}

	$img = html()->e( 'img', [
		'src'   => GROUNDHOGG_ASSETS_URL . 'images/' . $link,
		'width' => $width
	] );

	if ( $echo ) {
		echo $img;

		return true;
	}

	return $img;
}

/**
 * Return form submission error html or echo it.
 *
 * @param bool $return
 *
 * @return bool|string
 */
function form_errors( $return = true ) {
	if ( Plugin::$instance->submission_handler->has_errors() ) {

		$errors   = Plugin::$instance->submission_handler->get_errors();
		$err_html = "";

		foreach ( $errors as $error ) {
			$err_html .= sprintf( '<li id="%s">%s</li>', $error->get_error_code(), $error->get_error_message() );
		}

		$err_html = sprintf( "<ul class='gh-form-errors'>%s</ul>", $err_html );
		$err_html = sprintf( "<div class='gh-message-wrapper gh-form-errors-wrapper'>%s</div>", $err_html );

		if ( $return ) {
			return $err_html;
		}

		echo $err_html;

		return true;
	}

	return false;
}

/**
 * Get the email templates
 *
 * @return array
 */
function get_email_templates() {
	$templates = apply_filters( 'groundhogg/templates/emails', [] );

	/**
	 * @var $email_templates array
	 */
	return $templates;
}

/**
 * Checks data to see if it matches anything in the blacklist.
 *
 * @param string $data
 *
 * @return bool
 */
function blacklist_check( $data = '' ) {

	if ( ! is_array( $data ) && ! is_object( $data ) ) {
		$mod_keys = trim( get_option( 'blacklist_keys' ) );
		if ( '' == $mod_keys ) {
			return false; // If moderation keys are empty
		}

		// Ensure HTML tags are not being used to bypass the blacklist.
		$data_no_html = wp_strip_all_tags( $data );

		$words = explode( "\n", $mod_keys );

		foreach ( (array) $words as $word ) {
			$word = trim( $word );

			// Skip empty lines
			if ( empty( $word ) ) {
				continue;
			}

			// Do some escaping magic so that '#' chars in the
			// spam words don't break things:
			$word = preg_quote( $word, '#' );

			$pattern = "#$word#i";

			if ( preg_match( $pattern, $data ) || preg_match( $pattern, $data_no_html ) ) {
				return true;
			}
		}

		return false;
	}

	foreach ( (array) $data as $datum ) {
		if ( blacklist_check( $datum ) ) {
			return true;
		}
	}

	return false;
}

/**
 * @return mixed|void
 */
function get_managed_page_name() {
	return apply_filters( 'groundhogg/managed_page_name', get_option( 'gh_managed_page_name_override', 'gh' ) );
}

/**
 * Return the URL markeup for the managed page
 *
 * @param string $url
 *
 * @return string|void
 */
function managed_page_url( $url = '' ) {
	return trailingslashit( rtrim( home_url( get_managed_page_name() ), '/' ) . '/' . ltrim( $url, '/' ) );
}

/**
 * Setup the managed page
 */
function setup_managed_page() {
	$managed_page_name = get_managed_page_name();

	$query = new \WP_Query();
	$posts = $query->query( [
		'name'        => $managed_page_name,
		'post_type'   => 'page',
		'post_status' => 'publish'
	] );

	if ( empty( $posts ) ) {
		$post_id = wp_insert_post( [
			'post_title'   => 'managed-page',
			'post_status'  => 'publish',
			'post_name'    => $managed_page_name,
			'post_type'    => 'page',
			'post_content' => "Shhhh! This is a secret page. Go away!"
		], true );

		if ( is_wp_error( $post_id ) ) {
			Plugin::$instance->notices->add( $post_id );
		}
	}
}

/**
 * Add a managed rewrite rule
 *
 * @param string $regex
 * @param string $query
 * @param string $after
 */
function add_managed_rewrite_rule( $regex = '', $query = '', $after = 'top' ) {

	$managed_page_name = get_managed_page_name();

	if ( strpos( $query, 'index.php' ) === false ) {
		$ahead = sprintf( 'index.php?pagename=%s&', $managed_page_name );
		$query = $ahead . $query;
	}

	if ( strpos( $regex, '^' . $managed_page_name ) !== 0 ) {
		$regex = '^' . $managed_page_name . '/' . $regex;
	}

	add_rewrite_rule( $regex, $query, $after );
}

/**
 * @deprecated since 2.0.9.2
 *
 * @param string $string
 *
 * @return string
 */
function managed_rewrite_rule( $string = '' ) {
	return sprintf( 'index.php?pagename=%s&', get_managed_page_name() ) . $string;
}

/**
 * @return bool
 */
function is_managed_page() {
	return get_query_var( 'pagename' ) === get_managed_page_name();
}

/**
 * HTML for the no-index meta tag
 */
function no_index_tag() {
	?>
    <meta name="robots" content="noindex">
	<?php
}

/**
 * No-index the managed page if someone ends up here for whatever reason.
 */
function no_index_managed_page() {
	if ( ! is_managed_page() ) {
		return;
	}

	no_index_tag();
}

add_action( 'wp_head', __NAMESPACE__ . '\no_index_managed_page' );

/**
 * Add the new rewrite rules.
 */
function install_custom_rewrites() {
	setup_managed_page();

	Plugin::$instance->tracking->add_rewrite_rules();
	Plugin::$instance->rewrites->add_rewrite_rules();
	Plugin::$instance->preferences->add_rewrite_rules();

	do_action( 'groundhogg/install_custom_rewrites' );

	flush_rewrite_rules();
}

/**
 * Retrieve URL with nonce added to URL query.
 *
 * @since 2.0.4
 *
 * @param string     $name      Optional. Nonce name. Default '_wpnonce'.
 *
 * @param string     $actionurl URL to add nonce action.
 *
 * @param int|string $action    Optional. Nonce action name. Default -1.
 *
 * @return string
 */
function nonce_url_no_amp( $actionurl, $action = - 1, $name = '_wpnonce' ) {
	return add_query_arg( $name, wp_create_nonce( $action ), $actionurl );
}

/**
 * Remove &amp; from the url
 * Relevant for when links are provided in replacement codes.
 *
 * @param $url
 *
 * @return string|string[]
 */
function no_and_amp( $url ) {
	return preg_replace( "/(&amp;)([^=]*=[^&]*)/", "&$2", $url );
}

/**
 * Return a dashicon
 *
 * @param        $icon
 * @param string $wrap
 * @param array  $atts
 *
 * @return string
 */
function dashicon( $icon, $wrap = 'span', $atts = [], $echo = false ) {
	$atts = wp_parse_args( $atts, [
		'class' => ''
	] );

	$atts['class'] .= ' dashicons dashicons-' . $icon;

	$html = html()->e( $wrap, $atts, '', false );

	if ( $echo ) {
		echo $html;
	}

	return $html;
}

/**
 * Output a dashicon
 *
 * @param        $icon
 * @param string $wrap
 * @param array  $atts
 */
function dashicon_e( $icon, $wrap = 'span', $atts = [] ) {
	dashicon( $icon, $wrap, $atts, true );
}


/**
 * Whather the current admin page is a groundhogg page.
 *
 * @return bool
 */
function is_admin_groundhogg_page() {
	$page = get_request_var( 'page' );

	return is_admin() && $page && ( preg_match( '/^gh/', $page ) || $page === 'groundhogg' );
}


if ( ! function_exists( __NAMESPACE__ . '\is_white_labeled' ) ) {

	/**
	 * Whether the Groundhogg is white labeled or not.
	 *
	 * @return bool
	 */
	function is_white_labeled() {
		return false; // todo make false
	}
}

if ( ! function_exists( __NAMESPACE__ . '\white_labeled_name' ) ) {

	/**
	 * Return replacement name form white label
	 *
	 * @return string
	 */
	function white_labeled_name() {
		return 'Groundhogg';  // TODO
	}
}

/**
 * Gets the main blog ID.
 *
 * @return int
 */
function get_main_blog_id() {
	if ( is_multisite() ) {
		return get_network()->site_id;
	}

	return false;
}

/**
 * Whether the current blog is the main blog.
 *
 * @return bool
 */
function is_main_blog() {
	if ( ! is_multisite() ) {
		return true;
	}

	return get_main_blog_id() === get_current_blog_id();
}

/**
 * Remote post json content
 * Glorified wp_remote_post wrapper
 *
 * @param string $url
 * @param array  $body
 * @param string $method
 * @param array  $headers
 * @param bool   $as_array
 *
 * @return array|bool|WP_Error|object
 */
function remote_post_json( $url = '', $body = [], $method = 'POST', $headers = [], $as_array = false ) {
	$method = strtoupper( $method );

	if ( ! isset_not_empty( $headers, 'Content-type' ) ) {
		$headers['Content-type'] = sprintf( 'application/json; charset=%s', get_bloginfo( 'charset' ) );
	}

	switch ( $method ) {
		case 'POST':
		case 'PUT':
		case 'PATCH':
		case 'DELETE':
			$body = is_array( $body ) ? wp_json_encode( $body ) : $body;
			break;
	}

	$args = [
		'method'      => $method,
		'headers'     => $headers,
		'body'        => $body,
		'data_format' => 'body',
		'sslverify'   => true,
		'user-agent'  => 'Groundhogg/' . GROUNDHOGG_VERSION . '; ' . home_url()

	];

	if ( $method === 'GET' ) {
		$response = wp_remote_get( $url, $args );
	} else {
		$response = wp_remote_post( $url, $args );
	}

	if ( ! $response ) {
		return new WP_Error( 'unknown_error', sprintf( 'Failed to initialize remote %s.', $method ), $response );
	}

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$json = json_decode( wp_remote_retrieve_body( $response ), $as_array );

	if ( ! $json ) {
		return new WP_Error( 'unknown_error', sprintf( 'Failed to initialize remote %s.', $method ), wp_remote_retrieve_body( $response ) );
	}

	if ( is_json_error( $json ) ) {
		$error = get_json_error( $json );

		$data = (array) $error->get_error_data();

		$data['url']     = $url;
		$data['method']  = $method;
		$data['headers'] = $headers;
		$data['body']    = json_decode( $body );

		$error->add_data( $data );

		return $error;
	}

	return $json;
}

/**
 * Returns the full format of dat time based on WP settings.
 *
 * @return string
 */
function get_date_time_format() {
	return sprintf( "%s %s", get_option( 'date_format' ), get_option( 'time_format' ) );
}

/**
 * Url to access protected files in the Groundhogg uploads folder.
 *
 * @param $path     string abspath to a file.
 * @param $download bool
 *
 * @return string
 */
function file_access_url( $path, $download = false ) {
	// Get the base path
	$base_uploads_folder = Plugin::instance()->utils->files->get_base_uploads_dir();
	$base_uploads_url    = Plugin::instance()->utils->files->get_base_uploads_url();

	// Remove the extra path info from the path
	if ( strpos( $path, $base_uploads_folder ) !== false ) {
		$path = str_replace( $base_uploads_folder, '', $path );
		// Remove the extra url info from the path
	} else if ( strpos( $path, $base_uploads_url ) !== false ) {
		$path = str_replace( $base_uploads_url, '', $path );
	}

	$url = managed_page_url( 'uploads/' . ltrim( $path, '/' ) );

	// WP Engine file download links do not work if forward slash is not present.
	if ( ! is_wpengine() ) {
		$url = untrailingslashit( $url );
	}

	if ( $download ) {
		$url = add_query_arg( [ 'download' => true ], $url );
	}

	return $url;
}

/**
 * Triggers the API benchmark
 *
 * @param string $call_name   the name you wish to call
 * @param string $id_or_email id or email of the contact
 * @param bool   $by_user_id  whether the ID is the ID of a WP user
 */
function do_api_trigger( $call_name = '', $id_or_email = '', $by_user_id = false ) {
	do_action( 'groundhogg/steps/benchmarks/api', $call_name, $id_or_email, $by_user_id );
}

/**
 * Wrapper for the do_api_trigger function.
 *
 * @param string $call_name
 * @param string $id_or_email
 * @param bool   $by_user_id
 */
function do_api_benchmark( $call_name = '', $id_or_email = '', $by_user_id = false ) {
	do_api_trigger( $call_name, $id_or_email, $by_user_id );
}

/**
 * Get the value of an option.
 *
 * @param string $option
 *
 * @return mixed|string
 */
function get_screen_option( $option = '' ) {
	$user          = get_current_user_id();
	$screen        = get_current_screen();
	$screen_option = $screen->get_option( $option, 'option' );
	$value         = get_user_meta( $user, $screen_option, true );

	if ( empty( $value ) || ( is_numeric( $value ) && $value < 1 ) ) {
		$value = $screen->get_option( $option, 'default' );
	}

	return $value;
}

if ( ! function_exists( __NAMESPACE__ . '\get_email_top_image_url' ) ):

	/**
	 * Return the theme logo URL.
	 *
	 * @return mixed
	 */
	function get_email_top_image_url() {
		$image = wp_get_attachment_image_src( get_theme_mod( 'custom_logo' ), 'full' );

		if ( ! $image ) {
			return 'https://via.placeholder.com/350x150';
		}

		return $image[0];
	}

endif;

/**
 * Gets the ids of the magic tags.
 *
 * @return int[]
 */
function get_magic_tag_ids() {
	return array_values( Plugin::$instance->tag_mapping->get_tag_map() );
}

/**
 * Get the attribute of an HTML tag from the tag.
 * NOTE: This currently does NOT support attributes which do not have values in quotes.
 * Todo Support 'magic' attributes
 *
 * @param $tag
 *
 * @return array|false
 */
function get_tag_attributes( $tag ) {
	if ( ! preg_match( '/<[^>]+>/', $tag, $matches ) ) {
		return false;
	}

	$tag = $matches[0];

	preg_match_all( "/([a-z\-]+)(=\"([^\"]+)\")/", $tag, $all_atts );

	$attributes = map_deep( $all_atts[1], 'sanitize_key' );
	$values     = $all_atts[3];
	$attributes = array_combine( $attributes, $values );

	if ( isset_not_empty( $attributes, 'style' ) ) {
		$attributes['style'] = parse_inline_styles( $attributes['style'] );
	}

	return $attributes;
}

/**
 * Gets the tag name given a tag
 *
 * @param $tag
 *
 * @return bool|mixed
 */
function get_tag_name( $tag ) {
	if ( ! preg_match( '/<[^>]+>/', $tag ) ) {
		return false;
	}
	preg_match( '/<([^\W]+)/', $tag, $matches );

	return $matches[1];
}

/**
 * Given a string of inline styles, parse it and return an array of [ attribute => value ]
 *
 * @param $style string
 *
 * @return array
 */
function parse_inline_styles( $style ) {
	$bits = explode( ';', $style );

	$css = [];

	foreach ( $bits as $bit ) {

		$rule              = explode( ':', $bit );
		$attribute         = sanitize_key( $rule[0] );
		$value             = trim( $rule[1] );
		$css[ $attribute ] = $value;
	}

	return $css;
}

/**
 * echo an action input, similar to wp_nonce_field
 *
 * @param string $action
 * @param bool   $echo
 *
 * @return bool|string
 */
function action_input( $action = '', $echo = true, $nonce=false ) {
	$input = html()->input( [ 'value' => $action, 'type' => 'hidden', 'name' => 'action' ] );

	if ( $nonce ){
		$input .= wp_nonce_field( $action, '_wpnonce', true, false );
	}

	if ( $echo ) {
		echo $input;

		return true;
	}

	return $input;
}

/**
 * Return an actionable url
 *
 * @param       $action
 * @param array $args
 *
 * @return string
 */
function action_url( $action, $args = [] ) {
	$url_args = [
		'page'     => get_request_var( 'page' ),
		'tab'      => get_request_var( 'tab' ),
		'action'   => $action,
		'_wpnonce' => wp_create_nonce( $action )
	];

	$url_args = array_filter( array_merge( $url_args, $args ) );

	return add_query_arg( urlencode_deep( $url_args ), admin_url( 'admin.php' ) );
}

/**
 * Get the default country code of the site.
 *
 * @return string the cc code of the site. US is default
 */
function get_default_country_code() {
	// Is the CC already set?
	$cc = get_option( 'gh_default_country_code' );

	if ( $cc ) {
		return $cc;
	}

	// Get the IP of the logged in user
	if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {

		$cc = Plugin::instance()->utils->location->ip_info( null, 'countrycode' );

		if ( $cc ) {
			update_option( 'gh_default_country_code', $cc );

			return $cc;
		}

	}

	// Get the IP of the site wherever it's being hosted
	$parse_url = wp_parse_url( home_url(), PHP_URL_HOST );

	if ( $parse_url ) {
		$ip = gethostbyname( $parse_url );
		$cc = Plugin::instance()->utils->location->ip_info( $ip, 'countrycode' );

		if ( $cc ) {
			update_option( 'gh_default_country_code', $cc );

			return $cc;
		}
	}

	return 'US';
}

/**
 * @return Mobile_Validator
 */
function mobile_validator() {
	global $groundhogg_mobile_validator;

	if ( ! $groundhogg_mobile_validator instanceof Mobile_Validator ) {
		$groundhogg_mobile_validator = new Mobile_Validator();
	}

	return $groundhogg_mobile_validator;
}

/**
 * Validate a mobile number
 *
 * @param        $number       string
 * @param string $country_code the country code of the supposed contact
 * @param bool   $with_plus    whether to return with the + or not
 *
 * @return bool|string
 */
function validate_mobile_number( $number, $country_code = '', $with_plus = false ) {
	if ( ! $country_code ) {
		$country_code = get_default_country_code();
	}

	$number = preg_replace( "/[^0-9]/", "", $number );

	if ( ! number_has_country_code( $number ) ) {
		$number = \Groundhogg\mobile_validator()->normalize( $number, $country_code );
	}

	if ( empty( $number ) ) {
		return false;
	}

	// Number may come from validator meaning it will be in array
	if ( is_array( $number ) ) {
		$number = $number[0];
	}

	// Add plus to string if not there
	if ( $with_plus ) {
		if ( strpos( $number, '+' ) === false ) {
			$number = '+' . $number;
		}
		// Remove plus from string
	} else {
		$number = str_replace( '+', '', $number );
	}

	return $number;
}

/**
 * Check if the number has a specific country code.
 *
 * @param string $number
 *
 * @return bool
 */
function number_has_country_code( $number = '' ) {
	if ( ! $number ) {
		return false;
	}

	$iso3166 = \Groundhogg\mobile_validator()->maybe_get_iso3166_by_phone( $number );

	// If found ISO than number has country code.
	return ! empty( $iso3166 );
}

/**
 * Get an error from an uploaded file.
 *
 * @param $file
 *
 * @return bool|WP_Error
 */
function get_upload_wp_error( $file ) {
	if ( ! is_array( $file ) ) {
		return new WP_Error( 'not_a_file', 'No file was provided.' );
	}

	// no Error
	if ( absint( $file['error'] ) === UPLOAD_ERR_OK ) {
		return false;
	}

	if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
		return new WP_Error( 'upload_error', 'File is not uploaded.' );
	}

	switch ( $file['error'] ) {
		case UPLOAD_ERR_INI_SIZE:
			$message = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
			break;
		case UPLOAD_ERR_FORM_SIZE:
			$message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
			break;
		case UPLOAD_ERR_PARTIAL:
			$message = "The uploaded file was only partially uploaded";
			break;
		case UPLOAD_ERR_NO_FILE:
			$message = "No file was uploaded";
			break;
		case UPLOAD_ERR_NO_TMP_DIR:
			$message = "Missing a temporary folder";
			break;
		case UPLOAD_ERR_CANT_WRITE:
			$message = "Failed to write file to disk";
			break;

		case UPLOAD_ERR_EXTENSION:
			$message = "File upload stopped by extension";
			break;
		default:
			$message = "Unknown upload error";
			break;
	}

	return new WP_Error( 'upload_error', $message, $file );
}

/**
 * Whether the guided setup is finished or not.
 *
 * @return mixed
 */
function guided_setup_finished() {

	if ( is_white_labeled() ) {
		return true;
	}

	return (bool) Plugin::$instance->settings->get_option( 'gh_guided_setup_finished', false );
}

/**
 * Convert a multi-dimensional array into a single-dimensional array.
 *
 * @param array $array The multi-dimensional array.
 *
 * @return array
 * @author Sean Cannon, LitmusBox.com | seanc@litmusbox.com
 */
function array_flatten( $array ) {
	if ( ! is_array( $array ) ) {
		return false;
	}
	$result = array();
	foreach ( $array as $key => $value ) {
		if ( is_array( $value ) ) {
			$result = array_merge( $result, array_flatten( $value ) );
		} else {
			$result[ $key ] = $value;
		}
	}

	return $result;
}

/**
 * Whether the SMS plugin is active. For backwards compatibility
 *
 * @return bool
 */
function is_sms_plugin_active() {
	return defined( 'GROUNDHOGG_SMS_VERSION' );
}

/**
 * Checks if Groundhogg pro features are installed.
 *
 * @return bool
 */
function is_pro_features_active() {
	return defined( 'GROUNDHOGG_PRO_VERSION' );
}

/**
 * If the current customer has access to premium features...
 *
 * Will return true if the `Groundhogg - Helper` is installed (has a plan license)
 * or if `Groundhogg - Advanced Features` is installed (probably has a license)
 *
 * @return bool
 */
function has_premium_features() {
	return defined( 'GROUNDHOGG_HELPER_VERSION' ) || defined( 'GROUNDHOGG_PRO_VERSION' );
}

add_action( 'admin_menu', function () {

	if ( ! current_user_can( 'edit_contacts' ) ) {
		return;
	}

	global $submenu;

	$groundhogg = get_array_var( $submenu, 'groundhogg' );

	if ( ! $groundhogg ) {
		return;
	}

	foreach ( $groundhogg as &$li ) {
		$li[4] = $li[2];
	}

	$submenu['groundhogg'] = $groundhogg;

}, 99999999 );


add_action( 'admin_print_styles', function () {

	if ( is_white_labeled() ) {
		return;
	}

	?>
    <style>
        #adminmenu #toplevel_page_groundhogg a.gh_go_pro .dashicons {
            font-size: 18px;
            margin-right: 8px;
        }

        #adminmenu #toplevel_page_groundhogg a.gh_go_pro {
            color: #FFF;
            font-weight: bold;
            display: inline-block;
            width: calc(100% - 44px);
            background: #dc741b;
        }

        #adminmenu #toplevel_page_groundhogg a.gh_go_pro:hover {
            background: #eb7c1e;
        }

        #adminmenu #toplevel_page_groundhogg li.gh_go_pro {
            text-align: center;
        }

        #adminmenu #toplevel_page_groundhogg li.gh_tools:before,
        #adminmenu #toplevel_page_groundhogg li.gh_go_pro:before,
        #adminmenu #toplevel_page_groundhogg li.gh_contacts:before {
            background: #b4b9be;
            content: "";
            display: block;
            height: 1px;
            margin: 5px auto 0;
            width: calc(100% - 24px);
            opacity: .4;
        }

        #adminmenu #toplevel_page_groundhogg li.gh_go_pro:before {
            margin-bottom: 8px;
        }
    </style>
	<?php
} );

/**
 * Allow funnel files to be uploaded
 */
function allow_funnel_uploads() {
	add_filter( 'mime_types', __NAMESPACE__ . '\_allow_funnel_uploads' );
}

/**
 * Allow .funnel files to be uploaded
 *
 * @param $mimes
 *
 * @return mixed
 */
function _allow_funnel_uploads( $mimes ) {
	$mimes['funnel'] = 'text/plain';

	return $mimes;
}

/**
 * Check if all the items in the given array are in a dataset.
 *
 * @param $items   array
 * @param $dataset array
 *
 * @return bool
 */
function has_all( $items = [], $dataset = [] ) {
	if ( ! is_array( $items ) || ! is_array( $dataset ) ) {
		return false;
	}

	// if empty then automatically true
	if ( empty( $items ) ) {
		return true;
	}

	// If the count of intersect is the same as $items then all the items are in the dataset
	return count( array_intersect( $items, $dataset ) ) === count( $items );
}

/**
 * If WP CRON is not
 */
function fallback_disable_wp_cron() {
	if ( ! defined( 'DISABLE_WP_CRON' ) && is_option_enabled( 'gh_disable_wp_cron' ) ) {
		define( 'DISABLE_WP_CRON', true );
		define( 'GH_SHOW_DISABLE_WP_CRON_OPTION', true );
	}
}

// Before wp_cron is added.
add_action( 'init', __NAMESPACE__ . '\fallback_disable_wp_cron', 1 );

/**
 * Is the current hosting provider wpengine?
 *
 * @return bool
 */
function is_wpengine() {
	return defined( 'WPE_PLUGIN_BASE' );
}

/**
 * Renamed the function for better clarity
 *
 * @return bool|\WP_User
 */
function get_primary_user() {
	_doing_it_wrong( 'get_primary_user', "Use <code>get_primary_owner</code> instead.", GROUNDHOGG_VERSION );

	return get_primary_owner();
}

/**
 * Get the primary user.
 *
 * @return bool|\WP_User
 */
function get_primary_owner() {

	static $user, $primary_user_id;

	if ( is_a( $user, '\WP_User' ) ) {
		return $user;
	}

	$primary_user_id = absint( get_option( 'gh_primary_user', 1 ) );

	if ( ! $primary_user_id ) {
		return false;
	}

	$user = get_userdata( $primary_user_id );

	return $user;
}

/**
 * Whether experimental features are enabled.
 *
 * @return bool
 */
function use_experimental_features() {
	return is_option_enabled( 'gh_enable_experimental_features' );
}

/**
 * Return the micro seconds of micro time as a float.
 *
 * @return float
 */
function micro_seconds() {
	$secs = explode( ' ', microtime() );
	$secs = floatval( $secs[0] );

	return $secs;
}

/**
 * @param bool $formatted if true will add 'px' to end
 *
 * @return mixed|void
 */
function get_default_email_width( $formatted = false ) {
	$width = absint( apply_filters( 'groundhogg/email_template/width', get_option( 'get_default_email_width', 580 ) ) );
	$width = ! $formatted ? $width : $width . 'px';

	return $width;
}

/**
 * Ge the user's test email address
 *
 * @param int $user_id
 *
 * @return mixed|string
 */
function get_user_test_email( $user_id = 0 ) {
	if ( ! $user_id && is_user_logged_in() ) {
		$user_id = get_current_user_id();
	}

	$email = get_user_meta( $user_id, 'preferred_test_email', true );

	return is_email( $email ) ? $email : wp_get_current_user()->user_email;
}

/**
 * Update a user's preferred test email address.
 *
 * @param string $email
 * @param int    $user_id
 *
 * @return bool|int
 */
function set_user_test_email( $email = '', $user_id = 0 ) {
	if ( ! $user_id && is_user_logged_in() ) {
		$user_id = get_current_user_id();
	}

	if ( ! $user_id || ! is_email( $email ) ) {
		return false;
	}

	return update_user_meta( $user_id, 'preferred_test_email', $email );
}

/**
 * Whether the gh-cron file was installed
 *
 * @return bool
 */
function gh_cron_installed() {
	return file_exists( ABSPATH . 'gh-cron.php' );
}

/**
 * Install the GH cron file
 *
 * @return bool
 */
function install_gh_cron_file() {

	$gh_cron_php = file_get_contents( GROUNDHOGG_PATH . 'gh-cron.txt' );
	$bytes       = file_put_contents( ABSPATH . 'gh-cron.php', $gh_cron_php );

	return (bool) $bytes;
}

/**
 * Remove the gh-cron.php file.
 *
 * @return bool
 */
function uninstall_gh_cron_file() {
	return @unlink( ABSPATH . 'gh-cron.php' );
}

/**
 * Get an event from the event history table by referencing its ID from the event queue
 *
 * @param $queued_id int
 *
 * @return bool|Event
 */
function get_event_by_queued_id( $queued_id ) {

	if ( ! $queued_id ) {
		return false;
	}

	$event = new Event( absint( $queued_id ), 'events', 'queued_id' );

	if ( ! $event->exists() ) {
		return false;
	}

	return $event;
}

/**
 * Get an event from the event history table by referencing its ID from the event queue
 *
 * @param $event_id int
 *
 * @return bool|Event
 */
function get_queued_event_by_id( $event_id ) {

	$event = new Event( absint( $event_id ), 'event_queue', 'ID' );

	if ( ! $event->exists() ) {
		return false;
	}

	return $event;
}

/**
 * Add an event to the event queue.
 *
 * @param $args
 *
 * @return bool|Event
 */
function enqueue_event( $args ) {
	$event_id = get_db( 'event_queue' )->add( $args );

	if ( ! $event_id ) {
		return false;
	}

	return get_queued_event_by_id( $event_id );
}

/**
 * Generate a referer hash string
 *
 * @param $referer
 *
 * @return false|string
 */
function generate_referer_hash( $referer ) {
	return substr( md5( $referer ), 0, 20 );
}

/**
 * @param $time
 *
 * @return int
 */
function convert_to_local_time( $time ) {
	return Plugin::$instance->utils->date_time->convert_to_local_time( $time );
}

/**
 * Get an array of all the valid owners on the site
 *
 * @return \WP_User[]
 */
function get_owners() {

	static $users;

	if ( ! empty( $users ) ) {
		return $users;
	}

	$roles__in = [];

	foreach ( wp_roles()->roles as $role_slug => $role ) {
		if ( isset_not_empty( $role['capabilities'], 'view_contacts' ) ) {
			$roles__in[] = $role_slug;
		}
	}

	$users = get_users( [ 'role__in' => ! empty( $roles__in ) ? $roles__in : Main_Roles::$owner_roles ] );

	return apply_filters( 'groundhogg/owners', $users );
}

/**
 * Shorthand for number formatting
 *
 * @param     $number
 * @param int $decimals
 *
 * @return string
 */
function _nf( $number, $decimals = 0 ) {
	return number_format_i18n( $number, $decimals );
}

/**
 * Whether Groundhogg is network active or not.
 *
 * @return bool
 */
function is_groundhogg_network_active() {
	if ( ! is_multisite() ) {
		return false;
	}

	$plugins = get_site_option( 'active_sitewide_plugins' );

	if ( isset( $plugins['groundhogg/groundhogg.php'] ) ) {
		return true;
	}

	return false;
}

/**
 * Do an action after a contact has been created or updated
 *
 * @param int|Contact|Email $contact
 * @param string            $hook
 *
 * @return bool
 */
function contact_action( $contact = 0, $hook = 'created' ) {

	if ( ! $contact ) {
		return false;
	} else if ( ! $contact instanceof Contact ) {
		$contact = get_contactdata( $contact );
	}

	do_action( "groundhogg/contact/{$hook}", $contact );

	return true;
}

/**
 * Whether the content has replacements in it.
 *
 * @param $content
 *
 * @return false|int
 */
function has_replacements( $content ) {
	return preg_match( '/{([^{}]+)}/', $content );
}

/**
 * @param $contact Contact|mixed
 *
 * @return bool
 */
function is_a_contact( $contact ) {
	return $contact && $contact instanceof Contact && $contact->exists();
}

/**
 * Whether the given user is a user
 *
 * @param $user
 *
 * @return bool
 */
function is_a_user( $user ) {
	return $user && $user instanceof \WP_User;
}

/**
 * Generate a key which can be used to perform high level operations that requires a level of authentication
 * For example change email preferences or auto-login.
 *
 * @param bool      $contact          Contact
 * @param string    $usage            what they key should be used for
 * @param float|int $expiration       the time at which the key expires
 * @param bool      $delete_after_use whether to delete the key once it's been used
 *
 * @return bool
 */
function generate_permissions_key( $contact = false, $usage = 'preferences', $expiration = WEEK_IN_SECONDS, $delete_after_use = false ) {

	$contact = $contact ?: get_contactdata();

	if ( ! is_a_contact( $contact ) ) {
		return false;
	}

	// Cache key is a combo of the contact ID and the usage case
	$cache_key = $contact->get_id() . '-' . $usage;

	// If a key was already created for the given contact within the current runtime
	$found = wp_cache_get( $cache_key, 'permissions_keys' );

	// use it instead of creating a new one
	if ( $found ) {
		return $found;
	}

	$key = wp_generate_password( 20, false );

	// Generate the permissions_key
	get_db( 'permissions_keys' )->add( [
		'contact_id'       => $contact->get_id(),
		'usage_type'       => sanitize_key( $usage ),
		'permissions_key'  => wp_hash_password( $key ),
		'delete_after_use' => $delete_after_use,
		'expiration_date'  => Ymd_His( time() + $expiration )
	] );

	// set the key for the given contact in the cache
	wp_cache_set( $cache_key, $key, 'permissions_keys' );

	return $key;
}

/**
 * Check the validity of a permissions key
 *
 * @param        $key     string
 * @param string $usage   string
 * @param bool   $contact Contact
 *
 * @return bool
 */
function check_permissions_key( $key, $contact = false, $usage = 'preferences' ) {

	$contact = $contact ?: get_contactdata();

	if ( ! is_a_contact( $contact ) || empty( $key ) || ! is_string( $key ) ) {
		return false;
	}

	$keys = get_db( 'permissions_keys' )->query( [
		'where' => [
			[ 'contact_id', '=', $contact->get_id() ],
			[ 'usage_type', '=', $usage ],
			[ 'expiration_date', '>', date( 'Y-m-d H:i:s' ) ],
		]
	] );

	if ( empty( $keys ) ) {
		return false;
	}

	if ( empty( $wp_hasher ) ) {
		require_once ABSPATH . WPINC . '/class-phpass.php';
		$wp_hasher = new \PasswordHash( 8, true );
	}

	foreach ( $keys as $permissions_key ) {
		if ( $wp_hasher->CheckPassword( $key, $permissions_key->permissions_key ) ) {

			// Maybe delete after
			if ( $permissions_key->delete_after_use ) {
				get_db( 'permissions_keys' )->delete( $permissions_key->ID );
			}

			return true;
		}
	}

	return false;
}

/**
 * Generate a url with the permissions key on it.
 *
 * @param string    $url              the url to append the key to
 * @param Contact   $contact          the contact the key is to be created for
 * @param string    $usage            the usage type for the key
 * @param float|int $expiration       the expiration time of the key in seconds, defaults to a week since the common use is for the preferences center.
 * @param bool      $delete_after_use whether the key should be delete after it is used.
 *
 * @return string
 */
function permissions_key_url( $url, $contact, $usage = 'preferences', $expiration = WEEK_IN_SECONDS, $delete_after_use = false ) {
	return add_query_arg( [
		'pk' => generate_permissions_key( $contact, $usage, $expiration, $delete_after_use ),
	], $url );
}

/**
 * Get the permissions key
 * if one is not available return false
 * Will set the permissions key cookie if the key is found in the URL
 *
 * @return string|false
 */
function get_permissions_key() {
	// check for the permissions_key and set it as a cookie
	if ( $permissions_key = get_url_var( 'pk' ) ) {
		set_cookie( 'gh-permissions-key', $permissions_key, HOUR_IN_SECONDS );
	} else {
		$permissions_key = get_cookie( 'gh-permissions-key' );
	}

	return $permissions_key;
}

/**
 * Same as preg_quote but not some characters
 *
 * @param       $str
 * @param array $except
 * @param null  $delim
 *
 * @return mixed|string
 */
function preg_quote_except( $str, $except = [], $delim = null ) {
	$str = preg_quote( $str, $delim );

	if ( empty( $except ) ) {
		return $str;
	}

	for ( $i = 0; $i < count( $except ); $i ++ ) {
		$from[] = '\\' . $except[ $i ];
		$to[]   = $except[ $i ];
	}

	return str_replace( $from, $to, $str );
}

/**
 * Get the regex for URLs to ignore tracking
 *
 * @param array $exclusions
 *
 * @return false|string
 */
function get_url_exclusions_regex( $exclusions = [] ){
	static $exclusions_regex;

	if ( ! $exclusions_regex ) {

		$exclusions = get_option( 'gh_url_tracking_exclusions' );

		if ( ! is_array( $exclusions ) ) {
			$exclusions = explode( PHP_EOL, $exclusions );
		}

		if ( empty( $exclusions ) ) {
			return false;
		}

		$exclusions       = array_map( function ( $exclusion ) {
			return preg_quote_except( trim( $exclusion ), [ '$', '^' ] );
		}, array_filter( $exclusions ) );

		$exclusions_regex = implode( '|', $exclusions );
	}

	return ! empty( $exclusions_regex ) ? "@$exclusions_regex@" : false;
}

/**
 * Check whether a specific URL is to be excluded from click tracking.
 *
 * @param       $url
 *
 * @param array $exclusions
 *
 * @return false|int
 */
function is_url_excluded_from_tracking( $url, $exclusions = [] ) {

	$exclusions_regex = get_url_exclusions_regex( $exclusions );

	$matched = $exclusions_regex !== false ? preg_match( $exclusions_regex, $url ) : false;

	return apply_filters( 'groundhogg/is_url_excluded_from_tracking', $matched, $url, $exclusions_regex );
}

/**
 * Whether the tracking precedence option is enabled.
 *
 * @return mixed|void
 */
function is_ignore_user_tracking_precedence_enabled() {
	return apply_filters( 'groundhogg/tracking/ignore_user_precedence', is_option_enabled( 'gh_ignore_user_precedence' ) );
}

/**
 * Check if a contact and a user match
 *
 * @param $contact Contact|int
 * @param $user    \WP_User|int
 *
 * @return bool
 */
function contact_and_user_match( $contact, $user ) {

	if ( is_int( $contact ) ) {
		$contact = get_contactdata( $contact );
	}

	if ( ! is_a_contact( $contact ) ) {
		return false;
	}

	if ( is_int( $user ) ) {
		$user = get_userdata( $user );
	}

	if ( ! is_a_user( $user ) ) {
		return false;
	}

	return $contact->get_email() === $user->user_email;
}

/**
 * Whether the current user matches the current contact with the same email address.
 *
 * @return bool
 */
function current_contact_and_logged_in_user_match() {

	if ( ! is_user_logged_in() || ! get_contactdata() ) {
		return false;
	}

	return contact_and_user_match( get_contactdata(), wp_get_current_user() );
}

/**
 * Fix nested P tags caused by formatted replacements.
 *
 * @param $content
 *
 * @return string|string[]|null
 */
function fix_nested_p( $content ) {

	$patterns = [
		'@(<p[^>]*>)([^>]*)(<p[^>]*>)@m', // opening tags
		'@(<\/p[^>]*>)([^>]*)(<\/p[^>]*>)@m' // closing tags
	];

	$replacements = [
		'$1$2',
		'$2$3',
	];

	return preg_replace( $patterns, $replacements, $content );
}

/**
 * Track activity that happens on site caused by a human.
 * Use the tracking cookie to populate the main arguments.
 *
 * @param $type
 * @param $details
 */
function track_live_activity( $type, $details = [] ) {

	// Use tracked contact
	$contact = get_contactdata();

	// If there is not one available, skip
	if ( ! is_a_contact( $contact ) ) {
		return;
	}

	$args = [
		'funnel_id'  => tracking()->get_current_funnel_id(),
		'contact_id' => tracking()->get_current_contact_id(),
		'email_id'   => tracking()->get_current_email_id(),
		'event_id'   => tracking()->get_current_event() ? tracking()->get_current_event()->get_id() : false,
		'referer'    => tracking()->get_leadsource(),
	];

	track_activity( $contact, $type, $args, $details );
}

/**
 * Log an activity conducted by the contact while they are performing actions on the site.
 * Uses the cookie details for reporting.
 *
 * @param string  $type    string, an activity identifier
 * @param array   $args    the details for the activity
 * @param array   $details details about that activity
 * @param Contact $contact the contact to track
 */
function track_activity( $contact, $type = '', $args = [], $details = [] ) {

	// If there is not one available, skip
	if ( ! is_a_contact( $contact ) ) {
		return;
	}

	// use tracking cookies to generate information for the activity log
	$defaults = [
		'activity_type' => $type,
		'timestamp'     => time(),
	];

	// Merge overrides with args
	$args = wp_parse_args( $defaults, $args );
	$args = apply_filters( 'groundhogg/track_live_activity/args', $args, $contact );

	// Add the activity to the DB
	$id = get_db( 'activity' )->add( $args );

	if ( ! $id ) {
		return;
	}

	$activity = new Activity( $id );

	// Add any details to the activity meta
	foreach ( $details as $detail_key => $value ) {
		$activity->update_meta( $detail_key, $value );
	}

	/**
	 * Fires after some activity is tracked
	 *
	 * @param $activity Activity
	 * @param $contact  Contact
	 */
	do_action( 'groundhogg/track_activity', $activity, $contact );
}

/**
 * Return json response for meta picker.
 */
function handle_ajax_meta_picker() {

	if ( ! current_user_can( 'edit_contacts' ) || ! wp_verify_nonce( get_post_var( 'nonce' ), 'meta-picker' ) ) {
		wp_send_json_error();
	}

	$search = sanitize_text_field( get_post_var( 'term' ) );

	$table = get_db( 'contactmeta' );

	global $wpdb;

	$keys = $wpdb->get_col(
		"SELECT DISTINCT meta_key FROM {$table->get_table_name()} WHERE `meta_key` RLIKE '{$search}' ORDER BY meta_key ASC"
	);

	$response = array_map( function ( $key ) {
		return [
			'id'    => $key,
			'label' => $key,
			'value' => $key
		];
	}, $keys );

	/**
	 * Filter the json response for the meta key picker
	 *
	 * @param $response array[]
	 * @param $search   string
	 */
	$response = apply_filters( 'groundhogg/handle_ajax_meta_picker', $response, $search );

	wp_send_json( $response );
}

add_action( 'wp_ajax_gh_meta_picker', __NAMESPACE__ . '\handle_ajax_meta_picker' );

/**
 * Quick formatting function for Y-m-d H:i:s date time.
 *
 * @param false $time
 *
 * @return false|string
 */
function Ymd_His( $time = false ) {
	return date( 'Y-m-d H:i:s', $time ?: time() );
}

/**
 * Find which plugin has wp_mail defined.
 *
 * @return string|false
 */
function extrapolate_wp_mail_plugin() {

	try {
		$reflFunc = new \ReflectionFunction( 'wp_mail' );
		$defined  = wp_normalize_path( $reflFunc->getFileName() );
	} catch ( \ReflectionException $e ) {
		return false;
	}

	$active_plugins = get_option( 'active_plugins', [] );

	foreach ( $active_plugins as $active_plugin ) {

		$plugin_dir = 'wp-content/plugins/' . dirname( $active_plugin ) . '/';

		if ( strpos( $defined, $plugin_dir ) !== false ) {
			return $active_plugin;
		}
	}

	// No active plugins are the cause, that means a plugin is probably including pluggable.php explicitly somewhere...
	// BAD JuJu guys...

	// todo, find out which plugin includes pluggable before it's supposed to.

	return $defined;
}

/**
 * Tracks pings to the wp-cron.php file
 */
function track_wp_cron_ping() {
	if ( defined( 'DOING_CRON' ) && DOING_CRON && ( ! defined( 'DOING_GH_CRON' ) || ! DOING_GH_CRON ) ) {
		update_option( 'wp_cron_last_ping', time() );
	}
}

add_action( 'wp_loaded', __NAMESPACE__ . '\track_wp_cron_ping' );

/**
 * Tracks the pings of the gh-cron.php.
 */
function track_gh_cron_ping() {
	update_option( 'gh_cron_last_ping', time() );
}

add_action( 'groundhogg_process_queue', __NAMESPACE__ . '\track_gh_cron_ping', 9 );

/**
 * Same as array_map, but passes both the key AND the value
 *
 * @param $array    array
 * @param $callback callable
 *
 * @return array
 */
function array_map_with_keys( array $array, callable $callback ): array {
	foreach ( $array as $i => &$v ) {
		$v = call_user_func( $callback, $v, $i );
	}

	return $array;
}

/**
 * Same as array_map, but modifies the key instead of the value
 *
 * @param $array    array
 * @param $callback callable
 *
 * @return array
 */
function array_map_keys( array $array, callable $callback ): array {

	$new_array = [];

	foreach ( $array as $i => $v ) {
		$i               = call_user_func( $callback, $i, $v );
		$new_array[ $i ] = $v;
	}

	return $new_array;
}

/**
 * Sanitize any type email header
 *
 * @param $header_value
 * @param $header_type
 *
 * @return string
 */
function sanitize_email_header( $header_value, $header_type ): string {
	switch ( $header_type ) {
		case 'from':
			// If only the email is provided
			if ( is_email( $header_value ) ) {
				$header_value = has_replacements( $header_value ) ? sanitize_text_field( $header_value ) : sanitize_email( $header_value );
			} else if ( preg_match( '/([^<]+) <([^>]+)>/', $header_value, $matches ) ) {
				$email_address = has_replacements( $matches[2] ) ? sanitize_text_field( $matches[2] ) : sanitize_email( $matches[2] );
				$name          = sanitize_text_field( $matches[1] );
				$header_value  = sprintf( '%s <%s>', $name, $email_address );
			} else {
				$header_value = '';
			}
			break;
		case 'bcc':
		case 'cc':
		case 'return-path':
		case 'reply-to':
			$emails       = explode( ',', $header_value );
			$emails       = map_deep( $emails, 'trim' );
			$emails       = map_deep( $emails, function ( $email ) {
				if ( has_replacements( $email ) ) {
					return sanitize_text_field( $email );
				} else {
					return sanitize_email( $email );
				}
			} );
			$emails       = implode( ',', array_filter( $emails ) );
			$header_value = $emails;
			break;
		default:
			$header_value = sanitize_text_field( $header_value );
			break;
	}

	return $header_value;
}

/**
 * Uninstall Groundhogg
 *
 * Deletes
 * - All DB tables
 * - All Options/Transients
 * - All Meta
 * - All Cron Jobs
 * - Any installed files
 */
function uninstall_groundhogg() {

	global $wpdb;


	//Delete DBS
	Plugin::$instance->dbs->drop_dbs();

	$other_tables = [
		'gh_contractmeta',
		'gh_contracts',
		'gh_dealmeta',
		'gh_deals',
		'gh_pipelines_stages',
		'gh_pipelines',
		'gh_proof',
		'gh_calendarmeta',
		'gh_calendar',
		'gh_appointmentmeta',
		'gh_appointments'

	];

	foreach ( $other_tables as $table ) {
		$table_name = $wpdb->prefix . $table;
		$wpdb->query( "DROP TABLE IF EXISTS " . $table_name );
	}

	//Remove Roles & Caps
	Plugin::$instance->roles->remove_roles_and_caps();

	//Remove all files
	Plugin::$instance->utils->files->delete_all_files();

	/** Cleanup Cron Events */
	wp_clear_scheduled_hook( Event_Queue::WP_CRON_HOOK );
	wp_clear_scheduled_hook( Bounce_Checker::ACTION );
	wp_clear_scheduled_hook( Stats_Collection::ACTION );
	wp_clear_scheduled_hook( 'groundhogg/sending_service/verify_domain' );

	//delete api keys from user_meta
	delete_metadata( 'user', 0, 'wpgh_user_public_key', '', true );
	delete_metadata( 'user', 0, 'wpgh_user_secret_key', '', true );

	// Remove any transients and options we've left behind
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'gh\_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'wpgh\_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_wpgh\_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_gh\_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_site\_transient\_wpgh\_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_site\_transient\_gh\_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_timeout\_wpgh\_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_timeout\_gh\_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_site\_transient\_timeout\_wpgh\_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_site\_transient\_timeout\_gh\_%'" );

	uninstall_gh_cron_file();

	do_action( 'groundhogg/uninstall' );

	if ( ob_get_contents() ) {
		file_put_contents( __DIR__ . '/../groundhogg-uninstall-errors.txt', ob_get_contents() );
	}
}
