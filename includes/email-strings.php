<?php

namespace Groundhogg;

use Groundhogg\Abilities\Emails\Email_Block_Document;
use Groundhogg\Abilities\Emails\Email_Block_Renderer;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The text of an email as a list of strings, for translating it: its subject, preview text, and each piece of text in
 * it, by a key that stays the same in a variation copied from the email (see Email_Variations).
 *
 * A piece of text is what's between block-level tags, like a paragraph, a list item or a button's label. The inline
 * tags in it (bold, links...) are numbered placeholders, `<1>bold</1>` or `<2/>` for a line break, so whoever
 * translates it sees no styles or URLs and can't break them: writing it back puts the original tags where the
 * placeholders are. HTML entities are decoded, and encoded again when it's written back.
 *
 * Keys:
 * - subject, preview
 * - in an email built with blocks, the block's id for its first piece of text, then "{id}.2", "{id}.3"..., and
 *   "{id}.alt" for an image's or video's alt text. Text, heading, button, menu and HTML blocks are read. The footer is
 *   made again in the contact's language when the email is sent (see Block_Registry::footer()). Global and dynamic
 *   blocks aren't the email's own text, see not_translated().
 * - in an HTML email, "text.1", "text.2"... in the order they're in the body
 *
 * A variation remembers what the email said for each string when it was translated (a hash of it), so it can tell
 * which of its strings are still in the email's language, and which the email has changed since, see compare().
 */
class Email_Strings {

	/**
	 * Tags that are part of a piece of text, not the end of one
	 */
	const INLINE_TAGS = [ 'a', 'abbr', 'b', 'bdi', 'bdo', 'br', 'cite', 'code', 'del', 'dfn', 'em', 'font', 'i', 'img', 'ins', 'kbd', 'mark', 'q', 's', 'samp', 'small', 'span', 'strike', 'strong', 'sub', 'sup', 'u', 'var', 'wbr' ];

	/**
	 * Tags with no end tag
	 */
	const VOID_TAGS = [ 'br', 'img', 'wbr' ];

	/**
	 * Tags whose content isn't text to translate
	 */
	const SKIP_TAGS = [ 'style', 'script', 'head', 'title', 'noscript' ];

	/**
	 * The blocks that have text
	 */
	const TEXT_BLOCKS = [ 'text', 'heading', 'button', 'menu', 'html' ];

	/**
	 * The blocks whose image has alt text
	 */
	const ALT_BLOCKS = [ 'image', 'video' ];

	/**
	 * Where a variation keeps the hashes of what the email said when each string was translated
	 */
	const SOURCE_KEY = 'variation_sources';

	public function __construct() {
		add_action( 'groundhogg/api/email/updated', [ $this, 'remember_hand_translations' ] );
	}

	/**
	 * Whether the email's text can be read as strings
	 *
	 * @param Email $email
	 *
	 * @return bool
	 */
	public static function supports( Email $email ): bool {
		return in_array( $email->get_editor_type(), [ 'blocks', 'html' ], true );
	}

	/**
	 * The email's strings, in the order they're in it
	 *
	 * @param Email $email
	 *
	 * @return string[]|WP_Error key => text
	 */
	public static function get( Email $email ) {

		$found = self::collect( $email );

		if ( is_wp_error( $found ) ) {
			return $found;
		}

		return array_map( function ( $string ) {
			return $string['text'];
		}, $found['strings'] );
	}

