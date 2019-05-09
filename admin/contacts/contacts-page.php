<?php
namespace Groundhogg\Admin\Contacts;
use Groundhogg\Admin\Admin_Page;
use Groundhogg\Plugin;
use Groundhogg\Contact;
use Groundhogg\Preferences;
use function Groundhogg\send_email_notification;
use function Groundhogg\send_sms_notification;
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

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

class Contacts_Page extends Admin_Page
{


    protected function add_ajax_actions()
    {
        add_action('wp_ajax_wpgh_inline_save_contacts', array($this, 'save_inline'));
    }

    /**
     * Get the page slug
     *
     * @return string
     */
    public function get_slug()
    {
        return 'gh_contacts';
    }

    /**
     * Get the menu name
     *
     * @return string
     */
    public function get_name()
    {
        return _x('Contacts', 'page_title', 'groundhogg');
    }

    /**
     * The required minimum capability required to load the page
     *
     * @return string
     */
    public function get_cap()
    {
        return 'view_contacts';

    }

    public function get_priority(){
        return 5;
    }

    /**
     * Get the item type for this page
     *
     * @return mixed
     */
    public function get_item_type()
    {
        return 'contact';
    }


    /**
     * @var Bulk_Contact_Manager todo
     */
    private $exporter;


    /**
     * Get the scripts in there
     */
    public function scripts()
    {
        if ($this->get_current_action() === 'edit' || $this->get_current_action() === 'add' || $this->get_current_action() === 'form' ) {
            wp_enqueue_style('groundhogg-admin-contact-editor' );
            wp_enqueue_script('groundhogg-admin-contact-editor' );
        } else {
            wp_enqueue_style('select2' );
            wp_enqueue_script('select2' );
            wp_enqueue_style('groundhogg-admin-contact-inline' );
            wp_enqueue_script('groundhogg-admin-contact-inline' );
        }
    }
    /* Register the page */

    /* help bar */
    public function help()
    {
        $screen = get_current_screen();

        $screen->add_help_tab(
            array(
                'id' => 'gh_overview',
                'title' => __('Overview'),
                'content' => '<p>' . __("This is where you can manage and view your contacts. Click the quick edit to quickly change contact details.", 'groundhogg') . '</p>'
            )
        );

        $screen->add_help_tab(
            array(
                'id' => 'gh_edit',
                'title' => __('Editing'),
                'content' => '<p>' . __("While editing a contact you can modify any of their personal information. There are several points of interest...", 'groundhogg') . '</p>'
                    . '<ul> '
                    . '<li>' . __('Manually unsubscribe a contact by checking the "mark as unsubscribed" button.', 'groundhogg') . '</li>'
                    . '<li>' . __('Make sure your in compliance by ensuring the terms of agreement and GDPR consent are both checked under the compliance section.', 'groundhogg') . '</li>'
                    . '<li>' . __('View the origin of the contact by looking at the lead source field.', 'groundhogg') . '</li>'
                    . '<li>' . __('Add or remove custom information about the contact by enabling the "Edit Meta" section. Each meta also includes a replacement code to include it in an email.', 'groundhogg') . '</li>'
                    . '<li>' . __('Re-run or cancel events for this contact by viewing the "Upcoming Events" or "Recent History" Section', 'groundhogg') . '</li>'
                    . '<li>' . __('Monitor their engagement by looking in the "Recent Email History" section.', 'groundhogg') . '</li>'
                    . '</ul>'
            )
        );
    }



    /**
     * Get the screen title
     */
    public function get_title()
    {
        switch ($this->get_current_action()) {
            case 'add':
                return _ex('Add Contact', 'page_title', 'groundhogg');
                break;
            case 'edit':
                $contacts = $this->get_items();
                $contact = Plugin::$instance->utils->get_contact( array_shift($contacts) ); //todo check
                if ($contact) {
                    return sprintf( _x('Edit Contact: %s', 'page_title', 'groundhogg'), $contact->get_full_name() );
                } else {
                    return _ex('Oops!', 'page_title', 'groundhogg');
                }

                break;
            case 'form':

                if ( key_exists( 'contact', $_GET ) ){
                    $contacts = $this->get_items();
                    $contact = Plugin::$instance->utils->get_contact( array_shift($contacts) ); // todo check
                    return sprintf( _x('Submit Form For %s', 'page_title', 'groundhogg'), $contact->full_name );
                } else{
                    return _ex( 'Submit Form', 'page_title', 'groundhogg' );
                }

                break;
            case 'search':
                return _ex('Search Contacts', 'page_title', 'groundhogg');
                break;
            case 'view':
            default:
                return _ex('Contacts', 'page_title', 'groundhogg');
                break;
        }
    }


