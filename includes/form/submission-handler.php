<?php
namespace Groundhogg\Form;

use function Groundhogg\after_form_submit_handler;
use function Groundhogg\blacklist_check;
use Groundhogg\Contact;
use function Groundhogg\decrypt;
use function Groundhogg\doing_rest;
use function Groundhogg\form_errors;
use function Groundhogg\get_array_var;
use function Groundhogg\get_request_var;
use Groundhogg\Plugin;
use function Groundhogg\split_name;
use Groundhogg\Step;
use Groundhogg\Submission;
use Groundhogg\Supports_Errors;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Submission
 *
 * Process a from submission if a form submission is in progress.
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */
class Submission_Handler extends Supports_Errors
{

    protected $doing_rest = false;

    /**
     * @var array
     */
    protected $posted_data = [];

    /**
     * @var array
     */
    protected $posted_files = [];

    /**
     * @var int
     */
    protected $form_id = 0;

    /**
     * @var array
     */
    protected $configuration = [];

    /**
     * @var Step
     */
    protected $step;


    public function __construct()
    {
        if ( ! get_request_var( 'gh_submit_form' ) ){
            return;
        }

        if ( wp_doing_ajax() && wp_verify_nonce( get_request_var( '_ghnonce' ), 'groundhogg_frontend' ) ) {
            add_action('wp_ajax_groundhogg_ajax_form_submit', [$this, 'setup']);
            add_action('wp_ajax_groundhogg_ajax_form_submit', [$this, 'ajax_handler']);
            add_action('wp_ajax_nopriv_groundhogg_ajax_form_submit', [$this, 'setup']);
            add_action('wp_ajax_nopriv_groundhogg_ajax_form_submit', [$this, 'ajax_handler']);
        } else {
            add_action( 'init', [ $this, 'setup' ] );
        }
    }

    public function ajax_handler()
    {
        if ( $this->has_errors() ){
            wp_send_json_error( [ 'errors' => $this->get_errors(), 'html' => form_errors() ] );
        }
    }

    public function setup()
    {
        // Set the form ID
        $this->form_id = absint( get_request_var( 'gh_submit_form' ) );

        $form_key = absint( decrypt( get_request_var( 'gh_submit_form_key' ) ) );

        if ( $this->form_id !== $form_key ){
            $this->add_error( 'invalid_form', "This form does not exist." );
            return;
        }

        // Set a step
        $this->step = Plugin::$instance->utils->get_step( $this->get_form_id() );

        if ( ! $this->step ){
            $this->add_error( 'invalid_form', "This form does not exist." );
            return;
        }

        // Setup the configuration
        $this->configuration = $this->step->get_meta( 'config' );

        if ( ! $this->configuration ){
            $this->add_error( 'invalid_form_configuration', "This form is setup incorrectly." );
            return;
        }

        // Setup the POST data
        $this->posted_data = wp_unslash( $_POST );
        $this->posted_files = $_FILES;

        do_action( 'groundhogg/submission_handler/setup', $this );

        // Check spam and honeypot.
        if ( $this->spam_check() ){
            $this->add_error( new \WP_Error( 'failed_spam_check', 'Submission flagged as spam.' ) );
            return;
        }

        $this->process();
    }

