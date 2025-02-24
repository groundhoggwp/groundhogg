<?php

namespace Groundhogg\steps\logic;

use Groundhogg\Contact;
use Groundhogg\Contact_Query;
use Groundhogg\DB\Query\Filters;
use function Groundhogg\array_apply_callbacks;
use function Groundhogg\get_array_var;
use function Groundhogg\html;

class Split_Path extends Logic {

	public function get_name() {
		return 'Split Path';
	}

	public function get_type() {
		return 'split_path';
	}

	public function get_description() {
		// TODO: Implement get_description() method.
	}

	public function get_sub_group() {
		return 'logic';
	}

	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/remove-tag.svg';
	}

	public function settings( $step ) {

		echo html()->e( 'p', [], __( 'You can specify as many paths as you would like. The contact will travel down the first path they match with, from left to right (top to bottom).' ) );

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

		$defined_paths = array_map( [ $this, 'maybe_prefix_branch' ], array_keys( $branches ) );

		$defined_paths[] = $step_id . '-else';

		return $defined_paths;
	}

	/**
	 * Whether the contact matches a given branch
	 *
	 * @throws \Groundhogg\DB\Query\FilterException
	 *
	 * @param Contact $contact
	 * @param string  $branch
	 *
	 * @return bool
	 */
	public function matches_branch_conditions( string $branch, Contact $contact ) {
		$path = explode( '-', $branch );
		$path = $path[1]; // this is the key within $branches

		$branches = $this->get_setting( 'branches' );

		// can't match any of the previous branches
		if ( $path === 'else' ) {

			// loop through branches to check and see if we match any
			foreach ( $branches as $branch_key => $branch_value ) {
				if ( $this->matches_branch_conditions( $branch_key, $contact ) ) {
					return false;
				}
			}

			return true;
		}

		$branch = get_array_var( $branches, $path, [] );

		// branch does not exist
		if ( ! $branch ) {
			return false;
		}

		$contactQuery = new Contact_Query();
		$contactQuery->set_query_params( [
			'limit'           => 1,
			'include_filters' => get_array_var( $branch, 'include_filters', [] ),
			'exclude_filters' => get_array_var( $branch, 'exclude_filters', [] ),
			'include'         => [ $contact->ID ]
		] );

		$count = $contactQuery->count();

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

			$current_step = $this->get_current_step();
			$step_id      = $current_step->ID;

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
				'sanitize' => [ $this, 'sanitize_branches' ]
			]
		];
	}

	public function generate_step_title( $step ) {
		return 'Split Path';
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

		$i = 1;

		foreach ( $branches as $branch_key => $branch_value ) {

			if ( $branch_key === $path ) {
				return get_array_var( get_array_var( $branches, $path ), 'name', 'Path ' . $i );
			}

			$i ++;
		}

		return 'Else';
	}
}
