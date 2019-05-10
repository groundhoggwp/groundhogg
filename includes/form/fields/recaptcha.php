<?php
namespace Groundhogg\Form\Fields;

class Recaptcha extends Field
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
        return 'submit';
    }
}