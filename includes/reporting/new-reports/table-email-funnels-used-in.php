<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\Classes\Activity;
use Groundhogg\Contact_Query;
use Groundhogg\Email;
use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Step;
use function Groundhogg\_nf;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use function Groundhogg\percentage;

class Table_Email_Funnels_Used_In extends Base_Table_Report {

	public function get_label() {
		return [
			__( 'Flow', 'groundhogg' ),
			__( 'Step', 'groundhogg' ),
			__( 'Sent', 'groundhogg' ),
			__( 'Open Rate', 'groundhogg' ),
			__( 'Click Thru Rate', 'groundhogg' ),
		];

	}

	protected function get_table_data() {

		$email_id = $this->get_email_id();

		$steps = get_db( 'stepmeta' )->query( [
			'meta_key'   => 'email_id',
			'meta_value' => $email_id
		] );

		$step_ids = wp_parse_id_list( wp_list_pluck( $steps, 'step_id' ) );

		$data = [];

		foreach ( $step_ids as $step_id ) {

			$step = new Step( $step_id );

			if ( ! $step->exists() ) {
				continue;
			}

			$sent = get_db( 'events' )->count( [
				'step_id'   => $step->get_id(),
				'funnel_id' => $step->get_funnel_id(),
				'status'    => Event::COMPLETE,
				'before'    => $this->end,
				'after'     => $this->start
			] );

			$opened = get_db( 'activity' )->count( [
				'select'        => 'DISTINCT contact_id',
				'activity_type' => Activity::EMAIL_OPENED,
				'funnel_id'     => $step->get_funnel_id(),
				'step_id'       => $step->get_id(),
				'email_id'      => $email_id,
				'before'        => $this->end,
				'after'         => $this->start
			] );

			$clicked = get_db( 'activity' )->count( [
				'select'        => 'DISTINCT contact_id',
				'activity_type' => Activity::EMAIL_CLICKED,
				'funnel_id'     => $step->get_funnel_id(),
				'step_id'       => $step->get_id(),
				'email_id'      => $email_id,
				'before'        => $this->end,
				'after'         => $this->start
			] );

			$data[] = [
				// Funnel
				html()->e( 'a', [
					'href' => admin_page_url( 'gh_funnels', [
						'action' => 'edit',
						'funnel' => $step->get_funnel_id(),
					] ),
				], $step->get_funnel_title() ),
				// Step
				html()->e( 'a', [
					'href' => admin_page_url( 'gh_funnels', [
						'action' => 'edit',
						'funnel' => $step->get_funnel_id(),
					] ),
				], $step->get_step_title() ),
				// Sent
				html()->e( 'a', [
					'href' => admin_page_url( 'gh_contacts', [
						'report' => [
							'step_id'   => $step->get_id(),
							'funnel_id' => $step->get_funnel_id(),
							'status'    => Event::COMPLETE,
							'before'    => $this->end,
							'after'     => $this->start
						]
					] ),
				], _nf( $sent ) ),
				// Opens
				html()->e( 'a', [
					'href' => admin_page_url( 'gh_contacts', [
						'activity' => [
							'activity_type' => Activity::EMAIL_OPENED,
							'step_id'       => $step->get_id(),
							'funnel_id'     => $step->get_funnel_id(),
							'before'        => $this->end,
							'after'         => $this->start
						]
					] ),
				], percentage( $sent, $opened ) . '%' ),
				// Clicks
				html()->e( 'a', [
					'href' => admin_page_url( 'gh_contacts', [
						'activity' => [
							'activity_type' => Activity::EMAIL_CLICKED,
							'step_id'       => $step->get_id(),
							'funnel_id'     => $step->get_funnel_id(),
							'before'        => $this->end,
							'after'         => $this->start
						]
					] ),
				], percentage( $opened, $clicked ) . '%' )

			];

		}

		return $data;

	}

	protected function normalize_datum( $item_key, $item_data ) {
		// TODO: Implement normalize_datum() method.
	}
}
