<?php

namespace Groundhogg\Admin\Emails;

use Groundhogg;
use Groundhogg\Admin\Admin_Page;
use Groundhogg\Email;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use function Groundhogg\get_url_var;
use function Groundhogg\html;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * View Emails
 *
 * Allow the user to view & edit the emails
 * Contains add, save, delete, etc for the admin functions...
 *
 * @since       File available since Release 0.1
 * @subpackage  Admin/Emails
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Admin
 */
class Emails_Page extends Admin_Page {


	protected function get_current_action() {

		$action = parent::get_current_action();

		if ( $action == 'view' && get_db( 'emails' )->is_empty() ) {
			$action = 'add';
		}

		return $action;
	}

	protected function add_additional_actions() {
		Groundhogg\add_disable_emojis_action();

		if ( $this->is_current_page() && in_array( $this->get_current_action(), [ 'add', 'edit' ] ) ) {
			add_action( 'in_admin_header', array( $this, 'prevent_notices' ) );
		}
	}

	public function admin_title( $admin_title, $title ) {
		switch ( $this->get_current_action() ) {
			case 'add':
				$admin_title = sprintf( "%s &lsaquo; %s", __( 'Add' ), $admin_title );
				break;
			case 'edit':
				$email_id = Groundhogg\get_request_var( 'email' );

				if ( ! $email_id ) {
					wp_die( 'Invalid Email Id.' );
				}

				$email       = new Email( absint( $email_id ) );
				$admin_title = sprintf( "%s &lsaquo; %s &lsaquo; %s", $email->get_title(), __( 'Edit' ), $admin_title );
				break;
		}

		return $admin_title;
	}

	public function get_slug() {
		return 'gh_emails';
	}

	public function get_name() {
		return _x( 'Emails', 'page_title', 'groundhogg' );
	}

	public function get_cap() {
		return 'edit_emails';
	}

	public function get_item_type() {
		return 'email';
	}

	public function get_priority() {
		return 15;
	}

	public function scripts() {

		if ( $this->current_action_is( [ 'edit', 'add' ] ) ) {

			$email_id = absint( Groundhogg\get_request_var( 'email' ) );
			$email    = new Email( $email_id );

			if ( ! $email->exists() ) {
				$email->title = 'My new email';
			}

			Groundhogg\enqueue_email_block_editor_assets( [
				'email_id' => $email_id,
				'email'    => $email
			] );
		}

		if ( $this->current_action_is( 'view' ) ) {
			wp_enqueue_script( 'groundhogg-admin-components' );

			$this->enqueue_table_filters( [
				'stringColumns' => [
					'title'   => 'Title',
					'subject' => 'Subject',
					'content' => 'Content',
				],
				'selectColumns' => [
					'message_type' => [
						'Message Type',
						[
							\Groundhogg_Email_Services::MARKETING     => 'Marketing',
							\Groundhogg_Email_Services::TRANSACTIONAL => 'Transactional',
						]
					]
				]
			] );

			wp_enqueue_script( 'groundhogg-admin-email-filters' );
		}

		remove_editor_styles();

		wp_enqueue_style( 'groundhogg-admin' );
	}

	/**
	 * Add help tab at top of screen
	 *
	 * @return mixed|void
	 */
	public function help() {
	}

	/**
	 * Get the title of the current page
	 */
	function get_title() {
		switch ( $this->get_current_action() ) {
			case 'add':
				return _x( 'Add Email', 'page_title', 'groundhogg' );
				break;
			case 'edit':
				return _x( 'Edit Email', 'page_title', 'groundhogg' );
				break;
			case 'view':
			default:
				return _x( 'Emails', 'page_title', 'groundhogg' );
				break;
		}
	}

	/**
	 *
	 *
	 * @return array|array[]
	 */
	protected function get_title_actions() {

		$broadcast_args = [ 'action' => 'add', 'type' => 'email' ];

		if ( $email = Groundhogg\get_request_var( 'email' ) ) {
			$broadcast_args['email'] = absint( $email );
		}

		return [
			[
				'link'   => $this->admin_url( [ 'action' => 'add' ] ),
				'action' => __( 'Add New', 'groundhogg' ),
				'target' => '_self',
			],
			[
				'link'   => Groundhogg\admin_page_url( 'gh_broadcasts', $broadcast_args ),
				'action' => __( 'Broadcast', 'groundhogg' ),
				'target' => '_self',
			],
		];
	}

	/**
	 * Restore trashed email
	 *
	 * @return false
	 */
	public function process_restore() {
		if ( ! current_user_can( 'edit_emails' ) ) {
			$this->wp_die_no_access();
		}

		foreach ( $this->get_items() as $id ) {
			$email = new Email( $id );
			if ( $email->exists() ) {
				$email->update( [ 'status' => 'draft' ] );
			}
		}

		$this->add_notice(
			esc_attr( 'restored' ),
			sprintf( "%s %d %s",
				__( 'Restored' ),
				count( $this->get_items() ),
				__( 'Emails', 'groundhogg' ) ),
			'success'
		);

		return false;
	}

	/**
	 * Duplicate an email
	 *
	 * @return string|\WP_Error
	 */
	public function process_duplicate() {
		if ( ! current_user_can( 'add_emails' ) ) {
			$this->wp_die_no_access();
		}

		$email_id = absint( get_url_var( 'email' ) );

		$email = new Email( $email_id );

		if ( ! $email->exists() ) {
			return new \WP_Error( 'error', 'Email does not exist.' );
		}

		$new = $email->duplicate();

		return $new->admin_link();
	}

