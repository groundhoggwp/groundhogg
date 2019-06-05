<?php
namespace Groundhogg\Api\V3;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

use Groundhogg\Plugin;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Authentication_Api extends Base
{

    public function register_routes()
    {
        register_rest_route(self::NAME_SPACE, '/authentication', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [ $this, 'get_credentials' ],
                'args' => [
                    'login' => [
                        'description' => 'User login.',
                        'required' => true
                    ],
                    'password' => [
                        'description' => 'User password.',
                        'required' => true
                    ]
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
    public function get_credentials( WP_REST_Request $request )
    {

        $login = $request->get_param( 'login' );
        $pw = $request->get_param( 'password' );

        $user = wp_signon( [ 'user_login' => $login, 'user_password' => $pw ], false );

        if ( is_wp_error( $user ) ){
            return $user;
        }

        $user_id = $user->ID;

        $key = [];

        $key['public'] = get_user_meta( $user_id,'wpgh_user_public_key',true);
        $key['token']  = hash( 'md5', get_user_meta( $user_id,'wpgh_user_secret_key' ,true) . $key['public'] );

        return self::SUCCESS_RESPONSE( $key );
    }
}