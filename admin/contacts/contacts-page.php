<?php

namespace Groundhogg\Admin\Contacts;

use Groundhogg\Admin\Admin_Page;
use Groundhogg\Admin\Contacts\Tables\Contact_Table_Columns;
use Groundhogg\Classes\Note;
use Groundhogg\Saved_Searches;
use Groundhogg\Scripts;
use Groundhogg\Step;
use function Groundhogg\admin_page_url;
use function Groundhogg\do_replacements;
use function Groundhogg\get_array_var;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_post_var;
use function Groundhogg\get_request_query;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use function Groundhogg\get_url_var;
use function Groundhogg\html;
use function Groundhogg\is_a_contact;
use function Groundhogg\kses_wrapper;
use function Groundhogg\modal_link_url;
use function Groundhogg\normalize_files;
use Groundhogg\Plugin;
use Groundhogg\Contact;
use Groundhogg\Preferences;
use function Groundhogg\sanitize_email_header;
use function Groundhogg\send_email_notification;
use function Groundhogg\set_request_var;
use function Groundhogg\validate_tags;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Page gh_contacts
 *
 * This class registers the page with the admin menu, contains the private scripts to add contacts,
 * delete contacts, and manage contacts in the admin area
 *
 * There are several hooks you can use to add your own functionality to manage a contact in the default admin view.
 * The most relevant will likely be the following...
 *
 * add_action( 'wpgh_admin_update_contact_after', 'my_save_function' ); ($id)
 *
 * When saving custom information or doing something else. Runs after the admin saves a contact via the admin screen.
 *
 * @since       File available since Release 0.1
 * @subpackage  Admin/Contacts
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Admin
 */
class Contacts_Page extends Admin_Page {

	protected function add_additional_actions() {
		if ( $this->get_current_action() === 'view' ) {
			new Contact_Table_Columns();
		}

		if ( $this->get_current_action() === 'edit' ) {
			new Info_Cards();
		}
	}

	protected function add_ajax_actions() {

		new Contact_Table_Columns();
		new Info_Cards();

		add_action( 'wp_ajax_wpgh_inline_save_contacts', array( $this, 'save_inline' ) );
		add_action( 'wp_ajax_groundhogg_edit_notes', [ $this, 'edit_note_ajax' ] );
		add_action( 'wp_ajax_groundhogg_delete_notes', [ $this, 'delete_note_ajax' ] );
		add_action( 'wp_ajax_groundhogg_add_notes', [ $this, 'add_note_ajax' ] );
	}

	/**
	 * Get the page slug
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'gh_contacts';
	}

	/**
	 * Get the menu name
	 *
	 * @return string
	 */
	public function get_name() {
		return _x( 'Contacts', 'page_title', 'groundhogg' );
	}

	/**
	 * The required minimum capability required to load the page
	 *
	 * @return string
	 */
	public function get_cap() {
		return 'view_contacts';

	}

	public function get_priority() {
		return 5;
	}

	/**
	 * Get the item type for this page
	 *
	 * @return mixed
	 */
	public function get_item_type() {
		return 'contact';
	}

	/**
	 * Get the scripts in there
	 */
	public function scripts() {

		wp_enqueue_style( 'groundhogg-admin' );

		if ( $this->get_current_action() === 'edit' || $this->get_current_action() === 'add' || $this->get_current_action() === 'form' ) {
			wp_enqueue_style( 'groundhogg-admin-contact-editor' );
			wp_enqueue_style( 'groundhogg-admin-contact-info-cards' );
			wp_enqueue_style( 'buttons' );
			wp_enqueue_style( 'media-views' );
//			wp_enqueue_style( 'edit' );
			wp_enqueue_script( 'postbox' );
			wp_enqueue_script( 'groundhogg-admin-contact-editor' );
			wp_enqueue_script( 'groundhogg-admin-contact-info-cards' );
			wp_localize_script( 'groundhogg-admin-contact-editor', 'ContactEditor', [
				'contact_id'       => absint( get_url_var( 'contact' ) ),
				'delete_note_text' => __( 'Are you sure you want to delete this note?', 'groundhogg' ),
			] );
		} else {
			wp_enqueue_style( 'select2' );
			wp_enqueue_script( 'select2' );
			wp_enqueue_style( 'groundhogg-admin-contact-inline' );
			wp_enqueue_script( 'groundhogg-admin-contact-inline' );

			// Advanced Search
			Scripts::enqueue_advanced_search_filters_scripts();
		}
	}

	public function admin_title( $admin_title, $title ) {

		switch ( $this->get_current_action() ) {
			case 'add':
				$admin_title = sprintf( "%s &lsaquo; %s", __( 'Add' ), $admin_title );
				break;
			case 'edit':
				$contact_id = get_request_var( 'contact' );
				$contact    = get_contactdata( absint( $contact_id ) );

				if ( $contact ) {
					$prefix      = $contact->get_first_name() ? $contact->get_full_name() : $contact->get_email();
					$admin_title = sprintf( "%s &lsaquo; %s &lsaquo; %s", $prefix, __( 'Edit' ), $admin_title );
				}

				break;
		}

		return $admin_title;
	}

	/* help bar */

