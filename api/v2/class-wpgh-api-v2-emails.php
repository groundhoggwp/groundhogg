<?php
/**
 * Groundhogg API tags
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
class WPGH_API_V2_EMAILS extends WPGH_API_V2_BASE
{
    public function __construct()
    {
        //initialize api if user check the api section
        add_action('rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes()
    {
        register_rest_route('gh/v2', '/emails', array(
            array(
                // By using this constant we ensure that when the WP_REST_Server changes, our readable endpoints will work as intended.
                'methods' => WP_REST_Server::READABLE,
                // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
                'callback' => array($this, 'get_emails'),
                'permission_callback' => array($this, 'rest_authentication'),
            )
        ));

        register_rest_route('gh/v2', '/emails/send' ,array(
            // By using this constant we ensure that when the WP_REST_Server changes, our create endpoints will work as intended.
            'methods' => WP_REST_Server::CREATABLE,
            // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
            'callback' => array($this, 'send_email'),
            'permission_callback' => array($this, 'rest_authentication'),
            'args'=> array(
                'id_or_email' => array(
                    'required'    => true,
                    'description' => _x( 'Contact ID, User ID, or Email Address which you want to send to.', 'api', 'groundhogg' ),
                ),
                'by_user_id' => array(
                    'required'    => false,
                    'description' => _x( 'Search using the User ID.', 'api', 'groundhogg' ),
                ),
                'email_id' => array(
                    'required'    => true,
                    'description' => _x( 'Email ID which you want to send.', 'api', 'groundhogg' ),
                )
            )
        ));

    }

    //GET METHOD
    public function get_emails( WP_REST_Request $request )
    { // GET list of available LIST of EMAILS

        $response  = array();
        $search_args = array();

        if (isset ($request['email_id'])) {
            $id = intval( $request['email_id'] );
            $data = array( WPGH()->emails->get( $id ) );
        } else if (isset ($request['search'])) {
            $search_args[ 'search' ] = sanitize_text_field( $request['search'] );
            $data = WPGH()->emails->get_emails( $search_args );
        } else {
            $data = WPGH()->emails->get_emails();
        }

        if ( count( $data ) > 0) {

            foreach ($data as $email) {
                $response[] = array(
                    "email" => $email
                );
            }

            return rest_ensure_response( $response ) ;
        } else {
            return new WP_Error('error', _x( 'No emails found.', 'api', 'groundhogg' ), [ 'status' => 400 ] );
        }
    }

    public function send_email( WP_REST_Request $request )
    {

        if( isset( $request['email_id'] ) && isset( $request['id_or_email'] ) ) {
            $email_id   = intval( $request['email_id'] );
            $id_or_email = $request['id_or_email'];
            $by_user_id = filter_var( $request->get_param( 'by_user_id' ), FILTER_VALIDATE_BOOLEAN );

            $contact = wpgh_get_contact( $id_or_email, $by_user_id );


            if( ! $contact ) {
                return new WP_Error('no_contact', sprintf( _x( 'Contact was not found given: %s', 'api', 'groundhogg' ), $id_or_email ), [ 'status' => 400 ] );
            }

            if( !WPGH()->emails->exists( $email_id ) ) {
                return new WP_Error('no_email', sprintf( _x( 'Email with ID %d not found.', 'api', 'groundhogg' ), $email_id ), [ 'status' => 400 ] );
            }

            $status = wpgh_send_email_notification( $email_id, $contact->ID );

            if( $status ) {
                return rest_ensure_response(array(
                    'status' => 'success',
                    'message' => _x( 'Email sent successfully to contact.', 'api', 'groundhogg' )
                ));
            } else {
                return new WP_Error('send_error', _x( 'Email not sent.', 'api', 'groundhogg' ) , [ 'status' => 500 ] );
            }

        } else {
            return new WP_Error('invalid_request', _x( 'email_id and contact_id are required to perform this operation.', 'api', 'groundhogg' ), [ 'status' => 400 ] );
        }

    }

}