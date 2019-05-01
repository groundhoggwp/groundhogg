<?php
/**
 * Groundhogg API Emails
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
 * WPGH_API_V3_EMAILS Class
 *
 * Renders API returns as a JSON
 *
 * @since  1.5
 */
class WPGH_API_V3_BROADCASTS extends WPGH_API_V3_BASE
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
//        if ( ! current_user_can( 'edit_emails' ) ){
//            return self::ERROR_INVALID_PERMISSIONS();
//        }
//
//        $query =  $request->get_param( 'query' ) ? (array) $request->get_param( 'query' ) : [];
//
//        $search = $request->get_param( 'q' ) ? $request->get_param( 'q' ) : $request->get_param( 'search' ) ;
//        $search = sanitize_text_field( stripslashes( $search ) );
//
//        if ( ! key_exists( 'search', $query ) && ! empty( $search ) ){
//            $query[ 'search' ] = $search;
//        }
//
//        $is_for_select = filter_var( $request->get_param( 'select' ), FILTER_VALIDATE_BOOLEAN );
//        $is_for_select2 = filter_var( $request->get_param( 'select2' ), FILTER_VALIDATE_BOOLEAN );
//
//        $emails = WPGH()->emails->get_emails( $query );
//
//        if ( $is_for_select2 ){
//            $json = array();
//
//            foreach ( $emails as $i => $email ) {
//
//                $json[] = array(
//                    'id' => $email->ID,
//                    'text' => $email->subject . ' (' . $email->status . ')'
//                );
//
//            }
//
//            $results = array( 'results' => $json, 'more' => false );
//
//            return rest_ensure_response( $results );
//        }
//
//        if ( $is_for_select ){
//
//            $response_emails = [];
//
//            foreach ( $emails as $i => $email ) {
//                $response_emails[ $email->ID ] = $email->subject;
//            }
//
//            $emails = $response_emails;
//
//        }

        $broadcasts = WPGH()->broadcasts->get_broadcasts();
        $response_broadcast = [];
        foreach ( $broadcasts as $broadcast ){

            if ( $broadcast->status == 'sent' && $broadcast->object_type == 'email') {

                $total = WPGH()->events->count( array(
                    'funnel_id'     => WPGH_BROADCAST,
                    'step_id'       => $broadcast->ID
                ) );

                $opens = WPGH()->activity->count( array(
                    'funnel_id'     => WPGH_BROADCAST,
                    'step_id'       => $broadcast->ID,
                    'activity_type' => 'email_opened'
                ) );

                $clicks = WPGH()->activity->count( array(
                    'funnel_id'     => WPGH_BROADCAST,
                    'step_id'       => $broadcast->ID,
                    'activity_type' => 'email_link_click'
                ) );

                $stats  =  [
                    'total' => $total,
                    'opens' => $opens,
                    'clicks'=> $clicks
                ];

                $broadcast->stats = $stats;
            }

            if ($broadcast->object_type === 'email') {

                $email =  WPGH()->emails->get_email($broadcast->object_id);

                $broadcast->object_name  = $email->subject;
            }


            if ($broadcast->object_type === 'sms') {

                $sms =  WPGH()->sms->get_sms($broadcast->object_id);

                $broadcast->object_name  = $sms->title;
            }



            $p_time = intval( $broadcast->send_time ) + ( wpgh_get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );

            $broadcast->send_time = date_i18n( 'jS F, Y \@ h:i A', intval( $p_time ) );

            $response_broadcast[] = $broadcast;
        }
        return self::SUCCESS_RESPONSE( [ 'broadcasts' => $broadcasts ] );
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
            $email = new WPGH_Email( $object_id );
            if ( $email->is_draft() ){
                return self::ERROR_400('email_in_draft_mode', sprintf( _x( 'You cannot schedule an email while it is in draft mode.', 'api', 'groundhogg' ) ) );
            }
        }

        $contact_sum = 0;

        foreach ($tags as $tag) {
            $tag = WPGH()->tags->get_tag(intval($tag));
            if ($tag) {
                $contact_sum += $tag->contact_count;
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
        $send_time = wpgh_convert_to_utc_0(strtotime($time_string));

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

        $broadcast_id = WPGH()->broadcasts->add($args);

        if (!$broadcast_id) {
            return self::ERROR_UNKNOWN();
        }

        $config['broadcast_id'] = $broadcast_id;

        $query = array(
            'tags_include' => $tags,
            'tags_exclude' => $exclude_tags
        );

        $config['contact_query'] = $query;

        if (isset($_POST['send_in_timezone'])) {
            $config['send_in_local_time'] = true;
        }

        set_transient('gh_get_broadcast_config', $config, HOUR_IN_SECONDS);

        return self::SUCCESS_RESPONSE( [], _x( 'Broadcast scheduled successfully.', 'api', 'groundhogg' ) );
    }
}