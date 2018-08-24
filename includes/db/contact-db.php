<?php
/**
 * Contact database functions
 *
 * Functions to manipulate and retrieve data from the database.
 *
 * @package     wp-funnels
 * @subpackage  Includes/Contacts
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

/**
 * Adds meta data field to a contact.
 *
 * @param int    $contact_id    Contact ID.
 * @param string $meta_key   Metadata name.
 * @param mixed  $meta_value Metadata value.
 * @param bool   $unique     Optional, default is false. Whether the same key should not be added.
 * @return int|false Meta ID on success, false on failure.
 */
function wpfn_add_contact_meta($contact_id, $meta_key, $meta_value, $unique = false) {
    return add_metadata('contact', $contact_id, $meta_key, $meta_value, $unique);
}

/**
 * Removes metadata matching criteria from a contact.
 *
 * You can match based on the key, or key and value. Removing based on key and
 * value, will keep from removing duplicate metadata with the same key. It also
 * allows removing all metadata matching key, if needed.
 *
 * @param int    $contact_id    Contact ID
 * @param string $meta_key   Metadata name.
 * @param mixed  $meta_value Optional. Metadata value.
 * @return bool True on success, false on failure.
 */
function wpfn_delete_contact_meta($contact_id, $meta_key, $meta_value = '') {
    return delete_metadata('contact', $contact_id, $meta_key, $meta_value);
}

/**
 * Retrieve meta field for a contact.
 *
 * @param int    $contact_id Contact ID.
 * @param string $key     Optional. The meta key to retrieve. By default, returns data for all keys.
 * @param bool   $single  Whether to return a single value.
 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single is true.
 */
function wpfn_get_contact_meta( $contact_id, $key = '', $single = true ) {
    return get_metadata('contact', $contact_id, $key, $single );
}

/**
 * Update contact meta field based on contact ID.
 *
 * Use the $prev_value parameter to differentiate between meta fields with the
 * same key and contact ID.
 *
 * If the meta field for the user does not exist, it will be added.
 *
 * @param int    $contact_id   Contact ID.
 * @param string $meta_key   Metadata key.
 * @param mixed  $meta_value Metadata value.
 * @param mixed  $prev_value Optional. Previous value to check before removing.
 * @return int|bool Meta ID if the key didn't exist, true on successful update, false on failure.
 */
function wpfn_update_contact_meta($contact_id, $meta_key, $meta_value, $prev_value = '') {
    return update_metadata('contact', $contact_id, $meta_key, $meta_value, $prev_value);
}

/**
 * Get a contact row via the ID of the contact
 *
 * @param int $id Contact Id
 *
 * @return array|bool
 */
function wpfn_get_contact_by_id( $id )
{
    global $wpdb;

    if ( ! $id || ! is_numeric( $id ) )
        return false;

    $id = absint( $id );
    if ( ! $id )
        return false;

    $table_name = $wpdb->prefix . WPFN_CONTACTS;

    $sql_prep1 = $wpdb->prepare("SELECT * FROM $table_name WHERE ID = %d", $id);
    $contact = $wpdb->get_row( $sql_prep1, ARRAY_A );

    return $contact;
}

/**
 * Get a contact row via the email of the contact
 *
 * @param string $email Contact's Email
 *
 * @return array|bool
 */
function wpfn_get_contact_by_email( $email )
{
    global $wpdb;

    if ( ! $email || ! is_string( $email ) )
        return false;

    $email = stripslashes( strtolower( $email ) );
    if ( ! $email )
        return false;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $table_name = $wpdb->prefix . WPFN_CONTACTS;

    $sql_prep1 = $wpdb->prepare("SELECT * FROM $table_name WHERE email = %s", $email);
    $contact = $wpdb->get_row( $sql_prep1, ARRAY_A );

    return $contact;
}

