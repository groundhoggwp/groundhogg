<?php
namespace Groundhogg\Form\Fields;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-05-09
 * Time: 4:25 PM
 */

class Checkbox extends Input
{
    public function get_default_args()
    {
        return [
            'label'         => '',
            'name'          => '',
            'id'            => '',
            'class'         => '',
            'value'         => '1',
            'tag'           => 0,
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
        return 'checkbox';
    }

    /**
     * @return string
     */
    public function render()
    {
        return sprintf(
            '<label class="gh-input-label"><input type="checkbox" name="%2$s" id="%3$s" class="gh-input %4$s" value="%5$s" placeholder="%6$s" title="%7$s" %8$s %9$s> %1$s</label>',
            $this->get_label(),
            $this->get_name(),
            $this->get_id(),
            $this->get_classes(),
            $this->get_value(),
            $this->get_placeholder(),
            $this->get_title(),
            $this->get_attributes(),
            $this->is_required() ? 'required' : ''
        );
    }
}