<?php

use function Groundhogg\get_array_var;

class Groundhogg_Email_Services {

	const TRANSACTIONAL = 'transactional';
	const MARKETING = 'marketing';

	private static $email_services = [];

	public static function get() {
		return self::$email_services;
	}

	public static function init() {
		self::register( 'wp_mail', __( 'WordPress Default', 'groundhogg' ), 'wp_mail' );
	}

	/**
	 * Register a new email service
	 *
	 * @param $id       string
	 * @param $name     string
	 * @param $callback callable
	 */
	public static function register( $id, $name, $callback ) {
		self::$email_services[ $id ] = [
			'id'       => $id,
			'name'     => $name,
			'callback' => $callback
		];
	}

	/**
	 * Get the callback function provided the ID of a registered email service.
	 *
	 * @param $id
	 *
	 * @return bool|mixed
	 */
	public static function get_callback( $id ) {
		return get_array_var( get_array_var( self::$email_services, $id ), 'callback' );
	}

	/**
	 * Get the callback function provided the ID of a registered email service.
	 *
	 * @param $id
	 *
	 * @return bool|mixed
	 */
	public static function get_name( $id ) {
		return get_array_var( get_array_var( self::$email_services, $id ), 'name' );
	}

	/**
	 * Get the services as a dropdown.
	 *
	 * @return array[]
	 */
	public static function dropdown() {
		return array_map( function ( $service ) {
			return $service['name'];
		}, self::$email_services );
	}

	/**
	 * @param $type
	 *
	 * @return false|mixed|string|void
	 */
	public static function get_saved_service( $type ) {
		$saved_service = get_option( 'gh_' . $type . '_email_service', 'wp_mail' );

		return $saved_service ?: 'wp_mail';
	}

	/**
	 * Set the service
	 *
	 * @param $type    string
	 * @param $service string
	 */
	public static function set_service( $type, $service ) {
		update_option( 'gh_' . $type . '_email_service', $service );
	}

	/**
	 * Handler for transactional emails.
	 *
	 * @param string|array $to          Array or comma-separated list of email addresses to send message.
	 * @param string       $subject     Email subject
	 * @param string       $message     Message contents
	 * @param string|array $headers     Optional. Additional headers.
	 * @param string|array $attachments Optional. Files to attach.
	 *
	 * @return bool Whether the email contents were sent successfully.
	 */
	public static function send( $service, $to, $subject, $message, $headers = '', $attachments = array() ) {
		$callback = is_callable( self::get_callback( $service ) ) ? self::get_callback( $service ) : 'wp_mail';

		return call_user_func( $callback, $to, $subject, $message, $headers, $attachments );
	}

	/**
	 * Handler for transactional emails.
	 *
	 * @param string|array $to          Array or comma-separated list of email addresses to send message.
	 * @param string       $subject     Email subject
	 * @param string       $message     Message contents
	 * @param string|array $headers     Optional. Additional headers.
	 * @param string|array $attachments Optional. Files to attach.
	 *
	 * @return bool Whether the email contents were sent successfully.
	 */
	public static function send_transactional( $to, $subject, $message, $headers = '', $attachments = array() ) {
		$service = self::get_saved_service( self::TRANSACTIONAL );

		return self::send( $service, $to, $subject, $message, $headers, $attachments );
	}

	/**
	 * Handler for marketing emails.
	 *
	 * @param string|array $to          Array or comma-separated list of email addresses to send message.
	 * @param string       $subject     Email subject
	 * @param string       $message     Message contents
	 * @param string|array $headers     Optional. Additional headers.
	 * @param string|array $attachments Optional. Files to attach.
	 *
	 * @return bool Whether the email contents were sent successfully.
	 */
	public static function send_marketing( $to, $subject, $message, $headers = '', $attachments = array() ) {
		$service = self::get_saved_service( self::MARKETING );

		return self::send( $service, $to, $subject, $message, $headers, $attachments );
	}
}
