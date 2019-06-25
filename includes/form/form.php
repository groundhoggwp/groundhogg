<?php

namespace Groundhogg\Form;

use function Groundhogg\array_to_atts;
use function Groundhogg\encrypt;
use Groundhogg\Form\Fields\Checkbox;
use Groundhogg\Form\Fields\Column;
use Groundhogg\Form\Fields\Date;
use Groundhogg\Form\Fields\Email;
use Groundhogg\Form\Fields\Field;
use Groundhogg\Form\Fields\File;
use Groundhogg\Form\Fields\First;
use Groundhogg\Form\Fields\GDPR;
use Groundhogg\Form\Fields\Last;
use Groundhogg\Form\Fields\Number;
use Groundhogg\Form\Fields\Phone;
use Groundhogg\Form\Fields\Radio;
use Groundhogg\Form\Fields\Recaptcha;
use Groundhogg\Form\Fields\Row;
use Groundhogg\Form\Fields\Dropdown;
use Groundhogg\Form\Fields\Submit;
use Groundhogg\Form\Fields\Terms;
use Groundhogg\Form\Fields\Text;
use Groundhogg\Form\Fields\Textarea;
use Groundhogg\Form\Fields\Time;
use function Groundhogg\get_array_var;
use function Groundhogg\get_db;
use function Groundhogg\html;
use function Groundhogg\isset_not_empty;
use Groundhogg\Plugin;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-05-10
 * Time: 9:51 AM
 */

class Form {

    protected $attributes = [];

    /**
     * Manager constructor.
     */
    public function __construct( $atts )
    {
        $this->attributes = shortcode_atts( [
            'class'     => '',
            'id'        => 0
        ], $atts);

        $this->init_fields();
    }

    /**
     * @return int
     */
    public function get_id()
    {
        return absint( get_array_var( $this->attributes, 'id' ) );
    }
    
    /**
     * Setup the base Fields for the plugin
     */
    protected function init_fields()
    {
        $this->column = new Column( $this->get_id() );
        $this->row = new Row( $this->get_id() );
        $this->text = new Text( $this->get_id() );
        $this->textarea = new Textarea( $this->get_id());
        $this->first = new First($this->get_id());
        $this->last = new Last($this->get_id());
        $this->email = new Email($this->get_id());
        $this->phone = new Phone($this->get_id());
        $this->number = new Number($this->get_id());
        $this->date = new Date($this->get_id());
        $this->time = new Time($this->get_id());
        $this->file = new File($this->get_id());
        $this->select = new Dropdown($this->get_id());
        $this->radio = new Radio($this->get_id());
        $this->checkbox = new Checkbox($this->get_id());
        $this->terms = new Terms($this->get_id());
        $this->gdpr = new GDPR($this->get_id());
        $this->recaptcha = new Recaptcha($this->get_id());
        $this->submint = new Submit($this->get_id());
        do_action( 'groundhogg/form/fields/init', $this );
    }


    /**
     * List of fields
     *
     * @var Field[]
     */
    protected $fields = [];
    
    /**
     * Set the data to the given value
     *
     * @param $key string
     * @return Field
     */
    public function get_field( $key ){
        return $this->$key;
    }

    /**
     * Magic get method
     *
     * @param $key string
     * @return Field|false
     */
    public function __get( $key )
    {
        if ( isset_not_empty( $this->fields, $key ) ){
            return $this->fields[ $key ];
        }

        return false;
    }


    /**
     * Set the data to the given value
     *
     * @param $key string
     * @param $value Field
     */
    public function __set( $key, $value )
    {
        $this->fields[ $key ] = $value;
    }

    public function get_shortcode()
    {
        return sprintf('[gh_form id="%d"]', $this->get_id() );
    }

    public function get_iframe_embed_code()
    {
        $form_iframe_url = site_url( sprintf( 'gh/forms/iframe/%s/', urlencode( encrypt( $this->get_id() ) ) ) );
        $script = sprintf('<script id="%s" type="text/javascript" src="%s"></script>', 'groundhogg_form_' . $this->get_id(), $form_iframe_url );

        return $script;
    }

    public function get_submission_url()
    {
        return site_url( sprintf( 'gh/forms/%s/submit/', urlencode( encrypt( $this->get_id() ) ) ) );
    }

