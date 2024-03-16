<?php

namespace Groundhogg\Mailer;

use Groundhogg\GH_Mailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Doesn't to postSend, only preSend
 */
class Log_Only extends GH_Mailer {

	/**
	 * Don't actually send the email
	 *
	 * @throws Exception
	 * @return bool
	 */
	public function send() {
		try {
			if (!$this->preSend()) {
				return false;
			}

			// Just return true
			return true;
		} catch (Exception $exc) {
			$this->mailHeader = '';
			$this->setError($exc->getMessage());
			if ($this->exceptions) {
				throw $exc;
			}

			return false;
		}
	}
}
