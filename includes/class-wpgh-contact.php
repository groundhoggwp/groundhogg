<?php
/**
 * Contacts Class
 *
 * This class is a readonly format for easily access data of a customer.
 *
 * @package     groundhogg
 * @subpackage  Includes/Contacts
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_Contact
{

	/**
	 * @var int $ID The Contact's Id
	 */
	public $ID;

	/**
	 * @var string Contact's Primary Email
	 */
	public $email;

	/**
	 * @var string $first_name Contact's First Name
	 */
	public $first_name;

	/**
	 * @var string $last_name Contact's Last Name
	 */
	public $last_name;

    /**
     * @var string the full name of the contact
     */
	public $full_name;

	/**
	 * @var string $date_created Date & time the contact was created
	 */
	public $date_created;

	/**
	 * @var array $tags list of tag ids the contact has
	 */
	public $tags;

	/**
	 * @var string $activity an account of all activity associated with the contact.
	 */
	public $activity;

	/**
	 * @var int $optin_status the optin status of the contact
	 */
	public $optin_status;

    /**
     * @var int the owner ID
     */
	public $owner;

    /**
     * The contact notes
     *
     * @var string
     */
    public $notes;

	/**
	 * WPGH_Contact constructor.
	 *
	 * @param string|int $_id_or_email either the email or the id of the contact to retrieve
     * @param bool get contact via the User ID
	 */
    public function __construct( $_id_or_email = false, $by_user_id = false ) {


        if ( false === $_id_or_email || ( is_numeric( $_id_or_email ) && (int) $_id_or_email !== absint( $_id_or_email ) ) ) {
            return false;
        }

        $by_user_id = is_bool( $by_user_id ) ? $by_user_id : false;

        if ( is_numeric( $_id_or_email ) ) {
            $field = $by_user_id ? 'user_id' : 'ID';
        } else {
            $field = 'email';
        }

        //$contact = WPGH()->contacts->get_contact_by( $field, $_id_or_email );
        $contact = wpgh_get_contact_by( $field, $_id_or_email );

        if ( empty( $contact ) || ! is_object( $contact ) ) {
            return false;
        }

        $this->setup_contact( $contact );
    }

    /**
     * Setup the default contact args
     *
     * @param $contact object|array
     */
	private function setup_contact( $contact )
    {

        foreach ( (object) $contact as $key => $value)
        {
            switch ( $key ){
                case 'ID':

                    $this->ID = intval( $contact->ID );

                    break;
                case 'first_name':

                    $this->first_name = ucfirst( $contact->first_name );

                    break;
                case 'last_name':

                    $this->last_name = ucfirst( $contact->last_name );

                    break;
                case 'email':

                    $this->email = strtolower( $contact->email );

                    break;
                case 'optin_status':

                    $this->optin_status = intval( $contact->optin_status );

                    break;
                case 'owner':

                    $this->owner = intval( $contact->owner );

                    break;
                case 'date_created':

                    $this->date_created = $contact->date_created;

                    break;
                default:
                    $this->$key = $value;

            }

        }

        $this->full_name = sprintf( "%s %s", $this->first_name, $this->last_name );

        $this->tags = array_map( 'intval', wpgh_get_contact_tags( $this->ID ) );
//        $this->tags = array_map( 'intval', WPGH->tag_relationships->get_column_by( 'contact_id', $this->ID, 'tag_id' ) );

        $this->notes = $this->get_meta('notes' );

    }

    /**
     * Update the contact with the given information
     *
     * @param $data
     * @return bool
     */
    public function update( $data = array() ) {

        if ( empty( $data ) ) {
            return false;
        }

        //$data = $this->sanitize_columns( $data );

        do_action( 'wpgh_contact_pre_update', $this->ID, $data );

        $updated = false;

//        if ( WPGH()->contacts->update( $this->ID, $data ) )
        if ( wpgh_update_contact( $this->ID, $data ) )
        {

            $contact = wpgh_get_contact_by( 'ID', $this->ID );
            //$contact = $this->db->get_contact_by( 'ID', $this->ID );
            $this->setup_contact( $contact);

            $updated = true;

        }

        do_action( 'wpgh_contact_post_update', $updated, $this->ID, $data );

        return $updated;
    }

    /**
     * Return whether the contact is marketable or not.
     *
     * @return bool
     */
    public function is_marketable()
    {
        /* check for strict GDPR settings */
        if ( wpgh_is_gdpr() && wpgh_is_gdpr_strict() )
        {
            $consent = $this->get_meta('gdpr_consent' );

            if ( $consent !== 'yes' )
                return false;
        }

        switch ( $this->optin_status )
        {
            case WPGH_UNCONFIRMED:
                /* check for grace period if necessary */
                if ( wpgh_is_confirmation_strict() )
                {
                    if ( ! wpgh_is_in_grace_period( $this->ID ) )
                        return false;
                }

                return true;
                break;
            case WPGH_CONFIRMED:
                return true;
                break;
            case WPGH_SPAM;
            case WPGH_HARD_BOUNCE;
            case WPGH_UNSUBSCRIBED:
                return false;
                break;
            case WPGH_WEEKLY:
                $last_sent = $this->get_meta( 'last_sent' );
                return ( time() - intval( $last_sent ) ) > 7 * 24 * HOUR_IN_SECONDS;
                break;
            case WPGH_MONTHLY:
                $last_sent = $this->get_meta( 'last_sent' );
                return ( time() - intval( $last_sent ) ) > 30 * 24 * HOUR_IN_SECONDS;
                break;
            default:
                return true;
                break;
        }
    }

    /**
     * Add a note to the contact
     *
     * @param $note
     * @return bool
     */
    public function add_note( $note )
    {
        if ( ! $note || ! is_string( $note ) )
            return false;

        $note = sanitize_textarea_field( $note );

        $current_notes = $this->notes;

        $new_notes = sprintf( "===== %s =====\n\n", date_i18n( get_option( 'date_format' ) ) );
        $new_notes .= sprintf( "%s\n\n", $note );
        $new_notes .= $current_notes;

        $new_notes = sanitize_textarea_field( $new_notes );

        $this->update_meta( 'notes', $new_notes );
        $this->notes = $new_notes;

        do_action( 'wpgh_contact_note_added', $this->ID );

        return true;
    }

    /**
     * Get some contact meta
     *
     * @param $key
     * @return mixed
     */
    public function get_meta( $key )
    {
        // return WPGH()->contact_meta->get_meta( $this->ID, $key );
        return wpgh_get_contact_meta( $this->ID, $key );
    }

    /**
     * Update some information about the contact
     *
     * @param $key
     * @param $value
     *
     * @return mixed
     */
    public function update_meta( $key, $value )
    {
        // return WPGH()->contact_meta->update_meta( $this->ID, $key, $value );
        return wpgh_update_contact_meta( $this->ID, $key, $value );
    }

    /**
     * Add some meta
     *
     * @param $key
     * @param $value
     *
     * @return mixed
     */
    public function add_meta( $key, $value )
    {
        // return WPGH()->contact_meta->add_meta( $this->ID, $key, $value );
        return wpgh_add_contact_meta( $this->ID, $key, $value );
    }

    /**
     * Delete some meta
     *
     * @param $key
     * @return mixed
     */
    public function delete_meta( $key )
    {
        // return WPGH()->contact_meta->delete_meta( $this->ID, $key );
        return wpgh_delete_contact_meta( $this->ID, $key );
    }


    /**
     * Magic get method
     *
     * @param $key
     * @return bool|mixed
     */
    public function __get( $key )
    {
        if ( property_exists( $this, $key ) ){

            return $this->$key;

        } elseif ( method_exists( $this, $key ) ) {

            return call_user_func( array( $this, $key ) );

        } else {

            $exists = $this->get_meta( $key );

            if ( $exists )
                return $exists;

        }

        return false;
    }

    /**
     * Add a list of tags or a single tag top the contact
     *
     * @param $tag_id_or_array array|int
     * @return bool
     */
    public function add_tag( $tag_id_or_array )
    {
        if ( is_array( $tag_id_or_array ) ){

            // $tags = WPGH()->tags->validate( $tag_id_or_array );
            $tags = wpgh_validate_tags( $tag_id_or_array );

            foreach ( $tags as $tag_id )
            {

                if ( ! $this->has_tag( $tag_id ) ){

                    $this->tags[] = $tag_id;

                    // WPGH()->tag_relationships->add( $this->ID, $tag_id );
                    wpgh_insert_contact_tag_relationship( $this->ID, $tag_id );

                }

            }

            return true;

        } else if ( is_numeric( $tag_id_or_array ) ) {

            $tag_id = absint( $tag_id_or_array );

            if ( wpgh_tag_exists( $tag_id ) && ! $this->has_tag( $tag_id ) )
            {
                $this->tags[] = $tag_id;

                // return WPGH()->tag_relationships->add( $this->ID, $tag_id );
                return wpgh_insert_contact_tag_relationship( $this->ID, $tag_id );

            }

        }

        return false;
    }


    /**
     * Remove a single tag or several tag from the contact
     *
     * @param $tag_id_or_array
     * @return bool
     */
    public function remove_tag( $tag_id_or_array )
    {
        if ( is_array( $tag_id_or_array ) ){

            // $tags = WPGH()->tags->validate( $tag_id_or_array );
            $tags = wpgh_validate_tags( $tag_id_or_array );

            foreach ( $tags as $tag_id )
            {

                if ( $this->has_tag( $tag_id ) ){

                    if (($key = array_search($tag_id, $this->tags)) !== false) {
                        unset($this->tags[$key]);
                        // WPGH()->tag_relationships->delete( $this->ID, $tag_id );
                        wpgh_delete_contact_tag_relationship( $this->ID, $tag_id );
                    }

                }

            }

            return true;

        } else if ( is_numeric( $tag_id_or_array ) ) {

            $tag_id = absint( $tag_id_or_array );

            // WPGH()->tags->exists( $tag_id );
            if ( wpgh_tag_exists( $tag_id ) && ! $this->has_tag( $tag_id ) )
            {
                if (($key = array_search($tag_id, $this->tags)) !== false) {
                    unset($this->tags[$key]);
                    // return WPGH()->tag_relationships->delete( $this->ID, $tag_id );
                    return wpgh_delete_contact_tag_relationship( $this->ID, $tag_id );
                }

            }

        }

        return false;
    }

	/**
	 * return whether the contact has a specific tag
	 *
	 * @param int|string $tag_id_or_name the ID or name or the tag
	 *
	 * @return bool
	 */
	function has_tag( $tag_id_or_name )
	{

	    if ( ! is_numeric( $tag_id_or_name ) )
        {

            $tag = (object) wpgh_get_tag( $tag_id_or_name );

            $tag_id = intval( $tag->tag_id );

        } else {

	        $tag_id = absint( $tag_id_or_name );

        }

	    return in_array( $tag_id, $this->tags );
	}

    /**
     * Output a contact. Just give the email back
     *
     * @return string
     */
	function __toString()
    {
        return $this->email;
    }

}