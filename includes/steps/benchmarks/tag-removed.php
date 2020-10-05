<?php

namespace Groundhogg\Steps\Benchmarks;

use Groundhogg\Contact;
use function Groundhogg\get_array_var;
use Groundhogg\HTML;
use function Groundhogg\get_db;
use function Groundhogg\isset_not_empty;
use Groundhogg\Plugin;
use Groundhogg\Step;
use Groundhogg\Tag;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Tag Removed
 *
 * This will run whenever a tag is removed
 *
 * @package     Elements
 * @subpackage  Elements/Benchmarks
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
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
		return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/tag-removed.png';
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
	 * Based on the current step and contact,
	 *
	 * @return bool
	 */
	protected function can_complete_step() {

		$removed_tag = $this->get_data( 'tag_id' );

		$tags      = wp_parse_id_list( $this->get_setting( 'tags' ) );
		$condition = $this->get_setting( 'condition', 'any' );

		switch ( $condition ) {
			default:
			case 'any':
				$not_has_tags = in_array( $removed_tag, $tags );
				break;
			case 'all':
				$diff         = array_diff( $tags, $this->get_current_contact()->get_tag_ids() );
				$not_has_tags = in_array( $removed_tag, $tags ) && count( $diff ) === count( $tags );
				break;
		}

		return $not_has_tags;
	}
}