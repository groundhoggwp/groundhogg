<?php

namespace Groundhogg\Markdown;

use Groundhogg\Pii;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turns a record, like a contact, into markdown for an AI model to read: what it is, and its history. It's made of
 * sections, like a contact's notes or activity, so what's sent can be picked: an AI feature asks for the sections it
 * needs, and a screen can list them for people to pick from, see sections().
 *
 *     Markdown::render( 'contact', $contact, [ 'details', 'tags', 'notes' ] );
 *
 * Personal details, like a contact's name, email address and phone numbers, are left out unless they're asked for
 * and the current person can see them, see render(). Without them, the contact's own details are also taken out of
 * free text, like notes. Most of what an AI model needs, like what someone did and what they're interested in,
 * doesn't need to say who they are.
 *
 * Add a section to a type with register(), on the groundhogg/markdown/{type}/sections action:
 *
 *     add_action( 'groundhogg/markdown/contact/sections', function () {
 *         Markdown::register( 'contact', 'orders', [
 *             'label'       => __( 'Orders', 'my-plugin' ),
 *             'description' => __( 'What they bought, and when.', 'my-plugin' ),
 *             'callback'    => function ( Contact $contact, array $args ) {
 *                 return Markdown::items( [ '2026-11-20: Pro plan, $199' ] );
 *             },
 *         ] );
 *     } );
 */
class Markdown {

	/**
	 * @var array[] by type, then key
	 */
	protected static $sections = [];

	/**
	 * @var bool[] the types whose sections have been registered, by type
	 */
	protected static $registered = [];

	/**
	 * @var string[] what each section of the last render had, by key: content, none or hidden
	 */
	protected static $last_render = [];

	/**
	 * What each section of the last render had, by key: content, none or hidden. Like for knowing which sections an AI
	 * answer could have drawn on.
	 *
	 * @return string[]
	 */
	public static function last_render(): array {
		return self::$last_render;
	}

	/**
	 * Whether the current person may see all of a section of a record: true, false, or a sentence saying what of it
	 * they can't see, see register()
	 *
	 * @param string $type
	 * @param string $key
	 * @param mixed  $object
	 *
	 * @return bool|string
	 */
	public static function can_see( string $type, string $key, $object ) {

		$section = self::sections( $type )[ $key ] ?? null;

		if ( ! $section ) {
			return false;
		}

		if ( $section['personal'] && Pii::is_restricted() ) {
			return false;
		}

		return is_callable( $section['can'] ) ? call_user_func( $section['can'], $object, [
			'personal' => false,
			'limit'    => 20,
			'since'    => 0,
			'until'    => 0,
		] ) : true;
	}

	/**
	 * The most IDs an option takes, like flows
	 */
	const MAX_IDS = 50;

	/**
	 * What registers the sections a type has built in, and those of the official add-ons that are running
	 */
	const CORE = [
		'contact' => [
			[ Contact_Markdown::class, 'register_sections' ],
			[ Add_On_Markdown::class, 'register_sections' ],
		],
	];

	/**
	 * Add a section to a type
	 *
	 * @param string $type    the type of record, like contact
	 * @param string $key     how it's asked for, like notes
	 * @param array  $section label, description, callback( $object, array $args ): string, and:
	 *                        - personal: true when it's only made of personal details, so it's left out unless
	 *                        they're asked for
	 *                        - can( $object, array $args ): true when the current person may see all of it, false
	 *                        when they may see none of it, or a sentence saying what they can't see when they may
	 *                        see part of it, like only their own deals. Leave it out when anyone who can see the
	 *                        record can see the section. Someone who can't is told so, rather than the section
	 *                        looking empty, see render().
	 *
	 * @return void
	 */
	public static function register( string $type, string $key, array $section ) {
		self::$sections[ $type ][ $key ] = wp_parse_args( $section, [
			'label'       => $key,
			'description' => '',
			'callback'    => null,
			'can'         => null,
			'personal'    => false,
			'options'     => [],
		] );
	}

