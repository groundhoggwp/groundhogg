<?php

namespace Groundhogg;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Thumbnails for the email editor's video block
 *
 * Email clients can't play video or reliably layer one image over another, so the block shows a still: the video's
 * thumbnail with a play button and/or a progress bar drawn on it, saved as a JPEG in the uploads folder and linked to
 * the video. YouTube thumbnails come straight from YouTube, the others (Vimeo, Wistia, Rumble, or any oEmbed provider WordPress
 * knows that has thumbnails) from oEmbed.
 *
 * The file name is a hash of the thumbnail and the overlay options, so making the same image again returns the same
 * URL, and a changed thumbnail gets a new URL that email clients haven't cached.
 */
class Email_Video_Thumbnail {

	/**
	 * Changes when the drawing changes, so images made by an older version are made again
	 */
	const VERSION = 1;

	/**
	 * Width of the images, twice the usual email width so they're sharp on high density screens
	 */
	const WIDTH = 1200;

	/**
	 * Overlays are drawn this many times bigger and then scaled down, which smooths their edges
	 */
	const SCALE = 4;

	const PLAY_BUTTONS = [ 'none', 'circle', 'outline', 'pill', 'box', 'triangle' ];

	const PROGRESS_BARS = [ 'none', 'line', 'scrubber', 'player' ];

	/**
	 * @return array
	 */
	public static function default_options(): array {
		return [
			'playButton'  => [
				'style'     => 'circle',
				'color'     => '#000000',
				'iconColor' => '#ffffff',
				'size'      => 18,
				'opacity'   => 70,
			],
			'progressBar' => [
				'style'    => 'none',
				'color'    => '#ff0000',
				'progress' => 30,
			],
			'darken'      => 0,
		];
	}

	/**
	 * Overlay options with anything missing or invalid replaced by the defaults
	 *
	 * @param array $options playButton, progressBar, darken
	 *
	 * @return array
	 */
	public static function normalize_options( array $options ): array {

		$defaults = self::default_options();

		$play     = wp_parse_args( (array) ( $options['playButton'] ?? [] ), $defaults['playButton'] );
		$progress = wp_parse_args( (array) ( $options['progressBar'] ?? [] ), $defaults['progressBar'] );

		return [
			'playButton'  => [
				'style'     => one_of( $play['style'], self::PLAY_BUTTONS ),
				'color'     => self::hex( $play['color'], $defaults['playButton']['color'] ),
				'iconColor' => self::hex( $play['iconColor'], $defaults['playButton']['iconColor'] ),
				'size'      => self::clamp( $play['size'], 5, 50 ),
				'opacity'   => self::clamp( $play['opacity'], 0, 100 ),
			],
			'progressBar' => [
				'style'    => one_of( $progress['style'], self::PROGRESS_BARS ),
				'color'    => self::hex( $progress['color'], $defaults['progressBar']['color'] ),
				'progress' => self::clamp( $progress['progress'], 0, 100 ),
			],
			'darken'      => self::clamp( $options['darken'] ?? 0, 0, 90 ),
		];
	}

	/**
	 * @param mixed $value
	 * @param int   $min
	 * @param int   $max
	 *
	 * @return int
	 */
	protected static function clamp( $value, int $min, int $max ): int {
		return max( $min, min( $max, (int) round( (float) $value ) ) );
	}

	/**
	 * A 6 digit hex color, from 3 or 6 digits
	 *
	 * @param mixed  $color
	 * @param string $default
	 *
	 * @return string
	 */
	protected static function hex( $color, string $default ): string {

		$color = sanitize_hex_color( is_string( $color ) ? trim( $color ) : '' );

		if ( ! $color ) {
			return $default;
		}

		if ( strlen( $color ) === 4 ) {
			$color = '#' . $color[1] . $color[1] . $color[2] . $color[2] . $color[3] . $color[3];
		}

		return strtolower( $color );
	}

