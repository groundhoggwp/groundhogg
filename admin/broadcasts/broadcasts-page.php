<?php

namespace Groundhogg\Admin\Broadcasts;

use Groundhogg\Admin\Admin_Page;
use Groundhogg\Broadcast;
use Groundhogg\Bulk_Jobs\Broadcast_Scheduler;
use Groundhogg\Email;
use Groundhogg\Plugin;
use Groundhogg\Preferences;
use Groundhogg\Saved_Searches;
use function Groundhogg\admin_page_url;
use function Groundhogg\enqueue_broadcast_assets;
use function Groundhogg\enqueue_filter_assets;
use function Groundhogg\get_db;
use function Groundhogg\get_post_var;
use function Groundhogg\get_request_var;
use function Groundhogg\is_sms_plugin_active;
use function Groundhogg\validate_tags;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The page gh_broadcasts
 *
 * This class adds the broadcasts page to the menu and renders the output for the broadcasts page
 * IT also contains the private functions add() and cancel()
 * These are made private for good reason as the broadcasts function was decided to be kept a closed process.
 * If you are a developer, simply BUGGER OFF!
 *
 * @since       File available since Release 0.1
 * @subpackage  Admin/Broadcasts
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Admin
 */
class Broadcasts_Page extends Admin_Page {

	/**
	 * @var Broadcast_Scheduler
	 */
	public $scheduler;

	protected function add_ajax_actions() {
	}

	public function help() {
	}

	protected function add_additional_actions() {
		$this->scheduler = new Broadcast_Scheduler();
	}

	/**
	 * enqueue editor scripts
	 */
	public function scripts() {
		wp_enqueue_style( 'groundhogg-admin' );
		wp_enqueue_style( 'groundhogg-admin-email-preview' );
		wp_enqueue_script( 'groundhogg-admin-email-preview' );

		enqueue_broadcast_assets();
	}

	public function get_priority() {
		return 25;
	}

	public function get_slug() {
		return 'gh_broadcasts';
	}

	public function get_name() {
		return _x( 'Broadcasts', 'page_title', 'groundhogg' );
	}

	public function get_cap() {
		return 'schedule_broadcasts';
	}

	public function get_item_type() {
		return 'broadcast';
	}

	/**
	 * Get the current screen title
	 */
	function get_title() {
		switch ( $this->get_current_action() ) {
			case 'add':
				return _x( 'Schedule Broadcast', 'page_title', 'groundhogg' );
				break;
			default:
				return _x( 'Broadcasts', 'page_title', 'groundhogg' );
				break;
		}
	}

	public function process_cancel() {
		if ( ! current_user_can( 'cancel_broadcasts' ) ) {
			$this->wp_die_no_access();
		}

		foreach ( $this->get_items() as $id ) {

			$broadcast = new Broadcast( $id );
			$broadcast->cancel();
		}

		$this->add_notice( 'cancelled', sprintf( _nx( '%d broadcasts cancelled', '%d broadcast cancelled', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $this->get_items() ) ) );

		return false;
	}