	public function help() {
		$screen = get_current_screen();

		$screen->add_help_tab(
			array(
				'id'      => 'gh_overview',
				'title'   => __( 'Overview' ),
				'content' => '<p>' . __( "This is where you can manage and view your contacts. Click the quick edit to quickly change contact details.", 'groundhogg' ) . '</p>'
			)
		);

		$screen->add_help_tab(
			array(
				'id'      => 'gh_edit',
				'title'   => __( 'Editing' ),
				'content' => '<p>' . __( "While editing a contact you can modify any of their personal information. There are several points of interest...", 'groundhogg' ) . '</p>'
				             . '<ul> '
				             . '<li>' . __( 'Manually unsubscribe a contact by checking the "mark as unsubscribed" button.', 'groundhogg' ) . '</li>'
				             . '<li>' . __( 'Make sure your in compliance by ensuring the terms of agreement and GDPR consent are both checked under the compliance section.', 'groundhogg' ) . '</li>'
				             . '<li>' . __( 'View the origin of the contact by looking at the lead source field.', 'groundhogg' ) . '</li>'
				             . '<li>' . __( 'Add or remove custom information about the contact by enabling the "Edit Meta" section. Each meta also includes a replacement code to include it in an email.', 'groundhogg' ) . '</li>'
				             . '<li>' . __( 'Re-run or cancel events for this contact by viewing the "Upcoming Events" or "Recent History" Section', 'groundhogg' ) . '</li>'
				             . '<li>' . __( 'Monitor their engagement by looking in the "Recent Email History" section.', 'groundhogg' ) . '</li>'
				             . '</ul>'
			)
		);
	}

	public function get_pointers_view() {
		return [
			[
				'id'        => 'export_contacts',
				'screen'    => $this->get_screen_id(),
				'target'    => '.export-contacts',
				'title'     => 'Export Your Contacts',
				'show_next' => true,
				'content'   => 'You can export your whole list, or part of you list by clicking this button. IT will always export the current query.',
				'position'  => [
					'edge'  => 'left', //top, bottom, left, right
					'align' => 'middle' //top, bottom, left, right, middle
				]
			],
			[
				'id'        => 'filter_contacts',
				'screen'    => $this->get_screen_id(),
				'target'    => '.subsubsub',
				'title'     => 'Filter Contacts',
				'show_next' => true,
				'content'   => 'You can quickly see which of you contacts can be marketed to and which cannot by filtering them here.',
				'position'  => [
					'edge'  => 'top', //top, bottom, left, right
					'align' => 'middle' //top, bottom, left, right, middle
				]
			],
			[
				'id'        => 'search_contacts',
				'screen'    => $this->get_screen_id(),
				'target'    => '#search_contacts',
				'title'     => 'Search Contacts',
				'show_next' => true,
				'content'   => 'Search for contacts that meet more detailed criteria.',
				'position'  => [
					'edge'  => 'top', //top, bottom, left, right
					'align' => 'left' //top, bottom, left, right, middle
				]
			],
			[
				'id'        => 'import_contacts',
				'screen'    => $this->get_screen_id(),
				'target'    => '#import_contacts',
				'title'     => 'Import Contacts',
				'show_next' => false,
				'content'   => 'Have a list already? Import them now!',
				'position'  => [
					'edge'  => 'top', //top, bottom, left, right
					'align' => 'left' //top, bottom, left, right, middle
				]
			]
		];
	}

	public function get_pointers_add() {
		return [
			[
				'id'        => 'add_contacts',
				'screen'    => $this->get_screen_id(),
				'target'    => '.nav-tab-wrapper a:last-child',
				'title'     => 'Add New Contacts',
				'show_next' => true,
				'content'   => 'You can use our standard form to add new contacts or you can use any of your web forms.',
				'position'  => [
					'edge'  => 'left', //top, bottom, left, right
					'align' => 'middle' //top, bottom, left, right, middle
				]
			],
			[
				'id'        => 'select_form',
				'screen'    => $this->get_screen_id(),
				'target'    => '#form-submit-link',
				'title'     => 'Choose A Form',
				'show_next' => false,
				'content'   => 'You can add a contact based on any form in any funnel. Select your form from the dropdown and click "Change Form"',
				'position'  => [
					'edge'  => 'top', //top, bottom, left, right
					'align' => 'left' //top, bottom, left, right, middle
				]
			],
		];
	}

