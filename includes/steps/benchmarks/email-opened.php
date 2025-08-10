<?php

namespace Groundhogg\Steps\Benchmarks;

use Groundhogg\Contact;
use Groundhogg\Email;
use Groundhogg\Step;
use Groundhogg\Steps\Actions\Send_Email;
use Groundhogg\Tracking;
use function Groundhogg\array_bold;
use function Groundhogg\array_map_to_class;
use function Groundhogg\get_contactdata;
use function Groundhogg\html;
use function Groundhogg\orList;

class Email_Opened extends Benchmark {

	public function is_legacy() {
		return true;
	}

	protected function get_complete_hooks() {
		return [
			'groundhogg/tracking/email/opened' => 1
		];
	}

	/**
	 * @param $tracking Tracking
	 */
	public function setup( $tracking ) {

		$contact_id = $tracking->get_current_contact_id();
		$step_id    = $tracking->get_current_event()->get_step_id();

		$this->add_data( 'contact_id', $contact_id );
		$this->add_data( 'step_id', $step_id );
	}

	/**
	 * @return false|Contact
	 */
	protected function get_the_contact() {
		return get_contactdata( $this->get_data( 'contact_id' ) );
	}

	protected function can_complete_step() {
		$email_step_ids     = wp_parse_id_list( $this->get_setting( 'email_steps' ) );
		$current_email_step = absint( $this->get_data( 'step_id' ) );

		return in_array( $current_email_step, $email_step_ids );
	}

	public function get_name() {
		return __( 'Email Opened', 'groundhogg-pro' );
	}

	public function get_type() {
		return 'email_opened';
	}

	public function get_sub_group() {
		return 'activity';
	}

	public function get_description() {
		return __( 'Runs when a contact opens an email that was sent from the funnel.', 'groundhogg-pro' );
	}

	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/activity/email-opened.svg';
	}

	public function after_step_warnings() {
		?>
        <div class="step-warnings">
		<?php

		echo html()->e( 'div', [ 'class' => 'notice notice-warning' ], [
			html()->e( 'p', [], __( 'This benchmark is available for legacy usage only. Running automation based on email opens is <b>NOT</b> a good idea. False positives are frequent. We recommend you use the <b>Link Click</b> benchmark instead.', 'groundhogg-pro' ) )
		] );

		?>
        </div><?php
	}

	/**
	 * Output settings
	 *
	 * @param Step $step
	 */
	public function settings( $step ) {

		$funnel      = $step->get_funnel();
		$email_steps = array_filter( $funnel->get_steps(), function ( Step $other ) use ( $step ) {
			return $other->is_before( $step ) && $other->type_is( Send_Email::TYPE );
		} );

		html()->start_form_table();

		html()->start_row();

		html()->th( esc_html__( 'Select email steps:', 'groundhogg' ) );

		$td_content = [];

		$email_options = [];

		foreach ( $email_steps as $email_step ) {

			$email_id = absint( $email_step->get_meta( 'email_id' ) );

			if ( $email_id ) {

				$email = new Email( $email_id );

				$email_options[ $email_step->get_id() ] = sprintf( "%d. %s (%s)", $email_step->get_order(), $email_step->get_title(), $email->get_title() );
			}

		}

		$td_content[] = html()->select2( [
			'name'     => $this->setting_name_prefix( 'email_steps' ) . '[]',
			'id'       => $this->setting_id_prefix( 'email_steps' ),
			'data'     => $email_options,
			'multiple' => true,
			'selected' => wp_parse_id_list( $this->get_setting( 'email_steps' ) )
		] );

		$td_content[] = html()->description( __( 'Update the funnel to show new email steps in the email step picker.', 'groundhogg-pro' ) );

		html()->td( $td_content );

		html()->end_row();

		html()->end_form_table();

		// TODO: Implement settings() method.
	}

	public function save( $step ) {
		$this->save_setting( 'email_steps', wp_parse_id_list( $this->get_posted_data( 'email_steps' ) ) );
	}

	public function generate_step_title( $step ) {

		$email_steps = $this->get_setting( 'email_steps' );
		array_map_to_class( $email_steps, Step::class );

        if ( empty( $email_steps ) ){
            return 'Email opened';
        }

		$email_titles = array_map( function ( $step ) {
			$email_id = absint( $step->get_meta( 'email_id' ) );

			if ( ! $email_id ) {
				return false;
			}

			$email = new Email( $email_id );

			return $email->get_title();

		}, $email_steps );

		return sprintf( 'Opens %s', orList( array_bold( array_unique( array_filter( $email_titles ) ) ) ) );
	}
}
