<?php

namespace Groundhogg;

use Groundhogg\Bulk_Jobs\Bulk_Job;

class Tag_Mapping extends Bulk_Job {

	const MARKETABLE = 'marketable';
	const NON_MARKETABLE = 'unmarketable';

	/**
	 * the tag map.
	 *
	 * @var array
	 */
	private $tag_map = [];

	public static function enabled() {
		return is_option_enabled( 'gh_enable_tag_mapping' );
	}

	/**
     * If the tag mapping option is enabled, maybe install the default tags
     *
	 * @param $old_value
	 * @param $new_value
	 *
	 * @return void
	 */
    public function maybe_install_default_tag_mapping( $old_value, $new_value ){
        if ( $old_value !== $new_value && $new_value){
            $this->install_default_tags();
        }
    }

	/**
	 * Tag_Mapping constructor.
	 */
	public function __construct() {

		if ( ! self::enabled() ) {
			return;
		}

        add_action( 'update_option_gh_enable_tag_mapping', [ $this, 'maybe_install_default_tag_mapping' ], 10, 2 );

		// Listen for an explicit status change.
		add_action( 'groundhogg/contact/preferences/updated', [ $this, 'optin_status_changed' ], 10, 4 );
		add_action( 'groundhogg/db/post_insert/contact', [ $this, 'optin_status_set' ], 10, 1 );
//		add_action( 'groundhogg/contact/tag_applied', [ $this, 'listen_for_tag_change' ], 10, 2 );

		// Contact's marketability can expire with time, but it's too costly to setup a cronjob
		// So instead we'll listen for an event failed. #goodenough
		add_action( 'groundhogg/event/failed', [ $this, 'listen_for_non_marketable' ] );

		// Quick hook to update the marketing preference automatically when a contact is created for the first time.
		add_action( 'groundhogg/contact/post_create', [ $this, 'change_marketing_preference' ], 10, 3 );
		add_action( 'groundhogg/contact/post_create', [ $this, 'auto_map_roles' ], 10, 3 );

		// Map User Roles...
		add_action( 'add_user_role', [ $this, 'apply_tags_to_contact_from_new_roles' ], 10, 2 );
		add_action( 'set_user_role', [ $this, 'apply_tags_to_contact_from_changed_roles' ], 10, 3 );
		add_action( 'remove_user_role', [ $this, 'remove_tags_from_contact_from_remove_roles' ], 10, 2 );

		// GDPR consent management
		add_action( 'groundhogg/contact/added_gdpr_consent', [ $this, 'consent_changed' ] );
		add_action( 'groundhogg/contact/added_marketing_consent', [ $this, 'consent_changed' ] );
		add_action( 'groundhogg/contact/revoked_gdpr_consent', [ $this, 'consent_changed' ] );
		add_action( 'groundhogg/contact/revoked_marketing_consent', [ $this, 'consent_changed' ] );

		add_action( 'admin_init', [ $this, 'reset_tags' ] );

		add_filter( 'groundhogg/contacts/add_tag/before', [ $this, 'filter_out_optin_status_tags' ] );
		add_filter( 'groundhogg/contacts/remove_tag/before', [ $this, 'filter_out_optin_status_tags' ] );

		parent::__construct();
	}

	/**
	 * Change tags if the contact is marketing/unmarketable
	 *
	 * @param Contact $contact
	 */
	public function consent_changed( Contact $contact ) {

		$this->set_mapping_tags( true );

		if ( $contact->is_marketable() ) {
			$contact->apply_tag( $this->get_status_tag( self::MARKETABLE ) );
			$contact->remove_tag( $this->get_status_tag( self::NON_MARKETABLE ) );
		} else {
			$contact->remove_tag( $this->get_status_tag( self::MARKETABLE ) );
			$contact->apply_tag( $this->get_status_tag( self::NON_MARKETABLE ) );
		}

		$this->set_mapping_tags( false );
	}

	/**
	 * Filter out optin status tags from being applied or removed.
	 *
	 * @param $tags
	 *
	 * @return array
	 */
	public function filter_out_optin_status_tags( $tags ) {

		if ( $this->mapping_tags ) {
			return $tags;
		}

		return array_diff( $tags, array_values( $this->get_tag_map() ) );
	}