	public function get_pointers_edit() {
		return [
			[
				'id'        => 'contact_tab_general',
				'screen'    => $this->get_screen_id(),
				'target'    => '#tab_general',
				'title'     => 'General Information',
				'show_next' => true,
				'content'   => "This is where you'll find basic information about the contact, including and contact information.",
				'position'  => [
					'edge'  => 'left', //top, bottom, left, right
					'align' => 'middle' //top, bottom, left, right, middle
				]
			],
			[
				'id'        => 'contact_tab_meta_data',
				'screen'    => $this->get_screen_id(),
				'target'    => '#tab_meta_data',
				'title'     => 'Custom Information',
				'show_next' => true,
				'content'   => "This is where you'll find any custom information about a contact.",
				'position'  => [
					'edge'  => 'top', //top, bottom, left, right
					'align' => 'left' //top, bottom, left, right, middle
				]
			],
			[
				'id'        => 'contact_tab_segmentation',
				'screen'    => $this->get_screen_id(),
				'target'    => '#tab_segmentation',
				'title'     => 'Segmentation',
				'show_next' => true,
				'content'   => "This is where you can add or remove tags. Also where you can see where the contact came from.",
				'position'  => [
					'edge'  => 'top', //top, bottom, left, right
					'align' => 'left' //top, bottom, left, right, middle
				]
			],
			[
				'id'        => 'contact_tab_notes',
				'screen'    => $this->get_screen_id(),
				'target'    => '#tab_notes',
				'title'     => 'Notes',
				'show_next' => true,
				'content'   => "Keep things you'd like to remember about contacts in their notes section.",
				'position'  => [
					'edge'  => 'left', //top, bottom, left, right
					'align' => 'middle' //top, bottom, left, right, middle
				]
			],
			[
				'id'        => 'contact_tab_files',
				'screen'    => $this->get_screen_id(),
				'target'    => '#tab_files',
				'title'     => 'Files',
				'show_next' => true,
				'content'   => "Upload any files you'd like to keep associated with the contact.",
				'position'  => [
					'edge'  => 'left', //top, bottom, left, right
					'align' => 'middle' //top, bottom, left, right, middle
				]
			],
			[
				'id'        => 'contact_tab_actions',
				'screen'    => $this->get_screen_id(),
				'target'    => '#tab_actions',
				'title'     => 'Actions',
				'show_next' => true,
				'content'   => "Send emails, texts, or submit internal forms from this tab.",
				'position'  => [
					'edge'  => 'left', //top, bottom, left, right
					'align' => 'middle' //top, bottom, left, right, middle
				]
			],
			[
				'id'        => 'contact_tab_activity',
				'screen'    => $this->get_screen_id(),
				'target'    => '#tab_activity',
				'title'     => 'Activity',
				'show_next' => false,
				'content'   => "View any activity the contact was involved with.",
				'position'  => [
					'edge'  => 'left', //top, bottom, left, right
					'align' => 'middle' //top, bottom, left, right, middle
				]
			],
		];
	}

	/**
	 * Get the screen title
	 */
	public function get_title() {
		switch ( $this->get_current_action() ) {
			case 'add':
				return _ex( 'Add Contact', 'page_title', 'groundhogg' );
				break;
			case 'edit':
				$contacts = $this->get_items();
				$contact  = get_contactdata( array_shift( $contacts ) ); //todo check
				if ( $contact ) {
					return sprintf( _x( 'Edit Contact: %s', 'page_title', 'groundhogg' ), $contact->get_full_name() );
				} else {
					return _ex( 'Oops!', 'page_title', 'groundhogg' );
				}

				break;
			case 'form':

				if ( key_exists( 'contact', $_GET ) ) {
					$contacts = $this->get_items();
					$contact  = get_contactdata( array_shift( $contacts ) ); // todo check

					return sprintf( _x( 'Submit Form For %s', 'page_title', 'groundhogg' ), $contact->get_full_name() );
				} else {
					return _ex( 'Submit Form', 'page_title', 'groundhogg' );
				}

				break;
			case 'search':
				return _ex( 'Search Contacts', 'page_title', 'groundhogg' );
				break;
			case 'view':
			default:
				return _ex( 'Contacts', 'page_title', 'groundhogg' );
				break;
		}
	}

	protected function get_title_actions() {
//
//		$search_modal_link = modal_link_url( [
//			'title'              => __( 'Search Contacts', 'groundhogg' ),
//			'footer_button_text' => __( 'Search' ),
//			'source'             => 'search-filters',
//			'height'             => 500,
//			'width'              => 900,
//			'footer'             => 'false',
//			'preventSave'        => 'true',
//		] );

		return [
			[
				'link'   => $this->admin_url( [ 'action' => 'add' ] ),
				'action' => __( 'Add New', 'groundhogg' ),
				'target' => '_self',
				'id'     => '',
			],
			[
				'link'   => admin_page_url( 'gh_tools', [ 'tab' => 'import', 'action' => 'add' ] ),
				'action' => __( 'Import', 'groundhogg' ),
				'target' => '_self',
				'id'     => 'import_contacts'
			],
			[
				'link'   => $this->get_current_action() === 'view' ? '#' : admin_page_url( 'gh_contacts', [ 'is_searching' => true ] ),
				'action' => __( 'Advanced Search', 'groundhogg' ),
				'target' => '_self',
				'id'     => 'search_contacts',
			],
		];
	}

