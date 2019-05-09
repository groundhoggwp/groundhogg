<?php
namespace Groundhogg\Form\Fields;

use function Groundhogg\words_to_key;

abstract class Input extends Field
{

    /**
     * @return array|mixed
     */
    public function get_default_args()
    {
        return [
            "type"          => "text",
            "label"         => "",
            "name"          => "",
            "id"            => "",
            "class"         => "",
            "value"         => "",
            "placeholder"   => "",
            "title"         => "",
            "attributes"    => "",
            "required"      => false,
        ];
    }

    /**
     * Get the field ID
     *
     * @return string
     */
    public function get_id()
    {
        return $this->get_att( "id", $this->get_name() );
    }

    /**
     * Get the Field name
     *
     * @return string
     */
    public function get_name()
    {
        return $this->get_att( "name", words_to_key( $this->get_label() ) );
    }

    /**
     * Get the field"s label
     *
     * @return string
     */
    public function get_label()
    {
        return $this->get_att( "label" );
    }

    /**
     * Get the field placeholder.
     *
     * @return string
     */
    public function get_placeholder()
    {
        return esc_attr( $this->get_att( "placeholder" ) );
    }

    /**
     * Get the field value.
     *
     * @return mixed
     */
    public function get_value()
    {
        if ( $this->should_auto_populate() ){
            return $this->get_data_from_contact( $this->get_name() );
        }

        return esc_attr( $this->get_att( "value" ) );
    }

    /**
     * @return string
     */
    public function get_classes()
    {
        return esc_attr( $this->get_att( "class" ) );
    }

    /**
     * @return string
     */
    public function get_type()
    {
        return esc_attr( $this->get_att( "type" )  );
    }

    /**
     * @return string
     */
    public function get_title()
    {
        return esc_attr( $this->get_att( "title", $this->get_label() ) );
    }

    /**
     * @return mixed
     */
    public function get_attributes()
    {
        return $this->get_att( "attributes" );
    }

    /**
     * Whether the field is required
     *
     * @return bool
     */
    public function is_required()
    {
        return filter_var( FILTER_VALIDATE_BOOLEAN, $this->get_att( "required" ) );
    }

    /**
     * Render the field in HTML
     *
     * @return string
     */
    public function render()
    {
        return sprintf(
            '<label class="gh-input-label">%1$s <input type="%2$s" name="%3$s" id="%4$s" class="gh-input %5$s" value="%6$s" placeholder="%7$s" title="%8$s" %9$s %10$s></label>',
            $this->get_label(),
            $this->get_type(),
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