<?php

namespace Groundhogg\Steps\Benchmarks;

use Groundhogg\Contact;
use Groundhogg\Email;
use Groundhogg\Step;
use function Groundhogg\dashicon;
use function Groundhogg\get_hostname;
use function Groundhogg\html;
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

	public function get_sub_group() {
		return 'activity';
	}

	/**
	 * Get the description
	 *
	 * @return string
	 */
	public function get_description() {
		return _x( 'Runs whenever the special tracking link is clicked and redirects the user to the target page.', 'step_description', 'groundhogg' );
	}

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	public function get_icon() {
//		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/link-clicked.png';
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/activity/link-click.svg';
	}

	/**
	 * @param $step Step
	 */
	public function settings( $step ) {

		$redirect_url = $step->get_meta( 'redirect_to' );

		echo html()->e( 'p', [], __( 'Copy this link to use in emails or on your website...', 'groundhogg' ) );

		echo html()->input( [
			'class'    => 'regular-text code',
			'value'    => sprintf( managed_page_url( "click/%s/" ), $step->get_slug() ),
			'onfocus'  => "this.select()",
			'readonly' => true,
		] );

		echo html()->e( 'p', [], __( 'When the link is clicked, redirect to...', 'groundhogg' ) );

		echo html()->e( 'div', [
			'class' => 'gh-input-group'
		], [
			html()->link_picker( [
				'placeholder' => 'https://example.com',
				'name'        => $this->setting_name_prefix( 'redirect_to' ),
				'value'       => $this->get_setting( 'redirect_to' )
			] ),
			html()->e( 'a', [
				'href'   => $redirect_url,
				'target' => '_blank',
				'class'  => 'gh-button secondary icon'
			], dashicon( 'external' ) )
		] );

		?><p></p><?php
	}

	/**
	 * added __ to disable
	 *
	 * @param $step
	 *
	 * @return string
	 */
	public function __generate_step_title( $step ) {

		$redirect_url = $step->get_meta( 'redirect_to' );

		$basename = basename( $redirect_url );

		$file = wp_check_filetype( $basename );

		if ( $file && $file['type'] ) {
			return 'Downloads <b>' . $basename . '</b>';
		}

		$hostname = get_hostname( $redirect_url );
		$path     = wp_parse_url( $redirect_url, PHP_URL_PATH );

		if ( $hostname === get_hostname() && ! empty( $path ) ) {
			$path = '<code>' . $path . '</code>';
		} else {
			$path = '<b>' . $hostname . '</b>';
		}

		return 'Track click to ' . $path;
	}

	/**
	 * Save the step settings
	 *
	 * @param $step Step
	 */
	public function save( $step ) {
		$this->save_setting( 'redirect_to', sanitize_text_field( $this->get_posted_data( 'redirect_to', home_url() ) ) );

		$slug_in_use = $this->get_setting( 'slug_in_use' );

		if ( $slug_in_use !== $step->get_slug() ) {
//			$this->replace_links_in_email_content( $slug_in_use, $step );
			$this->save_setting( 'slug_in_use', $step->get_slug() );
		}
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
	 * @param $step    Step
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

	/**
	 * Update email content when slug changes
	 *
	 * @param $old_slug string
	 * @param $new_slug string
	 * @param $step     Step
	 *
	 * @return void
	 */
	protected function replace_links_in_email_content( $old_slug, $step ) {

		$new_url       = sprintf( managed_page_url( "click/%s/" ), $step->get_slug() );
		$old_url_regex = "@https?://[A-z0-9/\-.]+/gh/click/$old_slug/@";

		$send_email_steps = $step->get_funnel()->get_steps( [
			'step_type' => 'send_email'
		] );

		foreach ( $send_email_steps as $send_email_step ) {
			$email = new Email( $send_email_step->get_meta( 'email_id' ) );

			if ( ! $email->exists() ) {
				continue;
			}

			$content = preg_replace( $old_url_regex, $new_url, $email->get_content() );
			$email->update( [
				'content' => $content
			] );
		}
	}

	/**
	 * Search and replace emails for the link click url
	 *
	 * @param $step Step
	 *
	 * @return void
	 */
	public function post_import( $step ) {

		// get all send-email steps in the funnel
		// loop through all the emails
		// search and replace for the old URL and the new URL

		$old_slug = $step->get_meta( 'imported_step_id' ) . '-' . sanitize_title( $step->get_step_title() );
		$this->replace_links_in_email_content( $old_slug, $step );
	}
}
