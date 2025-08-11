<?php

namespace Groundhogg\steps\benchmarks;

use Groundhogg\Contact;
use Groundhogg\Preferences;
use Groundhogg\Step;
use function Groundhogg\array_bold;
use function Groundhogg\html;
use function Groundhogg\orList;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tag Applied
 *
 * This will run whenever a tag is applied
 *
 * @since       File available since Release 0.9
 * @subpackage  Elements/Benchmarks
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Elements
 */
class Optin_Status_Changed extends Benchmark {

	const TYPE = 'optin_status_changed';

	public function get_help_article() {
		return 'https://docs.groundhogg.io/docs/builder/benchmarks/tag-applied/';
	}

	/**
	 * Get the element name
	 *
	 * @return string
	 */
	public function get_name() {
		return _x( 'Opt-in status changed', 'step_name', 'groundhogg' );
	}

	/**
	 * Get the element type
	 *
	 * @return string
	 */
	public function get_type() {
		return 'optin_status_changed';
	}

	public function get_sub_group() {
		return 'crm';
	}

	/**
	 * Get the description
	 *
	 * @return string
	 */
	public function get_description() {
		return _x( 'Runs whenever the opt-in status of a contact changes.', 'step_description', 'groundhogg' );
	}

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/crm/optin-status-changed.svg';
	}


	/**
	 * @param $step Step
	 */
	public function settings( $step ) {

		html( 'p', [], esc_html__( 'When a contact\'s opt-in status is changed from...', 'groundhogg' ) );

        html(  html()->select2( [
	        'id'          => $this->setting_id_prefix( 'from_status' ),
	        'name'        => $this->setting_name_prefix( 'from_status' ) . '[]',
	        'data'        => Preferences::get_preference_names(),
	        'selected'    => $this->get_setting( 'from_status', [] ),
	        'multiple'    => true,
	        'placeholder' => __( 'Any status', 'groundhogg' )
        ] ) );

		html( 'p', [], esc_html__( 'To any of these statuses...', 'groundhogg' ) );

		html( html()->select2( [
			'id'          => $this->setting_id_prefix( 'status' ),
			'name'        => $this->setting_name_prefix( 'status' ) . '[]',
			'data'        => Preferences::get_preference_names(),
			'selected'    => $this->get_setting( 'status', [] ),
			'multiple'    => true,
			'placeholder' => __( 'Any status', 'groundhogg' )
		] ) );

        html( 'p' );
	}

	public function sanitize_statuses( $statuses ) {
		if ( ! is_array( $statuses ) || empty( $statuses ) ) {
			return [];
		}

		return array_intersect( wp_parse_id_list( $statuses ), array_keys( Preferences::get_preference_names() ) );
	}

	public function get_settings_schema() {
		return [
			'from_status' => [
				'default'      => [],
				'if_undefined' => [],
				'sanitize'     => [ $this, 'sanitize_statuses' ]
			],
			'status'      => [
				'default'      => [],
				'if_undefined' => [],
				'sanitize'     => [ $this, 'sanitize_statuses' ]
			]
		];
	}

	public function generate_step_title( $step ) {

		$to_status   = array_map( [ Preferences::class, 'get_preference_pretty_name' ], $this->get_setting( 'status', [] ) );
		$from_status = array_map( [ Preferences::class, 'get_preference_pretty_name' ], $this->get_setting( 'from_status', [] ) );

		if ( empty( $to_status ) && ! empty( $from_status ) ) {
			$title = sprintf( 'Opt-in status changed from %s', orList( array_bold( $from_status ) ) );
		} else if ( ! empty( $to_status ) && empty( $from_status ) ) {
			$title = sprintf( 'Opt-in status changed to %s', orList( array_bold( $to_status ) ) );
		} else if ( ! empty( $to_status ) && ! empty( $from_status ) ) {
			$title = sprintf( 'Opt-in status changed from %s to %s', orList( array_bold( $from_status ) ), orList( array_bold( $to_status ) ) );
		} else {
			$title = 'Opt-in status is changed';
		}

		return $title;
	}

	/**
	 * get the hook for which the benchmark will run
	 *
	 * @return array
	 */
	protected function get_complete_hooks() {
		return [
			'groundhogg/contact/preferences/updated' => 4
		];
	}

	/**
	 * Setup
	 *
	 * @param int     $contact_id
	 * @param int     $new_status
	 * @param int     $old_status
	 * @param Contact $contact
	 */
	public function setup( int $contact_id, int $new_status, int $old_status, Contact $contact ) {
		$this->set_current_contact( $contact );
		$this->add_data( 'from', $old_status );
		$this->add_data( 'to', $new_status );
		$this->add_data( 'contact', $contact );
	}

	/**
	 * Get the contact from the data set.
	 *
	 * @return Contact
	 */
	protected function get_the_contact() {
		return $this->get_data( 'contact' );
	}

	/**
	 * Based on the current step and contact,
	 *
	 * @return bool
	 */
	protected function can_complete_step() {

		$from = $this->get_data( 'from' );
		$to   = $this->get_data( 'to' );

		$from_status_setting = $this->get_setting( 'from_status', [] );
		$to_status_setting   = $this->get_setting( 'status', [] );

		// from status is not empty and given from is not present
		if ( ! empty( $from_status_setting ) && ! in_array( $from, $from_status_setting ) ) {
			return false;
		}

		// to status is not empty and given to is not present
		if ( ! empty( $to_status_setting ) && ! in_array( $to, $to_status_setting ) ) {
			return false;
		}

		return true;
	}
}
