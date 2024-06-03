<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
use Groundhogg\Background_Tasks;
use Groundhogg\Campaign;
use Groundhogg\Contact_Query;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Step;
use Groundhogg\Utils\DateTimeHelper;
use WP_REST_Request;
use WP_REST_Server;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_object_ids;
use function Groundhogg\is_a_contact;
use function Groundhogg\is_template_site;

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

		register_rest_route( self::NAME_SPACE, "/{$route}/(?P<{$key}>\d+)/start", [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'add_contacts' ],
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
	 * Handle campaigns
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function update_single( WP_REST_Request $request ) {
		$primary_key = absint( $request->get_param( $this->get_primary_key() ) );

		$object = $this->create_new_object( $primary_key );

		if ( ! $object->exists() ) {
			return $this->ERROR_RESOURCE_NOT_FOUND();
		}

		$data = $request->get_param( 'data' );
		$meta = $request->get_param( 'meta' );

		$object->update( $data );

		// If the current object supports meta data...
		if ( method_exists( $object, 'update_meta' ) ) {
			$object->update_meta( $meta );
		}

		if ( $request->has_param( 'campaigns' ) ) {

			$campaigns        = wp_parse_id_list( $request->get_param( 'campaigns' ) );
			$has_campaigns    = get_object_ids( $object->get_related_objects( 'campaign' ) );
			$add_campaigns    = array_diff( $campaigns, $has_campaigns );
			$remove_campaigns = array_diff( $has_campaigns, $campaigns );

			if ( ! empty( $add_campaigns ) ) {
				foreach ( $add_campaigns as $campaign ) {
					$object->create_relationship( new Campaign( $campaign ) );
				}
			}

			if ( ! empty( $remove_campaigns ) ) {
				foreach ( $remove_campaigns as $campaign ) {
					$object->delete_relationship( new Campaign( $campaign ) );
				}
			}

		}

		$this->do_object_updated_action( $object );

		return self::SUCCESS_RESPONSE( [ 'item' => $object ] );
	}

	/**
	 * Add contacts to a funnel
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function add_contacts( \WP_REST_Request $request ) {
		$funnel = new Funnel( $request->get_param( $this->get_primary_key() ) );

		if ( ! $funnel->is_active() ) {
			return self::ERROR_401( 'error', 'Funnel is not active' );
		}

		// Search for contacts, may contain limit/offset
		$query_vars = $request->get_param( 'query' );
		$contact_id = $request->get_param( 'contact_id' );
		$step_id    = absint( $request->get_param( 'step_id' ) );

		$step = $step_id ? new Step( $step_id ) : new Step( $funnel->get_first_action_id() );

		if ( ! $step->exists() || $step->get_funnel_id() !== $funnel->get_id() ) {
			return self::ERROR_404( 'error', 'Given step not found', [
				'step_id' => $step_id
			] );
		}

		// Using the contact ID because it's only a single contact
		if ( $contact_id ){

			$contact = get_contactdata( $contact_id );

			if ( ! is_a_contact( $contact ) ){
				return self::ERROR_401( 'error', 'Given contact not found', [
					'contact_id' => $contact_id
				] );
			}

			$step->enqueue( $contact );

			return self::SUCCESS_RESPONSE();
		}

		// Doing it with batches and limit
		if ( isset( $query_vars['limit'] ) || isset( $query_vars['number'] ) ) {
			$query    = new Contact_Query();
			$contacts = $query->query( $query_vars, true );

			foreach ( $contacts as $contact ) {
				$step->enqueue( $contact );
			}

			return self::SUCCESS_RESPONSE( [
				'added' => count( $contacts )
			] );
		}

		$date = sanitize_text_field( $request->get_param( 'date' ) );
		$time = sanitize_text_field( $request->get_param( 'time' ) );
		$now  = filter_var( $request->get_param( 'now' ), FILTER_VALIDATE_BOOLEAN );

		// Then later
		if ( $date && $time && ! $now ) {
			try {
				$date = new DateTimeHelper( "$date $time", wp_timezone() );

				add_filter( 'groundhogg/background_tasks/schedule_time', function ( $when ) use ( $date ) {
					return $date->getTimestamp();
				} );
			} catch ( \Exception $e ){
				return self::ERROR_500( 'invalid_date', $e->getMessage() );
			}
		}

		$scheduled = Background_Tasks::add_contacts_to_funnel( $step->get_id(), $query_vars );

		if ( is_wp_error( $scheduled ) ){
			return $scheduled;
		}

		return self::SUCCESS_RESPONSE();
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

		$funnel = new Funnel();
		$result = $funnel->import( $template );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( ! $funnel->exists() ) {
			return self::ERROR_400();
		}

		return self::SUCCESS_RESPONSE( [
			'item' => $funnel,
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
		return is_template_site() || current_user_can( 'export_funnels' ) || current_user_can( 'view_funnels' );
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
