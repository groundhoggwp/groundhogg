<?php

namespace Groundhogg;

use function Groundhogg\Notices\add_notice;
use function Groundhogg\Notices\wp_redirect_with_notice;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include __DIR__ . '/managed-page.php';

if ( ! function_exists( 'obfuscate_email' ) ):
	/**
	 * Obfuscate an email address
	 *
	 * @param $email
	 *
	 * @return string|string[]|null
	 */
	function obfuscate_email( $email ) {
		if ( ! is_email( $email ) ) {
			return false;
		}

		$parts = explode( '@', $email );

		$parts[0] = preg_replace( '/(?!^).(?=.+$)/', '*', $parts[0] );
		$parts[1] = preg_replace( '/(?!^).(?=.+\.)/', '*', $parts[1] );
		$email    = implode( '@', $parts );

		return $email;
	}

endif;

if ( ! function_exists( __NAMESPACE__ . '\mail_gdpr_data' ) ) {

	/**
	 * Mail the contact profile to the contact which requested it.
	 * Uses the regular wp_mail function.
	 *
	 * @param $contact_id
	 *
	 * @return bool
	 */
	function mail_gdpr_data( $contact_id ) {

		$contact = get_contactdata( $contact_id );

		$message = __( "You are receiving this message because you have requested an audit of your personal information. This message contains all current information about your contact profile.", 'groundhogg' );
		$message .= "\r\n";

		$contact_data = apply_filters( 'groundhogg/preferences/contact_data', $contact->get_as_array() );

		// Basic Information
		$message .= sprintf( "\r\n======== %s =========\r\n", __( 'Basic Information', 'groundhogg' ) );

		foreach ( $contact_data['data'] as $key => $contact_datum ) {
			$message .= sprintf( "%s: %s\n", key_to_words( $key ), $contact_datum );
		}

		// Custom Information
		if ( isset_not_empty( $contact_data, 'meta' ) ) {
			$message .= sprintf( "\r\n======== %s =========\r\n", __( 'Other Information', 'groundhogg' ) );
			foreach ( $contact_data['meta'] as $key => $contact_datum ) {
				$message .= sprintf( "%s: %s\n", key_to_words( $key ), $contact_datum );
			}
		}

		// Custom Information
		if ( isset_not_empty( $contact_data, 'tags' ) ) {
			$message .= sprintf( "\r\n======== %s =========\r\n", __( 'Profile Tags', 'groundhogg' ) );

			$tag_names = [];

			foreach ( $contact_data['tags'] as $tag_id ) {
				$tag_names[] = Plugin::$instance->dbs->get_db( 'tags' )->get_column_by( 'tag_name', 'tag_id', $tag_id );
			}

			$message .= sprintf( "%s\n", implode( ', ', $tag_names ) );
		}

		// Files
		if ( isset_not_empty( $contact_data, 'files' ) ) {
			$message .= sprintf( "\r\n======== %s =========\r\n", __( 'Files', 'groundhogg' ) );

			foreach ( $contact_data['files'] as $file_data ) {
				$message .= sprintf( "%s: %s\n", $file_data['file_name'], $file_data['file_url'] );
			}
		}

		$subject_line = sprintf( __( '[%s] Your personal profile audit', 'groundhogg' ), get_bloginfo( 'title' ) );

		/**
		 * Filters the GDPR audit subject line
		 */
		$subject_line = apply_filters( 'groundhogg/preferences/gdpr_audit_subject_line', $subject_line );

		/**
		 * Filters the message
		 */
		$message = apply_filters( 'groundhogg/preferences/gdpr_audit_message', $message );

		return wp_mail( $contact->get_email(), wp_specialchars_decode( $subject_line ), $message, [
			'Content-Type: text/plain'
		] );
	}
}

