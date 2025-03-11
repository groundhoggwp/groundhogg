<?php

namespace Groundhogg\Steps\Logic;

use Groundhogg\Contact;
use Groundhogg\Contact_Query;
use Groundhogg\DB\Query\Filters;
use Groundhogg\Step;
use function Groundhogg\html;

class If_Else extends Branch_Logic {

	public function get_name() {
		return 'Yes/No';
	}

	public function get_type() {
		return 'if_else';
	}

	public function get_description() {
		return 'Segment contacts down the Yes branch if they meet specific criteria.';
	}

	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/yes-no.svg';
	}

	public function settings( $step ) {

		echo html()->e( 'p', [], __( 'If the contact matches the conditions they will go down the <span class="pill green">YES</span> branch, otherwise the <span class="pill red">NO</span> branch.' ) );

		echo html()->e( 'div', [ 'class' => 'include-search-filters' ], [ html()->e( 'div', [ 'id' => $this->setting_id_prefix( 'include_filters' ) ] ) ] );
		echo html()->e( 'div', [ 'class' => 'exclude-search-filters' ], [ html()->e( 'div', [ 'id' => $this->setting_id_prefix( 'exclude_filters' ) ] ) ] );

		?><p></p><?php
	}

	/**
	 * @param Step $step
	 *
	 * @return void
	 */
	public function validate_settings( Step $step ) {

		$include_filters = $this->get_setting( 'include_filters' );
		$exclude_filters = $this->get_setting( 'exclude_filters' );

		if ( empty( $include_filters ) && empty( $exclude_filters ) ) {
			$step->add_error( new \WP_Error( 'empty_filters', 'No filters have been configured, so all contacts will travel the yes branch by default.' ) );
		}
	}

	public function get_settings_schema() {
		return [
			'include_filters' => [
				'default'  => [],
				'sanitize' => function ( $filters ) {

					if ( empty( $filters ) ) {
						return [];
					}

					return Filters::sanitize( $filters );
				}
			],
			'include_display' => [
				'default'  => '',
				'sanitize' => function ( $value ) {
					return wp_kses( $value, 'data' );
				}
			],
			'exclude_filters' => [
				'default'  => [],
				'sanitize' => function ( $filters ) {

					if ( empty( $filters ) ) {
						return [];
					}

					return Filters::sanitize( $filters );
				}
			],
			'exclude_display' => [
				'default'  => '',
				'sanitize' => function ( $value ) {
					return wp_kses( $value, 'data' );
				}
			],
		];
	}

	/**
	 * Yes/No only has yes, no branches
	 *
	 * @return string[]
	 */
	public function get_branches() {
		return [
			$this->maybe_prefix_branch( 'yes' ),
			$this->maybe_prefix_branch( 'no' ),
		];
	}

	/**
	 * Returns YES or NO
	 *
	 * @param string $branch
	 *
	 * @return string YES/NO
	 */
	protected function get_branch_name( $branch ) {
		return strtoupper( explode( '-', $branch )[1] );
	}

    protected function get_branch_classes( $branch_id ): string {
	    return str_ends_with( $branch_id, '-no' ) ? 'red' : 'green';
    }

	/**
	 * Which path?
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

		$include_filters = $this->get_setting( 'include_filters', [] );
		$exclude_filters = $this->get_setting( 'exclude_filters', [] );

		if ( empty( $include_filters ) && empty( $exclude_filters ) ) {
			return $path === 'yes';
		}

		// ideally this query will be cached in the event it gets run more than once.
		$contactQuery = new Contact_Query( [
			'limit'           => 1,
			'include_filters' => $this->get_setting( 'include_filters', [] ),
			'exclude_filters' => $this->get_setting( 'exclude_filters', [] ),
			'include'         => [ $contact->ID ]
		] );

		$count = $contactQuery->count();

		switch ( $path ) {
			case 'yes':
				return $count === 1;
			case 'no':
				return $count === 0;
		}

		return false;
	}

    public function get_logic_action( Contact $contact ) {

        $include_filters = $this->get_setting( 'include_filters', [] );
        $exclude_filters = $this->get_setting( 'exclude_filters', [] );

        if ( empty( $include_filters ) && empty( $exclude_filters ) ) {
            return $this->get_first_of_branch( 'yes' );
        }

	    $contactQuery = new Contact_Query( [
		    'limit'           => 1,
		    'include_filters' => $this->get_setting( 'include_filters', [] ),
		    'exclude_filters' => $this->get_setting( 'exclude_filters', [] ),
		    'include'         => [ $contact->ID ]
	    ] );

	    $count = $contactQuery->count();

        return $this->get_first_of_branch( $count === 0 ? 'no' : 'yes' );
    }

	/**
	 * Step title
	 *
	 * @param $step
	 *
	 * @return false|string
	 */
	public function generate_step_title( $step ) {

		$include_display = $this->get_setting( 'include_display' );
		$exclude_display = $this->get_setting( 'exclude_display' );

		$parts = [];

		if ( ! empty( $include_display ) ) {
			$parts[] = $include_display;
		}

		if ( ! empty( $exclude_display ) ) {
			$parts[] = $exclude_display;
		}

		return implode( ' AND NOT ', $parts );
	}
}
