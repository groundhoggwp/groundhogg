<?php

namespace Groundhogg\Steps\Benchmarks;

use Groundhogg\Contact;
use Groundhogg\Form;
use Groundhogg\Plugin;
use Groundhogg\Properties;
use Groundhogg\Step;
use Groundhogg\Submission;
use function Groundhogg\encrypt;
use function Groundhogg\get_array_var;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_custom_fields_dropdown_options;
use function Groundhogg\get_url_var;
use function Groundhogg\html;
use function Groundhogg\kses;
use function Groundhogg\managed_page_url;


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

class Form_Filled extends Benchmark {

	public function get_sub_group() {
		return 'forms';
	}

	public function get_help_article() {
		return 'https://docs.groundhogg.io/docs/builder/benchmarks/web-form/';
	}

	protected function add_additional_actions() {
		add_action( 'admin_footer', [ $this, 'modal_form' ] );
	}

	public function is_legacy() {
		return true;
	}

	/**
	 * Get element name
	 *
	 * @return string
	 */
	public function get_name() {
		return _x( 'Web Form (Legacy)', 'step_name', 'groundhogg' );
	}

	/**
	 * Get element type
	 *
	 * @return string
	 */
	public function get_type() {
		return 'form_fill';
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


	/**
	 * @return int[]
	 */
	protected function get_complete_hooks() {
		return [];
	}

	/**
	 * Setup the completion process
	 *
	 * @param $submission Submission
	 * @param $contact    Contact
	 * @param $submission_handler
	 */
	public function setup( $submission, $contact, $submission_handler ) {
		$this->add_data( 'form_id', $submission->get_form_id() );
		$this->add_data( 'contact_id', $submission->get_contact_id() );
	}

	/**
	 * Based on the current step and contact,
	 *
	 * @return bool
	 */
	protected function can_complete_step() {
		return absint( $this->get_current_step()->get_id() ) === absint( $this->get_data( 'form_id' ) );
	}

	/**
	 * @return false|Contact
	 */
	protected function get_the_contact() {
		return get_contactdata( $this->get_data( 'contact_id' ) );
	}

	/**
	 * Enqueue the form builder JS in the admin area
	 */
	public function admin_scripts() {
		wp_enqueue_script( 'groundhogg-admin-form-builder' );
	}

	public function get_default_form() {
		return "[row][col width=\"1/2\"][first required=\"true\" label=\"First Name *\" placeholder=\"John\"][/col][col width=\"1/2\"][last required=\"true\" label=\"Last Name *\" placeholder=\"Doe\"][/col][/row][row][col width=\"1/1\"][email required=\"true\" label=\"Email *\" placeholder=\"email@example.com\"][/col][/row][row][col width=\"1/1\"][submit text=\"Submit\"][/col][/row]";
	}

	/**
	 * Save the step settings
	 *
	 * @param $step Step
	 */
	public function save( $step ) {
		$this->save_setting( 'form', wp_kses_post( $this->get_posted_data( 'form' ) ) );
		$this->save_setting( 'success_page', sanitize_text_field( $this->get_posted_data( 'success_page' ) ) );
		$this->save_setting( 'success_message', sanitize_textarea_field( $this->get_posted_data( 'success_message' ) ) );
		$this->save_setting( 'enable_ajax', absint( $this->get_posted_data( 'enable_ajax' ) ) );

		// Render the config quietly
		$form = do_shortcode( sprintf( "[gh_form id=%d]", $step->get_id() ) );

		// upgrade the form
		if ( $this->get_posted_data( 'upgrade_form_confirm' ) === 'confirm' ) {
			$this->upgrade_form( $step );
		}
	}

	/**
	 * @param $step Step
	 *
	 * @return void
	 */
	protected function upgrade_form( $step ) {

		$config     = $step->get_meta( 'config' );
		$shortcodes = $step->get_meta( 'form' );

		$fields = [];

		$form = [
			'recaptcha' => [
				'type'         => 'recaptcha',
				'label'        => 'reCAPTCHA',
				'enabled'      => false,
				'column_width' => '1/1'
			],
		];

		if ( ! empty( $config ) ) {

			foreach ( $config as $field ) {

				$field['atts'] = wp_parse_args( $field['atts'], [
					'id'          => '',
					'class'       => '',
					'placeholder' => '',
					'value'       => '',
				] );

				switch ( $field['type'] ) {
					case 'first':
					case 'last':
					case 'email':
						$fields[] = [
							'type'         => $field['type'],
							'required'     => $field['required'],
							'hide_label'   => empty( $field['label'] ),
							'label'        => str_replace( '*', '', $field['label'] ),
							'id'           => $field['atts']['id'],
							'className'    => $field['atts']['class'],
							'placeholder'  => $field['atts']['placeholder'],
							'value'        => $field['atts']['value'],
							'column_width' => '1/1'
						];
						break;
					case 'phone':
						$fields[] = [
							'type'         => 'phone',
							'phone_type'   => $field['atts']['phone_type'],
							'required'     => $field['required'],
							'hide_label'   => empty( $field['label'] ),
							'label'        => str_replace( '*', '', $field['label'] ),
							'id'           => $field['atts']['id'],
							'className'    => $field['atts']['class'],
							'placeholder'  => $field['atts']['placeholder'],
							'value'        => $field['atts']['value'],
							'column_width' => '1/1'
						];
						break;
					case 'address':

						$fields[] = [
							'type'         => 'line1',
							'required'     => $field['required'],
							'hide_label'   => false,
							'label'        => __( 'Line 1', 'groundhogg' ),
							'id'           => $field['atts']['id'],
							'className'    => $field['atts']['class'],
							'placeholder'  => '',
							'value'        => '',
							'column_width' => '2/3'
						];

						$fields[] = [
							'type'         => 'line2',
							'required'     => $field['required'],
							'hide_label'   => false,
							'label'        => __( 'Line 2', 'groundhogg' ),
							'id'           => $field['atts']['id'],
							'className'    => $field['atts']['class'],
							'placeholder'  => '',
							'value'        => '',
							'column_width' => '1/3'
						];

						$fields[] = [
							'type'         => 'city',
							'required'     => $field['required'],
							'hide_label'   => false,
							'label'        => __( 'City', 'groundhogg' ),
							'id'           => $field['atts']['id'],
							'className'    => $field['atts']['class'],
							'placeholder'  => '',
							'value'        => '',
							'column_width' => '1/1'
						];

						$fields[] = [
							'type'         => 'state',
							'required'     => $field['required'],
							'hide_label'   => false,
							'label'        => __( 'State', 'groundhogg' ),
							'id'           => $field['atts']['id'],
							'className'    => $field['atts']['class'],
							'placeholder'  => '',
							'value'        => '',
							'column_width' => '1/3'
						];

						$fields[] = [
							'type'         => 'zip_code',
							'required'     => $field['required'],
							'hide_label'   => false,
							'label'        => __( 'Zip Code', 'groundhogg' ),
							'id'           => $field['atts']['id'],
							'className'    => $field['atts']['class'],
							'placeholder'  => '',
							'value'        => '',
							'column_width' => '1/3'
						];

						$fields[] = [
							'type'         => 'country',
							'required'     => $field['required'],
							'hide_label'   => false,
							'label'        => __( 'Country', 'groundhogg' ),
							'id'           => $field['atts']['id'],
							'className'    => $field['atts']['class'],
							'placeholder'  => '',
							'value'        => '',
							'column_width' => '1/3'
						];

						break;
					case 'birthday':
						$fields[] = [
							'type'         => 'birthday',
							'required'     => $field['required'],
							'hide_label'   => empty( $field['label'] ),
							'label'        => str_replace( '*', '', $field['label'] ),
							'id'           => $field['atts']['id'],
							'className'    => $field['atts']['class'],
							'value'        => '',
							'column_width' => '1/1'
						];

						break;
					case 'gdpr':
					case 'terms':
						$fields[] = [
							'type'         => $field['type'],
							'label'        => str_replace( '*', '', $field['label'] ),
							'id'           => $field['atts']['id'],
							'className'    => $field['atts']['class'],
							'column_width' => '1/1'
						];

						break;
					case 'text':
					case 'textarea':
						$fields[] = [
							'type'         => $field['type'],
							'name'         => $field['name'],
							'required'     => $field['required'],
							'hide_label'   => empty( $field['label'] ),
							'label'        => str_replace( '*', '', $field['label'] ),
							'id'           => $field['atts']['id'],
							'className'    => $field['atts']['class'],
							'placeholder'  => $field['atts']['placeholder'],
							'value'        => $field['atts']['value'],
							'column_width' => '1/1'
						];
						break;
					case 'number':
					case 'date':
					case 'time':
						$fields[] = [
							'type'         => $field['type'],
							'name'         => $field['name'],
							'required'     => $field['required'],
							'hide_label'   => empty( $field['label'] ),
							'label'        => str_replace( '*', '', $field['label'] ),
							'id'           => $field['atts']['id'],
							'className'    => $field['atts']['class'],
							'placeholder'  => $field['atts']['placeholder'],
							'value'        => $field['atts']['value'],
							'min'          => get_array_var( $field['atts'], 'min' ),
							'max'          => get_array_var( $field['atts'], 'max' ),
							'column_width' => '1/1'
						];
						break;
					case 'file':
						$fields[] = [
							'type'         => 'file',
							'name'         => $field['name'],
							'required'     => $field['required'],
							'hide_label'   => empty( $field['label'] ),
							'label'        => str_replace( '*', '', $field['label'] ),
							'id'           => $field['atts']['id'],
							'className'    => $field['atts']['class'],
							'file_types'   => explode( ',', str_replace( '.', '', $field['atts']['file_types'] ) ),
							'column_width' => '1/1'
						];
						break;
					case 'checkbox':
						$fields[] = [
							'type'         => 'checkbox',
							'name'         => $field['name'],
							'required'     => $field['required'],
							'label'        => str_replace( '*', '', $field['label'] ),
							'id'           => $field['atts']['id'],
							'className'    => $field['atts']['class'],
							'value'        => $field['atts']['value'],
							'tags'         => [ absint( $field['atts']['tag'] ) ],
							'column_width' => '1/1'
						];
						break;
					case 'radio':
					case 'dropdown':
						$fields[] = [
							'type'         => $field['type'],
							'name'         => $field['name'],
							'required'     => $field['required'],
							'label'        => str_replace( '*', '', $field['label'] ),
							'id'           => $field['atts']['id'],
							'className'    => $field['atts']['class'],
							'options'      => array_map( function ( $option ) {
								return explode( '|', $option );
							}, explode( ',', $field['atts']['options'] ) ),
							'column_width' => '1/1'
						];
						break;
					case 'custom':
						$fields[] = [
							'type'         => 'custom_field',
							'name'         => $field['name'],
							'required'     => $field['required'],
							'property'     => get_array_var( Properties::instance()->get_field( $field['atts']['custom_field'] ), 'id' ),
							'label'        => str_replace( '*', '', $field['label'] ),
							'id'           => $field['atts']['id'],
							'className'    => $field['atts']['class'],
							'column_width' => '1/1'
						];

						break;
					case 'recaptcha':

						$form['recaptcha']['enabled'] = true;

						break;
				}

			}
		}

		preg_match( '/\[submit ([^\]]+)\]/', $shortcodes, $matches );

		$atts = $matches[1];
		$atts = wp_parse_args( shortcode_parse_atts( $atts ), [
			'id'    => '',
			'class' => '',
			'text'  => ''
		] );

		$button = [
			'type'         => 'button',
			'column_width' => '1/1',
			'text'         => $atts['text'],
			'className'    => $atts['class'],
			'id'           => $atts['id'],
		];

		$form['fields'] = $fields;
		$form['button'] = $button;

		$step->update_meta( 'form', $form );

		$step->update( [
			'step_type' => 'web_form'
		] );
	}

	protected function before_step_notes( Step $step ) {

		$form            = new Form\Form( [ 'id' => $step->get_id() ] );
		$form_embed_code = esc_html( $form->get_html_embed_code() );
		$form_url        = managed_page_url( sprintf( 'forms/%s/', urlencode( encrypt( $step->get_id() ) ) ) );

		html( [
			html()->button( [
				'type'  => 'button',
				'id'    => $this->setting_id_prefix( 'upgrade_form' ),
				'text'  => __( 'Upgrade Form', 'groundhogg' ),
				'class' => 'gh-button secondary full-width'
			] ),
			html()->input( [
				'type' => 'hidden',
				'name' => $this->setting_name_prefix( 'upgrade_form_confirm' ),
				'id'   => $this->setting_id_prefix( 'upgrade_form_confirm' ),
			] ),
			html()->input( [
				'type' => 'hidden',
				'name' => $this->setting_name_prefix( 'upgrade_form_confirm' ),
				'id'   => $this->setting_id_prefix( 'upgrade_form_confirm' ),
			] )
		] );

		?>
        <div class="gh-panel">
            <div class="gh-panel-header">
                <h2><?php esc_html_e( 'Embed options', 'groundhogg' ); ?></h2>
            </div>
            <div class="inside">
                <div class="display-flex column gap-10">
                    <label><?php printf( '%s:', esc_html__( 'Shortcode', 'groundhogg' ) ); ?></label>
                    <input
                            type="text"
                            onfocus="this.select()"
                            class="code"
                            value="<?php echo esc_attr( $form->get_shortcode() ); ?>"
                            readonly>
                    <label><?php printf( '%s:', esc_html__( 'Iframe', 'groundhogg' ) ); ?></label>
                    <input
                            type="text"
                            onfocus="this.select()"
                            class="code"
                            value="<?php echo esc_attr( $form->get_iframe_embed_code() ); ?>"
                            readonly>
                    <label><?php printf( '%s:', esc_html__( 'HTML', 'groundhogg' ) ); ?></label>
                    <input
                            type="text"
                            onfocus="this.select()"
                            class="code"
                            value="<?php echo esc_attr( $form_embed_code ); ?>"
                            readonly>
                    <label><?php printf( '%s:', esc_html__( 'Hosted', 'groundhogg' ) ); ?></label>
                    <input
                            type="text"
                            onfocus="this.select()"
                            class="code"
                            value="<?php echo esc_attr( $form->get_submission_url() ); ?>"
                            readonly>
                </div>
                <p>
	                <?php html( html()->modal_link( array(
		                'title'              => esc_html__( 'Preview', 'groundhogg' ),
		                'text'               => esc_html__( 'Preview', 'groundhogg' ),
		                'footer_button_text' => esc_html__( 'Close', 'groundhogg' ),
						'id'                 => '',
						'class'              => 'gh-button secondary',
						'source'             => $form_url,
//						'height'             => 700,
						'width'              => 600,
						'footer'             => 'false',
						'preventSave'        => 'true',
	                ) ) );
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

		$form = new Form\Form( [ 'id' => $step->get_id() ] );

		$form_url = managed_page_url( sprintf( 'forms/%s/', urlencode( encrypt( $step->get_id() ) ) ) );

		$default_form = $this->get_default_form();

		if ( ! $this->get_setting( 'form' ) ) {
			$this->save_setting( 'form', $default_form );
		}

		?>
        <p></p>
        <div class="form-editor">
            <div class="form-buttons">
				<?php

				$buttons = [
					[
						'text'      => __( 'Row', 'groundhogg' ),
						'data-type' => 'row',
					],
					[
						'text'      => __( 'Col', 'groundhogg' ),
						'data-type' => 'col',
					],
					[
						'text'      => __( 'First', 'groundhogg' ),
						'data-type' => 'first',
					],
					[
						'text'      => __( 'Last', 'groundhogg' ),
						'data-type' => 'last',
					],
					[
						'text'      => __( 'Email', 'groundhogg' ),
						'data-type' => 'email',
					],
					[
						'text'      => __( 'Phone', 'groundhogg' ),
						'data-type' => 'phone',
					],
					[
						'text'      => __( 'Address', 'groundhogg' ),
						'data-type' => 'address',
					],
					[
						'text'      => __( 'Birthday', 'groundhogg' ),
						'data-type' => 'birthday',
					],
					[
						'text'      => __( 'GDPR', 'groundhogg' ),
						'data-type' => 'gdpr',
					],
					[
						'text'      => __( 'Terms', 'groundhogg' ),
						'data-type' => 'terms',
					],
					[
						'text'      => __( 'Custom Field', 'groundhogg' ),
						'data-type' => 'custom',
					],
					[
						'text'      => __( 'Text', 'groundhogg' ),
						'data-type' => 'text',
					],
					[
						'text'      => __( 'Textarea', 'groundhogg' ),
						'data-type' => 'textarea',
					],
					[
						'text'      => __( 'Number', 'groundhogg' ),
						'data-type' => 'number',
					],
					[
						'text'      => __( 'Dropdown', 'groundhogg' ),
						'data-type' => 'dropdown',
					],
					[
						'text'      => __( 'Radio', 'groundhogg' ),
						'data-type' => 'radio',
					],
					[
						'text'      => __( 'Checkbox', 'groundhogg' ),
						'data-type' => 'checkbox',
					],
					[
						'text'      => __( 'Date', 'groundhogg' ),
						'data-type' => 'date',
					],
					[
						'text'      => __( 'Time', 'groundhogg' ),
						'data-type' => 'time',
					],
					[
						'text'      => __( 'File', 'groundhogg' ),
						'data-type' => 'file',
					],
					[
						'text'      => __( 'ReCAPTCHA', 'groundhogg' ),
						'data-type' => 'recaptcha',
					],
					[
						'text'      => __( 'Submit', 'groundhogg' ),
						'data-type' => 'submit',
					],
				];

				$buttons = apply_filters( 'wpgh_form_builder_buttons', $buttons );

				foreach ( $buttons as $button ) {

					$args = wp_parse_args( $button, array(
						'text'               => __( 'Field', 'groundhogg' ),
						/* translators: %s: the field type */
						'title'              => sprintf( __( 'Insert Field: %s', 'groundhogg' ), $button['text'] ),
						'class'              => 'gh-button grey text small code',
						'source'             => 'form-field-editor',
						'footer_button_text' => __( 'Insert Field', 'groundhogg' ),
						'width'              => 600,
						'height'             => 600
					) );

					html( html()->modal_link( $args ) );
				} ?>
            </div>

			<?php

			$code = $this->prettify( $this->get_setting( 'form', $default_form ) );
			$rows = min( substr_count( $code, "\n" ) + 1, 15 );

			html( html()->textarea( [
				'id'    => $this->setting_id_prefix( 'form' ),
				'name'  => $this->setting_name_prefix( 'form' ),
				'value' => $code,
				'class' => 'code form-html',
				'cols'  => '',
				'rows'  => $rows,
				'style' => [
					'white-space' => ' nowrap',
					'width'       => '100%'
				],
			] ) ) ?>
        </div>
        <table class="form-table">
            <tbody>
            <tr>
                <th>
					<?php esc_attr_e( 'Submit via AJAX:', 'groundhogg' ); ?>
                </th>
                <td>
					<?php

					$ajax_is_enabled = (bool) $this->get_setting( 'enable_ajax' );

					html( html()->checkbox( [
						'label' => esc_html__( 'Enable', 'groundhogg' ),
						'name'    => $this->setting_name_prefix( 'enable_ajax' ),
						'id'      => $this->setting_id_prefix( 'enable_ajax' ),
						'class'   => 'enable-ajax auto-save',
						'value'   => '1',
						'checked' => $ajax_is_enabled,
						'title' => __( 'Enable Ajax', 'groundhogg' ),
					] ) ); ?>
                </td>
            </tr>
            <tr class="<?php echo $ajax_is_enabled ? '' : 'hidden'; ?>">
                <th>
					<?php esc_attr_e( 'Thank You Message:', 'groundhogg' ); ?>
                </th>
                <td>
					<?php

					html( html()->textarea( [
						'id'    => $this->setting_id_prefix( 'success_message' ),
						'name'  => $this->setting_name_prefix( 'success_message' ),
						'title' => __( 'Thank You Message', 'groundhogg' ),
						'value' => $this->get_setting( 'success_message', __( 'Your submission has been received.', 'groundhogg' ) ),
						'cols'  => '',
						'rows'  => 3,
						'style' => [
							'width' => '100%'
						],
					] ) ); ?>
                </td>
            </tr>
            <tr class="<?php echo $ajax_is_enabled ? 'hidden' : ''; ?>">
                <th>
					<?php esc_attr_e( 'Thank You Page:', 'groundhogg' ); ?>
                </th>
                <td>
					<?php

                    html( html()->link_picker([
	                    'type'  => 'text',
	                    'id'    => $this->setting_id_prefix( 'success_page' ),
	                    'name'  => $this->setting_name_prefix( 'success_page' ),
	                    'title' => __( 'Thank You Page', 'groundhogg' ),
	                    'value' => $this->get_setting( 'success_page', home_url( 'thank-you/' ) )
                    ]) );

					?>
                </td>
            </tr>
            </tbody>
        </table>
		<?php
	}

	/**
	 * Prettifies the shortcode text to make it easier to identify and read
	 *
	 * @param $code string of shortcode
	 *
	 * @return string
	 */
	private function prettify( $code ) {

		$pretty = $code;

		/* Remove all newlines & whitespace */
		$code  = trim( $code, " \t\n\r" );
		$code  = preg_replace( '/(\])\s*(\[)/', "$1$2", $code );
		$code  = preg_replace( '/(\])/', "$1" . PHP_EOL, $code );
		$codes = explode( PHP_EOL, $code );

//        var_dump( $codes );

		$depth  = 0;
		$pretty = '';

		foreach ( $codes as $i => $shortcode ) {

			$shortcode = trim( $shortcode, " \t\n\r" );
			if ( empty( $shortcode ) ) {
				continue;
			}

			/* Opening tag */
			if ( preg_match( '/\[(col|row)\b[^\]]*\]/', $shortcode ) ) {
				$pretty .= str_repeat( str_repeat( " ", 4 ), $depth ) . $shortcode;
				$depth ++;
				/* Closing tag */
			} else if ( preg_match( '/\[\/(col|row)\]/', $shortcode ) ) {
//                var_dump( $shortcode) ;
				$depth --;
				$pretty .= str_repeat( str_repeat( " ", 4 ), $depth ) . $shortcode;
				/* Other stuff */
			} else {
				$pretty .= str_repeat( str_repeat( " ", 4 ), $depth ) . $shortcode;
			}

			$pretty .= PHP_EOL;

		}

		return $pretty;

	}

	/**
	 * Load the field builder form
	 */
	public function modal_form() {
		// do not load on every page.
		if ( get_url_var( 'page' ) !== 'gh_funnels' || get_url_var( 'action' ) !== 'edit' ) {
			return;
		}

		?>
        <div id="form-field-editor" class="form-field-editor hidden">
            <form class="form-field-form" id="form-field-form" method="post" action="">
                <table class="form-table">
                    <tbody>
                    <tr id="gh-field-required">
                        <th><?php esc_html_e( 'Required Field', 'groundhogg' ); ?></th>
                        <td><?php
							html( html()->checkbox( array(
								'id'    => 'field-required',
								'name'  => 'required',
								'label' => __( 'Yes', 'groundhogg' ),
								'value' => 'true'
							) ) );
							?></td>
                    </tr>
                    <tr id="gh-field-label">
                        <th><?php esc_html_e( 'Label', 'groundhogg' ); ?></th>
                        <td><?php
							html( html()->input( array(
								'id'   => 'field-label',
								'name' => 'label'
							) ) );
							?><p class="description"><?php esc_html_e( 'The field label.', 'groundhogg' ); ?></p></td>
                    </tr>
                    <tr id="gh-field-text">
                        <th><?php esc_html_e( 'Text', 'groundhogg' ); ?></th>
                        <td><?php
							html( html()->input( array(
								'id'   => 'field-text',
								'name' => 'text'
							) ) );
							?><p class="description"><?php esc_html_e( 'The button text.', 'groundhogg' ); ?></p></td>
                    </tr>
                    <tr id="gh-field-placeholder">
                        <th><?php esc_html_e( 'Placeholder', 'groundhogg' ); ?></th>
                        <td><?php
							html( html()->input( array(
								'id'   => 'field-placeholder',
								'name' => 'placeholder'
							) ) );
							?>
                            <p class="description"><?php esc_html_e( 'The ghost text within the field.', 'groundhogg' ); ?></p>
                        </td>
                    </tr>
                    <tr id="gh-field-name">
                        <th><?php esc_html_e( 'Meta name', 'groundhogg' ); ?></th>
                        <td><?php
							html( html()->meta_picker( array(
								'id'   => 'field-name',
								'name' => 'name'
							) ) );
							?>
                            <p class="description"><?php esc_html_e( 'This will be the custom field name. I.E. {meta.name}', 'groundhogg' ); ?></p>
                        </td>
                    </tr>
                    <tr id="gh-field-custom_field">
                        <th><?php esc_html_e( 'Custom Field', 'groundhogg' ); ?></th>
                        <td><?php
							html( html()->dropdown( [
								'id'      => 'field-custom_field',
								'name'    => 'custom_field',
								'options' => get_custom_fields_dropdown_options()
							] ) );
							?>
                            <p class="description"><?php esc_html_e( 'Select a custom field to show.', 'groundhogg' ); ?></p>
                        </td>
                    </tr>

                    <!--BEGIN NUMBER OPTIONS -->
                    <tr id="gh-field-min">
                        <th><?php esc_html_e( 'Min', 'groundhogg' ); ?></th>
                        <td><?php
							html( html()->number( array(
								'id'    => 'field-min',
								'name'  => 'min',
								'class' => 'input'
							) ) );
							?>
                            <p class="description"><?php esc_html_e( 'The minimum number a user can enter.', 'groundhogg' ); ?></p>
                        </td>
                    </tr>
                    <tr id="gh-field-max">
                        <th><?php esc_html_e( 'Max', 'groundhogg' ); ?></th>
                        <td><?php
							html( html()->number( array(
								'id'    => 'field-max',
								'name'  => 'max',
								'class' => 'input'
							) ) );
							?>
                            <p class="description"><?php esc_html_e( 'The max number a user can enter.', 'groundhogg' ); ?></p>
                        </td>
                    </tr>
                    <!-- END NUMBER OPTIONS -->

                    <tr id="gh-field-value">
                        <th><?php esc_html_e( 'Value', 'groundhogg' ); ?></th>
                        <td><?php
							html( html()->input( array(
								'id'   => 'field-value',
								'name' => 'value'
							) ) );
							?><p class="description"><?php esc_html_e( 'The default value of the field.', 'groundhogg' ); ?></p>
                        </td>
                    </tr>
                    <tr id="gh-field-tag">
                        <th><?php esc_html_e( 'Add Tag', 'groundhogg' ); ?></th>
                        <td><?php
							html( html()->tag_picker( array(
								'id'       => 'field-tag',
								'name'     => 'tag',
								'class'    => 'gh-single-tag-picker',
								'multiple' => false
							) ) );
							?>
                            <p class="description"><?php esc_html_e( 'Add a tag when this checkbox is selected.', 'groundhogg' ); ?></p>
                        </td>
                    </tr>

                    <tr id="gh-field-options">
                        <th><?php esc_html_e( 'Options', 'groundhogg' ); ?></th>
                        <td><?php
							html( html()->textarea( array(
								'id'    => 'field-options',
								'name'  => 'options',
								'cols'  => 50,
								'rows'  => '5',
								'class' => 'hidden'
							) ) );
							?>
                            <div id='gh-option-table' class="display-flex gap-10 column">
                                <div class='option-wrapper display-flex gap-10'>
                                    <input type='text' class='input' style='float: left' name='option[]'
                                           placeholder='Option Text'>


                                    <select class='gh-single-tag-picker' name='tags[]'
                                            style='max-width: 140px;'></select>


                                    <a style="text-decoration: none"
                                       href="javascript:void(0)"
                                       class="deleteOption gh-button danger text small">
                                        <span class="dashicons dashicons-trash"></span>
                                    </a>
                                </div>
                            </div>
                            <button type="button" class="gh-button secondary addoption">
								<?php echo esc_html_x( 'Add Option', 'action', 'groundhogg' ); ?>
                            </button>
                        </td>
                    </tr>
                    <tr id="gh-field-multiple">
                        <th><?php esc_html_e( 'Allow Multiple Selections', 'groundhogg' ); ?></th>
                        <td><?php
							html( html()->checkbox( array(
								'id'    => 'field-multiple',
								'name'  => 'multiple',
								'label' => __( 'Yes', 'groundhogg' )
							) ) );
							?></td>
                    </tr>
                    <tr id="gh-field-default">
                        <th><?php esc_html_e( 'Default', 'groundhogg' ); ?></th>
                        <td><?php
							html( html()->input( array(
								'id'   => 'field-default',
								'name' => 'default',
								'cols' => 50,
								'rows' => '5'
							) ) );
							?>
                            <p class="description"><?php esc_html_e( 'The blank option which appears at the top of the list.', 'groundhogg' ); ?></p>
                        </td>
                    </tr>

                    <!-- BEGIN COLUMN OPTIONS -->
                    <tr id="gh-field-width">
                        <th><?php esc_html_e( 'Width', 'groundhogg' ); ?></th>
                        <td><?php
							html( html()->dropdown( array(
								'id'          => 'field-width',
								'name'        => 'width',
								'options'     => array(
									'1/1' => '1/1',
									'1/2' => '1/2',
									'1/3' => '1/3',
									'1/4' => '1/4',
									'2/3' => '2/3',
									'3/4' => '3/4',
								),
								'option_none' => false
							) ) );
							?><p class="description"><?php esc_html_e( 'The width of the column.', 'groundhogg' ); ?></p></td>
                    </tr>
                    <!-- END COLUMN OPTIONS -->

                    <!-- BEGIN CAPTCHA OPTIONS -->
					<?php if ( Form\Fields\Recaptcha::get_version() !== 'v3' ): ?>
                        <tr id="gh-field-captcha-theme">
                            <th><?php esc_html_e( 'Theme', 'groundhogg' ); ?></th>
                            <td><?php
								html( html()->dropdown( array(
									'id'      => 'field-theme',
									'name'    => 'captcha-theme',
									'options' => array(
										'light' => 'Light',
										'dark'  => 'Dark',
									)
								) ) );
								?><p class="description"><?php esc_html_e( 'The CAPTCHA Theme.', 'groundhogg' ); ?></p></td>
                        </tr>
                        <tr id="gh-field-captcha-size">
                            <th><?php esc_html_e( 'Size', 'groundhogg' ); ?></th>
                            <td><?php
								html( html()->dropdown( array(
									'id'      => 'field-captcha-size',
									'name'    => 'captcha-size',
									'options' => array(
										'normal'  => 'Normal',
										'compact' => 'Compact',
									)
								) ) );
								?><p class="description"><?php esc_html_e( 'The CAPTCHA Size.', 'groundhogg' ); ?></p></td>
                        </tr>
					<?php endif; ?>
                    <!-- END CAPTCHA OPTIONS -->

                    <!-- BEGIN DATE OPTIONS -->
                    <tr id="gh-field-min_date">
                        <th><?php esc_html_e( 'Min Date', 'groundhogg' ); ?></th>
                        <td><?php
							html( html()->input( array(
								'type'        => 'date',
								'id'          => 'field-min_date',
								'name'        => 'min_date',
								'placeholder' => 'YYY-MM-DD or +3 days or -1 days'
							) ) );
							?>
                            <p class="description"><?php esc_html_e( 'The minimum date a user can enter. You can enter a dynamic date or static date.', 'groundhogg' ); ?></p>
                        </td>
                    </tr>
                    <tr id="gh-field-max_date">
                        <th><?php esc_html_e( 'Max Date', 'groundhogg' ); ?></th>
                        <td><?php
							html( html()->input( array(
								'type'        => 'date',
								'id'          => 'field-max_date',
								'name'        => 'max_date',
								'placeholder' => 'YYY-MM-DD or +3 days or -1 days'
							) ) );
							?>
                            <p class="description"><?php esc_html_e( 'The maximum date a user can enter. You can enter a dynamic date or static date.', 'groundhogg' ); ?></p>
                        </td>
                    </tr>
                    <!-- END DATE OPTIONS -->

                    <!-- BEGIN PHONE OPTIONS-->
                    <tr id="gh-field-phone_type">
                        <th><?php esc_html_e( 'Phone Type', 'groundhogg' ); ?></th>
                        <td><?php
							html( html()->dropdown( [
								'id'          => 'field-phone_type',
								'name'        => 'phone_type',
								'options'     => [
									'primary' => __( 'Primary Phone', 'groundhogg' ),
									'mobile'  => __( 'Mobile Phone', 'groundhogg' ),
									'company' => __( 'Company Phone', 'groundhogg' ),
								],
								'option_none' => false,
							] ) )
							?>
                            <p class="description"><?php esc_html_e( 'Which phone field you want the contact to provide.', 'groundhogg' ); ?></p>
                        </td>
                    </tr>
                    <tr id="gh-field-show_ext">
                        <th><?php esc_html_e( 'Collect Number Extension', 'groundhogg' ); ?></th>
                        <td><?php
							html( html()->checkbox( [
								'id'    => 'field-show_ext',
								'name'  => 'show_ext',
								'label' => __( 'Yes', 'groundhogg' ),
								'value' => 'true'
							] ) );
							?>
                            <p class="description"><?php esc_html_e( 'Ask to collect the phone number extension.', 'groundhogg' ); ?></p>
                        </td>
                    </tr>
                    <!-- END PHONE OPTIONS-->

                    <!-- BEGIN TIME OPTIONS -->
                    <tr id="gh-field-min_time">
                        <th><?php esc_html_e( 'Min Time', 'groundhogg' ); ?></th>
                        <td><?php
							html( html()->input( array(
								'type' => 'time',
								'id'   => 'field-min_time',
								'name' => 'min_time'
							) ) );
							?>
                            <p class="description"><?php esc_html_e( 'The minimum time a user can enter. You can enter a dynamic time or static time.', 'groundhogg' ); ?></p>
                        </td>
                    </tr>
                    <tr id="gh-field-max_time">
                        <th><?php esc_html_e( 'Max Time', 'groundhogg' ); ?></th>
                        <td><?php
							html( html()->input( array(
								'type' => 'time',
								'id'   => 'field-max_time',
								'name' => 'max_time'
							) ) );
							?>
                            <p class="description"><?php esc_html_e( 'The maximum time a user can enter. You can enter a dynamic time or static time.', 'groundhogg' ); ?></p>
                        </td>
                    </tr>
                    <!-- END TIME OPTIONS -->

                    <!-- BEGIN FILE OPTIONS -->
                    <tr id="gh-field-max_file_size">
                        <th><?php esc_html_e( 'Max File Size', 'groundhogg' ); ?></th>
                        <td><?php
							html( html()->number( array(
								'id'          => 'field-max_file_size',
								'name'        => 'max_file_size',
								'placeholder' => '1000000',
								'min'         => 0,
								'max'         => wp_max_upload_size() * 1000000
							) ) );
							?>
                            <p class="description"><?php
                                /* translators: %d: the number of allowed bytes */
                                kses( sprintf( __( 'Maximum size a file can be <b>in Bytes</b>. Your max upload size is %d Bytes.', 'groundhogg' ), wp_max_upload_size() ), [ 'b' => [] ] );
                                ?></p>
                        </td>
                    </tr>
                    <tr id="gh-field-file_types">
                        <th><?php esc_html_e( 'Accepted File Types', 'groundhogg' ); ?></th>
                        <td><?php
							html( html()->input( array(
								'id'          => 'field-file_types',
								'name'        => 'file_types',
								'placeholder' => '.pdf,.txt,.doc,.docx'
							) ) );
							?>
                            <p class="description"><?php esc_html_e( 'The types of files a user may upload (comma separated). Leave empty to not specify.', 'groundhogg' ) ?></p>
                        </td>
                    </tr>
                    <!-- END FILE OPTIONS -->

                    <!-- BEGIN EXTENSION PLUGIN CUSTOM OPTIONS -->
					<?php do_action( 'groundhogg/steps/benchmarks/form/extra_settings' ); ?>
                    <!-- END EXTENSION PLUGIN CUSTOM OPTIONS -->

                    <!-- BEGIN CSS OPTIONS -->
                    <tr id="gh-field-id">
                        <th><?php esc_html_e( 'CSS ID', 'groundhogg' ); ?></th>
                        <td><?php
							html( html()->input( array( 'id' => 'field-id', 'name' => 'id' ) ) );
							?><p class="description"><?php esc_html_e( 'Use to apply CSS.', 'groundhogg' ); ?></p></td>
                    </tr>
                    <tr id="gh-field-class">
                        <th><?php esc_html_e( 'CSS Class', 'groundhogg' ); ?></th>
                        <td><?php
							html( html()->input( array(
								'id'   => 'field-class',
								'name' => 'class'
							) ) );
							?><p class="description"><?php esc_html_e( 'Use to apply CSS.', 'groundhogg' ); ?></p></td>
                    </tr>
                    <!-- END CSS OPTIONS -->
                    </tbody>
                </table>
            </form>
        </div>
		<?php
	}
}
