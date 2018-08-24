<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-08-17
 * Time: 9:52 AM
 */

/*
 * Title       string
 * email_id    int    -> email id
 * sent_by     int    -> user id
 * date_created  datetime
 * send_at     int    -> time
 * send_to     string -> tags
 */

/**
 * Insert a new broadcast
 *
 * @param $email_id int the ID of the email to send.
 * @param $tags array list of tags
 * @param $send_at int the time to send
 * @return bool|int false on failure or the id of the new broadcast
 */
function wpfn_insert_broadcast( $email_id, $tags, $send_at )
{
    global $wpdb;

    $success = $wpdb->insert(
        $wpdb->prefix . WPFN_BROADCASTS,
        array(
            'email_id' => $email_id,
            'from_user' => get_current_user_id(),
            'send_to_tags' => maybe_serialize( $tags ),
            'send_at' => $send_at,
            'broadcast_status' => 'scheduled',
            'date_created' => current_time( 'mysql' ),
        )
    );

    if ( $success ){
        return $wpdb->insert_id;
    } else {
        return false;
    }
}

/**
 * Update information about a broadcast
 *
 * @param $id int broadcast ID
 * @param $key string Column Name
 * @param $value string New Column Value
 *
 * @return false|int contact ID in success, false on failure
 */
function wpfn_update_broadcast( $id, $key, $value )
{
    global $wpdb;

    if ( ! $id || ! is_numeric( $id ) )
        return false;

    $id = absint( $id );
    if ( ! $id )
        return false;

    do_action( 'wpfn_update_broadcast_before', $id );

    return $wpdb->update(
        $wpdb->prefix . WPFN_BROADCASTS,
        array(
            $key => $value
        ),
        array( 'ID' => $id ),
        array(
            '%s'	// value1
        ),
        array( '%d' )
    );
}

/**
 * Get the broadcast
 *
 * @param $id int ID of the broadcast
 * @return array|false the broadcast or false on failure.
 */
function wpfn_get_broadcast_by_id( $id )
{
    global $wpdb;

    if ( ! $id || ! is_numeric( $id ) )
        return false;

    $id = absint( $id );
    if ( ! $id )
        return false;

    $table_name = $wpdb->prefix . WPFN_BROADCASTS;

    $sql_prep1 = $wpdb->prepare("SELECT * FROM $table_name WHERE ID = %d", $id);
    $broadcast = $wpdb->get_row( $sql_prep1, ARRAY_A );

    $broadcast['send_to_tags'] = maybe_unserialize( $broadcast['send_to_tags'] );

    return $broadcast;
}


/**
 * Get the count of emails for a specific column value
 *
 * @param $where string the column
 * @param $clause string the value
 * @return int the count of items
 */
function wpfn_count_broadcast_items( $where='', $clause='' )
{
    global $wpdb;

    $table_name = $wpdb->prefix . WPFN_BROADCASTS;

    if ( $where && $clause ){
        return (int) $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE $where LIKE %s", $clause ) );
    } else {
        return (int) $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE broadcast_status LIKE %s OR broadcast_status LIKE %s", 'sent', 'scheduled' ) );
    }
}

define( 'WPFN_BROADCASTS', 'broadcasts' );
define( 'WPFN_BROADCASTS_DB_VERSION', '0.1' );

/**
 * Create the broadcasts database table.
 */
function wpfn_create_broadcasts_db()
{

    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $table_name = $wpdb->prefix . WPFN_BROADCASTS;

    if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name && version_compare( get_option( 'wpfn_broadcasts_db_version' ), WPFN_EMAILS_DB_VERSION, '==' ) )
        return;

    $sql = "CREATE TABLE $table_name (
      ID bigint(20) NOT NULL AUTO_INCREMENT,
      email_id bigint(20) NOT NULL,
      from_user bigint(20) NOT NULL,
      send_at bigint(20) NOT NULL,
      send_to_tags longtext NOT NULL,
      broadcast_status VARCHAR(20) NOT NULL,
      date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
      PRIMARY KEY  (ID)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta( $sql );

    update_option( 'wpfn_broadcasts_db_version', WPFN_EMAILS_DB_VERSION );
}