    /**
     * Process the form values
     *
     * @return bool|false
     */
    public function process()
    {

        // Arrays for stuff
        $meta = [];
        $tags = [];
        $args = [];
        $files = [];

        // Iterate over expected fields...
        foreach ( $this->configuration as $field => $config ){

            // Check for FILE type special...
            if ( $this->field_is( $field, 'file' ) ){
                if ( $this->field_is_required( $field ) && ! $this->get_posted_file( $field )  ){
                    $this->add_error( 'missing_required_field', sprintf( __( '<b>Missing a required field:</b> %s', 'groundhogg' ), $this->get_field_label( $field ) ) );
                    continue;
                }

                // Auto map to files array
                $files[ $field ] = $this->get_posted_file( $field );

            // Check for required fields...
            } else {
                if ( $this->field_is_required( $field ) && ! $this->get_posted_data( $field )  ){
                    $this->add_error( 'missing_required_field', sprintf( __( '<b>Missing a required field:</b> %s', 'groundhogg' ), $this->get_field_label( $field ) ) );
                    continue;
                }
            }

            $value = $this->get_posted_data( $field );
            $callback = $this->get_field_config_att( $field, 'callback' );

            // Validate the input against the Field class.
            $value = apply_filters(
                'groundhogg/form/submission_handler/' . $this->get_field_config_att( $field, 'type' ) . '/validate',
                call_user_func_array( $callback , [ $value, $this->get_field_config( $field ) ] )
            );

            if ( is_wp_error( $value ) ){
                $this->add_error( $value );
                continue;
            }

            // Run Basic checks...
            switch ( $field ){
                case 'full_name':
                    $parts = split_name( $value );
                    $args[ 'first_name' ] = $parts[0];
                    $args[ 'last_name' ] = $parts[1];
                    break;
                case 'first_name':
                case 'last_name':
                    $args[ $field ] = $value;
                    break;
                case 'email':
                    $args[ 'email' ] = $value;
                    break;
	            case 'address':

	            	$parts = [
	            		'street_address_1',
	            		'street_address_2',
	            		'city',
	            		'postal_zip',
	            		'region',
			            'country'
		            ];

	            	foreach ( $parts as $key ){
	            		$meta[ $key ] = get_array_var( $value, $key );
		            }

	            	break;
                // Only checks whether value is not empty.
                case 'terms_agreement':
                    if ( ! empty( $value ) ){
                        $meta[ 'terms_agreement' ] = 'yes';
                        $meta[ 'terms_agreement_date' ] = date_i18n( get_option( 'date_format' ) );
                    }
                    break;
                // Only checks whether value is not empty.
                case 'gdpr_consent':
                    if ( ! empty( $value ) ){
                        $meta[ 'gdpr_consent' ] = 'yes';
                        $meta[ 'gdpr_consent_date' ] = date_i18n( get_option( 'date_format' ) );
                    }
                    break;
                case 'country':
                    if ( strlen( $value ) !== 2 ){
                        $countries = Plugin::$instance->utils->location->get_countries_list();
                        $code = array_search( $value, $countries );
                        if ( $code ){
                            $value = $code;
                        }
                    }
                    $meta[ $field ] = $value;
                    break;
	            // Custom Fields.
	            default:
		            $meta[ $field ] = $value;
		            break;
            }

            // Check for tag mappings.
            if ( $this->has_tag_map( $field ) ){
                //get list of values
                if (preg_match('/\,/' , $value)) {

                    $values= explode( ',', $value );

                    foreach ( $values as $value){
                        $tags[] = $this->get_tag_from_map( $field, trim( $value ));
                    }

                } else {
                    $tags[] = $this->get_tag_from_map( $field, $value );
                }

            }

        }

        do_action( 'groundhogg/form/submission_handler/before_create_contact', $args, $meta, $tags, $files, $this );

        $first = get_array_var( $args, 'first_name' );
        $last = get_array_var( $args, 'last_name' );

        if ( $first === $last ){
            $this->add_error( new \WP_Error( 'error', __( 'First & last name should not be the same.' ) ) );
        }

        if ( $this->has_errors() ){
            return false;
        }

        $email = get_array_var( $args, 'email' );

        if ( ! $email ){
            $contact = Plugin::$instance->tracking->get_current_contact();
        } else {

            $contact = new Contact( $args );

            if ( ! $contact->exists() ){
                return $this->add_error( 'db_error', 'Unable to create contact record.' );
            }

        }

        if ( ! $contact || ! $contact->exists() ){
            return $this->add_error( 'no_record', 'Unable to create contact record.' );
        }

        // Create the submission
        $submission = new Submission( [
            'step_id' => $this->get_form_id(),
            'contact_id' => $contact->get_id()
        ] );

        // Add the submission data.
        $submission_data = array_merge( $args, $meta );
        $submission->add_posted_data( $submission_data );

        // Upload the files.
        foreach ( $files as $file_key => $file ){
            $file = $contact->upload_file( $file );

            if ( ! is_wp_error( $file ) && is_array( $file ) ){
                // Add direct url to meta
                $meta[ $file_key ] = $file[ 'url' ];
            }
        }

        // Update the meta
        foreach ( $meta as $meta_key => $meta_value ) {
            $contact->update_meta( $meta_key, $meta_value );
        }

        // Apply the tags
        $contact->add_tag( $tags );

        // No need for this if is in the admin
        if ( ! $this->is_admin_submission() ){
            after_form_submit_handler( $contact );
        }

        $feed_response = apply_filters( 'groundhogg/form/submission_handler/feed', true, $submission, $contact, $this );

        if ( is_wp_error( $feed_response ) ){
            return $this->add_error( $feed_response );
        }

        if ( ! $this->has_errors() ){

            /**
             * After a successful submission.
             *
             * @param $submission Submission
             * @param $contact Contact
             * @param $this Submission_Handler
             */
            do_action( 'groundhogg/form/submission_handler/after', $submission, $contact, $this );

            if ( $this->is_ajax_request() ){

                $success_message = $this->step->get_meta('success_message' );

                if ( ! $success_message ){
                    $success_message = __( 'Your submission has been received!', 'groundhogg' );
                }

                wp_send_json_success( [ 'message' => $success_message,  ] );

            } else if ( $this->is_admin_submission() ){

                Plugin::$instance->notices->add( 'form_filled', _x( 'Form submitted', 'notice', 'groundhogg' ) );
                $admin_url = admin_url( sprintf( 'admin.php?page=gh_contacts&action=edit&contact=%d', $contact->get_id() ) );
                wp_redirect( $admin_url );
                die();

            } else {

                $success_page = $this->step->get_meta('success_page' );
                wp_redirect( $success_page );
                die();

            }
        }

        return false;
    }

