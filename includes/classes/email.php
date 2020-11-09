<?php

namespace Groundhogg;

use Composer\Repository\PackageRepository;
use Groundhogg\Api\V3\Unsubscribe_Api;
use Groundhogg\Classes\Activity;
use Groundhogg\DB\Email_Meta;
use Groundhogg\DB\Emails;
use WP_Error;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email
 *
 * Lots of helper methods... also where the actual sending of emails occurs.
 *
 * One thing to note is the template.
 *
 * You may add your own email templates by defining, email-template.php in your theme.
 * The default template is email-default.php
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */
class Email extends Base_Object_With_Meta {
	/**
	 * Whether the email is a test or not.
	 *
	 * @var bool
	 */
	public $testing = false;

	/**
	 * A contact which may or may not be need. (optional)
	 *
	 * @var Contact
	 */
	protected $contact;

	/**
	 * The event related to this email send
	 *
	 * @var Event
	 */
	protected $event;

	/**
	 * @var WP_User
	 */
	protected $from_userdata;

	/**
	 * Return the DB instance that is associated with items of this type.
	 *
	 * @return Emails
	 */
	protected function get_db() {
		return Plugin::$instance->dbs->get_db( 'emails' );
	}

	/**
	 * Return a META DB instance associated with items of this type.
	 *
	 * @return Email_Meta
	 */
	protected function get_meta_db() {
		return Plugin::$instance->dbs->get_db( 'emailmeta' );
	}

	/**
	 * Do any post setup actions.
	 *
	 * @return void
	 */
	protected function post_setup() {
		if ( $this->get_from_user_id() ) {
			$this->from_userdata = get_userdata( $this->get_from_user_id() );
		}
	}

	/**
	 * A string to represent the object type
	 *
	 * @return string
	 */
	protected function get_object_type() {
		return 'email';
	}

	public function get_id() {
		return absint( $this->ID );
	}

	public function get_subject_line() {
		return $this->subject;
	}

	public function get_title() {
		return $this->title ? $this->title : $this->get_subject_line();
	}

	public function get_pre_header() {
		return $this->pre_header;
	}

	public function get_content() {
		return $this->content;
	}

	public function get_author_id() {
		return absint( $this->author );
	}

	public function get_from_user_id() {
		return absint( $this->from_user );
	}

	public function get_from_user() {
		return $this->from_userdata;
	}

	public function get_status() {
		return $this->status;
	}

	public function get_last_updated() {
		return $this->last_updated;
	}

	public function get_date_created() {
		return $this->date_created;
	}

	/**
	 * @return Contact
	 */
	public function get_contact() {
		return $this->contact;
	}

	/**
	 * @return Event
	 */
	public function get_event() {
		return $this->event;
	}

	/**
	 * @return string
	 */
	public function get_alt_body() {
		$body = "";

		if ( $this->has_custom_alt_body() ) {
			$body = wp_strip_all_tags( $this->get_meta( 'alt_body' ), false );
		}

		if ( empty( $body ) ) {
			$body = $this->strip_html_tags( $this->get_content() );
		}

		return $body;
	}

	/**
	 * @return string
	 */
	public function get_merged_alt_body() {
		return do_replacements( $this->get_alt_body(), $this->get_contact()->get_id() );
	}

	/**
	 * @return bool
	 */
	public function is_draft() {
		return $this->get_status() === 'draft';
	}

	/**
	 * @return bool
	 */
	public function is_ready() {
		return $this->get_status() === 'ready';
	}

	/**
	 * @return bool
	 */
	public function has_custom_alt_body() {
		return boolval( $this->get_meta( 'use_custom_alt_body' ) );
	}

	/**
	 * @return bool
	 */
	public function is_template() {
		return (bool) $this->is_template;
	}

	/**
	 * @return bool
	 */
	public function is_testing() {
		return (bool) $this->testing;
	}

	/**
	 * get the template type
	 *
	 * @return string
	 */
	public function get_template() {
		return apply_filters( 'groundhogg/email/template', 'default' );
	}

	/**
	 * Turns on test mode
	 */
	public function enable_test_mode() {
		$this->testing = true;
	}

