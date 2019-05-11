<?php

namespace Groundhogg\Form\Fields;

use function Groundhogg\isset_not_empty;
use Groundhogg\Plugin;
use function Groundhogg\words_to_key;

class Dropdown extends Input
{

    protected $tag_map = [];

    public function get_default_args()
    {
        return [
            'label'         => _x( 'Select *', 'form_default', 'groundhogg' ),
            'name'          => '',
            'id'            => '',
            'class'         => '',
            'options'       => '',
            'attributes'    => '',
            'title'         => '',
            'default'       => _x( 'Please select one', 'form_default', 'groundhogg' ),
            'multiple'      => false,
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
        return 'dropdown';
    }

    public function get_config()
    {
        return array_merge( [ 'tag_mapping' => $this->get_tag_mapping() ], parent::get_config() );
    }

    /**
     * @param string $option
     * @param int $tag_id
     */
    protected function add_tag_mapping( $option = '', $tag_id = 0 )
    {
        $this->tag_map[ md5( $option ) ] = absint( $tag_id );
    }

    /**
     * @param $option
     * @return bool|mixed
     */
    public function get_tag_mapping( $option='' )
    {
        // Init the tag map...
        if ( empty( $this->tag_map ) ){
            $this->get_options();
        }

        if ( ! $option ){
            return $this->tag_map;
        }

        if ( isset_not_empty( $this->tag_map, md5( $option ) ) ){
            return $this->tag_map[ md5( $option ) ];
        }

        return false;
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

                $this->add_tag_mapping( $value, $tag );
            }

            if ( strpos( $option, '|' ) ){
                $parts = explode( '|', $value );
                $option = $parts[0];
            }

            $return[] = $option;
        }

        return $return;
    }

    public function is_multiple()
    {
        return filter_var( $this->get_att( 'multiple' ), FILTER_VALIDATE_BOOLEAN );
    }

    public function get_default()
    {
        return $this->get_att( 'default' );
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
        $options = $config[ 'atts' ][ 'options' ];

        $input = is_array( $input ) ? $input : [ $input ];

        foreach ( $input as $item ){

            // Match the input to the options string.
            if ( strpos( $options, $item ) === false ){
                return new \WP_Error( 'invalid_input', __( 'Please select a valid dropdown option.', 'groundhogg' ) );
            }
        }

        return implode( ', ', $input );
    }

    public function render()
    {

        $optionHTML = sprintf( "<option value=''>%s</option>", $this->get_default() );

        $options = $this->get_options();

        foreach ( $options as $i => $value ){

            $selected = '';

            if ( ! $this->is_multiple() && $this->get_value() ){
                $selected = $this->get_default() === $value ? 'selected' : '';
                $selected = $this->get_value() === $value ? 'selected' : $selected;
            } else if ( is_array( $this->get_value() ) ) {
                $selected = in_array( $value, $this->get_value() ) ? 'selected' : '';
            }

            $optionHTML .= sprintf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $value ), $selected, $value );
        }

        return sprintf(
            "<label class='gh-input-label'>%s <select name='%s' id='%s' class='gh-input %s' title='%s' %s %s %s>%s</select></label>",
            $this->get_label(),
            $this->get_name() . ( $this->is_multiple() ? '[]' : '' ),
            $this->get_id(),
            $this->get_classes(),
            $this->get_title(),
            $this->get_attributes(),
            $this->is_required() ? 'required' : '',
            $this->is_multiple() ? 'multiple' : '' ,
            $optionHTML
        );
    }
}