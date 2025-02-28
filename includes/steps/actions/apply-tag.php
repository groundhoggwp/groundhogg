<?php

namespace Groundhogg\Steps\Actions;

use Groundhogg\Contact;
use Groundhogg\Event;
use Groundhogg\Step;
use function Groundhogg\andList;
use function Groundhogg\array_bold;
use function Groundhogg\get_db;
use function Groundhogg\html;
use function Groundhogg\parse_tag_list;

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
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/apply-tag.svg';
	}

	/**
	 * @param $step Step
	 */
	public function settings( $step ) {

		echo html()->e( 'p', [], __( 'Apply all of the following tags...', 'groundhogg' ) );

		echo html()->dropdown( [
			'id' => $this->setting_id_prefix( 'tags' )
		] );

		echo html()->e( 'p' );
	}

	public function get_settings_schema() {
		return [
			'tags' => [
				'default'  => [],
				'sanitize' => function ( $tags ) {
					return parse_tag_list( $tags );
				}
			],
		];
	}

	public function validate_settings( Step $step ) {
		$tags = $this->get_setting( 'tags' );
		if ( empty( $tags ) ) {
			$step->add_error( 'no_tags', 'No tags have been selected.' );
		}
	}

	public function generate_step_title( $step ) {

		$tags = array_bold( parse_tag_list( $this->get_setting( 'tags' ), 'name', false ) );

		if ( empty( $tags ) ) {
			$name = __( 'Apply tags', 'groundhogg' );
		} else if ( count( $tags ) >= 4 ) {
			$name = sprintf( __( 'Apply %s tags', 'groundhogg' ), '<b>' . count( $tags ) . '</b>' );
		} else {
			$name = sprintf( __( 'Apply %s', 'groundhogg' ), andList( $tags ) );
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
		return $contact->add_tag( $this->get_setting( 'tags' ) );
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
