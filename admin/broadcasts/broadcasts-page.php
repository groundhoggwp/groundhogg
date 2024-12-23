<?php

namespace Groundhogg\Admin\Broadcasts;

use Groundhogg\Admin\Admin_Page;
use Groundhogg\Broadcast;
use Groundhogg\Utils\DateTimeHelper;
use function Groundhogg\admin_page_url;
use function Groundhogg\enqueue_broadcast_assets;
use function Groundhogg\get_db;
use function Groundhogg\get_post_var;
use function Groundhogg\get_url_var;
use function Groundhogg\is_sms_plugin_active;
use function Groundhogg\notices;
use function Groundhogg\verify_admin_ajax_nonce;

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

	protected function add_ajax_actions() {
		add_action( 'wp_ajax_gh_estimate_send_duration', [ $this, 'ajax_estimate_send_duration' ] );
	}

	public function ajax_estimate_send_duration() {

		if ( ! verify_admin_ajax_nonce() || ! current_user_can( 'schedule_broadcasts' ) ) {
			$this->wp_die_no_access();
		}

		$total_contacts  = get_post_var( 'total_contacts' );
		$amount          = get_post_var( 'batch_amount' );
		$interval        = get_post_var( 'batch_interval' );
		$interval_length = get_post_var( 'batch_interval_length' );

        $batches = floor( $total_contacts / $amount );

        $dateTime = new DateTimeHelper();
        $total_interval_length = $batches * $interval_length;

        $dateTime->modify( "+$total_interval_length $interval" );

        wp_send_json_success([
            'time' => $dateTime->human_time_diff(),
        ]);
	}

	public function help() {
	}

	protected function add_additional_actions() {
		if ( get_db( 'broadcasts' )->is_empty() && ! get_db( 'emails' )->exists( [ 'status' => 'ready' ] ) ) {

			notices()->add( 'dne', __( 'You must create an email before you can schedule a broadcast.', 'groundhogg' ), 'notice' );

			wp_redirect( admin_page_url( 'gh_emails', [ 'action' => 'add' ] ) );
			die();
		}
	}

	protected function get_current_action() {
		$action = parent::get_current_action();

		if ( $action == 'view' && get_db( 'broadcasts' )->is_empty() ) {
			$action = 'add';
		}

		return $action;
	}

	/**
	 * enqueue editor scripts
	 */
	public function scripts() {
		wp_enqueue_style( 'groundhogg-admin' );

		enqueue_broadcast_assets();
	}

	public function get_priority() {
		return 55;
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

				$type = get_url_var( 'type', 'email' );

				if ( $type === 'sms' ) {
					return _x( 'Schedule SMS Broadcast', 'page_title', 'groundhogg' );
				}

				return _x( 'Schedule Email Broadcast', 'page_title', 'groundhogg' );
			default:
				return _x( 'Broadcasts', 'page_title', 'groundhogg' );
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

		if ( $this->current_action_is( 'add' ) ) {
			return [];
		}

		$actions   = [];
		$actions[] = [
			'link'   => $this->admin_url( [ 'action' => 'add', 'type' => 'email' ] ),
			'action' => __( 'Schedule Email Broadcast', 'groundhogg' ),
			'target' => '_self',
			'id'     => 'gh-schedule-broadcast'
		];

		if ( is_sms_plugin_active() ) {
			$actions[] = [
				'link'   => $this->admin_url( [ 'action' => 'add', 'type' => 'sms' ] ),
				'action' => __( 'Schedule SMS Broadcast', 'groundhogg' ),
				'target' => '_self',
				'id'     => 'gh-schedule-sms-broadcast'
			];
		}

		return $actions;
	}

	/**
	 * Display the table
	 */
	public function view() {

        // fix sending broadcasts
        Broadcast::transition_from_sending_to_sent();

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
}
