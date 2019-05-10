<?php
namespace Groundhogg\Form\Fields;

use Groundhogg\Form\FormV2;
use function Groundhogg\get_db;
use Groundhogg\Plugin;
use function Groundhogg\words_to_key;

abstract class Input extends Field
{

    public function __construct(int $id = 0)
    {

        add_action( 'groundhogg/form/shortcode/after', [ self::class, 'save_config' ] );

        parent::__construct($id);
    }

    protected static $configurations = [];

    /**
     * @param $form FormV2
     */
    public static function save_config( $form )
    {
        $config = self::$configurations[ $form->get_id() ];

        var_dump( $config );

        get_db( 'stepmeta' )->update_meta( $form->get_id(), 'config', $config );
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

    public function get_callback()
    {
        return $this->get_att( 'callback', 'sanitize_text_field' );
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
        $this->atts = shortcode_atts( $this->get_default_args(), $atts, $this->get_shortcode_name() );

        $content = do_shortcode( $this->field_wrap( $this->render() ) );

        $this->add_field_config();

//        var_dump( self::$configurations );

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
            'callback' => $this->get_callback(),
            'type' => $this->get_shortcode_name(),
        ];
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