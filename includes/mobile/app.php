<?php

namespace Groundhogg\Mobile;

use function Groundhogg\add_managed_rewrite_rule;
use function Groundhogg\is_managed_page;
use function Groundhogg\is_white_labeled;
use function Groundhogg\managed_page_url;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The mobile app entry point: a branded route under the managed page (`/gh/app/` by default).
 *
 * One server route serves the shell, and everything after the `#` is routed in the browser, so adding a
 * screen never needs a rewrite rule. The web app manifest and service worker are served from under the
 * same path, which gives the service worker the app as its scope without any extra header.
 */
class App {

	/**
	 * Bump when the rewrite rules below change, so sites flush once without waiting for an update routine.
	 */
	const REWRITES_VERSION = 2;

	/**
	 * @var Activity_Feed
	 */
	public $activity_feed;

	/**
	 * @var Recent
	 */
	public $recent;

	/**
	 * @var Promo
	 */
	public $promo;

	/**
	 * @var Task_Assignees
	 */
	public $task_assignees;

	public function __construct() {
		$this->activity_feed = new Activity_Feed();
		$this->recent        = new Recent();
		$this->promo         = new Promo();
		$this->task_assignees = new Task_Assignees();

		add_action( 'init', [ $this, 'add_rewrite_rules' ] );
		add_action( 'init', [ $this, 'maybe_flush_rewrite_rules' ], 99 );

		// Before Rewrites::template_redirect() and the template loader, which don't know these sub pages
		add_action( 'template_redirect', [ $this, 'template_redirect' ], 8 );
	}

	/**
	 * The domain the site is on, without a leading www. It is what tells this site's app from another's: someone
	 * with the app for several sites sees it on each home screen icon and at the top of every screen.
	 *
	 * @return string
	 */
	public static function domain() {

		$host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
		$host = preg_replace( '/^www\./', '', $host );

		// a site always has a host, but the app's name can never be blank
		return $host !== '' ? $host : strtolower( (string) wp_parse_url( network_home_url(), PHP_URL_HOST ) ?: 'localhost' );
	}

	/**
	 * The name the app goes by, on the home screen and in the title bar. It is the site's domain and nothing else,
	 * so that the same app on several sites reads as several different ones, whatever the site or the white label
	 * is called.
	 *
	 * @return string
	 */
	public static function name() {
		return self::domain();
	}

	/**
	 * A color for a domain: the same one every time, spread over the whole circle of hues from the domain's text, so that
	 * two sites rarely have the same. It is dark enough for white text to be read on it, whatever the hue is.
	 *
	 * @param string $domain
	 *
	 * @return string like #7a2f4b
	 */
	public static function color_for( string $domain ): string {

		$hue        = crc32( strtolower( $domain ) ) % 360;
		$saturation = 0.55;
		$lightness  = 0.30;

		// HSL to RGB
		$c = ( 1 - abs( 2 * $lightness - 1 ) ) * $saturation;
		$x = $c * ( 1 - abs( fmod( $hue / 60, 2 ) - 1 ) );
		$m = $lightness - $c / 2;

		$sector = (int) floor( $hue / 60 );
		$rgb    = [
			[ $c, $x, 0 ],
			[ $x, $c, 0 ],
			[ 0, $c, $x ],
			[ 0, $x, $c ],
			[ $x, 0, $c ],
			[ $c, 0, $x ],
		][ $sector % 6 ];

		return sprintf( '#%02x%02x%02x', round( ( $rgb[0] + $m ) * 255 ), round( ( $rgb[1] + $m ) * 255 ), round( ( $rgb[2] + $m ) * 255 ) );
	}

	/**
	 * The color of this site's app: the header, and what the phone paints around it.
	 *
	 * @return string
	 */
	public static function color(): string {
		return self::color_for( self::domain() );
	}

	/**
	 * @return string the app's URL, with a trailing slash
	 */
	public static function url() {
		return managed_page_url( 'app' );
	}

