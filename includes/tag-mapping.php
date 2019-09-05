<?php
namespace Groundhogg;

use Groundhogg\Bulk_Jobs\Bulk_Job;

class Tag_Mapping extends Bulk_Job
{

    const MARKETABLE = 'marketable';
    const NON_MARKETABLE = 'unmarketable';

    /**
     * the tag map.
     *
     * @var array
     */
    private $tag_map = [];

    /**
     * Tag_Mapping constructor.
     */
    public function __construct()
    {

        // Listen for an explicit status change.
        add_action( 'groundhogg/contact/preferences/updated', [ $this, 'optin_status_changed' ], 10, 3 );

        // Contact's marketability can expire with time, but it's too costly to setup a cronjob
        // So instead we'll listen for an event failed. #goodenough
        add_action( 'groundhogg/event/failed', [ $this, 'listen_for_non_marketable' ] );

        if ( get_option( 'gh_optin_status_job', false ) ){
            add_action( 'admin_init', [ $this, 'add_upgrade_notice' ] );
        }

        // Quick hook to update the marketing preference automatically when a contact is created for the first time.
        add_action( 'groundhogg/contact/post_create', [ $this, 'change_marketing_preference' ], 10, 3 );
        add_action( 'groundhogg/contact/post_create', [ $this, 'auto_map_roles' ], 10, 3 );

        // Map User Roles...
        add_action( 'add_user_role', [ $this, 'apply_tags_to_contact_from_new_roles' ], 10, 2 );
        add_action( 'set_user_role', [ $this, 'apply_tags_to_contact_from_changed_roles' ], 10, 3 );
        add_action( 'remove_user_role', [ $this, 'remove_tags_from_contact_from_remove_roles' ], 10, 2 );

        add_action( 'admin_init', function (){
            if ( ! get_option( 'gh_confirmed_tag' ) ){
                Plugin::$instance->tag_mapping->install_default_tags();
            }
        } );

        add_action( 'admin_init', [ $this, 'reset_tags' ] );

        parent::__construct();
    }

    public function reset_tags()
    {
        if ( current_user_can( 'manage_options' ) && wp_verify_nonce( get_request_var( 'reset_tags' ), 'reset_tags' ) ){
            $this->install_default_tags();
            Plugin::$instance->notices->add( 'tags_reset', __( 'Tags have been reset!', 'groundhogg' ) );
        }
    }

    public function reset_tags_ui()
    {
        ?>
        <a href="<?php echo wp_nonce_url( $_SERVER[ 'REQUEST_URI' ], 'reset_tags', 'reset_tags' ); ?>" class="button-secondary"><?php _ex( 'Reset Tags', 'action', 'groundhogg' ) ?></a>
        <?php
    }

    /**
     * Whenever a contact is created if user roles are available automap them
     *
     * @param $id int
     * @param $data array
     * @param $contact Contact
     */
    public function auto_map_roles( $id, $data, $contact )
    {
        if ( $contact->get_userdata() ){
            $contact->add_tag( $this->get_roles_pretty_names( $contact->get_userdata()->roles ) );
        }
    }

    /**
     * This auto runs the contact function "change_marketing_preference whenever a contact is created for the first time."
     * This will then perform our tag associative mapping functions...
     *
     * @param $id int
     * @param $data array
     * @param $contact Contact
     */
    public function change_marketing_preference( $id, $data, $contact )
    {
        $contact->change_marketing_preference( $contact->get_optin_status() );
    }

