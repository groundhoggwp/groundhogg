<?php

namespace Groundhogg\AI\Jobs;

use Groundhogg\AI\Job;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Alt text for an image, so people using a screen reader, or whose email app blocks images, know what it shows.
 *
 * The image is sent from the Media Library when it's there, so it works on a site the provider can't reach, like a
 * local or staging one. Anything else is sent as its URL for the provider to fetch.
 */
class Alt_Text extends Job {

	/**
	 * The image types providers read
	 */
	const TYPES = [ 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ];

	/**
	 * Bigger files are sent as a smaller size from the Media Library, providers refuse anything much larger
	 */
	const MAX_BYTES = 4 * MB_IN_BYTES;

	public function get_name(): string {
		return 'alt-text';
	}

	public function get_label(): string {
		return __( 'Alt text', 'groundhogg' );
	}

	public function get_description(): string {
		return __( 'The "Write alt text" button under an image\'s alt text in the email editor.', 'groundhogg' );
	}

	public function get_capability(): string {
		return 'upload_files';
	}

	/**
	 * Takes the image's src, or attachment_id for one in the Media Library, and optionally context, a few words on
	 * where it's used, like the email's subject
	 *
	 * @param array $input
	 *
	 * @return array|WP_Error [ file, mime, context ]
	 */
	public function validate( array $input ) {

		$src           = esc_url_raw( (string) ( $input['src'] ?? '' ) );
		$attachment_id = absint( $input['attachment_id'] ?? 0 );

		if ( ! $attachment_id && $src ) {
			$attachment_id = attachment_url_to_postid( $src );
		}

		$file = '';

		if ( $attachment_id && wp_attachment_is_image( $attachment_id ) ) {
			$file = $this->local_file( $attachment_id, $src );
		}

		if ( ! $file && $src ) {
			$file = $this->uploads_path( $src );
		}

		if ( $file ) {
			$mime = wp_check_filetype( $file )['type'];
		} else if ( $src && wp_http_validate_url( $src ) ) {
			// one the provider fetches itself
			$file = $src;
			$mime = wp_check_filetype( strtok( $src, '?' ) )['type'] ?: $this->remote_type( $src );
		} else {
			return new WP_Error( 'ai_no_image', __( 'Choose an image first.', 'groundhogg' ) );
		}

		if ( ! in_array( $mime, self::TYPES, true ) ) {
			return new WP_Error( 'ai_image_type', __( 'Alt text can only be written for JPEG, PNG, GIF and WebP images.', 'groundhogg' ) );
		}

		return [
			'file'    => $file,
			'mime'    => $mime,
			'context' => substr( sanitize_text_field( (string) ( $input['context'] ?? '' ) ), 0, 200 ),
		];
	}

	/**
	 * The type of an image whose URL doesn't say, like many from image services and CDNs, from its host. Only public
	 * addresses are asked, see wp_safe_remote_head().
	 *
	 * @param string $src
	 *
	 * @return string
	 */
	protected function remote_type( string $src ): string {

		$response = wp_safe_remote_head( $src, [
			'timeout'     => 5,
			'redirection' => 3,
		] );

		if ( is_wp_error( $response ) ) {
			return '';
		}

		// image/png; charset=binary
		return strtolower( trim( strtok( (string) wp_remote_retrieve_header( $response, 'content-type' ), ';' ) ) );
	}

	/**
	 * The attachment's file, or a smaller size of it when it's too big to send
	 *
	 * @param int    $attachment_id
	 * @param string $src the size in use, when it's one of the attachment's
	 *
	 * @return string
	 */
	protected function local_file( int $attachment_id, string $src ): string {

		$file = (string) get_attached_file( $attachment_id );

		if ( ! $file || ! is_readable( $file ) ) {
			return '';
		}

		// the size the email uses, when it's a smaller one
		$size = $src ? $this->uploads_path( $src ) : '';

		if ( $size ) {
			$file = $size;
		}

		if ( filesize( $file ) <= self::MAX_BYTES ) {
			return $file;
		}

		$large = image_get_intermediate_size( $attachment_id, 'large' );

		if ( $large && ! empty( $large['path'] ) ) {
			$path = trailingslashit( wp_get_upload_dir()['basedir'] ) . $large['path'];

			if ( is_readable( $path ) && filesize( $path ) <= self::MAX_BYTES ) {
				return $path;
			}
		}

		return '';
	}

	/**
	 * The file in the uploads folder a URL points to, if it's there and not too big
	 *
	 * @param string $src
	 *
	 * @return string
	 */
	protected function uploads_path( string $src ): string {

		$uploads = wp_get_upload_dir();
		$base    = set_url_scheme( $uploads['baseurl'] );
		$src     = set_url_scheme( strtok( $src, '?' ) );

		if ( strpos( $src, trailingslashit( $base ) ) !== 0 ) {
			return '';
		}

		$relative = substr( $src, strlen( trailingslashit( $base ) ) );

		// nothing outside the uploads folder
		if ( strpos( $relative, '..' ) !== false ) {
			return '';
		}

		$path = trailingslashit( $uploads['basedir'] ) . rawurldecode( $relative );

		return is_readable( $path ) && filesize( $path ) <= self::MAX_BYTES ? $path : '';
	}

	public function prompt( array $input ) {

		$ask = $input['context']
			/* translators: %s: where the image is used, like an email's subject */
			? sprintf( __( 'Write alt text for this image. It appears in an email about: %s', 'groundhogg' ), $input['context'] )
			: __( 'Write alt text for this image. It appears in a marketing email.', 'groundhogg' );

		return $this->new_prompt()
		            ->using_system_instruction( 'You write alt text for images in emails, for people using screen readers and for email apps that block images. Describe what matters about the image in one short sentence of under 125 characters, in the language of the request. Don\'t start with "Image of" or "Picture of", and don\'t mention that it is an image unless that is the point. If it contains important words, include them. Reply with the alt text only, without quotes.' )
		            ->with_text( $ask )
		            ->with_file( $input['file'], $input['mime'] )
		            // no temperature: models that reason before they answer refuse one
		            ->using_max_tokens( 200 );
	}

	public function respond( $result, array $input ) {

		$alt = trim( wp_strip_all_tags( $result->toText() ) );

		// what models add despite being asked not to
		$alt = preg_replace( '/^(alt text|alt)\s*:\s*/i', '', $alt );
		$alt = trim( $alt, " \t\n\r\0\x0B\"'“”‘’" );

		if ( $alt === '' ) {
			return new WP_Error( 'ai_empty', __( 'The AI provider didn\'t write any alt text. Try again.', 'groundhogg' ) );
		}

		return [
			'alt' => mb_substr( $alt, 0, 250 ),
		];
	}
}
