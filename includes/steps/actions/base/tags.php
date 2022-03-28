<?php

namespace Groundhogg\Steps\Actions\Base;

use Groundhogg\Contact;
use Groundhogg\Event;
use Groundhogg\HTML;
use Groundhogg\Step;
use Groundhogg\Steps\Actions\Action;
use Groundhogg\Tag;
use function Groundhogg\array_map_to_class;
use function Groundhogg\class_list_to_ids;
use function Groundhogg\id_list_to_class;
use function Groundhogg\isset_not_empty;
use function Groundhogg\validate_tags;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Apply tag
 *
 * Adds a tag to the contact.
 *
 * @package     Elements
 * @subpackage  Elements/Actions
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */
abstract class Tags extends Action {

	/**
	 * Save the step settings
	 *
	 * @param $step Step
	 */
	public function save( $step ) {
		$this->save_setting( 'tags', validate_tags( $this->get_posted_data( 'tags', [] ) ) );
	}

	/**
	 * @param array $args
	 * @param Step  $step
	 */
	public function import( $args, $step ) {

		if ( ! isset_not_empty( $args, 'tags' ) ) {
			return;
		}

		// legacy import
		if ( is_string( $args['tags'][0] ) ) {
			$this->save_setting( 'tags', validate_tags( $args['tags'] ) );
		}

		$tags = array_map( function ( $t ) {

			$tag = new Tag();

			$tag->create( $t['data'] );

			return $tag;

		}, $args['tags'] );

		$this->save_setting( 'tags', class_list_to_ids( $tags ) );
	}

	/**
	 * @param array $args
	 * @param Step  $step
	 *
	 * @return array
	 */
	public function export( $args, $step ) {
		$args['tags'] = id_list_to_class( $step->get_meta( 'tags' ), Tag::class );

		return $args;
	}
}