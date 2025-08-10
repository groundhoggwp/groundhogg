<?php

namespace Groundhogg\Steps\Benchmarks;

use Groundhogg\Email;
use Groundhogg\Form;
use Groundhogg\Plugin;
use Groundhogg\Properties;
use Groundhogg\Step;
use function Groundhogg\array_apply_callbacks;
use function Groundhogg\bold_it;
use function Groundhogg\encrypt;
use function Groundhogg\html;
use function Groundhogg\managed_page_url;
use function Groundhogg\one_of;


/**
 * Form Filled
 *
 * This will run whenever a form is completed
 *
 * @since       File available since Release 0.9
 * @subpackage  Elements/Benchmarks
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Elements
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Web_Form extends Benchmark {

	public function get_sub_group() {
		return 'forms';
	}

	/**
	 * Get element name
	 *
	 * @return string
	 */
	public function get_name() {
		return _x( 'Web Form', 'step_name', 'groundhogg' );
	}

	/**
	 * Get element type
	 *
	 * @return string
	 */
	public function get_type() {
		return 'web_form';
	}

	/**
	 * Get element description
	 *
	 * @return string
	 */
	public function get_description() {
		return _x( 'Use this form builder to create forms and display them on your site with shortcodes.', 'step_description', 'groundhogg' );
	}

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/forms/web-form.svg';
	}

	public function complete() {
		return;
	}

	/**
	 * @return int[]
	 */
	protected function get_complete_hooks() {
		return [];
	}

	protected function after_settings( Step $step ) {
		echo html()->input( [
			'id'          => $this->setting_id_prefix( 'form_name' ),
			'name'        => $this->setting_name_prefix( 'form_name' ),
			'value'       => $this->get_setting( 'form_name', $step->step_title ),
			'class'       => 'full-width',
			'style'       => [
				'font-size' => '18px'
			],
			'placeholder' => 'Form name...'
		] );

		echo html()->e( 'div', [
			'id' => "step_{$step->ID}_web_form_builder"
		], 'Form Builder' );
	}

	protected function before_step_notes( Step $step ) {

		$form     = new Form\Form_v2( [ 'id' => $step->get_id() ] );
		$form_url = add_query_arg( 'preview', '1', managed_page_url( sprintf( 'forms/%s/', urlencode( encrypt( $step->get_id() ) ) ) ) );

		?>
        <div class="gh-panel">
            <div class="gh-panel-header">
                <h2><?php esc_html_e( 'Embed options' ); ?></h2>
            </div>
            <div class="inside">
                <div class="display-flex column gap-10">
                    <label><?php printf( '%s:', __( 'Shortcode', 'groundhogg' ) ); ?></label>
                    <input
                            type="text"
                            onfocus="this.select()"
                            class="full-width code copy-text"
                            value="<?php echo esc_attr( $form->get_shortcode() ); ?>"
                            readonly>
                    <label><?php printf( '%s:', __( 'Iframe', 'groundhogg' ) ); ?></label>
                    <input
                            type="text"
                            onfocus="this.select()"
                            class="full-width code copy-text"
                            value="<?php echo esc_attr( $form->get_iframe_embed_code() ); ?>"
                            readonly>
                    <label><?php printf( '%s:', __( 'Hosted', 'groundhogg' ) ); ?></label>
                    <input
                            type="text"
                            onfocus="this.select()"
                            class="full-width code copy-text"
                            value="<?php echo esc_attr( $form->get_submission_url() ); ?>"
                            readonly>
                </div>
                <p>
					<?php echo Plugin::$instance->utils->html->modal_link( array(
						'title'              => __( 'Preview', 'groundhogg' ),
						'text'               => __( 'Preview', 'groundhogg' ),
						'footer_button_text' => __( 'Close', 'groundhogg' ),
						'id'                 => '',
						'class'              => 'gh-button secondary',
						'source'             => $form_url,
//						'height'             => 700,
						'width'              => 600,
						'footer'             => 'false',
						'preventSave'        => 'true',
					) );
					?>
                </p>
            </div>

        </div>

		<?php
	}

	/**
	 * @param $step Step
	 */
	public function settings( $step ) {
	}

	/**
	 * Given an individual field, remove unknown attrs and apply callbacks to known attrs
	 *
	 * @param $field
	 *
	 * @return array
	 */
	public function sanitize_form_field( $field ) {

		return array_apply_callbacks( $field, [
			'id'            => 'sanitize_key',
			'type'          => 'sanitize_key',
			'name'          => 'sanitize_key',
			'className'     => 'sanitize_text_field',
			'placeholder'   => 'sanitize_text_field',
			'value'         => 'sanitize_text_field',
			'text'          => function ( $label ) {
				return wp_kses( $label, 'data' );
			},
			'label'         => function ( $label ) {
				return wp_kses( $label, 'data' );
			},
			'html'          => function ( $label ) {
				return wp_kses( $label, 'post' );
			},
			'phone_type'    => function ( $value ) {
				return one_of( $value, [ 'primary', 'mobile', 'company' ] );
			},
			'required'      => 'boolval',
			'checked'       => 'boolval',
			'multiple'      => 'boolval',
			'enabled'       => 'boolval',
			'redact' => function ( $value ) {
				$value = absint( $value );

				return one_of( $value, [ 0, 1, 6, 12, 24 ] );
			},
			'file_types'    => function ( $value ) {
				return array_map( 'sanitize_text_field', $value );
			},
			'captcha_theme' => function ( $value ) {
				return one_of( $value, [ 'light', 'dark' ] );
			},
			'captcha_size'  => function ( $value ) {
				return one_of( $value, [ 'normal', 'compact' ] );
			},
			'hide_label'    => 'boolval',
			'options'       => function ( $options ) {

				return array_map( function ( $option ) {
					$value = sanitize_text_field( $option[0] );
					$tags  = '';
					if ( isset( $option[1] ) ) {
						$tags = implode( ',', wp_parse_id_list( $option[1] ) );
					}

					return [ $value, $tags ];
				}, $options );
			},
			'column_width'  => function ( $value ) {
				return one_of( $value, [ '1/1', '1/2', '1/3', '1/4', '2/3', '3/4' ] );
			},
			'property'      => function ( $property_id ) {
				$property = Properties::instance()->get_field( $property_id );
				if ( ! $property ) {
					return false;
				}

				return $property_id;

			},
			'tags'          => 'wp_parse_id_list',
		], true );
	}

	/**
	 * Make sure the form schema is sanitized correctly
	 *
	 * @param $form
	 *
	 * @return array
	 */
	public function sanitize_form( $form ) {

		// let's just make sure it's the format we expect
		$form = json_decode( wp_json_encode( $form ), true );

		$form['button'] = $this->sanitize_form_field( $form['button'] );
		$form['fields'] = array_map( [ $this, 'sanitize_form_field' ], $form['fields'] );

		if ( isset( $form['recaptcha'] ) ) {
			$form['recaptcha'] = $this->sanitize_form_field( $form['recaptcha'] );
		}

		return $form;
	}

	/**
	 *
	 * @return array {
	 * @type mixed    $default
	 * @type callable $sanitize
	 * }
	 */
	public function get_settings_schema() {
		return [
			'form'            => [
				'default'  => [],
				'sanitize' => [ $this, 'sanitize_form' ],
			],
			'form_name'       => [
				'default'  => '',
				'sanitize' => 'sanitize_text_field',
				'initial'  => 'Web Form'
			],
			'enable_ajax'     => [
				'default'  => false,
				'sanitize' => 'boolval',
				'initial'  => true
			],
			'accent_color'    => [
				'default'  => '',
				'sanitize' => 'sanitize_hex_color',
			],
			'theme'           => [
				'default'  => '',
				'sanitize' => 'sanitize_text_field',
			],
			'success_message' => [
				'default'  => '',
				'sanitize' => 'wp_kses_post',
                'initial'  => __( 'Thanks! Check your inbox for further details.', 'groundhogg' )
			],
			'success_page'    => [
				'default'  => '',
				'sanitize' => 'sanitize_text_field',
			]
		];
	}

	public function generate_step_title( $step ) {
		return sprintf( __( 'Submits %s', 'groundhogg' ), bold_it( $this->get_setting( 'form_name' ) ) );
	}

	protected function get_the_contact() {
		return false;
	}

	protected function can_complete_step() {
		return false;
	}

	/**
	 * Update email content when slug changes
	 *
	 * @param $old_slug string
	 * @param $new_slug string
	 * @param $step     Step
	 *
	 * @return void
	 */
	protected function replace_links_in_other_steps( $old_slug, $step ) {

		$new_url       = sprintf( managed_page_url( "forms/%s/" ), $step->get_slug() );
		$old_url_regex = "@https?://[A-z0-9/\-.]+/gh/forms/$old_slug/@";

		$steps = $step->get_funnel()->get_steps();

		foreach ( $steps as $_step ) {

			switch ( $_step->get_type() ) {
				case 'send_email':

					$email = new Email( $_step->get_meta( 'email_id' ) );

					if ( ! $email->exists() ) {
						break;
					}

					$content = preg_replace( $old_url_regex, $new_url, $email->get_content() );
					$email->update( [
						'content' => $content
					] );

					break;
				case 'link_click':

					$to_link = $_step->get_meta( 'redirect_to' ) ?: '';
					$to_link = preg_replace( $old_url_regex, $new_url, $to_link );
					$_step->update_meta( 'redirect_to', $to_link );

					break;
			}
		}
	}

	/**
	 * Search and replace emails for the link click url
	 *
	 * @param $step Step
	 *
	 * @return void
	 */
	public function post_import( $step ) {


		// get all send-email steps in the funnel
		// loop through all the emails
		// search and replace for the old URL and the new URL

		$old_slug = $step->get_meta( 'imported_step_id' ) . '-' . sanitize_title( $step->get_step_title() );
		$this->replace_links_in_other_steps( $old_slug, $step );
	}
}
