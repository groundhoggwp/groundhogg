<?php

namespace Groundhogg;

use Groundhogg\Classes\Activity;
use Groundhogg\Classes\Page_Visit;
use Groundhogg\Lib\Mobile\Mobile_Validator;
use Groundhogg\Queue\Event_Queue;
use Groundhogg\Queue\Process_Contact_Events;
use Groundhogg\Utils\DateTimeHelper;
use WP_Error;


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds functional support for getting some options from constants instead
 *
 * @param $option_name string the name of the option
 *
 * @return void
 */
function add_constant_support( $option_name ) {

	$hook     = "pre_option_$option_name";
	$callback = __NAMESPACE__ . '\maybe_get_option_from_constant';

	if ( ! has_filter( $hook, $callback ) ) {
		add_filter( $hook, $callback, 10, 2 );
	}
}

/**
 * Given an option name, check if it's defined as a constant and if it is, return that value instead
 *
 * @param $value       false|null
 * @param $option_name string
 *
 * @return mixed
 */
function maybe_get_option_from_constant( $value, $option_name ) {
	$constant = strtoupper( $option_name );

	if ( defined( $constant ) ) {
		return constant( $constant );
	}

	return $value;
}

add_constant_support( 'gh_master_license' );
add_constant_support( 'gh_recaptcha_secret_key' );
add_constant_support( 'gh_recaptcha_site_key' );

/**
 * If an email address is provided but a space is in place of a plus then swap out the space for a plus
 *
 * @param $str string
 */
