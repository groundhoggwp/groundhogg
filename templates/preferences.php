<?php

namespace Groundhogg;

use Groundhogg\Classes\Activity;
use Groundhogg\Form\Form_Fields;
use function Groundhogg\Notices\add_notice;
use function Groundhogg\Notices\redirect_with_notice;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once __DIR__ . '/managed-page.php';

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

		return implode( '@', $parts );
	}

endif;

if ( ! function_exists( __NAMESPACE__ . '\mail_gdpr_data' ) ) {

	/**
	 * Mail the contact profile to the contact which requested it.
	 * Uses the regular wp_mail function.
	 *
	 * @param $contact
	 *
	 * @return bool
	 */
	function mail_gdpr_data( $contact ) {

		$contact = get_contactdata( $contact );

		if ( ! is_a_contact( $contact ) ) {
			return false;
		}

		ob_start();

		do_action( 'groundhogg/preferences/gdpr_audit_message/before', $contact );

		?>
        <p><?php esc_html_e( 'You are receiving this message because you have requested an audit of your personal information. This message contains all current information about your contact profile.', 'groundhogg' ); ?></p>
        <h3><?php esc_html_e( 'Basic Information', 'groundhogg' );; ?></h3>
		<?php

		html()->list_table( [
			'style' => [
				'border-spacing' => '10px'
			]
		], [], [
			[ esc_html__( 'Name', 'groundhogg' ), $contact->get_full_name() ],
			[ esc_html__( 'Email', 'groundhogg' ), $contact->get_email() ],
			[ esc_html__( 'Phone', 'groundhogg' ), $contact->get_phone_number() ],
			[ esc_html__( 'Mobile', 'groundhogg' ), $contact->get_mobile_number() ],
			[ esc_html__( 'Address', 'groundhogg' ), implode( ', ', $contact->get_address() ) ],
			[ esc_html__( 'IP Address', 'groundhogg' ), $contact->get_ip_address() ],
			[ esc_html__( 'Subscribed', 'groundhogg' ), date_i18n( get_date_time_format(), date_as_int( $contact->get_date_created() ) ) ],
			[ esc_html__( 'Tags', 'groundhogg' ), implode( ', ', $contact->get_tags( true ) ) ],
		] );

		do_action( 'groundhogg/preferences/gdpr_audit_message/after_basic', $contact );

		?>
        <h3><?php esc_html_e( 'Other Information', 'groundhogg' ); ?></h3>
		<?php

		$groups = Properties::instance()->get_groups();

		foreach ( $groups as $group ):

			?><h4><?php echo esc_html( $group['name'] ) ?></h4><?php

			$properties = Properties::instance()->get_fields( $group['id'] );

			html()->list_table( [
				'style' => [
					'border-spacing' => '10px'
				]
			], [], array_map( function ( $p ) use ( $contact ) {

				return [
					$p['label'],
					display_custom_field( $p['name'], $contact, false )
				];

			}, $properties ) );

		endforeach;

		do_action( 'groundhogg/preferences/gdpr_audit_message/after_other', $contact );

		$files = $contact->get_files();

		if ( ! empty( $files ) ):

			?>
            <h3><?php esc_html_e( 'Files', 'groundhogg' ); ?></h3>
            <ul>
			<?php

			foreach ( $files as $i => $file ) {
				printf( '<li><a href="%s">%s</a></li>', esc_url( permissions_key_url( $file['url'], $contact, 'download_files' ) . '&identity=' . encrypt( $contact->get_email() ) ), esc_html( $file['name'] ) );
			}

			?></ul><?php

		endif;

		?>
        <h3><?php esc_html_e( 'Email Archive', 'groundhogg' );; ?></h3>
        <p><?php esc_html_e( 'See an archive of emails you\'ve received from us in the past.', 'groundhogg' );; ?></p>
        <p><?php html( 'a', [
				'href' => add_failsafe_tracking_params( permissions_key_url( managed_page_url( 'archive' ), $contact, 'view_archive' ), $contact )
	        ], esc_html__( 'View email archive', 'groundhogg' ) ); ?></p>
		<?php

		$contact_methods = [
			html()->e( 'a', [ 'href' => 'mailto:' . get_default_from_email() ], get_default_from_email() ),
		];

		$phone = get_option( 'gh_business_phone' );

		if ( $phone ) {
			$contact_methods[] = html()->e( 'a', [ 'href' => 'tel: ' . $phone ], esc_html( $phone ) );
		}

		?>
        <p style="margin-top: 30px"><?php echo esc_html( get_option( 'gh_business_name' ) ) ?></p>
        <p><?php
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- handled upstream
            echo implode( ' | ', $contact_methods ) ?></p>
        <p>
            <i><?php esc_html_e( 'This information is provided without any guarantee of being accurate, and is exhaustive to the best of our knowledge, with the exception of potentially sensitive information.', 'groundhogg' ); ?></i>
        </p>
        <p>
            <i><?php esc_html_e( 'If there is information that you wish to obtain that is not included in this audit, please contact us.', 'groundhogg' ); ?></i>
        </p>
		<?php

		$message = ob_get_clean();

		// Filters the message
		$message = apply_filters( 'groundhogg/preferences/gdpr_audit_message', $message );

		/* translators: 1: site or business name */
		$subject_line = sprintf( __( '[%s] Your personal profile audit', 'groundhogg' ), get_option( 'gh_business_name' ) );

		// Filters the GDPR audit subject line
		$subject_line = apply_filters( 'groundhogg/preferences/gdpr_audit_subject_line', $subject_line );

		return \Groundhogg_Email_Services::send_transactional( $contact->get_email(), wp_specialchars_decode( $subject_line ), $message, [
			'Content-Type: text/html'
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

		$preferences_url = add_failsafe_tracking_params( permissions_key_url( managed_page_url( 'preferences/manage' ), $contact ), $contact );
		$unsubscribe_url = add_failsafe_tracking_params( permissions_key_url( managed_page_url( 'preferences/unsubscribe' ), $contact ), $contact );

		$links = [
			html()->e( 'a', [ 'href' => $preferences_url ], esc_html__( 'Update my preferences', 'groundhogg' ) ),
			html()->e( 'a', [ 'href' => $unsubscribe_url, 'style' => [ 'color' => 'red' ] ], esc_html__( 'Unsubscribe', 'groundhogg' ) ),
		];

		if ( Plugin::instance()->preferences->is_gdpr_enabled() ) {
			$erase_url = add_failsafe_tracking_params( permissions_key_url( managed_page_url( 'preferences/erase' ), $contact ), $contact );
			$links[]   = html()->e( 'a', [ 'href' => $erase_url, 'style' => [ 'color' => 'red' ] ], esc_html__( 'Erase my data', 'groundhogg' ) );
		}

		ob_start();

		?>
        <p><?php /* translators: 1: site name */
			printf( esc_html__( 'Someone has requested to manage your email preferences on %s.', 'groundhogg' ), esc_html( get_bloginfo() ) ) ?></p>
        <p><?php esc_html_e( 'If you did not initiate this request, just ignore this email and nothing will happen.', 'groundhogg' ); ?></p>
        <p><?php esc_html_e( 'Use any of the following links to manage your preferences.', 'groundhogg' ); ?></p>
        <p><?php
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- handled upstream
            echo implode( ' | ', $links ) ?></p>
		<?php

		$message = ob_get_clean();

		/* translators: 1: site name */
		$subject = sprintf( _x( '[%s] Manage your preferences', 'subject line', 'groundhogg' ), get_bloginfo( 'name' ) );

		/**
		 * Filters the subject
		 */
		$subject = apply_filters( 'groundhogg/preferences/send_preferences_link_subject_line', $subject, $contact );

		/**
		 * Filters the message
		 */
		$message = apply_filters( 'groundhogg/preferences/send_preferences_link_message', $message, $contact );

		add_action( 'phpmailer_init', function ( $mailer ) use ( $message ) {
			// set AltBody
			$mailer->AltBody = $message;
		} );

		return \Groundhogg_Email_Services::send_transactional(
			$contact->get_email(),
			wp_specialchars_decode( $subject ),
			make_clickable( wpautop( $message ) ), [
			'Content-Type: text/html'
		] );
	}
}

if ( ! function_exists( __NAMESPACE__ . '\send_archive_link' ) ) {

	/**
	 * Send an email notification to the contact with a link that will allow them to access their email archive.
	 *
	 * @return bool
	 */
	function send_archive_link() {

		$contact = get_contactdata();

		if ( ! $contact ) {
			return false;
		}

		$archive_url = add_failsafe_tracking_params( permissions_key_url( managed_page_url( 'archive' ), $contact, 'view_archive' ), $contact );

		$links = [
			html()->e( 'a', [ 'href' => $archive_url ], esc_html__( 'View my email archive', 'groundhogg' ) ),
			html()->e( 'a', [ 'href' => managed_page_url( 'campaigns' ) ], esc_html__( 'View public archives', 'groundhogg' ) ),
		];

		ob_start();

		?>
        <p><?php esc_html_e( 'Click the link below to access your email archives.', 'groundhogg' );; ?></p>
        <p><?php
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- handled upstream
            echo implode( ' | ', $links ) ?></p>
		<?php

		$message = ob_get_clean();

		/* translators: 1: site name */
		$subject = sprintf( _x( '[%s] View your email archive', 'subject line', 'groundhogg' ), get_bloginfo( 'name' ) );

		/**
		 * Filters the subject
		 */
		$subject = apply_filters( 'groundhogg/preferences/send_archive_link_subject_line', $subject, $contact );

		/**
		 * Filters the message
		 */
		$message = apply_filters( 'groundhogg/preferences/send_archive_link_message', $message, $contact );

		add_action( 'phpmailer_init', function ( $mailer ) use ( $message ) {
			// set AltBody
			$mailer->AltBody = $message;
		} );

		return \Groundhogg_Email_Services::send_transactional(
			$contact->get_email(),
			wp_specialchars_decode( $subject ),
			make_clickable( wpautop( $message ) ), [
			'Content-Type: text/html'
		] );
	}
}

$contact         = get_contactdata();
$permissions_key = get_permissions_key( 'preferences', true );

$action = get_query_var( 'action', 'profile' );

if ( ! is_ignore_user_tracking_precedence_enabled() ) {
	// If the user takes precedence of the tracking cookie
	// => The current user is logged in
	// => the pk is valid
	$can_edit_preferences = is_user_logged_in() || check_permissions_key( $permissions_key, $contact, 'preferences' );
} else {
	// if the contact takes precedence over the tracking cookie
	// => current user and contact match
	// => it's a site admin and they can edit contacts
	// => the pk is valid
	$can_edit_preferences = current_user_can( 'edit_contacts' ) || current_contact_and_logged_in_user_match() || check_permissions_key( $permissions_key, $contact, 'preferences' );
}

$can_edit_preferences = apply_filters( 'groundhogg/can_edit_preferences', $can_edit_preferences );

// if the visitor can't change the preferences show a default message.
if ( ! $can_edit_preferences || ! is_a_contact( $contact ) ) {
	$action = 'unidentified';
}

if ( current_user_can( 'view_contacts' ) && ! tracking()->tracking_cookie_matches_logged_in_user() ) {
	add_notice( 'notice_admin_logged_in_testing_warning' );
}

$unsub_reasons                      = apply_filters( 'groundhogg/unsubscribe_reasons', [
	'not_subscribed'  => _x( "I don't know why I'm subscribed", 'unsubscribe reason', 'groundhogg' ),
	'not_interested'  => _x( "I'm no longer interested", 'unsubscribe reason', 'groundhogg' ),
	'irrelevant'      => _x( 'Your emails are not relevant to me', 'unsubscribe reason', 'groundhogg' ),
	'too_often'       => _x( 'You email me too often', 'unsubscribe reason', 'groundhogg' ),
	'too_complicated' => _x( 'Your emails are too complicated', 'unsubscribe reason', 'groundhogg' ),
	'repetitive'      => _x( 'Your emails are repetitive', 'unsubscribe reason', 'groundhogg' ),
	'spam'            => _x( "You're spamming me", 'unsubscribe reason', 'groundhogg' ),
	'other'           => _x( 'Other', 'unsubscribe reason', 'groundhogg' ),
] );

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
        <form class="box" method="post">
			<?php wp_nonce_field( 'request_email_confirmation' ); ?>
            <h3 class="no-margin-top"><?php esc_html_e( 'Whoops! We were unable to confirm your identity.', 'groundhogg' );; ?></h3>
            <p><?php esc_html_e( 'This may have occurred if you clicked an expired link or your browser is blocking cookies.', 'groundhogg' );; ?></p>
            <p><?php esc_html_e( 'If you are trying to change your email preferences please enter your email address and we will send you an email with a special link.', 'groundhogg' );; ?></p>
            <p>
                <label><?php esc_html_e( 'Your Email Address', 'groundhogg' );; ?>
                    <input type="email" name="email" required>
                </label>
            </p>
            <p>
                <button type="submit" class="button"><?php esc_html_e( 'Submit', 'groundhogg' ); ?></button>
            </p>
        </form>
		<?php

		managed_page_footer();

		break;
	case 'download':

		if ( wp_verify_nonce( get_request_var( '_wpnonce' ), 'download_profile' ) ) {
			if ( mail_gdpr_data( $contact ) ) {

				$notice = 'notice_gdpr_email_sent';

				/**
				 * After the request is made to download the profile
				 *
				 * @param $contact Contact
				 */
				do_action( 'groundhogg/preferences/download_profile', $contact );

			} else {

				if ( \Groundhogg_Email_Services::has_error() && current_user_can( 'manage_options' ) ) {
					$notice = 'notice_email_issue';
				} else {
					$notice = 'notice_general_issue_message';
				}

			}
		}

		redirect_with_notice( managed_page_url( 'preferences/profile/' ), $notice );
		die();

	default:
	case 'profile':

		$custom_profile_fields = get_option( 'gh_custom_profile_fields', [] );
		$custom_profile_fields_form = get_array_var( $custom_profile_fields, 0 );
		$custom_profile_fields_map  = get_array_var( $custom_profile_fields, 1 );

		if ( wp_verify_nonce( get_request_var( '_wpnonce' ), 'update_contact_profile' ) ) {

			if ( ! empty( $custom_profile_fields_form ) ) {
				$contact = generate_contact_with_map( wp_unslash( $_POST ), $custom_profile_fields_map, [
					'name' => __( 'Preferences Center', 'groundhogg' ),
				], $contact );

				if ( $contact && is_a_contact( $contact ) ) {
					add_notice( 'notice_profile_updated' );
				}
			} else {
				$email      = sanitize_email( get_request_var( 'email' ) );
				$first_name = sanitize_text_field( get_request_var( 'first_name' ) );
				$last_name  = sanitize_text_field( get_request_var( 'last_name' ) );

				$args = [
					'email'      => $email,
					'first_name' => $first_name,
					'last_name'  => $last_name,
				];

				if ( $contact->update( array_filter( $args ) ) ) {
					add_notice( 'notice_profile_updated' );
				}
			}
		}

		if ( wp_verify_nonce( get_url_var( '_wpnonce' ), 'send_archive_link' ) ) {

			if ( send_archive_link() ) {
				redirect_with_notice( managed_page_url( 'preferences' ), 'notice_preferences_link_sent' );
			} else {
				add_notice( 'notice_general_issue_message' );
			}
		}

		if ( wp_verify_nonce( get_request_var( '_wpnonce' ), 'update_gdpr_consent' ) ) {

			$processing_consent = get_post_var( 'gdpr_consent' );
			$marketing_consent  = get_post_var( 'marketing_consent' );

			if ( $processing_consent !== 'yes' ) {
				$contact->revoke_gdpr_consent();
			} else {
				$contact->set_gdpr_consent();
			}

			if ( $marketing_consent !== 'yes' ) {
				$contact->revoke_gdpr_consent( 'marketing' );
			} else {
				$contact->set_gdpr_consent( 'marketing' );
			}

			add_notice( 'notice_profile_updated' );

		}

		managed_page_head( __( 'Update Profile', 'groundhogg' ), 'profile' );

		?>
        <form class="box" action="" id="preferences" method="post">
            <h2 class="no-margin-top">
	            <?php
	            printf(
	                    /* translators: 1: contact name and email address*/
                        esc_html__( 'Update information for %s.', 'groundhogg' ),
                        '<span class="contact-name">' . esc_html( $contact->get_full_name() ) . ' (' . esc_html( $contact->get_email() ) . ')</span>'
                ) ?>
            </h2>
            <p><?php esc_html_e( 'Use the form below to update your information to the most current.', 'groundhogg' ); ?></p>
			<?php wp_nonce_field( 'update_contact_profile' ); ?>
            <div class="display-flex columns gap-20">

				<?php

				if ( ! empty( $custom_profile_fields_form ) ) :
					$form = new Form_Fields( $custom_profile_fields_form, $contact );
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated HTML
					echo $form;
				else:

					?>
                    <label for="first-name"><?php kses_e( get_default_field_label( 'first_name' ) ); ?></label>
					<?php html( html()->input( [
					'id'          => 'first-name',
					'name'        => 'first_name',
					'value'       => $contact->get_first_name(),
					'placeholder' => 'John',
				] ) ) ?>
                    <label for="last-name"><?php kses_e( get_default_field_label( 'last_name' ) ); ?></label>
					<?php html( html()->input( [
					'id'          => 'last-name',
					'name'        => 'last_name',
					'value'       => $contact->get_last_name(),
					'placeholder' => 'Doe',
				] ) ) ?>
                    <label for="email"><?php kses_e( get_default_field_label( 'email' ) ); ?></label>
					<?php html( html()->input( [
					'type'  => 'email',
					'id'    => 'email',
					'name'  => 'email',
					'value' => $contact->get_email()
				] ) );

				endif;

				if ( current_contact_and_logged_in_user_match() ):
					?>
                    <p>
                        <i><?php esc_html_e( '⚠️ Changing your email address here will not change the email address for your user account.', 'groundhogg' ) ?></i>
                    </p>
				<?php
				endif;

				?>
				<?php do_action( 'groundhogg/preferences/profile_form' ); ?>
            </div>
            <p>
                <input id="submit" type="submit" class="button"
                       value="<?php esc_attr_e( 'Save Changes', 'groundhogg' ); ?>">
            </p>
        </form>
        <!-- Manage preferences -->
        <div class="box">
            <h2 class="no-margin-top"><?php esc_html_e( 'Email Preferences', 'groundhogg' ); ?></h2>
            <p><?php esc_html_e( 'Click below to manage your communication preferences and determine when and how you would like to receive communication from us.', 'groundhogg' ); ?></p>
            <p>
                <a id="gotopreferences"
                   href="<?php echo esc_url( managed_page_url( 'preferences/manage/' ) ); ?>"><?php esc_html_e( 'Change my preferences', 'groundhogg' ); ?> &rarr;</a>
            </p>
        </div>
        <!-- Send archive link-->
        <div class="box" id="archive">
            <h2 class="no-margin-top"><?php esc_html_e( 'Email Archive', 'groundhogg' ); ?></h2>
            <p><?php esc_html_e( 'See an archive of all the emails you have received from us. Click the button below and we will send you an email with a special link to view your archive.', 'groundhogg' ); ?></p>
            <p>
                <a id="access-archive" class="button"
                   href="<?php echo esc_url( wp_nonce_url( managed_page_url( 'preferences' ), 'send_archive_link' ) ); ?>"><?php esc_html_e( 'Send me my archive link', 'groundhogg' ); ?></a>
            </p>
            <p>
                <a id="public-archive"
                   href="<?php echo esc_url( managed_page_url( 'campaigns' ) ); ?>"><?php esc_html_e( 'Browse the public archives', 'groundhogg' ); ?> &rarr;</a>
            </p>
        </div>
        <!-- GDPR -->
		<?php if ( Plugin::$instance->preferences->is_gdpr_enabled() ): ?>
        <div class="box">
            <h2 class="no-margin-top"><?php esc_html_e( 'Consent & Compliance', 'groundhogg' ); ?></h2>
            <p><?php esc_html_e( 'If you wish to update your consent please do so below. Changes to your consent will be honored instantly.', 'groundhogg' ); ?></p>
            <form class="gdpr-consent" method="post">
				<?php wp_nonce_field( 'update_gdpr_consent' );

                html( 'p', [], html()->checkbox( [
	                'label'   => get_default_field_label( 'gdpr_consent' ),
	                'name'    => 'gdpr_consent',
	                'id'      => 'gdpr_consent',
	                'class'   => 'gh-gdpr',
	                'value'   => 'yes',
	                'title'   => _x( 'I Consent', 'form_default', 'groundhogg' ),
	                'checked' => $contact->get_meta( 'gdpr_consent' ) === 'yes',
                ] ) );

                html( 'p', [], html()->checkbox( [
	                'label'   => get_default_field_label( 'marketing_consent' ),
	                'name'    => 'marketing_consent',
	                'id'      => 'marketing_consent',
	                'class'   => 'gh-gdpr',
	                'value'   => 'yes',
	                'title'   => _x( 'I Consent', 'form_default', 'groundhogg' ),
	                'checked' => $contact->get_meta( 'marketing_consent' ) === 'yes',
                ] ) )

                ?>
                <button class="button" type="submit"><?php esc_html_e( 'Update Consent', 'groundhogg' );; ?></button>
            </form>
            <p><?php esc_html_e( 'Click below to email yourself an audit of all personal information currently on file.', 'groundhogg' ); ?></p>
            <p>
                <a id="downloadprofile" class="button"
                   href="<?php echo esc_url( wp_nonce_url( managed_page_url( 'preferences/download/' ), 'download_profile' ) ); ?>"><?php esc_html_e( 'Download Profile', 'groundhogg' ); ?></a>
            </p>
            <p><?php esc_html_e( 'If you wish for us to no longer have access to your information you can request a data erasure in accordance with your privacy rights.', 'groundhogg' ); ?></p>
            <p>
                <a id="eraseprofile" class="button danger"
                   href="<?php echo esc_url( managed_page_url( 'preferences/erase/' ) ); ?>"><?php esc_html_e( 'Erase Profile', 'groundhogg' ); ?></a>
            </p>
        </div>
	<?php endif; ?>
		<?php do_action( 'groundhogg/preferences/profile_form/after' ); ?>
		<?php

		managed_page_footer();
		break;

	case 'manage':

		do_action( 'groundhogg/preferences/manage/before' );

		// Backwards compatibility
		if ( wp_verify_nonce( get_request_var( '_wpnonce' ), 'manage_email_preferences' ) ) {

			$preference = get_request_var( 'preference' );
			$redirect   = false;
			$notice     = 'notice_preferences_updated';

			switch ( $preference ) {
				case 'unsubscribe':
					$contact->unsubscribe();
					track_live_activity( Activity::UNSUBSCRIBED );
					$redirect = nonce_url_no_amp( managed_page_url( 'preferences/unsubscribed/' ), 'unsubscribe' );
					$notice   = 'notice_unsubscribed';
					break;
				case 'confirm':
					$contact->change_marketing_preference( Preferences::CONFIRMED );
					$redirect = managed_page_url( 'preferences/confirm/' );
					break;
				case 'gdpr_delete':
					$contact->unsubscribe();
					$notice   = 'notice_unsubscribed';
					$redirect = managed_page_url( 'preferences/erase/' );
					break;
			}

			do_action( 'groundhogg/preferences/manage/preferences_updated', $contact, $preference );

			if ( $redirect ) {
				redirect_with_notice( $redirect, $notice );
				die();
			}

			redirect_with_notice( managed_page_url( 'preferences/profile/' ), $notice );
			die();

		}

		// Unsubscribe form
		if ( wp_verify_nonce( get_post_var( '_wpnonce' ), 'unsubscribe' ) ) {

			$contact->unsubscribe();

			$feedback = substr( sanitize_textarea_field( get_post_var( 'feedback' ) ), 0, 100 );
			$reason   = sanitize_text_field( get_post_var( 'reason' ) );

			// Make sure the reason is an official one
			if ( ! key_exists( $reason, $unsub_reasons ) ) {
				$reason = 'other';
			}

			track_live_activity( Activity::UNSUBSCRIBED, [
				'reason'   => $reason,
				'feedback' => $feedback
			] );

			// If also erasing their data
			if ( Plugin::instance()->preferences->is_gdpr_enabled() && get_post_var( 'erase_my_data' ) ) {
				redirect_with_notice( managed_page_url( 'preferences/erase' ), 'notice_unsubscribed' );
			}

			// Show unsubscribed page
			redirect_with_notice( managed_page_url( 'preferences/unsubscribed' ), 'notice_unsubscribed' );
		}

		managed_page_head( __( 'Manage Preferences', 'groundhogg' ), 'manage' );

		// Show option to resubscribe first if the contact is unsubscribed
		if ( $contact->optin_status_is( Preferences::UNSUBSCRIBED ) ): ?>
            <div class="box re-subscribe">
                <h2 class="no-margin-top">
					<?php esc_html_e( 'Subscribe', 'groundhogg' ); ?>
                </h2>
                <p><?php
		            printf(
		                /* translators: 1: open <b>, 2: closing </b>, 3: site name */
                        esc_html__( 'You are currently %1$sunsubscribed%2$s. Would you like to receive occasional content, marketing, and promotions from %3$s?', 'groundhogg' ),
                        '<b>',
                        '</b>',
                        esc_html( get_bloginfo() )
                    ); ?></p>
                <p>
                    <a id="gotoprofile" class="button"
                       href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'preference', 'confirm', managed_page_url( 'preferences/manage' ) ), 'manage_email_preferences' ) ); ?>"><?php esc_html_e( 'Yes! Subscribe!', 'groundhogg' ); ?></a>
                </p>
            </div>
		<?php endif;

		do_action( 'groundhogg/preferences/manage/form/before' );

		if ( ! $contact->optin_status_is( Preferences::UNSUBSCRIBED ) ): ?>
            <form method="post" class="box" id="unsubscribe-form">
				<?php wp_nonce_field( 'unsubscribe' ) ?>
                <h2 class="no-margin-top">
					<?php esc_html_e( 'Unsubscribe', 'groundhogg' ); ?>
                </h2>
                <p><?php
		            printf(
		                /* translators: 1: open <b>, 2: closing </b>, 3: site name */
                        esc_html__( 'Complete this form if you no longer want to receive %1$smarketing%2$s from %3$s. You may still receive transactional information related to your account.', 'groundhogg' ),
                        '<b>',
                        '</b>',
                        esc_html( get_bloginfo() )
                    ); ?></p>
                <label for="reason"><?php esc_html_e( "Can you tell us why you're unsubscribing?", 'groundhogg' ); ?> <span class="optional"><?php esc_html_e( '(optional)', 'groundhogg' ) ?></span></label>
				<?php html( html()->dropdown( [
					'id'      => 'reason',
					'name'    => 'reason',
					'options' => $unsub_reasons
				] ) ); ?>
                <label for="feedback"><?php esc_html_e( "Do you have any feedback on how we can improve?", 'groundhogg' ); ?> <span class="optional"><?php esc_html_e( '(optional)', 'groundhogg' ) ?></span></label>
				<?php html( html()->textarea( [
					'id'        => 'feedback',
					'name'      => 'feedback',
					'rows'      => 2,
					'maxlength' => 100
				] ) );

				if ( Plugin::instance()->preferences->is_gdpr_enabled() ):

					html( html()->checkbox( [
						'label' => esc_html__( 'I would like my data to be erased in accordance with my data privacy rights.', 'groundhogg' ) . ' ' . html()->e( 'span', [ 'class' => 'optional' ], esc_html__( '(optional)', 'groundhogg' ) ),
						'name'  => 'erase_my_data',
						'id'    => 'erase-data',
					] ) );

				endif; ?>
                <p>
                    <button class="button danger" type="submit"><?php esc_html_e( 'Unsubscribe', 'groundhogg' ); ?></button>
                </p>
                <p>
                    <a id="gotoprofile"
                       href="<?php echo esc_url( home_url() ); ?>">&larr; <?php esc_html_e( 'Never mind, I want to stay subscribed!', 'groundhogg' ); ?></a>
                </p>
            </form>
		<?php endif;

		do_action( 'groundhogg/preferences/manage/form/after' );

		managed_page_footer();

		break;
	case 'unsubscribed': // this is when they are coming from the /preferences/manage/ page

		// Contact is not unsubscribed, they can't see this page
		if ( ! $contact->optin_status_is( Preferences::UNSUBSCRIBED ) ) {
			wp_safe_redirect( managed_page_url( 'preferences/manage' ) );
            exit;
		}

		managed_page_head( __( 'Unsubscribed', 'groundhogg' ), 'unsubscribe' );

		?>
        <div class="box">
            <p>
                <b><?php /* translators: 1: the contact's email address */
		            printf( esc_html__( 'Your email address %s has just been unsubscribed.', 'groundhogg' ), esc_html( $contact->get_email() ) ) ?></b>
            </p>
            <p><?php esc_html_e( 'Further interactions with our site may be interpreted as re-subscribing to our list and will result in further electronic communication.', 'groundhogg' ); ?></p>
            <a href="<?php echo esc_url( wp_nonce_url( managed_page_url( 'preferences/manage/' ) . '?preference=confirm', 'manage_email_preferences' ) ); ?>"
               class="button preference-confirm"><?php kses_e( __( "Oops, I didn't mean to unsubscribe! <b>Re-subscribe me!</b>", 'groundhogg' ) ); ?></a>
        </div>
		<?php

		managed_page_footer();

		break;
	case 'unsubscribe': // this is when the one-click unsubscribe feature is being used

		// Contact is not already unsubscribed, unsubscribe them
		if ( ! $contact->optin_status_is( Preferences::UNSUBSCRIBED ) ) {

			$contact->unsubscribe();

			track_live_activity( Activity::UNSUBSCRIBED, [
				'reason' => 'one_click'
			] );

			add_notice( 'notice_unsubscribed' );
		}

		// Survey form
		if ( wp_verify_nonce( get_post_var( '_wpnonce' ), 'unsub_survey' ) ) {

			$feedback = substr( sanitize_textarea_field( get_post_var( 'feedback' ) ), 0, 100 );
			$reason   = sanitize_text_field( get_post_var( 'reason' ) );

			// Make sure the reason is an official one
			if ( ! key_exists( $reason, $unsub_reasons ) ) {
				$reason = 'other';
			}

			// This will get the most recent unsubscribed activity, which should be the one we just created
			$activity = new Activity( [
				'activity_type' => Activity::UNSUBSCRIBED,
				'contact_id'    => $contact->ID,
			] );

			// Update the reason and feedback
			$activity->update_meta( [
				'reason'   => $reason,
				'feedback' => $feedback
			] );

			// Show unsubscribed page
			redirect_with_notice( managed_page_url( 'preferences/unsubscribed' ), 'notice_unsubscribed' );
		}

		managed_page_head( __( 'Unsubscribed', 'groundhogg' ), 'unsubscribe' );

		?>
        <div class="box">
            <p>
                <b><?php /* translators: 1: the contact's email address */
		            printf( esc_html__( 'Your email address %s has just been unsubscribed.', 'groundhogg' ), esc_html( $contact->get_email() ) ) ?></b>
            </p>
            <p><?php esc_html_e( 'Further interactions with our site may be interpreted as re-subscribing to our list and will result in further electronic communication.', 'groundhogg' ); ?></p>

            <form method="post" id="unsubscribe-survey">
				<?php wp_nonce_field( 'unsub_survey' ) ?>
                <p><?php esc_html_e( 'Will you take a moment to help us send better email?', 'groundhogg' ); ?></p>
                <label for="reason"><?php esc_html_e( "Can you tell us why you unsubscribed?", 'groundhogg' ); ?></label>
				<?php html( html()->dropdown( [
					'id'      => 'reason',
					'name'    => 'reason',
					'options' => $unsub_reasons
				] ) ) ?>
                <label for="feedback"><?php esc_html_e( "Do you have any feedback on how we can improve?", 'groundhogg' ); ?></label>
				<?php html( html()->textarea( [
					'id'        => 'feedback',
					'name'      => 'feedback',
					'rows'      => 2,
					'maxlength' => 100
				] ) ); ?>
                <button class="button" type="submit"><?php esc_html_e( 'Submit', 'groundhogg' ); ?></button>
            </form>
            <p>
                <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'preference', 'confirm', managed_page_url( 'preferences/manage' ) ), 'manage_email_preferences' ) ); ?>"><?php esc_html_e( "Didn't mean to unsubscribe? Re-subscribe now!", 'groundhogg' ); ?></a>
            </p>
        </div>
		<?php

		managed_page_footer();

		break;
	case 'confirm':

		$contact->change_marketing_preference( Preferences::CONFIRMED );
		$redirect_to = esc_url_raw( sanitize_text_field( get_url_var( 'redirect_to' ) ) );
		$redirect_to = apply_filters( 'groundhogg/confirmed/redirect_to', $redirect_to, $contact );

		if ( $redirect_to ) {
            // phpcs:ignore WordPress.Security.SafeRedirect -- user defined redirect
			wp_redirect( $redirect_to );
            exit;
		}

		managed_page_head( __( 'Confirmed', 'groundhogg' ), 'confirm' );

		?>
        <div class="box">
            <p>
                <b><?php /* translators: 1: the contact's email address */
		            printf( esc_html__( 'Your email address %s has just been confirmed!', 'groundhogg' ), esc_html( $contact->get_email() ) ) ?></b>
            </p>
            <p><?php /* translators: 1: site title/name */
		        kses_e( __( 'You will now receive electronic communication from %1$s. Should you wish to change your communication preferences you may do so at any time by clicking the <b>Manage Preferences</b> link or <b>Unsubscribe</b> link in the footer of any email sent by %1$s.', 'groundhogg' ), esc_html( get_bloginfo( 'title', 'display' ) ) ); ?></p>
            <p>
                <a id="gotosite" class="button"
                   href="<?php echo esc_url( home_url() ); ?>"><?php
                    /* translators: 1: site title/name */
	                printf( esc_html__( 'Return to %s', 'groundhogg' ), esc_html( get_bloginfo( 'title', 'display' ) ) ); ?></a>
            </p>
        </div>
		<?php

		managed_page_footer();

		break;

	case 'erase':

		if ( ! wp_verify_nonce( get_request_var( '_wpnonce' ), 'erase_profile' ) ) {

			managed_page_head( esc_html__( 'Erase your profile', 'groundhogg' ), 'erase' );

			?>
            <div class="box">
                <h2 class="no-margin-top"><?php esc_html_e( 'Are you sure you want to erase your profile?', 'groundhogg' ); ?></h2>
                <p><?php esc_html_e( "Erasing your profile will mean that you will no longer receive critical updates and communications from us.", 'groundhogg' ); ?></p>
                <p><b><?php esc_html_e( "What will be erased?", 'groundhogg' ); ?></b></p>
                <ul>
                    <li><?php esc_html_e( "Your profile details.", 'groundhogg' ); ?></li>
                    <li><?php esc_html_e( "Your profile marketing history.", 'groundhogg' ); ?></li>
                    <li><?php esc_html_e( "Tracking data associated with your profile.", 'groundhogg' ); ?></li>
                </ul>
                <p><b><?php kses_e( __( "What will be <u>NOT</u> erased?", 'groundhogg' ) ); ?></b></p>
                <ul>
					<?php if ( $contact->get_user_id() ): ?>
                        <li><?php kses_e( __( "Your user account. <i>To erase your user account contact us.</i>", 'groundhogg' ) ); ?></li>
                        <li><?php esc_html_e( "Tracking data and historic details associated with your user account.", 'groundhogg' ); ?></li>
					<?php endif; ?>
                    <li><?php esc_html_e( "Associated purchase history and orders.", 'groundhogg' ); ?></li>
                    <li><?php esc_html_e( "Legal documents associated with your profile.", 'groundhogg' ); ?></li>
                </ul>
                <p>
                    <a id="eraseprofile" class="button danger"
                       href="<?php echo esc_url( wp_nonce_url( managed_page_url( 'preferences/erase/' ), 'erase_profile' ) ); ?>"><?php esc_html_e( 'Erase Profile', 'groundhogg' ); ?></a>
                </p>
                <p>
                    <a id="gotoprofile"
                       href="<?php echo esc_url( managed_page_url( 'preferences/profile/' ) ); ?>">&larr; <?php esc_html_e( "Never mind! Don't erase my profile.", 'groundhogg' ); ?></a>
                </p>
            </div>
			<?php
			managed_page_footer();

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
            <p><b><?php esc_html_e( 'Your profile has been erased!', 'groundhogg' ); ?></b></p>
            <p><?php esc_html_e( 'Further interactions with our site may be interpreted as re-subscribing to our list and will result in further communication.', 'groundhogg' ); ?></p>
            <p>
                <a id="gotosite" class="button"
                   href="<?php echo esc_url( home_url() ); ?>"><?php
                    /* translators: 1: site title/name */
	                printf( esc_html__( 'Return to %s', 'groundhogg' ), esc_html( get_bloginfo( 'title', 'display' ) ) );
                    ?></a>
            </p>
        </div>
		<?php
		managed_page_footer();
		break;
endswitch;
