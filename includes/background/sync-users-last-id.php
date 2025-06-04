<?php

namespace Groundhogg\background;

use function Groundhogg\_nf;
use function Groundhogg\bold_it;
use function Groundhogg\create_contact_from_user;
use function Groundhogg\is_option_enabled;
use function Groundhogg\notices;

class Sync_Users_Last_Id extends Sync_Users {

	protected int $last_id;

	public function __construct( int $batch = 0 ) {
		$this->last_id = 0;
		parent::__construct( $batch );
	}

	/**
	 * Filter the WP_User_Query by last_id
	 *
	 * @param \WP_User_Query $query
	 *
	 * @return void
	 */
	public function filter_last_id( \WP_User_Query &$query ) {
		$query->query_where .= ' AND ID > ' . $this->last_id;
	}

	/**
	 * Process the items
	 *
	 * @return bool
	 */
	public function process(): bool {

		add_filter( 'pre_user_query', [ $this, 'filter_last_id' ] );

		$user_query = new \WP_User_Query( [
			'number'  => self::BATCH_LIMIT,
			'orderby' => 'ID',
			'order'   => 'ASC',
		] );

		$users = $user_query->get_results();

		remove_filter( 'pre_user_query', [ $this, 'filter_last_id' ]  );

		if ( empty( $users ) ) {
			$message = sprintf( __( '%s users have been synced!', 'groundhogg' ), bold_it( _nf( $this->users ) ) );
			notices()->add_user_notice( $message, 'success', true, $this->user_id );

			return true;
		}

		$sync_meta = is_option_enabled( 'gh_sync_user_meta' );

		foreach ( $users as $user ) {
			$this->last_id = $user->ID;
			create_contact_from_user( $user, $sync_meta );
		}

		$this->batch ++;

		return false;
	}
}