function maybe_change_space_to_plus_in_email( $str ) {

	// No space, quit
	if ( strpos( $str, ' ' ) === false ) {
		return $str;
	}

	// replace the space with a plus and run is_email
	$maybe_email = str_replace( ' ', '+', $str );

	if ( is_email( $maybe_email ) ) {
		return $maybe_email;
	}

	return $str;
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
 * Get a contact
 *
 * @param $contact_id_or_email mixed
 * @param $by_user_id
 *
 * @return false|Contact
 */
function get_contactdata( $contact_id_or_email = false, $by_user_id = false ) {

	// We already have a contact
	if ( is_a_contact( $contact_id_or_email ) ) {
		return $contact_id_or_email;
	}

	if ( empty( $contact_id_or_email ) ) {

        // From queue?
		if ( Event_Queue::is_processing() ) {
			return \Groundhogg\event_queue()->get_current_contact();
		}

        // From tracking?
		if ( $contact = tracking()->get_current_contact() ) {
			return $contact;
		}

		return false;
	}

	static $cache = [];

	$cache_key = is_numeric( $contact_id_or_email ) ? $contact_id_or_email . ':' . $by_user_id : $contact_id_or_email;

    if ( key_exists( $cache_key, $cache ) ) {
		return $cache[ $cache_key ];
	}

	$contact = new Contact( $contact_id_or_email, $by_user_id );

	if ( $contact->exists() ) {
        // Set the contact in the cache
		$cache[ $cache_key ] = $contact;

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
	}

    return false;
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
 * @param string       $fragment
 *
 * @return string
 */
function admin_page_url( $page, $args = [], $fragment = '' ) {

	if ( is_array( $page ) ) {
		$url = admin_page_url( get_url_var( 'page' ), $page );

		if ( is_string( $args ) ) {
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

function report_link( $content, $params ){
    return html()->e('a', [
        'href' => admin_page_url( 'gh_reporting', $params )
    ], $content );
}

/**
 * open the contacts page with specific filters
 *
 * @param $content
 * @param $filters
 *
 * @return string
 */
function contact_filters_link( $content, $filters, $link = true ) {

    if ( ! $link  ){
        return $content;
    }

	return html()->e( 'a', [
		'target' => '_blank',
		'href'   => admin_page_url( 'gh_contacts', [
			'filters' => base64_json_encode( array_values( $filters ) )
		] )
	], $content );
}

/**
 * Removes evertything after the ? from the URL
 *
 * @param $url
 *
 * @return string
 */
function remove_query_string_from_url( $url ) {
	return preg_replace( '@\?.*@', '', $url );
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
 * @return string
 */
function last_db_error() {
	global $wpdb;

	return $wpdb->last_error;
}

/**
 * @return string
 */
function last_db_query() {
	global $wpdb;

	return $wpdb->last_query;
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

	/**
	 * Whether the option is enabled or not.
	 *
	 * @param $enabled bool
	 * @param $option  string
	 */
	return apply_filters( 'groundhogg/is_option_enabled', ( ! is_array( $option ) && $option ) || ( is_array( $option ) && in_array( 'on', $option ) ), $option );
}

/**
 * Shorthand;
 *
 * @return HTML
 */
function html() {
	return Plugin::instance()->utils->html;
}

/**
 * Shorthand
 *
 * @return Notices
 */
function notices() {
	return Plugin::instance()->notices;
}

/**
 * @return Replacements
 */
function replacements() {
	return Plugin::instance()->replacements;
}

/**
 * @return Tracking
 */
function tracking() {
	return Plugin::instance()->tracking;
}

/**
 * @return Utils
 */
function utils() {
	return Plugin::instance()->utils;
}

function bulk_jobs() {
	return Plugin::instance()->bulk_jobs;
}

/**
 * @return Files
 */
function files() {
	return utils()->files;
}

/**
 * @return Event_Queue
 */
function event_queue() {
	return Plugin::instance()->event_queue;
}

/**
 * @return \Groundhogg\DB\Event_Queue
 */
function event_queue_db() {
	return get_db( 'event_queue' );
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
	return wp_unslash( urldecode_deep( get_array_var( $_GET, $key, $default ) ) );
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
 * base64 encode safe for url
 *
 * @param $stuff
 *
 * @return array|string|string[]
 */
function base64url_encode( $stuff ) {
	return str_replace( [ '+', '/', '=' ], [ '-', '_', '' ], base64_encode( $stuff ) );
}

/**
 * Base64 decode stuff from url
 *
 * @param $stuff
 *
 * @return false|string
 */
function base64url_decode( $stuff ) {
	return base64_decode( str_replace( [ '-', '_' ], [ '+', '/' ], $stuff ) );
}

/**
 * Encodes a string via json and base64, typically for a URL
 *
 * @param $query
 *
 * @return string
 */
function base64_json_encode( $query ) {
	return base64url_encode( wp_json_encode( $query ) );
}

/**
 * Given a string which is assumed to be base64 encoded json, decode it
 *
 * @param $query $string
 *
 * @return mixed
 */
function base64_json_decode( $string ) {
	return json_decode( base64url_decode( $string ), true );
}

/**
 * Utility function to serialize stuff and md5 it, useful for creating
 * keys based on prop values
 *
 * @param $stuff
 *
 * @return string
 */
function md5serialize( $stuff ) {
	return md5( maybe_serialize( $stuff ) );
}

/**
 * Helper function to generate a semi unique JOIN alias given a filter
 *
 * @param $filter
 * @param $prefix
 *
 * @return string
 */
function alias_from_filter( $filter ) {

	$type = $filter['type'];

	unset( $filter['type'] );
	unset( $filter['id'] );
	unset( $filter['count'] ); // not relevant to JOIN
	unset( $filter['count_compare'] ); // not relevant to JOIN
	ksort( $filter );

	return $type . '_' . preg_replace( '/[^A-Za-z0-9_]+/', '_', implode( '_', array_filter( $filter ) ) );
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
		'submit',
        'operation'
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

	if ( isset_not_empty( $query, 'filters' ) && is_string( $query['filters'] ) ) {
		$query['filters'] = base64_json_decode( $query['filters'] );
	}
	if ( isset_not_empty( $query, 'include_filters' ) && is_string( $query['include_filters'] ) ) {
		$query['include_filters'] = base64_json_decode( $query['include_filters'] );
	}
	if ( isset_not_empty( $query, 'exclude_filters' ) && is_string( $query['exclude_filters'] ) ) {
		$query['exclude_filters'] = base64_json_decode( $query['exclude_filters'] );
	}

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
	return parse_tag_list( $maybe_tags, 'ID', current_user_can( 'add_tags' ) );
}

/**
 * Replacements Wrapper.
 *
 * @param string      $content
 * @param int|Contact $contact_id
 * @param string      $context what context the replacements are being used for. 'html' or 'plain'
 *
 * @return string
 */
function do_replacements( $content = '', $contact_id = 0, $context = 'html' ) {
	return Plugin::$instance->replacements->process( $content, $contact_id, $context );
}

/**
 * Pass plain as the context to do_replacements
 *
 * @param string $content
 * @param int    $contact_id
 *
 * @return string
 */
function do_replacements_plain_text( $content = '', $contact_id = 0 ) {
	return do_replacements( $content, $contact_id, 'plain' );
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
 * Find an item in an array based on a predicate
 *
 * @param $array     array
 * @param $predicate callable
 *
 * @return mixed|false if item is not found return false
 */
function array_find( array $array, callable $predicate ) {

	foreach ( $array as $item ) {
		if ( call_user_func( $predicate, $item ) ) {
			return $item;
		}
	}

	return false;
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
 * @param     $denom int denominator
 * @param     $numer int numerator
 * @param int $precision
 *
 * @return float
 */
function percentage( $denom, $numer, $precision = 2 ) {
	$denom = intval( $denom );
	$numer = intval( $numer );

	if ( ! $denom ) {
		return 0;
	}

	return round( ( $numer / $denom ) * 100, $precision );
}

/**
 * @param $num
 * @param $compare
 *
 * @return string
 */
function format_number_with_percentage( $num, $compare ) {

    if ( empty ($num) && empty($compare)){
        return '-';
    }

	return sprintf( '%s%% (%s)', _nf( percentage( $compare, $num ) ), _nf( $num ), );
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
		if ( strpos( $wp_style->src, $themes_uri ) !== false || strpos( $wp_style->handle, $theme_name ) !== false ) {
			wp_dequeue_style( $wp_style->handle );
		}
	}

	$additional = [
		'fusion-dynamic-css'
	];

	foreach ( $additional as $style ) {
		wp_dequeue_style( $style );
	}
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

	if ( ! is_array( $atts ) ) {
		return '';
	}

	foreach ( $atts as $key => $value ) {

		$key = strtolower( $key );

		switch ( $key ) {
			case 'value':
				$value = esc_attr( $value );
				break;
			case 'cellpadding':
			case 'cellspacing':
				$value = absint( $value );
				break;
			case 'width':
			case 'height':

				if ( str_ends_with( $value, '%' ) ) {
					$value = absint( substr( $value, 0, - 1 ) ) . '%';
				} else {
					$value = absint( $value );
				}
				break;
			case 'class':
				$value = esc_attr( trim( is_array( $value ) ? implode( ' ', $value ) : $value ) );
				break;
			case 'style':
				$value = array_to_css( $value );
				break;
			case 'href':
			case 'action':
			case 'src':
				$value = strpos( $value, 'data:image/png;base64,' ) === false ? esc_url( $value ) : esc_attr( $value );
				break;
			default:

				if ( empty( $value ) ) {
					continue 2;
				}

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
 * Converts Camel Case to Kebab Case
 *
 * @param $string
 *
 * @return string
 */
function camel_to_kebab( $string ) {

	$string = preg_replace_callback( '/([a-z])([A-Z])/', function ( $matches ) {
		return strtolower( $matches[1] ) . '-' . strtolower( $matches[2] );
	}, $string );

	return sanitize_key( $string );
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

		if ( is_null( $value ) ) {
			continue;
		}

		if ( is_array( $value ) ) {
			$value = implode( ' ', $value );
		}

		$attribute = camel_to_kebab( $key );

		switch ( $attribute ) {
			case 'use':
				continue 2;
			case 'height':
			case 'width':
			case 'font-size':
				$value = is_numeric( $value ) ? $value . 'px' : esc_attr( $value );
				break;
			case 'font-family':
				$value = esc_attr( str_replace( '"', '\'', $value ) );
				break;
			default:
				$value = esc_attr( $value );
				break;
		}

		$css .= $attribute . ':' . $value . ';';
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
function set_cookie( $cookie = '', $value = '', $expiration = HOUR_IN_SECONDS ) {
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
	return set_cookie( $cookie, '', - 3600 );
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
 * The hostname with no www.
 *
 * @return string
 */
function get_hostname( $url = '' ) {
	$hostname = wp_parse_url( $url ?: home_url(), PHP_URL_HOST );

	if ( ! $hostname ) {
		return false;
	}

	if ( substr( $hostname, 0, 4 ) == 'www.' ) {
		$hostname = substr( $hostname, 4 );
	}

	return $hostname;
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

	if ( empty( $from ) ) {
		$from = 'wordpress@' . get_hostname();
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
 * @param string|array $attachments Optional. Files to attach.
 *
 * @param string|array $to          Array or comma-separated list of email addresses to send message.
 *
 * @param string       $subject     Email subject
 *
 * @param string       $message     Message contents
 *
 * @param string|array $headers     Optional. Additional headers.
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
	if ( ! isset( $_REQUEST['_wp_http_referer'] ) ) {
		return wp_get_referer();
	}

	return ( is_ssl() ? "https" : "http" ) . "://{$_SERVER['HTTP_HOST']}" . $_REQUEST['_wp_http_referer'];
}

/**
 * Recount the contacts per tag...
 *
 * @deprecated 3.2 no replacement
 */
function recount_tag_contacts_count() {
	_deprecated_function( 'recount_tag_contacts_count', '3.2.3' );
}

/**
 * Create a contact quickly from a user account.
 *
 * @param $user      \WP_User|int
 * @param $sync_meta bool whether to copy the meta data over.
 *
 * @return Contact|false|WP_Error the new contact, false on failure, or WP_Error on error
 */
function create_contact_from_user( $user = false, $sync_meta = false ) {

	if ( is_int( $user ) ) {
		$user = get_userdata( $user );
	} else if ( $user === false ) {
		$user = wp_get_current_user();
	}

	if ( ! is_a( $user, '\WP_User' ) ) {
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

	// Update the locale of the contact
	$contact->set_locale( $user->locale );

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

	// Filter all the user args
	$new_user_args = apply_filters( 'groundhogg/create_user_from_contact/new_user_args', [
		'user_pass'     => wp_generate_password(),
		'user_email'    => $contact->get_email(),
		// Filter the user login
		'user_login'    => apply_filters( 'groundhogg/create_user_from_contact/user_login', $contact->get_email(), $contact ),
		'user_nicename' => $contact->get_full_name(),
		'display_name'  => $contact->get_full_name(),
		'first_name'    => $contact->get_first_name(),
		'last_name'     => $contact->get_last_name(),
		'role'          => $role,
		'locale'        => $contact->get_locale()
	], $contact );

	$user_id = wp_insert_user( $new_user_args );

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
 * Provides a global hook not requiring the benchmark anymore.
 *
 * @param $userId int the Id of the user
 */
function convert_user_to_contact_when_user_registered( $userId ) {

	$user = get_userdata( $userId );

	if ( ! $user || is_wp_error( $user ) ) {
		return;
	}

	/**
	 * Whether the new user should be automatically converted to a contact or not
	 *
	 * @param $should  bool
	 * @param $user_id int
	 * @param $user    \WP_User
	 *
	 * @return bool
	 */
	if ( ! apply_filters( 'groundhogg/should_convert_user_to_contact_when_user_registered', ! is_option_enabled( 'gh_disable_user_sync' ), $userId, $user ) ) {
		return;
	}

	$contact = create_contact_from_user( $user, is_option_enabled( 'gh_sync_user_meta' ) );

	if ( ! $contact || is_wp_error( $contact ) ) {
		return;
	}

	// Do not run when in admin or QUEUE is processing
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
		'step_type' => [ 'form_fill', 'web_form' ]
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
 * @param int|Email          $email_id            the ID of the email to send
 * @param int|string|Contact $contact_id_or_email the ID of the contact to send to
 * @param int                $time                time time to send at, defaults to time()
 *
 * @return bool whether the scheduling was successful.
 */
function send_email_notification( $email_id, $contact_id_or_email, $time = 0 ) {

	$contact = is_a_contact( $contact_id_or_email ) ? $contact_id_or_email : get_contactdata( $contact_id_or_email );
	$email   = is_numeric( $email_id ) ? new Email( $email_id ) : $email_id;

	if ( ! $contact || ! $email ) {
		return false;
	}

	$time = $time ? ( is_string( $time ) ? strtotime( $time ) : $time ) : time();

	$event = [
		'time'       => $time,
		'funnel_id'  => 0,
		'step_id'    => $email->get_id(),
		'email_id'   => $email->get_id(),
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

	if ( ! is_a_contact( $contact ) ) {
		return;
	}

	// If they re-opt-in we consider them unconfirmed
	if ( ! $contact->is_marketable() ) {

		// Opt-in status is already unconfirmed
		if ( $contact->optin_status_is( Preferences::UNCONFIRMED ) ) {
			// this will take care of double opt-in requirements
			$contact->reset_date_optin_status_changed();
		} else {
			$contact->change_marketing_preference( Preferences::UNCONFIRMED );
		}
	}

	/**
	 * Helper function.
	 *
	 * @param $contact Contact
	 */
	do_action( 'groundhogg/after_form_submit', $contact );
}

add_action( 'groundhogg/after_form_submit', __NAMESPACE__ . '\extrapolate_location_after_signup', 9 );

// If we are able to extrapolate the location, we can fix the mobile number if provided
add_action( 'groundhogg/after_form_submit', __NAMESPACE__ . '\maybe_validate_and_update_mobile_number', 9 );

/**
 * Get the location of a contact record when they signup
 *
 * @param $contact Contact
 */
function extrapolate_location_after_signup( $contact ) {
	// Update their location based on the current IP address.
	if ( apply_filters( 'groundhogg/should_extrapolate_location', true ) && $contact->update_meta( 'ip_address', utils()->location->get_real_ip() ) ) {
		$contact->extrapolate_location();
	}
}

add_action( 'groundhogg/after_form_submit', __NAMESPACE__ . '\save_source_page_after_signup', 9 );

/**
 * Save the source page if one is not set already
 *
 * @param $contact Contact
 */
function save_source_page_after_signup( $contact ) {
	if ( ! $contact->get_meta( 'source_page' ) ) {
		$contact->update_meta( 'source_page', wpgh_get_referer() );
	}
}

add_action( 'groundhogg/after_form_submit', __NAMESPACE__ . '\save_lead_source_after_signup', 9 );

/**
 * Save the source page if one is not set already
 *
 * @param $contact Contact
 */
function set_utm_parameters( $contact ) {

	$utm = get_cookie( Tracking::UTM_COOKIE );

	if ( ! $utm ) {
		return;
	}

	$utm = json_decode( $utm, true );

	if ( empty( $utm ) ) {
		return;
	}

	// If there is a contact, update their UTM stats to the one provided by the campaign
	foreach ( $utm as $utm_var => $utm_val ) {
		if ( ! empty( $utm_val ) ) {
			$contact->update_meta(
				$utm_var,
				sanitize_text_field( $utm_val )
			);
		}
	}
}

add_action( 'groundhogg/after_form_submit', __NAMESPACE__ . '\set_utm_parameters', 9 );

/**
 * Save the leadsource if one is not set already
 *
 * @param $contact Contact
 */
function save_lead_source_after_signup( $contact ) {
	if ( ! $contact->get_meta( 'lead_source' ) ) {
		$contact->update_meta( 'lead_source', tracking()->get_leadsource() );
	}
}

add_action( 'groundhogg/after_form_submit', __NAMESPACE__ . '\track_page_visits_after_signup', 9 );

/**
 * Track any non-tracked page visits for a contact record when they first sign up
 *
 * Do not use to track page visits manually, use track_page_visit instead
 *
 * @param $contact Contact
 */
function track_page_visits_after_signup( $contact ) {

	// Track any non logged page visits for the new contact
	$pages_visited = get_cookie( Tracking::PAGE_VISITS_COOKIE );

	if ( empty( $pages_visited ) ) {
		return;
	}

	$pages_visited = json_decode( stripslashes( $pages_visited ), true );

	if ( empty( $pages_visited ) ) {
		return;
	}

	foreach ( $pages_visited as $visit ) {

		$url = get_array_var( $visit, 0 );

		if ( empty( $url ) ) {
			continue;
		}

		$times = get_array_var( $visit, 1 );

		foreach ( $times as $time ) {

			// was already tracked
			if ( $time[1] ) {
				continue;
			}

			track_page_visit( $url, $contact, [
				'timestamp' => absint( $time[0] )
			] );
		}
	}


	// Delete the cookie and clear the results.
	delete_cookie( Tracking::PAGE_VISITS_COOKIE );
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

	// Ensure contact and event are existing
	if ( ! $event->exists() || ! is_a_contact( $event->get_contact() ) ) {
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

	if ( is_array( $name ) ) {
		$name = implode( ' ', $name );
	}

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
 * Return the number of rows in a CSV file
 * todo this seems to be over-counting the total rows by 2
 *
 * @param $file_path
 *
 * @return int
 */
function count_csv_rows( $file_path ) {
	$file = new \SplFileObject( $file_path, 'r' );

    $rows = 0;

	while ( ! $file->eof() ){
		$file->fgets();
		$rows++;
	}

	$file = null;

	return $rows - 2; // -1 to account for the header row
}

/**
 * Get a list of items from a file path, if file does not exist of there are no items return an empty array.
 *
 * @param string $file_path   path to the CSV file
 * @param int    $rows        the number of rows to retrieve, a negative number will mean all rows
 * @param int    $offset      the offset to start
 * @param string $delimiter   the file delimiter, if false it will guess
 * @param bool   $associative whether to return the results as an associative array or regular array
 *
 * @return array
 */
function get_items_from_csv( string $file_path = '', int $rows = 0, int $offset = 0, string $delimiter = '', bool $associative = true ): array {

	if ( ! file_exists( $file_path ) ) {
		return [];
	}

	// If a delimiter is not provided, make a guess.
	if ( ! $delimiter ) {
		$delimiter = get_csv_delimiter( $file_path ) ?: ',';
	}

	$data = array();

	$file = new \SplFileObject( $file_path, 'r' );

	// Headers are always the first row
	$header       = $file->fgetcsv( $delimiter );
	$header_count = count( $header );

	// Get all rows
	if ( ! $rows ) {
		$rows = 999999999;
	}

    // Advance the file pointer
	if ( $offset > 0 ) {
        while ( ! $file->eof() && $offset > 0 ){
            $file->fgets();
			$offset--;
		}
	}

	while ( ! $file->eof() && count( $data ) < $rows ) {

		$row = $file->fgetcsv( $delimiter );

		if ( $associative ) {
			if ( count( $row ) > $header_count ) {

				$row = array_slice( $row, 0, $header_count );
			} else if ( count( $row ) < $header_count ) {

				$row = array_pad( $row, $header_count, '' );
			}

			$data[] = array_combine( $header, $row );
		} else {
			$data[] = $row;
		}
	}

	$file = null;

	return $data;
}

/**
 * Get the pretty name for the header in the export file
 *
 * @param string $header
 * @param string $type
 *
 * @return mixed|string
 */
function export_header_pretty_name( $header = '', $type = 'basic' ) {
	static $keys;

	if ( empty( $keys ) ) {
		$keys = get_exportable_fields();
	}

	$key = $header;

	if ( $type === 'pretty' ) {

		if ( isset_not_empty( $keys, $header ) ) {
			return $keys[ $header ];
		}

		$header = key_to_words( $header );
	}

	return apply_filters( 'groundhogg/export_header_name', $header, $key, $type );
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
		'optin_status'              => __( 'Opt-in Status', 'groundhogg' ),
		'user_id'                   => __( 'User Id', 'groundhogg' ),
		'owner_id'                  => __( 'Owner Id', 'groundhogg' ),
		'date_created'              => __( 'Date Created', 'groundhogg' ),
		'date_optin_status_changed' => __( 'Date Opt-in Status Changed', 'groundhogg' ),
		'birthday'                  => __( 'Birthday', 'groundhogg' ),
		'mobile_phone'              => __( 'Mobile Phone Number', 'groundhogg' ),
		'primary_phone'             => __( 'Primary Phone Number', 'groundhogg' ),
		'primary_phone_extension'   => __( 'Primary Phone Number Extension', 'groundhogg' ),
		'street_address_1'          => __( 'Street Address 1', 'groundhogg' ),
		'street_address_2'          => __( 'Street Address 2', 'groundhogg' ),
		'city'                      => __( 'City', 'groundhogg' ),
		'postal_zip'                => __( 'Postal/Zip', 'groundhogg' ),
		'region'                    => __( 'Province/State/Region', 'groundhogg' ),
		'country'                   => __( 'Country', 'groundhogg' ),

//		'company_name'              => __( 'Company Name', 'groundhogg' ),
//		'company_address'           => __( 'Full Company Address', 'groundhogg' ),
//		'company_phone'             => __( 'Company Phone Number', 'groundhogg' ),
//		'company_phone_extension'   => __( 'Company Phone Number Extension', 'groundhogg' ),
//		'job_title'                 => __( 'Job Title', 'groundhogg' ),

		'time_zone'              => __( 'Time Zone', 'groundhogg' ),
		'ip_address'             => __( 'IP Address', 'groundhogg' ),
		'lead_source'            => __( 'Lead Source', 'groundhogg' ),
		'source_page'            => __( 'Signup Page', 'groundhogg' ),
		'terms_agreement'        => __( 'Terms Agreement', 'groundhogg' ),
		'gdpr_consent'           => __( 'Data Processing Consent', 'groundhogg' ),
		'gdpr_consent_date'      => __( 'Data Processing Consent Data', 'groundhogg' ),
		'marketing_consent'      => __( 'Marketing Consent', 'groundhogg' ),
		'marketing_consent_date' => __( 'Marketing Consent Date', 'groundhogg' ),
		'notes'                  => __( 'Notes', 'groundhogg' ),
		'tags'                   => __( 'Tags', 'groundhogg' ),
		'utm_campaign'           => __( 'UTM Campaign', 'groundhogg' ),
		'utm_content'            => __( 'UTM Content', 'groundhogg' ),
		'utm_medium'             => __( 'UTM Medium', 'groundhogg' ),
		'utm_term'               => __( 'UTM Term', 'groundhogg' ),
		'utm_source'             => __( 'UTM Source', 'groundhogg' ),
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
		case 'full_name':
			$return = $contact->get_full_name();
			break;
		default:
			$return = $contact->$field;
			break;
		case 'notes':
			$return = wp_json_encode( $contact->get_notes() );
			break;
		case 'tags':
			$tags   = $contact->get_tags( true );
			$names  = array_map_to_method( $tags, 'get_name' );
			$return = implode( ',', $names );

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
		__( 'Contact Info', 'groundhogg' )  => [
			'full_name'                 => __( 'Full Name', 'groundhogg' ),
			'first_name'                => __( 'First Name', 'groundhogg' ),
			'last_name'                 => __( 'Last Name', 'groundhogg' ),
			'email'                     => __( 'Email Address', 'groundhogg' ),
			'optin_status'              => __( 'Opt-in Status', 'groundhogg' ),
			'date_created'              => __( 'Date Created', 'groundhogg' ),
			'date_optin_status_changed' => __( 'Date Opt-in Status Changed', 'groundhogg' ),
			'birthday'                  => __( 'Birthday', 'groundhogg' ),
			'mobile_phone'              => __( 'Mobile Phone Number', 'groundhogg' ),
			'primary_phone'             => __( 'Primary Phone Number', 'groundhogg' ),
			'primary_phone_extension'   => __( 'Primary Phone Number Extension', 'groundhogg' ),
			'contact_id'                => __( 'Contact ID', 'groundhogg' ),
		],
		__( 'User' ) => [
			'user_id'    => __( 'User Id/Login', 'groundhogg' ),
			'user_email' => __( 'User Email', 'groundhogg' ),
		],
		__( 'Contact Owner', 'groundhogg' ) => [
			'owner_id'    => __( 'Owner Id/Login', 'groundhogg' ),
			'owner_email' => __( 'Owner Email', 'groundhogg' ),
		],
		__( 'CRM', 'groundhogg' )           => [
			'notes'     => __( 'Add To Notes', 'groundhogg' ),
			'tags'      => __( 'Apply Value as Tag', 'groundhogg' ),
			'meta'      => __( 'Add as Custom Meta', 'groundhogg' ),
			'copy_file' => __( 'Add as File', 'groundhogg' ),
		],
		__( 'Compliance', 'groundhogg' )    => [
			'terms_agreement'   => __( 'Terms Agreement', 'groundhogg' ),
			'gdpr_consent'      => __( 'Data Processing Consent', 'groundhogg' ),
			'marketing_consent' => __( 'Marketing Consent', 'groundhogg' ),
		],
		__( 'Address', 'groundhogg' )       => [
			'street_address_1' => __( 'Line 1', 'groundhogg' ),
			'street_address_2' => __( 'Line 2', 'groundhogg' ),
			'city'             => __( 'City', 'groundhogg' ),
			'postal_zip'       => __( 'Postal/Zip', 'groundhogg' ),
			'region'           => __( 'Province/State/Region', 'groundhogg' ),
			'country'          => __( 'Country', 'groundhogg' ),
			'time_zone'        => __( 'Time Zone', 'groundhogg' ),
			'ip_address'       => __( 'IP Address', 'groundhogg' ),
		],
		__( 'Tracking', 'groundhogg' )      => [
			'utm_campaign' => __( 'UTM Campaign', 'groundhogg' ),
			'utm_content'  => __( 'UTM Content', 'groundhogg' ),
			'utm_medium'   => __( 'UTM Medium', 'groundhogg' ),
			'utm_term'     => __( 'UTM Term', 'groundhogg' ),
			'utm_source'   => __( 'UTM Source', 'groundhogg' ),
			'lead_source'  => __( 'Lead Source', 'groundhogg' ),
			'source_page'  => __( 'Signup Page', 'groundhogg' ),
		],
	];

	$fields = array_merge( $defaults, $extra );

	return apply_filters( 'groundhogg/mappable_fields', $fields );

}

/**
 * Update an existing contact with mapped data
 *
 * @param $contact Contact
 * @param $fields  array
 * @param $map     array
 *
 * @return false|Contact
 */
function update_contact_with_map( $contact, array $fields, array $map = [] ) {

	$contact = get_contactdata( $contact );

	if ( ! is_a_contact( $contact ) ) {
		return false;
	}

	do_action_ref_array( 'groundhogg/update_contact_with_map/before', [
		&$contact,
		&$fields,
		&$map
	] );

	if ( empty( $map ) ) {
		$keys = array_keys( $fields );
		$map  = array_combine( $keys, $keys );
	}

	$meta        = [];
	$tags        = [];
	$remove_tags = [];
	$notes       = [];
	$args        = [];
	$files       = [];
	$copy        = [];

	// flags
	$gdpr_consent      = false;
	$marketing_consent = false;
	$terms_agreement   = false;

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
				do_action_ref_array( 'groundhogg/update_contact_with_map/default', [
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
			case 'street_address_1' :
			case 'street_address_2':
			case 'city':
			case 'postal_zip':
			case 'region':

//			case 'company_name':
//			case 'company_address':
//			case 'company_phone':
//			case 'company_phone_extension':
//			case 'job_title':

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
					$terms_agreement = true;
				}
				break;
			// Only checks whether value is not empty.
			case 'gdpr_consent':
				if ( ! empty( $value ) ) {
					$gdpr_consent = true;
				}
				break;
			case 'marketing_consent':
				if ( ! empty( $value ) ) {
					$marketing_consent = true;
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
				if ( is_array( $value ) ) {
					$tags = array_merge( $tags, $value );
				} else {
					$maybe_tags = explode( ',', $value );
					$tags       = array_merge( $tags, $maybe_tags );
				}
				break;
			case 'remove_tags':
				if ( is_array( $value ) ) {
					$remove_tags = array_merge( $remove_tags, $value );
				} else {
					$maybe_tags  = explode( ',', $value );
					$remove_tags = array_merge( $remove_tags, $maybe_tags );
				}
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
				if ( is_string( $value ) && is_copyable_file( $value ) ) {
					$copy[] = $value;
					break;
				}
				// Maybe multiple files?
				if ( is_array( $value ) ) {
					foreach ( $value as $string ) {
						if ( is_string( $string ) && is_copyable_file( $string ) ) {
							$copy[] = $string;
						}
					}
				}
				break;
			case 'notes':
				if ( $json = json_decode( $value, true ) ) {
					$notes = array_merge( $notes, $json );
				} else {
					$notes[] = sanitize_textarea_field( $value );
				}
				break;
			case 'time_zone':
				$zones = Plugin::$instance->utils->location->get_time_zones();
				// valid timezone
				if ( key_exists( $value, $zones ) ) {
					$meta[ $field ] = $value;
				}
				break;
			case 'ip_address':
				$ip = filter_var( $value, FILTER_VALIDATE_IP );
				if ( $ip ) {
					$meta[ $field ] = $ip;
				}
				break;
			case 'birthday':

				if ( empty( $value ) ) {
					$meta['birthday'] = '';
					break;
				}

				if ( is_string( $value ) ) {
					$meta['birthday'] = Ymd( strtotime( $value ) );
					break;
				}

				if ( is_array( $value ) ) {
					$year  = absint( $value['year'] );
					$month = absint( $value['month'] );
					$day   = absint( $value['day'] );

					// Not a complete birthday?
					if ( ! $year || ! $month || ! $day ) {
						break;
					}

					$meta['birthday'] = Ymd(
						mktime( 0, 0, 0, $month, $day, $year )
					);
				}

				break;
		}

	}

	$contact->update( $args );

	if ( $gdpr_consent ) {
		$contact->set_gdpr_consent();
	}

	if ( $marketing_consent ) {
		$contact->set_marketing_consent();
	}

	if ( $terms_agreement ) {
		$contact->set_terms_agreement();
	}

	// Add Tags
	if ( ! empty( $tags ) ) {
		$contact->apply_tag( $tags );
	}

	// Remove tags
	if ( ! empty( $remove_tags ) ) {
		$contact->remove_tag( $remove_tags );
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

	/**
	 * @param $contact Contact the contact record
	 * @param $map     array the map of given data to contact data
	 * @param $fields  array the values of the given fields
	 */
	do_action( 'groundhogg/update_contact_with_map/after', $contact, $map, $fields );

	return $contact;
}

/**
 * Generate a contact from given associative array and a field map.
 *
 * @throws \Exception
 *
 * @param $fields array the raw data from the source
 *
 * @param $map    array map of field_ids to contact keys
 *
 * @return Contact|false
 */
function generate_contact_with_map( $fields, $map = [] ) {

	if ( empty( $map ) ) {
		$keys = array_keys( $fields );
		$map  = array_combine( $keys, $keys );
	}

	do_action_ref_array( 'groundhogg/generate_contact_with_map/before', [
		&$fields,
		&$map
	] );

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
			case 'contact_id':
				$args[ $field ] = absint( $value );
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
			case 'user_email':
			case 'owner_email':

				if ( ! is_email( $value ) ) {
					break;
				}

				$user = get_user_by( 'email', $value );

				// Make sure User exists
				if ( ! $user ) {
					break;
				}

				$swap = [
					'user_email'  => 'user_id',
					'owner_email' => 'owner_id'
				];

				// Check the mapped owner can actually own contacts.
				if ( $field !== 'owner_email' || user_can( $user, 'edit_contacts' ) ) {
					$args[ $swap[ $field ] ] = $user->ID;
				}

				break;
			case 'user_id':
			case 'owner_id':

				if ( is_email( $value ) ) {
					// Email Passed
					$by = 'email';
				} else if ( is_string( $value ) && ! is_numeric( $value ) ) {
					// Username passed
					$by = 'login';
				} else {
					// ID Passed
					$by    = 'id';
					$value = absint( $value );
				}

				$user = get_user_by( $by, $value );

				// Make sure User exists
				if ( $user ) {
					// Check the mapped owner can actually own contacts.
					if ( $field !== 'owner_id' || user_can( $user, 'edit_contacts' ) ) {
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
			case 'gdpr_consent':
			case 'marketing_consent':
				if ( ! empty( $value ) ) {
					$args[ $field ] = true;
				}
				break;
			case 'country':
				if ( strlen( $value ) !== 2 ) {
					$countries = utils()->location->get_countries_list();
					$code      = array_search( $value, $countries );
					if ( $code ) {
						$value = $code;
					}
				}
				$meta[ $field ] = $value;
				break;
			case 'tags':

				if ( is_string( $value ) ) {
					$value = explode( ',', $value );
				}

				if ( ! is_array( $value ) ) {
					$value = [ $value ];
				}

				$tags = array_merge( $tags, $value );
				break;
			case 'meta':
				$meta[ get_key_from_column_label( $column ) ] = sanitize_text_field( $value );
				break;
			case 'files':

				// passed path as the value
				if ( file_exists( $value ) ) {
					$files[ $column ] = $value;
				} // ZGet from $_FILES
				else if ( isset_not_empty( $_FILES, $column ) ) {
					$files[ $column ] = wp_unslash( get_array_var( $_FILES, $column ) );
				}
				break;

			case 'copy_file':
				if ( is_string( $value ) && is_copyable_file( $value ) ) {
					$copy[] = $value;
					break;
				}
				// Maybe multiple files?
				if ( is_array( $value ) ) {
					foreach ( $value as $string ) {
						if ( is_string( $string ) && is_copyable_file( $string ) ) {
							$copy[] = $string;
						}
					}
				}
				break;
			case 'notes':

				if ( is_array( $value ) ) {
					$notes = array_merge( $notes, $value );
					break;
				}

				if ( $json = json_decode( $value, true ) ) {
					$notes = array_merge( $notes, $json );
				} else {
					$notes[] = sanitize_textarea_field( $value );
				}

				break;
			case 'time_zone':
				$zones = utils()->location->get_time_zones();
				// valid timezone
				if ( key_exists( $value, $zones ) ) {
					$meta[ $field ] = $value;
				}
				break;
			case 'ip_address':
				$ip = filter_var( $value, FILTER_VALIDATE_IP );
				if ( $ip ) {
					$meta[ $field ] = $ip;
				}
				break;
			case 'birthday':

				if ( empty( $value ) ) {
					$meta['birthday'] = '';
					break;
				}

				if ( is_string( $value ) ) {
					$meta['birthday'] = Ymd( strtotime( $value ) );
					break;
				}

				if ( is_array( $value ) ) {
					$year  = absint( $value['year'] );
					$month = absint( $value['month'] );
					$day   = absint( $value['day'] );

					// not a complete birthday?
					if ( ! $year || ! $month || ! $day ) {
						break;
					}

					$meta['birthday'] = Ymd(
						mktime( 0, 0, 0, $month, $day, $year )
					);
				}

				break;
		}

	}

	$contact = false;

	if ( isset_not_empty( $args, 'email' ) ) {

		// Get given email
		if ( ! is_email( $args['email'] ) ) {
			return false;
		}

		$contact = new Contact( [ 'email' => $args['email'] ] );

	} else {

		if ( isset_not_empty( $args, 'user_id' ) ) {

			// Get by given user id
			$contact = get_contactdata( $args['user_id'], true );

		} else if ( isset_not_empty( $args, 'contact_id' ) ) {

			// Get by given contact id
			$contact = get_contactdata( $args['contact_id'] );
			unset( $args['contact_id'] );

		} else if ( ! current_user_can( 'view_contacts' ) ) {

			// Is there an active contact record?
			$contact = get_contactdata();
		}
	}

	if ( ! is_a_contact( $contact ) || ! $contact->exists() ) {
		return false;
	}

	// Prevent sales reps from importing or making changes to existing contacts of which they are not assigned
	if ( current_user_can( 'add_contacts' ) && ! current_user_can( 'edit_contact', $contact ) ) {
		return false;
	}

	// Update contact info
	$contact->update( $args );

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

	//	 update meta data
	if ( ! empty( $meta ) ) {
		$contact->update_meta( $meta );
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

	/**
     * After the contact is generated with the map
     *
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
//
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
			$link = 'groundhogg-logo-black.svg';
			break;
		case 'white':
			$link = 'groundhogg-logo-white.svg';
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
		$mod_keys = trim( get_option( 'disallowed_keys' ) );
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
			'post_title'   => __( 'Preference Center', 'groundhogg' ),
			'post_status'  => 'publish',
			'post_name'    => $managed_page_name,
			'post_type'    => 'page',
			'post_content' => "[preferences-center]"
		], true );

		if ( is_wp_error( $post_id ) ) {
			Plugin::$instance->notices->add( $post_id );
		}
	}
}

add_shortcode( 'preferences-center', __NAMESPACE__ . '\preferences_center_shortcode' );

function preferences_center_shortcode() {
	if ( ! current_user_can( 'view_contacts' ) ) {
		return sprintf( '<p>%s</p>', __( 'Oops! Something went wrong finding your email preferences. Please try again shortly or contact us.' ) );
	}

	ob_start();

	?>
    <p><b><?php _e( 'This message is only shown to administrators!', 'groundhogg' ); ?></b></p>
    <p><?php _e( 'Something is preventing the template for the preferences center to be displayed.', 'groundhogg' ); ?></p>
    <p><?php _e( 'Here are some things you can try:', 'groundhogg' ) ?></p>
    <ul>
        <li>
            <a href="<?php echo admin_url( 'options-permalink.php' ) ?>"><?php _e( 'Re-save your permalinks.', 'groundhogg' ) ?></a>
        </li>
        <li>
            <a href="<?php echo admin_page_url( 'gh_tools' ) ?>"><?php _e( 'Enable safe mode to check for a plugin conflict.', 'groundhogg' ) ?></a>
        </li>
        <li><?php _e( 'Try viewing this page in an incognito window.', 'groundhogg' ) ?></li>
        <li><?php _e( 'Clearing your cookies.', 'groundhogg' ) ?></li>
    </ul>
    <p><?php _e( 'If none of those options work, contact customer support.', 'groundhogg' ) ?></p>
	<?php

	return ob_get_clean();
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
 * Whether the given string is a replacement code
 *
 * Checks format ONLY, not whether the code callback is actually valid.
 *
 * @param string $code
 * @param bool   $exact
 *
 * @return false|int
 */
function is_replacement_code_format( string $code, $exact = true ) {
	$format = $exact ? "^{([^{}]+)}$" : "{([^{}]+)}";

	return preg_match( "/$format/", $code );
}

/**
 * Whather the current admin page is a groundhogg page.
 *
 * @return bool
 */
function is_admin_groundhogg_page() {
	$page = get_request_var( 'page' );

	return is_admin() && $page && ( preg_match( '/^gh_/', $page ) || $page === 'groundhogg' );
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
		$data['body']    = $body;

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
 * Returns the full format of dat time based on WP settings.
 *
 * @return string
 */
function get_time_format() {
	return get_option( 'time_format' );
}

/**
 * @param $time
 *
 * @return string
 */
function format_date( $time ) {
	return date_i18n( get_date_time_format(), $time );
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

	if ( is_user_logged_in() && current_user_can( 'download_files' ) ) {
		return action_url( 'download_file', [
			'page'      => 'gh_tools',
			'file_path' => ltrim( $path, '/' ),
			'download'  => $download
		] );
	}

	$url = managed_page_url( 'file-download/' . ltrim( $path, '/' ) );

	// WP Engine file download links do not work if forward slash is not present.
	$url = trailingslashit( $url );

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
 * Better named alias for do_api_trigger
 *
 * @param string $call_name
 * @param string $id_or_email
 * @param false  $by_user_id
 */
function do_plugin_api_benchmark( $call_name = '', $id_or_email = '', $by_user_id = false ) {
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
function action_input( $action = '', $echo = true, $nonce = false ) {
	$input = html()->input( [ 'value' => $action, 'type' => 'hidden', 'name' => 'action' ] );

	if ( $nonce ) {
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

	static $cc;

	if ( $cc ) {
		return $cc;
	}

	// Is the CC already set?
	$cc = get_option( 'gh_default_country_code' );

	if ( $cc ) {
		return $cc;
	}

	// Get the IP of the logged in user
	if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {

		$cc = utils()->location->ip_info( null, 'countrycode' );

		if ( $cc ) {
			update_option( 'gh_default_country_code', $cc );

			return $cc;
		}

	}

	// Get the IP of the site wherever it's being hosted
	$parse_url = wp_parse_url( home_url(), PHP_URL_HOST );

	if ( $parse_url ) {
		$ip = gethostbyname( $parse_url );
		$cc = utils()->location->ip_info( $ip, 'countrycode' );

		if ( $cc ) {
			update_option( 'gh_default_country_code', $cc );

			return $cc;
		}
	}

	$cc = 'US';

	return $cc;
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
function validate_mobile_number( $number, $country_code = '', $with_plus = true ) {

	// Remove non-digits
	$number = preg_replace( "/[^0-9]/", "", $number );

	// Keep initial number
	$initial = $number;

	if ( ! $country_code ) {
		$country_code = get_default_country_code();
	}

	if ( ! number_has_country_code( $number ) ) {
		$number = \Groundhogg\mobile_validator()->normalize( $number, $country_code );

		// Try neighboring country
		if ( empty( $number ) ) {
			switch ( $country_code ) {
				case 'US':
					$number = \Groundhogg\mobile_validator()->normalize( $number, 'CA' );
					break;
				case 'CA':
					$number = \Groundhogg\mobile_validator()->normalize( $number, 'US' );
					break;
			}
		}
	}

	// Unable to validate number, could be because of a mismatch of country code to number
	if ( empty( $number ) ) {
		return $initial;
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
 * Runs a contact's mobile number though the validation function and updates it if it's different from the original
 * Run this after most contact creation opportunities
 *
 * @param $contact Contact
 *
 * @return void
 */
function maybe_validate_and_update_mobile_number( $contact ) {

	$contact = get_contactdata( $contact );

	if ( ! is_a_contact( $contact ) ) {
		return;
	}

	$to           = $contact->get_mobile_number();
	$country_code = $contact->get_meta( 'country' );

	if ( ! $to || ! $country_code ) {
		return;
	}

	// Is e164 format already
	if ( preg_match( '/^\+[1-9]\d{1,14}$/', $to ) ) {
		return;
	}

	$validated = validate_mobile_number( $to, $country_code );

	if ( $validated !== $to && $validated ) {
		$contact->update_meta( 'mobile_phone', $validated );
	}
}

// Webhooks, integrations
add_action( 'groundhogg/generate_contact_with_map/after', __NAMESPACE__ . '\maybe_validate_and_update_mobile_number', 99, 1 );
// Admin
add_action( 'groundhogg/admin/contact/save', __NAMESPACE__ . '\maybe_validate_and_update_mobile_number', 99, 1 );
// API
add_action( 'groundhogg/api/contact/created', __NAMESPACE__ . '\maybe_validate_and_update_mobile_number', 99, 1 );
add_action( 'groundhogg/api/contact/updated', __NAMESPACE__ . '\maybe_validate_and_update_mobile_number', 99, 1 );

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
 * Fetches the master license, if one is available
 *
 * @return void
 */
function get_master_license() {
	return get_option( 'gh_master_license' );
}

/**
 * Checks if the Groundhogg helper plugin is installed
 *
 * @return bool
 */
function is_helper_plugin_installed() {
	return defined( 'GROUNDHOGG_HELPER_VERSION' );
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
	return defined( 'GROUNDHOGG_HELPER_VERSION' ) || defined( 'GROUNDHOGG_PRO_VERSION' ) || get_option( 'gh_master_license' ) !== false || is_white_labeled();
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

/**
 * Menu styles when plugin is not white labelled
 *
 * @return void
 */
function maybe_print_menu_styles() {

	?>
    <style>

        <?php if ( $unread = notices()->count_unread() > 0 ): ?>
        .unread-notices::after {
            content: '<?php echo $unread ?>' !important;
        }

        <?php endif; ?>

        #wp-admin-bar-top-secondary #wp-admin-bar-groundhogg.groundhogg-admin-bar-menu .ab-item {
            display: flex;
            align-items: center;
            cursor: pointer;
        }

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
            margin: 5px 0;
            width: 100%;
            opacity: .4;
        }

        #adminmenu #toplevel_page_groundhogg li.gh_go_pro:before {
            margin-bottom: 8px;
        }
    </style>
	<?php
}

add_action( 'admin_print_styles', __NAMESPACE__ . '\maybe_print_menu_styles' );

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

	/**
	 * Filter the primary owner
	 *
	 * @param $user \WP_User
	 */
	return apply_filters( 'groundhogg/primary_owner', $user );
}

/**
 * Get the ID of the default owner
 *
 * @return false|int
 */
function get_primary_owner_id() {

	$owner = get_primary_owner();

	if ( ! $owner || ! is_a( $owner, \WP_User::class ) ) {
		return false;
	}

	return $owner->ID;
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
 * Converts a date to an int
 *
 * @param $date
 *
 * @return false|int
 */
function date_as_int( $date ) {
	return is_numeric( $date ) ? absint( $date ) : strtotime( $date );
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
 * @param $time
 *
 * @return int
 */
function convert_to_utc_0( $time ) {
	return Plugin::$instance->utils->date_time->convert_to_utc_0( $time );
}

/**
 * Retrieve an array of roles that have the view_contacts cap
 *
 * @return array|mixed
 */
function get_owner_roles() {

	static $roles;

	if ( ! empty( $roles ) ) {
		return $roles;
	}

	$roles = [];

	foreach ( wp_roles()->roles as $role_slug => $role ) {
		if ( isset_not_empty( $role['capabilities'], 'view_contacts' ) ) {
			$roles[] = $role_slug;
		}
	}

	if ( empty( $roles ) ) {
		return Main_Roles::get_owner_roles();
	}

	return $roles;
}

/**
 * Get an array of all the valid owners on the site
 *
 * @return \WP_User[]
 */
function get_owners() {

	static $users;

	if ( empty( $users ) ) {
		// Check option cache first
		$cached_users = get_option( 'gh_owners' );

		if ( is_array( $cached_users ) && ! empty( $cached_users ) ) {
			$users = array_filter( array_map( 'get_userdata', wp_parse_id_list( $cached_users ) ) );
		} else {
			$users = get_users( [ 'role__in' => get_owner_roles() ] );

			$user_ids = array_map( function ( $user ) {
				return $user->ID;
			}, $users );

			update_option( 'gh_owners', $user_ids );
		}
	}

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
function is_a_contact( $contact ): bool {
	return is_a( $contact, Contact::class );
}

/**
 * Whether the given user is a user
 *
 * @param $user
 *
 * @return bool
 */
function is_a_user( $user ): bool {
	return is_a( $user, \WP_User::class );
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
	$cache_key = $contact->get_id() . ':' . $usage . ':' . $expiration;

	// If a key was already created for the given contact within the current runtime
	$key = wp_cache_get( $cache_key, 'permissions_keys', false, $found );

	// use it instead of creating a new one
	if ( $found ) {
		return $key;
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
	wp_cache_set( $cache_key, $key, 'permissions_keys', MINUTE_IN_SECONDS );

	return $key;
}

/**
 * Invalidate the permissions keys for a specific contact
 *
 * @param Contact $contact
 * @param         $usage
 *
 * @return bool
 */
function invalidate_contact_permissions_keys( Contact $contact, string $usage = '' ) {

    $query = [
	    'contact_id' => $contact->get_id()
    ];

    if ( ! empty( $usage ) ){
        $query[ 'usage_type' ] = $usage;
    }

	$deleted = get_db( 'permissions_keys' )->delete( $query );

    return $deleted;
}

/**
 * If the user_id or email address is changed, invalidate existing permissions keys for that contact.
 *
 * @param int $id
 * @param array $updated
 * @param Contact $contact
 * @param array $old
 *
 * @return void
 */
function maybe_invalidate_permissions_keys_when_contact_updated( $id, $updated, $contact, $old ){

    // All permissions keys
    if ( isset( $updated['email'] ) && $updated['email'] !== $old[ 'email' ] ){
        invalidate_contact_permissions_keys( $contact );
        return;
    }

    // If the user_id was updated, only invalidate permissions keys for auto login
    if ( isset( $updated['user_id'] ) && $updated['user_id'] !== $old[ 'user_id' ] ){
	    invalidate_contact_permissions_keys( $contact, 'auto_login' );
    }
}

add_action( 'groundhogg/contact/post_update', __NAMESPACE__ . '\maybe_invalidate_permissions_keys_when_contact_updated', 10, 4 );

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
 * Wrapper for `permissions_key_url()` which will give a permissions key link
 * if an email is sending or the current logged-in user and the tracking cookie of the contact match up
 *
 *
 * @param $url
 * @param $contact
 * @param $usage
 * @param $expiration
 * @param $delete_after_use
 *
 * @return mixed|string
 */
function maybe_permissions_key_url( $url, $contact, $usage = 'preferences', $expiration = WEEK_IN_SECONDS, $delete_after_use = false ) {

	if ( is_sending() || current_contact_and_logged_in_user_match() ) {

		Email_Logger::email_is_sensitive();

		return permissions_key_url( $url, $contact, $usage, $expiration, $delete_after_use );
	}

	return $url;
}

/**
 * Adds params to the url for failsafe tracking if for whatever reason the tracking links aren't working.
 *
 * @param string  $url
 * @param Contact $contact
 *
 * @return string
 */
function add_failsafe_tracking_params( string $url, Contact $contact ){

    $params = [
	    'gi' => base64url_encode( encrypt( $contact->get_email() ) )
    ];

	if ( the_email() && is_sending() && the_email()->get_event() && the_email()->get_event()->exists() ) {
		$params['ge'] = dechex( the_email()->get_event()->get_id() );
    } else if ( Event_Queue::is_processing() ){
		$params['ge'] = dechex( \Groundhogg\event_queue()->get_current_event()->get_id() );
    }

    return add_query_arg( $params, $url );
}

/**
 * Generate a URL with the permissions key on it.
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
 * Generate a confirmation URL
 *
 * @param $contact
 *
 * @return mixed|void
 */
function confirmation_url( $contact ) {
	$url = managed_page_url( 'preferences/confirm/' );

	// create permissions url
	$url = permissions_key_url( $url, $contact, 'preferences' );

	$url = add_failsafe_tracking_params( $url, $contact );

	return apply_filters( 'groundhogg/confirmation_url', $url, $contact );
}

/**
 * Generate an unsubscribe URL
 *
 * @param $contact
 *
 * @return mixed|void
 */
function unsubscribe_url( $contact ) {
	$url = managed_page_url( is_option_enabled( 'gh_enable_one_click_unsubscribe' ) ? 'preferences/unsubscribe' : 'preferences/manage' );

	// create permissions url
	$url = permissions_key_url( $url, $contact, 'preferences' );

	$url = add_failsafe_tracking_params( $url, $contact );

	return apply_filters( 'groundhogg/unsubscribe_url', $url, $contact );
}

/**
 * @param $usage
 *
 * @return bool|mixed
 */
function get_permissions_key_cookie( $usage ) {
	$cookie = 'gh-permissions-key';

	if ( $usage ) {
		$cookie .= '-' . $usage;
	}

	return get_cookie( $cookie );
}

/**
 * @param $key
 * @param $usage
 *
 * @return void
 */
function set_permissions_key_cookie( $key, $usage = '' ) {

	$cookie = 'gh-permissions-key';

	if ( $usage ) {
		$cookie .= '-' . $usage;
	}

	set_cookie( $cookie, $key, HOUR_IN_SECONDS );
}

/**
 * Get the permissions key
 * if one is not available return false
 * Will set the permissions key cookie if the key is found in the URL
 *
 * @return string|false
 */
function get_permissions_key( $usage = '', $set_cookie = false ) {

	// Try to get from URL
	$permissions_key = get_url_var( 'pk' );

	if ( $permissions_key ) {

		// if we also want to set it as the cookie
		if ( $set_cookie ) {
			set_permissions_key_cookie( $permissions_key, $usage );
		}

		return $permissions_key;
	}

	// Try to get from cookies
	return get_permissions_key_cookie( $usage );
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
function get_url_exclusions_regex( $exclusions = [] ) {
	static $exclusions_regex;

	if ( ! $exclusions_regex ) {

		$exclusions = get_option( 'gh_url_tracking_exclusions' );

		if ( ! is_array( $exclusions ) ) {
			$exclusions = explode( PHP_EOL, $exclusions );
		}

		if ( empty( $exclusions ) ) {
			return false;
		}

		$exclusions = array_map( function ( $exclusion ) {
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
function contact_and_user_match( $contact = false, $user = false ) {

	$contact = get_contactdata( $contact );

	if ( ! is_a_contact( $contact ) ) {
		return false;
	}

	if ( is_int( $user ) ) {
		$user = get_userdata( $user );
	} else if ( ! $user ) {
		$user = wp_get_current_user();
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
		'@(<p[^>]*>)([^>]*)(<p[^>]*>)@m', // double opening P tags
		'@(<\/p[^>]*>)([^>]*)(<\/p[^>]*>)@m', // double closing P tags
		'@(<p[^>]*>)(<div[^>]*>)@m', // opening p with opening div
		'@(<\/div[^>]*>)(<\/p[^>]*>)@m', // closing div with closing p
	];

	$replacements = [
		'$1$2',
		'$2$3',
		'$2',
		'$1',
	];

	return preg_replace( $patterns, $replacements, $content );
}

/**
 * Track a page visit
 *
 * @param       $ref     string a URL
 * @param       $contact Contact
 * @param array $override
 */
function track_page_visit( $ref, $contact, $override = [] ) {
	$path     = sanitize_text_field( parse_url( $ref, PHP_URL_PATH ) );
	$query    = sanitize_text_field( parse_url( $ref, PHP_URL_QUERY ) );
	$fragment = sanitize_text_field( parse_url( $ref, PHP_URL_FRAGMENT ) );

	$visit = get_db( 'page_visits' )->add( array_merge( [
		'contact_id' => $contact->get_id(),
		'path'       => $path,
		'query'      => $query,
		'fragment'   => $fragment,
	], $override ) );

	$visit = new Page_Visit( $visit );

	if ( ! $visit->exists() ) {
		return false;
	}

	/**
	 * Runs when a page visit is tracked
	 *
	 * @param $visit   Page_Visit
	 * @param $contact Contact
	 */
	do_action( 'groundhogg/tracking/page_visit', $visit, $contact );

	return $visit;
}

/**
 * Track activity that happens on site caused by a human.
 * Use the tracking cookie to populate the main arguments.
 *
 * @param $type
 * @param $details
 *
 * @return Activity|False
 */
function track_live_activity( $type, $details = [], $value = 0 ) {

    if ( tracking()->get_current_event() ){
        return track_event_activity( tracking()->get_current_event(), $type, $details, [
	        'value' => $value
        ] );
    }

	// Use tracked contact
	$contact = get_contactdata();

	// If there is not one available, skip
	if ( ! is_a_contact( $contact ) ) {
		return false;
	}

	$args = [
		'value' => $value
	];

	$args = apply_filters( 'groundhogg/track_live_activity/args', $args, $contact );

	return track_activity( $contact, $type, $args, $details );
}

/**
 * Log an activity conducted by the contact while they are performing actions on the site.
 * Uses the cookie details for reporting.
 *
 * @param string             $type    string, an activity identifier
 * @param array              $args    the details for the activity
 * @param array              $details details about that activity
 * @param Contact|string|int $contact the contact to track
 *
 * @return Activity|False
 */
function track_activity( $contact, $type = '', $args = [], $details = [] ) {

	$contact = get_contactdata( $contact );

	// If there is not one available, skip
	if ( ! is_a_contact( $contact ) ) {
		return false;
	}

	// use tracking cookies to generate information for the activity log
	$defaults = [
		'activity_type' => $type,
		'timestamp'     => time(),
		'contact_id'    => $contact->get_id()
	];

	// Merge overrides with args
	$args = wp_parse_args( $args, $defaults );

	$args = apply_filters( 'groundhogg/track_activity/args', $args, $contact );

	// Add the activity to the DB
	$id = get_db( 'activity' )->add( $args );

	if ( ! $id ) {
		return false;
	}

	$activity = new Activity( $id );

	// Add any details to the activity meta
	foreach ( $details as $detail_key => $value ) {
		$activity->update_meta( $detail_key, $value );
	}

	track_activity_actions( $activity );

	return $activity;
}

/**
 * Track activity associated with a specific event
 *
 * @param Event  $event
 * @param string $type
 * @param array  $details
 * @param array  $args
 *
 * @return Activity|False
 */
function track_event_activity( Event $event, string $type = '', array $details = [], array $args = [] ) {

	$contact = $event->get_contact();

	// If there is not one available, skip
	if ( ! is_a_contact( $contact ) ) {
		return false;
	}

	$args = wp_parse_args( $args, [
		'event_id'      => $event->ID,
		'funnel_id'     => $event->funnel_id,
		'step_id'       => $event->step_id,
		'email_id'      => $event->email_id,
	] );

    return track_activity( $contact, $type, $args, $details );
}

/**
 * do actions when activity is tracked
 *
 * @param Activity $activity
 *
 * @return void
 */
function track_activity_actions( $activity ) {

	/**
	 * Fires after some activity is tracked
	 *
	 * @param $activity Activity
	 * @param $contact  Contact
	 */
	do_action( 'groundhogg/track_activity', $activity, $activity->get_contact() );
	do_action( "groundhogg/track_activity/{$activity->activity_type}", $activity, $activity->get_contact() );
}


/**
 * Return json response for meta picker.
 */
function handle_ajax_user_meta_picker() {

	if ( ! current_user_can( 'edit_users' ) || ! wp_verify_nonce( get_post_var( 'nonce' ), 'meta-picker' ) ) {
		wp_send_json_error();
	}

	$search = sanitize_text_field( get_post_var( 'term' ) );

	global $wpdb;

	$keys = $wpdb->get_col(
		"SELECT DISTINCT meta_key FROM {$wpdb->usermeta} WHERE `meta_key` RLIKE '{$search}' ORDER BY meta_key ASC"
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

add_action( 'wp_ajax_user_meta_picker', __NAMESPACE__ . '\handle_ajax_user_meta_picker' );

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
 * Return json response for meta picker.
 */
function handle_ajax_meta_value_picker() {

	if ( ! current_user_can( 'edit_contacts' ) || ! wp_verify_nonce( get_post_var( 'nonce' ), 'meta-picker' ) ) {
		wp_send_json_error();
	}

	$search   = sanitize_text_field( get_post_var( 'term' ) );
	$meta_key = sanitize_text_field( get_post_var( 'meta_key' ) );

	$table = get_db( 'contactmeta' );

	global $wpdb;

	$keys = $wpdb->get_col(
		"SELECT DISTINCT meta_value FROM {$table->get_table_name()} WHERE `meta_key` = '{$meta_key}' AND `meta_value` RLIKE '{$search}' ORDER BY meta_value ASC"
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
	$response = apply_filters( 'groundhogg/handle_ajax_meta_value_picker', $response, $search );

	wp_send_json( $response );
}

add_action( 'wp_ajax_gh_meta_value_picker', __NAMESPACE__ . '\handle_ajax_meta_value_picker' );

/**
 * Current time or time provided
 *
 * @param $time
 *
 * @return false|int|mixed
 */
function get_time( $time ) {
	return is_string( $time ) ? ( strtotime( $time ) ?: time() ) : ( $time ?: time() );
}

/**
 * Quick formatting function for Y-m-d H:i:s date time.
 *
 * @param false $time
 *
 * @return false|string
 */
function Ymd_His( $time = false, $local = false ) {
	$date = new \DateTime();

	if ( $local ) {
		$date->setTimezone( wp_timezone() );
	}

	$date->setTimestamp( get_time( $time ) );

	return $date->format( 'Y-m-d H:i:s' );
}

/**
 * Quick formatting function for H:i:s time.
 *
 * @param false $time
 *
 * @return false|string
 */
function His( $time = false ) {
	return date( 'H:i:s', get_time( $time ) );
}

/**
 * Quick formatting function for Y-m-d date.
 *
 * @param false $time
 *
 * @return false|string
 */
function Ymd( $time = false, $local = false ) {
	$date = new \DateTime();

	if ( $local ) {
		$date->setTimezone( wp_timezone() );
	}

	$date->setTimestamp( get_time( $time ) );

	return $date->format( 'Y-m-d' );
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
 * Doing gh-cron.php
 *
 * @return bool
 */
function gh_doing_cron() {
	return defined( 'DOING_GH_CRON' ) && DOING_GH_CRON;
}

/**
 * Tracks pings to the wp-cron.php file
 */
function track_wp_cron_ping() {
	if ( wp_doing_cron() && ! gh_doing_cron() ) {
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

add_action( 'groundhogg/event_queue/before_process', __NAMESPACE__ . '\track_gh_cron_ping', 9 );

/**
 * Whether the queue is processing every single minute
 * Let's use a 90 second grace period though, instead of 60 seconds to try and avoid a race condition
 *
 * @return mixed|void
 */
function is_event_queue_processing() {
	$gh_cron_setup = time() - get_option( 'gh_cron_last_ping' ) <= 90;

	return apply_filters( 'groundhogg/cron_is_working', $gh_cron_setup );
}

/**
 * Takes a string or int and returns a mysql friendly date
 *
 * @param $date string|int
 *
 * @return string|false
 */
function convert_to_mysql_date( $date ) {
	if ( ! is_int( $date ) && ! is_string( $date ) ) {
		return false;
	}

	return is_int( $date ) ? date( 'Y-m-d H:i:s', $date ) : date( 'Y-m-d H:i:s', strtotime( $date ) );
}

/**
 * Map a function to the value of attr in an array or an object
 *
 * @param &$arr array|object
 * @param $key  string
 * @param $func callable
 */
function map_func_to_attr( &$arr, $key, $func ) {
	if ( isset_not_empty( $arr, $key ) ) {
		if ( is_array( $arr ) ) {
			$arr[ $key ] = call_user_func( $func, $arr[ $key ] );
		} else if ( is_object( $arr ) ) {
			$arr->$key = call_user_func( $func, $arr->$key );
		}
	}
}

/**
 * Handle sanitization of contact meta is most likely situations.
 *
 * @param mixed  $meta_value
 * @param string $meta_key
 * @param string $object_type
 *
 * @return string
 */
function sanitize_object_meta( $meta_value, $meta_key = '', $object_type = '' ) {

	$original_meta_value = $meta_value;

	if ( is_string( $meta_value ) && strpos( $meta_value, PHP_EOL ) !== false ) {
		$meta_value = sanitize_textarea_field( $meta_value );
	} else if ( is_string( $meta_value ) ) {
		$meta_value = sanitize_text_field( $meta_value );
	}

	/**
	 * Filter the object meta
	 *
	 * @param mixed  $meta_value
	 * @param string $meta_key
	 * @param string $object_type
	 * @param mixed  $original_meta_value
	 */
	return apply_filters( 'groundhogg/sanitize_object_meta', $meta_value, $meta_key, $object_type, $original_meta_value );
}

/**
 * Check if the email address is in use
 * You can pass a contact record to double check against the current contact as well.
 *
 * @param string       $email_address
 * @param bool|Contact $current_contact
 *
 * @return bool
 */
function is_email_address_in_use( $email_address, $current_contact = false ) {

	$contact = get_contactdata( $email_address );

	// If there is no contact record
	if ( ! is_a_contact( $contact ) ) {
		return false;
		// If there is a contact but it's the same as the one we are passing...
	} else if ( is_a_contact( $current_contact ) && $contact->get_id() === $current_contact->get_id() ) {
		return false;
		// Otherwise
	} else {
		return true;
	}
}

/**
 * Get CSV file info
 *
 * @param $file_path string
 *
 * @return array|bool
 */
function get_csv_file_info( $file_path ) {

	if ( ! file_exists( $file_path ) ) {
		return false;
	}

	return [
		'file_name' => basename( $file_path ),
		'file_path' => $file_path,
		'file_url'  => file_access_url( basename( dirname( $file_path ) ) . '/' . basename( $file_path ), true ),
		'timestamp' => filemtime( $file_path ),
		'rows'      => count( file( $file_path, FILE_SKIP_EMPTY_LINES ) ) - 1,
	];
}

/**
 * Parse list of Ids into classes
 *
 * @param $list
 * @param $class
 *
 * @return array
 */
function id_list_to_class( $list, $class ) {
	return array_map( function ( $id ) use ( &$class ) {
		return new $class( $id );
	}, wp_parse_id_list( $list ) );
}

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
 * Returns unqiue items based on a predicate
 *
 * @param $array    array
 * @param $callback callable
 *
 * @return array
 */
function array_unique_cb( $array, $callback ) {

	$seen = [];

	return array_filter( $array, function ( $item ) use ( $seen, $callback ) {

		$__item = call_user_func( $callback, $item );

		if ( in_array( $__item, $seen ) ) {
			return false;
		}

		$seen[] = $__item;

		return true;
	} );
}

/**
 * Filters out elements from that match the predicate from the original array
 * Does not preserve keys
 *
 * @param $array     array
 * @param $predicate callable
 *
 * @return array the filtered array
 */
function array_filter_splice( &$array, $predicate ) {
	$new_array = [];

	$filtered = array_filter( $array, function ( $item ) use ( $predicate, &$new_array ) {

		// Item matched predicate, remove from original
		if ( call_user_func( $predicate, $item ) ) {
			return true;
		}

		$new_array[] = $item;

		return false;
	} );

	// Set the original array to the array with elements filtered out
	$array = $new_array;

	return $filtered;
}

/**
 * Checks if at least one of the elements in the array matches some predicate
 *
 * @param array    $array
 * @param callable $predicate
 *
 * @return bool
 */
function array_any( $array, $predicate ) {
	foreach ( $array as $element ) {
		if ( call_user_func( $predicate, $element ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Checks if all the elements in the array match some predicate
 *
 * @param array    $array
 * @param callable $predicate
 *
 * @return bool
 */
function array_all( $array, $predicate ) {
	foreach ( $array as $element ) {
		if ( ! call_user_func( $predicate, $element ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Is something empty
 *
 * @param $something
 *
 * @return bool
 */
function is_empty( $something ) {
	return empty( $something );
}

/**
 * Is something not empty
 *
 * @param $something
 *
 * @return bool
 */
function is_not_empty( $something ) {
	return ! is_empty( $something );
}

/**
 * Check if all the elements in an array are empty
 *
 * @param $array
 *
 * @return bool
 */
function array_all_empty( $array ) {
	return array_all( $array, __NAMESPACE__ . '\is_empty' );
}

/**
 * Check if all of the elements in the array are not empty
 *
 * @param $array
 *
 * @return bool
 */
function array_all_not_empty( $array ) {
	return array_all( $array, __NAMESPACE__ . '\is_not_empty' );
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

		case 'in-reply-to':

			if ( preg_match( '/^<([^>]+)>$/', $header_value, $matches ) ) {
				$header_value = sprintf( '<%s>', is_email( $matches[1] ) ? sanitize_email( $matches[1] ) : sanitize_text_field( $matches[1] ) );
			} else {
				$header_value = sprintf( '<%s>', is_email( $header_value ) ? sanitize_email( $header_value ) : sanitize_text_field( $header_value ) );
			}

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

	//Remove Roles & Caps
	Plugin::$instance->roles->remove_roles_and_caps();

	//Remove all files
	Plugin::$instance->utils->files->delete_all_files();

	/** Cleanup Cron Events */
	wp_clear_scheduled_hook( Event_Queue::WP_CRON_HOOK );
	wp_clear_scheduled_hook( Bounce_Checker::ACTION );
	wp_clear_scheduled_hook( 'groundhogg/sending_service/verify_domain' );
	wp_clear_scheduled_hook( 'groundhogg/purge_email_logs' );
	wp_clear_scheduled_hook( 'groundhogg/purge_page_visits' );
	wp_clear_scheduled_hook( 'groundhogg/aws/remove_old_logs' );
	wp_clear_scheduled_hook( 'groundhogg/birthday' );
	wp_clear_scheduled_hook( 'groundhogg/telemetry' );
	wp_clear_scheduled_hook( 'groundhogg/check_bounces' );
	wp_clear_scheduled_hook( 'groundhogg/purge_expired_permissions_keys' );
	wp_clear_scheduled_hook( 'groundhogg/cleanup' );

	//delete api keys from user_meta
	delete_metadata( 'user', 0, 'wpgh_user_public_key', '', true );
	delete_metadata( 'user', 0, 'wpgh_user_secret_key', '', true );

	// Remove any transients and options we've left behind
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'gh_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'wpgh_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'wp_gh_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'transient_wpgh_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_gh_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_site_transient_wpgh_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_site_transient_gh_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_wpgh_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_gh_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_site_transient_timeout_wpgh_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_site_transient_timeout_gh_%'" );

	uninstall_gh_cron_file();

	do_action( 'groundhogg/uninstall' );

	if ( ob_get_contents() ) {
		file_put_contents( __DIR__ . '/../groundhogg-uninstall-errors.txt', ob_get_contents() );
	}
}

/**
 * Get the field label
 *
 * @param string $field
 *
 * @return mixed|void
 */
function get_default_field_label( $field = '' ) {

	switch ( $field ) {
		default:
			$label = "";
			break;
		case 'first_name':
			$label = _x( 'First Name', 'field_label', 'groundhogg' );
			break;
		case 'last_name':
			$label = _x( 'Last Name', 'field_label', 'groundhogg' );
			break;
		case 'full_name':
			$label = _x( 'Full Name', 'field_label', 'groundhogg' );
			break;
		case 'email':
			$label = _x( 'Email Address', 'field_label', 'groundhogg' );
			break;
		case 'primary_phone':
			$label = _x( 'Phone Number', 'field_label', 'groundhogg' );
			break;
		case 'mobile_phone':
			$label = _x( 'Mobile Phone Number', 'field_label', 'groundhogg' );
			break;
		case 'gdpr_consent':
			$label = sprintf( _x( "I agree to %s's storage and processing of my personal data.", 'field_label', 'groundhogg' ), get_bloginfo() );
			break;
		case 'marketing_consent':
			$label = sprintf( _x( "I agree to receive marketing offers and updates from %s.", 'field_label', 'groundhogg' ), get_bloginfo() );
			break;
		case 'terms_agreement':
			$label = sprintf( _x( "I agree to terms and conditions of %s.", 'field_label', 'groundhogg' ), get_bloginfo() );
			break;
	}

	return apply_filters( 'groundhogg/default_field_label', $label, $field );
}

/**
 * Map items in an array to a specific class
 *
 * @param $array
 * @param $class
 *
 * @return mixed[]
 */
function array_map_to_class( &$array, $class ) {
	foreach ( $array as &$mixed ) {

		if ( is_a( $mixed, $class ) ) {
			continue;
		}

		$mixed = new $class( $mixed );
	}

	return $array;
}

/**
 * Map items in an array to a specific class
 *
 * @param $array
 * @param $class
 *
 * @return mixed[]
 */
function map_to_class( $array, $class ) {
	foreach ( $array as &$mixed ) {

		if ( is_a( $mixed, $class ) ) {
			continue;
		}

		$mixed = new $class( $mixed );
	}

	return $array;
}

/**
 * @param $array
 *
 * @return Contact[]
 */
function array_map_to_contacts( &$array ) {
	return array_map_to_class( $array, Contact::class );
}

/**
 * @param $array
 *
 * @return Step[]
 */
function array_map_to_step( &$array ) {
	return array_map_to_class( $array, Step::class );
}

/**
 * Get a list of active steps
 *
 * @param $type
 *
 * @return Step[]
 */
function get_active_steps( $type ) {
	$steps = get_db( 'steps' )->query( [
		'step_type'   => $type,
		'step_status' => 'active'
	] );

	array_map_to_step( $steps );

	return $steps;
}

/**
 * Get objects as ID list
 *
 * @param $array
 *
 * @return array
 */
function get_object_ids( $array ) {
	return array_map( function ( $object ) {
		return $object->get_id();
	}, $array );
}

/**
 * Adds actions to disable emojis
 *
 * @return void
 */
function add_disable_emojis_action() {
	add_action( 'init', __NAMESPACE__ . '\disable_emojis' );
	add_action( 'admin_init', __NAMESPACE__ . '\disable_emojis' );
}

/**
 * Disables emojis
 *
 * @credit Ryan Hellyer https://en-ca.wordpress.org/plugins/disable-emojis/
 *
 * @return void
 */
function disable_emojis() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter( 'tiny_mce_plugins', __NAMESPACE__ . '\disable_emojis_tinymce' );
	add_filter( 'wp_resource_hints', __NAMESPACE__ . '\disable_emojis_remove_dns_prefetch', 10, 2 );
}

/**
 * Filter function used to remove the tinymce emoji plugin.
 *
 * @credit Ryan Hellyer https://en-ca.wordpress.org/plugins/disable-emojis/
 *
 * @param array $plugins
 *
 * @return   array             Difference betwen the two arrays
 */
function disable_emojis_tinymce( $plugins ) {
	if ( is_array( $plugins ) ) {
		return array_diff( $plugins, array( 'wpemoji' ) );
	}

	return array();
}

/**
 * Remove emoji CDN hostname from DNS prefetching hints.
 *
 * @credit Ryan Hellyer https://en-ca.wordpress.org/plugins/disable-emojis/
 *
 * @param array  $urls          URLs to print for resource hints.
 * @param string $relation_type The relation type the URLs are printed for.
 *
 * @return array                 Difference betwen the two arrays.
 */
function disable_emojis_remove_dns_prefetch( $urls, $relation_type ) {

	if ( 'dns-prefetch' == $relation_type ) {

		// Strip out any URLs referencing the WordPress.org emoji location
		$emoji_svg_url_bit = 'https://s.w.org/images/core/emoji/';
		foreach ( $urls as $key => $url ) {
			if ( strpos( $url, $emoji_svg_url_bit ) !== false ) {
				unset( $urls[ $key ] );
			}
		}

	}

	return $urls;
}

/**
 * Returns an array of all the meta keys in a table.
 *
 * @return array
 */
function get_keys() {
	global $wpdb;
	$table = get_db( 'contactmeta' );

	$keys = $wpdb->get_col(
		"SELECT DISTINCT meta_key FROM {$table->get_table_name()} ORDER BY meta_key ASC"
	);

	$key_array = array_combine( $keys, $keys );

	return $key_array;
}

function get_pages_list() {
	$pages      = get_pages();
	$lists_page = array();
	foreach ( $pages as $page ) {
		$lists_page[] = get_page_link( $page->ID );
	}

	return $lists_page;
}

/**
 * Creates a relationship between two objects
 *
 * @param $primary   Base_Object
 * @param $secondary Base_Object
 *
 * @return bool
 */
function create_object_relationship( $primary, $secondary ) {
	return (bool) get_db( 'object_relationships' )->add( [
		'primary_object_id'     => $primary->get_id(),
		'primary_object_type'   => $primary->get_object_type,
		'secondary_object_id'   => $secondary->get_id(),
		'secondary_object_type' => $secondary->get_object_type,
	] );
}

/**
 * Delete a relationship between two objects
 *
 * @param Base_Object $primary
 * @param Base_Object $secondary
 *
 * @return bool
 */
function delete_object_relationship( $primary, $secondary ) {
	return get_db( 'object_relationships' )->delete( [
		'primary_object_id'     => $primary->get_id(),
		'primary_object_type'   => $primary->get_object_type,
		'secondary_object_id'   => $secondary->get_id(),
		'secondary_object_type' => $secondary->get_object_type,
	] );
}


/**
 * Delete a relationship between two objects
 *
 * @param Base_Object $primary
 * @param Base_Object $secondary
 *
 * @return bool
 */
function has_object_relationship( $primary, $secondary ) {
	return get_db( 'object_relationships' )->exists( [
		'primary_object_id'     => $primary->get_id(),
		'primary_object_type'   => $primary->get_object_type,
		'secondary_object_id'   => $secondary->get_id(),
		'secondary_object_type' => $secondary->get_object_type,
	] );
}

/**
 * Get relationships for an object
 *
 * @param Base_Object $object
 * @param bool        $is_primary
 */
function get_object_relationships( $object, $is_primary = true ) {
	return get_db( 'object_relationships' )->query( [
		$is_primary ? 'primary_object_id' : 'secondary_object_id'     => $object->get_id(),
		$is_primary ? 'primary_object_type' : 'secondary_object_type' => $object->get_object_type,
	] );
}

/**
 * Before should always be greater than the after
 *
 * @param $before
 * @param $after
 *
 * @return void
 */
function maybe_swap_dates( &$before, &$after ) {
	// If after is > than before, swap them
	if ( $after > $before ) {
		$temp   = $before;
		$before = $after;
		$after  = $temp;
	}
}

/**
 * Create an Object extending Base_Object given very little information
 *
 * @param $object      mixed
 * @param $object_type string
 */
function create_object_from_type( $object, $object_type ) {

	$table = Plugin::instance()->dbs->get_object_db_by_object_type( $object_type );

	return $table->create_object( $object );
}

/**
 * Whether this site provides templates, if so then the gh/v4/emails READ and gh/v4/funnels READ will be public
 *
 * @return bool
 */
function is_template_site() {
	return apply_filters( 'groundhogg/is_template_site', defined( 'IS_GROUNDHOGG_TEMPLATE_SITE' ) && IS_GROUNDHOGG_TEMPLATE_SITE );
}

/**
 * Enqueue any step type registration assets
 */
function enqueue_step_type_assets() {
	do_action( 'groundhogg_enqueue_step_type_assets' );
}

/**
 * Enqueue assets for scheduling broadcasts
 */
function enqueue_broadcast_assets() {
	wp_enqueue_script( 'groundhogg-admin-send-broadcast' );
	wp_enqueue_style( 'groundhogg-admin-element' );
	enqueue_filter_assets();

	do_action( 'groundhogg_enqueue_broadcast_assets' );
}

/**
 * Enqueue the new block editor
 */
function enqueue_email_block_editor_assets( $extra = [] ) {

	wp_enqueue_style( 'groundhogg-email-block-editor' );
	wp_enqueue_script( 'groundhogg-email-block-editor' );

	enqueue_filter_assets();

	wp_enqueue_editor();
	wp_enqueue_media();
	wp_enqueue_code_editor( [
		'type' => 'text/html'
	] );

	wp_enqueue_script( 'htmlhint' );
	wp_enqueue_script( 'csslint' );

	$business_name  = get_option( 'gh_business_name', get_bloginfo() );
	$address        = do_replacements( '{business_address}' );
	$tel            = get_option( 'gh_phone' );
	$terms          = get_option( 'gh_terms' );
	$privacy_policy = get_option( 'gh_privacy_policy' ) ?: get_privacy_policy_url();
	$links          = implode( ' | ', array_filter( [
		$tel ? html()->e( 'a', [ 'href' => 'tel: ' . $tel ], $tel ) : false,
		$privacy_policy ? html()->e( 'a', [ 'href' => $privacy_policy ], __( 'Privacy Policy' ) ) : false,
		$terms ? html()->e( 'a', [ 'href' => $terms ], __( 'Terms' ) ) : false,
	] ) );
	$unsubscribe    = sprintf( __( 'Don\'t want these emails? %s.', 'groundhogg' ), html()->e( 'a', [
		'href' => '#unsubscribe_link#'
	], __( 'Unsubscribe', 'groundhogg' ) ) );

	$post_types = array_map( function ( $post_type ) {

		/**
		 * @var $post_type \WP_Post_Type
		 */
		$taxonomies = get_object_taxonomies( $post_type->name, 'objects' );

		$taxonomies = array_map( function ( $tax ) {
			return [
				'label'        => $tax->label,
				'name'         => $tax->name,
				'rest_base'    => $tax->rest_base,
				'show_in_rest' => $tax->show_in_rest,
				'public'       => $tax->public
			];
		}, $taxonomies );

		return [
			'name'         => $post_type->name,
			'labels'       => $post_type->labels,
			'taxonomies'   => $taxonomies,
			'show_in_rest' => $post_type->show_in_rest,
			'rest_base'    => $post_type->rest_base
		];

	}, get_post_types( [ 'public' => true ], false ) );

	$localized = array_merge( [
		'footer'        => compact( 'business_name', 'address', 'links', 'unsubscribe' ),
		'colorPalette'  => get_option( 'gh_email_editor_color_palette', [] ),
		'globalFonts'   => get_option( 'gh_email_editor_global_fonts', [] ),
		'globalSocials' => get_option( 'gh_email_editor_global_social_accounts', [] ),
		'imageSizes'    => array_values( get_intermediate_image_sizes() ),
		'assets'        => [
			'logo' => has_custom_logo() ? wp_get_attachment_image_src( get_theme_mod( 'custom_logo' ), 'full' ) : false,
		],
		'post_types'    => $post_types
	], $extra );

	wp_add_inline_script( 'groundhogg-email-block-editor', 'const _BlockEditor = ' . wp_json_encode( $localized ), 'before' );
}

/**
 * Enqueue any step type registration assets
 */
function enqueue_filter_assets() {

	wp_enqueue_script( 'groundhogg-admin-search-filters' );
	wp_enqueue_style( 'groundhogg-admin-search-filters' );

	do_action( 'groundhogg_enqueue_filter_assets' );
}

/**
 * Get the original link from the referer_hash
 *
 * @param $hash
 *
 * @return false|string
 */
function get_referer_from_referer_hash( $hash ) {
	return get_db( 'activity' )->get_column_by( 'referer', 'referer_hash', $hash );
}

/**
 * Changes the contact query vars in a query pre version 2.5 to a filters query so that
 * the filters show in the new contact filtering search
 *
 * The search query pre 2.5 is all an AND clauses
 *
 * @param $query
 *
 * @return array|false
 */
function get_filters_from_old_query_vars( $query = [] ) {

	$filters = [ [] ];

	$common_query_filters = [
		'first_name',
		'last_name',
		'email',
	];

	// First Name | Last Name | Email
	foreach ( $common_query_filters as $common_query_filter ) {
		if ( isset_not_empty( $query, $common_query_filter ) ) {
			$filters[0][] = [
				'type'    => $common_query_filter,
				'compare' => get_array_var( $query, $common_query_filter . '_compare', 'contains' ),
				'value'   => $query[ $common_query_filter ]
			];
		}
	}

	// Optin Status
	if ( isset_not_empty( $query, 'optin_status' ) ) {
		$filters[0][] = [
			'type'    => 'optin_status',
			'compare' => 'in',
			'value'   => ensure_array( $query['optin_status'] )
		];
	}

	// Optin Status
	if ( isset_not_empty( $query, 'optin_status_exclude' ) ) {
		$filters[0][] = [
			'type'    => 'optin_status',
			'compare' => 'not_in',
			'value'   => wp_parse_id_list( ensure_array( $query['optin_status_exclude'] ) )
		];
	}

	// Contact owner
	if ( isset_not_empty( $query, 'owner' ) ) {
		$filters[0][] = [
			'type'    => 'owner',
			'compare' => 'in',
			'value'   => wp_parse_id_list( ensure_array( $query['owner'] ) )
		];
	}

	// Tags
	if ( isset_not_empty( $query, 'tags_include' ) ) {
		$filters[0][] = [
			'type'     => 'tags',
			'compare'  => 'includes',
			'compare2' => isset_not_empty( $query, 'tags_include_needs_all' ) ? 'any' : 'all',
			'tags'     => wp_parse_id_list( ensure_array( $query['tags_include'] ) )
		];
	}

	// Tags
	if ( isset_not_empty( $query, 'tags_exclude' ) ) {
		$filters[0][] = [
			'type'     => 'tags',
			'compare'  => 'excludes',
			'compare2' => isset_not_empty( $query, 'tags_excludes_needs_all' ) ? 'any' : 'all',
			'tags'     => wp_parse_id_list( ensure_array( $query['tags_exclude'] ) ),
		];
	}

	if ( isset_not_empty( $query, 'date_query' ) ) {

		$date_query = $query['date_query'];

		$compare = false;
		// between
		if ( isset_not_empty( $date_query, 'after' ) && isset_not_empty( $date_query, 'before' ) ) {
			$compare = 'between';
		} else if ( isset_not_empty( $date_query, 'after' ) ) {
			$compare = 'after';
		} else if ( isset_not_empty( $date_query, 'before' ) ) {
			$compare = 'before';
		}

		$filters[0][] = [
			'type'       => 'date_created',
			'date_range' => $compare,
			'after'      => get_array_var( $date_query, 'after' ),
			'before'     => get_array_var( $date_query, 'before' )
		];
	}

	if ( isset_not_empty( $query, 'date_before' ) || isset_not_empty( $query, 'date_after' ) ) {

		$compare = 'between';
		// between
		if ( isset_not_empty( $query, 'date_before' ) && isset_not_empty( $query, 'date_after' ) ) {
			$compare = 'between';
		} else if ( isset_not_empty( $query, 'date_after' ) ) {
			$compare = 'after';
		} else if ( isset_not_empty( $query, 'date_before' ) ) {
			$compare = 'before';
		}

		$filters[0][] = [
			'type'       => 'date_created',
			'date_range' => $compare,
			'after'      => get_array_var( $query, 'date_after' ),
			'before'     => get_array_var( $query, 'date_before' )
		];
	}

	if ( isset_not_empty( $query, 'report' ) ) {

		$events_query = wp_parse_args( $query['report'], [
			'event_type' => Event::FUNNEL,
			'status'     => 'complete',
		] );

		$map = [
			'step'   => 'step_id',
			'funnel' => 'funnel_id',
			'start'  => 'after',
			'end'    => 'before',
			'type'   => 'event_type',
		];

		foreach ( $map as $old_key => $new_key ) {
			if ( $val = get_array_var( $events_query, $old_key ) ) {
				$events_query[ $new_key ] = $val;
			}
		}

		switch ( absint( $events_query['event_type'] ) ) {
			default:
			case Event::FUNNEL:
				$filters[0][] = [
					'type'       => 'funnel_history',
					'status'     => $events_query['status'],
					'funnel_id'  => absint( get_array_var( $events_query, 'funnel_id' ) ),
					'step_id'    => absint( get_array_var( $events_query, 'step_id' ) ),
					'date_range' => 'between',
					'after'      => Ymd_His( absint( get_array_var( $events_query, 'after' ) ) ),
					'before'     => Ymd_His( absint( get_array_var( $events_query, 'before' ) ) )
				];

				break;

			case Event::BROADCAST:

				$broadcast_id = absint( get_array_var( $events_query, 'step_id' ) );
				$broadcast    = new Broadcast( $broadcast_id );

				if ( $broadcast->exists() ) {
					$filters[0][] = [
						'type'         => 'broadcast_received',
						'status'       => $broadcast->is_sent() ? 'complete' : 'waiting',
						'broadcast_id' => $broadcast_id,
					];
				}

				break;
			case Event::EMAIL_NOTIFICATION:
				break;
		}
	}

	if ( isset_not_empty( $query, 'activity' ) ) {

		$activity_query = wp_parse_args( $query['activity'], [
			'activity_type' => ''
		] );

		$map = [
			'step'   => 'step_id',
			'funnel' => 'funnel_id',
			'start'  => 'after',
			'end'    => 'before',
		];

		foreach ( $map as $old_key => $new_key ) {
			if ( $val = get_array_var( $activity_query, $old_key ) ) {
				$activity_query[ $new_key ] = $val;
			}
		}

		switch ( $activity_query['activity_type'] ) {
			default:
				break;
			case Activity::SMS_CLICKED:
				$filters[0][] = [
					'type'         => 'broadcast_link_clicked',
					'broadcast_id' => absint( $activity_query['step_id'] ),
					'link'         => get_array_var( $activity_query, 'referer', get_referer_from_referer_hash( get_array_var( $activity_query, 'referer_hash' ) ) ),
				];
				break;
			case Activity::EMAIL_CLICKED:
				if ( get_array_var( $activity_query, 'funnel_id' ) == Broadcast::FUNNEL_ID ) {
					$filters[0][] = [
						'type'         => 'broadcast_link_clicked',
						'broadcast_id' => absint( $activity_query['step_id'] ),
						'link'         => get_array_var( $activity_query, 'referer', get_referer_from_referer_hash( get_array_var( $activity_query, 'referer_hash' ) ) ),
					];
				} else {
					$filters[0][] = [
						'type'          => 'email_link_clicked',
						'count_compare' => 'greater_than_or_equal_to',
						'count'         => 1,
						'email_id'      => absint( $activity_query['email_id'] ),
						'link'          => get_array_var( $activity_query, 'referer', get_referer_from_referer_hash( get_array_var( $activity_query, 'referer_hash' ) ) ),
						'after'         => Ymd_His( absint( get_array_var( $activity_query, 'after' ) ) ),
						'before'        => Ymd_His( absint( get_array_var( $activity_query, 'before' ) ) ),
						'date_range'    => 'between',
					];
				}
				break;
			case Activity::EMAIL_OPENED:

				if ( get_array_var( $activity_query, 'funnel_id' ) == Broadcast::FUNNEL_ID ) {

					$filters[0][] = [
						'type'         => 'broadcast_opened',
						'broadcast_id' => absint( $activity_query['step_id'] )
					];

				} else {
					$filters[0][] = [
						'type'          => 'email_opened',
						'count_compare' => 'greater_than_or_equal_to',
						'count'         => 1,
						'email_id'      => absint( get_array_var( $activity_query, 'email_id' ) ),
						'after'         => Ymd_His( absint( get_array_var( $activity_query, 'after' ) ) ),
						'before'        => Ymd_His( absint( get_array_var( $activity_query, 'before' ) ) ),
						'date_range'    => 'between',
					];
				}

				break;
		}

	}

	// Meta
	$meta_compare_map = [
		'='          => 'equals',
		'!='         => 'not_equals',
		'>'          => 'greater_than',
		'gt'         => 'greater_than',
		'<'          => 'less_than',
		'lt'         => 'less_than',
		'>='         => 'greater_than_or_equal_to',
		'gt_eq'      => 'greater_than_or_equal_to',
		'<='         => 'less_than_or_equal_to',
		'lt_eq'      => 'less_than_or_equal_to',
		'REGEXP'     => 'contains',
		'NOT REGEXP' => 'not_contains',
		'EXISTS'     => 'not_empty',
		'NOT EXISTS' => 'empty',
	];

	// Meta Key
	if ( isset_not_empty( $query, 'meta_key' ) ) {
		$filters[0][] = [
			'type'    => 'meta',
			'meta'    => $query['meta_key'],
			'compare' => get_array_var( $meta_compare_map, get_array_var( $query, 'meta_compare' ), 'equals' ),
			'value'   => get_array_var( $query, 'meta_value' )
		];
	}

	$filters = apply_filters( 'groundhogg/get_filters_from_old_query_vars', $filters, $query );

	// Filters is an array[] so wrap in another array
	return ! empty( $filters[0] ) ? $filters : [];
}

/**
 * Whether the admin bar widget is enabled or not.
 *
 * @return mixed|void
 */
function is_admin_bar_widget_disabled() {
	return apply_filters( 'groundhogg/is_admin_bar_widget_disabled', is_option_enabled( 'gh_is_admin_bar_widget_disabled' ) );
}

/**
 * Implodes a string list surrounded by quotes, useful for SQL queries.
 *
 * @param $items
 *
 * @return string
 */
function implode_in_quotes( $items ) {
	return implode( ',', array_map( function ( $item ) {
		$item = esc_sql( $item );

		return "'$item'";
	}, $items ) );
}

/**
 * Same as implode in quotes but will handle integers differently
 *
 * @param $items
 *
 * @return string
 */
function maybe_implode_in_quotes( $items ) {
	return implode( ',', array_map( function ( $i ) {
		$i = esc_sql( $i );

		if ( is_numeric( $i ) ) {
			return intval( $i );
		} else if ( is_string( $i ) ) {
			return "'{$i}'";
		}

		return $i;
	}, $items ) );
}

/**
 * Get the current visitors IP address
 *
 * @return string
 */
function get_current_ip_address() {
	return utils()->location->get_real_ip();
}

/**
 * Collapse long strings into shorter ones by replacing the middle with "..."
 *
 * @param     $str
 * @param int $size
 *
 * @return mixed|string
 */
function collapse_string( $str, $size = 30 ) {

	if ( strlen( $str ) <= $size ) {
		return $str;
	}

	$before = ( $size / 2 ) - 1;
	$after  = ( $size / 2 ) - 2;

	return sprintf( '%s...%s', substr( $str, 0, $before ), substr( $str, - $after, $after ) );
}

/**
 * Has the current visitor accepted cookies
 *
 * @return bool|mixed|void
 */
function has_accepted_cookies() {

	// GDPR features are not enabled, so consent is implicit
	if ( ! Plugin::$instance->preferences->is_gdpr_enabled() ) {
		return apply_filters( 'groundhogg/has_accepted_cookies', true );
	}

	$cookie_name  = get_option( 'gh_consent_cookie_name' );
	$cookie_value = get_option( 'gh_consent_cookie_value' );

	return apply_filters( 'groundhogg/has_accepted_cookies', get_cookie( $cookie_name ) === $cookie_value );
}

/**
 * Calls a method of each on each item
 *
 * @param $array  mixed[]
 * @param $method callable
 *
 * @return array|false[]
 */
function array_map_to_method( $array, $method ) {
	return array_map( function ( $item ) use ( $method ) {

		if ( method_exists( $item, $method ) ) {
			return call_user_func( [ $item, $method ] );
		}

		return false;
	}, $array );
}

/**
 * Take a list of stuff that may or may not be tag Ids
 * and return a list of those Ids or tag objects
 *
 * Things which can be passed
 *
 * Tag[]
 * int[]
 * string[]: ['16', '22', '96']
 * string[]: ['my-tag', 'another-tag', 'third-tag']
 * string[]: ['My Tag', 'Another Tag', 'Third Tag']
 * Tag
 * int
 * string: '16,22,96,41'
 * string: 'my-tag',
 * string: 'My tag'
 *
 * @param mixed  $maybe_tags stuff that might be tags
 * @param string $as         accepts ID | slug | name
 * @param bool   $create     whether to create a new tag if the given one does not exist.
 *
 * @return mixed[]
 */
function parse_tag_list( $maybe_tags, $as = 'ID', $create = true ) {

	// Some falsy value? Return false
	if ( empty( $maybe_tags ) ) {
		return [];
	}

	if ( is_array( $maybe_tags ) ) {

		$tags = array_map( function ( $maybe_tag ) use ( $create ) {

			if ( empty( $maybe_tag ) ) {
				return false;
			}

			if ( is_a( $maybe_tag, Tag::class ) ) {
				return $maybe_tag;
			}

			if ( is_numeric( $maybe_tag ) ) {
				return new Tag( $maybe_tag );
			}

			if ( is_string( $maybe_tag ) ) {

				$slug = sanitize_title( $maybe_tag );

				// This will create a new tag if it doesn't exist already :)
				if ( $create ) {
					return new Tag( [
						'tag_name' => $maybe_tag,
						'tag_slug' => $slug
					] );
				}

				return new Tag( $slug, 'tag_slug' );
			}

			return false;

		}, $maybe_tags );

	} else if ( is_a( $maybe_tags, Tag::class ) ) {
		$tags = [ $maybe_tags ];
	} else if ( is_numeric( $maybe_tags ) ) {
		$tags = [ new Tag( $maybe_tags ) ];
	} else if ( is_string( $maybe_tags ) ) {
		// it's a comma separated list
		if ( str_contains( $maybe_tags, ',' ) ) {
			$tags = parse_tag_list( wp_parse_list( $maybe_tags ), 'tags' );
		} else {

			// if create is true, use the query and create method, otherwise use the slug
			$tags = [
				$create ? new Tag( [
					'tag_name' => $maybe_tags,
					'tag_slug' => sanitize_title( $maybe_tags )
				] ) : new Tag( sanitize_title( $maybe_tags ), 'tag_slug' )
			];
		}

	} else {
		// Return an empty array instead...
		return [];
	}

	$tags = array_filter( $tags, function ( $tag ) {
		return is_a( $tag, Tag::class ) && $tag->exists();
	} );

	switch ( $as ) {
		case 'ID':
			return array_map_to_method( $tags, 'get_id' );
		case 'slug':
			return array_map_to_method( $tags, 'get_slug' );
		case 'name':
			return array_map_to_method( $tags, 'get_name' );
		default:
		case 'tags':
		case 'object':
			return $tags;
	}
}

/**
 * Add a new log entry
 *
 * @param        $event
 * @param string $name
 * @param string $value
 *
 * @return void
 */
function log( $event, $name = '', $value = '' ) {
	get_db( 'logs' )->add( [
		'event' => sanitize_key( $event ),
		'name'  => sanitize_text_field( $name ),
		'value' => sanitize_text_field( $value )
	] );
}

/**
 * Whether WP FUsion is active or not
 *
 * @return bool
 */
function is_wp_fusion_active() {
	return defined( 'WP_FUSION_VERSION' );
}

/**
 * Get the hostname of an email address
 *
 * @param $email string
 *
 * @return false|mixed|string
 */
function get_email_address_hostname( $email ) {

	if ( ! is_email( $email ) ) {
		return false;
	}

	$parts = explode( '@', $email );

	return $parts[1];
}

/**
 * If the given email is from a free inbox provider
 *
 * @param $email string
 */
function is_free_email_provider( $email ) {

	if ( ! is_email( $email ) ) {
		return false;
	}

	static $providers = [];

	// initialize providers
	if ( empty( $providers ) ) {
		$providers = json_decode( file_get_contents( GROUNDHOGG_ASSETS_PATH . 'lib/free-email-providers.json' ), true );
	}

	return apply_filters( 'groundhogg/is_free_email_provider', in_array( get_email_address_hostname( $email ), $providers ) );
}

/**
 * Filter object array based on exists
 *
 * @param $array
 *
 * @return array
 */
function filter_object_exists( $array ) {
	return array_filter( $array, function ( $obj ) {
		return method_exists( $obj, 'exists' ) && $obj->exists();
	} );
}

add_action( 'delete_user_form', __NAMESPACE__ . '\select_contact_owner_to_reassign', 10, 2 );

/**
 * Show a dropdown to reassign any contacts or other assigned objects to the new user
 *
 * @param $current_user \WP_User
 * @param $all_ids      int[]
 *
 * @return void
 */
function select_contact_owner_to_reassign( $current_user, $all_ids ) {

	$num_related_contacts = get_db( 'contacts' )->count( [
		'users_include' => $all_ids
	] );

	if ( $num_related_contacts > 0 ):

		?>
        <fieldset>
            <p>
                <legend><?php _e( 'Also delete contact records related to these users?', 'groundhogg' ); ?></legend>
            </p>
            <p><?php echo html()->checkbox( [
					'label' => sprintf( _n( 'Delete %s related contact record', 'Delete %s related contact records', $num_related_contacts, 'groundhogg' ), number_format_i18n( $num_related_contacts ) ),
					'name'  => 'delete_related_contact_records'
				] ) ?></p>
        </fieldset>
	<?php
	endif;


	$has_owned_content = get_db( 'contacts' )->count( [
			'owner' => $all_ids
		] ) > 0 || get_db( 'funnels' )->count( [
			'author' => $all_ids
		] ) > 0 || get_db( 'emails' )->count( [
			'author' => $all_ids
		] ) > 0;

	$has_owned_content = apply_filters( 'groundhogg/user_has_crm_content', $has_owned_content );

	if ( $has_owned_content ):

		?>
        <fieldset>
            <p>
                <legend><?php echo _n( 'Who should CRM data owned by this user be reassigned to?', 'Who should CRM data owned by these users be reassigned to?', count( $all_ids ), 'groundhogg' ); ?></legend>
            </p>
            <p><?php echo html()->dropdown_owners( [
					'name'        => 'reassign_groundhogg_owner',
					'option_none' => false,
					'exclude'     => $all_ids
				] ) ?></p>
        </fieldset>
	<?php

	endif;

}

add_action( 'delete_user', __NAMESPACE__ . '\do_reassignments_when_user_deleted', 10, 2 );

/**
 * Reassign any deleted owner content to the new owner
 *
 * @param $id
 * @param $reassign
 *
 * @return void
 */
function do_reassignments_when_user_deleted( $id, $reassign ) {

	// We're not in the admin screen
	if ( ! is_admin() ) {
		return;
	}

	$delete_contact_records = get_request_var( 'delete_related_contact_records' );
	$new_owner              = absint( get_request_var( 'reassign_groundhogg_owner' ) );

	// Delete the related contact record if required
	if ( $delete_contact_records ) {
		$contact = get_contactdata( $id, true );

		if ( $contact && $contact->exists() ) {
			$contact->delete();
		}
	}

	// if a new owner was provided and the delete user was an owner
	if ( $new_owner && user_can( $id, 'view_contacts' ) && user_can( $new_owner, 'view_contacts' ) ) {

		/**
		 * Notify other plugins of owner re-assignment
		 *
		 * @param $user_id   int the ID of the deleted user
		 * @param $new_owner int the ID of the new owner
		 */
		do_action( 'groundhogg/owner_deleted', $id, $new_owner );
	}
}

/**
 * Minify html content
 *
 * @param $content
 *
 * @return string
 */
function minify_html( $content ) {
	$search = array(
		'/\>[^\S ]+/s',     // strip whitespaces after tags, except space
		'/[^\S ]+\</s',     // strip whitespaces before tags, except space
		'/(\s)+/s',         // shorten multiple whitespace sequences
		'/<!--(.|\s)*?-->/', // Remove HTML comments,
		'/\>\s+\</m' // Remove HTML comments,
	);

	$replace = array(
		'>',
		'<',
		'\\1',
		'',
		'><',
	);

	return preg_replace( $search, $replace, $content );
}

/**
 * Whether Google Recaptcha is enabled
 *
 * @return bool
 */
function is_recaptcha_enabled() {
	return is_option_enabled( 'gh_recaptcha_site_key' ) && is_option_enabled( 'gh_recaptcha_secret_key' );
}

/**
 * Whether a file can be copied because the file exists or it's a downloadable URL of the same hostname
 *
 * @param $file string
 *
 * @return bool
 */
function is_copyable_file( $file ) {
	return file_exists( $file ) || get_hostname( $file ) === get_hostname();
}

/**
 * Process events in the event queue
 * Pass a contact to only process evenAts for a specific contact
 *
 * @param $contacts Contact|Contact[]
 *
 * @return bool|WP_Error[]
 */
function process_events( $contacts = [] ) {

	if ( empty( $contacts ) ) {
		return false;
	}

	// Event queue is already in progress
	if ( Event_Queue::is_processing() ) {
		return true;
	}

	$process = new Process_Contact_Events( $contacts );

	return $process->has_errors() ? $process->get_errors() : true;
}

/**
 * Backwards compat for encrypted ID vs slug
 *
 * @param $data
 *
 * @return int|mixed
 */
function maybe_url_decrypt_id( $data ) {
	return absint( decrypt( urldecode( $data ) ) ) ?: $data;
}

function iframe_js() {
	?>
    <script>
      if (window.self !== window.top) {
        document.querySelector('html').classList.add('iframed')
      }
    </script>
	<?php
}

add_action( 'admin_head', __NAMESPACE__ . '\iframe_js', 9 );

/**
 * Whether the current locale is english or not
 *
 * @return bool
 */
function site_locale_is_english() {
	return in_array( get_locale(), [
		'en',
		'en-us',
		'en_US',
		'en-au',
		'en_AU',
		'en-ca',
		'en_CA',
		'en-gb',
		'en_GB',
	] );
}

/**
 * Whether custom step names should be forced instead of created based on settings
 *
 * @return bool
 */
function force_custom_step_names() {
	return is_option_enabled( 'gh_force_custom_step_names' ) || ! site_locale_is_english();
}

function bold_it( $content ) {
	return html()->e( 'b', [], $content, false );
}

function code_it( $content ) {
	return html()->e( 'code', [], $content, false );
}

function array_bold( $array ) {
	return array_map( function ( $item ) {
		return bold_it( $item );
	}, $array );
}

function array_code( $array ) {
	return array_map( function ( $item ) {
		return code_it( $item );
	}, $array );
}

function andList( $array ) {
	if ( empty( $array ) ) {
		return '';
	}
	if ( count( $array ) === 1 ) {
		return $array[0];
	}

	return sprintf( _x( '%s and %s', 'and preceding the last item in a list', 'groundhogg' ),
		implode( ', ', array_slice( $array, 0, - 1 ) ), $array[ count( $array ) - 1 ] );
}

function orList( $array ) {
	if ( empty( $array ) ) {
		return '';
	}
	if ( count( $array ) === 1 ) {
		return $array[0];
	}

	return sprintf( _x( '%s or %s', 'or preceding the last item in a list', 'groundhogg' ),
		implode( ', ', array_slice( $array, 0, - 1 ) ), $array[ count( $array ) - 1 ] );
}

/**
 * Returns the current user's timezone
 *
 * @return \DateTimeZone
 */
function get_user_timezone() {

	$tz = utils()->location->ip_info( null, 'time_zone' );

	if ( $tz ) {
		return new \DateTimeZone( $tz );
	}

	return wp_timezone();
}

/**
 * Add the ordinal suffix to a number;
 *
 * @param $num
 *
 * @return string
 */
function ordinal_suffix( $num ) {
	$num = intval( $num );
	$num = $num % 100; // protect against large numbers
	if ( $num < 11 || $num > 13 ) {
		switch ( $num % 10 ) {
			case 1:
				return $num . 'st';
			case 2:
				return $num . 'nd';
			case 3:
				return $num . 'rd';
		}
	}

	return $num . 'th';
}

/**
 * If funnel events go missing, this will restore them
 *
 * @return void
 */
function restore_missing_funnel_events() {
	global $wpdb;

	$events_table = \Groundhogg\get_db( 'events' );
	$last_30_days = strtotime( '30 days ago' );

	$SQL = "SELECT contact_id,funnel_id FROM $events_table->table_name 
WHERE event_type = 1 AND time > $last_30_days AND status = 'complete' GROUP BY contact_id, funnel_id";

	$results = $wpdb->get_results( $SQL );

	foreach ( $results as $record ) {

		// Get most recent event for the given funnel
		$_event = $events_table->query( [
			'contact_id' => $record->contact_id,
			'funnel_id'  => $record->funnel_id,
			'event_type' => \Groundhogg\Event::FUNNEL,
			'limit'      => 1,
			'orderby'    => 'time',
			'order'      => 'DESC'
		] );

		$event = new \Groundhogg\Event( $_event[0]->ID );

		// if the event does not exist
		// If the event did not complete
		// If the event does not have an associated step
		// If the step does not exist
		if ( ! $event->exists()
		     || ! $event->is_complete()
		     || ! $event->get_step()
		     || ! $event->get_step()->exists()
		) {
			continue;
		}

		// Do nothing if there is a pending waiting event
		if ( event_queue_db()->exists( [
			'contact_id' => $event->get_contact_id(),
			'funnel_id'  => $event->get_funnel_id(),
			'event_type' => Event::FUNNEL,
			'status'     => Event::WAITING
		] ) ) {
			continue;
		}

		$event->get_step()->run_after( $event->get_contact(), $event );
	}
}


/**
 * Get the scree Id of a given gh page
 *
 * @param $page
 *
 * @return string
 */
function get_gh_page_screen_id( $page = '' ) {
	$pagename = sanitize_title( white_labeled_name() );

	return "{$pagename}_page_{$page}";

}

/**
 * Is the current screen a specific Groundhogg page
 *
 * @param $page
 *
 * @return bool
 */
function current_screen_is_gh_page( $page = '' ) {
	return get_gh_page_screen_id( $page ) === get_current_screen()->id;
}

/**
 * generic function for fetching last changed in cache group
 *
 * @param $group
 *
 * @return false|mixed|string
 */
function cache_get_last_changed( $group ) {
	if ( function_exists( 'wp_cache_get_last_changed' ) ) {
		return wp_cache_get_last_changed( $group );
	}

	$last_changed = wp_cache_get( 'last_changed', $group );

	if ( ! $last_changed ) {
		$last_changed = microtime();
		wp_cache_set( 'last_changed', $last_changed, $group );
	}

	return $last_changed;
}

/**
 * Allows expiring of cache results
 *
 * @param $group
 *
 * @return void
 */
function cache_set_last_changed( $group ) {
	wp_cache_set( 'last_changed', microtime(), $group );
}

/**
 * Deletes any pending events for a given step type for the current contact
 *
 * @return false|int
 */
function clear_pending_events_by_step_type( $type, $contact = false ) {

	$contact = get_contactdata( $contact );

	if ( ! is_a_contact( $contact ) ) {
		return false;
	}

	$steps = get_db( 'steps' )->query( [
		'step_type' => $type
	] );

	if ( empty( $steps ) ) {
		return false;
	}

	return event_queue_db()->query( [
		'operation' => 'DELETE',
		'where'     => [
			[ 'step_id', 'IN', wp_parse_id_list( wp_list_pluck( $steps, 'ID' ) ) ],
			[ 'event_type', '=', Event::FUNNEL ],
			[ 'status', '=', Event::WAITING ],
			[ 'contact_id', '=', $contact->get_id() ]
		]
	] );
}

/**
 * Whether the given data is base64 encoded or not
 *
 * @param $data
 *
 * @return bool
 */
function is_base64_encoded( $data ) {
	return base64_decode( $data, true ) !== false;
}

/**
 * The best approximate date this site started using Groundhogg
 *
 * @return \DateTimeInterface
 */
function date_started_using_groundhogg() {
	return get_db( 'contacts' )->get_date_created();
}

/**
 * The number of emails sent between a specific time frame
 *
 * @param \DateTimeInterface $from
 * @param \DateTimeInterface $to
 *
 * @return void
 */
function num_emails_sent( \DateTimeInterface $from, \DateTimeInterface $to ) {

	global $wpdb;

	$events_table = get_db( 'events' )->get_table_name();
	$steps_table  = get_db( 'steps' )->get_table_name();

	return $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM $events_table e 
                LEFT JOIN $steps_table s ON e.step_id = s.ID 
                WHERE e.status = %s AND ( s.step_type = %s OR e.event_type = %d OR e.event_type = %d)
                AND e.time >= %d AND e.time <= %d"
		, 'complete', 'send_email', Event::BROADCAST, Event::EMAIL_NOTIFICATION,
		$from->getTimestamp(), $to->getTimestamp() )
	);
}

/**
 * Tells us whether this site qualifies for review your funnel
 *
 * @return bool
 */
function qualifies_for_review_your_funnel() {

	$one_month_ago = new DateTimeHelper( '1 month ago' );

	$conditions = [
		// has at least 3 active funnels
		get_db( 'funnels' )->count( [ 'status' => 'active' ] ) >= 3,

		// using for at least 30 days
		date_started_using_groundhogg() < $one_month_ago,

		// has sent at least 1000 emails in the last month
		num_emails_sent( $one_month_ago, new DateTimeHelper( 'now' ) ) >= 1000,

		// Is a paid customer
		has_premium_features()
	];

	return ! in_array( false, $conditions );
}

/**
 * Parses a list into number and non-numeric items
 *
 * @param $list     array|string
 * @param $sanitize callable
 *
 * @return array|int[]
 */
function parse_maybe_numeric_list( $list, $sanitize = 'sanitize_text_field' ) {
	$list = wp_parse_list( $list );

	return array_map( function ( $item ) use ( $sanitize ) {
		return is_numeric( $item ) ? absint( $item ) : call_user_func( $sanitize, $item );
	}, $list );
}

/**
 * Programmatically create a task and associate it with the relevant object
 *
 * @param $args   array
 * @param $object Base_Object|Contact
 *
 * @return void
 */
function create_task( $args, $object ) {

}

/**
 * If the contact is viewing the email in the browser
 *
 * @return bool
 */
function is_browser_view() {
	return defined( 'GROUNDHOGG_IS_BROWSER_VIEW' ) && GROUNDHOGG_IS_BROWSER_VIEW;
}

/**
 * If an email is actually sending to the contact
 * This is used as a flag for generating replacements which may contain sensitive information
 *
 * @param bool|null $sending
 *
 * @return bool
 */
function is_sending( $sending = null ) {

	static $_sending;

	if ( $sending === null ) {
		return $_sending;
	}

	$_sending = $sending;

	return $_sending;
}

/**
 * Makes swapping array keys easy...
 *
 * @param array $array
 * @param array $key_map
 *
 * @return array
 */
function swap_array_keys( array $array = [], array $key_map = [] ) {

	foreach ( $key_map as $old => $new ) {
		if ( isset( $array[ $old ] ) ) {
			$array[ $new ] = $array[ $old ];
		}
	}

	return $array;
}

/**
 * Main tabs in the contact profile that can be selected to open by default
 *
 * @return mixed|void
 */
function get_valid_contact_tabs() {
	return apply_filters( 'groundhogg/get_valid_contact_tabs', [
		'activity' => __( 'Activity Timeline', 'groundhogg' ),
		'notes'    => __( 'Notes', 'groundhogg' ),
		'tasks'    => __( 'Tasks', 'groundhogg' ),
		'files'    => __( 'Files', 'groundhogg' ),
	] );
}

/**
 * The default tab to show in the contact record
 *
 * @return false|mixed|string
 */
function get_default_contact_tab() {

	if ( get_url_var( '_tab' ) ) {
		$tab = get_url_var( '_tab' );
	} else {
		$tab = get_user_meta( get_current_user_id(), 'gh_default_contact_tab', true );

		if ( empty( $tab ) ) {
			$tab = get_option( 'gh_default_contact_tab' ) ?: 'activity';
		}
	}

	return key_exists( $tab, get_valid_contact_tabs() ) ? $tab : 'activity';
}

/**
 * Standard admin ajax nonce function
 *
 * @return false|int
 */
function verify_admin_ajax_nonce() {
	return wp_verify_nonce( get_request_var( 'gh_admin_ajax_nonce' ), 'admin_ajax' );
}

/**
 * Returns the name for the day of the week given an int
 *
 * @param $number int
 *
 * @return bool|mixed
 */
function day_of_week( $number = 0 ) {
	return get_array_var( [
		'Sunday',
		'Monday',
		'Tuesday',
		'Wednesday',
		'Thursday',
		'Friday',
		'Saturday',
	], absint( $number ) );
}

/**
 * Sets/gets the email, for use with email templates
 *
 * @param null|Email $set_email
 *
 * @return Email
 */
function the_email( $set_email = null ) {
	static $email;

	if ( $set_email ) {
		$email = $set_email;
	}

	return $email;
}

/**
 * Convert hex to RGB
 * https://stackoverflow.com/questions/12228644/how-to-detect-light-colors-with-php
 *
 * @param $hexCode
 *
 * @return float|int
 */
function HEXToRGB( $hexCode ) {
	if ( $hexCode[0] == '#' ) {
		$hexCode = substr( $hexCode, 1 );
	}

	if ( strlen( $hexCode ) == 3 ) {
		$hexCode = $hexCode[0] . $hexCode[0] . $hexCode[1] . $hexCode[1] . $hexCode[2] . $hexCode[2];
	}

	$r = hexdec( $hexCode[0] . $hexCode[1] );
	$g = hexdec( $hexCode[2] . $hexCode[3] );
	$b = hexdec( $hexCode[4] . $hexCode[5] );

	return $b + ( $g << 0x8 ) + ( $r << 0x10 );
}

/**
 * Convert RGD to HSL
 * https://stackoverflow.com/questions/12228644/how-to-detect-light-colors-with-php
 *
 * @param $RGB
 *
 * @return object
 */
function RGBToHSL( $RGB ) {
	$r = 0xFF & ( $RGB >> 0x10 );
	$g = 0xFF & ( $RGB >> 0x8 );
	$b = 0xFF & $RGB;

	$r = ( (float) $r ) / 255.0;
	$g = ( (float) $g ) / 255.0;
	$b = ( (float) $b ) / 255.0;

	$maxC = max( $r, $g, $b );
	$minC = min( $r, $g, $b );

	$l = ( $maxC + $minC ) / 2.0;

	if ( $maxC == $minC ) {
		$s = 0;
		$h = 0;
	} else {
		if ( $l < .5 ) {
			$s = ( $maxC - $minC ) / ( $maxC + $minC );
		} else {
			$s = ( $maxC - $minC ) / ( 2.0 - $maxC - $minC );
		}
		if ( $r == $maxC ) {
			$h = ( $g - $b ) / ( $maxC - $minC );
		}
		if ( $g == $maxC ) {
			$h = 2.0 + ( $b - $r ) / ( $maxC - $minC );
		}
		if ( $b == $maxC ) {
			$h = 4.0 + ( $r - $g ) / ( $maxC - $minC );
		}

		$h = $h / 6.0;
	}

	$h = (int) round( 255.0 * $h );
	$s = (int) round( 255.0 * $s );
	$l = (int) round( 255.0 * $l );

	return (object) array( 'hue' => $h, 'saturation' => $s, 'lightness' => $l );
}

/**
 * Test the lightness of a hex color
 *
 * @param $hex
 * @param $compare
 *
 * @return bool
 */
function hex_is_lighter_than( $hex, $compare ) {
	$rgb = HEXToRGB( $hex );
	$hsl = RGBToHSL( $rgb );

	return $hsl->lightness > $compare;
}

/**
 * Downloads given json as JSON file
 *
 * @param $json mixed something json encodable
 * @param $name string name fo the file to export
 */
function download_json( $json, $name ) {

	status_header( 200 );
	nocache_headers();

	$export_string = wp_json_encode( $json );

	$export_name = strtolower( preg_replace( '/[^A-z0-9]/', '-', $name ) );

	header( "Content-type: text/plain" );
	header( "Content-disposition: attachment; filename=" . $export_name . ".json" );
	$file = fopen( 'php://output', 'w' );
	fputs( $file, $export_string );
	fclose( $file );
	exit();

}

/**
 * Regex that matches JSON
 *
 * @return string
 */
function get_json_regex() {
	return '(?(DEFINE)'
	       . '(?\'json\'(?>\s*(?&object)\s*|\s*(?&array)\s*))'
	       . '(?\'object\'(?>\{\s*(?>(?&pair)(?>\s*,\s*(?&pair))*)?\s*\}))'
	       . '(?\'pair\'(?>(?&STRING)\s*:\s*(?&value)))'
	       . '(?\'array\'(?>\[\s*(?>(?&value)(?>\s*,\s*(?&value))*)?\s*\]))'
	       . '(?\'value\'(?>true|false|null|(?&STRING)|(?&NUMBER)|(?&object)|(?&array)))'
	       . '(?\'STRING\'(?>"(?>\\\\(?>["\/\\\\bfnrt]|u[a-fA-F0-9]{4})|[^\\\\"\0-\x1F\x7F]+)*"))'
	       . '(?\'NUMBER\'(?>-?(?>0|[1-9][0-9]*)(?>\.[0-9]+)?(?>[eE][+-]?[0-9]+)?))'
	       . ')';
}

/**
 * Convert HTML to markdown
 *
 * @param      $string
 * @param bool $clean_up
 * @param bool $tidy_up
 *
 * @return string
 *
 * @credit https://gist.github.com/gaffling/94eca5d545b1781a2ea34324b1cf7a6c
 */
function html2markdown( $string, $clean_up = true, $tidy_up = true ) {

	if ( empty( $string ) ) {
		return '';
	}

	// INI
	$markdown = $string;
	// USE ONLY THE BODY OF A WEBPAGE
	if ( $clean_up == true ) {
		// CORRECT THE HTML - or use https://www.barattalo.it/html-fixer/
		$dom = new \DOMDocument(); // FIX ENCODING https://stackoverflow.com/a/8218649

		if ( function_exists( 'iconv' ) ) {
			@$dom->loadHTML( htmlspecialchars_decode( iconv( 'UTF-8', 'ISO-8859-1', htmlentities( $markdown, ENT_COMPAT, 'UTF-8' ) ), ENT_QUOTES ) );
		} else {
			@$dom->loadHTML( $markdown );
		}

		$markdown = $dom->saveHTML();
		// preg_match() IS NOT SO NICE, BUT WORKS FOR ME
		preg_match( "/<body[^>]*>(.*?)<\/body>/is", $markdown, $matches );
		$markdown = $matches[1];
		$markdown = str_replace( array( "\r", "\n", "\t", '  ' ), array( '', '', '', ' ' ), $markdown );
	}
	// GET RID OF ATTRIBUTES! PARTICULARLY USEFUL WHEN CONVERTING GARBAGE WYSIWYG CODE TO MARKDOWN
	if ( $tidy_up == true ) {
		$elements = implode( '|', [
			'table',
			'tr',
			'th',
			'td',
			'ul',
			'ol',
			'li',
			'p',
			'blockquote',
			'strong',
			'b',
			'em',
			'i',
			'hr',
			'h1',
			'h2',
			'h3',
			'h4',
			'h5',
			'h6'
		] );

		$markdown = preg_replace( "/<($elements) [^>]*?>/", "<$1>", $markdown );
	}
	// REPLACE SIMPLE TAGS WITH MARKDOWN EQUIVALENT
	$regexMap = array(
		'p'        => '',
		'\/p'      => PHP_EOL,
		'span'     => '',
		'span.*?'  => '',
		'\/span'   => '',
		'div'      => '',
		'div.*?'   => '',
		'\/div'    => '',
		'h1'       => '# ',
		'h2'       => '## ',
		'h3'       => '### ',
		'h4'       => '#### ',
		'h5'       => '##### ',
		'h6'       => '###### ',
		'\/h\d'    => PHP_EOL,
		'br'       => '  ' . PHP_EOL,
		'br\s\/'   => '  ' . PHP_EOL,
		'hr'       => PHP_EOL . '---' . PHP_EOL . PHP_EOL,
		'hr.*?'    => PHP_EOL . '---' . PHP_EOL . PHP_EOL,
		'strong'   => '**',
		'\/strong' => '**',
		'b'        => '**',
		'\/b'      => '**',
		'em'       => '_',
		'\/em'     => '_',
		'i'        => '_',
		'\/i'      => '_',
	);
	foreach ( $regexMap as $el => $replacement ) {
		$markdown = preg_replace( "/\<$el\>/i", $replacement, $markdown );
	}

	// IMAGES
	if ( preg_match_all( '/<img.*src="([^\s"]*?)".*?\/?>/i', $markdown, $matches ) ) {
		foreach ( $matches[0] as $i => $img_markup ) {
			$url = $matches[1][ $i ];
			$alt = '';
			if ( preg_match( '/alt="([^"]*?)"/i', $img_markup, $alt_match ) ) {
				$alt = $alt_match[1];
			}
			if ( $alt == '' and preg_match( '/title="([^"]*?)"/i', $img_markup, $alt_match ) ) {
				$alt = $alt_match[1];
			}
			$img_markdown = '![' . $alt . '](' . $url . ')';
			$markdown     = str_replace( $img_markup, $img_markdown, $markdown );
		}
	}
	// LINKS
	if ( preg_match_all( '/<a.*?href="([^\s"]*?)".*?>(.*?)?<\/a>/is', $markdown, $matches ) ) {
		foreach ( $matches[0] as $i => $a_markup ) {
			$href       = $matches[1][ $i ];
			$text       = $matches[2][ $i ];
			$a_markdown = '[' . $text . '](' . $href . ')';
			$markdown   = str_replace( $a_markup, $a_markdown, $markdown );
		}
	}
	// UNORDERED LISTS
	if ( preg_match_all( '/<ul>(.*?)<\/ul>/is', $markdown, $ul_matches ) ) {
		$markdown = preg_replace( '/<ul>|<\/ul>/i', '', $markdown );
		foreach ( $ul_matches[0] as $ul ) {
			if ( preg_match_all( '/[\t ]?<li>(.*?)<\/li>/is', $ul, $list_items_ul ) ) {
				foreach ( $list_items_ul[0] as $i => $ulli_markup ) {
					$ulli_inner    = trim( $list_items_ul[1][ $i ] );
					$ulli_markdown = ' * ' . $ulli_inner . PHP_EOL;
					$markdown      = str_replace( $ulli_markup, $ulli_markdown, $markdown );
				}
			}
		}
	}
	// ORDERED LISTS
	if ( preg_match_all( '/<ol>(.*?)<\/ol>/is', $markdown, $ol_matches ) ) {
		$markdown = preg_replace( '/<ol>|<\/ol>/i', '', $markdown );
		foreach ( $ol_matches[0] as $ol ) {
			if ( preg_match_all( '/[\t ]?<li>(.*?)<\/li>/is', $ol, $list_items_ol ) ) {
				foreach ( $list_items_ol[0] as $i => $olli_markup ) {
					$olli_inner    = trim( $list_items_ol[1][ $i ] );
					$olli_markdown = ' ' . ( $i + 1 ) . '. ' . $olli_inner . PHP_EOL;
					$markdown      = str_replace( $olli_markup, $olli_markdown, $markdown );
				}
			}
		}
	}
	// TABLE
	if ( preg_match_all( '/<table>(.*?)<\/table>/s', $markdown, $matches ) ) {
		$markdown = preg_replace( '/<table>|<\/table>/', '', $markdown );
		foreach ( $matches[0] as $table ) {
			if ( preg_match_all( '/<tr>(.*?)<\/tr>/s', $table, $table_items ) ) {
				foreach ( $table_items[0] as $i => $tr_markup ) {
					if ( preg_match_all( '/.*?<(th|td)>(.*?)<\/(th|td)>.*?/s', $tr_markup, $tr_items ) ) {
						$colums = count( $tr_items[1] );
						foreach ( $tr_items[0] as $i => $tr_markup ) {
							$td_inner    = $tr_items[2][ $i ];
							$td_markdown = $td_inner;
							if ( $colums != ( $i + 1 ) ) {
								$td_markdown = '|' . $td_inner . '|';
							}
							$markdown = str_replace( $tr_markup, $td_markdown, $markdown );
						}
					}
				}
			}
		}
		$head     = PHP_EOL . '|' . str_repeat( '---|', $colums ) . PHP_EOL;
		$markdown = preg_replace( '/<\/tr>/', '|' . $head, $markdown, 1 );
		$markdown = preg_replace( '/<\/tr>/', '|' . PHP_EOL, $markdown );
	}
	// BLOCKQUOTES
	if ( preg_match_all( '/<blockquote>(.*?)<\/blockquote>/is', $markdown, $matches ) ) {
		$blockquote_markdown = '';
		foreach ( $matches[1] as $i => $inner_html ) {
			$blockquote_markup   = $matches[ $i ];
			$blockquote_markdown = '';
			$lines               = explode( PHP_EOL, $inner_html );
			foreach ( $lines as $line ) {
				$blockquote_markdown .= '> ' . $line . PHP_EOL;
			}
			$markdown = str_replace( $blockquote_markup, $blockquote_markdown, $markdown );
		}
	}

	return trim( $markdown );
}

/**
 * Returns a string representing Good Fiar or Poor given specific thresholds
 *
 * @param int  $number
 * @param int  $great
 * @param int  $good
 * @param int  $fair
 * @param int  $poor
 * @param bool $inverse
 *
 * @return string good|fair|poor|bad
 */
function is_good_fair_or_poor( int $number, int $great, int $good, int $fair, int $poor ){

	if ( $number >= $great ){
		return 'great';
	}

    if ( $number >= $good ){
        return 'good';
    }

    if ( $number >= $fair ){
        return 'fair';
    }

    if ( $number >= $poor ){
        return 'poor';
    }

	return 'bad';
}

/**
 * Get the pretty name of a role
 *
 * @param $role string
 *
 * @return string
 */
function get_role_display_name( $role ) {
	return translate_user_role( wp_roles()->roles[ $role ]['name'] );
}

/**
 * Generate a unique string to serve as a claim
 *
 * @return false|string
 */
function generate_claim(){
	$claim_id    = md5( uniqid( microtime() ) );

	return substr( $claim_id, 0, 20 );
}
