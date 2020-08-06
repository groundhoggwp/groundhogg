<?php

namespace Groundhogg\Steps\Benchmarks;

use Groundhogg\Contact;
use Groundhogg\Reporting\Reporting;
use Groundhogg\Utils\Graph;
use function Groundhogg\convert_form_shortcode_to_json;
use function Groundhogg\convert_shortcode_to_json;
use function Groundhogg\encrypt;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;
use function Groundhogg\get_url_var;
use function Groundhogg\html;
use Groundhogg\HTML;
use Groundhogg\Plugin;
use Groundhogg\Step;
use Groundhogg\Contact_Query;
use Groundhogg\Event;
use Groundhogg\Form;
use Groundhogg\Submission;
use function Groundhogg\managed_page_url;
use function Groundhogg\percentage;


/**
 * Form Filled
 *
 * This will run whenever a form is completed
 *
 * @package     Elements
 * @subpackage  Elements/Benchmarks
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Form_Filled extends Benchmark {

	public function get_help_article() {
		return 'https://docs.groundhogg.io/docs/builder/benchmarks/web-form/';
	}

	protected function add_additional_actions() {
		add_action( 'admin_footer', [ $this, 'modal_form' ] );
	}

	/**
	 * Get element name
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
		return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/form-filled.png';
	}


	/**
	 * @return int[]
	 */
	protected function get_complete_hooks() {
		return [
			'groundhogg/form/submission_handler/after' => 3
		];
	}

	/**
	 * Setup the completion process
	 *
	 * @param $submission Submission
	 * @param $contact Contact
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
		return "[row][col width=\"1/2\"][first required=\"true\" label=\"First Name\" placeholder=\"John\"][/col][col width=\"1/2\"][last required=\"true\" label=\"Last Name\" placeholder=\"Doe\"][/col][/row][row][col width=\"1/1\"][email required=\"true\" label=\"Email\" placeholder=\"email@example.com\"][/col][/row][row][col width=\"1/1\"][submit text=\"Submit\"][/col][/row]";
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

		$form_embed_code = esc_html( $form->get_html_embed_code() );

		?>
        <table class="form-table">
            <tbody>
            <tr>
                <th>
					<?php esc_attr_e( 'Embed:', 'groundhogg' ); ?>
                </th>
                <td>
                    <table class="embed-options">
                        <tbody>
                        <tr>
                            <td><?php printf( '%s:', __( 'Shortcode' ) ); ?></td>
                            <td><input
                                        type="text"
                                        onfocus="this.select()"
                                        class="regular-text code"
                                        value="<?php echo esc_attr( $form->get_shortcode() ); ?>"
                                        readonly></td>
                        </tr>
                        <tr>
                            <td><?php printf( '%s:', __( 'Iframe' ) ); ?></td>
                            <td><input
                                        type="text"
                                        onfocus="this.select()"
                                        class="regular-text code"
                                        value="<?php echo esc_attr( $form->get_iframe_embed_code() ); ?>"
                                        readonly></td>
                        </tr>
                        <tr>
                            <td><?php printf( '%s:', __( 'HTML' ) ); ?></td>
                            <td><input
                                        type="text"
                                        onfocus="this.select()"
                                        class="regular-text code"
                                        value="<?php echo esc_attr( $form_embed_code ); ?>"
                                        readonly></td>
                        </tr>
                        <tr>
                            <td><?php printf( '%s:', __( 'Hosted' ) ); ?></td>
                            <td><input
                                        type="text"
                                        onfocus="this.select()"
                                        class="regular-text code"
                                        value="<?php echo esc_attr( $form->get_hosted_url() ); ?>"
                                        readonly></td>
                        </tr>
                        </tbody>
                    </table>
                    <p>
						<?php echo Plugin::$instance->utils->html->modal_link( array(
							'title'              => __( 'Preview' ),
							'text'               => __( 'Preview' ),
							'footer_button_text' => __( 'Close' ),
							'id'                 => '',
							'class'              => 'button button-secondary',
							'source'             => $form_url,
							'height'             => 700,
							'width'              => 600,
							'footer'             => 'true',
							'preventSave'        => 'true',
						) );
						?>
                    </p>
                </td>
            </tr>
            <tr>
                <th>
					<?php esc_attr_e( 'Submit via AJAX:', 'groundhogg' ); ?>
                </th>
                <td>
					<?php

					$ajax_is_enabled = (bool) $this->get_setting( 'enable_ajax' );

					echo Plugin::$instance->utils->html->checkbox( [
						'label'   => __( 'Enable' ),
						'name'    => $this->setting_name_prefix( 'enable_ajax' ),
						'id'      => $this->setting_id_prefix( 'enable_ajax' ),
						'class'   => 'enable-ajax auto-save',
						'value'   => '1',
						'checked' => $ajax_is_enabled,
						'title'   => __( 'Enable Ajax' ),
					] ); ?>
                </td>
            </tr>
            <tr class="<?php echo $ajax_is_enabled ? '' : 'hidden'; ?>">
                <th>
					<?php esc_attr_e( 'Thank You Message:', 'groundhogg' ); ?>
                </th>
                <td>
					<?php

					$args = array(
						'id'    => $this->setting_id_prefix( 'success_message' ),
						'name'  => $this->setting_name_prefix( 'success_message' ),
						'title' => __( 'Thank You Message' ),
						'value' => $this->get_setting( 'success_message', __( 'Your submission has been received.' ) ),
						'cols'  => '',
						'rows'  => 3,
						'style' => [
							'width' => '100%'
						],
					);

					echo Plugin::$instance->utils->html->textarea( $args ); ?>
                </td>
            </tr>
            <tr class="<?php echo $ajax_is_enabled ? 'hidden' : ''; ?>">
                <th>
					<?php esc_attr_e( 'Thank You Page:', 'groundhogg' ); ?>
                </th>
                <td>
					<?php

					$args = array(
						'type'  => 'text',
						'id'    => $this->setting_id_prefix( 'success_page' ),
						'name'  => $this->setting_name_prefix( 'success_page' ),
						'title' => __( 'Thank You Page' ),
						'value' => $this->get_setting( 'success_page', home_url( 'thank-you/' ) )
					);

					echo Plugin::$instance->utils->html->link_picker( $args ); ?>
                </td>
            </tr>
            </tbody>
        </table>
        <table>
            <tbody>
            <tr>
                <td>
                    <div class="form-editor">
                        <div class="form-buttons">
							<?php

							$buttons = array(
								array(
									'text'  => __( 'Row', 'groundhogg' ),
									'class' => 'button button-secondary row'
								),
								array(
									'text'  => __( 'Col', 'groundhogg' ),
									'class' => 'button button-secondary col'
								),
								array(
									'text'  => __( 'First', 'groundhogg' ),
									'class' => 'button button-secondary first'
								),
								array(
									'text'  => __( 'Last', 'groundhogg' ),
									'class' => 'button button-secondary last'
								),
								array(
									'text'  => __( 'Email', 'groundhogg' ),
									'class' => 'button button-secondary email'
								),
								array(
									'text'  => __( 'Phone', 'groundhogg' ),
									'class' => 'button button-secondary phone'
								),
								array(
									'text'  => __( 'Address', 'groundhogg' ),
									'class' => 'button button-secondary address'
								),
								array(
									'text'  => __( 'Birthday', 'groundhogg' ),
									'class' => 'button button-secondary birthday'
								),
								array(
									'text'  => __( 'GDPR', 'groundhogg' ),
									'class' => 'button button-secondary gdpr'
								),
								array(
									'text'  => __( 'Terms', 'groundhogg' ),
									'class' => 'button button-secondary terms'
								),
								array(
									'text'  => __( 'Text', 'groundhogg' ),
									'class' => 'button button-secondary text'
								),
								array(
									'text'  => __( 'Textarea', 'groundhogg' ),
									'class' => 'button button-secondary textarea'
								),
								array(
									'text'  => __( 'Number', 'groundhogg' ),
									'class' => 'button button-secondary number'
								),
								array(
									'text'  => __( 'Dropdown', 'groundhogg' ),
									'class' => 'button button-secondary dropdown'
								),
								array(
									'text'  => __( 'Radio', 'groundhogg' ),
									'class' => 'button button-secondary radio'
								),
								array(
									'text'  => __( 'Checkbox', 'groundhogg' ),
									'class' => 'button button-secondary checkbox'
								),
								array(
									'text'  => __( 'Date', 'groundhogg' ),
									'class' => 'button button-secondary date'
								),
								array(
									'text'  => __( 'Time', 'groundhogg' ),
									'class' => 'button button-secondary time'
								),
								array(
									'text'  => __( 'File', 'groundhogg' ),
									'class' => 'button button-secondary file'
								),
								array(
									'text'  => __( 'ReCAPTCHA', 'groundhogg' ),
									'class' => 'button button-secondary recaptcha'
								),
								array(
									'text'  => __( 'Submit', 'groundhogg' ),
									'class' => 'button button-secondary submit'
								),
							);

							$buttons = apply_filters( 'wpgh_form_builder_buttons', $buttons );

							foreach ( $buttons as $button ) {

								$args = wp_parse_args( $button, array(
									'text'               => __( 'Field', 'groundhogg' ),
									'title'              => sprintf( __( 'Insert Field: %s', 'groundhogg' ), $button['text'] ),
									'class'              => 'button button-secondary column',
									'source'             => 'form-field-editor',
									'footer_button_text' => __( 'Insert Field', 'groundhogg' ),
									'width'              => 600,
									'height'             => 600
								) );

								echo Plugin::$instance->utils->html->modal_link( $args );
							} ?>
                        </div>

						<?php

						$code = $this->prettify( $this->get_setting( 'form', $default_form ) );
						$rows = min( substr_count( $code, "\n" ) + 1, 15 );

						$args = array(
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
						); ?>

						<?php echo Plugin::$instance->utils->html->textarea( $args ) ?>
                    </div>
                </td>
            </tr>
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
                        <th><?php _e( 'Required Field', 'groundhogg' ) ?></th>
                        <td><?php
							echo Plugin::$instance->utils->html->checkbox( array(
								'id'    => 'field-required',
								'name'  => 'required',
								'label' => __( 'Yes' ),
								'value' => 'true'
							) );
							?></td>
                    </tr>
                    <tr id="gh-field-label">
                        <th><?php _e( 'Label', 'groundhogg' ) ?></th>
                        <td><?php
							echo Plugin::$instance->utils->html->input( array(
								'id'   => 'field-label',
								'name' => 'label'
							) );
							?><p class="description"><?php _e( 'The field label.', 'groundhogg' ); ?></p></td>
                    </tr>
                    <tr id="gh-field-text">
                        <th><?php _e( 'Text', 'groundhogg' ) ?></th>
                        <td><?php
							echo Plugin::$instance->utils->html->input( array(
								'id'   => 'field-text',
								'name' => 'text'
							) );
							?><p class="description"><?php _e( 'The button text.', 'groundhogg' ); ?></p></td>
                    </tr>
                    <tr id="gh-field-placeholder">
                        <th><?php _e( 'Placeholder', 'groundhogg' ) ?></th>
                        <td><?php
							echo Plugin::$instance->utils->html->input( array(
								'id'   => 'field-placeholder',
								'name' => 'placeholder'
							) );
							?>
                            <p class="description"><?php _e( 'The ghost text within the field.', 'groundhogg' ); ?></p>
                        </td>
                    </tr>
                    <tr id="gh-field-name">
                        <th><?php _e( 'Name', 'groundhogg' ) ?></th>
                        <td><?php
							echo Plugin::$instance->utils->html->input( array(
								'id'   => 'field-name',
								'name' => 'name'
							) );
							?>
                            <p class="description"><?php _e( 'This will be the custom field name. I.E. {meta.name}', 'groundhogg' ) ?></p>
                        </td>
                    </tr>

                    <!--BEGIN NUMBER OPTIONS -->
                    <tr id="gh-field-min">
                        <th><?php _e( 'Min', 'groundhogg' ) ?></th>
                        <td><?php
							echo Plugin::$instance->utils->html->number( array(
								'id'    => 'field-min',
								'name'  => 'min',
								'class' => 'input'
							) );
							?>
                            <p class="description"><?php _e( 'The minimum number a user can enter.', 'groundhogg' ); ?></p>
                        </td>
                    </tr>
                    <tr id="gh-field-max">
                        <th><?php _e( 'Max', 'groundhogg' ) ?></th>
                        <td><?php
							echo Plugin::$instance->utils->html->number( array(
								'id'    => 'field-max',
								'name'  => 'max',
								'class' => 'input'
							) );
							?>
                            <p class="description"><?php _e( 'The max number a user can enter.', 'groundhogg' ); ?></p>
                        </td>
                    </tr>
                    <!-- END NUMBER OPTIONS -->

                    <tr id="gh-field-value">
                        <th><?php _e( 'Value', 'groundhogg' ) ?></th>
                        <td><?php
							echo Plugin::$instance->utils->html->input( array(
								'id'   => 'field-value',
								'name' => 'value'
							) );
							?><p class="description"><?php _e( 'The default value of the field.', 'groundhogg' ); ?></p>
                        </td>
                    </tr>
                    <tr id="gh-field-tag">
                        <th><?php _e( 'Add Tag', 'groundhogg' ) ?></th>
                        <td><?php
							echo Plugin::$instance->utils->html->tag_picker( array(
								'id'       => 'field-tag',
								'name'     => 'tag',
								'class'    => 'gh-single-tag-picker',
								'multiple' => false
							) );
							?>
                            <p class="description"><?php _e( 'Add a tag when this checkbox is selected.', 'groundhogg' ); ?></p>
                        </td>
                    </tr>

                    <tr id="gh-field-options">
                        <th><?php _e( 'Options', 'groundhogg' ) ?></th>
                        <td><?php
							echo Plugin::$instance->utils->html->textarea( array(
								'id'    => 'field-options',
								'name'  => 'options',
								'cols'  => 50,
								'rows'  => '5',
								'class' => 'hidden'
							) );
							?>
                            <div id='gh-option-table'>
                                <div class='option-wrapper' style='margin-bottom:10px;'>
                                    <div style='display: inline-block;width: 170px;vertical-align: top;'>
                                        <input type='text' class='input' style='float: left' name='option[]'
                                               placeholder='Option Text'>
                                    </div>
                                    <div style='display: inline-block;width: 220px;vertical-align: top;'>
                                        <select class='gh-single-tag-picker' name='tags[]'
                                                style='max-width: 140px;'></select>
                                    </div>
                                    <div style='display: inline-block;width: 20px;vertical-align: top;'>
                                        <span class="row-actions"><span class="delete"><a style="text-decoration: none"
                                                                                          href="javascript:void(0)"
                                                                                          class="deleteOption"><span
                                                            class="dashicons dashicons-trash"></span></a></span></span>
                                    </div>
                                </div>
                            </div>
                            <button type="button"
                                    class="button-secondary addoption"><?php _ex( 'Add Option', 'action', 'groundhogg' ); ?></button>
                            <!--                            <button type="button" id="btn-saveoption" class="button-primary">-->
							<?php //_ex( 'Save Options', 'action', 'groundhogg' );
							?><!--</button>-->
                            <p class="description"><?php _e( 'Enter option name to add option. Tags are optional.You need to save options when you make changes.', 'groundhogg' ) ?></p>
                        </td>
                    </tr>
                    <tr id="gh-field-multiple">
                        <th><?php _e( 'Allow Multiple Selections', 'groundhogg' ) ?></th>
                        <td><?php
							echo Plugin::$instance->utils->html->checkbox( array(
								'id'    => 'field-multiple',
								'name'  => 'multiple',
								'label' => __( 'Yes' )
							) );
							?></td>
                    </tr>
                    <tr id="gh-field-default">
                        <th><?php _e( 'Default', 'groundhogg' ) ?></th>
                        <td><?php
							echo Plugin::$instance->utils->html->input( array(
								'id'   => 'field-default',
								'name' => 'default',
								'cols' => 50,
								'rows' => '5'
							) );
							?>
                            <p class="description"><?php _e( 'The blank option which appears at the top of the list.', 'groundhogg' ) ?></p>
                        </td>
                    </tr>

                    <!-- BEGIN COLUMN OPTIONS -->
                    <tr id="gh-field-width">
                        <th><?php _e( 'Width', 'groundhogg' ) ?></th>
                        <td><?php
							echo Plugin::$instance->utils->html->dropdown( array(
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
							) );
							?><p class="description"><?php _e( 'The width of the column.', 'groundhogg' ); ?></p></td>
                    </tr>
                    <!-- END COLUMN OPTIONS -->

                    <!-- BEGIN CAPTCHA OPTIONS -->
					<?php if ( Form\Fields\Recaptcha::get_version() !== 'v3' ): ?>
                        <tr id="gh-field-captcha-theme">
                            <th><?php _e( 'Theme', 'groundhogg' ) ?></th>
                            <td><?php
								echo Plugin::$instance->utils->html->dropdown( array(
									'id'      => 'field-theme',
									'name'    => 'captcha-theme',
									'options' => array(
										'light' => 'Light',
										'dark'  => 'Dark',
									)
								) );
								?><p class="description"><?php _e( 'The CAPTCHA Theme.', 'groundhogg' ) ?></p></td>
                        </tr>
                        <tr id="gh-field-captcha-size">
                            <th><?php _e( 'Size', 'groundhogg' ) ?></th>
                            <td><?php
								echo Plugin::$instance->utils->html->dropdown( array(
									'id'      => 'field-captcha-size',
									'name'    => 'captcha-size',
									'options' => array(
										'normal'  => 'Normal',
										'compact' => 'Compact',
									)
								) );
								?><p class="description"><?php _e( 'The CAPTCHA Size.', 'groundhogg' ) ?></p></td>
                        </tr>
					<?php endif; ?>
                    <!-- END CAPTCHA OPTIONS -->

                    <!-- BEGIN DATE OPTIONS -->
                    <tr id="gh-field-min_date">
                        <th><?php _e( 'Min Date', 'groundhogg' ) ?></th>
                        <td><?php
							echo Plugin::$instance->utils->html->input( array(
								'type'        => 'date',
								'id'          => 'field-min_date',
								'name'        => 'min_date',
								'placeholder' => 'YYY-MM-DD or +3 days or -1 days'
							) );
							?>
                            <p class="description"><?php _e( 'The minimum date a user can enter. You can enter a dynamic date or static date.', 'groundhogg' ) ?></p>
                        </td>
                    </tr>
                    <tr id="gh-field-max_date">
                        <th><?php _e( 'Max Date', 'groundhogg' ) ?></th>
                        <td><?php
							echo Plugin::$instance->utils->html->input( array(
								'type'        => 'date',
								'id'          => 'field-max_date',
								'name'        => 'max_date',
								'placeholder' => 'YYY-MM-DD or +3 days or -1 days'
							) );
							?>
                            <p class="description"><?php _e( 'The maximum date a user can enter. You can enter a dynamic date or static date.', 'groundhogg' ) ?></p>
                        </td>
                    </tr>
                    <!-- END DATE OPTIONS -->

                    <!-- BEGIN TIME OPTIONS -->
                    <tr id="gh-field-min_time">
                        <th><?php _e( 'Min Time', 'groundhogg' ) ?></th>
                        <td><?php
							echo Plugin::$instance->utils->html->input( array(
								'type' => 'time',
								'id'   => 'field-min_time',
								'name' => 'min_time'
							) );
							?>
                            <p class="description"><?php _e( 'The minimum time a user can enter. You can enter a dynamic time or static time.', 'groundhogg' ) ?></p>
                        </td>
                    </tr>
                    <tr id="gh-field-max_time">
                        <th><?php _e( 'Max Time', 'groundhogg' ) ?></th>
                        <td><?php
							echo Plugin::$instance->utils->html->input( array(
								'type' => 'time',
								'id'   => 'field-max_time',
								'name' => 'max_time'
							) );
							?>
                            <p class="description"><?php _e( 'The maximum time a user can enter. You can enter a dynamic time or static time.', 'groundhogg' ) ?></p>
                        </td>
                    </tr>
                    <!-- END TIME OPTIONS -->

                    <!-- BEGIN FILE OPTIONS -->
                    <tr id="gh-field-max_file_size">
                        <th><?php _e( 'Max File Size', 'groundhogg' ) ?></th>
                        <td><?php
							echo Plugin::$instance->utils->html->number( array(
								'id'          => 'field-max_file_size',
								'name'        => 'max_file_size',
								'placeholder' => '1000000',
								'min'         => 0,
								'max'         => wp_max_upload_size() * 1000000
							) );
							?>
                            <p class="description"><?php printf( __( 'Maximum size a file can be <b>in Bytes</b>. Your max upload size is %d Bytes.', 'groundhogg' ), wp_max_upload_size() ); ?></p>
                        </td>
                    </tr>
                    <tr id="gh-field-file_types">
                        <th><?php _e( 'Accepted File Types', 'groundhogg' ) ?></th>
                        <td><?php
							echo Plugin::$instance->utils->html->input( array(
								'id'          => 'field-file_types',
								'name'        => 'file_types',
								'placeholder' => '.pdf,.txt,.doc,.docx'
							) );
							?>
                            <p class="description"><?php _e( 'The types of files a user may upload (comma separated). Leave empty to not specify.', 'groundhogg' ) ?></p>
                        </td>
                    </tr>
                    <!-- END FILE OPTIONS -->

                    <!-- BEGIN EXTENSION PLUGIN CUSTOM OPTIONS -->
					<?php do_action( 'groundhogg/steps/benchmarks/form/extra_settings' ); ?>
                    <!-- END EXTENSION PLUGIN CUSTOM OPTIONS -->

                    <!-- BEGIN CSS OPTIONS -->
                    <tr id="gh-field-id">
                        <th><?php _e( 'CSS ID', 'groundhogg' ) ?></th>
                        <td><?php
							echo Plugin::$instance->utils->html->input( array( 'id' => 'field-id', 'name' => 'id' ) );
							?><p class="description"><?php _e( 'Use to apply CSS.', 'groundhogg' ) ?></p></td>
                    </tr>
                    <tr id="gh-field-class">
                        <th><?php _e( 'CSS Class', 'groundhogg' ) ?></th>
                        <td><?php
							echo Plugin::$instance->utils->html->input( array(
								'id'   => 'field-class',
								'name' => 'class'
							) );
							?><p class="description"><?php _e( 'Use to apply CSS.', 'groundhogg' ) ?></p></td>
                    </tr>
                    <!-- END CSS OPTIONS -->
                    </tbody>
                </table>
            </form>
        </div>
		<?php
	}

	/**
	 * Save the step settings
	 *
	 * @param $step Step
	 */
	public function save( $step ) {
		$this->save_setting( 'form_name', sanitize_text_field( $this->get_posted_data( 'form_name' ) ) );
		$this->save_setting( 'form', wp_kses_post( $this->get_posted_data( 'form' ) ) );
		$this->save_setting( 'form_json', $this->get_posted_data( 'form_json', [] ) );
		$this->save_setting( 'success_page', sanitize_text_field( $this->get_posted_data( 'success_page' ) ) );
		$this->save_setting( 'success_message', sanitize_textarea_field( $this->get_posted_data( 'success_message' ) ) );
		$this->save_setting( 'enable_ajax', absint( $this->get_posted_data( 'enable_ajax' ) ) );

		// Render the config quietly
//		$form = do_shortcode( sprintf( "[gh_form id=%d]", $step->get_id() ) );

	}

	public function context( $context, $step ) {

		$form = new Form\Form( [ 'id' => $step->get_id() ] );

		$embed = [
			'shortcode' => sprintf( "[gh_form id=%d]", $step->get_id() ),
			'html'      => $form->get_html_embed_code(),
			'iframe'    => $form->get_iframe_embed_code(),
			'hosted'    => esc_url( $form->get_hosted_url() ),
		];

		$context[ 'embed' ] = $embed;
		$context[ 'url' ] = esc_url( $form->get_submission_url() );
		$context[ 'default_form_json' ] = convert_form_shortcode_to_json( $this->get_setting( 'form', $this->get_default_form() ) );
		$context[ 'recaptcha_is_v3' ] = get_option( 'gh_recaptcha_version', 'v2' ) ?: 'v2' === 'v3';

		return $context;
	}
}