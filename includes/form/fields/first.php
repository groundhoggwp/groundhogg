<?php
namespace Groundhogg\Form\Fields;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-05-09
 * Time: 4:25 PM
 */

class First extends Input
{

    public function get_default_args()
    {
        return [
            'type'          => 'text',
            'label'         => _x( 'First Name *', 'form_default', 'groundhogg' ),
            'name'          => 'first_name',
            'id'            => 'first_name',
            'class'         => 'gh-first-name',
            'value'         => '',
            'placeholder'   => 'John',
            'pattern'       => '',
            'title'         => _x( 'Do not include numbers or special characters.', 'form_default', 'groundhogg' ),
            'required'      => false,
        ];
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
        if ( preg_match( '/[0-9_!¡?÷?¿\/\\+=@#$%ˆ&*(){}|~<>;:[\]]/u', $input ) ){

            if ( current_user_can( 'edit_funnels' ) ){
                return new \WP_Error( 'invalid_first_name', __( 'Names should not contain numbers or special symbols.', 'groundhogg' ) );
            }

            return new \WP_Error( 'invalid_first_name', __( 'Please provide a valid first name.', 'groundhogg' ) );

        }

        return apply_filters( 'groundhogg/form/fields/first/validate' , sanitize_textarea_field( $input ) );
    }

    /**
     * Get the name of the shortcode
     *
     * @return string
     */
    public function get_shortcode_name()
    {
        return 'first';
    }
}