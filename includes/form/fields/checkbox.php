<?php
namespace Groundhogg\Form\Fields;

use Groundhogg\Plugin;

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
            'callback'      => 'sanitize_textarea_field',
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

    public function is_checked()
    {
        $a = $this->should_auto_populate() && $this->get_value() === $this->get_data_from_contact( $this->get_name() );
        $b = Plugin::$instance->submission_handler->has_errors() && Plugin::$instance->submission_handler->get_posted_data( $this->get_name() );

        return $a || $b;
    }

    public function get_config()
    {
        return array_merge( [ 'tag_mapping' => [
            md5( $this->get_value() ) => $this->get_att( 'tag' )
        ] ], parent::get_config() );
    }

    /**
     * @return string
     */
    public function render()
    {
        return sprintf(
            "<label class='gh-checkbox-label'><input type='checkbox' name='%s' id='%s' class='gh-checkbox %s' value='%s' title='%s' %s %s %s> %s</label>",
            $this->get_name(),
            $this->get_id(),
            $this->get_classes(),
            $this->get_value(),
            $this->get_title(),
            $this->get_attributes(),
            $this->is_required() ? 'required' : '' ,
            $this->is_checked() ? 'checked' : '' ,
            $this->get_label()
        );
    }
}