<?php

namespace Groundhogg\Mobile;

use function Groundhogg\add_managed_rewrite_rule;
use function Groundhogg\is_managed_page;
use function Groundhogg\is_white_labeled;
use function Groundhogg\managed_page_url;
use function Groundhogg\white_labeled_name;

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
	const REWRITES_VERSION = 1;

	/**
	 * @var Activity_Feed
	 */
	public $activity_feed;

	/**
	 * @var Recent
	 */
	public $recent;

	public function __construct() {
		$this->activity_feed = new Activity_Feed();
		$this->recent        = new Recent();

		add_action( 'init', [ $this, 'add_rewrite_rules' ] );
		add_action( 'init', [ $this, 'maybe_flush_rewrite_rules' ], 99 );

		// Before Rewrites::template_redirect() and the template loader, which don't know these sub pages
		add_action( 'template_redirect', [ $this, 'template_redirect' ], 8 );
	}

	/**
	 * The name the app goes by, on the home screen and in the title bar. It's the white label name when the site has
	 * one, and the site's own name when a white labeled site hasn't set a name, so it is never blank.
	 *
	 * @return string
	 */
	public static function name() {
		$name = white_labeled_name();

		return is_string( $name ) && trim( $name ) !== '' ? trim( $name ) : get_bloginfo( 'name', 'display' );
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
	 * @return array[]
	 */
	protected function get_icons() {

		$icons = [];

		foreach ( [ 192, 512 ] as $size ) {
			$url = get_site_icon_url( $size );

			if ( $url ) {
				$icons[] = [ 'src' => $url, 'sizes' => "{$size}x{$size}", 'type' => 'image/png', 'purpose' => 'any' ];
			}
		}

		// the brand's mark is only a fallback for a site that isn't white labeled
		if ( ! $icons && ! is_white_labeled() ) {
			$icons[] = [
				'src'   => GROUNDHOGG_ASSETS_URL . 'images/groundhogg-icon-small.png',
				'sizes' => '200x226',
				'type'  => 'image/png',
			];
		}

		return $icons;
	}

	/**
	 * @return string the URL of the icon the iOS home screen uses, empty when there isn't one to use
	 */
	public static function touch_icon_url() {
		$icon = get_site_icon_url( 180 );

		if ( $icon ) {
			return $icon;
		}

		return is_white_labeled() ? '' : GROUNDHOGG_ASSETS_URL . 'images/groundhogg-icon-small.png';
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
			'theme_color'      => '#ffffff',
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
