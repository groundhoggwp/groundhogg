<?php
/**
 * Email database functions
 *
 * Functions to manipulate and retrieve data from the database.
 *
 * @package     wp-funnels
 * @subpackage  Includes/Emails
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

/**
 * Insert a new email into the DB.
 *
 * @param $content string the Email Content
 * @param $subject string the Email Subject
 * @param $pre_header string the Email Pre_header
 * @param $from_user int the ID of the user the email is to be sent from
 *
 * @return bool|int the ID of the new Email, false on failure.
 */
function wpfn_insert_new_email( $content, $subject, $pre_header, $from_user )
{
	global $wpdb;

	$success = $wpdb->insert(
		$wpdb->prefix . WPFN_EMAILS,
		array(
			'content'       => $content,
			'subject'       => $subject,
			'pre_header'    => $pre_header,
			'from_user'     => $from_user,
			'email_status'  => 'draft',
			'date_created'  => current_time( 'mysql' )
		)
	);

	if ( $success ){
		return $wpdb->insert_id;
	} else {
		return false;
	}
}

/**
 * Get the count of emails for a specifci column value
 *
 * @param $where string the column
 * @param $clause string the value
 * @return int the count of items
 */
function wpfn_count_email_items( $where='', $clause='' )
{
    global $wpdb;

    $table_name = $wpdb->prefix . WPFN_EMAILS;

    if ( $where && $clause ){
        return $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE $where LIKE %s", $clause ) );
    } else {
        return $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE email_status LIKE %s OR email_status LIKE %s OR email_status LIKE %s", 'ready', 'draft', '' ) );
    }
}

/**
 * Return a list of emails
 *
 * @return array associative list of emails to their respective IDs
 */
function wpfn_get_emails()
{
    global $wpdb;

    $table_name = $wpdb->prefix . WPFN_EMAILS;

    return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name WHERE email_status LIKE %s", 'ready' ), ARRAY_A );
}

/**
 * Get an email row via the ID of the email
 *
 * @param int $id Email Id
 *
 * @return object|false the Email, false on failure.
 */
function wpfn_get_email_by_id( $id )
{
	global $wpdb;

	if ( ! $id || ! is_numeric( $id ) )
		return false;

	$id = absint( $id );
	if ( ! $id )
		return false;

	$table_name = $wpdb->prefix . WPFN_EMAILS;

	$sql_prep1 = $wpdb->prepare("SELECT * FROM $table_name WHERE ID = %d", $id);
	$email = $wpdb->get_row( $sql_prep1 );

	return $email;
}

/**
 * Update information about an email
 *
 * @param $id int Contact ID
 * @param $key string Column Name
 * @param $value string New Column Value
 *
 * @return false|int email ID in success, false on failure
 */
