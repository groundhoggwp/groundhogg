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
 * @since       File available since Release 0.9
 * @subpackage  Elements/Benchmarks
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Elements
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
		return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/email-confirmed.png';
	}

	/**
	 * Register the controls for this step
	 *
	 */
	public function register_controls() {
		$this->start_controls_section( 'general', [
			'label' => __( 'Conditions', 'groundhogg' )
		] );

		$this->add_control( 'skip_to', [
			'label'       => __( 'Skip to this step?', 'groundhogg' ),
			'type'        => 'yes_no_toggle',
			'description' => __( 'Skip to this step if the contact has already confirmed their email address.', 'groundhogg' )
		] );

		$this->end_controls_section();	}

	/**
	 * Save the step settings
	 *
	 * @param $step Step
	 * @param $settings array
	 */
	public function save( $step, $settings ) {
		$this->save_setting( 'skip_to', (bool) $this->get_posted_data( 'skip_to', false ) );
	}

	/**
	 * get the hook for which the benchmark will run
	 *
	 * @return string[]
	 */
	protected function get_complete_hooks() {
		return [
			'groundhogg/contact/preferences/updated' => 3,
			'groundhogg/step/email/confirmed'        => 4
		];
	}

	/**
	 * @param $contact_id     int
	 * @param $preference     int
	 * @param $old_preference int
	 * @param $funnel_id      int|bool only passed in SendEMail step if skip if confirmed is enabled.
	 */
	public function setup( $contact_id, $preference, $old_preference, $funnel_id = false ) {
		$this->add_data( 'contact_id', $contact_id );
		$this->add_data( 'preference', $preference );
		$this->add_data( 'funnel_id', $funnel_id );
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
		$funnel_id = $this->get_data( 'funnel_id' );

		// False if the funnel ID is set (From Email step) and the current step is not in that funnel
		if ( $funnel_id && $funnel_id !== $this->get_current_step()->get_funnel_id() ) {
			return false;
		}

		return $this->get_data( 'preference' ) === Preferences::CONFIRMED && $this->get_current_contact()->get_optin_status() === Preferences::CONFIRMED;
	}
}