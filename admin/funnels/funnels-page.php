<?php

namespace Groundhogg\Admin\Funnels;

use Groundhogg\Admin\Admin_Page;
use Groundhogg\Campaign;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Funnel;
use Groundhogg\Library;
use Groundhogg\Plugin;
use Groundhogg\Step;
use WP_Error;
use function Groundhogg\action_url;
use function Groundhogg\add_disable_emojis_action;
use function Groundhogg\admin_page_url;
use function Groundhogg\array_apply_callbacks;
use function Groundhogg\array_map_keys;
use function Groundhogg\check_lock;
use function Groundhogg\db;
use function Groundhogg\download_json;
use function Groundhogg\enqueue_email_block_editor_assets;
use function Groundhogg\enqueue_groundhogg_modal;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;
use function Groundhogg\get_post_var;
use function Groundhogg\get_request_var;
use function Groundhogg\get_sanitized_FILE;
use function Groundhogg\get_upload_wp_error;
use function Groundhogg\get_url_var;
use function Groundhogg\html;
use function Groundhogg\isset_not_empty;
use function Groundhogg\map_to_class;
use function Groundhogg\notices;
use function Groundhogg\one_of;
use function Groundhogg\use_edit_lock;
use function Groundhogg\verify_admin_ajax_nonce;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * View Funnels
 *
 * Allow the user to view & edit the funnels
 *
 * @since       0.1
 * @subpackage  Includes/Funnels
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @package     groundhogg
 */
class Funnels_Page extends Admin_Page {

	protected function get_current_action() {
		$action = parent::get_current_action();

		if ( $action === 'view' && db()->funnels->is_empty() ) {
			$action = 'add';
		}

		return $action;
	}

	protected function add_ajax_actions() {
		add_action( 'wp_ajax_gh_save_funnel_via_ajax', [ $this, 'ajax_save_funnel' ] );
		add_action( 'wp_ajax_gh_flow_simulate', [ $this, 'ajax_simulate' ] );

		add_action( 'wp_ajax_gh_funnel_editor_full_screen_preference', [
			$this,
			'update_user_full_screen_preference'
		] );
	}

	public function ajax_simulate() {

		if ( ! verify_admin_ajax_nonce() || ! current_user_can( 'edit_funnels' ) ) {
			wp_send_json_error();
		}

		$step_id    = absint( get_post_var( 'from' ) );
		$contact_id = absint( get_post_var( 'contact' ) );
		$dry        = filter_var( get_post_var( 'dry' ), FILTER_VALIDATE_BOOLEAN );
		$contact    = get_contactdata( $contact_id ?: false );
		$step       = new Step( $step_id );

		Simulator::simulate( $step, $contact, $dry );
	}

	/**
	 * Whether the editor should appear full screen or not
	 *
	 * @return void
	 */
	function update_user_full_screen_preference() {

		if ( ! verify_admin_ajax_nonce() || ! current_user_can( 'edit_funnels' ) ) {
			$this->wp_die_no_access();
		}

		$is_full_screen = filter_var( get_post_var( 'full_screen', false ), FILTER_VALIDATE_BOOLEAN );
		update_user_meta( get_current_user_id(), 'gh_funnel_editor_full_screen', $is_full_screen );

		wp_send_json( $is_full_screen );
	}

	public function admin_title( $admin_title, $title ) {
		switch ( $this->get_current_action() ) {
			case 'add':
				$admin_title = sprintf( "%s &lsaquo; %s", esc_html__( 'Add', 'groundhogg' ), $admin_title );
				break;
			case 'edit':
				$funnel_id = get_request_var( 'funnel' );
				$funnel    = new Funnel( absint( $funnel_id ) );

				if ( $funnel->exists() ) {
					$admin_title = sprintf( "%s &lsaquo; %s &lsaquo; %s", esc_html( $funnel->get_title() ), esc_html__( 'Edit', 'groundhogg' ), $admin_title );
				}

				break;
		}

		return $admin_title;
	}

