<?php

namespace Groundhogg\Admin\Events;

// Exit if accessed directly
use Groundhogg\Admin\Guided_Setup\Steps\Email;
use Groundhogg\Admin\Table;
use Groundhogg\DB\DB;
use Groundhogg\Email_Log_Item;
use WP_List_Table;
use function Groundhogg\action_url;
use function Groundhogg\admin_page_url;
use function Groundhogg\dashicon;
use function Groundhogg\enqueue_groundhogg_modal;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_date_time_format;
use function Groundhogg\get_db;
use function Groundhogg\get_url_var;
use function Groundhogg\html;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Email_Log_Table extends Table {

	/**
	 * TT_Example_List_Table constructor.
	 *
	 * REQUIRED. Set up a constructor that references the parent constructor. We
	 * use the parent reference to set some default configs.
	 */
	public function __construct() {
		// Set parent defaults.
		parent::__construct( array(
			'singular' => 'email',     // Singular name of the listed records.
			'plural'   => 'emails',    // Plural name of the listed records.
			'ajax'     => false,       // Does this table support ajax?
		) );
	}

	/**
	 * @inheritDoc
	 */
	function get_table_id() {
		return 'email_log_table';
	}

	/**
	 * @inheritDoc
	 */
	function get_db() {
		return get_db( 'email_log' );
	}

	protected function view_param() {
		return 'status';
	}

	/**
	 * @inheritDoc
	 */
	protected function get_row_actions( $item, $column_name, $primary ) {
		return [];
	}

	/**
	 * @inheritDoc
	 */
	protected function get_views_setup() {
		return [
			[
				'view'    => '',
				'display' => __( 'All' ),
				'count'   => [],
			],
			[
				'view'    => 'sent',
				'display' => __( 'Sent', 'groundhogg' ),
				'count'   => [ 'status' => 'sent' ],
			],
			[
				'view'    => 'failed',
				'display' => __( 'Failed', 'groundhogg' ),
				'count'   => [ 'status' => 'failed' ],
			],
			[
				'view'    => 'wordpress',
				'display' => __( 'WordPress', 'groundhogg' ),
				'count'   => [ 'message_type' => \Groundhogg_Email_Services::WORDPRESS ],
				'param'   => [ 'message_type' => \Groundhogg_Email_Services::WORDPRESS ],
			],
			[
				'view'    => 'transactional',
				'display' => __( 'Transactional', 'groundhogg' ),
				'count'   => [ 'message_type' => \Groundhogg_Email_Services::TRANSACTIONAL ],
				'param'   => [ 'message_type' => \Groundhogg_Email_Services::TRANSACTIONAL ],
			],
			[
				'view'    => 'marketing',
				'display' => __( 'Marketing', 'groundhogg' ),
				'count'   => [ 'message_type' => \Groundhogg_Email_Services::MARKETING ],
				'param'   => [ 'message_type' => \Groundhogg_Email_Services::MARKETING ],
			]
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
	 * @return array An associative array containing column information.
	 * @see WP_List_Table::::single_row_columns()
	 */
	public function get_columns() {
		$columns = array(
			'cb'      => '<input type="checkbox" />', // Render a checkbox instead of text.
			'subject' => _x( 'Subject', 'Column label', 'groundhogg' ),
			'to'      => _x( 'Recipients', 'Column label', 'groundhogg' ),
//			'from'    => _x( 'From', 'Column label', 'groundhogg' ),
//			'content' => _x( 'Content', 'Column label', 'groundhogg' ),
			'status'  => _x( 'Status', 'Column label', 'groundhogg' ),
			'sent'    => _x( 'Sent', 'Column label', 'groundhogg' ),
			//'date_created' => _x( 'Date Created', 'Column label', 'groundhogg' ),
		);

		if ( $this->get_view() === 'failed' ) {
			$columns['error_code']    = _x( 'Error', 'Column label', 'groundhogg' );
			$columns['error_message'] = _x( 'Message', 'Column label', 'groundhogg' );
		}

		return apply_filters( 'groundhogg/log/columns', $columns );
	}

	protected function parse_item( $item ) {
		return new Email_Log_Item( $item->ID );
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
			'status' => array( 'status', false ),
			'sent'   => array( 'date_sent', false ),
		);

		return apply_filters( 'groundhogg/log/sortable_columns', $sortable_columns );
	}

	/**
	 * @param        $email Email_Log_Item
	 * @param string $column_name
	 * @param string $primary
	 *
	 * @return string
	 */
	protected function handle_row_actions( $email, $column_name, $primary ) {

		if ( $primary !== $column_name ) {
			return '';
		}
//
		$actions['resend']       = "<a href='" . action_url( 'resend', [
				'email' => $email->get_id()
			] ) . "'>" . __( 'Resend', 'groundhogg' ) . "</a>";

		$actions['view-details'] = html()->modal_link( [
			'title'       => __( 'Log Details', 'groundhogg' ),
			'text'        => __( 'View Details', 'groundhogg' ),
			'class'       => 'view-email-log',
			'footer'      => 'false',
			'height'      => 500,
			'width'       => 500,
			'source'      => 'modal-log-details',
			'data-log-id' => $email->get_id()
		] );

		return $this->row_actions( apply_filters( 'groundhogg/log/row_actions', $actions, $email, $column_name ) );
	}

	/**
	 * @param $email Email_Log_Item
	 *
	 * @return string|void
	 */
	protected function column_to( $email ) {

		$links = [];

		foreach ( $email->recipients as $recipient ) {

			if ( ! is_email( $recipient ) ) {
				continue;
			}

			$contact = get_contactdata( $recipient );

			if ( $contact ) {
				$links[] = sprintf( '<a href="%2$s">%1$s</a>', $recipient, admin_page_url( 'gh_contacts', [
					'action'  => 'edit',
					'contact' => $contact->get_id()
				] ) );
			} else {
				$links[] = sprintf( '<a href="mailto:%1$s">%1$s</a>', $recipient );
			}
		}

		return implode( ', ', $links );
	}

	/**
	 * @param $email Email_Log_Item
	 *
	 * @return string|void
	 */
	protected function column_subject( $email ) {
		esc_html_e( $email->subject );
	}

	/**
	 * @param $email Email_Log_Item
	 *
	 * @return string|void
	 */
	protected function column_from( $email ) {
		esc_html_e( $email->from_address );
	}

	/**
	 * @param $email Email_Log_Item
	 *
	 * @return string|void
	 */
	protected function column_status( $email ) {

		switch ( $email->status ):

			case 'sent':
			case 'delivered':

				?>
                <span class="email-sent"><?php _e( 'Sent', 'groundhogg' ) ?></span>
				<?php

				break;
			case 'failed':
			case 'bounced':
			case 'softfail':

				?>
                <span class="email-failed"><?php _e( 'Failed', 'groundhogg' ) ?></span>
				<?php

				break;

		endswitch;

	}

	/**
	 * @param $email Email_Log_Item
	 *
	 * @return string|void
	 */
	protected function column_sent( $email ) {

		$lu_time   = mysql2date( 'U', $email->date_sent );
		$cur_time  = (int) current_time( 'timestamp' );
		$time_diff = $lu_time - $cur_time;

		if ( absint( $time_diff ) > 24 * HOUR_IN_SECONDS ) {
			$time = date_i18n( get_date_time_format(), intval( $lu_time ) );
		} else {
			$time = sprintf( "%s ago", human_time_diff( $lu_time, $cur_time ) );
		}

		return '<abbr title="' . date_i18n( DATE_ISO8601, intval( $lu_time ) ) . '">' . $time . '</abbr>';
	}

	/**
	 * For more detailed insight into how columns are handled, take a look at
	 * WP_List_Table::single_row_columns()
	 *
	 * @param object $email A singular item (one full row's worth of data).
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
	 * Get an associative array ( option_name => option_title ) with the list
	 * of bulk steps available on this table.
	 *
	 * @return array An associative array containing all the bulk steps.
	 */
	protected function get_bulk_actions() {

		$actions = [
//			'retry'     => __( 'Retry', 'groundhogg' ),
//			'blacklist' => __( 'Blacklist', 'groundhogg' ),
//			'whitelist' => __( 'Whitelist', 'groundhogg' ),
			'resend' => __( 'Resend', 'groundhogg' ),
			'delete' => __( 'Delete', 'groundhogg' ),
		];

		return apply_filters( 'groundhogg/log/bulk_actions', $actions );
	}
}
