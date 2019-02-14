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
            'methods' => WP_REST_Server::READABLE,
            // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
            'callback' => array($this, 'send_email'),
            'permission_callback' => array($this, 'rest_authentication'),
            'args'=> array(
                'contact_id' => array(
                    'required'    => true,
                    'description' => _x( 'Contact ID which you want to send to.', 'api', 'groundhogg' ),
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
            return new WP_Error('error', _x( 'No emails found.', 'api', 'groundhogg' ) );
        }
    }

    public function send_email( WP_REST_Request $request )
    { // GET list of available LIST of EMAILS
        //check for contact_id and email_id
        if( isset( $request['email_id'] ) && isset( $request['contact_id'] ) ) {
            $email_id   = intval( $request['email_id'] );
            $contact_id = intval( $request['contact_id'] );

            if( !WPGH()->emails->exists( $email_id ) ) {
                return new WP_Error('error', sprintf( _x( 'Email with ID %d not found.', 'api', 'groundhogg' ), $email_id ) );
            }
            if( !WPGH()->contacts->exists( $contact_id , 'ID' ) ) {
                return new WP_Error('error', sprintf( _x( 'Contact with ID %d not found.', 'api', 'groundhogg' ), $contact_id ) );
            }
            $email      = new WPGH_Email( $email_id );
            $status     = $email->send( $contact_id );
            if( $status ) {
                return rest_ensure_response(array(
                    'code' => 'success',
                    'message' => _x( 'Email sent successfully to contact.', 'api', 'groundhogg' )
                ));
            } else {
                return new WP_Error('error', _x( 'Email not sent.', 'api', 'groundhogg' ));
            }

        } else {
            return new WP_Error('error', _x( 'email_id and contact_id are required to perform this operation.', 'api', 'groundhogg' ) );
        }

    }

}