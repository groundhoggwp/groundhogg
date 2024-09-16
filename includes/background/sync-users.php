<?php

namespace Groundhogg\background;

use Groundhogg\Contact;
use Groundhogg\Contact_Query;
use Groundhogg\Preferences;
use function Groundhogg\_nf;
use function Groundhogg\bold_it;
use function Groundhogg\code_it;
use function Groundhogg\contact_filters_link;
use function Groundhogg\count_csv_rows;
use function Groundhogg\create_contact_from_user;
use function Groundhogg\files;
use function Groundhogg\generate_contact_with_map;
use function Groundhogg\get_array_var;
use function Groundhogg\get_csv_delimiter;
use function Groundhogg\is_a_contact;
use function Groundhogg\is_option_enabled;
use function Groundhogg\isset_not_empty;
use function Groundhogg\notices;
use function Groundhogg\percentage;
use function Groundhogg\track_activity;
use function Groundhogg\white_labeled_name;

class Sync_Users extends Task {

	protected int $batch;
	protected int $user_id;
	protected int $users;

	const BATCH_LIMIT = 100;

	public function __construct(int $batch = 0 ) {
		$this->user_id  = get_current_user_id();
		$this->batch    = $batch;

		$user_count = count_users();
		$num_users  = $user_count['total_users'];

		$this->users = $num_users;
	}

	public function get_progress() {
		return percentage( $this->users, $this->batch * self::BATCH_LIMIT );
	}

	public function get_batches_remaining() {
		return floor( $this->users / self::BATCH_LIMIT ) - $this->batch;
	}

	/**
	 * Title of the task
	 *
	 * @return string
	 */
	public function get_title(){
		return sprintf( 'Sync %s users', bold_it( _nf( $this->users ) ) );
	}

	/**
	 * Only runs once at the beginning of the task
	 *
	 * @return bool
	 */
	public function can_run() {
		return user_can( $this->user_id, 'edit_users' ) && user_can( $this->user_id, 'add_contacts' );
	}

	/**
	 * Process the items
	 *
	 * @return bool
	 */
	public function process(): bool {

		$user_query = new \WP_User_Query( [
			'number' => self::BATCH_LIMIT,
			'offset' => $this->batch * self::BATCH_LIMIT
		] );

		$users = $user_query->get_results();

		if ( empty( $users ) ) {
			$message = sprintf( __( '%s users have been synced!', 'groundhogg' ), bold_it( _nf( $this->users ) ) );
			notices()->add_user_notice( $message, 'success', true, $this->user_id );
			return true;
		}

		foreach ( $users as $user ){
			create_contact_from_user( $user, is_option_enabled( 'gh_sync_user_meta' ) );
		}

		$this->batch++;

		return false;
	}
}
