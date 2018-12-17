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
                // By using this constant we ensure that when the WP_REST_Server changes, our readable endpoints will work as intended.
                'methods' => WP_REST_Server::READABLE,
                // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
                'callback' => array($this, 'get_contact'),
                'permission_callback' => array($this, 'rest_authentication'),
                'args'=> array(
                    'contact_id' => array(
                        'required'    => false,
                        'description' => 'The ID of contact you want to retrieve.',

                    )
                )
            ),
            array(
                // By using this constant we ensure that when the WP_REST_Server changes, our create endpoints will work as intended.
                'methods' => WP_REST_Server::CREATABLE,
                // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
                'callback' => array($this, 'create_contact'),
                'permission_callback' => array($this, 'rest_authentication'),
                'args'=> array(
                    'contact' => array(
                        'required'    => true,
                        'description' => 'Contains list of contact argument. Please visit www.groundhogg.io for full list of accepted argument.',
                    )
                )
            ),

            array(
                // By using this constant we ensure that when the WP_REST_Server changes, our create endpoints will work as intended.
                'methods' => WP_REST_Server::DELETABLE,
                // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
                'callback' => array($this, 'delete_contact'),
                'permission_callback' => array($this, 'rest_authentication'),
                'args'=> array(
                    'contact_id' => array(
                        'required'    => true,
                        'description' => 'Contact ID which you want to delete.',

                    )
                )
            ),
            array(
                // By using this constant we ensure that when the WP_REST_Server changes, our create endpoints will work as intended.
                'methods' => 'PUT, PATCH',
                // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
                'callback' => array($this, 'update_contact'),
                'permission_callback' => array($this, 'rest_authentication'),
                'args'=> array(
                    'contact' => array(
                        'description' => 'Contains list of contact argument. Please visit www.groundhogg.io for full list of accepted argument.',
                    ),
                    'apply_tags' => array(
                        'description' => 'contains list of tags and contact id for applying tags to contact. Please visit www.groundhogg.io for more details.',
                    ),
                    'remove_tags' => array(
                        'description' => 'contains list of tags and contact id for removing tags from contact.Please visit www.groundhogg.io for more details.',
                    )
                )
            ),
        ));

    }

    //GET METHOD
    public function get_contact(WP_REST_Request $request)
    {
        if ( ! user_can( $request['wpgh_user_id'], 'view_contacts' ) ){
            return new WP_Error('error', __('you are not eligible to perform this operation.'));
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
                    $contact_meta[ $key ] = array_pop( $value );
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

            return new WP_Error('error', 'No contact found.', array('status' => 404));

        }
    }

    //POST METHOD
    public function create_contact(WP_REST_Request $request)
    {
        if ( ! user_can( $request['wpgh_user_id'], 'add_contacts' ) ){
            return new WP_Error('error', __('you are not eligible to perform this operation.'));

        }

        $contact_meta = null;

        $parameters = $request->get_json_params();
        if (isset ($parameters['contact']['contact_meta'])) {
            $contact_meta = $parameters['contact']['contact_meta'];
            unset($parameters['contact']['contact_meta']);
        }
        $contact_detail = $parameters['contact'];

        if (isset($parameters['contact']['email'])) {

            //validate email address
            if( is_email($parameters['contact']['email']) === false ) {
                return new WP_Error('error', __('Please enter valid email address to add new contact.') );
            }

            //  ---------------  Insert operation --------

            $data_array = array_map('sanitize_text_field', $contact_detail);

            //adding data in contact table
            $contact_id = WPGH()->contacts->add($data_array);

            // insert data in contact meta table if users send meta data
            if ($contact_meta !== null) {
                $data_meta = $contact_meta;
                foreach ($data_meta as $key => $value) {
                    WPGH()->contact_meta->add_meta($contact_id, $key, $value);
                }
            }
            return rest_ensure_response(array(
                'code' => 'success',
                'message' => __('Contact Added successfully.'),
                'contact_id' => $contact_id
            ));

        } else {

            return new WP_Error('error', __('Please enter email address to add new contact.'), array('status' => 404));
        }

    }

    //PUT METHOD
    public function update_contact(WP_REST_Request $request)
    {
        if ( ! user_can( $request['wpgh_user_id'], 'edit_contacts' ) ){
            return new WP_Error('error', __('you are not eligible to perform this operation.'));
        }

        $parameters = $request->get_json_params();

        if(isset($parameters['apply_tags']))
        {
            if(isset($parameters['apply_tags']['contact_id'])) {

                if (isset($parameters['apply_tags']['tags'])) {
                    if (WPGH()->contacts->count(array('ID' => $parameters['apply_tags']['contact_id'])) > 0) {
                        $contact = wpgh_get_contact( $parameters['apply_tags']['contact_id']);
                        $result = $contact->add_tag( $parameters['apply_tags']['tags'] );
                        if ( $result ) {
                            return rest_ensure_response(array(
                                'code' => 'success',
                                'message' => 'tag(s) applied successfully.'
                            ));
                        } else {
                            return new WP_Error('error', __('something went wrong!'));
                        }
                    } else {
                        return new WP_Error('error', __('Entered contact_id not found.'));
                    }
                } else {
                    return new WP_Error('error', __('Please provide array of tags.'));
                }
            } else {
                return new WP_Error('error', __('Please provide contact_id'));
            }
        }

        if(isset($parameters['remove_tags']))
        {
            if(isset($parameters['remove_tags']['contact_id'])) {

                if (isset($parameters['remove_tags']['tags'])) {
                    if (WPGH()->contacts->count(array('ID' => $parameters['remove_tags']['contact_id'])) > 0) {
                        $contact = wpgh_get_contact( $parameters['remove_tags']['contact_id']);
                        $result = $contact->remove_tag( $parameters['remove_tags']['tags'] );
                        if ( $result ) {
                            return rest_ensure_response(array(
                                'code' => 'success',
                                'message' => 'tag(s) removed successfully.'
                            ));
                        } else {
                            return new WP_Error('error', __('something went wrong!'));
                        }
                    } else {
                        return new WP_Error('error', __('Entered contact_id not found.'));
                    }
                } else {
                    return new WP_Error('error', __('Please provide array of tags.'));
                }
            } else {
                return new WP_Error('error', __('Please provide contact_id'));
            }
        }

        if (isset ($parameters['contact']['contact_id'])) {// check user enter contact id for operation
            $contact_id = $parameters['contact']['contact_id'];
            unset($parameters['contact']['contact_id']);
            //$contact = WPGH()->contacts->get_contacts(array('ID' => $contact_id));
            if (WPGH()->contacts->count(array('ID' => $contact_id)) > 0) { //check id exist in databse

                if ((isset($parameters['contact']['email'])) && (WPGH()->contacts->exists($parameters['contact']['email']))) {//if email already exist in database and user wants to update it..

                    //validate email address
                    if( is_email($parameters['contact']['email']) === false ) {
                        return new WP_Error('error', __('Please enter valid email address to add new contact.') );
                    }

                    unset($parameters['contact']['email']);
                }
                $update = 0 ;

                if (isset($parameters['contact']['contact_meta'])) {// update meta
                    //$data_meta = $data->meta;
                    foreach ($parameters['contact']['contact_meta'] as $key => $value) {
                        WPGH()->contact_meta->update_meta($contact_id, sanitize_key($key), sanitize_text_field($value));
                        $update++;
                    }
                    unset($parameters['contact']['contact_meta']);
                }


                //update contact table
                $data_array = $parameters['contact'];

                $data_array = array_map('sanitize_text_field', $data_array);

                if (count($data_array) > 0) {
                    // update data only if there is a data
                    WPGH()->contacts->update($contact_id, $data_array);
                    $update++;
                }
                if($update > 0) {
                    return rest_ensure_response(array(
                        'code' => 'success',
                        'message' => 'Contact Updated successfully.'
                    ));
                }

            } else {
                return new WP_Error('error', __('No contact exists with the given ID.'));
            }

        } else {// response to enter contact_id
            return new WP_Error('error', __('Please provide a contact ID.'));
        }
    }

    //DELETE METHOD
    public function delete_contact(WP_REST_Request $request)
    {// function invoked if user wants to delete one contact

        if ( ! user_can( $request['wpgh_user_id'], 'delete_contacts' ) ){
            return new WP_Error('error', __('you are not eligible to perform this operation.'));
        }

        if (isset($request['contact_id'])) {
            $contact_id = $request['contact_id'];
            // ----------- code to delete contact
            if (WPGH()->contacts->count(array('ID' => $contact_id)) > 0) {
                if (WPGH()->contacts->delete(array('ID' => $contact_id))) {
                    return rest_ensure_response(array(
                        'code' => 'success',
                        'message' => 'contact deleted successfully.'
                    ));
                } else {
                    return new WP_Error('error', __('Something went wrong'));
                }
            } else {

                return new WP_Error('error', __('No contact found with provided contact_id') );
            }

        } else {

            return new WP_Error('error', __('Please enter Contact ID to perform this operation.'), array('status' => 404));
        }

    }

}