	/**
	 * Get the current screen title based on the action
	 */
	public function get_title() {
		switch ( $this->get_current_action() ) {
			case 'add':
				return _x( 'Add Flow', 'page_title', 'groundhogg' );
			case 'edit':
				return _x( 'Edit Flow', 'page_title', 'groundhogg' );
			case 'view':
			default:
				return _x( 'Flows', 'page_title', 'groundhogg' );
		}
	}

	protected function get_title_actions() {

		if ( $this->get_current_action() === 'add' ) {
			return [
				[
					'link'   => action_url( 'start_from_scratch' ),
					'action' => esc_html__( 'Start from scratch', 'groundhogg' ),
					'target' => '_self',
				]
			];
		}

		return [
			[
				'link'   => $this->admin_url( [ 'action' => 'add' ] ),
				'action' => esc_html__( 'Add New', 'groundhogg' ),
				'target' => '_self',
			]
		];
	}

	/**
	 * Redirect to the add screen if no funnels are present.
	 */
	public function redirect_to_add() {
		if ( get_db( 'funnels' )->count() == 0 ) {
			wp_safe_redirect( $this->admin_url( [ 'action' => 'add' ] ) );
			exit;
		}
	}

	protected function add_additional_actions() {

		add_disable_emojis_action();

		if ( $this->is_current_page() && $this->get_current_action() === 'edit' ) {
			add_action( 'in_admin_header', array( $this, 'prevent_notices' ) );
			/* just need to enqueue it... */
			enqueue_groundhogg_modal();
		}

		add_action( "groundhogg/admin/gh_funnels/before", function () {
			if ( get_db( 'funnels' )->exists( [ 'status' => 'inactive' ] ) && ! get_db( 'funnels' )->exists( [ 'status' => 'active' ] ) ) {
				notices()->add( 'no_active_funnels', sprintf( '%s %s', esc_html__( 'You have no active flows.', 'groundhogg' ), html()->e( 'a', [
					'href' => admin_url( 'admin.php?page=gh_funnels&status=inactive' ),
				], esc_html__( 'Activate a flow!', 'groundhogg' ) ) ), 'warning' );
			}
		} );
	}

	public function get_slug() {
		return 'gh_funnels';
	}

	public function get_name() {
		return _x( 'Flows', 'page_title', 'groundhogg' );
	}

	public function get_cap() {
		return 'edit_funnels';
	}

	public function get_item_type() {
		return 'funnel';
	}

	public function get_priority() {
		return 50;
	}

	/**
	 * enqueue editor scripts
	 */
	public function scripts() {

		switch ( $this->get_current_action() ) {
			case 'edit':

				$funnel = new Funnel( get_url_var( 'funnel' ) );

				if ( ! $funnel->exists() ) {
					return;
				}

				wp_enqueue_editor();

				wp_enqueue_style( 'editor-buttons' );
				wp_enqueue_style( 'jquery-ui' );

				wp_enqueue_script( 'wplink' );

				wp_enqueue_script( 'jquery-ui-sortable' );
				wp_enqueue_script( 'jquery-ui-draggable' );

				wp_enqueue_style( 'groundhogg-admin-funnel-editor' );
				wp_enqueue_script( 'groundhogg-admin-funnel-editor' );

				$data = array_merge( $funnel->get_as_array(), [
					'id'                  => absint( get_request_var( 'funnel' ) ),
					'save_text'           => esc_html__( 'Update', 'groundhogg' ),
					'export_url'          => $funnel->export_url(),
					'is_active'           => $funnel->is_active(),
					'funnelTourDismissed' => notices()->is_dismissed( 'funnel-tour' ),
					'scratchFunnelURL'    => action_url( 'start_from_scratch' ),
					'is_editor'           => true,
				] );

				wp_add_inline_script( 'groundhogg-admin-funnel-editor', "var Funnel = " . wp_json_encode( $data ), 'before' );

				wp_enqueue_script( 'groundhogg-admin-replacements' );
				wp_enqueue_script( 'groundhogg-admin-funnel-steps' );
				wp_enqueue_style( 'groundhogg-admin-reporting' );

				add_filter( 'admin_body_class', function ( $class ) {

					$is_full_screen = get_user_meta( get_current_user_id(), 'gh_funnel_editor_full_screen', true );

					if ( $is_full_screen ) {
						$class .= ' gh-full-screen';
					}

					return $class;
				} );

				enqueue_email_block_editor_assets();

				use_edit_lock( $funnel );

				do_action( 'groundhogg/admin/funnels/editor_scripts' );

				break;
			case 'add':
				wp_enqueue_style( 'groundhogg-admin-element' );
				break;
			case 'view':
				$this->enqueue_table_filters( [
					'stringColumns' => [
						'title' => 'Title',
					],
					'dateColumns'   => [
						'date_created' => 'Date Created',
						'last_updated' => 'Last Updated',
					],
				] );

				$query = new Table_Query( 'funnels' );
				$query->setSelect( 'DISTINCT author' );
				$results = wp_parse_id_list( wp_list_pluck( $query->get_results(), 'author' ) );

				// todo add script to preview funnels like in the email editor
//				wp_enqueue_style( 'groundhogg-admin-funnel-editor' );
//				wp_enqueue_script( 'groundhogg-admin-funnel-editor' );
				wp_enqueue_script( 'groundhogg-admin-filter-funnels' );
				wp_add_inline_script( 'groundhogg-admin-filter-funnels', "Groundhogg.authors = " . wp_json_encode( $results ) );

				break;
		}

		wp_enqueue_style( 'groundhogg-admin' );
	}

