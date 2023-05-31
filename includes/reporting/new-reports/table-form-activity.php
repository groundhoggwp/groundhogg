<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\Classes\Activity;
use Groundhogg\Email;
use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Step;
use function Groundhogg\_nf;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_db;
use function Groundhogg\get_form_list;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use function Groundhogg\percentage;

class Table_Form_Activity extends Base_Table_Report {


	public function get_label() {
		return [
			__( 'Name', 'groundhogg' ),
			__( 'Unique Impressions', 'groundhogg' ),
			__( 'Total Impressions', 'groundhogg' ),
			__( 'Submissions', 'groundhogg' ),
			__( 'Conversion Rate', 'groundhogg' ),
		];
	}

	protected function get_table_data() {

		$forms = get_form_list();

		$data = [];

		foreach ( $forms as $form_id => $form_name ) {

			$form_step = new Step( $form_id );

			if ( $this->get_funnel_id() && $this->get_funnel_id() !== $form_step->get_funnel_id() ) {
				continue;
			}

			$form_stats = [
				'name' => html()->e( 'a', [
					'href' => admin_page_url( 'gh_funnels', [
						'action' => 'edit',
						'funnel' => $form_step->get_funnel_id()
					], $form_id )
				], $form_name ),
			];

			$unique_impressions = get_db( 'form_impressions' )->count( [
				'form_id' => $form_id,
				'before'  => $this->end,
				'after'   => $this->start
			] );

			$form_stats['unique_impressions'] = _nf( $unique_impressions );

			$total_impressions = get_db( 'form_impressions' )->query( [
				'select'  => 'views',
				'func'    => 'sum',
				'form_id' => $form_id,
				'before'  => $this->end,
				'after'   => $this->start
			] );

			$form_stats['total_impressions'] = _nf( absint( $total_impressions ) );

			$submissions = absint( get_db( 'events' )->count( [
				'funnel_id'  => $form_step->get_funnel_id(),
				'step_id'    => $form_id,
				'event_type' => Event::FUNNEL,
				'status'     => Event::COMPLETE,
				'before'     => $this->end,
				'after'      => $this->start
			] ) );

			$form_stats['submissions'] = $submissions > 0 ? html()->e( 'a', [
				'href' => admin_page_url( 'gh_contacts', [
					'report' => [
						'funnel_id'  => $form_step->get_funnel_id(),
						'step_id'    => $form_id,
						'event_type' => Event::FUNNEL,
						'status'     => Event::COMPLETE,
						'before'     => $this->end,
						'after'      => $this->start,
					]
				] ),
			], _nf( $submissions ) ?: '0', false ) : 0;

			$conversion_rate = percentage( $unique_impressions, $submissions, 2 );

			$form_stats['conversion_rate'] = $conversion_rate . '%';

			$data[] = $form_stats;

		}

		usort( $data, [ $this, 'sort' ] );

		return $data;

	}

	/**
	 * @param $a
	 * @param $b
	 *
	 * @return mixed
	 */
	public function sort( $a, $b ) {
		return absint( $b['conversion_rate'] ) - absint( $a['conversion_rate'] );
	}

	protected function normalize_datum( $item_key, $item_data ) {
		// TODO: Implement normalize_datum() method.
	}

	protected function no_data_notice() {
		return html()->e( 'div', [ 'class' => 'notice notice-warning' ], [
			html()->e( 'p', [], __( 'You have no active forms.', 'groundhogg' ) )
		] );
	}
}
