<?php

namespace Groundhogg\Notifications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The emails that Groundhogg sends to its users, and which of them a user has asked for.
 *
 * What a user has asked for is user meta, and a notification can be more than one meta key: unsubscribing from My Day
 * takes away the team section along with the rest of it, since it's one email.
 */
class Subscriptions {

	/**
	 * The notifications a user can opt out of
	 *
	 * @return array[] id => [ label, meta (the meta keys that say a user wants it), cap (what a user needs to get it) ]
	 */
	public static function types(): array {

		$types = [
			'broadcast_results'    => [
				'label' => __( 'next-day broadcast results', 'groundhogg' ),
				'meta'  => [ 'gh_broadcast_results' ],
				'cap'   => 'view_reports',
			],
			'performance_overview' => [
				'label' => __( 'monthly and weekly performance overviews', 'groundhogg' ),
				'meta'  => [ 'gh_weekly_overview' ],
				'cap'   => 'view_reports',
			],
			'my_day'               => [
				'label' => __( 'daily summaries of your day', 'groundhogg' ),
				'meta'  => [ 'gh_my_day', 'gh_my_day_team' ],
				'cap'   => 'view_contacts',
			],
		];

		/**
		 * The notifications a user can opt out of. An add-on that sends its own can add it, and its emails can then carry
		 * the same unsubscribe link.
		 *
		 * @param array[] $types id => [ label, meta, cap ]
		 */
		return apply_filters( 'groundhogg/notifications/types', $types );
	}

	/**
	 * @param string $id
	 *
	 * @return array|false
	 */
	public static function get( string $id ) {
		$types = self::types();

		return $types[ $id ] ?? false;
	}

	/**
	 * Whether the user has asked for a notification
	 *
	 * @param int    $user_id
	 * @param string $id
	 *
	 * @return bool
	 */
	public static function is_subscribed( int $user_id, string $id ): bool {

		$type = self::get( $id );

		if ( ! $type ) {
			return false;
		}

		foreach ( $type['meta'] as $key ) {
			if ( get_user_meta( $user_id, $key, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Stop sending a notification to a user
	 *
	 * @param int    $user_id
	 * @param string $id
	 *
	 * @return bool whether it's a notification at all
	 */
	public static function unsubscribe( int $user_id, string $id ): bool {

		$type = self::get( $id );

		if ( ! $type ) {
			return false;
		}

		foreach ( $type['meta'] as $key ) {
			delete_user_meta( $user_id, $key );
		}

		return true;
	}

	/**
	 * The link in an email that stops it, which works without signing in. It outlasts the email's other links by a long
	 * way, since an email can be opened a year after it came and all that it can do is turn something off.
	 *
	 * @param int    $user_id
	 * @param string $id
	 *
	 * @return string
	 */
	public static function unsubscribe_url( int $user_id, string $id ): string {
		return Signed_Link::url( 'unsub', $user_id, $id, YEAR_IN_SECONDS );
	}
}
