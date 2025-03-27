<?php

namespace Groundhogg\Steps\Premium\Benchmarks;

use Groundhogg\Steps\Benchmarks\Benchmark;
use Groundhogg\Steps\Premium\Trait_Premium_Step;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Page_Visited extends Benchmark {

	use Trait_Premium_Step;

	public function get_help_article() {
		return 'https://docs.groundhogg.io/docs/builder/benchmarks/page-visited/';
	}

	/**
	 * Get the element name
	 *
	 * @return string
	 */
	public function get_name() {
		return _x( 'Page Visited', 'step_name', 'groundhogg-pro' );
	}

	/**
	 * Get the element type
	 *
	 * @return string
	 */
	public function get_type() {
		return 'page_visited';
	}

	public function get_sub_group() {
		return 'activity';
	}

	/**
	 * Get the description
	 *
	 * @return string
	 */
	public function get_description() {
		return _x( 'Runs whenever the specified page is visited.', 'step_description', 'groundhogg-pro' );
	}

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/activity/page-visited.svg';
	}
}
