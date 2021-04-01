<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
use Groundhogg\DB\Step_Edges;
use Groundhogg\Funnel;
use Groundhogg\Step;
use WP_REST_Server;
use function Groundhogg\get_array_var;
use function Groundhogg\get_db;
use function Groundhogg\map_func_to_attr;
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

		register_rest_route( self::NAME_SPACE, "/funnels/(?P<ID>\d+)/step/?(?P<step_id>\d+)?", [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_step' ],
				// 'permission_callback' => [ $this, 'create_permissions_callback' ]
			],
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update_step' ],
				// 'permission_callback' => [ $this, 'update_permissions_callback' ]
			],
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_step' ],
				// 'permission_callback' => [ $this, 'delete_permissions_callback' ]
			],
		] );
	}

	const NEW_STEP = 'new';

	/**
	 * @return Step_Edges
	 */
	function get_edges_db() {
		return get_db( 'step_edges' );
	}

	/**
	 * @param $edges
	 * @param $step Step
	 */
	function handle_edges( $edges, $step ) {

		$new_edges    = get_array_var( $edges, 'new', [] );
		$delete_edges = get_array_var( $edges, 'delete', [] );

		foreach ( $new_edges as $new_edge ) {

			$new_edge = wp_parse_args( $new_edge, [
				'from' => '',
				'to'   => '',
			] );

			$from_id = $new_edge['from'] === self::NEW_STEP ? $step->get_id() : absint( $new_edge['from'] );
			$to_id   = $new_edge['to'] === self::NEW_STEP ? $step->get_id() : absint( $new_edge['to'] );

			$this->get_edges_db()->add( [
				'funnel_id' => $step->get_funnel_id(),
				'from_id'   => $from_id,
				'to_id'     => $to_id,
			] );
		}

		foreach ( $delete_edges as $edge ) {

			$edge = wp_parse_args( $edge, [
				'from' => '',
				'to'   => '',
			] );

			map_func_to_attr( $edge, 'from', 'absint' );
			map_func_to_attr( $edge, 'to', 'absint' );

			$this->get_edges_db()->delete( [
				'funnel_id' => $step->get_funnel_id(),
				'from_id'   => $edge['from'],
				'to_id'     => $edge['to'],
			] );
		}
	}

	/**
	 * Create a step for a funnel
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function create_step( \WP_REST_Request $request ) {

		$funnel_id = absint( $request->get_param( 'ID' ) );
		$funnel    = new Funnel( $funnel_id );

		if ( ! $funnel->exists() ) {
			return self::ERROR_404();
		}

		$data = $request->get_param( 'data' );
		$meta = $request->get_param( 'meta' );

		$edges = $request->get_param( 'edges' );

		$data['funnel_id'] = $funnel_id;

		$step = new Step();
		$step->create( $data, $meta );

		$this->handle_edges( $edges, $step );

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
	public function update_step( \WP_REST_Request $request ) {

		$funnel_id = absint( $request->get_param( 'ID' ) );
		$step_id   = absint( $request->get_param( 'step_id' ) );

		$data = $request->get_param( 'data' );
		$meta = $request->get_param( 'meta' );

		$step = new Step( $step_id );

		$step->update( $data, $meta );

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
	public function delete_step( \WP_REST_Request $request ) {

		$funnel_id = absint( $request->get_param( 'ID' ) );
		$step_id   = absint( $request->get_param( 'step_id' ) );

		$step = new Step( $step_id );

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
	public function duplicate( \WP_REST_Request $request ) {

		$ID     = absint( $request->get_param( 'ID' ) );
		$funnel = new Funnel( $ID );

		if ( ! $funnel->exists() ) {
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
		return true;
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