	/**
	 * Create a contact via the admin area
	 */
	public function process_add() {
		if ( ! current_user_can( 'add_contacts' ) ) {
			$this->wp_die_no_access();
		}

		do_action( 'groundhogg/admin/contacts/add/before' );

		$_POST = wp_unslash( $_POST );

		if ( ! get_request_var( 'email' ) ) {
			return new \WP_Error( 'no_email', __( "Please enter a valid email address.", 'groundhogg' ) );
		}

		$args['first_name'] = sanitize_text_field( get_request_var( 'first_name' ) );
		$args['last_name']  = sanitize_text_field( get_request_var( 'last_name' ) );
		$args['owner_id']   = absint( get_request_var( 'owner_id', get_current_user_id() ) );

		$email = sanitize_email( get_request_var( 'email' ) );

		if ( ! Plugin::$instance->dbs->get_db( 'contacts' )->exists( $email ) ) {
			$args['email'] = $email;
		} else {
			return new \WP_Error( 'email_exists', sprintf( _x( 'Sorry, the email %s already belongs to another contact.', 'page_title', 'groundhogg' ), $email ) );
		}

		if ( ! is_email( $email ) ) {
			return new \WP_Error( 'invalid_email', __( "Please enter a valid email address.", 'groundhogg' ) );
		}

		$contact = new Contact( $args );

		if ( ! $contact->exists() ) {
			return new \WP_Error( 'db_error', __( 'Could not add contact.', 'groundhogg' ) );
		}

		$contact->update_meta( 'primary_phone', sanitize_text_field( get_request_var( 'primary_phone' ) ) );
		$contact->update_meta( 'primary_phone_extension', sanitize_text_field( get_request_var( 'primary_phone_extension' ) ) );
		$contact->add_note( get_request_var( 'notes' ) );


		if ( get_request_var( 'tags' ) ) {
			$contact->add_tag( get_request_var( 'tags' ) );
		}

		/**
		 * After the contact is created via the admin!
		 */
		do_action( 'groundhogg/admin/contacts/add/after', $contact );

		$this->add_notice( 'created', _x( "Contact created!", 'notice', 'groundhogg' ), 'success' );

		return $this->admin_url( [ 'action' => 'edit', 'contact' => $contact->get_id() ] );
	}

	public function get_meta_key_exclusions() {
		return apply_filters( 'groundhogg/admin/contacts/exclude_meta_list', [
			'birthday',
			'birthday_month',
			'birthday_day',
			'birthday_year',
			'lead_source',
			'source_page',
			'page_source',
			'terms_agreement',
			'terms_agreement_date',
			'gdpr_consent',
			'gdpr_consent_date',
			'marketing_consent',
			'marketing_consent_date',
			'mobile_phone',
			'primary_phone',
			'primary_phone_extension',
			'company_phone',
			'company_phone_extension',
			'street_address_1',
			'street_address_2',
			'time_zone',
			'city',
			'postal_zip',
			'region',
			'country',
			'notes',
			'files',
			'company_name',
			'company_address',
			'job_title',
			'ip_address',
			'last_optin',
			'last_sent',
			'country_name',
			'region_code',
		] );
	}

	/**
	 * Process a file upload
	 *
	 * @return array|bool|\WP_Error
	 */
	public function process_upload_file() {

		$id = absint( get_request_var( 'contact' ) );

		if ( ! $id ) {
			return new \WP_Error( 'no_contact_id', __( 'Contact id not found.', 'groundhogg' ) );
		}

		$contact = get_contactdata( $id );
		$count   = 0;

		if ( ! empty( $_FILES['files'] ) ) {
			$files = normalize_files( $_FILES['files'] );
			foreach ( $files as $file_key => $file ) {
				if ( ! get_array_var( $file, 'error' ) ) {
					$e = $contact->upload_file( $file );
					if ( is_wp_error( $e ) ) {
						return $e;
					}
					$count ++;
				}
			}
		}

		$this->add_notice( 'uploaded', sprintf( _n( "Uploaded file", "Uploaded %s files", $count, 'groundhogg' ), $count ) );

		return admin_page_url( 'gh_contacts', [ 'action' => 'edit', 'contact' => $contact->get_id() ] );
	}

