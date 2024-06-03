<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\Broadcast;
use Groundhogg\Reporting\New_Reports\Traits\Broadcast_Stats;
use function Groundhogg\_nf;
use function Groundhogg\admin_page_url;
use function Groundhogg\contact_filters_link;
use function Groundhogg\convert_to_local_time;
use function Groundhogg\format_number_with_percentage;
use function Groundhogg\get_date_time_format;
use function Groundhogg\get_db;
use function Groundhogg\html;
use function Groundhogg\percentage;

class Table_Broadcast_Stats extends Base_Table_Report {

	use Broadcast_Stats;

	/**
	 * @return mixed
	 */
	protected function get_broadcast_id() {
		$id = parent::get_broadcast_id();

		if ( ! $id ) {

			$broadcasts = get_db( 'broadcasts' )->query( [
				'status'  => 'sent',
				'orderby' => 'send_time',
				'order'   => 'desc',
				'limit'   => 1
			] );

			if ( ! empty( $broadcasts ) ) {
				$id = absint( array_shift( $broadcasts )->ID );
			}
		}

		return $id;
	}

	protected function get_table_data() {

		$broadcast = new Broadcast( $this->get_broadcast_id() );

		if ( ! $broadcast->exists() ) {
			return [];
		}

		[
			'sent'         => $sent,
			'opened'       => $opened,
			'clicked'      => $clicked,
			'unsubscribed' => $unsubscribed

		] = $this->get_broadcast_stats();

		$title  = $broadcast->is_email() ? $broadcast->get_object()->get_subject_line() : $broadcast->get_title();
		$object = $broadcast->get_object();

		return [
			[
				'label' => __( 'Subject', 'groundhogg' ),
				'data'  => $broadcast->is_email() ? html()->wrap( $title, 'a', [
					'href'  => admin_page_url( 'gh_emails', [
						'action' => 'edit',
						'email'  => $object->get_id()
					] ),
					'title' => $title,
				] ) : $title
			],
			[
				'label' => __( 'Date', 'groundhogg' ),
				'data'  => date_i18n( get_date_time_format(), convert_to_local_time( $broadcast->get_send_time() ) ),
			],
			[
				'label' => __( 'Sent', 'groundhogg' ),
				'data'  => contact_filters_link( _nf( $sent ), [
					[
						[
							'type'         => 'broadcast_received',
							'broadcast_id' => $broadcast->ID,
						]
					]
				], $sent )
			],
			$broadcast->is_sms() ? false : [
				'label' => __( 'Opens', 'groundhogg' ),
				'data'  => contact_filters_link( format_number_with_percentage( $opened, $sent ), [
					[
						[
							'type'          => 'broadcast_opened',
							'broadcast_id'  => $broadcast->ID,
							'count'         => 1,
							'count_compare' => 'greater_than_or_equal_to',
						]
					]
				], $opened )
			],
			[
				'label' => __( 'Clicks', 'groundhogg' ),
				'data'  => contact_filters_link( format_number_with_percentage( $clicked, $broadcast->is_email() ? $opened : $sent ), [
					[
						[
							'type'          => 'broadcast_link_clicked',
							'broadcast_id'  => $broadcast->ID,
							'count'         => 1,
							'count_compare' => 'greater_than_or_equal_to',
						]
					]
				], $clicked )
			],
			$broadcast->is_sms() ? false : [
				'label' => __( 'Unopened', 'groundhogg' ),
				'data'  => _nf( $sent - $opened ) . ' (' . percentage( $sent, $sent - $opened ) . '%)'
			],
			[
				'label' => __( 'Unsubscribed', 'groundhogg' ),
				'data'  => contact_filters_link( format_number_with_percentage( $unsubscribed, $sent ), [
					[
						[
							'type'      => 'unsubscribed',
							'funnel_id' => Broadcast::FUNNEL_ID,
							'step_id'   => $broadcast->ID,
						]
					]
				], $unsubscribed ),
			],

		];

	}

	protected function normalize_datum( $item_key, $item_data ) {
		// TODO: Implement normalize_datum() method.
	}

	function get_label() {
		return [];
	}
}
