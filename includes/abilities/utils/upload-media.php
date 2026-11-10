<?php

namespace Groundhogg\Abilities\Utils;

use Groundhogg\Abilities\Ability;
use WP_Error;

/**
 * Uploads a file into the WordPress Media Library and returns its ID, URL,
 * and relative path - the missing piece for building an email (via
 * groundhogg/create-email-template or groundhogg/update-email-template) that
 * includes a real image rather than a placeholder: upload the image first,
 * then use the returned `url` as an `image` block's `src` (see
 * groundhogg/get-authoring-guide, topic "email") or a plain `<img>` src in
 * HTML content.
 *
 * Two ways in, and `url` is the one agents should use:
 *
 * - `url`: the site downloads the file itself (download_url(), which goes
 *   through wp_safe_remote_get(), so private and local addresses are refused)
 *   and sideloads it with media_handle_sideload(). An agent only has to pass
 *   an address. In practice language models can't produce base64 for a real
 *   image - they write something plausible that decodes to garbage - so `data`
 *   only works for a caller that has the actual bytes from a tool.
 * - `data`: the file's bytes, base64-encoded - abilities are called with a
 *   JSON payload, not a multipart form, so there's no "attach a file"
 *   mechanism to hook into. Image data is checked to really be an image
 *   before anything is saved, so a garbled upload fails loudly, pointing at
 *   `url`, instead of leaving a broken image in the Media Library.
 *
 * Either way it registers a real Media Library attachment - not just a file
 * dropped in uploads/ - so it behaves exactly like an image uploaded through
 * wp-admin (thumbnails/sizes generated, shows up in Media Library, has post
 * meta).
 *
 * Deliberately generic (not Groundhogg-specific) and filed under a
 * "Groundhogg Utilities" category rather than groundhogg-email - uploading a
 * file has nothing to do with email specifically, and the result is just as
 * usable as a contact's profile picture, an image block, or anything else
 * that takes a URL. See groundhogg/search-media for finding what's already
 * there.
 */
class Upload_Media extends Ability {

	protected const NAME       = 'groundhogg/upload-media';
	protected const CATEGORY   = 'groundhogg-utils';
	protected const CAPABILITY = 'upload_files';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = false;
	protected const IDEMPOTENT  = false;

