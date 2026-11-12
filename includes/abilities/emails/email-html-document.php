<?php

namespace Groundhogg\Abilities\Emails;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The content of an email that isn't built with the block editor, like one from the HTML editor, to find things in
 * and change a piece at a time, the way Email_Block_Document does for block emails.
 *
 * The content is changed as a string, at the positions found, and never parsed and written out again: a DOM parser
 * would rewrite the whole document (quoting, entities, Outlook's conditional comments) for a one word change.
 *
 * - replace_text() changes visible text only, never tags, attributes, comments or CSS.
 * - replace_html() changes an exact piece of the HTML, like a link's href or a table row.
 * - find() returns where something is, with some of the HTML around it, to copy an exact piece for replace_html().
 * - outline() is the visible text, with links and images, without the head, CSS and inline styles.
 */
class Email_Html_Document {

	/**
	 * Tags whose opening and closing tags are counted, so a change can't leave one open or close one too many
	 */
	const BALANCED_TAGS = [ 'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th', 'div', 'a', 'center' ];

	/**
	 * A tag, with quoted attribute values that may have > in them
	 */
	const TAG = '<[a-zA-Z\/!?](?:"[^"]*"|\'[^\']*\'|[^\'">])*>';

	/**
	 * How much of the HTML on each side of a match find() returns
	 */
	const CONTEXT = 80;

	/**
	 * @var string
	 */
	protected $html;

	/**
	 * @param string $html
	 */
	public function __construct( string $html ) {
		$this->html = $html;
	}

	/**
	 * @return string
	 */
	public function html(): string {
		return $this->html;
	}

	/**
	 * The content split into what can be markup and what's text, as [ start, length, is_text ] ranges. Comments
	 * (Outlook's conditional ones among them), tags, and whatever is in a <style>, <script> or <head> other than its
	 * <title> are markup.
	 *
	 * @return array[]
	 */
	protected function ranges(): array {

		$pattern = '/<!--.*?-->|<(style|script)\b[^>]*>.*?<\/\1\s*>|' . self::TAG . '/is';

		preg_match_all( $pattern, $this->html, $matches, PREG_OFFSET_CAPTURE );

		$ranges = [];
		$at     = 0;

		foreach ( $matches[0] as $match ) {

			[ $markup, $start ] = $match;

			if ( $start > $at ) {
				$ranges[] = [ $at, $start - $at, true ];
			}

			$ranges[] = [ $start, strlen( $markup ), false ];
			$at       = $start + strlen( $markup );
		}

		if ( $at < strlen( $this->html ) ) {
			$ranges[] = [ $at, strlen( $this->html ) - $at, true ];
		}

		// whitespace between the tags of the head isn't text, but the title is
		$head_end = $this->head_end();

		foreach ( $ranges as &$range ) {
			if ( $range[2] && $range[0] < $head_end && ! $this->in_title( $range[0] ) ) {
				$range[2] = false;
			}
		}

		return $ranges;
	}

	/**
	 * Where the head ends, or 0 if there's none
	 *
	 * @return int
	 */
	protected function head_end(): int {

		if ( ! preg_match( '/<\/head\s*>|<body\b/i', $this->html, $match, PREG_OFFSET_CAPTURE ) ) {
			return 0;
		}

		return $match[0][1];
	}

	/**
	 * Whether a position is inside the <title>
	 *
	 * @param int $position
	 *
	 * @return bool
	 */
	protected function in_title( int $position ): bool {

		if ( ! preg_match( '/<title\b[^>]*>(.*?)<\/title\s*>/is', $this->html, $match, PREG_OFFSET_CAPTURE ) ) {
			return false;
		}

		return $position >= $match[1][1] && $position < $match[1][1] + strlen( $match[1][0] );
	}

	/**
	 * The ways some text can be written in HTML: as it is, and with its special characters as entities
	 *
	 * @param string $find
	 * @param string $replace
	 *
	 * @return array[] [ find, replace ] pairs
	 */
	protected static function written_as( string $find, string $replace ): array {
		return array_values( array_unique( [
			[ $find, $replace ],
			[ htmlspecialchars( $find, ENT_NOQUOTES, 'UTF-8', false ), htmlspecialchars( $replace, ENT_NOQUOTES, 'UTF-8', false ) ],
			[ htmlspecialchars( $find, ENT_QUOTES, 'UTF-8', false ), htmlspecialchars( $replace, ENT_QUOTES, 'UTF-8', false ) ],
		], SORT_REGULAR ) );
	}

	/**
	 * Replace visible text
	 *
	 * Only the text between tags is searched, so a word that's also in a tag name, an attribute (like a URL) or the
	 * CSS isn't changed. Text written with entities is found too, like "Tom & Jerry" for "Tom &amp; Jerry", but text
	 * broken up by a tag (like a <strong> in the middle) isn't.
	 *
	 * @param mixed $find
	 * @param mixed $replace
	 * @param bool  $all replace every match, rather than needing there to be exactly one
	 *
	 * @return array|WP_Error count: how many were replaced, at: where the first one is now
	 */
	public function replace_text( $find, $replace, bool $all = false ) {

		if ( ! is_string( $find ) || $find === '' || ! is_string( $replace ) ) {
			return new WP_Error( 'groundhogg_invalid_find', __( '`find` must be a string that isn\'t empty, and `replace` a string.', 'groundhogg' ) );
		}

		$ranges = array_filter( $this->ranges(), function ( $range ) {
			return $range[2];
		} );

		foreach ( self::written_as( $find, $replace ) as [ $search, $with ] ) {

			$positions = [];

			foreach ( $ranges as [ $start, $length ] ) {

				$text   = substr( $this->html, $start, $length );
				$offset = 0;

				while ( ( $found = strpos( $text, $search, $offset ) ) !== false ) {
					$positions[] = $start + $found;
					$offset      = $found + strlen( $search );
				}
			}

			if ( ! empty( $positions ) ) {
				break;
			}
		}

		if ( empty( $positions ) ) {

			$hint = $this->find( $find )['count'] > 0
				? __( 'It is in the HTML, but not as visible text on its own (it may be in a tag, an attribute, the CSS or a comment, or broken up by a tag like <strong>). Use "replace_html" with the exact HTML instead.', 'groundhogg' )
				: __( 'It\'s matched against the text as written, so text written differently (like with a curly apostrophe) won\'t match. Look for it with groundhogg/get-email-template\'s `find`.', 'groundhogg' );

			return new WP_Error(
				'groundhogg_text_not_found',
				/* translators: 1: the text, 2: a hint */
				sprintf( __( '"%1$s" wasn\'t found. %2$s', 'groundhogg' ), $find, $hint )
			);
		}

		if ( count( $positions ) > 1 && ! $all ) {
			return new WP_Error(
				'groundhogg_text_not_unique',
				/* translators: 1: the text, 2: how many times */
				sprintf( __( '"%1$s" was found %2$d times. Make `find` longer so it\'s unique, or set `all` to replace every one.', 'groundhogg' ), $find, count( $positions ) )
			);
		}

		return $this->replace_at( $positions, strlen( $search ), $with );
	}

	/**
	 * Replace a piece of the HTML, exactly as it's written
	 *
	 * If it isn't found as written, it's looked for again with any whitespace matching any other whitespace, since a
	 * copy of a piece of HTML often has its line breaks or indents changed.
	 *
	 * @param mixed $find
	 * @param mixed $replace
	 * @param bool  $all replace every match, rather than needing there to be exactly one
	 *
	 * @return array|WP_Error count: how many were replaced, at: where the first one is now
	 */
	public function replace_html( $find, $replace, bool $all = false ) {

		if ( ! is_string( $find ) || trim( $find ) === '' || ! is_string( $replace ) ) {
			return new WP_Error( 'groundhogg_invalid_find', __( '`find` must be a string that isn\'t empty, and `replace` a string.', 'groundhogg' ) );
		}

		$matches = $this->matches_of_html( $find );

		if ( empty( $matches ) ) {
			return new WP_Error(
				'groundhogg_html_not_found',
				/* translators: %s: the HTML */
				sprintf( __( '"%s" wasn\'t found in the HTML. It has to match the HTML exactly as it\'s saved, which may differ from what was written, like in how its inline styles are spaced. Copy it from what groundhogg/get-email-template\'s `find` returns.', 'groundhogg' ), $find )
			);
		}

		if ( count( $matches ) > 1 && ! $all ) {
			return new WP_Error(
				'groundhogg_html_not_unique',
				/* translators: 1: the HTML, 2: how many times */
				sprintf( __( '"%1$s" was found %2$d times. Make `find` longer so it\'s unique, or set `all` to replace every one.', 'groundhogg' ), $find, count( $matches ) )
			);
		}

		$before = $this->tag_balance( $this->html );
		$html   = $this->html;

		// from the end, so the positions before each one stay right
		foreach ( array_reverse( $matches ) as [ $start, $length ] ) {
			$html = substr_replace( $html, $replace, $start, $length );
		}

		$problem = $this->balance_problem( $before, $this->tag_balance( $html ) );

		if ( $problem ) {
			return $problem;
		}

		$this->html = $html;

		return [
			'count' => count( $matches ),
			'at'    => $matches[0][0],
		];
	}

	/**
	 * Where a piece of HTML is, exactly as written or else with any whitespace matching any whitespace
	 *
	 * @param string $find
	 *
	 * @return array[] [ start, length ] of each match
	 */
	protected function matches_of_html( string $find ): array {

		$matches = [];
		$offset  = 0;

		while ( ( $found = strpos( $this->html, $find, $offset ) ) !== false ) {
			$matches[] = [ $found, strlen( $find ) ];
			$offset    = $found + strlen( $find );
		}

		if ( ! empty( $matches ) ) {
			return $matches;
		}

		// split at whitespace, and between tags, where one may have whitespace and the other none, like "<tr><td>"
		$words   = preg_split( '/\s+|(?<=>)(?=<)/', trim( $find ), - 1, PREG_SPLIT_NO_EMPTY );
		$pattern = '';

		foreach ( $words as $i => $word ) {

			if ( $i > 0 ) {
				$pattern .= substr( $words[ $i - 1 ], - 1 ) === '>' || $word[0] === '<' ? '\s*' : '\s+';
			}

			$pattern .= preg_quote( $word, '/' );
		}

		if ( ! preg_match_all( '/' . $pattern . '/s', $this->html, $found, PREG_OFFSET_CAPTURE ) ) {
			return [];
		}

		return array_map( function ( $match ) {
			return [ $match[1], strlen( $match[0] ) ];
		}, $found[0] );
	}

	/**
	 * Replace what's at each position, from the end so the positions before it stay right
	 *
	 * @param int[]  $positions
	 * @param int    $length
	 * @param string $with
	 *
	 * @return array|WP_Error
	 */
	protected function replace_at( array $positions, int $length, string $with ) {

		$before = $this->tag_balance( $this->html );
		$html   = $this->html;

		foreach ( array_reverse( $positions ) as $position ) {
			$html = substr_replace( $html, $with, $position, $length );
		}

		// the replacement can have tags in it
		$problem = $this->balance_problem( $before, $this->tag_balance( $html ) );

		if ( $problem ) {
			return $problem;
		}

		$this->html = $html;

		return [
			'count' => count( $positions ),
			'at'    => $positions[0],
		];
	}

	/**
	 * For each tag in BALANCED_TAGS, how many more are opened than closed, outside comments
	 *
	 * @param string $html
	 *
	 * @return int[]
	 */
	protected function tag_balance( string $html ): array {

		$html    = preg_replace( '/<!--.*?-->/s', '', $html );
		$balance = [];

		foreach ( self::BALANCED_TAGS as $tag ) {
			$balance[ $tag ] = preg_match_all( '/<' . $tag . '\b/i', $html ) - preg_match_all( '/<\/' . $tag . '\s*>/i', $html );
		}

		return $balance;
	}

	/**
	 * An error if a change opens a tag it doesn't close, or closes one it didn't open
	 *
	 * @param int[] $before
	 * @param int[] $after
	 *
	 * @return WP_Error|null
	 */
	protected function balance_problem( array $before, array $after ) {

		$off = [];

		foreach ( $before as $tag => $count ) {
			if ( $after[ $tag ] !== $count ) {
				$off[] = '<' . $tag . '>';
			}
		}

		if ( empty( $off ) ) {
			return null;
		}

		return new WP_Error(
			'groundhogg_unbalanced_html',
			/* translators: %s: tags */
			sprintf( __( 'The change would leave %s opened and closed a different number of times, which can break the layout. Replace whole elements, from their opening tag to their closing tag.', 'groundhogg' ), implode( ', ', $off ) )
		);
	}

	/**
	 * Where some text or HTML is, with some of the HTML around it
	 *
	 * The search ignores case, and text is also looked for as it's written with entities, so "Tom & Jerry" finds
	 * "Tom &amp; Jerry".
	 *
	 * @param string $query
	 * @param int    $limit the most matches to return
	 *
	 * @return array count: how many there are, matches: up to $limit of them, each with the HTML around it as
	 *               `before`, `match` and `after`, and its `position` in the content
	 */
	public function find( string $query, int $limit = 10 ): array {

		if ( $query === '' ) {
			return [ 'count' => 0, 'matches' => [] ];
		}

		$found = [];

		foreach ( self::written_as( $query, '' ) as [ $search ] ) {

			$offset = 0;

			while ( ( $at = stripos( $this->html, $search, $offset ) ) !== false ) {
				$found[ $at ] = strlen( $search );
				$offset       = $at + strlen( $search );
			}
		}

		ksort( $found );

		$matches = [];

		foreach ( array_slice( $found, 0, $limit, true ) as $at => $length ) {

			$from = max( 0, $at - self::CONTEXT );

			$matches[] = [
				'before'   => $this->slice( $from, $at - $from ),
				'match'    => substr( $this->html, $at, $length ),
				'after'    => $this->slice( $at + $length, self::CONTEXT ),
				'position' => $at,
			];
		}

		return [
			'count'   => count( $found ),
			'matches' => $matches,
		];
	}

	/**
	 * Some of the HTML around a position, like find() returns, to show where a change was made
	 *
	 * @param int $at
	 * @param int $length
	 *
	 * @return string
	 */
	public function excerpt( int $at, int $length = 0 ): string {

		$from = max( 0, $at - self::CONTEXT / 2 );

		return $this->slice( $from, $at + $length + self::CONTEXT - $from );
	}

	/**
	 * Part of the HTML by bytes, without a character cut in two at either end
	 *
	 * @param int $from
	 * @param int $length
	 *
	 * @return string
	 */
	protected function slice( int $from, int $length ): string {

		$piece = preg_replace( '/^[\x80-\xBF]+/', '', (string) substr( $this->html, $from, $length ) );

		if ( preg_match( '/[\xC0-\xFF][\x80-\xBF]*$/', $piece, $tail ) && ! self::complete( $tail[0] ) ) {
			$piece = substr( $piece, 0, - strlen( $tail[0] ) );
		}

		return $piece;
	}

	/**
	 * Whether the bytes are a whole UTF-8 character
	 *
	 * @param string $bytes
	 *
	 * @return bool
	 */
	protected static function complete( string $bytes ): bool {

		$first = ord( $bytes[0] );
		$need  = $first >= 0xF0 ? 4 : ( $first >= 0xE0 ? 3 : 2 );

		return strlen( $bytes ) === $need;
	}

	/**
	 * The visible text of the email, a line for each paragraph, heading, list item or cell, with links as
	 * [text](href) and images as ![alt](file name). Without the head, CSS, comments and attributes, so it's a small
	 * part of the HTML.
	 *
	 * @return string
	 */
	public function outline(): string {

		$html = substr( $this->html, $this->head_end() );
		$html = preg_replace( '/<!--.*?-->|<(style|script|head)\b[^>]*>.*?<\/\1\s*>/is', '', $html );

		$out   = '';
		$hrefs = [];

		preg_match_all( '/(' . self::TAG . ')|([^<]+)/s', $html, $tokens, PREG_SET_ORDER );

		foreach ( $tokens as $token ) {

			if ( empty( $token[1] ) ) {
				$out .= preg_replace( '/\s+/u', ' ', self::decode( $token[0] ) );
				continue;
			}

			if ( ! preg_match( '/^<(\/?)([a-zA-Z0-9]+)/', $token[1], $tag ) ) {
				continue;
			}

			$closing = $tag[1] === '/';
			$name    = strtolower( $tag[2] );

			if ( $name === 'a' ) {

				if ( $closing ) {
					$href = array_pop( $hrefs );
					$out  .= $href ? '](' . $href . ')' : '';
				} else {
					$href    = self::attribute( $token[1], 'href' );
					$hrefs[] = $href;
					$out     .= $href ? '[' : '';
				}

				continue;
			}

			if ( $name === 'img' ) {
				$src = self::attribute( $token[1], 'src' );
				$out .= '![' . self::attribute( $token[1], 'alt' ) . '](' . ( $src ? wp_basename( (string) wp_parse_url( $src, PHP_URL_PATH ) ) : '' ) . ')';
				continue;
			}

			if ( preg_match( '/^h([1-6])$/', $name, $level ) ) {
				$out .= "\n" . ( $closing ? '' : str_repeat( '#', (int) $level[1] ) . ' ' );
				continue;
			}

			if ( $name === 'li' && ! $closing ) {
				$out .= "\n- ";
				continue;
			}

			if ( in_array( $name, [ 'p', 'div', 'br', 'tr', 'td', 'th', 'table', 'ul', 'ol', 'li', 'blockquote', 'center', 'hr' ], true ) ) {
				$out .= "\n";
			}
		}

		$lines = array_map( 'trim', explode( "\n", $out ) );

		// lines with only markdown left, like an empty link or a heading's #
		$lines = array_filter( $lines, function ( $line ) {
			return $line !== '' && ! preg_match( '/^(#+|-|\[\]\([^)]*\))$/', $line );
		} );

		return implode( "\n", $lines );
	}

	/**
	 * The value of an attribute of a tag
	 *
	 * @param string $tag
	 * @param string $name
	 *
	 * @return string
	 */
	protected static function attribute( string $tag, string $name ): string {

		if ( ! preg_match( '/\s' . $name . '\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $tag, $match ) ) {
			return '';
		}

		// whichever way it was quoted
		$value = '';

		foreach ( [ 1, 2, 3 ] as $group ) {
			if ( isset( $match[ $group ] ) && $match[ $group ] !== '' ) {
				$value = $match[ $group ];
				break;
			}
		}

		return self::decode( $value );
	}

	/**
	 * Decode HTML entities, including HTML5 ones like &apos;
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	protected static function decode( string $text ): string {
		return html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}
}