	/**
	 * Update the contact via the admin screen
	 */
	public function process_edit() {
		if ( ! current_user_can( 'edit_contacts' ) ) {
			$this->wp_die_no_access();
		}

		$id = absint( get_request_var( 'contact' ) );

		if ( ! $id ) {
			return new \WP_Error( 'no_contact_id', __( 'Contact id not found.', 'groundhogg' ) );
		}

		$contact = get_contactdata( $id );

		//this meta data will not be deleted.

		$posted_meta = get_request_var( 'meta' );

		// Handle Meta ADD/DELETE
		if ( $posted_meta ) {

			$posted_meta_keys = array_keys( $posted_meta );
			$stored_meta_keys = array_keys( $contact->get_meta() );
			$exclude_keys     = $this->get_meta_key_exclusions();

			$editable_meta  = array_diff( $stored_meta_keys, $exclude_keys );
			$deletable_meta = array_diff( $editable_meta, $posted_meta_keys );

			foreach ( $editable_meta as $key ) {

				$value = sanitize_text_field( get_array_var( $posted_meta, $key ) );

				// Ignore serialized data
				if ( $value !== 'SERIALIZED DATA' ) {
					$contact->update_meta( $key, $value );
				}

			}

			foreach ( $deletable_meta as $key ) {
				$contact->delete_meta( $key );
			}
		}

		$new_meta_keys = get_request_var( 'newmetakey', [] );
		$new_meta_vals = get_request_var( 'newmetavalue', [] );

		foreach ( $new_meta_keys as $i => $new_meta_key ) {
			if ( strpos( $new_meta_vals[ $i ], PHP_EOL ) !== false ) {
				$contact->update_meta( sanitize_key( $new_meta_key ), sanitize_textarea_field( $new_meta_vals[ $i ] ) );
			} else {
				$contact->update_meta( sanitize_key( $new_meta_key ), sanitize_text_field( $new_meta_vals[ $i ] ) );
			}
		}

		$args = [];

		$email = sanitize_email( get_request_var( 'email' ) );

		//check if it's the current email address.
		if ( $contact->get_email() !== $email ) {
			if ( ! Plugin::$instance->dbs->get_db( 'contacts' )->exists( $email ) ) {
				$args['email'] = $email;
			} else {
				$this->add_notice( new \WP_Error( 'email_exists', sprintf( _x( 'Sorry, the email %s already belongs to another contact.', 'notice', 'groundhogg' ), $email ) ) );
			}
		}

		$args['first_name'] = sanitize_text_field( get_request_var( 'first_name' ) );
		$args['last_name']  = sanitize_text_field( get_request_var( 'last_name' ) );
		$args['owner_id']   = absint( get_request_var( 'owner_id' ) );
		$args['user_id']    = absint( get_request_var( 'user', $contact->get_user_id() ) );

		$contact->update( $args );

		$basic_text_fields = [
			'mobile_phone',
			'primary_phone',
			'primary_phone_extension',
			'company_phone',
			'company_phone_extension',
			'company_name',
			'job_title',
			'company_address',
			'street_address_1',
			'street_address_2',
			'city',
			'postal_zip',
			'region',
			'country',
			'lead_source',
			'source_page',
			'ip_address',
			'time_zone',
		];

		$basic_text_fields = apply_filters( 'groundhogg/contact/update/basic_fields', $basic_text_fields, $contact );

		foreach ( $basic_text_fields as $field ) {
			if ( get_request_var( $field, false, true ) ) {
				$contact->update_meta( $field, sanitize_text_field( get_request_var( $field, false, true ) ) );
			} else {
				$contact->delete_meta( $field );
			}
		}

		if ( get_request_var( 'extrapolate_location' ) ) {
			if ( $contact->extrapolate_location( true ) ) {
				$this->add_notice( 'location_updated', _x( 'Location updated.', 'notice', 'groundhogg' ), 'info' );
			}
		}

		$birthday_parts = map_deep( get_request_var( 'birthday', [] ), 'absint' );

		// Ignore 0 values.
		if ( ! empty( $birthday_parts ) && array_sum( $birthday_parts ) > 0 ) {
			// Birthday
			$parts = [
				'year',
				'month',
				'day',
			];

			$birthday = [];

			foreach ( $parts as $key ) {
				$date       = get_array_var( $birthday_parts, $key );
				$birthday[] = $date;
			}

			// If is valid date
			if ( checkdate( $birthday[1], $birthday[2], $birthday[0] ) ) {
				$birthday = implode( '-', $birthday );
				$contact->update_meta( 'birthday', $birthday );
			} else {
				$this->add_notice( new \WP_Error( 'invalid_date', __( 'The birthday date provided is not a valid date.' ) ) );
			}
		}

		// Process any tag adds/removals.
		if ( get_post_var( 'tags', [] ) ) {

			$tags = validate_tags( get_request_var( 'tags', [] ) );

			$cur_tags = $contact->get_tags();
			$new_tags = $tags;

			$delete_tags = array_diff( $cur_tags, $new_tags );
			if ( ! empty( $delete_tags ) ) {
				$contact->remove_tag( $delete_tags );
			}

			$add_tags = array_diff( $new_tags, $cur_tags );
			if ( ! empty( $add_tags ) ) {
				$result = $contact->add_tag( $add_tags );
				if ( ! $result ) {
					return new \WP_Error( 'bad-tag', __( 'Hmm, looks like we could not add the new tags...', 'groundhogg' ) );
				}
			}
		} else {
			// If the tags array is empty all tags were deleted.
			$contact->remove_tag( $contact->get_tags() );
		}

		// Check if needing to unsubscribe.
		if ( get_request_var( 'unsubscribe' ) ) {
			$contact->unsubscribe();
			$this->add_notice(
				esc_attr( 'unsubscribed' ),
				_x( 'This contact will no longer receive marketing.', 'notice', 'groundhogg' ),
				'info'
			);
		}

		// Check if we are manually confirming the email naually
		if ( get_request_var( 'manual_confirm' ) ) {
			if ( get_request_var( 'confirmation_reason' ) ) {
				$contact->change_marketing_preference( Preferences::CONFIRMED );
				$contact->update_meta( 'manual_confirmation_reason', sanitize_textarea_field( get_request_var( 'confirmation_reason' ) ) );
				$this->add_notice(
					esc_attr( 'confirmed' ),
					_x( 'This contact\'s email address has been confirmed.', 'notice', 'groundhogg' ),
					'info'
				);
			} else {
				return new \WP_Error( 'manual_confirmation_error', __( 'A reason is required to change the email confirmation status.', 'groundhogg' ) );

			}
		}

		if ( isset( $_POST['send_email'] ) && isset( $_POST['email_id'] ) && current_user_can( 'send_emails' ) ) {
			$mail_id = intval( $_POST['email_id'] );
			if ( send_email_notification( $mail_id, $contact->get_id() ) ) {
				$this->add_notice( 'email_queued', _x( 'The email has been added to the queue and will send shortly.', 'notice', 'groundhogg' ) );
			}
		}

		if ( isset( $_POST['start_funnel'] ) && isset( $_POST['add_contacts_to_funnel_step_picker'] ) && current_user_can( 'edit_contacts' ) ) {
			$step = new Step( intval( $_POST['add_contacts_to_funnel_step_picker'] ) );
			if ( $step->exists() && $step->enqueue( $contact ) ) {
				$this->add_notice( 'started', _x( "Contact added to funnel.", 'notice', 'groundhogg' ), 'info' );
			}
		}

		if ( ! empty( $_FILES['files'] ) ) {
			$files = normalize_files( $_FILES['files'] );
			foreach ( $files as $file_key => $file ) {
				if ( ! get_array_var( $file, 'error' ) ) {
					$e = $contact->upload_file( $file );
					if ( is_wp_error( $e ) ) {
						return $e;
					}
				}
			}
		}

		do_action( 'groundhogg/admin/contact/save', $contact->get_id(), $contact );

		if ( get_request_var( 'switch_form' ) ) {
			wp_safe_redirect( $this->admin_url( [
				'action'  => 'form',
				'contact' => $contact->get_id(),
				'form'    => get_request_var( 'manual_form_submission' ),
			] ) );
			die();
		}

		$this->add_notice( 'update', _x( "Contact updated!", 'notice', 'groundhogg' ), 'success' );

		return true;
	}

