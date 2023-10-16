<?php

namespace Groundhogg\Steps\Actions;

use Groundhogg\Classes\Activity;
use Groundhogg\Contact;
use Groundhogg\Email;
use Groundhogg\Event;
use Groundhogg\Step;
use function Groundhogg\get_array_var;
use function Groundhogg\get_db;
use function Groundhogg\get_object_ids;
use function Groundhogg\html;
use function Groundhogg\isset_not_empty;
use function Groundhogg\track_activity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Send Email
 *
 * This will send an email to the contact using WP_MAIL
 *
 * @since       File available since Release 0.9
 * @see         WPGH_Email::send()
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Elements
 * @subpackage  Elements/Actions
 */
class Send_Email extends Action {

	const TYPE = 'send_email';

	/**
	 * @return string
	 */
	public function get_help_article() {
		return 'https://docs.groundhogg.io/docs/builder/actions/send-email/';
	}

	/**
	 * Get the element name
	 *
	 * @return string
	 */
	public function get_name() {
		return _x( 'Send Email', 'step_name', 'groundhogg' );
	}

	public function get_sub_group() {
		return 'comms';
	}

	/**
	 * Get the element type
	 *
	 * @return string
	 */
	public function get_type() {
		return 'send_email';
	}

	/**
	 * Get the description
	 *
	 * @return string
	 */
	public function get_description() {
		return _x( 'Send an email to a contact.', 'step_description', 'groundhogg' );
	}

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/send-email.svg';
	}

	/**
	 * Display the settings
	 *
	 * @param $step Step
	 */
	public function settings( $step ) {

		echo html()->e( 'div', [ 'id' => 'step_' . $step->get_id() . '_send_email' ], '', false )

		?><p></p><?php
	}

	public function validate_settings( Step $step ) {
		$email = new Email( $this->get_setting( 'email_id' ) );

		if ( ! $email->exists() ) {
			$step->add_error( 'email_dne', __( 'You have not selected an email!', 'groundhogg' ) );
		}

		if ( ( $email->is_draft() && $step->get_funnel()->is_active() ) ) {
			$step->add_error( 'email_in_draft_mode', __( 'The selected email is in draft mode! It will not be sent and will cause automation to stop.' ) );
		}
	}

	/**
	 * Save the settings
	 *
	 * @param $step Step
	 */
	public function save( $step ) {
		$this->save_setting( 'skip_if_confirmed', ( bool ) $this->get_posted_data( 'skip_if_confirmed', false ) );
		$this->save_setting( 'reply_in_thread', ( bool ) $this->get_posted_data( 'reply_in_thread', false ) );
	}

	public function generate_step_title( $step ) {

		$email = new Email( $this->get_setting( 'email_id' ) );

		if ( ! $email->exists() ) {
			return 'Send an email';
		}

		return sprintf( __( 'Send %s', 'groundhogg' ), '<b>' . $email->get_title() . '</b>' );
	}

	/**
	 * Conditionally adds In-Reply-To and References headers
	 *
	 * @param $headers string[]
	 *
	 * @return string[]
	 */
	public function set_thread_headers( $headers ) {
		$message_id = sprintf( '<%s>', $this->message_id );

		$headers['in-reply-to'] = $message_id;
		$headers['references']  = $message_id;

		return $headers;
	}

	/**
	 * Add "Re:" to the subject line and set the subject line to that of the previous email
	 *
	 * @param string $subject
	 *
	 * @return string the new subject line
	 */
	public function set_thread_subject( $subject ) {
		return sprintf( __( 'Re: %s', 'groundhogg' ), $this->subject );
	}

	protected $message_id;
	protected $subject;

	/**
	 * Process the apply note step...
	 *
	 * @param $contact Contact
	 * @param $event   Event
	 *
	 * @return bool|\WP_Error
	 */
	public function run( $contact, $event ) {

		$email_id = absint( $this->get_setting( 'email_id' ) );
		$email    = new Email( $email_id );

		if ( ! $email->exists() ) {
			return new \WP_Error( 'email_dne', 'Invalid email ID provided.' );
		}

		$reply_in_thread = $this->get_setting( 'reply_in_thread' );

		// Either starting a thread, or replying to a thread
		if ( $reply_in_thread ) {

			// Get the previous email step in the funnel
			$prev_email_step = $this->get_current_step()->get_prev_of_type( $this->get_type() );

			// If the previous email in the funnel is part of the thread
			$prev_is_thread = $prev_email_step && $prev_email_step->get_meta( 'reply_in_thread' );

			// If the previous email is part of the thread
			if ( $prev_is_thread ) {

				// Check for thread activity
				$thread_replies = get_db( 'activity' )->query( [
					'activity_type' => 'thread_reply',
					'funnel_id'     => $event->get_funnel_id(),
					'contact_id'    => $contact->get_id(),
					// Can only thread in sequence, no skipping
					'step_id'       => $prev_email_step->get_id(),
					'orderby'       => 'ID',
					'order'         => 'DESC',
					'limit'         => 1
				] );

				// We are replying to the previous email
				if ( ! empty( $thread_replies ) ) {

					$last_thread_reply = new Activity( $thread_replies[0] );

					$this->message_id = $last_thread_reply->get_meta( 'message_id' );
					$this->subject    = $last_thread_reply->get_meta( 'subject' );

					// Filter subject line
					add_filter( 'groundhogg/email/subject', [ $this, 'set_thread_subject' ] );
					// Filter headers
					add_filter( 'groundhogg/email/headers', [ $this, 'set_thread_headers' ] );
				}
			}
		}

		$sent = $email->send( $contact, $event );

		if ( $reply_in_thread ) {

			if ( isset( $last_thread_reply ) ) {

				$subject = $this->subject;

				$this->message_id = '';
				$this->subject    = '';
				remove_filter( 'groundhogg/email/headers', [ $this, 'set_thread_headers' ] );
				remove_filter( 'groundhogg/email/subject', [ $this, 'set_thread_subject' ] );
			} else {
				$subject = $email->get_merged_subject_line();
			}

			$message_id = \Groundhogg_Email_Services::get_message_id();

			track_activity( $contact, 'thread_reply', [
				'funnel_id' => $event->get_funnel_id(),
				'step_id'   => $event->get_step_id(),
				'event_id'  => $event->get_id(),
				'email_id'  => $email->get_id(),
			], [
				'message_id' => $message_id,
				'subject'    => $subject
			] );

		}

		return $sent;
	}

	/**
	 * Add the reply settings, as well as skip if confirmed if the email is transactional
	 *
	 * @param Step $step
	 */
	protected function before_step_notes( Step $step ) {

		$email            = new Email( $this->get_setting( 'email_id' ) );
		$has_confirmation = $email->exists() && $email->is_confirmation_email();

		$prev_email_step = $step->get_prev_of_type( 'send_email' );
		$prev_is_reply   = false;

		if ( $prev_email_step ) {
			$prev_is_reply = $prev_email_step->get_meta( 'reply_in_thread' );
		}

		?>
		<div class="gh-panel">
			<div class="gh-panel-header">
				<h2><?php _e( 'Email Settings' ) ?></h2>
			</div>
			<div class="inside display-flex column gap-10">
				<?php echo html()->checkbox( [
					'label'   => $prev_is_reply ? __( 'Send as reply to the previous email', 'groundhogg' ) : __( 'Start an email thread', 'groundhogg' ),
					'name'    => $this->setting_name_prefix( 'reply_in_thread' ),
					'checked' => $this->get_setting( 'reply_in_thread' ),
				] ); ?>
				<?php if ( $has_confirmation ): ?>
					<?php echo html()->checkbox( [
						'label'   => __( 'Skip this email if the contact is already confirmed', 'groundhogg' ),
						'name'    => $this->setting_name_prefix( 'skip_if_confirmed' ),
						'checked' => $this->get_setting( 'skip_if_confirmed' )
					] ); ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	protected function labels() {

		if ( $this->get_setting( 'reply_in_thread' ) ): ?>
			<div class="step-label green"><?php _e( 'Thread', 'groundhogg' ); ?></div>
		<?php
		endif;
	}

	/**
	 * Create a new email and set the step email_id to the ID of the new email.
	 *
	 * @param $step Step
	 * @param $args array list of args to provide criteria for import.
	 */
	public function import( $args, $step ) {

		// Not doing new import
		if ( ! isset_not_empty( $args, 'email' ) ) {
			// Legacy import
			if ( isset_not_empty( $args, 'content' ) ) {
				$this->legacy_import( $args, $step );
			}

			return;
		}

		$raw_email = json_decode( wp_json_encode( $args['email'] ), true );
		$data      = wp_array_slice_assoc( $raw_email['data'], [ 'title', 'subject', 'pre_header', 'content' ] );
		$meta      = $raw_email['meta'];

		$email = new Email();

		$email->create( $data );
		$email->update_meta( $meta );

		$step->update_meta( 'email_id', $email->get_id() );
	}

	/**
	 * Create a new email and set the step email_id to the ID of the new email.
	 *
	 * @param $step Step
	 * @param $args array list of args to provide criteria for import.
	 */
	public function legacy_import( $args, $step ) {

		if ( ! isset_not_empty( $args, 'content' ) || ! isset_not_empty( $args, 'subject' ) ) {
			return;
		}

		if ( ! isset_not_empty( $args, 'content' ) ) {
			$args['pre_header'] = '';
		}

		$email_id = get_db( 'emails' )->add( [
			'content'    => $args['content'],
			'subject'    => $args['subject'],
			'pre_header' => $args['pre_header'],
			'title'      => get_array_var( $args, 'title', $args['subject'] ),
			'from_user'  => get_current_user_id(),
			'author'     => get_current_user_id()
		] );

		if ( $email_id ) {
			$step->update_meta( 'email_id', $email_id );
		}
	}


	/**
	 * Export all tag related steps
	 *
	 * @param $args array of args
	 * @param $step Step
	 *
	 * @return array of tag names
	 */
	public function export( $args, $step ) {
		$email_id = absint( $step->get_meta( 'email_id' ) );

		$email = new Email( $email_id );

		if ( ! $email->exists() ) {
			return $args;
		}

		$args['email'] = $email;

		return $args;
	}
}
