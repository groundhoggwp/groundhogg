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
use function Groundhogg\parse_tag_list;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Tag Applied
 *
 * This will run whenever a tag is applied
 *
 * @package     Elements
 * @subpackage  Elements/Benchmarks
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */
class Tag_Applied extends Benchmark {

	const TYPE = 'tag_applied';

	public function get_help_article() {
		return 'https://docs.groundhogg.io/docs/builder/benchmarks/tag-applied/';
	}

	/**
	 * Get the element name
	 *
	 * @return string
	 */
	public function get_name() {
		return _x( 'Tag Applied', 'step_name', 'groundhogg' );
	}

	/**
	 * Get the element type
	 *
	 * @return string
	 */
	public function get_type() {
		return 'tag_applied';
	}

	/**
	 * Get the description
	 *
	 * @return string
	 */
	public function get_description() {
		return _x( 'Runs whenever any of the specified tags are added to a contact.', 'step_description', 'groundhogg' );
	}

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/tag-applied.png';
	}

	/**
	 * @param $step Step
	 */
	public function settings( $step ) {
		$this->start_controls_section();

		$html = Plugin::$instance->utils->html;

		$condition_picker = $html->dropdown( [
			'name'        => $this->setting_name_prefix( 'condition' ),
			'selected'    => $this->get_setting( 'condition', 'any' ),
			'option_none' => false,
			'style'       => [ 'vertical-align' => 'middle' ],
			'options'     =>
				[
					'any' => __( 'any' ),
					'all' => __( 'all' ),
				]
		] );

		$this->add_control( 'tags', [
			'label'       => sprintf( __( 'Run when %s of these tags are applied:', 'groundhogg' ), $condition_picker ),
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
		$this->save_setting( 'tags', Plugin::$instance->dbs->get_db( 'tags' )->validate( $this->get_posted_data( 'tags', [] ) ) );
		$this->save_setting( 'condition', sanitize_text_field( $this->get_posted_data( 'condition', 'any' ) ) );
	}

	/**
	 * @param array $args
	 * @param Step $step
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
	 * @param Step $step
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

			$tag = new Tag( $tag_id );

			if ( $tag ) {
				$args['tags'][] = $tag->get_name();
			}

		}

		return $args;
	}

	/**
	 * get the hook for which the benchmark will run
	 *
	 * @return string[]
	 */
	protected function get_complete_hooks() {
		return [
			[ 'groundhogg/contact/tag_applied', 2 ]
		];
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

		$applied_tag = $this->get_data( 'tag_id' );

		$tags      = parse_tag_list( $this->get_setting( 'tags' ) );
		$condition = $this->get_setting( 'condition', 'any' );

		switch ( $condition ) {
			default:
			case 'any':
				$has_tags = in_array( $applied_tag, $tags );
				break;
			case 'all':
				$intersect = array_intersect( $tags, $this->get_current_contact()->get_tags() );
				$has_tags  = in_array( $applied_tag, $tags ) && count( $intersect ) === count( $tags );
				break;
		}

		return $has_tags;
	}
}