	/**
	 * Whether the current email contains a confirmation link.
	 *
	 * @return bool
	 */
	public function is_confirmation_email() {
		return ( strpos( $this->get_content(), '{confirmation_link}' ) !== false ) || ( strpos( $this->get_content(), '{confirmation_link_raw}' ) !== false );
	}

	/**
	 * Whether browser view is enabled
	 *
	 * @param $bool
	 *
	 * @return bool
	 */
	public function browser_view_enabled( $bool ) {
		return boolval( $this->get_meta( 'browser_view', true ) );
	}

	/**
	 * Return the browser view option for this email.
	 *
	 * @param $link
	 *
	 * @return string
	 */
	public function browser_view_link( $link ) {
		return managed_page_url( sprintf( "browser-view/emails/%d", $this->get_id() ) );
	}

	/**
	 * Return the tracking link for this email when opened.
	 *
	 * @return string
	 */
	public function get_open_tracking_link() {
		return managed_page_url( sprintf(
			"tracking/email/open/%s/%s/%s/",
			dechex( $this->get_contact()->get_id() ),
			! $this->is_testing() ? dechex( $this->get_event()->get_id( true ) ) : 0,
			dechex( $this->get_id() )
		) );
	}

	/**
	 * Return the tracking link for this email when a link is clicked.
	 *
	 * @return string
	 */
	public function get_click_tracking_link() {
		return managed_page_url(
			sprintf( 'tracking/email/click/%s/%s/%s/',
				dechex( $this->get_contact()->get_id() ),
				! $this->is_testing() ? dechex( $this->get_event()->get_id( true ) ) : 0,
				dechex( $this->get_id() )
			)
		);
	}

	/**
	 * @param $link
	 *
	 * @return string
	 */
	public function click_tracking_link( $link ) {
		return sprintf( "{$this->get_click_tracking_link()}/%s/", base64_encode( $link ) );
	}

	/**
	 * Add alignment CSS to the email content for outlook
	 *
	 * @param $css string the email's current css
	 *
	 * @return string
	 */
	public function get_alignment_outlook( $css ) {
		$alignment = $this->get_meta( 'alignment', true );

		return ( $alignment === 'left' ) ? '' : 'center';
	}

	/**
	 * Add alignment CSS to the email content
	 *
	 * @param $css array the email's current css
	 *
	 * @return array
	 */
	public function get_alignment( $css ) {
		$alignment = $this->get_meta( 'alignment', true );

		if ( $alignment === 'left' ) {
			$css['margin-left']  = '0';
			$css['margin-right'] = 'auto';
		} else {
			$css['margin-left']  = 'auto';
			$css['margin-right'] = 'auto';
		}

		return $css;
	}

	/**
	 * Get the email being sent to
	 *
	 * @return  string
	 */
	public function get_to_address() {
		return apply_filters( 'groundhogg/email/to', $this->get_contact()->get_email() );
	}

	/**
	 * Get the subject line for the email.
	 *
	 * @return string
	 */
	public function get_merged_subject_line() {
		$subject = do_replacements( $this->get_subject_line(), $this->get_contact()->get_id() );

		if ( $this->is_testing() ) {
			$subject = sprintf( __( '[TEST] %s' ), $subject );
		}

		return apply_filters( 'groundhogg/email/subject', $subject );
	}

	/**
	 * Return pre header text
	 * This is called by a filter rather than directly
	 *
	 * @param $content
	 *
	 * @return string
	 */
	public function get_merged_pre_header( $content ) {
		$pre_header = Plugin::$instance->replacements->process(
			$this->get_pre_header(),
			$this->get_contact()->get_id()
		);

		$pre_header = apply_filters( 'wpgh_email_pre_header', $pre_header );

		return apply_filters( 'groundhogg/email/pre_header', $pre_header );
	}

	/**
	 * Return email content
	 * This is called by a filter rather than directly
	 *
	 * @param $content
	 *
	 * @return string
	 */
	public function get_merged_content( $content = '' ) {
		$content = do_replacements(
			$this->get_content(),
			$this->get_contact()->get_id()
		);

		// Autop non blocked emails.
		if ( strpos( $content, 'data-block' ) === false ) {
			$content = wpautop( $content );
		}

		/* filter out double http based on bug where links have http:// prepended */
		$schema  = is_ssl() ? 'https://' : 'http://';
		$content = str_replace( 'http://https://', $schema, $content );
		$content = str_replace( 'http://http://', $schema, $content );

		/* Other filters */
		$content = apply_filters( 'wpgh_email_template_make_clickable', true ) ? make_clickable( $content ) : $content;
		$content = str_replace( '&#038;', '&amp;', $content );
		$content = do_shortcode( $content );
		$content = fix_nested_p( $content );

		return apply_filters( 'groundhogg/email/get_merged_content', $content );
	}

