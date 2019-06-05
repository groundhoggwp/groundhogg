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

class Data_Api extends Base
{

    public function register_routes()
    {
        $callback = $this->get_auth_callback();

        // BASIC DB QUERY
        register_rest_route(self::NAME_SPACE, '/data', [
            [
                'methods' => WP_REST_Server::READABLE,
                'permission_callback' => $callback,
                'callback' => [ $this, 'query' ],
                'args' => [
                    'db' => [
                        'required' => true
                    ],
                    'query' => [
                        'required' => true
                    ]
                ]
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'permission_callback' => $callback,
                'callback' => [ $this, 'add' ],
                'args' => [
                    'db' => [
                        'required' => true
                    ],
                    'query' => [
                        'required' => true
                    ]
                ]
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'permission_callback' => $callback,
                'callback' => [ $this, 'update' ],
                'args' => [
                    'db' => [
                        'required' => true
                    ],
                    'query' => [
                        'required' => true
                    ],
                    'where' => [
                        'required' => true
                    ]
                ]
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'permission_callback' => $callback,
                'callback' => [ $this, 'delete' ],
                'args' => [
                    'db' => [
                        'required' => true
                    ],
                    'query' => [
                        'required' => true
                    ],
                    'where' => [
                        'required' => true
                    ]
                ]
            ],
        ] );
    }


    protected function get_db_from_request( WP_REST_Request $request )
    {
        $db = get_db( $request->get_param( 'db' )  );

        if ( ! $db ){
            return self::ERROR_401( 'db_does_not_exist', 'The database you have requested does not exist.' );
        }

        return $db;
    }

    /**
     * Query any DB
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function query( WP_REST_Request $request )
    {
        $db = $this->get_db_from_request( $request );

        if ( is_wp_error( $db ) ) {
            return $db;
        }

        $results = get_db( $db )->query( $request->get_param( 'query' ) );
        return self::SUCCESS_RESPONSE( [ 'results' => $results ] );
    }

    /**
     * Query any DB
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function add( WP_REST_Request $request )
    {
        $db = $this->get_db_from_request( $request );

        if ( is_wp_error( $db ) ) {
            return $db;
        }

        $id = get_db( $db )->add( $request->get_param( 'query' ) );

        if ( ! $id ){
            return self::ERROR_401( 'db_eror', 'Unable to add record.' );
        }

        return self::SUCCESS_RESPONSE( [ 'id' => $id ] );
    }

    /**
     * Query any DB
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function update( WP_REST_Request $request )
    {
        $db = $this->get_db_from_request( $request );

        if ( is_wp_error( $db ) ) {
            return $db;
        }

        $where = $request->get_param( 'where' );
        $query = $request->get_param( 'query' );

        $result = $db->update( 0, $query, $where );

        if ( ! $result ){
            return self::ERROR_401( 'db_error', 'Unable to update records.' );
        }

        return self::SUCCESS_RESPONSE();
    }

    /**
     * Query any DB
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function delete( WP_REST_Request $request )
    {
        $db = $this->get_db_from_request( $request );

        if ( is_wp_error( $db ) ) {
            return $db;
        }

        $where = $request->get_param( 'where' );

        $result = $db->bulk_delete( $where );

        if ( ! $result ){
            return self::ERROR_401( 'db_error', 'Unable to delete records.' );
        }

        return self::SUCCESS_RESPONSE();
    }

}