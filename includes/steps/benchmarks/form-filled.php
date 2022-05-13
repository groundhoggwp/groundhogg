<?php

namespace Groundhogg\Steps\Benchmarks;

use Groundhogg\Contact;
use Groundhogg\Reporting\Reporting;
use Groundhogg\Utils\Graph;
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

	public function get_help_article() {
		return 'https://docs.groundhogg.io/docs/builder/benchmarks/web-form/';
	}

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
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/form-filled.png';
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
	 * Save the step settings
	 *
	 * @param $step Step
	 */
	public function save( $step ) {

		$form = $this->get_posted_data( 'form' );

		$sanitized = [
			'fields'    => $form['fields'],
			'recaptcha' => $form['recaptcha'],
			'button'    => $form['button'],
		];

		$this->save_setting( 'form', $sanitized );
		$this->save_setting( 'form_name', sanitize_text_field( $this->get_posted_data('form_name')));
		$this->save_setting( 'success_page', sanitize_text_field( $this->get_posted_data( 'success_page' ) ) );
		$this->save_setting( 'success_message', sanitize_textarea_field( $this->get_posted_data( 'success_message' ) ) );
		$this->save_setting( 'enable_ajax', boolval( $this->get_posted_data( 'enable_ajax' ) ) );
	}

	public function settings( $step ) {
		// TODO: Implement settings() method.
	}
}
