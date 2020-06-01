<?php

namespace Groundhogg\Utils;

use function Groundhogg\isset_not_empty;
use Groundhogg\Template_Loader;

abstract class Abstract_Rewrites {

	/**
	 * Rewrites constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'add_rewrite_rules' ] );
		add_filter( 'query_vars', [ $this, 'add_query_vars' ] );
		add_filter( 'request', [ $this, 'parse_query' ] );

		// Give precedence over page builders...
		add_filter( 'template_include', [ $this, 'template_include' ], 99 );
		add_action( 'template_redirect', [ $this, 'template_redirect' ] );
	}

	/**
	 * Add the rewrite rules required for the Preferences center.
	 */
	abstract public function add_rewrite_rules();

	/**
	 * Add the query vars needed to manage the request.
	 *
	 * @param $vars
	 *
	 * @return array
	 */
	abstract public function add_query_vars( $vars );

	/**
	 * Maps a function to a specific query var.
	 *
	 * @param $query
	 *
	 * @return mixed
	 */
	abstract public function parse_query( $query );

	/**
	 * Overwrite the existing template with the manage preferences template.
	 *
	 * @param $template
	 *
	 * @return string
	 */
	abstract public function template_include( $template = '' );

	/**
	 * Perform Superlink/link click benchmark stuff.
	 *
	 * @param string $template
	 */
	abstract public function template_redirect( $template = '' );

	/**
	 * @return Template_Loader
	 */
	public function get_template_loader() {
		return new Template_Loader();
	}

	/**
	 * @param $array
	 * @param $key
	 * @param $func
	 */
	public function map_query_var( &$array, $key, $func ) {
		if ( ! function_exists( $func ) ) {
			return;
		}

		if ( isset_not_empty( $array, $key ) ) {
			$array[ $key ] = call_user_func( $func, $array[ $key ] );
		}
	}
}