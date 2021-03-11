<?php

namespace Groundhogg\Reporting\Reports;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-01-03
 * Time: 3:24 PM
 */
class Contacts_By_Search_Engine extends Contacts_By_Meta {
	public function __construct() {
		add_filter( "groundhogg/reporting/reports/{$this->get_id()}/data", [ $this, 'parse_data' ] );
		parent::__construct();
	}

	/**
	 * @return string
	 */
	public function get_id() {
		return 'contacts_by_search_engine';
	}

	/**
	 * @return string
	 */
	public function get_name() {
		return __( 'Contacts By Search Engine', 'groundhogg' );
	}

	/**
	 * Return the meta_key used to query the DB
	 *
	 * @return string
	 */
	public function get_meta_key() {
		return 'lead_source';
	}

	/**
	 * Setup the search_engines array from the yaml file in lib
	 */
	public function get_search_engines() {
		if ( ! class_exists( 'Spyc' ) ) {
			include_once GROUNDHOGG_PATH . 'includes/lib/yaml/Spyc.php';
		}

		return \Spyc::YAMLLoad( GROUNDHOGG_PATH . 'includes/lib/potential-known-leadsources/SearchEngines.yml' );
	}

	/**
	 * Special search function for comparing lead sources to potential search engine matches.
	 *
	 * @param $search string the URL in question
	 * @param $urls array list of string potential matches...
	 *
	 * @return bool
	 */
	private function in_urls( $search, $urls ) {

		foreach ( $urls as $url ) {

			/* Given YAML dataset uses .{} as sequence for match all expression, convert into regex friendly */
			$url     = str_replace( '.{}', '\.{1,3}', $url );
			$url     = str_replace( '{}.', '.{1,}?\.?', $url );
			$pattern = '#' . $url . '#';
//            var_dump( $pattern );
			if ( preg_match( $pattern, $search ) ) {
				return true;
			}

		}

		return false;
	}

	/**
	 * @param $data
	 *
	 * @return mixed
	 */
	public function parse_data( $data ) {
		$search_engines = $this->get_search_engines();

		$return = [];

		foreach ( $data as $datum => $num_contacts ) {
			if ( filter_var( $datum, FILTER_VALIDATE_URL ) ) {
				$test_lead_source = parse_url( $datum, PHP_URL_HOST );
				$test_lead_source = str_replace( 'www.', '', $test_lead_source );
				foreach ( $search_engines as $engine_name => $atts ) {
					$urls = $atts[0]['urls'];
					if ( $this->in_urls( $test_lead_source, $urls ) ) {
						$return[ $engine_name ] = $num_contacts;
					}
				}
			}
		}

		return $return;
	}
}