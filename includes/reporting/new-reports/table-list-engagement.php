<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Contact_Query;
use function Groundhogg\_nf;
use function Groundhogg\admin_page_url;
use function Groundhogg\base64_json_encode;
use function Groundhogg\html;
use function Groundhogg\Ymd;

class Table_List_Engagement extends Base_Table_Report {

	public function get_label() {
		return [
			esc_html__( 'Status', 'groundhogg' ),
			esc_html__( 'Contacts', 'groundhogg' ),
		];
	}

	/**
	 * @return array|mixed
	 */
	protected function get_table_data() {

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

		$engaged = new Contact_Query( [
			'filters' => $engaged_filters
		] );

		$rows = [];

		$rows[] = [
			__( 'Engaged', 'groundhogg' ),
			html()->e( 'a', [
				'href' => admin_page_url( 'gh_contacts', [
					'filters' => base64_json_encode( $engaged_filters )
				] )
			], _nf( $engaged->count() ), false )
		];

		$unengaged = new Contact_Query( [
			'exclude_filters' => $engaged_filters
		] );

		$rows[] = [
			__( 'Unengaged', 'groundhogg' ),
			html()->e( 'a', [
				'href' => admin_page_url( 'gh_contacts', [
					'exclude_filters' => base64_json_encode( $engaged_filters )
				] )
			], _nf( $unengaged->count() ), false )
		];

		$marketable_filters = [
			[
				[
					'type' => 'is_marketable',
					'marketable' => 'yes'
				]
			]
		];

		$marketable = new Contact_Query( [
			'filters' => $marketable_filters
		] );

		$rows[] = [
			__( 'Marketable', 'groundhogg' ),
			html()->e( 'a', [
				'href' => admin_page_url( 'gh_contacts', [
					'filters' => base64_json_encode( $marketable_filters )
				] )
			], _nf( $marketable->count() ), false )
		];

		$unmarketable_filters = [
			[
				[
					'type' => 'is_marketable',
					'marketable' => 'no'
				]
			]
		];

		$unmarketable = new Contact_Query( [
			'filters' => $unmarketable_filters
		] );

		$rows[] = [
			__( 'Un-Marketable', 'groundhogg' ),
			html()->e( 'a', [
				'href' => admin_page_url( 'gh_contacts', [
					'filters' => base64_json_encode( $unmarketable_filters )
				] )
			], _nf( $unmarketable->count() ), false )
		];

		return $rows;
	}

	protected function normalize_datum( $item_key, $item_data ) {
		// TODO: Implement normalize_datum() method.
	}

}