    /**
     * Create a contact via the admin area
     */
    public function process_add()
    {
        if (!current_user_can('add_contacts')) {
            $this->wp_die_no_access();
        }

        do_action('wpgh_admin_add_contact_before');

        if (!isset($_POST['email'])) {
            return new \WP_Error( 'NO_EMAIL', __("Please enter a valid email address.", 'groundhogg') );
        }

        if (isset($_POST['first_name']))
            $args['first_name'] = sanitize_text_field($_POST['first_name']);

        if (isset($_POST['last_name']))
            $args['last_name'] = sanitize_text_field($_POST['last_name']);

        if (isset($_POST['email'])) {

            $email = sanitize_email($_POST['email']);


            if (!Plugin::$instance->dbs->get_db('contacts')->exists($email)) {
                $args['email'] = $email;
            } else {
                return new \WP_Error( 'email_exists',  sprintf( _x('Sorry, the email %s already belongs to another contact.', 'page_title', 'groundhogg'), $email ) );
            }
        }

        if (!is_email($args['email'])) {
            return new \WP_Error( 'NO_EMAIL', __("Please enter a valid email address.", 'groundhogg') );
        }

        if (isset($_POST['owner_id'])) {
            $args['owner_id'] = intval($_POST['owner_id']);
        }

        $id = Plugin::$instance->dbs->get_db('contacts')->add($args);

        $contact = Plugin::$instance->utils->get_contact( $id );

        if (isset($_POST['primary_phone'])) {
            $contact->update_meta('primary_phone', sanitize_text_field($_POST['primary_phone']));
        }

        if (isset($_POST['primary_phone_extension'])) {
            $contact->update_meta('primary_phone_extension', sanitize_text_field($_POST['primary_phone_extension']));
        }

        if (isset($_POST['notes'])) {
            $contact->add_note($_POST['notes']);
        }

        if (isset($_POST['tags'])) {
            $contact->add_tag($_POST['tags']);
        }

        $this->add_notice('created', _x("Contact created!", 'notice', 'groundhogg'), 'success');

        return (admin_url('admin.php?page=gh_contacts&action=edit&contact=' . $id));
    }


    public function process_delete ()
    {
        if (!current_user_can('delete_contacts')) {
          $this->wp_die_no_access();
        }

        foreach ($this->get_items() as $id) {
            do_action('wpgh_pre_admin_delete_contact', $id);
            if ( ! Plugin::$instance->dbs->get_db( 'contacts' )->delete( $id ) ){
                return new \WP_Error( 'unable_to_delete_contact', "Something went wrong while deleting the contact." );
            }
        }

        $this->add_notice(
            esc_attr('deleted'),
            sprintf(_nx('Deleted %d contact', 'Deleted %d contacts', count($this->get_items()), 'notice', 'groundhogg'), count($this->get_items())),
            'success'
        );

        return true;
    }

