<?php

namespace Groundhogg\Mobile;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The registry of mobile app features.
 *
 * Core and add-ons declare what they contribute to the mobile app here, on the
 * `groundhogg/mobile/register_features` action. The declaration is data only: ids, labels, capabilities
 * and ordering. The components that render each contribution are registered in JS under the same ids.
 *
 * Capabilities registered here decide what the user is **shown**. They never grant anything, the REST
 * endpoints a feature calls remain the security boundary. See docs/mobile-app-registry.md.
 */
class Features {

	/**
	 * Version of the contract between the shell and feature bundles. Additive changes don't bump it.
	 */
	const API_VERSION = 1;

	/**
	 * The extension points a feature can contribute to, and the keys each contribution may carry
	 * into the manifest. Anything else a contribution holds (a callable `cap`, say) stays on the server.
	 */
	const CONTRIBUTIONS = [
		'nav'              => [ 'id', 'label', 'icon', 'priority', 'route', 'placement', 'badge' ],
		'home_cards'       => [ 'id', 'label', 'priority' ],
		'contact_panels'   => [ 'id', 'label', 'icon', 'priority' ],
		'contact_actions'  => [ 'id', 'label', 'icon', 'priority', 'href' ],
		'quick_create'     => [ 'id', 'label', 'icon', 'priority' ],
		'search_providers' => [ 'id', 'label', 'icon', 'priority', 'route' ],
	];

	/**
	 * @var Features
	 */
	protected static $instance;

	/**
	 * @var array[] normalized features by id
	 */
	protected $features = [];

	/**
	 * @var bool whether the register action has fired
	 */
	protected $registered = false;

	/**
	 * @return Features
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register a feature.
	 *
	 * @param string $id   unique slug, immutable once shipped
	 * @param array  $args label, min_api_version, cap, script, style, and one list per extension point
	 *
	 * @return bool whether it was registered
	 */
	public function register( string $id, array $args = [] ) {

		$id = sanitize_key( $id );

		if ( ! $id ) {
			_doing_it_wrong( __METHOD__, 'A mobile feature needs an id.', '4.10' );

			return false;
		}

		if ( isset( $this->features[ $id ] ) ) {
			_doing_it_wrong( __METHOD__, sprintf( 'The mobile feature "%s" is already registered.', esc_html( $id ) ), '4.10' );

			return false;
		}

		$feature = [
			'id'              => $id,
			'label'           => (string) ( $args['label'] ?? $id ),
			'min_api_version' => (int) ( $args['min_api_version'] ?? 1 ),
			'cap'             => $args['cap'] ?? '',
			'script'          => self::normalize_asset( $args['script'] ?? null ),
			'style'           => self::normalize_asset( $args['style'] ?? null ),
		];

		foreach ( array_keys( self::CONTRIBUTIONS ) as $point ) {
			$feature[ $point ] = self::normalize_contributions( $point, (array) ( $args[ $point ] ?? [] ) );
		}

		$this->features[ $id ] = $feature;

		return true;
	}

	/**
	 * A script or style is `[ 'src' => '', 'version' => '', 'domain' => '' ]`. A plain string is the src.
	 *
	 * @param string|array|null $asset
	 *
	 * @return array|null
	 */
	protected static function normalize_asset( $asset ) {

		if ( is_string( $asset ) ) {
			$asset = [ 'src' => $asset ];
		}

		if ( ! is_array( $asset ) || empty( $asset['src'] ) ) {
			return null;
		}

		return [
			'src'     => esc_url_raw( $asset['src'] ),
			'version' => (string) ( $asset['version'] ?? '' ),
			'domain'  => (string) ( $asset['domain'] ?? '' ),
		];
	}

