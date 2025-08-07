<?php

namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
class User_Syncing {

	public function __construct() {
		add_action( 'user_register', [ $this, 'sync_new_user' ], 10, 1 );
		add_action( 'profile_update', [ $this, 'sync_existing_user' ], 10, 2 );

		add_action( 'set_user_role', [ $this, 'maybe_clear_owners_cache' ], 10, 3 );
		add_action( 'add_user_role', [ $this, 'maybe_clear_owners_cache' ], 10, 2 );
		add_action( 'remove_user_role', [ $this, 'maybe_clear_owners_cache' ], 10, 2 );
		add_action( 'delete_user', [ $this, 'maybe_clear_owners_cache' ], 10, 1 );

		if ( is_option_enabled( 'gh_sync_user_meta' ) ) {
			add_action( 'added_user_meta', [ $this, 'user_meta_added' ], 10, 4 );
			add_action( 'updated_user_meta', [ $this, 'user_meta_updated' ], 10, 4 );
			add_action( 'deleted_user_meta', [ $this, 'user_meta_deleted' ], 10, 4 );
		}

		add_filter( 'wp_privacy_personal_data_erasers', [ $this, 'register_eraser' ] );
	}

	/**
	 * Registers an eraser for Groundhogg
	 *
	 * @param $erasers
	 */
	public function register_eraser( $erasers ) {
		$erasers['groundhogg'] = array(
			'eraser_friendly_name' => white_labeled_name(),
			'callback'             => [ $this, 'eraser_callback' ],
		);

		return $erasers;
	}

	/**
	 * Erase Groundhogg data when an erasure request is performed
	 *
	 * @param string $email_address
	 * @param int $page
	 *
	 * @return array
	 */
	public function eraser_callback( $email_address, $page = 1 ) {

		$contact = get_contactdata( $email_address );

		// No contact record, we're done here
		if ( ! is_a_contact( $contact ) ){
			return [
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => [],
				'done'           => true,
			];
		}

		$items_removed = $contact->delete();

		return [
			'items_removed'  => $items_removed,
			'items_retained' => ! $items_removed,
			'messages'       => [],
			'done'           => true,
		];
	}

	/**
	 * Deletes the owners cache option if the role of a user is changed from or to an owner
	 *
	 * @param       $user_id   int
	 * @param       $role      string
	 * @param array $old_roles string[]
	 *
	 * @return void
	 */
	public function maybe_clear_owners_cache( $user_id, $role = '', $old_roles = [] ) {

		if ( in_array( $role, get_owner_roles() ) ) {
			delete_option( 'gh_owners' );
		}

		if ( count( array_intersect( $old_roles, get_owner_roles() ) ) > 0 ) {
			delete_option( 'gh_owners' );
		}

		if ( empty( $role ) && empty( $old_roles ) && user_can( $user_id, 'view_contacts' ) ) {
			delete_option( 'gh_owners' );
		}
	}

	/**
	 * Sync a new user to the contacts
	 *
	 * @param $user_id
	 */
	public function sync_new_user( $user_id ) {

		if ( is_option_enabled( 'gh_disable_user_sync' ) ) {
			return;
		}

		convert_user_to_contact_when_user_registered( $user_id );
	}

	/**
	 * Sync existing users with the contacts
	 *
	 * @param $user_id
	 * @param $old_data
	 */
	public function sync_existing_user( $user_id, $old_data ) {

		if ( is_option_enabled( 'gh_disable_user_sync' ) ) {
			return;
		}

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
	 * @param $meta_id     int
	 * @param $object_id   int
	 * @param $meta_key    string
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
	 * @param $meta_id     int
	 * @param $object_id   int
	 * @param $meta_key    string
	 * @param $_meta_value mixed
	 */
	public function user_meta_updated( $meta_id, $object_id, $meta_key, $_meta_value ) {

		$contact = get_contactdata( $object_id, true );

		if ( ! is_a_contact( $contact ) || self::is_meta_ignored( $meta_key ) ) {
			return;
		}

		if ( self::is_primary_meta_key( $meta_key ) ) {
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
	 * @param $meta_id     int
	 * @param $object_id   int
	 * @param $meta_key    string
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
