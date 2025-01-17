<?php

namespace Groundhogg;

// Exit if accessed directly
use Groundhogg\Classes\Note;
use Groundhogg\Classes\Traits\File_Box;
use Groundhogg\DB\DB;
use Groundhogg\DB\Meta_DB;
use Groundhogg\DB\Tag_Relationships;
use Groundhogg\DB\Tags;
use Groundhogg\Utils\DateTimeHelper;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contact
 *
 * Lots going on here, to much to cover. Essentially, you have a contact, lost of helper methods, cool stuff.
 * This was originally modified from the EDD_Customer class by easy digital downloads, but quickly came into it's own.
 *
 * @since       File available since Release 0.1
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
 */
class Contact extends Base_Object_With_Meta {

	/**
	 * Tag IDs
	 *
	 * @var int[]
	 */
	protected $tags = [];

	/**
	 * An instance of the WP User
	 *
	 * @var WP_User
	 */
	protected $user;

	/**
	 * @var WP_User
	 */
	protected $owner;

	/**
	 * Contact constructor.
	 *
	 * @param bool|int|string|array $_id_or_email_or_args
	 * @param bool                  $by_user_id
	 *
	 * @return void
	 */
	public function __construct( $_id_or_email_or_args = false, $by_user_id = false ) {

		// ID given
		if ( is_numeric( $_id_or_email_or_args ) ) {
			$by_user_id = is_bool( $by_user_id ) ? $by_user_id : false;
			$field      = $by_user_id ? 'user_id' : 'ID';

			parent::__construct( $_id_or_email_or_args, $field );

			if ( $by_user_id && ! $this->exists() ) {
				$this->create_from_user( $by_user_id );
			}

			return;
		}

		// Email given
		if ( is_string( $_id_or_email_or_args ) && is_email( $_id_or_email_or_args ) ) {
			parent::__construct( $_id_or_email_or_args, 'email' );

			return;
		}

		// Pass email in array
		if ( is_array( $_id_or_email_or_args ) && isset_not_empty( $_id_or_email_or_args, 'email' ) ) {
			parent::__construct( $_id_or_email_or_args['email'], 'email' );

			if ( ! $this->exists() ) {
				$this->create( $_id_or_email_or_args );
			} else {
				$this->update( $_id_or_email_or_args );
			}

			return;
		}

		// Pass ID in array
		if ( is_array( $_id_or_email_or_args ) && isset_not_empty( $_id_or_email_or_args, 'ID' ) ) {
			parent::__construct( absint( $_id_or_email_or_args['ID'] ), 'ID' );

			if ( $this->exists() ) {
				$this->update( $_id_or_email_or_args );
			}

			return;
		}

		// Pass user_id in array
		if ( is_array( $_id_or_email_or_args ) && isset_not_empty( $_id_or_email_or_args, 'user_id' ) ) {

			$user_id = absint( $_id_or_email_or_args['user_id'] );
			parent::__construct( $user_id, 'user_id' );

			if ( ! $this->exists() ) {
				$this->create_from_user( $user_id );
			}

			$this->update( $_id_or_email_or_args );

			return;
		}

		// Support fetching contact by user
		if ( is_a( $_id_or_email_or_args, '\WP_User' ) ) {
			parent::__construct( $_id_or_email_or_args->ID, 'user_id' );

			if ( ! $this->exists() ) {
				$this->create_from_user( $_id_or_email_or_args );
			}

			return;
		}

		parent::__construct( $_id_or_email_or_args );
	}


	/**
	 * Create the contact from a user
	 *
	 * @param $user WP_User|int
	 *
	 * @return bool
	 */
	protected function create_from_user( $user ) {

		if ( ! is_int( $user ) ) {
			$user = get_userdata( $user );
		}

		if ( ! is_a( $user, WP_User::class ) ) {
			return false;
		}

		return $this->create( [
			'first_name' => $user->first_name,
			'last_name'  => $user->last_name,
			'email'      => $user->user_email,
		] );
	}

	/**
	 * Gets the avatar image.
	 *
	 * @return false|string
	 */
	public function get_profile_picture( $size = 300 ) {

		if ( $this->profile_picture ) {
			$profile_pic = $this->profile_picture;
		} else {
			$profile_pic           = get_avatar_url( $this->get_email(), [ 'size' => $size ] );
			$this->profile_picture = $profile_pic;
		}

		/**
		 * @param $profile_picture string link to the current profile picture
		 * @param $contact_id      int the contact id
		 * @param $contact         Contact the contact
		 */
		return apply_filters( 'groundhogg/contact/profile_picture', $profile_pic, $this->get_id(), $this );
	}

