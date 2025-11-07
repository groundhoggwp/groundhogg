<?php

namespace Groundhogg;

use Groundhogg\Api\V4\Unsubscribe_Api;
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

		$title   = $this->title;
		$subject = $this->subject;

		// polyfill title as the subject if empty
		if ( empty( $title ) && ! empty( $subject ) ) {
			$this->title = $subject;
		}

		$this->is_template = boolval( $this->is_template );

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
		$this->data = $this->sanitize_columns( $data );
		$this->meta = $meta;

		$this->post_setup();
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
		return $this->title; // defaults to subject in post_setup
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
			$content = Block_Registry::instance()->parse_blocks( $content, 'plain' );

			// this is maintained for backwards compatibility, for new emails is won't do anything...
			$content = $this->maybe_hide_blocks( $content, 'plain' );
			$content = Block_Registry::instance()->replace_dynamic_content( $content, 'plain' );
		}

		// Do plain text replacements
		$content = do_replacements_plain_text( $content, $this->get_contact() );

		// Unsub link that may be in the footer
		$content = str_replace( '#unsubscribe_link#', $this->get_unsubscribe_link(), $content );

		// Fix markdown line breaks
		$content = preg_replace( '/(?<=[^\\S])\h\\n/', "  \n", $content );

		// Tracking must be enabled and there must be a valid event to track with
