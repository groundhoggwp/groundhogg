<?php

use Groundhogg\Email_Logger;
use function Groundhogg\disable_emojis;
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
		self::register( 'log_only', __( 'Log Only', 'groundhogg' ), __NAMESPACE__ . '\log_only' );

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

		if ( self::service_in_use( 'log_only' ) && ! Email_Logger::is_enabled() && current_user_can( 'manage_options' ) ){
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
	public static function service_in_use( $service ){
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

		disable_emojis();

		$callback                    = is_callable( self::get_callback( $service ) ) ? self::get_callback( $service ) : 'wp_mail';
		self::$current_email_service = $service;

		$sent = call_user_func( $callback, $to, $subject, $message, $headers, $attachments );

		self::clear();

		return $sent;
	}

	/**
	 * Handler for unknown email type.
	 *
	 * @param string|array $to          Array or comma-separated list of email addresses to send message.
	 * @param string       $subject     Email subject
	 * @param string       $message     Message contents
	 * @param string|array $headers     Optional. Additional headers.
	 * @param string|array $attachments Optional. Files to attach.
	 *
	 * @return bool Whether the email contents were sent successfully.
	 */
	public static function send_type( $type, $to, $subject, $message, $headers = '', $attachments = array() ) {
		$service                    = self::get_saved_service( $type );
		self::$current_message_type = $type;

		return self::send( $service, $to, $subject, $message, $headers, $attachments );
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

/**
 * Warning to display if log_only sertvice is in use but the email logs aren't.
 *
 * @return void
 */
function log_only_logs_not_enabled_notice(){

	?>
	<div class="notice notice-warning is-dismissible">
		<p>
			<?php printf( __( '<b>Attention:</b> The <code>Log Only</code> email service is in use, but email logs are not enabled. <a href="%s">Enable logging!</a>' ), \Groundhogg\admin_page_url( 'gh_settings', [ 'tab' => 'email' ] ) ); ?>
		</p>
	</div>
	<?php

}

/**
 * This is a copy of `wp_mail()` except that it doesn't send the email. Instead of using PHPMailer:send() we use PHPMailer::preSend()
 * So that all logging actions are run, but the email is never actually sent, and we get realistic feedback.
 *
 * The default content type is `text/plain` which does not allow using HTML.
 * However, you can set the content type of the email by using the
 * {@see 'wp_mail_content_type'} filter.
 *
 * The default charset is based on the charset used on the blog. The charset can
 * be set using the {@see 'wp_mail_charset'} filter.
 *
 * @since 1.2.1
 * @since 5.5.0 is_email() is used for email validation,
 *              instead of PHPMailer's default validator.
 *
 * @param string|string[] $to          Array or comma-separated list of email addresses to send message.
 * @param string          $subject     Email subject.
 * @param string          $message     Message contents.
 * @param string|string[] $headers     Optional. Additional headers.
 * @param string|string[] $attachments Optional. Paths to files to attach.
 *
 * @return bool Whether the email was sent successfully.
 * @global PHPMailer\PHPMailer\PHPMailer $phpmailer
 *
 */
function log_only( $to, $subject, $message, $headers = '', $attachments = array() ) {
	// Compact the input, apply the filters, and extract them back out.

	/**
	 * Filters the wp_mail() arguments.
	 *
	 * @since 2.2.0
	 *
	 * @param array $args {
	 *     Array of the `wp_mail()` arguments.
	 *
	 *     @type string|string[] $to          Array or comma-separated list of email addresses to send message.
	 *     @type string          $subject     Email subject.
	 *     @type string          $message     Message contents.
	 *     @type string|string[] $headers     Additional headers.
	 *     @type string|string[] $attachments Paths to files to attach.
	 * }
	 */
	$atts = apply_filters( 'wp_mail', compact( 'to', 'subject', 'message', 'headers', 'attachments' ) );

	if ( isset( $atts['to'] ) ) {
		$to = $atts['to'];
	}

	if ( ! is_array( $to ) ) {
		$to = explode( ',', $to );
	}

	if ( isset( $atts['subject'] ) ) {
		$subject = $atts['subject'];
	}

	if ( isset( $atts['message'] ) ) {
		$message = $atts['message'];
	}

	if ( isset( $atts['headers'] ) ) {
		$headers = $atts['headers'];
	}

	if ( isset( $atts['attachments'] ) ) {
		$attachments = $atts['attachments'];
	}

	if ( ! is_array( $attachments ) ) {
		$attachments = explode( "\n", str_replace( "\r\n", "\n", $attachments ) );
	}
	global $phpmailer;

	// (Re)create it, if it's gone missing.
	if ( ! ( $phpmailer instanceof PHPMailer\PHPMailer\PHPMailer ) ) {
		require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
		require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
		require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
		$phpmailer = new PHPMailer\PHPMailer\PHPMailer( true );

		$phpmailer::$validator = static function ( $email ) {
			return (bool) is_email( $email );
		};
	}

	// Headers.
	$cc       = array();
	$bcc      = array();
	$reply_to = array();

	if ( empty( $headers ) ) {
		$headers = array();
	} else {
		if ( ! is_array( $headers ) ) {
			// Explode the headers out, so this function can take
			// both string headers and an array of headers.
			$tempheaders = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
		} else {
			$tempheaders = $headers;
		}
		$headers = array();

		// If it's actually got contents.
		if ( ! empty( $tempheaders ) ) {
			// Iterate through the raw headers.
			foreach ( (array) $tempheaders as $header ) {
				if ( strpos( $header, ':' ) === false ) {
					if ( false !== stripos( $header, 'boundary=' ) ) {
						$parts    = preg_split( '/boundary=/i', trim( $header ) );
						$boundary = trim( str_replace( array( "'", '"' ), '', $parts[1] ) );
					}
					continue;
				}
				// Explode them out.
				list( $name, $content ) = explode( ':', trim( $header ), 2 );

				// Cleanup crew.
				$name    = trim( $name );
				$content = trim( $content );

				switch ( strtolower( $name ) ) {
					// Mainly for legacy -- process a "From:" header if it's there.
					case 'from':
						$bracket_pos = strpos( $content, '<' );
						if ( false !== $bracket_pos ) {
							// Text before the bracketed email is the "From" name.
							if ( $bracket_pos > 0 ) {
								$from_name = substr( $content, 0, $bracket_pos - 1 );
								$from_name = str_replace( '"', '', $from_name );
								$from_name = trim( $from_name );
							}

							$from_email = substr( $content, $bracket_pos + 1 );
							$from_email = str_replace( '>', '', $from_email );
							$from_email = trim( $from_email );

							// Avoid setting an empty $from_email.
						} elseif ( '' !== trim( $content ) ) {
							$from_email = trim( $content );
						}
						break;
					case 'content-type':
						if ( strpos( $content, ';' ) !== false ) {
							list( $type, $charset_content ) = explode( ';', $content );
							$content_type                   = trim( $type );
							if ( false !== stripos( $charset_content, 'charset=' ) ) {
								$charset = trim( str_replace( array( 'charset=', '"' ), '', $charset_content ) );
							} elseif ( false !== stripos( $charset_content, 'boundary=' ) ) {
								$boundary = trim( str_replace( array( 'BOUNDARY=', 'boundary=', '"' ), '', $charset_content ) );
								$charset  = '';
							}

							// Avoid setting an empty $content_type.
						} elseif ( '' !== trim( $content ) ) {
							$content_type = trim( $content );
						}
						break;
					case 'cc':
						$cc = array_merge( (array) $cc, explode( ',', $content ) );
						break;
					case 'bcc':
						$bcc = array_merge( (array) $bcc, explode( ',', $content ) );
						break;
					case 'reply-to':
						$reply_to = array_merge( (array) $reply_to, explode( ',', $content ) );
						break;
					default:
						// Add it to our grand headers array.
						$headers[ trim( $name ) ] = trim( $content );
						break;
				}
			}
		}
	}

	// Empty out the values that may be set.
	$phpmailer->clearAllRecipients();
	$phpmailer->clearAttachments();
	$phpmailer->clearCustomHeaders();
	$phpmailer->clearReplyTos();

	// Set "From" name and email.

	// If we don't have a name from the input headers.
	if ( ! isset( $from_name ) ) {
		$from_name = 'WordPress';
	}

	/*
	 * If we don't have an email from the input headers, default to wordpress@$sitename
	 * Some hosts will block outgoing mail from this address if it doesn't exist,
	 * but there's no easy alternative. Defaulting to admin_email might appear to be
	 * another option, but some hosts may refuse to relay mail from an unknown domain.
	 * See https://core.trac.wordpress.org/ticket/5007.
	 */
	if ( ! isset( $from_email ) ) {
		// Get the site domain and get rid of www.
		$sitename = wp_parse_url( network_home_url(), PHP_URL_HOST );
		if ( 'www.' === substr( $sitename, 0, 4 ) ) {
			$sitename = substr( $sitename, 4 );
		}

		$from_email = 'wordpress@' . $sitename;
	}

	/**
	 * Filters the email address to send from.
	 *
	 * @since 2.2.0
	 *
	 * @param string $from_email Email address to send from.
	 */
	$from_email = apply_filters( 'wp_mail_from', $from_email );

	/**
	 * Filters the name to associate with the "from" email address.
	 *
	 * @since 2.3.0
	 *
	 * @param string $from_name Name associated with the "from" email address.
	 */
	$from_name = apply_filters( 'wp_mail_from_name', $from_name );

	try {
		$phpmailer->setFrom( $from_email, $from_name, false );
	} catch ( PHPMailer\PHPMailer\Exception $e ) {
		$mail_error_data                             = compact( 'to', 'subject', 'message', 'headers', 'attachments' );
		$mail_error_data['phpmailer_exception_code'] = $e->getCode();

		/** This filter is documented in wp-includes/pluggable.php */
		do_action( 'wp_mail_failed', new WP_Error( 'wp_mail_failed', $e->getMessage(), $mail_error_data ) );

		return false;
	}

	// Set mail's subject and body.
	$phpmailer->Subject = $subject;
	$phpmailer->Body    = $message;

	// Set destination addresses, using appropriate methods for handling addresses.
	$address_headers = compact( 'to', 'cc', 'bcc', 'reply_to' );

	foreach ( $address_headers as $address_header => $addresses ) {
		if ( empty( $addresses ) ) {
			continue;
		}

		foreach ( (array) $addresses as $address ) {
			try {
				// Break $recipient into name and address parts if in the format "Foo <bar@baz.com>".
				$recipient_name = '';

				if ( preg_match( '/(.*)<(.+)>/', $address, $matches ) ) {
					if ( count( $matches ) == 3 ) {
						$recipient_name = $matches[1];
						$address        = $matches[2];
					}
				}

				switch ( $address_header ) {
					case 'to':
						$phpmailer->addAddress( $address, $recipient_name );
						break;
					case 'cc':
						$phpmailer->addCc( $address, $recipient_name );
						break;
					case 'bcc':
						$phpmailer->addBcc( $address, $recipient_name );
						break;
					case 'reply_to':
						$phpmailer->addReplyTo( $address, $recipient_name );
						break;
				}
			} catch ( PHPMailer\PHPMailer\Exception $e ) {
				continue;
			}
		}
	}

	// Set to use PHP's mail().
	$phpmailer->isMail();

	// Set Content-Type and charset.

	// If we don't have a content-type from the input headers.
	if ( ! isset( $content_type ) ) {
		$content_type = 'text/plain';
	}

	/**
	 * Filters the wp_mail() content type.
	 *
	 * @since 2.3.0
	 *
	 * @param string $content_type Default wp_mail() content type.
	 */
	$content_type = apply_filters( 'wp_mail_content_type', $content_type );

	$phpmailer->ContentType = $content_type;

	// Set whether it's plaintext, depending on $content_type.
	if ( 'text/html' === $content_type ) {
		$phpmailer->isHTML( true );
	}

	// If we don't have a charset from the input headers.
	if ( ! isset( $charset ) ) {
		$charset = get_bloginfo( 'charset' );
	}

	/**
	 * Filters the default wp_mail() charset.
	 *
	 * @since 2.3.0
	 *
	 * @param string $charset Default email charset.
	 */
	$phpmailer->CharSet = apply_filters( 'wp_mail_charset', $charset );

	// Set custom headers.
	if ( ! empty( $headers ) ) {
		foreach ( (array) $headers as $name => $content ) {
			// Only add custom headers not added automatically by PHPMailer.
			if ( ! in_array( $name, array( 'MIME-Version', 'X-Mailer' ), true ) ) {
				try {
					$phpmailer->addCustomHeader( sprintf( '%1$s: %2$s', $name, $content ) );
				} catch ( PHPMailer\PHPMailer\Exception $e ) {
					continue;
				}
			}
		}

		if ( false !== stripos( $content_type, 'multipart' ) && ! empty( $boundary ) ) {
			$phpmailer->addCustomHeader( sprintf( 'Content-Type: %s; boundary="%s"', $content_type, $boundary ) );
		}
	}

	if ( ! empty( $attachments ) ) {
		foreach ( $attachments as $attachment ) {
			try {
				$phpmailer->addAttachment( $attachment );
			} catch ( PHPMailer\PHPMailer\Exception $e ) {
				continue;
			}
		}
	}

	/**
	 * Fires after PHPMailer is initialized.
	 *
	 * @since 2.2.0
	 *
	 * @param PHPMailer $phpmailer The PHPMailer instance (passed by reference).
	 */
	do_action_ref_array( 'phpmailer_init', array( &$phpmailer ) );

	$mail_data = compact( 'to', 'subject', 'message', 'headers', 'attachments' );

	// Send!
	try {

		// Only do pre send, not post send to emulate sending the email
		// This is the only difference from the standard `wp_mail()` function.
		$send = $phpmailer->preSend();

		/**
		 * Fires after PHPMailer has successfully sent a mail.
		 *
		 * The firing of this action does not necessarily mean that the recipient received the
		 * email successfully. It only means that the `send` method above was able to
		 * process the request without any errors.
		 *
		 * @since 5.9.0
		 *
		 * @param array $mail_data An array containing the mail recipient, subject, message, headers, and attachments.
		 */
		do_action( 'wp_mail_succeeded', $mail_data );

		return $send;
	} catch ( PHPMailer\PHPMailer\Exception $e ) {
		$mail_data['phpmailer_exception_code'] = $e->getCode();

		/**
		 * Fires after a PHPMailer\PHPMailer\Exception is caught.
		 *
		 * @since 4.4.0
		 *
		 * @param WP_Error $error A WP_Error object with the PHPMailer\PHPMailer\Exception message, and an array
		 *                        containing the mail recipient, subject, message, headers, and attachments.
		 */
		do_action( 'wp_mail_failed', new WP_Error( 'wp_mail_failed', $e->getMessage(), $mail_data ) );

		return false;
	}
}
