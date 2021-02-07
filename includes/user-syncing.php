<?php

namespace Groundhogg;

class User_Syncing {

	public function __construct() {
		add_action( 'user_register', [ $this, 'sync_new_user' ], 10, 1 );
		add_action( 'profile_update', [ $this, 'sync_existing_user' ], 10, 2 );

		if ( is_option_enabled( 'gh_sync_user_meta' ) ) {
			add_action( 'added_user_meta', [ $this, 'user_meta_added' ], 10, 4 );
			add_action( 'updated_user_meta', [ $this, 'user_meta_updated' ], 10, 4 );
			add_action( 'deleted_user_meta', [ $this, 'user_meta_deleted' ], 10, 4 );
		}
	}

	/**
	 * Sync a new user to the contacts
	 *
	 * @param $user_id
	 */
	public function sync_new_user( $user_id ) {
		convert_user_to_contact_when_user_registered( $user_id );
	}

	/**
	 * Sync existing users with the contacts
	 *
	 * @param $user_id
	 * @param $old_data
	 */
	public function sync_existing_user( $user_id, $old_data ) {
		create_contact_from_user( $user_id, is_option_enabled( 'gh_sync_user_meta' ) );
	}

	/**
	 * List of meta keys for the user record that are considered primary for the contact
	 *
	 * @param $meta_key
	 *
	 * @return bool
	 */
	public static function is_primary_meta_key( $meta_key ) {

		$keys = [
			'first_name',
			'last_name',
			'email',
		];

		return in_array( $meta_key, $keys );
	}

	/**
	 * List of meta keys which should not be synced because they are not relevant in the Contact context
	 *
	 * @param $meta_key
	 *
	 * @return bool
	 */
	public static function is_meta_ignored( $meta_key ) {
		$keys = [
			'dismissed_wp_pointers',
			'show_welcome_panel',
			'rich_editing',
			'syntax_highlighting',
			'comment_shortcuts',
			'admin_color',
			'use_ssl',
			'show_admin_bar_front',
			'locale',
			'wp_capabilities',
			'wp_user_level',
			'dismissed_wp_pointers',
			'show_welcome_panel',
			'session_tokens',
			'wp_dashboard_quick_press_last_post_id',
			'groundhogg_info_card_order',
		];

		return in_array( $meta_key, $keys );
	}

	/**
	 * Add meta to the contact record when it is added to the user
	 *
	 * @param $meta_id int
	 * @param $object_id int
	 * @param $meta_key string
	 * @param $_meta_value mixed
	 */
	public function user_meta_added( $meta_id, $object_id, $meta_key, $_meta_value ) {
		$contact = get_contactdata( $object_id, true );

		if ( ! is_a_contact( $contact ) || self::is_meta_ignored( $meta_key ) ) {
			return;
		}

		if ( self::is_primary_meta_key( $meta_key ) ) {
			$contact->update( [
				$meta_key => $_meta_value
			] );
		} else {
			$contact->add_meta( $meta_key, $_meta_value );
		}
	}

	/**
	 * When user meta is updated, update it in the contact record.
	 *
	 * @param $meta_id int
	 * @param $object_id int
	 * @param $meta_key string
	 * @param $_meta_value mixed
	 */
	public function user_meta_updated( $meta_id, $object_id, $meta_key, $_meta_value ) {

		$contact = get_contactdata( $object_id, true );

		if ( ! is_a_contact( $contact ) || self::is_meta_ignored( $meta_key ) ) {
			return;
		}

		if ( self::is_primary_meta_key( $meta_key ) ){
			$contact->update( [
				$meta_key => $_meta_value
			] );
		} else {
			$contact->update_meta( $meta_key, $_meta_value );
		}
	}

	/**
	 * Delete meta from the contact record when it is deleted from the user
	 *
	 * @param $meta_id int
	 * @param $object_id int
	 * @param $meta_key string
	 * @param $_meta_value mixed
	 */
	public function user_meta_deleted( $meta_id, $object_id, $meta_key, $_meta_value ) {
		$contact = get_contactdata( $object_id, true );

		if ( ! is_a_contact( $contact ) || self::is_meta_ignored( $meta_key ) ) {
			return;
		}

		$contact->delete_meta( $meta_key );
	}
}
