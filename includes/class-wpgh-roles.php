<?php
/**
 * Roles and Capabilities
 *
 * @package     EDD
 * @subpackage  Classes/Roles
 * @copyright   Copyright (c) 2015, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.4.4
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
            'schedule_broadcast',
            'cancel_broadcast',
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
        );

        return apply_filters( 'wpgh_event_caps', $caps );
    }

	/**
	 * Add new shop-specific capabilities
	 *
	 * @since  1.4.4
	 * @global WP_Roles $wp_roles
	 * @return void
	 */
	public function add_caps() {
		global $wp_roles;

		if ( class_exists('WP_Roles') ) {
			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new WP_Roles();
			}
		}

		if ( is_object( $wp_roles ) ) {
			$wp_roles->add_cap( 'sales_manager', 'gh_manage_contacts' );
			$wp_roles->add_cap( 'sales_manager', 'gh_manage_tags' );
			$wp_roles->add_cap( 'shop_manager', 'export_shop_reports' );
			$wp_roles->add_cap( 'shop_manager', 'manage_shop_settings' );
			$wp_roles->add_cap( 'shop_manager', 'manage_shop_discounts' );

			$wp_roles->add_cap( 'administrator', 'view_shop_reports' );
			$wp_roles->add_cap( 'administrator', 'view_shop_sensitive_data' );
			$wp_roles->add_cap( 'administrator', 'export_shop_reports' );
			$wp_roles->add_cap( 'administrator', 'manage_shop_discounts' );
			$wp_roles->add_cap( 'administrator', 'manage_shop_settings' );

			// Add the main post type capabilities
			$capabilities = $this->get_core_caps();
			foreach ( $capabilities as $cap_group ) {
				foreach ( $cap_group as $cap ) {
					$wp_roles->add_cap( 'shop_manager', $cap );
					$wp_roles->add_cap( 'administrator', $cap );
					$wp_roles->add_cap( 'shop_worker', $cap );
				}
			}

			$wp_roles->add_cap( 'shop_accountant', 'edit_products' );
			$wp_roles->add_cap( 'shop_accountant', 'read_private_products' );
			$wp_roles->add_cap( 'shop_accountant', 'view_shop_reports' );
			$wp_roles->add_cap( 'shop_accountant', 'export_shop_reports' );
			$wp_roles->add_cap( 'shop_accountant', 'edit_shop_payments' );

			$wp_roles->add_cap( 'shop_vendor', 'edit_product' );
			$wp_roles->add_cap( 'shop_vendor', 'edit_products' );
			$wp_roles->add_cap( 'shop_vendor', 'delete_product' );
			$wp_roles->add_cap( 'shop_vendor', 'delete_products' );
			$wp_roles->add_cap( 'shop_vendor', 'publish_products' );
			$wp_roles->add_cap( 'shop_vendor', 'edit_published_products' );
			$wp_roles->add_cap( 'shop_vendor', 'upload_files' );
			$wp_roles->add_cap( 'shop_vendor', 'assign_product_terms' );
		}
	}

	/**
	 * Gets the core post type capabilities
	 *
	 * @since  1.4.4
	 * @return array $capabilities Core post type capabilities
	 */
	public function get_core_caps() {
		$capabilities = array();

		$capability_types = array( 'product', 'shop_payment', 'shop_discount' );

		foreach ( $capability_types as $capability_type ) {
			$capabilities[ $capability_type ] = array(
				// Post type
				"edit_{$capability_type}",
				"read_{$capability_type}",
				"delete_{$capability_type}",
				"edit_{$capability_type}s",
				"edit_others_{$capability_type}s",
				"publish_{$capability_type}s",
				"read_private_{$capability_type}s",
				"delete_{$capability_type}s",
				"delete_private_{$capability_type}s",
				"delete_published_{$capability_type}s",
				"delete_others_{$capability_type}s",
				"edit_private_{$capability_type}s",
				"edit_published_{$capability_type}s",

				// Terms
				"manage_{$capability_type}_terms",
				"edit_{$capability_type}_terms",
				"delete_{$capability_type}_terms",
				"assign_{$capability_type}_terms",

				// Custom
				"view_{$capability_type}_stats",
				"import_{$capability_type}s",
			);
		}

		return $capabilities;
	}

	/**
	 * Map meta caps to primitive caps
	 *
	 * @since  2.0
	 * @return array $caps
	 */
	public function meta_caps( $caps, $cap, $user_id, $args ) {

		switch( $cap ) {

			case 'view_product_stats' :

				if( empty( $args[0] ) ) {
					break;
				}

				$download = get_post( $args[0] );
				if ( empty( $download ) ) {
					break;
				}

				if( user_can( $user_id, 'view_shop_reports' ) || $user_id == $download->post_author ) {
					$caps = array();
				}

				break;
		}

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
			/** Shop Manager Capabilities */
			$wp_roles->remove_cap( 'shop_manager', 'view_shop_reports' );
			$wp_roles->remove_cap( 'shop_manager', 'view_shop_sensitive_data' );
			$wp_roles->remove_cap( 'shop_manager', 'export_shop_reports' );
			$wp_roles->remove_cap( 'shop_manager', 'manage_shop_discounts' );
			$wp_roles->remove_cap( 'shop_manager', 'manage_shop_settings' );

			/** Site Administrator Capabilities */
			$wp_roles->remove_cap( 'administrator', 'view_shop_reports' );
			$wp_roles->remove_cap( 'administrator', 'view_shop_sensitive_data' );
			$wp_roles->remove_cap( 'administrator', 'export_shop_reports' );
			$wp_roles->remove_cap( 'administrator', 'manage_shop_discounts' );
			$wp_roles->remove_cap( 'administrator', 'manage_shop_settings' );

			/** Remove the Main Post Type Capabilities */
			$capabilities = $this->get_core_caps();

			foreach ( $capabilities as $cap_group ) {
				foreach ( $cap_group as $cap ) {
					$wp_roles->remove_cap( 'shop_manager', $cap );
					$wp_roles->remove_cap( 'administrator', $cap );
					$wp_roles->remove_cap( 'shop_worker', $cap );
				}
			}

			/** Shop Accountant Capabilities */
			$wp_roles->remove_cap( 'shop_accountant', 'edit_products' );
			$wp_roles->remove_cap( 'shop_accountant', 'read_private_products' );
			$wp_roles->remove_cap( 'shop_accountant', 'view_shop_reports' );
			$wp_roles->remove_cap( 'shop_accountant', 'export_shop_reports' );

			/** Shop Vendor Capabilities */
			$wp_roles->remove_cap( 'shop_vendor', 'edit_product' );
			$wp_roles->remove_cap( 'shop_vendor', 'edit_products' );
			$wp_roles->remove_cap( 'shop_vendor', 'delete_product' );
			$wp_roles->remove_cap( 'shop_vendor', 'delete_products' );
			$wp_roles->remove_cap( 'shop_vendor', 'publish_products' );
			$wp_roles->remove_cap( 'shop_vendor', 'edit_published_products' );
			$wp_roles->remove_cap( 'shop_vendor', 'upload_files' );
		}
	}
}
