<?php
namespace Groundhogg\Form\Fields;

use Groundhogg\Form\Form;
use function Groundhogg\get_db;
use function Groundhogg\html;
use Groundhogg\Plugin;
use function Groundhogg\words_to_key;

abstract class Input extends Field
{

    public function __construct($id = 0)
    {

        // No sense in updating for every single visitor visit... not efficient
        if ( current_user_can( 'edit_funnels' ) ){
            add_filter( 'groundhogg/form/shortcode', [ self::class, 'save_config' ], 99, 2 );
        }

        parent::__construct($id);
    }

    protected static $configurations = [];

    /**
     * @param $form Form
     * @param $html string
     *
     * @return string
     */
    public static function save_config( $html, $form )
    {
        $config = self::$configurations[ $form->get_id() ];

        get_db( 'stepmeta' )->update_meta( $form->get_id(), 'config', $config );

        return $html;
    }

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
            'callback'      => 'sanitize_text_field',
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

        if ( Plugin::$instance->submission_handler->has_errors() ){
            return Plugin::$instance->submission_handler->get_posted_data( $this->get_name() );
        }

        return esc_attr( $this->get_att( "value" ) );
    }

    /**
     * Return the value that will be the final value.
     *
     * @param $input string|array
     * @param $config array
     * @return string
     */
    public static function validate( $input, $config )
    {
        return apply_filters( 'groundhogg/form/fields/input/validate' , sanitize_text_field( $input ) );
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
        return filter_var( $this->get_att( "required" ), FILTER_VALIDATE_BOOLEAN );
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

        $this->atts = map_deep( shortcode_atts( $this->get_default_args(), $atts, $this->get_shortcode_name() ), 'wp_specialchars_decode' );

        $content = do_shortcode( $this->field_wrap( $this->render() ) );

        $this->add_field_config();

        return apply_filters( 'groundhogg/form/fields/' . $this->get_shortcode_name(), $content );
    }

    public function add_field_config()
    {
        self::$configurations[ $this->get_form_id() ][ $this->get_name() ] = $this->get_config();
    }

    public function get_config()
    {
        return [
            'name' => $this->get_name(),
            'required' => $this->is_required(),
            'label' => $this->get_label(),
            'callback' => wp_slash( [ get_class( $this ) , 'validate' ] ),
            'type' => $this->get_shortcode_name(),
            'atts' => $this->atts,
        ];
    }

    protected function parse_atts( $atts = [] )
    {
        $parsed = [];
        foreach ( $atts as $att => $val ){
            if ( ! empty( $val ) ){
                $parsed[ $att ] = $val;
            }
        }

        return $parsed;
    }

    public function has_label()
    {
        $label = $this->get_label();
        return ! empty( $label );
    }


    /**
     * Render the field in HTML
     *
     * @return string
     */
    public function render()
    {

        $atts = [
            'type'  => $this->get_type(),
            'name'  => $this->get_name(),
            'id'    => $this->get_id(),
            'class' => $this->get_classes() . ' gh-input',
            'value' => $this->get_value(),
            'placeholder' => $this->get_placeholder(),
            'title' => $this->get_title(),
            'required' => $this->is_required(),
            'pattern' => $this->get_att( 'pattern' )
        ];

        // No label, do not wrap in label element.
        if ( ! $this->has_label() ){
            return html()->input( $atts );
        }

        return html()->wrap([
            $this->get_label(),
            html()->input( $atts )
        ],
            'label',
            [
                'class' => 'gh-input-label'
            ]
        );

    }
}