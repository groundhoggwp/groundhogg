<?php
namespace Groundhogg\Api\V3;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

use function Groundhogg\do_api_trigger;
use function Groundhogg\get_cookie;
use function Groundhogg\get_db;
use Groundhogg\Plugin;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Plugin_Api extends Base
{

    public function register_routes()
    {
        register_rest_route(self::NAME_SPACE, '/do_api_benchmark/', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'permission_callback' => $this->get_auth_callback(),
                'callback' => [ $this, 'trigger' ],
                'args' => [
                    'call_name' => [
                        'description' => 'The call name of the API benchmark',
                        'required' => true
                    ],
                    'id_or_email' => [
                        'required'    => true,
                        'description' => _x('The ID or email of the contact you want to update.','api','groundhogg'),
                    ],
                    'by_user_id' => [
                        'required'    => false,
                        'description' => _x( 'Search using the user ID.', 'api', 'groundhogg' ),
                    ],
                ]
            ]
        ] );

        register_rest_route(self::NAME_SPACE, '/do_api_trigger/', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'permission_callback' => $this->get_auth_callback(),
                'callback' => [ $this, 'trigger' ],
                'args' => [
                    'call_name' => [
                        'description' => 'The call name of the API benchmark',
                        'required' => true
                    ],
                    'id_or_email' => [
                        'required'    => true,
                        'description' => _x('The ID or email of the contact you want to update.','api','groundhogg'),
                    ],
                    'by_user_id' => [
                        'required'    => false,
                        'description' => _x( 'Search using the user ID.', 'api', 'groundhogg' ),
                    ],
                ]
            ]
        ] );
    }

    /**
     * Perform a page view action
     *
     * @param WP_REST_Request $request
     * @return mixed|WP_Error|WP_REST_Response
     */
    public function trigger( WP_REST_Request $request )
    {

        $contact = self::get_contact_from_request( $request );

        if ( is_wp_error( $contact ) ){
            return $contact;
        }

        do_api_trigger( $request->get_param( 'call_name' ), $contact->get_id(), false );

        return self::SUCCESS_RESPONSE();
    }
}