	protected function get_args(): array {

		return [
			'label'       => __( 'Upload Media', 'groundhogg' ),
			'description' => __( 'Add a file (e.g. an image) to the WordPress Media Library. Give the `url` of a file that\'s online and the site downloads it - this is almost always what you want. Only use `data` (base64) when a tool gave you the file\'s actual bytes; never write base64 yourself, it won\'t be a valid file. Returns the attachment\'s id, URL, and relative path, for use as an image src elsewhere - e.g. an image block in groundhogg/create-email-template\'s content. Check groundhogg/search-media first for an image that\'s already there.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [
					'url'      => [
						'type'        => 'string',
						'format'      => 'uri',
						'description' => __( 'The public address of the file to download into the Media Library. Use this or `data`, not both.', 'groundhogg' ),
					],
					'data'     => [
						'type'        => 'string',
						'description' => __( 'The file\'s raw bytes, base64-encoded, only when you have them from a tool. Requires `filename`. Use this or `url`, not both.', 'groundhogg' ),
					],
					'filename' => [
						'type'        => 'string',
						'description' => __( 'The file name, with extension (e.g. "broadcast-calendar.png") - the extension determines the mime type and whether the upload is allowed at all (same allowlist as uploading through wp-admin). Required with `data`. With `url` it defaults to the `title`, or else the name in the address, with the extension of the file\'s actual type when the address has none.', 'groundhogg' ),
					],
					'title'    => [
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
					'id'            => [
						'type'        => 'integer',
						'description' => __( 'The Media Library attachment ID.', 'groundhogg' ),
					],
					'url'           => [
						'type'        => 'string',
						'description' => __( 'The full URL to the uploaded file - use this as an image src.', 'groundhogg' ),
					],
					'relative_path' => [
						'type'        => 'string',
						'description' => __( 'Path relative to the uploads directory (e.g. "2026/09/broadcast-calendar.png").', 'groundhogg' ),
					],
					'mime_type'     => [
						'type' => 'string',
					],
					'width'         => [
						'type'        => [ 'integer', 'null' ],
						'description' => __( 'Pixel width, for images. null for non-image files.', 'groundhogg' ),
					],
					'height'        => [
						'type'        => [ 'integer', 'null' ],
						'description' => __( 'Pixel height, for images. null for non-image files.', 'groundhogg' ),
					],
					'filesize'      => [
						'type'        => [ 'integer', 'null' ],
						'description' => __( 'File size in bytes.', 'groundhogg' ),
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$has_url  = ! empty( $input['url'] );
		$has_data = ! empty( $input['data'] );

		if ( $has_url === $has_data ) {
			return new WP_Error( 'groundhogg_invalid_input', __( 'Give either `url` or `data`, not both.', 'groundhogg' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$attachment_id = $has_url ? $this->from_url( $input ) : $this->from_data( $input );

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		if ( isset( $input['alt_text'] ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $input['alt_text'] ) );
		}

		$file     = get_attached_file( $attachment_id );
		$metadata = wp_get_attachment_metadata( $attachment_id ) ?: [];

		return [
			'id'            => $attachment_id,
			'url'           => wp_get_attachment_url( $attachment_id ),
			'relative_path' => _wp_relative_upload_path( $file ),
			'mime_type'     => get_post_mime_type( $attachment_id ),
			'width'         => $metadata['width'] ?? null,
			'height'        => $metadata['height'] ?? null,
			'filesize'      => filesize( $file ) ?: null,
		];
	}

	/**
	 * Download the file and add it to the Media Library.
	 *
	 * @param array $input
	 *
	 * @return int|WP_Error the attachment ID
	 */
	protected function from_url( array $input ) {

		$url = esc_url_raw( $input['url'] );

		if ( ! wp_http_validate_url( $url ) ) {
			return new WP_Error( 'groundhogg_invalid_url', __( '`url` must be a public http or https address.', 'groundhogg' ) );
		}

		$tmp = download_url( $url, 30 );

		if ( is_wp_error( $tmp ) ) {
			return new WP_Error( 'groundhogg_download_failed', sprintf(
			/* translators: %s: why the download failed */
				__( 'Could not download that file: %s', 'groundhogg' ),
				$tmp->get_error_message()
			) );
		}

		$too_large = $this->check_size( (int) filesize( $tmp ) );

		if ( is_wp_error( $too_large ) ) {
			wp_delete_file( $tmp );

			return $too_large;
		}

		$source = sanitize_file_name( $input['filename'] ?? wp_basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ) );

		// Plenty of image addresses have no extension (CDNs, placeholder services), so go by what was downloaded
		$extension = wp_check_filetype( $source )['ext'] ?: $this->extension_for( (string) wp_get_image_mime( $tmp ) );

		if ( ! $extension ) {
			wp_delete_file( $tmp );

			return new WP_Error(
				'groundhogg_disallowed_file_type',
				__( 'Could not tell what kind of file that is, or it\'s not a type this site accepts. Give a `filename` with the right extension.', 'groundhogg' )
			);
		}

		// Named after its title unless told otherwise, so the file says what it is instead of a CDN's hash or "unnamed-file"
		$name     = empty( $input['filename'] ) && ! empty( $input['title'] ) ? $input['title'] : pathinfo( $source, PATHINFO_FILENAME );
		$filename = sanitize_file_name( ( $name ?: 'image' ) . '.' . $extension );

		$attachment_id = media_handle_sideload( [
			'name'     => $filename,
			'tmp_name' => $tmp,
		], 0, isset( $input['title'] ) ? sanitize_text_field( $input['title'] ) : null );

		if ( is_wp_error( $attachment_id ) ) {
			wp_delete_file( $tmp );
		}

		return $attachment_id;
	}

	/**
	 * Write base64 bytes to the uploads folder and add them to the Media Library.
	 *
	 * This mirrors what media_handle_sideload() does, but starting from bytes written straight to their final
	 * destination via wp_upload_bits() rather than an existing temp file, which is what that function expects.
	 *
	 * @param array $input
	 *
	 * @return int|WP_Error the attachment ID
	 */
	protected function from_data( array $input ) {

		if ( empty( $input['filename'] ) ) {
			return new WP_Error( 'groundhogg_invalid_input', __( '`filename` is required with `data`.', 'groundhogg' ) );
		}

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
			return new WP_Error( 'groundhogg_invalid_data', __( '"data" is not valid, non-empty base64. If the file is online, give its `url` instead.', 'groundhogg' ) );
		}

		// Base64 that a language model wrote itself decodes fine but isn't an image, catch it before it's saved
		if ( strpos( $filetype['type'], 'image/' ) === 0 && $filetype['type'] !== 'image/svg+xml' && ! @getimagesizefromstring( $decoded ) ) {
			return new WP_Error( 'groundhogg_invalid_data', __( '"data" doesn\'t decode to a valid image. If the image is online, give its `url` instead and the site will download it.', 'groundhogg' ) );
		}

		$too_large = $this->check_size( strlen( $decoded ) );

		if ( is_wp_error( $too_large ) ) {
			return $too_large;
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
		$metadata = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );

		if ( ! is_wp_error( $metadata ) && ! empty( $metadata ) ) {
			wp_update_attachment_metadata( $attachment_id, $metadata );
		}

		return $attachment_id;
	}

	/**
	 * @param int $bytes
	 *
	 * @return true|WP_Error
	 */
	protected function check_size( int $bytes ) {

		$max_size = wp_max_upload_size();

		if ( $max_size && $bytes > $max_size ) {
			return new WP_Error(
				'groundhogg_file_too_large',
				sprintf(
				/* translators: %s: the maximum upload size, formatted (e.g. "8 MB") */
					__( 'That file is larger than this site\'s maximum upload size of %s.', 'groundhogg' ),
					size_format( $max_size )
				)
			);
		}

		return true;
	}

	/**
	 * The extension this site allows for a mime type, e.g. "jpg" for image/jpeg.
	 *
	 * @param string $mime
	 *
	 * @return string empty when the type isn't allowed
	 */
	protected function extension_for( string $mime ): string {

		if ( ! $mime ) {
			return '';
		}

		foreach ( get_allowed_mime_types() as $extensions => $type ) {
			if ( $type === $mime ) {
				return explode( '|', $extensions )[0];
			}
		}

		return '';
	}
}
