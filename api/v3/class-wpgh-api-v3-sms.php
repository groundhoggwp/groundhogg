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

        register_rest_route(self::NAMESPACE, '/sms', [
            [
	            'methods' => WP_REST_Server::READABLE,
	            'callback' => [ $this, 'get_sms' ],
	            'permission_callback' => $auth_callback,
	            'args' => [
		            'query' => [
			            'description' => _x( 'Any search parameters.', 'api', 'groundhogg' )
		            ],
		            'select' => [
			            'required'    => false,
			            'description' => _x( 'Whether to retrieve as available for a select input.', 'api', 'groundhogg' ),
		            ],
		            'select2' => [
			            'required'    => false,
			            'description' => _x( 'Whether to retrieve as available for an ajax select2 input.', 'api', 'groundhogg' ),
		            ],
		            'search' => [
			            'required'    => false,
			            'description' => _x( 'Search string for tag name.', 'api', 'groundhogg' ),
		            ],
		            'q' => [
			            'required'    => false,
			            'description' => _x( 'Shorthand for search.', 'api', 'groundhogg' ),
		            ],
	            ]
            ]
        ] );

        register_rest_route(self::NAMESPACE, '/sms/send' ,array(
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

	    $query =  $request->get_param( 'query' ) ? (array) $request->get_param( 'query' ) : [];

	    $search = $request->get_param( 'q' ) ? $request->get_param( 'q' ) : $request->get_param( 'search' ) ;
	    $search = sanitize_text_field( stripslashes( $search ) );

	    if ( ! key_exists( 'search', $query ) && ! empty( $search ) ){
		    $query[ 'search' ] = $search;
	    }

	    $is_for_select = filter_var( $request->get_param( 'select' ), FILTER_VALIDATE_BOOLEAN );
	    $is_for_select2 = filter_var( $request->get_param( 'select2' ), FILTER_VALIDATE_BOOLEAN );

	    $sms = WPGH()->sms->get_smses( $query );

	    if ( $is_for_select2 ){
		    $json = array();

		    foreach ( $sms as $i => $sms_single ) {

			    $json[] = array(
				    'id' => $sms_single->ID,
				    'text' => $sms_single->title
			    );

		    }

		    $results = array( 'results' => $json, 'more' => false );

		    return rest_ensure_response( $results );
	    }

	    if ( $is_for_select ){

		    $response_sms = [];

		    foreach ( $sms as $i => $sms_single ) {
			    $response_sms[ $sms_single->ID ] = $sms_single->title;
		    }

		    $sms = $response_sms;

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