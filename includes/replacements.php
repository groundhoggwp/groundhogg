<?php

namespace Groundhogg;

use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Utils\DateTimeHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Replacements
 *
 * The inspiration for this class came from EDD_Email_Tags by easy digital downloads.
 * But ours is better because it allows for dynamic arguments passed with the replacements code.
 *
 * @since       File available since Release 0.1
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
 */
class Replacements implements \JsonSerializable {

	/**
	 * Array of replacement codes and their callback functions
	 *
	 * @var array
	 */
	public $replacement_codes = [];

	/**
	 * Groups to which codes can be assigned
	 *
	 * @var array
	 */
	public $replacement_code_groups = [];

	/**
	 * The contact ID
	 *
	 * @var int
	 */
	protected $contact_id;

	/**
	 * @var Contact
	 */
	protected $current_contact;

	/**
	 * Replacements constructor.
	 */
	public function __construct() {

		add_action( 'init', [ $this, 'setup_defaults' ] );

		if ( is_admin_groundhogg_page() ) {
			add_action( 'admin_footer', [ $this, 'replacements_in_footer' ] );
		}

		// Todo: add additional hooks that might trigger a cache invalidation
		add_action( 'groundhogg/contact/post_update', [ $this, 'invalidate_replacements_cache' ] );
		add_action( 'groundhogg/api/contact/updated', [ $this, 'invalidate_replacements_cache' ] );
		// New post is published
		add_action( 'new_to_publish', [ $this, 'invalidate_replacements_cache' ] );
		add_action( 'draft_to_publish', [ $this, 'invalidate_replacements_cache' ] );
		add_action( 'future_to_publish', [ $this, 'invalidate_replacements_cache' ] );
		// When post is saved (todo might not need)
		add_action( 'save_post', [ $this, 'invalidate_replacements_cache' ] );
	}

	/**
	 * Get the codes that appear on the frontent
	 *
	 * @return array
	 */
	public function get_codes_for_frontend() {
		return array_map( function ( $replacement ) {

			unset( $replacement['hidden'] );
			unset( $replacement['callback'] );
			unset( $replacement['callback_plain'] );

			return $replacement;
		}, array_filter( $this->replacement_codes, function ( $replacement ) {
			return ! $replacement['hidden'];
		} ) );
	}

