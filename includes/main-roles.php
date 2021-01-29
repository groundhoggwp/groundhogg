<?php

namespace Groundhogg;


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Roles Class
 *
 * This class handles the role creation and assignment of capabilities for those roles.
 *
 * These roles let us have Sales People, Marketers, etc, each of whom can do
 * certain things within the CRM
 *
 * @since 1.4.4
 */
class Main_Roles extends Roles {

	/**
	 * Valid owner roles..
	 *
	 * @var array
	 */
	public static $owner_roles = [
		'administrator',
		'marketer',
		'sales_manager',
		'sales_rep'
	];

	/**
	 * Get the list of valid owner roles...
	 *
	 * @return mixed|void
	 */
	public static function get_owner_roles (){
		return apply_filters( 'groundhogg/owner_roles', self::$owner_roles );
	}

	/**
	 * Returns an array  of role => [
	 *  'role' => '',
	 *  'name' => '',
	 *  'caps' => []
	 * ]
	 *
	 * In this case caps should just be the meta cap map for other WP related stuff.
	 *
	 * @return array[]
	 */
	public function get_roles() {
		// TODO Revisit sales rep & sales manager caps...

		return apply_filters( 'groundhogg/roles/get_roles', [
			[
				'role' => 'marketer',
				'name' => _x( 'Marketer', 'role', 'groundhogg' ),
				'caps' => [
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
					'read_private_posts'     => true,
					'add_activity'           => true,
					'edit_activity'          => true,
					'view_activity'          => true,

				]
			],
			[
				'role' => 'sales_manager',
				'name' => _x( 'Sales Manager', 'role', 'groundhogg' ),
				'caps' => [
					'read'         => true,
					'edit_posts'   => false,
					'upload_files' => true,
					'delete_posts' => false,

				]
			],
			[
				'role' => 'sales_rep',
				'name' => _x( 'Sales Representative', 'role', 'groundhogg' ),
				'caps' => [
					'read'         => true,
					'edit_posts'   => false,
					'upload_files' => true,
					'delete_posts' => false
				]
			]
		] );
	}

	/**
	 * Return all GH Caps
	 *
	 * @return array
	 */
	public function get_administrator_caps() {
		return $this->get_all_caps();
	}

	/**
	 * Return all GH Caps
	 *
	 * @return array
	 */
	public function get_marketer_caps() {
		return $this->get_all_caps();
	}

	/**
	 * Return only specific caps...
	 *
	 * @return array
	 */
	public function get_sales_manager_caps() {
		return [
			'edit_contacts',
			'view_contacts',
			'view_all_contacts',
			'import_contacts',
			'send_emails',
			'view_events',
			'manage_tags',
			'download_contact_files',
			'add_notes',
			'delete_notes',
			'edit_notes',
			'view_notes',
			'add_activity',
			'view_activity',
		];
	}