	/**
	 * The options of a list, like notes or activity: how many, and when. A section that is a list should offer them,
	 * merged with its own, like which flows. They're in the $args its callback gets, see render().
	 *
	 * An option is type (ids, strings, time or integer), label and description, choices for strings when there's a
	 * set to pick from, value => label, and name for ids: a function that gives an ID's title, for the line that says
	 * what a section was narrowed to. Left out, an option means everything.
	 *
	 * @return array[]
	 */
	public static function list_options(): array {
		return [
			'limit' => [
				'type'        => 'integer',
				'label'       => __( 'How many', 'groundhogg' ),
				'description' => __( 'The most items, newest first.', 'groundhogg' ),
			],
			'since' => [
				'type'        => 'time',
				'label'       => __( 'Since', 'groundhogg' ),
				'description' => __( 'Only what happened since then: a date, like 2026-11-01, or a time ago, like "30 days ago". All time when left out.', 'groundhogg' ),
			],
			'until' => [
				'type'        => 'time',
				'label'       => __( 'Until', 'groundhogg' ),
				'description' => __( 'Only what happened before then: a date, or a time ago. Up to now when left out.', 'groundhogg' ),
			],
		];
	}

	/**
	 * A moment someone wrote, like 2026-11-01 or "30 days ago", in the site's time zone
	 *
	 * @param mixed $value      a timestamp, a date or a relative time
	 * @param bool  $end_of_day a date on its own means the end of that day, like for until
	 *
	 * @return int a timestamp, 0 when there isn't one
	 */
	public static function time( $value, bool $end_of_day = false ): int {

		// a timestamp, from 1973 on. A shorter number is more likely a date, like 20261101.
		if ( is_int( $value ) || ( is_numeric( $value ) && (float) $value >= 100000000 ) ) {
			return absint( $value );
		}

		$value = trim( (string) $value );

		if ( $value === '' ) {
			return 0;
		}

		// "30 days" is 30 days ago: PHP reads it as 30 days from now, and a period of history is never in the future
		if ( preg_match( '/^\d+\s*(second|sec|minute|min|hour|day|week|fortnight|month|year)s?$/i', $value ) ) {
			$value .= ' ago';
		}

		// 2026-11-25 until the end of the 25th, not its first second
		if ( $end_of_day && preg_match( '/^\d{4}-?\d{2}-?\d{2}$/', $value ) ) {
			$value .= ' 23:59:59';
		}

		try {
			$date = new \DateTime( $value, wp_timezone() );
		} catch ( \Exception $e ) {
			return 0;
		}

		return max( 0, $date->getTimestamp() );
	}

	/**
	 * Options as given, kept to what a section offers, each as its type
	 *
	 * @param array $offered the section's options
	 * @param array $given
	 *
	 * @return array
	 */
	public static function clean_options( array $offered, array $given ): array {

		$clean = [];

		foreach ( $offered as $name => $option ) {

			if ( ! isset( $given[ $name ] ) ) {
				continue;
			}

			$value = $given[ $name ];

			switch ( $option['type'] ?? '' ) {

				case 'ids':
					// what isn't a number is 0, which is no ID. A list long enough to cost something is cut short.
					$value = array_slice( array_values( array_filter( wp_parse_id_list( $value ) ) ), 0, self::MAX_IDS );
					break;

				case 'strings':
					$value = array_values( array_filter( array_map( function ( $string ) {
						return is_scalar( $string ) ? sanitize_text_field( (string) $string ) : '';
					}, (array) $value ), 'strlen' ) );

					if ( ! empty( $option['choices'] ) ) {
						$value = array_values( array_intersect( $value, array_map( 'strval', array_keys( $option['choices'] ) ) ) );
					}
					break;

				case 'time':
					$value = self::time( $value, $name === 'until' );
					break;

				case 'integer':
					$value = min( 200, max( 1, absint( $value ) ) );
					break;

				default:
					$value = null;
			}

			// nothing left means everything, as if it wasn't given
			if ( $value === null || $value === [] || $value === 0 ) {
				continue;
			}

			$clean[ $name ] = $value;
		}

		return $clean;
	}

	/**
	 * The time options that were given but can't be read, which would otherwise quietly mean all time
	 *
	 * @param array[] $offered a section's options
	 * @param array   $given
	 *
	 * @return string[] their names
	 */
	public static function unreadable_times( array $offered, array $given ): array {

		$bad = [];

		foreach ( $offered as $name => $option ) {

			if ( ( $option['type'] ?? '' ) !== 'time' || ! isset( $given[ $name ] ) || $given[ $name ] === '' || $given[ $name ] === null ) {
				continue;
			}

			if ( ! is_scalar( $given[ $name ] ) || ! self::time( $given[ $name ] ) ) {
				$bad[] = $name;
			}
		}

		return $bad;
	}

