<?php

namespace Groundhogg\Steps;

use Groundhogg\Step;
use function Groundhogg\bold_it;

trait Trait_Polyfill {

	public function __construct( Step $step ) {
		$this->set_current_step( $step );
	}

	public function get_name() {
		return strtoupper( str_replace( '_', ' ', $this->get_type() ) );
	}

	public function get_type() {
		return $this->get_current_step()->get_type();
	}

	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/warning.svg';
	}

	public function get_description() {
		// TODO: Implement get_description() method.
	}

	public function settings( $step ) {
		?><p>This step type was not properly registered. Possibly because you do not have the required add-ons installed.</p><?php
		?><p></p><?php
	}

	public function validate_settings( Step $step ) {
		$step->add_error( 'invalid-type', sprintf( "The type %s has not been registered.", bold_it( $this->get_name() ) ) );
	}

	/**
	 * False will ensure the step is skipped, rather than stopping the funnel
	 *
	 * @param $contact
	 * @param $event
	 *
	 * @return false
	 */
	public function run( $contact, $event ) {
		return false;
	}

}
