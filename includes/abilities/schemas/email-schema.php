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
				'editor_type' => [
					'type'        => 'string',
					'enum'        => [ 'html', 'blocks', 'legacy_blocks', 'legacy_plain' ],
					'description' => __( 'Which editor this email\'s content was built with, and which one wp-admin will open it in. "html": raw HTML (from the meta "type" flag, or content starting with "<!DOCTYPE"). "blocks": the current block editor (from the meta "blocks" flag) - content is HTML annotated with block comments ("<!--text:uuid {...}-->...<!--/text:uuid-->", etc.) that get parsed back into editable blocks. "legacy_blocks"/"legacy_plain": older formats inferred from the content itself. groundhogg/create-email\'s `editor` input controls which of "html"/"blocks" it produces.', 'groundhogg' ),
				],
				'template_settings' => [
					'type'        => 'object',
					'description' => __( 'The outer page layout/background this email renders inside of - distinct from the block/HTML content itself. Mirrors the "Template Settings" panel in wp-admin\'s email editor. Set via groundhogg/create-email\'s or groundhogg/update-email\'s `template_settings` input.', 'groundhogg' ),
					'properties'  => self::template_settings_properties(),
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
	 * The `template_settings` object's properties - shared by the output
	 * schema above and the `template_settings` input on
	 * groundhogg/create-email and groundhogg/update-email, so the property
	 * names, enums, and descriptions can't drift out of sync between them.
	 *
	 * @param bool $with_defaults Include `default` values for "layout" and
	 *                            "direction" - appropriate for create-email,
	 *                            where a brand-new email needs a sensible
	 *                            starting point. Leave false for the output
	 *                            schema and for update-email, where an absent
	 *                            property should be left untouched rather
	 *                            than implying it would reset to a default.
	 *
	 * @return array
	 */
	public static function template_settings_properties( bool $with_defaults = false ): array {

		$properties = [
			'layout' => [
				'type'        => 'string',
				'enum'        => [ 'boxed', 'full_width', 'full_width_contained' ],
				'description' => __( '"boxed": centered content column over a page background (alignment/width apply). "full_width": content spans the full viewport - width/alignment are ignored. "full_width_contained": full-bleed body; only the footer (when there\'s no footer block) is constrained to width.', 'groundhogg' ),
			],
			'width' => [
				'type'        => 'integer',
				'minimum'     => 100,
				'description' => __( 'Content column width in pixels. Ignored for "full_width".', 'groundhogg' ),
			],
			'alignment' => [
				'type'        => 'string',
				'enum'        => [ 'left', 'center' ],
				'description' => __( 'Horizontal position of the content column on the page background. Only meaningful for "boxed".', 'groundhogg' ),
			],
			'direction' => [
				'type'        => 'string',
				'enum'        => [ 'ltr', 'rtl' ],
				'description' => __( 'Text direction for the whole email.', 'groundhogg' ),
			],
			'background_color' => [
				'type'        => 'string',
				'description' => __( 'Page background color (any CSS color value) behind the content column.', 'groundhogg' ),
			],
			'background_image' => [
				'type'        => 'string',
				'format'      => 'uri',
				'description' => __( 'Page background image URL.', 'groundhogg' ),
			],
			'background_position' => [
				'type'        => 'string',
				'description' => __( 'CSS background-position, e.g. "center center". Only meaningful with background_image.', 'groundhogg' ),
			],
			'background_size' => [
				'type'        => 'string',
				'description' => __( 'CSS background-size, e.g. "cover" or "auto". Only meaningful with background_image.', 'groundhogg' ),
			],
			'background_repeat' => [
				'type'        => 'string',
				'description' => __( 'CSS background-repeat, e.g. "no-repeat". Only meaningful with background_image.', 'groundhogg' ),
			],
		];

		if ( $with_defaults ) {
			$properties['layout']['default']    = 'boxed';
			$properties['direction']['default'] = 'ltr';
		}

		return $properties;
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
			'id'                => $object->get_id(),
			'title'             => $object->get_title(),
			'subject'           => $object->get_subject_line(),
			'pre_header'        => $object->get_pre_header(),
			'status'            => $object->get_status(),
			'is_template'       => (bool) $object->is_template(),
			'message_type'      => (string) $object->get_message_type(),
			'editor_type'       => $object->get_editor_type(),
			'template_settings' => [
				'layout'              => $object->get_template(),
				'width'               => $object->get_width(),
				'alignment'           => $object->get_alignment() ?: 'left',
				'direction'           => $object->get_meta( 'direction' ) ?: 'ltr',
				'background_color'    => (string) $object->get_meta( 'backgroundColor' ),
				'background_image'    => (string) $object->get_meta( 'backgroundImage' ),
				'background_position' => (string) $object->get_meta( 'backgroundPosition' ),
				'background_size'     => (string) $object->get_meta( 'backgroundSize' ),
				'background_repeat'   => (string) $object->get_meta( 'backgroundRepeat' ),
			],
			'from'              => [
				'profile' => (string) $object->from_profile,
				'name'    => $profile['from_name'] ?? '',
				'email'   => $profile['from_email'] ?? '',
			],
			'date_created'      => (string) $object->get_date_created(),
			'last_updated'      => (string) $object->get_last_updated(),
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