	/**
	 * The options as a JSON schema, for an ability's input
	 *
	 * @param array[] $options
	 *
	 * @return array
	 */
	public static function options_schema( array $options ): array {

		$properties = [];

		foreach ( $options as $name => $option ) {

			$description = trim( ( $option['label'] ?? $name ) . ': ' . ( $option['description'] ?? '' ), ': ' );

			switch ( $option['type'] ?? '' ) {
				case 'ids':
					$schema = [ 'type' => 'array', 'items' => [ 'type' => 'integer', 'minimum' => 1 ], 'maxItems' => self::MAX_IDS ];
					break;
				case 'strings':
					$schema = [ 'type' => 'array', 'items' => [ 'type' => 'string' ] ];

					if ( ! empty( $option['choices'] ) ) {
						$schema['items']['enum'] = array_map( 'strval', array_keys( $option['choices'] ) );
					}
					break;
				case 'time':
					$schema = [ 'type' => [ 'string', 'integer' ] ];
					break;
				case 'integer':
					$schema = [ 'type' => 'integer', 'minimum' => 1, 'maximum' => 200 ];
					break;
				default:
					continue 2;
			}

			$properties[ $name ] = $schema + [ 'description' => $description ];
		}

		return [
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => $properties,
		];
	}

	/**
	 * What a section was narrowed to, like "Only since 2026-10-27, Flows: 3", or nothing when it has everything. The
	 * number of items isn't said: lists are newest first, and that's what limit cuts.
	 *
	 * @param array[] $offered the section's options
	 * @param array   $args    the section's $args
	 *
	 * @return string
	 */
	public static function scope( array $offered, array $args ): string {

		$parts = [];

		foreach ( $offered as $name => $option ) {

			if ( $name === 'limit' || empty( $args[ $name ] ) ) {
				continue;
			}

			$value = $args[ $name ];

			switch ( $option['type'] ?? '' ) {
				case 'time':
					$value = wp_date( 'Y-m-d', $value );
					break;
				case 'ids':
					// by name when the option knows how, a model does better with "Long Term Nurture" than with 45
					$value = implode( ', ', array_map( function ( $id ) use ( $option ) {
						$name = is_callable( $option['name'] ?? null ) ? trim( (string) call_user_func( $option['name'], $id ) ) : '';

						return $name !== '' ? sprintf( '"%s" (%d)', $name, $id ) : (string) $id;
					}, (array) $value ) );
					break;
				case 'strings':
					$value = implode( ', ', array_map( function ( $choice ) use ( $option ) {
						return $option['choices'][ $choice ] ?? $choice;
					}, (array) $value ) );
					break;
				default:
					$value = implode( ', ', (array) $value );
			}

			$parts[] = ( $option['label'] ?? $name ) . ': ' . $value;
		}

		if ( empty( $parts ) ) {
			return '';
		}

		/* translators: %s: what the list was narrowed to, like "Since: 2026-10-27; Flows: 3" */
		return sprintf( __( 'Only what was asked for, so there may be more: %s.', 'groundhogg' ), implode( '; ', $parts ) );
	}

	/**
	 * Keep a query to the time range in $args
	 *
	 * @param \Groundhogg\DB\Query\Where $where
	 * @param string                     $column
	 * @param array                      $args   a section's
	 * @param string                     $format unix for a timestamp column, mysql for a UTC datetime one, local for a
	 *                                           datetime in the site's time zone
	 *
	 * @return void
	 */
	public static function where_in_range( $where, string $column, array $args, string $format = 'unix' ) {

		foreach ( [ 'since' => '>=', 'until' => '<=' ] as $key => $compare ) {

			if ( empty( $args[ $key ] ) ) {
				continue;
			}

			switch ( $format ) {
				case 'mysql':
					$value = gmdate( 'Y-m-d H:i:s', $args[ $key ] );
					break;
				case 'local':
					$value = wp_date( 'Y-m-d H:i:s', $args[ $key ] );
					break;
				default:
					$value = $args[ $key ];
			}

			if ( $compare === '>=' ) {
				$where->greaterThanEqualTo( $column, $value );
			} else {
				$where->lessThanEqualTo( $column, $value );
			}
		}
	}