	public function reset_tags() {
		if ( current_user_can( 'manage_options' ) && wp_verify_nonce( get_request_var( 'reset_tags' ), 'reset_tags' ) ) {
			$this->install_default_tags( true );
			Plugin::$instance->notices->add( 'tags_reset', __( 'Tags have been reset!', 'groundhogg' ) );
		}
	}

	public function reset_tags_ui() {

        if ( ! self::enabled() ){
            return;
        }

		?>
        <a href="<?php echo wp_nonce_url( $_SERVER['REQUEST_URI'], 'reset_tags', 'reset_tags' ); ?>"
           class="button-secondary"><?php _ex( 'Reset Tags', 'action', 'groundhogg' ) ?></a>
		<?php
	}

	/**
	 * Whenever a contact is created if user roles are available automap them
	 *
	 * @param $id      int
	 * @param $data    array
	 * @param $contact Contact
	 */
	public function auto_map_roles( $id, $data, $contact ) {
		if ( $contact->get_userdata() ) {
			$contact->add_tag( $this->get_roles_pretty_names( $contact->get_userdata()->roles ) );
		}
	}

	/**
	 * This auto runs the contact function "change_marketing_preference whenever a contact is created for the first time."
	 * This will then perform our tag associative mapping functions...
	 *
	 * @param $id      int
	 * @param $data    array
	 * @param $contact Contact
	 */
	public function change_marketing_preference( $id, $data, $contact ) {
		$this->optin_status_changed( $id, $contact->get_optin_status(), $contact->get_optin_status(), $contact );
	}

	/**
	 * When a role is set also set the tag
	 *
	 * @param $user_id   int
	 * @param $role      string
	 * @param $old_roles string[]
	 */
	public function apply_tags_to_contact_from_changed_roles( $user_id, $role, $old_roles ) {

		// Exit if installing because the tables would not have yet been installed...
		if ( wp_installing() ) {
			return;
		}

		$contact = get_contactdata( $user_id, true );

		if ( ! $contact || ! $contact->exists() ) {
			return;
		}

		// Convert list of roles to a list of tags and remove them...
		$roles = $this->get_roles_pretty_names( $old_roles );
		$contact->remove_tag( $roles );

		// Add the new role as a tag
		$role = $this->get_role_pretty_name( $role );
		$contact->add_tag( $role );
	}

	/**
	 * When a role is remove also remove the tag
	 *
	 * @param $user_id int
	 * @param $role    string
	 */
	public function remove_tags_from_contact_from_remove_roles( $user_id, $role ) {
		$contact = get_contactdata( $user_id, true );

		if ( ! $contact || ! $contact->exists() ) {
			return;
		}

		$role = $this->get_role_pretty_name( $role );
		$contact->remove_tag( $role );
	}

	/**
	 * When a role is added also add the tag
	 *
	 * @param $user_id int
	 * @param $role    string
	 */
	public function apply_tags_to_contact_from_new_roles( $user_id, $role ) {

		$contact = get_contactdata( $user_id, true );

		if ( ! $contact || ! $contact->exists() ) {
			return;
		}

		$role = $this->get_role_pretty_name( $role );
		$contact->add_tag( $role );
	}

	/**
	 * Convert an array of roles to n array of display roles
	 *
	 * @param $roles array an array of user roles...
	 *
	 * @return array an array of pretty role names.
	 */
	public function get_roles_pretty_names( $roles ) {
		$pretty_roles = array();

		foreach ( $roles as $role ) {
			$pretty_roles[] = $this->get_role_pretty_name( $role );
		}

		return $pretty_roles;
	}

	/**
	 * Convert a role to a tag name
	 *
	 * @param $role string the user role
	 *
	 * @return int the ID of the tag
	 */
	public function convert_role_to_tag( $role ) {
		$tags = Plugin::$instance->dbs->get_db( 'tags' )->validate( $this->get_role_pretty_name( $role ) );

		return array_shift( $tags );
	}

	/**
	 * Get the pretty name of a role
	 *
	 * @param $role string
	 *
	 * @return string
	 */
	public function get_role_pretty_name( $role ) {
		return translate_user_role( wp_roles()->roles[ $role ]['name'] );
	}

