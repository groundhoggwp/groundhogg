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

define( 'WPFN_BROADCASTS', 'emails' );
define( 'WPFN_BROADCASTS_DB_VERSION', '0.2' );

/**
 * Create the broadcasts database table.
 */
function wpfn_create_broadcasts_db()
{

    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $table_name = $wpdb->prefix . WPFN_EMAILS;

    if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name && version_compare( get_option( 'wpfn_broadcasts_db_version' ), WPFN_EMAILS_DB_VERSION, '==' ) )
        return;

    $sql = "CREATE TABLE $table_name (
      ID bigint(20) NOT NULL AUTO_INCREMENT,
      email_id bigint(20) NOT NULL,
      from_user bigint(20) NOT NULL,
      send_at bigint(20) NOT NULL,
      send_to longtext NOT NULL,
      date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
      PRIMARY KEY  (ID)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta( $sql );

    update_option( 'wpfn_broadcasts_db_version', WPFN_EMAILS_DB_VERSION );
}
