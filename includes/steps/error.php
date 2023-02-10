<?php

namespace Groundhogg\Steps;

use Groundhogg\Step;
use function Groundhogg\html;
use function Groundhogg\key_to_words;

class Error extends Funnel_Step {

	public function get_name() {
		return __( 'Error' );
	}

	public function get_type() {
		return 'error';
	}

	public function get_group() {
		return false;
	}

	public function get_description() {
		return '';
	}

	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/no-icon.png';
	}

	/**
	 * @param \Groundhogg\Contact $contact
	 * @param \Groundhogg\Event   $event
	 *
	 * @return bool|\WP_Error
	 */
	public function run( $contact, $event ) {
		return new \WP_Error( 'invalid_step_type', 'This step type is not active.' );
	}

	public function before_step_warnings() {
		$this->add_error( 'error', __( 'No settings were found for this step type. This may be because you disabled an add-on which utilized this step type. You should either enable the addon that registers this step type, or delete this step from the funnel.', 'groundhogg' ) );
	}

	/**
	 * @param Step $step
	 */
	public function settings( $step ) {
	}

	/**
	 * @param Step $step
	 *
	 * @return bool
	 */
	public function save( $step ) {
	}
}