    /**
     * When a role is set also set the tag
     *
     * @param $user_id int
     * @param $role string
     * @param $old_roles string[]
     */
    public function apply_tags_to_contact_from_changed_roles( $user_id, $role, $old_roles )
    {
        $contact = Plugin::$instance->utils->get_contact( $user_id, true );

        if ( ! $contact || ! $contact->exists() ){
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
     * @param $role string
     */
    public function remove_tags_from_contact_from_remove_roles( $user_id, $role )
    {
        $contact = Plugin::$instance->utils->get_contact( $user_id, true );
        $role = $this->get_role_pretty_name( $role );
        $contact->remove_tag( $role );
    }

    /**
     * When a role is added also add the tag
     *
     * @param $user_id int
     * @param $role string
     */
    public function apply_tags_to_contact_from_new_roles( $user_id, $role )
    {
        $contact = Plugin::$instance->utils->get_contact( $user_id, true );

        if ( ! $contact || ! $contact->exists() ){
            return;
        }

        $role = $this->get_role_pretty_name( $role );
        $contact->add_tag( $role );
    }

    /**
     * Convert an array of roles to n array of display roles
     *
     * @param $roles array an array of user roles...
     * @return array an array of pretty role names.
     */
    public function get_roles_pretty_names( $roles )
    {
        $pretty_roles = array();

        foreach ( $roles as $role ){
            $pretty_roles[] = $this->get_role_pretty_name( $role );
        }

        return $pretty_roles;
    }

    /**
     * Convert a role to a tag name
     *
     * @param $role string the user role
     * @return int the ID of the tag
     */
    public function convert_role_to_tag( $role )
    {
        $tags = Plugin::$instance->dbs->get_db('tags' )->validate( $this->get_role_pretty_name( $role ) );
        return array_shift( $tags );
    }

    /**
     * Get the pretty name of a role
     *
     * @param $role string
     * @return string
     */
    public function get_role_pretty_name( $role )
    {
        return translate_user_role( wp_roles()->roles[ $role ]['name'] );
    }

    /**
     * Add a notice promting the user to perform the retroactive bulk action.
     */
    public function add_upgrade_notice()
    {
        $notice = sprintf(
            __( "New features are now available, but we need to perform an upgrade process first! %s", 'groundhogg' ),
            sprintf( "&nbsp;&nbsp;<a href='%s' class='button button-secondary'>Start Upgrade</a>", $this->get_start_url() )
        );

        Plugin::$instance->notices->add( 'status_tag_upgrade_notice', $notice, 'info' );
    }

    /**
     * Get the list of default tags and option names...
     *
     * @return array
     */
    private function get_default_tags()
    {
        $tags = [
            'gh_confirmed_tag' => [
               'tag_name' => 'Confirmed',
               'tag_description' => 'This tags is applied to anyone whose optin status is confirmed.',
            ],
            'gh_unconfirmed_tag' => [
               'tag_name' => 'Unconfirmed',
               'tag_description' => 'This tag is applied to anyone whose optin status is unconfirmed.',
            ],
            'gh_unsubscribed_tag' => [
               'tag_name' => 'Unsubscribed',
               'tag_description' => 'This tag is applied to anyone whose optin status is unsubscribed.',
            ],
            'gh_spammed_tag' => [
               'tag_name' => 'Spam',
               'tag_description' => 'This tag is applied to anyone whose optin status is spam.',
            ],
            'gh_bounced_tag' => [
               'tag_name' => 'Bounced',
               'tag_description' => 'This tag is applied to anyone whose optin status is bounced.',
            ],
            'gh_complained_tag' => [
               'tag_name' => 'Complained',
               'tag_description' => 'This tag is applied to anyone whose optin status is complained.',
            ],
            'gh_monthly_tag' => [
               'tag_name' => 'Subscribed (Monthly)',
               'tag_description' => 'This tag is applied to anyone whose receives emails monthly.',
            ],
            'gh_weekly_tag' => [
               'tag_name' => 'Subscribed (Weekly)',
               'tag_description' => 'This tag is applied to anyone who receives emails weekly.',
            ],
            'gh_marketable_tag' => [
               'tag_name' => 'Marketable',
               'tag_description' => 'This tag is applied to anyone whose optin status is marketable.',
            ],
            'gh_non_marketable_tag' => [
               'tag_name' => 'Non-marketable',
               'tag_description' => 'This tag is applied to anyone whose optin status is non-marketable.',
            ],
        ];

        return $tags;
    }

    /**
     * Install the defaults.
     */
    public function install_default_tags()
    {
        $tags = $this->get_default_tags();
        foreach ( $tags as $option_name => $tag_args ){
            if ( ! Plugin::$instance->settings->get_option( $option_name, false ) ){
                $tags_id = Plugin::$instance->dbs->get_db( 'tags' )->add( $tag_args );
                if ( $tags_id ){
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
    public function get_tag_map()
    {

        if ( empty( $this->tag_map ) ){
            $this->tag_map = [
                Preferences::CONFIRMED    => Plugin::$instance->settings->get_option( 'gh_confirmed_tag',   false ),
                Preferences::UNCONFIRMED  => Plugin::$instance->settings->get_option( 'gh_unconfirmed_tag', false ),
                Preferences::UNSUBSCRIBED => Plugin::$instance->settings->get_option( 'gh_unsubscribed_tag', false ),
                Preferences::SPAM         => Plugin::$instance->settings->get_option( 'gh_spammed_tag',     false ),
                Preferences::HARD_BOUNCE  => Plugin::$instance->settings->get_option( 'gh_bounced_tag',     false ),
                Preferences::COMPLAINED   => Plugin::$instance->settings->get_option( 'gh_complained_tag',  false ),
                Preferences::WEEKLY       => Plugin::$instance->settings->get_option( 'gh_weekly_tag',      false ),
                Preferences::MONTHLY      => Plugin::$instance->settings->get_option( 'gh_monthly_tag',     false ),
                self::MARKETABLE          => Plugin::$instance->settings->get_option( 'gh_marketable_tag',  false ),
                self::NON_MARKETABLE      => Plugin::$instance->settings->get_option( 'gh_non_marketable_tag', false ),
            ];
        }

        return $this->tag_map;

    }

    /**
     * Get the associated tag for an optin status.
     *
     * @param int $status
     * @return bool|mixed
     */
    public function get_status_tag( $status = 0 )
    {

        $map = $this->get_tag_map();

        if ( key_exists( $status, $map ) ){
            return $map[ $status ];
        }

        return false;

    }

    /**
     * Perform the tag mapping.
     *
     * @param $contact_id int the ID of the contact
     * @param int $status the status.
     * @param int $old_status the previous status.
     *
     * @return void
     */
    public function optin_status_changed( $contact_id=0, $status=0, $old_status=0 )
    {

        $contact = Plugin::$instance->utils->get_contact( $contact_id );

        if ( ! $contact )
            return;

        $non_marketable_tag = $this->get_status_tag( self::NON_MARKETABLE );
        $marketable_tag = $this->get_status_tag( self::MARKETABLE );

        /* Tags to remove */
        $remove_tags = [
            $this->get_status_tag( $old_status ),
        ];

        /* Marketable decision */
        if ( $contact->is_marketable() && $contact->has_tag( $non_marketable_tag ) ){
            $remove_tags[] = $non_marketable_tag;
        } else if ( ! $contact->is_marketable() && $contact->has_tag( $marketable_tag ) ){
            $remove_tags[] = $marketable_tag;
        }

        /* Remove all the un-needed tags */
        $contact->remove_tag( $remove_tags );

        /* Tags to add */
        $add_tags = [
            $this->get_status_tag( $status ),
        ];

        if ( $contact->is_marketable() && ! $contact->has_tag( $marketable_tag ) ){
            $add_tags[] = $marketable_tag;
        } else if ( ! $contact->is_marketable() && ! $contact->has_tag( $non_marketable_tag ) ){
            $add_tags[] = $non_marketable_tag;
        }

        /* Add the tags */
        $contact->apply_tag( $add_tags );

    }

    /**
     * Listen for the event failed hook.
     *
     * What this will allow is to listen for a NON_MARKETABLE error code which will allow the adding of the non marketable tag.
     *
     * @param $event Event
     */
    public function listen_for_non_marketable( $event )
    {

        $non_marketable_tag = $this->get_status_tag( self::NON_MARKETABLE );
        $marketable_tag = $this->get_status_tag( self::MARKETABLE );

        if (
        	// Check for unmarketable error code.
        	$event->get_last_error()->get_error_code() === 'non_marketable'
	        // Check if contact currently has marketable tag
	        && $event->get_contact()->has_tag( $marketable_tag )
	        // Ignore monthly or weekly preferences
            && ! in_array( $event->get_contact()->get_optin_status(), [ Preferences::WEEKLY, Preferences::MONTHLY ] ) ){
            $event->get_contact()->remove_tag( $marketable_tag );
            $event->get_contact()->apply_tag( $non_marketable_tag );
        }

    }

    /**
     * @param $items
     * @return int
     */
    public function max_items( $max, $items ){
        $max = intval( ini_get( 'max_input_vars' ) );
        return min( $max, 100 );
    }

    /**
     * Get the IDS of all contacts.
     *
     * @param $items
     * @return array
     */
    public function query( $items )
    {
        $query = new Contact_Query();
        $items = $query->query([]);

        $ids = wp_list_pluck( $items, 'ID' );

        return $ids;
    }

    /**
     * Get the action reference.
     *
     * @return string
     */
    public function get_action()
    {
        return 'bulk_map_segmentation_tags';
    }

    /**
     * Do stuff before the loop
     *
     * @return void
     */
    protected function pre_loop(){}

    /**
     * do stuff after the loop
     *
     * @return void
     */
    protected function post_loop(){}

    /**
     * Process an item
     *
     * @param $item mixed
     * @param $args array
     * @return void
     */
    protected function process_item( $item )
    {
        $contact = Plugin::$instance->utils->get_contact( absint( $item ) );

        if ( $contact ){

            $tags = [];

            $tags[] = $this->get_status_tag( $contact->get_optin_status() );
            $tags[] = $contact->is_marketable() ? $this->get_status_tag( self::MARKETABLE ) : $this->get_status_tag( self::NON_MARKETABLE );

            $role_tags = $this->get_roles_pretty_names( $contact->get_userdata()->roles );
            $tags = array_merge( $tags, $role_tags );

            $contact->apply_tag( $tags );

        }
    }

    /**
     * Cleanup any options/transients/notices after the bulk job has been processed.
     *
     * @return void
     */
    protected function clean_up()
    {
        Plugin::$instance->notices->remove( 'status_tag_upgrade_notice' );
        Plugin::$instance->settings->delete_option( 'gh_optin_status_job' );
    }

    /**
     * @return string
     */
    protected function get_finished_notice()
    {
        return _x('Job finished! Optin status & User Role tag mapping has now been enabled.', 'notice', 'groundhogg');
    }
}