	public function add_rewrite_rules() {
		add_managed_rewrite_rule( 'app/?$', 'subpage=app' );
		add_managed_rewrite_rule( 'app/manifest\.webmanifest$', 'subpage=app_manifest' );
		add_managed_rewrite_rule( 'app/sw\.js$', 'subpage=app_sw' );
		add_managed_rewrite_rule( 'app/icon\.svg$', 'subpage=app_icon' );
	}

	/**
	 * Flush once per rules version, since an add-on style update routine would be easy to forget.
	 */
	public function maybe_flush_rewrite_rules() {

		$key = 'gh_mobile_rewrites_version';

		if ( (int) get_option( $key ) === self::REWRITES_VERSION ) {
			return;
		}

		flush_rewrite_rules( false );
		update_option( $key, self::REWRITES_VERSION, true );
	}

	/**
	 * @return bool whether script debug is on, which loads the unminified bundle
	 */
	protected static function dot_min() {
		return \Groundhogg\Plugin::$instance->scripts->is_script_debug_enabled() ? '' : '.min';
	}

	/**
	 * The version to put on the bundle's URLs. It includes the file's modified time, so a rebuilt bundle is
	 * never held back by a cache, the service worker's included, without waiting for a plugin release.
	 *
	 * @return string
	 */
	public static function asset_version() {
		$file = GROUNDHOGG_ASSETS_PATH . 'js/mobile/mobile' . self::dot_min() . '.js';

		return GROUNDHOGG_VERSION . ( file_exists( $file ) ? '.' . filemtime( $file ) : '' );
	}

	/**
	 * @return string
	 */
	public static function core_script_url() {
		return GROUNDHOGG_ASSETS_URL . 'js/mobile/mobile' . self::dot_min() . '.js';
	}

	/**
	 * @return string
	 */
	public static function core_style_url() {
		return GROUNDHOGG_ASSETS_URL . 'css/mobile/mobile.css';
	}

	public function template_redirect() {

		if ( ! is_managed_page() ) {
			return;
		}

		switch ( get_query_var( 'subpage' ) ) {
			case 'app':
				$this->render_shell();
				break;
			case 'app_manifest':
				$this->render_web_manifest();
				break;
			case 'app_sw':
				$this->render_service_worker();
				break;
			case 'app_icon':
				$this->render_icon();
				break;
		}
	}

	/**
	 * Headers that keep a CDN, a caching plugin or the browser from holding on to the shell, which carries
	 * a REST nonce and the user's manifest.
	 */
	protected function never_cache() {

		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}

