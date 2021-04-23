<?php

namespace Groundhogg;

use GroundhoggSMS\Classes\SMS;

class Utils {
	/**
	 * @var Location
	 */
	public $location;

	/**
	 * @var HTML
	 */
	public $html;

	/**
	 * @var Files
	 */
	public $files;

	/**
	 * @var Date_Time
	 */
	public $date_time;

	/**
	 * @var Base_Object[]
	 */
	protected static $object_cache = [];

	/**
	 * Map for type to Class Name
	 *
	 * @var string[]
	 */
	protected static $class_object_map = [
		'contact'   => '\Groundhogg\Contact',
		'funnel'    => '\Groundhogg\Funnel',
		'step'      => '\Groundhogg\Step',
		'event'     => '\Groundhogg\Event',
		'email'     => '\Groundhogg\Email',
		'broadcast' => '\Groundhogg\Broadcast',
		'tag'       => '\Groundhogg\Tag',
	];

	/**
	 * Utils constructor.
	 */
	public function __construct() {

		$this->location  = new Location();
		$this->html      = new HTML();
		$this->files     = new Files();
		$this->date_time = new Date_Time();

	}

	/**
	 * Get an object from the cache or return a new instance of the object.
	 *
	 * @param int         $id
	 * @param string|bool $by
	 * @param string      $object
	 * @param bool        $get_from_cache
	 *
	 * @return false|Base_Object|Base_Object_With_Meta
	 */
	public function get_object( $id = 0, $by = 'ID', $object = 'contact', $get_from_cache = true ) {


		$cache_key = $id . ':' . $by . ':' . $object;
//
//		if ( $get_from_cache ) {
//			$cache_value = wp_cache_get( $cache_key, 'groundhogg/objects' );
//			if ( $cache_value ){
//				return $cache_value;
//			}
//		}

		$class = isset_not_empty( self::$class_object_map, $object ) ? self::$class_object_map[ $object ] : ucfirst( $object );
		$class = apply_filters( 'groundhogg/utils/get_object', $class, $object );

		if ( ! $class ) {
			return false;
		}

		/**
		 * @type $object Base_Object
		 */
		$object = new $class( $id, $by );

		if ( $object && $object->exists() ) {
			wp_cache_set( $cache_key, $object, 'groundhogg/objects' );

			return $object;
		}

		return false;
	}

	/**
	 * Get a contact!
	 *
	 * @param      $id_or_email
	 * @param bool $by_user_id
	 * @param bool $get_from_cache
	 *
	 * @return Contact|false
	 * @deprecated 2.4
	 */
	public function get_contact( $id_or_email, $by_user_id = false, $get_from_cache = true ) {
		return $this->get_object( $id_or_email, $by_user_id, 'contact', $get_from_cache );
	}

	/**
	 * @param      $id
	 * @param bool $get_from_cache
	 *
	 * @return Step
	 * @deprecated 2.4
	 */
	public function get_step( $id, $get_from_cache = true ) {
		return $this->get_object( $id, 'ID', 'step', $get_from_cache );
	}

	/**
	 * @param      $id
	 * @param bool $get_from_cache
	 *
	 * @return Event
	 * @deprecated Use Event object new Event()
	 */
	public function get_event( $id, $get_from_cache = true ) {
		return $this->get_object( $id, 'ID', 'event', $get_from_cache );
	}

	/**
	 * @param      $id
	 * @param bool $get_from_cache
	 *
	 * @return Funnel
	 * @deprecated 2.4
	 */
	public function get_funnel( $id, $get_from_cache = true ) {
		return $this->get_object( $id, 'ID', 'funnel', $get_from_cache );
	}

	/**
	 * @param      $id
	 * @param bool $get_from_cache
	 *
	 * @return Email
	 * @deprecated 2.4
	 */
	public function get_email( $id, $get_from_cache = true ) {
		return $this->get_object( $id, 'ID', 'email', $get_from_cache );
	}

	/**
	 * @param      $id
	 * @param bool $get_from_cache
	 *
	 * @return Sms
	 * @deprecated 2.1.14
	 *
	 * @deprecated 2.1.14
	 */
	public function get_sms( $id, $get_from_cache = true ) {
		return $this->get_object( $id, 'ID', 'sms', $get_from_cache );
	}

	static $secret_key = '';
	static $secret_iv = '';
	static $encrypt_method = "AES-256-CBC";

	/**
	 * Provides a quick way to instill a contact session and tie events to a particluar contact.
	 *
	 * @param        $string |int the thing to encrypt/decrypt
	 * @param string $action whether to encrypt or decrypt
	 *
	 * @return bool|string false if failur, the result and success.
	 */
	public function encrypt_decrypt( $string, $action = 'e' ) {

		// you may change these values to your own
		$encrypt_method = "AES-256-CBC";

		if ( ! self::$secret_key ){

			self::$secret_key = get_option( 'gh_secret_key', false );

			if ( ! self::$secret_key ){
				self::$secret_key = bin2hex( openssl_random_pseudo_bytes( 32 ) );
				update_option( 'gh_secret_key', self::$secret_key );
			}
		}

		if ( ! self::$secret_iv ){

			self::$secret_iv = get_option( 'gh_secret_key', false );

			if ( ! self::$secret_iv ){
				self::$secret_iv = bin2hex( openssl_random_pseudo_bytes( 16 ) );
				update_option( 'gh_secret_key', self::$secret_iv );
			}
		}

		if ( in_array( self::$encrypt_method, openssl_get_cipher_methods() ) ) {

			//backwards compat
			if ( ctype_xdigit( self::$secret_key ) ) {
				self::$secret_key = hex2bin( self::$secret_key );
				self::$secret_iv  = hex2bin( self::$secret_iv );
			}

			$output = false;
			$key    = substr( hash( 'sha256', self::$secret_key ), 0, 32 );
			$iv     = substr( hash( 'sha256', self::$secret_iv ), 0, 16 );

			if ( $action == 'e' ) {
				$output = base64_encode( openssl_encrypt( $string, $encrypt_method, $key, 0, $iv ) );
			} else if ( $action == 'd' ) {
				$output = openssl_decrypt( base64_decode( $string ), $encrypt_method, $key, 0, $iv );
			}
		} else {
			if ( $action == 'e' ) {
				$output = base64_encode( $string );
			} else if ( $action == 'd' ) {
				$output = base64_decode( $string );
			}
		}

		return $output;
	}


}