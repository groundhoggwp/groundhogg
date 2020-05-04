<?php

namespace Groundhogg\Admin\Contacts;

use Groundhogg\Admin\Admin_Page;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_array_var;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use function Groundhogg\get_url_var;
use function Groundhogg\groundhogg_url;
use function Groundhogg\normalize_files;
use Groundhogg\Plugin;
use Groundhogg\Contact;
use Groundhogg\Preferences;
use function Groundhogg\send_email_notification;
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
 * @package     Admin
 * @subpackage  Admin/Contacts
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */
class Contacts_Page extends Admin_Page {

	protected function add_additional_actions() {
	}

	protected function add_ajax_actions() {
		add_action( 'wp_ajax_wpgh_inline_save_contacts', array( $this, 'save_inline' ) );
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
			wp_enqueue_script( 'groundhogg-admin-contact-editor' );
		} else {
			wp_enqueue_style( 'select2' );
			wp_enqueue_script( 'select2' );
			wp_enqueue_style( 'groundhogg-admin-contact-inline' );
			wp_enqueue_script( 'groundhogg-admin-contact-inline' );
		}
	}

	public function admin_title( $admin_title, $title ) {

		switch ( $this->get_current_action() ) {
			case 'add':
				$admin_title = sprintf( "%s &lsaquo; %s", __( 'Add' ), $admin_title );
				break;
			case 'edit':
				$contact_id = get_request_var( 'contact' );
				$contact    = Plugin::$instance->utils->get_contact( absint( $contact_id ) );

				if ( $contact ) {
					$admin_title = sprintf( "%s &lsaquo; %s &lsaquo; %s", $contact->get_full_name(), __( 'Edit' ), $admin_title );
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
				$contact  = Plugin::$instance->utils->get_contact( array_shift( $contacts ) ); //todo check
				if ( $contact ) {
					return sprintf( _x( 'Edit Contact: %s', 'page_title', 'groundhogg' ), $contact->get_full_name() );
				} else {
					return _ex( 'Oops!', 'page_title', 'groundhogg' );
				}

				break;
			case 'form':

				if ( key_exists( 'contact', $_GET ) ) {
					$contacts = $this->get_items();
					$contact  = Plugin::$instance->utils->get_contact( array_shift( $contacts ) ); // todo check

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
		return [
			[
				'link'   => $this->admin_url( [ 'action' => 'add' ] ),
				'action' => __( 'Add New', 'groundhogg' ),
				'target' => '_self',
				'id'     => '',
			],
			[
				'link'   => Plugin::$instance->admin->tools->admin_url( [ 'tab' => 'import', 'action' => 'add' ] ),
				'action' => __( 'Import', 'groundhogg' ),
				'target' => '_self',
				'id'     => 'import_contacts'
			],
			[
				'link'   => '#',
				'action' => __( 'Search', 'groundhogg' ),
				'target' => '_self',
				'id'     => 'search_contacts'
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
		$args['owner_id']   = absint( get_request_var( 'owner_id' ) );

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

		if ( get_request_var( 'notes' ) ) {
			$contact->add_note( get_request_var( 'notes' ) );
		}

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
			'primary_phone',
			'primary_phone_extension',
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
			'primary_phone',
			'primary_phone_extension',
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
			if ( $contact->extrapolate_location() ) {
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

		if ( get_request_var( 'add_new_note' ) ) {
			$contact->add_note( get_request_var( 'add_note' ) );
		}

		if ( isset( $_POST['send_email'] ) && isset( $_POST['email_id'] ) && current_user_can( 'send_emails' ) ) {
			$mail_id = intval( $_POST['email_id'] );
			if ( send_email_notification( $mail_id, $contact->get_id() ) ) {
				$this->add_notice( 'email_queued', _x( 'The email has been added to the queue and will send shortly.', 'notice', 'groundhogg' ) );
			}
		}

		if ( isset( $_POST['start_funnel'] ) && isset( $_POST['add_contacts_to_funnel_step_picker'] ) && current_user_can( 'edit_contacts' ) ) {
			$step = Plugin::$instance->utils->get_step( intval( $_POST['add_contacts_to_funnel_step_picker'] ) );
			if ( $step->enqueue( $contact ) ) {
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
			sprintf( _nx( 'Deleted %d contact', 'Deleted %d contacts', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $this->get_items() ) ),
			'success'
		);

		return false;
	}

	public function process_spam() {
		if ( ! current_user_can( 'edit_contacts' ) ) {
			$this->wp_die_no_access();
		}

		foreach ( $this->get_items() as $id ) {
			$contact = Plugin::$instance->utils->get_contact( $id );
			$contact->change_marketing_preference( Preferences::SPAM );

			$ip_address = $contact->get_meta( 'ip_address' );

			if ( $ip_address ) {
				$blacklist = get_option( 'blacklist_keys' );
				$blacklist .= "\n" . $ip_address;
				$blacklist = sanitize_textarea_field( $blacklist );
				update_option( 'blacklist_keys', $blacklist );
			}

			do_action( 'groundhogg/admin/contacts/spam', $contact );
		}

		$this->add_notice(
			esc_attr( 'spammed' ),
			sprintf( _nx( 'Marked %d contact as spam.', 'Marked %d contacts as spam.', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $this->get_items() ) ),
			'success'
		);

		return false;
	}

	public function process_unspam() {
		if ( ! current_user_can( 'edit_contacts' ) ) {
			$this->wp_die_no_access();
		}

		foreach ( $this->get_items() as $id ) {
			$contact = Plugin::$instance->utils->get_contact( $id );
			$contact->change_marketing_preference( Preferences::UNCONFIRMED );

			do_action( 'groundhogg/admin/contacts/unspam', $contact );
		}

		$this->add_notice(
			esc_attr( 'unspam' ),
			sprintf( _nx( 'Approved %d contact.', 'Approved %d contacts.', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $this->get_items() ) ),
			'success'
		);

		return false;
	}

	public function process_unbounce() {
		if ( ! current_user_can( 'edit_contacts' ) ) {
			$this->wp_die_no_access();
		}

		foreach ( $this->get_items() as $id ) {
			$contact = Plugin::$instance->utils->get_contact( $id );
			$contact->change_marketing_preference( Preferences::UNCONFIRMED );

			do_action( 'groundhogg/admin/contacts/unbounce', $contact );
		}

		$this->add_notice(
			esc_attr( 'unbounce' ),
			sprintf( _nx( 'Approved %d contact.', 'Approved %d contacts.', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $this->get_items() ) ),
			'success'
		);

		return false;
	}

	public function process_apply_tag() {
		if ( ! current_user_can( 'edit_contacts' ) ) {
			$this->wp_die_no_access();
		}

		if ( get_request_var( 'bulk_tags', false ) ) {

			$tags = validate_tags( get_request_var( 'bulk_tags' ) );

			foreach ( $this->get_items() as $id ) {
				$contact = Plugin::$instance->utils->get_contact( $id );
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
				$contact = Plugin::$instance->utils->get_contact( $id );
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

		$args['first_name'] = sanitize_text_field( get_request_var( 'first_name' ) );
		$args['last_name']  = sanitize_text_field( get_request_var( 'last_name' ) );
		$args['owner_id']   = absint( get_request_var( 'owner' ) );

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

		if ( get_request_var( 'unsubscribe' ) ) {
			$contact->unsubscribe();
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
			return new \WP_Error( 'error', 'The given contact does nto exist.' );
		}

		$folders = $contact->get_uploads_folder();
		$path    = $folders['path'];

		$file_path = wp_normalize_path( $path . DIRECTORY_SEPARATOR . $file_name );

		if ( ! file_exists( $file_path ) ) {
			return new \WP_Error( 'error', 'The requested file does nto exist.' );
		}

		unlink( $file_path );

		$this->add_notice( 'success', __( 'File deleted.', 'groundhogg' ) );

		// Return to contact edit screen.
		return admin_page_url( 'gh_contacts', [ 'action' => 'edit', 'contact' => $contact->get_id() ] );

	}

	/**
	 * Display the contact table
	 */
	public function view() {
		if ( ! current_user_can( 'view_contacts' ) ) {
			$this->wp_die_no_access();
		}

		$contacts_table = new Tables\Contacts_Table();

		include dirname( __FILE__ ) . '/search.php';

		$this->search_form( __( 'Search Contacts', 'groundhogg' ) );

		$contacts_table->views();
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
		include dirname( __FILE__ ) . '/contact-editor.php';
	}

	/**
	 * Display the add screen
	 */
	function add() {
		if ( ! current_user_can( 'add_contacts' ) ) {
			$this->wp_die_no_access();
		}
		include dirname( __FILE__ ) . '/add-contact.php';
	}

	function search() {
		if ( ! current_user_can( 'view_contacts' ) ) {
			$this->wp_die_no_access();
		}
		include dirname( __FILE__ ) . '/search.php';
	}

	public function form() {
		if ( ! current_user_can( 'edit_contacts' ) ) {
			$this->wp_die_no_access();
		}
		include dirname( __FILE__ ) . '/form-admin-submit.php';
	}
}