<?php

namespace Groundhogg\Api\V3;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Groundhogg\Admin\Dashboard\Dashboard_Widgets;
use Groundhogg\Funnel;
use Groundhogg\Step;
use phpDocumentor\Reflection\Types\Self_;
use function Groundhogg\get_db;
use Groundhogg\Plugin;
use function Groundhogg\get_request_var;
use function Groundhogg\show_groundhogg_branding;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

use Groundhogg\Admin\Dashboard\Widgets;

class Steps_Api extends Base {

	public function register_routes() {

		$callback = $this->get_auth_callback();

		register_rest_route( self::NAME_SPACE, '/steps', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => $callback,
				'callback'            => [ $this, 'create' ],
				'args'                => [
					'funnel_id' => [
						'required' => true,
					],
				]
			],
			[
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => $callback,
				'callback'            => [ $this, 'read' ],
			],
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'permission_callback' => $callback,
				'callback'            => [ $this, 'update' ],
			],
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'permission_callback' => $callback,
				'callback'            => [ $this, 'delete' ],
			],
		] );

	}

	/**
	 * Create a new step!
	 *
	 * Requires:
	 *  - step_type
	 *  - funnel_id
	 *  - settings (optional)
	 *  - order
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function create( WP_REST_Request $request ) {

		$funnel_id = absint( $request->get_param( 'funnel_id' ) );
		$duplicate = absint( $request->get_param( 'duplicate' ) );
		$after     = absint( $request->get_param( 'after' ) );
		$type      = sanitize_key( $request->get_param( 'type' ) );

		// Reorder the steps which will be coming after this new step
		$funnel = new Funnel( $funnel_id );

		if ( ! $funnel->exists() ) {
			return self::ERROR_404( 'error', 'funnel not found' );
		}

		$after_step = new Step( $after );
		$step_order = $after_step->get_order() + 1;

		$all_steps = $funnel->get_steps();

		foreach ( $all_steps as $step ) {
			if ( $step->get_order() < $step_order ) {
				continue;
			}

			$step->update( [
				'step_order' => $step->get_order() + 1
			] );
		}

		if ( $duplicate && $duplicate === $after_step->get_id() ){

			$step = new Step([
				'funnel_id'   => $after_step->get_funnel_id(),
				'step_title'  => sprintf( __( '%s - (copy)', 'groundhogg' ), $after_step->get_title() ),
				'step_type'   => $after_step->get_type(),
				'step_group'  => $after_step->get_group(),
				'step_status' => 'ready',
				'step_order'  => $step_order,
			]);

			$meta = $after_step->get_all_meta();

			foreach ( $meta as $key => $value ) {
				$step->update_meta( $key, $value );
			}

		} else {
			$elements = Plugin::$instance->step_manager->get_elements();

			$title      = $elements[ $type ]->get_name();
			$step_group = $elements[ $type ]->get_group();

			$step = new Step( [
				'funnel_id'  => $funnel_id,
				'step_title' => $title,
				'step_type'  => $type,
				'step_group' => $step_group,
				'step_order' => $step_order,
			] );
		}

		// reorder the steps.
		return self::SUCCESS_RESPONSE( [ 'step' => $step->get_as_array() ] );
	}

	public function read( WP_REST_Request $request ) {

		$step_id = absint( $request->get_param( 'step_id' ) );
		$step    = new Step( $step_id );

		if ( ! $step->exists() ) {
			return self::ERROR_404( 'error', 'Step not found.' );
		}

		return self::SUCCESS_RESPONSE( [ 'step' => $step->get_as_array() ] );
	}

	/**
	 * Update a step
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function update( WP_REST_Request $request ) {

		$step_id = absint( $request->get_param( 'step_id' ) );
		$step    = new Step( $step_id );

		if ( ! $step->exists() ) {
			return self::ERROR_404( 'error', 'Step not found.' );
		}

		// Update any root level arguments
		$args = $request->get_param( 'args' );

		if ( $args ) {
			$args = map_deep( $args, 'sanitize_text_field' );
			$step->update( $args );
		}

		// Update the step delay
		$delay = $request->get_param( 'delay' );

		if ( $delay ) {
			$delay = map_deep( $delay, 'sanitize_text_field' );
			$step->update_delay( $delay );
		}

		// Update the step delay
		$settings = $request->get_param( 'settings' );

		if ( ! empty( $settings ) ) {
			$step->save( $settings );
		}

		return self::SUCCESS_RESPONSE( [ 'step' => $step->get_as_array() ] );
	}

	public function delete( WP_REST_Request $request ) {

		$step_id = absint( $request->get_param( 'step_id' ) );

		$step = new Step( $step_id );

		if ( ! $step->exists() ) {
			return self::ERROR_404( 'error', 'step does not exist.' );
		}

		$funnel = $step->get_funnel();

		$step->delete();

		// Reorder the steps
		foreach ( $funnel->get_steps() as $i => $step ) {
			$step->update( [ 'step_order' => $i + 1 ] );
		}

		return self::SUCCESS_RESPONSE();

	}


}