    /**
     * Update the contact via the admin screen
     */
    public function process_edit()
    {

        if (!current_user_can('edit_contacts')) {
            $this->wp_die_no_access();
        }

        $id = intval($_GET['contact']);

        if (!$id) {
            return new \WP_Error( 'no_contcat', __( 'Contact id not found' ) );
        }

        $contact = Plugin::$instance->utils->get_contact( $id );

//        do_action('wpgh_admin_update_contact_before', $id); todo

        //todo security check

        /* Save the meta first... as actual fields might overwrite it later... */
        $cur_meta = Plugin::$instance->dbs->get_db('contactmeta')->get_meta( $id );

//        WPGH()->contact_meta->get_meta($id); todo

        $exclude_meta_list = array(
            'files',
            'notes'
        );

        if (isset($_POST['meta'])) {
            $posted_meta = $_POST['meta'];
            foreach ($cur_meta as $key => $value) {
                if (isset($posted_meta[$key])) {
                    $contact->update_meta($key, sanitize_text_field($posted_meta[$key]));
                } else {
                    if (!in_array($key, $exclude_meta_list)) {
                        $contact->delete_meta($key);
                    }
                }
            }
        }

        /* add new meta */
        if (isset($_POST['newmetakey']) && isset($_POST['newmetavalue'])) {

            $new_meta_keys = $_POST['newmetakey'];
            $new_meta_vals = $_POST['newmetavalue'];

            foreach ($new_meta_keys as $i => $new_meta_key) {
                if (strpos($new_meta_vals[$i], PHP_EOL) !== false) {
                    $contact->update_meta(sanitize_key($new_meta_key), sanitize_textarea_field(stripslashes($new_meta_vals[$i])));
                } else {
                    $contact->update_meta(sanitize_key($new_meta_key), sanitize_text_field(stripslashes($new_meta_vals[$i])));
                }
            }

        }

        $args = array();

        if (isset($_POST['email'])) {

            $email = sanitize_email($_POST['email']);

            //check if it's the current email address.
            if ($contact->email !== $email) {
                //check if another email address like it exists...

                if (!Plugin::$instance->dbs->get_db('contacts')->exists($email)) {
                    $args['email'] = $email;
                    //update new optin status to unconfirmed
                    $contact->change_marketing_preference( Preferences::UNCONFIRMED ); //todo
                    return new \WP_Error( 'optin_status_updated',sprintf(_x('The email address of this contact has been changed to %s. Their optin status has been changed to [unconfirmed] to reflect the change as well.', 'notice', 'groundhogg'), $email) );

                } else {
                    return new \WP_Error( 'email_exists', sprintf(_x('Sorry, the email %s already belongs to another contact.', 'notice', 'groundhogg'), $email) );
                }
            }
        }

        if (isset($_POST['first_name'])) {
            $args['first_name'] = sanitize_text_field($_POST['first_name']);
        }

        if (isset($_POST['last_name'])) {
            $args['last_name'] = sanitize_text_field($_POST['last_name']);
        }

        if (isset($_POST['owner_id'])) {
            $args['owner_id'] = intval($_POST['owner_id']);
        }

        if (isset($_POST['user'])) {
            $args['user_id'] = intval($_POST['user']);
        }

        if ( isset( $_POST[ 'unlink_user' ]) ){
            $args['user_id'] = null;
        }

        $args = array_map('stripslashes', $args);
        $contact->update($args);

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

        foreach ( $basic_text_fields as $field ){
            if (isset($_POST[$field]) ) {
                $contact->update_meta($field, sanitize_text_field(stripslashes($_POST[$field])));
            }
        }

        if ( isset( $_POST[ 'extrapolate_location' ] ) ){
            if ( $contact->extrapolate_location() ){
                $this->add_notice('location_updated', _x( 'Location updated.', 'notice', 'groundhogg' ), 'info');
            }
        }

        if (isset($_POST['tags'])) {

            $tags = Plugin::$instance->dbs->get_db('tags')->validate( wp_unslash($_POST['tags']));

            $cur_tags = $contact->get_tags();
            $new_tags = $tags;

            $delete_tags = array_diff($cur_tags, $new_tags);
            if (!empty($delete_tags)) {
                $contact->remove_tag($delete_tags);
            }

            $add_tags = array_diff($new_tags, $cur_tags);
            if (!empty($add_tags)) {

//                print_r( $add_tags );

                $result = $contact->add_tag($add_tags);
                if (!$result) {
                    return new \WP_Error( 'bad-tag', __('Hmm, looks like we could not add the new tags...' , 'groundhogg' ) );
                }
            }
        } else {
            $contact->remove_tag($contact->get_tags());
        }

        /* Update Main Contact Information */

        //Do after tags get updated for compatibility with new optin status change.

        if (isset($_POST['unsubscribe'])) {

            $contact->unsubscribe();

            $this->add_notice(
                esc_attr('unsubscribed'),
                _x('This contact will no longer receive marketing.', 'notice', 'groundhogg'),
                'info'
            );
        }

        if ( isset( $_POST['manual_confirm'] ) ) {
            if ( isset( $_POST[ 'confirmation_reason' ] ) && ! empty( $_POST[ 'confirmation_reason' ] ) ){
                $contact->change_marketing_preference( Preferences::CONFIRMED );
                $contact->update_meta( 'manual_confirmation_reason', sanitize_textarea_field( stripslashes( $_POST[ 'confirmation_reason' ] ) ) );
                $this->add_notice(
                    esc_attr('confirmed'),
                    _x('This contact\'s email address has been confirmed.', 'notice', 'groundhogg'),
                    'info'
                );
            } else {
                return new \WP_Error( 'manual_confirmation_error', __('A reason is required to change the email confirmation status.' , 'groundhogg' ) );

            }
        }

        if ( isset( $_POST[ 'add_new_note' ] ) ){
            $contact->add_note( $_POST[ 'add_note' ] );
        }

        if (isset($_POST['send_email']) && isset($_POST['email_id']) && current_user_can('send_emails')) {

            $mail_id = intval( $_POST['email_id'] );

            if( send_email_notification( $mail_id, $contact->ID ) ){
                $this->add_notice( 'email_queued', _x( 'The email has been added to the queue and will send shortly.', 'notice', 'groundhogg' ) );
            }
        }

        /* USE the same email priviledges */
        if (isset($_POST['send_sms']) && isset($_POST['sms_id']) && current_user_can('send_emails')) {

            $sms_id = intval( $_POST['sms_id'] );

            if( send_sms_notification( $sms_id, $contact->ID ) ){
                $this->add_notice( 'sms_queued', _x( 'The sms has been added to the queue and will send shortly.', 'notice', 'groundhogg' ) );
            }
        }

        if (isset($_POST['start_funnel']) && isset($_POST['add_contacts_to_funnel_step_picker']) && current_user_can('edit_contacts')) {

//            $step = wpgh_get_funnel_step(intval($_POST['add_contacts_to_funnel_step_picker'])); todo

            $step = Plugin::$instance->utils->get_step(intval($_POST['add_contacts_to_funnel_step_picker']));
            if ($step->enqueue($contact)) {
                $this->add_notice('started', _x("Contact added to funnel.", 'notice', 'groundhogg'), 'info');
            }
        }

        $this->add_notice('update', _x("Contact updated!", 'notice', 'groundhogg'), 'success');

//        if (!empty($_FILES['files']['tmp_name'][0])) {
//            $this->upload_files();
//        } todo ENABLE UPLOAD FILE FOR CONTACTS

        //do_action('wpgh_admin_update_contact_after', $id); todo remove

        return true;
    }

