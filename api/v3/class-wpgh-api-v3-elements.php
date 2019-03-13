<?php
/**
 * Groundhogg Elements API
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
 * WPGH_API_V3_ELEMENTS Class
 *
 * Renders API returns as a JSON
 *
 * @since  1.5
 */
class WPGH_API_V3_ELEMENTS extends WPGH_API_V3_BASE
{

    public function register_routes()
    {
        register_rest_route('gh/v3', '/elements/page-view', [
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'page_view'),
            ]
        ] );

        register_rest_route('gh/v3', '/elements/form-impression', [
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'form_impression'),
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
        $contact = WPGH()->tracking->get_contact();

        if ( ! $contact ){
            return self::ERROR_200( 'no_contact', 'No contact to track...' );
        }

        $ref = $request->get_param( 'ref' );

        if ( ! $ref ){
            return self::ERROR_400( 'no_ref', 'Cannot track blank pages...' );
        }

        do_action( 'groundhogg/api/v3/elements/page-view', $ref, $contact );

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
            require_once WPGH_PLUGIN_DIR . 'includes/lib/browser.php';

        $browser = new Browser();

        if ( $browser->isRobot() || $browser->isAol() ){
            return self::ERROR_401( 'looks_like_a_bot', 'Form impressions only track bots.' );
        }

        $ID = intval( $request->get_param( 'form_id' ) );

        if ( ! WPGH()->steps->exists( $ID ) ){
            return self::ERROR_400( 'form_dne', 'The given form does not exist.' );
        }

        $step = new WPGH_Step( $ID );

        $response = array();

        /*
         * Is Contact
         */
        if ( $contact = WPGH()->tracking->get_contact() ) {

            $db = WPGH()->activity;

            /* Check if impression for contact exists... */
            $args = array(
                'funnel_id'     => $step->funnel_id,
                'step_id'       => $step->ID,
                'contact_id'    => $contact->ID,
                'activity_type' => 'form_impression',
            );

            $response[ 'cid' ] = $contact->ID;

        } else {
            /*
            * Not a Contact
            */

            /* validate against viewers IP? Cookie? TBD */
            $db = WPGH()->activity;

            /* Check if impression for contact exists... */
            if ( isset( $_COOKIE[ 'gh_ref_id' ] ) ){
                $ref_id = sanitize_key( $_COOKIE[ 'gh_ref_id' ] );
            } else {
                $ref_id = uniqid( 'g' );
            }

            $args = array(
                'funnel_id'     => $step->funnel_id,
                'step_id'       => $step->ID,
                'activity_type' => 'form_impression',
                'ref'           => $ref_id
            );

            $response[ 'ref_id' ] = $ref_id;

        }

        if ( $db->activity_exists( $args ) ){
            return self::ERROR_200( 'no_double_track', 'Unique views only.' );
        }

        do_action( 'groundhogg/api/v3/elements/form-impression' );

        $args[ 'timestamp' ] = time();
        $db->add( $args );

        return self::SUCCESS_RESPONSE( $response );
    }

}