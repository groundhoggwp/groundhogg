<?php

namespace Groundhogg\Admin\Contacts\Tables;

use function Groundhogg\get_date_time_format;
use function Groundhogg\get_db;
use function Groundhogg\get_url_var;
use function Groundhogg\scheduled_time;
use \WP_List_Table;
use Groundhogg\Event;
use Groundhogg\Plugin;
use Groundhogg\Email;
use function Groundhogg\scheduled_time_column;

/**
 * Activity table view
 *
 * This is an extension of the WP_List_Table, it shows the recent email activity of a contact at the bottom of the contact record
 * Shows the subject line of the email sent, the date it was opened and the link they clicked if they click a link
 *
 * @package     Admin
 * @subpackage  Admin/Contacts
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @see         WP_List_Table, contact-editor.php
 * @since       File available since Release 0.9
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// WP_List_Table is not loaded automatically so we need to load it in our application
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Contact_Activity_Table extends WP_List_Table {

	/**
	 * @var array
	 */
	public $data;

	/**
	 * TT_Example_List_Table constructor.
	 *
	 * REQUIRED. Set up a constructor that references the parent constructor. We
	 * use the parent reference to set some default configs.
	 */
	public function __construct() {
		// Set parent defaults.
		parent::__construct( array(
			'singular' => 'event',     // Singular name of the listed records.
			'plural'   => 'events',    // Plural name of the listed records.
			'ajax'     => false,       // Does this table support ajax?
		) );
	}

	/**
	 * @return array An associative array containing column information.
	 * @see WP_List_Table::::single_row_columns()
	 */
	public function get_columns() {
		$columns = array(
			'email' => _x( 'Email', 'Column label', 'groundhogg' ),
			'open'  => _x( 'Opened', 'Column label', 'groundhogg' ),
			'click' => _x( 'Clicked', 'Column label', 'groundhogg' ),
			'sent'  => _x( 'Sent', 'Column label', 'groundhogg' ),
		);

		return apply_filters( 'wpgh_contact_activity_columns', $columns );
	}

	/**
	 * Generates content for a single row of the table
	 *
	 * @param object $item The current item
	 *
	 * @since 3.1.0
	 *
	 */
	public function single_row( $item ) {
		echo '<tr>';
		$this->single_row_columns( new Event( $item->ID ) );
		echo '</tr>';
	}

	/**
	 * @param $event Event
	 *
	 * @return string
	 */
	protected function column_email( $event ) {
		$email = $event->get_email();

		if ( ! $email || ! $email->exists() ) {
			return false;
		}

		return sprintf( "<a href='%s' target='_blank'>%s</a>", admin_url( 'admin.php?page=gh_emails&action=edit&email=' . $email->get_id() ), $email->get_subject_line() );
	}

	/**
	 * @param $event Event
	 *
	 * @return string
	 */
	protected function column_open( $event ) {

		$activity = get_db( 'activity' )->query( [
			'event_id'      => $event->get_id(),
			'step_id'       => $event->get_step_id(),
			'activity_type' => 'email_opened',
			'contact_id'    => $event->get_contact_id(),
		] );

		if ( empty( $activity ) ) {
			return '&#x2014;';
		}

		$activity = array_shift( $activity );
		$time     = absint( $activity->timestamp );

		return scheduled_time_column( $time, true, $event->get_contact() );
	}

	/**
	 * @param $event Event
	 *
	 * @return string
	 */
	protected function column_click( $event ) {

		$activity = get_db( 'activity' )->query( [
			'event_id'      => $event->get_id(),
			'step_id'       => $event->get_step_id(),
			'activity_type' => 'email_link_click',
			'contact_id'    => $event->get_contact_id(),
		] );

		if ( empty( $activity ) ) {
			return '&#x2014;';
		}

		$activity = array_shift( $activity );

		return '<a target="_blank" href="' . esc_url( $activity->referer ) . '">' . esc_url( $activity->referer ) . '</a>';

	}

	/**
	 * @param $event Event
	 *
	 * @return string
	 */
	public function column_sent( $event ){
		return scheduled_time_column( $event->get_time(), true, $event->get_contact() );
	}

	public function display_tablenav( $which ) {
		if ( $which === 'top' ):
			?>
            <div class="tablenav <?php echo esc_attr( $which ); ?>">
				<?php $this->extra_tablenav( $which ); ?>
                <br class="clear"/>
            </div>
		<?php
		endif;
	}

	/**
	 * Get all the data!
	 */
	function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = array(); // No hidden columns
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$per_page = absint( get_url_var( 'limit', 10 ) );
		$paged    = $this->get_pagenum();
		$offset   = $per_page * ( $paged - 1 );
		$order    = get_url_var( 'order', 'DESC' );
		$orderby  = get_url_var( 'orderby', 'time' );

		global $wpdb;

		$events_table = get_db( 'events' );
		$steps_table  = get_db( 'steps' );

		$contact_id = absint( get_url_var( 'contact' ) );

		$data = $wpdb->get_results( $wpdb->prepare(
			"SELECT e.*,s.step_type FROM {$events_table->get_table_name()} e 
                        LEFT JOIN {$steps_table->get_table_name()} s ON e.step_id = s.ID 
                        WHERE e.contact_id = %d AND e.status = %s AND ( s.step_type = %s OR e.event_type = %s OR e.event_type = %s)
                        ORDER BY $orderby $order LIMIT $per_page OFFSET $offset"
			, $contact_id, 'complete', 'send_email', Event::BROADCAST, Event::EMAIL_NOTIFICATION )
		);

		$total = $wpdb->get_var( $wpdb->prepare(
			"SELECT count(*) FROM {$events_table->get_table_name()} e 
                        LEFT JOIN {$steps_table->get_table_name()} s ON e.step_id = s.ID 
                        WHERE e.contact_id = %d AND e.status = %s AND ( s.step_type = %s OR e.event_type = %s OR e.event_type = %s)"
			, $contact_id, 'complete', 'send_email', Event::BROADCAST, Event::EMAIL_NOTIFICATION )
		);

		$this->items = $data;

		// Add condition to be sure we don't divide by zero.
		// If $this->per_page is 0, then set total pages to 1.
		$total_pages = $per_page ? ceil( (int) $total / (int) $per_page ) : 1;

		$this->set_pagination_args( array(
			'total_items' => $total,
			'per_page'    => $per_page,
			'total_pages' => $total_pages,
		) );
	}
}