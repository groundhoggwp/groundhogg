<?php

namespace Groundhogg;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if ( ! class_exists( '\PHPMailer\PHPMailer\PHPMailer' ) ) {
	require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
	require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
	require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
}

/**
 * Other sending services should inherit this class
 */
class GH_Mailer extends PHPMailer {

	/**
	 * Compat for list-unsubscribe and related headers to avoid encoding.
	 *
	 * @return string
	 */
	public function createHeader() {

		// Remove List-* from custom headers because encoding them breaks functionality in some clients
		$ListUnsubscribeHeaders = array_filter_splice( $this->CustomHeader, function ( $header ){
			return str_starts_with( strtolower( $header[0] ), 'list-' );
		} );

		// The original header
		$result = parent::createHeader();

		// Add list unsubscribe headers here so that they don't get encoded because iCloud does not decode them.
		foreach ($ListUnsubscribeHeaders as $header) {
			$result .= $this->headerLine(
				trim($header[0]),
				trim($header[1]) // NO ENCODING
			);
		}

		return $result;
	}
}

class GH_SMTP extends SMTP{

}

class GH_Mailer_Exception extends Exception{

}
