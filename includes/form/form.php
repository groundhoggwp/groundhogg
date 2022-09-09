<?php

namespace Groundhogg\Form;

use Groundhogg\Form\Fields\Birthday;
use Groundhogg\Form\Fields\Custom_Field;
use Groundhogg\Step;
use function Groundhogg\admin_page_url;
use function Groundhogg\array_to_atts;
use function Groundhogg\do_replacements;
use function Groundhogg\encrypt;
use Groundhogg\Form\Fields\Address;
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
use function Groundhogg\form_errors;
use function Groundhogg\get_array_var;
use function Groundhogg\get_db;
use function Groundhogg\html;
use function Groundhogg\isset_not_empty;
use Groundhogg\Plugin;
use function Groundhogg\managed_page_url;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-05-10
 * Time: 9:51 AM
 */
class Form implements \JsonSerializable {

	public $step;
	protected $attributes = [];
	protected $uniqid;

	/**
	 * Manager constructor.
	 */
	public function __construct( $atts ) {
		$this->attributes = shortcode_atts( [
			'class' => '',
			'id'    => 0
		], $atts );

		$this->step   = new Step( $atts['id'] );
		$this->uniqid = uniqid( 'form_' );
		$this->init_fields();
	}


	/**
	 * @return int
	 */
	public function get_id() {
		return absint( get_array_var( $this->attributes, 'id' ) );
	}

