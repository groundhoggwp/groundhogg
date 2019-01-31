<?php
/**
 * Groundhogg API Contacts
 *
 * This class provides a front-facing JSON API that makes it possible to
 * query data from the other application application.
 *
 * @package     WPGH
 * @subpackage  Classes/API
 *
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WPGH_API_V2_CONTACTS Class
 *
 * Renders API returns as a JSON
 *
 * @since  1.5
 */
class WPGH_API_V2_CONTACTS extends WPGH_API_V2_BASE
{

    public function __construct()
    {
        //initialize api if user check the api section
        add_action('rest_api_init', array( $this, 'register_routs' ) );
    }

    public function register_routs()
    {
        register_rest_route('gh/v2', '/contact', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_contact'),
                'permission_callback' => array($this, 'rest_authentication'),
                'args'=> array(
                    'contact_id' => array(
                        'required'    => false,
                        'description' => __('The ID of contact you want to retrieve.','groundhogg')
                    )
                )
            ),
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_contact'),
                'permission_callback' => array($this, 'rest_authentication'),
                'args'=> array(
                    'contact' => array(
                        'required'    => true,
                        'description' => __('Contains list of contact arguments. Please visit www.groundhogg.io for full list of accepted argument.','groundhogg')
                    )
                )
            ),

            array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'delete_contact'),
                'permission_callback' => array($this, 'rest_authentication'),
                'args'=> array(
                    'contact_id' => array(
                        'required'    => true,
                        'description' => __('Contact ID which you want to delete.','groundhogg')

                    )
                )
            ),
            array(
                'methods' => 'PUT, PATCH',
                'callback' => array($this, 'update_contact'),
                'permission_callback' => array($this, 'rest_authentication'),
                'args'=> array(
                    'contact' => array(
                        'description' => __('Contains list of contact argument. Please visit www.groundhogg.io for full list of accepted argument.','groundhogg')
                    ),
                    'apply_tags' => array(
                        'description' => __('Contains array of tags and contact id for applying tags to contact. Please visit www.groundhogg.io for more details.','groundhogg')
                    ),
                    'remove_tags' => array(
                        'description' => __('Contains array of tags and contact id for removing tags from contact.Please visit www.groundhogg.io for more details.','groundhogg')
                    )
                )
            ),
        ));

    }

    //GET METHOD
    public function get_contact(WP_REST_Request $request)
    {
        if ( ! user_can( $request['wpgh_user_id'], 'view_contacts' ) ){
            return new WP_Error('error', __( 'You are not eligible to perform this operation.','groundhogg' ) );
        }

        $returncontact = array();
        // method to get contact from  GroundHogg

        $search_args = array();

        if (isset ($request['contact_id'])) {
            $search_args[ 'ID' ] = intval( $request['contact_id'] );
        }

        if (isset ($request['email'])) {
            $search_args[ 'email' ] = sanitize_email( $request['email'] );
        }

        if (isset ($request['first_name'])) {
            $search_args[ 'first_name' ] = sanitize_text_field( $request['first_name'] );
        }

        if (isset ($request['last_name'])) {
            $search_args[ 'last_name' ] = sanitize_text_field( $request['last_name'] );
        }

        if (isset ($request['optin_status'])) {
            $search_args[ 'optin_status' ] = intval( $request['optin_status'] );
        }

        if (isset ($request['owner'])) {
            $search_args[ 'owner' ] = intval( $request['owner'] );
        }

        if (isset ($request['user_id'])) {
            $search_args[ 'user_id' ] = intval( $request['user_id'] );
        }

        if ( isset( $request[ 'query' ] ) ){
            $search_args = $request[ 'query' ];
        }

        $contacts = WPGH()->contacts->get_contacts( $search_args );

        if (count($contacts) > 0) {

            foreach ($contacts as $contact) {
                $contact_meta = WPGH()->contact_meta->get_meta($contact->ID);

                foreach ( $contact_meta as $key => $value ){
                    $contact_meta[ $key ] = array_pop( $value);
                }

                $contact->contact_meta = $contact_meta;
                $returncontact[] = array(
                    'contact' => $contact
                );
            }

            if ( count( $returncontact ) === 1 ){
                $returncontact = array_pop( $returncontact );
            }
            return rest_ensure_response($returncontact);

        } else {

            return new WP_Error('error', __('No contact found.','groundhogg' ) );

        }
    }

    //POST METHOD
    public function create_contact(WP_REST_Request $request)
    {
        if ( ! user_can( $request['wpgh_user_id'], 'add_contacts' ) ){
            return new WP_Error('error', __('You are not eligible to perform this operation.' ,'groundhogg') );
        }
        $contact_meta = null;
        $parameters = $request->get_json_params();
        if ( isset( $parameters['contact']['meta'] ) ) {
            $contact_meta = $parameters['contact']['meta'];
            unset($parameters['contact']['meta']);
        }
        $contact_detail = $parameters['contact'];
        if( isset( $parameters['contact']['email'] ) ) {
            //validate email address
            if ( is_email($parameters['contact']['email']) === false ) {
                return new WP_Error('error', __('Please enter valid email address to add new contact.','groundhogg') );
            }
            //  ---------------  Insert operation --------
            $data_array = array_map('sanitize_text_field', $contact_detail);
            //adding data in contact table
            $contact_id = WPGH()->contacts->add( $data_array );
            // insert data in contact meta table if users send meta data
            if ( $contact_meta !== null ) {
                $data_meta = $contact_meta;
                foreach( $data_meta as $key => $value ) {
                    WPGH()->contact_meta->add_meta( $contact_id, sanitize_key( $key ), sanitize_text_field( $value ) );
                }
            }
            return rest_ensure_response(array(
                'code' => 'success',
                'message' => __('Contact Added successfully.' ,'groundhogg'),
                'contact_id' => $contact_id
            ));
        } else {
            return new WP_Error('error', __('Please enter email address to for new contact.','groundhogg' ) );
        }
    }
    //PUT METHOD
    public function update_contact(WP_REST_Request $request)
    {
        if ( ! user_can( $request['wpgh_user_id'], 'edit_contacts' ) ){
            return new WP_Error('error', __( 'You are not eligible to perform this operation.','groundhogg' ));
        }
        $parameters = $request->get_json_params();
        if ( isset( $parameters['apply_tags'] ) )        {
            if ( isset( $parameters['apply_tags']['contact_id'] ) ) {
                if ( isset( $parameters['apply_tags']['tags'] ) ) {
                    if (WPGH()->contacts->count( array( 'ID' =>  intval( $parameters['apply_tags']['contact_id'] ) ) ) > 0) {
                        $contact = wpgh_get_contact( intval( $parameters['apply_tags']['contact_id'] ) );
                        $tags = array_map('sanitize_text_field', $parameters['apply_tags']['tags']);

                        $result = $contact->add_tag( $tags );
                        if ( $result ) {
                            return rest_ensure_response(array(
                                'code' => 'success',
                                'message' => __('Tags applied successfully.','groundhogg')
                            ));
                        } else {
                            return new WP_Error('error', __('something went wrong!','groundhogg' ) );
                        }
                    } else {
                        return new WP_Error('error', __('Entered contact_id not found.','groundhogg') );
                    }
                } else {
                    return new WP_Error('error', __('Please enter array of tags.','groundhogg' ) );
                }
            } else {
                return new WP_Error('error', __('Please enter contact_id','groundhogg' ) );
            }
        }

        if(isset($parameters['remove_tags']))
        {
            if (isset($parameters['remove_tags']['contact_id'])) {

                if (isset($parameters['remove_tags']['tags'])) {
                    if (WPGH()->contacts->count( array( 'ID' => intval( $parameters['remove_tags']['contact_id'] ) ) ) > 0) {
                        $contact = wpgh_get_contact( intval( $parameters['remove_tags']['contact_id'] ) );
                        $tags = array_map('sanitize_text_field', $parameters['remove_tags']['tags']);
                        $result = $contact->remove_tag( $tags );
                        if ( $result ) {
                            return rest_ensure_response(array(
                                'code' => 'success',
                                'message' => __('Tags removed successfully.','groundhogg')
                            ));
                        } else {
                            return new WP_Error('error', __('something went wrong!','groundhogg'));
                        }
                    } else {
                        return new WP_Error('error', __('Entered contact_id not found.','groundhogg'));
                    }
                } else {
                    return new WP_Error('error', __('Please enter array of tags.','groundhogg'));
                }
            } else {
                return new WP_Error('error', __('Please enter contact_id','groundhogg'));
            }
        }

        if ( isset ($parameters['contact']['contact_id'])) {// check user enter contact id for operation
            $contact_id = intval( $parameters['contact']['contact_id'] );
            unset($parameters['contact']['contact_id']);
            //$contact = WPGH()->contacts->get_contacts(array('ID' => $contact_id));
            if ( WPGH()->contacts->count( array( 'ID' => $contact_id ) ) > 0 ) { //check id exist in databse

                if ( ( isset($parameters['contact']['email'] ) ) && ( WPGH()->contacts->exists( sanitize_email( $parameters['contact']['email'] ) ) ) ) {//if email already exist in database and user wants to update it..
                    //validate email address
                    if( is_email($parameters['contact']['email']) === false ) {
                        return new WP_Error('error', __('Please enter valid email address to add new contact.' ,'groundhogg') );
                    }
                    unset( $parameters['contact']['email'] );
                }
                $update = 0 ;
                if ( isset( $parameters['contact']['meta'] ) ) {// update meta
                    //$data_meta = $data->meta;
                    foreach ($parameters['contact']['meta'] as $key => $value) {
                        WPGH()->contact_meta->update_meta($contact_id, sanitize_key($key), sanitize_text_field($value));
                        $update++;
                    }
                    unset($parameters['contact']['meta']);
                }
                //update contact table
                $data_array = $parameters['contact'];
                $data_array = array_map('sanitize_text_field', $data_array);
                if (count($data_array) > 0) {
                    // update data only if there is a data
                    WPGH()->contacts->update($contact_id, $data_array);
                    $update++;
                }
                if ($update > 0) {
                    return rest_ensure_response(array(
                        'code' => 'success',
                        'message' => __( 'Contact Updated successfully.','groundhogg')
                    ));
                }
            } else {
                return new WP_Error('error', __('No contact exists with the given ID.','groundhogg'));
            }
        } else {// response to enter contact_id
            return new WP_Error('error', __('Please enter a contact ID.','groundhogg'));
        }
    }

    //DELETE METHOD
    public function delete_contact( WP_REST_Request $request )
    {// function invoked if user wants to delete one contact
        if( ! user_can( $request['wpgh_user_id'], 'delete_contacts' ) ){
            return new WP_Error('error', __('you are not eligible to perform this operation.'));
        }
        if( isset( $request['contact_id'] ) ) {
            $contact_id = intval( $request['contact_id'] );
            // ----------- code to delete contact
            if ( WPGH()->contacts->count( array( 'ID' => $contact_id) ) > 0) {
                if ( WPGH()->contacts->delete( array('ID' => $contact_id))) {
                    return rest_ensure_response(array(
                        'code' => 'success',
                        'message' => __('Contact deleted successfully.','groundhogg')
                    ));
                } else {
                    return new WP_Error('error', __( 'Something went wrong','groundhogg' ) );
                }
            } else {

                return new WP_Error('error', __('No contact found with entered contact_id','groundhogg') );
            }

        } else {

            return new WP_Error('error', __('Please enter Contact ID to perform this operation.','groundhogg'));
        }

    }

}