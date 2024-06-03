<?php

namespace Groundhogg\Admin\Events;

// Exit if accessed directly
use Groundhogg\Admin\Table;
use Groundhogg\Email_Log_Item;
use WP_List_Table;
use function Groundhogg\action_url;
use function Groundhogg\admin_page_url;
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
				'query'   => [],
			],
			[
				'view'    => 'sent',
				'display' => __( 'Sent', 'groundhogg' ),
				'query'   => [ 'status' => 'sent' ],
			],
			[
				'view'    => 'failed',
				'display' => __( 'Failed', 'groundhogg' ),
				'query'   => [ 'status' => 'failed' ],
			],
			[
				'view'    => 'wordpress',
				'display' => __( 'WordPress', 'groundhogg' ),
				'query'   => [ 'message_type' => \Groundhogg_Email_Services::WORDPRESS ],
			],
			[
				'view'    => 'transactional',
				'display' => __( 'Transactional', 'groundhogg' ),
				'query'   => [ 'message_type' => \Groundhogg_Email_Services::TRANSACTIONAL ],
			],
			[
				'view'    => 'marketing',
				'display' => __( 'Marketing', 'groundhogg' ),
				'query'   => [ 'message_type' => \Groundhogg_Email_Services::MARKETING ],
			]
		];
	}

	/**
	 * @inheritDoc
	 */
	function get_default_query() {
		return [];
	}

	protected function extra_tablenav( $which ) {
		if ( $which !== 'top' ) {
			return;
		}

		?>
        <script>( function ($) {
            $(() => {

              const $before = $('#before')
              const $after = $('#after')
              const $search = $('#search-by-date')

              $('#date-filter').on('change', (e) => {
                switch (e.target.value) {
                  default:
                    $before.addClass('hidden').val('')
                    $after.addClass('hidden').val('')
                    $search.addClass('hidden')
                    break
                  case 'before':
                    $search.removeClass('hidden')
                    $before.removeClass('hidden')
                    $after.addClass('hidden').val('')
                    break
                  case 'after':
                    $search.removeClass('hidden')
                    $after.removeClass('hidden')
                    $before.addClass('hidden').val('')
                    break
                  case 'between':
                    $search.removeClass('hidden')
                    $before.removeClass('hidden')
                    $after.removeClass('hidden')
                    break
                }
              })
            })
          } )(jQuery)</script>
        <div class="alignleft gh-actions" style="display: flex;gap: 3px;align-items: center">
			<?php

			echo html()->dropdown( [
				'id'          => 'date-filter',
				'name'        => 'date_filter',
				'option_none' => __( 'Filter by date' ),
				'options'     => [
					'before'  => __( 'Before', 'groundhogg' ),
					'after'   => __( 'After', 'groundhogg' ),
					'between' => __( 'Between', 'groundhogg' ),
				],
				'selected'    => get_url_var( 'date_filter' )
			] );

			echo html()->input( [
				'type'  => 'date',
				'id'    => 'after',
				'name'  => 'after',
				'value' => get_url_var( 'after' ),
				'class' => 'input' . ( get_url_var( 'after' ) ? '' : ' hidden' )
			] );

			echo html()->input( [
				'type'  => 'date',
				'id'    => 'before',
				'name'  => 'before',
				'value' => get_url_var( 'before' ),
				'class' => 'input' . ( get_url_var( 'before' ) ? '' : ' hidden' )
			] );

			echo html()->button( [
				'text'  => __( 'Search' ),
				'id'    => 'search-by-date',
				'type'  => 'submit',
				'value' => 'filter_logs',
				'name'  => 'action',
				'class' => 'button button-secondary' . ( get_url_var( 'before' ) || get_url_var( 'after' ) ? '' : ' hidden' )
			] )

			?></div><?php
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
		$actions['resend'] = "<a href='" . action_url( 'resend', [
				'email' => $email->get_id()
			] ) . "'>" . __( 'Resend', 'groundhogg' ) . "</a>";

		$actions['view-details'] = html()->e( 'a', [
			'href'        => '#',
			'class'       => 'view-email-log',
			'data-log-id' => $email->get_id()
		], __( 'View Details' ) );

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
                <span class="pill success"><?php _e( 'Sent', 'groundhogg' ) ?></span>
				<?php

				break;
			case 'failed':
			case 'bounced':
			case 'softfail':

				?>
                <span class="pill danger gh-has-tooltip">
                    <?php _e( 'Failed', 'groundhogg' ) ?>
                    <span class="gh-tooltip bottom"><?php esc_html_e( $email->error_message ); ?></span>
                </span>
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
		$cur_time  = time();
		$time_diff = $lu_time - $cur_time;

		$date = new \DateTime();
		$date->setTimestamp( $lu_time );
		$date->setTimezone( wp_timezone() );

		if ( absint( $time_diff ) > 24 * HOUR_IN_SECONDS ) {
			$time = $date->format( get_date_time_format() );
		} else {
			$time = sprintf( "%s ago", human_time_diff( $lu_time, $cur_time ) );
		}

		return '<abbr title="' . $date->format( DATE_RFC3339 ) . '">' . $time . '</abbr>';
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
	 * Get an associative array ( option_name => option_title ) with the list
	 * of bulk steps available on this table.
	 *
	 * @return array An associative array containing all the bulk steps.
	 */
	protected function get_bulk_actions() {

		$actions = [
			'resend' => __( 'Resend', 'groundhogg' ),
			'delete' => __( 'Delete', 'groundhogg' ),
		];

		return apply_filters( 'groundhogg/log/bulk_actions', $actions );
	}
}
