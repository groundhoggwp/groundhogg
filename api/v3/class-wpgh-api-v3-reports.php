<?php
/**
 * Groundhogg API REPORTS
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
 * WPGH_API_V3_REPORTS Class
 *
 * Renders API returns as a JSON
 *
 * @since  1.5
 */
class WPGH_API_V3_REPORTS extends WPGH_API_V3_BASE
{

    public function register_routes()
    {

        $auth_callback = $this->get_auth_callback();

        register_rest_route('gh/v3', '/reports', [
            [
	            'methods' => WP_REST_Server::READABLE,
	            'callback' => [ $this, 'get_reports' ],
	            'permission_callback' => $auth_callback,
	            'args' => [
		            'report_id' => [
			            'description' => _x( 'The ID of the report to retrieve.', 'api', 'groundhogg' )
		            ],
		            'date_range' => [
			            'description' => _x( 'The time period for which the report should be generated.', 'api', 'groundhogg' )
		            ],
		            'custom_start_date' => [
			            'description' => _x( 'A custom start date. The date_range must be set to `custom`.', 'api', 'groundhogg' )
		            ],
		            'custom_end_date' => [
			            'description' => _x( 'A custom end date. The date_range must be set to `custom`.', 'api', 'groundhogg' )
		            ],

	            ]
            ]
        ] );
    }

    /**
     * Get a list of sms which match a given query
     *
     * @param WP_REST_Request $request
     * @return WP_Error|WP_REST_Response
     */
    public function get_reports( WP_REST_Request $request )
    {
	    if ( ! current_user_can( 'view_reports' ) ){
		    return self::ERROR_INVALID_PERMISSIONS();
	    }

	    // todo, implement this.
    }

}