		nocache_headers();
	}

	protected function render_shell() {

		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url( self::url() ) );
			exit;
		}

		$this->never_cache();

		// they have found their way here, so there is no need to tell them about the app any more
		Promo::record_use();

		$manifest = Manifest::build();

		// The user can't use anything the app offers
		if ( empty( $manifest['features']['core']['nav'] ) ) {
			wp_die(
				esc_html__( 'You do not have access to the mobile app.', 'groundhogg' ),
				esc_html__( 'No access', 'groundhogg' ),
				[ 'response' => 403 ]
			);
		}

		status_header( 200 );
		header( 'Content-Type: text/html; charset=utf-8' );

		include GROUNDHOGG_PATH . 'templates/mobile/shell.php';
		exit;
	}

	/**
	 * Icons for the install prompt and the home screen. A site icon wins, since it is the site's own mark.
	 *
	 * @param bool|null $white_labeled whether the brand's mark is kept out, by default whether the site is white labeled
	 *
	 * @return array[]
	 */
	public function get_icons( $white_labeled = null ) {

		$white_labeled = $white_labeled ?? is_white_labeled();

		$icons = [];

		foreach ( [ 192, 512 ] as $size ) {
			$url = get_site_icon_url( $size );

			if ( $url ) {
				$icons[] = [ 'src' => $url, 'sizes' => "{$size}x{$size}", 'type' => 'image/png', 'purpose' => 'any' ];
			}
		}

		if ( $icons ) {
			return $icons;
		}

		// Browsers need a square icon of 192 and 512 to offer to install the app. The brand's mark is the fallback for
		// a site that isn't white labeled, and a white labeled one gets its domain's letter in the app's color.
		if ( ! $white_labeled ) {
			$images = GROUNDHOGG_ASSETS_URL . 'images/mobile/';

			return [
				[ 'src' => $images . 'app-icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any' ],
				[ 'src' => $images . 'app-icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any' ],
				[ 'src' => $images . 'app-icon-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable' ],
			];
		}

		$svg = self::url() . 'icon.svg';

		return [
			[ 'src' => $svg, 'sizes' => 'any', 'type' => 'image/svg+xml', 'purpose' => 'any' ],
			[ 'src' => $svg, 'sizes' => 'any', 'type' => 'image/svg+xml', 'purpose' => 'maskable' ],
		];
	}

	/**
	 * The icon of a site that is white labeled and has no site icon: the first letter of its domain, in white on the
	 * app's color. The letter stays well inside the middle of the square, so a phone can crop it to a circle.
	 *
	 * @return string
	 */
	public static function generated_icon_svg(): string {

		$letter = strtoupper( mb_substr( self::domain(), 0, 1 ) );

		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">'
		       . '<rect width="512" height="512" fill="' . esc_attr( self::color() ) . '"/>'
		       . '<text x="256" y="256" fill="#ffffff" font-family="Helvetica, Arial, sans-serif" font-size="260" font-weight="700" text-anchor="middle" dominant-baseline="central">' . esc_html( $letter ) . '</text>'
		       . '</svg>';
	}

	/**
	 * @return string the URL of the icon the iOS home screen uses, empty when there isn't one to use (iOS can't use an SVG)
	 */
	public static function touch_icon_url() {
		$icon = get_site_icon_url( 180 );

		if ( $icon ) {
			return $icon;
		}

		return is_white_labeled() ? '' : GROUNDHOGG_ASSETS_URL . 'images/mobile/app-icon-180.png';
	}

	protected function render_icon() {

		header( 'Content-Type: image/svg+xml; charset=utf-8' );
		header( 'Cache-Control: public, max-age=3600' );

		echo self::generated_icon_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts
		exit;
	}

	protected function render_web_manifest() {

		$url = self::url();

		$data = [
			'name'             => self::name(),
			'short_name'       => self::name(),
			'description'      => __( 'Your contacts, tasks and deals on the go.', 'groundhogg' ),
			'id'               => $url,
			'start_url'        => $url,
			'scope'            => $url,
			'display'          => 'standalone',
			'orientation'      => 'portrait',
			'background_color' => '#ffffff',
			// the header is this color, so the phone's own bars match it
			'theme_color'      => self::color(),
			'icons'            => $this->get_icons(),
		];

		header( 'Content-Type: application/manifest+json; charset=utf-8' );
		header( 'Cache-Control: public, max-age=3600' );

		echo wp_json_encode( $data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON
		exit;
	}

	/**
	 * The service worker only caches the shell's own static files. Pages carry a nonce and REST data is live,
	 * so neither is ever served from a cache.
	 */
	protected function render_service_worker() {

		$file = GROUNDHOGG_PATH . 'assets/js/mobile/sw.js';

		if ( ! file_exists( $file ) ) {
			status_header( 404 );
			exit;
		}

		$config = [
			'version' => self::asset_version(),
			'assets'  => [
				self::core_script_url() . '?ver=' . self::asset_version(),
				self::core_style_url() . '?ver=' . self::asset_version(),
			],
		];

		header( 'Content-Type: application/javascript; charset=utf-8' );
		header( 'Cache-Control: no-cache' );

		echo 'self.GH_SW = ' . wp_json_encode( $config ) . ";\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON
		readfile( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- our own static file
		exit;
	}
}