	/**
	 * Delete a bunch of contacts
	 *
	 * @return false|\WP_Error
	 */
	public function process_delete() {
		if ( ! current_user_can( 'delete_contacts' ) ) {
			$this->wp_die_no_access();
		}

		foreach ( $this->get_items() as $id ) {
			if ( ! Plugin::$instance->dbs->get_db( 'contacts' )->delete( $id ) ) {
				return new \WP_Error( 'unable_to_delete_contact', "Something went wrong while deleting the contact." );
			}
		}

		$this->add_notice(
			esc_attr( 'deleted' ),
			sprintf( _nx( 'Deleted %d contact.', 'Deleted %d contacts.', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $this->get_items() ) ),
			'success'
		);

		return false;
	}

	/**
	 * Update the status of a contact
	 *
	 * @return false|string
	 */
	public function process_status_change() {
		if ( ! current_user_can( 'edit_contacts' ) ) {
			$this->wp_die_no_access();
		}

		$status = absint( get_request_var( 'status' ) );

		foreach ( $this->get_items() as $id ) {
			$contact = get_contactdata( $id );

			// Don't re-subscribe contacts already confirmed
			if ( $contact->get_optin_status() === Preferences::CONFIRMED && $status === Preferences::UNCONFIRMED ) {
				continue;
			}

			$contact->change_marketing_preference( $status );

			if ( $status === Preferences::SPAM ) {
				$ip_address = $contact->get_meta( 'ip_address' );

				if ( $ip_address ) {
					$blacklist = get_option( 'blacklist_keys' );
					$blacklist .= "\n" . $ip_address;
					$blacklist = sanitize_textarea_field( $blacklist );
					update_option( 'blacklist_keys', $blacklist );
				}
			}


			do_action( "groundhogg/admin/contacts/{$status}", $contact );
		}

		$this->add_notice(
			esc_attr( 'status-updated' ),
			sprintf( _nx( 'Marked contact as %2$s.', 'Marked %1$d contacts as %2$s.', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $this->get_items() ), Preferences::get_preference_pretty_name( $status ) ),
			'success'
		);

		if ( count( $this->get_items() ) === 1 ) {
			return $this->admin_url( [ 'action' => 'edit', 'contact' => $id ] );
		}

		return false;
	}

	/**
	 * Process spam bulk action
	 *
	 * @return false|string
	 */
	public function process_spam() {
		set_request_var( 'status', Preferences::SPAM );

		return $this->process_status_change();
	}

	/**
	 * Process re-subscribe bulk action
	 *
	 * @return false|string
	 */
	public function process_resubscribe() {
		set_request_var( 'status', Preferences::UNCONFIRMED );

		return $this->process_status_change();
	}

	public function process_apply_tag() {
		if ( ! current_user_can( 'edit_contacts' ) ) {
			$this->wp_die_no_access();
		}

		if ( get_request_var( 'bulk_tags', false ) ) {

			$tags = validate_tags( get_request_var( 'bulk_tags' ) );

			foreach ( $this->get_items() as $id ) {
				$contact = get_contactdata( $id );
				$contact->apply_tag( $tags );
			}

			$this->add_notice(
				esc_attr( 'applied_tags' ),
				sprintf( _nx( 'Applied %d tags to %d contact', 'Applied %d tags to %d contacts', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $tags ), count( $this->get_items() ) ),
				'success'
			);
		}

		return false;
	}

	/**
	 * Remove tags from the contact.
	 *
	 * @return bool
	 */
	public function process_remove_tag() {
		if ( ! current_user_can( 'edit_contacts' ) ) {
			$this->wp_die_no_access();
		}

		if ( get_request_var( 'bulk_tags', false ) ) {

			$tags = wp_parse_id_list( get_request_var( 'bulk_tags' ) );

			foreach ( $this->get_items() as $id ) {
				$contact = get_contactdata( $id );
				$contact->remove_tag( $tags );
			}

			$this->add_notice(
				esc_attr( 'removed_tags' ),
				sprintf( _nx( 'Removed %d tags from %d contact', 'Removed %d tags from %d contacts', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $tags ), count( $this->get_items() ) ),
				'success'
			);

		}

		return false;
	}

