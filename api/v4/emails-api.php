<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
use Groundhogg\Campaign;
use Groundhogg\Contact;
use Groundhogg\Block_Registry;
use Groundhogg\Email;
use Groundhogg\Event;
use Groundhogg\Library_Email;
use Groundhogg\Plugin;
use WP_REST_Request;
use WP_REST_Server;
use function Groundhogg\array_map_to_contacts;
use function Groundhogg\base64_json_decode;
use function Groundhogg\do_replacements;
use function Groundhogg\email_kses;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_default_from_email;
use function Groundhogg\get_default_from_name;
use function Groundhogg\get_object_ids;
use function Groundhogg\is_sending;
use function Groundhogg\is_template_site;
use function Groundhogg\map_to_class;
use function Groundhogg\process_events;
use function Groundhogg\send_email_notification;
use function Groundhogg\track_activity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Emails_Api extends Base_Object_Api {

	public function register_routes() {
		parent::register_routes();

		$key   = $this->get_primary_key();
		$route = $this->get_route();

		register_rest_route( self::NAME_SPACE, "emails/send", [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'send_email' ],
				'permission_callback' => [ $this, 'send_permissions_callback' ]
			],
		] );

		register_rest_route( self::NAME_SPACE, "/{$route}/(?P<{$key}>\d+)/send", [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'send_email_by_id' ],
				'permission_callback' => [ $this, 'send_permissions_callback' ]
			],
		] );

		register_rest_route( self::NAME_SPACE, "/{$route}/(?P<{$key}>\d+)/test", [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'sent_test' ],
				'permission_callback' => [ $this, 'send_permissions_callback' ]
			],
		] );

		register_rest_route( self::NAME_SPACE, "/{$route}/test", [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'sent_test' ],
				'permission_callback' => [ $this, 'send_permissions_callback' ]
			],
		] );

		register_rest_route( self::NAME_SPACE, "/{$route}/(?P<{$key}>\d+)/preview", [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'generate_preview' ],
				'permission_callback' => [ $this, 'update_permissions_callback' ]
			],
		] );

		register_rest_route( self::NAME_SPACE, "/{$route}/preview", [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'generate_preview' ],
				'permission_callback' => [ $this, 'update_permissions_callback' ]
			],
		] );

		register_rest_route( self::NAME_SPACE, "/{$route}/blocks/(?P<block_type>\w+)/", [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'render_block' ],
				'permission_callback' => [ $this, 'update_permissions_callback' ]
			],
		] );

		register_rest_route( self::NAME_SPACE, "/{$route}/play-button", [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'overlay_play_button' ],
				'permission_callback' => [ $this, 'update_permissions_callback' ]
			],
		] );
	}

	/**
	 * Handle campaigns
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|\WP_Error|\WP_REST_Response
	 */
	public function create_single( WP_REST_Request $request ) {
		$data      = $request->get_param( 'data' );
		$meta      = $request->get_param( 'meta' );
		$campaigns = wp_parse_id_list( $request->get_param( 'campaigns' ) );

		$object = $this->create_new_object( $data, $meta, $request->has_param( 'force' ) );

		if ( ! $object->exists() ) {

			global $wpdb;

			return self::ERROR_400( 'error', 'Bad request.', [
				'data' => $data,
				'meta' => $meta,
				'wpdb' => $wpdb->last_error
			] );
		}

		if ( ! empty( $campaigns ) ) {
			foreach ( $campaigns as $campaign ) {
				$object->create_relationship( new Campaign( $campaign ) );
			}
		}

		$this->do_object_created_action( $object );

		return self::SUCCESS_RESPONSE( [
			'item' => $object
		] );
	}

	/**
	 * Handle campaigns
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function update_single( WP_REST_Request $request ) {
		$primary_key = absint( $request->get_param( $this->get_primary_key() ) );

		$object = $this->create_new_object( $primary_key );

		if ( ! $object->exists() ) {
			return $this->ERROR_RESOURCE_NOT_FOUND();
		}

		$data      = $request->get_param( 'data' );
		$meta      = $request->get_param( 'meta' );
		$campaigns = wp_parse_id_list( $request->get_param( 'campaigns' ) );

		$object->update( $data );

		// If the current object supports meta data...
		if ( method_exists( $object, 'update_meta' ) ) {
			$object->update_meta( $meta );
		}

		$has_campaigns    = get_object_ids( $object->get_related_objects( 'campaign' ) );
		$add_campaigns    = array_diff( $campaigns, $has_campaigns );
		$remove_campaigns = array_diff( $has_campaigns, $campaigns );

		if ( ! empty( $add_campaigns ) ) {
			foreach ( $add_campaigns as $campaign ) {
				$object->create_relationship( new Campaign( $campaign ) );
			}
		}

		if ( ! empty( $remove_campaigns ) ) {
			foreach ( $remove_campaigns as $campaign ) {
				$object->delete_relationship( new Campaign( $campaign ) );
			}
		}

		$this->do_object_updated_action( $object );

		return self::SUCCESS_RESPONSE( [ 'item' => $object ] );
	}

	/**
	 * Render a dynamic block
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 */
	public function render_block( \WP_REST_Request $request ) {

		$block = $request->get_param( 'block_type' );
		$props = base64_json_decode( $request->get_param( 'props' ) );

		$html = Block_Registry::instance()->render_block( $block, $props );

		return self::SUCCESS_RESPONSE( [
			'content' => $html
		] );
	}

	/**
	 * Overlay a play button onto a video thumbnail
	 *
	 * @param $request
	 */
	public function overlay_play_button( \WP_REST_Request $request ) {

		$thumb_url = $request->get_param( 'url' );

		if ( ! $thumb_url ) {
			return self::ERROR_404();
		}

		$response = wp_remote_get( $thumb_url );

		if ( is_wp_error( $response ) ) {
			return self::ERROR_404();
		}

		$image = wp_remote_retrieve_body( $response );

		if ( function_exists( 'imagecreatefromjpeg' ) ) {

			// Load the base image
			$baseImage = imagecreatefromstring( $image );
			if ( ! $baseImage ) {
				return self::ERROR_404( 'error', 'Could not load thumbnail resource' );
			}

			// Load the play button image with a transparent background
			$playButton = imagecreatefrompng( GROUNDHOGG_ASSETS_PATH . 'images/play-button.png' );

			if ( ! $playButton ) {
				return self::ERROR_404( 'error', 'Could not load play button resource' );
			}

			// Get the dimensions of the base image and play button
			$baseWidth  = imagesx( $baseImage );
			$baseHeight = imagesy( $baseImage );

			// Square Image
			$newSize = $baseWidth * 0.15;

			$resizedPlayButton = imagescale( $playButton, $newSize, $newSize );

			$buttonWidth  = imagesx( $resizedPlayButton );
			$buttonHeight = imagesy( $resizedPlayButton );

			// Calculate the position to place the play button in the center of the base image
			$positionX = ( $baseWidth - $buttonWidth ) / 2;
			$positionY = ( $baseHeight - $buttonHeight ) / 2;

			// Copy the play button onto the base image
			$result = imagecopy( $baseImage, $resizedPlayButton, $positionX, $positionY, 0, 0, $buttonWidth, $buttonHeight );

			if ( ! $result ) {
				return self::ERROR_404( 'error', 'Could not add play button to image' );
			}

			// Output the final image
			header( 'Content-Type: image/jpeg' );
			imagejpeg( $baseImage );

			// Clean up resources
			imagedestroy( $baseImage );
			imagedestroy( $playButton );
			imagedestroy( $resizedPlayButton );
			die();
		}

		header( 'Content-Type: image/jpeg' );
		echo $image;
		die();
	}

	/**
	 * Send emails to the contact based on email and contact ID
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function send_email_by_id( \WP_REST_Request $request ) {

		//get email
		$email_id = absint( $request->get_param( $this->get_primary_key() ) );

		$email = new Email( $email_id );

		if ( ! $email->exists() ) {
			return $this->ERROR_RESOURCE_NOT_FOUND();
		}

		$to      = $request->get_param( 'to' );
		$contact = get_contactdata( $to );

		if ( ! $contact ) {
			return self::ERROR_404( 'error', 'Contact not found' );
		}

		//send emails
		$status = send_email_notification( $email, $contact, $request->get_param( 'when' ) );

		add_action( 'wp_mail_failed', [ $this, 'handle_wp_mail_error' ] );

		$result = process_events( $contact );

		if ( $result !== true ) {
			return $result[0];
		}

		if ( ! $status ) {
			return self::ERROR_UNKNOWN();
		}

		if ( $this->has_errors() ) {
			return $this->get_last_error();
		}

		return self::SUCCESS_RESPONSE();
	}


	/**
	 * If there was an issue with WP mail send it straight away
	 *
	 * @param $error
	 */
	public function handle_wp_mail_error( $error ) {
		$this->add_error( $error );
	}


	/**
	 * Really basic send email handler
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function send_email( \WP_REST_Request $request ) {

		is_sending( true );

		$to  = array_map( 'sanitize_email', $request->get_param( 'to' ) ?: [] );
		$cc  = array_map( 'sanitize_email', $request->get_param( 'cc' ) ?: [] );
		$bcc = array_map( 'sanitize_email', $request->get_param( 'bcc' ) ?: [] );

		if ( empty( $to ) && empty( $cc ) && empty( $bcc ) ) {
			return self::ERROR_401( 'no_recipients', 'No recipients were defined.' );
		}

		// Get relevant contact records
		$contactRecords = $to;
		$contactRecords = array_map_to_contacts( $contactRecords );
		$contact        = array_shift( $contactRecords );

		$from_email = sanitize_email( $request->get_param( 'from_email' ) ) ?: get_default_from_email();
		$from_name  = sanitize_text_field( $request->get_param( 'from_name' ) ) ?: get_default_from_name();

		$content = $request->get_param( 'content' );

		// Replacements will be based on the first email address provided
		if ( $contact && $contact->exists() ) {
			$content = do_replacements( $content, $contact );
		}

		if ( apply_filters( 'groundhogg/add_custom_footer_text_to_personal_emails', true ) ) {
			$content .= wpautop( get_option( 'gh_custom_email_footer_text' ) );
		}

		$content = email_kses( $content );
		$subject = sanitize_text_field( $request->get_param( 'subject' ) );
		$type    = sanitize_text_field( $request->get_param( 'type' ) ?: 'wordpress' );

		$headers = [
			'Content-Type: text/html',
			sprintf( "From: %s <%s>", $from_name, $from_email ),
		];

		if ( ! empty( $cc ) ) {
			$headers[] = 'Cc: ' . implode( ',', $cc );
		}

		if ( ! empty( $bcc ) ) {
			$headers[] = 'Bcc: ' . implode( ',', $bcc );
		}

		add_action( 'wp_mail_failed', [ $this, 'handle_wp_mail_error' ] );

		$result = \Groundhogg_Email_Services::send_type( $type, $to, $subject, $content, $headers );

		if ( $this->has_errors() ) {
			return $this->get_last_error();
		}

		if ( ! $result ) {
			return self::ERROR_500();
		}

		$all_recipients = array_unique( array_merge( $to, $bcc, $cc ) );

		foreach ( $all_recipients as $recipient ) {
			$contact = get_contactdata( $recipient );

			if ( ! $contact ) {
				continue;
			}

			track_activity( $contact, 'composed_email_sent', [], [
				'subject' => $subject,
				'from'    => $from_email,
				'sent_by' => get_current_user_id()
			] );
		}

		return self::SUCCESS_RESPONSE();
	}

	/**
	 * Send a test email address
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function sent_test( \WP_REST_Request $request ) {

		//get email
		$email_id = absint( $request->get_param( $this->get_primary_key() ) );

		if ( $email_id ) {
			$email = new Email( $email_id );

			if ( ! $email->exists() ) {
				return $this->ERROR_RESOURCE_NOT_FOUND();
			}
		} // Temp email
		else {
			$email = new Email();
		}

		$to = array_filter( array_map( 'sanitize_email', wp_parse_list( $request->get_param( 'to' ) ) ) );

		if ( empty( $to ) ) {
			return self::ERROR_401( 'error', 'Invalid email address provided' );
		}

		update_user_meta( get_current_user_id(), 'gh_test_emails', $to );

		// Use the current user as the contact data
		$contact = new Contact( [
			'email' => wp_get_current_user()->user_email
		] );

		if ( $request->has_param( 'data' ) && $request->has_param( 'meta' ) ) {
			// Override with the dump
			$email->data = $request->get_param( 'data' );
			$email->meta = $request->get_param( 'meta' );
		}

		$email->enable_test_mode();

		add_filter( 'groundhogg/email/to', function ( $emails ) use ( $to ) {
			return implode( ',', $to );
		} );

		$sent = $email->send( $contact, new Event() );

		return self::SUCCESS_RESPONSE( [
			'sent' => $sent
		] );
	}

	/**
	 * Takes a dump of an email and generates a preview of the content without saving it
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function generate_preview( \WP_REST_Request $request ) {

		//get email
		$email_id = absint( $request->get_param( $this->get_primary_key() ) );

		if ( $email_id ) {
			$email = new Email( $email_id );

			if ( ! $email->exists() ) {
				return $this->ERROR_RESOURCE_NOT_FOUND();
			}
		} // Temp email
		else {
			$email = new Email();
		}

		// Override with the dump
		$email->data = $request->get_param( 'data' );
		$email->meta = $request->get_param( 'meta' );

		$email->enable_test_mode();

		return self::SUCCESS_RESPONSE( [ 'item' => $email ] );
	}

	public function read( WP_REST_Request $request ) {
		$query = $request->get_params();

		if ( $request->has_param( 'remote_templates' ) ) {
			unset( $query['remote_templates'] );
		}

		$query = wp_parse_args( $query, [
			'select'     => '*',
			'orderby'    => $this->get_primary_key(),
			'order'      => 'DESC',
			'limit'      => 25,
			'found_rows' => true,
		] );

		$items = $this->get_db_table()->query( $query );
		$total = $this->get_db_table()->found_rows();

		$items = array_map( [ $this, 'map_raw_object_to_class' ], $items );

		if ( $request->has_param( 'remote_templates' ) ) {
			$remote_templates = map_to_class( Plugin::instance()->library->get_email_templates(), Library_Email::class );
			$items            = array_merge( $items, $remote_templates );
			$total            = $total + count( $remote_templates );
		}

		return self::SUCCESS_RESPONSE( [
			'total_items' => $total,
			'items'       => $items
		] );
	}

	public function get_db_table_name() {
		return 'emails';
	}

	public function send_permissions_callback() {
		return current_user_can( 'send_emails' );
	}

	public function read_permissions_callback() {
		return is_template_site() || current_user_can( 'view_emails' ) || current_user_can( 'edit_emails' );
	}

	public function update_permissions_callback() {
		return current_user_can( 'edit_emails' );
	}

	public function create_permissions_callback() {
		return current_user_can( 'add_emails' );
	}

	public function delete_permissions_callback() {
		return current_user_can( 'delete_emails' );
	}
}