	/**
	 * Whether a moment is in the time range in $args, for what isn't queried from a table
	 *
	 * @param int   $time
	 * @param array $args a section's
	 *
	 * @return bool
	 */
	public static function in_range( int $time, array $args ): bool {
		return ( empty( $args['since'] ) || $time >= $args['since'] ) && ( empty( $args['until'] ) || $time <= $args['until'] );
	}

	/**
	 * What a section says when the person it's for may not see it. An AI model would otherwise take a missing section
	 * as an empty one, like no orders meaning they never bought anything.
	 *
	 * @return string
	 */
	public static function hidden(): string {
		return __( 'Hidden: the person this is for doesn\'t have permission to see this. Treat it as unknown, not as empty.', 'groundhogg' );
	}

	/**
	 * What a section with nothing in it says, so it isn't mistaken for one that wasn't asked for, or was hidden
	 *
	 * @return string
	 */
	public static function none(): string {
		return __( 'None.', 'groundhogg' );
	}

	/**
	 * The sections of a type, by key, to pick from
	 *
	 * @param string $type
	 *
	 * @return array[] each with key, label, description and personal
	 */
	public static function sections( string $type ): array {

		if ( empty( self::$registered[ $type ] ) ) {
			self::$registered[ $type ] = true;

			foreach ( self::CORE[ $type ] ?? [] as $register ) {
				call_user_func( $register );
			}

			/**
			 * Add sections to a type of record, with Markdown::register()
			 *
			 * @param string $type
			 */
			do_action( "groundhogg/markdown/{$type}/sections", $type );
		}

		$sections = [];

		foreach ( self::$sections[ $type ] ?? [] as $key => $section ) {
			if ( is_callable( $section['callback'] ) ) {
				$sections[ $key ] = array_merge( [ 'key' => $key ], $section );
			}
		}

		return $sections;
	}

	/**
	 * The sections, for a screen or the browser: without their callbacks
	 *
	 * @param string $type
	 *
	 * @return array[]
	 */
	public static function section_options( string $type ): array {
		return array_values( array_map( function ( $section ) {
			unset( $section['callback'], $section['can'] );

			// how an option names an ID is for the scope line, not for a picker
			foreach ( $section['options'] as $name => $option ) {
				unset( $section['options'][ $name ]['name'] );
			}

			return $section;
		}, self::sections( $type ) ) );
	}

	/**
	 * Forget the sections, so they're registered again
	 *
	 * @return void
	 */
	public static function reset() {
		self::$sections   = [];
		self::$registered = [];
	}

