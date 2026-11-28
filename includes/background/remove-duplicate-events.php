<?php

namespace Groundhogg\Background;

use Groundhogg\Broadcast;
use function Groundhogg\_nf;
use function Groundhogg\bold_it;
use function Groundhogg\get_db;
use function Groundhogg\notices;
use function Groundhogg\percentage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Removes the copies of events that were added to the history table more than once, which made reports like the sent
 * count of a broadcast too high. Goes through the table a range of IDs at a time, oldest first, keeping the row with the
 * lowest ID of each event.
 *
 * Only rows that match on every column the move copies are removed, see Events::DUPLICATE_MATCH_COLUMNS.
 */
class Remove_Duplicate_Events extends Task {

	/**
	 * How many IDs each batch covers. Every batch is a range scan on the primary key plus a queued_idx lookup per row.
	 */
	const BATCH_SIZE = 10000;

	protected int $last_id = 0;
	protected int $max_id = 0;
	protected int $deleted = 0;
	protected int $user_id = 0;

	public function __construct() {
		global $wpdb;

		$events = get_db( 'events' )->get_table_name();

		// rows added after this were moved with the duplicate check, so there's no need to go further
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name
		$this->max_id  = absint( $wpdb->get_var( "SELECT MAX(ID) FROM $events" ) );
		$this->user_id = get_current_user_id();
	}

	public function get_title() {
		return __( 'Remove duplicate events from the event history', 'groundhogg' );
	}

	public function can_run() {
		return true;
	}

	/**
	 * @return bool
	 */
	public function process() {

		if ( $this->last_id >= $this->max_id ) {

			/* translators: %s: the number of duplicate events removed */
			$message = sprintf( esc_html__( 'Removed %s duplicate events from the event history.', 'groundhogg' ), bold_it( _nf( $this->deleted ) ) );
			notices()->add_user_notice( $message, 'success', true, $this->user_id );

			return true;
		}

		$up_to = min( $this->last_id + self::BATCH_SIZE, $this->max_id );

		$result = get_db( 'events' )->delete_duplicate_events( $this->last_id, $up_to );

		$this->deleted += $result['deleted'];
		$this->last_id = $up_to;

		// the sent count of a broadcast is cached once it's sent
		foreach ( $result['broadcast_ids'] as $broadcast_id ) {
			$broadcast = new Broadcast( $broadcast_id );
			if ( $broadcast->exists() ) {
				$broadcast->clear_cached_report_data();
			}
		}

		return false;
	}

	public function get_progress() {
		return percentage( $this->max_id, $this->last_id );
	}

	public function get_batches_remaining() {
		return (int) ceil( max( 0, $this->max_id - $this->last_id ) / self::BATCH_SIZE );
	}
}
