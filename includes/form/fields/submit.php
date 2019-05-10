<?php
namespace Groundhogg\Form\Fields;

class Submit extends Field
{

    /**
     * @return array|mixed
     */
    public function get_default_args()
    {
        return [
            'id'            => 'gh-submit',
            'class'         => 'gh-submit',
            'text'          => __( 'Submit' ),
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

    /**
     * @return string
     */
    public function get_text()
    {
        return $this->get_att( 'text' );
    }

    /**
     * Render the field in HTML
     *
     * @return string
     */
    public function render()
    {
        return sprintf(
            "<div class='gh-button-wrapper'><button type='submit' id='%s' class='gh-submit-button %s'>%s</button></div>",
            $this->get_id(),
            $this->get_classes(),
            $this->get_text()
        );
    }

    /**
     * @param $atts array the shortcode atts
     * @param string $content
     *
     * @return string
     */
    public function shortcode( $atts, $content = '' )
    {
        $this->content = $content;
        $this->atts = shortcode_atts( $this->get_default_args(), $atts, $this->get_shortcode_name() );

        $content = do_shortcode( $this->field_wrap( $this->render() ) );

        return apply_filters( 'groundhogg/form/fields/' . $this->get_shortcode_name(), $content );
    }

    /**
     * Get the name of the shortcode
     *
     * @return string
     */
    public function get_shortcode_name()
    {
        return 'submit';
    }
}