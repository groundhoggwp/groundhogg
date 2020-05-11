<?php

namespace Groundhogg\Admin\Guided_Setup\Steps;

use function Groundhogg\admin_page_url;
use function Groundhogg\dashicon;
use function Groundhogg\get_request_var;
use Groundhogg\Plugin;
use function Groundhogg\html;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-02-27
 * Time: 10:56 AM
 */
abstract class Step {

	public function __construct() {
		if ( $this->get_slug() === sanitize_key( get_request_var( 'guided_setup_step_save' ) ) ) {
			add_action( 'init', [ $this, 'load_dependencies' ] );
			add_action( 'admin_init', [ $this, 'go_to_next' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'scripts' ] );
		}
	}

	/**
	 * Any scripts that need to be loaded.
	 */
	public function scripts() {
	}

	/**
	 * Allow overwriting of dependencies.
	 */
	public function load_dependencies() {
	}

	/**
	 * @return string
	 */
	abstract public function get_title();

	/**
	 * @return string
	 */
	abstract public function get_slug();

	/**
	 * @return string
	 */
	abstract public function get_description();

	/**
	 * @return void
	 */
	abstract public function get_content();

	/**
	 * @return bool
	 */
	abstract public function save();

	/**
	 * Save the settings, if successful go to next step.
	 */
	public function go_to_next() {
		if ( $this->get_slug() === sanitize_key( get_request_var( 'guided_setup_step_save' ) ) ) {
			if ( ! wp_verify_nonce( get_request_var( '_wpnonce' ) ) || ! current_user_can( 'manage_options' ) ) {
				return;
			}

			if ( $this->save() ) {
				Plugin::$instance->notices->add( 'save', _x( 'Configuration saved!', 'guided_setup', 'groundhogg' ) );
				wp_redirect( admin_page_url( 'gh_guided_setup', [ 'step' => $this->get_current_step_id() + 1 ] ) );
				die();
			}
		}
	}

	/**
	 * Get the current step progression, or false if none defined.
	 *
	 * @return int
	 */
	public function get_current_step_id() {
		return absint( get_request_var( 'step' ) );
	}

	protected function step_nav() {
		echo html()->wrap( dashicon( 'yes' ) . __( 'Next', 'groundhogg' ), 'button', [
			'class' => 'button button-primary big-button next-button',
			'type'  => 'submit',
		] );
	}

	protected function step_url( $step_id = 0 ) {
		return add_query_arg( [ 'step' => $step_id ], admin_page_url( 'gh_guided_setup' ) );
	}

	protected function next_step_url() {
		return $this->step_url( $this->get_current_step_id() + 1 );
	}

	protected function prev_step_url() {
		return $this->step_url( $this->get_current_step_id() - 1 );
	}

	/**
	 * Get default step content.
	 *
	 * @return void
	 */
	public function view() {

		echo html()->input( array(
			'type'  => 'hidden',
			'name'  => 'guided_setup_step_save',
			'value' => $this->get_slug(),
		) ); ?>
        <div class="big-header" style="text-align: center;margin: 1.5em;">
            <span style="font-size: 40px;line-height: 1.2em;"><b><?php echo $this->get_title(); ?></b></span>
        </div>
        <div id="notices">
            <h1 class="hidden">&nbsp;</h1>
			<?php Plugin::$instance->notices->print_notices(); ?>
        </div>
        <div class="setup-wrap <?php esc_attr_e( $this->get_slug() );?>">
            <div class="postbox">
                <div class="inside">
					<?php if ( ! empty( $this->get_description() ) ): ?>
                        <div class="description">
                            <p>
								<?php echo $this->get_description(); ?>
                            </p>
                        </div>
                        <hr>
					<?php endif; ?>
					<?php $this->get_content(); ?>
                    <div class="step-nav">
						<?php $this->step_nav(); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="setup-footer-nav">
			<?php $this->footer_nav(); ?>
        </div>
		<?php
	}

	protected function footer_nav() {
		$links = [];

		$links[] = html()->e( 'a', [ 'href' => $this->prev_step_url() ], __( '&larr; Back' ) );
		$links[] = html()->e( 'a', [ 'href' => $this->next_step_url() ], __( 'Skip this step &rarr;' ) );

		echo implode( ' | ', $links );
	}

}