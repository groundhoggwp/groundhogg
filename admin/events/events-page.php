<?php

namespace Groundhogg\Admin\Events;

use cli\Table;
use Groundhogg\Admin\Admin_Page;
use Groundhogg\Admin\Tabbed_Admin_Page;
use Groundhogg\Email_Log_Item;
use Groundhogg\Email_Logger;
use Groundhogg\Event;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_db;
use Groundhogg\Plugin;
use function Groundhogg\get_request_var;
use function Groundhogg\get_url_var;
use function Groundhogg\is_option_enabled;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * View Events
 *
 * Allow the user to view & edit the events
 * This allows one to manage all the events associated with funnels, broadcasts, and funnels.
 * This was included as a page for the convenience of the end user. Although only advanced users will use it probably.
 *
 * @since       File available since Release 0.1
 * @subpackage  Admin/Events
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Admin
 */
class Events_Page extends Tabbed_Admin_Page {

	//UNUSED FUNCTIONS
	protected function add_ajax_actions() {
		add_action( 'wp_ajax_groundhogg_view_email_log', [ $this, 'output_email_log' ] );
	}

	public function output_email_log() {
		if ( ! current_user_can( 'view_events' ) ) {
			wp_send_json_error();
		}

		ob_start();

		$this->view_log();

		$content = ob_get_clean();

		wp_send_json_success( [
			'content' => $content
		] );
	}

	public function raw_email_content() {
		if ( get_url_var( 'action' ) !== 'view_log_content' ) {
			return;
		}

		$preview_id = absint( get_url_var( 'log' ) );

		$log_item = new Email_Log_Item( $preview_id );

		if ( ! $log_item->exists() ) {
			wp_die( 'Invalid log item ID.' );
		}

		if ( preg_match( '/<html[^>]*>/', $log_item->content ) ) {
			echo $log_item->content;
		} else {
			echo wpautop( esc_html( $log_item->content ) );
		}

		die();
	}

	public function help() {
	}

	protected function add_additional_actions() {
		add_action( 'admin_init', [ $this, 'raw_email_content' ] );
		add_action( 'admin_head', function () {
		    ?>
            <style>
                .email-sent{
                    color: green;
                }
                .email-failed{
                    color: red;
                }
            </style>
            <?php
        } );

	}


	public function scripts() {
		wp_enqueue_style( 'groundhogg-admin' );
		wp_enqueue_style( 'groundhogg-admin-email-log' );
		wp_enqueue_script( 'groundhogg-admin-fullframe' );
		wp_enqueue_script( 'groundhogg-admin-email-log' );

	}

	public function get_slug() {
		return 'gh_events';
	}

	public function get_name() {
		return _x( 'Logs', 'page_title', 'groundhogg' );
	}

	public function get_cap() {
		return 'view_events';
	}

	public function get_item_type() {

		switch ( $this->get_current_tab() ) {
			default:
			case 'events' :
				return 'events';
			case 'emails' :
				return 'email';
		}
	}

	public function get_priority() {
		return 99;
	}

	protected function get_title_actions() {

		if ( $this->get_current_tab() !== 'emails' ) {
			return [];
		}

		return [
			[
				'link'   => admin_page_url( 'gh_settings', [ 'tab' => 'email' ], 'email-logging' ),
				'action' => __( 'Settings', 'groundhogg' ),
				'target' => '_self',
			]
		];
	}

	/**
	 *  Sets the title of the page
	 * @return string
	 */
	public function get_title() {
		return _x( 'Logs', 'page_title', 'groundhogg' );
	}

	/**
	 * Cancels scheduled broadcast
	 *
	 * @return bool
	 */
	public function process_cancel() {

		if ( ! current_user_can( 'cancel_events' ) ) {
			$this->wp_die_no_access();
		}

		global $wpdb;

		$event_queue = get_db( 'event_queue' )->get_table_name();
		$event_ids   = implode( ',', $this->get_items() );
		$cancelled   = Event::CANCELLED;

		// Update the time
		$wpdb->query( "UPDATE {$event_queue} SET `status` = '$cancelled' WHERE `ID` in ({$event_ids})" );

		// Move the items over...
		get_db( 'event_queue' )->move_events_to_history( [ 'ID' => $this->get_items() ] );

		$this->add_notice( 'cancelled', sprintf( _nx( '%d event cancelled', '%d events cancelled', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $this->get_items() ) ) );

		if ( $contact_id = absint( get_request_var( 'return_to_contact' ) ) ) {
			return admin_url( 'admin.php?page=gh_contacts&action=edit&tab=activity&contact=' . $contact_id );
		}

		//false return users to the main page
		return false;
	}

