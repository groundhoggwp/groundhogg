<?php

namespace Groundhogg\Abilities\Utils;

use Groundhogg\Abilities\Ability;
use WP_Query;

/**
 * Finds files already in the WordPress Media Library, so an agent building an email can use the site's own images
 * before it reaches for a placeholder or uploads something new.
 *
 * Kept small on purpose: other plugins offer full media library abilities, and when an agent has those it should use
 * them. This is the fallback that makes sure there's always a way to look, and the description says so.
 */
class Search_Media extends Ability {

	protected const NAME       = 'groundhogg/search-media';
	protected const CATEGORY   = 'groundhogg-utils';
	protected const CAPABILITY = 'upload_files';

	protected const READONLY   = true;
	protected const IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'Search Media', 'groundhogg' ),
			'description' => __( 'Search the WordPress Media Library for files to use, e.g. images for an email. A lightweight fallback: if your connection has dedicated media library tools from another plugin, prefer those. Searches titles, captions and descriptions, newest first. To add a file, see groundhogg/upload-media.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [
					'search' => [
						'type'        => 'string',
						'description' => __( 'Words to look for, e.g. "logo" or "team photo". Omit to list the most recent files.', 'groundhogg' ),
					],
					'type'   => [
						'type'        => 'string',
						'enum'        => [ 'image', 'video', 'audio', 'application', 'any' ],
						'default'     => 'image',
						'description' => __( 'The kind of file. Defaults to images.', 'groundhogg' ),
					],
					'limit'  => [
						'type'    => 'integer',
						'minimum' => 1,
						'maximum' => 50,
						'default' => 20,
					],
					'page'   => [
						'type'    => 'integer',
						'minimum' => 1,
						'default' => 1,
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'total' => [
						'type'        => 'integer',
						'description' => __( 'How many files match, across all pages.', 'groundhogg' ),
					],
					'items' => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'id'        => [ 'type' => 'integer' ],
								'url'       => [
									'type'        => 'string',
									'description' => __( 'The full size file - use this as an image src.', 'groundhogg' ),
								],
								'title'     => [ 'type' => 'string' ],
								'alt_text'  => [ 'type' => 'string' ],
								'caption'   => [ 'type' => 'string' ],
								'mime_type' => [ 'type' => 'string' ],
								'width'     => [ 'type' => [ 'integer', 'null' ] ],
								'height'    => [ 'type' => [ 'integer', 'null' ] ],
								'date'      => [
									'type'        => 'string',
									'description' => __( 'When it was uploaded, Y-m-d.', 'groundhogg' ),
								],
							],
						],
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$type = $input['type'] ?? 'image';

		$query = new WP_Query( [
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => $type === 'any' ? '' : sanitize_key( $type ),
			's'              => sanitize_text_field( $input['search'] ?? '' ),
			'posts_per_page' => min( 50, max( 1, absint( $input['limit'] ?? 20 ) ) ),
			'paged'          => max( 1, absint( $input['page'] ?? 1 ) ),
			'orderby'        => 'date',
			'order'          => 'DESC',
		] );

		$items = array_map( function ( $post ) {

			$metadata = wp_get_attachment_metadata( $post->ID ) ?: [];

			return [
				'id'        => $post->ID,
				'url'       => wp_get_attachment_url( $post->ID ),
				'title'     => $post->post_title,
				'alt_text'  => (string) get_post_meta( $post->ID, '_wp_attachment_image_alt', true ),
				'caption'   => $post->post_excerpt,
				'mime_type' => $post->post_mime_type,
				'width'     => $metadata['width'] ?? null,
				'height'    => $metadata['height'] ?? null,
				'date'      => get_the_date( 'Y-m-d', $post ),
			];
		}, $query->posts );

		return [
			'total' => (int) $query->found_posts,
			'items' => $items,
		];
	}
}