	public function help() {
	}

	public function process_add_campaigns() {
		if ( ! current_user_can( 'manage_campaigns' ) ) {
			$this->wp_die_no_access();
		}

		$campaigns = wp_parse_id_list( get_post_var( 'bulk_campaigns' ) );
		$campaigns = map_to_class( $campaigns, Campaign::class );
		foreach ( $this->get_items() as $id ) {
			$flow = new Funnel( $id );
			foreach ( $campaigns as $campaign ) {
				$flow->create_relationship( $campaign );
			}

		}

		$this->add_notice( 'updated', __( 'Flow campaigns updated!', 'groundhogg' ) );

		return false;
	}

	public function process_remove_campaigns() {
		if ( ! current_user_can( 'manage_campaigns' ) ) {
			$this->wp_die_no_access();
		}

		$campaigns = wp_parse_id_list( get_post_var( 'bulk_campaigns' ) );
		$campaigns = map_to_class( $campaigns, Campaign::class );
		foreach ( $this->get_items() as $id ) {
			$flow = new Funnel( $id );
			foreach ( $campaigns as $campaign ) {
				$flow->delete_relationship( $campaign );
			}
		}

		$this->add_notice( 'updated', __( 'Flow campaigns updated!', 'groundhogg' ) );

		return false;
	}

	public function process_delete() {
		if ( ! current_user_can( 'delete_funnels' ) ) {
			$this->wp_die_no_access();
		}

		foreach ( $this->get_items() as $id ) {
			$funnel = new Funnel( $id );

			if ( ! $funnel->exists() || check_lock( $funnel ) ) {
				continue;
			}

			$funnel->delete();
		}

		$this->add_notice(
			esc_attr( 'deleted' ),
			/* translators: %d: the number of flows deleted */
			sprintf( _nx( 'Deleted %d flow', 'Deleted %d flows', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $this->get_items() ) ),
			'success'
		);

		return false;
	}

	/**
	 * Update the current funnels to a specific status
	 *
	 * @param $status
	 *
	 * @return int
	 */
	public function update_funnels_status( $status ) {

		if ( ! current_user_can( 'edit_funnels' ) ) {
			$this->wp_die_no_access();
		}

		$updated = 0;

		foreach ( $this->get_items() as $id ) {
			$funnel = new Funnel( $id );

			if ( ! $funnel->exists() || check_lock( $funnel ) ) {
				continue;
			}

			if ( $funnel->update( [
				'status' => $status
			] ) ) {
				$updated ++;
			}
		}

		return $updated;
	}