	/**
	 * Clean up the events DB if something goes wrong.
	 *
	 * @return bool
	 */
	public function process_cleanup() {
		if ( ! current_user_can( 'execute_events' ) ) {
			$this->wp_die_no_access();
		}

		global $wpdb;

		$events = get_db( 'event_queue' );

		$wpdb->query( "UPDATE {$events->get_table_name()} SET claim = '' WHERE claim <> ''" );
		$wpdb->query( "UPDATE {$events->get_table_name()} SET status = 'complete' WHERE status = 'in_progress'" );

		return false;
	}

	/**
	 * Delete any failed or cancelled events.
	 */
	public function process_purge() {
		if ( ! current_user_can( 'cancel_events' ) ) {
			$this->wp_die_no_access();
		}

		global $wpdb;

		$events = get_db( 'events' );

		$result = $wpdb->query( "DELETE FROM {$events->get_table_name()} WHERE `status` in ( 'waiting', 'failed' )" );

		if ( $result !== false ) {
			$this->add_notice( 'events_purged', __( 'Purged failed events!' ) );
		}
	}

	/**
	 * Reschedule events if running now in the waiting table.
	 *
	 * @return bool
	 */
	public function process_execute_now() {
		if ( ! current_user_can( 'execute_events' ) ) {
			$this->wp_die_no_access();
		}

		global $wpdb;

		$event_queue = get_db( 'event_queue' );
		$event_ids   = implode( ',', $this->get_items() );
		$time        = time();

		$wpdb->query( "UPDATE {$event_queue->get_table_name()} SET `time` = {$time} WHERE `ID` in ({$event_ids})" );

		$this->add_notice( 'scheduled', sprintf( _nx( '%d event rescheduled', '%d events rescheduled', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $this->get_items() ) ) );

		if ( $contact_id = absint( get_request_var( 'return_to_contact' ) ) ) {
			return admin_url( 'admin.php?page=gh_contacts&action=edit&tab=activity&contact=' . $contact_id );
		}

		return false;
	}

	/**
	 * Executes the event
	 *
	 * @return bool
	 */
	public function process_execute_again() {
		if ( ! current_user_can( 'execute_events' ) ) {
			$this->wp_die_no_access();
		}

		global $wpdb;

		$events      = get_db( 'events' )->get_table_name();
		$event_queue = get_db( 'event_queue' )->get_table_name();
		$event_ids   = implode( ',', $this->get_items() );
		$time        = time();
		$waiting     = Event::WAITING;

		$claim = substr( md5( wp_json_encode( $this->get_items() ) ), 0, 20 );

		// Update the claim column
		$wpdb->query( "UPDATE {$events} SET `claim` = '$claim' WHERE `ID` in ({$event_ids});" );

		// Move the events over... only delete if the status is not complete
		get_db( 'events' )->move_events_to_queue( [ 'claim' => $claim ], get_request_var( 'status' ) === Event::COMPLETE ? false : true );

		// Update claim, status, and time...
		$wpdb->query( "UPDATE {$event_queue} SET `claim` = '', `status` = '$waiting', `time` = $time WHERE `claim` = '$claim';" );

		$this->add_notice( 'scheduled', sprintf( _nx( '%d event rescheduled', '%d events rescheduled', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $this->get_items() ) ) );

		if ( $contact_id = absint( get_request_var( 'return_to_contact' ) ) ) {
			return admin_url( 'admin.php?page=gh_contacts&action=edit&tab=activity&contact=' . $contact_id );
		}

		return false;
	}

	/**
	 * Uncancels any cancelled events...
	 *
	 * @return bool
	 */
	public function process_uncancel() {
		if ( ! current_user_can( 'execute_events' ) ) {
			$this->wp_die_no_access();
		}

		global $wpdb;

		$events    = get_db( 'events' )->get_table_name();
		$event_ids = implode( ',', $this->get_items() );
		$cancelled = Event::CANCELLED;
		$waiting   = Event::WAITING;

		// Update the status back to waiting...
		$wpdb->query( "UPDATE {$events} SET `status` = '$waiting' WHERE `ID` in ({$event_ids}) AND `status` = '$cancelled';" );

		// Move the events over...
		get_db( 'events' )->move_events_to_queue( [ 'ID' => $this->get_items(), 'status' => $waiting ], true );

		$this->add_notice( 'scheduled', sprintf( _nx( '%d event uncancelled', '%d events uncancelled', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $this->get_items() ) ) );

		if ( $contact_id = absint( get_request_var( 'return_to_contact' ) ) ) {
			return admin_url( 'admin.php?page=gh_contacts&action=edit&tab=activity&contact=' . $contact_id );
		}

		return false;
	}

