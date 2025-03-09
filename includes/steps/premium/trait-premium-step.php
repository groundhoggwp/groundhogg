<?php

namespace Groundhogg\Steps\Premium;

use Groundhogg\Contact;
use function Groundhogg\html;
use function Groundhogg\is_pro_features_active;

trait Trait_Premium_Step {

	public function run( $contact, $event ) {

		if ( is_pro_features_active() ) {
			return true;
		}

		//do nothing
		return new \WP_Error( 'premium', 'This step requires a premium license.' );
	}

	public function is_premium() {
		return true;
	}

	protected function get_complete_hooks() {
		return [];
	}

	protected function get_the_contact() {
		return false;
	}

	protected function can_complete_step() {
		return false;
	}

	public function settings( $step ) {

		echo html()->e( 'p', [], sprintf( 'The %s step is a paid feature. Upgrade to unlock it along with 20+ other premium steps!', $this->get_name() ) );
		echo html()->e( 'a', [
			'href'   => 'https://groundhogg.io/pricing/',
			'target' => '_blank',
			'class'  => 'gh-button primary'
		], 'Upgrade now!' );

		?><p></p><?php

	}

	public function generate_step_title( $step ) {
		return $this->get_name();
	}

	public function get_logic_action( Contact $contact ) {
		return false;
	}

}
