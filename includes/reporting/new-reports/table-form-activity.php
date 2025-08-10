<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Event;
use Groundhogg\Step;
use function Groundhogg\_nf;
use function Groundhogg\admin_page_url;
use function Groundhogg\contact_filters_link;
use function Groundhogg\format_number_with_percentage;
use function Groundhogg\get_db;
use function Groundhogg\html;
use function Groundhogg\is_good_fair_or_poor;
use function Groundhogg\percentage;

class Table_Form_Activity extends Base_Table_Report {


	public function get_label() {
		return [
			esc_html__( 'Name', 'groundhogg' ),
			esc_html_x( 'Views', 'stats', 'groundhogg' ),
			esc_html_x( 'Impressions', 'stats', 'groundhogg' ),
			esc_html_x( 'Submissions', 'stats', 'groundhogg' ),
		];
	}

	protected $orderby = 3;
	protected $per_page = 20;

	protected function get_table_data() {

		$stepQuery = new Table_Query( 'steps' );
		$stepQuery
			->setSelect( '*' )
			->whereIn( 'step_type', [ 'form_fill', 'web_form' ] )
			->equals( 'step_status', 'active' );

		if ( $this->get_funnel_id() ) {
			$stepQuery->where( 'funnel_id', $this->get_funnel_id() );
		}

		$submissionQuery = new Table_Query( 'submissions' );
		$submissionQuery
			->setSelect( 'step_id', [ 'COUNT(ID)', 'submissions' ] )
			->setGroupby( 'step_id' )
			->where()
			->lessThanEqualTo( 'date_created', $this->endDate->ymdhis() )
			->greaterThanEqualTo( 'date_created', $this->startDate->ymdhis() );

		$submissionsJoin = $stepQuery->addJoin( 'LEFT', [ $submissionQuery, 'submissions' ] );
		$submissionsJoin->onColumn( 'step_id', 'ID' );

		$impressionQuery = new Table_Query( 'form_impressions' );
		$impressionQuery
			->setSelect( 'form_id', [ 'SUM(views)', 'total_views' ],  [ 'COUNT(ID)', 'impressions' ] )
			->setGroupby( 'form_id' )
			->where()
			->lessThanEqualTo( 'timestamp', $this->end )
			->greaterThanEqualTo( 'timestamp', $this->start );

		$impressionsJoin = $stepQuery->addJoin( 'LEFT', [ $impressionQuery, 'impressions' ] );
		$impressionsJoin->onColumn( 'form_id', 'ID' );

		$stepQuery->setOrderby( 'submissions.submissions' );

//		wp_send_json( "$stepQuery" );

		$formResults = $stepQuery->get_results();

		$data = [];

		foreach ( $formResults as $form_result ) {

			$form_step = new Step( $form_result->ID );

			$submissions = absint( $form_result->submissions );
			$impressions = absint( $form_result->impressions );
			$views       = absint( $form_result->total_views );

			$data[] = [

				'form' => html()->e('a', [
					'href' => admin_page_url( 'gh_funnels', [
						'action' => 'edit',
						'funnel' => $form_step->get_funnel_id()
					], $form_step->ID )
				], $form_step->get_title() ),

				'views' => _nf( $views ),
				'impressions' => _nf( $impressions ),
				'submissions' => contact_filters_link( format_number_with_percentage( $submissions, $impressions ), [
					[
						[
							'type'       => 'funnel_history',
							'funnel_id'  => $form_step->get_funnel_id(),
							'step_id'    => $form_step->get_id(),
							'date_range' => 'between',
							'before'     => $this->endDate->ymd(),
							'after'      => $this->startDate->ymd()
						]
					]
				], $submissions ),
				'orderby' => [
					$form_step->ID,
					$views,
					$impressions,
					$submissions
				],
				'cellClasses'    => [
					// One of Good/Fair/Poor
					'', // title
					'', // views
					'', // Impressions
					$views ? is_good_fair_or_poor( percentage( $impressions, $submissions ), 30, 20, 10, 5 ) : '', // unsubscribed
				]

			];

		}

		return $data;

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
