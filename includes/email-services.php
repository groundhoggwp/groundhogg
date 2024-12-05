<?php

use Groundhogg\Email_Logger;
use Groundhogg\Mailer\Log_Only;
use function Groundhogg\disable_emojis;
use function Groundhogg\get_array_var;

class Groundhogg_Email_Services {

	const TRANSACTIONAL = 'transactional';
	const MARKETING = 'marketing';
	const WORDPRESS = 'wordpress';

	private static $message_id = '';
	private static $current_message_type = null;
	private static $current_email_service = null;

	private static $email_services = [];

	public static function get() {
		return self::$email_services;
	}

	public static function init() {
		self::register( 'wp_mail', __( 'WordPress Default', 'groundhogg' ), 'wp_mail' );
		self::register( 'log_only', __( 'Log Only', 'groundhogg' ), __NAMESPACE__ . '\log_only' );

		if ( function_exists( 'mailhawk_mail' ) ) {
			self::register( 'mailhawk', __( 'MailHawk', 'groundhogg' ), 'mailhawk_mail' );
		}

		foreach ( [ self::TRANSACTIONAL, self::MARKETING ] as $channel ) {

			add_action( sprintf( 'update_option_gh_%s_email_service', $channel ), [
				'Groundhogg_Email_Services',
				sprintf( 'option_update_%s_callback', $channel )
			], 10, 2 );

			add_filter( sprintf( "option_gh_%s_email_service", $channel ), [
				'Groundhogg_Email_Services',
				sprintf( 'option_%s', $channel )
			] );

		}

		add_action( 'admin_notices', [ 'Groundhogg_Email_Services', 'hide_conflicts' ], 1 );

		do_action( 'Groundhogg/email_services/init' );
	}

