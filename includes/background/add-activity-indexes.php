<?php

namespace Groundhogg\Background;

use Groundhogg\DB\Activity;
use function Groundhogg\db;
use function Groundhogg\percentage;

/**
 * Adds Activity::PERFORMANCE_INDEXES to an existing activity table, one index per step.
 *
 * Each index is built online (writes to the table carry on), but can still take minutes on a large table,
 * so this runs in the background instead of holding up the request that runs the update.
 */
class Add_Activity_Indexes extends Task {

	protected int $added = 0;

	public function get_title() {
		return __( 'Add indexes to the activity table for faster reports and segments', 'groundhogg' );
	}

	public function can_run() {
		return true;
	}

	public function process() {

		// a build from an earlier run is still going, wait for it instead of queueing a second ALTER behind it
		if ( db()->activity->is_being_altered() ) {
			sleep( 5 );

			return false;
		}

		$done = db()->activity->add_next_performance_index();

		if ( $done !== false ) {
			return $done; // true, or WP_Error which fails the task
		}

		$this->added ++;

		return false;
	}

	public function get_progress() {
		return percentage( count( Activity::PERFORMANCE_INDEXES ), $this->added );
	}

	public function get_batches_remaining() {
		return max( 0, count( Activity::PERFORMANCE_INDEXES ) - $this->added );
	}
}
