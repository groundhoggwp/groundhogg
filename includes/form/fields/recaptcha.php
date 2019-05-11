<?php
namespace Groundhogg\Form\Fields;

use Groundhogg\Plugin;

class Recaptcha extends Input
{

    /**
     * @return array|mixed
     */
    public function get_default_args()
    {
        return [
            'theme'         => false,
            'captcha-theme' => 'light',
            'size'          => false,
            'captcha-size'  => 'normal',
        ];
    }

    /**
     * @return string
     */
    public function get_theme()
    {
        return $this->get_att( 'theme', $this->get_att( 'captcha-theme' ) );
    }

    /**
     * @return string
     */
    public function get_size()
    {
        return $this->get_att( 'size', $this->get_att( 'captcha-size' ) );
    }

    public function get_name()
    {
        return 'g-recaptcha-response';
    }

    public function get_id()
    {
        return 'g-recaptcha-response';
    }

    /**
     * Render the field in HTML
     *
     * @return string
     */
    public function render()
    {
        wp_enqueue_script( 'google-recaptcha-v2', 'https://www.google.com/recaptcha/api.js', array(), true );
        return sprintf( '<div class="g-recaptcha" data-sitekey="%s" data-theme="%s" data-size="%s"></div>',
            get_option( 'gh_recaptcha_site_key', '' ),
            $this->get_theme(),
            $this->get_size()
        );
    }

    /**
     * Get the name of the shortcode
     *
     * @return string
     */
    public function get_shortcode_name()
    {
        return 'recaptcha';
    }

    /**
     * @param $input
     * @param $config
     * @return \WP_Error|true
     */
    public static function validate( $input, $config )
    {
        $file_name = sprintf(
            "https://www.google.com/recaptcha/api/siteverify?secret=%s&response=%s",
            Plugin::$instance->settings->get_option( 'gh_recaptcha_secret_key' ),
            $input
        );

        $verifyResponse = file_get_contents( $file_name );
        $responseData = json_decode( $verifyResponse );

        if( $responseData->success == false ){
            return new \WP_Error( 'captcha_verification_failed', _x( 'Failed reCaptcha verification. You are probably a robot.', 'submission_error', 'groundhogg' ) );
        }

        return true;
    }
}