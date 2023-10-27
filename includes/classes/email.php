<?php

namespace Groundhogg;

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
 * @since       File available since Release 0.1
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
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
	 * Set the from_select and from_type properties
	 *
	 * @return void
	 */
	protected function set_from_select() {
		$this->from_select = $this->from_user > 0 ? $this->from_user : ( $this->get_meta( 'use_default_from' ) ? 'default' : 0 );
		$this->from_type   = $this->from_user > 0 ? 'user' : ( $this->get_meta( 'use_default_from' ) ? 'default' : 'owner' );
	}

	/**
	 * Do any post setup actions.
	 *
	 * @return void
	 */
	protected function post_setup() {

		$this->ID        = absint( $this->ID );
		$this->from_user = absint( $this->from_user );

		if ( $this->from_user > 0 ) {
			$this->from_userdata = get_userdata( $this->get_from_user_id() );
		}

		$this->set_from_select();

		// Maybe update from the meta message type
		if ( ! isset_not_empty( $this->data, 'message_type' ) ) {
			$message_type = $this->get_meta( 'message_type' );
			$this->update( [
				'message_type' => $message_type ?: 'marketing'
			] );
		}
	}

	/**
	 * Sets data for preview reasons
	 *
	 * @param $data
	 * @param $meta
	 *
	 * @return void
	 */
	public function set_preview_data( $data, $meta ) {
		$this->data = $data;
		$this->meta = $meta;

		$this->post_setup();
		$this->enable_test_mode();
	}

	/**
	 * A string to represent the object type
	 *
	 * @return string
	 */
	protected function get_object_type() {
		return 'email';
	}

	public function get_subject_line() {
		return $this->subject;
	}

	public function get_title() {
		return $this->title ?: $this->get_subject_line();
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
		return $this->from_user;
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
	 * If a custom alt body is currently enabled
	 *
	 * @return bool
	 */
	public function using_custom_alt_body() {
		return boolval( $this->get_meta( 'use_custom_alt_body' ) );
	}

	/**
	 *
	 * @deprecated
	 * @return bool
	 */
	public function has_custom_alt_body() {
		return $this->using_custom_alt_body();
	}

	/**
	 * fetch the custom alt body
	 *
	 * @return array|mixed
	 */
	public function get_custom_alt_body() {
		return $this->get_meta( 'alt_body' );
	}

	/**
	 * Fetch the alt body based on the email settings
	 *
	 * @return string
	 */
	public function get_alt_body() {

		$plain_text = $this->plain_text ?: $this->get_meta( 'plain_text' );

		if ( $plain_text ) {
			return $plain_text;
		}

		$body = "";

		if ( $this->using_custom_alt_body() ) {
			$body = $this->get_custom_alt_body();
		}

		// Default to content
		if ( empty( $body ) ) {
			$body = $this->get_content();
		}

		// Strip HTML we don't like
		return $this->strip_html_tags( $body );
	}

	/**
	 * Get the merged alt body
	 *
	 * @return string
	 */
	public function get_merged_alt_body() {

		the_email( $this );

		// Get the plain text version
		$content = $this->get_alt_body();

		// Block editor replacements
		if ( $this->is_block_editor() ) {
			$content = $this->maybe_hide_blocks( $content, 'plain' );
			$content = Block_Registry::instance()->replace_dynamic_content( $content, 'plain' );
		}

		// Do plain text replacements
		$content = do_replacements_plain_text( $content, $this->get_contact() );

		// Unsub link that may be in the footer
		$content = str_replace( '#unsubscribe_link#', $this->get_unsubscribe_link(), $content );

		// Fix markdown line breaks
		$content = preg_replace( '/(?<=[^\\S])\h\\n/', "  \n", $content );

		// Re-strip
		return $this->strip_html_tags( $content );
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
	 * If the email has the posts replacement code
	 *
	 * @return bool
	 */
	public function has_columns() {
		return preg_match( '/\{posts|post-card|<!-- posts:|email-columns/', $this->content );
	}

	/**
	 * get the template type
	 *
	 * @return string
	 */
	public function get_template() {
		return apply_filters( 'groundhogg/email/template', $this->get_meta( 'template' ) ?: 'boxed' );
	}

	/**
	 * Turns on test mode
	 */
	public function enable_test_mode() {
		$this->testing = true;
		$this->set_event( new Event() );
	}

	/**
	 * Whether the current email contains a confirmation link.
	 *
	 * @return bool
	 */
	public function is_confirmation_email() {
		return strpos( $this->get_content(), 'confirmation_link' ) !== false;
	}

	/**
	 * Check if the message is transactional
	 *
	 * @return bool
	 */
	public function is_transactional() {
		return $this->get_message_type() === 'transactional';
	}

	/**
	 * If the message is marketing related
	 *
	 * @return bool
	 */
	public function is_marketing() {
		return ! $this->is_transactional();
	}

	/**
	 * Get the message_type
	 *
	 * @return bool|mixed
	 */
	public function get_message_type() {
		return $this->message_type;
	}


	/**
	 * Whether browser view is enabled
	 *
	 * @param $bool
	 *
	 * @return bool
	 */
	public function browser_view_enabled( $bool = false ) {
		return boolval( $this->get_meta( 'browser_view' ) );
	}

	/**
	 * Return the browser view option for this email.
	 *
	 * @param $link
	 *
	 * @return string
	 */
	public function browser_view_link( $link = '' ) {
		return permissions_key_url( managed_page_url( sprintf( "archive/%s", dechex( $this->get_event()->get_id() ) ) ), $this->get_contact(), 'view_archive' );
	}

	/**
	 * Return the tracking link for this email when opened.
	 *
	 * @return string
	 */
	public function get_open_tracking_link() {
		return managed_page_url( sprintf(
			"o/%s/%s",
			dechex( $this->get_contact()->get_id() ),
			! $this->is_testing() ? dechex( $this->get_event()->get_id( true ) ) : 0,
		) );
	}

	/**
	 * Return the tracking link for this email when a link is clicked.
	 *
	 * @return string
	 */
	public function get_click_tracking_link() {
		return managed_page_url(
			sprintf( 'c/%s/%s/',
				dechex( $this->get_contact()->get_id() ),
				! $this->is_testing() ? dechex( $this->get_event()->get_id( true ) ) : 0,
			)
		);
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
	 * Returns the alignment for an email
	 *
	 * @return string
	 */
	public function get_alignment() {
		return $this->get_meta( 'alignment', true );
	}

	/**
	 * Get the custom CSS for the email
	 *
	 * @return array|mixed
	 */
	public function get_css() {
		return $this->get_meta( 'css' );
	}

	/**
	 * Width of the email in pixels
	 *
	 * @return int
	 */
	public function get_width() {
		return apply_filters( 'groundhogg/email/width', absint( $this->get_meta( 'width' ) ?: get_default_email_width() ), $this );
	}

	/**
	 * Get the email being sent to
	 *
	 * @return  string
	 */
	public function get_to_address() {

		/**
		 * Filter the to address
		 *
		 * @param string  $email
		 * @param Contact $contact
		 * @param Email   $email
		 */
		return apply_filters( 'groundhogg/email/to', $this->get_contact()->get_email(), $this->get_contact(), $this );
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

		return apply_filters( 'groundhogg/email/subject', $subject, $this, $this->get_contact() );
	}

	/**
	 * Return pre header text
	 * This is called by a filter rather than directly
	 *
	 * @param $content
	 *
	 * @return string
	 */
	public function get_merged_pre_header( $content = '' ) {
		$pre_header = do_replacements(
			$this->get_pre_header(),
			$this->get_contact()->get_id()
		);

		$pre_header = apply_filters( 'wpgh_email_pre_header', $pre_header );

		return apply_filters( 'groundhogg/email/pre_header', $pre_header );
	}

	/**
	 * If the email has the footer block
	 *
	 * @return bool
	 */
	public function has_footer_block() {
		return $this->is_block_editor() && str_contains( $this->content, '<div id="footer"' );
	}

	/**
	 * Returns the editor type based on the format of the content
	 *
	 * @return string
	 */
	public function get_editor_type() {

		// HTML editor
		if ( $this->get_meta( 'type' ) === 'html' || str_starts_with( $this->content, '<!DOCTYPE' ) ) {
			return 'html';
		}

		// Legacy blocks
		if ( str_contains( $this->content, 'data-block' ) || str_contains( $this->content, 'text_block' ) || str_contains( $this->content, '<div class="row"' ) ) {
			return 'legacy_blocks';
		}

		// New blocks
		if ( $this->get_meta( 'blocks' ) ) {
			return 'blocks';
		}

		// Legacy plain
		return 'legacy_plain';
	}

	/**
	 * If is using the block editor
	 *
	 * @return bool
	 */
	public function is_block_editor() {
		return $this->get_editor_type() === 'blocks';
	}

	/**
	 * If is using the HTML editor
	 *
	 * @return bool
	 */
	public function is_html_editor() {
		return $this->get_editor_type() === 'html';
	}

	/**
	 * Parses the content and retrieves blocks from the content
	 * Not nested though
	 *
	 * @return array|false
	 */
	public function get_blocks() {

		if ( ! $this->is_block_editor() ) {
			return false;
		}

		return Block_Registry::instance()->parse_blocks( $this->content );
	}

	/**
	 * Hide blocks that have conditional visibility enabled
	 * Also ends up removing HTML comments which is an added bonus
	 *
	 * @param string $content
	 * @param string $context
	 *
	 * @return array|mixed|string|string[]|null
	 */
	protected function maybe_hide_blocks( $content, $context = 'html' ) {

		if ( $context === 'plain' ) {
			$pattern = '/%s\\[filters:(?\'id\'[A-Za-z0-9-]+) (?\'attributes\'(?&json))\\](?\'content\'.*)\\[\\/filters:\\k\'id\'\]/s';
		} else {
			$pattern = '/%s<!--\\s*(?\'type\'[a-z]+):(?\'id\'[A-Za-z0-9-]+) (?\'attributes\'(?&json))\\s*-->(?\'content\'.*)<!--\\s*\/\\k\'type\':\\k\'id\'\\s*-->/s';
		}

		$pattern = sprintf( $pattern, get_json_regex() );
		$found   = preg_match_all( $pattern, $content, $matches );

		if ( ! $found ) {
			return $content;
		}

		foreach ( $matches[0] as $i => $match ) {
			$block_content = $matches['content'][ $i ];
			$type          = $matches['type'][ $i ];

			// Gets rid of the [filters {...}] code in the plain text
			if ( $this->is_testing() ) {
				$content = str_replace( $match, $this->maybe_hide_blocks( $block_content, $context ), $content );
				continue;
			}

			$block = json_decode( $matches['attributes'][ $i ], true );

			if ( ! $block ) {
				// could not decode json
				continue;
			}

			//  No filters
			if ( ! isset_not_empty( $block, 'include_filters' ) && ! isset_not_empty( $block, 'exclude_filters' ) ) {
				// Still have to check inner content
				$content = str_replace( $match, $this->maybe_hide_blocks( $block_content, $context ), $content );
				continue;
			}

			$query = new Contact_Query( [
				'filters'         => get_array_var( $block, 'include_filters', [] ),
				'exclude_filters' => get_array_var( $block, 'exclude_filters', [] ),
				'include'         => $this->get_contact()->get_id()
			] );

			$count = $query->count();

			// If count is zero, no match and can't see
			if ( $count === 0 ) {
				// Remove the block
				$content = str_replace( $match, '', $content );
			} else {
				// Check inner content if any
				$content = str_replace( $match, $this->maybe_hide_blocks( $block_content, $context ), $content );
			}
		}

		return $content;
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

		$content = $this->get_content();

		switch ( $this->get_editor_type() ) {
			// Block Editor
			case 'blocks':
				$content = $this->maybe_hide_blocks( $content );
				$content = Block_Registry::instance()->replace_dynamic_content( $content );

				// Special handling for footer unsub link
				if ( $this->has_footer_block() ) {
					$content = str_replace( '#unsubscribe_link#', $this->get_unsubscribe_link(), $content );
				}
				break;
			// Legacy plain text editor
			case 'legacy_plain':
				$content = wpautop( $content );
				break;
			// HTML templates
			case 'html':

				// Handle open tracking image
				if ( ! is_option_enabled( 'gh_disable_open_tracking' ) ) {

					if ( str_contains( $content, '</body>' ) ) {
						$content = str_replace( '</body>', html()->e( 'img', [
								'src'    => $this->get_open_tracking_link(),
								'width'  => '0',
								'height' => '0',
								'alt'    => '',
							] ) . '</body>', $content );
					}

				}

				break;
			// Legacy Block Editor
			case 'legacy_blocks':
				// There's no post processing for legacy_blocks
				break;
		}

		$content = do_replacements(
			$content,
			$this->get_contact()
		);

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
	 * Temp array to store a map if the original link to the tracking link
	 * For sanitization later
	 *
	 * @var array
	 */
	protected $tracking_link_map = [];

	/**
	 * Replace the link with another link which has the ?ref UTM which will lead to the original link
	 *
	 * @param $matches
	 *
	 * @return string
	 */
	public function tracking_link_callback( $matches ) {

		$clean_url = no_and_amp( html_entity_decode( $matches[2] ) );

		// If the url is not to be tracked leave it alone.
		if ( is_url_excluded_from_tracking( $clean_url ) ) {
			return $matches[1] . $clean_url . $matches[3];
		}

		$local_hostname = wp_parse_url( home_url(), PHP_URL_HOST );

		// target hostname and local hostname are the same
		// in that case just use the path as the hostname is not needed.
		if ( wp_parse_url( $clean_url, PHP_URL_HOST ) === $local_hostname ) {
			$regex     = preg_quote( $local_hostname );
			$clean_url = preg_replace( "@https?://$regex@", '', $clean_url );
		}

		// Save to link map...
		$tracking_link                         = trailingslashit( $this->get_click_tracking_link() . base64_encode( $clean_url ) );
		$this->tracking_link_map[ $clean_url ] = $tracking_link;

		return $matches[1] . $tracking_link . $matches[3];
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
		if ( ! $this->is_testing() ) {
			$url = unsubscribe_url( $this->get_contact() );
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
		return managed_page_url( 'preferences/profile' );
	}

	/**
	 * Build the email
	 *
	 * @return string
	 */
	public function build() {

		disable_emojis();

		the_email( $this );

		switch ( $this->get_editor_type() ) {
			case 'html':
				$content = $this->get_merged_content();
				break;
			default:
				$templates = new Template_Loader();

				ob_start();

				$templates->get_template_part( 'email/' . $this->get_template() );

				$content = ob_get_clean();
				break;
		}

		if ( ! is_option_enabled( 'gh_disable_click_tracking' ) ) {
			$content = $this->convert_to_tracking_links( $content );
		}

		return apply_filters( 'groundhogg/email/the_content', $content );
	}

	/**
	 * Return the from name for the email
	 *
	 *
	 * @return string
	 */
	public function get_from_name() {

		switch ( $this->from_type ) {

			case 'owner':

				if ( $this->get_contact() && $this->get_contact()->get_ownerdata() ) {
					return $this->get_contact()->get_ownerdata()->display_name;
				}

				break;
			case 'user':

				if ( $this->get_from_user() ) {
					return $this->get_from_user()->display_name;
				}

				break;
		}

		return get_default_from_name();

	}

	/**
	 * Return the from name for the email
	 *
	 * @return string
	 */
	public function get_from_email() {

		switch ( $this->from_type ) {

			case 'owner':

				if ( $this->get_contact() && $this->get_contact()->get_ownerdata() ) {
					return $this->get_contact()->get_ownerdata()->user_email;
				}

				break;
			case 'user':

				if ( $this->get_from_user() ) {
					return $this->get_from_user()->user_email;
				}

				break;
		}

		return get_default_from_email();
	}

	/**
	 * The compiled from header
	 *
	 * @return string
	 */
	public function get_from_header(){
		return sprintf( '%s <%s>', wp_specialchars_decode( $this->get_from_name() ), $this->get_from_email() );
	}

	/**
	 * The reply-to address
	 *
	 * @return string
	 */
	public function get_reply_to_address() {
		return $this->get_meta( 'reply_to_override' ) ? do_replacements( $this->get_meta( 'reply_to_override' ), $this->get_contact() ) : $this->get_from_email();
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

		$defaults = [
			'from'         => $this->get_from_header(),
			'reply-to'     => $this->get_reply_to_address(),
			'return-path'  => is_email( get_return_path_email() ) ? get_return_path_email() : $this->get_from_email(),
			'content-type' => 'text/html; charset=UTF-8',
		];

		// Do not add this header to transactional emails or if the header is disabled in the settings.
		if ( ! $this->is_transactional() && ! is_option_enabled( 'gh_disable_unsubscribe_header' ) ) {

			$list_unsub_header = sprintf(
				'<%s>,<mailto:%s?subject=Unsubscribe %s from %s>',
				add_query_arg( [
					'contact' => encrypt( $this->get_contact()->get_email() )
				], rest_url( Unsubscribe_Api::NAME_SPACE . '/unsubscribe' ) ),
				get_bloginfo( 'admin_email' ),
				$this->get_to_address(),
				get_bloginfo()
			);

			$defaults['list-unsubscribe']      = $list_unsub_header;
			$defaults['list-unsubscribe-post'] = 'List-Unsubscribe=One-Click';
		}

		// Add list-id header to marketing emails
		if ( ! $this->is_transactional() ) {
			$defaults['list-id'] = wp_parse_url( home_url(), PHP_URL_HOST );
		}

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
		$this->contact = get_contactdata( $contact );
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
	 * Send the email
	 *
	 * @param $contact_id_or_email Contact|int|string
	 * @param $event               Event|int the of the associated event
	 *
	 * @return bool|WP_Error
	 */
	public function send( $contact_id_or_email, $event = 0 ) {

		is_sending( true );
		the_email( $this );

		// Clear any old previous errors.
		$this->clear_errors();

		if ( ! $this->is_ready() && ! $this->is_testing() ) {
			return new WP_Error( 'email_not_ready', sprintf( __( 'Emails cannot be sent in %s mode.', 'groundhogg' ), $this->get_status() ) );
		}

		$contact = $contact_id_or_email instanceof Contact ? $contact_id_or_email : get_contactdata( $contact_id_or_email );

		if ( ! $contact ) {
			return new WP_Error( 'no_recipient', __( 'No valid recipient was provided.' ) );
		}

		$this->set_contact( $contact );

		/* we got an event so all is well */
		if ( is_object( $event ) ) {
			$this->set_event( $event );
		}

		// Contact is undeliverable
		if ( ! $contact->is_deliverable() ) {
			return new WP_Error( 'undeliverable', __( 'The email address is marked as undeliverable.' ) );
		}

		// Ignore if testing or the message is transactional
		if ( ! $this->is_testing() && ! $this->is_transactional() && ! $contact->is_marketable() ) {
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

		if ( $this->is_transactional() ) {
			// If the email is transactional, use the installed transactional system
			$sent = \Groundhogg_Email_Services::send_transactional( $to, $subject, $content, $headers );
		} else {
			// If the email is marketing, send using the installed marketing system, in most cases also wp_mail.
			$sent = \Groundhogg_Email_Services::send_marketing( $to, $subject, $content, $headers );
		}

		is_sending( false );

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
//				'@\t+@siu',
//				'@\n+@siu'
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

		return preg_replace( $search, $replace, $content );
	}

	/**
	 * @param array $data
	 *
	 * @return array|mixed
	 */
	protected function sanitize_columns( $data = [] ) {

		foreach ( $data as $key => &$value ) {
			switch ( $key ) {
				case 'content':

					$value = trim( $value );
					$value = email_kses( $value );

					break;
				case 'is_template':
				case 'from_user':
				case 'author':
					$value = absint( $value );
					break;
				case 'plain_text':
					$value = sanitize_textarea_field( $value );
					break;
				case 'subject':
				case 'title':
				case 'pre_header':
				case 'status':
				case 'message_type':
				default:
					$value = sanitize_text_field( $value );
					break;
				case 'from_select':

					if ( $value === 'default' ) {
						$data['from_user'] = 0;
					} else {
						$data['from_user'] = $value;
					}

					break;
			}
		}

		return $data;

	}

	/**
	 * @return array
	 */
	public function get_as_array() {

		// Check if coming from contacts page
		if ( current_user_can( 'edit_contacts' ) ) {

			$referer = wp_get_referer();
			$params  = [];
			wp_parse_str( wp_parse_url( $referer, PHP_URL_QUERY ), $params );

			if ( get_array_var( $params, 'page' ) === 'gh_contacts' && isset_not_empty( $params, 'contact' ) ) {
				$contact_id = absint( $params['contact'] );
				$this->set_contact( $contact_id );
			}
		}

		// Ensure there is a contact object there somewhere
		if ( ! is_a_contact( $this->contact ) ) {

			$contact = get_contactdata();

			if ( ! $contact && is_user_logged_in() ) {
				$user = wp_get_current_user();

				$contact             = new Contact();
				$contact->email      = $user->user_email;
				$contact->first_name = $user->first_name;
				$contact->last_name  = $user->last_name;
			}

			if ( ! $contact ) {
				return parent::get_as_array();
			}

			$this->set_contact( $contact );
		}

		// Ensure there is an event object there somewhere
		if ( ! $this->event ) {
			$this->set_event( new Event() );
		}

		the_email( $this );

		$live_preview = $this->build();

		// Auto p the plain text content so we can create a new text block
		if ( $this->get_editor_type() === 'legacy_plain' ) {
			$this->content = wpautop( $this->content );
		}

		// Do this again just in case 🤷
		$this->set_from_select();

		return array_merge( parent::get_as_array(), [
			'campaigns' => $this->get_related_objects( 'campaign' ),
			'context'   => [
				'editor_type' => $this->get_editor_type(),
				'from_avatar' => get_avatar_url( $this->get_from_user_id(), [
					'size' => 40
				] ),
				'from_name'   => $this->get_from_name(),
				'from_email'  => $this->get_from_email(),
				'subject'     => $this->get_merged_subject_line(),
				'from_user'   => $this->get_from_user(),
				'built'       => $live_preview,
				'plain'       => $this->get_merged_alt_body(),
			]
		] );
	}

	/**
	 * Get related email statistics
	 *
	 * @param $start
	 * @param $end
	 *
	 * @return array
	 */
	public function get_email_stats( $start, $end, $steps_ids = [] ) {

		if ( empty( $steps_ids ) ) {

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

			$steps_ids = wp_parse_id_list( $steps_ids );

			$where_events = [
				'relationship' => "AND",
				[ 'col' => 'step_id', 'val' => $steps_ids, 'compare' => 'IN' ],
				[ 'col' => 'event_type', 'val' => Event::FUNNEL, 'compare' => '=' ],
				[ 'col' => 'status', 'val' => Event::COMPLETE, 'compare' => '=' ],
				[ 'col' => 'time', 'val' => $start, 'compare' => '>=' ],
				[ 'col' => 'time', 'val' => $end, 'compare' => '<=' ],
			];

			$count = get_db( 'events' )->count( [
				'where' => $where_events,
			] );

			$where_opened = [
				'relationship' => "AND",
				[ 'col' => 'step_id', 'val' => $steps_ids, 'compare' => 'IN' ],
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
				[ 'col' => 'step_id', 'val' => $steps_ids, 'compare' => 'IN' ],
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
				'step_id'       => $steps_ids,
				'activity_type' => Activity::UNSUBSCRIBED,
				'before'        => $end,
				'after'         => $start,
			] );
		}

		return [
			'steps'              => $steps_ids,
			'sent'               => $count,
			'opened'             => $opened,
			'open_rate'          => percentage( $count, $opened ),
			'clicked'            => $clicked,
			'all_clicks'         => $all_clicked,
			'unsubscribed'       => $unsubscribed,
			'click_through_rate' => percentage( $opened, $clicked ),
		];
	}
}
