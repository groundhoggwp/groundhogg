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
    public function __construct()
    {
        //initialize api if user check the api section
        add_action('rest_api_init', array( $this, 'register_routes' ) );
    }

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
            'methods' => WP_REST_Server::READABLE,
            // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
            'callback' => array($this, 'send_sms'),
            'permission_callback' => array($this, 'rest_authentication'),
            'args'=> array(
                'contact_id' => array(
                    'required'    => true,
                    'description' => _x( 'Contact ID which you want to send to.', 'api', 'groundhogg' ),
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
            return new WP_Error('error', _x( 'No sms found.', 'api', 'groundhogg' ) );
        }
    }

    public function send_sms( WP_REST_Request $request )
    { // GET list of available LIST of EMAILS
        //check for contact_id and sms_id
        if( isset( $request['sms_id'] ) && isset( $request['contact_id'] ) ) {
            $sms_id   = intval( $request['sms_id'] );
            $contact_id = intval( $request['contact_id'] );

            if( !WPGH()->sms->exists( $sms_id ) ) {
                return new WP_Error('error', sprintf( _x( 'SMS with ID %d not found.', 'api', 'groundhogg' ), $sms_id ) );
            }
            if( !WPGH()->contacts->exists( $contact_id , 'ID' ) ) {
                return new WP_Error('error', sprintf( _x( 'Contact with ID %d not found.', 'api', 'groundhogg' ), $contact_id ) );
            }
            $status = wpgh_send_sms_notification( $sms_id, $contact_id );
            if( $status ) {
                return rest_ensure_response(array(
                    'code' => 'success',
                    'message' => _x( 'SMS sent successfully to contact.', 'api', 'groundhogg' )
                ));
            } else {
                return new WP_Error('error', _x( 'SMS not sent.', 'api', 'groundhogg' ));
            }

        } else {
            return new WP_Error('error', _x( 'sms_id and contact_id are required to perform this operation.', 'api', 'groundhogg' ) );
        }

    }

}