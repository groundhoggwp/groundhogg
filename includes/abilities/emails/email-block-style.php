<?php

namespace Groundhogg\Abilities\Emails;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * An element's inline style, changed the way the block editor changes it in the browser.
 *
 * The editor (email-block-editor.js) builds block markup with DOM elements and sets their styles one property at a
 * time (`el.style.padding = '5px 5px 5px 5px'`), and the browser then writes the style attribute: shorthands like
 * padding are folded back together, colors become rgb(), invalid values are ignored, and setting '' removes a
 * property. When the email is saved, email_kses() turns the rgb() colors back into hex.
 *
 * Email_Block_Renderer uses this to write the same style attributes the editor would, so that an email made from
 * block specs opens and saves in the editor without changes. It also reads styles back like
 * CSSStyleDeclaration.getPropertyValue(), for parsing blocks out of saved content.
 *
 * Values are kept in the form email_kses() leaves them in: hex colors are lowercase six-digit, font families aren't
 * quoted.
 */
class Email_Block_Style {

	/**
	 * Shorthands made of a value for each side, and their longhands in the order top, right, bottom, left
	 */
	const SIDES = [
		'margin'        => [ 'margin-top', 'margin-right', 'margin-bottom', 'margin-left' ],
		'padding'       => [ 'padding-top', 'padding-right', 'padding-bottom', 'padding-left' ],
		'border-width'  => [ 'border-top-width', 'border-right-width', 'border-bottom-width', 'border-left-width' ],
		'border-style'  => [ 'border-top-style', 'border-right-style', 'border-bottom-style', 'border-left-style' ],
		'border-color'  => [ 'border-top-color', 'border-right-color', 'border-bottom-color', 'border-left-color' ],
		'border-radius' => [ 'border-top-left-radius', 'border-top-right-radius', 'border-bottom-right-radius', 'border-bottom-left-radius' ],
	];

	/**
	 * Properties that take a plain number, any other property needs a unit for a number that isn't 0
	 */
	const UNITLESS = [ 'line-height', 'font-weight', 'opacity', 'z-index', 'flex-grow', 'flex-shrink', 'order' ];

	/**
	 * Properties that are lengths, where a unitless number other than 0 is invalid
	 */
	const LENGTHS = [
		'width',
		'height',
		'max-width',
		'min-width',
		'max-height',
		'font-size',
		'letter-spacing',
		'top',
		'right',
		'bottom',
		'left',
	];

	/**
	 * Fonts the editor knows, see `fonts` in email-block-editor.js. Any other font is read as the first one of these
	 * that shares a family with it, or Arial.
	 */
	const FONTS = [
		'Arial, sans-serif',
		'Arial Black, Arial, sans-serif',
		'Arial Narrow, Arial, sans-serif',
		'Book Antiqua, Palatino, serif',
		'Calibri, sans-serif',
		'Century Gothic, sans-serif',
		'Comic Sans MS, Comic Sans, cursive',
		'Copperplate Gothic Light, Copperplate, Century Gothic, Arial, sans-serif',
		'Copperplate, sans-serif',
		'Courier New, Courier, monospace',
		'Futura, Calibri, Arial, sans-serif',
		'Garamond, Palatino, Times New Roman, Times, serif',
		'Georgia, serif',
		'Helvetica, Arial, sans-serif',
		'Impact, Arial Black, sans-serif',
		'Lucida Grande, sans-serif',
		'Lucida Sans, Lucida Grande, Arial, sans-serif',
		'Palatino, Times New Roman, Times, serif',
		'system-ui, sans-serif',
		'Tahoma, sans-serif',
		'Times New Roman, Times, serif',
		'Trebuchet MS, sans-serif',
		'Verdana, Geneva, sans-serif',
	];

	/**
	 * The declarations in order, longhands for the properties in SIDES
	 *
	 * @var string[]
	 */
	protected $props = [];

	/**
	 * @param string $style a style attribute's value
	 */
	public function __construct( string $style = '' ) {

		// split on semicolons that aren't in brackets or quotes, like in url(...)
		foreach ( self::split( $style, ';' ) as $declaration ) {

			$colon = strpos( $declaration, ':' );

			if ( $colon === false ) {
				continue;
			}

			$name  = strtolower( trim( substr( $declaration, 0, $colon ) ) );
			$value = trim( substr( $declaration, $colon + 1 ) );

			if ( $name === '' || $value === '' ) {
				continue;
			}

			$this->set( $name, $value );
		}
	}