function wpfn_get_user_by_email( $email )
{
    global $wpdb;

    if ( ! $email || ! is_string( $email ) )
        return false;

    $email = stripslashes( strtolower( $email ) );
    if ( ! $email )
        return false;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $table_name = $wpdb->prefix . users;

    $sql_prep1 = $wpdb->prepare("SELECT * FROM $table_name WHERE user_email = %s", $email);
    $contact = $wpdb->get_row( $sql_prep1, ARRAY_A );

    return $contact;
}

/**
 * Insert a new contact into the DB.
 *
 * @param $email string Contact's email
 * @param string $first First name
 * @param string $last Last Name
 *
 * @return false|int the contact ID on success, false on failure.
 */
function wpfn_insert_new_contact( $email, $first='', $last='', $owner_id='', $user_id='')
{
    global $wpdb;

        if ( ! $email || ! is_string( $email ) )
            return false;

        $email = sanitize_email( stripslashes( strtolower( $email ) ) );
        if ( ! $email )
            return false;

        $success = $wpdb->insert(
            $wpdb->prefix . WPFN_CONTACTS,
            array(
                'email' => $email,
                'first_name' => $first,
                'last_name' => $last,
                'owner_id' => $owner_id,
                'user_id' => $user_id,
                'optin_status' => 0,
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
 * Update information about a contact
 *
 * @param $id int Contact ID
 * @param $key string Column Name
 * @param $value string New Column Value
 *
 * @return false|int contact ID in success, false on failure
 */
function wpfn_update_contact( $id, $key, $value )
{
    global $wpdb;

    if ( ! $id || ! is_numeric( $id ) )
        return false;

    $id = absint( $id );
    if ( ! $id )
        return false;

    do_action( 'wpfn_update_contact_before', $id );

    return $wpdb->update(
        $wpdb->prefix . WPFN_CONTACTS,
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
 * Deletes the contact from the db.
 *
 * @param $id int ID of the contact
 * @return true
 */
function wpfn_delete_contact( $id )
{
    global $wpdb;

    if ( ! $id || ! is_numeric( $id ) )
        return false;

    $id = absint( $id );
    if ( ! $id )
        return false;

    do_action( 'wpfn_delete_contact_before', $id );

    //delete contact from contacts table
    $wpdb->delete(
        $wpdb->prefix . WPFN_CONTACTS,
        array( 'ID' => $id ),
        array( '%d' )
    );

    //delete the contact meta
    $wpdb->delete(
        $wpdb->contactmeta,
        array( 'ID' => $id ),
        array( '%d' )
    );

    do_action( 'wpfn_delete_contact_after' );

    return true;

}

/**
 * Quick function to update contact's email
 *
 * @param $id int Contact's ID
 * @param $email string the contact's email
 *
 * @return bool|false ID on success, false on failure
 */
function wpfn_update_contact_email( $id, $email )
{
    if ( ! $email || ! is_string( $email ) )
        return false;

    $email = stripslashes( strtolower( $email ) );
    if ( ! $email )
        return false;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    return wpfn_update_contact( $id, 'email', $email );
}

add_action( 'plugins_loaded', 'wpfn_integrate_contacts_wpdb' );

/**
 * add support for the metadata API so I don't have to code it myself.
 */
function wpfn_integrate_contacts_wpdb()
{
    global $wpdb;

    $wpdb->contacts = $wpdb->prefix . 'contacts';
    $wpdb->tables[] = 'contacts';

    $wpdb->contactmeta = $wpdb->prefix . 'contactmeta';
    $wpdb->tables[] = 'contactmeta';

    return;
}

define( 'WPFN_CONTACTS', 'contacts' );
define( 'WPFN_CONTACTS_DB_VERSION', '0.4' );

function wpfn_create_contacts_db()
{

    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $table_name = $wpdb->prefix . WPFN_CONTACTS;

    if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name && version_compare( get_option('wpfn_contacts_db_version'), WPFN_CONTACTS_DB_VERSION, '==' ) )
        return;

    $sql = "CREATE TABLE $table_name (
      ID bigint(20) NOT NULL AUTO_INCREMENT,
      email tinytext NOT NULL,
      first_name tinytext NOT NULL,
      last_name tinytext NOT NULL,
      owner_id bigint(20) NOT NULL,
      user_id bigint(20) NOT NULL,
      optin_status int NOT NULL,
      date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
      PRIMARY KEY  (ID),
      KEY email (email)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta( $sql );

    update_option( 'wpfn_contacts_db_version', WPFN_CONTACTS_DB_VERSION );

}

define( 'WPFN_CONTACT_META', 'contactmeta' );
define( 'WPFN_CONTACT_META_DB_VERSION', '0.2' );

function wpfn_create_contact_meta_db()
{
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $table_name = $wpdb->prefix . WPFN_CONTACT_META;

    if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name && version_compare( get_option('wpfn_contact_meta_db_version'), WPFN_CONTACT_META_DB_VERSION, '==' ) )
        return;

    $max_index_length = 191;

    $install_query = "CREATE TABLE $table_name (
		meta_id bigint(20) unsigned NOT NULL auto_increment,
		contact_id bigint(20) unsigned NOT NULL default '0',
		meta_key varchar(255) default NULL,
		meta_value longtext,
		PRIMARY KEY  (meta_id),
		KEY contact (contact_id),
		KEY meta_key (meta_key($max_index_length))
	) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta( $install_query );

    update_option( 'wpfn_contact_meta_db_version', WPFN_CONTACT_META_DB_VERSION );

}

/**
 * Get the name of a tag
 *
 * @param $id int the Id of the tag
 * @return string the name of the tag
 */
function wpfn_get_tag_name( $id )
{
    global $wpdb;

    if ( ! $id || ! is_numeric( $id ) )
        return false;

    $id = absint( $id );
    if ( ! $id )
        return false;

    $table = $wpdb->prefix . WPFN_CONTACT_TAGS;

    $tag = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table WHERE tag_id = %d", $id
        ), ARRAY_A
    );

    return $tag['tag_name'];
}

