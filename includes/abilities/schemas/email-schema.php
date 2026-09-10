<?php

namespace Groundhogg\Abilities\Schemas;

use Groundhogg\Email;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared schema for a Groundhogg Email ("email template"), as returned by
 * abilities like groundhogg/list-email-templates.
 *
 * Standard fields (id, title, subject, status, from, dates) are small, single-row
 * values and are always included. `content` (the full rendered HTML body) and
 * `plain_text` are bounded-but-large - a single email body is routinely tens of
 * KB - so they are only returned when explicitly requested via the $include
 * argument to transform(). This mirrors how Contact_Schema gates `meta`.
 */
class Email_Schema extends Schema {

	public static function get_schema(): array {

		return [
			'type'       => 'object',
			'properties' => [
				'id' => [
					'type' => 'integer',
				],
				'title' => [
					'type'        => 'string',
					'description' => __( 'The internal admin title. Falls back to the subject line when unset.', 'groundhogg' ),
				],
				'subject' => [
					'type'        => 'string',
					'description' => __( 'The raw subject line. May contain merge tags like {first_name} that are resolved per-recipient at send time.', 'groundhogg' ),
				],
				'pre_header' => [
					'type' => 'string',
				],
				'status' => [
					'type'        => 'string',
					'description' => __( 'Either "ready" or "draft". Only a "ready" email sends cleanly through groundhogg/send-email-template.', 'groundhogg' ),
				],
				'is_template' => [
					'type'        => 'boolean',
					'description' => __( 'True for emails saved as reusable templates (Emails > Templates) rather than one-off sends.', 'groundhogg' ),
				],
				'message_type' => [
					'type'        => 'string',
					'description' => __( '"marketing" or "transactional" - which sending rules and compliance footer apply.', 'groundhogg' ),
				],
				'from' => [
					'type'        => 'object',
					'description' => __( 'The configured sender. name/email may contain merge tags (e.g. the "owner" profile). See groundhogg/list-sender-profiles.', 'groundhogg' ),
					'properties'  => [
						'profile' => [
							'type' => 'string',
						],
						'name' => [
							'type' => 'string',
						],
						'email' => [
							'type' => 'string',
						],
					],
				],
				'date_created' => [
					'type'        => 'string',
					'description' => __( 'Y-m-d H:i:s in the site timezone.', 'groundhogg' ),
				],
				'last_updated' => [
					'type'        => 'string',
					'description' => __( 'Y-m-d H:i:s in the site timezone.', 'groundhogg' ),
				],
				'content' => [
					'type'        => 'string',
					'description' => __( 'Only present when "content" is passed in expand. The full email body as HTML - can be large.', 'groundhogg' ),
				],
				'plain_text' => [
					'type'        => 'string',
					'description' => __( 'Only present when "plain_text" is passed in expand. The plain-text alternative body.', 'groundhogg' ),
				],
			],
		];
	}

	/**
	 * Accepts an existing Email, or anything Email::__construct() accepts (an ID
	 * or raw DB row).
	 *
	 * @param Email|int|object $object
	 * @param array            $include Optional sections to include: 'content', 'plain_text'.
	 *
	 * @return array
	 */
	public static function transform( $object, array $include = [] ): array {

		if ( ! $object instanceof Email ) {
			$object = new Email( $object );
		}

		$profile = $object->get_from_profile();

		$data = [
			'id'           => $object->get_id(),
			'title'        => $object->get_title(),
			'subject'      => $object->get_subject_line(),
			'pre_header'   => $object->get_pre_header(),
			'status'       => $object->get_status(),
			'is_template'  => (bool) $object->is_template(),
			'message_type' => (string) $object->get_message_type(),
			'from'         => [
				'profile' => (string) $object->from_profile,
				'name'    => $profile['from_name'] ?? '',
				'email'   => $profile['from_email'] ?? '',
			],
			'date_created' => (string) $object->get_date_created(),
			'last_updated' => (string) $object->get_last_updated(),
		];

		if ( in_array( 'content', $include, true ) ) {
			$data['content'] = $object->get_content();
		}

		if ( in_array( 'plain_text', $include, true ) ) {
			$data['plain_text'] = (string) $object->plain_text;
		}

		return $data;
	}
}