if ( ! function_exists( __NAMESPACE__ . '\send_email_preferences_link' ) ) {

	/**
	 * Send an email notification to the contact with a link that will allow them to access the preferences center.
	 *
	 * @return bool
	 */
	function send_email_preferences_link() {

		$email = get_post_var( 'email' );
		$email = sanitize_email( $email );

		if ( ! $email ) {
			return false;
		}

		$contact = get_contactdata( $email );

		if ( ! is_a_contact( $contact ) ) {
			return false;
		}

		$preferences_link = managed_page_url( 'preferences/manage' );
		$preferences_link = permissions_key_url( $preferences_link, $contact );
		$preferences_link = add_query_arg( 'identity', encrypt( $email ), $preferences_link );

		$message = __( 'Someone has requested to manage your email preferences:' ) . "\r\n\r\n";
		/* translators: %s: Site name. */
		$message .= sprintf( __( 'Site Name: %s', 'groundhogg' ), get_bloginfo( 'name' ) ) . "\r\n\r\n";
		$message .= __( 'If this was a mistake, just ignore this email and nothing will happen.', 'groundhogg' ) . "\r\n\r\n";
		$message .= __( 'To manage your preferences, visit the following address:', 'groundhogg' ) . "\r\n\r\n";
		$message .= $preferences_link;

		$subject = sprintf( _x( '[%s] Manage your preferences', 'subject line', 'groundhogg' ), get_bloginfo( 'name' ) );

		/**
		 * Filters the subject
		 */
		$subject = apply_filters( 'groundhogg/preferences/send_preferences_link_subject_line', $subject, $contact );

		/**
		 * Filters the emssage
		 */
		$message = apply_filters( 'groundhogg/preferences/send_preferences_link_message', $message, $contact );

		return wp_mail( $contact->get_email(), wp_specialchars_decode( $subject ), $message, [
			'Content-Type: text/plain'
		] );
	}
}

add_action( 'enqueue_managed_page_styles', function () {
	wp_enqueue_style( 'manage-preferences' );
} );

add_action( 'enqueue_managed_page_scripts', function () {
	wp_enqueue_script( 'manage-preferences' );
} );

// check for the permissions_key and set it as a cookie
if ( $permissions_key = get_url_var( 'pk' ) ) {
	set_cookie( 'gh-permissions-key', $permissions_key, HOUR_IN_SECONDS );
} else {
	$permissions_key = get_cookie( 'gh-permissions-key' );
}

if ( $permissions_key && ( $enc_identity = get_url_var( 'identity' ) ) ) {
	$identity = decrypt( $enc_identity );
	$contact  = get_contactdata( $identity );

	// If the identity passed is valid and we can validate the permissions key we're good!
	if ( is_a_contact( $contact ) && check_permissions_key( $permissions_key, $contact ) ) {
		tracking()->start_tracking( $contact );
		wp_redirect( managed_page_url( 'preferences/manage' ) );
	}
}

$contact = get_contactdata();
$action  = get_query_var( 'action', 'profile' );

if ( ! is_ignore_user_tracking_precedence_enabled() ) {
	// If the user takes precedence of the tracking cookie
	// => The current user is logged in
	// => the pk is validk
	$can_edit_preferences = is_user_logged_in() || check_permissions_key( $permissions_key, $contact );
} else {
	// if the contact takes precedence over the tracking cookie
	// => current user and contact match
	// => it's a site admin and they can edit contacts
	// => the pk is valid
	$can_edit_preferences = current_user_can( 'edit_contacts' ) || current_contact_and_logged_in_user_match() || check_permissions_key( $permissions_key, $contact );
}

$can_edit_preferences = apply_filters( 'groundhogg/can_edit_preferences', $can_edit_preferences );

// if the visitor can't change the preferences show a default message.
if ( ! $can_edit_preferences || ! is_a_contact( $contact ) ) {
	$action = 'unidentified';
}

