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
	 * WPGH_Contact constructor.
	 *
	 * @param string|int $email_or_id either the email or the id of the contact to retrieve
	 */
	function __construct( $email_or_id )
	{
		if ( ! $email_or_id )
			return;

		if ( is_numeric( $email_or_id ) ) {
			$id = absint( $email_or_id );
			$contact = wpgh_get_contact_by_id( $id );
		} elseif ( is_string( $email_or_id ) ) {
			$contact = wpgh_get_contact_by_email( $email_or_id );
		} else {
			$contact = wpgh_get_contact_by_id( $email_or_id );
		}

		$this->ID = intval( $contact['ID'] );
		$this->email = strtolower( $contact['email'] );
		$this->first_name = ucfirst( $contact['first_name'] );
		$this->last_name = ucfirst( $contact['last_name'] );
		$this->optin_status = intval( $contact['optin_status'] );
		$this->activity = wpgh_get_contact_meta( $this->ID, 'activity_log', true );
		$this->date_created = $contact['date_created'];
	}

	/**
	 * Get the contact's ID
	 *
	 * @return int the Id of the contact
	 */
	function get_id()
	{
		return $this->ID;
	}

	/**
	 * Get the contact's first name
	 *
	 * @return string
	 */
	function get_first()
	{
		return $this->first_name;
	}

	/**
	 * Get the contact's last name
	 *
	 * @return string
	 */
	function get_last()
	{
		return $this->last_name;
	}

	/**
	 * Get the full name of the contact
	 *
	 * @return string
	 */
	function get_full()
	{
		return $this->first_name . ' ' . $this->last_name;
	}

	/**
	 * Get the Email of the contact
	 *
	 * @return string
	 */
	function get_email()
	{
		return $this->email;
	}

	/**
	 * Get the phone number of the contact
	 *
	 * @return string the phone number
	 */
	function get_phone()
	{
		return wpgh_get_contact_meta( $this->ID, 'primary_phone', true );
	}

	/**
	 * Get the extension of the phone #
	 *
	 * @return string the extension of the contact's phone #
	 */
	function get_phone_extension()
	{
		return wpgh_get_contact_meta( $this->ID, 'primary_phone_extension', true );
	}

	/**
	 * Get the full phone number with extension
	 *
	 * @return string
	 */
	function get_phone_with_extension()
	{
		return $this->get_phone() . ' x' . $this->get_phone_extension();
	}

	/**
	 * Get the optin status of the contact
	 *
	 * @return int
	 */
	function get_optin_status()
	{
		return $this->optin_status;
	}

	/**
	 * Returns the recent activity of the contact.
	 *
	 * @return string
	 */
	function get_activity()
	{
		return $this->activity;
	}

	/**
	 * Get the activity in an array format for easy manipulation.
	 *
	 * @return array|false the activity in an array format
	 */
	function get_parsed_activity()
	{
		if ( empty( $this->activity ) )
			return false;

		$activity = explode( PHP_EOL, $this->activity );
		$activity = array_map( 'trim', $activity );
		foreach ( $activity as $i => $entry ){
			$activity[$i] = explode( ' | ', $entry );
		}
		return $activity;
	}

	/**
	 * Return the array of tags belonging to the contact
	 *
	 * @return array|mixed
	 */
	function get_tags()
	{
		return wpgh_get_contact_tags( $this->ID );
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
	    return wpgh_has_tag( $this->ID, $tag_id_or_name );
	}

	/**
	 * Get custom data about the contact.
	 *
	 * @param string $meta_key the meta key to retrieve
	 *
	 * @return mixed
	 */
	function get_meta( $meta_key )
	{
		return wpgh_get_contact_meta( $this->ID, $meta_key, true );
	}

	function __toString()
    {
        return $this->get_email();
    }

}