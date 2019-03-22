<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-03-21
 * Time: 12:11 PM
 */

if ( ! class_exists( 'PHPMailer' ) ){
    require_once ABSPATH . WPINC . '/class-phpmailer.php';
    require_once ABSPATH . WPINC . '/class-smtp.php';
}

class GH_SS_Mailer extends PHPMailer
{
    /**
     * Create a message and send it.
     * Uses the sending method specified by $Mailer.
     * @throws phpmailerException
     * @return boolean false on error - See the ErrorInfo property for details of the error.
     */
    public function send()
    {
        try {

            if (!$this->preSend()) {
                return false;
            }

            $message = $this->getSentMIMEMessage();

            if ( apply_filters( 'groundhogg/mailer/use_gh_ss', true ) ){
                $response = WPGH()->service_manager->request( 'emails/wp_mail/v2', [
                    'message' => $message,
                ], 'POST' );
            } else {
                $response = apply_filters( 'groundhogg/mailer/custom', $message );
            }

        } catch (phpmailerException $exc) {

            $this->mailHeader = '';
            $this->setError($exc->getMessage());

            if ($this->exceptions) {
                throw $exc;
            }

            return false;
        }

        if ( is_wp_error( $response ) ){

            if ( $response->get_error_code() === 'invalid_recipients' ){

                /* handle bounces */
                $data = (array) $response->get_error_data();

                $bounces = gisset_not_empty( $data, 'bounces' )? $data[ 'bounces' ] : [];

                if ( ! empty( $bounces ) ){
                    foreach ( $bounces as $email ){
                        if ( $contact = wpgh_get_contact( $email ) ){
                            $contact->change_marketing_preference( WPGH_HARD_BOUNCE );
                        }
                    }

                }

                $complaints = gisset_not_empty( $data, 'complaints' )? $data[ 'complaints' ] : [];

                if ( ! empty( $complaints ) ){
                    foreach ( $complaints as $email ){
                        if ( $contact = wpgh_get_contact( $email ) ){
                            $contact->change_marketing_preference( WPGH_COMPLAINED );
                        }
                    }

                }

            }

            throw new phpmailerException( $response->get_error_message(), self::STOP_CRITICAL );
        }

        return true;
    }
}