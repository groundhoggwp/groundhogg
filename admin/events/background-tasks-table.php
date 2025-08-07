<?php

namespace Groundhogg\Admin\Events;

// Exit if accessed directly
use Exception;
use Groundhogg\Admin\Table;
use Groundhogg\Classes\Background_Task;
use Groundhogg\Utils\DateTimeHelper;
use WP_List_Table;
use function Groundhogg\action_url;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_db;
use function Groundhogg\html;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Background_Tasks_Table extends Table {

	/**
	 * TT_Example_List_Table constructor.
	 *
	 * REQUIRED. Set up a constructor that references the parent constructor. We
	 * use the parent reference to set some default configs.
	 */
	public function __construct() {
		// Set parent defaults.
		parent::__construct( array(
			'singular' => 'task',     // Singular name of the listed records.
			'plural'   => 'tasks',    // Plural name of the listed records.
			'ajax'     => false,      // Does this table support ajax?
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
		return 'bg_tasks_table';
	}

	/**
	 * @inheritDoc
	 */
	function get_db() {
		return get_db( 'background_tasks' );
	}

	/**
	 * @inheritDoc
	 */
	protected function get_views_setup() {
		return [
			[
				'view'    => 'pending',
				'display' => __( 'Pending', 'groundhogg' ),
				'query'   => [ 'status' => 'pending' ],
			],
			[
				'view'    => 'in_progress',
				'display' => __( 'In Progress', 'groundhogg' ),
				'query'   => [ 'status' => 'in_progress' ],
			],
			[
				'view'    => 'done',
				'display' => __( 'Done', 'groundhogg' ),
				'query'   => [ 'status' => 'done' ],
			],
			[
				'view'    => 'cancelled',
				'display' => __( 'Cancelled', 'groundhogg' ),
				'query'   => [ 'status' => 'cancelled' ],
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	function get_default_query() {
		return [];
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
		$columns = [
			'cb'   => '<input type="checkbox" />', // Render a checkbox instead of text.
			'task' => _x( 'Task', 'Column label', 'groundhogg' ),
			'user' => _x( 'User', 'Column label', 'groundhogg' ),
			'date' => _x( 'Date', 'Column label', 'groundhogg' ),
		];

		if ( $this->get_view() === 'pending' ) {
			$columns['starts'] = _x( 'Starts in', 'Column label', 'groundhogg' );
		}

		if ( $this->get_view() === 'done' ) {
			unset( $columns['cb'] );
		}


		if ( $this->get_view() === 'in_progress' ) {
			$columns['progress'] = _x( 'Progress', 'Column label', 'groundhogg' );
		}

		return apply_filters( 'groundhogg/bg_task_log/columns', $columns );
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

		$sortable_columns = [
			'date'   => [ 'date_created', false ],
			'user'   => [ 'user_id', false ],
			'starts' => [ 'time', false ],
		];

		return apply_filters( 'groundhogg/bg_task_log/sortable_columns', $sortable_columns );
	}

	protected function parse_item( $item ) {
		return new Background_Task( $item );
	}

	/**
	 * @param $task Background_Task
	 *
	 * @return string
	 */
	protected function column_user( $task ) {

		$url = admin_page_url( 'gh_events', [
			'tab'     => 'tasks',
			'user_id' => $task->user_id,
			'view'    => $this->get_view(),
		] );

		$user = get_userdata( $task->user_id );

		return html()->e( 'a', [
			'href' => $url,
		], $user ? $user->display_name : __( 'System', 'groundhogg' ) );
	}


	/**
	 * @throws Exception
	 *
	 * @param $task Background_Task
	 *
	 * @return string
	 */
	protected function column_date( $task ) {
		$date = new DateTimeHelper( $task->date_created );

		return "<abbr title='{$date->ymdhis()}'>{$date->wpDateTimeFormat()}</abbr>";
	}

	/**
	 * @throws Exception
	 *
	 * @param $task Background_Task
	 *
	 * @return string
	 */
	protected function column_starts( $task ) {
		$date = new DateTimeHelper( $task->time );

		if ( $date->isPast() ) {
			$diff = __( 'Now!', 'groundhogg' );
		} else {
			$diff = human_time_diff( time(), $task->time );
		}

		return html()->e( 'span', [
			'class'         => 'task-progress',
			'data-progress' => $task->get_progress(),
			'data-id'       => $task->ID
		], "<abbr title='{$date->ymdhis()}'>{$diff}</abbr>" );
	}

	/**
	 * @param $task Background_Task
	 *
	 * @return void
	 */
	protected function column_task( $task ) {

		if ( method_exists( $task->theTask, 'get_title' ) ) {
			echo $task->theTask->get_title();

			return;
		}

		?>
        <pre style="margin:0"><?php echo wp_json_encode( $task->theTask, JSON_PRETTY_PRINT ) ?></pre><?php
	}

	/**
	 * @param $task Background_Task
	 *
	 * @return string
	 */
	protected function column_progress( $task ) {

		$progress = floor( $task->get_progress() );

		return html()->e( 'span', [
			'class'         => 'task-progress',
			'data-progress' => $progress,
			'data-id'       => $task->ID
		], $progress . '%' );
	}

	/**
	 * For more detailed insight into how columns are handled, take a look at
	 * WP_List_Table::single_row_columns()
	 *
	 * @param object $email       A singular item (one full row's worth of data).
	 * @param string $column_name The name/slug of the column to be processed.
	 *
	 * @return string|void Text or HTML to be placed inside the column <td>.
	 */
	protected function column_default( $email, $column_name ) {
		do_action( 'groundhogg/log/custom_column', $email, $column_name );
	}

	/**
	 * Get value for checkbox column.
	 *
	 * @param object $email A singular item (one full row's worth of data).
	 *
	 * @return string Text to be placed inside the column <td>.
	 */
	protected function column_cb( $email ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			$this->_args['singular'],  // Let's simply repurpose the table's singular label ("movie").
			$email->ID                // The value of the checkbox should be the record's ID.
		);
	}

	/**
	 * @param $item Background_Task
	 * @param $column_name
	 * @param $primary
	 *
	 * @return array
	 */
	protected function get_row_actions( $item, $column_name, $primary ) {

		$actions = [];

		switch ( $item->status ) {
			case 'done':
				break;
			case 'cancelled':
				$actions[] = [ 'class' => 'edit', 'display' => __( 'Resume' ), 'url' => action_url( 'resume_task', [ 'task' => $item->ID ] ) ];
				break;
			default:
				$actions[] = [ 'class' => 'trash', 'display' => __( 'Cancel' ), 'url' => action_url( 'cancel_task', [ 'task' => $item->ID ] ) ];
				$actions[] = [
					'class'     => 'edit',
					'display'   => __( 'Run now' ),
					'linkProps' => [
						'class' => 'do-task',
					],
					'url'       => action_url( 'process_task', [ 'task' => $item->ID ] )
				];
				break;
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

		switch ( $this->get_view() ) {
			default:
				$actions['cancel_task'] = __( 'Cancel', 'groundhogg' );
				break;
			case 'cancelled':
				$actions['resume_task'] = __( 'Resume', 'groundhogg' );
				break;
			case 'done':
				break;
		}

		return apply_filters( 'groundhogg/log/bulk_actions', $actions );
	}

	public function prepare_items() {
		parent::prepare_items();

		// todo there is probably a better way to handle this
		$this->items = array_filter( $this->items, function ( Background_Task $item ) {
			return ! $item->is_incomplete_class();
		} );
	}
}
