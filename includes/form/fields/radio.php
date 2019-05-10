<?php

namespace Groundhogg\Form\Fields;

use function Groundhogg\isset_not_empty;
use Groundhogg\Plugin;
use function Groundhogg\words_to_key;

class Radio extends Select
{

    public function get_default_args()
    {
        return [
            'label'         => _x( 'Radio *', 'form_default', 'groundhogg' ),
            'name'          => '',
            'id'            => '',
            'class'         => '',
            'options'       => '',
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
        return 'radio';
    }

    /**
     * Get the select options
     *
     * @return array
     */
    public function get_options()
    {
        $options = $this->get_att( 'options', [] );

        if ( is_string( $options ) ){
            $options = explode( ',', $options );
        }

        $return = [];

        foreach ( $options as $i => $option  ){

            $value = is_string( $i ) ? $i : $option;

            /**
             * Check if tag should be applied
             *
             * @since 1.1
             */
            if ( strpos( $value, '|' ) ){
                $parts = explode( '|', $value );
                $value = $parts[0];
                $tag = intval( $parts[1] );

                $this->add_tag_mapping( $option, $tag );
            }

            if ( strpos( $option, '|' ) ){
                $parts = explode( '|', $value );
                $option = $parts[0];
            }

            $return[] = $option;
        }

        return $return;
    }

    public function render()
    {
        $options = $this->get_options();

        $optionHTML = "";

        foreach ( $options as $i => $value ){

            $checked = $this->get_value() === $value ? 'checked' : '';

            $optionHTML .= sprintf( "<div class='gh-radio-wrapper'><label class='gh-radio-label'><input class='gh-radio %s' type='radio' name='%s' id='%s' value='%s' %s %s> %s</label></div>",
                $this->get_classes(),
                $this->get_name(),
                $this->get_id() . '-' . $i,
                esc_attr( $value ),
                $this->is_required() ? 'required' : '',
                $checked,
                $value
            );
        }

        return $optionHTML;
    }
}