	/**
	 * @param string  $point
	 * @param array[] $contributions
	 *
	 * @return array[]
	 */
	protected static function normalize_contributions( string $point, array $contributions ) {

		$normalized = [];

		foreach ( $contributions as $contribution ) {

			if ( ! is_array( $contribution ) || empty( $contribution['id'] ) ) {
				continue;
			}

			$contribution['id']       = sanitize_key( $contribution['id'] );
			$contribution['label']    = (string) ( $contribution['label'] ?? '' );
			$contribution['priority'] = (int) ( $contribution['priority'] ?? 10 );
			$contribution['cap']      = $contribution['cap'] ?? '';

			if ( $point === 'nav' ) {
				$contribution['placement'] = ( $contribution['placement'] ?? 'more' ) === 'tab' ? 'tab' : 'more';
			}

			$normalized[ $contribution['id'] ] = $contribution;
		}

		return $normalized;
	}

	/**
	 * Every registered feature, after core and add-ons have had their say.
	 *
	 * @return array[]
	 */
	public function all() {

		if ( ! $this->registered ) {

			// set first, so a feature that reads the registry from inside the action can't loop
			$this->registered = true;

			Core_Features::register( $this );

			/**
			 * Add-ons register their mobile features here, never at file load or on a guessed `init` priority.
			 *
			 * @param Features $features
			 */
			do_action( 'groundhogg/mobile/register_features', $this );
		}

		return $this->features;
	}

	/**
	 * Whether a user passes a registration's capability requirement.
	 *
	 * @param string|string[]|callable $cap '' means anyone, an array means all of them, a callable gets the user id
	 * @param int                      $user_id
	 *
	 * @return bool
	 */
	public static function user_passes( $cap, int $user_id ) {

		if ( empty( $cap ) ) {
			return true;
		}

		if ( is_callable( $cap ) ) {
			return (bool) call_user_func( $cap, $user_id );
		}

		foreach ( (array) $cap as $required ) {
			if ( ! user_can( $user_id, $required ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * The capability names a requirement mentions, so the manifest can tell the client whether the user has them.
	 *
	 * @param mixed $cap
	 *
	 * @return string[]
	 */
	protected static function cap_names( $cap ) {
		return is_callable( $cap ) ? [] : array_filter( array_map( 'strval', (array) $cap ) );
	}

	/**
	 * The features a user can see, with only what is safe and useful to send to the client.
	 *
	 * Features the user can't use are dropped entirely, so their bundles never load. A feature that
	 * requires a newer mobile API than this core provides is dropped too.
	 *
	 * @param int $user_id defaults to the current user
	 *
	 * @return array{features: array[], caps: string[]}
	 */
	public function for_user( int $user_id = 0 ) {

		$user_id = $user_id ?: get_current_user_id();

		$visible = [];
		$caps    = [];

		foreach ( $this->all() as $id => $feature ) {

			if ( $feature['min_api_version'] > self::API_VERSION ) {

				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- debug only
					error_log( sprintf( 'Groundhogg mobile: feature "%s" needs mobile API %d, this is %d.', $id, $feature['min_api_version'], self::API_VERSION ) );
				}

				continue;
			}

			if ( ! self::user_passes( $feature['cap'], $user_id ) ) {
				continue;
			}

			$out = [
				'id'     => $id,
				'label'  => $feature['label'],
				'script' => $feature['script'],
				'style'  => $feature['style'],
			];

			$caps = array_merge( $caps, self::cap_names( $feature['cap'] ) );
			$any  = false;

			foreach ( self::CONTRIBUTIONS as $point => $keys ) {

				$kept = [];

				foreach ( $feature[ $point ] as $contribution ) {

					if ( ! self::user_passes( $contribution['cap'], $user_id ) ) {
						continue;
					}

					$caps   = array_merge( $caps, self::cap_names( $contribution['cap'] ) );
					$kept[] = array_intersect_key( $contribution, array_flip( $keys ) );
				}

				usort( $kept, function ( $a, $b ) {
					return [ $a['priority'], $a['id'] ] <=> [ $b['priority'], $b['id'] ];
				} );

				$out[ $point ] = $kept;
				$any           = $any || ! empty( $kept );
			}

			// nothing for this user to see
			if ( ! $any ) {
				continue;
			}

			$visible[ $id ] = $out;
		}

		return [
			'features' => $visible,
			'caps'     => array_values( array_unique( $caps ) ),
		];
	}
}