	/**
	 * Convert links to tracking links
	 *
	 * @param $content string content which may contain Superlinks
	 *
	 * @return string
	 */
	public function convert_to_tracking_links( $content ) {
		/* Filter the links to include data about the email, campaign, and funnel steps... */
		$content = preg_replace_callback( '/(href=")(?!mailto)(?!tel)([^"]*)(")/i', [
			$this,
			'tracking_link_callback'
		], $content );

		// Also get single quote HTML since that's a thing that can happen.
		return preg_replace_callback( '/(href=\')(?!mailto)(?!tel)([^"]*)(\')/i', [
			$this,
			'tracking_link_callback'
		], $content );
	}

	/**
	 * Replace the link with another link which has the ?ref UTM which will lead to the original link
	 *
	 * @param $matches
	 *
	 * @return string
	 */
	public function tracking_link_callback( $matches ) {

		$clean_url = str_replace( '&amp;', '&', $matches[2] );

		// If the url is not to be tracked leave it alone.
		if ( is_url_excluded_from_tracking( $clean_url ) ){
			return $matches[1] . $clean_url . $matches[3];
		}

		return $matches[1] . trailingslashit( $this->get_click_tracking_link() . base64_encode( $clean_url ) ) . $matches[3];
	}

	/**
	 * Return footer content
	 *
	 * @param $content
	 *
	 * @return string
	 */
	public function get_footer_text( $content ) {
		$footer = "";

		$footer .= "&copy; {business_name}<br/>";
		$footer .= "{business_address}<br/>";

		$sub = array();

		if ( Plugin::$instance->settings->get_option( 'phone', 0 ) ) {
			$sub[] = "<a href=\"tel:{business_phone}\">{business_phone}</a>";
		}

		if ( Plugin::$instance->settings->get_option( 'privacy_policy' ) ) {
			$sub[] = sprintf(
				"<a href=\"%s\">%s</a>",
				esc_url( Plugin::$instance->settings->get_option( 'privacy_policy' ) ),
				apply_filters( 'groundhogg/email/privacy_policy_link_text', __( 'Privacy Policy', 'groundhogg' ) )
			);
		}

		if ( Plugin::$instance->settings->get_option( 'terms' ) ) {
			$sub[] = sprintf(
				"<a href=\"%s\">%s</a>",
				esc_url( Plugin::$instance->settings->get_option( 'terms' ) ),
				apply_filters( 'groundhogg/email/terms_link_text', __( 'Terms', 'groundhogg' ) )
			);
		}

		$footer .= implode( ' | ', $sub );

		$footer = do_replacements( $footer, $this->get_contact()->get_id() );

		return apply_filters( 'groundhogg/email/footer', $footer );
	}

	/**
	 * Get the unsub link
	 *
	 * @param $url
	 *
	 * @return false|string
	 */
	public function get_unsubscribe_link( $url = '' ) {
		$url = managed_page_url( 'preferences/manage' );

		// only add permissions key if this is a real email being sent.
		if ( ! $this->is_testing() && ! is_user_logged_in() ){
			$url = permissions_key_url( $url, $this->get_contact(), 'preferences' );
		}

		return $url;
	}

	/**
	 * Get the unsub link
	 *
	 * @param $url
	 *
	 * @return false|string
	 */
	public function get_preferences_link( $url = '' ) {
		$url = managed_page_url( 'preferences/profile' );

		return $url;
	}

