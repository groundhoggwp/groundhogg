<?php

namespace Groundhogg;

/**
 * Convert lone less than signs.
 *
 * KSES already converts lone greater than signs.
 *
 * @since 2.3.0
 *
 * @param string $text Text to be converted.
 * @return string Converted text.
 */
function pre_kses_less_than( $text ) {
	return preg_replace_callback( '%<[^>]*?((?=<)|>|$)%', __NAMESPACE__ . '\pre_kses_less_than_callback', $text );
}

/**
 * Callback function used by preg_replace.
 *
 * @since 2.3.0
 *
 * @param array $matches Populated by matches to preg_replace.
 * @return string The text returned after esc_html if needed.
 */
function pre_kses_less_than_callback( $matches ) {
	if ( false === strpos( $matches[0], '>' ) && strpos( $matches[0], '<!' ) !== 0 ) {
		return esc_html( $matches[0] );
	}
	return $matches[0];
}

/**
 * Filters text content and strips out disallowed HTML.
 *
 * This function makes sure that only the allowed HTML element names, attribute
 * names, attribute values, and HTML entities will occur in the given text string.
 *
 * This function expects unslashed data.
 *
 * @param string         $string            Text content to filter.
 * @param array[]|string $allowed_html      An array of allowed HTML elements and attributes,
 *                                          or a context name such as 'post'. See wp_kses_allowed_html()
 *                                          for the list of accepted context names.
 * @param string[]       $allowed_protocols Array of allowed URL protocols.
 *
 * @return string Filtered content containing only the allowed HTML.
 * @see   wp_allowed_protocols() for the default allowed protocols in link URLs.
 *
 * @since 1.0.0
 *
 * @see   wp_kses_post() for specifically filtering post content and fields.
 */
function kses( $string, $allowed_html, $allowed_protocols = array() ) {
	if ( empty( $allowed_protocols ) ) {
		$allowed_protocols = wp_allowed_protocols();
	}

	$string = wp_kses_no_null( $string, array( 'slash_zero' => 'keep' ) );
	$string = wp_kses_normalize_entities( $string );

	// Converting lone less than without checking whats after breaks MSO comments
	remove_filter( 'pre_kses', 'wp_pre_kses_less_than' );
	add_filter( 'pre_kses', __NAMESPACE__ . '\pre_kses_less_than' );

	$string = wp_kses_hook( $string, $allowed_html, $allowed_protocols );

	remove_filter( 'pre_kses', __NAMESPACE__ . '\pre_kses_less_than' );
	add_filter( 'pre_kses', 'wp_pre_kses_less_than' );

	return kses_split( $string, $allowed_html, $allowed_protocols );
}

/**
 * Searches for HTML tags, no matter how malformed.
 *
 * It also matches stray `>` characters.
 *
 * @param string          $string                 Content to filter.
 * @param array[]|string  $allowed_html           An array of allowed HTML elements and attributes,
 *                                                or a context name such as 'post'. See wp_kses_allowed_html()
 *                                                for the list of accepted context names.
 * @param string[]        $allowed_protocols      Array of allowed URL protocols.
 *
 * @return string Content with fixed HTML tags
 * @global array[]|string $pass_allowed_html      An array of allowed HTML elements and attributes,
 *                                                or a context name such as 'post'.
 * @global string[]       $pass_allowed_protocols Array of allowed URL protocols.
 *
 * @since 1.0.0
 *
 */
function kses_split( $string, $allowed_html, $allowed_protocols ) {
	global $pass_allowed_html, $pass_allowed_protocols;

	$pass_allowed_html      = $allowed_html;
	$pass_allowed_protocols = $allowed_protocols;

	return preg_replace_callback( '%(<!--.*?(-->|$))|(<[^>]*(>|$)|>)%', __NAMESPACE__ . '\_kses_split_callback', $string );
}