	/**
	 * Setup the base Fields for the plugin
	 */
	protected function init_fields() {
		$this->column    = new Column( $this->get_id() );
		$this->row       = new Row( $this->get_id() );
		$this->text      = new Text( $this->get_id() );
		$this->textarea  = new Textarea( $this->get_id() );
		$this->first     = new First( $this->get_id() );
		$this->last      = new Last( $this->get_id() );
		$this->email     = new Email( $this->get_id() );
		$this->phone     = new Phone( $this->get_id() );
		$this->number    = new Number( $this->get_id() );
		$this->date      = new Date( $this->get_id() );
		$this->time      = new Time( $this->get_id() );
		$this->file      = new File( $this->get_id() );
		$this->select    = new Dropdown( $this->get_id() );
		$this->radio     = new Radio( $this->get_id() );
		$this->checkbox  = new Checkbox( $this->get_id() );
		$this->terms     = new Terms( $this->get_id() );
		$this->gdpr      = new GDPR( $this->get_id() );
		$this->address   = new Address( $this->get_id() );
		$this->recaptcha = new Recaptcha( $this->get_id() );
		$this->submit    = new Submit( $this->get_id() );
		$this->birthday  = new Birthday( $this->get_id() );
		$this->custom    = new Custom_Field( $this->get_id() );

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
	 *
	 * @return Field
	 */
	public function get_field( $key ) {
		return $this->$key;
	}

	/**
	 * Magic get method
	 *
	 * @param $key string
	 *
	 * @return Field|false
	 */
	public function __get( $key ) {
		if ( isset_not_empty( $this->fields, $key ) ) {
			return $this->fields[ $key ];
		}

		return false;
	}


	/**
	 * Set the data to the given value
	 *
	 * @param $key   string
	 * @param $value Field
	 */
	public function __set( $key, $value ) {
		$this->fields[ $key ] = $value;
	}

	public function get_shortcode() {
		return sprintf( '[gh_form id="%d"]', $this->get_id() );
	}

	public function get_iframe_embed_code() {
		$form_iframe_url = managed_page_url( sprintf( 'forms/iframe/%s/', urlencode( encrypt( $this->get_id() ) ) ) );

		return sprintf( '<script id="%s" type="text/javascript" src="%s"></script>', 'groundhogg_form_' . $this->get_id(), $form_iframe_url );
	}

	public function get_submission_url() {
		return managed_page_url( sprintf( 'forms/%s/submit/', urlencode( encrypt( $this->get_id() ) ) ) );
	}

	public function get_hosted_url() {
		return managed_page_url( sprintf( 'forms/%s/', urlencode( encrypt( $this->get_id() ) ) ) );
	}

	public function is_active() {
		return $this->step->is_active();
	}

	public function get_html_embed_code() {

		if ( ! $this->step->exists() ) {
			return '';
		}

		$form = html()->e( 'link', [
			'rel'  => 'stylesheet',
			'href' => GROUNDHOGG_ASSETS_URL . 'css/frontend/form.css'
		] );

		$form .= '<div class="gh-form-wrapper">';

		$atts = [
			'method'  => 'post',
			'class'   => 'gh-form ' . $this->attributes['class'],
			'target'  => '_parent',
			'action'  => $this->get_submission_url(),
			'enctype' => 'multipart/form-data',
			'name'    => $this->step->get_step_title()
		];

		$form .= sprintf( "<form %s>", array_to_atts( $atts ) );

		if ( ! empty( $this->attributes['id'] ) ) {
			$form .= "<input type='hidden' name='gh_submit_form_key' value='" . encrypt( $this->get_id() ) . "'>";
			$form .= "<input type='hidden' name='gh_submit_form' value='" . $this->get_id() . "'>";
		}

		$step = new Step( $this->get_id() );

		$form .= do_shortcode( $step->get_meta( 'form' ) );

		$form .= '</form>';

		$form .= '</div>';

		return apply_filters( 'groundhogg/form/embed', $form, $this );
	}


	/**
	 * Do the shortcode
	 *
	 * @return string
	 */
	public function shortcode() {

		wp_enqueue_style( 'groundhogg-form' );

		$form = '<div class="gh-form-wrapper">';

		/* Errors from a previous submission */
		$form .= form_errors( true );

		if ( ! $this->step->exists() ) {
			return false;
		}

		$submit_via_ajax = $this->step->get_meta( 'enable_ajax' );

		if ( $submit_via_ajax ) {
			wp_enqueue_script( 'groundhogg-ajax-form' );
			wp_enqueue_style( 'groundhogg-loader' );
		}

		$atts = [
			'method'  => 'post',
			'class'   => 'gh-form ' . $this->attributes['class'] . ( $submit_via_ajax ? ' ajax-submit' : '' ),
			'target'  => '_parent',
			'enctype' => 'multipart/form-data',
			'name'    => $this->step->get_step_title(),
			'id'      => $this->uniqid
		];

		if ( get_query_var( 'doing_iframe' ) ) {
			$atts['action'] = $this->get_submission_url();
		}

		$form .= sprintf( "<form %s>", array_to_atts( $atts ) );

		if ( ! empty( $this->attributes['id'] ) ) {
			$form .= "<input type='hidden' name='gh_submit_form_key' value='" . encrypt( $this->get_id() ) . "'>";
			$form .= "<input type='hidden' name='gh_submit_form' value='" . $this->get_id() . "'>";
		}

		$form .= '<div class="form-fields">';
		$form .= do_shortcode( do_replacements( $this->step->get_meta( 'form' ) ) );
		$form .= '</div>';

		$form .= '</form>';

		if ( is_user_logged_in() && current_user_can( 'edit_funnels' ) ) {
			$form .= sprintf( "<div class='gh-form-edit-link'><a href='%s'>%s</a></div>", admin_page_url( 'gh_funnels', [
				'action' => 'edit',
				'funnel' => $this->step->get_funnel_id(),
			], $this->step->get_id() ), __( 'Edit Form' ) );
		}

		$form .= '</div>';

		return apply_filters( 'groundhogg/form/shortcode', $form, $this );
	}

	/**
	 * Just return the shortcode
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->shortcode();
	}

	public function jsonSerialize() {
		return [
			'ID'            => $this->get_id(),
			'name'          => $this->step->get_title(),
			'rendered'      => $this->shortcode(),
			'embed_methods' => [
				'html'   => $this->get_html_embed_code(),
				'iframe' => $this->get_iframe_embed_code(),
				'url'    => $this->get_submission_url()
			]
		];
	}
}