	public static function hide_conflicts() {
		if ( function_exists( 'mailhawk_mail' ) ) {
			if ( self::get_wordpress_service() === 'mailhawk'
			     || self::get_transactional_service() === 'mailhawk'
			     || self::get_marketing_service() === 'mailhawk' ) {
				remove_action( 'admin_notices', 'mailhawk_wp_mail_already_defined' );
			}
		}

		if ( self::service_in_use( 'log_only' ) && ! Email_Logger::is_enabled() && current_user_can( 'manage_options' ) ) {
			add_action( 'admin_notices', __NAMESPACE__ . '\log_only_logs_not_enabled_notice' );
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
	 * Whether a registered service is in use by one of the email layers
	 *
	 * @param $service
	 *
	 * @return bool
	 */
	public static function service_in_use( $service ) {
		return in_array( $service, [
			self::get_wordpress_service(),
			self::get_transactional_service(),
			self::get_marketing_service(),
		] );
	}

	/**
	 * Marketing cannot be wp_mail if WP Default is also not wp_mail
	 *
	 * @param $old_val     mixed
	 * @param $new_val     mixed
	 */
	public static function option_update_marketing_callback( $old_val, $new_val ) {
		if ( $new_val === 'wp_mail' && self::get_wordpress_service() !== 'wp_mail' ) {
			self::set_service_for_type( self::MARKETING, self::get_wordpress_service() );
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
			self::set_service_for_type( self::TRANSACTIONAL, self::get_wordpress_service() );
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
	 * Set the current message type
	 *
	 * @param $type
	 *
	 * @return void
	 */
	public static function set_current_message_type( $type ) {
		self::$current_message_type = $type;
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
	 * Alias for set_service()
	 *
	 * @param $service
	 *
	 * @return void
	 */
	public static function set_current_email_service( $service ) {

		// Only allow registered services to be used
		if ( array_key_exists( $service, self::$email_services ) ) {
			self::$current_email_service = $service;
		}

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
	 * @param string $id the ID of the service
	 *
	 * @return callable
	 */
	public static function get_service_callback( $id = false ) {

		if ( ! $id ) {
			$id = self::get_current_email_service();
		}

		return get_array_var( get_array_var( self::$email_services, $id ), 'callback' );
	}

	/**
	 * Alias for get_service_callback()
	 *
	 * @param bool $id the ID of the service
	 *
	 * @return callable
	 * @depreacted since 3.7.3
	 */
	public static function get_callback( $id = false ) {
		_deprecated_function( __METHOD__, '3.7.3', __CLASS__ . '::get_service_callback()' );

		return self::get_service_callback( $id );
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
	 * Sets the current service to the given
	 *
	 * @return void
	 * @depreacted 3.7.3
	 */
	public static function set_service( $type, $service ) {
		_deprecated_function( __METHOD__, '3.7.4', __CLASS__ . '::set_service_for_type()' );
		self::set_service_for_type( $type, $service );
	}

	/**
	 * Set the service
	 *
	 * @param $type    string
	 * @param $service string
	 */
	public static function set_service_for_type( $type, $service ) {
		update_option( 'gh_' . $type . '_email_service', $service );
	}

	/**
	 * Handler for all emails.
	 *
	 * @param string|array $to          Array or comma-separated list of email addresses to send message.
	 * @param string       $subject     Email subject
	 * @param string       $message     Message contents
	 * @param string|array $headers     Optional. Additional headers.
	 * @param string|array $attachments Optional. Files to attach.
	 *
	 * @return bool Whether the email contents were sent successfully.
	 */
	public static function send( $to, $subject, $message, $headers = '', $attachments = array() ) {

		disable_emojis();

		/**
		 * Allow hooks to swap wap out flags before the email is sent based on the current state of Groundhogg_Email_Services
		 *
		 * @param mixed  $to      the recipients of the email
		 * @param string $subject the email subject line
		 * @param string $message the email message
		 * @param mixed  $headers the email headers
		 */
		do_action( 'groundhogg/email_services/before_send', $to, $subject, $message, $headers, $attachments );

		$callback = self::get_service_callback();

		// fallback to wp_mail() if callback is not available
		if ( ! is_callable( $callback ) ) {
			$callback = 'wp_mail';
		}

		// clear the message id from any previous send
		self::$message_id = '';

		add_action( 'wp_mail_failed', [ self::class, 'catch_wp_mail_failed' ] );

		$sent = call_user_func( $callback, $to, $subject, $message, $headers, $attachments );

		self::clear();

		remove_action( 'wp_mail_failed', [ self::class, 'catch_wp_mail_failed' ] );

		return $sent;
	}

	/**
	 * Last WP_Error error from most recently sent email
	 *
	 * @var WP_Error
	 */
	protected static $last_error;

	/**
	 * Catch the WP_Error from a failed email
	 *
	 * @param $error WP_Error
	 */
	public static function catch_wp_mail_failed( $error ) {
		self::$last_error = $error;
	}

	/**
	 * Returns the most recent error
	 *
	 * @return WP_Error
	 */
	public static function get_last_error() {
		return self::$last_error;
	}

	/**
	 * Whether the last_error isset and is a wp_error
	 *
	 * @return bool
	 */
	public static function has_error() {
		return is_wp_error( self::$last_error );
	}

	/**
	 * Sets a message ID
	 *
	 * @param $message_id
	 */
	public static function set_message_id( $message_id ) {
		self::$message_id = $message_id;

		Email_Logger::set_msg_id( $message_id );
	}

	/**
	 * Get a message Id
	 *
	 * @return null
	 */
	public static function get_message_id() {
		return self::$message_id;
	}

	/**
	 * Send an email of a specific type
	 *
	 * @param string       $type        The message type we're sending for
	 * @param string|array $to          Array or comma-separated list of email addresses to send message.
	 * @param string       $subject     Email subject
	 * @param string       $message     Message contents
	 * @param string|array $headers     Optional. Additional headers.
	 * @param string|array $attachments Optional. Files to attach.
	 *
	 * @return bool Whether the email contents were sent successfully.
	 */
	public static function send_type( $type, $to, $subject, $message, $headers = '', $attachments = array() ) {
		self::set_current_message_type( $type );
		$service = self::get_saved_service( $type );

		return self::send_with_service( $service, $to, $subject, $message, $headers, $attachments );
	}

	/**
	 * Send an email using a specific service
	 *
	 * @param string $service     The service to use for sending the email
	 * @param mixed  $to          where to send the email to
	 * @param string $subject     the subject line to use
	 * @param string $message     the message to send
	 * @param mixed  $headers     any headers
	 * @param mixed  $attachments any attachments
	 *
	 * @return bool
	 */
	public static function send_with_service( $service, $to, $subject, $message, $headers = '', $attachments = array() ) {
		self::set_current_email_service( $service );

		return self::send( $to, $subject, $message, $headers, $attachments );
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
		return self::send_type( self::TRANSACTIONAL, $to, $subject, $message, $headers, $attachments );
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
		return self::send_type( self::MARKETING, $to, $subject, $message, $headers, $attachments );
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
		return self::send_type( self::WORDPRESS, $to, $subject, $message, $headers, $attachments );
	}
}

if ( ! function_exists( 'log_only_logs_not_enabled_notice' ) ) {

	/**
	 * Warning to display if log_only service is in use but the email logs aren't.
	 *
	 * @return void
	 */
	function log_only_logs_not_enabled_notice() {

		?>
        <div class="notice notice-warning is-dismissible">
            <p>
				<?php printf( __( '<b>Attention:</b> The <code>Log Only</code> email service is in use, but email logs are not enabled. <a href="%s">Enable logging!</a>' ), \Groundhogg\admin_page_url( 'gh_settings', [ 'tab' => 'email' ] ) ); ?>
            </p>
        </div>
		<?php

	}

}

if ( ! function_exists( 'log_only' ) ) {

	/**
	 * Wraps gh_mail() and uses the Log_Only mailer which does not send any email
	 *
	 * @param $to
	 * @param $subject
	 * @param $message
	 * @param $headers
	 * @param $attachments
	 *
	 * @return bool
	 */
	function log_only( $to, $subject, $message, $headers = '', $attachments = array() ) {

		static $mailer;

		if ( ! isset( $mailer ) ) {
			$mailer = new Log_Only( true );
		}

		return gh_mail( $to, $subject, $message, $headers, $attachments, $mailer );
	}

}
