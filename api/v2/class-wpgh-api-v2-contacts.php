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
        add_action('rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routse()
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
                        'description' => __('Contains list of contact arguments. Please visit www.groundhogg.io for full list of accepted arguments.','groundhogg')
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
                        'description' => __('ID of the contact which you want to delete.','groundhogg')

                    )
                )
            ),
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_contact'),
                'permission_callback' => array($this, 'rest_authentication'),
                'args'=> array(
                    'contact_id' => array(
                        'required'    => true,
                        'description' => __('The ID of the contact to update.','groundhogg')
                    ),
                    'contact'    => array(
                        'required'    => false,
                        'description' => __('Array of updated contact details.','groundhogg')
                    ),
                    'apply_tags' => array(
                        'required'    => false,
                        'description' => __('Contains array of tag ids/slugs for which you want to apply to the contact.','groundhogg')
                    ),
                    'remove_tags' => array(
                        'required'    => false,
                        'description' => __('Contains array of tag ids/slugs for which you want to remove from the contact.','groundhogg')
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

            return new WP_Error('error', __('Please provide a valid contact ID.','groundhogg' ) );

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
                return new WP_Error('error', __('Please provide a valid email address.','groundhogg') );
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
                'message' => __('Contact added successfully.' ,'groundhogg'),
                'contact_id' => $contact_id
            ));
        } else {
            return new WP_Error('error', __('Please enter a valid email address.','groundhogg' ) );
        }
    }

	/**
	 * Updates a contact given a conatct array
	 * Can also apply & remove tags
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
    public function update_contact(WP_REST_Request $request)
    {
        if ( ! user_can( $request['wpgh_user_id'], 'edit_contacts' ) ){
            return new WP_Error('error', __( 'You are not eligible to perform this operation.','groundhogg' ));
        }

        $parameters = $request->get_params();
        $contact_id = intval( $request->get_param( 'contact_id' ) );
        $result = false;

        if ( ! $contact_id || ! WPGH()->contacts->exists( $contact_id, 'ID' ) ){
	        return new WP_Error('INVALID_ID', __( 'Please provide a valid contact ID.','groundhogg' ));
        }

        $contact = wpgh_get_contact( $contact_id );

        /* UPDATE CONTACT */
        if ( $request->get_param( 'contact' ) ){

        	$updated_contact_args = $request->get_param( 'contact' );

        	/* Update the meta data and the remove it from the general contact query */
        	if ( isset( $updated_contact_args[ 'meta' ] ) && is_array( $updated_contact_args[ 'meta' ] ) ){
		        $updated_meta = $updated_contact_args[ 'meta' ];

		        foreach ($updated_meta as $key => $value ){
		        	$contact->update_meta( sanitize_key( $key ), sanitize_textarea_field( $value ) );
		        }

		        unset( $updated_contact_args[ 'meta' ] );
	        }

	        if ( isset( $updated_contact_args[ 'email' ] ) && ! is_email( isset( $updated_contact_args[ 'email' ] ) ) ){
		        return new WP_Error('INVALID_EMAIL', __( 'Please provide a valid email address.','groundhogg' ));
	        }

	        /* Check if email address already belongs to another contact */
	        if ( isset( $updated_contact_args[ 'email' ] ) && $contact->email !== $updated_contact_args[ 'email' ] && WPGH()->contacts->exists( $updated_contact_args[ 'email' ] ) ){
		        return new WP_Error('EMAIL_IN_USE', __( 'This email address is already being used by another contact.','groundhogg' ));
	        }

	        $updated_contact_args = array_map( 'sanitize_text_field', $updated_contact_args );

	        $result = $contact->update( $updated_contact_args );

        }

        /* APPLY TAGS */
        if ( isset( $parameters['apply_tags'] ) ){
            $tags = array_map('sanitize_text_field', $parameters['apply_tags']);
            $result = $contact->add_tag( $tags );

            if ( ! $result ) {
	            return new WP_Error('APPLY_TAG_ERROR', __( 'Could not apply tags.', 'groundhogg' ) );
            }
        }

        /* REMOVE TAGS */
        if(isset($parameters['remove_tags'])) {
            $tags = array_map('sanitize_text_field', $parameters['remove_tags']['tags']);
            $result = $contact->remove_tag( $tags );
            if ( ! $result ) {
                return new WP_Error('REMOVE_TAG_ERROR', __('Could not remove tags.','groundhogg'));
            }
        }

        if ( $result ){
	        return rest_ensure_response( array(
		        'code' => 'success',
		        'message' => __( 'Contact updated successfully.','groundhogg')
	        ));
        } else {
	        return new WP_Error('ERROR', __('Could not update contact.','groundhogg'));
        }
    }

    //DELETE METHOD
    public function delete_contact( WP_REST_Request $request )
    {// function invoked if user wants to delete one contact
        if( ! user_can( $request['wpgh_user_id'], 'delete_contacts' ) ){
            return new WP_Error('error', __('You are not eligible to perform this operation.'));
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
                    return new WP_Error('error', __( 'Something went wrong.','groundhogg' ) );
                }
            } else {

                return new WP_Error('error', __('Please provide a valid contact ID.','groundhogg') );
            }

        } else {

            return new WP_Error('error', __('Please provide a valid contact ID.','groundhogg'));
        }

    }

}