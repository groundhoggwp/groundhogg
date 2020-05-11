<?php

namespace Groundhogg\Reporting\Reports;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-01-03
 * Time: 3:24 PM
 */
class Contacts_By_Social_Media extends Contacts_By_Meta {
	public function __construct() {
		add_filter( "groundhogg/reporting/reports/{$this->get_id()}/data", [ $this, 'parse_data' ] );
		parent::__construct();
	}

	/**
	 * @return string
	 */
	public function get_id() {
		return 'contacts_by_social_media';
	}

	/**
	 * @return string
	 */
	public function get_name() {
		return __( 'Contacts By Social Media', 'groundhogg' );
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

		Return \Spyc::YAMLLoad( GROUNDHOGG_PATH . 'includes/lib/potential-known-leadsources/Socials.yml' );
	}

	/**
	 * @param $data
	 *
	 * @return mixed
	 */
	public function parse_data( $data ) {
		$networks = $this->get_search_engines();

		$return = [];

		foreach ( $data as $datum => $num_contacts ) {
			if ( filter_var( $datum, FILTER_VALIDATE_URL ) ) {
				$test_lead_source = parse_url( $datum, PHP_URL_HOST );
				$test_lead_source = str_replace( 'www.', '', $test_lead_source );
				foreach ( $networks as $network => $urls ) {
					if ( in_array( $test_lead_source, $urls ) ) {
						$return[ $network ] = $num_contacts;
					}
				}
			}
		}

		return $return;
	}
}