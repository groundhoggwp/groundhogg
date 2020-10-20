<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
use Groundhogg\Funnel;
use Groundhogg\Step;
use WP_REST_Server;
use function Groundhogg\sanitize_object_meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Funnels_Api extends Base_Object_Api {

	public function register_routes() {
		parent::register_routes();

		register_rest_route( self::NAME_SPACE, "/funnels/(?P<ID>\d+)/duplicate", [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'duplicate' ],
				'permission_callback' => [ $this, 'create_permissions_callback' ]
			],
		] );

		register_rest_route( self::NAME_SPACE, "/funnels/(?P<ID>\d+)/step/(?P<step_id>\d+)?", [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_step' ],
				'permission_callback' => [ $this, 'create_permissions_callback' ]
			],
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update_step' ],
				'permission_callback' => [ $this, 'update_permissions_callback' ]
			],
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_step' ],
				'permission_callback' => [ $this, 'delete_permissions_callback' ]
			],
		] );
	}

	/**
	 * Create a step for a funnel
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 */
	public function create_step( \WP_REST_Request $request ){

		$funnel_id = absint( $request->get_param( 'ID' ) );

		$data = $request->get_param( 'data' );
		$meta = $request->get_param( 'meta' );

		$step = new Step( $data );

		foreach ( $meta as $key => $value ){
			$step->update_meta( sanitize_key( $key ), sanitize_object_meta( $value ) );
		}

		// Add parent and child associations of new step
		foreach ( $step->get_parent_steps() as $parent ){
			$parent->add_child_step( $step );

			foreach ( $step->get_child_steps() as $child ) {
				$child->add_parent_step( $step );

				// remove all the associations of parents and children
				$parent->remove_child_step( $child );
				$child->remove_parent_step( $parent );
			}
		}

		$funnel = new Funnel( $funnel_id );

		return self::SUCCESS_RESPONSE( [
			'item' => $funnel
		] );
	}

	/**
	 * Update a step in the funnel
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 */
	public function update_step( \WP_REST_Request $request ){

		$funnel_id = absint( $request->get_param( 'ID' ) );
		$step_id   = absint( $request->get_param( 'step_id' ) );

		$data = $request->get_param( 'data' );
		$meta = $request->get_param( 'meta' );

		$step = new Step( $step_id );

		$step->update( $data );

		foreach ( $meta as $key => $value ){
			$step->update_meta( sanitize_key( $key ), sanitize_object_meta( $value ) );
		}

		// Add parent and child associations of new step
		foreach ( $step->get_parent_steps() as $parent ){
			$parent->add_child_step( $step );

			foreach ( $step->get_child_steps() as $child ) {
				$child->add_parent_step( $step );

				// remove all the associations of parents and children
				$parent->remove_child_step( $child );
				$child->remove_parent_step( $parent );
			}
		}

		$funnel = new Funnel( $funnel_id );

		return self::SUCCESS_RESPONSE( [
			'item' => $funnel
		] );
	}

	/**
	 * Delete a step from the funnel
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 */
	public function delete_step( \WP_REST_Request $request ){

		$funnel_id = absint( $request->get_param( 'ID' ) );
		$step_id   = absint( $request->get_param( 'step_id' ) );

		$step = new Step( $step_id );

		// Add likewise associations of parent to child
		foreach ( $step->get_parent_steps() as $parent ){
			foreach ( $step->get_child_steps() as $child ) {
				$parent->add_child_step( $child );
				$child->add_parent_step( $parent );
			}
		}

		$step->delete();

		$funnel = new Funnel( $funnel_id );

		return self::SUCCESS_RESPONSE( [
			'item' => $funnel
		] );
	}

	/**
	 * Duplicate the funnel
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function duplicate( \WP_REST_Request $request ){

		$ID = absint( $request->get_param( 'ID' ) );
		$funnel = new Funnel( $ID );

		if ( ! $funnel->exists() ){
			return self::ERROR_404();
		}

		$new_funnel = new Funnel();

		$new_funnel->import( $funnel->export() );

		return self::SUCCESS_RESPONSE( [
			'item' => $new_funnel
		] );
	}

	/**
	 * The name of the table resource to use
	 *
	 * @return string
	 */
	public function get_db_table_name() {
		return 'funnels';
	}

	/**
	 * Permissions callback for read
	 *
	 * @return bool
	 */
	public function read_permissions_callback() {
		return current_user_can( 'view_funnels' );
	}

	/**
	 * Permissions callback for update
	 *
	 * @return mixed
	 */
	public function update_permissions_callback() {
		return current_user_can( 'edit_funnels' );
	}

	/**
	 * Permissions callback for create
	 *
	 * @return mixed
	 */
	public function create_permissions_callback() {
		return current_user_can( 'add_funnels' );
	}

	/**
	 * Permissions callback for delete
	 *
	 * @return mixed
	 */
	public function delete_permissions_callback() {
		return current_user_can( 'delete_funnels' );
	}
}