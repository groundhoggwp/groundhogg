<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Step;
use WP_REST_Server;
use function Groundhogg\get_array_var;
use function Groundhogg\get_db;
use function Groundhogg\is_template_site;
use function Groundhogg\isset_not_empty;
use function Groundhogg\map_func_to_attr;
use function Groundhogg\sanitize_object_meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Funnels_Api extends Base_Object_Api {

	/**
	 * register the commit route
	 *
	 * @return mixed|void
	 */
	public function register_routes() {
		parent::register_routes();

		$route = $this->get_route();
		$key   = $this->get_primary_key();

		register_rest_route( self::NAME_SPACE, "/{$route}/import", [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'import' ],
				'permission_callback' => [ $this, 'create_permissions_callback' ]
			],
		] );

		register_rest_route( self::NAME_SPACE, "/{$route}/(?P<{$key}>\d+)/commit", [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'commit' ],
				'permission_callback' => [ $this, 'update_permissions_callback' ]
			],
		] );

		register_rest_route( self::NAME_SPACE, "/{$route}/form-integration", [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'form_integration' ],
				'permission_callback' => [ $this, 'update_permissions_callback' ]
			],
		] );
	}

	/**
	 * Get the field mapping data for a form integration step
	 *
	 * @param \WP_REST_Request $request
	 */
	public function form_integration( \WP_REST_Request $request ) {

		$type = $request->get_param( 'type' );

		$step = Plugin::instance()->step_manager->elements[ $type ];

		if ( ! method_exists( $step, 'get_forms_for_api' ) ) {
			return self::ERROR_401();
		}

		$forms = $step->get_forms_for_api();

		return self::SUCCESS_RESPONSE( [
			'forms' => $forms,
		] );
	}

	/**
	 * Import the provided template, create the new funnel and return the item
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return bool|int|\WP_Error|\WP_REST_Response
	 */
	public function import( \WP_REST_Request $request ) {

		// Get the template
		$template = $request->get_json_params();

		// Is this a legacy funnel template or a new template?

		// New template, old templates does not have the 'data' prop
		if ( isset_not_empty( $template, 'data' ) ) {

			// Create the funnel
			$funnel = new Funnel();

			$funnel->create( [
				'title' => $template['data']['title']
			] );

			// Import the steps with their settings
			$steps = $template['steps'];

			// Import the steps
			foreach ( $steps as $_step ) {

				// Override the funnel ID to the newly created one
				$_step['data'] = array_merge( $_step['data'], [
					'funnel_id' => $funnel->get_id()
				] );

				$step = new Step();

				$step->create( $_step['data'] ); // use create method to ensure uniqueness
				$step->update_meta( $_step['meta'] ); // save all that meta data!
				$step->import( $_step['export'] ); // import any relevant exported information
			}

			// Etc...

		} // Old template from pre 2.5
		else {
			$funnel = new Funnel();
			$result = $funnel->legacy_import( $template );

			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		return self::SUCCESS_RESPONSE( [
			'item' => $funnel
		] );
	}

	/**
	 * Commit the funnel
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function commit( \WP_REST_Request $request ) {

		$funnel = new Funnel( $request->get_param( $this->get_primary_key() ) );

		$funnel->update_meta( $request->get_json_params() );

		// If the commit was successful, meaning no errors, return he updated funnel
		if ( $funnel->commit() ) {

			return self::SUCCESS_RESPONSE( [
				'item' => $funnel
			] );

		} // If the commit failed, return all the errors
		else {

			return self::ERROR_400( 'error', 'Unable to commit changes.', [
				'errors' => $funnel->get_errors(),
				'item'   => $funnel
			] );
		}

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
		return is_template_site() || current_user_can( 'export_funnels' );
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