	/**
	 * Return the DB instance that is associated with items of this type.
	 *
	 * @return DB
	 */
	protected function get_db() {
		return get_db( 'contacts' );
	}

	/**
	 * Return a META DB instance associated with items of this type.
	 *
	 * @return Meta_DB
	 */
	protected function get_meta_db() {
		return get_db( 'contactmeta' );
	}

	/**
	 * Get the tags DB
	 *
	 * @return Tags
	 */
	protected function get_tags_db() {
		return get_db( 'tags' );
	}

	/**
	 * Get the tag rel DB
	 *
	 * @return Tag_Relationships
	 */
	protected function get_tag_rel_db() {
		return get_db( 'tag_relationships' );
	}

	/**
	 * A string to represent the object type
	 *
	 * @return string
	 */
	protected function get_object_type() {
		return 'contact';
	}

	/**
	 * Do any post setup actions.
	 *
	 * @return void
	 */
	protected function post_setup() {
		$this->tags  = wp_parse_id_list( $this->get_tag_rel_db()->get_relationships( $this->ID ) );
		$this->user  = get_userdata( $this->get_user_id() );
		$this->owner = get_userdata( $this->get_owner_id() );

		$this->ID           = absint( $this->ID );
		$this->user_id      = absint( $this->user_id );
		$this->owner_id     = absint( $this->owner_id );
		$this->optin_status = absint( $this->optin_status );
	}

	/**
	 * The contact ID
	 *
	 * @return int
	 */
	public function get_id() {
		return absint( $this->ID );
	}

	/**
	 * Get the tags
	 *
	 * @return array
	 */
	public function get_tag_ids() {
		return wp_parse_id_list( $this->tags );
	}

	/**
	 * Get the tag objects.
	 *
	 * @return Tag[]
	 */
	public function get_tags( $as_object = false ) {

		return $as_object ? array_map( function ( $tag_id ) {
			return new Tag( $tag_id );
		}, $this->get_tag_ids() ) : $this->get_tag_ids();
	}

	/**
	 * @return array
	 */
	public function get_tags_for_select2() {
		$return = [];

		foreach ( $this->get_tags() as $tag_id ) {
			$tag = new Tag( $tag_id );

			$return[] = [
				'id'   => $tag->get_id(),
				'text' => $tag->get_name()
			];
		}

		return $return;
	}

	/**
	 * Get all the notes...
	 *
	 * @return Note[]
	 */
	public function get_all_notes() {
		return $this->get_notes();
	}

	/**
	 * Get the contact's email address
	 *
	 * @return string
	 */
	public function get_email() {
		return strtolower( $this->email );
	}

	/**
	 * Gets the contact's optin status
	 *
	 * @return int
	 */
	public function get_optin_status() {
		return absint( $this->optin_status );
	}

	/**
	 * Compare the current opt-in status to whatever was given
	 *
	 * @param $status array|int
	 *
	 * @return bool
	 */
	public function optin_status_is( $status ) {
		return is_array( $status ) ? in_array( $this->get_optin_status(), $status ) : $this->get_optin_status() === $status;
	}

	/**
	 * Get the contact's first name
	 *
	 * @return string
	 */
	public function get_first_name() {
		return ucwords( $this->first_name );
	}

	/**
	 * Gtet the contact's last name
	 *
	 * @return string
	 */
	public function get_last_name() {
		return ucwords( $this->last_name );
	}

	/**
	 * Get the contact's full name
	 *
	 * @return string
	 */
	public function get_full_name() {
		return trim( sprintf( '%s %s', $this->get_first_name(), $this->get_last_name() ) );
	}

	/**
	 * Get the user ID
	 *
	 * @return int
	 */
	public function get_user_id() {
		return absint( $this->user_id );
	}

	/**
	 * Get the user ID
	 *
	 * @return int
	 */
	public function get_owner_id() {
		return absint( $this->owner_id );
	}

