<?php

namespace Groundhogg\Steps\Benchmarks;

use Groundhogg\Contact;
use Groundhogg\HTML;
use Groundhogg\Step;
use function Groundhogg\managed_page_url;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Link_Clicked extends Benchmark {

	public function get_help_article() {
		return 'https://docs.groundhogg.io/docs/builder/benchmarks/link-click/';
	}

	/**
	 * Get the element name
	 *
	 * @return string
	 */
	public function get_name() {
		return _x( 'Link Click', 'step_name', 'groundhogg' );
	}

	/**
	 * Get the element type
	 *
	 * @return string
	 */
	public function get_type() {
		return 'link_click';
	}

	/**
	 * Get the description
	 *
	 * @return string
	 */
	public function get_description() {
		return _x( 'Runs whenever a special link is clicked and redirects the user to another page.', 'step_description', 'groundhogg' );
	}

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/link-clicked.png';
	}

	/**
	 * @param $step Step
	 */
	public function settings( $step ) {

		$redirect_url = $step->get_meta( 'redirect_to' );

		$this->start_controls_section();

		$this->add_control( 'tracking_link', [
			'label'       => __( 'Copy This Link:', 'groundhogg' ),
			'type'        => HTML::INPUT,
			'default'     => sprintf( managed_page_url( "link/click/%d/" ), $step->get_id() ),
			'description' => __( 'Paste this link in any email or page. Once a contact clicks it the benchmark will be completed and the contact will be redirected to the page set below.', 'groundhogg' ),
			'field'       => [
				'class'    => 'regular-text code',
				'value'    => sprintf( managed_page_url( "link/click/%d/" ), $step->get_id() ),
				'onfocus'  => "this.select()",
				'readonly' => true,
			],
		] );

		$this->add_control( 'redirect_to', [
			'label'       => __( 'Redirect To:', 'groundhogg' ),
			'type'        => HTML::LINK_PICKER,
			'default'     => home_url(),
			'description' => __( 'Contacts will be redirected to this link.', 'groundhogg' ),
		] );

		$this->end_controls_section();
	}

	/**
	 * Save the step settings
	 *
	 * @param $step Step
	 */
	public function save( $step ) {
		$this->save_setting( 'redirect_to', sanitize_text_field( $this->get_posted_data( 'redirect_to', home_url() ) ) );
	}

	/**
	 * Get the hook for which the benchmark will run
	 *
	 * @return string[]
	 */
	protected function get_complete_hooks() {
		return [ 'groundhogg/rewrites/benchmark_link/clicked' => 2 ];
	}

	/**
	 *
	 *
	 * @param $contact Contact
	 * @param $step Step
	 */
	public function setup( $contact, $step ) {
		$this->set_current_contact( $contact );
		$this->add_data( 'link_id', $step->get_id() );
	}

	/**
	 * Get the contact from the data set.
	 *
	 * @return Contact
	 */
	protected function get_the_contact() {
		return $this->get_current_contact();
	}

	/**
	 * Based on the current step and contact,
	 *
	 * @return bool
	 */
	protected function can_complete_step() {
		$link_id = $this->get_data( 'link_id', 0 );

		return $this->get_current_step()->get_id() === $link_id;
	}
}