	/**
	 * Get the list of default tags and option names...
	 *
	 * @return array
	 */
	private function get_default_tags() {
		$tags = [
			'gh_confirmed_tag'      => [
				'tag_name'        => 'Confirmed',
				'tag_description' => 'This tags is applied to anyone whose optin status is confirmed.',
			],
			'gh_unconfirmed_tag'    => [
				'tag_name'        => 'Unconfirmed',
				'tag_description' => 'This tag is applied to anyone whose optin status is unconfirmed.',
			],
			'gh_unsubscribed_tag'   => [
				'tag_name'        => 'Unsubscribed',
				'tag_description' => 'This tag is applied to anyone whose optin status is unsubscribed.',
			],
			'gh_spammed_tag'        => [
				'tag_name'        => 'Spam',
				'tag_description' => 'This tag is applied to anyone whose optin status is spam.',
			],
			'gh_bounced_tag'        => [
				'tag_name'        => 'Bounced',
				'tag_description' => 'This tag is applied to anyone whose optin status is bounced.',
			],
			'gh_complained_tag'     => [
				'tag_name'        => 'Complained',
				'tag_description' => 'This tag is applied to anyone whose optin status is complained.',
			],
			'gh_monthly_tag'        => [
				'tag_name'        => 'Subscribed (Monthly)',
				'tag_description' => 'This tag is applied to anyone whose receives emails monthly.',
			],
			'gh_weekly_tag'         => [
				'tag_name'        => 'Subscribed (Weekly)',
				'tag_description' => 'This tag is applied to anyone who receives emails weekly.',
			],
			'gh_marketable_tag'     => [
				'tag_name'        => 'Marketable',
				'tag_description' => 'This tag is applied to anyone whose optin status is marketable.',
			],
			'gh_non_marketable_tag' => [
				'tag_name'        => 'Non-marketable',
				'tag_description' => 'This tag is applied to anyone whose optin status is non-marketable.',
			],
		];

		return $tags;
	}

	/**
	 * Install the defaults.
	 */
	public function install_default_tags( $force = false ) {
		$tags = $this->get_default_tags();
		foreach ( $tags as $option_name => $tag_args ) {
			if ( $force || ! Plugin::$instance->settings->get_option( $option_name, false ) ) {
				$tags_id = Plugin::$instance->dbs->get_db( 'tags' )->add( $tag_args );
				if ( $tags_id ) {
					Plugin::$instance->settings->update_option( $option_name, $tags_id );
				}
			}
		}
	}

	/**
	 * get the map of optin status to tag
	 *
	 * @return array
	 */
	public function get_tag_map() {

		if ( empty( $this->tag_map ) ) {
			$this->tag_map = [
				Preferences::CONFIRMED    => get_option( 'gh_confirmed_tag', false ),
				Preferences::UNCONFIRMED  => get_option( 'gh_unconfirmed_tag', false ),
				Preferences::UNSUBSCRIBED => get_option( 'gh_unsubscribed_tag', false ),
				Preferences::SPAM         => get_option( 'gh_spammed_tag', false ),
				Preferences::HARD_BOUNCE  => get_option( 'gh_bounced_tag', false ),
				Preferences::COMPLAINED   => get_option( 'gh_complained_tag', false ),
				Preferences::WEEKLY       => get_option( 'gh_weekly_tag', false ),
				Preferences::MONTHLY      => get_option( 'gh_monthly_tag', false ),
				self::MARKETABLE          => get_option( 'gh_marketable_tag', false ),
				self::NON_MARKETABLE      => get_option( 'gh_non_marketable_tag', false ),
			];
		}

		return $this->tag_map;

	}

	/**
	 * Get the associated tag for an optin status.
	 *
	 * @param int $status
	 *
	 * @return bool|mixed
	 */
	public function get_status_tag( $status = 0 ) {

		$map = $this->get_tag_map();

		if ( key_exists( $status, $map ) ) {
			return $map[ $status ];
		}

		return false;
	}

	/**
	 * When the contact is created with an initial optin status, perform the tag mapping.
	 *
	 * @param $contact_id int
	 */
	public function optin_status_set( $contact_id ) {
		if ( ! $contact_id ) {
			return;
		}

		$contact = get_contactdata( $contact_id );

		if ( ! $contact || ! $contact->exists() ) {
			return;
		}

		$this->optin_status_changed( $contact_id, $contact->get_optin_status() );
	}

	protected $mapping_tags = false;

	/**
	 * Set if mapping tags
	 *
	 * @param $set
	 */
	protected function set_mapping_tags( $set ) {
		$this->mapping_tags = (bool) $set;
	}

