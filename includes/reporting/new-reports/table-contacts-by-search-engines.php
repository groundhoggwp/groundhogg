<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\Plugin;
use function Groundhogg\get_db;
use function Groundhogg\html;
use function Groundhogg\percentage;

class Table_Contacts_By_Search_Engines extends  Base_Table_Report
{
	function only_show_top_10() {
		return true ;
	}

	function column_title() {
		// TODO: Implement column_title() method.
	}

	/**
	 * @return array
	 */
	public function get_data()
	{
		return [
			'type'=> 'table',
			'label'=> $this->get_label(),
			'data' =>
				$this->get_search_engine_data()
		];
	}

	public function get_label() {
		return [
			__( 'Search Engines', 'groundhogg' ),
			__( 'Contacts', 'groundhogg' ),
		];

	}


	protected function get_search_engine_data() {

		$contacts = get_db( 'contacts' )->query( [
			'date_query' => [
				'after'  => date( 'Y-m-d H:i:s', $this->start ),
				'before' => date( 'Y-m-d H:i:s', $this->end ),
			]
		] );

		$contacts = wp_parse_id_list( wp_list_pluck( $contacts, 'ID' ) );


		$rows = get_db( 'contactmeta' )->query( [
			'contact_id' =>$contacts,
			'meta_key' => 'lead_source'
		], false );


		$values = wp_list_pluck( $rows, 'meta_value'  );

		$counts = array_count_values( $values );


		$search_engines = $this->get_search_engines();

		$return = [];

		foreach ( $counts as $datum => $num_contacts ){
			if ( filter_var( $datum, FILTER_VALIDATE_URL ) ){
				$test_lead_source = parse_url( $datum, PHP_URL_HOST );
				$test_lead_source = str_replace( 'www.', '', $test_lead_source );
				foreach ( $search_engines as $engine_name => $atts ){
					$urls = $atts[0]['urls'];
					if ( $this->in_urls( $test_lead_source, $urls ) ) {
						$return[ $engine_name ] = $num_contacts;
					}
				}
			}
		}


		$data  = $this->normalize_data($return );

		$total = array_sum( wp_list_pluck( $data, 'data' ) );

		foreach ( $data as $i => $datum )
		{

			$sub_tal = $datum[ 'data' ];
			$percentage = ' (' . percentage( $total, $sub_tal ) . '%)';

			$datum[ 'data' ] = html()->wrap( $datum[ 'data' ] . $percentage, 'a', [ 'href' => $datum[ 'url' ], 'class' => 'number-total' ] );
			unset( $datum['url'] );
			$data[ $i ] = $datum;
		}

		return $data;


	}


	/**
	 * Normalize a datum
	 *
	 * @param $item_key
	 * @param $item_data
	 * @return array
	 */
	protected function normalize_datum($item_key, $item_data)
	{
		return [
			'label' => Plugin::$instance->utils->html->wrap( $item_key, 'a', [ 'href' => $item_key, 'target' => '_blank' ] ),
			'data' => $item_data,
			'url'  => admin_url( 'admin.php?page=gh_contacts&meta_value=lead_source&meta_value=' . urlencode( $item_key ) )
		];
	}


	/**
	 * Setup the search_engines array from the yaml file in lib
	 */
	public function get_search_engines()
	{
		if ( ! class_exists( 'Spyc' ) ){
			include_once GROUNDHOGG_PATH . 'includes/lib/yaml/Spyc.php';
		}

		Return \Spyc::YAMLLoad( GROUNDHOGG_PATH . 'includes/lib/potential-known-leadsources/SearchEngines.yml' );
	}



	/**
	 * Special search function for comparing lead sources to potential search engine matches.
	 *
	 * @param $search string the URL in question
	 * @param $urls array list of string potential matches...
	 *
	 * @return bool
	 */
	private function in_urls( $search, $urls )
	{

		foreach ( $urls as $url ){

			/* Given YAML dataset uses .{} as sequence for match all expression, convert into regex friendly */
			$url = str_replace( '.{}', '\.{1,3}', $url );
			$url = str_replace( '{}.', '.{1,}?\.?', $url );
			$pattern = '#' . $url . '#';
//            var_dump( $pattern );
			if ( preg_match( $pattern, $search ) ){
				return true;
			}

		}

		return false;
	}


}