	/**
	 * The id of a YouTube video from any of its URLs
	 *
	 * @param string $url
	 *
	 * @return string|false
	 */
	public static function youtube_id( string $url ) {

		$parts = wp_parse_url( trim( $url ) );

		if ( empty( $parts['host'] ) ) {
			return false;
		}

		$host = preg_replace( '/^(www\.|m\.|music\.)/', '', strtolower( $parts['host'] ) );
		$path = $parts['path'] ?? '';
		$id   = '';

		if ( $host === 'youtu.be' ) {
			$id = explode( '/', trim( $path, '/' ) )[0];
		} else if ( in_array( $host, [ 'youtube.com', 'youtube-nocookie.com' ], true ) ) {
			if ( untrailingslashit( $path ) === '/watch' ) {
				parse_str( $parts['query'] ?? '', $query );
				$id = is_string( $query['v'] ?? null ) ? $query['v'] : '';
			} else if ( preg_match( '#^/(?:embed|shorts|live|v)/([^/]+)#', $path, $matches ) ) {
				$id = $matches[1];
			}
		}

		return preg_match( '/^[A-Za-z0-9_-]{11}$/', $id ) ? $id : false;
	}

	/**
	 * oEmbed endpoints for video hosts WordPress doesn't know, by the pattern of their hosts
	 */
	const OEMBED_PROVIDERS = [
		'/(^|\.)(wistia\.com|wistia\.net|wi\.st)$/' => 'https://fast.wistia.net/oembed',
		'/(^|\.)rumble\.com$/'                       => 'https://rumble.com/api/Media/oembed.json',
	];

	/**
	 * A video's oEmbed data
	 *
	 * WordPress doesn't know Wistia or Rumble, so they're asked directly. Discovery is off because it would fetch any
	 * URL it's given, so only providers WordPress knows are asked otherwise.
	 *
	 * @param string $url
	 *
	 * @return object|false
	 */
	protected static function oembed( string $url ) {

		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );

		foreach ( self::OEMBED_PROVIDERS as $pattern => $endpoint ) {

			if ( ! preg_match( $pattern, $host ) ) {
				continue;
			}

			$response = wp_safe_remote_get( add_query_arg( 'url', rawurlencode( $url ), $endpoint ), [ 'timeout' => 15 ] );

			if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
				return false;
			}

			$data = json_decode( wp_remote_retrieve_body( $response ) );

