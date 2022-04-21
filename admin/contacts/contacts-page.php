<?php

namespace Groundhogg\Admin\Contacts;

use Groundhogg\Admin\Admin_Page;
use Groundhogg\Admin\Contacts\Tables\Contact_Table_Columns;
use Groundhogg\Classes\Note;
use Groundhogg\Properties;
use Groundhogg\Saved_Searches;
use Groundhogg\Scripts;
use Groundhogg\Step;
use function Groundhogg\admin_page_url;
use function Groundhogg\base64_json_decode;
use function Groundhogg\bulk_jobs;
use function Groundhogg\do_replacements;
use function Groundhogg\enqueue_filter_assets;
use function Groundhogg\enqueue_step_type_assets;
use function Groundhogg\generate_contact_with_map;
use function Groundhogg\get_array_var;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_filters_from_old_query_vars;
use function Groundhogg\get_mappable_fields;
use function Groundhogg\get_post_var;
use function Groundhogg\get_request_query;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use function Groundhogg\get_url_var;
use function Groundhogg\html;
use function Groundhogg\is_a_contact;
use function Groundhogg\isset_not_empty;
use function Groundhogg\kses_wrapper;
use function Groundhogg\modal_link_url;
use function Groundhogg\normalize_files;
use Groundhogg\Plugin;
use Groundhogg\Contact;
use Groundhogg\Preferences;
use function Groundhogg\sanitize_email_header;
use function Groundhogg\send_email_notification;
use function Groundhogg\set_request_var;
use function Groundhogg\utils;
use function Groundhogg\validate_tags;
use function Groundhogg\Ymd;
use function Groundhogg\Ymd_His;

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

		add_action( 'wp_ajax_groundhogg_contact_upload_file', [ $this, 'ajax_upload_file' ] );
		add_action( 'wp_ajax_groundhogg_edit_contact', [ $this, 'ajax_edit_contact' ] );
		add_action( 'wp_ajax_groundhogg_contact_table_row', [ $this, 'ajax_contact_table_row' ] );
		add_action( 'wp_ajax_groundhogg_get_contacts_table', [ $this, 'ajax_get_table' ] );
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

		$filter_query = [
			'filters'         => [],
			'exclude_filters' => []
		];

		if ( $filters = get_url_var( 'filters' ) ) {
			$filter_query['filters'] = is_string( $filters ) ? base64_json_decode( $filters ) : $filters;
		}

		if ( $exclude_filters = get_url_var( 'exclude_filters' ) ) {
			$filter_query['exclude_filters'] = is_string( $exclude_filters ) ? base64_json_decode( $exclude_filters ) : $exclude_filters;
		}

		if ( $saved_search = get_url_var( 'saved_search' ) ) {
			$saved_search = Saved_Searches::instance()->get( $saved_search );

			// If the search does not have filters we need to migrate it
			if ( ! isset_not_empty( $saved_search['query'], 'filters' ) ) {
				$saved_search['query'] = [
					'filters' => get_filters_from_old_query_vars( $saved_search['query'] )
				];
			}

			if ( isset( $saved_search['query']['filters'] ) ) {
				$filter_query['filters'] = $saved_search['query']['filters'];
			}

			if ( isset( $saved_search['query']['exclude_filters'] ) ) {
				$filter_query['exclude_filters'] = $saved_search['query']['exclude_filters'];
			}
		}

		if ( empty( $filter_query['filters'] ) && empty( $filter_query['exclude_filters'] ) ) {
			$filter_query['filters'] = get_filters_from_old_query_vars( get_request_query() );
		}

		wp_enqueue_style( 'groundhogg-admin' );
		wp_enqueue_style( 'groundhogg-admin-element' );
		wp_enqueue_script( 'groundhogg-admin-components' );

		switch ( $this->get_current_action() ) {
			default:
			case 'bulk_edit':

				wp_enqueue_script( 'groundhogg-admin-bulk-edit-contacts' );

				wp_localize_script( 'groundhogg-admin-bulk-edit-contacts', 'BulkEdit', [
					'meta_exclusions'              => $this->get_meta_key_exclusions(),
					'gh_contact_custom_properties' => Properties::instance()->get_all(),
					'query'                        => get_request_query(),
					'filter_query'                 => $filter_query,
					'countries'                    => utils()->location->get_countries_list(),
					'time_zones'                   => utils()->location->get_time_zones(),
					'language_dropdown'           => wp_dropdown_languages([
						'id'                          => 'locale',
						'name'                        => 'locale',
						'selected'                    => '',
						'echo'                        => false,
						'show_available_translations' => true,
						'show_option_site_default'    => false,
						'show_option_en_us'           => true,
						'explicit_option_en_us'       => true,
                    ])
				] );

				break;
			case 'edit':
				wp_enqueue_editor();
				wp_enqueue_media();

				$contact = get_contactdata( get_url_var( 'contact' ) );

				if ( ! $contact ) {
					$this->add_notice( new \WP_Error( 'error', sprintf( __( 'Contact with ID %d does not exist' ), get_url_var( 'contact' ) ) ) );
					?>
                    <script>window.open('<?php echo admin_page_url( 'gh_contacts' ); ?>', '_self')</script>
					<?php
					die();
				}

                enqueue_step_type_assets();
				wp_enqueue_style( 'groundhogg-admin-contact-editor' );
				wp_enqueue_style( 'groundhogg-admin-contact-info-cards' );
				wp_enqueue_style( 'buttons' );
				wp_enqueue_style( 'media-views' );
				wp_enqueue_script( 'postbox' );
				wp_enqueue_script( 'groundhogg-admin-contact-editor' );
				wp_enqueue_script( 'groundhogg-admin-contact-info-cards' );
				wp_localize_script( 'groundhogg-admin-contact-editor', 'ContactEditor', [
					'contact_id'                   => $contact->get_id(),
					'contact'                      => $contact,
					'meta_exclusions'              => $this->get_meta_key_exclusions(),
					'gh_contact_custom_properties' => Properties::instance()->get_all(),
					'marketable'                   => $contact->is_marketable(),
					'i18n'                         => [
						'marketable_reason' => Plugin::instance()->preferences->get_optin_status_text( $contact )
					],
				] );
				break;
			case 'view':

				wp_enqueue_style( 'select2' );
				wp_enqueue_script( 'select2' );
				wp_enqueue_style( 'groundhogg-admin-contact-inline' );
				enqueue_filter_assets();

				// Advanced Search
				wp_enqueue_script( 'groundhogg-admin-contact-search' );
				wp_localize_script( 'groundhogg-admin-contact-search', 'ContactSearch', [
					'url'           => admin_page_url( 'gh_contacts' ),
					'query'         => get_request_query(),
					'filter_query'  => $filter_query,
					'searches'      => array_values( Saved_Searches::instance()->get_all() ),
					'currentSearch' => $saved_search
				] );
				break;
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

	}

	/**
	 * Get the screen title
	 */
	public function get_title() {
		switch ( $this->get_current_action() ) {
			case 'add':
				return _ex( 'Add Contact', 'page_title', 'groundhogg' );
			case 'edit':
				$contacts = $this->get_items();
				$contact  = get_contactdata( array_shift( $contacts ) ); //todo check
				if ( $contact ) {
					return sprintf( _x( 'Edit Contact: %s', 'page_title', 'groundhogg' ), $contact->get_full_name() );
				} else {
					return _ex( 'Oops!', 'page_title', 'groundhogg' );
				}
			case 'form':

				if ( key_exists( 'contact', $_GET ) ) {
					$contacts = $this->get_items();
					$contact  = get_contactdata( array_shift( $contacts ) ); // todo check

					return sprintf( _x( 'Submit Form For %s', 'page_title', 'groundhogg' ), $contact->get_full_name() );
				} else {
					return _ex( 'Submit Form', 'page_title', 'groundhogg' );
				}
			case 'bulk_edit':
				return __( 'Bulk Edit Contacts', 'groundhogg' );
			case 'view':
			default:
				return _ex( 'Contacts', 'page_title', 'groundhogg' );
		}
	}

	protected function get_title_actions() {

		if ( $this->get_current_action() == 'bulk_edit' ) {
			return [];
		}

		$actions = [];

		if ( current_user_can( 'add_contacts' ) ) {
			$actions[] = [
				'link'   => $this->admin_url( [ 'action' => 'add' ] ),
				'action' => __( 'Add New', 'groundhogg' ),
				'target' => '_self',
				'id'     => 'quick-add',
			];
		}

		if ( current_user_can( 'import_contacts' ) ) {
			$actions[] = [
				'link'   => admin_page_url( 'gh_tools', [ 'tab' => 'import', 'action' => 'add' ] ),
				'action' => __( 'Import', 'groundhogg' ),
				'target' => '_self',
				'id'     => 'import_contacts'
			];
		}

		return $actions;
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

		$args['first_name'] = sanitize_text_field( get_post_var( 'first_name' ) );
		$args['last_name']  = sanitize_text_field( get_post_var( 'last_name' ) );
		$args['owner_id']   = absint( get_post_var( 'owner_id', get_current_user_id() ) );

		$email = sanitize_email( get_post_var( 'email' ) );

		if ( ! get_db( 'contacts' )->exists( $email ) ) {
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

		$contact->update_meta( 'mobile_phone', sanitize_text_field( get_post_var( 'mobile_phone' ) ) );
		$contact->update_meta( 'primary_phone', sanitize_text_field( get_post_var( 'primary_phone' ) ) );
		$contact->update_meta( 'primary_phone_extension', sanitize_text_field( get_post_var( 'primary_phone_extension' ) ) );
		$contact->add_note( get_post_var( 'notes' ) );


		if ( get_request_var( 'tags' ) ) {
			$contact->add_tag( get_post_var( 'tags' ) );
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
			'alternate_emails',
			'alternate_phones',
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
			'street_address_1',
			'street_address_2',
			'time_zone',
			'times_logged_in',
			'user_login',
			'city',
			'postal_zip',
			'region',
			'country',
			'notes',
			'files',
			'ip_address',
			'last_optin',
			'last_sent',
			'country_name',
			'region_code',
			'locale',

//			Moved to companies addon
//			'company_name',
//			'company_address',
//			'company_department',
//			'company_phone',
//			'company_phone_extension',
//			'job_title',
		] );
	}

	public function ajax_upload_file() {

		$id      = absint( get_post_var( 'contact' ) );
		$contact = get_contactdata( $id );

		$file = $_FILES['file-upload'];

		if ( ! get_array_var( $file, 'error' ) ) {
			$e = $contact->upload_file( $file );

			if ( is_wp_error( $e ) ) {
				wp_send_json_error( $e );
			}
		}

		wp_send_json_success( [
			'files' => $contact->get_files()
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
	 * Update the contact via ajax for backwards compatibility with custom extensions
	 */
	public function ajax_edit_contact() {

		if ( ! doing_action() ) {
			return;
		}

		$id = absint( get_request_var( 'contact' ) );

		$this->process_edit();

		$contact = new Contact( $id );

		ob_start();

		include __DIR__ . '/parts/details-card.php';

		$details = ob_get_clean();

		wp_send_json_success( [
			'contact' => $contact,
			'details' => $details,
		] );
	}

	/**
	 * Update the contact via the admin screen
	 */
	public function process_edit() {

		$id = absint( get_request_var( 'contact' ) );

		if ( ! $id ) {
			return new \WP_Error( 'no_contact_id', __( 'Contact id not found.', 'groundhogg' ) );
		}

		$contact = get_contactdata( $id );

		if ( ! current_user_can( 'edit_contact', $contact ) ) {
			$this->wp_die_no_access();
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

		$args['first_name']   = sanitize_text_field( get_request_var( 'first_name' ) );
		$args['last_name']    = sanitize_text_field( get_request_var( 'last_name' ) );
		$args['owner_id']     = absint( get_request_var( 'owner_id' ) );
		$args['user_id']      = absint( get_request_var( 'user', $contact->get_user_id() ) );
		$args['optin_status'] = Preferences::sanitize( get_request_var( 'optin_status' ), $contact->get_optin_status() );

		$contact->update( $args );

		$basic_text_fields = [
			'mobile_phone',
			'primary_phone',
			'primary_phone_extension',
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
			'locale',

			// Moved to companies addon
//			'company_phone',
//			'company_phone_extension',
//			'company_name',
//			'job_title',
//			'company_address',
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
				$time     = mktime( 0, 0, 0, $birthday[1], $birthday[2], $birthday[0] );
				$birthday = Ymd( $time );

				$contact->update_meta( 'birthday', $birthday );
			} else {
				$this->add_notice( new \WP_Error( 'invalid_date', __( 'The birthday date provided is not a valid date.' ) ) );
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

		if ( ! wp_doing_ajax() ) {
			$this->add_notice( 'update', _x( "Contact updated!", 'notice', 'groundhogg' ), 'success' );
		}

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

			if ( ! current_user_can( 'delete_contact', $id ) ) {
				$this->wp_die_no_access();
			}

			if ( ! get_db( 'contacts' )->delete( $id ) ) {
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

			if ( ! current_user_can( 'edit_contact', $contact ) ) {
				$this->wp_die_no_access();
			}

			// Don't re-subscribe contacts already confirmed
			if ( $contact->get_optin_status() === Preferences::CONFIRMED && $status === Preferences::UNCONFIRMED ) {
				continue;
			}

			$contact->change_marketing_preference( $status );

			if ( $status === Preferences::SPAM ) {
				$ip_address = $contact->get_meta( 'ip_address' );

				if ( $ip_address ) {
					$blacklist = get_option( 'disallowed_keys' );
					$blacklist .= "\n" . $ip_address;
					$blacklist = sanitize_textarea_field( $blacklist );
					update_option( 'disallowed_keys', $blacklist );
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


	public function ajax_get_table() {

//		if ( ! current_user_can( 'view_contacts' ) ){
//			return;
//		}

		ob_start();

		$contacts_table = new Tables\Contacts_Table();

		?>
        <form method="post" id="contacts-table-form">
			<?php
			$contacts_table->prepare_items();
			$contacts_table->display();
			?>
        </form>
		<?php

		$table = ob_get_clean();

		wp_send_json_success( [
			'html' => $table
		] );
	}

	/**
	 * Save the contact during inline edit
	 */
	public function ajax_contact_table_row() {

		if ( ! wp_doing_ajax() ) {
			return;
		}

		if ( ! current_user_can( 'edit_contacts' ) ) {
			$this->wp_die_no_access();
		}

		$contact = get_contactdata( get_post_var( 'contact' ) );

		if ( ! current_user_can( 'view_contact', $contact ) ) {
			wp_send_json_error();
		}

		$contactTable = new Tables\Contacts_Table;

		ob_start();

		$contactTable->single_row( $contact );

		$row = ob_get_clean();

		wp_send_json_success( [
			'row' => $row
		] );
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
	 * Display the contact table
	 */
	public function view() {
		if ( ! current_user_can( 'view_contacts' ) ) {
			$this->wp_die_no_access();
		}

		$contacts_table = new Tables\Contacts_Table();

//		include __DIR__ . '/advanced-search.php';

		$contacts_table->views();

		include __DIR__ . '/parts/quick-search.php';

		?>
        <form method="post" id="contacts-table-form">
			<?php
			$contacts_table->prepare_items();
			$contacts_table->display();
			?>
        </form>
		<?php
	}

	function bulk_edit() {
		if ( ! current_user_can( 'edit_contacts' ) ) {
			$this->wp_die_no_access();
		}

		?>
        <div id="bulk-edit"></div><?php
	}

	function process___export() {
		if ( ! current_user_can( 'export_contacts' ) ) {
			$this->wp_die_no_access();
		}

		return admin_page_url( 'gh_tools', [
			'tab'    => 'export',
			'action' => 'choose_columns',
			'query'  => [
				'include' => implode( ',', $this->get_items() )
			]
		] );
	}

	function process___bulk_edit() {
		if ( ! current_user_can( 'edit_contacts' ) ) {
			$this->wp_die_no_access();
		}

		return admin_page_url( 'gh_contacts', [
			'action'  => 'bulk_edit',
			'include' => implode( ',', $this->get_items() )
		] );
	}

	function process_bulk_edit() {
		if ( ! current_user_can( 'edit_contacts' ) ) {
			$this->wp_die_no_access();
		}

		$query = get_post_var( 'query' );

		$exclude_keys = [
			'_wpnonce'         => '',
			'_wp_http_referer' => '',
			'query'            => '',
			'submit'           => '',
		];

		$edits = array_diff_key( $_POST, $exclude_keys );

		set_transient( 'gh_bulk_edit_fields', array_filter( $edits ) );
		set_transient( 'gh_bulk_edit_query', $query );

//		wp_send_json( [
//			'query'    => $query,
//			'edits'    => $edits,
//			'mappable' => array_keys( get_mappable_fields() )
//		] );

		bulk_jobs()->bulk_edit_contacts->start( $query );
	}

	/**
	 * Display the edit screen
	 */
	function edit() {

		if ( ! current_user_can( 'view_contacts' ) ) {
			$this->wp_die_no_access();
		}

		include __DIR__ . '/parts/edit.php';
	}

	/**
	 * Display the add screen
	 */
	function add() {
		if ( ! current_user_can( 'add_contacts' ) ) {
			$this->wp_die_no_access();
		}
		include __DIR__ . '/parts/add-contact.php';
	}

	public function form() {
		if ( ! current_user_can( 'edit_contacts' ) ) {
			$this->wp_die_no_access();
		}
		include __DIR__ . '/parts/form-admin-submit.php';
	}
}