switch ( $action ):
	case 'unidentified':

		if ( wp_verify_nonce( get_post_var( '_wpnonce' ), 'request_email_confirmation' ) ) {

			if ( send_email_preferences_link() ) {
				add_notice( 'notice_preferences_link_sent' );
			} else {
				add_notice( 'notice_general_issue_message' );
			}

		}

		managed_page_head( __( 'Manage Preferences', 'groundhogg' ), 'manage' );
		?>
        <form method="post">
			<?php wp_nonce_field( 'request_email_confirmation' ); ?>
            <h3><?php _e( 'Whoops! We were unable to confirm your identity.', 'groundhogg' ); ?></h3>
            <p><?php _e( 'This may have occurred if you clicked an expired link or your browser is blocking cookies.', 'groundhogg' ); ?></p>
            <p><?php _e( 'If you are trying to change your email preferences please enter your email address and we will send you an email with a special link.', 'groundhogg' ); ?></p>
            <p>
                <label><?php _e( 'Your Email Address', 'groundhogg' ); ?>
                    <input type="email" name="email" required>
                </label>
            </p>
            <p>
                <button type="submit" class="button"><?php _e( 'Submit' ) ?></button>
            </p>
        </form>
		<?php

		managed_page_footer();

		break;
	case 'download':

		if ( wp_verify_nonce( get_request_var( '_wpnonce' ), 'download_profile' ) ) {
			if ( mail_gdpr_data( $contact->get_id() ) ) {

				$notice = 'notice_gdpr_email_sent';

				/**
				 * After the request is made to download the profile
				 *
				 * @param $contact Contact
				 */
				do_action( 'groundhogg/preferences/download_profile', $contact );

			} else {
				$notice = 'notice_general_issue_message';
			}
		}

		wp_redirect_with_notice( managed_page_url( 'preferences/profile/' ), $notice );
		die();

	default:
	case 'profile':

		if ( wp_verify_nonce( get_request_var( '_wpnonce' ), 'update_contact_profile' ) ) {

			$email = sanitize_email( get_request_var( 'email' ) );
			if ( ! $email || $email !== $contact->get_email() ) {
				add_notice( 'notice_email_verification_required' );
			} else {
				$args = [
					'first_name' => sanitize_text_field( get_request_var( 'first_name' ) ),
					'last_name'  => sanitize_text_field( get_request_var( 'last_name' ) ),
				];

				$args = apply_filters( 'groundhogg/preferences/update_profile', $args, $contact );

				if ( $contact->update( $args ) ) {
					add_notice( 'notice_profile_updated' );
				}

				apply_filters( 'groundhogg/preferences/update_profile', $args, $contact );
			}
		}

		managed_page_head( __( 'Update Profile', 'groundhogg' ), 'profile' );

		?>
        <form action="" id="preferences" method="post">
            <p>
                <b><?php printf( __( 'Update information for <span class="contact-name">%s (%s)</span>.', 'groundhogg' ), $contact->get_full_name(), obfuscate_email( $contact->get_email() ) ) ?></b>
            </p>
            <p><?php _e( 'Use the form below to update your information to the most current.', 'groundhogg' ) ?></p>
			<?php wp_nonce_field( 'update_contact_profile' ); ?>
            <p>
                <label><?php _e( 'First Name', 'groundhogg' ); ?>
                    <input type="text" name="first_name" required>
                </label>
            </p>
            <p>
                <label><?php _e( 'Last Name', 'groundhogg' ); ?>
                    <input type="text" name="last_name" required>
                </label>
            </p>
            <p>
                <label><?php _e( 'Confirm Email Address', 'groundhogg' ); ?>
                    <input type="email" name="email" required>
                </label>
            </p>
			<?php do_action( 'groundhogg/preferences/profile_form' ); ?>
            <p>
                <input id="submit" type="submit" class="button"
                       value="<?php esc_attr_e( 'Save Changes', 'groundhogg' ); ?>">
            </p>
        </form>
        <div class="box">
            <p><?php _e( 'Click below to manage your communication preferences and determine when and how you would like to receive communication from us.', 'groundhogg' ) ?></p>
            <p>
                <a id="gotopreferences" class="button"
                   href="<?php echo esc_url( managed_page_url( 'preferences/manage/' ) ); ?>"><?php _e( 'Change Email Preferences', 'groundhogg' ) ?></a>
            </p>
        </div>
		<?php if ( Plugin::$instance->preferences->is_gdpr_enabled() ): ?>
        <div class="box">
            <p><?php _e( 'Click below to email yourself an audit of all personal information currently on file. Or if you wish for us to no longer have access to this information you can request a data erasure in accordance with your privacy rights.', 'groundhogg' ) ?></p>
            <p>
                <a id="downloadprofile" class="button"
                   href="<?php echo esc_url( wp_nonce_url( managed_page_url( 'preferences/download/' ), 'download_profile' ) ); ?>"><?php _e( 'Download Profile', 'groundhogg' ) ?></a>
                <a id="eraseprofile" class="button right"
                   href="<?php echo esc_url( wp_nonce_url( managed_page_url( 'preferences/erase/' ), 'erase_profile' ) ); ?>"><?php _e( 'Erase Profile', 'groundhogg' ) ?></a>
            </p>
        </div>
	<?php endif; ?>
		<?php do_action( 'groundhogg/preferences/profile_form/after' ); ?>
		<?php

		managed_page_footer();
		break;

	case 'manage':

		do_action( 'groundhogg/preferences/manage/before' );

		if ( wp_verify_nonce( get_request_var( '_wpnonce' ), 'manage_email_preferences' ) ) {

			$preference = get_request_var( 'preference' );
			$redirect   = false;

			switch ( $preference ) {
				case 'unsubscribe':
					$redirect = nonce_url_no_amp( managed_page_url( 'preferences/unsubscribe/' ), 'unsubscribe' );
					break;
				case 'confirm':
					$redirect = nonce_url_no_amp( managed_page_url( 'preferences/confirm/' ), - 1, 'key' );
					break;
				case 'weekly':
					$contact->change_marketing_preference( Preferences::WEEKLY );
					break;
				case 'monthly':
					$contact->change_marketing_preference( Preferences::MONTHLY );
					break;
				case 'gdpr_delete':
					$redirect = nonce_url_no_amp( managed_page_url( 'preferences/erase/' ), 'erase_profile' );
					break;
			}

			do_action( 'groundhogg/preferences/manage/preferences_updated', $contact, $preference );

			if ( $redirect ) {
				wp_redirect_with_notice( $redirect, 'notice_preferences_updated' );
				die();
			}

			wp_redirect_with_notice( managed_page_url( 'preferences/profile/' ), 'notice_preferences_updated' );
			die();

		}

		managed_page_head( __( 'Manage Preferences', 'groundhogg' ), 'manage' );

		$preferences = [
			'confirm'     => _x( 'I love this company, you can communicate with me whenever you feel like.', 'preferences', 'groundhogg' ),
			'weekly'      => _x( "It's getting a bit much. Communicate with me weekly.", 'preferences', 'groundhogg' ),
			'monthly'     => _x( 'Distance makes the heart grow fonder. Communicate with me monthly.', 'preferences', 'groundhogg' ),
			'unsubscribe' => _x( 'I no longer wish to receive any form of communication. Unsubscribe me!', 'preferences', 'groundhogg' )
		];

		if ( Plugin::$instance->preferences->is_gdpr_enabled() ) {
			$preferences['gdpr_delete'] = _x( 'Unsubscribe me and delete any personal information about me.', 'preferences', 'groundhogg' );
		}

		$preferences = apply_filters( 'manage_email_preferences_options', $preferences );

		do_action( 'groundhogg/preferences/manage/form/before' );

		?>
        <form action="" id="preferences" method="post">
            <p>
                <b><?php printf( __( 'Managing preferences for <span class="contact-name">%s (%s)</span>.', 'groundhogg' ), $contact->get_full_name(), obfuscate_email( $contact->get_email() ) ) ?></b>
            </p>
			<?php wp_nonce_field( 'manage_email_preferences' ); ?>
			<?php do_action( 'groundhogg/preferences/manage/form/inside' ); ?>
            <ul class="preferences">
				<?php foreach ( $preferences as $preference => $text ): ?>
                    <li><label><input type="radio" name="preference" value="<?php esc_attr_e( $preference ); ?>"
                                      class="preference-<?php esc_attr_e( $preference ); ?>"
                            ><?php echo $text; ?></label></li>
				<?php endforeach; ?>
            </ul>
            <p>
                <input class="button" id="submit" type="submit"
                       value="<?php esc_attr_e( 'Save Changes', 'groundhogg' ); ?>">
                <a id="gotoprofile" class="button right"
                   href="<?php echo esc_url( managed_page_url( 'preferences/profile/' ) ); ?>"><?php _e( 'Cancel' ) ?></a>
            </p>
        </form>
		<?php

		do_action( 'groundhogg/preferences/manage/form/after' );

		managed_page_footer();

		break;
	case 'unsubscribe':

		if ( ! wp_verify_nonce( get_request_var( '_wpnonce' ), 'unsubscribe' ) ) {
			wp_redirect( managed_page_url( 'preferences/manage/' ) );
			die();
		}

		$contact->unsubscribe();

		managed_page_head( __( 'Unsubscribed', 'groundhogg' ), 'unsubscribe' );

		?>
        <div class="box">
            <p>
                <b><?php printf( __( 'Your email address %s has just been unsubscribed.', 'groundhogg' ), obfuscate_email( $contact->get_email() ) ) ?></b>
            </p>
            <p><?php _e( 'Further interactions with our site may be interpreted as re-subscribing to our list and will result in further electronic communication.', 'groundhogg' ); ?></p>
            <p>
                <a id="gotosite" class="button"
                   href="<?php echo esc_url( home_url() ); ?>"><?php printf( __( 'Return to %s', 'groundhogg' ), get_bloginfo( 'title', 'display' ) ); ?></a>
            </p>
        </div>
		<?php

		managed_page_footer();

		break;
	case 'confirm':

		if ( ! wp_verify_nonce( get_request_var( 'key' ) ) ) {
			wp_redirect( managed_page_url( 'preferences/manage/' ) );
			die();
		}

		$contact->change_marketing_preference( Preferences::CONFIRMED );
		$redirect_to = esc_url_raw( sanitize_text_field( get_url_var( 'redirect_to' ) ) );
		$redirect_to = apply_filters( 'groundhogg/confirmed/redirect_to', $redirect_to, $contact );

		if ( $redirect_to ) {
			die( wp_redirect( $redirect_to ) );
		}

		managed_page_head( __( 'Confirmed', 'groundhogg' ), 'confirm' );

		?>
        <div class="box">
            <p>
                <b><?php printf( __( 'Your email address %s has just been confirmed!', 'groundhogg' ), obfuscate_email( $contact->get_email() ) ) ?></b>
            </p>
            <p><?php printf( __( 'You will now receive electronic communication from %1$s. Should you wish to change your communication preferences you may do so at any time by clicking the <b>Manage Preferences</b> link or <b>Unsubscribe</b> link in the footer of any email sent by %1$s.', 'groundhogg' ), get_bloginfo( 'title', 'display' ) ); ?></p>
            <p>
                <a id="gotosite" class="button"
                   href="<?php echo esc_url( home_url() ); ?>"><?php printf( __( 'Return to %s', 'groundhogg' ), get_bloginfo( 'title', 'display' ) ); ?></a>
            </p>
        </div>
		<?php

		managed_page_footer();

		break;

	case 'erase':

		if ( ! wp_verify_nonce( get_request_var( '_wpnonce' ), 'erase_profile' ) ) {
			wp_redirect( managed_page_url( 'preferences/profile/' ) );
			die();
		}

		/**
		 * Before the request is made to erase the profile
		 *
		 * @param $contact Contact
		 */
		do_action( 'groundhogg/preferences/erase_profile', $contact );

		$contact->delete();

		managed_page_head( __( 'Erased', 'groundhogg' ), 'erase' );

		?>
        <div class="box">
            <p><b><?php _e( 'Your data has been erased!', 'groundhogg' ); ?></b></p>
            <p><?php _e( 'Further interactions with our site may be interpreted as re-subscribing to our list and will result in further communication.', 'groundhogg' ); ?></p>
            <p>
                <a id="gotosite" class="button"
                   href="<?php echo esc_url( home_url() ); ?>"><?php printf( __( 'Return to %s', 'groundhogg' ), get_bloginfo( 'title', 'display' ) ); ?></a>
            </p>
        </div>
		<?php
		managed_page_footer();
		break;
endswitch;