	/**
	 * Perform the tag mapping.
	 *
	 * @param int     $contact_id the ID of the contact
	 * @param int     $new_status the status.
	 * @param int     $old_status the previous status.
	 * @param Contact $contact
	 *
	 * @return void
	 */
	public function optin_status_changed( $contact_id = 0, $new_status = 0, $old_status = 0, $contact = null ) {

		if ( ! is_a_contact( $contact ) ) {
			return;
		}

		$this->set_mapping_tags( true );

		$non_marketable_tag = $this->get_status_tag( self::NON_MARKETABLE );
		$marketable_tag     = $this->get_status_tag( self::MARKETABLE );

		$remove_tags = [];

		// Remove old status tags
		if ( $old_status ) {
			$remove_tags[] = $this->get_status_tag( $old_status );
		}

		// Remove the non marketable if contact can receive marketing
		$remove_tags[] = $contact->is_marketable() ? $non_marketable_tag : $marketable_tag;

		/* Remove all the un-needed tags */
		$contact->remove_tag( $remove_tags );

		$add_tags = [];

		if ( $new_status ) {
			$add_tags[] = $this->get_status_tag( $new_status );
		}

		// Add the marketable if contact can receive marketing
		$add_tags[] = $contact->is_marketable() ? $marketable_tag : $non_marketable_tag;

		/* Add the tags */
		$contact->apply_tag( $add_tags );

		$this->set_mapping_tags( false );
	}

	/**
	 * Update the optin status if a mapped optin status tag is applied.
	 *
	 * @param $contact Contact
	 * @param $tag_id  int
	 *
	 * @return void
	 */
	public function listen_for_tag_change( $contact, $tag_id ) {

		if ( ! in_array( $tag_id, $this->get_tag_map() ) ) {
			return;
		}

		$preference = array_search( $tag_id, $this->get_tag_map() );

		if ( is_int( $preference ) ) {
			$contact->change_marketing_preference( $preference );
		}
	}

	/**
	 * Listen for the event failed hook.
	 *
	 * What this will allow is to listen for a NON_MARKETABLE error code which will allow the adding of the non marketable tag.
	 *
	 * @param $event Event
	 */
	public function listen_for_non_marketable( $event ) {

		$non_marketable_tag = $this->get_status_tag( self::NON_MARKETABLE );
		$marketable_tag     = $this->get_status_tag( self::MARKETABLE );

		if (
			// Check for unmarketable error code.
			$event->get_last_error()->get_error_code() === 'non_marketable'
			// Check if contact currently has marketable tag
			&& $event->get_contact()->has_tag( $marketable_tag )
			// Ignore monthly or weekly preferences
			&& ! in_array( $event->get_contact()->get_optin_status(), [
				Preferences::WEEKLY,
				Preferences::MONTHLY
			] ) ) {
			$event->get_contact()->remove_tag( $marketable_tag );
			$event->get_contact()->apply_tag( $non_marketable_tag );
		}

	}

	/**
	 * @param $items
	 *
	 * @return int
	 */
	public function max_items( $max, $items ) {
		$max = intval( ini_get( 'max_input_vars' ) );

		return min( $max, 100 );
	}

	/**
	 * Get the IDS of all contacts.
	 *
	 * @param $items
	 *
	 * @return array
	 */
	public function query( $items ) {
		$query = new Contact_Query();
		$items = $query->query( [] );

		$ids = wp_list_pluck( $items, 'ID' );

		return $ids;
	}

	/**
	 * Get the action reference.
	 *
	 * @return string
	 */
	public function get_action() {
		return 'bulk_map_segmentation_tags';
	}

	/**
	 * Do stuff before the loop
	 *
	 * @return void
	 */
	protected function pre_loop() {
	}

	/**
	 * do stuff after the loop
	 *
	 * @return void
	 */
	protected function post_loop() {
	}

	/**
	 * Process an item
	 *
	 * @param $item mixed
	 *
	 * @return void
	 */
	protected function process_item( $item ) {
		$contact = get_contactdata( absint( $item ) );

		$this->optin_status_changed( $contact->get_id(), $contact->get_optin_status(), null, $contact );
	}

	protected function get_return_url() {
		return admin_page_url( 'gh_tools', [ 'tab' => 'misc' ] );
	}

	protected function clean_up() {
		// TODO: Implement clean_up() method.
	}
}
