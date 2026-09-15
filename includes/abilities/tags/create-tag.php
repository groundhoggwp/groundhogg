<?php

namespace Groundhogg\Abilities\Tags;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\Tag_Schema;
use Groundhogg\Tag;
use function Groundhogg\get_db;

/**
 * Creates a new Groundhogg tag. If a tag with the given name already exists - matched by slug,
 * the same way Groundhogg matches tags everywhere else (Tags::add()) - the existing tag is
 * returned (and updated with any other fields provided) instead of creating a duplicate. The
 * response's "created" field tells you which happened.
 */
class Create_Tag extends Ability {

	protected const NAME       = 'groundhogg/create-tag';
	protected const CATEGORY   = 'groundhogg-tags';
	protected const CAPABILITY = 'add_tags';

	protected const IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'Create Tag', 'groundhogg' ),
			'description' => __( 'Create a new Groundhogg tag. If a tag with the given name already exists, the existing tag is returned (and updated with any other fields provided) instead of creating a duplicate - the response\'s "created" field tells you which happened. Use the returned id with groundhogg/create-contact\'s/groundhogg/update-contact\'s tags param, or groundhogg/search-contacts\' tags_include/tags_exclude.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'name' ],
				'properties'           => [
					'name'                => [
						'type'        => 'string',
						'description' => __( 'The tag name, e.g. "Newsletter Subscriber". Matched against existing tags by slug - "Newsletter Subscriber" and "newsletter-subscriber" resolve to the same tag.', 'groundhogg' ),
					],
					'description'         => [
						'type'        => 'string',
						'description' => __( 'Internal notes about what this tag is for - not shown to contacts.', 'groundhogg' ),
					],
					'show_as_preference'  => [
						'type'        => 'boolean',
						'default'     => false,
						'description' => __( 'Whether contacts can see and toggle this tag themselves on the email preferences page.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => array_merge( Tag_Schema::get_schema()['properties'], [
					'created' => [
						'type'        => 'boolean',
						'description' => __( 'True if this created a brand-new tag; false if an existing tag with the same name was found (and possibly updated).', 'groundhogg' ),
					],
				] ),
			],
		];
	}

	public function __invoke( $input ) {

		$db   = get_db( 'tags' );
		$slug = sanitize_title( $input['name'] );

		$existed = (bool) $db->exists( $slug, 'tag_slug' );

		$data = [ 'tag_name' => sanitize_text_field( $input['name'] ) ];

		if ( isset( $input['description'] ) ) {
			$data['tag_description'] = sanitize_textarea_field( $input['description'] );
		}

		if ( isset( $input['show_as_preference'] ) ) {
			$data['show_as_preference'] = $input['show_as_preference'] ? 1 : 0;
		}

		$tag_id = $db->add( $data );
		$tag    = new Tag( $tag_id );

		// add() only applies tag_name when the slug already matched an existing tag - apply the
		// rest of what was given now.
		if ( $existed ) {
			unset( $data['tag_name'] );

			if ( $data ) {
				$tag->update( $data );
			}
		}

		return array_merge( Tag_Schema::transform( $tag ), [
			'created' => ! $existed,
		] );
	}
}
