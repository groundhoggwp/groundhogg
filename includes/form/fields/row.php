<?php
namespace Groundhogg\Form\Fields;

class Row extends Field
{

    /**
     * @return array|mixed
     */
    public function get_default_args()
    {
        return [
            'id'    => '',
            'class' => ''
        ];
    }

    /**
     * Get the field ID
     *
     * @return string
     */
    public function get_id()
    {
        return $this->get_att( "id" );
    }

    /**
     * @return string
     */
    public function get_classes()
    {
        return esc_attr( $this->get_att( "class" ) );
    }

    public function get_content()
    {
        return $this->content;
    }

    /**
     * Render the field in HTML
     *
     * @return string
     */
    public function render()
    {
        return sprintf( "<div id='%s' class='gh-form-row clearfix %s'>%s</div>", $this->get_id(), $this->get_classes(), do_shortcode( $this->get_content() ) );
    }

    /**
     * Get the name of the shortcode
     *
     * @return string
     */
    public function get_shortcode_name()
    {
        return 'row';
    }
}