<?php

namespace Groundhogg\Steps\Actions;

use Groundhogg\Contact;
use Groundhogg\Event;
use Groundhogg\Step;
use function Groundhogg\do_replacements;
use function Groundhogg\html;
use function Groundhogg\Ymd_His;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Apply Note
 *
 * Apply a note to a contact through the funnel builder.
 *
 * @since       File available since Release 0.9
 * @subpackage  Elements/Actions
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Elements
 */
class Apply_Note extends Action {
	/**
	 * @return string
	 */
	public function get_help_article() {
		return 'https://docs.groundhogg.io/docs/builder/actions/apply-note/';
	}

	/**
	 * ] et the element name
	 *
	 * @return string
	 */
	public function get_name() {
		return _x( 'Add Note', 'step_name', 'groundhogg' );
	}

	/**
	 * Get the element type
	 *
	 * @return string
	 */
	public function get_type() {
		return 'apply_note';
	}

	public function get_sub_group() {
		return 'crm';
	}

	/**
	 * Get the description
	 *
	 * @return string
	 */
	public function get_description() {
		return _x( 'Add a note to the notes section of a contact.', 'step_description', 'groundhogg' );
	}

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	public function get_icon() {
//		return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/apply-note.png';
		return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/add-note.svg';
	}

	/**
	 * @param $step Step
	 */
	public function settings( $step ) {

		echo html()->textarea( [
			'id'    => $this->setting_id_prefix( 'note_text' ),
			'name'  => 'note_text',
			'value' => $this->get_setting( 'note_text' )
		] );

	}

	public function get_settings_schema() {
		return [
			'note_text' => [
				'default'  => '',
				'sanitize' => 'wp_kses_post'
			]
		];
	}

	/**
	 * Process the apply note step...
	 *
	 * @param $contact Contact
	 * @param $event   Event
	 *
	 * @return true;
	 */
	public function run( $contact, $event ) {

		$note = $this->get_setting( 'note_text' );

		$finished_note = do_replacements( $note, $contact );

		// Add funnel context
		$note = $contact->add_note( $finished_note, 'funnel', $event->get_funnel_id(), [
			'timestamp'    => $event->get_time(),
			'date_created' => Ymd_His( $event->get_time() )
		] );

		return true;

	}
}
