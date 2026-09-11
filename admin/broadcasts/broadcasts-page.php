<?php

namespace Groundhogg\Admin\Broadcasts;

use Groundhogg\Admin\Admin_Page;
use Groundhogg\Admin\Tabbed_Admin_Page;
use Groundhogg\Broadcast;
use Groundhogg\Campaign;
use Groundhogg\Classes\Recurring_Broadcast;
use Groundhogg\Utils\DateTimeHelper;
use WP_Error;
use function Groundhogg\admin_page_url;
use function Groundhogg\enqueue_broadcast_assets;
use function Groundhogg\enqueue_broadcast_calendar_assets;
use function Groundhogg\get_db;
use function Groundhogg\get_post_var;
use function Groundhogg\get_url_var;
use function Groundhogg\is_sms_plugin_active;
use function Groundhogg\map_to_class;
use function Groundhogg\notices;
use function Groundhogg\one_of;
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
class Broadcasts_Page extends Tabbed_Admin_Page {

	protected function add_ajax_actions() {
		add_action( 'wp_ajax_gh_estimate_send_duration', [ $this, 'ajax_estimate_send_duration' ] );
	}

	public function ajax_estimate_send_duration() {

		if ( ! verify_admin_ajax_nonce() || ! current_user_can( 'schedule_broadcasts' ) ) {
			$this->wp_die_no_access();
		}

		$total_contacts  = absint( get_post_var( 'total_contacts' ) );
		$amount          = absint( get_post_var( 'batch_amount' ) );
		$interval        = one_of( get_post_var( 'batch_interval' ), [ 'minutes', 'hours', 'days' ] );
		$interval_length = absint( get_post_var( 'batch_interval_length' ) );

		$batches = floor( $total_contacts / $amount );

		$dateTime              = new DateTimeHelper();
		$total_interval_length = $batches * $interval_length;

		$dateTime->modify( "+$total_interval_length $interval" );

		wp_send_json_success( [
			'time' => $dateTime->human_time_diff(),
		] );
	}

	public function help() {
	}