	/**
	 * Empty trashed emails
	 *
	 * @return false
	 */
	public function process_empty_trash() {

		if ( ! current_user_can( 'delete_emails' ) ) {
			$this->wp_die_no_access();
		}

		$emails = get_db( 'emails' )->query( [ 'status' => 'trash' ] );

		foreach ( $emails as $email ) {
			$email = new Email( $email );
			if ( $email->exists() ) {
				$email->delete();
			}
		}

		$this->add_notice(
			esc_attr( 'deleted' ),
			sprintf( "%s %d %s",
				__( 'Deleted' ),
				count( $emails ),
				__( 'Emails', 'groundhogg' ) ),
			'success'
		);

		return false;
	}

	/**
	 * Delete an email
	 *
	 * @return false|\WP_Error
	 */
	public function process_delete() {
		if ( ! current_user_can( 'delete_emails' ) ) {
			$this->wp_die_no_access();
		}

		foreach ( $this->get_items() as $id ) {
			$email = new Email( $id );
			if ( $email->exists() ) {
				$email->delete();
			}
		}

		$this->add_notice(
			esc_attr( 'deleted' ),
			sprintf( _nx( 'Deleted %d email', 'Deleted %d emails', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $this->get_items() ) ),
			'success'
		);

		return false;
	}

	/**
	 * Move an email to the trash
	 *
	 * @return false
	 */
	public function process_trash() {
		if ( ! current_user_can( 'edit_emails' ) ) {
			$this->wp_die_no_access();
		}

		foreach ( $this->get_items() as $id ) {
			$email = new Email( $id );
			if ( $email->exists() ) {
				$email->update( [ 'status' => 'trash' ] );
			}
		}

		$this->add_notice(
			esc_attr( 'trashed' ),
			sprintf( "%s %d %s",
				__( 'Trashed' ),
				count( $this->get_items() ),
				__( 'Emails', 'groundhogg' ) ),
			'success'
		);

		return false;
	}

	/**
	 *  Export an email as json
	 */
	public function process_export() {

		if ( ! current_user_can( 'edit_emails' ) ) {
			$this->wp_die_no_access();
		}

		$email = new Email( absint( get_url_var( 'email' ) ) );

		if ( ! $email->exists() ) {
			return new \WP_Error( 'error', 'Email could not be exported.' );
		}

		Groundhogg\download_json( $email, $email->get_title() );
	}

	protected function search_form( $title = false, $name = 's' ) {

		?>
        <form method="get" class="search-form">
			<?php html()->hidden_GET_inputs( true ); ?>

	        <?php if ( ! get_url_var( 'include_filters' ) ):
		        echo html()->input( [
			        'type' => 'hidden',
			        'name' => 'include_filters'
		        ] );
	        endif; ?>

            <label class="screen-reader-text" for="gh-post-search-input"><?php esc_attr_e( 'Search' ); ?>:</label>

            <div style="float: right" class="gh-input-group">
                <input type="search" id="gh-post-search-input" name="s"
                       value="<?php esc_attr_e( get_request_var( 's' ) ); ?>">
				<?php

				echo html()->dropdown( [
					'options'           => [
						'title'   => __( 'Title', 'groundhogg' ),
						'subject' => __( 'Subject', 'groundhogg' ),
						'content' => __( 'Content', 'groundhogg' ),
					],
					'option_none'       => __( 'Everywhere', 'groundhogg' ),
					'option_none_value' => '',
					'name'              => 'search_columns',
					'selected'          => get_request_var( 'search_columns' )
				] );

				?>
                <button type="submit" id="search-submit"
                        class="gh-button primary small"><?php esc_attr_e( 'Search' ); ?></button>
            </div>
        </form>
		<?php
	}

	/**
	 * Table
	 *
	 * @return mixed|void
	 */
	public function view() {

		if ( ! class_exists( 'Emails_Table' ) ) {
			include __DIR__ . '/emails-table.php';
		}

		$emails_table = new Emails_Table();

		$emails_table->views();
		$this->table_filters();

		$this->search_form();

		?>
        <form method="post">
			<?php $emails_table->prepare_items(); ?>
			<?php $emails_table->display(); ?>
        </form>
		<?php
	}

	/**
	 * Output HTML necessary for the new block editor
	 */
	public function block_editor() {
		if ( ! current_user_can( 'edit_emails' ) ) {
			$this->wp_die_no_access();
		}

		include __DIR__ . '/block-editor.php';
	}

	/**
	 * Output HTML for the page
	 */
	public function page() {

		if ( $this->current_action_is( 'edit' ) || $this->current_action_is( 'add' ) ) {
			$this->block_editor();

			return;
		}

		parent::page();
	}

	/**
	 * @deprecated now using block editor
	 */
	public function edit() {
		if ( ! current_user_can( 'edit_emails' ) ) {
			$this->wp_die_no_access();
		}

		$this->block_editor();
	}

	/**
	 * @deprecated now using block editor
	 */
	public function add() {
		if ( ! current_user_can( 'add_emails' ) ) {
			$this->wp_die_no_access();
		}

		$this->block_editor();
	}

	protected function add_ajax_actions() {
		// TODO: Implement add_ajax_actions() method.
	}
}
