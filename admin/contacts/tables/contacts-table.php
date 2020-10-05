<?php

namespace Groundhogg\Admin\Contacts\Tables;

use function Groundhogg\_nf;
use function Groundhogg\admin_page_url;
use function Groundhogg\current_user_is;
use function Groundhogg\get_date_time_format;
use function Groundhogg\get_db;
use function Groundhogg\get_post_var;
use function Groundhogg\get_request_query;
use function Groundhogg\get_request_var;
use function Groundhogg\get_screen_option;
use function Groundhogg\get_url_var;
use function Groundhogg\html;
use function Groundhogg\isset_not_empty;
use Groundhogg\Preferences;
use \WP_List_Table;
use Groundhogg\Plugin;
use Groundhogg\Contact;
use Groundhogg\Contact_Query;
use function Groundhogg\scheduled_time_column;


/**
 * Contacts Table Class
 *
 * This class shows the data table for accessing information about a customer.
 *
 * @since       File available since Release 0.1
 * @see         WP_List_Table, contact-editor.php
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Admin
 * @subpackage  Admin/Contacts
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// WP_List_Table is not loaded automatically so we need to load it in our application
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Contacts_Table extends WP_List_Table {

	private $query;

	/**
	 * TT_Example_List_Table constructor.
	 *
	 * REQUIRED. Set up a constructor that references the parent constructor. We
	 * use the parent reference to set some default configs.
	 */
	public function __construct() {
		// Set parent defaults.
		parent::__construct( array(
			'singular' => 'contact',     // Singular name of the listed records.
			'plural'   => 'contacts',    // Plural name of the listed records.
			'ajax'     => true,       // Does this table support ajax?
			'screen'   => wp_doing_ajax() ? 'admin_ajax' : null
		) );
	}

	/**
	 * Prepares the list of items for displaying.
	 * @global $wpdb \wpdb
	 * @uses $this->_column_headers
	 * @uses $this->items
	 * @uses $this->get_columns()
	 * @uses $this->get_sortable_columns()
	 * @uses $this->get_pagenum()
	 * @uses $this->set_pagination_args()
	 */
	function prepare_items() {


		$columns  = $this->get_columns();
		$hidden   = array(); // No hidden columns
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$per_page = absint( get_url_var( 'limit', get_screen_option( 'per_page' ) ) );
		$paged    = $this->get_pagenum();
		$offset   = $per_page * ( $paged - 1 );
		$search   = get_url_var( 's' );
		$order    = get_url_var( 'order', 'DESC' );
		$orderby  = get_url_var( 'orderby', 'ID' );

		$query = get_request_query();

		// Since unconfirmed is 0 (aside maybe we should change that) we need to specify we actually want it still.
		$optin_status = get_request_var( 'optin_status' );

		if ( is_array( $optin_status ) ) {
			$query['optin_status'] = map_deep( $optin_status, 'absint' );
		}

		$date_query = [
			'relation' => 'AND'
		];

		$date_inner_query = [ 'inclusive' => true ];

		$include_date_query = false;

		if ( $date_before = get_request_var( 'date_before' ) ) {
			$date_before                = sanitize_text_field( $date_before );
			$date_inner_query['before'] = $date_before;

			$include_date_query = true;
		}

		if ( $date_after = get_request_var( 'date_after' ) ) {
			$date_after                = sanitize_text_field( $date_after );
			$date_inner_query['after'] = $date_after;

			$include_date_query = true;
		}

		$date_query[] = $date_inner_query;

		if ( $include_date_query ) {
			$query['date_query'] = $date_query;
		}

		$query['number']  = $per_page;
		$query['offset']  = $offset;
		$query['orderby'] = $orderby;
		$query['search']  = $search;
		$query['order']   = $order;

		$query = apply_filters( 'groundhogg/admin/contacts/search_query', $query );

		$this->query = $query;

		$c_query = new Contact_Query();
		$data    = $c_query->query( $query );

		set_transient( 'groundhogg_contact_query_args', $c_query->query_vars, HOUR_IN_SECONDS );

		// Unset number for the count full count
		unset( $query['number'] );

		$total = get_db( 'contacts' )->count( $query );

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

	/**
	 * @return array An associative array containing column information.
	 * @see WP_List_Table::::single_row_columns()
	 */
	public function get_columns() {
		$columns = array(
			'cb'           => '<input type="checkbox" />', // Render a checkbox instead of text.
			'email'        => _x( 'Email', 'Column label', 'groundhogg' ),
			'first_name'   => _x( 'First Name', 'Column label', 'groundhogg' ),
			'last_name'    => _x( 'Last Name', 'Column label', 'groundhogg' ),
			'user_id'      => _x( 'Username', 'Column label', 'groundhogg' ),
			'owner_id'     => _x( 'Owner', 'Column label', 'groundhogg' ),
			'date_created' => _x( 'Date Created', 'Column label', 'groundhogg' ),
		);

		return apply_filters( 'groundhogg_contact_columns', $columns );
	}

	/**
	 * Get a list of sortable columns. The format is:
	 * 'internal-name' => 'orderby'
	 * or
	 * 'internal-name' => array( 'orderby', true )
	 *
	 * The second format will make the initial sorting order be descending
	 * @return array An associative array containing all the columns that should be sortable.
	 */
	protected function get_sortable_columns() {
		$sortable_columns = array(
			'email'        => array( 'email', false ),
			'first_name'   => array( 'first_name', false ),
			'last_name'    => array( 'last_name', false ),
			'user_id'      => array( 'user_id', false ),
			'owner_id'     => array( 'owner_id', false ),
			'date_created' => array( 'date_created', false )
		);

		return apply_filters( 'groundhogg_contact_sortable_columns', $sortable_columns );
	}

	/**
	 * @param object|Contact $contact
	 * @param int $level
	 */
	public function single_row( $contact, $level = 0 ) {

		if ( ! $contact instanceof Contact ) {
			$contact = Plugin::$instance->utils->get_contact( absint( $contact->ID ) );
		}

		if ( ! $contact ) {
			return;
		}

		?>
        <tr id="contact-<?php echo $contact->get_id(); ?>">
			<?php $this->single_row_columns( $contact ); ?>
        </tr>
		<?php
	}

	/**
	 * Outputs the hidden row displayed when inline editing
	 *
	 * @global string $mode List table view mode.
	 */
	public function inline_edit() {
		?>
        <table style="display: none">
            <tbody id="inlineedit">
            <tr id="inline-edit"
                class="inline-edit-row inline-edit-row-contact quick-edit-row quick-edit-row-contact inline-edit-contact inline-editor"
                style="display: none">
                <td colspan="<?php echo $this->get_column_count(); ?>" class="colspanchange">
                    <fieldset class="inline-edit-col-left">
                        <legend class="inline-edit-legend"><?php echo __( 'Quick Edit' ); ?></legend>
                        <div class="inline-edit-col">
                            <label>
                                <span class="title"><?php _e( 'Email' ); ?></span>
                                <span class="input-text-wrap"><input type="text" name="email"
                                                                     class="cemail regular-text" value=""/></span>
                            </label>
                            <label>
                                <span class="title"><?php _e( 'First Name', 'groundhogg' ); ?></span>
                                <span class="input-text-wrap"><input type="text" name="first_name"
                                                                     class="cfirst_name regular-text" value=""/></span>
                            </label>
                            <label>
                                <span class="title"><?php _e( 'Last Name', 'groundhogg' ); ?></span>
                                <span class="input-text-wrap"><input type="text" name="last_name"
                                                                     class="clast_name regular-text" value=""/></span>
                            </label>
                            <label>
                                <span class="title"><?php _e( 'Owner', 'groundhogg' ); ?></span>
                                <span class="input-text-wrap">
                                    <?php $args = array(
	                                    'show_option_none' => __( 'Select an owner' ),
	                                    'id'               => 'owner',
	                                    'name'             => 'owner',
	                                    'role'             => 'administrator',
	                                    'class'            => 'cowner'
                                    ); ?>
                                    <?php wp_dropdown_users( $args ) ?>
                                </span>
                            </label>
                            <label>
                                <input type="checkbox"
                                       name="unsubscribe"><?php _ex( 'Unsubscribe this contact.', 'action', 'groundhogg' ); ?>
                            </label>
                        </div>
                    </fieldset>
                    <fieldset class="inline-edit-col-right">
                        <div class="inline-edit-col">
                            <label class="inline-edit-tags">
                                <span class="title"><?php _e( 'Tags' ); ?></span>
                            </label>
							<?php echo Plugin::$instance->utils->html->dropdown( array(
								'id'   => 'tags',
								'name' => 'tags[]'
							) ); ?>
                        </div>
                    </fieldset>
                    <div class="submit inline-edit-save">
                        <button type="button" class="button cancel alignleft"><?php _e( 'Cancel' ); ?></button>
						<?php wp_nonce_field( 'inlineeditnonce', '_inline_edit' ); ?>
                        <button type="button"
                                class="button button-primary save alignright"><?php _e( 'Update' ); ?></button>
                        <span class="spinner"></span>
                        <br class="clear"/>
                        <div class="notice notice-error notice-alt inline hidden">
                            <p class="error"></p>
                        </div>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
		<?php
	}

	/**
	 * @param $contact Contact
	 *
	 * @return string
	 */
	protected function column_email( $contact ) {

		$editUrl = admin_url( 'admin.php?page=gh_contacts&action=edit&contact=' . $contact->get_id() );
		$html    = '<div id="inline_' . intval( $contact->get_id() ) . '" class="hidden">';
		$html    .= '  <div class="email">' . esc_html( $contact->get_email() ) . '</div>';
		$html    .= '  <div class="first_name">' . esc_html( $contact->get_first_name() ) . '</div>';
		$html    .= '  <div class="last_name">' . esc_html( $contact->get_last_name() ) . '</div>';
		$html    .= '  <div class="optin_status">' . esc_html( $contact->get_optin_status() ) . '</div>';
		if ( $contact->get_owner_id() ) {
			$html .= '  <div class="owner">' . esc_html( $contact->get_owner_id() ) . '</div>';
		}
		$html .= '  <div class="tags">' . esc_html( json_encode( $contact->get_tag_ids() ) ) . '</div>';
		$html .= '  <div class="tags-data">' . esc_html( wp_json_encode( $contact->get_tags_for_select2() ) ) . '</div>';
		$html .= '</div>';

		$html .= "<strong>";

		$html .= "<a class='row-title' href='$editUrl'>" . html()->e( 'img', [
				'src'   => $contact->get_profile_picture(),
				'style' => [
					'float'        => 'left',
					'margin-right' => '10px'
				],
				'width' => 40
			] ) . esc_html( $contact->get_email() ) . "</a>";

		if ( ! get_request_var( 'optin_status' ) ) {

			$html .= " &#x2014; " . "<span class='post-state'>(" . Preferences::get_preference_pretty_name( $contact->get_optin_status() ) . ")</span>";
		}

		$html .= "</strong>";

		return $html;

	}

	/**
	 * @param $contact Contact
	 *
	 * @return string
	 */
	protected function column_first_name( $contact ) {
		return $contact->get_first_name() ? $contact->get_first_name() : '&#x2014;';
	}

	/**
	 * @param $contact Contact
	 *
	 * @return string
	 */
	protected function column_last_name( $contact ) {
		return $contact->get_last_name() ? $contact->get_last_name() : '&#x2014;';
	}

	/**
	 * @param $contact Contact
	 *
	 * @return string
	 */
	protected function column_user_id( $contact ) {
		return $contact->get_userdata() ? '<a href="' . admin_url( 'user-edit.php?user_id=' . $contact->get_userdata()->ID ) . '">' . $contact->get_userdata()->display_name . '</a>' : '&#x2014;';
	}

	/**
	 * @param $contact Contact
	 *
	 * @return string
	 */
	protected function column_owner_id( $contact ) {
		return ! empty( $contact->get_owner_id() ) ? '<a href="' . admin_url( 'admin.php?page=gh_contacts&owner=' . $contact->get_owner_id() ) . '">' . $contact->get_ownerdata()->user_login . '</a>' : '&#x2014;';
	}

	/**
	 * @param $contact Contact
	 *
	 * @return string
	 */
	protected function column_date_created( $contact ) {
		$dc_time = mysql2date( 'U', $contact->get_date_created() );
		$dc_time = Plugin::instance()->utils->date_time->convert_to_utc_0( $dc_time );

		return scheduled_time_column( $dc_time, false, false, false );
	}

	/**
	 * Get default column value.
	 *
	 * @param object $contact A singular item (one full row's worth of data).
	 * @param string $column_name The name/slug of the column to be processed.
	 *
	 * @return string Text or HTML to be placed inside the column <td>.
	 */
	protected function column_default( $contact, $column_name ) {

		do_action( 'groundhogg_contacts_custom_column', $contact, $column_name );

		return '';
	}

	/**
	 * Get value for checkbox column.
	 *
	 * @param  $contact Contact A singular item (one full row's worth of data).
	 *
	 * @return string Text to be placed inside the column <td>.
	 */
	protected function column_cb( $contact ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			$this->_args['singular'],  // Let's simply repurpose the table's singular label ("movie").
			$contact->get_id()                // The value of the checkbox should be the record's ID.
		);
	}

	/**
	 * Get an associative array ( option_name => option_title ) with the list
	 * of bulk steps available on this table.
	 * @return array An associative array containing all the bulk steps.
	 */
	protected function get_bulk_actions() {

		$actions = array(
			'apply_tag'  => _x( 'Apply Tag', 'List table bulk action', 'groundhogg' ),
			'remove_tag' => _x( 'Remove Tag', 'List table bulk action', 'groundhogg' ),
			'delete'     => _x( 'Delete', 'List table bulk action', 'groundhogg' ),
			'spam'       => _x( 'Spam', 'List table bulk action', 'groundhogg' ),
			'unspam'     => _x( 'Unspam', 'List table bulk action', 'groundhogg' ),
		);

		// Sales reps/managers can't delete contacts...
		if ( ! current_user_can( 'delete_contacts' ) ){
			unset( $actions[ 'delete' ] );
        }

		return apply_filters( 'groundhogg_contact_bulk_actions', $actions );
	}

	protected function get_view() {
		return ( isset( $_GET['optin_status'] ) ) ? absint( $_GET['optin_status'] ) : 0;
	}

	protected function get_views() {

		$views = [
			'all'          => [
				'id'    => 'all',
				'name'  => __( 'All', 'groundhogg' ),
				'query' => []
			],
			'unconfirmed'  => [
				'id'    => 'unconfirmed',
				'name'  => __( 'Unconfirmed', 'groundhogg' ),
				'query' => [ 'optin_status' => Preferences::UNCONFIRMED ]
			],
			'confirmed'    => [
				'id'    => 'confirmed',
				'name'  => __( 'Confirmed', 'groundhogg' ),
				'query' => [ 'optin_status' => Preferences::CONFIRMED ],
			],
			'weekly'       => [
				'id'    => 'weekly',
				'name'  => __( 'Weekly', 'groundhogg' ),
				'query' => [ 'optin_status' => Preferences::WEEKLY ],
			],
			'monthly'      => [
				'id'    => 'monthly',
				'name'  => __( 'Monthly', 'groundhogg' ),
				'query' => [ 'optin_status' => Preferences::MONTHLY ],
			],
			'unsubscribed' => [
				'id'    => 'unsubscribed',
				'name'  => __( 'Unsubscribed', 'groundhogg' ),
				'query' => [ 'optin_status' => Preferences::UNSUBSCRIBED ],
			],
			'spam'         => [
				'id'    => 'spam',
				'name'  => __( 'Spam', 'groundhogg' ),
				'query' => [ 'optin_status' => Preferences::SPAM ],
			],
			'bounced'      => [
				'id'    => 'bounced',
				'name'  => __( 'Bounced', 'groundhogg' ),
				'query' => [ 'optin_status' => Preferences::HARD_BOUNCE ],
			],
			'complained'   => [
				'id'    => 'complained',
				'name'  => __( 'Complained', 'groundhogg' ),
				'query' => [ 'optin_status' => Preferences::COMPLAINED ],
			],
		];

		$parsed = [];

		foreach ( $views as $view ) {

			$view = wp_parse_args( $view, [
				'query' => [],
				'name'  => '',
				'id'    => '',
			] );

			$view['query']['view'] = $view['id'];

			$parsed[] = html()->e( 'a', [
				'href'  => admin_page_url( 'gh_contacts', $view['query'] ),
				'class' => get_url_var( 'view' ) === $view['id'] ? 'current' : '',
			], sprintf(
					'%s <span class="count">(%s)</span>',
					$view['name'],
					_nf( get_db( 'contacts' )->count( $view['query'] ) )
				)
			);
		}

		return $parsed;
	}

	/**
	 * Generates and displays row action links.
	 *
	 * @param        $contact     Contact Contact being acted upon.
	 * @param string $column_name Current column name.
	 * @param string $primary Primary column name.
	 *
	 * @return string Row steps output for posts.
	 */
	protected function handle_row_actions( $contact, $column_name, $primary ) {
		if ( $primary !== $column_name ) {
			return '';
		}

		$actions = array();
		$title   = $contact->get_email();

		$actions['inline hide-if-no-js'] = sprintf(
			'<a href="#" class="editinline" aria-label="%s">%s</a>',
			/* translators: %s: title */
			esc_attr( sprintf( __( 'Quick edit &#8220;%s&#8221; inline' ), $title ) ),
			__( 'Quick&nbsp;Edit' )
		);

		$editUrl = admin_url( 'admin.php?page=gh_contacts&action=edit&contact=' . $contact->get_id() );

		$actions['edit'] = sprintf(
			'<a href="%s" class="edit" aria-label="%s">%s</a>',
			/* translators: %s: title */
			$editUrl,
			esc_attr( __( 'Edit' ) ),
			__( 'Edit' )
		);

		if ( $contact->get_optin_status() === Preferences::SPAM ) {
			$actions['unspam'] = sprintf(
				'<a href="%s" class="unspam" aria-label="%s">%s</a>',
				wp_nonce_url( admin_url( 'admin.php?page=gh_contacts&contact=' . $contact->get_id() . '&action=unspam' ) ),
				/* translators: %s: title */
				esc_attr( sprintf( _x( 'Mark %s as approved.', 'action', 'groundhogg' ), $title ) ),
				__( 'Approve' )
			);
		} else if ( $contact->get_optin_status() === Preferences::HARD_BOUNCE ) {
			$actions['unbounce'] = sprintf(
				'<a href="%s" class="unbounce" aria-label="%s">%s</a>',
				wp_nonce_url( admin_url( 'admin.php?page=gh_contacts&contact=' . $contact->get_id() . '&action=unbounce' ) ),
				/* translators: %s: title */
				esc_attr( sprintf( _x( 'Mark %s as a valid email.', 'action', 'groundhogg' ), $title ) ),
				_x( 'Valid Email', 'action', 'groundhogg' )
			);
		} else {
			$actions['spam'] = sprintf(
				'<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
				wp_nonce_url( admin_url( 'admin.php?page=gh_contacts&contact=' . $contact->get_id() . '&action=spam' ) ),
				/* translators: %s: title */
				esc_attr( sprintf( _x( 'Mark %s as spam', 'action', 'groundhogg' ), $title ) ),
				__( 'Spam' )
			);
		}

		$actions['delete'] = sprintf(
			'<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
			wp_nonce_url( admin_url( 'admin.php?page=gh_contacts&contact=' . $contact->get_id() . '&action=delete' ) ),
			/* translators: %s: title */
			esc_attr( sprintf( __( 'Delete &#8220;%s&#8221; permanently' ), $title ) ),
			__( 'Delete' )
		);

		return $this->row_actions( apply_filters( 'groundhogg_contact_row_actions', $actions, $contact, $column_name ) );
	}

	/**
	 * @param string $which
	 */
	protected function extra_tablenav( $which ) {
		if ( $which === 'top' ) : ?>
            <script>
                jQuery(function ($) {
                    $("#bulk-action-selector-top,#bulk-action-selector-bottom").on("change", function () {
                        var $bulk = $(this);
                        if ($bulk.val() === "apply_tag" || $bulk.val() === "remove_tag") {
                            $(".bulk-tag-action").removeClass("hidden");
                        } else {
                            $(".bulk-tag-action").addClass("hidden");
                        }
                    });
                });
            </script>
            <div class="alignleft gh-actions bulk-tag-action hidden">
                <div style="width: 300px;display: inline-block;margin: 0 20px 5px 0"><?php echo Plugin::$instance->utils->html->tag_picker( [
						'name'        => 'bulk_tags[]',
						'id'          => 'bulk_tags',
						'class'       => 'gh-tag-picker',
						'data'        => array(),
						'selected'    => array(),
						'multiple'    => true,
						'placeholder' => __( 'Bulk Apply/Remove Tags', 'groundhogg' ),
						'tags'        => true,
					] ); ?></div>
            </div>
		<?php endif; ?>
        <div class="alignleft gh-actions">
			<?php

			if ( current_user_can( 'export_contacts' ) && $this->get_pagination_arg( 'total_items' ) > 0 ) {
				$export_query = $this->query;


				unset( $export_query['number'] );
				unset( $export_query['limit'] );
				unset( $export_query['offset'] );

				$export_url = admin_page_url( 'gh_tools', [
					'tab'    => 'export',
					'action' => 'choose_columns',
					'query'  => $export_query,
				] );

				?>
                <a class="button action export-contacts"
                   href="<?php echo esc_url( $export_url ); ?>"><?php printf( _nx( 'Export %s contact', 'Export %s contacts', $this->get_pagination_arg( 'total_items' ), 'action', 'groundhogg' ), number_format_i18n( $this->get_pagination_arg( 'total_items' ) ) ); ?></a>
				<?php
			}

			if ( current_user_can( 'schedule_broadcasts' ) && $this->get_pagination_arg( 'total_items' ) > 0  ){

				$broadcast_query = $this->query;

				unset( $broadcast_query['number'] );
				unset( $broadcast_query['limit'] );
				unset( $broadcast_query['offset'] );

				$broadcast_url = admin_page_url( 'gh_broadcasts', [
					'action' => 'add',
					'type'   => 'email',
					'query'  => $broadcast_query,
				] );

				?>
                <a class="button action broadcast-contacts"
                   href="<?php echo esc_url( $broadcast_url ); ?>">
                    <?php printf( _nx( 'Send a broadcast to %s contact', 'Send a broadcast to %s contacts', $this->get_pagination_arg( 'total_items' ), 'action', 'groundhogg' ), number_format_i18n( $this->get_pagination_arg( 'total_items' ) ) ); ?>
                </a>
				<?php
            }

			do_action( 'groundhogg/admin/contacts/table/extra_tablenav', $this );

			?></div><?php

	}
}