			return is_object( $data ) ? $data : false;
		}

		return _wp_oembed_get_object()->get_data( $url, [ 'discover' => false ] );
	}

	/**
	 * What's known about a video: its provider, title and where its thumbnail is
	 *
	 * @param string $url
	 * @param bool   $refresh look it up again rather than using what was found before
	 *
	 * @return array|WP_Error key, provider, title, thumbnails (URLs to try in order), crop (whether the thumbnail may be
	 *                        letterboxed to 4:3)
	 */
	public static function get_video( string $url, bool $refresh = false ) {

		$url = esc_url_raw( trim( $url ) );

		if ( ! $url || ! wp_http_validate_url( $url ) ) {
			return new WP_Error( 'invalid_url', __( 'Enter the link to a YouTube, Vimeo, Wistia or Rumble video.', 'groundhogg' ) );
		}

		$cache_key = 'gh_email_video_' . md5( $url );

		if ( ! $refresh ) {
			$cached = get_transient( $cache_key );

			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$data = self::oembed( $url );

		$youtube_id = self::youtube_id( $url );

		if ( $youtube_id ) {

			$base = 'https://i.ytimg.com/vi/' . $youtube_id . '/';

			$video = [
				'key'        => 'youtube-' . $youtube_id,
				'provider'   => 'youtube',
				'title'      => $data && ! empty( $data->title ) ? (string) $data->title : '',
				// maxresdefault is 16:9 but only made for HD videos, the others are 4:3 with black bars
				'thumbnails' => [ $base . 'maxresdefault.jpg', $base . 'sddefault.jpg', $base . 'hqdefault.jpg' ],
				'crop'       => true,
			];

		} else {

			if ( ! $data || empty( $data->thumbnail_url ) ) {
				return new WP_Error( 'no_thumbnail', __( 'Couldn\'t get a thumbnail for that link. Use a link to a public YouTube, Vimeo, Wistia or Rumble video.', 'groundhogg' ) );
			}

			$provider  = sanitize_key( strtolower( preg_replace( '/[^A-Za-z0-9]+.*$/', '', (string) ( $data->provider_name ?? 'video' ) ) ) ) ?: 'video';
			$thumbnail = (string) $data->thumbnail_url;
			$larger    = $thumbnail;

			switch ( $provider ) {
				case 'vimeo':
					// https://i.vimeocdn.com/video/452001751-8216e05...-d_640x360?region=us
					$larger = preg_replace( '/-d_\d+(x\d+)?/', '-d_1280', $thumbnail );
					break;
				case 'wistia':
					// https://embed-ssl.wistia.com/deliveries/2d2c14e1....jpg?image_crop_resized=960x540
					$larger = add_query_arg( 'image_resize', 1280, remove_query_arg( 'image_crop_resized', $thumbnail ) );
					break;
			}

			$video = [
				'key'        => $provider . '-' . substr( md5( $url ), 0, 12 ),
				'provider'   => $provider,
				'title'      => ! empty( $data->title ) ? (string) $data->title : '',
				'thumbnails' => array_values( array_unique( [ $larger, $thumbnail ] ) ),
				'crop'       => false,
			];
		}

		set_transient( $cache_key, $video, DAY_IN_SECONDS );

		return $video;
	}

	/**
	 * Where the images are saved: in the uploads folder, but not Groundhogg's, which isn't public
	 *
	 * @return array path, url
	 */
	public static function output_dir(): array {

		$uploads = wp_get_upload_dir();

		/**
		 * Where the email video block's thumbnails are saved
		 *
		 * @param array $dir path and url of the folder
		 */
		return apply_filters( 'groundhogg/email/video_thumbnails/dir', [
			'path' => wp_normalize_path( $uploads['basedir'] . '/email-video-thumbnails' ),
			'url'  => $uploads['baseurl'] . '/email-video-thumbnails',
		] );
	}

	/**
	 * Make the thumbnail of a video with overlays drawn on it
	 *
	 * @param string $url       the video's link
	 * @param array  $options   playButton, progressBar, darken, see normalize_options()
	 * @param string $thumbnail an image to use instead of the video's thumbnail
	 * @param bool   $refresh   get the video's thumbnail again rather than using the one saved before
	 *
	 * @return array|WP_Error src, width, height, title, provider
	 */
	public static function make( string $url, array $options = [], string $thumbnail = '', bool $refresh = false ) {

		$video = self::get_video( $url, $refresh );

		if ( is_wp_error( $video ) ) {
			return $video;
		}

		$thumbnail = esc_url_raw( trim( $thumbnail ) );
		$options   = self::normalize_options( $options );

		$source = $thumbnail
			? self::download( 'custom-' . md5( $thumbnail ), [ $thumbnail ], $refresh )
			: self::download( $video['key'], $video['thumbnails'], $refresh );

		if ( is_wp_error( $source ) ) {
			return $source;
		}

		$hash = substr( md5( md5_file( $source ) . wp_json_encode( $options ) . self::VERSION . ( $thumbnail ? '' : (int) $video['crop'] ) ), 0, 12 );
		$file = $video['key'] . '-' . $hash . '.jpg';
		$dir  = self::output_dir();
		$path = $dir['path'] . '/' . $file;

		$result = [
			'src'      => $dir['url'] . '/' . $file,
			'title'    => $video['title'],
			'provider' => $video['provider'],
		];

		if ( ! file_exists( $path ) ) {

			$image = self::compose( $source, $options, $video['crop'] && ! $thumbnail );

			if ( is_wp_error( $image ) ) {
				return $image;
			}

			ob_start();
			imagejpeg( $image, null, 85 );
			$jpeg = ob_get_clean();

			if ( ! wp_mkdir_p( $dir['path'] ) || ! files()->put( $path, $jpeg ) ) {
				return new WP_Error( 'not_saved', __( 'Couldn\'t save the thumbnail in the uploads folder. Check that it\'s writable.', 'groundhogg' ) );
			}
		}

		$size = wp_getimagesize( $path );

		$result['width']  = $size ? $size[0] : self::WIDTH;
		$result['height'] = $size ? $size[1] : 0;

		return $result;
	}

	/**
	 * Download a thumbnail, or use the copy saved before
	 *
	 * @param string   $key     what it's saved as
	 * @param string[] $urls    where it might be, the first that's an image is used
	 * @param bool     $refresh download it again
	 *
	 * @return string|WP_Error the path of the saved copy
	 */
	protected static function download( string $key, array $urls, bool $refresh ) {

		// the originals are kept in Groundhogg's uploads folder, which isn't public
		$path = files()->get_uploads_dir( 'video-thumbnails', sanitize_file_name( $key ) . '.img', true );

		if ( ! $path ) {
			return new WP_Error( 'invalid_path', __( 'Couldn\'t save the thumbnail.', 'groundhogg' ) );
		}

		if ( ! $refresh && file_exists( $path ) ) {
			return $path;
		}

		files()->mk_dir();

		foreach ( $urls as $url ) {

			$response = wp_safe_remote_get( $url, [ 'timeout' => 15 ] );

			if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
				continue;
			}

			$body = wp_remote_retrieve_body( $response );
			$size = $body ? getimagesizefromstring( $body ) : false;

			// YouTube's "no thumbnail" image is 120x90
			if ( ! $size || $size[0] <= 120 ) {
				continue;
			}

			if ( files()->put( $path, $body ) ) {
				return $path;
			}

			return new WP_Error( 'not_saved', __( 'Couldn\'t save the thumbnail in the uploads folder. Check that it\'s writable.', 'groundhogg' ) );
		}

		return new WP_Error( 'no_thumbnail', __( 'Couldn\'t download the video\'s thumbnail.', 'groundhogg' ) );
	}

	/**
	 * Draw the overlays on a thumbnail
	 *
	 * @param string $source the thumbnail's path
	 * @param array  $options
	 * @param bool   $crop   crop 4:3 thumbnails to 16:9, removing YouTube's black bars
	 *
	 * @return resource|\GdImage|WP_Error
	 */
	public static function compose( string $source, array $options, bool $crop = false ) {

		if ( ! function_exists( 'imagecreatetruecolor' ) ) {
			return new WP_Error( 'no_gd', __( 'Your server needs the PHP GD extension to make video thumbnails.', 'groundhogg' ) );
		}

		$original = @imagecreatefromstring( (string) files()->get( $source ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- unknown image types warn

		if ( ! $original ) {
			return new WP_Error( 'invalid_image', __( 'The video\'s thumbnail isn\'t an image this server can read.', 'groundhogg' ) );
		}

		$options = self::normalize_options( $options );

		$src_w = imagesx( $original );
		$src_h = imagesy( $original );
		$src_y = 0;

		if ( $crop && abs( $src_w / $src_h - 4 / 3 ) < 0.02 ) {
			$cropped_h = (int) round( $src_w * 9 / 16 );
			$src_y     = (int) round( ( $src_h - $cropped_h ) / 2 );
			$src_h     = $cropped_h;
		}

		$width  = self::WIDTH;
		$height = (int) round( $src_h * $width / $src_w );

		$image = imagecreatetruecolor( $width, $height );
		imagecopyresampled( $image, $original, 0, 0, 0, $src_y, $width, $height, $src_w, $src_h );
		imagealphablending( $image, true );

		if ( $options['darken'] > 0 ) {
			imagefilledrectangle( $image, 0, 0, $width, $height, self::color( $image, '#000000', $options['darken'] ) );
		}

		self::draw_progress_bar( $image, $options['progressBar'] );
		self::draw_play_button( $image, $options['playButton'] );

		return $image;
	}

	/**
	 * Allocate a color
	 *
	 * @param resource|\GdImage $image
	 * @param string            $hex
	 * @param int|float         $opacity 0 to 100
	 *
	 * @return int
	 */
	protected static function color( $image, string $hex, $opacity = 100 ): int {

		$hex = ltrim( self::hex( $hex, '#000000' ), '#' );

		return imagecolorallocatealpha(
			$image,
			hexdec( substr( $hex, 0, 2 ) ),
			hexdec( substr( $hex, 2, 2 ) ),
			hexdec( substr( $hex, 4, 2 ) ),
			127 - (int) round( 127 * max( 0, min( 100, $opacity ) ) / 100 )
		);
	}

	/**
	 * Draw shapes on a part of the image, smoothly
	 *
	 * The draw callback gets a transparent canvas SCALE times the size of the area and a function that turns the
	 * area's coordinates into the canvas', and the canvas is scaled down onto the image.
	 *
	 * @param resource|\GdImage $image
	 * @param int               $x
	 * @param int               $y
	 * @param int               $w
	 * @param int               $h
	 * @param callable          $draw function( $canvas, callable $s )
	 *
	 * @return void
	 */
	protected static function layer( $image, int $x, int $y, int $w, int $h, callable $draw ) {

		$w = max( 1, $w );
		$h = max( 1, $h );

		$canvas = imagecreatetruecolor( $w * self::SCALE, $h * self::SCALE );
		imagealphablending( $canvas, false );
		imagefill( $canvas, 0, 0, imagecolorallocatealpha( $canvas, 0, 0, 0, 127 ) );
		imagealphablending( $canvas, true );

		$draw( $canvas, function ( $value ) {
			return $value * self::SCALE;
		} );

		$small = imagecreatetruecolor( $w, $h );
		imagealphablending( $small, false );
		imagesavealpha( $small, true );
		imagefill( $small, 0, 0, imagecolorallocatealpha( $small, 0, 0, 0, 127 ) );
		imagecopyresampled( $small, $canvas, 0, 0, 0, 0, $w, $h, $w * self::SCALE, $h * self::SCALE );

		imagealphablending( $image, true );
		imagecopy( $image, $small, $x, $y, 0, 0, $w, $h );
	}

	/**
	 * Fill a polygon, given as [ [x, y], ... ]
	 *
	 * @param resource|\GdImage $canvas
	 * @param array             $points
	 * @param int               $color
	 *
	 * @return void
	 */
	protected static function polygon( $canvas, array $points, int $color ) {

		$flat = [];

		foreach ( $points as $point ) {
			$flat[] = (int) round( $point[0] );
			$flat[] = (int) round( $point[1] );
		}

		if ( PHP_VERSION_ID >= 80000 ) {
			imagefilledpolygon( $canvas, $flat, $color );
		} else {
			imagefilledpolygon( $canvas, $flat, count( $points ), $color ); // phpcs:ignore PHPCompatibility.FunctionUse.OptionalToRequiredFunctionParameters -- PHP 7.4 needs the count
		}
	}

	/**
	 * Points around an arc
	 *
	 * @param float $cx
	 * @param float $cy
	 * @param float $r
	 * @param float $from  angle in degrees, clockwise from 3 o'clock
	 * @param float $to
	 * @param int   $steps
	 *
	 * @return array
	 */
	protected static function arc( float $cx, float $cy, float $r, float $from = 0, float $to = 360, int $steps = 96 ): array {

		$points = [];

		for ( $i = 0; $i <= $steps; $i ++ ) {
			$angle    = deg2rad( $from + ( $to - $from ) * $i / $steps );
			$points[] = [ $cx + $r * cos( $angle ), $cy + $r * sin( $angle ) ];
		}

		return $points;
	}

	/**
	 * A ring, or part of one, as one polygon so it's drawn without overlaps
	 *
	 * @param float $cx
	 * @param float $cy
	 * @param float $inner
	 * @param float $outer
	 * @param float $from
	 * @param float $to
	 *
	 * @return array
	 */
	protected static function ring( float $cx, float $cy, float $inner, float $outer, float $from = 0, float $to = 360 ): array {
		return array_merge(
			self::arc( $cx, $cy, $outer, $from, $to ),
			array_reverse( self::arc( $cx, $cy, max( 0, $inner ), $from, $to ) )
		);
	}

	/**
	 * A rectangle with rounded corners
	 *
	 * @param float $x1
	 * @param float $y1
	 * @param float $x2
	 * @param float $y2
	 * @param float $r
	 *
	 * @return array
	 */
	protected static function rounded_rect( float $x1, float $y1, float $x2, float $y2, float $r ): array {

		$r = max( 0, min( $r, ( $x2 - $x1 ) / 2, ( $y2 - $y1 ) / 2 ) );

		return array_merge(
			self::arc( $x2 - $r, $y1 + $r, $r, 270, 360, 24 ),
			self::arc( $x2 - $r, $y2 - $r, $r, 0, 90, 24 ),
			self::arc( $x1 + $r, $y2 - $r, $r, 90, 180, 24 ),
			self::arc( $x1 + $r, $y1 + $r, $r, 180, 270, 24 )
		);
	}

	/**
	 * A play triangle, pointing right, which looks centered on cx, cy
	 *
	 * @param float $cx
	 * @param float $cy
	 * @param float $size its height
	 *
	 * @return array
	 */
	protected static function triangle( float $cx, float $cy, float $size ): array {

		$width = $size * 0.88;
		// centered on its centroid rather than its box, otherwise it looks too far left
		$left = $cx - $width / 3;

		return [
			[ $left, $cy - $size / 2 ],
			[ $left + $width, $cy ],
			[ $left, $cy + $size / 2 ],
		];
	}

	/**
	 * Grow a shape from its center, for shadows
	 *
	 * @param array $points
	 * @param float $cx
	 * @param float $cy
	 * @param float $factor
	 *
	 * @return array
	 */
	protected static function grow( array $points, float $cx, float $cy, float $factor ): array {
		return array_map( function ( $point ) use ( $cx, $cy, $factor ) {
			return [ $cx + ( $point[0] - $cx ) * $factor, $cy + ( $point[1] - $cy ) * $factor ];
		}, $points );
	}

	/**
	 * A soft shadow under a shape, so a white icon stands out on a light thumbnail
	 *
	 * @param resource|\GdImage $canvas
	 * @param array             $points
	 * @param float             $cx
	 * @param float             $cy
	 * @param float             $radius how big the shape is, to size the shadow
	 *
	 * @return void
	 */
	protected static function shadow( $canvas, array $points, float $cx, float $cy, float $radius ) {

		$layers = 10;
		$spread = $radius * 0.18;
		$color  = self::color( $canvas, '#000000', 6 );

		for ( $i = $layers; $i >= 1; $i -- ) {
			$factor = 1 + ( $spread * $i / $layers ) / max( 1, $radius );
			self::polygon( $canvas, self::grow( $points, $cx, $cy + $spread * 0.25, $factor ), $color );
		}
	}

	/**
	 * Draw the play button in the middle of the image
	 *
	 * @param resource|\GdImage $image
	 * @param array             $button style, color, iconColor, size (percent of the width), opacity
	 *
	 * @return void
	 */
	protected static function draw_play_button( $image, array $button ) {

		if ( $button['style'] === 'none' ) {
			return;
		}

		$width  = imagesx( $image );
		$height = imagesy( $image );

		// the button's height
		$size = $width * $button['size'] / 100;

		// the area it's drawn in, with room for the shadow
		$area_w = (int) ceil( $size * 1.8 );
		$area_h = (int) ceil( $size * 1.4 );
		$x      = (int) round( ( $width - $area_w ) / 2 );
		$y      = (int) round( ( $height - $area_h ) / 2 );

		self::layer( $image, $x, $y, $area_w, $area_h, function ( $canvas, $s ) use ( $button, $size, $area_w, $area_h ) {

			$cx   = $s( $area_w / 2 );
			$cy   = $s( $area_h / 2 );
			$size = $s( $size );
			$fill = self::color( $canvas, $button['color'], $button['opacity'] );
			$icon = self::color( $canvas, $button['iconColor'] );

			switch ( $button['style'] ) {

				case 'circle':
					self::polygon( $canvas, self::arc( $cx, $cy, $size / 2 ), $fill );
					self::polygon( $canvas, self::triangle( $cx, $cy, $size * 0.4 ), $icon );
					break;

				case 'outline':
					$ring = self::ring( $cx, $cy, $size / 2 - $size * 0.065, $size / 2 );
					self::shadow( $canvas, self::arc( $cx, $cy, $size / 2 ), $cx, $cy, $size / 2 );
					if ( $button['opacity'] > 0 ) {
						self::polygon( $canvas, self::arc( $cx, $cy, $size / 2 ), self::color( $canvas, $button['color'], $button['opacity'] * 0.5 ) );
					}
					self::polygon( $canvas, $ring, $icon );
					self::polygon( $canvas, self::triangle( $cx, $cy, $size * 0.4 ), $icon );
					break;

				case 'pill':
					$w = $size * 1.42;
					self::polygon( $canvas, self::rounded_rect( $cx - $w / 2, $cy - $size / 2, $cx + $w / 2, $cy + $size / 2, $size * 0.3 ), $fill );
					self::polygon( $canvas, self::triangle( $cx, $cy, $size * 0.42 ), $icon );
					break;

				case 'box':
					$w = $size * 1.6;
					self::polygon( $canvas, self::rounded_rect( $cx - $w / 2, $cy - $size / 2, $cx + $w / 2, $cy + $size / 2, $size * 0.1 ), $fill );
					self::polygon( $canvas, self::triangle( $cx, $cy, $size * 0.42 ), $icon );
					break;

				case 'triangle':
					$triangle = self::triangle( $cx, $cy, $size * 0.8 );
					self::shadow( $canvas, $triangle, $cx, $cy, $size * 0.4 );
					self::polygon( $canvas, $triangle, $icon );
					break;
			}
		} );
	}

	/**
	 * Draw the progress bar at the bottom of the image
	 *
	 * @param resource|\GdImage $image
	 * @param array             $bar style, color, progress (percent)
	 *
	 * @return void
	 */
	protected static function draw_progress_bar( $image, array $bar ) {

		if ( $bar['style'] === 'none' ) {
			return;
		}

		$width    = imagesx( $image );
		$height   = imagesy( $image );
		$progress = $bar['progress'] / 100;

		switch ( $bar['style'] ) {

			case 'line':
				$thickness = max( 4, (int) round( $height * 0.014 ) );

				self::layer( $image, 0, $height - $thickness, $width, $thickness, function ( $canvas, $s ) use ( $bar, $width, $thickness, $progress ) {
					imagefilledrectangle( $canvas, 0, 0, $s( $width ), $s( $thickness ), self::color( $canvas, '#ffffff', 40 ) );
					if ( $progress > 0 ) {
						imagefilledrectangle( $canvas, 0, 0, (int) round( $s( $width * $progress ) ), $s( $thickness ), self::color( $canvas, $bar['color'] ) );
					}
				} );

				break;

			case 'scrubber':
			case 'player':

				$is_player = $bar['style'] === 'player';
				$margin    = $width * 0.025;
				$thickness = max( 3, $height * 0.009 );
				$knob      = $thickness * 1.9;
				$strip     = (int) ceil( $height * ( $is_player ? 0.2 : 0.1 ) );
				$top       = $height - $strip;

				// darken the bottom so the controls show on light thumbnails
				$shade = $is_player ? 70 : 45;

				for ( $row = 0; $row < $strip; $row ++ ) {
					$opacity = $shade * pow( $row / $strip, 1.5 );
					imageline( $image, 0, $top + $row, $width, $top + $row, self::color( $image, '#000000', $opacity ) );
				}

				// the bar's center, from the top of the strip
				$bar_y = $is_player ? $strip * 0.38 : $strip * 0.6;

				self::layer( $image, 0, $top, $width, $strip, function ( $canvas, $s ) use ( $bar, $width, $strip, $margin, $thickness, $knob, $bar_y, $progress, $is_player ) {

					$left  = $s( $margin );
					$right = $s( $width - $margin );
					$y     = $s( $bar_y );
					$half  = $s( $thickness ) / 2;
					$end   = $left + ( $right - $left ) * $progress;
					$color = self::color( $canvas, $bar['color'] );
					$white = self::color( $canvas, '#ffffff' );

					self::polygon( $canvas, self::rounded_rect( $left, $y - $half, $right, $y + $half, $half ), self::color( $canvas, '#ffffff', 40 ) );

					if ( $progress > 0 ) {
						self::polygon( $canvas, self::rounded_rect( $left, $y - $half, max( $end, $left + $half * 2 ), $y + $half, $half ), $color );
					}

					self::polygon( $canvas, self::arc( $end, $y, $s( $knob ) ), $color );

					if ( ! $is_player ) {
						return;
					}

					// play, volume and full screen icons under the bar
					$icon = $s( $strip * 0.3 );
					$row  = $s( $strip * 0.72 );

					// play
					self::polygon( $canvas, self::triangle( $left + $icon * 0.4, $row, $icon * 0.85 ), $white );

					// volume: the speaker and two waves
					$vx = $left + $icon * 1.6;
					self::polygon( $canvas, [
						[ $vx, $row - $icon * 0.18 ],
						[ $vx + $icon * 0.22, $row - $icon * 0.18 ],
						[ $vx + $icon * 0.5, $row - $icon * 0.42 ],
						[ $vx + $icon * 0.5, $row + $icon * 0.42 ],
						[ $vx + $icon * 0.22, $row + $icon * 0.18 ],
						[ $vx, $row + $icon * 0.18 ],
					], $white );

					$wave = $icon * 0.07;
					self::polygon( $canvas, self::ring( $vx + $icon * 0.5, $row, $icon * 0.28 - $wave, $icon * 0.28 + $wave, -45, 45 ), $white );
					self::polygon( $canvas, self::ring( $vx + $icon * 0.5, $row, $icon * 0.52 - $wave, $icon * 0.52 + $wave, -45, 45 ), $white );

					// full screen: four corners
					$fx   = $right - $icon * 0.45;
					$half = $icon * 0.4;
					$arm  = $icon * 0.28;
					$line = $icon * 0.12;

					foreach ( [ [ -1, -1 ], [ 1, -1 ], [ 1, 1 ], [ -1, 1 ] ] as $corner ) {

						list( $dx, $dy ) = $corner;

						$cx = $fx + $dx * $half;
						$cy = $row + $dy * $half;

						self::polygon( $canvas, [
							[ $cx, $cy ],
							[ $cx - $dx * $arm, $cy ],
							[ $cx - $dx * $arm, $cy - $dy * $line ],
							[ $cx - $dx * $line, $cy - $dy * $line ],
							[ $cx - $dx * $line, $cy - $dy * $arm ],
							[ $cx, $cy - $dy * $arm ],
						], $white );
					}
				} );

				break;
		}
	}
}