	/**
	 * Restore a funnel
	 *
	 * @return void
	 */
	public function process_restore() {
		$updated = $this->update_funnels_status( 'inactive' );

		$this->add_notice(
			esc_attr( 'restored' ),
			/* translators: %d: the number of flows restored */
			sprintf( _nx( 'Restored %d flow', 'Restored %d flows', $updated, 'notice', 'groundhogg' ), $updated ),
			'success'
		);
	}

	/**
	 * Archive a funnel
	 *
	 * @return void
	 */
	public function process_archive() {
		$updated = $this->update_funnels_status( 'archived' );

		$this->add_notice(
			esc_attr( 'archived' ),
			/* translators: %d: the number of flows archived */
			sprintf( _nx( 'Archived %d flow', 'Archived %d flows', $updated, 'notice', 'groundhogg' ), $updated ),
			'success'
		);
	}

	/**
	 * Deactivate a funnel
	 *
	 * @return void
	 */
	public function process_deactivate() {
		$updated = $this->update_funnels_status( 'inactive' );

		$this->add_notice(
			esc_attr( 'deactivated' ),
			/* translators: %d: the number of flows deactivated */
			sprintf( _nx( 'Deactivated %d flow', 'Deactivated %d flows', $updated, 'notice', 'groundhogg' ), $updated ),
			'success'
		);
	}

	/**
	 * Activate a funnel
	 *
	 * @return void
	 */
	public function process_activate() {
		$updated = $this->update_funnels_status( 'active' );

		$this->add_notice(
			esc_attr( 'activated' ),
			/* translators: %d: the number of flows activated */
			sprintf( _nx( 'Activated %d flow', 'Activated %d flows', $updated, 'notice', 'groundhogg' ), $updated ),
			'success'
		);
	}

	/**
	 * Duplicate a funnel
	 *
	 * @return false|string
	 */
	public function process_duplicate() {

		if ( ! current_user_can( 'add_funnels' ) ) {
			$this->wp_die_no_access();
		}

		foreach ( $this->get_items() as $id ) {

			$funnel = new Funnel( $id );

			if ( ! $funnel->exists() ) {
				continue;
			}

			$json = $funnel->export();

			$new_funnel = new Funnel();
			$id         = $new_funnel->import( $json );

			$new_funnel->update( [
				/* translators: %s: the previous flow title */
				'title' => sprintf( __( 'Copy of %s', 'groundhogg' ), $funnel->get_title() ),
			] );

			return $this->admin_url( [ 'action' => 'edit', 'funnel' => $id, 'from' => 'add' ] );
		}

		return false;
	}

	/**
	 * Export a list of funnels
	 *
	 * @return void
	 */
	public function process_export() {

		if ( ! current_user_can( 'edit_funnels' ) ) {
			$this->wp_die_no_access();
		}

		$funnels = [];

		foreach ( $this->get_items() as $item ) {
			$funnel    = new Funnel( $item );
			$funnels[] = $funnel->export();
		}

		download_json( $funnels, 'funnels' );
	}

	/**
	 * Create a new funnel without using a template
	 *
	 * @return string
	 */
	public function process_start_from_scratch() {
		if ( ! current_user_can( 'add_funnels' ) ) {
			$this->wp_die_no_access();
		}

		$funnel = new Funnel();
		$funnel->create( [
			'title'  => 'My new flow',
			'author' => get_current_user_id(),
			'status' => 'inactive',
		] );

		return add_query_arg( 'from', 'add', $funnel->admin_link() );
	}

