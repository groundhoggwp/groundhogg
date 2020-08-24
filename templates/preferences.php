<?php

namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include GROUNDHOGG_PATH . 'templates/managed-page.php';

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

if ( ! function_exists( 'mail_gdpr_data' ) ) {

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

		$message = __( "You are receiving this message because you have requested an audit of your personal information. This message contains all current information about your contact profile.\n", 'groundhogg' );

		$contact_data = apply_filters( 'groundhogg/preferences/contact_data', $contact->get_as_array() );

		// Basic Information
		$message .= sprintf( "\n======== %s =========\n", __( 'Basic Information', 'groundhogg' ) );

		foreach ( $contact_data['data'] as $key => $contact_datum ) {
			$message .= sprintf( "%s: %s\n", key_to_words( $key ), $contact_datum );
		}

		// Custom Information
		if ( isset_not_empty( $contact_data, 'meta' ) ) {
			$message .= sprintf( "\n======== %s =========\n", __( 'Other Information', 'groundhogg' ) );
			foreach ( $contact_data['meta'] as $key => $contact_datum ) {
				$message .= sprintf( "%s: %s\n", key_to_words( $key ), $contact_datum );
			}
		}

		// Custom Information
		if ( isset_not_empty( $contact_data, 'tags' ) ) {
			$message .= sprintf( "\n======== %s =========\n", __( 'Profile Tags', 'groundhogg' ) );

			$tag_names = [];

			foreach ( $contact_data['tags'] as $tag_id ) {
				$tag_names[] = Plugin::$instance->dbs->get_db( 'tags' )->get_column_by( 'tag_name', 'tag_id', $tag_id );
			}

			$message .= sprintf( "%s\n", implode( ', ', $tag_names ) );
		}

		// Files
		if ( isset_not_empty( $contact_data, 'files' ) ) {
			$message .= sprintf( "\n======== %s =========\n", __( 'Files', 'groundhogg' ) );

			foreach ( $contact_data['files'] as $file_data ) {
				$message .= sprintf( "%s: %s\n", $file_data['file_name'], $file_data['file_url'] );
			}
		}

		return wp_mail( $contact->get_email(), sprintf( __( 'Your personal profile audit with %s', 'groundhogg' ), get_bloginfo( 'title' ) ), esc_html( $message ) );
	}

}

add_action( 'enqueue_managed_page_styles', function () {
	wp_enqueue_style( 'manage-preferences' );
} );

add_action( 'enqueue_managed_page_scripts', function () {
	wp_enqueue_script( 'manage-preferences' );
} );

$contact = get_contactdata();
$action  = get_query_var( 'action', 'profile' );

// Compat for erase action which will be true because there will be no contact.
if ( ! $contact && $action !== 'erase' ) {
	$action = 'no_email';
}

switch ( $action ):

	default:
	case 'no_email':

		if ( wp_verify_nonce( get_request_var( '_wpnonce' ), 'identify_yourself' ) ) {

			$email = sanitize_email( get_request_var( 'email' ) );

			if ( is_email( $email ) ) {
				$contact = get_contactdata( $email );

				if ( $contact ) {
					// Start tracking this contact
					after_form_submit_handler( $contact );
					die( wp_redirect( managed_page_url( 'preferences/profile/' ) ) );
				}
			}
		}

		managed_page_head( __( 'Manage Preferences', 'groundhogg' ), 'manage' );

		?>
        <form action="" id="emailaddress" method="post">
			<?php wp_nonce_field( 'identify_yourself' ); ?>
            <p><?php _e( 'Please enter your email address to manage your preferences.', 'groundhogg' ); ?></p>
            <p><input type="email" name="email" id="email"
                      placeholder="<?php esc_attr_e( "your.name@domain.com", 'groundhogg' ); ?>" required></p>
            <p>
                <input id="submit" type="submit" class="button" value="<?php esc_attr_e( 'Submit', 'groundhogg' ); ?>">
            </p>
        </form>
		<?php

		managed_page_footer();

		break;
	case 'download':

		if ( wp_verify_nonce( get_request_var( '_wpnonce' ), 'download_profile' ) ) {
			if ( mail_gdpr_data( $contact->get_id() ) ) {
				Plugin::$instance->notices->add( 'sent', sprintf( __( 'Profile information sent to your inbox %s!', 'groundhogg' ), obfuscate_email( $contact->get_email() ) ) );

				/**
				 * After the request is made to download the profile
				 *
				 * @param $contact Contact
				 */
				do_action( 'groundhogg/preferences/download_profile', $contact );

			} else {
				Plugin::$instance->notices->add( new \WP_Error( 'failed', __( 'Something went wrong sending your email.', 'groundhogg' ) ) );
			}

		}

		wp_redirect( managed_page_url( 'preferences/profile/' ) );
		die();

	case 'profile':

		if ( wp_verify_nonce( get_request_var( '_wpnonce' ), 'update_contact_profile' ) ) {

			$email = sanitize_email( get_request_var( 'email' ) );
			if ( ! $email || $email !== $contact->get_email() ) {
				Plugin::$instance->notices->add( new \WP_Error( 'bad_email', __( 'You must verify your email address.', 'groundhogg' ) ) );

			} else {
				$args = [
					'first_name' => sanitize_text_field( get_request_var( 'first_name' ) ),
					'last_name'  => sanitize_text_field( get_request_var( 'last_name' ) ),
//                'email' => sanitize_email( get_request_var( 'email' ) ) ,
				];

				$args = apply_filters( 'groundhogg/preferences/update_profile', $args, $contact );

				if ( $contact->update( $args ) ) {
					Plugin::$instance->notices->add( 'updated', __( 'Profile updated!', 'groundhogg' ) );
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

			if ( ! Plugin::$instance->preferences->current_contact_can_modify_preferences() ) {
				notices()->add( 'error', __( "You can't do that now.", 'groundhogg' ) );
				wp_redirect( managed_page_url( 'preferences/profile/' ) );
				die();
			}

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
				wp_redirect( $redirect );
				die();
			}

			Plugin::$instance->notices->add( 'updated', __( 'Preferences saved!', 'groundhogg' ) );

			wp_redirect( managed_page_url( 'preferences/profile/' ) );
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
		} else if ( ! Plugin::$instance->preferences->current_contact_can_modify_preferences() ) {
			notices()->add( 'error', __( "You can't do that now.", 'groundhogg' ) );
			wp_redirect( managed_page_url( 'preferences/profile/' ) );
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
		} else if ( ! Plugin::$instance->preferences->current_contact_can_modify_preferences() ) {
			notices()->add( 'error', __( "You can't do that now.", 'groundhogg' ) );
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
