<?php
namespace Groundhogg\Api\V3;

use Groundhogg\Broadcast;
use Groundhogg\Email;
use Groundhogg\Plugin;
use function Groundhogg\get_db;
use Groundhogg\Tag;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Broadcasts_Api extends Base
{

    public function register_routes()
    {

        $auth_callback = $this->get_auth_callback();

        register_rest_route(self::NAME_SPACE, '/broadcasts', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [ $this, 'get_broadcast' ],
                'permission_callback' => $auth_callback,
            ]
        ] );

        register_rest_route(self::NAME_SPACE, '/broadcasts/schedule' ,array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [ $this, 'schedule_broadcast' ],
            'permission_callback' => $auth_callback,
//            'args'=> array(
//                'id_or_email' => [
//                    'required'    => true,
//                    'description' => _x('The ID or email of the contact you want to send email to.','api','groundhogg'),
//                ],
//                'by_user_id' => [
//                    'required'    => false,
//                    'description' => _x( 'Search using the user ID.', 'api', 'groundhogg' ),
//                ],
//                'email_id' => [
//                    'required'    => true,
//                    'description' => _x( 'Email ID which you want to send.', 'api', 'groundhogg' ),
//                ]
//            )
        ));

    }

    /**
     * Get a list of broadcast.
     *
     * @param WP_REST_Request $request
     * @return WP_Error|WP_REST_Response
     */
    public function get_broadcast( WP_REST_Request $request )
    {
        if ( ! current_user_can( 'view_reports' ) ){
            return self::ERROR_INVALID_PERMISSIONS();
        }

        $broadcast_ids = wp_parse_id_list( wp_list_pluck( get_db( 'broadcasts' )->query(), 'ID' ) );

        $response_broadcast = [];

        foreach ( $broadcast_ids as $broadcast_id ){

            $broadcast = new Broadcast( $broadcast_id );
            $response_broadcast[] = $broadcast->get_as_array();
        }

        return self::SUCCESS_RESPONSE( [ 'broadcasts' => $response_broadcast ] );
    }

    /**
     * Schedule broadcast for provided tags.
     *
     * @param WP_REST_Request $request
     * @return WP_Error|WP_REST_Response
     */
    public function schedule_broadcast( WP_REST_Request $request )
    {
        if (!current_user_can('schedule_broadcasts')) {
            return self::ERROR_INVALID_PERMISSIONS();
        }

        $config = [];

        $object_id = intval( $request->get_param( 'email_or_sms_id' ) );
        $tags =  wp_parse_id_list( $request->get_param('tags' ) );
        $exclude_tags =  wp_parse_id_list( $request->get_param('exclude_tags' ) );
        $date =  $request->get_param('date');
        $time =  $request->get_param('time');
        $send_now =  $request->get_param('send_now');
        $send_in_timezone = $request->get_param('local_time' );
        $type = $request->get_param('type');

        /* Set the object  */
        $config['object_id'] = $object_id;
        $config['object_type'] = $type;

        if ( $config[ 'object_type' ] === 'email' ){
            $email = new Email( $object_id );
            if ( $email->is_draft() ){
                return self::ERROR_400('email_in_draft_mode', sprintf( _x( 'You cannot schedule an email while it is in draft mode.', 'api', 'groundhogg' ) ) );
            }
        }

        $contact_sum = 0;

        foreach ( $tags as $tag_id ) {
            $tag = new Tag( $tag_id );

            if ( $tag->exists() ){
                $contact_sum += $tag->get_contact_count();
            }
        }

        if ( $contact_sum === 0 ) {
            return self::ERROR_400('no_contacts', sprintf( _x( 'Please select a tag with at least 1 contact.', 'api', 'groundhogg' ) ) );
        }

        if($date) {
            $send_date = $date;
        } else {
            $send_date = date('Y/m/d', strtotime('tomorrow'));
        }

        if ($time){
            $send_time = $time;
        } else {
            $send_time = '9:30';
        }

        $time_string = $send_date . ' ' . $send_time;

        /* convert to UTC */
        $send_time = Plugin::$instance->utils->date_time->convert_to_utc_0( strtotime( $time_string ) );

        if ($send_now) {
            $config['send_now'] = true;
            $send_time = time() + 10;
        }

        if ($send_time < time()) {
            return self::ERROR_400('invalid_date', _x( 'Please select a time in the future', 'api', 'groundhogg' ) );
        }

        /* Set the email */
        $config['send_time'] = $send_time;

        $args = array(
            'object_id' => $object_id,
            'object_type' => $config[ 'object_type' ],
            'tags' => $tags,
            'send_time' => $send_time,
            'scheduled_by' => get_current_user_id(),
            'status' => 'scheduled',
        );

        $broadcast = new Broadcast( $args );

        if (!$broadcast->exists()) {
            return self::ERROR_UNKNOWN();
        }

        $config['broadcast_id'] = $broadcast->get_id();

        $query = array(
            'tags_include' => $tags,
            'tags_exclude' => $exclude_tags
        );

        $config['contact_query'] = $query;

        if ( $send_in_timezone ) {
            $config[ 'send_in_local_time' ] = true;
        }

        set_transient('gh_get_broadcast_config', $config, HOUR_IN_SECONDS);

        return self::SUCCESS_RESPONSE( [], _x( 'Broadcast scheduled successfully.', 'api', 'groundhogg' ) );
    }
}