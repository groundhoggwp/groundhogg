<?php

namespace Groundhogg\Steps\Benchmarks;

use Groundhogg\Contact;
use Groundhogg\Step;
use function Groundhogg\andList;
use function Groundhogg\array_bold;
use function Groundhogg\html;
use function Groundhogg\orList;
use function Groundhogg\parse_tag_list;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Tag Removed
 *
 * This will run whenever a tag is removed
 *
 * @since       File available since Release 0.9
 * @subpackage  Elements/Benchmarks
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Elements
 */
class Tag_Removed extends Tag_Applied {

	public function get_help_article() {
		return 'https://docs.groundhogg.io/docs/builder/benchmarks/tag-removed/';
	}

	/**
	 * Get the element name
	 *
	 * @return string
	 */
	public function get_name() {
		return _x( 'Tag Removed', 'step_name', 'groundhogg' );
	}

	/**
	 * Get the element type
	 *
	 * @return string
	 */
	public function get_type() {
		return 'tag_removed';
	}

	/**
	 * Get the description
	 *
	 * @return string
	 */
	public function get_description() {
		return _x( 'Runs whenever any of the specified tags are removed from a contact.', 'step_description', 'groundhogg' );
	}

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	public function get_icon() {
//		return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/tag-removed.png';
		return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/tag-removed.svg';
	}

	/**
	 * @param $step Step
	 */
	public function settings( $step ) {

		echo html()->e( 'p', [], __( 'Run when the following tags are removed from the contact...', 'groundhogg' ) );

		parent::settings( $step );
	}

	public function generate_step_title( $step ) {

		$condition = $this->get_setting( 'condition' );
		$tags      = array_bold( parse_tag_list( $this->get_setting( 'tags' ), 'name', false ) );

		if ( empty( $tags ) ) {
			$name = __( 'A tag is removed', 'groundhogg' );
		} else if ( count( $tags ) === 1 ) {
			$name = sprintf( __( '%s is removed', 'groundhogg' ), orList( $tags ) );
		} else if ( count( $tags ) >= 4 ) {
			switch ( $condition ) {
				default:
				case 'any':
					$name = sprintf( __( 'Any of %s tags are removed', 'groundhogg' ), '<b>' . count( $tags ) . '</b>' );
					break;
				case 'all':
					$name = sprintf( __( '%s tags are removed', 'groundhogg' ), '<b>' . count( $tags ) . '</b>' );
					break;
			}
		} else {

			switch ( $condition ) {
				default:
				case 'any':
					$name = sprintf( __( '%s is removed', 'groundhogg' ), orList( $tags ) );
					break;
				case 'all':
					$name = sprintf( __( '%s are removed', 'groundhogg' ), andList( $tags ) );
					break;
			}
		}

		return $name;
	}

	/**
	 * get the hook for which the benchmark will run
	 *
	 * @return string[]
	 */
	protected function get_complete_hooks() {
		return [ 'groundhogg/contact/tag_removed' => 2 ];
	}

	/**
	 * Setup
	 *
	 * @param $contact
	 * @param $tag_id
	 */
	public function setup( $contact, $tag_id ) {
		$this->set_current_contact( $contact );
		$this->add_data( 'tag_id', $tag_id );
	}

	/**
	 * Get the contact from the data set.
	 *
	 * @return Contact
	 */
	protected function get_the_contact() {
		return $this->get_current_contact();
	}

	/**
	 * Based on the current step and contact,
	 *
	 * @return bool
	 */
	protected function can_complete_step() {

		$removed_tag = $this->get_data( 'tag_id' );

		$tags      = parse_tag_list( $this->get_setting( 'tags' ) );
		$condition = $this->get_setting( 'condition', 'any' );

		switch ( $condition ) {
			default:
			case 'any':
				$not_has_tags = in_array( $removed_tag, $tags );
				break;
			case 'all':
				$diff         = array_diff( $tags, $this->get_current_contact()->get_tags() );
				$not_has_tags = in_array( $removed_tag, $tags ) && count( $diff ) === count( $tags );
				break;
		}

		return $not_has_tags;
	}
}
