<?php

namespace Groundhogg\Form\Fields;

use Groundhogg\Contact;
use function Groundhogg\get_array_var;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_request_var;

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
     * @var Contact
     */
    protected $contact;

    /**
     * Field constructor.
     */
    public function __construct()
    {
        add_shortcode( $this->get_shortcode_name(), [ $this, 'shortcode' ] );

        if ( $this->should_auto_populate() ){
            $this->contact = get_contactdata( absint( get_request_var( 'contact' ) ) );
        }
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
        return is_admin() && current_user_can( 'edit_contacts' ) && key_exists( 'contact', $_GET );
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
    final public function shortcode( $atts, $content = '' )
    {
        $this->atts = shortcode_atts( $this->get_default_args(), $atts, $this->get_shortcode_name() );
        return do_shortcode( $this->field_wrap( $this->render() ) );
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