<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Classes\Activity;
use Groundhogg\Contact_Query;
use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Preferences;
use Groundhogg\Tag_Mapping;
use function Groundhogg\_nf;
use function Groundhogg\admin_page_url;
use function Groundhogg\base64_json_encode;
use function Groundhogg\get_db;
use function Groundhogg\html;
use function Groundhogg\key_to_words;
use function Groundhogg\Ymd;
use function Groundhogg\Ymd_His;

class Table_List_Engagement extends Base_Table_Report {

	public function get_label() {
		return [
			__( 'Status', 'groundhogg' ),
			__( 'Contacts', 'groundhogg' ),
		];
	}

	/**
	 * @return array|mixed
	 */
	protected function get_table_data() {

		$query = new Contact_Query();

		$engaged_filters = [
			[
				[
					'type'       => 'was_active',
					'date_range' => 'between',
					'before'     => Ymd( $this->end ),
					'after'      => Ymd( $this->start ),
				]
			]
		];

		$engaged = $query->count( [
			'filters' => $engaged_filters
		] );

		$rows = [];

		$rows[] = [
			__( 'Engaged' ),
			html()->e( 'a', [
				'href' => admin_page_url( 'gh_contacts', [
					'filters' => base64_json_encode( $engaged_filters )
				] )
			], _nf( $engaged ), false )
		];

		$unengaged = $query->count( [
			'exclude_filters' => $engaged_filters
		] );

		$rows[] = [
			__( 'Unengaged' ),
			html()->e( 'a', [
				'href' => admin_page_url( 'gh_contacts', [
					'exclude_filters' => base64_json_encode( $engaged_filters )
				] )
			], _nf( $unengaged ), false )
		];

		$marketable_filters = [
			[
				[
					'type' => 'is_marketable',
					'marketable' => 'yes'
				]
			]
		];

		$marketable = $query->count( [
			'filters' => $marketable_filters

		] );

		$rows[] = [
			__( 'Marketable' ),
			html()->e( 'a', [
				'href' => admin_page_url( 'gh_contacts', [
					'filters' => base64_json_encode( $marketable_filters )
				] )
			], _nf( $marketable ), false )
		];

		$unmarketable_filters = [
			[
				[
					'type' => 'is_marketable',
					'marketable' => 'no'
				]
			]
		];

		$unmarketable = $query->count( [
			'filters' => $unmarketable_filters
		] );

		$rows[] = [
			__( 'Marketable' ),
			html()->e( 'a', [
				'href' => admin_page_url( 'gh_contacts', [
					'filters' => base64_json_encode( $unmarketable_filters )
				] )
			], _nf( $unmarketable ), false )
		];

		return $rows;
	}

	protected function normalize_datum( $item_key, $item_data ) {
		// TODO: Implement normalize_datum() method.
	}

}