function wpfn_update_email( $id, $key, $value )
{
	global $wpdb;

	if ( ! $id || ! is_numeric( $id ) )
		return false;

	$id = absint( $id );
	if ( ! $id )
		return false;

	return $wpdb->update(
		$wpdb->prefix . WPFN_EMAILS,
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
 * Delta an email and all email meta form the DB
 *
 * @param $id int thi ID of the email
 * @return bool whether the deletion of the email was successful.
 */
function wpfn_delete_email( $id )
{
    global $wpdb;

    if ( ! $id || ! is_numeric( $id ) )
        return false;

    $id = absint( $id );
    if ( ! $id )
        return false;

    $table = $wpdb->prefix . WPFN_EMAILS;

    $email = $wpdb->delete(
        $table,
        array( 'ID' => $id ),
        array( '%d' )
    );

    $meta = $wpdb->delete(
        $wpdb->emailmeta,
        array( 'email_id' => $id ),
        array( '%d' )
    );

    return $email && $meta;
}

/**
 * Adds meta data field to a email.
 *
 * @param int    $email_id    Contact ID.
 * @param string $meta_key   Metadata name.
 * @param mixed  $meta_value Metadata value.
 * @param bool   $unique     Optional, default is false. Whether the same key should not be added.
 * @return int|false Meta ID on success, false on failure.
 */
function wpfn_add_email_meta($email_id, $meta_key, $meta_value, $unique = false) {
	return add_metadata('email', $email_id, $meta_key, $meta_value, $unique);
}

/**
 * Removes metadata matching criteria from a email.
 *
 * You can match based on the key, or key and value. Removing based on key and
 * value, will keep from removing duplicate metadata with the same key. It also
 * allows removing all metadata matching key, if needed.
 *
 * @param int    $email_id    Contact ID
 * @param string $meta_key   Metadata name.
 * @param mixed  $meta_value Optional. Metadata value.
 * @return bool True on success, false on failure.
 */
function wpfn_delete_email_meta($email_id, $meta_key, $meta_value = '') {
	return delete_metadata('email', $email_id, $meta_key, $meta_value);
}

/**
 * Retrieve meta field for a email.
 *
 * @param int    $email_id Contact ID.
 * @param string $key     Optional. The meta key to retrieve. By default, returns data for all keys.
 * @param bool   $single  Whether to return a single value.
 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single is true.
 */
function wpfn_get_email_meta( $email_id, $key = '', $single = true ) {
	return get_metadata('email', $email_id, $key, $single );
}

/**
 * Update email meta field based on email ID.
 *
 * Use the $prev_value parameter to differentiate between meta fields with the
 * same key and email ID.
 *
 * If the meta field for the user does not exist, it will be added.
 *
 * @param int    $email_id   Contact ID.
 * @param string $meta_key   Metadata key.
 * @param mixed  $meta_value Metadata value.
 * @param mixed  $prev_value Optional. Previous value to check before removing.
 * @return int|bool Meta ID if the key didn't exist, true on successful update, false on failure.
 */
function wpfn_update_email_meta($email_id, $meta_key, $meta_value, $prev_value = '') {
	return update_metadata('email', $email_id, $meta_key, $meta_value, $prev_value);
}

add_action( 'plugins_loaded', 'wpfn_integrate_emails_wpdb' );

/**
 * add support for the metadata API so I don't have to code it myself.
 */
function wpfn_integrate_emails_wpdb()
{
	global $wpdb;

	$wpdb->emailmeta = $wpdb->prefix . 'emailmeta';
	$wpdb->tables[] = 'emailmeta';

	return;
}

define( 'WPFN_EMAILS', 'emails' );
define( 'WPFN_EMAILS_DB_VERSION', '0.2' );

/**
 * Create the emails database table.
 */
function wpfn_create_emails_db()
{

	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();

	$table_name = $wpdb->prefix . WPFN_EMAILS;

	if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name && version_compare( get_option('wpfn_emails_db_version'), WPFN_EMAILS_DB_VERSION, '==' ) )
		return;

	$sql = "CREATE TABLE $table_name (
      ID bigint(20) NOT NULL AUTO_INCREMENT,
      content longtext NOT NULL,
      subject text NOT NULL,
      pre_header text NOT NULL,
      from_user bigint(20) NOT NULL,
      date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
      email_status VARCHAR(20) NOT NULL,
      PRIMARY KEY  (ID)
    ) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	update_option( 'wpfn_emails_db_version', WPFN_EMAILS_DB_VERSION );
}

define( 'WPFN_EMAIL_META', 'emailmeta' );
define( 'WPFN_EMAIL_META_DB_VERSION', '0.2' );

function wpfn_create_email_meta_db()
{
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();

	$table_name = $wpdb->prefix . WPFN_EMAIL_META;

	if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name && version_compare( get_option('wpfn_email_meta_db_version'), WPFN_EMAIL_META_DB_VERSION, '==' ) )
		return;

	$max_index_length = 191;

	$install_query = "CREATE TABLE $table_name (
		meta_id bigint(20) unsigned NOT NULL auto_increment,
		email_id bigint(20) unsigned NOT NULL default '0',
		meta_key varchar(255) default NULL,
		meta_value longtext,
		PRIMARY KEY  (meta_id),
		KEY email (email_id),
		KEY meta_key (meta_key($max_index_length))
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $install_query );

	update_option( 'wpfn_email_meta_db_version', WPFN_EMAIL_META_DB_VERSION );

}