//		if ( ! is_option_enabled( 'gh_disable_click_tracking' ) && $this->event && $this->event->exists() ) {
//			$content = $this->convert_to_tracking_links( $content, 'plain' );
//		}

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
	public function browser_view_link() {

		if ( $this->event && $this->event->exists() ) {
			return permissions_key_url( managed_page_url( sprintf( "archive/%s", dechex( $this->get_event()->get_id() ) ) ), $this->get_contact(), 'view_archive' );
		}

		return managed_page_url( 'archive' );

	}

	/**
	 * Return the tracking link for this email when opened.
	 *
	 * @return string
	 */
	public function get_open_tracking_src() {
		return managed_page_url( sprintf(
			"o/%s/%s",
			dechex( $this->get_contact()->get_id() ),
			dechex( $this->get_event()->get_id( true ) )
		) );
	}

	/**
	 * Return the tracking link for this email when a link is clicked.
	 *
	 * @return string
	 */
	public function get_click_tracking_link() {
		return managed_page_url( sprintf(
			'c/%s/%s/',
			dechex( $this->get_contact()->get_id() ),
			dechex( $this->get_event()->get_id( true ) )
		) );
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

		$parts = [
			$this->get_meta( 'css' ),
			$this->get_meta( 'template_css' ),
		];

		return implode( PHP_EOL, $parts );
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

		$subject = do_replacements( $this->get_subject_line(), $this->get_contact() );

		return apply_filters( 'groundhogg/email/subject', $subject, $this, $this->get_contact() );
	}

	/**
	 * What goes in the <title> tag
	 *
	 * @return string
	 */
	public function get_html_head_title() {
		$subject = $this->get_merged_subject_line();
		// if actually sending, the <title> should be the same as the subject line
		/* translators: Login screen title. 1: Login screen name, 2: Network or site name */
		return is_sending() ? $subject : sprintf( '%1$s &lsaquo; %2$s', $this->get_merged_subject_line(), get_bloginfo( 'name', 'display' ) );
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
			$this->get_contact()
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

		// New blocks
		if ( $this->get_meta( 'blocks' ) ) {
			return 'blocks';
		}

		// Legacy blocks
		if ( str_contains( $this->content, 'data-block' ) || str_contains( $this->content, 'text_block' ) || str_contains( $this->content, '<div class="row"' ) ) {
			return 'legacy_blocks';
		}

		// Legacy plain
		return 'legacy_plain';
	}

	/**
	 * If is using the block editorX
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
	 * @since 4.1.1 this will only ever be used in the 'plain' context for backwards compatibility, as Block_Registry::parse_blocks() handles this for HTML
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
//			$type          = $matches['type'][ $i ];

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

			// No contact
			if ( ! is_a_contact( $this->contact ) ) {
				// Remove the block
				$content = str_replace( $match, '', $content );
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
	 * @param string $deprecated unused in 3.0+
	 *
	 * @return string
	 */
	public function get_merged_content( $deprecated = '' ) {

		$content = $this->get_content();

		switch ( $this->get_editor_type() ) {
			// Block Editor
			case 'blocks':
				$content = Block_Registry::instance()->parse_blocks( $content, 'html' );

				// this is now handled by Block_Registry::parse_blocks()
//				$content = $this->maybe_hide_blocks( $content );

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
				if ( ! is_option_enabled( 'gh_disable_open_tracking' ) && $this->event && $this->event->exists() ) {

					if ( str_contains( $content, '</body>' ) ) {
						$content = str_replace( '</body>', html()->e( 'img', [
								'src'    => $this->get_open_tracking_src(),
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

		// No contact? Ignore replacements.
		if ( is_a_contact( $this->contact ) ) {
			$content = do_replacements(
				$content,
				$this->get_contact()
			);
		}

		/* filter out double http based on bug where links have http:// prepended */
		$schema  = is_ssl() ? 'https://' : 'http://';
		$content = str_replace( 'http://https://', $schema, $content );
		$content = str_replace( 'http://http://', $schema, $content );

		/* Other filters */
		$content = make_clickable( $content );
		$content = str_replace( '&#038;', '&amp;', $content );
		$content = do_shortcode( $content );
		$content = fix_nested_p( $content );

		return apply_filters( 'groundhogg/email/get_merged_content', $content );
	}

	/**
	 * Get URLs from the content.
	 * This will only get static URLs, dynamic URLs possibly added by replacement codes will not be retrieved.
	 *
	 * @return string[]
	 */
	public function get_urls() {

		preg_match_all( '@href="(?<url>https?://[^"]+)"@i', $this->event && $this->contact ? $this->get_merged_content() : $this->content, $matches );

		return $matches['url'];
	}

	/**
	 * Convert links to tracking links
	 *
	 * @param $content string content which may contain Superlinks
	 *
	 * @return string
	 */
	public function convert_to_tracking_links( $content, $context = 'html' ) {
		if ( $context === 'plain' ) {
			return preg_replace_callback( '@\((https?://[^)]+)\)@i', [
				$this,
				'tracking_link_callback_plain'
			], $content );
		}

		return preg_replace_callback( '@href=["\'](https?://[^"\']+)["\']@i', [
			$this,
			'tracking_link_callback'
		], $content );
	}

	/**
	 * Convert plain text version tracking links
	 *
	 * @param $matches
	 *
	 * @return string
	 */
	public function tracking_link_callback_plain( $matches ) {
		return $this->tracking_link_callback( $matches, '(%s)' );
	}

	/**
	 * Replace the link with another link which has the ?ref UTM which will lead to the original link
	 *
	 * @param $matches
	 *
	 * @return string
	 */
	public function tracking_link_callback( $matches, $replacement = 'href="%s"' ) {

		$clean_url = no_and_amp( html_entity_decode( $matches[1] ) );

		// If the url is not to be tracked leave it alone.
		if ( is_url_excluded_from_tracking( $clean_url ) ) {
			return sprintf( $replacement, $clean_url );
		}

		$local_hostname = wp_parse_url( home_url(), PHP_URL_HOST );

		// target hostname and local hostname are the same
		// in that case just use the path as the hostname is not needed.
		if ( wp_parse_url( $clean_url, PHP_URL_HOST ) === $local_hostname ) {
			$regex     = preg_quote( $local_hostname );
			$clean_url = preg_replace( "@https?://$regex@", '', $clean_url );
		}

		// Tracking link does not support empty
		// "/" sends it to the homepage.
		if ( empty( $clean_url ) ) {
			$clean_url = '/';
		}

		$tracking_link = trailingslashit( $this->get_click_tracking_link() . base64url_encode( $clean_url ) );

		return sprintf( $replacement, $tracking_link );
	}

	/**
	 * Add UTM params to tracking links
	 *
	 * @param $content
	 *
	 * @return array|string|string[]|null
	 */
	public function maybe_add_utm_to_links( $content, $context = 'html' ) {

		$utm_params = array_filter( [
			'utm_source'   => $this->utm_source,
			'utm_campaign' => $this->utm_campaign,
			'utm_content'  => $this->utm_content,
			'utm_term'     => $this->utm_term,
			'utm_medium'   => $this->utm_medium,
		] );

		if ( empty( $utm_params ) ) {
			return $content;
		}

		$url = untrailingslashit( home_url() );

		if ( $context === 'plain' ) {
			return preg_replace_callback( "@\(({$url}[^)]*)\)@i", [
				$this,
				'utm_link_callback_plain'
			], $content );
		}

		return preg_replace_callback( "@href=[\"']({$url}[^\"']*)[\"']@i", [
			$this,
			'utm_link_callback'
		], $content );
	}

	/**
	 * Add UTM links to plain text
	 *
	 * @param $matches
	 *
	 * @return string
	 */
	protected function utm_link_callback_plain( $matches ) {
		return $this->utm_link_callback( $matches, '(%s)' );
	}

	/**
	 * Add the utm params to a link
	 *
	 * @param array $matches
	 *
	 * @return string
	 */
	protected function utm_link_callback( $matches, $format = 'href="%s"' ) {

		$clean_url = no_and_amp( html_entity_decode( $matches[1] ) );

		$utm_params = urlencode_deep( array_filter( [
			'utm_source'   => $this->utm_source,
			'utm_campaign' => $this->utm_campaign,
			'utm_content'  => $this->utm_content,
			'utm_term'     => $this->utm_term,
			'utm_medium'   => $this->utm_medium,
		] ) );

		return sprintf( $format, add_query_arg( $utm_params, $clean_url ) );
	}

	/**
	 * Compresses the block Ids of the email content
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public function compress_block_ids( string $content ) {
		preg_match_all( '/b-([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $content, $matches );

		$unique = array_unique( $matches[0] );
		$map    = [];

		foreach ( $unique as $index => $full ) {
			$map[ $full ] = 'b-' . ( $index + 1 );
		}

		return str_replace( array_keys( $map ), array_values( $map ), $content );
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
		if ( ! $this->is_testing() && is_a_contact( $this->contact ) ) {
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
				// compress block Ids to reduce overall email size, and css block size.
				$content = $this->compress_block_ids( $content );
				break;
		}

		$content = $this->maybe_add_utm_to_links( $content );

		// Tracking must be enabled and there must be a valid event to track with
		if ( ! is_option_enabled( 'gh_disable_click_tracking' ) && $this->event && $this->event->exists() ) {
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
	public function get_from_header() {
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
	 * Get the outgoing email service that will be used by this email
	 *
	 * @return string
	 */
	public function get_outgoing_email_service() {
		return $this->is_transactional() ? \Groundhogg_Email_Services::get_transactional_service() : \Groundhogg_Email_Services::get_marketing_service();
	}

	/**
	 * Get the headers to send
	 *
	 * @return array
	 */
	public function get_headers() {

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
			'From'                     => $this->get_from_header(),
			'Reply-To'                 => $this->get_reply_to_address(),
			'Content-Type'             => 'text/html; charset=UTF-8',
			'Precedence'               => 'bulk',
			'X-Auto-Response-Suppress' => 'AutoReply'
		];

		$outgoing_service = $this->get_outgoing_email_service();

		// if we're using any of our API integrations, do not set the return path, it might cause issues, or even cause emails not to send at all.
		if ( $outgoing_service === 'wp_mail' || $outgoing_service === 'smtp' ) {
			$defaults['Return-Path'] = is_email( get_return_path_email() ) ? get_return_path_email() : $this->get_from_email();
		}

		// Do not add this header to transactional. Only add the header if we can tie it to an event
		if ( ! $this->is_transactional() && $this->event && $this->event->exists() ) {

			$unsub_pk = generate_permissions_key( $this->contact, 'preferences' );
			$event_id = dechex( $this->event->get_id() );

			$one_click = rest_url( sprintf( '%s/unsubscribe/%s/%s', Unsubscribe_Api::NAME_SPACE, $event_id, $unsub_pk ) );
			$mail_to   = esc_url( sprintf( 'mailto:%s?subject=%s',
				get_option( 'gh_unsubscribe_email' ) ?: get_bloginfo( 'admin_email' ),
				/* translators: 1: contact's email address, 2: the site name */
				sprintf( __( 'Unsubscribe %1$s from %2$s', 'groundhogg' ), $this->contact->get_email(), get_bloginfo() ) ) );

			/**
			 * Filter the email address the unsubscribe notification is sent to
			 *
			 * @param string $email_address
			 * @param Email  $email
			 * @param string $key      The key required to unsubscribe the contact
			 * @param string $event_id the event id
			 */
			$mail_to = apply_filters( 'groundhogg/list_unsubscribe_header/mailto', $mail_to, $this, $unsub_pk, $event_id );

			$list_unsub_header = sprintf(
				'<%s>, <%s>',
				$one_click,
				$mail_to
			);

			/**
			 * Filter the list unsubscribe header
			 *
			 * @param string $list_unsub_header
			 * @param string $one_click
			 * @param Email  $email
			 */
			$list_unsub_header = apply_filters( 'groundhogg/list_unsubscribe_header', $list_unsub_header, $this, $unsub_pk, $event_id );

			$defaults['List-Unsubscribe']      = $list_unsub_header;
			$defaults['List-Unsubscribe-Post'] = 'List-Unsubscribe=One-Click';
		}

		// Add list-id header to marketing emails
		if ( ! $this->is_transactional() ) {
			$defaults['List-Id'] = wp_parse_url( home_url(), PHP_URL_HOST );
		}

		// Merge the custom headers with the defaults...
		$headers = wp_parse_args( $headers, $defaults );

		/**
		 * Filter the headers while they are still in the associated array format
		 *
		 * @param $headers array
		 * @param $email   Email
		 * @param $contact Contact
		 */
		$headers = apply_filters( "groundhogg/email/headers_assoc", $headers, $this, $this->contact );

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

		if ( is_int( $event ) ) {
			$event = get_queued_event_by_id( $event );
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
	public function send( $contact_id_or_email, $event = null ) {

		is_sending( true );
		the_email( $this );

		// Clear any old previous errors.
		$this->clear_errors();

		$contact = get_contactdata( $contact_id_or_email );

		if ( ! is_a_contact( $contact ) ) {
			return new WP_Error( 'no_recipient', esc_html__( 'No valid recipient was provided.', 'groundhogg' ) );
		}

		$this->set_contact( $contact );

		/* we got an event so all is well */
		if ( $event !== null ) {
			$this->set_event( $event );
		}

		// We're not testing
		if ( ! $this->is_testing() ) {

			// If email isn't set to ready
			if ( ! $this->is_ready() ) {
				/* translators: %s: the email's status */
				return new WP_Error( 'email_not_ready', sprintf( esc_html__( 'Emails cannot be sent in %s mode.', 'groundhogg' ), $this->get_status() ) );
			}

			// Contact is undeliverable
			if ( ! $contact->is_deliverable() ) {
				return new WP_Error( 'undeliverable', esc_html__( 'The email address is marked as undeliverable.', 'groundhogg' ) );
			}

			// Ignore if testing or the message is transactional
			if ( ! $this->is_transactional() && ! $contact->is_marketable() ) {
				return new WP_Error( 'non_marketable', esc_html__( 'Contact is not marketable.', 'groundhogg' ) );
			}
		}

		/* Additional settings */
		add_action( 'phpmailer_init', [ $this, 'set_bounce_return_path' ] );
		add_action( 'phpmailer_init', [ $this, 'set_plaintext_body' ] );
		add_action( 'wp_mail_failed', [ $this, 'mail_failed' ] );
		add_filter( 'wp_mail_content_type', [ $this, 'send_in_html' ] );

		// even though we set the from header, plugins will try to overwrite the from email and name
		add_filter( 'wp_mail_from_name', [ $this, 'get_from_name' ] );
		add_filter( 'wp_mail_from', [ $this, 'get_from_email' ] );

		$content = $this->build();
		$to      = $this->get_to_address();
		$subject = $this->get_merged_subject_line();
		$headers = $this->get_headers();

		do_action_ref_array( 'groundhogg/email/before_send', [ $this, &$to, &$subject, &$content, &$headers ] );

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
		remove_filter( 'wp_mail_from', [ $this, 'get_from_email' ] );
		remove_filter( 'wp_mail_from_name', [ $this, 'get_from_name' ] );

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
					$value = email_kses( $value );
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
	 * Sanitize stuff
	 *
	 * @param string $key
	 * @param        $value
	 *
	 * @return array|mixed
	 */
	protected function sanitize_meta( $key, $value ) {

		switch ( $key ) {
			case 'custom_headers':
				$value = array_map_keys( $value, 'sanitize_key' );
				$value = array_map( 'sanitize_text_field', $value );
				break;
			case 'replacements':
				$value = array_map_keys( $value, 'sanitize_key' );
				$value = array_map( '\Groundhogg\email_kses', $value );
				break;
			case 'blocks':
			case 'use_default_from':
			case 'browser_view':
				$value = boolval( $value );
				break;
			case 'reply_to_override':
				$value = sanitize_email( $value );
				break;
			case 'type':
			case 'utm_content':
			case 'utm_campaign':
			case 'utm_medium':
			case 'utm_source':
			case 'utm_term':
			case 'backgroundImage':
			case 'backgroundPosition':
			case 'backgroundSize':
			case 'backgroundRepeat':
				$value = sanitize_text_field( $value );
				break;
			case 'direction':
				$value = one_of( $value, [ 'ltr', 'rtl' ] );
				break;
			case 'alignment':
				$value = one_of( $value, [ 'left', 'center' ] );
				break;
			case 'backgroundColor':
				$value = sanitize_hex_color( $value );
				break;
			case 'width':
				$value = absint( $value );
				if ( ! $value ){
					$value = 640; // set value to default 640 if unset
				}
				break;
			case 'css':
			case 'template_css':
				$value = email_kses( $value ); // todo sanitize_css()?
				break;
		}

		return $value;
	}

	/**
	 * Override title and status when duplicating
	 *
	 * @param $overrides
	 * @param $meta_overrides
	 *
	 * @return Base_Object|Base_Object_With_Meta
	 */
	public function duplicate( $overrides = [], $meta_overrides = [] ) {

		$overrides = array_merge( [
			/* translators: %s: the name of the email being duplicated */
			'title'  => sprintf( __( 'Copy of %s', 'groundhogg' ), $this->get_title() ),
			'status' => 'draft'
		], $overrides );

		return parent::duplicate( $overrides, $meta_overrides );
	}

	/**
	 * Context data is not needed for exporting
	 *
	 * @return array
	 */
	public function export() {
		return parent::get_as_array();
	}

	/**
	 * @return array
	 */
	public function get_as_array() {

		$referer = wp_get_referer();
		$params  = [];
		wp_parse_str( wp_parse_url( $referer, PHP_URL_QUERY ), $params );

		// Check if coming from contacts page
		if ( current_user_can( 'edit_contacts' )
		     && get_array_var( $params, 'page' ) === 'gh_contacts'
		     && isset_not_empty( $params, 'contact' )
		) {
			// We're previewing from a contact's perspective
			$contact_id = absint( $params['contact'] );
			$this->set_contact( $contact_id );
		}

		// Maybe we're editing the email
		if ( current_user_can( 'edit_emails' )
		     && get_array_var( $params, 'page' ) === 'gh_emails'
		     && get_array_var( 'action' ) === 'edit'
		) {
			// Enable test mode for previews from the edit screen
			$this->enable_test_mode();
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
				$contact = new Contact();
			}

			$this->set_contact( $contact );
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
//				'from_user'   => $this->get_from_user(),
				'built'       => $live_preview,
				'plain'       => $this->get_merged_alt_body(),
			]
		] );
	}
}
