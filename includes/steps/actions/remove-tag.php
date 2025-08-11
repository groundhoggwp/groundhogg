<?php

namespace Groundhogg\Steps\Actions;

use Groundhogg\Contact;
use Groundhogg\Event;
use Groundhogg\Step;
use function Groundhogg\andList;
use function Groundhogg\array_bold;
use function Groundhogg\html;
use function Groundhogg\parse_tag_list;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove Tag
 *
 * This will remove any specified tags from the contact
 *
 * @since       File available since Release 0.9
 * @subpackage  Elements/Actions
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Elements
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
		return esc_html_x( 'Remove Tag', 'step_name', 'groundhogg' );
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
		return esc_html_x( 'Remove a tag from a contact.', 'step_description', 'groundhogg' );
	}

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	public function get_icon() {
//		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/remove-tag.png';
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/crm/remove-tag.svg';
	}

	/**
	 * @param $step Step
	 */
	public function settings( $step ) {

		html( 'p', [], __( 'Remove all of the following tags...', 'groundhogg' ) );

		html( html()->dropdown( [
			'id' => $this->setting_id_prefix( 'tags' )
		] ) );

		html( 'p' );
	}


	public function generate_step_title( $step ) {

		$tags = array_bold( parse_tag_list( $this->get_setting( 'tags' ), 'name', false ) );

		if ( empty( $tags ) ) {
			$name = __( 'Remove tags', 'groundhogg' );
		} else if ( count( $tags ) >= 4 ) {
			/* translators: %s: the number of tags to remove */
			$name = sprintf( __( 'Remove %s tags', 'groundhogg' ), '<b>' . count( $tags ) . '</b>' );
		} else {
			/* translators: %s: the list of tags to remove, like "Customer and user" */
			$name = sprintf( __( 'Remove %s', 'groundhogg' ), andList( $tags ) );
		}

		return $name;
	}

	/**
	 * Process the apply tag step...
	 *
	 * @param $contact Contact
	 * @param $event   Event
	 *
	 * @return true
	 */
	public function run( $contact, $event ) {
		$tags = wp_parse_id_list( $this->get_setting( 'tags' ) );

		return $contact->remove_tag( $tags );
	}
}