	/**
	 * The blocks in an email whose text isn't read as strings, so translating the strings doesn't translate them: a
	 * global block, which is the same in every email it's in, and dynamic blocks, whose content comes from the site
	 * when the email is sent, like posts or a shortcode. So whoever translates it can say what's left to check by hand.
	 *
	 * @param Email $email
	 *
	 * @return array[] each with id, type, and note: what to do about it
	 */
	public static function not_translated( Email $email ): array {

		if ( $email->get_editor_type() !== 'blocks' ) {
			return [];
		}

		$doc = new Email_Block_Document( (string) $email->get_content() );

		if ( ! $doc->is_valid() ) {
			return [];
		}

		$list = [];

		foreach ( $doc->ids() as $id ) {

			$type = (string) $doc->get( $id )['type'];

			switch ( $type ) {
				case 'global':
					$note = __( 'A global block is the same in every email it\'s in. Swap it for a global block written in this language.', 'groundhogg' );
					break;
				case 'posts':
				case 'queryloop':
					$note = __( 'Shows posts from the site when the email is sent, in the language they\'re written in.', 'groundhogg' );
					break;
				case 'footer':
					// made again in the contact's language when it's sent, see Block_Registry::footer()
					continue 2;
				default:
					// a dynamic block, like a shortcode or one from an add-on
					if ( ! Block_Registry::instance()->is_registered( $type ) ) {
						continue 2;
					}

					$note = __( 'Its content comes from the site when the email is sent, so it isn\'t translated with the text.', 'groundhogg' );
					break;
			}

			$list[] = [
				'id'   => $id,
				'type' => $type,
				'note' => $note,
			];
		}

		/**
		 * The blocks in an email whose text isn't translated with its strings
		 *
		 * @param array[] $list  each with id, type and note
		 * @param Email   $email
		 */
		return apply_filters( 'groundhogg/email_strings/not_translated', $list, $email );
	}

