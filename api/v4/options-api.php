<?php

namespace Groundhogg\Api\V4;

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

	/**
	 * Update options
	 *
	 * Expects and key => value array of options
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 */
	public function update( \WP_REST_Request $request ){

		$options = $request->get_params();

		foreach ( $options as $option => $value ){

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

		return self::SUCCESS_RESPONSE();
	}

	/**
	 * Delete options
	 *
	 * Expects an array of option keys
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 */
	public function delete( \WP_REST_Request $request ){

		$options = $request->get_params();

		foreach ( $options as $option ){
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
	 * @return \WP_REST_Response
	 */
	public function read( \WP_REST_Request $request ){

		$options = $request->get_params();

		return self::SUCCESS_RESPONSE([
			'options' => array_map( function ( $option ) { return get_option( $option ); }, $options )
		]);
	}
}
