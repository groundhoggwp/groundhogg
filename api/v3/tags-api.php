<?php
namespace Groundhogg\Api\V3;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

use Groundhogg\Contact;
use Groundhogg\Contact_Query;
use function Groundhogg\get_contactdata;
use Groundhogg\Plugin;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Tags_Api extends Base
{

    public function register_routes()
    {

        $auth_callback = $this->get_auth_callback();

        register_rest_route('gh/v3', '/tags', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [ $this, 'get_tags' ],
                'permission_callback' => $auth_callback,
                'args'=> [
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
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [ $this, 'create_tags' ],
                'permission_callback' => $auth_callback,
                'args'=> [
                    'tags' => [
                        'required'    => true,
                        'description' => _x( 'Array of tag names.', 'api', 'groundhogg' ),
                    ]
                ]
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [ $this, 'delete_tag' ],
                'permission_callback' => $auth_callback,
                'args'=> [
                    'tag_id' => [
                        'required'    => true,
                        'description' => _x( 'The ID of the tag to delete.', 'api', 'groundhogg' ),
                    ]
                ]
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [ $this, 'update_tag' ],
                'permission_callback' => $auth_callback,
                'args'=> [
                    'tag_id' => [
                        'required'    => true,
                        'description' => _x( 'Contains array of tags to update.', 'api', 'groundhogg' ),
                    ],
                    'tag_name' => [
                        'description' => _x( 'The new name of the tag.', 'api', 'groundhogg' ),
                    ],
                    'tag_description' => [
                        'description' => _x( 'the new description of the tag.', 'api', 'groundhogg' ),
                    ]
                ]
            ],
        ] );

        register_rest_route('gh/v3', '/tags/apply', [
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [ $this, 'apply_tags' ],
                'permission_callback' => $auth_callback,
                'args'=> [
                    'id_or_email' => [
                        'required'    => true,
                        'description' => _x('The ID or email of the contact you want to apply tags to.','api','groundhogg'),
                    ],
                    'by_user_id' => [
                        'required'    => false,
                        'description' => _x( 'Search using the user ID.', 'api', 'groundhogg' ),
                    ],
                    'tags' => [
                        'required'    => true,
                        'description' => _x( 'Array of tag names or tag ids.', 'api', 'groundhogg' ),
                    ]
                ]
            ]
        ]);

        register_rest_route('gh/v3', '/tags/remove', [
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [ $this, 'remove_tags' ],
                'permission_callback' => $auth_callback,
                'args'=> [
                    'id_or_email' => [
                        'required'    => true,
                        'description' => _x('The ID or email of the contact you want to remove tags from.','api','groundhogg'),
                    ],
                    'by_user_id' => [
                        'required'    => false,
                        'description' => _x( 'Search using the user ID.', 'api', 'groundhogg' ),
                    ],
                    'tags' => [
                        'required'    => true,
                        'description' => _x( 'Array of tag names or tag ids.', 'api', 'groundhogg' ),
                    ]
                ]
            ]
        ]);

    }

    /**
     * Get all the tags
     *
     * @param WP_REST_Request $request
     * @return WP_Error|WP_REST_Response
     */
    public function get_tags(WP_REST_Request $request)
    {
        if ( ! current_user_can( 'manage_tags' ) ){
            return self::ERROR_INVALID_PERMISSIONS();
        }

        $search = $request->get_param( 'q' ) ? $request->get_param( 'q' ) : $request->get_param( 'search' ) ;
        $search = sanitize_text_field( stripslashes( $search ) );

        $is_for_select = filter_var( $request->get_param( 'select' ), FILTER_VALIDATE_BOOLEAN );
        $is_for_select2 = filter_var( $request->get_param( 'select2' ), FILTER_VALIDATE_BOOLEAN );

        $tags = Plugin::$instance->dbs->get_db( 'tags' )->search( $search );

        if ( $is_for_select2 ){
            $json = array();

            foreach ( $tags as $i => $tag ) {
                $json[] = array(
                    'id' => $tag->tag_id,
                    'text' => sprintf( "%s (%s)", $tag->tag_name, $tag->contact_count )
                );
            }

            $results = array( 'results' => $json, 'more' => false );

            return rest_ensure_response( $results );
        }

        if ( $is_for_select ){

            $response_tags = [];

            foreach ( $tags as $i => $tag ) {
                $response_tags[ $tag->tag_id ] = sprintf( "%s (%s)", $tag->tag_name, $tag->contact_count );
            }

            $tags = $response_tags;

        }

        return self::SUCCESS_RESPONSE( [ 'tags' => $tags ] );
    }

    /**
     * Created tags
     *
     * @param WP_REST_Request $request
     * @return WP_Error|WP_REST_Response
     */
    public function create_tags(WP_REST_Request $request)
    {
        if ( ! current_user_can( 'add_tags' ) ){
            return self::ERROR_INVALID_PERMISSIONS();
        }

        $tag_names = $request->get_param( 'tags' );

        if ( empty( $tag_names ) ){
            return self::ERROR_400( 'invalid_tag_names', 'An array of tags is required.' );
        }

        $tag_ids = Plugin::$instance->dbs->get_db( 'tags' )->validate( $tag_names );

        $response_tags = [];

        foreach ( $tag_ids as $tag_id ){
            $response_tags[ $tag_id ] = Plugin::$instance->dbs->get_db( 'tags' )->get_column_by( 'tag_name', 'tag_id', $tag_id );
        }

        return self::SUCCESS_RESPONSE( [ 'tags' => $response_tags ] );
    }

    /**
     * Update a tag
     *
     * @param WP_REST_Request $request
     * @return WP_Error|WP_REST_Response
     */
    public function update_tag(WP_REST_Request $request)
    {
        if ( ! current_user_can( 'edit_tags' ) ){
            return self::ERROR_INVALID_PERMISSIONS();
        }

        $tag_id = intval( $request->get_param( 'tag_id' ) );
        $tag_name = sanitize_text_field( $request->get_param( 'tag_name' ) );
        $tag_description = sanitize_text_field( $request->get_param( 'tag_description' ) );

        if ( ! $tag_id || ! $tag_name ){
            return self::ERROR_400( 'invalid_tag_params', 'Please provide proper arguments.' );
        }

        $args = array(
            'tag_name'          => $tag_name,
            'tag_slug'          => sanitize_title( $tag_name ),
            'tag_description'   => $tag_description,
        );

        if ( ! Plugin::$instance->dbs->get_db( 'tags' )->update( $tag_id, $args ) ){
            return self::ERROR_UNKNOWN();
        }

        return self::SUCCESS_RESPONSE();

    }

    /**
     * Delete a tag
     *
     * @param WP_REST_Request $request
     * @return WP_Error|WP_REST_Response
     */
    public function delete_tag( WP_REST_Request $request)
    {
        if ( ! current_user_can( 'delete_tags' ) ){
            return self::ERROR_INVALID_PERMISSIONS();
        }

        $tag_id = intval( $request->get_param( 'tag_id' ) );

        if ( ! $tag_id ){
            return self::ERROR_400( 'invalid_tag_params', 'Please provide proper arguments.' );
        }

        if ( ! Plugin::$instance->dbs->get_db( 'tags' )->delete( $tag_id ) ){
            return self::ERROR_UNKNOWN();
        }

        return self::SUCCESS_RESPONSE();
    }

    /**
     * Apply tags to a contact
     *
     * @param WP_REST_Request $request
     * @return false|WP_Error|WP_REST_Response
     */
    public function apply_tags( WP_REST_Request $request )
    {

        if ( ! current_user_can( 'edit_contacts' ) ) {
            return self::ERROR_INVALID_PERMISSIONS();
        }

        $contact = self::get_contact_from_request( $request );

        if( is_wp_error( $contact ) ) {
            return $contact;
        }

        $tag_names = $request->get_param( 'tags' );

        if ( empty( $tag_names ) ){
            return self::ERROR_400( 'invalid_tag_names', 'An array of tags is required.' );
        }

        $contact->apply_tag( $tag_names );

        return self::SUCCESS_RESPONSE();

    }

    /**
     * Remove tags from a contact
     *
     * @param WP_REST_Request $request
     * @return false|WP_Error|WP_REST_Response|Contact
     */
    public function remove_tags( WP_REST_Request $request )
    {

        if ( ! current_user_can( 'edit_contacts' ) ) {
            return self::ERROR_INVALID_PERMISSIONS();
        }

        $contact = self::get_contact_from_request( $request );

        if( is_wp_error( $contact ) ) {
            return $contact;
        }

        $tag_names = $request->get_param( 'tags' );

        if ( empty( $tag_names ) ){
            return self::ERROR_400( 'invalid_tag_names', 'An array of tags is required.' );
        }

        $contact->remove_tag( $tag_names );

        return self::SUCCESS_RESPONSE();

    }
}