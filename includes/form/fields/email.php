<?php
namespace Groundhogg\Form\Fields;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-05-09
 * Time: 4:25 PM
 */

class Email extends Input
{

    public function get_default_args()
    {
        return [
            'type'          => 'email',
            'label'         => _x( 'Email *', 'form_default', 'groundhogg' ),
            'name'          => 'email',
            'id'            => 'email',
            'class'         => 'gh-email',
            'value'         => '',
            'placeholder'   => '',
            'attributes'    => '',
            'required'      => true,
        ];
    }

    /**
     * Get the name of the shortcode
     *
     * @return string
     */
    public function get_shortcode_name()
    {
        return 'email';
    }

    /**
     * Return the value that will be the final value.
     *
     * @param $input
     * @param $config
     * @return string
     */
    public static function validate( $input, $config )
    {
        $input = sanitize_email( $input ) ;

        if ( ! is_email( $input ) ){
            return new \WP_Error( 'invalid_email', __( 'Please provide a valid email address.', 'groundhogg' ) );
        }

        return apply_filters( 'groundhogg/form/fields/email/validate', $input );
    }
}