	/**
	 * Process add action for the funnel.
	 *
	 * @return string|WP_Error
	 */
	public function process_add() {

		if ( ! current_user_can( 'add_funnels' ) ) {
			$this->wp_die_no_access();
		}

		$funnel_id = false;

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce checked upstream
		if ( isset( $_POST['funnel_template'] ) ) {

			$template_id = get_post_var( 'funnel_template' );
			$library     = new Library();
			$template    = $library->get_funnel_template( $template_id );
			$funnel_id   = $this->import_funnel( $template );

			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce checked upstream
		} else if ( isset( $_POST['funnel_id'] ) ) {

			$from_funnel = absint( get_post_var( 'funnel_id' ) );
			$from_funnel = new Funnel( $from_funnel );

			$json      = $from_funnel->export();
			$funnel_id = $this->import_funnel( $json );

			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce checked upstream
		} else if ( isset( $_FILES['funnel_template'] ) ) {

			$file  = get_sanitized_FILE( 'funnel_template' );
			$error = get_upload_wp_error( $file );

			if ( is_wp_error( $error ) ) {
				return $error;
			}

			$validate = wp_check_filetype( $file['name'], [
				'funnel' => 'text/plain',
				'json'   => 'application/json',
			] );

			if ( ! in_array( $validate['ext'], [ 'json', 'funnel' ] ) ) {
				return new WP_Error( 'invalid_template', esc_html__( 'Please upload a valid flow template.', 'groundhogg' ) );
			}

			$json = file_get_contents( $file['tmp_name'] );
			$json = json_decode( $json );

			if ( ! $json ) {
				return new WP_Error( 'invalid_json', 'Funnel template has invalid JSON.' );
			}

			// Importing multiple funnels
			if ( is_array( $json ) ) {

				foreach ( $json as $funnel ) {
					$this->import_funnel( $funnel );
				}

				/* translators: %d: the number of flows imported */
				$this->add_notice( 'imported', sprintf( esc_html__( 'Imported %d flows', 'groundhogg' ), count( $json ) ) );

				return admin_page_url( 'gh_funnels', [ 'view' => 'inactive' ] );
			}

			$funnel_id = $this->import_funnel( $json );

		} elseif ( $json = get_post_var( 'funnel_json' ) ) {

			$json = json_decode( $json );

			if ( ! $json ) {
				return new WP_Error( 'invalid_json', 'Invalid JSON provided.' );
			}

			// Importing multiple funnels
			if ( is_array( $json ) ) {

				foreach ( $json as $funnel ) {
					$this->import_funnel( $funnel );
				}

				/* translators: %d: the number of flows imported */
				$this->add_notice( 'imported', sprintf( esc_html__( 'Imported %d flows', 'groundhogg' ), count( $json ) ) );

				return admin_page_url( 'gh_funnels', [ 'view' => 'inactive' ] );
			}

			$funnel_id = $this->import_funnel( $json );
		}

		if ( is_wp_error( $funnel_id ) ) {
			return $funnel_id;
		}

		if ( empty( $funnel_id ) ) {
			return new WP_Error( 'error', esc_html__( 'Could not create flow.', 'groundhogg' ) );
		}

		return admin_page_url( 'gh_funnels', [
			'action' => 'edit',
			'funnel' => $funnel_id,
			'from'   => 'add'
		] );

	}

	/**
	 * Deconstructs the given array and builds a full funnel.
	 *
	 * @param $import array|string
	 *
	 * @return bool|int whether the import was successful or the ID
	 */
	public function import_funnel( $import = array() ) {

		if ( ! current_user_can( 'import_funnels' ) ) {
			$this->wp_die_no_access();
		}

		$funnel = new Funnel();

		return $funnel->import( $import );
	}

	/**
	 * Save the funnel via ajax...
	 */
	public function ajax_save_funnel() {
		if ( ! wp_doing_ajax() ) {
			return;
		}

		if ( ! $this->verify_action() ) {
			wp_send_json_error();
		}

		$result = $this->process_edit();

		$funnel = $this->get_current_funnel();

		if ( ! $funnel->exists() ) {
			wp_send_json_error();
		}

		$response = [
			'sortable' => $funnel->step_flow( false ),
			'settings' => $funnel->step_settings( false ),
			'funnel'   => $funnel,
		];

		if ( is_wp_error( $result ) ) {
			$response['err'] = $result->get_error_messages();
		}

		if ( $this->has_errors() ) {
			$response['err'] = $this->get_last_error()->get_error_message();
		}

		$this->send_ajax_response( $response );

	}

