<?php
/**
 * Groundhogg API Emails
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
 * WPGH_API_V3_EMAILS Class
 *
 * Renders API returns as a JSON
 *
 * @since  1.5
 */
class WPGH_API_V3_EMAILS extends WPGH_API_V3_BASE
{

    public function register_routes()
    {

        $auth_callback = $this->get_auth_callback();

        register_rest_route('gh/v3', '/emails', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [ $this, 'get_emails' ],
                'permission_callback' => $auth_callback,
                'args' => [
                    'query' => [
                        'description' => _x( 'Any search parameters.', 'api', 'groundhogg' )
                    ]
                ]
            ]
        ] );

        register_rest_route('gh/v3', '/emails/send' ,array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [ $this, 'send_email' ],
            'permission_callback' => $auth_callback,
            'args'=> array(
                'id_or_email' => [
                    'required'    => true,
                    'description' => _x('The ID or email of the contact you want to send email to.','api','groundhogg'),
                ],
                'by_user_id' => [
                    'required'    => false,
                    'description' => _x( 'Search using the user ID.', 'api', 'groundhogg' ),
                ],
                'email_id' => [
                    'required'    => true,
                    'description' => _x( 'Email ID which you want to send.', 'api', 'groundhogg' ),
                ]
            )
        ));

    }

    /**
     * Get a list of emails which match a given query
     *
     * @param WP_REST_Request $request
     * @return WP_Error|WP_REST_Response
     */
    public function get_emails( WP_REST_Request $request )
    {
        if ( ! current_user_can( 'edit_emails' ) ){
            return self::ERROR_INVALID_PERMISSIONS();
        }

        $query = (array) $request->get_param( 'query' );

        if ( empty( $query ) ){
            $query = [];
        }

        $emails = WPGH()->emails->get_emails( $query );

        if ( empty( $emails ) ){
            return self::ERROR_404( 'no_emails', 'No emails matched the provided query.' );
        }

        return self::SUCCESS_RESPONSE( [ 'emails' => $emails ] );
    }

    /**
     * Send an email to the provided contact
     *
     * @param WP_REST_Request $request
     * @return WP_Error|WP_REST_Response
     */
    public function send_email( WP_REST_Request $request )
    {
        if ( ! current_user_can( 'send_emails' ) ) {
            return self::ERROR_INVALID_PERMISSIONS();
        }

        $contact = self::get_contact_from_request( $request );

        if( is_wp_error( $contact ) ) {
            return $contact;
        }

        $email_id = intval( $request->get_param( 'email_id' ) );

        if( ! WPGH()->emails->exists( $email_id ) ) {
            return self::ERROR_400('no_email', sprintf( _x( 'Email with ID %d not found.', 'api', 'groundhogg' ), $email_id ) );
        }

        $status = wpgh_send_email_notification( $email_id, $contact->ID );

        if( ! $status ) {
            return self::ERROR_UNKNOWN();
        }

        return self::SUCCESS_RESPONSE( [], _x( 'Email sent successfully to contact.', 'api', 'groundhogg' ) );
    }

}