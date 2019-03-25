<?php
/**
 * Roles and Capabilities
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WPGH_Roles Class
 *
 * This class handles the role creation and assignment of capabilities for those roles.
 *
 * These roles let us have Sales People, Marketers, etc, each of whom can do
 * certain things within the CRM
 *
 * @since 1.4.4
 */
class WPGH_Roles {

	/**
	 * Get things going
	 *
	 * @since 1.4.4
	 */
	public function __construct() {
		add_filter( 'map_meta_cap', array( $this, 'meta_caps' ), 10, 4 );
	}

	/**
	 * Add new shop roles with default WP caps
	 *
	 * @since 1.4.4
	 * @return void
	 */
	public function add_roles() {
		add_role( 'marketer', __( 'Marketer', 'gruondhogg' ), array(
			'read'                   => true,
			'edit_posts'             => true,
			'delete_posts'           => true,
			'unfiltered_html'        => true,
			'upload_files'           => true,
			'export'                 => true,
			'import'                 => true,
			'delete_others_pages'    => true,
			'delete_others_posts'    => true,
			'delete_pages'           => true,
			'delete_private_pages'   => true,
			'delete_private_posts'   => true,
			'delete_published_pages' => true,
			'delete_published_posts' => true,
			'edit_others_pages'      => true,
			'edit_others_posts'      => true,
			'edit_pages'             => true,
			'edit_private_pages'     => true,
			'edit_private_posts'     => true,
			'edit_published_pages'   => true,
			'edit_published_posts'   => true,
			'manage_categories'      => true,
			'manage_links'           => true,
			'moderate_comments'      => true,
			'publish_pages'          => true,
			'publish_posts'          => true,
			'read_private_pages'     => true,
			'read_private_posts'     => true
		) );

		add_role( 'sales_manager', __( 'Sales Manager', 'groundhogg' ), array(
            'read'                   => true,
            'edit_posts'             => false,
            'upload_files'           => true,
            'delete_posts'           => false
		) );
	}

    /**
     * Remove the roles from WPGH
     */
	public function remove_roles()
    {
        $roles = array( 'marketer', 'sales_manager' );
        foreach ( $roles as $role ) {
            remove_role( $role );
        }
    }


    ###################
    ### DEFINE CAPS ###
    ###################

    /**
     * Contacts:
     * - Add Contacts
     * - Delete Contacts
     * - Edit Contacts
     * - View Contacts
     * - Import Contacts
     * - Export Contacts
     *
     * Get caps related to managing contacts
     *
     * @return array
     */
    public function get_contact_caps()
    {
        $caps = array(
            'add_contacts',
            'delete_contacts',
            'edit_contacts',
            'view_contacts',
            'import_contacts',
            'export_contacts'
        );

        return apply_filters( 'wpgh_contact_caps', $caps );
    }

    /**
     * Tags:
     * - Add Tags
     * - Delete Tags
     * - Edit Tags
     * - Manage Tags (for contacts)
     *
     * Get caps related to managing tags
     *
     * @return array
     */
    public function get_tag_caps()
    {
        $caps = array(
            'add_tags',
            'delete_tags',
            'edit_tags',
            'manage_tags',
        );

        return apply_filters( 'wpgh_tag_caps', $caps );
    }

    /**
     * Superlinks:
     * - Add Superlinks
     * - Delete Superlinks
     * - Edit Superlinks
     *
     * Get caps related to managing superlinks
     *
     * @return array
     */
    public function get_superlink_caps()
    {
        $caps = array(
            'add_superlinks',
            'delete_superlinks',
            'edit_superlinks',
        );

        return apply_filters( 'wpgh_superlink_caps', $caps );
    }


	/**
	 * SMS:
	 * - Add sms
	 * - Delete sms
	 * - Edit sms
	 *
	 * Get caps related to managing sms
	 *
	 * @return array
	 */
	public function get_sms_caps()
	{
		$caps = array(
			'add_sms',
			'delete_sms',
			'edit_sms',
			'send_sms',
		);

		return apply_filters( 'wpgh_sms_caps', $caps );
	}

    /**
     * Broadcasts:
     * - Schedule Broadcasts
     * - Cancel Broadcasts
     * - View Broadcasts
     *
     * Get caps related to managing broadcasts
     *
     * @return array
     */
    public function get_broadcast_caps()
    {
        $caps = array(
            'schedule_broadcasts',
            'cancel_broadcasts',
            'view_broadcasts',
        );

        return apply_filters( 'wpgh_broadcast_caps', $caps );
    }

    /**
     *
     * Emails:
     * - Add Emails
     * - Delete Emails
     * - Edit Emails
     * - Send Emails
     *
     * Get caps related to managing emails
     *
     * @return array
     */
    public function get_email_caps()
    {
        $caps = array(
            'add_emails',
            'delete_emails',
            'edit_emails',
            'send_emails',
        );

        return apply_filters( 'wpgh_email_caps', $caps );
    }