	/**
	 * @return Funnel
	 */
	public function get_current_funnel() {
		return new Funnel( absint( get_request_var( 'funnel' ) ) );
	}

	/**
	 * Save the funnel
	 */
	public function process_edit() {

		if ( ! current_user_can( 'edit_funnels' ) ) {
			$this->wp_die_no_access();
		}

		$funnel_id = absint( get_request_var( 'funnel' ) );
		$funnel    = new Funnel( $funnel_id );

		if ( ! $funnel->exists() ) {
			wp_send_json_error();
		}

		// restore the prev state of the steps...
		if ( get_post_var( '_restore' ) ) {

			$prev_step_states = json_decode( get_post_var( '_restore' ), true );
			$keep_step_ids    = wp_parse_id_list( wp_list_pluck( $prev_step_states, 'ID' ) );

			// delete steps that were added that aren't in the previous step state
			$curr_steps = $funnel->get_steps();
			foreach ( $curr_steps as $curr_step ) {
				if ( ! in_array( $curr_step->ID, $keep_step_ids ) ) {
					$curr_step->delete();
				}
			}

			// update current steps with data from prev states
			foreach ( $prev_step_states as $prev_step_state ) {

				$step = new Step( absint( $prev_step_state['ID'] ) );
				if ( $step->exists() ) {
					$step->update( $prev_step_state['data'] );
				} else {
					$step->create( $prev_step_state['data'] );
				}

				$step->update_meta( $prev_step_state['meta'] );

			}

			return true;
		}

		if ( get_post_var( '_delete_step' ) ) {

			$step_id = absint( get_post_var( '_delete_step' ) );
			$step    = new Step( $step_id );

			if ( ! $step->exists() ) {
				wp_send_json_error();
			}

			$step->delete();
		}

		if ( get_post_var( '_lock_step' ) ) {
			$step_id = absint( get_post_var( '_lock_step' ) );
			// update directly to avoid the changes/commit feature
			db()->steps->update( $step_id, [ 'is_locked' => 1 ] );
		}

		if ( get_post_var( '_unlock_step' ) ) {
			$step_id = absint( get_post_var( '_unlock_step' ) );
			// update directly to avoid the changes/commit feature
			db()->steps->update( $step_id, [ 'is_locked' => 0 ] );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce checked upstream
		if ( count( $_POST, COUNT_RECURSIVE ) >= intval( ini_get( 'max_input_vars' ) ) ) {
			return new WP_Error( 'post_too_big', _x( 'Your [max_input_vars] is too small for your funnel! You may experience odd behaviour and your funnel may not save correctly. Please <a target="_blank" href="http://www.google.com/search?q=increase+max_input_vars+php">increase your [max_input_vars] to at least double the current size.</a>.', 'notice', 'groundhogg' ) );
		}

		//get all the steps in the funnel.
		$step_ids = get_post_var( 'step_ids' );
		Step::increment_step_order( 0 );

		if ( empty( $step_ids ) ) {
			return new WP_Error( 'no_steps', 'Please add automation first.' );
		}

		$metaUpdates = get_post_var( 'metaUpdates' );
		$metaUpdates = json_decode( $metaUpdates, true ) ?: [];
		$metaUpdates = array_map_keys( $metaUpdates, 'absint' );

		$step = null;

		foreach ( $step_ids as $order => $stepId ) {

			// maybe creating a step
			if ( ! is_numeric( $stepId ) ) {

				$step_data = json_decode( $stepId, true );

				if ( ! $step_data ) {
					continue;
				}

				// we're copying another step
				if ( isset( $step_data['duplicate'] ) ) {

					$step_to_copy = new Step( absint( $step_data['duplicate'] ) );

					if ( ! $step_to_copy->exists() ) {
						continue;
					}

					$new = $step_to_copy->duplicate( [
						'step_status' => 'inactive', // must be inactive to start,
						'step_order'  => Step::increment_step_order(),
					] );

					continue;
				}

				// we're copying another step
				if ( isset( $step_data['copy'] ) ) {

					$step_to_copy = new Step( absint( $step_data['copy'] ) );

					if ( ! $step_to_copy->exists() ) {
						continue;
					}

					$new = $step_to_copy->duplicate( [
						'funnel_id'   => $funnel_id,
						'step_status' => 'inactive', // must be inactive to start,
						'step_order'  => Step::increment_step_order(),
						'branch'      => sanitize_key( $step_data['branch'] ),
					] );

					continue;
				}

				$step_data = array_apply_callbacks( $step_data, [
					'step_type' => function ( $value ) {
						return one_of( $value, Plugin::instance()->step_manager->get_types() );
					},
					'branch'    => 'sanitize_key',
				] );

				// type is not registered
				if ( ! Plugin::instance()->step_manager->type_is_registered( $step_data['step_type'] ) ) {
					continue;
				}

				$element = Plugin::instance()->step_manager->get_element( $step_data['step_type'] );

				$step = new Step();

				$step->create( [
					'funnel_id'   => $funnel_id,
					'step_title'  => $element->get_name(),
					'step_type'   => $element->get_type(),
					'step_group'  => $element->get_group(),
					'step_order'  => Step::increment_step_order(),
					'step_status' => 'inactive', // all steps added are by default inactive
					'branch'      => $step_data['branch']
				] );

				$schema = $element->get_settings_schema();

				foreach ( $schema as $setting => $setting_schema ) {
					if ( isset( $setting_schema['initial'] ) ) {
						$step->update_meta( $setting, $setting_schema['initial'] );
					}
				}

				continue;
			}

			$stepId = absint( $stepId );
			$step   = new Step( $stepId );

			if ( isset_not_empty( $metaUpdates, $stepId ) ) {
				$step->update_meta( $metaUpdates[ $stepId ] );
			}

			$step->save();
		}

		$funnel->set_step_levels();

		// activate the funnel
		if ( get_post_var( '_activate' ) ) {
			$args['status']       = 'active';
			$args['last_updated'] = current_time( 'mysql' );
		}

		// deactivate the funnel
		if ( get_post_var( '_deactivate' ) ) {

			// changes were not committed, so let's delete them
			if ( ! get_post_var( '_commit' ) ) {
				$funnel->uncommit();
			}

			$args['status']       = 'inactive';
			$args['last_updated'] = current_time( 'mysql' );
		}

		// deleted uncommited changes
		if ( get_post_var( '_uncommit' ) ) {
			$funnel->uncommit();
		}

		if ( get_post_var( '_commit' ) && $funnel->is_active() ) {
			$args['last_updated'] = current_time( 'mysql' );
			$funnel->commit();
		}

		$args['title'] = sanitize_text_field( get_post_var( 'funnel_title' ) );

		$funnel->update( $args );

		/**
		 * Runs after the funnel as been updated.
		 */
		do_action( 'groundhogg/admin/funnel/updated', $funnel );

		return true;

	}

	public function edit() {
		if ( ! current_user_can( 'edit_funnels' ) ) {
			$this->wp_die_no_access();
		}

		include __DIR__ . '/funnel-editor.php';
	}

	public function add() {
		if ( ! current_user_can( 'add_funnels' ) ) {
			$this->wp_die_no_access();
		}

		include __DIR__ . '/add-funnel.php';
	}

	public function view() {
		if ( ! class_exists( 'Funnels_Table' ) ) {
			include __DIR__ . '/funnels-table.php';
		}

		$funnels_table = new Funnels_Table();

		$funnels_table->views();

		$this->table_filters();
		$this->search_form();

		?>
        <form method="post" class="wp-clearfix">
			<?php $funnels_table->prepare_items(); ?>
			<?php $funnels_table->display(); ?>
        </form>
		<?php
	}

	public function add_to_funnel() {
		if ( ! current_user_can( 'edit_funnels' ) ) {
			$this->wp_die_no_access();
		}

		include __DIR__ . '/add-to-funnel.php';
	}

	public function page() {
		if ( $this->get_current_action() === 'edit' ) {
			$this->edit();

			return;
		}

		parent::page();
	}
}