	/**
	 * Whether the given is the owner of this contact
	 *
	 * @param $owner WP_User|int
	 *
	 * @return bool
	 */
	public function owner_is( $owner ) {
		return $owner instanceof WP_User ? $this->get_owner_id() === $owner->ID : $this->get_owner_id() === $owner;
	}

	/**
	 * Get the user data
	 *
	 * @return WP_User|false
	 */
	public function get_userdata() {
		return $this->user;
	}

	/**
	 * @return WP_User
	 */
	public function get_ownerdata() {
		return $this->owner;
	}

	/**
	 * @return string
	 */
	public function get_phone_number() {
		return $this->get_meta( 'primary_phone' );
	}

	/**
	 * @return string
	 */
	public function get_mobile_number() {
		return $this->get_meta( 'mobile_phone' );
	}

	/**
	 * @return string
	 */
	public function get_phone_extension() {
		return $this->get_meta( 'primary_phone_extension' );
	}

	/**
	 * Get the contacts's IP
	 *
	 * @return mixed
	 */
	public function get_ip_address() {
		return $this->get_meta( 'ip_address' );
	}

	/**
	 * Get the contact's time_zone
	 *
	 * If one is not saved it will return the timezone of the site and not false
	 *
	 * @return string|\DateTimeZone
	 */
	public function get_time_zone( $as_string = true ) {
		$tz = $this->get_meta( 'time_zone' ) ?: wp_timezone_string();

		if ( $as_string ) {
			return $tz;
		}

		try {
			$tz = new \DateTimeZone( $tz );
		} catch ( \Exception $exception ) {
			$tz = wp_timezone();
		}

		return $tz;
	}

	/**
	 * Set the contact's locale
	 *
	 * @param string|bool $locale
	 */
	public function set_locale( $locale = false ) {
		$this->update_meta( 'locale', $locale ?: get_locale() );
	}

	/**
	 * Get the contact's locale
	 *
	 * @return string en_US if undefined
	 */
	public function get_locale() {
		return $this->get_meta( 'locale' ) ?: get_locale();
	}

	/**
	 * @throws \Exception
	 *
	 * @param bool $as_date
	 *
	 * @return bool|mixed
	 */
	public function get_date_created( $as_date = false ) {

		if ( $as_date ) {
			return new DateTimeHelper( $this->date_created );
		}

		return $this->date_created;
	}

	/**
	 * Get the address
	 *
	 * @param string[] $exclude
	 *
	 * @return array
	 */
	public function get_address( $exclude = [] ) {

		$address_keys = array_diff( [
			'street_address_1',
			'street_address_2',
			'city',
			'region',
			'country',
			'postal_zip',
		], $exclude );

		$address = [];

		foreach ( $address_keys as $key ) {
			$val = $this->get_meta( $key );
			if ( ! empty( $val ) ) {
				$address[ $key ] = $val;
			}
		}

		if ( isset_not_empty( $address, 'country' ) ) {
			// Map to the proper name
			$address['country'] = utils()->location->get_countries_list( $address['country'] );
		}

		return $address;
	}

	/**
	 * Return whether the contact actually exists
	 */
	public function exists() {
		return $this->ID > 0 || (bool) is_email( $this->email );
	}

	/**
	 * Return whether the contact is marketable or not.
	 *
	 * @return bool
	 */
	public function is_marketable() {
		return apply_filters( 'groundhogg/contact/is_marketable', Plugin::instance()->preferences->is_marketable( $this ), $this );
	}

	/**
	 * Whether the email address is deliverable
	 *
	 * @return bool
	 */
	public function is_deliverable() {
		return ! in_array( $this->get_optin_status(), [
			Preferences::HARD_BOUNCE,
			Preferences::COMPLAINED,
			Preferences::SPAM
		] );
	}

	/**
	 * Return whether the contact is confirmed or not.
	 *
	 * @return bool
	 */
	public function is_confirmed() {
		return $this->get_optin_status() === Preferences::CONFIRMED;
	}

	/**
	 * get the contact's notes
	 *
	 * @return Note[]
	 */
	public function get_notes() {

		$notes = get_db( 'notes' )->query( [
			'object_id'   => $this->get_id(),
			'object_type' => $this->get_object_type()
		] );

		array_map_to_class( $notes, Note::class );

		return $notes;
	}

