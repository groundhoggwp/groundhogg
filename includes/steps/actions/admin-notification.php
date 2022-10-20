<?php

namespace Groundhogg\Steps\Actions;

use Groundhogg\Contact;
use function Groundhogg\do_replacements;
use Groundhogg\Event;
use function Groundhogg\email_kses;
use function Groundhogg\get_default_from_email;
use function Groundhogg\get_default_from_name;
use Groundhogg\Step;
use function Groundhogg\get_owners;
use function Groundhogg\html;
use function Groundhogg\is_replacement_code_format;

/**
 * Admin Notification
 *
 * Registers the admin notification step in the funnel builder.
 * USes WP_MAIL to send all notifications
 *
 * @since       File available since Release 0.9
 * @subpackage  Elements/Actions
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Elements
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin_Notification extends Action {

	/**
	 * @return string
	 */
	public function get_help_article() {
		return 'https://docs.groundhogg.io/docs/builder/actions/admin-notification/';
	}

	/**
	 * An error if something goes wrong while sending the notification.
	 *
	 * @var \WP_Error
	 */
	private $mail_error;

	/**
	 * Get the element name
	 *
	 * @return string
	 */
	public function get_name() {
		return _x( 'Admin Notification', 'step_name', 'groundhogg' );
	}

	/**
	 * Get the element type
	 *
	 * @return string
	 */
	public function get_type() {
		return 'admin_notification';
	}

	/**
	 * Get the description
	 *
	 * @return string
	 */
	public function get_description() {
		return _x( 'Send an email or SMS notification to any email or list of emails.', 'step_description', 'groundhogg' );
	}

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	public function get_icon() {
//		return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/notification.svg';
//		return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/admin-notification.png';
		return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/admin-notification.svg';
	}

	/**
	 * @param $step Step
	 */
	public function settings( $step ) {

		?>
        <p></p>
        <div class="gh-rows-and-columns">
            <div class="gh-row">
                <div class="gh-col">
                    <label><?php _e( 'Send to...' ); ?></label>
                    <div class="">
						<?php

						$selected = $this->get_setting( 'send_to' );

						if ( empty( $selected ) ) {
							$selected = [ '{owner_email}' ];
						}

						$options = [
							'{owner_email}' => __( 'Contact Owner' ),
							'{email}'       => __( 'Contact' ),
						];

						foreach ( get_owners() as $owner ) {
							$options[ $owner->user_email ] = $owner->user_email;
						}

						$other_emails = array_diff( $selected, array_keys( $options ) );

						foreach ( $other_emails as $email ) {
							$options[ $email ] = $email;
						}

						echo html()->select2( [
							'id'       => $this->setting_id_prefix( 'send_to' ),
							'name'     => $this->setting_name_prefix( 'send_to' ) . '[]',
							'options'  => $options,
							'selected' => $selected,
							'multiple' => true,
							'tags'     => true,
						] );

						?>
                    </div>
                </div>
                <div class="gh-col">
                    <label><?php _e( 'Reply to...' ) ?></label>
                    <div class="gh-input-group">
						<?php

						$reply_to_type = $this->get_setting( 'reply_to_type', $this->get_setting( 'reply_to' ) ? 'custom' : 'owner' );

						echo html()->dropdown( [
							'name'        => $this->setting_name_prefix( 'reply_to_type' ),
							'options'     => [
								'contact' => __( 'Contact\'s email' ),
								'owner'   => __( 'Contact owner\'s email' ),
								'custom'  => __( 'Custom email' ),
							],
							'selected'    => $reply_to_type,
							'option_none' => false,
							'class'       => 'reply-to-type full-width',
						] );

						$classes = [
							'custom-email',
							'full-width'
						];

						if ( $reply_to_type !== 'custom' ) {
							$classes[] = 'hidden';
						}

						echo html()->input( [
							'name'  => $this->setting_name_prefix( 'reply_to' ),
							'value' => $this->get_setting( 'reply_to' ),
							'class' => implode( ' ', $classes )
						] )

						?>
                    </div>
                </div>
            </div>
            <div class="gh-row">
                <div class="gh-col">
                    <label><?php _e( 'Subject line' ) ?></label>
					<?php

					echo html()->input( [
						'name'  => $this->setting_name_prefix( 'subject' ),
						'value' => $this->get_setting( 'subject' ),
						'class' => 'full-width'
					] );

					?>
                </div>
            </div>
            <div class="gh-row">
                <div class="gh-col">
					<?php

					echo html()->textarea( [
						'id'    => $this->setting_id_prefix( 'note_text' ),
						'name'  => 'note_text',
						'value' => wpautop( $this->get_setting( 'note_text' ) )
					] );

					?>
                </div>
            </div>
            <div class="gh-row">
                <div class="gh-col">
					<?php

					echo html()->checkbox( [
						'label'   => __( 'Don\'t show admin links to the contact record in the notification.' ),
						'name'    => $this->setting_name_prefix( 'hide_admin_links' ),
						'checked' => $this->get_setting( 'hide_admin_links' )
					] );

					?>
                </div>
            </div>
        </div>
		<?php

	}

	public function validate_settings( Step $step ) {
		$send_to = $this->get_setting( 'send_to' );

		if ( is_string( $send_to ) ) {
			$send_to = explode( ',', $send_to );
			$send_to = array_map( 'trim', $send_to );
			$this->save_setting( 'send_to', $send_to );
		}
	}

	/**
	 * Save the step settings
	 *
	 * @param $step Step
	 */
	public function save( $step ) {

		$send_to = $this->get_posted_data( 'send_to', [] );

		if ( $send_to ) {

			$send_to = array_map( function ( $email ) {
				if ( is_replacement_code_format( $email ) ) {
					return $email;
				}

				return sanitize_email( $email );
			}, $send_to );

			$this->save_setting( 'send_to', array_filter( $send_to ) );
		}

		$reply_to_type = $this->get_posted_data( 'reply_to_type' );
		$reply_to      = $this->get_posted_data( 'reply_to' );

		if ( $reply_to ) {
			if ( is_replacement_code_format( $reply_to ) || is_email( $reply_to ) ) {
				$this->save_setting( 'reply_to_type', $reply_to );
			} else {
				$this->save_setting( 'reply_to_type', '' );
			}
		}

		$this->save_setting( 'reply_to_type', sanitize_text_field( $reply_to_type ) );
		$this->save_setting( 'hide_admin_links', boolval( $this->get_posted_data( 'hide_admin_links' ) ) );
		$this->save_setting( 'subject', sanitize_text_field( $this->get_posted_data( 'subject' ) ) );
	}

	/**
	 * Process the apply note step...
	 *
	 * @param $contact Contact
	 * @param $event   Event
	 *
	 * @return bool|\WP_Error
	 */
	public function run( $contact, $event ) {

		$note = $this->get_setting( 'note_text' );
		$body = email_kses( make_clickable( wpautop( do_replacements( $note, $contact ) ) ) );

		$hide_admin_links = $this->get_setting( 'hide_admin_links', false );

		if ( ! $hide_admin_links ) {
			$body .= sprintf( "<p><a href='%s'>%s</a></p>",
				$contact->admin_link(),
				__( 'Manage Contact', 'groundhogg' )
			);
		}

		$subject = $this->get_setting( 'subject' );
		$subject = sanitize_text_field( do_replacements( $subject, $contact ) );

		$send_to = $this->get_setting( 'send_to' );

		if ( ! is_array( $send_to ) ) {
			$send_to = do_replacements( $send_to, $contact );
			$send_to = explode( ',', $send_to );
		} else {
			$send_to = array_map( function ( $email ) use ( $contact ) {
				return sanitize_email( do_replacements( $email, $contact ) );
			}, $send_to );
		}

		$send_to       = array_filter( array_map( 'trim', $send_to ), 'is_email' );
		$reply_to_type = $this->get_setting( 'reply_to_type', 'custom' );
		$reply_to      = do_replacements( $this->get_setting( 'reply_to', $contact->get_email() ), $contact );

		// No recipients defined, skip
		if ( empty( $send_to ) ) {
			return false;
		}

		switch ( $reply_to_type ) {
			case 'contact':
				$reply_to = $contact->get_email();
				break;
			case 'owner':
				$reply_to = $contact->get_ownerdata()->user_email;
				break;
		}

		add_action( 'wp_mail_failed', [ $this, 'mail_failed' ] );

		$headers = [
			sprintf( 'From: %s <%s>', get_default_from_name(), get_default_from_email() ),
			"Content-Type: text/html"
		];

		if ( is_email( $reply_to ) ) {
			$headers[] = sprintf( 'Reply-To: %s', $reply_to );
		}

		$sent = \Groundhogg_Email_Services::send_transactional(
			$send_to,
			wp_specialchars_decode( $subject ),
			$body,
			$headers
		);

		remove_action( 'wp_mail_failed', [ $this, 'mail_failed' ] );

		if ( $this->has_errors() ) {
			return $this->get_last_error();
		}

		return $sent;

	}

	/**
	 * Map the error to the whatever
	 *
	 * @param $result
	 */
	public function mail_failed( $result ) {
		$this->add_error( $result );
	}
}
