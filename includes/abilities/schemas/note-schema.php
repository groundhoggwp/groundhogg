<?php

namespace Groundhogg\Abilities\Schemas;

use DateTime;
use Exception;
use Groundhogg\Classes\Note;
use Groundhogg\Utils\DateTimeHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared schema for a Groundhogg Note, as returned by abilities like
 * groundhogg/add-contact-note and groundhogg/list-contact-notes.
 *
 * Notes are actually polymorphic in the DB (object_type/object_id can point at a
 * contact, deal, company, etc. - see \Groundhogg\Classes\Note) but every ability that
 * uses this schema today scopes to object_type = 'contact' only, so `contact_id` is
 * exposed directly rather than the raw object_type/object_id pair - there's nothing
 * for a caller to disambiguate yet.
 */
class Note_Schema extends Schema {

	public static function get_schema(): array {

		return [
			'type'       => 'object',
			'properties' => [
				'id' => [
					'type' => 'integer',
				],
				'contact_id' => [
					'type' => 'integer',
				],
				'user_id' => [
					'type'        => 'integer',
					'description' => __( 'The WordPress user who authored the note.', 'groundhogg' ),
				],
				'summary' => [
					'type'        => 'string',
					'description' => __( 'Optional short summary/title - most notes leave this blank and rely on content.', 'groundhogg' ),
				],
				'content' => [
					'type' => 'string',
				],
				'date_created' => [
					'type'   => 'string',
					'format' => 'date-time',
				],
			],
		];
	}

	/**
	 * Accepts an existing Note, or anything Note::__construct() accepts (a note ID or
	 * raw DB row).
	 *
	 * @param Note|int|object $object
	 * @param array            $include Unused - Note has nothing bounded-but-optional
	 *                                   to offer (unlike Contact_Schema's tags/meta).
	 *
	 * @return array
	 */
	public static function transform( $object, array $include = [] ): array {

		if ( ! $object instanceof Note ) {
			$object = new Note( $object );
		}

		return [
			'id'           => $object->get_id(),
			'contact_id'   => absint( $object->object_id ),
			'user_id'      => $object->get_owner_id(),
			'summary'      => $object->summary,
			'content'      => $object->content,
			'date_created' => self::format_date_created( $object ),
		];
	}

	/**
	 * @param Note $object
	 *
	 * @return string
	 */
	private static function format_date_created( Note $object ): string {

		try {
			// Note itself (post_setup()) builds its `timestamp` property the same way -
			// new DateTimeHelper( $this->date_created ) with no explicit timezone, even
			// though date_created is stored as a GMT string (current_time('mysql', true))
			// - mirrored here rather than "correcting" it to an explicit UTC parse, so
			// this schema's date_created agrees with the note's own timestamp property
			// instead of silently disagreeing with it by a timezone offset.
			return ( new DateTimeHelper( $object->date_created ) )->format( DateTime::ATOM );
		} catch ( Exception $e ) {
			return (string) $object->date_created;
		}
	}
}