	/**
	 * Handles consent being set in the data array
	 *
	 * @param $data
	 *
	 * @return void
	 */
	protected function handle_consents_in_data( $data ) {

		// Handle consent
		$consents = [
			'gdpr_consent'      => [ $this, 'set_gdpr_consent' ],
			'marketing_consent' => [ $this, 'set_marketing_consent' ],
			'terms_agreement'   => [ $this, 'set_terms_agreement' ]
		];

		foreach ( $consents as $consent => $callback ) {
			if ( ! isset_not_empty( $data, $consent ) ) {
				continue;
			}

			if ( $data[ $consent ] ) {
				call_user_func( $callback );
			} else {
				$this->delete_compliance_and_date_meta( $consent );
			}
		}
	}

	/**
	 * Set the locale when the contact is first created
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	public function create( $data = [] ) {

		$created = parent::create( $data );

		$this->handle_consents_in_data( $data );

		$this->set_locale();

		return $created;
	}


	/**
	 * Contact update wrapper
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	public function update( $data = [] ) {

		// Not an array, send them home
		if ( ! is_array( $data ) ) {
			return false;
		}

		// If we do this first, then it makes dealing with other stuff much easier because sanitize columns will remove unknown table columns
		$this->handle_consents_in_data( $data );

		// Only update different data from the current.
		$data = $this->sanitize_columns( $data );
		$data = array_diff_assoc( $data, $this->data );

		// updating with existing data
		if ( empty( $data ) ) {
			// Only updating consents
			return true;
		}

		// If opt-in status is set at this point, it's a new status
		if ( isset_not_empty( $data, 'optin_status' ) ) {
			$old_preference = $this->get_optin_status();
			$new_preference = $data['optin_status'];

			if ( ! isset( $data['date_optin_status_changed'] ) ) {
				$data['date_optin_status_changed'] = current_time( 'mysql' );
			}
		}

		// Email is attempting to be changed
		if ( isset_not_empty( $data, 'email' ) ) {
			$new_email = $data['email'];

			// Invalid email, or in use by another contact record
			if ( ! is_email( $new_email ) || is_email_address_in_use( $new_email ) ) {
				unset( $data['email'] );
			}
		}

		$folders       = $this->get_uploads_folder();
		$orig_owner    = $this->owner;
		$orig_owner_id = $this->owner_id;

		$updated = parent::update( $data );

		// failed to update, no point in going further
		if ( ! $updated ) {
			return $updated;
		}

		$maybe_changed_folders = $this->get_uploads_folder();

		// Uploads directory has been changed, rename the folder to preserve file uploads
		if ( $folders['path'] !== $maybe_changed_folders['path'] ) {
			@rename( $folders['path'], $maybe_changed_folders['path'] );
		}

		// The contact owner was changed
		if ( $orig_owner_id !== $this->owner_id ) {

			/**
			 * When the owner is changed
			 *
			 * @param $owner      \WP_User
			 * @param $contact    Contact
			 * @param $prev_owner \WP_User
			 */
			do_action( 'groundhogg/contact/owner_changed', $this->owner, $this, $orig_owner );
		}

		if ( isset( $old_preference ) && isset( $new_preference ) ) {

			/**
			 * When the preference is updated
			 *
			 * @param $id int
			 * @param $new_preference
			 * @param $old_preference
			 * @param $contact
			 */
			do_action( 'groundhogg/contact/preferences/updated', $this->ID, $new_preference, $old_preference, $this );

			if ( $new_preference === Preferences::UNSUBSCRIBED ) {

				/**
				 * When the contact is unsubscribed
				 *
				 * @param $id int
				 * @param $new_preference
				 * @param $old_preference
				 * @param $contact
				 */
				do_action( 'groundhogg/contact/preferences/unsubscribed', $this->ID, $new_preference, $old_preference, $this );
			}
		}

		return $updated;
	}

	/**
	 * Ensure columns are sanitized
	 *
	 * @param $data
	 *
	 * @return array|mixed
	 */
	protected function sanitize_columns( $data = [] ) {

		$data = array_intersect_key( $data, $this->get_db()->get_columns() );

		foreach ( $data as $key => &$value ) {
			switch ( $key ) {
				case 'email':
					$value = strtolower( sanitize_email( $value ) );
					break;
				case 'first_name':
				case 'last_name':
				case 'date_created':
				case 'date_optin_status_changed':
					$value = sanitize_text_field( $value );
					break;
				case 'owner_id':
				case 'user_id':
					$value = absint( $value );
					break;
				case 'optin_status':
					$value = Preferences::sanitize( $value );
					break;
			}
		}

		return $data;
	}

	/**
	 * Wrapper function for add_tag to make it easier
	 *
	 * @param $tag_id_or_array
	 *
	 * @return bool
	 */
	public function apply_tag( $tag_id_or_array ) {
		return $this->add_tag( $tag_id_or_array );
	}

	/**
	 * Add a list of tags or a single tag top the contact
	 *
	 * @param $tag_id_or_array array|int|Tag
	 *
	 * @return bool
	 */
	public function add_tag( $tag_id_or_array ) {

		$tags = parse_tag_list( $tag_id_or_array );

		$tags = apply_filters( 'groundhogg/contacts/add_tag/before', $tags );

		// Apply tags that the contact doesn't already have
		$apply = array_diff( $tags, $this->tags );

		if ( empty( $apply ) ) {
			return false;
		}

		foreach ( $apply as $tag_id ) {

			$this->tags[] = $tag_id;

			$this->get_tag_rel_db()->batch_insert( [
				'tag_id'     => $tag_id,
				'contact_id' => $this->get_id()
			] );
		}

		$inserted = $this->get_tag_rel_db()->commit_batch_insert();

		// Did not insert new tags
		if ( $inserted === 0 ) {
			return false;
		}

		foreach ( $apply as $tag_id ) {
			/**
			 * When a tag relationship is created
			 *
			 * @param $contact Contact
			 * @param $tag_id  int
			 */
			do_action( 'groundhogg/contact/tag_applied', $this, $tag_id );
		}

		/**
		 * Similar to groundhogg/contact/tag_applied but passes all the tags as an array if multiple tags were passed
		 *
		 * @param $contact Contact
		 * @param $tag_ids int[]
		 */
		do_action( 'groundhogg/contact/tags_applied', $this, $apply );


		return true;

	}

	/**
	 * Remove a single tag or several tags from the contact
	 *
	 * @param $tag_id_or_array array|int|Tag
	 *
	 * @return bool
	 */
	public function remove_tag( $tag_id_or_array ) {

		$tags = parse_tag_list( $tag_id_or_array );

		$tags = apply_filters( 'groundhogg/contacts/remove_tag/before', $tags );

		$remove = array_intersect( $tags, $this->tags );

		if ( empty( $remove ) ) {
			return false;
		}

		$deleted = $this->get_tag_rel_db()->query( [
			'operation'  => 'DELETE',
			'tag_id'     => $remove,
			'contact_id' => $this->get_id()
		] );

		if ( $deleted === 0 ) {
			return false;
		}

		$this->tags = array_diff( $this->tags, $remove );

		foreach ( $remove as $tag_id ) {
			/**
			 * When a tag relationship is removed
			 *
			 * @param $contact Contact
			 * @param $tag_id  int
			 */
			do_action( 'groundhogg/contact/tag_removed', $this, $tag_id );
		}

		/**
		 * Similar to groundhogg/contact/tag_removed but passes all the tags as an array if multiple tags were passed
		 *
		 * @param $contact Contact
		 * @param $tag_ids int[]
		 */
		do_action( 'groundhogg/contact/tags_removed', $this, $remove );

		return true;
	}

	/**
	 * Alias for plural of tags...
	 *
	 * @param $maybe_tags
	 *
	 * @return bool
	 */
	public function has_tags( $maybe_tags ) {
		return $this->has_tag( $maybe_tags );
	}

	/**
	 * return whether the contact has a specific tag
	 *
	 * @param mixed $tag_id_or_name the ID or name or the tag or an array of tags
	 *
	 * @return bool true if the contact has the tag, false if they don't or no tag valid tag is passed in the first place
	 */
	public function has_tag( $tag_id_or_name ) {

		$tags = parse_tag_list( $tag_id_or_name );

		// If no tag is passed or the contact has no tags return false
		if ( empty( $tags ) || empty( $this->tags ) ) {
			return false;
		}

		// If the count of the passed tags is the same as the intersection of the tags then the contact has all the tags
		return count( array_intersect( $this->tags, $tags ) ) === count( $tags );
	}

	/**
	 * Sets the date the opt-in stats was last changed to the current time
	 *
	 * @return bool
	 */
	public function reset_date_optin_status_changed() {
		return $this->update( [
			'date_optin_status_changed' => current_time( 'mysql' )
		] );
	}

	/**
	 * Change the marketing preferences of a contact.
	 *
	 * @param $preference
	 */
	public function change_marketing_preference( $preference ) {

		// No change
		if ( $this->get_optin_status() === $preference ) {
			return;
		}

		$this->update( [
			'optin_status' => $preference
		] );
	}

	/**
	 * Change the owner Id
	 *
	 * @param $owner_id
	 */
	public function change_owner( $owner_id ) {

		// No change
		if ( $this->owner_is( $owner_id ) ) {
			return true;
		}

		if ( ! user_can( $owner_id, 'edit_contacts' ) ) {
			return false;
		}

		return $this->update( [
			'owner_id' => $owner_id
		] );
	}

	/**
	 * Unsubscribe a contact
	 */
	public function unsubscribe() {
		$this->change_marketing_preference( Preferences::UNSUBSCRIBED );
	}

	/**
	 * This will find a WP account with the same email and update the user_id accordingly
	 *
	 * @return bool true if we found a relevant user account, false otherwise.
	 */
	public function auto_link_account() {
		if ( $this->get_user_id() ) {
			return true;
		}

		$user = get_user_by( 'email', $this->get_email() );

		if ( $user ) {
			$this->update( [ 'user_id' => $user->ID ] );

			return true;
		}

		return false;
	}

	/**
	 * Extrapolate the contact's location from an IP.
	 *
	 * @param bool $override
	 *
	 * @return array|bool
	 */
	public function extrapolate_location( $override = false ) {
		$ip_address = $this->get_ip_address();

		/* Do not run for localhost IPv6 blank IP */
		if ( ! $ip_address || $ip_address === "::1" ) {
			return false;
		}

		$info = utils()->location->ip_info( $ip_address );

		if ( empty( $info ) ) {
			return false;
		}

		$location_meta = [
			'city'      => 'city',
			'region'    => 'region',
			'country'   => 'country_code',
			'time_zone' => 'time_zone',
		];

		foreach ( $location_meta as $meta_key => $ip_info_key ) {
			$meta_value = $this->get_meta( $meta_key );

			if ( key_exists( $ip_info_key, $info ) && ( empty( $meta_value ) || $override ) ) {
				$this->update_meta( $meta_key, $info[ $ip_info_key ] );
			}
		}

		return $info;
	}

	/**
	 * Returns the local time of the contact
	 * If time specified, converts the timestamp dependant on the timezone of the user.
	 *
	 * @param int $time UNIX timestamp
	 *
	 * @return int UNIX timestamp
	 */
	function get_local_time( $time = 0 ) {

		if ( ! $time ) {
			$time = time();
		}

		$time_zone = $this->get_time_zone();

		if ( $time_zone ) {
			try {
				$local_time = Plugin::$instance->utils->date_time->convert_to_foreign_time( $time, $time_zone );
			} catch ( \Exception $e ) {
				$local_time = $time;
			}
		} else {
			// If no timezone specified assume local time of site
			$local_time = Plugin::$instance->utils->date_time->convert_to_local_time( $time );
		}

		return $local_time;

	}

	/**
	 * Compensate for hour difference between local site time and the timezone of the contact.
	 *
	 * @param int $time
	 *
	 * @return int
	 */
	function get_local_time_in_utc_0( $time = 0 ) {

		if ( ! $time ) {
			$time = time();
		}

		return $time + $this->get_utc_0_offset();
	}

	/**
	 * Get the contacts timezone offset.
	 *
	 * @return int
	 */
	function get_time_zone_offset() {

		// Return site timezone offset if no timezone in contact record?
		if ( ! $this->get_time_zone() ) {
			return Plugin::$instance->utils->date_time->get_wp_offset();
		}

		try {
			return Plugin::$instance->utils->date_time->get_timezone_offset( $this->get_time_zone() );
		} catch ( \Exception $e ) {
			return 0;
		}
	}

	/**
	 * Get a proper UTC offset
	 *
	 * @return int
	 */
	function get_utc_0_offset() {
		return Plugin::$instance->utils->date_time->get_wp_offset() - $this->get_time_zone_offset();
	}

	use File_Box;

	/**
	 * Get the basename of the path
	 *
	 * @return string
	 */
	public function get_upload_folder_basename() {
		return md5( encrypt( $this->get_email() ) );
	}

	public function get_uploads_folder_subdir() {
		return 'uploads';
	}

	/**
	 * Get the age of the contact
	 *
	 * @return int
	 */
	public function get_age() {

		$date = $this->get_meta( 'birthday' );

		if ( empty( $date ) ) {
			return false;
		}

		$age_in_seconds = time() - strtotime( $date );
		$age_in_years   = floor( $age_in_seconds / YEAR_IN_SECONDS );

		return absint( $age_in_years );
	}


	/**
	 * Get the contact data as an array.
	 *
	 * @return array
	 */
	public function get_as_array() {
		$contact              = $this->get_data();
		$contact['ID']        = $this->get_id();
		$contact['gravatar']  = $this->get_profile_picture();
		$contact['full_name'] = $this->get_full_name();
		$contact['age']       = $this->get_age();

		return apply_filters(
			"groundhogg/{$this->get_object_type()}/get_as_array",
			[
				'ID'             => $this->get_id(),
				'data'           => $contact,
				'meta'           => $this->get_meta(),
				'tags'           => array_values( $this->get_tags( true ) ),
//				'files' => $this->get_files(),
				'user'           => $this->user,
//				'notes' => $this->get_notes(),
				'admin'          => $this->admin_link(),
				'is_marketable'  => $this->is_marketable(),
				'is_deliverable' => $this->is_deliverable(),
				'i18n'           => [
					'created' => human_time_diff( time(), $this->get_date_created( true )->getTimestamp() )
				]
			]
		);
	}

	/**
	 * Output a contact. Just give the full name & email
	 *
	 * @return string
	 */
	public function __toString() {
		return sprintf( "%s (%s)", $this->get_full_name(), $this->get_email() );
	}

	public function get_company() {
		return $this->get_meta( 'company_name' );
	}

	public function get_job_title() {
		return $this->get_meta( 'job_title' );
	}

	/**
	 * Merge $other into $this
	 * - Fills out missing info in $this from $other
	 * - Updates all events for $other with $this' ID
	 * - Updates all activity for $other with $this' ID
	 * - Updates all notes for $other with $this' ID
	 * - Move all files to $this' file folder
	 * - Deletes $other
	 *
	 * @param int|string|Contact $other
	 *
	 * @return bool
	 */
	public function merge( $other ) {

		$other = get_contactdata( $other );

		// Don't merge with itself
		// Don't merge with objects of a different type
		if ( ! is_a_contact( $other ) || $other->get_id() === $this->get_id() || $other->get_object_type() !== $this->get_object_type() ) {
			return false;
		}

		/**
		 * Before an object is merged
		 *
		 * @param Base_Object $original
		 * @param Base_Object $other
		 */
		do_action( "groundhogg/contact/pre_merge", $this, $other );

		// handle additional email addresses
		$additional_emails   = $this->get_meta( 'alternate_emails' ) ?: [];
		$additional_emails[] = $other->get_email();
		$additional_emails   = array_merge( $additional_emails, $other->get_meta( 'alternate_emails' ) ?: [] );
		$this->update_meta( 'alternate_emails', array_unique( $additional_emails ) );

		// Handle additional phone numbers
		$additional_phones   = $this->get_meta( 'alternate_phones' ) ?: [];
		$additional_phones[] = [ 'mobile', $this->get_mobile_number() ];
		$additional_phones[] = [ 'home', $this->get_phone_number() ];
		$additional_phones[] = [ 'business', $this->get_meta( 'company_phone' ) ];
		$additional_phones   = array_merge( $additional_phones, $other->get_meta( 'alternate_phones' ) ?: [] );
		$additional_phones   = array_filter( $additional_phones, function ( $r ) {
			return ! empty( $r[1] );
		} );
		$additional_phones   = array_unique_cb( $additional_phones, function ( $r ) {
			return $r[1];
		} );

		$this->update_meta( 'alternate_phones', $additional_phones );

		// Update the data
		$this->update( array_merge( array_filter( $other->data ), array_filter( $this->data ) ) );

		// Update the meta
		$this->update_meta( array_merge( array_filter( $other->meta ), array_filter( $this->meta ) ) );

		$uploads_dir = $this->get_uploads_folder();

		// Might have to create the directory
		if ( ! is_dir( $uploads_dir['path'] ) ) {
			wp_mkdir_p( $uploads_dir['path'] );
		}

		// Move any files to this contact's uploads folder.
		foreach ( $other->get_files() as $file ) {

			$file_path = $file['path'];
			$file_name = $file['name'];

			rename( $file_path, $uploads_dir['path'] . '/' . $file_name );
		}

		/**
		 * When an object is merged
		 *
		 * Handles
		 * - Activity
		 * - Other Activity
		 * - Page Visits
		 * - Events
		 * - Notes
		 * - Submissions
		 * - Tag Relationships
		 * - Object Relationships
		 * - Permissions Keys
		 *
		 * @param Base_Object $original
		 * @param Base_Object $other
		 */
		do_action( "groundhogg/contact/merged", $this, $other );

		$other->delete();

		return true;
	}

	protected function set_compliance_and_date_meta( $id ) {
		parent::update_meta( $id, 'yes' );
		parent::update_meta( "{$id}_date", date_i18n( get_date_time_format() ) );
	}

	protected function has_compliance_and_date_meta( $id ) {
		return $this->get_meta( $id ) === 'yes' && $this->get_meta( "{$id}_date" ) !== false;
	}

	protected function delete_compliance_and_date_meta( $id ) {
		$this->delete_meta( $id );
		$this->delete_meta( "{$id}_date" );
	}

	/**
	 * Set various forms of GDPR consent
	 *
	 * @param string $type
	 */
	public function set_gdpr_consent( $type = 'gdpr' ) {
		// Either GDPR or MARKETING always
		$type = $type === 'gdpr' ? 'gdpr' : 'marketing';

		$this->set_compliance_and_date_meta( "{$type}_consent" );

		do_action( "groundhogg/contact/added_{$type}_consent", $this );
	}

	/**
	 * Set the marketing consent
	 */
	public function set_marketing_consent() {
		$this->set_gdpr_consent( 'marketing' );
	}

	/**
	 * Revoke various forms of GDPR consent
	 *
	 * @param string $type
	 */
	public function revoke_gdpr_consent( $type = 'gdpr' ) {
		// Either GDPR or MARKETING always
		$type = $type === 'gdpr' ? 'gdpr' : 'marketing';

		$this->delete_compliance_and_date_meta( "{$type}_consent" );

		do_action( "groundhogg/contact/revoked_{$type}_consent", $this );
	}

	/**
	 * @param string $type
	 *
	 * @return bool
	 */
	public function has_gdpr_consent( $type = 'gdpr' ) {
		$type = $type === 'gdpr' ? 'gdpr' : 'marketing';

		return $this->has_compliance_and_date_meta( "{$type}_consent" );
	}

	/**
	 * ahve the contact agree to the terms and conditions
	 */
	public function set_terms_agreement() {
		$this->set_compliance_and_date_meta( 'terms_agreement' );

		do_action( "groundhogg/contact/agreed_to_terms", $this );
	}

	/**
	 * Handle meta keys with special meaning
	 *
	 * @param string|array $key
	 * @param false        $value
	 *
	 * @return bool
	 */
	public function handle_special_meta_keys( $key, $value = false ) {

		if ( ! is_string( $key ) ) {
			return false;
		}

		switch ( $key ) {
			case 'data_consent':
			case 'gdpr_consent':

				if ( ! $value ) {
					$this->revoke_gdpr_consent();
				} else {
					$this->set_gdpr_consent();
				}

				return true;
			case 'marketing_consent':
				if ( ! $value ) {
					$this->revoke_gdpr_consent( 'marketing' );
				} else {
					$this->set_marketing_consent();
				}

				return true;
			case 'terms_agreement':

				if ( $value ) {
					$this->set_terms_agreement();
				}

				return true;
		}

		return false;
	}

	public function add_meta( $key, $value = false ) {

		if ( $this->handle_special_meta_keys( $key, $value ) ) {
			return true;
		}

		return parent::add_meta( $key, $value );
	}

	public function update_meta( $key, $value = false ) {

		if ( $this->handle_special_meta_keys( $key, $value ) ) {
			return true;
		}

		return parent::update_meta( $key, $value );
	}

	public function add_note( $note, $context = 'system', $user_id = false, $overrides = [] ) {
		$note = do_replacements( $note, $this );

		return parent::add_note( $note, $context, $user_id, $overrides );
	}
}
