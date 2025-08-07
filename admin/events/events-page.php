<?php

namespace Groundhogg\Admin\Events;

use Exception;
use Groundhogg\Admin\Tabbed_Admin_Page;
use Groundhogg\Classes\Activity;
use Groundhogg\Classes\Background_Task;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Email_Log_Item;
use Groundhogg\Email_Logger;
use Groundhogg\Event;
use Groundhogg\Plugin;
use Groundhogg\Utils\Micro_Time_Tracker;
use Groundhogg_Email_Services;
use WP_Error;
use WP_User;
use function Groundhogg\_nf;
use function Groundhogg\admin_page_url;
use function Groundhogg\db;
use function Groundhogg\enqueue_filter_assets;
use function Groundhogg\event_queue_db;
use function Groundhogg\get_db;
use function Groundhogg\get_post_var;
use function Groundhogg\get_request_query;
use function Groundhogg\get_request_var;
use function Groundhogg\get_url_var;
use function Groundhogg\html;
use function Groundhogg\restore_missing_funnel_events;
use function Groundhogg\verify_admin_ajax_nonce;

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
		add_action( 'wp_ajax_gh_process_bg_task', [ $this, 'ajax_process_bg_task' ] );
	}

	public function ajax_process_bg_task() {

		if ( ! current_user_can( 'manage_options' ) || ! verify_admin_ajax_nonce() ) {
			$this->wp_die_no_access();
		}

		$task = new Background_Task( absint( get_post_var( 'task' ) ) );

		if ( ! $task->exists() || $task->is_claimed() ) {
			wp_send_json_error();
		}

		try {
			$task->process();
		} catch ( Exception $e ) {
			wp_send_json_error( new WP_Error( 'error', $e->getMessage() ) );
		}

		wp_send_json_success( [
			'done'     => $task->is_done(),
			'progress' => $task->get_progress()
		] );
	}

	public function help() {
	}

	protected function add_additional_actions() {
	}

	public function scripts() {
		wp_enqueue_style( 'groundhogg-admin' );

		switch ( $this->get_current_tab() ) {
			case 'emails':

				$errorCodeQuery = new Table_Query( 'email_log' );
				$errorCodeQuery
					->setSelect( 'error_code' )->setGroupby( 'error_code' );
				$error_codes = array_filter( wp_list_pluck( $errorCodeQuery->get_results(), 'error_code' ) );
				$error_codes = array_combine( $error_codes, $error_codes );

				$this->enqueue_table_filters( [
					'selectColumns' => [
						'email_service' => [ 'Email service', Groundhogg_Email_Services::dropdown() ],
						'message_type'  => [
							'Message Type',
							[
								Groundhogg_Email_Services::MARKETING     => 'Marketing',
								Groundhogg_Email_Services::TRANSACTIONAL => 'Transactional',
								Groundhogg_Email_Services::WORDPRESS     => 'WordPress'
							],
						],
						'status'        => [
							'Status',
							[
								'sent'   => 'Sent',
								'failed' => 'Failed'
							]
						],
						'error_code'    => [ 'Error code', $error_codes ]
					],
					'stringColumns' => [
						'from_address'  => 'From address',
						'subject'       => 'Subject',
						'content'       => 'Content',
						'headers'       => 'Headers',
						'error_message' => 'Error message',
					],
					'dateColumns'   => [
						'date_sent' => 'Date sent'
					]
				] );

				wp_enqueue_script( 'groundhogg-admin-filter-email-log' );
				wp_enqueue_script( 'groundhogg-admin-email-log' );

				break;
			case 'events':

				enqueue_filter_assets();

				$status = get_url_var( 'status' );

				switch ( $status ) {
					default:
					case 'waiting':
					case 'paused':
					case 'pending':

						$this->enqueue_table_filters( [
							'futureDateColumns' => [
								'time' => 'Will complete'
							],
						] );

						break;
					case 'complete':
					case 'cancelled':
						$this->enqueue_table_filters( [
							'dateColumns' => [
								'time' => 'Date completed'
							]
						] );

						break;
					case 'failed':
					case 'skipped':

						$errorCodeQuery = new Table_Query( 'events' );
						$errorCodeQuery
							->setSelect( 'error_code' )->setGroupby( 'error_code' )
							->where( 'status', $status );
						$error_codes = array_filter( wp_list_pluck( $errorCodeQuery->get_results(), 'error_code' ) );
						$error_codes = array_combine( $error_codes, $error_codes );

						$this->enqueue_table_filters( [
							'stringColumns' => [
								'error_message' => 'Error message',
							],
							'dateColumns'   => [
								'time' => 'Date attempted'
							],
							'selectColumns' => [
								'error_code' => [ 'Error code', $error_codes ]
							]
						] );

						break;
				}

				wp_enqueue_script( 'groundhogg-admin-filter-events' );

				break;
			case 'tasks':

				$user_ids = array_filter( get_db( 'background_tasks' )->get_unique_column_values( 'user_id' ) );
				$users    = array_combine( $user_ids, array_map( function ( $user_id ) {
					return ( new WP_User( $user_id ) )->display_name;
				}, $user_ids ) );

				$this->enqueue_table_filters( [
					'dateColumns'   => [
						'date_created' => 'Date created'
					],
					'selectColumns' => [
						'task_type' => [
							'Task Type',
							[
								'Import_Contacts'        => esc_html__( 'Import contacts', 'groundhogg' ),
								'Export_Contacts'        => esc_html__( 'Export contacts', 'groundhogg' ),
								'Schedule_Broadcast'     => esc_html__( 'Schedule broadcast', 'groundhogg' ),
								'Update_Contacts'        => esc_html__( 'Update contacts', 'groundhogg' ),
								'Delete_Contacts'        => esc_html__( 'Delete contacts', 'groundhogg' ),
								'Add_Contacts_To_Funnel' => esc_html__( 'Add contacts to flow', 'groundhogg' ),
								'Complete_Benchmark'     => esc_html__( 'Trigger Flow', 'groundhogg' ),
							]
						],
						'user_id'   => [ 'User', $users ]
					]
				] );

				break;
		}
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
			case 'tasks' :
				return 'task';
			case 'events' :
				return 'event';
			case 'emails' :
				return 'email';
		}
	}

	public function get_priority() {
		return 100;
	}

	protected function get_title_actions() {

		if ( $this->get_current_tab() !== 'emails' ) {
			return [];
		}

		return [
			[
				'link'   => admin_page_url( 'gh_settings', [ 'tab' => 'email' ], 'email-logging' ),
				'action' => esc_html__( 'Settings', 'groundhogg' ),
				'target' => '_self',
			]
		];
	}

	/**
	 *  Sets the title of the page
	 *
	 * @return string
	 */
	public function get_title() {
		return _x( 'Logs', 'page_title', 'groundhogg' );
	}

	/**
	 * Pause some events
	 *
	 * @return bool|WP_Error
	 */
	public function process_pause() {

		if ( ! current_user_can( 'cancel_events' ) ) {
			$this->wp_die_no_access();
		}

		$query_params = get_request_query();
		$items        = $this->get_items();

		if ( ! empty( $items ) ) {
			$query_params['include'] = $items;
		}

		$query = new Table_Query( 'event_queue' );
		$query->set_query_params( $query_params );
		$query->where()->equals( 'status', Event::WAITING );

		$result = $query->update( [
			'status'         => Event::PAUSED,
			'time_scheduled' => time(),
		] );

		if ( ! $result ) {
			return new WP_Error( 'error', 'Something went wrong' );
		}

		$this->add_notice( 'paused', sprintf( _nx( '%d event paused', '%d events paused', $result, 'notice', 'groundhogg' ), _nf( $result ) ) );

		return false;
	}

	/**
	 * Unpause some events
	 *
	 * @return bool|WP_Error
	 */
	public function process_unpause() {

		if ( ! current_user_can( 'execute_events' ) ) {
			$this->wp_die_no_access();
		}

		$query_params = get_request_query();
		$items        = $this->get_items();

		if ( ! empty( $items ) ) {
			$query_params['include'] = $items;
		}

		$query = new Table_Query( 'event_queue' );
		$query->set_query_params( $query_params );
		$query->where()->equals( 'status', Event::PAUSED );

		$result = $query->update( [
			'status'         => Event::WAITING,
			'time_scheduled' => time(),
		] );

		if ( ! $result ) {
			return new WP_Error( 'error', 'Something went wrong' );
		}

		$this->add_notice( 'paused', sprintf( _nx( '%d event unpaused', '%d events unpaused', $result, 'notice', 'groundhogg' ), _nf( $result ) ) );

		return false;
	}

	/**
	 * Cancel some events
	 *
	 * @return bool|WP_Error
	 */
	public function process_cancel() {

		if ( ! current_user_can( 'cancel_events' ) ) {
			$this->wp_die_no_access();
		}

		$query_params = get_request_query();
		$items        = $this->get_items();

		if ( ! empty( $items ) ) {
			$query_params['include'] = $items;
		}

		$query = new Table_Query( 'event_queue' );
		$query->set_query_params( $query_params );

		$result = $query->update( [
			'status'         => Event::CANCELLED,
			'time_scheduled' => time(), // use time scheduled as time_cancelled
		] );

		if ( ! $result ) {
			return new WP_Error( 'error', 'Something went wrong' );
		}

		// Move the items over...
		db()->event_queue->move_events_to_history( [ 'status' => Event::CANCELLED ] );

		$this->add_notice( 'cancelled', sprintf( _nx( '%s event cancelled', '%d events cancelled', $result, 'notice', 'groundhogg' ), _nf( $result ) ) );

		//false return users to the main page
		return false;
	}

	/**
	 * Uncancels any cancelled events...
	 *
	 * @return bool|WP_Error
	 */
	public function process_uncancel() {
		if ( ! current_user_can( 'execute_events' ) ) {
			$this->wp_die_no_access();
		}

		$query_params = get_request_query();
		$items        = $this->get_items();

		if ( ! empty( $items ) ) {
			$query_params['include'] = $items;
		}

		$query = new Table_Query( 'events' );
		$query->set_query_params( $query_params );

		$result = $query->update( [
			'status'         => Event::WAITING,
			'time_scheduled' => time(),
		] );

		if ( ! $result ) {
			return new WP_Error( 'db_error', esc_html__( 'There was an error updating the database.', 'groundhogg' ) );
		}

		// Move the events over...
		get_db( 'events' )->move_events_to_queue( [ 'status' => Event::WAITING ], true );

		$this->add_notice( 'scheduled', sprintf( _nx( '%s event uncancelled', '%s events uncancelled', $result, 'notice', 'groundhogg' ), _nf( $result ) ) );

		return false;
	}

	/**
	 * Delete any failed or cancelled events.
	 */
	public function process_purge() {
		if ( ! current_user_can( 'cancel_events' ) ) {
			$this->wp_die_no_access();
		}

		$status           = get_url_var( 'status' );
		$purgeable_events = [ Event::FAILED, Event::CANCELLED, Event::SKIPPED ];

		if ( empty( $status ) || ! in_array( $status, $purgeable_events ) ) {
			return new WP_Error( 'invalid_status', esc_html__( 'Invalid status.', 'groundhogg' ) );
		}

		$query_params = get_request_query();
		$items        = $this->get_items();

		if ( ! empty( $items ) ) {
			$query_params['include'] = $items;
		}

		$query = new Table_Query( 'events' );
		$query->set_query_params( $query_params );
		$result = $query->delete();

		if ( $result !== false ) {
			$this->add_notice( 'events_purged', sprintf( esc_html__( 'Purged %s events!' ), _nf( $result ) ) );
		}

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

		$time = time() - ( 5 * MINUTE_IN_SECONDS );
		$wpdb->query( "UPDATE {$events->get_table_name()} SET claim = '' WHERE claim != '' AND time < $time" );
		$wpdb->query( "UPDATE {$events->get_table_name()} SET status = 'complete' WHERE status = 'in_progress' AND time < $time" );

		$events->move_events_to_history( [
			'status' => [ Event::COMPLETE, Event::SKIPPED, Event::FAILED ]
		] );

		get_db( 'events' )->move_events_to_queue( [
			'status' => Event::WAITING
		], true );

		return false;
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

		$event_queue = get_db( 'event_queue' );

		$updated = $event_queue->query( [
			'operation' => 'UPDATE',
			'data'      => [
				'time'   => time(),
				'status' => Event::WAITING,
				'claim'  => '',
			],
			'ID'        => wp_parse_id_list( $this->get_items() ),
		] );

		$this->add_notice( 'scheduled', sprintf( _nx( '%s event rescheduled', '%s events rescheduled', $updated, 'notice', 'groundhogg' ), number_format_i18n( $updated ) ) );

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

		// Move the events over... only delete if the status is not complete
		get_db( 'events' )->move_events_to_queue( [ 'ID' => $this->get_items() ], get_request_var( 'status' ) === Event::COMPLETE ? false : true, [
			'time'   => time(),
			'status' => Event::WAITING
		] );

		$this->add_notice( 'scheduled', sprintf( _nx( '%d event rescheduled', '%d events rescheduled', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $this->get_items() ) ) );

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

		$this->table_filters();

		$this->filters_search_form();
		?>
        <form method="post" class="search-form wp-clearfix">
            <!-- search form -->
			<?php $events_table->prepare_items(); ?>
			<?php $events_table->display(); ?>
        </form>
		<?php
	}

	public function view_tasks() {
		if ( ! current_user_can( 'manage_options' ) ) {
			$this->wp_die_no_access();
		}

		$table = new Background_Tasks_Table();

		$table->views();

		$this->table_filters();

		$this->filters_search_form();
		?>
        <form method="post" class="search-form wp-clearfix">
            <!-- search form -->
			<?php $table->prepare_items(); ?>
			<?php $table->display(); ?>
        </form>
        <script>
          ( ($) => {

            const {
              ProgressBar,
            } = MakeEl

            $(document).on('click', '.do-task', e => {

              e.preventDefault()
              let row = e.target.closest('tr')
              let progressEl = row.querySelector('.task-progress')
              let taskId = progressEl.dataset.id
              let progress = progressEl.dataset.progress ?? 0

              const SmallProgressBar = (props = {}) => ProgressBar({
                percent  : progress,
                className: 'small',
                ...props,
              })

              let progressBar = SmallProgressBar()

              const doTask = () => Groundhogg.api.ajax({
                action             : 'gh_process_bg_task',
                task               : taskId,
                gh_admin_ajax_nonce: Groundhogg.nonces._adminajax,
              }).then(({
                data,
                success,
              }) => {

                if (success === false) {
                  morphdom(row.querySelector('.gh-progress-bar'), SmallProgressBar({
                    error: true,
                  }))
                  Groundhogg.element.dialog({
                    type   : 'error',
                    message: data[0].message,
                  })
                  return
                }

                progress = data.progress

                morphdom(row.querySelector('.gh-progress-bar'), SmallProgressBar())

                if (data.done) {
                  window.location.reload()
                }
                else {
                  doTask()
                }

              }).catch(err => {

                morphdom(row.querySelector('.gh-progress-bar'), SmallProgressBar({
                  error: true,
                }))

              })

              morphdom(progressEl, progressBar)
              doTask()

            })

          } )(jQuery)
        </script>
		<?php
	}

	/**
	 * @inheritDoc
	 */
	protected function get_tabs() {
		return [
			[
				'name' => esc_html__( 'Events', 'groundhogg' ),
				'slug' => 'events',
				'cap'  => 'view_events'
			],
			[
				'name' => esc_html__( 'Emails', 'groundhogg' ),
				'slug' => 'emails',
				'cap'  => 'view_logs'

			],
			[
				'name' => esc_html__( 'Background Tasks', 'groundhogg' ),
				'slug' => 'tasks',
				'cap'  => 'manage_options'
			],
			[
				'name' => esc_html__( 'Manage', 'groundhogg' ),
				'slug' => 'manage',
				'cap'  => 'view_events'
			],
		];
	}

	public function view_emails() {

		if ( ! current_user_can( 'view_events' ) ) {
			$this->wp_die_no_access();
		}

		$log_table = new Email_Log_Table();

		$log_table->views();

		$this->table_filters();

		?>
        <form method="get" class="search-form">
			<?php html()->hidden_GET_inputs( true ); ?>
            <input type="hidden" name="page" value="<?php echo esc_attr( get_request_var( 'page' ) ); ?>">
            <label class="screen-reader-text" for="gh-post-search-input"><?php esc_html_e( 'Search' ); ?>:</label>

			<?php if ( ! get_url_var( 'include_filters' ) ):
				echo html()->input( [
					'type' => 'hidden',
					'name' => 'include_filters'
				] );
			endif; ?>

            <div style="float: right" class="gh-input-group">
                <input type="search" id="gh-post-search-input" name="s"
                       value="<?php echo esc_attr( get_request_var( 's' ) ); ?>">
				<?php

				echo html()->dropdown( [
					'options'           => [
						'subject'    => esc_html__( 'Subject', 'groundhogg' ),
						'content'    => esc_html__( 'Body', 'groundhogg' ),
						'recipients' => esc_html__( 'Recipients', 'groundhogg' ),
						'headers'    => esc_html__( 'Headers', 'groundhogg' )
					],
					'option_none'       => esc_html__( 'Everywhere', 'groundhogg' ),
					'option_none_value' => '',
					'name'              => 'search_columns',
					'selected'          => get_request_var( 'search_columns' )
				] );

				?>
                <button type="submit" id="search-submit"
                        class="gh-button primary small"><?php esc_html_e( 'Search' ); ?></button>
            </div>
        </form>
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

	public function view_manage() {

		if ( ! current_user_can( 'view_events' ) ) {
			$this->wp_die_no_access();
		}

		include __DIR__ . '/manage.php';
	}

	/**
	 * Delete any failed or cancelled events.
	 */
	public function process_purge_completed_tool() {
		if ( ! current_user_can( 'cancel_events' ) ) {
			$this->wp_die_no_access();
		}

		global $wpdb;

		$time_range     = absint( get_post_var( 'time_range' ) );
		$time_unit      = sanitize_text_field( get_post_var( 'time_unit' ) );
		$what_to_delete = get_post_var( 'what_to_delete' );
		$confirm        = get_post_var( 'confirm' );
		$events         = get_db( 'events' );

		if ( $confirm !== 'confirm' ) {
			return new WP_Error( 'confirmation', 'Please type "confirm" in all lowercase to confirm the action.' );
		}

		$time = strtotime( "$time_range $time_unit ago" );

		if ( ! $time ) {
			return new WP_Error( 'invalid', 'Invalid time range supplied, no action was taken.' );
		}

		switch ( $what_to_delete ) {
			default:
			case 'all':
				$type_clause = "1=1";
				break;
			case 'funnel':
				$type_clause = "event_type = " . Event::FUNNEL;
				break;
			case 'broadcast':
				$type_clause = "event_type = " . Event::BROADCAST;
				break;
			case 'other':
				$type_clause = sprintf( "event_type NOT IN ( %s )", implode( ',', [
					Event::BROADCAST,
					Event::FUNNEL
				] ) );
				break;
		}

		$results = $wpdb->query( "
DELETE FROM {$events->get_table_name()} 
WHERE status = 'complete' AND `time` < {$time} AND $type_clause
ORDER BY ID" );

		$this->add_notice( 'success', $results ?
			sprintf( '%s logs have been purged!', number_format_i18n( $results ) ) :
			'The query ran successfully but there were no unprocessed logs for the given time range.' );

		return false;
	}

	/**
	 * Delete any failed or cancelled events.
	 */
	public function process_purge_activity_tool() {
		if ( ! current_user_can( 'cancel_events' ) ) {
			$this->wp_die_no_access();
		}

		global $wpdb;

		$time_range     = absint( get_post_var( 'time_range' ) );
		$time_unit      = sanitize_text_field( get_post_var( 'time_unit' ) );
		$what_to_delete = get_post_var( 'what_to_delete' );
		$confirm        = get_post_var( 'confirm' );
		$activity       = get_db( 'activity' );

		if ( $confirm !== 'confirm' ) {
			return new WP_Error( 'confirmation', 'Please type "confirm" in all lowercase to confirm the action.' );
		}

		$time = strtotime( "$time_range $time_unit ago" );

		if ( ! $time ) {
			return new WP_Error( 'invalid', 'Invalid time range supplied, no action was taken.' );
		}

		switch ( $what_to_delete ) {
			default:
			case 'all':
				$type_clause = "1=1";
				break;
			case 'opens':
				$type_clause = $wpdb->prepare( "activity_type = %s", Activity::EMAIL_OPENED );
				break;
			case 'clicks':
				$type_clause = $wpdb->prepare( "activity_type = %s", Activity::EMAIL_CLICKED );
				break;
			case 'login':
				$type_clause = $wpdb->prepare( "activity_type = %s", Activity::LOGIN );

				break;
		}

		$results = $wpdb->query( "
DELETE FROM {$activity->get_table_name()} 
WHERE `timestamp` < {$time} AND $type_clause
ORDER BY ID" );

		$this->add_notice( 'success', $results ?
			sprintf( '%s logs have been purged!', number_format_i18n( $results ) ) :
			'The query ran successfully but there were no unprocessed logs for the given time range.' );

		return false;
	}

	/**
	 * Admin tool to cancel waiting events
	 *
	 * @return false|WP_Error
	 */
	public function process_cancel_events_tool() {

		if ( ! current_user_can( 'cancel_events' ) ) {
			$this->wp_die_no_access();
		}

		global $wpdb;
		$events = event_queue_db();

		$what_to_cancel = sanitize_text_field( get_post_var( 'what_to_cancel' ) );
		$confirm        = get_post_var( 'confirm' );

		if ( $confirm !== 'confirm' ) {
			return new WP_Error( 'confirmation', 'Please type "confirm" in all lowercase to confirm the action.' );
		}

		switch ( $what_to_cancel ) {
			case 'all':

				$results = $wpdb->query( "
UPDATE {$events->get_table_name()} 
SET status = 'cancelled'
ORDER BY ID" );

				break;
			case 'waiting':
				event_queue_db()->update( [
					'status' => Event::WAITING
				], [
					'status' => Event::CANCELLED
				] );
				break;
			case 'paused':

				event_queue_db()->update( [
					'status' => Event::PAUSED
				], [
					'status' => Event::CANCELLED
				] );

				break;
			case 'funnel':
				event_queue_db()->update( [
					'event_type' => Event::FUNNEL
				], [
					'status' => Event::CANCELLED
				] );

				break;
			case 'broadcast':
				event_queue_db()->update( [
					'event_type' => Event::BROADCAST
				], [
					'status' => Event::CANCELLED
				] );

				break;
		}

		$results = $wpdb->rows_affected;

		if ( $results ) {
			event_queue_db()->move_events_to_history( [
				'status' => Event::CANCELLED
			] );
		}

		$this->add_notice( 'success', $results ?
			sprintf( '%s events have been cancelled!', number_format_i18n( $results ) ) :
			'The query ran successfully but there were no events found matching the given parameters.' );

		return false;
	}


	/**
	 * Tool to fix unprocessed events by rescheduling them or cancelling them
	 *
	 * @return false|WP_Error
	 */
	public function process_fix_unprocessed() {

		if ( ! current_user_can( 'cancel_events' ) ) {
			$this->wp_die_no_access();
		}

		global $wpdb;

		$events = event_queue_db();

		$fix_or_cancel    = sanitize_text_field( get_post_var( 'fix_or_cancel' ) );
		$older_or_younger = sanitize_text_field( get_post_var( 'older_or_younger' ) );
		$time_range       = absint( get_post_var( 'time_range' ) );
		$time_unit        = sanitize_text_field( get_post_var( 'time_unit' ) );
		$confirm          = get_post_var( 'confirm' );

		if ( $confirm !== 'confirm' ) {
			return new WP_Error( 'confirmation', 'Please type "confirm" in all lowercase to confirm the action.' );
		}

		$time = strtotime( "$time_range $time_unit ago" );

		if ( ! $time ) {
			return new WP_Error( 'invalid', 'Invalid time range supplied, no action was taken.' );
		}

		$compare = $older_or_younger == 'older' ? '<' : '>';

		switch ( $fix_or_cancel ) {
			case 'fix':

				$results = $wpdb->query( "
UPDATE {$events->get_table_name()} 
SET status = 'waiting', claim = '' 
WHERE status IN ( 'in_progress', 'waiting' ) AND `time` $compare {$time}
ORDER BY ID" );

				$this->add_notice( 'success', $results ?
					sprintf( '%s events have been rescheduled to run immediately!', number_format_i18n( $results ) ) :
					'The query ran successfully but there were no unprocessed events for the given time range.' );

				break;
			case 'cancel':

				$results = $wpdb->query( "
UPDATE {$events->get_table_name()} 
SET status = 'cancelled'
WHERE status IN ( 'in_progress', 'waiting' ) AND `time` $compare {$time}
ORDER BY ID" );

				if ( $results ) {
					event_queue_db()->move_events_to_history( [
						'status' => Event::CANCELLED
					] );
				}

				$this->add_notice( 'success', $results ?
					sprintf( '%s events have been cancelled!', number_format_i18n( $results ) ) :
					'The query ran successfully but there were no unprocessed events for the given time range.' );

				break;
		}

		return false;

	}

	/**
	 * Restore missing funnel events
	 *
	 * @return string
	 */
	public function process_restore_funnel_events() {
		if ( ! current_user_can( 'cancel_events' ) ) {
			$this->wp_die_no_access();
		}

		restore_missing_funnel_events();

		$this->add_notice( 'restored', 'Events restored!' );

		return admin_page_url( 'gh_events' );
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
	public function process_emails_resend() {

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

	public function process_cancel_task() {

		if ( ! current_user_can( 'manage_options' ) ) {
			$this->wp_die_no_access();
		}

		foreach ( $this->get_items() as $id ) {
			$task = new Background_Task( $id );
			$task->cancel();
		}

		$this->add_notice(
			esc_attr( 'cancelled' ),
			sprintf( _nx( 'Cancelled %d task', 'Cancelled %d task', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $this->get_items() ) ),
		);
	}

	public function process_process_task() {

		if ( ! current_user_can( 'manage_options' ) ) {
			$this->wp_die_no_access();
		}

		$task = null;

		$timer = new Micro_Time_Tracker();

		foreach ( $this->get_items() as $id ) {

			$task = new Background_Task( $id );

			try {
				$task->process();
			} catch ( Exception $exception ) {
				return new WP_Error( 'error', $exception->getMessage() );
			}
		}

		$time = $timer->time_elapsed_rounded();

		$this->add_notice( '', "Task processed in $time seconds." );

		if ( $task ) {
			return admin_page_url( 'gh_events', [
				'tab'    => 'tasks',
				'status' => $task->is_done() ? 'done' : 'in_progress',
				'view'   => $task->is_done() ? 'done' : 'in_progress',
			] );
		}
	}

	public function process_resume_task() {

		if ( ! current_user_can( 'manage_options' ) ) {
			$this->wp_die_no_access();
		}

		foreach ( $this->get_items() as $id ) {
			$task = new Background_Task( $id );
			$task->resume();
		}

		$this->add_notice(
			esc_attr( 'resume' ),
			sprintf( _nx( 'Resumed %d task', 'Resumed %d task', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $this->get_items() ) ),
		);
	}

	public function page() {

		if ( $this->get_current_tab() === 'emails' && ! Email_Logger::is_enabled() ) {
			$this->add_notice( 'inactive', sprintf( esc_html__( 'Email logging is currently disabled. You can enable email logging in the %1$semail settings%2$s.', 'groundhogg' ), "<a href=\"" . admin_page_url( 'gh_settings', [ 'tab' => 'email' ], 'email-logging' ) . "\">", "</a>" ), 'warning' );
		}

		parent::page();
	}
}
