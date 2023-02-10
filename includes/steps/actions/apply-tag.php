<?php

namespace Groundhogg\Steps\Actions;

use Groundhogg\Contact;
use Groundhogg\Event;
use Groundhogg\HTML;
use Groundhogg\Step;
use function Groundhogg\andList;
use function Groundhogg\array_bold;
use function Groundhogg\force_custom_step_names;
use function Groundhogg\get_db;
use function Groundhogg\parse_tag_list;
use function Groundhogg\validate_tags;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Apply tag
 *
 * Adds a tag to the contact.
 *
 * @since       File available since Release 0.9
 * @subpackage  Elements/Actions
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Elements
 */
class Apply_Tag extends Action {

	/**
	 * @return string
	 */
	public function get_help_article() {
		return 'https://docs.groundhogg.io/docs/builder/actions/apply-tag/';
	}

	/**
	 * Get the element name
	 *
	 * @return string
	 */
	public function get_name() {
		return _x( 'Apply Tag', 'step_name', 'groundhogg' );
	}

	/**
	 * Get the element type
	 *
	 * @return string
	 */
	public function get_type() {
		return 'apply_tag';
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
		return _x( 'Add a tag to a contact.', 'step_description', 'groundhogg' );
	}

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	public function get_icon() {
//		return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/apply-tag.png';
		return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/apply-tag.svg';
	}

	/**
	 * @param $step Step
	 */
	public function settings( $step ) {

		$this->start_controls_section();

		$this->add_control( 'tags', [
			'label'       => __( 'Apply These Tags:', 'groundhogg' ),
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

		if ( ! force_custom_step_names() ) {

			if ( empty( $tags ) ) {
				$name = __( 'Apply tags', 'groundhogg' );
			} else if ( count( $tags ) >= 4 ) {
				$name = sprintf( __( 'Apply %s tags', 'groundhogg' ), '<b>' . count( $tags ) . '</b>' );
			} else {
				$name = sprintf( __( 'Apply %s', 'groundhogg' ), andList( $tags ) );
			}

			$step->update( [
				'step_title' => $name
			] );
		}
	}

	public function step_title_edit( $step ) {

		if ( force_custom_step_names() ) {
			parent::step_title_edit( $step );

			return;
		}

		?>
        <div class="gh-panel-header">
            <h2><?php _e( 'Apply Tag Settings' ) ?></h2>
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

		return $contact->add_tag( $tags );
	}

	/**
	 * @param array $args
	 * @param Step  $step
	 */
	public function import( $args, $step ) {
		if ( empty( $args['tags'] ) ) {
			return;
		}

		$tags = get_db( 'tags' )->validate( $args['tags'] );

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