	/**
	 * Add all the filters relevant to the email content
	 */
	private function add_filters() {
		add_filter( 'groundhogg/email_template/alignment', [ $this, 'get_alignment_outlook' ] );
		add_filter( 'groundhogg/email_template/container_css', [ $this, 'get_alignment' ] );
		add_filter( 'groundhogg/email_template/show_browser_view', [ $this, 'browser_view_enabled' ] );
		add_filter( 'groundhogg/email_template/browser_view_link', [ $this, 'browser_view_link' ] );
		add_filter( 'groundhogg/email_template/pre_header_text', [ $this, 'get_merged_pre_header' ] );
		add_filter( 'groundhogg/email_template/content', [ $this, 'get_merged_content' ] );
		add_filter( 'groundhogg/email_template/footer_text', [ $this, 'get_footer_text' ] );
		add_filter( 'groundhogg/email_template/unsubscribe_link', [ $this, 'get_unsubscribe_link' ] );
		add_filter( 'groundhogg/email_template/preferences_link', [ $this, 'get_preferences_link' ] );
		add_filter( 'groundhogg/email_template/open_tracking_link', [ $this, 'get_open_tracking_link' ] );
		add_filter( 'groundhogg/email/the_content', [ $this, 'convert_to_tracking_links' ] );
		add_filter( 'groundhogg/email/the_content', [ $this, 'minify' ] );
	}


	/**
	 * Once the content is complete you will need to remove all the filters related to that specific content.
	 */
	private function remove_filters() {
		remove_filter( 'groundhogg/email_template/alignment', [ $this, 'get_alignment_outlook' ] );
		remove_filter( 'groundhogg/email_template/container_css', [ $this, 'get_alignment' ] );
		remove_filter( 'groundhogg/email_template/show_browser_view', [ $this, 'browser_view_enabled' ] );
		remove_filter( 'groundhogg/email_template/browser_view_link', [ $this, 'browser_view_link' ] );
		remove_filter( 'groundhogg/email_template/pre_header_text', [ $this, 'get_merged_pre_header' ] );
		remove_filter( 'groundhogg/email_template/content', [ $this, 'get_merged_content' ] );
		remove_filter( 'groundhogg/email_template/footer_text', [ $this, 'get_footer_text' ] );
		remove_filter( 'groundhogg/email_template/unsubscribe_link', [ $this, 'get_unsubscribe_link' ] );
		remove_filter( 'groundhogg/email_template/preferences_link', [ $this, 'get_preferences_link' ] );
		remove_filter( 'groundhogg/email_template/open_tracking_link', [ $this, 'get_open_tracking_link' ] );
		remove_filter( 'groundhogg/email/the_content', [ $this, 'convert_to_tracking_links' ] );
		remove_filter( 'groundhogg/email/the_content', [ $this, 'minify' ] );
	}

	/**
	 * Build the email
	 *
	 * @return string
	 */
	public function build() {
		$templates = new Template_Loader();

		$this->add_filters();

		ob_start();

		$template = $this->get_template();

		if ( has_action( "groundhogg/email/header/{$template}" ) ) {
			/**
			 *  Rather than loading the email from the default template, load whatever the custom template is.
			 */
			do_action( "groundhogg/email/header/{$template}", $this );

		} else {
			$templates->get_template_part( 'emails/header', $this->get_template() );
		}

		if ( has_action( "groundhogg/email/body/{$template}" ) ) {
			/**
			 *  Rather than loading the email from the default template, load whatever the custom template is.
			 */
			do_action( "groundhogg/email/body/{$template}", $this );
		} else {
			$templates->get_template_part( 'emails/body', $this->get_template() );
		}

		if ( has_action( "groundhogg/email/footer/{$template}" ) ) {
			/**
			 *  Rather than loading the email from the default template, load whatever the custom template is.
			 */
			do_action( "groundhogg/email/footer/{$template}", $this );

		} else {
			$templates->get_template_part( 'emails/footer', $this->get_template() );

		}

		$content = ob_get_clean();

		$content = apply_filters( 'groundhogg/email/the_content', $content );

		$this->remove_filters();

		return $content;
	}

	/**
	 * Return the from name for the email
	 *
	 * @return string
	 */
	public function get_from_name() {
		if ( $this->get_from_user() ) {
			return $this->get_from_user()->display_name;
		}

		if ( $this->get_contact()->get_ownerdata() ) {
			return $this->get_contact()->get_ownerdata()->display_name;
		}

		return get_default_from_name();

	}