    public function get_html_embed_code()
    {
        $form = html()->e( 'link', [ 'rel' => 'stylesheet', 'href' => GROUNDHOGG_ASSETS_URL . 'css/frontend/form.css' ] );

        $form .= '<div class="gh-form-wrapper">';

        $atts = [
            'method' => 'post',
            'class'  => 'gh-form ' . $this->attributes[ 'class' ],
            'target' => '_parent',
            'action' => $this->get_submission_url(),
            'enctype' => 'multipart/form-data'
        ];

        $form .= sprintf( "<form %s>", array_to_atts( $atts ) );

        if ( ! empty( $this->attributes[ 'id' ] ) ){
            $form .= "<input type='hidden' name='gh_submit_form' value='" . $this->get_id() . "'>";
        }

        $step = Plugin::$instance->utils->get_step( $this->get_id() );

        if ( ! $step ){
            return sprintf( "<p>%s</p>" , __( "<b>Configuration Error:</b> This form has been deleted." ) );
        }

        do_action( 'groundhogg/form/shortcode/before', $this );

        $content = do_shortcode( $step->get_meta( 'form' ) );

        do_action( 'groundhogg/form/shortcode/after', $this );

        if ( empty( $content ) ){
            return sprintf( "<p>%s</p>" , __( "<b>Configuration Error:</b> This form has either been deleted or has not content yet." ) );
        }

        $form .= $content;

        $form .= '</form>';

        $form .= '</div>';

        $form = apply_filters( 'groundhogg/form/after', $form, $this );

        return $form;
    }


    /**
     * Do the shortcode
     *
     * @param $atts
     * @param $content
     * @return string
     */
    public function shortcode()
    {

        wp_enqueue_style( 'groundhogg-form' );

        $form = '<div class="gh-form-wrapper">';

        /* Errors from a previous submission */
        if ( Plugin::$instance->submission_handler->has_errors() ){

            $errors = Plugin::$instance->submission_handler->get_errors();
            $err_html = "";

            foreach ( $errors as $error ){
                $err_html .= sprintf( '<li id="%s">%s</li>', $error->get_error_code(), $error->get_error_message() );
            }

            $err_html = sprintf( "<ul class='gh-form-errors'>%s</ul>", $err_html );
            $form .= sprintf( "<div class='gh-form-errors-wrapper'>%s</div>", $err_html );

        }

        $atts = [
            'method' => 'post',
            'class'  => 'gh-form ' . $this->attributes[ 'class' ],
            'target' => '_parent',
            'enctype' => 'multipart/form-data'
        ];

        if ( get_query_var( 'doing_iframe' ) ){
            $atts[ 'action' ] = $this->get_submission_url();
        }

        $form .= sprintf( "<form %s>", array_to_atts( $atts ) );

        if ( ! empty( $this->attributes[ 'id' ] ) ){
            $form .= "<input type='hidden' name='gh_submit_form' value='" . $this->get_id() . "'>";
        }

        $step = Plugin::$instance->utils->get_step( $this->get_id() );

        if ( ! $step ){
            return sprintf( "<p>%s</p>" , __( "<b>Configuration Error:</b> This form has been deleted." ) );
        }

        do_action( 'groundhogg/form/shortcode/before', $this );

        $content = do_shortcode( $step->get_meta( 'form' ) );

        do_action( 'groundhogg/form/shortcode/after', $this );

        if ( empty( $content ) ){
            return sprintf( "<p>%s</p>" , __( "<b>Configuration Error:</b> This form has either been deleted or has not content yet." ) );
        }

        $form .= $content;

        $form .= '</form>';

        if ( is_user_logged_in() && current_user_can( 'edit_funnels' ) ){
            $form .= sprintf( "<div class='gh-form-edit-link'><a href='%s'>%s</a></div>", admin_url( 'admin.php?page=gh_funnels&action=edit&funnel=' . $step->get_funnel_id() ), __( '(Edit Form)' ) );
        }

        $form .= '</div>';

        $form = apply_filters( 'groundhogg/form/after', $form, $this );

        return $form;
    }

    /**
     * Just return the shortcode
     *
     * @return string
     */
    public function __toString()
    {
        return $this->shortcode();
    }
    
}