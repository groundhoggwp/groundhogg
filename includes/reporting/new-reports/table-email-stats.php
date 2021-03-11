<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\Broadcast;
use Groundhogg\Classes\Activity;
use Groundhogg\Email;
use Groundhogg\Event;
use Groundhogg\Plugin;
use Groundhogg\Step;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_array_var;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use function Groundhogg\key_to_words;
use function Groundhogg\percentage;

class Table_Email_Stats extends Base_Table_Report {

	protected function get_table_data() {

		$email = new Email( $this->get_email_id() );

		$title = $email->get_subject_line();
		$stats = $email->get_email_stats( $this->start, $this->end );

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
				'label' => __( 'Total Delivered', 'groundhogg' ),
				'data'  => html()->wrap( $stats['sent'], 'a', [
					'href'  => add_query_arg(
						[
							'report' => [
								'step'   => $stats['steps'],
								'type'   => Event::FUNNEL,
								'status' => Event::COMPLETE,
								'before' => $this->end,
								'after'  => $this->start
							]
						],
						admin_url( sprintf( 'admin.php?page=gh_contacts' ) )
					),
					'class' => 'number-total'
				] )
			],
			[
				'label' => __( 'Opens', 'groundhogg' ),
				'data'  => html()->wrap( $stats['opened'] . ' (' . percentage( $stats['sent'], $stats['opened'] ) . '%)', 'a', [
					'href'  => add_query_arg(
						[
							'activity' => [
								'activity_type' => Activity::EMAIL_OPENED,
								'email_id'      => $email->get_id(),
								'after'         => $this->start,
								'before'        => $this->end
							]
						],
						admin_url( sprintf( 'admin.php?page=gh_contacts' ) )
					),
					'class' => 'number-total'
				] )
			],
			[
				'label' => __( 'Total Clicks', 'groundhogg' ),
				'data'  => html()->wrap( $stats['all_clicks'], 'span', [
					'class' => 'number-total'
				] )
			],
			[
				'label' => __( 'Unique Clicks', 'groundhogg' ),
				'data'  => html()->wrap( $stats['clicked'] . ' (' . percentage( $stats['sent'], $stats['clicked'] ) . '%)', 'a', [
					'href'  => add_query_arg(
						[
							'activity' => [
								'activity_type' => Activity::EMAIL_CLICKED,
								'email_id'      => $email->get_id(),
								'after'         => $this->start,
								'before'        => $this->end
							]
						],
						admin_url( sprintf( 'admin.php?page=gh_contacts' ) )
					),
					'class' => 'number-total'
				] )
			],
			[
				'label' => __( 'Click Thru Rate', 'groundhogg' ),
				'data'  => percentage( $stats['opened'], $stats['clicked'] ) . '%'
			],
			[
				'label' => __( 'Unsubscribed', 'groundhogg' ),
				'data'  => html()->wrap( $stats['unsubscribed'] . ' (' . percentage( $stats['sent'], $stats['unsubscribed'] ) . '%)', 'a', [
					'href'  => add_query_arg(
						[
							'activity' => [
								'activity_type' => Activity::UNSUBSCRIBED,
								'email_id'      => $email->get_id(),
								'after'         => $this->start,
								'before'        => $this->end
							]
						],
						admin_url( sprintf( 'admin.php?page=gh_contacts' ) )
					),
					'class' => 'number-total'
				] )
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