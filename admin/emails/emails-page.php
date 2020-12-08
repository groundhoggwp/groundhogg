<?php

namespace Groundhogg\Admin\Emails;

use Groundhogg;
use Groundhogg\Admin\Admin_Page;
use Groundhogg\Contact;
use Groundhogg\Email;
use Groundhogg\Plugin;
use function Groundhogg\get_request_var;
use function Groundhogg\get_url_var;
use function Groundhogg\has_replacements;
use function Groundhogg\managed_page_url;
use function Groundhogg\set_user_test_email;

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


	protected function add_ajax_actions() {
		add_action( 'wp_ajax_gh_update_email', array( $this, 'update_email_ajax' ) );
		add_action( 'wp_ajax_get_my_emails_search_results', array( $this, 'get_my_emails_search_results' ) );
	}

	protected function add_additional_actions() {
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

				$email       = Plugin::$instance->utils->get_email( absint( $email_id ) );
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
		if ( in_array( $this->get_current_action(), [ 'add', 'edit' ] ) ) {

			wp_enqueue_style( 'groundhogg-admin-email-editor-plain' );
			wp_enqueue_script( 'groundhogg-admin-email-editor-plain' );

			wp_localize_script( 'groundhogg-admin-email-editor-plain', 'Email', [
				'send_test_prompt' => __( 'Send test email to...', 'groundhogg' ),
				'email_id'         => absint( Groundhogg\get_request_var( 'email' ) ),
			] );

			remove_editor_styles();

			add_filter( 'mce_css', function ( $mce_css ) {
				return $mce_css . ', ' . GROUNDHOGG_ASSETS_URL . 'css/admin/email-wysiwyg-style.css';
			} );

			wp_enqueue_script( 'groundhogg-admin-iframe' );
			wp_enqueue_style( 'groundhogg-admin-iframe' );

			wp_enqueue_style( 'groundhogg-admin-email-preview' );
			wp_enqueue_script( 'groundhogg-admin-email-preview' );

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
	 * @return array|array[]
	 */
	protected function get_title_actions() {
		$broadcast_args = [ 'action' => 'add', 'type' => 'email' ];
		$reporting_args = [ 'tab' => 'email' ];

		if ( $email = Groundhogg\get_request_var( 'email' ) ) {
			$broadcast_args[ 'email' ] = absint( $email );
			$reporting_args            = [
				'tab'   => 'email_step',
				'email' => $email,
			];
		}

		return [
			[
				'link'   => $this->admin_url( [ 'action' => 'add' ] ),
				'action' => __( 'Add New', 'groundhogg' ),
				'target' => '_self',
			],
			[
				'link'   => Plugin::$instance->admin->get_page( 'broadcasts' )->admin_url( $broadcast_args ),
				'action' => __( 'Broadcast', 'groundhogg' ),
				'target' => '_self',
			],
			[
				'link'   => Groundhogg\admin_page_url( 'gh_reporting', $reporting_args ),
				'action' => __( 'Reporting', 'groundhogg' ),
				'target' => '_self',
			],
		];
	}

	public function process_restore() {
		if ( ! current_user_can( 'edit_emails' ) ) {
			$this->wp_die_no_access();
		}

		foreach ( $this->get_items() as $id ) {
			Plugin::$instance->dbs->get_db( 'emails' )->update( $id, [ 'status' => 'draft' ] );
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

	public function process_empty_trash() {

		if ( ! current_user_can( 'delete_emails' ) ) {
			$this->wp_die_no_access();
		}

		$emails = Plugin::$instance->dbs->get_db( 'emails' )->query( [ 'status' => 'trash' ] );

		foreach ( $emails as $email ) {
			Plugin::$instance->dbs->get_db( 'emails' )->delete( $email->ID );
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

	public function process_delete() {
		if ( ! current_user_can( 'delete_emails' ) ) {
			$this->wp_die_no_access();
		}

		foreach ( $this->get_items() as $id ) {
			if ( ! Plugin::$instance->dbs->get_db( 'emails' )->delete( $id ) ) {
				return new \WP_Error( 'unable_to_delete_email', "Something went wrong deleting the email." );
			}
		}

		$this->add_notice(
			esc_attr( 'deleted' ),
			sprintf( _nx( 'Deleted %d email', 'Deleted %d emails', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $this->get_items() ) ),
			'success'
		);

		return false;
	}

	public function process_trash() {
		if ( ! current_user_can( 'edit_emails' ) ) {
			$this->wp_die_no_access();
		}

		foreach ( $this->get_items() as $id ) {
			Plugin::$instance->dbs->get_db( 'emails' )->update( $id, [ 'status' => 'trash' ] );
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
	 * Add the email to the DB
	 *
	 * @return bool|string|\WP_Error
	 */
	public function process_add() {
		if ( ! current_user_can( 'add_emails' ) ) {
			$this->wp_die_no_access();
		}

		$default_tab = Groundhogg\is_option_enabled( 'gh_use_advanced_email_editor' ) ? 'my-emails' : 'new-email';

		$tab = Groundhogg\get_url_var( 'tab', $default_tab );

		switch ( $tab ) {

			case 'new-email':

				$email_id = Groundhogg\get_db( 'emails' )->add( [ 'author' => get_current_user_id() ] );

				if ( ! $email_id ) {
					return new \WP_Error( 'error', 'Unable to create email.' );
				}

				Groundhogg\set_request_var( 'email', $email_id );

				$result = $this->process_edit();

				if ( $result === true ) {
					return Groundhogg\admin_page_url( 'gh_emails', [ 'action' => 'edit', 'email' => $email_id ] );
				}

				return $result;

				break;

			case 'my-templates':
			case 'my-emails':
				$from_email = new Email( absint( Groundhogg\get_post_var( 'email_id' ) ) );

				if ( ! $from_email->exists() ) {
					return new \WP_Error( 'error', 'Invalid email ID!' );
				}

				$args[ 'content' ]    = $from_email->get_content();
				$args[ 'subject' ]    = $from_email->get_subject_line();
				$args[ 'title' ]      = sprintf( "%s - (copy)", $from_email->get_title() );
				$args[ 'pre_header' ] = $from_email->get_pre_header();

				$args[ 'author' ]    = get_current_user_id();
				$args[ 'from_user' ] = get_current_user_id();

				$email = new Email( $args );

				if ( ! $email->exists() ) {
					return new \WP_Error( 'error', 'Could not create email.' );
				}

				return Groundhogg\admin_page_url( 'gh_emails', [ 'action' => 'edit', 'email' => $email->get_id() ] );

				break;
			default:
				do_action( "groundhogg/admin/emails/process_add/{$tab}", $this );
				break;
		}

		return true;
	}

	/**
	 * Process the editing actions of the email
	 *
	 * @return bool|\WP_Error
	 */
	public function process_edit() {

		if ( ! current_user_can( 'edit_emails' ) ) {
			$this->wp_die_no_access();
		}

		$id    = absint( Groundhogg\get_request_var( 'email' ) );
		$email = Plugin::$instance->utils->get_email( $id );

		$args = array();

		$status = sanitize_text_field( Groundhogg\get_request_var( 'email_status', 'draft' ) );

		if ( $status === 'draft' ) {
			$this->add_notice( 'email-in-draft-mode', __( 'Emails cannot be sent while in DRAFT mode.', 'groundhogg' ), 'warning' );
		}

		$from_user = absint( Groundhogg\get_request_var( 'from_user' ) );

		$subject    = sanitize_text_field( Groundhogg\get_request_var( 'subject' ) );
		$pre_header = sanitize_text_field( Groundhogg\get_request_var( 'pre_header' ) );
		$content    = apply_filters( 'groundhogg/admin/emails/sanitize_email_content', Groundhogg\get_request_var( 'email_content' ) );

		$args[ 'status' ]       = $status;
		$args[ 'from_user' ]    = $from_user;
		$args[ 'subject' ]      = $subject;
		$args[ 'title' ]        = sanitize_text_field( Groundhogg\get_request_var( 'title', $subject ) );
		$args[ 'pre_header' ]   = $pre_header;
		$args[ 'content' ]      = $content;
		$args[ 'last_updated' ] = current_time( 'mysql' );
		$args[ 'is_template' ]  = key_exists( 'save_as_template', $_POST ) ? 1 : 0;


		if ( $email->update( $args ) ) {
			$this->add_notice( 'email-updated', __( 'Email Updated.', 'groundhogg' ), 'success' );
		} else {
			return new \WP_Error( 'unable_to_update_email', 'Unable to update email!' );
		}

		$email->update_meta( 'message_type', sanitize_text_field( Groundhogg\get_request_var( 'message_type' ) ) );
		$email->update_meta( 'alignment', sanitize_text_field( Groundhogg\get_request_var( 'email_alignment' ) ) );
		$email->update_meta( 'browser_view', boolval( Groundhogg\get_request_var( 'browser_view' ) ) );
		$email->update_meta( 'reply_to_override', sanitize_email( Groundhogg\get_request_var( 'reply_to_override' ) ) );

		if ( Groundhogg\get_request_var( 'use_custom_alt_body' ) ) {
			$email->update_meta( 'use_custom_alt_body', 1 );
			$email->update_meta( 'alt_body', wp_strip_all_tags( Groundhogg\get_request_var( 'alt_body' ) ) );
		} else {
			$email->delete_meta( 'use_custom_alt_body' );
			$email->delete_meta( 'alt_body' );
		}

		$headers = [];

		$headers_key   = Groundhogg\get_request_var( 'header_key' );
		$headers_value = Groundhogg\get_request_var( 'header_value' );

		if ( $headers_key && $headers_value ) {
			for ( $i = 0; $i < count( $headers_key ); $i ++ ) {
				if ( $headers_key[ $i ] ) {
					$header_key   = strtolower( sanitize_key( $headers_key[ $i ] ) );
					$header_value = $headers_value[ $i ];

					switch ( $header_key ) {
						case 'from':
							// If only the email is provided
							if ( is_email( $header_value ) ) {
								$headers[ $header_key ] = has_replacements( $header_value ) ? sanitize_text_field( $header_value ) : sanitize_email( $header_value );
							} else if ( preg_match( '/([^<]+) <([^>]+)>/', $header_value, $matches ) ) {
								$email_address          = has_replacements( $matches[ 2 ] ) ? sanitize_text_field( $matches[ 2 ] ) : sanitize_email( $matches[ 2 ] );
								$name                   = sanitize_text_field( $matches[ 1 ] );
								$headers[ $header_key ] = sprintf( '%s <%s>', $name, $email_address );
							} else {
								$headers[ $header_key ] = '';
							}
							break;
						case 'bcc':
						case 'cc':
						case 'return-path':
						case 'reply-to':
							$emails                 = explode( ',', $header_value );
							$emails                 = map_deep( $emails, 'trim' );
							$emails                 = map_deep( $emails, function ( $email ) {
								if ( has_replacements( $email ) ) {
									return sanitize_text_field( $email );
								} else {
									return sanitize_email( $email );
								}
							} );
							$emails                 = implode( ',', array_filter( $emails ) );
							$headers[ $header_key ] = $emails;
							break;
						default:
							$headers[ $header_key ] = sanitize_text_field( $header_value );
							break;
					}
				}
			}
		}

		$email->update_meta( 'custom_headers', $headers );

		if ( Groundhogg\get_request_var( 'test_email' ) ) {

			if ( ! current_user_can( 'send_emails' ) ) {
				$this->wp_die_no_access();
			}

			$test_email = strtolower( sanitize_email( get_request_var( 'test_email', wp_get_current_user()->user_email ) ) );
			$test_email = apply_filters( 'groundhogg/admin/emails/test_email', $test_email );

			if ( $test_email && is_email( $test_email ) ) {

				$contact = new Contact( $test_email );

				if ( $contact->exists() && $contact->get_email() === $test_email ) {

					$email->enable_test_mode();

					$sent = $email->send( $contact );

					set_user_test_email( $test_email );

					if ( ! $sent || is_wp_error( $sent ) ) {
						$error = is_wp_error( $sent ) ? $sent : new \WP_Error( 'oops', "Failed to send test." );
						$this->add_notice( $error );
					} else {
						$this->add_notice(
							esc_attr( 'sent-test' ),
							sprintf( "%s %s",
								__( 'Sent test email to', 'groundhogg' ),
								$contact->get_email() ),
							'success'
						);
					}
				}
			}
		}

		return true;
	}

	public function view() {
		if ( ! class_exists( 'Emails_Table' ) ) {
			include __DIR__ . '/emails-table.php';
		}

		$emails_table = new Emails_Table();

		$emails_table->views();

		$this->search_form( __( 'Search Emails', 'groundhogg' ) );

		?>
        <form method="post">
			<?php $emails_table->prepare_items(); ?>
			<?php $emails_table->display(); ?>
        </form>
		<?php
	}

	public function add() {
		if ( ! current_user_can( 'add_emails' ) ) {
			$this->wp_die_no_access();
		}

		include __DIR__ . '/add-new.php';
	}

	public function edit() {
		if ( ! current_user_can( 'edit_emails' ) ) {
			$this->wp_die_no_access();
		}

		include __DIR__ . '/email-editor.php';
	}


	/**
	 * Get search results
	 */
	public function get_my_emails_search_results() {
		ob_start();

		$emails = array_slice( Plugin::$instance->dbs->get_db( 'emails' )->query( [ 'search' => sanitize_text_field( Groundhogg\get_request_var( 's' ) ) ] ), 0, 20 );

		if ( empty( $emails ) ):
			?> <p
                style="text-align: center;font-size: 24px;"><?php _ex( 'Sorry, no emails were found.', 'notice', 'groundhogg' ); ?></p> <?php
		else:
			?>
			<?php foreach ( $emails as $email ):
			$email = new Email( $email->ID );
			?>
            <div class="postbox">
                <h2 class="hndle"><?php echo $email->get_title(); ?></h2>
                <div class="inside">
                    <p><?php echo __( 'Subject: ', 'groundhogg' ) . $email->get_subject_line(); ?></p>
                    <p><?php echo __( 'Pre-Header: ', 'groundhogg' ) . $email->get_pre_header(); ?></p>
                    <iframe class="email-container" style="margin-bottom: 10px; border: 1px solid #e5e5e5;" width="100%"
                            height="500" src="<?php echo managed_page_url( 'emails/' . $email->get_id() ); ?>"></iframe>
                    <button class="choose-template button-primary" name="email_id"
                            value="<?php echo $email->get_id(); ?>"><?php _e( 'Start Writing', 'groundhogg' ); ?></button>
                </div>
            </div>
		<?php endforeach;

		endif;

		$response = [ 'html' => ob_get_clean() ];
		wp_send_json( $response );
	}
}