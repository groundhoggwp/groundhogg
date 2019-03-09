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
class WPGH_API_V2_DEBUG extends WPGH_API_V2_BASE
{
    public function __construct()
    {
        //initialize api if user check the api section
        add_action('rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes()
    {
        register_rest_route('gh/v2', '/debug', array(
            array(
                // By using this constant we ensure that when the WP_REST_Server changes, our readable endpoints will work as intended.
                'methods' => WP_REST_Server::READABLE,
                // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
                'callback' => array($this, 'debug'),
                'permission_callback' => array($this, 'rest_authentication'),
            )
        ));

    }

    //GET METHOD
    public function debug( WP_REST_Request $request )
    { // GET list of available LIST of EMAILS

        return rest_ensure_response( [
            'stackstrace' => debug_backtrace()
        ] ) ;

    }

}