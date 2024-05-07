<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\Classes\Activity;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Email;
use Groundhogg\Event;
use Groundhogg\Reporting\New_Reports\Traits\Funnel_Email_Stats;
use Groundhogg\Step;
use function Groundhogg\_nf;
use function Groundhogg\admin_page_url;
use function Groundhogg\array_find;
use function Groundhogg\contact_filters_link;
use function Groundhogg\format_number_with_percentage;
use function Groundhogg\get_array_var;
use function Groundhogg\html;

class Table_Email_Stats extends Base_Table_Report {

	use Funnel_Email_Stats;

	protected function get_table_data() {
		$step  = new Step( $this->get_step_id() );
		$email = new Email( $this->get_email_id() );

		$title = $email->get_subject_line();

		[
			'sent' => $sent,
			'opened' => $opened,
			'clicked' => $clicked,
		] = $this->get_funnel_email_stats();

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
							'type'          => 'email_received',
							'email_id'      => $email->get_id(),
							'step_id'       => $step->get_id(),
							'funnel_id'     => $step->get_funnel_id(),
							'date_range'    => 'between',
							'after'         => $this->startDate->ymd(),
							'before'        => $this->endDate->ymd(),
							'count'         => 1,
							'count_compare' => 'greater_than_or_equal_to',
						]
					]
				], $sent )
			],
			[
				'label' => __( 'Opens', 'groundhogg' ),
				'data'  => contact_filters_link( format_number_with_percentage( $opened, $sent ), [
					[
						[
							'type'          => 'email_opened',
							'email_id'      => $email->get_id(),
							'step_id'       => $step->get_id(),
							'funnel_id'     => $step->get_funnel_id(),
							'date_range'    => 'between',
							'after'         => $this->startDate->ymd(),
							'before'        => $this->endDate->ymd(),
							'count'         => 1,
							'count_compare' => 'greater_than_or_equal_to',
						]
					]
				], $opened )
			],
			[
				'label' => __( 'Clicks', 'groundhogg' ),
				'data'  => contact_filters_link( format_number_with_percentage( $clicked, $opened ), [
					[
						[
							'type'          => 'email_link_clicked',
							'email_id'      => $email->get_id(),
							'step_id'       => $step->get_id(),
							'funnel_id'     => $step->get_funnel_id(),
							'date_range'    => 'between',
							'after'         => $this->startDate->ymd(),
							'before'        => $this->endDate->ymd(),
							'count'         => 1,
							'count_compare' => 'greater_than_or_equal_to',
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