	/**
	 * Set properties in order, like Object.assign( el.style, $props ) in the editor
	 *
	 * @param array $props camelCase or kebab-case names
	 *
	 * @return $this
	 */
	public function assign( array $props ) {
		foreach ( $props as $name => $value ) {
			$this->set( (string) $name, $value );
		}

		return $this;
	}

	/**
	 * Set a property like `el.style[ name ] = value`
	 *
	 * - null and '' remove it
	 * - an invalid value is ignored, like a number other than 0 for a length, or NaN
	 * - a shorthand in SIDES is stored as its longhands
	 *
	 * @param string $name
	 * @param mixed  $value
	 *
	 * @return $this
	 */
	public function set( string $name, $value ) {

		$name = self::kebab( $name );

		// CSS custom properties and other names starting with -- aren't used in emails
		if ( $name === '' || strpos( $name, '--' ) === 0 ) {
			return $this;
		}

		if ( $value === null || $value === '' ) {
			$this->remove( $name );

			return $this;
		}

		$value = self::value( $name, $value );

		if ( $value === null ) {
			return $this;
		}

		if ( isset( self::SIDES[ $name ] ) ) {

			$sides = self::expand( $value );

			if ( ! $sides ) {
				return $this;
			}

			foreach ( self::SIDES[ $name ] as $i => $longhand ) {
				$this->put( $longhand, self::side_value( $name, $sides[ $i ] ) );
			}

			return $this;
		}

		$this->put( $name, $value );

		return $this;
	}

	/**
	 * Set a single property, keeping its place when it's already set
	 *
	 * @param string $name
	 * @param string $value
	 *
	 * @return void
	 */
	protected function put( string $name, string $value ) {
		$this->props[ $name ] = $value;
	}

	/**
	 * Remove a property, or every longhand of a shorthand
	 *
	 * @param string $name
	 *
	 * @return $this
	 */
	public function remove( string $name ) {

		$name = self::kebab( $name );

		foreach ( self::SIDES[ $name ] ?? [ $name ] as $longhand ) {
			unset( $this->props[ $longhand ] );
		}

		return $this;
	}

	/**
	 * A property's value like getPropertyValue(), '' when it isn't set. A shorthand in SIDES only has a value when
	 * all its sides are set.
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	public function get( string $name ): string {

		$name = self::kebab( $name );

		if ( isset( self::SIDES[ $name ] ) ) {

			$sides = [];

			foreach ( self::SIDES[ $name ] as $longhand ) {

				if ( ! isset( $this->props[ $longhand ] ) ) {
					return '';
				}

				$sides[] = $this->props[ $longhand ];
			}

			return self::compact( $sides );
		}

		return $this->props[ $name ] ?? '';
	}

	/**
	 * Whether a property is set
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function has( string $name ): bool {
		return $this->get( $name ) !== '';
	}

	/**
	 * Whether any property is set
	 *
	 * @return bool
	 */
	public function is_empty(): bool {
		return empty( $this->props );
	}

	/**
	 * The style attribute, with the longhands of a shorthand folded back together where they're all set, in the place
	 * of the first one
	 *
	 * @return string
	 */
	public function __toString(): string {

		$out  = [];
		$done = [];

		foreach ( $this->props as $name => $value ) {

			$shorthand = self::shorthand_of( $name );

			if ( $shorthand ) {

				if ( isset( $done[ $shorthand ] ) ) {
					continue;
				}

				$folded = $this->get( $shorthand );

				if ( $folded !== '' ) {
					$done[ $shorthand ] = true;
					$out[]              = $shorthand . ': ' . $folded;
					continue;
				}
			}

			$out[] = $name . ': ' . $value;
		}

		return implode( '; ', $out ) . ( $out ? ';' : '' );
	}

	/**
	 * The shorthand a longhand belongs to
	 *
	 * @param string $longhand
	 *
	 * @return string
	 */
	protected static function shorthand_of( string $longhand ): string {
		foreach ( self::SIDES as $shorthand => $longhands ) {
			if ( in_array( $longhand, $longhands, true ) ) {
				return $shorthand;
			}
		}

		return '';
	}

