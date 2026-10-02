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

	const MAX_ATTEMPTS = 5;

	protected int $added = 0;
	protected int $attempts = 0; // failed builds in a row

	public function get_title() {
		return __( 'Add indexes to the activity table for faster reports and segments', 'groundhogg' );
	}

	public function can_run() {
		return true;
	}

	/**
	 * Returning null stops this pass and leaves the task to be picked up again on the next one, false would go
	 * straight to the next step, which is no use when what's in the way is another build or an error.
	 *
	 * @return bool|null|\WP_Error
	 */
	public function process() {

		// a build from an earlier run is still going, wait for it instead of queueing a second ALTER behind it
		if ( db()->activity->is_being_altered() ) {
			return null;
		}

		$done = db()->activity->add_next_performance_index();

		if ( is_wp_error( $done ) ) {

			// the index isn't added with a locking ALTER instead, so it's tried again a few times, then the task fails with the database's error
			if ( ++ $this->attempts < self::MAX_ATTEMPTS ) {
				return null;
			}

			return $done;
		}

		$this->attempts = 0;

		if ( $done !== false ) {
			return $done; // true
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