/**
 * Callback for `wp_kses_split()`.
 *
 * @param array           $matches                preg_replace regexp matches
 *
 * @return string
 * @global array[]|string $pass_allowed_html      An array of allowed HTML elements and attributes,
 *                                                or a context name such as 'post'.
 * @global string[]       $pass_allowed_protocols Array of allowed URL protocols.
 *
 * @since  3.1.0
 * @access private
 * @ignore
 *
 */
function _kses_split_callback( $match ) {
	global $pass_allowed_html, $pass_allowed_protocols;

	return kses_split2( $match[0], $pass_allowed_html, $pass_allowed_protocols );
}

/**
 * Callback for `wp_kses_split()` for fixing malformed HTML tags.
 *
 * This function does a lot of work. It rejects some very malformed things like
 * `<:::>`. It returns an empty string, if the element isn't allowed (look ma, no
 * `strip_tags()`!). Otherwise it splits the tag into an element and an attribute
 * list.
 *
 * After the tag is split into an element and an attribute list, it is run
 * through another filter which will remove illegal attributes and once that is
 * completed, will be returned.
 *
 * @access private
 *
 * @param string         $string            Content to filter.
 * @param array[]|string $allowed_html      An array of allowed HTML elements and attributes,
 *                                          or a context name such as 'post'. See wp_kses_allowed_html()
 *                                          for the list of accepted context names.
 * @param string[]       $allowed_protocols Array of allowed URL protocols.
 *
 * @return string Fixed HTML element
 * @ignore
 * @since  1.0.0
 *
 */
function kses_split2( $string, $allowed_html, $allowed_protocols ) {
	$string = wp_kses_stripslashes( $string );

	// It matched a ">" character.
	if ( '<' !== substr( $string, 0, 1 ) ) {
		return '&gt;';
	}

	if ( str_starts_with( $string, '<![endif' ) || str_starts_with( $string, '<!DOCTYPE' ) ){
		return $string;
	}

	// Allow HTML comments.
	if ( '<!--' === substr( $string, 0, 4 ) ) {

		// MSO comments may not have comment close right away
		$is_closed = str_ends_with( $string, '-->' );
		$string    = trim( str_replace( array( '<!--', '-->' ), '', $string ) );

		while ( ( $newstring = kses( $string, $allowed_html, $allowed_protocols ) ) != $string ) {
			$string = $newstring;
		}

		if ( '' === $string ) {
			return '';
		}

		// Compat for MSO comments
		if ( str_starts_with( $string, '[if' ) ) {
			$string = str_replace( ['&gt;', '&lt;'], ['>', '<'], $string );
		}

		// Prevent multiple dashes in comments.
		$string = preg_replace( '/--+/', '-', $string );
		// Prevent three dashes closing a comment.
		$string = preg_replace( '/-$/', '', $string );

		// Compat for MSO comments
		if ( str_starts_with( $string, '[if' ) && ! $is_closed ) {
			$string = "<!--{$string}";
		} else {
			$string = "<!--{$string}-->";
		}

		return $string;
	}

	// It's seriously malformed.
	if ( ! preg_match( '%^<\s*(/\s*)?([a-zA-Z0-9-]+)([^>]*)>?$%', $string, $matches ) ) {
		return '';
	}

	$slash    = trim( $matches[1] );
	$elem     = $matches[2];
	$attrlist = $matches[3];

	if ( ! is_array( $allowed_html ) ) {
		$allowed_html = wp_kses_allowed_html( $allowed_html );
	}

	// They are using a not allowed HTML element.
	if ( ! isset( $allowed_html[ strtolower( $elem ) ] ) ) {
		return '';
	}

	// MSO
	if ( str_starts_with( $attrlist, ':' ) ){

		$suffix = explode( ' ', $attrlist, 2 )[0];

		if ( '' !== $slash ) {
			return "</$elem$suffix>";
		}

		return str_replace( "<$elem", "<$elem$suffix", wp_kses_attr( $elem, $attrlist, $allowed_html, $allowed_protocols ) );
	}

	// No attributes are allowed for closing elements.
	if ( '' !== $slash ) {
		return "</$elem>";
	}

	return wp_kses_attr( $elem, $attrlist, $allowed_html, $allowed_protocols );
}