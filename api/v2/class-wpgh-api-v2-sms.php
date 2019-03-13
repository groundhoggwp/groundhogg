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
class WPGH_API_V2_SMS extends WPGH_API_V2_BASE
{
    public function register_routes()
    {
        register_rest_route('gh/v2', '/sms', array(
            array(
                // By using this constant we ensure that when the WP_REST_Server changes, our readable endpoints will work as intended.
                'methods' => WP_REST_Server::READABLE,
                // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
                'callback' => array($this, 'get_sms'),
                'permission_callback' => array($this, 'rest_authentication'),
            )
        ));

        register_rest_route('gh/v2', '/sms/send' ,array(
            // By using this constant we ensure that when the WP_REST_Server changes, our create endpoints will work as intended.
            'methods' => WP_REST_Server::CREATABLE,
            // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
            'callback' => array($this, 'send_sms'),
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
                'sms_id' => array(
                    'required'    => true,
                    'description' => _x( 'SMS ID which you want to send.', 'api', 'groundhogg' ),
                )
            )
        ));

    }

    //GET METHOD
    public function get_sms( WP_REST_Request $request )
    { // GET list of available LIST of EMAILS

        $response  = array();
        $search_args = array();

        if (isset ($request['sms_id'])) {
            $id = intval( $request['sms_id'] );
            $data = array( WPGH()->sms->get( $id ) );
        } else if (isset ($request['search'])) {
            $search_args[ 'search' ] = sanitize_text_field( $request['search'] );
            $data = WPGH()->sms->get_smses( $search_args );
        } else {
            $data = WPGH()->sms->get_all_sms();
        }

        if ( count( $data ) > 0) {

            foreach ($data as $sms) {
                $response[] = array(
                    "sms" => $sms
                );
            }

            return rest_ensure_response( $response ) ;
        } else {
            return new WP_Error('error', _x( 'No sms found.', 'api', 'groundhogg' ), [ 'status' => 400 ] );
        }
    }

    public function send_sms( WP_REST_Request $request )
    { // GET list of available LIST of EMAILS
        //check for contact_id and sms_id
        if( isset( $request['sms_id'] ) && isset( $request['contact_id'] ) ) {
            $sms_id   = intval( $request['sms_id'] );
            $id_or_email = $request['id_or_email'];
            $by_user_id = filter_var( $request->get_param( 'by_user_id' ), FILTER_VALIDATE_BOOLEAN );

            $contact = wpgh_get_contact( $id_or_email, $by_user_id );


            if( !WPGH()->sms->exists( $sms_id ) ) {
                return new WP_Error('error', sprintf( _x( 'SMS with ID %d not found.', 'api', 'groundhogg' ), $sms_id ), [ 'status' => 400 ] );
            }

            if( ! $contact ) {
                return new WP_Error('error', sprintf( _x( 'Contact with ID %d not found.', 'api', 'groundhogg' ), $id_or_email ), [ 'status' => 400 ] );
            }

            $status = wpgh_send_sms_notification( $sms_id, $contact->ID );

            if( $status ) {
                return rest_ensure_response(array(
                    'status' => 'success',
                    'message' => _x( 'SMS sent successfully to contact.', 'api', 'groundhogg' )
                ));
            } else {
                return new WP_Error('error', _x( 'SMS not sent.', 'api', 'groundhogg' ), [ 'status' => 500 ]);
            }

        } else {
            return new WP_Error('error', _x( 'sms_id and contact_id are required to perform this operation.', 'api', 'groundhogg' ), [ 'status' => 400 ] );
        }

    }

}