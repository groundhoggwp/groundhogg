<?php
namespace Groundhogg\Form\Fields;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-05-09
 * Time: 4:25 PM
 */

class Phone extends Input
{

    public function get_default_args()
    {
        return [
            'type'          => 'tel',
            'label'         => _x( 'Phone *', 'form_default', 'groundhogg' ),
            'name'          => 'primary_phone',
            'id'            => 'primary_phone',
            'class'         => 'gh-tel gh-input',
            'value'         => '',
            'placeholder'   => '',
            'attributes'    => '',
            'required'      => false,
        ];
    }

    /**
     * Get the name of the shortcode
     *
     * @return string
     */
    public function get_shortcode_name()
    {
        return 'phone';
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
        if ( ! preg_match( '/^[+]?[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\.\/0-9]*$/', $input ) ){
            return new \WP_Error( 'invalid_phone_number', __( 'Please provide a valid number.', 'groundhogg' ) );
        }

        return apply_filters( 'groundhogg/form/fields/number/validate' , $input );
    }
}