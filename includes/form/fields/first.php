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
            'placeholder'   => '',
            'attributes'    => 'pattern="[A-Za-z \-\']+"',
            'title'         => _x( 'Do not include numbers or special characters.', 'form_default', 'groundhogg' ),
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
        return 'first';
    }
}