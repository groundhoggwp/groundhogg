<?php

namespace Groundhogg\Steps\Premium\Logic;

use Groundhogg\Contact;
use Groundhogg\Contact_Query;
use Groundhogg\DB\Query\Filters;
use Groundhogg\steps\logic\Branch_Logic;
use Groundhogg\Steps\Premium\Trait_Premium_Step;
use function Groundhogg\array_apply_callbacks;
use function Groundhogg\get_array_var;
use function Groundhogg\int_to_letters;

class Split_Path extends Branch_Logic {

	use Trait_Premium_Step;

	public function get_name() {
		return 'Multi Branch';
	}

	public function get_type() {
		return 'split_path';
	}

	public function get_description() {
		return 'Similar to Yes/No logic, but each branch has its own conditions.';
	}

	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/logic/split-path.svg';
	}

	/**
	 * Return the branches
	 *
	 * @return string[]
	 */
	public function get_branches() {

		$branches = $this->get_setting( 'branches' );
		$step_id  = $this->get_current_step()->ID;

		$defined_paths = array_map( [ $this, 'maybe_prefix_branch' ], array_keys( $branches ) );

		$defined_paths[] = $step_id . '-else';

		return $defined_paths;
	}

	/**
	 * Returns the branch the contact should travel
	 *
	 * @param Contact $contact
	 *
	 * @return string
	 */
	public function get_logic_branch( Contact $contact ) {

		$branches = $this->get_setting( 'branches', [] );

		foreach ( $branches as $branch_key => $branch_settings ) {
			$branch = $this->maybe_prefix_branch( $branch_key );
			if ( $this->matches_branch_conditions( $branch, $contact ) ) {
				return $branch;
			}
		}

		return 'else';

	}

	/**
	 * Whether the contact matches a given branch
	 *
	 * @param Contact $contact
	 * @param string  $branch
	 *
	 * @return bool
	 */
	public function matches_branch_conditions( string $branch, Contact $contact ) {

		if ( str_starts_with( $branch, $this->get_current_step()->ID . '-' ) ) {
			$parts  = explode( '-', $branch );
			$branch = $parts[1]; // this is the key within $branches
		}

		$branches = $this->get_setting( 'branches', [] );

		// can't match any of the previous branches
		if ( $branch === 'else' ) {

			// loop through branches to check and see if we match any
			foreach ( $branches as $branch_key => $branch_value ) {
				if ( $this->matches_branch_conditions( $this->maybe_prefix_branch( $branch_key ), $contact ) ) {
					return false;
				}
			}

			return true;
		}

		try {
			$branch = get_array_var( $branches, $branch, [] );

			// branch does not exist
			if ( ! $branch ) {
				return false;
			}

			$contactQuery = new Contact_Query( [
				'limit'           => 1,
				'include_filters' => get_array_var( $branch, 'include_filters', [] ),
				'exclude_filters' => get_array_var( $branch, 'exclude_filters', [] ),
				'include'         => [ $contact->ID ]
			] );

			$count = $contactQuery->count();

		} catch ( \Exception $exception ) {
			return false;
		}

		return $count === 1;
	}

	protected function sanitize_branch( $branch ) {
		return array_apply_callbacks( $branch, [
			'name'            => 'sanitize_text_field',
			'include_filters' => function ( $filters ) {
				if ( empty( $filters ) ) {
					return [];
				}

				return Filters::sanitize( $filters );
			},
			'exclude_filters' => function ( $filters ) {
				if ( empty( $filters ) ) {
					return [];
				}

				return Filters::sanitize( $filters );
			}
		], true );
	}

	/**
	 * Given an array of branches, sanitize the filters for each
	 * Also, if a branch is deleted, delete the steps within the branch as well.
	 *
	 * @param array $branches the branch configuration.
	 *
	 * @return array
	 */
	public function sanitize_branches( $branches ) {

		if ( ! is_array( $branches ) ) {
			return [];
		}

		$old_branches = $this->get_setting( 'branches', [] );

		// we have to delete steps associated with deleted branches
		if ( is_array( $old_branches ) && ! empty( $old_branches ) ) {

			$current_step    = $this->get_current_step();
			$delete_branches = array_keys( array_diff_key( $old_branches, $branches ) );

			// maybe delete steps in unused branches
			if ( ! empty( $delete_branches ) ) {

				$delete_keys = array_map( [ $this, 'maybe_prefix_branch' ], $delete_branches );

				$delete_steps = $current_step->get_funnel()->get_steps( [
					'branch' => $delete_keys
				] );

				foreach ( $delete_steps as $delete_step ) {
					$delete_step->delete();
				}

				// reset the current step
				$this->set_current_step( $current_step );
			}
		}

		$sanitized = [];

		foreach ( $branches as $key => $branch ) {
			$sanitized[ sanitize_key( $key ) ] = $this->sanitize_branch( $branch );
		}

		return $sanitized;
	}

	public function get_settings_schema() {
		return [
			'branches' => [
				'default'  => [],
				'sanitize' => [ $this, 'sanitize_branches' ],
				'initial'  => [
					'a' => [ 'name' => 'A', 'include_filters' => [], 'exclude_filters' => [] ],
					'b' => [ 'name' => 'B', 'include_filters' => [], 'exclude_filters' => [] ],
				]
			]
		];
	}

	public function generate_step_title( $step ) {
		return 'Multi-Branch';
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

		if ( $path === 'else' ) {
			return 'Else';
		}

		$i = 0;

		foreach ( $branches as $branch_key => $branch_value ) {

			if ( $branch_key === $path ) {
				return get_array_var( get_array_var( $branches, $path ), 'name', int_to_letters( $i ) );
			}

			$i ++;
		}

		return 'Else';
	}
}
