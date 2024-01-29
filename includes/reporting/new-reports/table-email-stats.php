<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\Broadcast;
use Groundhogg\Classes\Activity;
use Groundhogg\Email;
use Groundhogg\Step;
use function Groundhogg\_nf;
use function Groundhogg\admin_page_url;
use function Groundhogg\contact_filters_link;
use function Groundhogg\format_number_with_percentage;
use function Groundhogg\html;
use function Groundhogg\percentage;

class Table_Email_Stats extends Base_Table_Report {

	protected function get_table_data() {
		$step = new Step( $this->get_step_id() );
		$email = new Email( $this->get_email_id() );

		$title = $email->get_subject_line();
		$stats = $email->get_email_stats( $this->start, $this->end );

		[
			'sent'         => $sent,
			'clicked'      => $clicked,
			'opened'       => $opened,
			'unsubscribed' => $unsubscribed
		] = $stats;

		return [
			[
				'label' => __( 'Subject', 'groundhogg' ),
				'data'  => html()->wrap( $title, 'a', [
					'href'  => admin_page_url( 'gh_emails', [ 'action' => 'edit', 'email' => $email->get_id() ] ),
					'title' => $title,
					'class' => 'number-total'
				] )
			],
			[
				'label' => __( 'Sent', 'groundhogg' ),
				'data'  => contact_filters_link( _nf( $sent ), [
					[
						[
							'type'       => 'email_received',
							'email_id'   => $email->get_id(),
							'step_id'    => $step->get_id(),
							'funnel_id'  => $step->get_funnel_id(),
							'date_range' => 'between',
							'after'      => $this->startDate->ymd(),
							'before'     => $this->endDate->ymd(),
						]
					]
				], $sent )
			],
			[
				'label' => __( 'Opens', 'groundhogg' ),
				'data' => contact_filters_link( format_number_with_percentage( $opened, $sent ), [
					[
						[
							'type'       => 'email_opened',
							'email_id'   => $email->get_id(),
							'step_id'    => $step->get_id(),
							'funnel_id'  => $step->get_funnel_id(),
							'date_range' => 'between',
							'after'      => $this->startDate->ymd(),
							'before'     => $this->endDate->ymd(),
						]
					]
				], $opened )
			],
			[
				'label' => __( 'Clicks', 'groundhogg' ),
				'data'  => contact_filters_link( format_number_with_percentage( $clicked, $opened ), [
					[
						[
							'type'       => 'email_link_clicked',
							'email_id'   => $email->get_id(),
							'step_id'    => $step->get_id(),
							'funnel_id'  => $step->get_funnel_id(),
							'date_range' => 'between',
							'after'      => $this->startDate->ymd(),
							'before'     => $this->endDate->ymd(),
						]
					]
				], $clicked )
			],
			[
				'label' => __( 'Unsubscribed', 'groundhogg' ),
				'data'  => contact_filters_link( format_number_with_percentage( $unsubscribed, $sent ), [
					[
						[
							'type'       => 'unsubscribed',
							'email_id'   => $email->get_id(),
							'step_id'    => $step->get_id(),
							'funnel_id'  => $step->get_funnel_id(),
							'date_range' => 'between',
							'after'      => $this->startDate->ymd(),
							'before'     => $this->endDate->ymd(),
						]
					]
				], $unsubscribed )
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