    protected function is_ajax_request()
    {
        return wp_doing_ajax() || doing_rest();
    }

    public function is_admin_submission()
    {
        return is_admin() && current_user_can( 'edit_contacts' );
    }

    public function get_posted_data( $key=false, $default = false )
    {
        if ( ! $key ){
            return $this->posted_data;
        }

        return get_array_var( $this->posted_data, $key, $default );
    }

    public function get_posted_file( $key=false, $default = false )
    {
        if ( ! $key ){
            return $this->posted_files;
        }

        return get_array_var( $this->posted_files, $key, $default );
    }

    public function get_form_id()
    {
        return $this->form_id;
    }

    public function get_field_config( $field )
    {
        return get_array_var( $this->configuration, $field );
    }

    public function field_is_required( $field )
    {
        return $this->get_field_config_att( $field, 'required' );
    }

    /**
     * @param $field
     * @param $att
     * @return bool|array|string
     */
    public function get_field_config_att( $field, $att )
    {
        return get_array_var( $this->get_field_config( $field ), $att );
    }

    public function get_field_label( $field )
    {
        return $this->get_field_config_att( $field, 'label' );
    }

    public function field_is( $field, $type )
    {
        return $this->get_field_config_att( $field, 'type' ) === $type;
    }

    public function has_tag_map( $field )
    {
        return (bool) $this->get_field_config_att( $field, 'tag_mapping' );
    }

    public function get_tag_from_map( $field, $value )
    {
        $map = $this->get_field_config_att( $field, 'tag_mapping' );
        return absint( get_array_var( $map, md5( $value ) ) );
    }

    /**
     * Perform a series of basic spam checks.
     *
     * @return bool
     */
    public function spam_check()
    {
        if( ! class_exists( '\Browser' ) ){
            require_once GROUNDHOGG_PATH . 'includes/lib/browser.php';
        }

        $browser = new \Browser();

        $posted_data = $this->get_posted_data();

        $white_list_keys = [
            '_ghnonce',
            '_wpnonce',
            'form_data',
            'gh_submit_form_key',
            'gh_submit_form',
            'action'
        ];

        foreach ( $white_list_keys as $key ){
            unset( $posted_data[ $key ] );
        }

        $checks = [
            $browser->isRobot(),
            $browser->isAol(),
            blacklist_check( Plugin::$instance->utils->location->get_real_ip() ),
            blacklist_check( $posted_data )
        ];

        return in_array( true, $checks );
    }


}