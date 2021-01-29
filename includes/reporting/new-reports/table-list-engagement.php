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
use function Groundhogg\get_db;
use function Groundhogg\html;
use function Groundhogg\key_to_words;

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

		$engaged = get_db( 'activity' )->count( [
			'select'   => 'contact_id',
			'distinct' => true,
			'where'    => [
				'relationship' => 'AND',
				// Start
				[
					'col'     => 'timestamp',
					'val'     => $this->start,
					'compare' => '>='
				],
				// END
				[
					'col'     => 'timestamp',
					'val'     => $this->end,
					'compare' => '<='
				],
				[
					'col'     => 'activity_type',
					'val'     => 'email_opened',
					'compare' => '='
				]
			]
		] );

		$total_contacts = get_db( 'contacts' )->count();

		$rows = [];

		$engaged_query = [
			'activity' => [
				'activity_type' => Activity::EMAIL_OPENED,
				'after'         => $this->start,
				'before'        => $this->end
			]
		];

		$rows[] = [
			"label" =>__( 'Engaged' ),
			"data"  =>_nf( $engaged )
		];

		$unengaged_query = [
			'activity' => [
				'activity_type' => Activity::EMAIL_OPENED,
				'after'         => $this->start,
				'before'        => $this->end,
				'exclude'       => true,
			]
		];

		$rows[] = [
			"label" =>__( 'Unengaged' ),
			"data"  =>_nf( $total_contacts - $engaged )
		];

		$marketable_query = [
			'tags_include' => Plugin::instance()->tag_mapping->get_status_tag( Tag_Mapping::MARKETABLE ),
		];

		$marketable_contacts = get_db( 'contacts' )->count( $marketable_query );

		$rows[] = [
			"label" =>__( 'Marketable' ),
			"data"  =>_nf( $marketable_contacts )
		];

		$unmarketable_query = [
			'tags_include' => Plugin::instance()->tag_mapping->get_status_tag( Tag_Mapping::NON_MARKETABLE ),
		];

		$unmarketable_contacts = get_db( 'contacts' )->count( $unmarketable_query );

		$rows[] = [
			"label" => __( 'Non Marketable' ),
			"data"  => _nf( $unmarketable_contacts )
		];

		return $rows;
	}

	protected function normalize_datum( $item_key, $item_data ) {
		// TODO: Implement normalize_datum() method.
	}

}