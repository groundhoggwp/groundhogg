<?php

namespace Groundhogg\Api\V4;

use function Groundhogg\array_map_keys;
use function Groundhogg\array_map_with_keys;

class Options_Api extends Base_Api{

	public function register_routes() {

		register_rest_route( self::NAME_SPACE, "/options", [
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'update' ],
				'permission_callback' => [ $this, 'permissions_callback' ]
			],
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read' ],
				'permission_callback' => [ $this, 'permissions_callback' ]
			],
			[
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update' ],
				'permission_callback' => [ $this, 'permissions_callback' ]
			],
			[
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete' ],
				'permission_callback' => [ $this, 'permissions_callback' ]
			],
		] );
	}

	/**
	 * Only users with manage_options cap can use this
	 *
	 * @return bool
	 */
	public function permissions_callback(){
		return current_user_can( 'manage_options' );
	}

	public static function ERROR_UPDATING_GLOBAL_OPTIONS_UNSUPPORTED() {
		return self::ERROR_400( 'global_options_unsupported', 'Updating non-Groundhogg options is not supported' );
	}

	/**
	 * Update options
	 *
	 * Expects and key => value array of options
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function update( \WP_REST_Request $request ){

		$options = $request->get_params();

		foreach ( $options as $option => $value ){

			// not a groundhogg option, so we're going to ignore
			if ( ! str_starts_with( $option, 'gh_' ) ) {
				return self::ERROR_UPDATING_GLOBAL_OPTIONS_UNSUPPORTED();
			}

			/**
			 * Filter the callback to sanitize the option
			 *
			 * @param $callback callable
			 * @param $option string
			 * @prarm $value mixed
			 */
			$sanitize_func = apply_filters( 'groundhogg/api/v4/options_sanitize_callback', 'sanitize_text_field', $option, $value );

			update_option( sanitize_key( $option ), call_user_func( $sanitize_func, $value ) );
		}

		$options = array_map_with_keys( $options, function ( $v, $opt ){
			return get_option( $opt );
		} );

		return self::SUCCESS_RESPONSE([
			'items' => $options
		]);
	}

	/**
	 * Delete options
	 *
	 * Expects an array of option keys
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function delete( \WP_REST_Request $request ){

		$options = $request->get_params();

		foreach ( $options as $option ){

			// not a groundhogg option, so we're going to ignore
			if ( ! str_starts_with( $option, 'gh_' ) ) {
				return self::ERROR_UPDATING_GLOBAL_OPTIONS_UNSUPPORTED();
			}

			delete_option( $option );
		}

		return self::SUCCESS_RESPONSE();
	}

	/**
	 * Read options
	 *
	 * Expects an array of option keys
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function read( \WP_REST_Request $request ){

		$options = array_keys( $request->get_params() );

		if ( \Groundhogg\array_any( $options, fn( $option ) => ! str_starts_with( $option, 'gh_' ) ) ) {
			return self::ERROR_400( 'global_options_unsupported', 'Reading non-Groundhogg options is not supported' );
		}

		$values = array_map( fn( $option ) => get_option( $option ), $options );

		return self::SUCCESS_RESPONSE([
			'items' => array_combine( $options, $values )
		]);
	}
}
