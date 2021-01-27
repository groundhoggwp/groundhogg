<?php

namespace Groundhogg;

use Groundhogg\DB\Email_Log;

class Email_Log_Item extends Base_Object {

	/**
	 * Handle post setup actions...
	 */
	protected function post_setup() {

		$int_props = [
			'retries'
		];

		foreach ( $int_props as $prop ){
			$this->$prop = intval( $this->$prop );
		}

	}

	/**
	 * Get the log DB
	 *
	 * @return Email_Log
	 */
	protected function get_db() {
		return get_db( 'email_log' );
	}

	/**
	 * Retry to send the email.
	 *
	 * @return bool
	 */
	public function retry() {

		// Compile headers!
		$headers = [];

		foreach ( $this->headers as $header ){
			$headers[] = sprintf( "%s: %s\n", $header[0], $header[1] );
		}

		$message_type = $this->message_type;
		$service      = \Groundhogg_Email_Services::get_saved_service( $message_type );

		Email_Logger::

		$result = \Groundhogg_Email_Services::send( $service, $this->recipients, $this->subject, $this->content, $headers );

		return $result;
	}

	/**
	 * Catch retry failed error
	 *
	 * @param $error \WP_Error
	 */
	public function catch_mail_error( $error ) {
		$this->add_error( $error );
	}

}