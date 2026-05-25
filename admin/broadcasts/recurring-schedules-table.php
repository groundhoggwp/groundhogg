<?php

namespace Groundhogg\Admin\Broadcasts;

// Exit if accessed directly
use Groundhogg\Admin\Table;
use Groundhogg\Broadcast;
use Groundhogg\Classes\Background_Task;
use Groundhogg\Classes\Recurring_Broadcast;
use Groundhogg\Utils\DateTimeHelper;
use WP_List_Table;
use function Groundhogg\action_url;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_db;
use function Groundhogg\html;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Recurring_Schedules_Table extends Table {

	/**
	 * TT_Example_List_Table constructor.
	 *
	 * REQUIRED. Set up a constructor that references the parent constructor. We
	 * use the parent reference to set some default configs.
	 */
	public function __construct() {
		// Set parent defaults.
		parent::__construct( array(
			'singular' => 'recurring schedule',     // Singular name of the listed records.
			'plural'   => 'recurring schedules',    // Plural name of the listed records.
			'ajax'     => false, // Does this table support ajax?
		) );
	}

	protected function get_table_classes() {
		$mode = get_user_setting( 'posts_list_mode', 'list' );

		$mode_class = esc_attr( 'table-view-' . $mode );

		return array( 'widefat', 'striped', $mode_class, $this->_args['plural'] );
	}

	/**
	 * @inheritDoc
	 */
	function get_table_id() {
		return 'recurring_schedules_table';
	}

	/**
	 * @inheritDoc
	 */
	function get_db() {
		return get_db( 'broadcasts' );
	}

	/**
	 * @inheritDoc
	 */
	protected function get_views_setup() {
		return [
			[
				'view'    => 'active',
				'display' => esc_html__( 'Active', 'groundhogg' ),
				'query'   => [ 'object_type' => 'recurring_broadcast', 'status' => 'active' ],
			],
			[
				'view'    => 'done',
				'display' => esc_html__( 'Cancelled', 'groundhogg' ),
				'query'   => [ 'object_type' => 'recurring_broadcast', 'status' => 'done' ],
			],
			[
				'view'    => 'paused',
				'display' => esc_html__( 'Paused', 'groundhogg' ),
				'query'   => [ 'object_type' => 'recurring_broadcast', 'status' => 'paused' ],
			],
			[
				'view'    => 'cancelled',
				'display' => esc_html__( 'Cancelled', 'groundhogg' ),
				'query'   => [ 'object_type' => 'recurring_broadcast', 'status' => 'cancelled' ],
			]
		];
	}

	/**
	 * @inheritDoc
	 */
	function get_default_query() {
		return [
			'object_type' => 'recurring_broadcast'
		];
	}

	/**
	 * Get a list of columns. The format is:
	 * 'internal-name' => 'Title'
	 *
	 * bulk steps or checkboxes, simply leave the 'cb' entry out of your array.
	 *
	 * @see WP_List_Table::::single_row_columns()
	 * @return array An associative array containing column information.
	 */
	public function get_columns() {
		$columns = array(
			'cb'             => '<input type="checkbox" />', // Render a checkbox instead of text.
			'broadcast'      => _x( 'Broadcast', 'Column label', 'groundhogg' ),
			'schedule'       => _x( 'Schedule', 'Column label', 'groundhogg' ),
			'next_send'      => _x( 'Next Send', 'Column label', 'groundhogg' ),
			'ends'           => _x( 'Ends', 'Column label', 'groundhogg' ),
//			'campaigns'      => _x( 'Campaigns', 'Column label', 'groundhogg' ),
		);

		return $columns;
	}

	/**
	 * Get a list of sortable columns. The format is:
	 * 'internal-name' => 'orderby'
	 * or
	 * 'internal-name' => array( 'orderby', true )
	 *
	 * @return array An associative array containing all the columns that should be sortable.
	 */
	protected function get_sortable_columns() {

		$sortable_columns = array(
			'object_id'      => array( 'object_id', false ),
			'next_send'      => array( 'next_send', false )
		);

		return $sortable_columns;
	}

	protected function parse_item( $item ) {
		return new Recurring_Broadcast( $item );
	}

	/**
	 * @param $schedule Recurring_Broadcast
	 *
	 * @return string
	 */
	protected function column_broadcast( Recurring_Broadcast $schedule ) {

		return html()->e( 'a', [
			'class' => 'row-title',
			'href'  => admin_page_url( 'gh_broadcasts', [
				'tab'         => 'broadcasts',
				'schedule_id' => $schedule->get_id()
			] )
		], $schedule->get_title() );
	}

	protected function column_schedule( Recurring_Broadcast $schedule ) {
		html( 'div', [ 'class' => 'schedule-preview', 'data-id' => $schedule->get_id() ] );
	}

	protected function column_next_send( Recurring_Broadcast $schedule ) {

		$date = new DateTimeHelper( $schedule->get_broadcast()->get_send_time() );

		html( 'abbr', [
			'title' => $date->ymdhis()
		], $date->i18n() );
	}

	protected function column_ends( Recurring_Broadcast $schedule ) {
		$ends = $schedule->get_end_date();

		if ( ! $ends ){
			return '&#x2014;';
		}

		html( 'abbr', [
			'title' => $ends->ymdhis()
		], $ends->date_i18n() );

		if ( $schedule->get_meta( 'repeats_until' ) === 'occurrences' ) {
			html( 'div', [ 'class' => 'count' ], $schedule->count_scheduled_broadcasts() . '/' . $schedule->get_meta( 'repeats_until_occurrences') );
		}
	}

	protected function column_campaigns() {

	}

	/**
	 * @param $item Recurring_Broadcast
	 * @param $column_name
	 * @param $primary
	 *
	 * @return array
	 */
	protected function get_row_actions( $item, $column_name, $primary ) {

		$actions = [];

		if ( $item->is_email() ){
			$actions[] = [ 'class' => 'edit', 'display' => esc_html__( 'Preview', 'groundhogg' ), 'url' => '#gh-email-preview/' . $item->get_broadcast()->get_object_id() ];
		}

		if ( $item->status_is( 'cancelled' ) && current_user_can( 'schedule_broadcasts' ) ){
			$actions[] = [ 'class' => 'edit', 'display' => esc_html_x( 'Resume', 'as in resume a broadcast', 'groundhogg' ), 'url' => action_url( 'resume', [ 'broadcast' => $item->ID ] ) ];
		}

		if ( $item->status_is( 'cancelled' ) && current_user_can( 'delete_emails' ) ){
			$actions[] = [ 'class' => 'trash', 'linkProps' => [
				'class' => 'danger-delete',
			], 'display' => esc_html__( 'Delete', 'groundhogg' ), 'url' => action_url( 'delete', [ 'broadcast' => $item->ID ] ) ];
		}

		if ( $item->status_is( 'active' ) && current_user_can( 'cancel_broadcasts' ) ){
			$actions[] = [ 'class' => 'trash', 'linkProps' => [
				'class' => 'danger-confirm',
				'data-alert' => __( 'Are you sure? Any pending broadcasts associated with this schedule will also be cancelled.', 'groundhogg' )
			], 'display' => esc_html_x( 'Cancel', 'as in cancel a broadcast', 'groundhogg' ), 'url' => action_url( 'cancel', [ 'broadcast' => $item->ID ] ) ];
		}

		return $actions;
	}

	/**
	 * Get an associative array ( option_name => option_title ) with the list
	 * of bulk steps available on this table.
	 *
	 * @return array An associative array containing all the bulk steps.
	 */
	protected function get_bulk_actions() {

		$actions = [];

		if ( current_user_can( 'cancel_broadcasts' ) && $this->get_view() === 'active' ){
			$actions['cancel'] = esc_html__( 'Cancel', 'groundhogg' );
		}

		if ( current_user_can( 'schedule_broadcasts' ) && $this->get_view() === 'cancelled' ){
			$actions['resume'] = esc_html__( 'Resume', 'groundhogg' );
		}

		if ( current_user_can( 'delete_emails' ) && $this->get_view() === 'cancelled' ){
			$actions['delete'] = esc_html__( 'Delete', 'groundhogg' );
		}

		return apply_filters( 'groundhogg/log/bulk_actions', $actions );
	}


}
