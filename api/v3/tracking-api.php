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

class Tracking_Api extends Base
{


    public function register_routes()
    {
        register_rest_route('gh/v3', '/tracking/page-view', [
            [
                'methods' => WP_REST_Server::EDITABLE,
                'permission_callback' => function ( WP_REST_Request $request ){
                    return wp_verify_nonce( $request->get_param( '_ghnonce' ), 'groundhogg_frontend' );
                },
                'callback' => [ $this, 'page_view' ],
                'args' => [
                    '_ghnonce' => [
                        'description' => 'Need this!',
                        'required' => true
                    ]
                ]
            ]
        ] );

        register_rest_route('gh/v3', '/tracking/form-impression', [
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [ $this, 'form_impression' ],
                'permission_callback' => function ( WP_REST_Request $request ){
                    return wp_verify_nonce( $request->get_param( '_ghnonce' ), 'groundhogg_frontend' );
                },
                'args' => [
                    '_ghnonce' => [
                        'description' => 'Need this!',
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
    public function page_view( WP_REST_Request $request )
    {
        $contact = Plugin::$instance->tracking->get_current_contact();

        if ( ! $contact ){
            return self::ERROR_200( 'no_contact', 'No contact to track...' );
        }

        $ref = $request->get_param( 'ref' );

        if ( ! $ref ){
            return self::ERROR_400( 'no_ref', 'Cannot track blank pages...' );
        }

        do_action( 'groundhogg/api/v3/steps/page-view', $ref, $contact );

        return self::SUCCESS_RESPONSE();

    }

    /**
     * Log a form impressions for tracking purposes.
     *
     * @param WP_REST_Request $request
     * @return mixed|WP_Error|WP_REST_Response
     */
    public function form_impression( WP_REST_Request $request )
    {
        if( !class_exists( 'Browser' ) )
            require_once GROUNDHOGG_PATH . 'includes/lib/browser.php';

        $browser = new \Browser();

        if ( $browser->isRobot() || $browser->isAol() ){
            return self::ERROR_401( 'looks_like_a_bot', 'Form impressions do not track bots.' );
        }

        $ID = intval( $request->get_param( 'form_id' ) );

        if ( ! get_db( 'steps' )->exists( $ID ) ){
            return self::ERROR_400( 'form_dne', 'The given form does not exist.' );
        }

        $step = Plugin::$instance->utils->get_step( $ID );

        $response = array();

        /*
         * Is Contact
         */
        if ( $contact = Plugin::$instance->tracking->get_current_contact() ) {


            /* Check if impression for contact exists... */
            $args = array(
                'funnel_id'     => $step->get_funnel_id(),
                'step_id'       => $step->get_id(),
                'contact_id'    => $contact->get_id(),
                'activity_type' => 'form_impression',
                'start'         => time() - DAY_IN_SECONDS
            );

            $response[ 'cid' ] = $contact->get_id();

        } else {
            /*
            * Not a Contact
            */

            /* Check if impression for contact exists... */
            if ( isset( $_COOKIE[ 'gh_ref_id' ] ) ){

                $ref_id = sanitize_key( $_COOKIE[ 'gh_ref_id' ] );

            } else {

                if ( ! Plugin::$instance->utils->location->verify_ip() ){
                    return self::ERROR_401( 'unverified_ip', 'Could not verify ip address.' );
                }

                $ref_id = uniqid( 'g' );
            }

            $args = array(
                'funnel_id'     => $step->get_funnel_id(),
                'step_id'       => $step->get_id(),
                'activity_type' => 'form_impression',
                'referer'       => $ref_id,
                'from'         => time() - DAY_IN_SECONDS
            );

            $response[ 'ref_id' ] = $ref_id;

        }

        if ( get_db( 'activity' )->exists( $args ) ){
            return self::ERROR_200( 'no_double_track', 'Unique views only.', $response );
        }

        do_action( 'groundhogg/api/v3/steps/form-impression' );

        unset( $args[ 'start' ] );
        $args[ 'timestamp' ] = time();
        get_db( 'activity' )->add( $args );

        return self::SUCCESS_RESPONSE( $response );
    }

}