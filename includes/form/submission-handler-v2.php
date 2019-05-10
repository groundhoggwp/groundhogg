<?php
namespace Groundhogg\Form;

use function Groundhogg\get_array_var;
use function Groundhogg\get_request_var;
use function Groundhogg\isset_not_empty;
use Groundhogg\Plugin;
use function Groundhogg\split_name;
use Groundhogg\Step;
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
class Submission_Handler_V2 extends Supports_Errors
{

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

        add_action( 'init', [ $this, 'setup' ] );
    }

    public function setup()
    {
        // Set the form ID
        $this->form_id = absint( get_request_var( 'gh_submit_form' ) );

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

//        $this->add_error( 'no_goo', 'foo' );
//        $this->add_error( 'no_goo_1', 'foo_1' );
//        $this->add_error( 'no_goo_2', 'foo_2' );

//        var_dump( $this->get_posted_data() );

        $this->process();
    }

    public function process()
    {

        // Arrays for stuff
        $meta = [];
        $tags = [];
        $notes = [];
        $args = [];
        $files = [];

        $value = "";

        // Iterate over expected fields...
        foreach ( $this->configuration as $field => $config ){

            // Check for FILE type special...
            if ( $this->field_is( $field, 'file' ) ){
                if ( $this->field_is_required( $field ) && ! $this->get_posted_file( $field )  ){
                    $this->add_error( 'missing_required_field', sprintf( __( '<b>Missing a required field:</b> %s', 'groundhogg' ), $this->get_field_label( $field ) ) );
                    continue;
                }
            } else {
                if ( $this->field_is_required( $field ) && ! $this->get_posted_data( $field )  ){
                    $this->add_error( 'missing_required_field', sprintf( __( '<b>Missing a required field:</b> %s', 'groundhogg' ), $this->get_field_label( $field ) ) );
                    continue;
                }
            }

            // Run Basic checks...
            switch ( $field ){
                case 'full_name':
                    $parts = split_name( $value );
                    $args[ 'first_name' ] = sanitize_text_field( $parts[0] );
                    $args[ 'last_name' ] = sanitize_text_field( $parts[1] );
                    break;
                case 'first_name':
                case 'last_name':
                    $args[ $field ] = sanitize_text_field( $value );
                    break;
                case 'email':
                    $args[ 'email' ] = sanitize_email( $value );
                    break;
                // Custom Fields.
                default:
                    if ( strpos( $value, PHP_EOL ) !== false ){
                        $meta[ $field ] = sanitize_textarea_field( $value );
                    } else {
                        $meta[ $field ] = sanitize_text_field( $value );
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
            }

        }

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

    /**
     * Check a given value for spam.
     * If it's in the blacklist, mark the contact as spam and die
     *
     * @param $args mixed
     * @return bool true if spam | false if pass
     */
    public function is_spam( $args )
    {
        /* Turn into array */
        if ( ! is_array( $args ) ){ $args = [ $args ]; }

        $blacklist = get_option( 'blacklist_keys', false );

        if ( ! empty( $blacklist ) ) {

            $words = explode(PHP_EOL, $blacklist );

            foreach ($words as $word) {

                foreach ( $args as $key => $value ){

                    /* if found */
                    if ( strpos( $value, $word ) !== false ){
                        return true;
                        /* Further checking */
                    } else if ( apply_filters( 'groundhogg/submission_handler/spam', false, $value, $word, $this ) ){
                        return true;
                    }
                }
            }
        }

        return false;

    }


}