	/**
	 * Every string, with where it is
	 *
	 * @param Email $email
	 *
	 * @return array|WP_Error [ strings => key => [ text, block?, unit?, alt? ], doc? ]
	 */
	protected static function collect( Email $email ) {

		if ( ! self::supports( $email ) ) {
			return new WP_Error( 'email_strings_not_supported', __( 'Only emails built with the block editor or the HTML editor can be read as strings.', 'groundhogg' ) );
		}

		$strings = [];

		foreach ( [ 'subject' => $email->get_subject_line(), 'preview' => $email->get_pre_header() ] as $key => $text ) {

			$text = self::collapse( html_entity_decode( (string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );

			if ( $text !== '' ) {
				$strings[ $key ] = [ 'text' => $text ];
			}
		}

		$content = (string) $email->get_content();

		if ( $email->get_editor_type() === 'html' ) {

			[ $start, $end ] = self::body_range( $content );

			foreach ( self::units_in( substr( $content, $start, $end - $start ) ) as $i => $unit ) {
				$unit['start'] += $start;
				$unit['end']   += $start;

				$strings[ 'text.' . ( $i + 1 ) ] = [
					'text' => $unit['text'],
					'unit' => $unit,
				];
			}

			return [ 'strings' => $strings ];
		}

		$doc = new Email_Block_Document( $content );

		if ( ! $doc->is_valid() ) {
			return Email_Block_Document::error_from( $doc->errors(), __( 'The email\'s content has problems that have to be fixed first:', 'groundhogg' ) );
		}

		foreach ( $doc->ids() as $id ) {

			$block = $doc->get( $id );
			$inner = $doc->inner_html( $id );

			if ( in_array( $block['type'], self::ALT_BLOCKS, true ) ) {

				$alt = self::alt_of( $inner );

				if ( $alt !== null && self::is_words( $alt ) ) {
					$strings[ $id . '.alt' ] = [
						'text'  => $alt,
						'block' => $id,
						'alt'   => true,
					];
				}

				continue;
			}

			if ( ! in_array( $block['type'], self::TEXT_BLOCKS, true ) ) {
				continue;
			}

			foreach ( self::units_in( $inner ) as $i => $unit ) {
				$strings[ $i === 0 ? $id : $id . '.' . ( $i + 1 ) ] = [
					'text'  => $unit['text'],
					'block' => $id,
					'unit'  => $unit,
				];
			}
		}

		return [ 'strings' => $strings, 'doc' => $doc ];
	}

	/**
	 * Change some of the email's strings, all or nothing: unknown keys and translations that lost or added a
	 * placeholder are errors and nothing is saved, unless $skip_invalid
	 *
	 * @param Email $email
	 * @param array $strings      key => text
	 * @param bool  $skip_invalid leave out the strings that can't be written instead of failing
	 *
	 * @return array|WP_Error [ changed => keys, skipped => key => error message ]
	 */
	public static function set( Email $email, array $strings, bool $skip_invalid = false ) {

		$found = self::collect( $email );

		if ( is_wp_error( $found ) ) {
			return $found;
		}

		$current   = $found['strings'];
		$errors    = [];
		$writes    = [];
		$confirmed = [];

		foreach ( $strings as $key => $text ) {

			$key = (string) $key;

			if ( ! isset( $current[ $key ] ) ) {
				/* translators: %s: a key of a string in the email */
				$errors[ $key ] = sprintf( __( 'The email has no string "%s".', 'groundhogg' ), $key );
				continue;
			}

			if ( ! is_string( $text ) ) {
				$errors[ $key ] = __( 'The text has to be a string.', 'groundhogg' );
				continue;
			}

			$text = self::collapse( $text );

			if ( $text === '' ) {
				$errors[ $key ] = __( 'The text can\'t be empty.', 'groundhogg' );
				continue;
			}

			// the same in both languages, like a name
			if ( $text === $current[ $key ]['text'] ) {
				$confirmed[] = $key;
				continue;
			}

			if ( isset( $current[ $key ]['unit'] ) ) {

				$html = self::build( $text, $current[ $key ]['unit']['tags'] );

				if ( is_wp_error( $html ) ) {
					$errors[ $key ] = $html->get_error_message();
					continue;
				}

				$writes[ $key ] = $html;
				continue;
			}

			$writes[ $key ] = $text;
		}

		if ( ! empty( $errors ) && ! $skip_invalid ) {
			$lines = [];
			foreach ( $errors as $key => $error ) {
				$lines[] = $key . ': ' . $error;
			}

			return new WP_Error( 'email_strings_invalid', __( 'Nothing was changed:', 'groundhogg' ) . "\n" . implode( "\n", $lines ), [ 'errors' => $errors ] );
		}

		if ( empty( $writes ) ) {

			// strings confirmed as the same in both languages, like a name
			if ( $email->is_variation() ) {
				self::remember_sources( $email, $confirmed );
			}

			return [ 'changed' => [], 'skipped' => $errors ];
		}

		$data = [];

		if ( isset( $writes['subject'] ) ) {
			$data['subject'] = $writes['subject'];
		}

		if ( isset( $writes['preview'] ) ) {
			$data['pre_header'] = $writes['preview'];
		}

		$content_writes = array_diff_key( $writes, array_flip( [ 'subject', 'preview' ] ) );

		if ( ! empty( $content_writes ) ) {

			$content = isset( $found['doc'] )
				? self::write_blocks( $found['doc'], $current, $content_writes )
				: self::write_units( (string) $email->get_content(), $current, $content_writes );

			if ( is_wp_error( $content ) ) {
				return $content;
			}

			if ( isset( $found['doc'] ) ) {

				// like the editor, which makes them again whenever it saves
				$made = ( new Email_Block_Renderer( Email_Block_Renderer::context_of( $email ) ) )->css_and_plain_text( $content );

				$data['content']    = $content;
				$data['plain_text'] = $made['plain_text'];

				// a fresh copy, see Email_Variations::fresh()
				( new Email( $email->get_id() ) )->update_meta( 'css', $made['css'] );
			} else {
				$data['content']    = email_kses( $content );
				$data['plain_text'] = '';
			}
		}

		$data['last_updated'] = current_time( 'mysql' );

		$email->update( $data );

		// what a variation's strings are translated from, to tell when the email changes. Only once they're written,
		// so a failed write doesn't mark them translated.
		if ( $email->is_variation() ) {
			self::remember_sources( $email, array_merge( array_keys( $writes ), $confirmed ) );
		}

		return [
			'changed' => array_keys( $writes ),
			'skipped' => $errors,
		];
	}

	/**
	 * Write strings into the blocks they're in
	 *
	 * @param Email_Block_Document $doc
	 * @param array                $current from collect()
	 * @param array                $writes  key => HTML for text, text for alt text
	 *
	 * @return string|WP_Error the content
	 */
	protected static function write_blocks( Email_Block_Document $doc, array $current, array $writes ) {

		$by_block = [];

		foreach ( $writes as $key => $value ) {
			$by_block[ $current[ $key ]['block'] ][ $key ] = $value;
		}

		foreach ( $by_block as $id => $block_writes ) {

			$inner = $doc->inner_html( $id );

			$units = [];

			foreach ( $block_writes as $key => $value ) {

				if ( ! empty( $current[ $key ]['alt'] ) ) {
					$inner = self::with_alt( $inner, $value );
					continue;
				}

				$units[] = [ $current[ $key ]['unit'], $value ];
			}

			$inner = self::splice_units( $inner, $units );

			$result = $doc->set_content( $id, $inner );

			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		$doc->normalize();

		$sanitized = $doc->sanitize();

		if ( is_wp_error( $sanitized ) ) {
			return $sanitized;
		}

		return $doc->html();
	}

	/**
	 * Write strings into an HTML email
	 *
	 * @param string $content
	 * @param array  $current from collect()
	 * @param array  $writes  key => HTML
	 *
	 * @return string
	 */
	protected static function write_units( string $content, array $current, array $writes ): string {

		$units = [];

		foreach ( $writes as $key => $html ) {
			$units[] = [ $current[ $key ]['unit'], $html ];
		}

		return self::splice_units( $content, $units );
	}

	/**
	 * Put new HTML where some units are, last first so the positions of the others stay right
	 *
	 * @param string $html
	 * @param array  $units [ unit, new HTML ]
	 *
	 * @return string
	 */
	protected static function splice_units( string $html, array $units ): string {

		usort( $units, function ( $a, $b ) {
			return $b[0]['start'] <=> $a[0]['start'];
		} );

		foreach ( $units as [ $unit, $new ] ) {
			$html = substr_replace( $html, $new, $unit['start'], $unit['end'] - $unit['start'] );
		}

		return $html;
	}

	/**
	 * Where the body of an HTML email is, so its head isn't read
	 *
	 * @param string $content
	 *
	 * @return int[] start, end
	 */
	protected static function body_range( string $content ): array {

		$start = 0;
		$end   = strlen( $content );

		if ( preg_match( '/<body\b[^>]*>/i', $content, $match, PREG_OFFSET_CAPTURE ) ) {
			$start = $match[0][1] + strlen( $match[0][0] );
		}

		if ( preg_match( '/<\/body\s*>/i', $content, $match, PREG_OFFSET_CAPTURE, $start ) ) {
			$end = $match[0][1];
		}

		return [ $start, $end ];
	}

	/**
	 * The pieces of text in some HTML
	 *
	 * @param string $html
	 *
	 * @return array[] each with start and end (where it is in $html), text (with placeholders), and tags (number =>
	 *                 the tags the placeholder stands for: open and close, or void)
	 */
	public static function units_in( string $html ): array {

		preg_match_all( '/<!--.*?-->|<(\/?)([a-zA-Z][a-zA-Z0-9]*)\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*>|[^<]+|</s', $html, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE );

		$units = [];
		$run   = [];
		$skip  = null;
		$depth = 0;

		foreach ( $matches as $match ) {

			$raw   = $match[0][0];
			$start = $match[0][1];
			$token = [
				'raw'   => $raw,
				'start' => $start,
				'end'   => $start + strlen( $raw ),
			];

			$name = isset( $match[2] ) && $match[2][1] !== - 1 ? strtolower( $match[2][0] ) : '';

			if ( $name === '' ) {
				$token['type'] = str_starts_with( $raw, '<!--' ) ? 'comment' : 'text';
			} else if ( $match[1][0] === '/' ) {
				$token['type'] = 'close';
			} else {
				$token['type'] = in_array( $name, self::VOID_TAGS, true ) || str_ends_with( $raw, '/>' ) ? 'void' : 'open';
			}

			$token['name'] = $name;

			// inside a tag whose content isn't text, until it ends
			if ( $skip !== null ) {
				if ( $token['name'] === $skip ) {
					$depth += $token['type'] === 'open' ? 1 : ( $token['type'] === 'close' ? - 1 : 0 );
					if ( $depth === 0 ) {
						$skip = null;
					}
				}
				continue;
			}

			if ( $token['type'] === 'open' && in_array( $name, self::SKIP_TAGS, true ) ) {
				self::flush( $run, $units );
				$skip  = $name;
				$depth = 1;
				continue;
			}

			$inline = $token['type'] === 'text' || in_array( $name, self::INLINE_TAGS, true );

			if ( ! $inline ) {
				self::flush( $run, $units );
				continue;
			}

			$run[] = $token;
		}

		self::flush( $run, $units );

		return $units;
	}

	/**
	 * Make a unit of the tokens in a run of text and inline tags, if there's text in it, and start a new run
	 *
	 * @param array $run
	 * @param array $units
	 *
	 * @return void
	 */
	protected static function flush( array &$run, array &$units ) {

		$tokens = $run;
		$run    = [];

		// what's around the text stays where it is: whitespace, line breaks, and tags around all of it
		$changed = true;

		while ( $changed && ! empty( $tokens ) ) {

			$changed = false;
			$first   = $tokens[0];
			$last    = end( $tokens );

			if ( ( $first['type'] === 'text' && trim( $first['raw'] ) === '' ) || $first['type'] === 'void' ) {
				array_shift( $tokens );
				$changed = true;
				continue;
			}

			if ( ( $last['type'] === 'text' && trim( $last['raw'] ) === '' ) || $last['type'] === 'void' ) {
				array_pop( $tokens );
				$changed = true;
				continue;
			}

			if ( count( $tokens ) >= 2 && $first['type'] === 'open' && $last['type'] === 'close' && $first['name'] === $last['name'] && self::closes_at( $tokens, 0 ) === count( $tokens ) - 1 ) {
				array_shift( $tokens );
				array_pop( $tokens );
				$changed = true;
			}
		}

		if ( empty( $tokens ) ) {
			return;
		}

		$text  = '';
		$tags  = [];
		$stack = [];
		$n     = 0;

		foreach ( $tokens as $token ) {

			switch ( $token['type'] ) {
				case 'text':
					$text .= html_entity_decode( $token['raw'], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
					break;
				case 'void':
					$n ++;
					$tags[ $n ] = [ 'void' => $token['raw'] ];
					$text       .= "<$n/>";
					break;
				case 'open':
					$n ++;
					$tags[ $n ] = [ 'open' => $token['raw'] ];
					$stack[]    = [ $token['name'], $n ];
					$text       .= "<$n>";
					break;
				case 'close':
					$opened = null;
					for ( $i = count( $stack ) - 1; $i >= 0; $i -- ) {
						if ( $stack[ $i ][0] === $token['name'] ) {
							$opened = $stack[ $i ][1];
							array_splice( $stack, $i, 1 );
							break;
						}
					}

					if ( $opened === null ) {
						$n ++;
						$opened = $n;
					}

					$tags[ $opened ]['close'] = $token['raw'];
					$text                     .= "</$opened>";
					break;
			}
		}

		$text = self::collapse( $text );

		if ( ! self::is_words( preg_replace( '/<\/?\d+\/?>/', '', $text ) ) ) {
			return;
		}

		$units[] = [
			'start' => $tokens[0]['start'],
			'end'   => end( $tokens )['end'],
			'text'  => $text,
			'tags'  => $tags,
		];
	}

	/**
	 * Where the tag opened at $index is closed in the tokens
	 *
	 * @param array $tokens
	 * @param int   $index
	 *
	 * @return int|null
	 */
	protected static function closes_at( array $tokens, int $index ) {

		$name  = $tokens[ $index ]['name'];
		$depth = 0;

		for ( $i = $index; $i < count( $tokens ); $i ++ ) {

			if ( $tokens[ $i ]['name'] !== $name ) {
				continue;
			}

			if ( $tokens[ $i ]['type'] === 'open' ) {
				$depth ++;
			} else if ( $tokens[ $i ]['type'] === 'close' ) {
				$depth --;
				if ( $depth === 0 ) {
					return $i;
				}
			}
		}

		return null;
	}

	/**
	 * The placeholders a unit's text has to keep
	 *
	 * @param array $tags
	 *
	 * @return string[] sorted
	 */
	protected static function placeholders( array $tags ): array {

		$placeholders = [];

		foreach ( $tags as $n => $tag ) {
			if ( isset( $tag['void'] ) ) {
				$placeholders[] = "<$n/>";
			}
			if ( isset( $tag['open'] ) ) {
				$placeholders[] = "<$n>";
			}
			if ( isset( $tag['close'] ) ) {
				$placeholders[] = "</$n>";
			}
		}

		sort( $placeholders );

		return $placeholders;
	}

	/**
	 * The HTML for a unit's new text, with its tags back where the placeholders are
	 *
	 * @param string $text
	 * @param array  $tags
	 *
	 * @return string|WP_Error
	 */
	public static function build( string $text, array $tags ) {

		preg_match_all( '/<\/?\d+\/?>/', $text, $found );

		$given = $found[0];
		sort( $given );

		$expected = self::placeholders( $tags );

		if ( $given !== $expected ) {
			return new WP_Error( 'email_strings_placeholders', empty( $expected )
				? __( 'The text has placeholders like <1> that the original doesn\'t have. Leave them out.', 'groundhogg' )
				/* translators: %s: the placeholders the text has to keep, like <1></1> */
				: sprintf( __( 'Keep each placeholder exactly once, around the words they belong to: %s', 'groundhogg' ), implode( ' ', $expected ) ) );
		}

		$pieces = preg_split( '/(<\/?\d+\/?>)/', $text, - 1, PREG_SPLIT_DELIM_CAPTURE );
		$html   = '';

		foreach ( $pieces as $piece ) {

			if ( preg_match( '/^<(\/?)(\d+)(\/?)>$/', $piece, $placeholder ) ) {

				$tag = $tags[ (int) $placeholder[2] ];

				if ( $placeholder[3] === '/' ) {
					$html .= $tag['void'];
				} else if ( $placeholder[1] === '/' ) {
					$html .= $tag['close'];
				} else {
					$html .= $tag['open'];
				}

				continue;
			}

			$html .= self::encode( $piece );
		}

		return $html;
	}

	/**
	 * Text as HTML: entities decoded first, so text that has some isn't encoded twice
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	protected static function encode( string $text ): string {
		return htmlspecialchars( html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' ), ENT_NOQUOTES, 'UTF-8' );
	}

	/**
	 * Whitespace on one line, the way it's shown
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	protected static function collapse( string $text ): string {
		return trim( preg_replace( '/[\s\x{00A0}]+/u', ' ', $text ) );
	}

	/**
	 * Whether some text has words in it, not just punctuation like a menu's separators, or numbers
	 *
	 * @param string $text
	 *
	 * @return bool
	 */
	protected static function is_words( string $text ): bool {
		return (bool) preg_match( '/\p{L}/u', $text );
	}

	/**
	 * The alt text of the first image
	 *
	 * @param string $html
	 *
	 * @return string|null
	 */
	protected static function alt_of( string $html ) {

		if ( ! preg_match( '/<img\b[^>]*?\salt\s*=\s*(["\'])(.*?)\1/is', $html, $match ) ) {
			return null;
		}

		return self::collapse( html_entity_decode( $match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	}

	/**
	 * The HTML with new alt text on its first image
	 *
	 * @param string $html
	 * @param string $alt
	 *
	 * @return string
	 */
	protected static function with_alt( string $html, string $alt ): string {
		return preg_replace_callback( '/(<img\b[^>]*?\salt\s*=\s*)(["\'])(.*?)\2/is', function ( $match ) use ( $alt ) {
			return $match[1] . '"' . esc_attr( html_entity_decode( $alt, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) . '"';
		}, $html, 1 );
	}

	/**
	 * A hash of a string, to tell later whether it changed
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	protected static function hash( string $text ): string {
		return substr( md5( $text ), 0, 12 );
	}

	/**
	 * Remember what the email said for some of a variation's strings, as what they were translated from
	 *
	 * @param Email $variation
	 * @param array $keys      the strings that were translated
	 * @param bool  $overwrite false to only remember strings that have no source yet
	 *
	 * @return void
	 */
	public static function remember_sources( Email $variation, array $keys, bool $overwrite = true ) {

		$parent = $variation->get_parent();

		if ( ! $parent || ! $parent->exists() ) {
			return;
		}

		$source = self::get( $parent );

		if ( is_wp_error( $source ) ) {
			return;
		}

		// a fresh copy, see Email_Variations::fresh()
		$variation = new Email( $variation->get_id() );
		$hashes    = self::source_hashes( $variation );

		foreach ( $keys as $key ) {
			if ( isset( $source[ $key ] ) && ( $overwrite || ! isset( $hashes[ $key ] ) ) ) {
				$hashes[ $key ] = self::hash( $source[ $key ] );
			}
		}

		$variation->update_meta( self::SOURCE_KEY, $hashes );
	}

	/**
	 * @param Email $variation
	 *
	 * @return string[] key => hash
	 */
	protected static function source_hashes( Email $variation ): array {
		$hashes = $variation->get_meta( self::SOURCE_KEY );

		return is_array( $hashes ) ? array_map( 'strval', $hashes ) : [];
	}

	/**
	 * When a variation is changed in the editor, the strings someone translated by hand are remembered as translated
	 * from what the email says now. Strings already remembered aren't, so a change to the email still shows.
	 *
	 * @param Email $email
	 *
	 * @return void
	 */
	public function remember_hand_translations( $email ) {

		if ( ! is_a( $email, Email::class ) || ! $email->is_variation() || ! self::supports( $email ) ) {
			return;
		}

		$compared = self::compare( $email );

		if ( is_wp_error( $compared ) ) {
			return;
		}

		$keys = [];

		foreach ( $compared['strings'] as $string ) {
			if ( $string['status'] === 'translated' && empty( $string['remembered'] ) ) {
				$keys[] = $string['key'];
			}
		}

		if ( ! empty( $keys ) ) {
			self::remember_sources( $email, $keys, false );
		}
	}

	/**
	 * A variation's strings next to the email's, and whether each one is translated
	 *
	 * Status of each string:
	 * - untranslated: it's the same as the email's, and wasn't translated to be
	 * - changed: the email's string changed since it was translated
	 * - translated
	 * - not_in_email: the email doesn't have it any more
	 *
	 * @param Email $variation
	 *
	 * @return array|WP_Error [ strings => [ key, text, source, status ], missing => [ key, source ] ]
	 */
	public static function compare( Email $variation ) {

		$parent = $variation->get_parent();

		if ( ! $parent || ! $parent->exists() ) {
			return new WP_Error( 'not_a_variation', __( 'This email is not a variation.', 'groundhogg' ) );
		}

		$strings = self::get( $variation );

		if ( is_wp_error( $strings ) ) {
			return $strings;
		}

		$source = self::get( $parent );

		if ( is_wp_error( $source ) ) {
			$source = [];
		}

		$hashes = self::source_hashes( $variation );
		$list   = [];

		foreach ( $strings as $key => $text ) {

			$key      = (string) $key;
			$original = $source[ $key ] ?? null;
			$hash     = $hashes[ $key ] ?? null;

			if ( $original === null ) {
				$status = 'not_in_email';
			} else if ( $hash !== null && $hash !== self::hash( $original ) ) {
				$status = 'changed';
			} else if ( $text === $original && $hash === null ) {
				$status = 'untranslated';
			} else {
				$status = 'translated';
			}

			$entry = [
				'key'    => $key,
				'text'   => $text,
				'status' => $status,
			];

			if ( $original !== null && $original !== $text ) {
				$entry['source'] = $original;
			}

			if ( $hash !== null ) {
				$entry['remembered'] = true;
			}

			$list[] = $entry;
		}

		$missing = [];

		foreach ( array_diff_key( $source, $strings ) as $key => $text ) {
			$missing[] = [
				'key'    => (string) $key,
				'source' => $text,
			];
		}

		return [
			'strings' => $list,
			'missing' => $missing,
		];
	}

	/**
	 * How many of a variation's strings need translating, for showing next to it
	 *
	 * @param Email $variation
	 *
	 * @return int|null null when it can't tell
	 */
	public static function count_to_translate( Email $variation ) {

		$compared = self::compare( $variation );

		if ( is_wp_error( $compared ) ) {
			return null;
		}

		return count( array_filter( $compared['strings'], function ( $string ) {
			return in_array( $string['status'], [ 'untranslated', 'changed' ], true );
		} ) ) + count( $compared['missing'] );
	}
}
