<?php

namespace Groundhogg\Admin\Events;

use cli\Table;
use Groundhogg\Admin\Admin_Page;
use Groundhogg\Event;
use function Groundhogg\get_db;
use Groundhogg\Plugin;
use function Groundhogg\get_request_var;

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
 * @package     Admin
 * @subpackage  Admin/Events
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */
class Events_Page extends Admin_Page {

	//UNUSED FUNCTIONS
	protected function add_ajax_actions() {
	}

	public function help() {
	}

	protected function add_additional_actions() {
	}

	public function scripts() {
		wp_enqueue_style( 'groundhogg-admin' );
	}

	public function get_slug() {
		return 'gh_events';
	}

	public function get_name() {
		return _x( 'Events', 'page_title', 'groundhogg' );
	}

	public function get_cap() {
		return 'view_events';
	}

	public function get_item_type() {
		return 'event';
	}

	public function get_priority() {
		return 40;
	}

	protected function get_title_actions() {
		return [];
	}

	/**
	 *  Sets the title of the page
	 * @return string
	 */
	public function get_title() {
		switch ( $this->get_current_action() ) {
			case 'view':
			default:
				return _x( 'Events', 'page_title', 'groundhogg' );
				break;
		}
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
		$cancelled = Event::CANCELLED;

		// Update the time
		$wpdb->query( "UPDATE {$event_queue} SET `status` = '$cancelled' WHERE `ID` in ({$event_ids})" );

		// Move the items over...
		get_db( 'event_queue' )->move_events_to_history( [ 'ID' =>  $this->get_items() ] );

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

		$events      = get_db( 'events' )->get_table_name();
		$event_ids   = implode( ',', $this->get_items() );
		$cancelled   = Event::CANCELLED;
		$waiting     = Event::WAITING;

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

}