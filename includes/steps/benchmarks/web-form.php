<?php

namespace Groundhogg\Steps\Benchmarks;

use Groundhogg\Form;
use Groundhogg\Plugin;
use Groundhogg\Step;
use function Groundhogg\encrypt;
use function Groundhogg\html;
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

class Web_Form extends Benchmark {


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
//		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/contact-form.svg';
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/web-form.svg';
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

	public function html_v2( $step ) {
		?>
        <div data-id="<?php echo $step->get_id(); ?>" data-type="<?php esc_attr_e( $this->get_type() ); ?>"
             title="<?php echo $step->get_title() ?>" id="settings-<?php echo $step->get_id(); ?>"
             class="step <?php echo $step->get_group(); ?> <?php echo $step->get_type(); ?>">

            <!-- WARNINGS -->
			<?php if ( $step->has_errors() ): ?>
                <div class="step-warnings">
					<?php foreach ( $step->get_errors() as $error ): ?>

                        <div id="<?php $error->get_error_code() ?>"
                             class="notice notice-warning is-dismissible">
							<?php echo wpautop( wp_kses_post( $error->get_error_message() ) ); ?>
                        </div>
					<?php endforeach; ?>
                </div>
			<?php endif; ?>
            <!-- SETTINGS -->
            <div class="step-flex">
                <div class="step-edit panels">
                    <div class="gh-panel">
						<?php $this->step_title_edit( $step ); ?>
                    </div>

					<?php $this->settings( $step ); ?>

					<?php do_action( "groundhogg/steps/{$this->get_type()}/settings/before", $step ); ?>
					<?php do_action( 'groundhogg/steps/settings/before', $this ); ?>
					<?php do_action( "groundhogg/steps/{$this->get_type()}/settings/after", $step ); ?>
					<?php do_action( 'groundhogg/steps/settings/after', $this ); ?>
                </div>
                <div class="step-notes">
					<?php $this->before_step_notes( $step ); ?>
					<?php if ( $step->is_benchmark() ): ?>
                        <div class="gh-panel benchmark-settings">
                            <div class="gh-panel-header">
                                <h2><?php _e( 'Settings', 'groundhogg' ); ?></h2>
                            </div>
                            <div class="inside display-flex gap-20 column">
								<?php if ( ! $step->is_starting() ):

									echo html()->checkbox( [
										'label'   => 'Allow contacts to enter the funnel at this step',
										'name'    => $this->setting_name_prefix( 'is_entry' ),
										'checked' => $step->is_entry()
									] );

								endif;

								echo html()->checkbox( [
									'label'   => 'Track conversion when completed',
									'name'    => $this->setting_name_prefix( 'is_conversion' ),
									'checked' => $step->is_conversion()
								] );

								?>
                            </div>
                        </div>
					<?php endif; ?>
					<?php
					echo html()->textarea( [
						'id'          => $this->setting_id_prefix( 'step-notes' ),
						'name'        => $this->setting_name_prefix( 'step_notes' ),
						'value'       => $step->get_step_notes(),
						'placeholder' => __( 'You can use this area to store custom notes about the step.', 'groundhogg' ),
						'class'       => 'step-notes-textarea'
					] );
					?>
                </div>
            </div>
        </div>
		<?php
	}

	protected function before_step_notes( Step $step ) {

		$form     = new Form\Form_v2( [ 'id' => $step->get_id() ] );
		$form_url = managed_page_url( sprintf( 'forms/%s/', urlencode( encrypt( $step->get_id() ) ) ) );

		?>
        <div class="gh-panel">
            <div class="gh-panel-header">
                <h2><?php _e( 'Embed options' ) ?></h2>
            </div>
            <div class="inside">
                <div class="display-flex column gap-10">
                    <label><?php printf( '%s:', __( 'Shortcode' ) ); ?></label>
                    <input
                            type="text"
                            onfocus="this.select()"
                            class="regular-text code"
                            value="<?php echo esc_attr( $form->get_shortcode() ); ?>"
                            readonly>
                    <label><?php printf( '%s:', __( 'Iframe' ) ); ?></label>
                    <input
                            type="text"
                            onfocus="this.select()"
                            class="regular-text code"
                            value="<?php echo esc_attr( $form->get_iframe_embed_code() ); ?>"
                            readonly>
                    <label><?php printf( '%s:', __( 'Hosted' ) ); ?></label>
                    <input
                            type="text"
                            onfocus="this.select()"
                            class="regular-text code"
                            value="<?php echo esc_attr( $form->get_submission_url() ); ?>"
                            readonly>
                </div>
                <p>
					<?php echo Plugin::$instance->utils->html->modal_link( array(
						'title'              => __( 'Preview' ),
						'text'               => __( 'Preview' ),
						'footer_button_text' => __( 'Close' ),
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
		echo html()->e( 'div', [
			'id' => "step_{$step->ID}_web_form_builder"
		], 'Form Builder' );
	}


	/**
	 * Save the step settings
	 *
	 * @param $step Step
	 */
	public function save( $step ) {

	}

	protected function get_the_contact() {
		return false;
	}

	protected function can_complete_step() {
		return false;
	}
}