/**
 * Return ALL the tags...
 *
 * @return array list of tags...
 */
function wpfn_get_tags()
{
    global $wpdb;

    $table_name = $wpdb->prefix . WPFN_CONTACT_TAGS;

    return $wpdb->get_results( "SELECT * FROM $table_name ORDER BY tag_id DESC", ARRAY_A );
}

/**
 * Return ALL the tags related to a contact...
 * @param $contact_id int ID of the contact
 *
 * @return array list of tags...
 */
function wpfn_get_contact_tags( $contact_id )
{
    global $wpdb;

    $table_name = $wpdb->prefix . WPFN_CONTACT_TAG_RELATIONSHIPS;

    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE contact_id = %d ORDER BY tag_id DESC"
            , $contact_id), ARRAY_A
    );
}


/**
 * Return ALL the tags related to a contact...
 * @param $tag_id int ID of the tag
 *
 * @return array list of contact Ids...
 */
function wpfn_get_contact_ids_by_tag( $tag_id )
{
    global $wpdb;

    $table_name = $wpdb->prefix . WPFN_CONTACT_TAG_RELATIONSHIPS;

    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE tag_id = %d ORDER BY contact_id DESC"
            , $tag_id), ARRAY_A
    );
}

/**
 * Check if a tag exists, if it does return it.
 *
 * @param $id int the ID of a given tag
 * @return array|false the tag or false on failure
 */
function wpfn_tag_exists( $id )
{
    global $wpdb;

    if ( ! $id || ! is_numeric( $id ) )
        return false;

    $id = absint( $id );
    if ( ! $id )
        return false;

    $table = $wpdb->prefix . WPFN_CONTACT_TAGS;

    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table WHERE tag_id = %d", $id
        ), ARRAY_A
    );

}

/**
 * Insert a new tag into the DB
 *
 * @param $tag_name int The name of the tag
 * @param string $tag_description string Description of the tag
 * @return bool|int the ID of the new tag or false on failure.
 */
