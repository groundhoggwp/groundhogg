<?php

namespace Groundhogg\Steps\Actions;

use Groundhogg\Contact;
use Groundhogg\Event;
use Groundhogg\HTML;
use Groundhogg\Plugin;
use Groundhogg\Step;
use function Groundhogg\validate_tags;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove Tag
 *
 * This will remove any specified tags from the contact
 *
 * @package     Elements
 * @subpackage  Elements/Actions
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */
class Remove_Tag extends Apply_Tag {

	/**
	 * @return string
	 */
	public function get_help_article() {
		return 'https://docs.groundhogg.io/docs/builder/actions/remove-tag/';
	}

	/**
	 * Get the element name
	 *
	 * @return string
	 */
	public function get_name() {
		return _x( 'Remove Tag', 'step_name', 'groundhogg' );
	}

	/**
	 * Get the element type
	 *
	 * @return string
	 */
	public function get_type() {
		return 'remove_tag';
	}

	/**
	 * Get the description
	 *
	 * @return string
	 */
	public function get_description() {
		return _x( 'Remove a tag from a contact.', 'step_description', 'groundhogg' );
	}

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/remove-tag.png';
	}

	/**
	 * Process the apply tag step...
	 *
	 * @param $contact Contact
	 * @param $event Event
	 *
	 * @return true
	 */
	public function run( $contact, $event ) {
		$tags = wp_parse_id_list( $this->get_setting( 'tags' ) );

		return $contact->remove_tag( $tags );
	}
}