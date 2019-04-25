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
 * WPGH_API_V2_ELEMENTS Class
 *
 * Renders API returns as a JSON
 *
 * @since  1.5
 */
class WPGH_API_V2_ELEMENTS extends WPGH_API_V2_BASE
{

    public function register_routes()
    {
        register_rest_route('gh/v2', '/steps/page-view', array(
            array(
                // By using this constant we ensure that when the WP_REST_Server changes, our readable endpoints will work as intended.
                'methods' => WP_REST_Server::EDITABLE,
                // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
                'callback' => array($this, 'page_view'),
            )
        ));

        register_rest_route('gh/v2', '/steps/form-impression', array(
            array(
                // By using this constant we ensure that when the WP_REST_Server changes, our readable endpoints will work as intended.
                'methods' => WP_REST_Server::EDITABLE,
                // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
                'callback' => array($this, 'form_impression'),
            )
        ));



    }

    public function page_view( WP_REST_Request $request )
    {
        $contact = WPGH()->tracking->get_contact();

        if ( ! $contact ){
            return new WP_Error( 'no_contact', 'No contact to track...', [ 'status' => 200 ] );
        }

        $ref = $request->get_param( 'ref' );

        if ( ! $ref ){
            return new WP_Error( 'no_ref', 'Cannot track blank pages...', [ 'status' => 400 ]  );
        }

        do_action( 'groundhogg/api/v2/steps/page-view', $ref, $contact );

        return rest_ensure_response( [
            'status' => 'success'
        ] );

    }

    public function form_impression( WP_REST_Request $request )
    {
        if( !class_exists( 'Browser' ) )
            require_once WPGH_PLUGIN_DIR . 'includes/lib/browser.php';

        $browser = new Browser();

        if ( $browser->isRobot() || $browser->isAol() ){
            return new WP_Error( 'looks_lik_a_bot', 'Form impressions only track bots.', [ 'status' => 401 ]  );
        }

        $ID = intval( $request->get_param( 'form_id' ) );

        if ( ! WPGH()->steps->exists( $ID ) ){
            return new WP_Error( 'form_dne', 'The given form does not exist.', [ 'status' => 400 ] );
        }

        $step = wpgh_get_funnel_step( $ID );

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
            return new WP_Error( 'no_double_track', 'Unique views only.', [ 'status' => 200 ] );
        }


        $args[ 'timestamp' ] = time();
        $db->add( $args );

        $response[ 'status' ] = 'success';


        return rest_ensure_response( $response );
    }

}