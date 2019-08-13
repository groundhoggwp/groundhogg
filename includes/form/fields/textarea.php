<?php
namespace Groundhogg\Form\Fields;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-05-09
 * Time: 4:25 PM
 */

class Textarea extends Input
{

    public function get_default_args()
    {
        return [
            'label'         => '',
            'name'          => '',
            'id'            => '',
            'class'         => '',
            'value'         => '',
            'placeholder'   => '',
            'title'         => '',
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
        return 'textarea';
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
        return apply_filters( 'groundhogg/form/fields/textarea/validate' , sanitize_textarea_field( $input ) );
    }

    /**
     * Render the HTML
     *
     * @return string
     */
    public function render()
    {
        return sprintf(
            '<label class="gh-input-label">%1$s <textarea name="%2$s" id="%3$s" class="gh-input %4$s" placeholder="%5$s" rows="4" title="%6$s" %7$s %8$s>%9$s</textarea></label>',
            $this->get_label(),
            $this->get_name(),
            $this->get_id(),
            $this->get_classes(),
            $this->get_placeholder(),
            $this->get_title(),
            $this->get_attributes(),
            $this->is_required() ? 'required' : '',
            $this->get_value()
        );
    }
}