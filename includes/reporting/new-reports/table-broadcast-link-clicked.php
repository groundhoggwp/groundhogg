<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\Broadcast;
use Groundhogg\Classes\Activity;
use Groundhogg\Plugin;
use function Groundhogg\generate_referer_hash;
use function Groundhogg\get_array_var;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use function Groundhogg\percentage;

class Table_Broadcast_Link_Clicked extends Base_Table_Report {


	public function get_label() {
		return [
			__( 'Link', 'groundhogg' ),
			__( 'Uniques', 'groundhogg' ),
			__( 'Clicks', 'groundhogg' ),
		];
	}

	protected function get_broadcast_id() {
//		return get_array_var( get_request_var( 'data', [] ), 'broadcast_id' );
		if ( get_array_var( $this->request_data, 'ddl_broadcasts' ) ) {
			$id = absint( get_array_var( $this->request_data, 'ddl_broadcasts' ) );
		}

		return $id;
	}

	protected function get_table_data() {

		$broadcast = new Broadcast( $this->get_broadcast_id() );

		$activity = get_db( 'activity' )->query( [
			'funnel_id'     => $broadcast->get_funnel_id(),
			'step_id'       => $broadcast->get_id(),
			'activity_type' => Activity::EMAIL_CLICKED,
		] );

		$links = [];

		foreach ( $activity as $event ) {

			if ( ! isset( $links[ $event->referer_hash ] ) ) {
				$links[ $event->referer_hash ] = [
					'referer'  => $event->referer,
					'hash'     => $event->referer_hash,
					'contacts' => [],
					'uniques'  => 0,
					'clicks'   => 0,
				];
			}

			$links[ $event->referer_hash ][ 'clicks' ] ++;
			$links[ $event->referer_hash ][ 'contacts' ][] = $event->contact_id;
			$links[ $event->referer_hash ][ 'uniques' ]    = count( array_unique( $links[ $event->referer_hash ][ 'contacts' ] ) );
		}

		if ( empty( $links ) ) {
			return [];
		}

		foreach ( $links as $hash => $link ) {
			$data [] = [
				'label'   => $link[ 'referer' ],
				'uniques' => $link[ 'uniques' ],
				'clicks'  => $link[ 'clicks' ]
			];
		}

		return $data;


	}


	/**
	 * Normalize a datum
	 *
	 * @param $item_key
	 * @param $item_data
	 *
	 * @return array
	 */
	protected function normalize_datum( $item_key, $item_data ) {

		$label = ! empty( $item_key ) ? Plugin::$instance->utils->location->get_countries_list( $item_key ) : __( 'Unknown' );
		$data  = $item_data;
		$url   = ! empty( $item_key ) ? admin_url( sprintf( 'admin.php?page=gh_contacts&meta_key=country&meta_value=%s', $item_key ) ) : '#';


		return [
			'label' => $label,
			'data'  => $data,
			'url'   => $url
		];
	}


}