	/**
	 * A record as markdown, a section each, under its label. Every section asked for is there, so a reader can tell
	 * what's empty from what wasn't asked for: one with nothing in it says so, see none(), and one the current person
	 * may not see says that, see hidden().
	 *
	 * @param string $type   the type of record, like contact
	 * @param mixed  $object the record
	 * @param array  $keys   the sections, in this order, all of them when empty. A section's options go in as
	 *                       key => options, like [ 'details', 'activity' => [ 'funnel_ids' => [ 3 ], 'since' => '30
	 *                       days ago' ] ], see each section's options.
	 * @param array  $args   personal: include personal details, only kept when the current person can see them, or
	 *                       when nobody is signed in, like in a flow. limit: the most items in a list, like notes.
	 *                       since and until: the time range of every list, a section's own options go first.
	 *
	 * @return string
	 */
	public static function render( string $type, $object, array $keys = [], array $args = [] ): string {

		$args = wp_parse_args( $args, [
			'personal' => false,
			'limit'    => 20,
			'since'    => 0,
			'until'    => 0,
		] );

		$asked_personal   = (bool) $args['personal'];
		$args['personal'] = $asked_personal && ! Pii::is_restricted();
		$args['limit']    = min( 200, max( 1, absint( $args['limit'] ) ) );
		$args['since']    = self::time( $args['since'] );
		$args['until']    = self::time( $args['until'], true );

		$sections = self::sections( $type );
		$wanted   = [];

		foreach ( $keys as $key => $options ) {
			if ( is_int( $key ) ) {
				$wanted[ (string) $options ] = [];
			} else {
				$wanted[ (string) $key ] = is_array( $options ) ? $options : [];
			}
		}

		if ( empty( $wanted ) ) {
			$wanted = array_fill_keys( array_keys( $sections ), [] );
		}

		$parts             = [];
		self::$last_render = [];

		foreach ( $wanted as $key => $options ) {

			$section = $sections[ $key ] ?? null;

			if ( ! $section ) {
				continue;
			}

			$heading = '## ' . $section['label'] . "\n\n";

			// personal details that weren't asked for are left out on purpose. Asked for and not allowed, they're hidden.
			if ( $section['personal'] && ! $args['personal'] ) {

				if ( $asked_personal ) {
					$parts[]                   = $heading . self::hidden();
					self::$last_render[ $key ] = 'hidden';
				}

				continue;
			}

			// the time range of every list, then the section's own options, kept to what it offers
			$section_args = $args;

			if ( ! isset( $section['options']['since'] ) ) {
				$section_args['since'] = 0;
				$section_args['until'] = 0;
			}

			$section_args = array_merge( $section_args, self::clean_options( $section['options'], $options ) );

			$can = is_callable( $section['can'] ) ? call_user_func( $section['can'], $object, $section_args ) : true;

			if ( $can === false ) {
				$parts[]                   = $heading . self::hidden();
				self::$last_render[ $key ] = 'hidden';
				continue;
			}

			$body = trim( (string) call_user_func( $section['callback'], $object, $section_args ) );

			// what they can't see of it, like other people's deals, and what it was narrowed to, so an empty list
			// isn't read as never
			$notes = array_filter( [
				is_string( $can ) ? $can : '',
				self::scope( $section['options'], $section_args ),
			] );

			$parts[]                   = $heading . ( $notes ? implode( "\n\n", $notes ) . "\n\n" : '' ) . ( $body !== '' ? $body : self::none() );
			self::$last_render[ $key ] = $body !== '' ? 'content' : 'none';
		}

		/**
		 * Filter a record as markdown
		 *
		 * @param string   $markdown
		 * @param mixed    $object
		 * @param string[] $keys
		 * @param array    $args
		 */
		return (string) apply_filters( "groundhogg/markdown/{$type}", implode( "\n\n", $parts ), $object, $keys, $args );
	}

	/**
	 * Text from a person or the site, made one line: no HTML, no extra space, and cut short when it's long
	 *
	 * @param mixed $text
	 * @param int   $max characters
	 *
	 * @return string
	 */
	public static function text( $text, int $max = 500 ): string {

		$text = preg_replace( '#<br\s*/?>|</(p|h[1-6]|li|div|td|tr|blockquote)\s*>#i', ' ', (string) $text );
		$text = wp_strip_all_tags( html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
		$text = trim( preg_replace( '/\s+/u', ' ', $text ) );

		if ( mb_strlen( $text ) > $max ) {
			$text = rtrim( mb_substr( $text, 0, $max - 1 ) ) . '…';
		}

		return $text;
	}

	/**
	 * A list
	 *
	 * @param string[] $items
	 *
	 * @return string
	 */
	public static function items( array $items ): string {

		$items = array_filter( array_map( 'trim', array_map( 'strval', $items ) ), 'strlen' );

		return implode( "\n", array_map( function ( $item ) {
			return '- ' . $item;
		}, $items ) );
	}

	/**
	 * Label: value lines, leaving out the ones without a value
	 *
	 * @param array $pairs label => value
	 *
	 * @return string
	 */
	public static function pairs( array $pairs ): string {

		$items = [];

		foreach ( $pairs as $label => $value ) {

			if ( is_array( $value ) ) {
				$value = implode( ', ', array_filter( array_map( 'strval', $value ), 'strlen' ) );
			}

			$value = trim( (string) $value );

			if ( $value !== '' ) {
				$items[] = $label . ': ' . $value;
			}
		}

		return self::items( $items );
	}

	/**
	 * A moment in the site's time zone, the way the lists write it
	 *
	 * @param int|string $time  a timestamp, or a date in UTC like most tables store
	 * @param bool       $local the date is in the site's time zone, like a form submission's
	 *
	 * @return string
	 */
	public static function date( $time, bool $local = false ): string {

		if ( ! is_numeric( $time ) && $local ) {
			try {
				$time = ( new \DateTime( (string) $time, wp_timezone() ) )->getTimestamp();
			} catch ( \Exception $e ) {
				$time = 0;
			}
		} else if ( ! is_numeric( $time ) ) {
			$time = strtotime( (string) $time . ' UTC' );
		}

		return $time ? wp_date( 'Y-m-d H:i', (int) $time ) : '';
	}
}