	/**
	 * Setup the default replacement codes
	 */
	public function setup_defaults() {

		$groups = [
			'contact'    => __( 'Contact Info', 'groundhogg' ),
			'crm'        => __( 'CRM', 'groundhogg' ),
			'address'    => __( 'Address', 'groundhogg' ),
			'user'       => __( 'WP User', 'groundhogg' ),
			'owner'      => __( 'Contact Owner', 'groundhogg' ),
			'activity'   => __( 'Activity', 'groundhogg' ),
			'site'       => __( 'Site', 'groundhogg' ),
			'post'       => __( 'Post', 'groundhogg' ),
			'compliance' => __( 'Compliance', 'groundhogg' ),
			'email'      => __( 'Email', 'groundhogg' ),
			'other'      => __( 'Other', 'groundhogg' ),
			'formatting' => __( 'Formatting', 'groundhogg' ),
		];

		$replacement_groups = apply_filters( 'groundhogg/replacements/default_groups', $groups );

		foreach ( $replacement_groups as $group => $name ) {
			$this->add_group( $group, $name );
		}

		$replacements = [
			[
				'code'        => 'id',
				'group'       => 'contact',
				'callback'    => [ $this, 'replacement_id' ],
				'name'        => __( 'Contact ID', 'groundhogg' ),
				'description' => _x( 'The contact\'s ID number.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'first',
				'group'       => 'contact',
				'callback'    => [ $this, 'replacement_first_name' ],
				'name'        => __( 'First Name', 'groundhogg' ),
				'description' => _x( 'The contact\'s first name.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'first_name',
				'callback'    => [ $this, 'replacement_first_name' ],
				'description' => _x( 'The contact\'s first name.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'first_initial',
				'callback'    => [ $this, 'replacement_first_initial' ],
				'description' => _x( 'The first letter of the contact\'s first name.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'last',
				'group'       => 'contact',
				'callback'    => [ $this, 'replacement_last_name' ],
				'name'        => __( 'Last Name', 'groundhogg' ),
				'description' => _x( 'The contact\'s last name.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'last_name',
				'callback'    => [ $this, 'replacement_last_name' ],
				'description' => _x( 'The contact\'s last name.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'last_initial',
				'callback'    => [ $this, 'replacement_last_initial' ],
				'description' => _x( 'The first letter of the contact\'s last name.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'full_name',
				'group'       => 'contact',
				'callback'    => [ $this, 'replacement_full_name' ],
				'name'        => __( 'Full Name', 'groundhogg' ),
				'description' => _x( 'The contact\'s full name.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'username',
				'group'       => 'user',
				'callback'    => [ $this, 'replacement_username' ],
				'name'        => __( 'User Name', 'groundhogg' ),
				'description' => _x( 'The contact\'s user record user name.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'email',
				'group'       => 'contact',
				'callback'    => [ $this, 'replacement_email' ],
				'name'        => __( 'Email Address', 'groundhogg' ),
				'description' => _x( 'The contact\'s email address.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'phone',
				'group'       => 'contact',
				'callback'    => [ $this, 'replacement_phone' ],
				'name'        => __( 'Primary Phone', 'groundhogg' ),
				'description' => _x( 'The contact\'s phone number.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'phone_ext',
				'group'       => 'contact',
				'callback'    => [ $this, 'replacement_phone_ext' ],
				'name'        => __( 'Primary Phone (with extension)', 'groundhogg' ),
				'description' => _x( 'The contact\'s phone number with the extension if available.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'mobile_phone',
				'group'       => 'contact',
				'callback'    => [ $this, 'replacement_mobile_phone' ],
				'name'        => __( 'Mobile Phone', 'groundhogg' ),
				'description' => _x( 'The contact\'s mobile phone number.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'line1',
				'group'       => 'address',
				'callback'    => [ $this, 'replacement_line1' ],
				'name'        => __( 'Line 1', 'groundhogg' ),
				'description' => _x( 'The contact\'s street address.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'line2',
				'group'       => 'address',
				'callback'    => [ $this, 'replacement_line2' ],
				'name'        => __( 'Line 2', 'groundhogg' ),
				'description' => _x( 'The contact\'s street address.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'city',
				'group'       => 'address',
				'callback'    => [ $this, 'replacement_city' ],
				'name'        => __( 'City', 'groundhogg' ),
				'description' => _x( 'The contact\'s city.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'state',
				'group'       => 'address',
				'callback'    => [ $this, 'replacement_state' ],
				'name'        => __( 'State', 'groundhogg' ),
				'description' => _x( 'The contact\'s state.', 'replacement', 'groundhogg' ),

			],
			[
				'code'        => 'zip_code',
				'group'       => 'address',
				'callback'    => [ $this, 'replacement_zip' ],
				'name'        => __( 'Zip Code', 'groundhogg' ),
				'description' => _x( 'The contact\'s zip code.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'country',
				'group'       => 'address',
				'callback'    => [ $this, 'replacement_country' ],
				'name'        => __( 'Country', 'groundhogg' ),
				'description' => _x( 'The contact\'s country.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'country_code',
				'group'       => 'address',
				'callback'    => [ $this, 'replacement_country_code' ],
				'name'        => __( 'Country Code', 'groundhogg' ),
				'description' => _x( 'The contact\'s country code.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'address',
				'group'       => 'address',
				'callback'    => [ $this, 'replacement_address' ],
				'name'        => __( 'Full Address', 'groundhogg' ),
				'description' => _x( 'The contact\'s full address.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'ip_address',
				'group'       => 'address',
				'callback'    => [ $this, 'replacement_ip_address' ],
				'name'        => __( 'IP Address', 'groundhogg' ),
				'description' => _x( 'The contact\'s IP address.', 'replacement', 'groundhogg' ),

			],
			[
				'code'        => 'time_zone',
				'group'       => 'address',
				'callback'    => [ $this, 'replacement_time_zone' ],
				'name'        => __( 'Time Zone', 'groundhogg' ),
				'description' => _x( 'The contact\'s time zone.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'birthday',
				'group'       => 'contact',
				'callback'    => [ $this, 'replacement_birthday' ],
				'name'        => __( 'Birthday', 'groundhogg' ),
				'description' => _x( 'The contact\'s birthday.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'upcoming_birthday',
				'group'       => 'contact',
				'callback'    => [ $this, 'replacement_upcoming_birthday' ],
				'name'        => __( 'Upcoming Birthday', 'groundhogg' ),
				'description' => _x( 'The contact\'s next birthday.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'website',
				'group'       => 'contact',
				'callback'    => [ $this, 'replacement_website' ],
				'name'        => __( 'Website', 'groundhogg' ),
				'description' => _x( 'The contact\'s website as parsed from their email address.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'tag_names',
				'group'       => 'crm',
				'callback'    => [ $this, 'tag_names' ],
				'name'        => __( 'Tag Names', 'groundhogg' ),
				'description' => _x( 'List of tags applied to the contact.', 'replacement', 'groundhogg' ),
			],
			[
				'code'         => 'meta',
				'group'        => 'crm',
				'default_args' => 'meta_key',
				'callback'     => [ $this, 'replacement_meta' ],
				'name'         => __( 'Meta Data', 'groundhogg' ),
				'description'  => _x( 'Any meta data related to the contact. Usage: {meta.attribute}', 'replacement', 'groundhogg' ),
			],
			[
				'code'         => 'profile_picture',
				'group'        => 'crm',
				'default_args' => '300',
				'callback'     => [ $this, 'replacement_profile_picture' ],
				'name'         => __( 'Profile Picture', 'groundhogg' ),
				'description'  => _x( 'The contact\'s profile picture.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'optin_status',
				'group'       => 'crm',
				'callback'    => [ $this, 'replacement_optin_status' ],
				'name'        => __( 'Opt-in Status', 'groundhogg' ),
				'description' => _x( 'The contact\'s opt-in status.', 'replacement', 'groundhogg' ),
			],
			[
				'code'         => 'user',
				'group'        => 'user',
				'default_args' => 'attribute',
				'callback'     => [ $this, 'replacement_user' ],
				'name'         => __( 'User Data', 'groundhogg' ),
				'description'  => _x( 'Any data related to the contact\'s linked user record. Usage: {user.attribute}', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'business_name',
				'group'       => 'site',
				'callback'    => [ $this, 'replacement_business_name' ],
				'name'        => __( 'Name', 'groundhogg' ),
				'description' => _x( 'The business name as defined in the settings.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'business_phone',
				'group'       => 'site',
				'callback'    => [ $this, 'replacement_business_phone' ],
				'name'        => __( 'Phone', 'groundhogg' ),
				'description' => _x( 'The business phone number as defined in the settings.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'business_address',
				'group'       => 'site',
				'callback'    => [ $this, 'replacement_business_address' ],
				'name'        => __( 'Address', 'groundhogg' ),
				'description' => _x( 'The business address as defined in the settings.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'site_url',
				'group'       => 'site',
				'callback'    => [ $this, 'site_url' ],
				'name'        => __( 'URL', 'groundhogg' ),
				'description' => _x( 'The site url.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'owner_first_name',
				'group'       => 'owner',
				'callback'    => [ $this, 'replacement_owner_first_name' ],
				'name'        => __( 'First Name', 'groundhogg' ),
				'description' => _x( 'The contact owner\'s name.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'owner_last_name',
				'group'       => 'owner',
				'callback'    => [ $this, 'replacement_owner_last_name' ],
				'name'        => __( 'Last Name', 'groundhogg' ),
				'description' => _x( 'The contact owner\'s name.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'owner_email',
				'group'       => 'owner',
				'callback'    => [ $this, 'replacement_owner_email' ],
				'name'        => __( 'Email', 'groundhogg' ),
				'description' => _x( 'The contact owner\'s email address.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'owner_phone',
				'group'       => 'owner',
				'callback'    => [ $this, 'replacement_owner_phone' ],
				'name'        => __( 'Phone', 'groundhogg' ),
				'description' => _x( 'The contact owner\'s phone number.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'owner_signature',
				'group'       => 'owner',
				'callback'    => [ $this, 'replacement_owner_signature' ],
				'name'        => __( 'Email Signature', 'groundhogg' ),
				'description' => _x( 'The contact owner\'s signature.', 'replacement', 'groundhogg' ),
			],
			[
				'code'         => 'owner',
				'group'        => 'owner',
				'default_args' => 'attribute',
				'callback'     => [ $this, 'replacement_owner' ],
				'name'         => __( 'Owner Data', 'groundhogg' ),
				'description'  => _x( 'Any data related to the contact\'s linked owner. Usage: {owner.attribute}', 'replacement', 'groundhogg' ),
			],
			[
				'code'           => 'confirmation_link',
				'group'          => 'compliance',
				'callback'       => [ $this, 'replacement_confirmation_link' ],
				'callback_plain' => [ $this, 'replacement_confirmation_link_plain_text' ],
				'name'           => __( 'Confirmation Link', 'groundhogg' ),
				'description'    => _x( 'A link to confirm the email address of a contact.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'confirmation_link_raw',
				'group'       => 'compliance',
				'callback'    => [ $this, 'replacement_confirmation_link_raw' ],
				'name'        => __( 'Raw Confirmation Link', 'groundhogg' ),
				'description' => _x( 'A link to confirm the email address of a contact which can be placed in a button or link.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'unsubscribe_link',
				'group'       => 'compliance',
				'callback'    => [ $this, 'replacement_unsubscribe_link' ],
				'name'        => __( 'Unsubscribe Link', 'groundhogg' ),
				'description' => _x( 'A link that will unsubscribe the contact.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'auto_login_link',
				'group'       => 'site',
				'callback'    => [ $this, 'replacement_auto_login_link' ],
				'name'        => __( 'Auto-Login link', 'groundhogg' ),
				'description' => _x( 'Automatically login the contact if they have a user account.', 'replacement', 'groundhogg' ),
			],
			[
				'code'         => 'date',
				'group'        => 'site',
				'default_args' => 'Y-m-d|now',
				'callback'     => [ $this, 'replacement_date' ],
				'name'         => __( 'Date', 'groundhogg' ),
				'description'  => _x( 'Insert a dynamic date based on the site\'s timezone. Usage {date.format|time}. Example: {date.Y-m-d|+2 days}', 'replacement', 'groundhogg' ),
			],
			[
				'code'         => 'local_date',
				'group'        => 'site',
				'default_args' => 'Y-m-d|now',
				'callback'     => [ $this, 'replacement_local_date' ],
				'name'         => __( 'Local Date', 'groundhogg' ),
				'description'  => _x( 'Same as {date} but will display in local time of the contact instead of the site.', 'replacement', 'groundhogg' ),
			],
			[
				'code'         => 'form_submission',
				'group'        => 'activity',
				'default_args' => 'layout="stacked" fields="all"',
				'callback'     => [ $this, 'replacement_form_submission' ],
				'name'         => __( 'Form Submission', 'groundhogg' ),
				'description'  => _x( 'All the responses from the most recent form submission.', 'replacement', 'groundhogg' ),
			],
			[
				'code'           => 'files',
				'group'          => 'crm',
				'callback'       => [ $this, 'replacement_files' ],
				'callback_plain' => [ $this, 'replacement_files_plain_text' ],
				'name'           => __( 'Files List', 'groundhogg' ),
				'description'    => _x( 'Insert all the files in a contact\'s file box.', 'replacement', 'groundhogg' ),
			],
			[
				'code'         => 'GET',
				'group'        => 'other',
				'callback'     => [ $this, 'replacement_get_params' ],
				'name'         => __( '$_GET', 'groundhogg' ),
				'default_args' => 'url_param',
				'description'  => _x( 'Retrieve something from the URL query string. Only works on the frontend.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'groundhogg_day_quote',
				'group'       => 'other',
				'name'        => __( 'Groundhog Day Quote', 'groundhogg' ),
				'callback'    => [ $this, 'get_random_groundhogday_quote' ],
				'description' => _x( 'Inserts a random quote from the movie Groundhog Day featuring Bill Murray!', 'replacement', 'groundhogg' ),
			],
			[
				'code'           => 'posts',
				'default_args'   => 'layout=grid number=5 featured excerpt',
				'group'          => 'post',
				'callback'       => [ $this, 'posts' ],
				'callback_plain' => [ $this, 'posts_plain' ],
				'name'           => __( 'Recent Posts', 'groundhogg' ),
				'description'    => _x( 'Show links posts in your email.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'post_title',
				'group'       => 'post',
				'callback'    => [ $this, 'post_title' ],
				'name'        => __( 'Post Title', 'groundhogg' ),
				'description' => _x( 'Return the title of a single recent post.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'post_excerpt',
				'group'       => 'post',
				'callback'    => [ $this, 'post_excerpt' ],
				'name'        => __( 'Post Excerpt', 'groundhogg' ),
				'description' => _x( 'Return the excerpt of a single recent post.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'post_content',
				'group'       => 'post',
				'callback'    => [ $this, 'post_content' ],
				'name'        => __( 'Post Content', 'groundhogg' ),
				'description' => _x( 'Return the content of a single recent post.', 'replacement', 'groundhogg' ),
			],
			[
				'code'           => 'post_featured_image',
				'group'          => 'post',
				'callback'       => [ $this, 'post_featured_image' ],
				'callback_plain' => [ $this, 'post_featured_image_plain_text' ],
				'name'           => __( 'Post Featured Image', 'groundhogg' ),
				'description'    => _x( 'Return the featured image of a single recent post.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'post_featured_image_url',
				'group'       => 'post',
				'callback'    => [ $this, 'post_featured_image_url' ],
				'name'        => __( 'Post Featured Image URL', 'groundhogg' ),
				'description' => _x( 'Return the featured image URL of a single recent post.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'post_link',
				'group'       => 'post',
				'callback'    => [ $this, 'post_url' ],
				'name'        => __( 'Post Link', 'groundhogg' ),
				'description' => _x( 'The URL of a single recent post.', 'replacement', 'groundhogg' ),
			],
			[
				'code'     => 'post_url',
				'group'    => 'post',
				'callback' => [ $this, 'post_url' ],
//				'name'        => __( 'Post URL', 'groundhogg' ),
//				'description' => _x( 'The URL of a single recent post.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'view_in_browser_link',
				'group'       => 'email',
				'callback'    => [ $this, 'view_in_browser_link' ],
				'name'        => __( 'View in browser link', 'groundhogg' ),
				'description' => _x( 'Link to view the email in the browser', 'replacement', 'groundhogg' ),
			],
			[
				'code'         => 'andList',
				'group'        => 'formatting',
				'default_args' => 'meta_key',
				'callback'     => [ $this, 'replacement_andList' ],
				'name'         => __( 'And List', 'groundhogg' ),
				'description'  => _x( 'Formats a custom field like checkboxes as a grammatically correct list using and.', 'replacement', 'groundhogg' ),
			],
			[
				'code'         => 'orList',
				'group'        => 'formatting',
				'default_args' => 'meta_key',
				'callback'     => [ $this, 'replacement_orList' ],
				'name'         => __( 'Or List', 'groundhogg' ),
				'description'  => _x( 'Formats a custom field like checkboxes as a grammatically correct list using or.', 'replacement', 'groundhogg' ),
			],
			[
				'code'         => 'ol',
				'group'        => 'formatting',
				'default_args' => 'meta_key',
				'callback'     => [ $this, 'replacement_ol' ],
				'name'         => __( 'Ordered List', 'groundhogg' ),
				'description'  => _x( 'Formats a custom field like checkboxes as an ordered list.', 'replacement', 'groundhogg' ),
			],
			[
				'code'         => 'ul',
				'group'        => 'formatting',
				'default_args' => 'meta_key',
				'callback'     => [ $this, 'replacement_ul' ],
				'name'         => __( 'Unordered List', 'groundhogg' ),
				'description'  => _x( 'Formats a custom field like checkboxes as an unordered list.', 'replacement', 'groundhogg' ),
			],
			[
				'code'         => 'substr',
				'group'        => 'formatting',
				'default_args' => '{replacement}',
				'callback'     => [ $this, 'replacement_substring' ],
				'name'         => __( 'Sub string', 'groundhogg' ),
				'description'  => _x( 'Returns a substring of the inner replacement code.', 'replacement', 'groundhogg' ),
			],
			[
				'code'         => 'this_email',
				'callback'     => [ $this, 'replacement_this_email' ],
			],
			[
				'code'         => 'this_flow',
				'callback'     => [ $this, 'replacement_this_flow' ],
			],
			[
				'group'        => 'formatting',
				'default_args' => 'text',
				'name'         => __( 'Redact Text', 'groundhogg' ),
				'description'  => _x( 'Redacts included text from any logging.', 'replacement', 'groundhogg' ),
				'code'         => 'redact',
				'callback'     => [ $this, 'replacement_redact' ],
			],
		];

		$replacements = apply_filters( 'groundhogg/replacements/defaults', $replacements );

		foreach ( $replacements as $replacement ) {
			$this->add(
				get_array_var( $replacement, 'code', '' ),
				get_array_var( $replacement, 'callback', '' ),
				get_array_var( $replacement, 'description', '' ),
				get_array_var( $replacement, 'name' ),
				get_array_var( $replacement, 'group' ),
				get_array_var( $replacement, 'default_args' ),
				get_array_var( $replacement, 'callback_plain' )
			);
		}

		do_action( 'groundhogg/replacements/init', $this );
	}

	/**
	 * Add a replacement code
	 *
	 * @param string   $code           the code
	 * @param callable $callback       the callback function
	 * @param string   $description    string description of the code
	 * @param string   $name           the display name of the replacement for the dropdown
	 * @param string   $group          the group where it should be displayed
	 * @param string   $default_args   the default args that should be inserted when selected
	 * @param callable $plain_callback callback for when rendering plain text replacements
	 *
	 * @return bool
	 */
	function add( $code, $callback, $description = '', $name = '', $group = 'other', $default_args = '', $plain_callback = '' ) {

		if ( ! $code || ! is_callable( $callback ) ) {
			return false;
		}

		if ( is_callable( $callback ) ) {

			// If the plain callback is not callable
			// Set it to a version that parses as markdown
			if ( ! is_callable( $plain_callback ) ) {
				$plain_callback = function ( ...$args ) use ( $callback ) {

					$content = $callback( ...$args );

					// contains HTML
					if ( wp_strip_all_tags( $content ) !== $content ) {
						$content = html2markdown( $content );
					}

					return $content;
				};
			}

			$this->replacement_codes[ $code ] = [
				'code'           => $code,
				'callback'       => $callback,
				'callback_plain' => $plain_callback,
				'name'           => $name ?: $code,
				'group'          => $group,
				'description'    => $description,
				'insert'         => ! empty( $default_args ) ? sprintf( '{%s.%s}', $code, $default_args ) : sprintf( '{%s}', $code ),
				'hidden'         => false,
			];

			return true;
		}

		return false;

	}

	/**
	 * Hide a replacement code from view
	 * Useful for making replacement codes backwards compatible without showing it in the UI
	 *
	 * @param $id
	 */
	function make_hidden( $id ) {
		$this->replacement_codes[ $id ]['hidden'] = true;
	}

	/**
	 * Register a new group
	 *
	 * @param $group
	 * @param $name
	 */
	public function add_group( $group, $name ) {
		$this->replacement_code_groups[ $group ] = $name;
	}

	/**
	 * Remove a replacement code
	 *
	 * @since 1.9
	 *
	 * @param string $code to remove
	 *
	 */
	public function remove( $code ) {
		unset( $this->replacement_codes[ $code ] );
	}

	/**
	 * Remove a replacement code group
	 *
	 * @param $group
	 */
	public function remove_group( $group ) {
		unset( $this->replacement_code_groups[ $group ] );
	}

	/**
	 * See if the replacement code exists already
	 *
	 * @param $code
	 *
	 * @return bool
	 */
	function has_replacement( $code ) {
		return array_key_exists( $code, $this->replacement_codes );
	}

	/**
	 * Returns a list of all replacement codes
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function get_replacements() {
		return $this->replacement_codes;
	}

	protected $context = 'html';

	/**
	 * Get the replacements context
	 *
	 * @return string
	 */
	public function get_context() {
		return $this->context;
	}

	/**
	 * If the context is HTML
	 *
	 * @return bool
	 */
	public function context_is_html() {
		return $this->get_context() === 'html';
	}

	/**
	 * If the context is plain text
	 *
	 * @return bool
	 */
	public function context_is_plain() {
		return $this->get_context() === 'plain';
	}

	/**
	 * Process the codes based on the given contact ID
	 *
	 * @param string           $content
	 *
	 * @param int|bool|Contact $contact_id_or_email ID of the contact
	 * @param string           $context             what context the replacements are in
	 *
	 * @return string
	 */
	public function process( string $content, $contact_id_or_email = false, $context = 'html' ) {

		$this->context = $context;

		if ( is_a_contact( $contact_id_or_email ) ) {
			$contact = $contact_id_or_email;
		} else {
			$contact = get_contactdata( $contact_id_or_email );
		}

		if ( $contact && $contact->exists() ) {
			$this->contact_id      = $contact->get_id();
			$this->current_contact = $contact;
		} else {
			$this->contact_id      = 0;
			$this->current_contact = new Contact;
		}

		return $this->tackle_replacements( $content );
	}

	const PATTERN = '/{([A-Za-z_0-9][^{}\n]+)}/';

	/**
	 * Recursive function to tackle nested replacement codes until no more replacements are found.
	 *
	 * @param string $content
	 *
	 * @return mixed
	 */
	public function tackle_replacements( string $content ) {

		if ( ! preg_match( self::PATTERN, $content ) ) {
			return $content;
		}

		$content = preg_replace_callback( self::PATTERN . 's', [
			$this,
			'do_replacement'
		], $content );

        // keep doing passes until no more to do
        return $this->tackle_replacements( $content );
	}

	/**
	 * @return Contact
	 */
	public function get_current_contact() {
		return $this->current_contact;
	}

	/**
	 * @param string $code
	 *
	 * @return array
	 */
	protected function parse_code( $code = '' ) {

		$default = "";
		$arg     = false;

		//Support Default Arguments.
		if ( strpos( $code, '::' ) > 0 ) {
			$parts   = explode( '::', $code, 2 );
			$code    = $parts[0];
			$default = $parts[1];
		}

		/* make sure that if it's a dynamic code to remove anything after the period */
		if ( strpos( $code, '.' ) > 0 ) {
			$parts = explode( '.', $code, 2 );
			$code  = $parts[0];
			$arg   = $parts[1];
		}

		return [
			'code'    => $code,
			'arg'     => $arg,
			'default' => $default
		];
	}

	private $code_stack = [];

	/**
	 * If we did a code
	 *
	 * @param $code
	 *
	 * @return bool
	 */
	private function did_code( $code ) {
		if ( isset( $this->code_stack[ $code ] ) && $this->code_stack[ $code ] ) {
			return true;
		}

		$this->add_code_to_stack( $code );

		return false;
	}

	/**
	 * Add a code to the stack to prevent recursive replacements
	 *
	 * @param $code
	 *
	 * @return void
	 */
	private function add_code_to_stack( $code ) {
		$this->code_stack[ $code ] = true;
	}

	/**
	 * Remove the code from the stack when finished.
	 *
	 * @param $code
	 *
	 * @return void
	 */
	private function remove_code_to_stack( $code ) {
		unset( $this->code_stack[ $code ] );
	}

	/**
	 * Process the given replacement code
	 *
	 * @param $m
	 *
	 * @return mixed
	 */
	private function do_replacement( $m ) {
		// Get tag
		$code = $m[1];

		$parts = $this->parse_code( $code );

		$arg     = $parts['arg'];
		$code    = $parts['code'];
		$default = $parts['default'];

		// The code exists and is set
		if ( $this->has_replacement( $code ) ) {

			$html_callback  = $this->replacement_codes[ $code ]['callback'];
			$plain_callback = $this->replacement_codes[ $code ]['callback_plain'];

			$cache_key = implode( ':', [
				// if there is no defined plain callback we should reference the html version
				$this->get_context(),
				$this->contact_id ?: 'anon',
				md5serialize( $parts ),
				cache_get_last_changed( 'groundhogg/replacements' )
			] );

			$cache_value = wp_cache_get( $cache_key, 'groundhogg/replacements', false, $found );

			if ( $found ) {
				return $cache_value;
			}

			$callback = $this->context_is_html() ? $html_callback : $plain_callback;

			if ( $arg ) {
				$text = call_user_func( $callback, $arg, $this->contact_id, $code );
			} else {
				$text = call_user_func( $callback, $this->contact_id, $code );
			}

			if ( empty( $text ) ) {
				$text = $default;
			}

			// Did we already do this code during the current process?
			// if we didn't it'll get added to the stack within Replacements::did_code().
			if ( $this->did_code( $cache_key ) ) {
				return '';
			}

			// tackle inner replacements within the returned text
			$text = $this->tackle_replacements( $text );

			/**
			 * Filter the return value of a given replacement code
			 *
			 * @param string $text the return value of the replacement code
			 */
			$value = apply_filters( "groundhogg/replacements/{$code}", $text );

			wp_cache_set( $cache_key, $value, 'groundhogg/replacements' );

			$this->remove_code_to_stack( $cache_key );

			return $value;
		}

		// Try to access contact fields directly
		$field     = str_starts_with( $code, '_' ) ? substr( $code, 1 ) : $code;
		$cache_key = implode( ':', [
			$this->contact_id ?: 'anon',
			$field,
			cache_get_last_changed( 'groundhogg/replacements' )
		] );

		$cache_value = wp_cache_get( $cache_key, 'groundhogg/replacements', false, $found );

		if ( $found ) {
			return $cache_value;
		}

		if ( $property = Properties::instance()->get_field( $field ) ) {
			$text = display_custom_field( $property, $this->current_contact, false );
		} else if ( property_exists( $this->get_current_contact(), $field ) ) {
			$text = $this->get_current_contact()->$field;
		} else {
			$text = $this->get_current_contact()->get_meta( $field );
		}

		if ( is_array( $text ) || is_object( $text ) ) {
			$text = wp_json_encode( $text );
		}

		if ( ! $text ) {
			$text = $default;
		}


		// Did we already do this code during the current process?
		// if we didn't it'll get added to the stack within Replacements::did_code().
		if ( $this->did_code( $cache_key ) ) {
			return '';
		}

		// tackle inner replacements within the returned text
		$text = $this->tackle_replacements( $text );

		wp_cache_set( $cache_key, $text, 'groundhogg/replacements' );

		$this->remove_code_to_stack( $cache_key );

		return $text;
	}

	/**
	 * Invalidate the replacements cache by setting the last changed
	 * - when contact is updated
	 *
	 * @return void
	 */
	public function invalidate_replacements_cache() {
		cache_set_last_changed( 'groundhogg/replacements' );
	}

	public function replacements_in_footer() {
		?>
        <div id="footer-replacement-codes" class="hidden">
			<?php $this->get_table(); ?>
        </div>
		<?php
	}

	public function get_table() {

		foreach ( $this->replacement_code_groups as $group => $name ):

			$codes = array_filter( $this->get_replacements(), function ( $code ) use ( $group ) {
				return $code['group'] === $group;
			} );

			?>
            <h3 class="replacements-group"><?php echo esc_html( $name ) ?></h3>
            <table class="wp-list-table widefat fixed striped replacements-table">
                <thead>
                <tr>
                    <th><?php esc_html_e( 'Name', 'groundhogg' ); ?></th>
                    <th><?php esc_html_e( 'Code', 'groundhogg' ); ?></th>
                    <th><?php esc_html_e( 'Description', 'groundhogg' ); ?></th>
                </tr>
                </thead>
                <tbody>

				<?php foreach ( $codes as $code => $replacement ):

					if ( $replacement['hidden'] ) {
						continue;
					}

					?>
                    <tr>
                        <td><?php echo esc_html( get_array_var( $replacement, 'name' ) ); ?></td>
                        <td>
                            <input class="replacement-selector code"
                                   type="text"
                                   style="border: none;outline: none;background: transparent;width: 100%;"
                                   onfocus="this.select();"
                                   value="<?php echo esc_attr( get_array_var( $replacement, 'insert', '{' . $code . '}' ) ) ?>"
                                   readonly>
                        </td>
                        <td>
                            <span class="description"><?php echo esc_html( $replacement['description'] ); ?></span>
                        </td>
                    </tr>
				<?php endforeach; ?>
                </tbody>
            </table>
		<?php
		endforeach;
	}

	public function show_replacements_button( $short = false ) {
		wp_enqueue_script( 'groundhogg-admin-replacements' );

		html( html()->modal_link( array(
			'title'              => esc_html__( 'Replacements', 'groundhogg' ),
			'text'               => $short
				? '<span style="vertical-align: middle" class="dashicons dashicons-admin-users"></span>'
				: '<span style="vertical-align: middle" class="dashicons dashicons-admin-users"></span>&nbsp;' . esc_html_x( 'Insert Replacement', 'replacement', 'groundhogg' ),
			'footer_button_text' => esc_html__( 'Insert', 'groundhogg' ),
			'id'                 => 'replacements',
			'class'              => 'button button-secondary no-padding replacements replacements-button',
			'source'             => 'footer-replacement-codes',
			'height'             => 900,
			'width'              => 700,
		) ) );

	}

	public function show_replacements_dropdown( $echo = true ) {
		wp_enqueue_script( 'groundhogg-admin-replacements' );

		$options = [];

		/**
		 * Build the categorized replacements list.
		 */
		foreach ( $this->replacement_code_groups as $group => $name ) {
			$options[ $name ] = array_map_with_keys( array_map_keys( array_filter( $this->replacement_codes, function ( $atts ) use ( $group ) {
				return $atts['group'] === $group && ! $atts['hidden'];
			} ), function ( $code, $atts ) {
				return get_array_var( $atts, 'insert', '{' . $code . '}' );
			} ), function ( $atts, $code ) {
				return $atts['name'];
			} );
		}

		$return = html()->e( 'div', [
			'class' => 'replacements-dropdown-wrap'
		], [
			'<span style="vertical-align: middle" class="dashicons dashicons-admin-users"></span>',
			html()->dropdown( [
				'option_none' => _x( 'Insert Replacement', 'replacement', 'groundhogg' ),
				'name'        => 'replacement_code',
				'id'          => 'replacement-code',
				'class'       => 'replacement-code-dropdown',
				'options'     => $options
			] )
		] );

		if ( $echo ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated HTML
			echo $return;

			return true;
		}

		return $return;
	}

	function replacement_birthday() {

		$birthday = $this->get_current_contact()->get_meta( 'birthday' );

		if ( ! $birthday ) {
			return '';
		}

		try {
			$birthday = new DateTimeHelper( $birthday );
		} catch ( \Exception $exception ) {
			return '';
		}

		return $birthday->ymd();
	}

	/**
	 * The upcoming birthday of the contact
	 *
	 * @return string
	 */
	function replacement_upcoming_birthday() {

		$birthday = $this->get_current_contact()->get_meta( 'birthday' );

		if ( ! $birthday ) {
			return '';
		}

		try {
			$birthday = new DateTimeHelper( $birthday );
		} catch ( \Exception $exception ) {
			return '';
		}

		$birthday->setToCurrentYear();

		if ( $birthday->isPast() ) {
			$birthday->modify( '+1 year' );
		}

		return $birthday->ymd();
	}


	/**
	 * The contact's website
	 *
	 * @return string
	 */
	function replacement_website() {

		$contact = $this->get_current_contact();

		if ( is_free_email_provider( $contact->get_email() ) ) {
			return '';
		}

		return 'https://' . get_email_address_hostname( $this->get_current_contact()->get_email() );
	}

	/**
	 * Generic handler function for arbitrary meta data
	 *
	 * @param string   $arg               generally the meta key and other specific formatting
	 * @param callable $get_meta_callback a callback function to retrieve the value from the root key
	 *
	 * @return mixed
	 */
	public static function handle_meta_replacement( $arg, callable $get_meta_callback ) {

		if ( empty( $arg ) ) {
			return '';
		}

		$parts  = explode( '|', $arg );
		$format = get_array_var( $parts, 1 );

		// support for serialized objects
		$nested_keys = explode( '.', get_array_var( $parts, 0 ) );
		$root_key    = array_shift( $nested_keys );

		$value = call_user_func( $get_meta_callback, $root_key );

		while ( ! empty( $nested_keys ) && is_iterable( $value ) ) {
			$key   = array_shift( $nested_keys );
			$value = $value[ $key ];
		}

		switch ( $format ) {
			default:
				return print_r( $value, true );
			case 'csv':
				return is_array( $value ) ? implode( ', ', $value ) : print_r( $value, true );
			case 'ol':
			case 'ul':
				return html()->e( $format, [], array_map( function ( $item ) {
					return html()->e( 'li', [], $item );
				}, is_array( $value ) ? $value : array_map( 'trim', explode( ',', $value ) ) ) );
			case 'andList':
				return is_array( $value ) ? andList( $value ) : $value;
			case 'orList':
				return is_array( $value ) ? orList( $value ) : $value;
		}
	}

	/**
	 * Return the contact meta
	 *
	 * usage is {meta.meta_key}
	 * or for serialized date you can do {meta.some_array.key}
	 *
	 * Additionally add a format function like {meta.some_array|ol}
	 *
	 * @param $contact_id int
	 * @param $arg        string the meta key
	 *
	 * @return mixed|string
	 */
	function replacement_meta( $arg, $contact_id ) {
		return self::handle_meta_replacement( $arg, [ $this->get_current_contact(), 'get_meta' ] );
	}

	/**
	 * Return the profile_picture
	 *
	 * @param $contact_id int
	 * @param $arg        string the meta key
	 *
	 * @return mixed|string
	 */
	function replacement_profile_picture( $arg, $contact_id ) {

		$size = absint( $arg );

		if ( empty( $contact_id ) ) {
			$size = 300;
		}

		$size = min( $size, 1000 );
		$size = max( $size, 20 );

		return $this->get_current_contact()->get_profile_picture( $size );
	}

	/**
	 * The pretty name of the optin status
	 *
	 * @param $contact_id
	 *
	 * @return string
	 */
	function replacement_optin_status( $contact_id ) {
		return Preferences::get_preference_pretty_name( $this->get_current_contact()->get_optin_status() );
	}

	/**
	 * Returns comma separated tags
	 *
	 * @param $contact_id
	 *
	 * @return string
	 */
	function tag_names( $contact_id ) {

		$tag_ids = $this->get_current_contact()->get_tags();
		$tags    = array_map( [ $this, 'get_contact_tag_names' ], $tag_ids );

		return implode( ',', $tags );
	}

	/**
	 * Returns tag name of the contact
	 *
	 * @param $tag_id
	 *
	 * @return string
	 */
	function get_contact_tag_names( $tag_id ) {
		$tag = new Tag( $tag_id );

		return $tag->get_name();
	}

	/**
	 * Return the contact meta
	 *
	 * @param $contact_id int
	 * @param $arg        string the meta key
	 *
	 * @return mixed
	 */
	function replacement_user( $arg, $contact_id ) {
		if ( ! $this->get_current_contact()->get_user_id() ) {
			return '';
		}

		return self::handle_meta_replacement( $arg, function ( $key ) {
			$rep = $this->get_current_contact()->get_userdata()->$key;

			// Try to get from meta
			if ( ! $rep ) {
				$rep = get_user_meta( $this->get_current_contact()->get_user_id(), $key, true );
			}

			return $rep;
		} );
	}

	/**
	 * Get something from $_GET
	 *
	 * @param $key
	 *
	 * @return mixed
	 */
	public function replacement_get_params( $key ) {
		return esc_html( get_url_var( $key ) );
	}


	/**
	 * Return back the ID of the contact.
	 *
	 * @param $contact_id int the contact_id
	 *
	 * @return string the first name
	 */
	function replacement_id( $contact_id ) {
		return $contact_id;
	}

	/**
	 * Return back the first name ot the contact.
	 *
	 * @param $contact_id int the contact_id
	 *
	 * @return string the first name
	 */
	function replacement_first_name( $contact_id ) {
		return $this->get_current_contact()->get_first_name();
	}

	/**
	 * The contacts first initial
	 *
	 * @return false|string
	 */
	function replacement_first_initial() {
		$name = $this->get_current_contact()->get_first_name();
		if ( empty( $name ) ) {
			return '';
		}

		return substr( $name, 0, 1 );
	}

	/**
	 * Return back the last name ot the contact.
	 *
	 * @param $contact_id int the contact_id
	 *
	 * @return string the last name
	 */
	function replacement_last_name( $contact_id ) {
		return $this->get_current_contact()->get_last_name();
	}

	/**
	 * The contacts last initial
	 *
	 * @return false|string
	 */
	function replacement_last_initial() {
		$name = $this->get_current_contact()->get_last_name();
		if ( empty( $name ) ) {
			return '';
		}

		return substr( $name, 0, 1 );
	}

	/**
	 * Return back the full name ot the contact.
	 *
	 * @param $contact_id int the contact_id
	 *
	 * @return string the last name
	 */
	function replacement_full_name( $contact_id ) {
		return $this->get_current_contact()->get_full_name();
	}

	/**
	 * Return the username of the contact if one exists.
	 *
	 * @param $contact_id int the contact's id
	 *
	 * @return string
	 */
	function replacement_username( $contact_id ) {
		return $this->get_current_contact()->get_userdata() ? $this->get_current_contact()->get_userdata()->user_login : $this->get_current_contact()->get_email();
	}

	/**
	 * Return back the email of the contact.
	 *
	 * @param $contact_id int the contact ID
	 *
	 * @return string the email
	 */
	function replacement_email( $contact_id ) {
		return $this->get_current_contact()->get_email();
	}

	/**
	 * Return back the phone # ot the contact.
	 *
	 * @param $contact_id int the contact_id
	 *
	 * @return string the first name
	 */
	function replacement_mobile_phone( $contact_id ) {
		return $this->get_current_contact()->get_mobile_number();
	}

	/**
	 * Return back the phone # ot the contact.
	 *
	 * @param $contact_id int the contact_id
	 *
	 * @return string the first name
	 */
	function replacement_phone( $contact_id ) {
		return $this->get_current_contact()->get_phone_number();
	}

	/**
	 * Return back the phone # ext the contact.
	 *
	 * @param $contact_id int the contact_id
	 *
	 * @return string the first name
	 */
	function replacement_phone_ext( $contact_id ) {

		$ext = $this->get_current_contact()->get_phone_extension();

		if ( $ext ) {
			return sprintf( "%s ext. %s", $this->current_contact->get_phone_number(), $this->current_contact->get_phone_extension() );
		} else {
			return $this->current_contact->get_phone_number();
		}
	}

	function replacement_line1() {
		return $this->get_current_contact()->get_meta( 'street_address_1' );
	}

	function replacement_line2() {
		return $this->get_current_contact()->get_meta( 'street_address_2' );
	}

	function replacement_city() {
		return $this->get_current_contact()->get_meta( 'city' );
	}

	function replacement_state() {
		return $this->get_current_contact()->get_meta( 'region' );
	}

	function replacement_zip() {
		return $this->get_current_contact()->get_meta( 'postal_zip' );
	}

	function replacement_country_code() {
		return $this->get_current_contact()->get_meta( 'country' );
	}

	/**
	 * Full country name if country is set,
	 * otherwise the empty string
	 *
	 * @return string
	 */
	function replacement_country() {

		$country_code = $this->get_current_contact()->get_meta( 'country' );

		if ( empty( $country_code ) ) {
			return '';
		}

		return utils()->location->get_countries_list( $country_code );
	}

	function replacement_ip_address() {
		return $this->get_current_contact()->get_meta( 'ip_address' );
	}

	function replacement_time_zone() {
		return $this->get_current_contact()->get_meta( 'time_zone' );
	}

	/**
	 * Return back the address of the contact.
	 *
	 * @param $contact_id int the contact_id
	 *
	 * @return string the first name
	 */
	function replacement_address( $contact_id ) {
		$address = implode( ', ', $this->get_current_contact()->get_address() );

		return $address;
	}

	/**
	 * Get the contact notes
	 *
	 * @param $contact_id
	 *
	 * @return mixed
	 */
	function replacement_notes( $contact_id ) {
		$notes = $this->get_current_contact()->get_all_notes();

		$return = "";

		foreach ( $notes as $note ) {
			$return .= sprintf( "\n\n===== %s =====", date( get_date_time_format(), $note->timestamp ) );
			$return .= sprintf( "\n\n%s", $note->content );
			$return .= sprintf( "\n\n%s", $note->content );
		}

		return $return;
	}

	/**
	 * Return back the email address of the contact owner.
	 *
	 * @param $contact_id int the contact ID
	 *
	 * @return string the owner's email
	 */
	function replacement_owner_email( $contact_id ) {
		$user = $this->get_current_contact()->get_ownerdata();

		if ( ! $user ) {
			return get_default_from_email();
		}

		return $user->user_email;
	}

	/**
	 * Return back the first name of the contact owner.
	 *
	 * @param $contact_id int the contact
	 *
	 * @return string the owner's name
	 */
	function replacement_owner_first_name( $contact_id ) {
		$user = $this->get_current_contact()->get_ownerdata();

		if ( ! $user ) {
			// return admin details
			$user = get_primary_owner();

			if ( ! $user ) {
				return '';
			}
		}

		return $user->first_name;
	}

	/**
	 * Return back the first name of the contact owner.
	 *
	 * @param $contact_id int the contact
	 *
	 * @return string the owner's name
	 */
	function replacement_owner_last_name( $contact_id ) {
		$user = $this->get_current_contact()->get_ownerdata();

		if ( ! $user ) {
			//return admin details
			$user = get_primary_owner();

			if ( ! $user ) {
				return '';
			}
		}

		return $user->last_name;
	}

	/**
	 * Return the owner's phone #
	 *
	 * @param $contact_id
	 *
	 * @return mixed|string
	 */
	function replacement_owner_phone( $contact_id ) {
		$user = $this->get_current_contact()->get_ownerdata();

		if ( ! $user || ! $user->phone ) {
			return $this->replacement_business_phone();
		}

		return $user->phone;
	}

	/**
	 * Return the owner's signature
	 *
	 * @param int $user_id
	 * @param int $contact_id
	 *
	 * @return mixed|string
	 */
	function replacement_owner_signature( $user_id = 0, $contact_id = 0 ) {

		$user_id    = absint( $user_id );
		$contact_id = absint( $contact_id );

		// If a specific user ID was passed
		if ( $user_id > 0 && $contact_id > 0 ) {
			$user = get_userdata( $user_id );
		} else {
			// Use contact's actual owner...
			$user = $this->get_current_contact()->get_ownerdata();
		}

		return wpautop( $user->signature );
	}

	/**
	 * Return the owner's signature
	 *
	 * @param mixed $attr the attribute to fetch...
	 * @param int   $contact_id
	 *
	 * @return mixed|string
	 */
	function replacement_owner( $attr, $contact_id = 0 ) {

		$user = $this->get_current_contact()->get_ownerdata();

		if ( ! $user ) {
			return false;
		}

		return $user->$attr;
	}

	/**
	 * Return a confirmation link for the contact
	 * This just gets the Opt-in Page link for now.
	 *
	 * @param $redirect_to string
	 *
	 * @return string the optin link
	 */
	function replacement_confirmation_link( $redirect_to ) {

		$link_text = apply_filters( 'groundhogg/replacements/confirmation_text', Plugin::$instance->settings->get_option( 'confirmation_text', __( 'Confirm your email.', 'groundhogg' ) ) );
		$link_url  = $this->replacement_confirmation_link_raw( $redirect_to );

		return "<a href=\"" . esc_url( $link_url ) . "\" target=\"_blank\">" . $link_text . "</a>";
	}

	/**
	 * Return a confirmation link for the contact
	 * This just gets the Opt-in Page link for now.
	 *
	 * @param $redirect_to string
	 *
	 * @return string the optin link
	 */
	function replacement_confirmation_link_plain_text( $redirect_to ) {

		$link_text = apply_filters( 'groundhogg/replacements/confirmation_text', Plugin::$instance->settings->get_option( 'confirmation_text', __( 'Confirm your email.', 'groundhogg' ) ) );
		$link_url  = $this->replacement_confirmation_link_raw( $redirect_to );

		return sprintf( "[%s](%s)", $link_text, $link_url );
	}

	/**
	 * Return a raw confirmation link for the contact that can be placed in a button.
	 * This just gets the Opt-in Page link for now.
	 *
	 * @param string $redirect_to
	 *
	 * @return string the optin link
	 */
	function replacement_confirmation_link_raw( $redirect_to = '' ) {

		$link_url = confirmation_url( $this->get_current_contact() );

		$redirect_to = is_string( $redirect_to ) ? esc_url_raw( no_and_amp( $redirect_to ) ) : false;

		if ( $redirect_to && is_string( $redirect_to ) ) {
			$link_url = add_query_arg( [
				'redirect_to' => urlencode( $redirect_to )
			], $link_url );
		}

		return $link_url;
	}

	/**
	 * Auto login the user
	 *
	 * @param $redirect_to
	 *
	 * @return string|void
	 */
	function replacement_auto_login_link( $redirect_to ) {

		$link_url    = managed_page_url( 'auto-login' );
		$redirect_to = is_string( $redirect_to ) ? esc_url_raw( no_and_amp( $redirect_to ) ) : false;

		if ( ! $this->get_current_contact()->get_userdata() ) {
			return $redirect_to;
		}

		if ( is_sending() ) {
			// Temporarily force the email to be sent to the user's email address instead
			the_email()->contact->email = the_email()->contact->get_userdata()->user_email;
		}

		$link_url = maybe_permissions_key_url( $link_url, $this->get_current_contact(), 'auto_login', DAY_IN_SECONDS, true );

		if ( $redirect_to && is_string( $redirect_to ) ) {
			$link_url = add_query_arg( [
				'redirect_to' => urlencode( $redirect_to )
			], $link_url );
		}

		return $link_url;
	}

	/**
	 * Merge in the unsubscribe link
	 *
	 * @return string|void
	 */
	function replacement_unsubscribe_link() {
		return unsubscribe_url( $this->get_current_contact() );
	}

	/**
	 * @return string
	 */
	function site_url() {
		return home_url();
	}


	/**
	 * Flag to modify the date replacement to output in the local time of the contact
	 *
	 * @var bool
	 */
	protected $date_display_in_contacts_local_time = false;

	/**
	 * Return a formatted date in local time.
	 *
	 * @param $time_string
	 *
	 * @return string
	 */
	function replacement_date( $time_string ) {

		$parts = preg_split( "/\||;/", $time_string );

		if ( count( $parts ) === 1 ) {
			$format = get_date_time_format();
			$when   = $parts[0];
		} else {
			$format = $parts[0];
			$when   = $parts[1];
		}

		try {
			$dateTime = new \DateTime( $when, wp_timezone() );
		} catch ( \Exception $e ) {

			// Swap the variables
			$temp   = $when;
			$when   = $format;
			$format = $temp;

			try {
				$dateTime = new \DateTime( $when, wp_timezone() );
			} catch ( \Exception $e ) {
				return '';
			}
		}

		if ( $this->date_display_in_contacts_local_time ) {
			$dateTime->setTimezone( $this->get_current_contact()->get_time_zone( false ) );
		}

		return $dateTime->format( $format );
	}

	/**
	 * Return a formatted date in contact's local time.
	 *
	 * @param $time_string
	 *
	 * @return string
	 */
	function replacement_local_date( $time_string ) {
		$this->date_display_in_contacts_local_time = true;
		$date                                      = $this->replacement_date( $time_string );
		$this->date_display_in_contacts_local_time = false;

		return $date;
	}


	/**
	 * Return the business name
	 *
	 * @return string
	 */
	function replacement_business_name() {
		return Plugin::$instance->settings->get_option( 'business_name' );
	}

	/**
	 * Return eh business phone #
	 *
	 * @return string
	 */
	function replacement_business_phone() {
		return Plugin::$instance->settings->get_option( 'phone' );
	}

	/**
	 * Return the business address
	 *
	 * @return array|string
	 */
	function replacement_business_address() {
		$address_keys = [
			'street_address_1',
			'street_address_2',
			'city',
			'region',
			'zip_or_postal',
			'country',
		];

		$address = [];

		foreach ( $address_keys as $key ) {

			$val = Plugin::$instance->settings->get_option( $key );
			if ( ! empty( $val ) ) {
				$address[ $key ] = $val;
			}
		}

		return implode( ', ', $address );
	}

	/**
	 * Get a file download link from a contact record.
	 *
	 * @param $key        string|int the key for the file
	 * @param $contact_id int
	 *
	 * @return string
	 */
	function replacement_files( $key = '', $contact_id = null ) {
		// Backwards compat
		if ( ! $contact_id ) {
			$contact_id = $key;
			$key        = false;
		}

		$files = $this->get_current_contact()->get_files();

		if ( empty( $files ) ) {
			return __( 'No files found.', 'groundhogg' );
		}

		$html = '';

		foreach ( $files as $i => $file ) {
			$html .= '<li><a href="' . esc_url( maybe_permissions_key_url( $file['url'], $this->get_current_contact(), 'download_files' ) ) . '">' . esc_html( $file['name'] ) . '</a></li>';
		}

		return sprintf( '<ul>%s</ul>', $html );
	}

	/**
	 * Get a file download link from a contact record.
	 *
	 * @param $key        string|int the key for the file
	 * @param $contact_id int
	 *
	 * @return string
	 */
	function replacement_files_plain_text( $key = '', $contact_id = null ) {
		// Backwards compat
		if ( ! $contact_id ) {
			$contact_id = $key;
			$key        = false;
		}

		$files = $this->get_current_contact()->get_files();

		if ( empty( $files ) ) {
			return __( 'No files found.', 'groundhogg' );
		}

		$html = [];

		foreach ( $files as $i => $file ) {
			$html[] = sprintf( '- [%s](%s)', esc_html( $file['name'] ), maybe_permissions_key_url( $file['url'], $this->get_current_contact(), 'download_files' ) );
		}

		return implode( PHP_EOL, $html );
	}

	/**
	 * Return a random quote from the movie groundhog day staring bill murray.
	 * Also the movie of which branding is based upon.
	 *
	 * @return mixed
	 */
	function get_random_groundhogday_quote() {
		$quotes = array();

		$quotes[] = "I'm not going to live by their rules anymore!";
		$quotes[] = "When Chekhov saw the long winter, he saw a winter bleak and dark and bereft of hope. Yet we know that winter is just another step in the cycle of life. But standing here among the people of Punxsutawney and basking in the warmth of their hearths and hearts, I couldn't imagine a better fate than a long and lustrous winter.";
		$quotes[] = "Hi, three cheeseburgers, two large fries, two milkshakes, and one large coke.";
		$quotes[] = "It's the same thing every day, Clean up your room, stand up straight, pick up your feet, take it like a man, be nice to your sister, don't mix beer and wine ever, Oh yeah, don't drive on the railroad tracks.";
		$quotes[] = "I'm a god, I'm not the God. I don't think.";
		$quotes[] = "Don't drive angry! Don't drive angry!";
		$quotes[] = "I'm betting he's going to swerve first.";
		$quotes[] = "You want a prediction about the weather? You're asking the wrong Phil. I'm going to give you a prediction about this winter. It's going to be cold, it's going to be dark, and it's going to last you for the rest of your lives!";
		$quotes[] = "We mustn't keep our audience waiting.";
		$quotes[] = "Okay campers, rise and shine, and don't forget your booties cause its cold out there...it's cold out there every day.";
		$quotes[] = "I peg you as a glass half empty kinda guy.";
		$quotes[] = "Well, what if there is no tomorrow? There wasn't one today.";
		$quotes[] = "Did he actually refer to himself as \"the talent\"?";
		$quotes[] = "Did you sleep well Mr. Connors?";

		$quotes = apply_filters( 'add_movie_quotes', $quotes );

		$quote = wp_rand( 0, count( $quotes ) - 1 );

		return $quotes[ $quote ];
	}

	/**
	 * Parse atts for replacement codes
	 *
	 * @param $text
	 *
	 * @return array
	 */
	function parse_atts( $text, $defaults = [] ) {

		if ( is_array( $text ) ) {
			return $text;
		}

		$_atts = shortcode_parse_atts( $text );

		if ( empty( $_atts ) ) {
			return [];
		}

		$atts = $defaults;

		foreach ( $_atts as $key => $value ) {

			// fix flags
			if ( is_numeric( $key ) ) {
				$atts[ $value ] = true;
				continue;
			}

			$atts[ $key ] = $value;
		}

		return wp_parse_args( $atts, $defaults );
	}

	/**
	 * Output a part about a post
	 *
	 * @param string $args
	 * @param string $which
	 *
	 * @return string
	 */
	function single_post( $args, $which = '' ) {

		$props = $this->parse_atts( $args );
		$props = wp_parse_args( $props, [
			'id'             => '',
			'thumbnail_size' => 'large',
		] );

		$props['number'] = 1;
		$query           = $this->create_post_query( $props );

		if ( ! $query->have_posts() ) {
			return '';
		}

		$query->the_post();

		switch ( $which ) {
			default:
			case 'title':
				$content = html_entity_decode( get_the_title(), ENT_QUOTES );
				break;
			case 'content':
				$content = get_the_content();

				/**
				 * Filters the post content.
				 *
				 * @since 0.71
				 *
				 * @param string $content Content of the current post.
				 *
				 */
				$content = apply_filters( 'the_content', $content );
				$content = str_replace( ']]>', ']]&gt;', $content );
				break;
			case 'excerpt':

				add_filter( 'excerpt_more', [ $this, 'post_excerpt_ellipses' ] );

				$content = get_the_excerpt();

				remove_filter( 'excerpt_more', [ $this, 'post_excerpt_ellipses' ] );

				break;
			case 'thumbnail':
			case 'featured_image':

				if ( ! has_post_thumbnail() ) {
					return '';
				}

				return html()->e( 'a', [
					'href' => get_the_permalink()
				], get_the_post_thumbnail( null, $props['thumbnail_size'] ) );
			case 'featured_image_url':
			case 'thumbnail_url':

				if ( ! has_post_thumbnail() ) {
					return '';
				}

				$content = get_the_post_thumbnail_url( null, $props['thumbnail_size'] );
				break;
			case 'url':
			case 'link':
				$content = get_the_permalink();
				break;
		}

		$query->reset_postdata();

		return $content;
	}

	/**
	 * Output a title from a recent post
	 *
	 * @param $args
	 * @param $contact_id
	 *
	 * @return string
	 */
	function post_title( $args, $contact_id = null ) {
		return $this->single_post( $args, 'title' );
	}

	/**
	 * Output the excerpt from a recent post
	 *
	 * @param $args
	 * @param $contact_id
	 *
	 * @return string
	 */
	function post_excerpt( $args, $contact_id = null ) {
		return $this->single_post( $args, 'excerpt' );
	}

	/**
	 * Output a title from a recent post
	 *
	 * @param $args
	 * @param $contact_id
	 *
	 * @return string
	 */
	function post_featured_image( $args, $contact_id = null ) {
		return $this->single_post( $args, 'featured_image' );
	}

	/**
	 * Output a title from a recent post
	 *
	 * @param $args
	 * @param $contact_id
	 *
	 * @return string
	 */
	function post_featured_image_plain_text( $args, $contact_id = null ) {
		return sprintf( '[![%1$s](%2$s)](%3$s)', __( 'Post Featured Image', 'groundhogg' ), $this->single_post( $args, 'featured_image_url' ), $this->single_post( $args, 'url' ) );
	}

	/**
	 * Output a title from a recent post
	 *
	 * @param $args
	 * @param $contact_id
	 *
	 * @return string
	 */
	function post_featured_image_url( $args, $contact_id = null ) {
		return $this->single_post( $args, 'featured_image_url' );
	}

	/**
	 * Output the content from a recent post
	 *
	 * @param $args
	 * @param $contact_id
	 *
	 * @return string
	 */
	function post_content( $args, $contact_id = null ) {
		return $this->single_post( $args, 'content' );
	}

	/**
	 * Output a url for a recent post
	 *
	 * @param $args
	 * @param $contact_id
	 *
	 * @return string
	 */
	function post_url( $args, $contact_id = null ) {
		return $this->single_post( $args, 'url' );
	}

	function post_excerpt_ellipses( $more ) {
		return '&hellip;';
	}

	/**
	 * handle posts dynamic block
	 *
	 * @param mixed    $args
	 * @param int|null $contact_id
	 *
	 * @return string
	 */
	public function posts_plain( $args, $contact_id = null ) {

		$props = $this->parse_atts( $args );

		return $this->posts( array_merge( $props, [
			'layout' => 'plain'
		] ), $contact_id );
	}

	/**
	 * Create a post query from the props given
	 *
	 * @param array $props the replacement props
	 *
	 * @return \WP_Query
	 */
	public function create_post_query( $props = [] ) {

		$props = wp_parse_args( $props, [
			'id'         => '',
			'number'     => 5,
			'offset'     => 0,
			'post_type'  => 'post',
			'category'   => '',
			'tag'        => '',
			'orderby'    => 'date',
			'order'      => 'DESC',
			'meta_key'   => '',
			'meta_value' => '',
			'within'     => '',
			'include'    => [],
			'exclude'    => [],
		] );

		$query_vars = [
			'post_status'    => 'publish',
			'posts_per_page' => $props['number'],
			'offset'         => $props['offset'],
			'post_type'      => $props['post_type'],
			'category'       => $props['category'],
			'tag'            => $props['tag'],
			'orderby'        => $props['orderby'],
			'order'          => $props['order'],
			'meta_key'       => $props['meta_key'],
			'meta_value'     => $props['meta_value'],
			'post__in'       => $props['include'],
			'post__not_in'   => $props['exclude'],
			'no_found_rows'  => true,
		];

		if ( isset_not_empty( $props, 'within' ) ) {
			$days = absint( $props['within'] );
			if ( $days ) {
				$query_vars['date_query'] = [
					'after' => $days . ' days ago'
				];
			}
		}

		$query_id = $props['id'];

		if ( $query_id ) {

			/**
			 * Filter post query variables
			 *
			 * @param $query   array the query args
			 * @param $contact Contact the current contact
			 */
			$query_vars = apply_filters( "groundhogg/posts/query/{$query_id}", $query_vars, $this->current_contact );
		}

		if ( $query_id ) {

			$filter_query = function ( $query ) use ( $query_id, $query_vars ) {
				/**
				 * Allow for modification of the query
				 */
				do_action_ref_array( "groundhogg/posts/wp_query/{$query_id}", [
					&$query,
					$query_vars,
					$this->current_contact,
				] );
			};

			add_action( 'pre_get_posts', $filter_query );
		}

		$query = new \WP_Query( $query_vars );

		if ( $query_id ) {
			remove_action( 'pre_get_posts', $filter_query );
		}

		return $query;
	}

	/**
	 * Display posts!
	 *
	 * @param mixed    $args
	 * @param int|null $contact_id
	 *
	 * @return string
	 */
	function posts( $args, $contact_id = null ) {

        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- ALL GENERATED, GEEZ

		$props = $this->parse_atts( $args );

		$props = wp_parse_args( $props, [
			'id'                 => '',
			'layout'             => 'ul',
			'featured'           => false,
			'excerpt'            => false,
			'thumbnail'          => true,
			'thumbnail_size'     => 'thumbnail',
			'thumbnail_position' => 'above',
			'columns'            => 2,
			'gap'                => 20,
			'cardStyle'          => [],
			'headingStyle'       => [],
			'excerptStyle'       => [],
		] );

		$query = $this->create_post_query( $props );

		if ( ! $query->have_posts() ) {
			return '';
		}

		/**
		 * Hook to maybe add some filters for the output of the core WP functions
		 *
		 * @param array $props
		 */
		do_action( 'groundhogg/posts/before_render', $props );

		add_filter( 'excerpt_more', [ $this, 'post_excerpt_ellipses' ] );

		if ( $this->context_is_plain() ) {
			$props['layout'] = 'plain';
		}

		switch ( $props['layout'] ) {
			default:
			case 'ul':
			case 'ol':

				$posts = $query->get_posts();

				$content = html()->e( $props['layout'] ?? 'ul', [], array_map( function ( $post ) use ( $props ) {
					return html()->e( 'li', [
						'style' => $props['headingStyle']
					], html()->e( 'a', [
						'href'  => get_permalink( $post ),
						'style' => [ 'color' => 'inherit' ]
					], get_the_title( $post ) ) );
				}, $posts ) );

				break;
			case 'grid':
			case 'cards':

				add_filter( 'post_thumbnail_html', __NAMESPACE__ . '\remove_thumbnail_dimensions' );

				$rows = [];

				$columnTable = sprintf( '<table class="email-columns %s responsive" role="presentation" width="100%%" style="width: 100%%; table-layout: fixed" cellpadding="0" cellspacing="0">', $props['layout'] );
				$columnGap   = sprintf( '<td class="email-columns-cell gap" style="width: %1$dpx;height: %1$dpx;line-height: 1;font-size: %1$dpx;" width="%1$d" height="%1$d">%2$s</td>', $props['gap'], str_repeat( '&nbsp;', 3 ) );

				$thumbnail = function ( $thumbnail_size ) {

					$post_thumbnail_id = get_post_thumbnail_id();
					$alt               = trim( wp_strip_all_tags( get_post_meta( $post_thumbnail_id, '_wp_attachment_image_alt', true ) ) );

					return html()->e( 'img', [
						'src'   => get_the_post_thumbnail_url( null, $thumbnail_size ),
						'alt'   => $alt,
						'class' => 'post-thumbnail ' . $thumbnail_size . ' ',
						'width' => '100%',
						'style' => [
							'vertical-align' => 'bottom'
						]
					] );
				};

				$render_post = function ( $thumbnail_size = 'thumbnail', $width = false ) use ( $props, $thumbnail ) {

					$card_style = wp_parse_args( $props['cardStyle'], [
						'borderStyle'     => 'none',
						'backgroundColor' => '#FFF',
						'padding'         => [ 'top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 20 ]
					] );

//                    var_dump( $props );

					extract( $card_style['padding'] );
					unset( $card_style['padding'] );

					$content_padding = implode( 'px ', [ $top, $right, $bottom, $left ] ) . 'px';

					if ( ! $width ) {
						$width = percentage( $props['columns'], 1 ) . '%';
					}

					return html()->e( 'td', [
						'class' => [
							'email-columns-cell',
							$props['layout'] === 'grid' ? 'post' : 'post-card',
							has_post_thumbnail() ? 'has-thumbnail' : ''
						],
						'width' => $width,
						'style' => array_merge( [
							'width'          => $width,
//							'background-color' => '#FFFFFF',
							'vertical-align' => 'top',
						], $card_style )
					], [
						has_post_thumbnail() && $props['thumbnail'] ? html()->e( 'div', [
							'class' => 'featured-image-wrap'
						], html()->e( 'a', [
							'href' => get_the_permalink(),
						], $thumbnail( $thumbnail_size ) ) ) : '',
						html()->e( 'table', [
							'class'       => 'card-content',
							'cellpadding' => 0,
							'cellspacing' => 0,
						], [
							html()->e( 'tr', [], html()->e( 'td', [
								'style' => [
									'padding' => $props['layout'] === 'cards' ? $content_padding : '20px 0'
								]
							], [
								html()->e( 'h2', [
									'style' => array_merge( [
										'margin-top' => '0'
									], $props['headingStyle'] )
								], html()->e( 'a', [
									'href'  => get_the_permalink(),
									'style' => [ 'color' => 'inherit' ]
								], get_the_title() ) ),
								$props['excerpt'] ? html()->e( 'p', [
									'class' => 'post-excerpt',
									'style' => $props['excerptStyle']
								], get_the_excerpt() ) : ''
							] ) )
						] ),
					] );
				};

				if ( $query->have_posts() ) {

					$rendered_posts = [];

					while ( $query->have_posts() ):

						$query->the_post();

						// First post is featured
						if ( $props['featured'] && empty( $rendered_posts ) ) {
							$rendered_posts[] = $render_post( 'large', '100%' );
						} else {
							$rendered_posts[] = $render_post( absint( $props['columns'] ) === 1 ? 'large' : $props['thumbnail_size'] );
						}


					endwhile;

					if ( $props['featured'] ) {
						$post   = array_shift( $rendered_posts );
						$rows[] = $columnTable;

						$rows[] = html()->e( 'tr', [
							'class' => 'email-columns-row',
						], $post );

						// Only add gap if more posts
						if ( ! empty( $rendered_posts ) ) {
							$rows[] = html()->e( 'tr', [
								'class' => 'email-columns-row',
							], $columnGap );
						}

						$rows[] = '</table>';
					}

					if ( ! empty( $rendered_posts ) ) {

						$rows[] = $columnTable;

						while ( ! empty( $rendered_posts ) ) {

							$posts   = array_splice( $rendered_posts, 0, absint( $props['columns'] ) );
							$columns = implode( $columnGap, $posts );

							$rows[] = html()->e( 'tr', [
								'class' => 'email-columns-row',
							], $columns );

							// Only add gap if more posts
							if ( ! empty( $rendered_posts ) ) {
								$rows[] = html()->e( 'tr', [
									'class' => 'email-columns-row',
								], $columnGap );
							}
						}

						$rows[] = '</table>';
					}
				}

				$content = implode( '', $rows );

				$query->reset_postdata();

				remove_filter( 'post_thumbnail_html', __NAMESPACE__ . '\remove_thumbnail_dimensions' );

				break;

			case 'h1':
			case 'h2':
			case 'h3':
			case 'h4':
			case 'h5':

				$tag = $props['layout'];

				$columnGap = sprintf( '<td class="email-columns-cell gap" style="width: %1$dpx;height: %1$dpx" width="%1$d" height="%1$d">%2$s</td>', $props['gap'], '&nbsp;' );

				ob_start();

				?>
                <table class="email-columns posts-table responsive" width="100%" style="width:100%">
					<?php while ( $query->have_posts() ):
						$query->the_post();

						$heading = html()->e( $tag, [
							'style' => $props['headingStyle']
						], html()->e( 'a', [
							'href'  => get_the_permalink(),
							'style' => [ 'color' => 'inherit' ],
						], get_the_title() ) );

						$excerpt = $props['excerpt'] ? html()->e( 'p', [
							'class' => 'post-excerpt',
							'style' => $props['excerptStyle']
						], get_the_excerpt() ) : '';

						$thumbnail_size    = $props['thumbnail_size'];
						$post_thumbnail_id = get_post_thumbnail_id();
						$alt               = trim( wp_strip_all_tags( get_post_meta( $post_thumbnail_id, '_wp_attachment_image_alt', true ) ) );

						$thumbnail = html()->e( 'a', [
							'href' => get_the_permalink()
						], html()->e( 'img', [
							'src'   => get_the_post_thumbnail_url( null, $thumbnail_size ),
							'alt'   => $alt,
							'class' => 'post-thumbnail ' . $thumbnail_size . ' ',
							'style' => [
								'vertical-align' => 'bottom'
							]
						] ) );

						?>
                        <tr class="email-columns-row">
                            <td class="post email-columns-cell post">
                                <table class="email-columns responsive post-table" width="100%" style="width:100%">
                                    <tr class="email-columns-row">
										<?php if ( $props['thumbnail'] && $props['thumbnail_position'] === 'left' ): ?>
                                            <td class="email-columns-cell one-half thumbnail" width="45%"
                                                style="width: 45%">
												<?php
                                                echo $thumbnail ?>
                                            </td>
											<?php echo $columnGap ?>
										<?php endif; ?>
                                        <td class="email-columns-cell post-details">
											<?php if ( $props['thumbnail'] && $props['thumbnail_position'] === 'above' ):
												echo $thumbnail;
											endif;

											echo $heading;

											if ( $props['thumbnail'] && $props['thumbnail_position'] === 'below' ):
												echo $thumbnail;
											endif;

											echo $excerpt
											?>
                                        </td>
										<?php if ( $props['thumbnail'] && $props['thumbnail_position'] === 'right' ): ?>
											<?php echo $columnGap ?>
                                            <td class="email-columns-cell one-half thumbnail" width="45%"
                                                style="width: 45%">
												<?php echo $thumbnail ?>
                                            </td>
										<?php endif; ?>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr class="email-columns-row">
							<?php echo $columnGap ?>
                        </tr>
					<?php endwhile; ?>
                </table>
				<?php

				$content = ob_get_clean();

				$query->reset_postdata();

				break;
			case 'plain':
				$posts   = $query->get_posts();
				$content = implode( "\n", array_map( function ( $post ) use ( $props ) {
					return sprintf( '- [%s](%s)', html_entity_decode( get_the_title( $post ) ), get_permalink( $post ) );
				}, $posts ) );

				break;
		}

		/**
		 * Hook to remove filters for the output of the core WP functions
		 *
		 * @param array $props
		 */
		do_action( 'groundhogg/posts/after_render', $props );

		remove_filter( 'excerpt_more', [ $this, 'post_excerpt_ellipses' ] );

		return $content;

		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Output the responses of a form submission in a structured format
	 *
	 * @param $args
	 *
	 * @return string|void
	 */
	public function replacement_form_submission( $args ) {

		$props = $this->parse_atts( $args, [
			'layout' => 'stacked',
			'form'   => '',
			'fields' => 'all',
			'hidden' => false,
			'type'   => 'form',
		] );

		if ( get_event_arg( 'submission_id' ) && empty( $props['form'] ) ) {
			// If there is a submission for the current event
			$submission = new Submission( absint( get_event_arg( 'submission_id' ) ) );
		} else {
			// do a query instead
			$query = new Table_Query( 'submissions' );
			$query->setLimit( 1 )
			      ->setOrderby( [ 'date_created', 'DESC' ] )
			      ->where()
			      ->equals( 'contact_id', $this->get_current_contact()->get_id() )
			      ->equals( 'type', $props['type'] );

			if ( in_array( $props['form'], [ 'last', 'newest', 'recent' ] ) || empty( $props['form'] ) ) {

				// get the most recent form submission

			} else if ( in_array( $props['form'], [ 'first', 'oldest' ] ) ) {

				// get the first form submission
				$query->setOrderby( [ 'date_created', 'ASC' ] );

			} else if ( absint( $props['form'] ) > 0 ) {

				// get the most recent submission for a specific form
				$query->where()->equals( 'form_id', absint( $props['form'] ) );
			}

			$submissions = $query->get_objects();

			if ( empty( $submissions ) ) {
				return '';
			}

			$submission = $submissions[0];
		}

		$answers = $submission->get_answers( $props['hidden'] );

		switch ( $props['layout'] ) {
			case 'table':

				return html()->e( 'table', [], array_map( function ( $answer ) {
					return html()->e( 'tr', [], [
						html()->e( 'th', [], $answer['label'] ),
						html()->e( 'td', [], $answer['value'] ),
					] );
				}, $answers ) );

			case 'beside':
				return implode( '', array_map( function ( $answer ) {
					return html()->e( 'p', [], bold_it( $answer['label'] ) . ': ' . $answer['value'] );
				}, $answers ) );
			case 'stacked':
			case 'below':
				return implode( '', array_map( function ( $answer ) {
					return html()->e( 'p', [], bold_it( $answer['label'] ) . '<br/>' . $answer['value'] );
				}, $answers ) );
			default:
			case 'normal':

				return implode( '', array_map( function ( $answer ) {

					return implode( '', [
						html()->e( 'p', [], bold_it( $answer['label'] ) ),
						html()->e( 'p', [], $answer['value'] )
					] );

				}, $answers ) );
		}
	}

	/**
	 * Show a view in browser link
	 *
	 * @return false|string
	 */
	public function view_in_browser_link() {

		$email = the_email();

		if ( ! $email || ! $email->exists() ) {
			return false;
		}

		return $email->browser_view_link();
	}

	/**
	 * Format a custom field as a grammatically correct list containing And
	 *
	 * @return false|string
	 */
	public function replacement_andList( $arg, $contact_id ) {
		return $this->replacement_meta( "$arg|andList", $contact_id );
	}


	/**
	 * Format a custom field as a grammatically correct list containing Or
	 *
	 * @return false|string
	 */
	public function replacement_orList( $arg, $contact_id ) {
		return $this->replacement_meta( "$arg|orList", $contact_id );
	}

	/**
	 * Format a custom field as an ordered list <ol>
	 *
	 * @return false|string
	 */
	public function replacement_ol( $arg, $contact_id ) {
		return $this->replacement_meta( "$arg|ol", $contact_id );
	}

	/**
	 * Format a custom field as an unordered list <ul>
	 *
	 * @return false|string
	 */
	public function replacement_ul( $arg, $contact_id ) {
		return $this->replacement_meta( "$arg|ul", $contact_id );
	}

	/**
	 * Substring format of inner replacement code
	 *
	 * @param $arg
	 *
	 * @return false|string
	 */
	public function replacement_substring( $arg ) {
		$args = split_last( $arg, ',', 2 );

		if ( empty( $args[0] ) ) {
			return '';
		}

		$string = $args[0];
		$start  = $args[1] ?? 0;
		$end    = $args[2] ?? strlen( $string );

		return substr( $string, $start, $end );
	}

	/**
     * Get the replacement for the current email
     *
	 * @throws \Exception
	 *
	 * @param $key
	 *
	 * @return bool|mixed|string
	 */
    public function replacement_this_email( $key = '' ) {

        if ( empty( $key ) || ! is_string( $key ) ) {
            return '';
        }

        $email = the_email();
        if ( ! $email || ! $email->exists() ) {
            return '';
        }

        $replacements = $email->get_meta( 'replacements' );
        return get_array_var( $replacements, $key, '' );
    }

	/**
	 * Get the replacement for the current email
	 *
	 * @throws \Exception
	 *
	 * @param $key
	 *
	 * @return bool|mixed|string
	 */
	public function replacement_this_flow( $key = '' ) {

		if ( empty( $key ) || ! is_string( $key ) ) {
			return '';
		}

		$flow = the_funnel();

		if ( ! $flow || ! $flow->exists() ) {
			return '';
		}

		$replacements = $flow->get_meta( 'replacements' );
		return get_array_var( $replacements, $key, '' );
	}

	/**
     * Accepts text and adds that text to the current redactor
     *
	 * @param $text
	 *
	 * @return string
	 */
	public function replacement_redact( $text = '') {

        if ( ! is_string( $text ) ){
            return '';
        }

        add_redaction( $text );

        return $text;
    }

	/**
	 * We don't want this to be serialized
	 *
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		return false;
	}
}
