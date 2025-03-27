<?php

namespace Groundhogg\Steps\Premium\Logic;

use Groundhogg\Contact;
use Groundhogg\Step;
use Groundhogg\steps\logic\Branch_Logic;
use Groundhogg\Steps\Premium\Trait_Premium_Step;

class Evergreen_Sequence extends Branch_Logic {

	use Trait_Premium_Step;

	protected function settings_should_ignore_morph() {
		return false;
	}

	public function get_branches() {
		return [
			$this->maybe_prefix_branch( 'eg' )
		];
	}

	public function get_sub_group() {
		return 'special';
	}

	protected function get_branch_name( $branch ) {
		return 'Sequence';
	}

	public function matches_branch_conditions( string $branch, Contact $contact ) {
		return true;
	}

	public function get_name() {
		return 'Smart Date Sequence';
	}

	public function get_type() {
		return 'evergreen_sequence';
	}

	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/logic/smart-date-sequence.svg';
	}

	public function get_description() {
		return 'Contacts will jump to the timer closest to the current date.';
	}

	public function get_sub_timer_steps() {
		return array_filter( $this->get_sub_steps( 'eg' ), function ( $proceeding ) {
			return $proceeding->is_timer();
		} );
	}

	public function get_settings_schema() {
		return [
			'timers' => [
				'sanitize'     => function ( $value ) {
					$ids = wp_parse_id_list( $value );

					return array_filter( $ids, function ( $id ) {
						return ( new Step( $id ) )->exists();
					} );
				},
				'default'      => [],
				'initial'      => [],
				'if_undefined' => []
			]
		];
	}

	protected function get_branch_classes( $branch_id ): string {
		return 'evergreen';
	}

	/**
	 * Move the contact to the correct timer within the evergreen sequence
	 *
	 * @param Contact $contact
	 *
	 * @return false|\Groundhogg\Step|mixed
	 */
	public function get_logic_action( Contact $contact ) {

		$timers = $this->get_sub_timer_steps();

		if ( empty( $timers ) ) {
			return $this->get_first_of_branch( 'eg' );
		}

		$specific_timers = $this->get_setting( 'timers' );

		foreach ( $timers as $timer ) {

			// The user chose specific timers, and the current timer was not selected
			if ( ! empty( $specific_timers ) && is_array( $specific_timers ) && ! in_array( $timer->ID, $specific_timers ) ) {
				continue;
			}

			if ( $this->get_current_step()->get_funnel()->is_active() && ! $timer->is_active() ) {
				continue;
			}

			$diff = $timer->get_run_time() - time();

			if ( $diff < 0 ) {
				continue;
			}

			$calculated[ $diff ] = $timer;
		}

		if ( empty( $calculated ) ) {
			return false;
		}

		$min = min( array_keys( $calculated ) );

		return $calculated[ $min ];

	}
}
