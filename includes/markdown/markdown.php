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
		] );
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
	 * @param array  $keys   the sections, in this order, all of them when empty
	 * @param array  $args   personal: include personal details, only kept when the current person can see them, or
	 *                       when nobody is signed in, like in a flow. limit: the most items in a list, like notes.
	 *
	 * @return string
	 */
	public static function render( string $type, $object, array $keys = [], array $args = [] ): string {

		$args = wp_parse_args( $args, [
			'personal' => false,
			'limit'    => 20,
		] );

		$asked_personal   = (bool) $args['personal'];
		$args['personal'] = $asked_personal && ! Pii::is_restricted();
		$args['limit']    = max( 1, absint( $args['limit'] ) );

		$sections = self::sections( $type );
		$keys     = $keys ?: array_keys( $sections );
		$parts    = [];

		foreach ( array_unique( $keys ) as $key ) {

			$section = $sections[ $key ] ?? null;

			if ( ! $section ) {
				continue;
			}

			$heading = '## ' . $section['label'] . "\n\n";

			// personal details that weren't asked for are left out on purpose. Asked for and not allowed, they're hidden.
			if ( $section['personal'] && ! $args['personal'] ) {

				if ( $asked_personal ) {
					$parts[] = $heading . self::hidden();
				}

				continue;
			}

			$can = is_callable( $section['can'] ) ? call_user_func( $section['can'], $object, $args ) : true;

			if ( $can === false ) {
				$parts[] = $heading . self::hidden();
				continue;
			}

			$body = trim( (string) call_user_func( $section['callback'], $object, $args ) );

			// what they can't see of it, like other people's deals
			$partial = is_string( $can ) && $can !== '' ? $can . "\n\n" : '';

			$parts[] = $heading . $partial . ( $body !== '' ? $body : self::none() );
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
	 * @param int|string $time a timestamp, or a date in UTC like the tables store
	 *
	 * @return string
	 */
	public static function date( $time ): string {

		if ( ! is_numeric( $time ) ) {
			$time = strtotime( (string) $time . ' UTC' );
		}

		return $time ? wp_date( 'Y-m-d H:i', (int) $time ) : '';
	}
}