	/**
	 * Schedule a new broadcast
	 */
	public function process_add() {
		if ( ! current_user_can( 'schedule_broadcasts' ) ) {
			$this->wp_die_no_access();
		}

		$meta = [];

		$object_id = isset( $_POST['object_id'] ) ? intval( $_POST['object_id'] ) : null;
		if ( ! $object_id ) {
			return new \WP_Error( 'unable_to_add_tags', __( 'Please select an email or SMS to send.', 'groundhogg' ) );
		}

		/* Set the object  */
		$meta['object_id']   = $object_id;
		$meta['object_type'] = isset( $_REQUEST['type'] ) && $_REQUEST['type'] === 'sms' ? 'sms' : 'email';

		if ( $meta['object_type'] === 'email' ) {

			$email = new Email( $object_id );

			if ( $email->is_draft() ) {
				return new \WP_Error( 'email_in_draft_mode', __( 'You cannot schedule an email while it is in draft mode.', 'groundhogg' ) );
			}
		}

		$send_date = isset( $_POST['date'] ) ? sanitize_text_field( $_POST['date'] ) : date( 'Y-m-d', strtotime( 'tomorrow' ) );
		$send_time = isset( $_POST['time'] ) ? sanitize_text_field( $_POST['time'] ) : '09:30';

		$time_string = $send_date . ' ' . $send_time;

		/* convert to UTC */
		$send_time = Plugin::$instance->utils->date_time->convert_to_utc_0( strtotime( $time_string ) );

		if ( isset( $_POST['send_now'] ) ) {
			$meta['send_now'] = true;
			$send_time        = time() + 10;
		}

		if ( $send_time < time() ) {
			return new \WP_Error( 'invalid_date', __( 'Please select a time in the future', 'groundhogg' ) );
		}

		/* Set the email */
		$meta['send_time'] = $send_time;

		$include_tags = validate_tags( get_post_var( 'tags', [] ) );
		$exclude_tags = validate_tags( get_post_var( 'exclude_tags', [] ) );

		$query = array(
			'tags_include'           => $include_tags,
			'tags_exclude'           => $exclude_tags,
			'tags_include_needs_all' => absint( get_request_var( 'tags_include_needs_all' ) ),
			'tags_exclude_needs_all' => absint( get_request_var( 'tags_exclude_needs_all' ) )
		);

		// Use a saved search instead.
		if ( $saved_search = sanitize_text_field( get_post_var( 'saved_search' ) ) ) {
			$search = Saved_Searches::instance()->get( $saved_search );
			if ( $search ) {
				$query = $search['query'];
			}
		} else if ( $custom_query = get_post_var( 'custom_query' ) ) {
			$query = map_deep( json_decode( $custom_query, true ), 'sanitize_text_field' );
		}

		// Unset the search param from the query...
		unset( $query['is_searching'] );

		$query = wp_parse_args( $query, [
			'optin_status' => [
				Preferences::CONFIRMED,
				Preferences::UNCONFIRMED,
			]
		] );

		// Assume marketing by default...
		$meta['is_transactional'] = false;

		// if the email is a transactional email we will remove the optin statuses from the query
		if ( $meta['object_type'] === 'email' && isset( $email ) && $email->is_transactional() ) {

			// Include additional statuses
			unset( $query['optin_status'] );

			$query['optin_status_exclude'] = [
				Preferences::SPAM,
				Preferences::HARD_BOUNCE,
				Preferences::COMPLAINED
			];

			// make transactional
			$meta['is_transactional'] = true;
		}

		$query = array_filter( $query );

		$args = array(
			'object_id'    => $object_id,
			'object_type'  => $meta['object_type'],
			'send_time'    => $send_time,
			'scheduled_by' => get_current_user_id(),
			'status'       => 'pending',
			'query'        => $query,
		);

		$num_contacts = get_db( 'contacts' )->count( $query );

		if ( $num_contacts === 0 ) {
			return new \WP_Error( 'error', __( 'No contacts match the given filters.', 'groundhogg' ) );
		}

		$broadcast_id = get_db( 'broadcasts' )->add( $args );

		if ( ! $broadcast_id ) {
			return new \WP_Error( 'unable_to_add_broadcast', __( 'Something went wrong while adding the broadcast.', 'groundhogg' ) );
		}

		if ( isset( $_POST['send_in_timezone'] ) ) {
			$meta['send_in_local_time'] = true;
		}

		$broadcast = new Broadcast( $broadcast_id );

		foreach ( $meta as $key => $value ) {
			$broadcast->update_meta( $key, $value );
		}

		/**
		 * Fires after the broadcast is added to the DB but before the user is redirected to the scheduler
		 *
		 * @param int   $broadcast_id the ID of the broadcast
		 * @param array $meta         the config object which is passed to the scheduler
		 */
		do_action( 'groundhogg/admin/broadcast/scheduled', $broadcast_id, $meta, $broadcast );

		$this->add_notice( 'review', __( 'Review your broadcast before scheduling!', 'groundhogg' ), 'warning' );

		return admin_page_url( 'gh_broadcasts', [
			'action'    => 'preview',
			'broadcast' => $broadcast_id,
		] );
	}