function wpfn_insert_tag( $tag_name, $tag_description='' )
{
    global $wpdb;

    if ( ! $tag_name || ! is_string( $tag_name ) )
        return false;

    $tag_name = sanitize_text_field( stripslashes( $tag_name ) );
    if ( ! $tag_name )
        return false;

    $tag_description = sanitize_text_field( $tag_description );

    $success = $wpdb->insert(
        $wpdb->prefix . WPFN_CONTACT_TAGS,
        array(
            'tag_name' => $tag_name,
            'tag_description' => $tag_description
        )
    );

    if ( $success ){
        return $wpdb->insert_id;
    } else {
        return false;
    }
}

/**
 * Update info about a tag
 *
 * @param $id int ID of the tag
 * @param $column string the column to update
 * @param string $value the value to update the column to
 * @return bool whether the update was successful.
 */
function wpfn_update_tag( $id, $column, $value='' )
{
    global $wpdb;

    if ( ! $id || ! is_numeric( $id ) )
        return false;

    $id = absint( $id );
    if ( ! $id )
        return false;

    $success = $wpdb->update(
        $wpdb->prefix . WPFN_CONTACT_TAGS,
        array(
            $column => $value
        ),
        array(
            'tag_id' => $id
        )
    );

    return $success;
}

/**
 * Delete a tag, an all tag relationships
 *
 * @param $id int the ID of the tag to delete
 *
 * @return bool whether the deletion was fully successful.
 */
function wpfn_delete_tag( $id )
{
    global $wpdb;

    if ( ! $id || ! is_numeric( $id ) )
        return false;

    $id = absint( $id );
    if ( ! $id )
        return false;

    $a = $wpdb->delete(
        $wpdb->prefix . WPFN_CONTACT_TAG_RELATIONSHIPS,
        array(
            'tag_id' => $id
        ),
        array(
            '%d'
        )
    );

    $b = $wpdb->delete(
        $wpdb->prefix . WPFN_CONTACT_TAGS,
        array(
            'tag_id' => $id
        ),
        array(
            '%d'
        )
    );

    return $a && $b;
}

/**
 * Gets contact -> tag relationship
 *
 * @param $contact_id int the ID of the contact
 * @param $tag_id int the ID of the tag
 * @return array the relationship.
 */
function wpfn_get_contact_tag_relationship( $contact_id, $tag_id )
{
    global $wpdb;

    if ( ! $contact_id || ! is_numeric( $contact_id ) || ! $tag_id || ! is_numeric( $tag_id ) )
        return false;

    $contact_id = absint( $contact_id );
    $tag_id = absint( $tag_id );
    if ( ! $contact_id || ! $tag_id )
        return false;

    return $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM " . $wpdb->prefix . WPFN_CONTACT_TAG_RELATIONSHIPS . "
        WHERE tag_id = %d AND contact_id = %d", $tag_id, $contact_id ), ARRAY_A
    );
}

/**
 * Add a tag relationship to the DB
 *
 * @param $contact_id int the ID of the contact
 * @param $tag_id int the ID of the tag
 * @return int 1
 */
function wpfn_insert_contact_tag_relationship( $contact_id, $tag_id )
{
    global $wpdb;

    if ( ! $contact_id || ! is_numeric( $contact_id ) || ! $tag_id || ! is_numeric( $tag_id ) )
        return false;

    $contact_id = absint( $contact_id );
    $tag_id = absint( $tag_id );
    if ( ! $contact_id || ! $tag_id )
        return false;

    if ( wpfn_get_contact_tag_relationship( $contact_id, $tag_id ) )
        return false;

    return $wpdb->insert(
        $wpdb->prefix . WPFN_CONTACT_TAG_RELATIONSHIPS,
        array(
            'contact_id' => $contact_id,
            'tag_id'     => $tag_id
        ),
        array(
            '%d',
            '%d'
        )
    );
}

/**
 * delete a tag relationship from the DB
 *
 * @param $contact_id int the ID of the contact
 * @param $tag_id int the ID of the tag
 * @return int 1
 */
