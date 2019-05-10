<?php

namespace Groundhogg\Form\Fields;

use Groundhogg\Contact;
use function Groundhogg\get_array_var;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_request_var;
use Groundhogg\Plugin;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-05-09
 * Time: 3:26 PM
 */

abstract class Field
{

    /**
     * @var array
     */
    protected $atts;

    /**
     * @var string
     */
    protected $content;

    /**
     * @var Contact
     */
    protected $contact;

    /**
     * @var int the ID of the form
     */
    protected $form_id;

    /**
     * Field constructor.
     */
    public function __construct( $id = 0 )
    {
        if ( ! $id ){
            return;
        }

        // Set the ID of the associated form
        $this->form_id = $id;

        add_shortcode( $this->get_shortcode_name(), [ $this, 'shortcode' ] );

        if ( $this->should_auto_populate() ){
            $this->contact = get_contactdata( absint( get_request_var( 'contact' ) ) );
        }
    }

    /**
     * @return int
     */
    public function get_form_id()
    {
        return absint( $this->form_id );
    }

    /**
     * Get a value from the attributes
     *
     * @param $key
     * @param bool $default
     * @return mixed
     */
    public function get_att( $key, $default=false )
    {
        return get_array_var( $this->atts, $key, $default );
    }

    /**
     * Whether the field should auto-populate
     *
     * @return bool
     */
    public function should_auto_populate()
    {
        return  ( is_admin() && current_user_can( 'edit_contacts' ) && key_exists( 'contact', $_GET ) );
    }

    /**
     * Get contact data...
     *
     * @param $key
     * @return bool|mixed
     */
    public function get_data_from_contact( $key )
    {
        return $this->contact->$key;
    }

    /**
     * Get the name of the shortcode
     *
     * @return string
     */
    abstract public function get_shortcode_name();

    /**
     * Get the default shortcode attributes
     *
     * @return mixed
     */
    abstract public function get_default_args();

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

        $content = do_shortcode( $this->render() );

        return apply_filters( 'groundhogg/form/fields/' . $this->get_shortcode_name(), $content );
    }

    /**
     * Wraps any content...
     *
     * @param $content
     * @return string
     */
    protected function field_wrap( $content )
    {
        return sprintf( "<div class='gh-form-field'>%s</div>", $content );
    }

    /**
     * Render the HTML
     *
     * @return mixed
     */
    abstract public function render();
}