	/**
	 * Confirm from the preview page
	 *
	 * @return string
	 */
	public function process_confirm_send() {
		if ( ! current_user_can( 'schedule_broadcasts' ) ) {
			$this->wp_die_no_access();
		}

		$broadcast_id = absint( get_request_var( 'broadcast' ) );

		$broadcast = new Broadcast( $broadcast_id );

		if ( ! $broadcast->exists() ) {
			return false;
		}

		$broadcast->update( [ 'status' => 'scheduled' ] );

		set_transient( 'gh_current_broadcast_id', $broadcast_id, DAY_IN_SECONDS );

		return $this->scheduler->get_start_url( [ 'broadcast' => $broadcast_id ] );
	}

	/**
	 * Resend a broadcast to anyone who has not opened it. Schedule as a new broadcast.
	 */
	public function process_resend_to_unopened() {
		if ( ! current_user_can( 'schedule_broadcasts' ) ) {
			$this->wp_die_no_access();
		}

		do_action( 'groundhogg/admin/broadcasts/process_resend_to_unopened' );
	}

	/**
	 * Delete
	 *
	 * @return bool|\WP_Error
	 */
	public function process_delete() {
		if ( ! current_user_can( 'schedule_broadcasts' ) ) {
			$this->wp_die_no_access();
		}

		foreach ( $this->get_items() as $id ) {
			if ( ! get_db( 'broadcasts' )->delete( $id ) ) {
				return new \WP_Error( 'unable_to_delete_broadcast', "Something went wrong while deleting the broadcast.", 'groundhogg' );
			}
		}

		$this->add_notice(
			esc_attr( 'deleted' ),
			sprintf( _nx( 'Deleted %d broadcast', 'Deleted %d broadcasts', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $this->get_items() ) ),
			'success'
		);

		return false;
	}

	/**
	 * @return array|array[]
	 */
	protected function get_title_actions() {

		$actions = [];

		if ( $this->get_current_action() !== 'add' ) {
			$actions[] = [
				'link'   => $this->admin_url( [ 'action' => 'add', 'type' => 'email' ] ),
				'action' => __( 'Schedule Email Broadcast', 'groundhogg' ),
				'target' => '_self',
				'id'     => 'gh-schedule-broadcast'
			];
		}

		if ( is_sms_plugin_active() ) {
			$actions[] = [
				'link'   => $this->admin_url( [ 'action' => 'add', 'type' => 'sms' ] ),
				'action' => __( 'Schedule SMS Broadcast', 'groundhogg' ),
				'target' => '_self',
			];
		}

		return $actions;
	}

	/**
	 * Display the table
	 */
	public function view() {
		$broadcasts_table = new Broadcasts_Table();

		$this->search_form( __( 'Search Broadcasts', 'groundhogg' ) );
		$broadcasts_table->views(); ?>
		<form method="post" class="wp-clearfix">
			<!-- search form -->
			<?php $broadcasts_table->prepare_items(); ?>
			<?php $broadcasts_table->display(); ?>
		</form>

		<?php
	}

	/**
	 * Display the scheduling page
	 */
	public function add() {
		if ( ! current_user_can( 'schedule_broadcasts' ) ) {
			$this->wp_die_no_access();
		}

		include __DIR__ . '/add.php';
	}

	/**
	 * Display the scheduling page
	 */
	public function preview() {
		if ( ! current_user_can( 'schedule_broadcasts' ) ) {
			$this->wp_die_no_access();
		}

		include __DIR__ . '/preview.php';
	}
}
