<?php

namespace Groundhogg\Steps\Actions;

use Groundhogg\Admin\Funnels\Simulator;
use Groundhogg\Contact;
use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Step;
use function Groundhogg\bold_it;
use function Groundhogg\html;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add to Flow
 *
 * Enrolls the contact in another (or the same) flow, either at its first
 * action step or at a specific step chosen in the settings.
 *
 * @since       File available since Release 4.8.2
 * @subpackage  Elements/Actions
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2026, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Elements
 */
class Add_To_Flow extends Action {

	/**
	 * @return string
	 */
	public function get_name() {
		return esc_html_x( 'Add to Flow', 'step_name', 'groundhogg' );
	}

	/**
	 * @return string
	 */
	public function get_type() {
		return 'add_to_flow';
	}

	public function get_sub_group() {
		return 'crm';
	}

	/**
	 * @return string
	 */
	public function get_description() {
		return _x( 'Add the contact to another flow.', 'step_description', 'groundhogg' );
	}

	/**
	 * @return string
	 */
	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/crm/add-to-flow.svg';
	}

	/**
	 * @param $step Step
	 */
	public function settings( $step ) {

		html( 'p', [], esc_html__( 'Add the contact to the following flow...', 'groundhogg' ) );

		html( 'div', [ 'class' => 'display-flex column gap-10' ], [
			html()->e( 'div', [ 'id' => $this->setting_id_prefix( 'funnel' ) ] ),
			html()->e( 'div', [ 'id' => $this->setting_id_prefix( 'funnel_step' ) ] )
		] );

		html( 'p' );
	}

	public function get_settings_schema() {
		return [
			'funnel_id' => [
				'default'  => 0,
				'sanitize' => 'absint',
			],
			'step_id'   => [
				'default'  => 0,
				'sanitize' => 'absint',
			],
		];
	}

	public function validate_settings( Step $step ) {

		$funnel_id = absint( $this->get_setting( 'funnel_id' ) );

		if ( ! $funnel_id ) {
			$step->add_error( 'no_flow', __( 'No flow has been selected.', 'groundhogg' ) );

			return;
		}

		$funnel = new Funnel( $funnel_id );

		if ( ! $funnel->exists() ) {
			$step->add_error( 'flow_not_found', __( 'The selected flow no longer exists.', 'groundhogg' ) );

			return;
		}

		$step_id = absint( $this->get_setting( 'step_id' ) );

		if ( $step_id ) {
			$target_step = new Step( $step_id );

			if ( ! $target_step->exists() || $target_step->get_funnel_id() !== $funnel->get_id() ) {
				$step->add_error( 'step_not_in_flow', __( 'The selected step does not belong to the selected flow.', 'groundhogg' ) );
			}
		}
	}

	public function generate_step_title( $step ) {

		$funnel_id = absint( $this->get_setting( 'funnel_id' ) );

		if ( ! $funnel_id ) {
			return __( 'Add to flow', 'groundhogg' );
		}

		$funnel = new Funnel( $funnel_id );

		if ( ! $funnel->exists() ) {
			return __( 'Add to flow', 'groundhogg' );
		}

		/* translators: %s: the name of the flow the contact will be added to */
		return sprintf( __( 'Add to %s', 'groundhogg' ), '<b>' . esc_html( $funnel->get_title() ) . '</b>' );
	}

	/**
	 * @param $contact Contact
	 * @param $event   Event
	 *
	 * @return bool|\WP_Error
	 */
	public function run( $contact, $event ) {

		$funnel = new Funnel( absint( $this->get_setting( 'funnel_id' ) ) );

		if ( ! $funnel->exists() ) {
			return new \WP_Error( 'invalid_flow', __( 'The selected flow no longer exists.', 'groundhogg' ) );
		}

		if ( ! $funnel->is_active() ) {
			return new \WP_Error( 'flow_not_active', __( 'The selected flow is not active.', 'groundhogg' ) );
		}

		$step_id     = absint( $this->get_setting( 'step_id' ) );
		$target_step = $step_id ? new Step( $step_id ) : null;

		if ( ! $target_step || ! $target_step->exists() || $target_step->get_funnel_id() !== $funnel->get_id() ) {
			$target_step = new Step( $funnel->get_first_action_id() );
		}

		if ( ! $target_step->exists() ) {
			return new \WP_Error( 'no_entry_step', __( 'The selected flow has no valid entry step.', 'groundhogg' ) );
		}

		$result = $target_step->enqueue( $contact );

		if ( is_wp_error( $result ) ) {
			Simulator::log( sprintf( '❌ Failed to add to %s: %s', bold_it( $funnel->get_title() ), $result->get_error_message() ) );
		} else if ( $result ) {
			Simulator::log( sprintf( '➡️ Added to %s at %s', bold_it( $funnel->get_title() ), bold_it( $target_step->get_title() ) ) );
		} else {
			Simulator::log( sprintf( '⚠️ Not added to %s - already in progress or ineligible', bold_it( $funnel->get_title() ) ) );
		}

		return $result;
	}

	/**
	 * @param array $args
	 * @param Step  $step
	 */
	public function import( $args, $step ) {
		if ( ! empty( $args['funnel_id'] ) ) {
			$this->save_setting( 'funnel_id', absint( $args['funnel_id'] ) );
		}

		if ( ! empty( $args['step_id'] ) ) {
			$this->save_setting( 'step_id', absint( $args['step_id'] ) );
		}
	}

	/**
	 * @param array $args
	 * @param Step  $step
	 *
	 * @return array
	 */
	public function export( $args, $step ) {
		$args['funnel_id'] = absint( $this->get_setting( 'funnel_id' ) );
		$args['step_id']   = absint( $this->get_setting( 'step_id' ) );

		return $args;
	}
}