	/**
	 * Edit a note...
	 */
	public function edit_note_ajax() {

		if ( ! wp_doing_ajax() ) {
			return;
		} else if ( ! current_user_can( 'edit_contacts' ) ) {
			wp_send_json_error();
		}

		$note_id = absint( get_request_var( 'note_id' ) );
		$content = sanitize_textarea_field( get_request_var( 'note' ) );
		$note    = new Note( $note_id );
		$note->update( [
			'timestamp' => time(),
			'content'   => $content,
			'context'   => 'user',
			'user_id'   => get_current_user_id(),
		] );

		ob_start();
		include __DIR__ . '/note.php';
		$html = ob_get_clean();

		wp_send_json_success( [
			'note' => $html,
		] );
	}

	/**
	 * Add a new note
	 */
	public function add_note_ajax() {

		if ( ! wp_doing_ajax() ) {
			return;
		} else if ( ! current_user_can( 'edit_contacts' ) ) {
			wp_send_json_error();
		}

		$note       = sanitize_textarea_field( get_post_var( 'note' ) );
		$contact_id = absint( get_post_var( 'contact' ) );

		$id = get_db( 'contactnotes' )->add( [
			'contact_id' => $contact_id,
			'context'    => 'user',
			'user_id'    => get_current_user_id(),
			'content'    => $note,
		] );

		$note = new Note( $id );

		ob_start();
		include __DIR__ . '/note.php';
		$html = ob_get_clean();

		wp_send_json_success( [
			'note' => $html,
		] );
	}


	public function delete_note_ajax() {

		if ( ! wp_doing_ajax() ) {
			return;
		}
		if ( ! current_user_can( 'edit_contacts' ) ) {
			wp_send_json_error();
		}

		$note_id = absint( get_request_var( 'note_id' ) );

		get_db( 'contactnotes' )->delete( $note_id );

		wp_send_json_success( [
			'msg' => __( 'Note deleted successfully.', 'groundhogg' )
		] );
	}


	/**
	 * Save the contact during inline edit
	 */
	public function save_inline() {

		if ( ! wp_doing_ajax() ) {
			return;
		}

		if ( ! current_user_can( 'edit_contacts' ) ) {
			$this->wp_die_no_access();
		}

		$id = absint( get_request_var( 'ID' ) );

		$contact = get_contactdata( $id );

		do_action( 'groundhogg/admin/contact/save_inline/before', $id, $contact );

		$email = sanitize_email( get_request_var( 'email' ) );

		$args['first_name']   = sanitize_text_field( get_request_var( 'first_name' ) );
		$args['last_name']    = sanitize_text_field( get_request_var( 'last_name' ) );
		$args['owner_id']     = absint( get_request_var( 'owner' ) );

		$meta_keys = [
			'mobile_phone',
			'primary_phone',
			'primary_phone_extension',
		];

		foreach ( $meta_keys as $meta_key ) {
			$contact->update_meta( $meta_key, sanitize_text_field( get_request_var( $meta_key ) ) );
		}

		$err = array();

		if ( ! $email ) {
			$err[] = _x( 'Email cannot be blank.', 'notice', 'groundhogg' );
		} else if ( ! is_email( $email ) ) {
			$err[] = _x( 'Invalid email address.', 'notice', 'groundhogg' );
		}

		if ( $contact->get_email() === $email || ! Plugin::$instance->dbs->get_db( 'contacts' )->exists( $email ) ) {
			$args['email'] = $email;
		} else {
			$err[] = sprintf( _x( 'Sorry, the email %s already belongs to another contact.', 'notice', 'groundhogg' ), $email );
		}

		if ( $err ) {
			echo implode( ', ', $err );
			exit;
		}

		$contact->update( $args );

		$contact->change_marketing_preference( get_request_var( 'optin_status' ) );

		// Process any tag removals.
		if ( get_request_var( 'tags' ) ) {

			$tags = Plugin::$instance->dbs->get_db( 'tags' )->validate( get_request_var( 'tags' ) );

			$cur_tags = $contact->get_tags();
			$new_tags = $tags;

			$delete_tags = array_diff( $cur_tags, $new_tags );
			if ( ! empty( $delete_tags ) ) {
				$contact->remove_tag( $delete_tags );
			}

			$add_tags = array_diff( $new_tags, $cur_tags );
			if ( ! empty( $add_tags ) ) {
				$result = $contact->add_tag( $add_tags );
				if ( ! $result ) {
					return new \WP_Error( 'bad-tag', __( 'Hmm, looks like we could not add the new tags...', 'groundhogg' ) );
				}
			}
		} else {
			$contact->remove_tag( $contact->get_tags() );
		}

		do_action( 'groundhogg/admin/contact/save_inline/after', $id, $contact );

		$contactTable = new Tables\Contacts_Table;

		$contactTable->single_row( $contact );
		wp_die();
	}

	/**
	 * Remove a file from the contact file box
	 */
	public function process_remove_file() {
		if ( ! current_user_can( 'edit_contacts' ) ) {
			$this->wp_die_no_access();
		}

		$file_name = sanitize_text_field( get_url_var( 'file' ) );

		$contact = get_contactdata( absint( get_url_var( 'contact' ) ) );

		if ( ! $contact ) {
			return new \WP_Error( 'error', 'The given contact does not exist.' );
		}

		$folders = $contact->get_uploads_folder();
		$path    = $folders['path'];

		$file_path = wp_normalize_path( $path . DIRECTORY_SEPARATOR . $file_name );

		if ( ! file_exists( $file_path ) ) {
			return new \WP_Error( 'error', 'The requested file does not exist.' );
		}

		unlink( $file_path );

		$this->add_notice( 'success', __( 'File deleted.', 'groundhogg' ) );

		// Return to contact edit screen.
		return admin_page_url( 'gh_contacts', [ 'action' => 'edit', 'contact' => $contact->get_id() ] );

	}