    /**
     * Upload files to a contact if uploaded from the admin page
     */
    private function upload_files()
    {
        $id = intval($_GET['contact']);
        $contact = Plugin::$instance->utils->get_contact($id);
        if (!isset($_FILES['files']['tmp_name'][0]) || empty($_FILES['files']['tmp_name'][0])) {
            return false;
        }

        $files = $_FILES['files'];

        $num_files = count($files['name']);

        $upload_overrides = array('test_form' => false);

        for ($i = 0; $i < $num_files; $i++) {

            $ifile = array(
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i],
            );

            if (!function_exists('wp_handle_upload')) {
                require_once(ABSPATH . '/wp-admin/includes/file.php');
            }

//            Plugin::$instance->dbs->get_db('submissions')->
            WPGH()->submission->contact = $contact;  // todo
            WPGH()->submission->set_upload_dirs();   // todo


            add_filter('upload_dir', array(WPGH()->submission, 'files_upload_dir')); //todo
            $mfile = wp_handle_upload($ifile, $upload_overrides);
            remove_filter('upload_dir', array(WPGH()->submission, 'files_upload_dir')); //todo

            if (isset($mfile['error'])) {
                if (empty($mfile['error'])) {
                    $mfile['error'] = __('Could not upload file.', 'notice', 'groundhogg');
                }
                return new \WP_Error( 'BAD_UPLOAD', $mfile['error'] );
            } else {
                $files = $contact->get_meta('files');
                if (!$files) {
                    $files = array();
                }
                $j = count($files) + 1;
                $mfile['key'] = $j;
                $mfile = array_map('wp_normalize_path', $mfile);
                $files[$j] = $mfile;
                $contact->update_meta('files', $files);
                /* Compat for local host WP filesystems */
            }
        }
        return true;
    }

    public function process_spam()
    {


        if (!current_user_can('edit_contacts')) {
            $this->wp_die_no_access();
        }

        foreach ($this->get_items() as $id) {


            $contact = Plugin::$instance->utils->get_contact( $id );
            $contact->change_marketing_preference( Preferences::SPAM );

            $ip_address = $contact->get_meta('ip_address');

            if ($ip_address) {
                $blacklist = Plugin::$instance->settings->get_option('blacklist_keys');
                $blacklist .= "\n" . $ip_address;
                $blacklist = sanitize_textarea_field($blacklist);
                update_option('blacklist_keys', $blacklist);
            }

            do_action('wpgh_contact_marked_as_spam', $id); //todo
        }

        $this->add_notice(
            esc_attr('spammed'),
            sprintf(_nx('Marked %d contact as spam.', 'Marked %d contact as spam.', count($this->get_items()), 'notice', 'groundhogg'), count($this->get_items())),
            'success'
        );

//        do_action('wpgh_spam_contacts'); todo remove

        return true;
    }

    public function process_unspam()
    {
        if (!current_user_can('edit_contacts')) {
            $this->wp_die_no_access();
        }

        foreach ($this->get_items() as $id) {
            $contact = Plugin::$instance->utils->get_contact($id);
            $contact->change_marketing_preference( Preferences::UNCONFIRMED );
        }

        $this->add_notice(
            esc_attr('unspam'),
            sprintf(_nx('Approved %d contact', 'Approved %d contacts', count($this->get_items()), 'notice', 'groundhogg'), count($this->get_items())),
            'success'
        );

      return true;
    }

    public function process_unbounce()
    {
        if (!current_user_can('edit_contacts')) {
            $this->wp_die_no_access();
        }

        foreach ($this->get_items() as $id) {
            $contact = Plugin::$instance->utils->get_contact($id);
            $contact->change_marketing_preference( Preferences::UNCONFIRMED );
        }

        $this->add_notice(
            esc_attr('unbounce'),
            sprintf(_nx('Approved %d contact', 'Approved %d contacts', count($this->get_items()), 'notice', 'groundhogg'), count($this->get_items())),
            'success'
        );
        return true;
    }

    public function process_search()
    {
        if (!current_user_can('edit_contacts')) {
            $this->wp_die_no_access();
        }

        if (!empty($_POST)) {
            $search = $this->do_search();
        }
    }

    public function process_apply_tag()
    {
        if (!current_user_can('edit_contacts')) {
            $this->wp_die_no_access();
        }

        if ( ! empty( $_POST[ 'bulk_tags' ] ) ){

            $tags = $_POST[ 'bulk_tags' ];

            foreach ($this->get_items() as $id) {
                $contact = Plugin::$instance->utils->get_contact($id);
                $contact->apply_tag( $tags );
            }

            $this->add_notice(
                esc_attr('applied_tags'),
                sprintf(_nx('Applied %d tags to %d contact', 'Applied %d tags to %d contacts', count($this->get_items()), 'notice', 'groundhogg'), count( $tags ), count($this->get_items())),
                'success'
            );
        }
        return true;
    }

    public function process_remove_tag()
    {
        if (!current_user_can('edit_contacts')) {
            $this->wp_die_no_access();
        }

        if ( ! empty( $_POST[ 'bulk_tags' ] ) ){

            $tags = $_POST[ 'bulk_tags' ];

            foreach ($this->get_items() as $id) {
                $contact = Plugin::$instance->utils->get_contact($id);
                $contact->remove_tag( $tags );
            }

            $this->add_notice(
                esc_attr('removed_tags'),
                sprintf(_nx('Removed %d tags from %d contact', 'Removed %d tags from %d contacts', count($this->get_items()), 'notice', 'groundhogg'), count( $tags ), count($this->get_items())),
                'success'
            );

        }
        return true;
    }



    /**
     * Save the contact during inline edit
     */
    public function save_inline()
    {

        if (!wp_doing_ajax()) {
            return;
        }

        if (!current_user_can('edit_contacts')) {
           $this->wp_die_no_access();
        }

        $id = (int)$_POST['ID'];

        $contact = Plugin::$instance->utils->get_contact($id);

        do_action('wpgh_inline_update_contact_before', $id); //todo remove

        $email = sanitize_email($_POST['email']);

        $args['first_name'] = sanitize_text_field($_POST['first_name']);
        $args['last_name'] = sanitize_text_field($_POST['last_name']);
        $args['owner_id'] = intval($_POST['owner']);

        $err = array();

        if (!$email) {
            $err[] = _x('Email cannot be blank.', 'notice', 'groundhogg');
        } else if (!is_email($email)) {
            $err[] = _x('Invalid email address.', 'notice', 'groundhogg');
        }

        //check if it's the current email address.
        if ($contact->email !== $email) {




            //check if another email address like it exists...
            if (!Plugin::$instance->dbs->get_db('contacts')->exists($email)) {
                $args['email'] = $email;

                //update new optin status to unconfirmed
                $contact->change_marketing_preference( Preferences::UNCONFIRMED );
                $err[] = sprintf(_x('The email address of this contact has been changed to %s. Their optin status has been changed to [unconfirmed] to reflect the change as well.', 'notice', 'groundhogg'), $email );

            } else {

                $err[] = sprintf(_x('Sorry, the email %s already belongs to another contact.', 'notice', 'groundhogg'), $email);

            }

        }

        if (!$args['first_name']) {
            $err[] = _x('First name cannot be blank.', 'notice', 'groundhogg');
        }

        if ($err) {
            echo implode(', ', $err);
            exit;
        }

        $args = array_map('stripslashes', $args);

        $contact->update($args);

        $tags = Plugin::$instance->dbs->get_db('tags')->validate( $_POST['tags'] ); //todo

        $cur_tags = $contact->get_tags();
        $new_tags = $tags;

        $delete_tags = array_diff($cur_tags, $new_tags);
        if (!empty($delete_tags)) {
            $contact->remove_tag($delete_tags);
        }

        $add_tags = array_diff($new_tags, $cur_tags);
        if (!empty($add_tags)) {
            $contact->add_tag($add_tags);

        }

        do_action('wpgh_inline_update_contact_after', $id); // todo remove

        if (!class_exists('Contacts_Table')) {
            include_once 'contacts-table.php';
        }

        $contactTable = new Contacts_Table;
        $contactTable->single_row( Plugin::$instance->dbs->get_db('contacts')->get( $id ) );

        wp_die(); // todo return or not !
    }


    /**
     * Display the contact table
     */
    function table()
    {

        if (!current_user_can('view_contacts')) {
            $this->wp_die_no_access();
        }

        if (!class_exists('Contacts_Table')) {
            include dirname(__FILE__) . '/contacts-table.php';
        }

        $contacts_table = new Contacts_Table();

        $this->search_form( __( 'Search Contacts', 'groundhogg' ) );


        $contacts_table->views(); ?>
        <form method="post" class="search-form wp-clearfix">
            <!-- search form -->

            <?php $contacts_table->prepare_items(); ?>
            <?php $contacts_table->display(); ?>
            <?php
            if ($contacts_table->has_items())
                $contacts_table->inline_edit();
            ?>
        </form>

        <?php
    }

    /**
     * Display the edit screen
     */
    function edit()
    {

        if (!current_user_can('view_contacts')) {
            $this->wp_die_no_access();
        }
        include dirname(__FILE__) . '/contact-editor.php';
    }

    /**
     * Display the add screen
     */
    function add()
    {
        if (!current_user_can('add_contacts')) {
            $this->wp_die_no_access();
        }
        include dirname(__FILE__) . '/add-contact.php';
    }

    function search()
    {
        if (!current_user_can('view_contacts')) {
            $this->wp_die_no_access();
        }
        include dirname(__FILE__) . '/search.php';
    }

    public function form()
    {
        if (!current_user_can('edit_contacts')) {
            $this->wp_die_no_access();
        }
        include dirname(__FILE__) . '/form-admin-submit.php';
    }

    /**
     * Display the title and dependent action include the appropriate page content
     */
    public function view()
    {
        ?>
        <div class="wrap">
            <hr class="wp-header-end">
            <?php switch ( $this->get_current_action() ){
                case 'add':
                    $this->add();
                    break;
                case 'edit':
                    $this->edit();
                    break;
                case 'search':
                    $this->search();
                    break;
                case 'form':
                    $this->form();
                    break;
                default:
                    $this->table();
            } ?>
        </div>
        <?php
    }

    protected function add_additional_actions()
    {
        // TODO: Implement add_additional_actions() method.
    }

    /**
     * From the search.php page access the POST and generate a WHERE clause...
     */
    private function do_search()
    {
        global $wpdb;

        $contacts       = Plugin::$instance->dbs->get_db('contacts')->table_name;
        $contact_meta   = Plugin::$instance->dbs->get_db('contactmeta')->table_name;
        $tags           = Plugin::$instance->dbs->get_db('tag_relationships')->table_name;


        $SELECT = "SELECT DISTINCT c.* FROM $contacts AS c LEFT JOIN $contact_meta AS meta ON c.ID = meta.contact_id LEFT JOIN $tags AS tags ON c.ID = tags.contact_id";
        $WHERE = "WHERE ";
        $CLAUSES = array();

        $general = $_POST[ 'c' ];
        $meta    = $_POST[ 'meta' ];
        $custom  = $_POST[ 'c_meta' ];
        $tags    = $_POST[ 'tags' ];
//        $tags_2    = $_POST[ 'tags_2' ];

        foreach ( $general as $key => $args ){

            if ( ! empty( $args[ 'search' ] ) && ! empty( $args[ 'comp' ] ) ){

                $search = $wpdb->esc_like( sanitize_text_field( stripslashes( $args[ 'search' ] ) ) );
                $CLAUSES[] = $this->generate_comparison_statement( 'c.' . $key, $args[ 'comp' ], $search );

            }

        }

        foreach ( $meta as $key => $args ){

            if ( ! empty( $args[ 'search' ] ) && ! empty( $args[ 'comp' ] ) ){

                $search = $wpdb->esc_like( sanitize_text_field( stripslashes( $args[ 'search' ] ) ) );
                $CLAUSES[] = $this->generate_comparison_statement( 'meta.meta_key', '=', sanitize_key( $key ) );
                $CLAUSES[] = $this->generate_comparison_statement( 'meta.meta_value', $args[ 'comp' ], $search );

            }

        }

        foreach ( $custom as $key => $args ){

            if ( ! empty( $args[ 'key' ] ) && ! empty( $args[ 'search' ] ) && ! empty( $args[ 'comp' ] ) ){

                $search = $wpdb->esc_like( sanitize_text_field( stripslashes( $args[ 'search' ] ) ) );
                $CLAUSES[] = $this->generate_comparison_statement( 'meta.meta_key', '=', sanitize_key( $args[ 'key' ] ) );
                $CLAUSES[] = $this->generate_comparison_statement( 'meta.meta_value', $args[ 'comp' ], $search );

            }

        }

        $tags_1 = wp_parse_id_list( $tags[ 'tags_1' ]['tags'] );
        $tags_2 = wp_parse_id_list( $tags[ 'tags_2' ]['tags'] );

        $SQL = sprintf( '%s %s %s', $SELECT, $WHERE, implode( ' AND ', $CLAUSES ) );

        var_dump($SQL);
        $results = $wpdb->get_results( $SQL );
        var_dump( $results );
        die();

    }

    /**
     * @param $key
     * @param $comp
     * @param $value
     *
     * @return string
     */
    private function generate_comparison_statement( $key, $comp, $value )
    {
        global $wpdb;

        if ( is_array( $value ) ){
            $value = sprintf( '(%s)', implode( ',', $value ) );
        } else if ( is_numeric( $value ) ){
            $value = intval( $value );
        }

        $insert = is_int( $value ) ? '%d' : '%s';

        switch ( $comp ){
            default:
            case '=':
                $statement = $wpdb->prepare( "$key = $insert", $value );
                break;
            case '!=':
                $statement = $wpdb->prepare( "$key = $insert", $value );
                break;
            case 'LIKE sw':
                $statement = $wpdb->prepare( "$key LIKE '%s'", $value . '%' );
                break;
            case 'LIKE ew':
                $statement = $wpdb->prepare( "$key LIKE '%s'", '%' . $value );
                break;
            case 'LIKE c':
                $statement = $wpdb->prepare( "$key LIKE '%s'", '%' . $value . '%' );
                break;
            case 'NOT LIKE c':
                $statement = $wpdb->prepare( "$key NOT LIKE '%s'", '%' . $value . '%' );
                break;
            case 'EMPTY':
                $statement = "$key IS EMPTY";
                break;
            case 'NOT EMPTY':
                $statement =  "$key IS NOT EMPTY";
                break;
        }

        return $statement;


    }
}