	/**
	 * camelCase to kebab-case, like objectToStyle() in element.js
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	public static function kebab( string $name ): string {
		return strtolower( trim( preg_replace( '/(?<!^)[A-Z]/', '-$0', $name ) ) );
	}

	/**
	 * Check and normalize a value for a property, null when the browser would ignore it
	 *
	 * @param string $name
	 * @param mixed  $value
	 *
	 * @return string|null
	 */
	protected static function value( string $name, $value ) {

		if ( is_bool( $value ) || is_array( $value ) || is_object( $value ) ) {
			return null;
		}

		if ( is_int( $value ) || is_float( $value ) ) {

			if ( is_float( $value ) && is_nan( $value ) ) {
				return null;
			}

			if ( in_array( $name, self::UNITLESS, true ) ) {
				return self::number( $value );
			}

			// a number without a unit is only a valid length when it's 0
			return (float) $value === 0.0 ? '0px' : null;
		}

		$value = trim( preg_replace( '/\s+/', ' ', (string) $value ) );

		// like `${ NaN }px`, which the editor sets when a number is missing
		if ( $value === '' || preg_match( '/(?<![a-z-])(NaN|undefined|null)(px|%|em)?(?![a-z-])/i', $value ) ) {
			return null;
		}

		if ( strpos( $name, 'color' ) !== false ) {
			return self::color( $value );
		}

		if ( $name === 'font-family' ) {
			return self::font_family( $value );
		}

		if ( in_array( $name, self::LENGTHS, true ) ) {

			if ( is_numeric( $value ) ) {
				return (float) $value === 0.0 ? '0px' : null;
			}

			return $value;
		}

		if ( isset( self::SIDES[ $name ] ) || preg_match( '/^(margin|padding)-/', $name ) || preg_match( '/-(width|radius)$/', $name ) ) {
			return $value;
		}

		return $value;
	}

	/**
	 * A side's value for a SIDES shorthand: lengths of 0 get a unit
	 *
	 * @param string $shorthand
	 * @param string $value
	 *
	 * @return string
	 */
	protected static function side_value( string $shorthand, string $value ): string {

		if ( $shorthand === 'border-style' ) {
			return $value;
		}

		if ( $shorthand === 'border-color' ) {
			return self::color( $value );
		}

		return is_numeric( $value ) && (float) $value === 0.0 ? '0px' : $value;
	}

	/**
	 * A number like JavaScript's String( number )
	 *
	 * @param int|float $number
	 *
	 * @return string
	 */
	public static function number( $number ): string {

		if ( is_int( $number ) || floor( $number ) == $number ) {
			return (string) (int) $number;
		}

		return rtrim( rtrim( sprintf( '%.10F', $number ), '0' ), '.' );
	}

	/**
	 * 1 to 4 values for the sides of a box, as top, right, bottom, left
	 *
	 * @param string $value
	 *
	 * @return string[]|false
	 */
	public static function expand( string $value ) {

		$parts = self::split( $value, ' ' );
		$parts = array_values( array_filter( $parts, 'strlen' ) );

		switch ( count( $parts ) ) {
			case 1:
				return [ $parts[0], $parts[0], $parts[0], $parts[0] ];
			case 2:
				return [ $parts[0], $parts[1], $parts[0], $parts[1] ];
			case 3:
				return [ $parts[0], $parts[1], $parts[2], $parts[1] ];
			case 4:
				return $parts;
		}

		return false;
	}

	/**
	 * Four sides as the shortest shorthand value, like the browser writes them
	 *
	 * @param string[] $sides top, right, bottom, left
	 *
	 * @return string
	 */
	public static function compact( array $sides ): string {

		list( $top, $right, $bottom, $left ) = $sides;

		if ( $left === $right ) {

			if ( $top === $bottom ) {
				return $top === $right ? $top : "$top $right";
			}

			return "$top $right $bottom";
		}

		return "$top $right $bottom $left";
	}

