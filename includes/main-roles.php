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
	public static function get_owner_roles() {
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
					'view_admin_dashboard'   => true,
					'level_1'                => true, // Deprecated capability required for showing in authors dropdown
				]
			],
			[
				'role' => 'sales_manager',
				'name' => _x( 'Sales Manager', 'role', 'groundhogg' ),
				'caps' => [
					'view_admin_dashboard' => true,
					'read'                 => true,
					'edit_posts'           => false,
					'upload_files'         => true,
					'delete_posts'         => false
				]
			],
			[
				'role' => 'sales_rep',
				'name' => _x( 'Sales Representative', 'role', 'groundhogg' ),
				'caps' => [
					'view_admin_dashboard' => true,
					'read'                 => true,
					'edit_posts'           => false,
					'upload_files'         => true,
					'delete_posts'         => false
				]
			]
		] );
	}

	/**
	 * Map caps to primitives
	 *
	 * @param array  $caps
	 * @param string $cap
	 * @param int    $user_id
	 * @param array  $args
	 *
	 * @return array
	 */
	public function map_meta_cap( $caps, $cap, $user_id, $args ) {

		switch ( $cap ) {
			case 'download_imports':
			case 'download_exports':
			case 'delete_imports':
			case 'delete_exports':

				$parts = explode( '_', $cap );

				$caps = [
					'view_previous_' . $parts[1],
					$parts[0] . '_files',
				];

				break;
			case 'edit_contact':
			case 'view_contact':
			case 'delete_contact':
			case 'edit_note':
			case 'view_note':
			case 'delete_note':
			case 'edit_task':
			case 'view_task':
			case 'delete_task':
			case 'edit_funnel':
			case 'edit_email':

				$caps = [];

				$parts       = explode( '_', $cap );
				$action      = $parts[0];
				$object_type = $parts[1];

				$caps[] = $action . '_' . $object_type . 's';

				$object = $args[0];

				// didn't pass the full object
				if ( ! is_object( $object ) || ! method_exists( $object, 'get_id' ) ) {
					$object = create_object_from_type( $object, $object_type );
				}

				// Not a real object
				if ( ! $object->exists() ) {
					$caps[] = 'do_not_allow';
					break;
				}

				switch ( $object_type ) {
					case 'note':
					case 'task':
					case 'contact':
					default:
						// Most common methods for comparing
						if ( ( method_exists( $object, 'get_owner_id' ) && $object->get_owner_id() !== $user_id ) ) {
							$caps[] = $action . '_others_' . $object_type . 's';
						}
						break;
				}

				break;
			case 'download_file':

				$file_path = $args[0];

				// Compat for WooCommerce usage of download file
				if ( ! is_string( $file_path ) ) {
					break;
				}

				if ( is_super_admin( $user_id ) ){
					break;
				}

				$caps = [];

				$file_path = wp_normalize_path( $file_path );

				$path = explode( '/', $file_path );

				switch ( $path[0] ) {
					case 'uploads':
						$caps[] = 'download_contact_files';

						$request        = $args[1];
						$contact        = get_array_var( $request, 'id' );
						$contact_folder = $path[1];

						$contact = new Contact( $contact );

						if ( ! $contact->exists() ) {
							$caps[] = 'do_not_allow';
							break;
						}

						// Trying to cheat the system
						if ( $contact_folder !== $contact->get_upload_folder_basename() ) {
							$caps[] = 'do_not_allow';
							break;
						}

						// Trying to download files of contacts that don't belong to them
						if ( ! $contact->owner_is( $user_id ) ) {
							$caps[] = 'view_others_contacts';
						}

						break;
					case 'exports':
						$caps[] = 'export_contacts';
						break;
					case 'imports':
						$caps[] = 'import_contacts';
						$caps[] = 'download_files';
						break;
					default:
						$caps[] = 'do_not_allow';
						break;
				}

				break;

		}

		return $caps;
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
			'add_contacts',
			'edit_contacts',
			'edit_others_contacts',
			'view_contacts',
			'view_others_contacts',
			'import_contacts',
			'export_contacts',
			'send_emails',
			'view_events',
			'manage_tags',
			'download_contact_files',

			// notes
			'add_notes',
			'view_others_notes',
			'edit_others_notes',
			'delete_others_notes',
			'delete_notes',
			'edit_notes',
			'view_notes',

			// tasks
			'add_tasks',
			'view_others_tasks',
			'edit_others_tasks',
			'delete_others_tasks',
			'delete_tasks',
			'edit_tasks',
			'view_tasks',

			'view_funnels',
			'view_emails',
			'view_broadcasts',
			'perform_bulk_actions'
		];
	}

	/**
	 * Return only specific caps...
	 *
	 * @return array
	 */
	public function get_sales_rep_caps() {
		return [
			// contacts
			'edit_contacts',
			'add_contacts',
			'view_contacts',
			'import_contacts',
			'export_contacts',
			// email
			'send_emails',
			// tags
			'manage_tags',
			// files
			'download_contact_files',
			// notes
			'view_others_notes',
			'add_notes',
			'delete_notes',
			'edit_notes',
			'view_notes',
			// tasks
			'view_others_tasks',
			'add_tasks',
			'delete_tasks',
			'edit_tasks',
			'view_tasks',

			'view_funnels',
			'view_emails',
			'view_broadcasts',
			'perform_bulk_actions',
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
			'edit_contacts',
			'edit_others_contacts',
			'view_contacts',
			'view_others_contacts',
			'delete_contacts',
			'delete_others_contacts',
			'import_contacts',
			'export_contacts',
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
			'view_emails',
			'delete_emails',
			'edit_emails',
			'send_emails',
		);

		return apply_filters( 'groundhogg/roles/caps/emails', $caps );
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
			'view_funnels',
			'delete_funnels',
			'edit_funnels',
			'export_funnels',
			'import_funnels',
		);

		return apply_filters( 'groundhogg/roles/caps/funnels', $caps );
	}


	/**
	 * caps for notes
	 *
	 * @return mixed|null
	 */
	public function get_note_caps() {
		$caps = array(
			'add_notes',
			'delete_notes',
			'edit_notes',
			'view_notes',
			'delete_others_notes',
			'edit_others_notes',
			'view_others_notes'
		);

		return apply_filters( 'groundhogg/roles/caps/notes', $caps );
	}

	/**
	 * Caps for tasks
	 *
	 * @return mixed|null
	 */
	public function get_task_caps() {
		$caps = array(
			'add_tasks',
			'delete_tasks',
			'edit_tasks',
			'view_tasks',
			'delete_others_tasks',
			'edit_others_tasks',
			'view_others_tasks'
		);

		return apply_filters( 'groundhogg/roles/caps/tasks', $caps );
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
			'delete_files',
			'view_previous_imports',
			'view_previous_exports',
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
	 * Activity
	 *  - View Reports
	 *  - Export Reports
	 *
	 * Get caps related to managing reporting
	 *
	 * @return array
	 */
	public function get_activity_caps() {
		$caps = array(
			'add_activity',
			'view_activity',
			'edit_activity',
			'delete_activity',
		);

		return apply_filters( 'groundhogg/roles/caps/reporting', $caps );
	}

	/**
	 * Get unrelated extra caps...
	 *
	 * @return string[]
	 */
	public function get_log_caps() {

		$caps = array(
			'view_logs',
			'delete_logs',
		);

		return apply_filters( 'groundhogg/roles/caps/logs', $caps );
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
			'edit_custom_properties',
			'manage_campaigns'
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
			$this->get_log_caps(),
			$this->get_note_caps(),
			$this->get_task_caps(),
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
	 * Return a cap to check against the admin to ensure caps are also installed.
	 *
	 * @return mixed
	 */
	protected function get_admin_cap_check() {
		return 'view_contacts';
	}
}
