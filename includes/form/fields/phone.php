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
            'class'         => 'gh-tel',
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
}