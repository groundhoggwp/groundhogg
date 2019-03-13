<?php
/**
 * Groundhogg API SMS
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
 * WPGH_API_V3_SMS Class
 *
 * Renders API returns as a JSON
 *
 * @since  1.5
 */
class WPGH_API_V3_SMS extends WPGH_API_V3_BASE
{

    public function register_routes()
    {

        $auth_callback = $this->get_auth_callback();

        register_rest_route('gh/v3', '/sms', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [ $this, 'get_sms' ],
                'permission_callback' => $auth_callback,
                'args' => [
                    'query' => [
                        'description' => _x( 'Any search parameters.', 'api', 'groundhogg' )
                    ]
                ]
            ]
        ] );

        register_rest_route('gh/v3', '/sms/send' ,array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [ $this, 'send_sms' ],
            'permission_callback' => $auth_callback,
            'args'=> array(
                'id_or_email' => [
                    'required'    => true,
                    'description' => _x('The ID or email of the contact you want to send sms to.','api','groundhogg'),
                ],
                'by_user_id' => [
                    'required'    => false,
                    'description' => _x( 'Search using the user ID.', 'api', 'groundhogg' ),
                ],
                'sms_id' => [
                    'required'    => true,
                    'description' => _x( 'SMS ID which you want to send.', 'api', 'groundhogg' ),
                ]
            )
        ));

    }

    /**
     * Get a list of sms which match a given query
     *
     * @param WP_REST_Request $request
     * @return WP_Error|WP_REST_Response
     */
    public function get_sms( WP_REST_Request $request )
    {
        if ( ! current_user_can( 'edit_sms' ) ){
            return self::ERROR_INVALID_PERMISSIONS();
        }

        $query = (array) $request->get_param( 'query' );

        if ( empty( $query ) ){
            $query = [];
        }

        $sms = WPGH()->sms->get_sms( $query );

        if ( empty( $sms ) ){
            return self::ERROR_404( 'no_sms', 'No sms matched the provided query.' );
        }

        return self::SUCCESS_RESPONSE( [ 'sms' => $sms ] );
    }

    /**
     * Send an sms to the provided contact
     *
     * @param WP_REST_Request $request
     * @return WP_Error|WP_REST_Response
     */
    public function send_sms( WP_REST_Request $request )
    {
        if ( ! current_user_can( 'send_sms' ) ) {
            return self::ERROR_INVALID_PERMISSIONS();
        }

        $contact = self::get_contact_from_request( $request );

        if( is_wp_error( $contact ) ) {
            return $contact;
        }

        $sms_id = intval( $request->get_param( 'sms_id' ) );

        if( ! WPGH()->sms->exists( $sms_id ) ) {
            return self::ERROR_400('no_sms', sprintf( _x( 'Email with ID %d not found.', 'api', 'groundhogg' ), $sms_id ) );
        }

        $status = wpgh_send_sms_notification( $sms_id, $contact->ID );

        if( ! $status ) {
            return self::ERROR_UNKNOWN();
        }

        return self::SUCCESS_RESPONSE( [], _x( 'SMS sent successfully to contact.', 'api', 'groundhogg' ) );
    }

}