	/**
	 * Split on a separator that isn't inside brackets or quotes
	 *
	 * @param string $value
	 * @param string $separator
	 *
	 * @return string[]
	 */
	protected static function split( string $value, string $separator ): array {

		$parts = [];
		$depth = 0;
		$quote = '';
		$part  = '';

		for ( $i = 0, $length = strlen( $value ); $i < $length; $i ++ ) {

			$char = $value[ $i ];

			if ( $quote ) {
				if ( $char === $quote ) {
					$quote = '';
				}
			} else if ( $char === '"' || $char === "'" ) {
				$quote = $char;
			} else if ( $char === '(' ) {
				$depth ++;
			} else if ( $char === ')' ) {
				$depth = max( 0, $depth - 1 );
			} else if ( $char === $separator && $depth === 0 ) {
				$parts[] = trim( $part );
				$part    = '';
				continue;
			}

			$part .= $char;
		}

		$parts[] = trim( $part );

		return $parts;
	}

	/**
	 * A color in the form email_kses() leaves it: hex and rgb() colors as lowercase six-digit hex, keywords lowercase
	 *
	 * @param string $color
	 *
	 * @return string
	 */
	public static function color( string $color ): string {

		$color = trim( $color );

		if ( preg_match( '/^#([0-9a-f]{3})$/i', $color, $m ) ) {
			$hex = $m[1];

			return strtolower( '#' . $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2] );
		}

		if ( preg_match( '/^#[0-9a-f]{6}$/i', $color ) ) {
			return strtolower( $color );
		}

		if ( preg_match( '/^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/i', $color, $m ) ) {
			return sprintf( '#%02x%02x%02x', min( 255, $m[1] ), min( 255, $m[2] ), min( 255, $m[3] ) );
		}

		if ( preg_match( '/^[a-z]+$/i', $color ) ) {
			return strtolower( $color );
		}

		return $color;
	}

	/**
	 * A font family list without quotes, as email_kses() takes them out before sanitizing
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public static function font_family( string $value ): string {
		$fonts = array_map( function ( $font ) {
			return trim( str_replace( [ '"', "'", '&quot;' ], '', $font ) );
		}, explode( ',', $value ) );

		return implode( ', ', array_filter( $fonts, 'strlen' ) );
	}

	/**
	 * The editor's font for a font family, like normalizeFont() in the editor: the first known font that has one of
	 * the families, in order, or Arial
	 *
	 * @param string $font
	 *
	 * @return string
	 */
	public static function normalize_font( string $font ): string {

		static $lookup;

		if ( ! $lookup ) {
			$lookup = [];
			foreach ( self::FONTS as $known ) {
				$lookup[ self::font_id( $known ) ] = $known;
			}
		}

		foreach ( explode( ',', self::font_family( $font ) ) as $family ) {
			$id = self::font_id( $family );
			if ( isset( $lookup[ $id ] ) ) {
				return $lookup[ $id ];
			}
		}

		return 'Arial, sans-serif';
	}

	/**
	 * @param string $font
	 *
	 * @return string
	 */
	protected static function font_id( string $font ): string {
		$first = explode( ',', str_replace( [ '"', "'" ], '', $font ) )[0];

		return str_replace( ' ', '-', strtolower( trim( $first ) ) );
	}

	/**
	 * CSS declarations from props, like objectToStyle() in element.js, which the editor uses for block CSS. Empty
	 * values are skipped, and plain numbers for some properties get px.
	 *
	 * NaN is skipped too: the editor's block state is copied through JSON, which turns NaN into null.
	 *
	 * @param array $props
	 *
	 * @return string
	 */
	public static function object_to_style( array $props ): string {

		$out = [];

		foreach ( $props as $name => $value ) {

			if ( $value === null || $value === '' || $value === 'null' || is_array( $value ) || is_bool( $value ) || ( is_float( $value ) && is_nan( $value ) ) ) {
				continue;
			}

			$name = self::kebab( (string) $name );

			if ( in_array( $name, [ 'font-size', 'height', 'width', 'margin', 'padding', 'margin-top' ], true ) && is_numeric( $value ) ) {
				$value .= 'px';
			}

			$out[] = $name . ':' . ( is_float( $value ) || is_int( $value ) ? self::number( $value ) : $value );
		}

		return $out ? implode( ';', $out ) . ';' : '';
	}
}
