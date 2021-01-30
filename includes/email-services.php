<?php

use function Groundhogg\get_array_var;

class Groundhogg_Email_Services {

	const TRANSACTIONAL = 'transactional';
	const MARKETING = 'marketing';
	const WORDPRESS = 'wordpress';

	private static $current_message_type = null;
	private static $current_email_service = null;

	private static $email_services = [];

	public static function get() {
		return self::$email_services;
	}

	public static function init() {
		self::register( 'wp_mail', __( 'WordPress Default', 'groundhogg' ), 'wp_mail' );

		if ( function_exists( 'mailhawk_mail' ) ) {
			self::register( 'mailhawk', __( 'MailHawk', 'groundhogg' ), 'mailhawk_mail' );
		}

		add_action( sprintf( 'update_option_gh_%s_email_service', self::TRANSACTIONAL ), [
			'Groundhogg_Email_Services',
			sprintf( 'option_update_%s_callback', self::TRANSACTIONAL )
		], 10, 2 );

		add_action( sprintf( 'update_option_gh_%s_email_service', self::MARKETING ), [
			'Groundhogg_Email_Services',
			sprintf( 'option_update_%s_callback', self::MARKETING )
		], 10, 2 );

		add_filter( sprintf( "option_gh_%s_email_service", self::TRANSACTIONAL ), [
			'Groundhogg_Email_Services',
			sprintf( 'option_%s', self::TRANSACTIONAL )
		] );

		add_filter( sprintf( "option_gh_%s_email_service", self::MARKETING ), [
			'Groundhogg_Email_Services',
			sprintf( 'option_%s', self::MARKETING )
		] );

		add_action( 'admin_notices', [ 'Groundhogg_Email_Services', 'hide_conflicts' ], 1 );
	}

	public static function hide_conflicts() {
		if ( function_exists( 'mailhawk_mail' ) ) {
			if ( self::get_wordpress_service() === 'mailhawk'
			     || self::get_transactional_service() === 'mailhawk'
			     || self::get_marketing_service() === 'mailhawk' ) {
				remove_action( 'admin_notices', 'mailhawk_wp_mail_already_defined' );
			}
		}
	}

	public static function option_transactional( $val ) {
		if ( $val === 'wp_mail' && self::get_wordpress_service() !== 'wp_mail' ) {
			return self::get_wordpress_service();
		}

		return $val;
	}

	public static function option_marketing( $val ) {
		if ( $val === 'wp_mail' && self::get_wordpress_service() !== 'wp_mail' ) {
			return self::get_wordpress_service();
		}

		return $val;
	}

	/**
	 * Marketing cannot be wp_mail if WP Default is also not wp_mail
	 *
	 * @param $old_val     mixed
	 * @param $new_val     mixed
	 */
	public static function option_update_marketing_callback( $old_val, $new_val ) {
		if ( $new_val === 'wp_mail' && self::get_wordpress_service() !== 'wp_mail' ) {
			self::set_service( self::MARKETING, self::get_wordpress_service() );
		}
	}

	/**
	 * If the default service is changed, but the others are not, update them so that everything is the same to avoid confusion.
	 *
	 * @param $old_val     mixed
	 * @param $new_val     mixed
	 */
	public static function option_update_transactional_callback( $old_val, $new_val ) {
		if ( $new_val === 'wp_mail' && self::get_wordpress_service() !== 'wp_mail' ) {
			self::set_service( self::TRANSACTIONAL, self::get_wordpress_service() );
		}
	}

	private static function clear() {
		self::$current_message_type  = null;
		self::$current_email_service = null;
	}

	public static function get_service_display_name( $service ) {
		return get_array_var( get_array_var( self::$email_services, $service ), 'name' );
	}

	/**
	 * Get the current message type
	 * If not set assume transactional
	 *
	 * @return null|string
	 */
	public static function get_current_message_type() {
		return self::$current_message_type ?: self::WORDPRESS;
	}

	/**
	 * Get the email service in use
	 *
	 * @return null|string
	 */
	public static function get_current_email_service() {
		return self::$current_email_service ?: self::get_wordpress_service();
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
	 * @return false|mixed|string|void
	 */
	public static function get_transactional_service() {
		return self::get_saved_service( self::TRANSACTIONAL );
	}

	/**
	 * @return false|mixed|string|void
	 */
	public static function get_marketing_service() {
		return self::get_saved_service( self::MARKETING );
	}

	/**
	 * @return false|mixed|string|void
	 */
	public static function get_wordpress_service() {
		return self::get_saved_service( self::WORDPRESS );
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
		$callback                    = is_callable( self::get_callback( $service ) ) ? self::get_callback( $service ) : 'wp_mail';
		self::$current_email_service = $service;

		$sent = call_user_func( $callback, $to, $subject, $message, $headers, $attachments );

		self::clear();

		return $sent;
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
		$service                    = self::get_saved_service( self::TRANSACTIONAL );
		self::$current_message_type = self::TRANSACTIONAL;

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
		$service                    = self::get_saved_service( self::MARKETING );
		self::$current_message_type = self::MARKETING;

		return self::send( $service, $to, $subject, $message, $headers, $attachments );
	}

	/**
	 * Handler for core WordPress emails.
	 *
	 * @param string|array $to          Array or comma-separated list of email addresses to send message.
	 * @param string       $subject     Email subject
	 * @param string       $message     Message contents
	 * @param string|array $headers     Optional. Additional headers.
	 * @param string|array $attachments Optional. Files to attach.
	 *
	 * @return bool Whether the email contents were sent successfully.
	 */
	public static function send_wordpress( $to, $subject, $message, $headers = '', $attachments = array() ) {
		$service                    = self::get_saved_service( self::WORDPRESS );
		self::$current_message_type = self::WORDPRESS;

		return self::send( $service, $to, $subject, $message, $headers, $attachments );
	}
}
