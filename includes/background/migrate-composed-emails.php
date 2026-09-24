<?php

namespace Groundhogg\Background;

use Groundhogg\Classes\Activity;
use Groundhogg\Classes\Message;
use function Groundhogg\_nf;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;
use function Groundhogg\percentage;
use function Groundhogg\redact;

/**
 * Moves historic `composed_email_sent` activity into the messages table.
 *
 * Activity only ever stored the (redacted) subject and the email log ID, so the body is
 * recovered from the email log when the log entry still exists, otherwise the message is subject only.
 *
 * Each activity row is deleted once its message exists, so the task can be interrupted and resumed safely.
 */
class Migrate_Composed_Emails extends Task {

	const ACTIVITY_TYPE = 'composed_email_sent';

	protected int $items = 0;
	protected int $migrated = 0;
	protected int $skipped = 0; // rows that couldn't be migrated, left in place and stepped over
	protected int $batchsize = 50;

	public function __construct() {
		parent::__construct();
		$this->items = get_db( 'activity' )->count( [ 'activity_type' => self::ACTIVITY_TYPE ] );
	}

	public function get_title() {
		/* translators: %s: number of emails */
		return sprintf( __( 'Move %s composed emails to messages', 'groundhogg' ), _nf( $this->items ) );
	}

	public function can_run() {
		return true;
	}

	public function process() {

		$rows = get_db( 'activity' )->query( [
			'activity_type' => self::ACTIVITY_TYPE,
			'limit'         => $this->batchsize,
			'offset'        => $this->skipped,
			'orderby'       => 'ID',
			'order'         => 'ASC',
		] );

		if ( empty( $rows ) ) {
			return true;
		}

		foreach ( $rows as $row ) {

			$activity = new Activity( $row );
			$contact  = get_contactdata( $activity->contact_id );

			if ( ! $contact || ! $contact->exists() ) {
				// orphaned, there's no one to associate the message with
				$activity->delete();
				continue;
			}

			$log     = null;
			$log_id  = absint( $activity->get_meta( 'log_id' ) );
			$content = '';

			if ( $log_id ) {
				$log = get_db( 'email_log' )->get( $log_id );
			}

			// never copy the body of a sensitive log out into a less restricted table
			if ( $log && empty( $log->is_sensitive ) ) {
				$content = redact( (string) $log->content );
			}

			$message = new Message();

			$created = $message->create( [
				'object_type'  => 'contact',
				'object_id'    => $contact->get_id(),
				'direction'    => Message::OUTBOUND,
				'user_id'      => absint( $activity->get_meta( 'sent_by' ) ),
				'from_address' => (string) $activity->get_meta( 'from' ),
				'to_address'   => $contact->get_email(),
				'subject'      => (string) $activity->get_meta( 'subject' ), // already redacted when recorded
				'content'      => $content,
				'email_log_id' => $log ? $log_id : 0,
				'status'       => 'sent',
				'date_created' => gmdate( 'Y-m-d H:i:s', $activity->get_timestamp() ),
			] );

			if ( ! $created ) {
				$this->skipped ++;
				continue;
			}

			$activity->delete();
			$this->migrated ++;
		}

		return false;
	}

	public function get_progress() {
		return percentage( $this->items, $this->migrated + $this->skipped );
	}

	public function get_batches_remaining() {
		return max( 0, (int) ceil( ( $this->items - $this->migrated - $this->skipped ) / $this->batchsize ) );
	}
}
