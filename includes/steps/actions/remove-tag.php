<?php

namespace Groundhogg\Steps\Actions;

use Groundhogg\Contact;
use Groundhogg\Event;
use Groundhogg\HTML;
use Groundhogg\Plugin;
use Groundhogg\Step;
use function Groundhogg\andList;
use function Groundhogg\array_bold;
use function Groundhogg\site_locale_is_english;
use function Groundhogg\get_db;
use function Groundhogg\parse_tag_list;
use function Groundhogg\validate_tags;

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
class Remove_Tag extends Action {

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
	 * @param $step Step
	 */
	public function settings( $step ) {

		$this->start_controls_section();

		$this->add_control( 'tags', [
			'label'       => __( 'Remove These Tags:', 'groundhogg' ),
			'type'        => HTML::TAG_PICKER,
			'description' => __( 'Add new tags by hitting [enter] or by typing a [comma].', 'groundhogg' ),
			'field'       => [
				'multiple' => true,
			]
		] );

		$this->end_controls_section();
	}

	/**
	 * Save the step settings
	 *
	 * @param $step Step
	 */
	public function save( $step ) {
		$tags = validate_tags( $this->get_posted_data( 'tags', [] ) );
		$this->save_setting( 'tags', $tags );
		$tags = array_bold( parse_tag_list( $tags, 'name', false ) );

		if ( site_locale_is_english() ) {

			if ( empty( $tags ) ) {
				$name = __( 'Remove tags', 'groundhogg' );
			} else if ( count( $tags ) >= 4 ) {
				$name = sprintf( __( 'Remove %s tags', 'groundhogg' ), '<b>' . count( $tags ) . '</b>' );
			} else {
				$name = sprintf( __( 'Remove %s', 'groundhogg' ), andList( $tags ) );
			}

			$step->update( [
				'step_title' => $name
			] );
		}
	}

	public function step_title_edit( $step ) {

		if ( ! site_locale_is_english() ) {
			parent::step_title_edit( $step );

			return;
		}

		?>
        <div class="gh-panel-header">
            <h2><?php _e( 'Remove Tag Settings' ) ?></h2>
        </div>
		<?php
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

	/**
	 * @param array $args
	 * @param Step  $step
	 */
	public function import( $args, $step ) {
		if ( empty( $args['tags'] ) ) {
			return;
		}

		$tags = validate_tags( $args['tags'] );

		$this->save_setting( 'tags', $tags );
	}

	/**
	 * @param array $args
	 * @param Step  $step
	 *
	 * @return array
	 */
	public function export( $args, $step ) {
		$args['tags'] = array();

		$tags = wp_parse_id_list( $this->get_setting( 'tags' ) );

		if ( empty( $tags ) ) {
			return $args;
		}

		foreach ( $tags as $tag_id ) {

			$tag = get_db( 'tags' )->get( $tag_id );

			if ( $tag ) {
				$args['tags'][] = $tag->tag_name;
			}

		}

		return $args;
	}
}
