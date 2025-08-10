<?php

namespace Groundhogg\Steps\Benchmarks;

use Groundhogg\Contact;
use Groundhogg\Step;
use Groundhogg\Tag;
use function Groundhogg\andList;
use function Groundhogg\array_bold;
use function Groundhogg\get_db;
use function Groundhogg\html;
use function Groundhogg\one_of;
use function Groundhogg\orList;
use function Groundhogg\parse_tag_list;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Tag Applied
 *
 * This will run whenever a tag is applied
 *
 * @since       File available since Release 0.9
 * @subpackage  Elements/Benchmarks
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Elements
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

	public function get_sub_group() {
		return 'crm';
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
//		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/tag-applied.png';
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/crm/tag-applied.svg';
	}

	public function tag_settings() {
		echo html()->e( 'div', [
			'class' => 'display-flex gap-10 align-top'
		], [
			html()->dropdown( [
				'name'        => $this->setting_name_prefix( 'condition' ),
				'selected'    => $this->get_setting( 'condition', 'any' ),
				'option_none' => false,
				'style'       => [ 'vertical-align' => 'middle' ],
				'options'     =>
					[
						'any' => __( 'Any of...', 'groundhogg' ),
						'all' => __( 'All of...', 'groundhogg' ),
					]
			] ),
			html()->dropdown( [
				'id' => $this->setting_id_prefix( 'tags' )
			] )
		] );

		echo html()->e( 'p' );
	}

	/**
	 * @param $step Step
	 */
	public function settings( $step ) {

		echo html()->e( 'p', [], __( 'Run when the following tags are applied to the contact...', 'groundhogg' ) );

		$this->tag_settings();
	}

	public function get_settings_schema() {
		return [
			'tags'      => [
				'default'  => [],
				'sanitize' => function ( $tags ) {
					return parse_tag_list( $tags );
				}
			],
			'condition' => [
				'default'  => 'any',
				'sanitize' => function ( $value ) {
					return one_of( $value, [ 'any', 'all' ] );
				}
			]
		];
	}

	public function validate_settings( Step $step ) {
		$tags = $this->get_setting( 'tags' );
		if ( empty( $tags ) ) {
			$step->add_error( 'no_tags', 'No tags have been selected.' );
		}
	}

	public function generate_step_title( $step ) {

		$condition = $this->get_setting( 'condition' );
		$tags      = array_bold( parse_tag_list( $this->get_setting( 'tags' ), 'name', false ) );

		if ( empty( $tags ) ) {
			$name = __( 'A tag is applied', 'groundhogg' );
		} else if ( count( $tags ) === 1 ) {
			$name = sprintf( __( '%s is applied', 'groundhogg' ), orList( $tags ) );
		} else if ( count( $tags ) >= 4 ) {
			switch ( $condition ) {
				default:
				case 'any':
					$name = sprintf( __( 'Any of %s tags are applied', 'groundhogg' ), '<b>' . count( $tags ) . '</b>' );
					break;
				case 'all':
					$name = sprintf( __( '%s tags are applied', 'groundhogg' ), '<b>' . count( $tags ) . '</b>' );
					break;
			}
		} else {

			switch ( $condition ) {
				default:
				case 'any':
					$name = sprintf( __( '%s is applied', 'groundhogg' ), orList( $tags ) );
					break;
				case 'all':
					$name = sprintf( __( '%s are applied', 'groundhogg' ), andList( $tags ) );
					break;
			}
		}

		return $name;
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
	 * @return array[]
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

		$tags      = parse_tag_list( $this->get_setting( 'tags' ), 'ID', false );
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
