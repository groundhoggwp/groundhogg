<?php
namespace Groundhogg\Api\V3;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

use function Groundhogg\get_db;
use Groundhogg\Plugin;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Reports_Api extends Base
{

    public function register_routes()
    {
        $callback = $this->get_auth_callback();

        // BASIC DB QUERY
        register_rest_route('gh/v3', '/reports', [
            [
                'methods' => WP_REST_Server::READABLE,
                'permission_callback' => $callback,
                'callback' => [ $this, 'get_report' ],
                'args' => [
                    'report' => [
                        'required' => true
                    ],
                    'range' => [
                        'required' => true
                    ],
                ]
            ],
        ] );
    }

    /**
     *
     *
     * @param WP_REST_Request $request
     * @return WP_Error|WP_REST_Response
     */
    public function get_report( WP_REST_Request $request )
    {

        if ( ! current_user_can( 'view_reports' ) ){
            return self::ERROR_INVALID_PERMISSIONS();
        }

        $report = $request->get_param( 'report' );
        $report = Plugin::$instance->reporting->get_report( $report );

        if ( ! $report ){
            return self::ERROR_401( 'no_report', 'The given report does not exist.' );
        }

        $response = [
            'data' => $report->get_data(),
            'start' => [ 'U' => $report->get_start_time(), 'MYSQL' => date( 'Y-m-d H:i:s', $report->get_start_time() ) ],
            'end' => [ 'U' => $report->get_end_time(), 'MYSQL' => date( 'Y-m-d H:i:s', $report->get_end_time() ) ],
        ];

        return self::SUCCESS_RESPONSE( $response );
    }


}