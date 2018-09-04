<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-08-24
 * Time: 9:14 AM
 */


/**
 * Insert activity into the log.
 *
 * @param $contact  int the ID of a contact
 * @param $activity string the Type of activity
 * @param $subject  int the ID of the subject, for example a form or an email
 * @param $funnel   int id of the funnel which they are currently in
 * @param $step     int id of the funnel step the contact came from
 * @param $ref      string the referer URL or destination URL
 * @return bool|false|int
 */
function wpfn_log_activity( $contact, $funnel, $step, $activity, $subject, $ref='' )
{
    if ( ! is_int( $contact ) || ! is_int( $subject ) )
        return false;

    global $wpdb;

    return $wpdb->insert(
        $wpdb->prefix . WPFN_ACTIVITY,
        array(
            'timestamp'     => time(),
            'contact_id'    => absint( $contact ),
            'funnel_id'     => absint( $funnel ),
            'step_id'       => absint( $step ),
            'activity_type' => $activity,
            'object_id'     => absint( $subject ),
            'referer'      => $ref
        )
    );
}

/**
 * Returns true if a similar activity for the contact given has occurred in the past.
 *
 * @param $contact int ID of the contact
 * @param $funnel int ID of the funnel
 * @param $step int ID of the funnel step
 * @param $activity string type of activity
 * @param $subject int ID of the subject matter.
 * @return bool whether the activity exists.
 */
function wpfn_activity_exists( $contact, $funnel, $step, $activity, $subject )
{
    global $wpdb;

    $table = $wpdb->prefix . WPFN_ACTIVITY;

    $query = $wpdb->prepare(
        "SELECT * FROM $table
        WHERE contact_id = %d AND funnel_id = %d AND step_id = %d AND activity_type = %s AND object_id = %d"
    , $contact, $funnel, $step, $activity, $subject );

    $results = $wpdb->get_results( $query );

    return ! empty( $results );
}

define( 'WPFN_ACTIVITY', 'activity_log' );
define( 'WPFN_ACTIVITY_DB_VERSION', '0.2' );

/**
 * Create the activity database table.
 * Activity will contain items such as Email Opens, Link clinks, Unsubscribes, form fills etc...
 */
function wpfn_create_activity_db()
{

    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $table_name = $wpdb->prefix . WPFN_ACTIVITY;

    if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name && version_compare( get_option( 'wpfn_activity_db_version' ), WPFN_ACTIVITY_DB_VERSION, '==' ) )
        return;

    $sql = "CREATE TABLE $table_name (
      timestamp bigint(20) NOT NULL,
      contact_id bigint(20) NOT NULL,
      funnel_id bigint(20) NOT NULL,
      step_id bigint(20) NOT NULL,
      activity_type VARCHAR(20) NOT NULL,
      object_id bigint(20) NOT NULL,
      referer text NOT NULL,
      PRIMARY KEY  (timestamp,contact_id,activity_type),
      KEY timestamp (timestamp),
      KEY contact_id (contact_id),
      KEY funnel_id (funnel_id),
      KEY step_id (step_id),
      KEY activity_type (activity_type),
      KEY object_id (object_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta( $sql );

    update_option( 'wpfn_activity_db_version', WPFN_ACTIVITY_DB_VERSION );
}