	/**
	 * Return the from name for the email
	 *
	 * @return string
	 */
	public function get_from_email() {
		if ( $this->get_from_user() ) {
			return $this->get_from_user()->user_email;
		}

		if ( $this->get_contact()->get_ownerdata() ) {
			return $this->get_contact()->get_ownerdata()->user_email;
		}

		return get_default_from_email();
	}

	/**
	 * The reply-to address
	 *
	 * @return string
	 */
	public function get_reply_to_address() {
		return ( is_email( $this->get_meta( 'reply_to_override' ) ) ? do_replacements( $this->get_meta( 'reply_to_override' ), $this->get_contact()->get_id() ) : $this->get_from_email() );
	}

	/**
	 * Get the headers to send
	 *
	 * @return array
	 */
	public function get_headers() {
		/* Use default mail-server */
		$headers = [];

		$custom_headers = $this->get_meta( 'custom_headers' ) ?: [];

		foreach ( $custom_headers as $custom_header => $custom_header_value ) {

			if ( empty( $custom_header_value ) ) {
				continue;
			}

			$key             = strtolower( $custom_header );
			$headers[ $key ] = do_replacements( $custom_header_value, $this->get_contact() );
		}

		$list_unsub_header = sprintf(
			'<%s>,<mailto:%s?subject=Unsubscribe %s from %s>',
			add_query_arg( [
				'contact' => encrypt( $this->get_contact()->get_email() )
			], rest_url( Unsubscribe_Api::NAME_SPACE . '/unsubscribe' ) ),
			get_bloginfo( 'admin_email' ),
			$this->get_to_address(),
			get_bloginfo()
		);

		$defaults = [
			'from'                  => sprintf( '%s <%s>', wp_specialchars_decode( $this->get_from_name() ), $this->get_from_email() ),
			'reply-to'              => $this->get_reply_to_address(),
			'return-path'           => is_email( get_return_path_email() ) ? get_return_path_email() : $this->get_from_email(),
			'content-type'          => 'text/html; charset=UTF-8',
			'list-unsubscribe'      => $list_unsub_header,
			'list-unsubscribe-post' => 'List-Unsubscribe=One-Click'
		];

		// Merge the custom headers with the defaults...
		$headers = wp_parse_args( $headers, $defaults );

		// Format the headers as they would have been formatted before.
		foreach ( $headers as $header_key => &$header_value ) {
			$header_value = sprintf( "%s: %s", $header_key, $header_value );
		}

		/**
		 * Filter the headers to maybe add additional recipients...
		 *
		 * @param $headers array
		 * @param $email   Email
		 * @param $contact Contact
		 */
		return apply_filters( "groundhogg/email/headers", $headers, $this, $this->contact );
	}

	/**
	 * Set the contact
	 *
	 * @param $contact Contact|int
	 */
	public function set_contact( $contact ) {
		if ( is_numeric( $contact ) ) {
			$contact = Plugin::$instance->utils->get_contact( $contact );
		}

		$this->contact = $contact;
	}

	/**
	 * Set Event
	 *
	 * This is PROBABLY a queued event...
	 *
	 * @param $event Event|int
	 */
	public function set_event( $event ) {
		if ( ! is_object( $event ) ) {
			$event = absint( $event );
			$event = get_queued_event_by_id( $event );

			if ( ! $event ) {
				$event = new Event( 0 );
			}
		}

		$this->event = $event;
	}

	/**
	 * Check if the message is transactional
	 *
	 * @return bool
	 */
	public function is_transactional() {
		return $this->get_meta( 'message_type' ) === 'transactional';
	}

