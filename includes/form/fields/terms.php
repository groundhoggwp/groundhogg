<?php
namespace Groundhogg\Form\Fields;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-05-09
 * Time: 4:25 PM
 */

class Terms extends Checkbox
{
    public function get_default_args()
    {
        return [
            'label'         => _x( 'I agree to the <i>terms of service</i>.', 'form_default', 'groundhogg' ),
            'name'          => 'agree_terms',
            'id'            => 'agree_terms',
            'class'         => 'gh-terms',
            'value'         => 'yes',
            'tag'           => 0,
            'title'         => _x( 'Please agree to the terms of service.', 'form_default', 'groundhogg' ),
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
        return 'terms';
    }
}