	/**
	 * Clean up the events DB if something goes wrong.
	 *
	 * @return bool
	 */
	public function process_process_queue() {
		if ( ! current_user_can( 'execute_events' ) ) {
			$this->wp_die_no_access();
		}

		$queue = Plugin::$instance->event_queue;

		Plugin::$instance->notices->add( 'queue-complete', sprintf( "%d events have been completed in %s seconds.", $queue->run_queue(), $queue->get_last_execution_time() ) );

		if ( $queue->has_errors() ) {
			Plugin::$instance->notices->add( 'queue-errors', sprintf( "%d events failed to complete. Please see the following errors.", count( $queue->get_errors() ) ), 'warning' );

			foreach ( $queue->get_errors() as $error ) {
				Plugin::instance()->notices->add( $error );
			}
		}

		if ( $contact_id = absint( get_request_var( 'return_to_contact' ) ) ) {
			return admin_url( 'admin.php?page=gh_contacts&action=edit&tab=activity&contact=' . $contact_id );
		}

		return false;
	}

	/**
	 * Show the main view
	 *
	 * @return mixed|void
	 */
	public function view() {
		if ( ! current_user_can( 'view_events' ) ) {
			$this->wp_die_no_access();
		}

		if ( ! class_exists( 'Events_Table' ) ) {
			include __DIR__ . '/events-table.php';
		}

		$events_table = new Events_Table();

		$events_table->views();
		?>
        <form method="post" class="search-form wp-clearfix">
            <!-- search form -->
			<?php $events_table->prepare_items(); ?>
			<?php $events_table->display(); ?>
        </form>

		<?php
	}

	/**
	 * @inheritDoc
	 */
	protected function get_tabs() {
		return [
			[
				'name' => __( 'Events', 'groundhogg' ),
				'slug' => 'events',
                'cap'  => 'view_events'
			],
			[
				'name' => __( 'Emails', 'groundhogg' ),
				'slug' => 'emails',
				'cap'  => 'view_logs'

			],
		];
	}

	public function view_emails() {

		if ( ! current_user_can( 'view_events' ) ) {
			$this->wp_die_no_access();
		}

		$log_table = new Email_Log_Table();

		$this->search_form( __( 'Search Logs', 'groundhogg' ) );

		$log_table->views();
		?>
        <form method="post" class="search-form wp-clearfix">
            <!-- search form -->
			<?php $log_table->prepare_items(); ?>
			<?php $log_table->display(); ?>
        </form>
        <div id="modal-log-details">
            <div id="modal-log-details-view"></div>
        </div>
		<?php
	}

	/**
	 * Delete some of the email logs
	 */
	public function process_emails_delete() {

		if ( ! current_user_can( 'delete_logs' ) ) {
			$this->wp_die_no_access();
		}

		foreach ( $this->get_items() as $id ) {
			get_db( 'email_log' )->delete( $id );
		}

		$this->add_notice(
			esc_attr( 'deleted' ),
			sprintf( _nx( 'Deleted %d email log', 'Deleted %d email logs', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $this->get_items() ) ),
			'success'
		);

		return false;
	}

	/**
	 * Resent emails
	 */
	public function process_emails_resend(){

		if ( ! current_user_can( 'send_emails' ) ) {
			$this->wp_die_no_access();
		}

		foreach ( $this->get_items() as $id ) {
			$log_item = new Email_Log_Item( $id );

			$log_item->retry();
		}

		$this->add_notice(
			esc_attr( 'resent' ),
			sprintf( _nx( 'Resent %d email', 'Resent %d emails', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $this->get_items() ) ),
			'success'
		);
    }

	public function view_log() {
		include __DIR__ . '/log-preview.php';
	}

	public function page() {

		if ( $this->get_current_tab() === 'log' ) {
			$this->view_log();

			return;
		}

		if ( $this->get_current_tab() === 'emails' && ! Email_Logger::is_enabled() ) {
			$this->add_notice( 'inactive', sprintf( __( "Email logging is currently disabled. You can enable email logging in the <a href='%s'>email settings</a>.", 'groundhogg' ), admin_page_url( 'gh_settings', [ 'tab' => 'email' ], 'email-logging' ) ), 'warning' );
		}

		parent::page();
	}
}