    /**
     *
     * Funnels:
     * - Add Funnels
     * - Delete Funnels
     * - Edit Funnels
     * - Export Funnels
     * - Import Funnels
     *
     * Get caps related to managing funnels
     *
     * @return array
     */
    public function get_funnel_caps()
    {
        $caps = array(
            'add_funnels',
            'delete_funnels',
            'edit_funnels',
            'export_funnels',
            'import_funnels',
        );

        return apply_filters( 'wpgh_funnel_caps', $caps );
    }

    /**
     *
     * Events:
     * - execute_events
     * - cancel_events
     * - schedule_events
     *
     * Get caps related to managing contacts
     *
     * @return array
     */
    public function get_event_caps()
    {
        $caps = array(
            'execute_events',
            'cancel_events',
            'schedule_events',
            'view_events',
        );

        return apply_filters( 'wpgh_event_caps', $caps );
    }

    /**
     * Reports
     *  - View Reports
     *  - Export Reports
     *
     * Get caps related to managing reports
     *
     * @return array
     */
    public function get_report_caps()
    {
        $caps = array(
            'view_reports',
            'export_reports',
        );

        return apply_filters( 'wpgh_report_caps', $caps );
    }

    /**
     * Returns a list of all the caps added by GH
     */
    public function get_gh_caps()
    {
        $caps = array_merge(
            $this->get_broadcast_caps(),
            $this->get_contact_caps(),
            $this->get_email_caps(),
            $this->get_event_caps(),
            $this->get_funnel_caps(),
            $this->get_superlink_caps(),
            $this->get_tag_caps(),
            $this->get_report_caps(),
            $this->get_sms_caps()
        );

        return $caps;
    }

	/**
	 * Add new shop-specific capabilities
	 *
	 * @since  1.4.4
	 * @global WP_Roles $wp_roles
	 * @return void
	 */
	public function add_caps()
    {
		global $wp_roles;

		if ( class_exists('WP_Roles') ) {
			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new WP_Roles();
			}
		}

		if ( is_object( $wp_roles ) ) {

            $caps = $this->get_gh_caps();

            /* Add all roles to the Admin levels */
            foreach ( $caps as $cap ){

                $wp_roles->add_cap( 'administrator', $cap );
                $wp_roles->add_cap( 'marketer', $cap );

            }

            /* Sales manager Role */

            /* Contacts*/
            $wp_roles->add_cap( 'sales_manager', 'add_contacts' );
            $wp_roles->add_cap( 'sales_manager', 'edit_contacts' );
            $wp_roles->add_cap( 'sales_manager', 'view_contacts' );

            /* Tags */
            $wp_roles->add_cap( 'sales_manager', 'manage_tags' );

            /* Events */
            $wp_roles->add_cap( 'sales_manager', 'execute_events' );
            $wp_roles->add_cap( 'sales_manager', 'cancel_events' );
            $wp_roles->add_cap( 'sales_manager', 'schedule_events' );
            $wp_roles->add_cap( 'sales_manager', 'view_events' );


		}
	}

	/**
	 * Map meta caps to primitive caps
	 *
	 * @since  2.0
	 * @return array $caps
	 */
	public function meta_caps( $caps, $cap, $user_id, $args ) {

		return $caps;

	}

	/**
	 * Remove core post type capabilities (called on uninstall)
	 *
	 * @since 1.5.2
	 * @return void
	 */
	public function remove_caps() {

		global $wp_roles;

		if ( class_exists( 'WP_Roles' ) ) {
			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new WP_Roles();
			}
		}

		if ( is_object( $wp_roles ) ) {

			/* Shop Manager Capabilities */
            $caps = $this->get_gh_caps();

            /* Add all roles to the Admin levels */
            foreach ( $caps as $cap ){

                $wp_roles->remove_cap( 'administrator', $cap );
                $wp_roles->remove_cap( 'marketer', $cap );

            }

            /* Sales manager Role */

            /* Contacts*/
            $wp_roles->remove_cap( 'sales_manager', 'add_contacts' );
            $wp_roles->remove_cap( 'sales_manager', 'edit_contacts' );
            $wp_roles->remove_cap( 'sales_manager', 'view_contacts' );

            /* Tags */
            $wp_roles->remove_cap( 'sales_manager', 'manage_tags' );

            /* Events */
            $wp_roles->remove_cap( 'sales_manager', 'execute_events' );
            $wp_roles->remove_cap( 'sales_manager', 'cancel_events' );
            $wp_roles->remove_cap( 'sales_manager', 'schedule_events' );
            $wp_roles->remove_cap( 'sales_manager', 'view_events' );
		}
	}

    /**
     * Get the appropriate message for when a user doesn't have a cap.
     *
     * @param $cap string
     * @return string
     */
	public function error( $cap ){

	    $error_str = str_replace( '_', ' ',  $cap  );

	    $error = sprintf( _x( 'Your user role does not have the required permissions to %s.', 'notice', 'groundhogg' ), $error_str );

        return $error;

    }

}
