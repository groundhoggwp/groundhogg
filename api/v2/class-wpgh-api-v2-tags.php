<?php
/**
 * Groundhogg API tags
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
 * WPGH_API_V2_CONTACTS Class
 *
 * Renders API returns as a JSON
 *
 * @since  1.5
 */
class WPGH_API_V2_TAGS extends WPGH_API_V2_BASE
{

    public function __construct()
    {
        //initialize api if user check the api section
        add_action('rest_api_init', array( $this, 'register_routs' ) );

    }

    public function register_routs()
    {
        register_rest_route('gh/v2', '/tags', array(
            array(
                // By using this constant we ensure that when the WP_REST_Server changes, our readable endpoints will work as intended.
                'methods' => WP_REST_Server::READABLE,
                // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
                'callback' => array($this, 'get_tags'),
                'permission_callback' => array($this, 'rest_authentication'),
                'args'=> array(
                    'tags_id' => array(
                        'required'    => false,
                        'description' => 'The ID of tag you want to retrieve.',
                    )
                )
            ),
            array(
                // By using this constant we ensure that when the WP_REST_Server changes, our create endpoints will work as intended.
                'methods' => WP_REST_Server::CREATABLE,
                // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
                'callback' => array($this, 'create_tags'),
                'permission_callback' => array($this, 'rest_authentication'),
                'args'=> array(
                    'tags' => array(
                        'required'    => true,
                        'description' => 'contains array of tags details user wants to create.',
                    )
                )

            ),

            array(
                // By using this constant we ensure that when the WP_REST_Server changes, our create endpoints will work as intended.
                'methods' => WP_REST_Server::DELETABLE,
                // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
                'callback' => array($this, 'delete_tags'),
                'permission_callback' => array($this, 'rest_authentication'),
                'args'=> array(
                    'tag_id' => array(
                        'required'    => true,
                        'description' => 'The ID of tag you want to delete.',
                    )
                )
            ),
            array(
                // By using this constant we ensure that when the WP_REST_Server changes, our create endpoints will work as intended.
                'methods' => 'PUT, PATCH',
                // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
                'callback' => array($this, 'update_tags'),
                'permission_callback' => array($this, 'rest_authentication'),
                'args'=> array(
                    'tags' => array(
                        'required'    => true,
                        'description' => 'contains array of tags details user wants to update with tag_id argument.',
                    )
                )
            ),
        ));

    }

    //GET METHOD
    public function get_tags(WP_REST_Request $request)
    {
//        if ( ! user_can( $request['wpgh_user_id'], 'view_contacts' ) ){
//            return new WP_Error('error', __('you are not eligible to perform this operation.'));
//        }

        // method to get contact from  GroundHogg


        $tag_id = null;
        $tags = null;
        if (isset ($request['tag_id'])) {
            $tag_id = ( integer) $request['tag_id'];
            if ( !( WPGH()->tags->get_tag( $tag_id ) === false) ) {
                $tags = WPGH()->tags->get_tag($tag_id);
            } else {
                return new WP_Error('error', __('no tag found with provided tag id.'), array('status' => 404));
            }

        } else {
            $tags = WPGH()->tags->get_tags();
        }

        if ( $tags != null ) {
            return rest_ensure_response(array('tags' => $tags));
        } else {
            return new WP_Error('error', 'No tags found.');
        }
    }

    //POST METHOD
    public function create_tags(WP_REST_Request $request)
    {

        if ( ! user_can( $request['wpgh_user_id'], 'add_tags' ) ){
            return new WP_Error('error', __('you are not eligible to perform this operation.'));
        }
        $parameters = $request->get_json_params();


        if ( isset ( $parameters['tags'] ) ) {

            $tags =  $parameters['tags'];
            $insert_count = 0;
            foreach ($tags as $tag)
            {
                $desc =  '';
                if ( isset( $tag['tag_name'] ) ) {
                    // check tag_name is set or not
                    if ( isset( $tag['tag_description'] ) ) {
                        //chcek for description
                        $desc  = sanitize_text_field( $tag['tag_description'] ) ;
                        $id = WPGH()->tags->add( array(
                            'tag_name' => sanitize_text_field($tag['tag_name']),
                            'tag_description' => $desc
                        ) );
                        $insert_count ++;
                    } else {
                        $id = WPGH()->tags->add( array(
                            'tag_name' => sanitize_text_field( $tag['tag_name'] )
                        ) );
                        $insert_count ++;
                    }

                }

            }

            if ($insert_count > 0 ) {

                return rest_ensure_response(array(
                    'code' => 'success',
                    'message' => $insert_count.' tags added successfully.'
                ));
            }


        } else {

            return new WP_Error('error', __('Please enter tags.'));
        }

    }

    //PUT METHOD
    public function update_tags(WP_REST_Request $request)
    {
        if ( ! user_can( $request['wpgh_user_id'], 'edit_tags' ) ){
            return new WP_Error('error', __('you are not eligible to perform this operation.'));
        }

        $parameters = $request->get_json_params();


        if ( isset ( $parameters['tags'] ) ) {

            if(isset($parameters['tags']['tag_id'] ) )  {

                if ( !( WPGH()->tags->get_tag( $parameters['tags']['tag_id'] ) === false) ) {

                    if(isset( $parameters['tags']['tag_name'] ) ){

                        $result  = WPGH()->tags->update( $parameters['tags']['tag_id'] , array(
                            'tag_name' => sanitize_text_field($parameters['tags']['tag_name']),

                        ) );
                    }
                    if(isset($parameters['tags']['tag_description'])){
                        $result  = WPGH()->tags->update( $parameters['tags']['tag_id'] , array(
                            'tag_description' => sanitize_text_field($parameters['tags']['tag_description']),
                        ) );
                    }

                    if($result){

                        return rest_ensure_response(array(
                            'code' => 'success',
                            'message' => 'Tag updated successfully.'
                        ));
                    } else {

                        return new WP_Error('error', __('Something went wrong'));
                    }

                } else {

                    return new WP_Error('error', __('no tag found with provided tag id.'), array('status' => 404));
                }
            } else {
                return new WP_Error('error', __('This operation needs tag_id argument.'));
            }


        } else {

            return new WP_Error('error', __('Please enter tag block to perform this operation.'), array('status' => 404));
        }

    }

    //DELETE METHOD
    public function delete_tags(WP_REST_Request $request)
    {// function invoked if user wants to delete one contact

        if ( ! user_can( $request['wpgh_user_id'], 'delete_tags' ) ){
            return new WP_Error('error', __('you are not eligible to perform this operation.'));
        }

        if (isset($request['tag_id'])) {
            $tag_id = $request['tag_id'];
            // ----------- code to delete contact
            if ( !( WPGH()->tags->get_tag( $tag_id ) === false) ) {

                if ( WPGH()->tags->delete($tag_id) ) {
                    return rest_ensure_response(array(
                        'code' => 'success',
                        'message' => 'tag deleted successfully.'
                    ));
                } else {
                    return new WP_Error('error', __('Something went wrong'));
                }
            } else {

                return new WP_Error('error', __('no tag found with provided tag id.'), array('status' => 404));
            }

        } else {

            return new WP_Error('error', __('Please enter tag_id to perform this operation.'), array('status' => 404));
        }

    }
}