	/**
	 * Send the email
	 *
	 * @param $contact_id_or_email Contact|int|string
	 * @param $event               Event|int the of the associated event
	 *
	 * @return bool|WP_Error
	 */
	public function send( $contact_id_or_email, $event = 0 ) {
		// Clear any old previous errors.
		$this->clear_errors();

		if ( ! $this->is_ready() && ! $this->is_testing() ) {
			return new WP_Error( 'email_not_ready', sprintf( __( 'Emails cannot be sent in %s mode.', 'groundhogg' ), $this->get_status() ) );
		}

		$contact = $contact_id_or_email instanceof Contact ? $contact_id_or_email : Plugin::$instance->utils->get_contact( $contact_id_or_email );

		if ( ! $contact ) {
			return new WP_Error( 'no_recipient', __( 'No valid recipient was provided.' ) );
		}

		$this->set_contact( $contact );

		/* we got an event so all is well */
		if ( is_object( $event ) ) {
			$this->set_event( $event );
		}

		$do_not_send = [
			Preferences::COMPLAINED,
			Preferences::HARD_BOUNCE,
			Preferences::SPAM
		];

		// Ignore if testing or the message is transactional
		if ( ! $this->is_testing() && ! $this->is_transactional() && ! $contact->is_marketable() && ! in_array( $contact->get_optin_status(), $do_not_send ) ) {
			return new WP_Error( 'non_marketable', __( 'Contact is not marketable.' ) );
		}

		do_action( 'groundhogg/email/before_send', $this );

		/* Additional settings */
		add_action( 'phpmailer_init', [ $this, 'set_bounce_return_path' ] );
		add_action( 'phpmailer_init', [ $this, 'set_plaintext_body' ] );
		add_action( 'wp_mail_failed', [ $this, 'mail_failed' ] );
		add_filter( 'wp_mail_content_type', [ $this, 'send_in_html' ] );

		$to      = $this->get_to_address();
		$subject = $this->get_merged_subject_line();
		$content = $this->build();

		$headers = $this->get_headers();

		/* Send with default WP */
		$sent = $this->send_with_wp(
			$to,
			$subject,
			$content,
			$headers
		);

		remove_action( 'phpmailer_init', [ $this, 'set_bounce_return_path' ] );
		remove_action( 'phpmailer_init', [ $this, 'set_plaintext_body' ] );
		remove_action( 'wp_mail_failed', [ $this, 'mail_failed' ] );
		remove_filter( 'wp_mail_content_type', [ $this, 'send_in_html' ] );

		if ( ! $sent ) {

			do_action( 'groundhogg/email/send_failed', $this );

		} else {

			$contact->update_meta( 'last_sent', time() );

		}

		do_action( 'groundhogg/email/after_send', $this );

		if ( $this->has_errors() ) {
			return $this->get_last_error();
		}

		return $sent;
	}

	/**
	 * Send with generic WP mail
	 *
	 * @param $to
	 * @param $subject
	 * @param $content
	 * @param $headers
	 *
	 * @return bool
	 */
	private function send_with_wp( $to, $subject, $content, $headers ) {
		return wp_mail( $to, $subject, $content, $headers );
	}

	/**
	 * Log failures
	 *
	 * @param $error WP_Error
	 */
	public function mail_failed( $error ) {
		$this->add_error( $error );
	}

	/**
	 * Specify that we are sending an HTML email
	 *
	 * @return string
	 */
	public function send_in_html() {
		return 'text/html';
	}

	/**
	 * Set the return path to the bounce email in the settings
	 *
	 * @param $phpmailer \PHPMailer|GH_SS_Mailer
	 */
	public function set_bounce_return_path( $phpmailer ) {
		$return_path = Plugin::$instance->settings->get_option( 'bounce_inbox', $phpmailer->From );

		if ( is_email( $return_path ) ) {
			$phpmailer->Sender = $return_path;
		}
	}

	/**
	 * Set the plain text version of the email
	 *
	 * @param $phpmailer \PHPMailer|GH_SS_Mailer
	 */
	public function set_plaintext_body( $phpmailer ) {

		// don't run if sending plain text email already
		if ( $phpmailer->ContentType === 'text/plain' ) {
			return;
		}

		// set AltBody
		$phpmailer->AltBody = $this->get_merged_alt_body();
	}

