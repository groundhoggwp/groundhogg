<?php

namespace Groundhogg\Admin\Emails;

use Groundhogg\Admin\Table;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Email;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use WP_List_Table;
use function Groundhogg\action_url;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_db;
use function Groundhogg\get_default_from_email;
use function Groundhogg\get_default_from_name;
use function Groundhogg\html;
use function Groundhogg\row_item_locked_text;
use function Groundhogg\scheduled_time_column;

/**
 * Emails Table Class
 *
 * This class shows the data table for accessing information about an email.
 *
 * @since       File available since Release 0.1
 * @subpackage  Admin/Emails
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Admin
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// WP_List_Table is not loaded automatically so we need to load it in our application
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Emails_Table extends Table {

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
			'cb'           => '<input type="checkbox" />', // Render a checkbox instead of text.
			'title'        => _x( 'Title', 'Column label', 'groundhogg' ),
			'subject'      => _x( 'Subject', 'Column label', 'groundhogg' ),
			'from_user'    => _x( 'From User', 'Column label', 'groundhogg' ),
			'campaigns'    => _x( 'Campaigns', 'Column label', 'groundhogg' ),
			'funnels'      => _x( 'Flows', 'Column label', 'groundhogg' ),
			'author'       => _x( 'Author', 'Column label', 'groundhogg' ),
			'last_updated' => _x( 'Last Updated', 'Column label', 'groundhogg' ),
		];

		return apply_filters( 'wpgh_email_columns', $columns );
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
			'title'        => [ 'title', false ],
			'subject'      => [ 'subject', false ],
			'from_user'    => [ 'from_user', false ],
			'author'       => [ 'author', false ],
			'last_updated' => [ 'last_updated', false ],
		];

		return apply_filters( 'wpgh_email_sortable_columns', $sortable_columns );
	}

	public function extra_tablenav( $which ) {
		if ( $this->get_view() !== 'trash' ) {
			return;
		}
		?>
        <div class="alignleft gh-actions">
            <a class="button danger"
               href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=gh_emails&view=trash&action=empty_trash' ), 'empty_trash' ); ?>"><?php _e( 'Empty Trash' ); ?></a>
        </div>
		<?php
	}

	protected function parse_item( $item ) {
		return new Email( $item );
	}

	/**
	 * @param $email Email
	 *
	 * @return string
	 */
	protected function column_title( $email ) {

		$subject = $email->get_title();
		$editUrl = $email->admin_link();

		if ( $this->get_view() === 'trash' ) {
			return "<strong class='row-title'>{$subject}</strong>";
		}

		row_item_locked_text( $email );

		$html = "<strong>";

		$html .= "<a class='row-title' href='$editUrl'>{$subject}</a>";

		if ( $email->is_draft() && ! $this->view_is( 'draft' ) ) {
			$html .= " &#x2014; " . "<span class='post-state'>" . __( 'Draft' ) . "</span>";
		}

		if ( $email->is_template() && ! $this->view_is( 'template' ) ) {
			$html .= " &#x2014; " . "<span class='post-state'>" . __( 'Template', 'groundhogg' ) . "</span>";
		}

		$html .= "</strong>";

		return $html;
	}

	/**
	 * @param $email Email
	 *
	 * @return mixed
	 */
	protected function column_subject( $email ) {
		return $email->get_subject_line();
	}

	/**
	 * @param $email Email
	 *
	 * @return string
	 */
	protected function column_from_user( $email ) {

		if ( $email->get_from_user_id() == 0 && ! $email->get_meta( 'use_default_from' ) ) {
			return __( 'The contact\'s owner', 'groundhogg' );
		}

		if ( $email->get_from_user() ) {
			return html()->e( 'a', [
				'href' => admin_page_url( 'gh_emails', [
					'from_user' => $email->get_from_user_id()
				] )
			], esc_html( sprintf( '%s <%s>', $email->get_from_name(), $email->get_from_email() ) ) );
		}

		return esc_html( sprintf( '%s <%s>', get_default_from_name(), get_default_from_email() ) );
	}

	/**
	 * @param $email Email
	 *
	 * @return string
	 */
	protected function column_author( $email ) {
		$user = get_userdata( intval( ( $email->get_author_id() ) ) );
		if ( ! $user ) {
			return __( 'Unknown', 'groundhogg' );
		}
		$from_user = esc_html( $user->display_name );
		$queryUrl  = admin_url( 'admin.php?page=gh_emails&view=author&author=' . $email->get_author_id() );

		return "<a href='$queryUrl'>$from_user</a>";
	}

	/**
	 * @param $email Email
	 *
	 * @return string
	 */
	protected function column_date_created( $email ) {
		$ds_time = Plugin::$instance->utils->date_time->convert_to_utc_0( strtotime( $email->get_date_created() ) );

		return scheduled_time_column( $ds_time, false, false, false );
	}

	/**
	 * @param $email Email
	 *
	 * @return string
	 */
	protected function column_last_updated( $email ) {
		$ds_time = Plugin::$instance->utils->date_time->convert_to_utc_0( strtotime( $email->get_last_updated() ) );

		return scheduled_time_column( $ds_time, false, false, false );
	}

	/**
	 * Show the list of funnels that the email is being used in
	 *
	 * @param Email $email
	 *
	 * @return string
	 */
	protected function column_funnels( $email ) {

		$stepQuery  = new Table_Query( 'steps' );
		$meta_alias = $stepQuery->joinMeta( 'email_id' );
		$stepQuery->where( 'step_type', 'send_email' );
		$stepQuery->setSelect( 'step_type', [ "$meta_alias.meta_value", 'email_id' ], 'funnel_id' );

		$funnelQuery = new Table_Query( 'funnels' );
		$join        = $funnelQuery->addJoin( 'LEFT', $stepQuery );
		$join->onColumn( 'funnel_id' );

		$funnelQuery->where( 'email_id', $email->ID );
		$funnelQuery->setGroupby( 'ID' );

		$funnels = $funnelQuery->get_objects( Funnel::class );

		return implode( ', ', array_map( function ( $funnel ) {
			return html()->e( 'a', [
				'href' => $funnel->admin_link()
			], $funnel->get_title() );
		}, $funnels ) );
	}

	/**
	 * @param Email $email
	 */
	protected function column_campaigns( $email ) {
		$campaigns = $email->get_related_objects( 'campaign' );

		return implode( ', ', array_map( function ( $campaign ) {
			return html()->e( 'a', [
				'href' => add_query_arg( [
					'related' => [ 'ID' => $campaign->ID, 'type' => 'campaign' ]
				], $_SERVER['REQUEST_URI'] ),
			], $campaign->get_name() );
		}, $campaigns ) );
	}

	/**
	 * For more detailed insight into how columns are handled, take a look at
	 * WP_List_Table::single_row_columns()
	 *
	 * @param object $email       A singular item (one full row's worth of data).
	 * @param string $column_name The name/slug of the column to be processed.
	 *
	 * @return string Text or HTML to be placed inside the column <td>.
	 */
	protected function column_default( $email, $column_name ) {

		do_action( 'wpgh_email_custom_column', $email, $column_name );

		return '';
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
				$actions['trash'] = _x( 'Trash', 'List table bulk action', 'groundhogg' );
				break;
			case 'trash':
				$actions['delete']  = _x( 'Delete Permanently', 'List table bulk action', 'groundhogg' );
				$actions['restore'] = _x( 'Restore', 'List table bulk action', 'groundhogg' );
				break;
		}

		return apply_filters( 'wpgh_email_bulk_actions', $actions );
	}

	function get_table_id() {
		return 'emails_table';
	}

	function get_db() {
		return get_db( 'emails' );
	}

	/**
	 * @param $item Email
	 * @param $column_name
	 * @param $primary
	 *
	 * @return array
	 */
	protected function get_row_actions( $item, $column_name, $primary ) {

		$actions = [];

		switch ( $this->get_view() ) {
			default:
				$actions[] = [ 'class' => 'gh-email-preview', 'display' => __( 'Preview' ), 'url' => '#' ];
				$actions[] = [ 'class' => 'edit', 'display' => __( 'Edit' ), 'url' => $item->admin_link() ];
				$actions[] = [
					'class'   => 'duplicate',
					'display' => __( 'Duplicate' ),
					'url'     => action_url( 'duplicate', [ 'email' => $item->get_id() ] )
				];
				$actions[] = [
					'class'   => 'trash',
					'display' => __( 'Trash' ),
					'url'     => action_url( 'trash', [ 'email' => $item->get_id() ] )
				];
				break;
			case 'trash':
				$actions[] = [
					'class'   => 'restore',
					'display' => __( 'Restore' ),
					'url'     => action_url( 'restore', [ 'email' => $item->get_id() ] )
				];
				$actions[] = [
					'class'   => 'trash',
					'display' => __( 'Delete' ),
					'url'     => action_url( 'delete', [ 'email' => $item->get_id() ] )
				];
				break;
		}

		return $actions;
	}

	protected function get_views_setup() {
		return [
			[
				'view'    => '',
				'display' => __( 'All' ),
				'query'   => [ 'status' => [ 'ready', 'draft' ] ],
			],
			[
				'view'    => 'ready',
				'display' => __( 'Ready', 'groundhogg' ),
				'query'   => [ 'status' => 'ready' ],
			],
			[
				'view'    => 'template',
				'display' => __( 'Templates', 'groundhogg' ),
				'query'   => [ 'is_template' => 1, 'status' => [ 'ready', 'draft' ] ],
			],
			[
				'view'    => 'draft',
				'display' => __( 'Drafts' ),
				'query'   => [ 'status' => 'draft' ],
			],
			[
				'view'    => 'marketing',
				'display' => __( 'Marketing', 'groundhogg' ),
				'query'   => [ 'message_type' => 'marketing', 'status' => [ 'ready', 'draft' ] ],
			],
			[
				'view'    => 'transactional',
				'display' => __( 'Transactional', 'groundhogg' ),
				'query'   => [ 'message_type' => 'transactional', 'status' => [ 'ready', 'draft' ] ],
			],
			[
				'view'    => 'blocks',
				'display' => __( 'Block Editor', 'groundhogg' ),
				'query'   => [
					'meta_query' => [
						[
							'key'     => 'blocks',
							'value'   => 1,
							'compare' => '='
						]
					],
					'status'     => [ 'ready', 'draft' ]
				],
			],
			[
				'view'    => 'html',
				'display' => __( 'HTML', 'groundhogg' ),
				'query'   => [
					'meta_query' => [
						[
							'key'     => 'type',
							'value'   => 'html',
							'compare' => '='
						]
					],
					'status'     => [ 'ready', 'draft' ]
				],
			],
//			[
//				'view'    => 'unused',
//				'display' => __( 'Unused', 'groundhogg' ),
//				'query'   => [
//					'unused' => true
//				],
//			],
			[
				'view'    => 'trash',
				'display' => __( 'Trash', 'groundhogg' ),
				'query'   => [ 'status' => 'trash' ],
			]
		];
	}

	function get_default_query() {
		return [
			'select' => [
				'ID',
				'subject',
				'title',
				'pre_header',
				'message_type',
				'author',
				'from_user',
				'status',
				'is_template',
				'last_updated',
				'date_created',
			]
		];
	}
}
