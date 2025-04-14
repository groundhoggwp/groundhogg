<?php

namespace Groundhogg\Api\V4;

use Groundhogg\Contact_Query;
use Groundhogg\Saved_Searches;
use function Groundhogg\sanitize_query_url_params;

class Searches_Api extends Base_Api {

	public function register_routes() {

		register_rest_route( self::NAME_SPACE, "/searches", [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read' ],
				'permission_callback' => [ $this, 'read_permissions_callback' ]
			],
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create' ],
				'permission_callback' => [ $this, 'create_permissions_callback' ]
			],
			[
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update' ],
				'permission_callback' => [ $this, 'update_permissions_callback' ]
			],
			[
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete' ],
				'permission_callback' => [ $this, 'delete_permissions_callback' ]
			],
		] );

		register_rest_route( self::NAME_SPACE, "/searches/(?P<id>[a-z0-9\-_]+)", [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read_single' ],
				'permission_callback' => [ $this, 'read_permissions_callback' ]
			],
			[
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update_single' ],
				'permission_callback' => [ $this, 'update_permissions_callback' ]
			],
			[
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_single' ],
				'permission_callback' => [ $this, 'delete_permissions_callback' ]
			],
		] );
	}

	/**
	 * Get all searches
	 *
	 * @return \WP_REST_Response
	 */
	public function read( \WP_REST_Request $request ) {

		$searches = array_values( Saved_Searches::instance()->get_all() );

		$term = $request->get_param( 'term' ) ?: $request->get_param( 'search' );

		if ( $term ) {
			$searches = array_filter( $searches, function ( $search ) use ( $term ) {
				return preg_match( "/{$term}/i", $search['name'] );
			} );
		}

		// Include contact counts
		if ( $request->has_param( 'counts' ) && $request->get_param( 'counts' ) ) {
			foreach ( $searches as &$search ) {
//				$time            = new Micro_Time_Tracker();
				$query           = new Contact_Query( $search['query'] );
				$search['count'] = $query->count();
//				$search['time']  = $time->time_elapsed_rounded();
			}

			// Sort by count descending
			usort( $searches, function ( $a, $b ) {
				return $b['count'] - $a['count'];
//				return $b['time'] - $a['time'];
			} );
		}

		return self::SUCCESS_RESPONSE( [
			'items'       => $searches,
			'total_items' => count( $searches )
		] );
	}

	/**
	 * Create a saved search
	 *
	 * @param $request
	 */
	public function create( \WP_REST_Request $request ) {

		$name     = sanitize_text_field( $request->get_param( 'name' ) );
		$query_id = uniqid( sanitize_title( $name ) . '-' );
		$query    = $request->get_param( 'query' );

		Saved_Searches::instance()->add( $query_id, [
			'name'  => $name,
			'id'    => $query_id,
			'query' => sanitize_query_url_params( $query ),
		] );

		return self::SUCCESS_RESPONSE( [
			'item' => Saved_Searches::instance()->get( $query_id )
		] );
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 */
	public function read_single( \WP_REST_Request $request ) {

		$search_id = $request->get_param( 'id' );

		return self::SUCCESS_RESPONSE( [
			'item' => Saved_Searches::instance()->get( $search_id )
		] );
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 */
	public function update_single( \WP_REST_Request $request ) {

		$search_id = $request->get_param( 'id' );

		$update = [];

		if ( $request->has_param( 'query' ) ){
			$update['query'] = sanitize_query_url_params( $request->get_param( 'query' ) ?: [] );
		}

		if ( $request->has_param( 'name' ) ){
			$update['name'] = sanitize_text_field( $request->get_param( 'name' ) );
		}

		Saved_Searches::instance()->update( $search_id, $update );

		return self::SUCCESS_RESPONSE( [
			'item' => Saved_Searches::instance()->get( $search_id )
		] );
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 */
	public function delete_single( \WP_REST_Request $request ) {

		$search_id = $request->get_param( 'id' );

		Saved_Searches::instance()->delete( $search_id );

		return self::SUCCESS_RESPONSE();
	}

	/**
	 * Permissions callback for read
	 *
	 * @return bool
	 */
	public function read_permissions_callback() {
		return current_user_can( 'view_contacts' );
	}

	/**
	 * Permissions callback for update
	 *
	 * @return mixed
	 */
	public function update_permissions_callback() {
		return current_user_can( 'edit_contacts' );
	}

	/**
	 * Permissions callback for create
	 *
	 * @return mixed
	 */
	public function create_permissions_callback() {
		return current_user_can( 'add_contacts' );
	}

	/**
	 * Permissions callback for delete
	 *
	 * @return mixed
	 */
	public function delete_permissions_callback() {
		return current_user_can( 'delete_contacts' );
	}
}