	/**
	 * Save the search
	 */
	public function process_save_this_search() {
		if ( ! current_user_can( 'view_contacts' ) ) {
			$this->wp_die_no_access();
		}

		if ( get_url_var( 'is_searching' ) !== 'on' ) {
			return new \WP_Error( 'error', __( 'Invalid search' ) );
		}

		$name     = sanitize_text_field( get_post_var( 'saved_search_name' ) );
		$query_id = uniqid( sanitize_title( $name ) . '-' );
		$query    = get_request_query();

		Saved_Searches::instance()->add( $query_id, [
			'name'  => $name,
			'id'    => $query_id,
			'query' => $query,
		] );

		$this->add_notice( 'saved', __( 'Search saved!', 'groundhogg' ) );

		// stay on page...

		return admin_page_url( 'gh_contacts', $query );
	}

	/**
	 * Load the search!
	 *
	 * @return string|\WP_Error
	 */
	public function process_load_search() {
		if ( ! current_user_can( 'view_contacts' ) ) {
			$this->wp_die_no_access();
		}

		$search_id = sanitize_text_field( get_post_var( 'saved_search' ) );

		$search = Saved_Searches::instance()->get( $search_id );

		if ( ! $search ) {
			return new \WP_Error( 'error', __( 'Invalid search' ) );
		}

		$query                    = $search['query'];
		$query['saved_search_id'] = $search_id;

		return admin_page_url( 'gh_contacts', $query );
	}

	/**
	 * Delete the current search
	 *
	 * @return bool
	 */
	public function process_delete_search() {

		if ( ! current_user_can( 'view_contacts' ) ) {
			$this->wp_die_no_access();
		}

		$search_id = sanitize_text_field( get_post_var( 'saved_search' ) );

		Saved_Searches::instance()->delete( $search_id );

		$this->add_notice( 'deleted', __( 'Search deleted!', 'groundhogg' ) );

		return true;
	}

	/**
	 * Send a 1 off personal email
	 *
	 * @return string
	 */
	public function process_send_personal_email() {

		$contact = get_contactdata( get_url_var( 'contact' ) );

		if ( ! is_a_contact( $contact ) || ! current_user_can( 'send_emails' ) ) {
			$this->wp_die_no_access();
		}

		$subject   = do_replacements( sanitize_text_field( get_post_var( 'subject' ) ) );
		$from_user = get_userdata( absint( get_post_var( 'from_user', $contact->get_owner_id() ) ) );
		$from      = sprintf( '%s <%s>', $from_user->display_name, $from_user->user_email );
		$cc        = do_replacements( sanitize_email_header( get_post_var( 'cc' ), 'cc' ), $contact );
		$bcc       = do_replacements( sanitize_email_header( get_post_var( 'bcc' ), 'bcc' ), $contact );
		$content   = do_replacements( wpautop( kses_wrapper( get_post_var( 'email_content' ) ) ), $contact );

		$headers = [
			'From: ' . $from
		];

		if ( ! empty( $cc ) ) {
			$headers[] = 'Cc: ' . $cc;
		}

		if ( ! empty( $bcc ) ) {
			$headers[] = 'Bcc: ' . $bcc;
		}

//		wp_send_json([
//            $content,
//            $headers
//        ]);

		add_action( 'wp_mail_failed', [ $this, 'catch_personal_email_error' ] );

		\Groundhogg_Email_Services::send_wordpress( $contact->get_email(), $subject, $content, $headers );

		return $this->admin_url( [
			'action'  => 'edit',
			'contact' => $contact->get_id()
		] );
	}

	public function catch_personal_email_error( $error ) {
		$this->add_notice( $error );
	}

	/**
	 * Display the contact table
	 */
	public function view() {
		if ( ! current_user_can( 'view_contacts' ) ) {
			$this->wp_die_no_access();
		}

		$contacts_table = new Tables\Contacts_Table();

		include __DIR__ . '/advanced-search.php';

		$contacts_table->views();

		include __DIR__ . '/quick-search.php';

		?>
        <form method="post">
			<?php
			$contacts_table->prepare_items();
			$contacts_table->display();

			if ( $contacts_table->has_items() ) {
				$contacts_table->inline_edit();
			} ?>
        </form>
		<?php
	}

	/**
	 * Display the edit screen
	 */
	function edit() {

		if ( ! current_user_can( 'view_contacts' ) ) {
			$this->wp_die_no_access();
		}
		include __DIR__ . '/edit.php';
	}

	/**
	 * Display the add screen
	 */
	function add() {
		if ( ! current_user_can( 'add_contacts' ) ) {
			$this->wp_die_no_access();
		}
		include __DIR__ . '/add-contact.php';
	}

	public function form() {
		if ( ! current_user_can( 'edit_contacts' ) ) {
			$this->wp_die_no_access();
		}
		include __DIR__ . '/form-admin-submit.php';
	}
}