	/**
	 * Remove HTML tags, including invisible text such as style and
	 * script code, and embedded objects.  Add line breaks around
	 * block-level tags to prevent word joining after tag removal.
	 */
	private function strip_html_tags( $text ) {
		$text = preg_replace(
			array(
				// Remove invisible content
				'@<head[^>]*?>.*?</head>@siu',
				'@<style[^>]*?>.*?</style>@siu',
				'@<script[^>]*?.*?</script>@siu',
				'@<object[^>]*?.*?</object>@siu',
				'@<embed[^>]*?.*?</embed>@siu',
				'@<noscript[^>]*?.*?</noscript>@siu',
				'@<noembed[^>]*?.*?</noembed>@siu',
				'@\t+@siu',
				'@\n+@siu'
			),
			'',
			$text );

		// replace certain steps with a line-break
		$text = preg_replace(
			array(
				'@</?((div)|(h[1-9])|(/tr)|(p)|(pre))@iu'
			),
			"\n\$0",
			$text );

		// replace other steps with a space
		$text = preg_replace(
			array(
				'@</((td)|(th))@iu'
			),
			" \$0",
			$text );

		// strip all remaining HTML tags, but not line breaks
		$text = wp_strip_all_tags( $text, false );

		// Give it back
		return $text;
	}

	/**
	 * Minify html content
	 *
	 * @param $content
	 *
	 * @return string
	 */
	public function minify( $content ) {
		$search = array(
			'/\>[^\S ]+/s',     // strip whitespaces after tags, except space
			'/[^\S ]+\</s',     // strip whitespaces before tags, except space
			'/(\s)+/s',         // shorten multiple whitespace sequences
			'/<!--(.|\s)*?-->/' // Remove HTML comments
		);

		$replace = array(
			'>',
			'<',
			'\\1',
			''
		);

		$buffer = preg_replace( $search, $replace, $content );

		return $buffer;
	}

	/**
	 * Get related email statistics
	 *
	 * @param $start
	 * @param $end
	 *
	 * @return array
	 */
	public function get_email_stats( $start, $end , $steps_ids = [] ) {


		if (empty( $steps_ids ) ) {

			$steps = get_db( 'stepmeta' )->query( [
				'meta_key'   => 'email_id',
				'meta_value' => $this->get_id()
			] );

			$steps_ids = wp_parse_id_list( wp_list_pluck( $steps, 'step_id' ) );
		}

		$count        = 0;
		$opened       = 0;
		$clicked      = 0;
		$all_clicked  = 0;
		$unsubscribed = 0;

		if ( ! empty( $steps_ids ) ) {

			$where_events = [
				'relationship' => "AND",
				[ 'col' => 'step_id', 'val' => $steps_ids, 'compare' => 'IN' ],
				[ 'col' => 'status', 'val' => 'complete', 'compare' => '=' ],
				[ 'col' => 'time', 'val' => $start, 'compare' => '>=' ],
				[ 'col' => 'time', 'val' => $end, 'compare' => '<=' ],
			];

			$count = get_db( 'events' )->count( [
				'where' => $where_events,
			] );

			$where_opened = [
				'relationship' => "AND",
				[ 'col' => 'email_id', 'val' => $this->get_id(), 'compare' => '=' ],
				[ 'col' => 'activity_type', 'val' => Activity::EMAIL_OPENED, 'compare' => '=' ],
				[ 'col' => 'timestamp', 'val' => $start, 'compare' => '>=' ],
				[ 'col' => 'timestamp', 'val' => $end, 'compare' => '<=' ],
			];

			$opened = get_db( 'activity' )->count( [
				'where' => $where_opened
			] );

			$where_clicked = [
				'relationship' => "AND",
				[ 'col' => 'email_id', 'val' => $this->get_id(), 'compare' => '=' ],
				[ 'col' => 'activity_type', 'val' => Activity::EMAIL_CLICKED, 'compare' => '=' ],
				[ 'col' => 'timestamp', 'val' => $start, 'compare' => '>=' ],
				[ 'col' => 'timestamp', 'val' => $end, 'compare' => '<=' ],
			];

			$clicked = get_db( 'activity' )->count( [
				'select' => 'DISTINCT contact_id',
				'where'  => $where_clicked
			] );

			$all_clicked = get_db( 'activity' )->count( [
				'where' => $where_clicked
			] );

			$unsubscribed = get_db( 'activity' )->count( [
				'email_id'      => $this->get_id(),
				'activity_type' => Activity::UNSUBSCRIBED,
				'before'        => $end,
				'after'         => $start,
			] );

		}

		return [
			'steps'        => $steps_ids,
			'sent'         => $count,
			'opened'       => $opened,
			'clicked'      => $clicked,
			'all_clicks'   => $all_clicked,
			'unsubscribed' => $unsubscribed,
		];
	}


}