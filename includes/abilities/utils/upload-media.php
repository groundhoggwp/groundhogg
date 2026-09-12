<?php

namespace Groundhogg\Abilities\Utils;

use Groundhogg\Abilities\Ability;
use WP_Error;

/**
 * Uploads a file into the WordPress Media Library and returns its ID, URL,
 * and relative path - the missing piece for building an email (via
 * groundhogg/create-email or groundhogg/update-email) that includes a real
 * image rather than a placeholder: upload the image first, then use the
 * returned `url` as an `image` block's `src` (see the groundhogg-block-email
 * skill) or a plain `<img>` src in HTML content.
 *
 * `data` is base64, not a raw file upload - abilities are called with a JSON
 * payload, not a multipart form, so there's no "attach a file" mechanism to
 * hook into. A caller that already has the bytes (a screenshot just taken, an
 * image downloaded from somewhere) base64-encodes them and sends them here.
 * If a caller instead has direct REST API access to this site, WordPress's
 * own POST /wp-json/wp/v2/media does the same thing without the ~33% base64
 * size overhead - this ability exists for callers that don't.
 *
 * Registers a real Media Library attachment - not just a file dropped in
 * uploads/ - so it behaves exactly like an image uploaded through wp-admin
 * (thumbnails/sizes generated, shows up in Media Library, has post meta).
 * This mirrors what media_handle_sideload() does, but starting from bytes
 * already written to their final destination via wp_upload_bits() rather
 * than an existing temp file, which is what that function expects.
 *
 * Deliberately generic (not Groundhogg-specific) and filed under a
 * "Groundhogg Utilities" category rather than groundhogg-email - uploading a
 * file has nothing to do with email specifically, and the result is just as
 * usable as a contact's profile picture, an image block, or anything else
 * that takes a URL.
 */
class Upload_Media extends Ability {

	protected const string NAME       = 'groundhogg/upload-media';
	protected const string CATEGORY   = 'groundhogg-utils';
	protected const string CAPABILITY = 'upload_files';

	protected const bool READONLY    = false;
	protected const bool DESTRUCTIVE = false;
	protected const bool IDEMPOTENT  = false;

	protected function get_args(): array {

		return [
			'label'       => __( 'Upload Media', 'groundhogg' ),
			'description' => __( 'Upload a file (e.g. an image) into the WordPress Media Library from base64 data. Returns the attachment\'s id, URL, and relative path, for use as an image src elsewhere - e.g. an image block in groundhogg/create-email\'s content.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'filename', 'data' ],
				'properties'           => [
					'filename' => [
						'type'        => 'string',
						'description' => __( 'The file name, with extension (e.g. "broadcast-calendar.png") - the extension determines the mime type and whether the upload is allowed at all (same allowlist as uploading through wp-admin).', 'groundhogg' ),
					],
					'data' => [
						'type'        => 'string',
						'description' => __( 'The file\'s raw bytes, base64-encoded.', 'groundhogg' ),
					],
					'title' => [
						'type'        => 'string',
						'description' => __( 'The attachment\'s title in the Media Library. Defaults to the filename without its extension.', 'groundhogg' ),
					],
					'alt_text' => [
						'type'        => 'string',
						'description' => __( 'Alt text, for images. Stored the same way as alt text set through the Media Library.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'id' => [
						'type'        => 'integer',
						'description' => __( 'The Media Library attachment ID.', 'groundhogg' ),
					],
					'url' => [
						'type'        => 'string',
						'description' => __( 'The full URL to the uploaded file - use this as an image src.', 'groundhogg' ),
					],
					'relative_path' => [
						'type'        => 'string',
						'description' => __( 'Path relative to the uploads directory (e.g. "2026/09/broadcast-calendar.png").', 'groundhogg' ),
					],
					'mime_type' => [
						'type' => 'string',
					],
					'width' => [
						'type'        => [ 'integer', 'null' ],
						'description' => __( 'Pixel width, for images. null for non-image files.', 'groundhogg' ),
					],
					'height' => [
						'type'        => [ 'integer', 'null' ],
						'description' => __( 'Pixel height, for images. null for non-image files.', 'groundhogg' ),
					],
					'filesize' => [
						'type'        => [ 'integer', 'null' ],
						'description' => __( 'File size in bytes.', 'groundhogg' ),
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$filename = sanitize_file_name( $input['filename'] );
		$filetype = wp_check_filetype( $filename );

		if ( empty( $filetype['type'] ) ) {
			return new WP_Error(
				'groundhogg_disallowed_file_type',
				__( 'That file extension is not allowed. This uses the same allowlist as uploading through wp-admin.', 'groundhogg' )
			);
		}

		$decoded = base64_decode( $input['data'], true );

		if ( $decoded === false || $decoded === '' ) {
			return new WP_Error( 'groundhogg_invalid_data', __( '"data" is not valid, non-empty base64.', 'groundhogg' ) );
		}

		$max_size = wp_max_upload_size();

		if ( $max_size && strlen( $decoded ) > $max_size ) {
			return new WP_Error(
				'groundhogg_file_too_large',
				sprintf(
					/* translators: %s: the maximum upload size, formatted (e.g. "8 MB") */
					__( 'That file is larger than this site\'s maximum upload size of %s.', 'groundhogg' ),
					size_format( $max_size )
				)
			);
		}

		$upload = wp_upload_bits( $filename, null, $decoded );

		if ( ! empty( $upload['error'] ) ) {
			return new WP_Error( 'groundhogg_upload_failed', $upload['error'] );
		}

		$attachment_id = wp_insert_attachment( [
			'post_mime_type' => $upload['type'],
			'post_title'     => isset( $input['title'] ) ? sanitize_text_field( $input['title'] ) : preg_replace( '/\.[^.]+$/', '', $filename ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		], $upload['file'], 0, true );

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		// Generates thumbnails/sizes for images, same as an upload through
		// wp-admin - not required to just reference the full-size url, but
		// skipping it would leave the attachment inconsistent with a normal
		// Media Library upload (no sizes, blank dimensions).
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$metadata = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );

		if ( ! is_wp_error( $metadata ) && ! empty( $metadata ) ) {
			wp_update_attachment_metadata( $attachment_id, $metadata );
		} else {
			$metadata = [];
		}

		if ( isset( $input['alt_text'] ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $input['alt_text'] ) );
		}

		return [
			'id'            => $attachment_id,
			'url'           => wp_get_attachment_url( $attachment_id ),
			'relative_path' => _wp_relative_upload_path( $upload['file'] ),
			'mime_type'     => $upload['type'],
			'width'         => $metadata['width'] ?? null,
			'height'        => $metadata['height'] ?? null,
			'filesize'      => filesize( $upload['file'] ) ?: null,
		];
	}
}
