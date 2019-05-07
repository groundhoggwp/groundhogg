<?php
namespace Groundhogg;
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-03-21
 * Time: 12:11 PM
 */

if ( ! class_exists( '\PHPMailer' ) ){
    require_once ABSPATH . WPINC . '/class-phpmailer.php';
    require_once ABSPATH . WPINC . '/class-smtp.php';
}

class GH_SS_Mailer extends \PHPMailer
{
    /**
     * Create a message and send it.
     * Uses the sending method specified by $Mailer.
     * @throws \phpmailerException
     * @return boolean false on error - See the ErrorInfo property for details of the error.
     */
    public function send()
    {
        try {

            if (!$this->preSend()) {
                return false;
            }

            $message = $this->getSentMIMEMessage();

            if ( apply_filters( 'groundhogg/gh_ss_mailer/use_gh_ss', true ) ){
                $response = Plugin::$instance->sending_service->request( 'emails/send', [
                    'message' => $message,
                ], 'POST' );
            } else {
                $response = apply_filters( 'groundhogg/gh_ss_mailer/send', $message );
            }

        } catch (\phpmailerException $exc) {

            $this->mailHeader = '';
            $this->setError($exc->getMessage());

            if ($this->exceptions) {
                throw $exc;
            }

            return false;
        }

        if ( is_wp_error( $response ) ){
            throw new \phpmailerException( $response->get_error_message(), self::STOP_CRITICAL );
        }

        return true;
    }
}