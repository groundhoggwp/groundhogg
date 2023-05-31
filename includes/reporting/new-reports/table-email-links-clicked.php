<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Classes\Activity;
use Groundhogg\Email;
use Groundhogg\Plugin;
use function Groundhogg\_nf;
use function Groundhogg\admin_page_url;
use function Groundhogg\generate_referer_hash;
use function Groundhogg\get_db;
use function Groundhogg\html;
use function Groundhogg\managed_page_url;
use function Groundhogg\remove_query_string_from_url;

class Table_Email_Links_Clicked extends Base_Table_Report {

	/**
	 * @return array|mixed
	 */
	public function get_label() {
		return [
			__( 'Link', 'groundhogg' ),
			__( 'Uniques', 'groundhogg' ),
			__( 'Clicks', 'groundhogg' ),
		];
	}

	protected function get_activities(){
		$email = new Email( $this->get_email_id() );

		return get_db( 'activity' )->query( [
			'email_id'      => $email->get_id(),
			'activity_type' => Activity::EMAIL_CLICKED,
			'before'        => $this->end,
			'after'         => $this->start,
		] );
	}

	protected function get_contact_query_link( $link ){
		return admin_page_url( 'gh_contacts', [
			'activity' => [
				'activity_type' => Activity::EMAIL_CLICKED,
				'email_id'      => $this->get_email_id(),
				'referer'       => $link['referer'],
				'before'        => $this->end,
				'after'         => $this->start
			]
		] );
	}

	protected function get_table_data() {

		$activity = $this->get_activities();

		$links = [];

		foreach ( $activity as $event ) {

			// Links with permissions keys
			if ( strpos( $event->referer, '?pk=' ) !== false ) {
				$event->referer      = remove_query_string_from_url( $event->referer );
				$event->referer_hash = generate_referer_hash( $event->referer );
			}

			if ( ! isset( $links[ $event->referer_hash ] ) ) {
				$links[ $event->referer_hash ] = [
					'referer'  => $event->referer,
					'hash'     => $event->referer_hash,
					'contacts' => [],
					'uniques'  => 0,
					'clicks'   => 0,
				];
			}

			$links[ $event->referer_hash ]['clicks'] ++;
			$links[ $event->referer_hash ]['contacts'][] = $event->contact_id;
			$links[ $event->referer_hash ]['uniques']    = count( array_unique( $links[ $event->referer_hash ]['contacts'] ) );
		}

		if ( empty( $links ) ) {
			return [];
		}

		$data = [];

		foreach ( $links as $hash => $link ) {
			$data[] = [
				'label'   => html()->wrap( $link['referer'], 'a', [
					'href'   => $link['referer'],
					'class'  => 'number-total',
					'title'  => $link['referer'],
					'target' => '_blank',
				] ),
				'uniques' => html()->wrap( _nf( $link['uniques'] ), 'a', [
					'href'  => $this->get_contact_query_link( $link ),
					'class' => 'number-total'
				] ),
				'clicks'  => html()->wrap( _nf( $link['clicks'] ), 'span', [ 'class' => 'number-total' ] ),
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
		return $item_data;
	}


}