	protected function add_additional_actions() {
		if ( get_db( 'broadcasts' )->is_empty() && ! get_db( 'emails' )->exists( [ 'status' => 'ready' ] ) ) {

			notices()->add( 'dne', esc_html__( 'You must create an email before you can schedule a broadcast.', 'groundhogg' ), 'notice' );

			wp_safe_redirect( admin_page_url( 'gh_emails', [ 'action' => 'add' ] ) );
			exit();
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
	 * The calendar shows a whole month at a time, so there's nothing to paginate.
	 * Leaving the parent to add its "Per page" option would put an otherwise empty
	 * Screen Options tab at the top of the page.
	 */
	public function screen_options() {

		if ( $this->showing_calendar() ) {
			return;
		}

		parent::screen_options();
	}

	/**
	 * enqueue editor scripts
	 */
	public function scripts() {
		wp_enqueue_style( 'groundhogg-admin' );

		if ( $this->showing_calendar() ) {
			enqueue_broadcast_calendar_assets();

			// the switcher link in the calendar's month nav is built in JS, but the
			// nonce that lets it persist the choice has to come from here
			wp_add_inline_script( 'groundhogg-admin-broadcast-calendar', 'var GroundhoggBroadcastCalendar = ' . wp_json_encode( [
				'tableUrl' => $this->layout_url( 'table' ),
			] ), 'before' );

			return;
		}

		enqueue_broadcast_assets();
	}

	/**
	 * Meta key holding the user's preferred way of looking at the broadcasts list
	 */
	const LAYOUT_PREFERENCE = 'gh_broadcasts_layout';

	/**
	 * Nonce action guarding writes to the layout preference
	 */
	const LAYOUT_NONCE = 'gh_broadcasts_layout';

	/**
	 * A link that switches to the given layout and remembers the choice.
	 *
	 * Returned raw, callers escape it. array_to_atts() runs href through esc_url()
	 * and the calendar hands it to setAttribute, so wp_nonce_url()'s esc_html()
	 * would be wrong for both.
	 *
	 * @param string $layout
	 *
	 * @return string
	 */
	protected function layout_url( $layout ) {
		return add_query_arg( [
			'_layout_nonce' => wp_create_nonce( self::LAYOUT_NONCE ),
		], admin_page_url( 'gh_broadcasts', [ 'layout' => $layout ] ) );
	}

	/**
	 * Whether the broadcasts are being shown in the calendar rather than the classic table.
	 *
	 * The calendar is the default, the table is still available at ?layout=table
	 * for searching, sorting and bulk actions.
	 *
	 * @return bool
	 */
	protected function showing_calendar() {

		if ( $this->get_current_tab() !== 'broadcasts' || ! $this->current_action_is( 'view' ) ) {
			return false;
		}

		return $this->get_layout_preference() === 'calendar';
	}

	/**
	 * Which of the two layouts to show the broadcasts in.
	 *
	 * An explicit ?layout= in the URL wins and is remembered against the user, so
	 * whichever one they last switched to is the one they come back to.
	 *
	 * The param is deliberately not called 'view', which the Recurring Schedules
	 * table on the sibling tab already uses for its own status filters.
	 *
	 * @return string either 'calendar' or 'table'
	 */
	protected function get_layout_preference() {

		static $layout = null;

		// the answer can't change within a request, and this writes user meta
		if ( $layout !== null ) {
			return $layout;
		}

		$layouts   = [ 'calendar', 'table' ];
		$requested = get_url_var( 'layout' );

		// they followed one of the layout switcher links
		if ( $requested && in_array( $requested, $layouts, true ) ) {

			$layout = $requested;

			// The param alone only decides how this one request renders, which keeps
			// bookmarks and the table's own links working. Writing the preference
			// takes a nonce, so a forged link can't quietly change what the user
			// comes back to. A stale nonce still switches, it just doesn't stick.
			if ( wp_verify_nonce( get_url_var( '_layout_nonce' ), self::LAYOUT_NONCE )
			     && get_user_meta( get_current_user_id(), self::LAYOUT_PREFERENCE, true ) !== $layout ) {
				update_user_meta( get_current_user_id(), self::LAYOUT_PREFERENCE, $layout );
			}

			return $layout;
		}

		// one_of falls back to the first option, so an unset or junk value is a calendar
		$layout = one_of( get_user_meta( get_current_user_id(), self::LAYOUT_PREFERENCE, true ), $layouts );

		return $layout;
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

        /* translators: %d: the number of broadcasts getting cancelled */
		$this->add_notice( 'cancelled', sprintf( _nx( '%d broadcast cancelled', '%d broadcasts cancelled', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $this->get_items() ) ) );

		return false;
	}

	public function process_cancel_recurring() {
		if ( ! current_user_can( 'cancel_broadcasts' ) ) {
			$this->wp_die_no_access();
		}

		foreach ( $this->get_items() as $id ) {

			$broadcast = new Recurring_Broadcast( $id );
			$broadcast->cancel();
		}

		/* translators: %d: the number of broadcasts getting cancelled */
		$this->add_notice( 'cancelled', sprintf( _nx( '%d schedule cancelled', '%d schedules cancelled', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $this->get_items() ) ) );

		return false;
	}

	public function process_resume_recurring() {
		if ( ! current_user_can( 'cancel_broadcasts' ) ) {
			$this->wp_die_no_access();
		}

		foreach ( $this->get_items() as $id ) {

			$broadcast = new Recurring_Broadcast( $id );
			$broadcast->resume();
		}

		/* translators: %d: the number of broadcasts getting resumed */
		$this->add_notice( 'resumed', sprintf( _nx( '%d schedule resumed', '%d schedules resumed', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $this->get_items() ) ) );

		return false;
	}

	public function process_add_campaigns() {
		if ( ! current_user_can( 'manage_campaigns' ) ) {
			$this->wp_die_no_access();
		}

        $campaigns = wp_parse_id_list( get_post_var( 'bulk_campaigns' ) );
        $campaigns = map_to_class( $campaigns, Campaign::class );
		foreach ( $this->get_items() as $id ) {
			$broadcast = new Broadcast( $id );
            foreach ( $campaigns as $campaign ) {
                $broadcast->create_relationship( $campaign );
            }

		}

		$this->add_notice( 'updated', __( 'Broadcast campaigns updated!', 'groundhogg' ) );

		return false;
	}

	public function process_remove_campaigns() {
		if ( ! current_user_can( 'manage_campaigns' ) ) {
			$this->wp_die_no_access();
		}

		$campaigns = wp_parse_id_list( get_post_var( 'bulk_campaigns' ) );
		$campaigns = map_to_class( $campaigns, Campaign::class );
		foreach ( $this->get_items() as $id ) {
			$broadcast = new Broadcast( $id );
			foreach ( $campaigns as $campaign ) {
				$broadcast->delete_relationship( $campaign );
			}
		}

		$this->add_notice( 'updated', __( 'Broadcast campaigns updated!', 'groundhogg' ) );

		return false;
	}

	/**
	 * Delete
	 *
	 * @return bool|WP_Error
	 */
	public function process_delete() {
		if ( ! current_user_can( 'delete_emails' ) ) {
			$this->wp_die_no_access();
		}

        $deleted = 0;

		foreach ( $this->get_items() as $id ) {
			$broadcast = new Broadcast( $id );
			if ( $broadcast->delete() ){
                $deleted++;
            }
		}

		$this->add_notice(
			esc_attr( 'deleted' ),
			/* translators: %d: the number of broadcasts getting deleted */
			sprintf( _nx( 'Deleted %d broadcast', 'Deleted %d broadcasts', $deleted, 'notice', 'groundhogg' ), $deleted ),
			'success'
		);

		return false;
	}

	/**
	 * Delete
	 *
	 * @return bool|WP_Error
	 */
	public function process_delete_recurring() {
		if ( ! current_user_can( 'delete_emails' ) ) {
			$this->wp_die_no_access();
		}

		$deleted = 0;

		foreach ( $this->get_items() as $id ) {
			$schedule = new Recurring_Broadcast( $id );
			if ( $schedule->delete() ){
				$deleted++;
			}
		}

		$this->add_notice(
			esc_attr( 'deleted' ),
			/* translators: %d: the number of broadcasts getting deleted */
			sprintf( _nx( 'Deleted %d schedule', 'Deleted %d schedules', $deleted, 'notice', 'groundhogg' ), $deleted ),
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
			'action' => esc_html__( 'Schedule Email Broadcast', 'groundhogg' ),
			'target' => '_self',
			'id'     => 'gh-schedule-broadcast'
		];

		if ( is_sms_plugin_active() ) {
			$actions[] = [
				'link'   => $this->admin_url( [ 'action' => 'add', 'type' => 'sms' ] ),
				'action' => esc_html__( 'Schedule SMS Broadcast', 'groundhogg' ),
				'target' => '_self',
				'id'     => 'gh-schedule-sms-broadcast'
			];
		}

		// the calendar has its own link back to the table in the month nav
		if ( $this->get_current_tab() === 'broadcasts' && $this->current_action_is( 'view' ) && ! $this->showing_calendar() ) {
			$actions[] = [
				'link'   => $this->layout_url( 'calendar' ),
				'action' => esc_html__( 'Calendar view', 'groundhogg' ),
				'target' => '_self',
				'id'     => 'gh-broadcast-calendar-view'
			];
		}

		return $actions;
	}

	/**
	 * Display the broadcasts, either in the calendar or the classic table
	 */
	public function view() {

		// fix sending broadcasts
		Broadcast::transition_from_sending_to_sent();

		if ( $this->showing_calendar() ) {
			$this->calendar();

			return;
		}

		$broadcasts_table = new Broadcasts_Table();

		$this->search_form( esc_html__( 'Search Broadcasts', 'groundhogg' ) );
		$broadcasts_table->views(); ?>
        <form method="post" class="wp-clearfix">
            <!-- search form -->
			<?php $broadcasts_table->prepare_items(); ?>
			<?php $broadcasts_table->display(); ?>
        </form>

		<?php
	}

	/**
	 * Mount point for the broadcast calendar, everything else is rendered by
	 * assets/js/admin/broadcasts/broadcast-calendar.js
	 */
	public function calendar() {
		?>
        <div id="gh-broadcast-calendar-mount" style="margin-top: 20px"></div>
		<?php
	}

	/**
	 * Display the table
	 */
	public function view_recurring() {

		$broadcasts_table = new Recurring_Schedules_Table();

		$this->search_form( esc_html__( 'Search', 'groundhogg' ) );
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

	protected function get_tabs() {
		$tabs = [
			[
				'name' => esc_html__( 'Broadcasts', 'groundhogg' ),
				'slug' => 'broadcasts'
			],
			[
				'name' => esc_html__( 'Recurring Schedules', 'groundhogg' ),
				'slug' => 'recurring'
			],
		];

		return $tabs;
	}
}