function wpfn_delete_contact_tag_relationship( $contact_id, $tag_id )
{
    global $wpdb;

    if ( ! $contact_id || ! is_numeric( $contact_id ) || ! $tag_id || ! is_numeric( $tag_id ) )
        return false;

    $contact_id = absint( $contact_id );
    $tag_id = absint( $tag_id );
    if ( ! $contact_id || ! $tag_id )
        return false;

    return $wpdb->delete(
        $wpdb->prefix . WPFN_CONTACT_TAG_RELATIONSHIPS,
        array(
            'contact_id' => $contact_id,
            'tag_id'     => $tag_id
        ),
        array(
            '%d',
            '%d'
        )
    );
}

/**
 * Delete a tag relationship from the DB
 *
 * @param $contact_id int the ID of the contact
 * @param $tag_id int the ID of the tag
 * @return int 1
 */
function wpdn_delete_contact_tag_relationship(  $contact_id, $tag_id )
{
    global $wpdb;

    if ( ! $contact_id || ! is_numeric( $contact_id ) || ! $tag_id || ! is_numeric( $tag_id ) )
        return false;

    $contact_id = absint( $contact_id );
    $tag_id = absint( $tag_id );
    if ( ! $contact_id || ! $tag_id )
        return false;

    return $wpdb->delete(
        $wpdb->prefix . WPFN_CONTACT_TAG_RELATIONSHIPS,
        array(
            'contact_id' => $contact_id,
            'tag_id'     => $tag_id
        ),
        array(
            '%d',
            '%d'
        )
    );
}

/**
 * Get the count of tag relationships by contact or by tag.
 *
 * @param $by string column reference
 * @param $id int the ID of the object to query, tag or contact
 * @return int|false, the number or relationships or false on failure.
 */
function wpfn_count_contact_tag_relationships( $by, $id )
{
    global $wpdb;

    if ( ! $id || ! is_numeric( $id ) )
        return false;

    $id = absint( $id );
    if ( ! $id )
        return false;

    $table = $wpdb->prefix . WPFN_CONTACT_TAG_RELATIONSHIPS;

    return $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM $table
            WHERE $by = %d", $id
        )
    );
}

define( 'WPFN_CONTACT_TAGS', 'contact_tags' );
define( 'WPFN_CONTACT_TAGS_DB_VERSION', '0.2' );

function wpfn_create_contact_tags_db()
{

    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $table_name = $wpdb->prefix . WPFN_CONTACT_TAGS;

    if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name && version_compare( get_option('wpfn_contact_tags_db_version'), WPFN_CONTACT_TAGS_DB_VERSION, '==' ) )
        return;

    $sql = "CREATE TABLE $table_name (
      tag_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      tag_name varchar(200) NOT NULL DEFAULT '',
      tag_description longtext NOT NULL,
      PRIMARY KEY  (tag_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta( $sql );

    update_option( 'wpfn_contact_tags_db_version', WPFN_CONTACT_TAGS_DB_VERSION );

}

define( 'WPFN_CONTACT_TAG_RELATIONSHIPS', 'contact_tag_relationships' );
define( 'WPFN_CONTACT_TAG_RELATIONSHIPS_DB_VERSION', '0.3' );

function wpfn_create_contact_tag_relationships_db()
{

    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $table_name = $wpdb->prefix . WPFN_CONTACT_TAG_RELATIONSHIPS;

    if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name && version_compare( get_option('wpfn_contact_tag_relationships_db_version'), WPFN_CONTACT_TAG_RELATIONSHIPS_DB_VERSION, '==' ) )
        return;

    $sql = "CREATE TABLE $table_name (
      tag_id bigint(20) unsigned NOT NULL DEFAULT 0,
      contact_id bigint(20) unsigned NOT NULL DEFAULT 0,
      PRIMARY KEY (contact_id,tag_id),
      KEY tag_id (tag_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta( $sql );

    update_option( 'wpfn_contact_tag_relationships_db_version', WPFN_CONTACT_TAG_RELATIONSHIPS_DB_VERSION );

}