	/**
	 * Return only specific caps...
	 *
	 * @return array
	 */
	public function get_sales_rep_caps() {
		return [
			'edit_contacts',
			'view_contacts',
			'view_own_contacts',
			'import_contacts',
			'send_emails',
			'view_events',
			'manage_tags',
			'download_contact_files',
			'add_notes',
			'delete_notes',
			'edit_notes',
			'view_notes',
			'add_activity',
			'view_activity',
		];
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
	public function get_contact_caps() {
		$caps = array(
			'add_contacts',
			'delete_contacts',
			'edit_contacts',
			'view_contacts',
			'view_all_contacts',
			'import_contacts',
			'export_contacts'
		);

		return apply_filters( 'groundhogg/roles/caps/contacts', $caps );
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
	public function get_tag_caps() {
		$caps = array(
			'add_tags',
			'delete_tags',
			'edit_tags',
			'manage_tags',
			'view_tags',
		);

		return apply_filters( 'groundhogg/roles/caps/tags', $caps );
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
	public function get_note_caps() {
		$caps = array(
			'add_notes',
			'delete_notes',
			'edit_notes',
			'view_notes',
		);

		return apply_filters( 'groundhogg/roles/caps/notes', $caps );
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
	public function get_broadcast_caps() {
		$caps = array(
			'schedule_broadcasts',
			'cancel_broadcasts',
			'view_broadcasts',
		);

		return apply_filters( 'groundhogg/roles/caps/broadcasts', $caps );
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
	public function get_email_caps() {
		$caps = array(
			'add_emails',
			'delete_emails',
			'edit_emails',
			'send_emails',
			'view_emails',
		);

		return apply_filters( 'groundhogg/roles/caps/emails', $caps );
	}

	/**
	 * Activity:
	 * - Add Activity
	 * - Delete Activity
	 * - Edit Activity
	 * - Send Activity
	 *
	 * Get caps related to managing activities
	 *
	 * @return array
	 */
	public function get_activity_caps() {
		$caps = array(
			'add_activity',
			'delete_activity',
			'edit_activity',
			'view_activity',
		);

		return apply_filters( 'groundhogg/roles/caps/activity', $caps );
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
	public function get_funnel_caps() {
		$caps = array(
			'add_funnels',
			'delete_funnels',
			'edit_funnels',
			'export_funnels',
			'import_funnels',
			'view_funnels',
		);

		return apply_filters( 'groundhogg/roles/caps/funnels', $caps );
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
	public function get_event_caps() {
		$caps = array(
			'execute_events',
			'cancel_events',
			'schedule_events',
			'view_events',
		);

		return apply_filters( 'groundhogg/roles/caps/events', $caps );
	}

	/**
	 * Roles for managing contact files
	 *
	 * @return array
	 */
	public function get_file_caps() {
		return [
			'download_files',
			'download_contact_files',
		];
	}

	/**
	 * Reports
	 *  - View Reports
	 *  - Export Reports
	 *
	 * Get caps related to managing reporting
	 *
	 * @return array
	 */
	public function get_report_caps() {
		$caps = array(
			'view_reports',
			'export_reports',
		);

		return apply_filters( 'groundhogg/roles/caps/reporting', $caps );
	}

	/**
	 * Get unrelated extra caps...
	 *
	 * @return string[]
	 */
	public function get_other_caps() {

		$caps = array(
			'perform_bulk_actions',
			'manage_gh_licenses',
		);

		return apply_filters( 'groundhogg/roles/caps/other', $caps );
	}


	/**
	 * Returns a list of all the caps added by GH
	 */
	public function get_all_caps() {
		$caps = array_merge(
			$this->get_broadcast_caps(),
			$this->get_contact_caps(),
			$this->get_email_caps(),
			$this->get_event_caps(),
			$this->get_funnel_caps(),
			$this->get_tag_caps(),
			$this->get_report_caps(),
			$this->get_other_caps(),
			$this->get_file_caps(),
			$this->get_note_caps(),
			$this->get_activity_caps()
		);

		return $caps;
	}

	/**
	 * Returns an array of roles used for select elements.
	 *
	 * @return string[]
	 */
	public function get_roles_for_select() {
		$editable_roles = array_reverse( get_editable_roles() );

		$roles = [];

		foreach ( $editable_roles as $role => $details ) {
			$name           = translate_user_role( $details['name'] );
			$roles[ $role ] = $name;
		}

		return $roles;
	}

	/**
	 * Returns an array of roles used for select elements.
	 *
	 * @return array[]
	 */
	public function get_roles_for_react_select() {
		if ( ! function_exists( 'get_editable_roles' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/user.php' );
		}

		$editable_roles = array_reverse( get_editable_roles() );

		$roles = [];

		foreach ( $editable_roles as $role => $details ) {
			$name    = translate_user_role( $details['name'] );
			$roles[] = [ 'value' => $role, 'label' => $name ];
		}

		return $roles;
	}

	/**
	 * Return a cap to check against the admin to ensure caps are also installed.
	 *
	 * @return mixed
	 */
	protected function get_admin_cap_check() {
		return 'view_all_contacts';
	}
}
