<?php

namespace Groundhogg\steps\logic;

use Groundhogg\Contact;
use function Groundhogg\array_apply_callbacks;
use function Groundhogg\get_array_var;
use function Groundhogg\html;

class Weighted_Distribution extends Split_Path {

	public function get_name() {
		return 'Weighted Distribution';
	}

	public function get_type() {
		return 'weighted_distribution';
	}

	public function get_description() {
		// TODO: Implement get_description() method.
	}

	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/remove-tag.svg';
	}

	public function settings( $step ) {

		echo html()->e( 'p', [], __( 'Add as many branches as you want and assign each a "weight". The total weight does not necessarily need to equal 100, but it\'s a good idea for simplicity.' ) );

		echo html()->e( 'div', [ 'id' => $this->setting_id_prefix( 'branches' ) ] );

		?><p></p><?php
	}

	/**
	 * Return the branches
	 *
	 * @return string[]
	 */
	public function get_branches() {

		$branches = $this->get_setting( 'branches' );
		$step_id  = $this->get_current_step()->ID;

		return array_map( function ( $branch_key ) use ( $step_id ) {
			return $step_id . '-' . $branch_key;
		}, array_keys( $branches ) );
	}

	/**
	 * Since we overrode get_branch_action() we can always return true from here since there's no conditional logic
	 *
	 * @param Contact $contact
	 * @param string  $branch
	 *
	 * @return bool
	 */
	public function matches_branch_conditions( string $branch, Contact $contact ) {
		return true;
	}

	/**
	 * Cleverly distribute contacts among the branches based on weighted distribution
	 *
	 * @param Contact $contact
	 *
	 * @return false|\Groundhogg\Step
	 */
	public function get_branch_action( Contact $contact ) {

		$branches = $this->get_setting( 'branches' );

		$total_weight = array_sum( wp_list_pluck( $branches, 'weight' ) );

		$random     = rand( 1, $total_weight ); // Generate a random number between 1 and 100
		$cumulative = 0;

		foreach ( $branches as $branch_key => $branch ) {

			$weight     = absint( $branch['weight'] );
			$cumulative += $weight;

			if ( $random <= $cumulative ) {
				return $this->get_first_of_branch( $branch_key ); // Return the chosen path
			}
		}

		return false;
	}

	protected function sanitize_branch( $branch ) {
		return array_apply_callbacks( $branch, [
			'weight' => 'absint',
		], true );
	}

	public function generate_step_title( $step ) {
		return 'Weighted Distribution';
	}

	/**
	 * @param $branch
	 *
	 * @return bool|mixed|string
	 */
	protected function get_branch_name( $branch ) {

		$path = explode( '-', $branch );
		$path = $path[1];

		$branches = $this->get_setting( 'branches' );

		$total_weight = array_sum( wp_list_pluck( $branches, 'weight' ) );

		foreach ( $branches as $branch_key => $branch_value ) {

			if ( $branch_key === $path ) {
				$name = get_array_var( get_array_var( $branches, $path ), 'weight' );

				return $total_weight <= 100 ? $name . '%' : $name;
			}
		}

		return '?';
	}
}
