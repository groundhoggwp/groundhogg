<?php
namespace Groundhogg\Api\V3;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

use Groundhogg\Admin\Dashboard\Dashboard_Widgets;
use function Groundhogg\get_db;
use Groundhogg\Plugin;
use function Groundhogg\show_groundhogg_branding;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

use Groundhogg\Admin\Dashboard\Widgets;

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
                    'chart_format' => [
                        'required' => false
                    ]
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

        if ( ! $report ){
            return self::ERROR_401( 'no_report', 'The given report does not exist.' );
        }

        $get_from_widget = $request->get_param( 'chart_format' );

        if ( ! filter_var( $get_from_widget, FILTER_VALIDATE_BOOLEAN ) ){
            $data = Plugin::$instance->reporting->get_report( $report )->get_data();
        } else {
            // this is most definitely a hack, do better next time.
            $widgets = new Dashboard_Widgets();
            $widgets->setup_widgets();
            $widget = $widgets->get_widget( $report );
            $data = [];

            if ( method_exists( $widget, 'get_chart_data' ) ){
                $data = $widget->get_chart_data();
            }
        }

        $response = [
            'data' => $data,
//            'start' => [ 'U' => $report->get_start_time(), 'MYSQL' => date( 'Y-m-d H:i:s', $report->get_start_time() ) ],
//            'end' => [ 'U' => $report->get_end_time(), 'MYSQL' => date( 'Y-m-d H:i:s', $report->get_end_time() ) ],
        ];

        return self::SUCCESS_RESPONSE( $response );

    }

}