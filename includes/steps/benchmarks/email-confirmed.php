<?php

namespace Groundhogg\Steps\Benchmarks;

use Groundhogg\Contact;
use function Groundhogg\get_contactdata;
use Groundhogg\Preferences;
use Groundhogg\Step;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email Confirmed
 *
 * This will run whenever an email is confirmed
 *
 * @package     Elements
 * @subpackage  Elements/Benchmarks
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */
class Email_Confirmed extends Benchmark {

	public function get_help_article() {
		return 'https://docs.groundhogg.io/docs/builder/benchmarks/email-confirmed/';
	}

	/**
	 * Get the element name
	 *
	 * @return string
	 */
	public function get_name() {
		return _x( 'Email Confirmed', 'step_name', 'groundhogg' );
	}

	/**
	 * Get the element type
	 *
	 * @return string
	 */
	public function get_type() {
		return 'email_confirmed';
	}

	/**
	 * Get the description
	 *
	 * @return string
	 */
	public function get_description() {
		return _x( 'Runs whenever a contact confirms their email.', 'step_description', 'groundhogg' );
	}

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/email-confirmed.png';
	}

	/**
	 * @param $step Step
	 */
	public function settings( $step ) {
		?>
		<table class="form-table">
			<tbody>
			<tr>
				<td>
					<p class="description"><?php _e( 'Runs whenever an email is confirmed while in this funnel', 'groundhogg' ); ?></p>
				</td>
			</tr>
			</tbody>
		</table>

		<?php
	}

	/**
	 * Save the step settings
	 *
	 * @param $step Step
	 */
	public function save( $step ) {
		//code is poetry...
	}

	/**
	 * get the hook for which the benchmark will run
	 *
	 * @return string[]
	 */
	protected function get_complete_hooks() {
		return [
			'groundhogg/contact/preferences/updated' => 3,
			'groundhogg/step/email/confirmed'        => 3
		];
	}

	/**
	 * @param $contact_id int
	 * @param $preference int
	 * @param $old_preference int
	 */
	public function setup( $contact_id, $preference, $old_preference ) {
		$this->add_data( 'contact_id', $contact_id );
		$this->add_data( 'preference', $preference );
	}

	/**
	 * Get the contact from the data set.
	 *
	 * @return Contact
	 */
	protected function get_the_contact() {
		return get_contactdata( $this->get_data( 'contact_id' ) );
	}

	/**
	 * Based on the current step and contact,
	 *
	 * @return bool
	 */
	protected function can_complete_step() {
		return $this->get_data( 'preference' ) === Preferences::CONFIRMED && $this->get_current_contact()->get_optin_status() === Preferences::CONFIRMED;
	}
}
