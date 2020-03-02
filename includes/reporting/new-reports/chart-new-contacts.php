<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Contact_Query;
use Groundhogg\Plugin;
use function Groundhogg\get_request_var;
use function Groundhogg\isset_not_empty;

class Chart_New_Contacts extends Base_Time_Chart_Report {

	protected function get_datasets() {

		$new      = $this->get_new_contacts();
		$previous = $this->get_previous_new_contact();

		$n = [];
		$p = [];


		for ( $i = 0; $i < count( $new ); $i ++ ) {

			$n[] = [
				't'     => $new[ $i ][ 't' ],
				'label' => sprintf( " %s (%s): %s", __( 'Contacts', 'groundhogg' ), date( get_option( 'date_format' ) . " " . get_option( 'time_format' ), strtotime( $new[ $i ][ 't' ] ) ), $new[ $i ][ 'y' ] ),
				'y'     => $new[ $i ][ 'y' ]
			];

			$p[] = [
				't'     => $new[ $i ][ 't' ],
				'label' => sprintf( " %s (%s): %s", __( 'Contacts', 'groundhogg' ), date( get_option( 'date_format' ) . " " . get_option( 'time_format' ), strtotime( $previous[ $i ][ 't' ] ) ), $previous[ $i ][ 'y' ] ),
				'y'     => $previous[ $i ][ 'y' ],
			];

		}

		return [

			'datasets' => [
				array_merge( [
					'label' => __( sprintf( "This Period( %s - %s)", date( get_option( 'date_format' ), $this->start ), date( get_option( 'date_format' ), $this->end ) ), 'groundhogg' ),
					'data'  => $n,
				], $this->get_line_style() ),
				array_merge( [
					'label' => __( sprintf( "Previous Period( %s - %s)", date( get_option( 'date_format' ), $this->compare_start ), date( get_option( 'date_format' ), $this->compare_end ) ), 'groundhogg' ),
					'data'  => $p,
				], $this->get_line_style() )
			]
		];

//		return [
//			'datasets' => [
//				$this->get_new_contacts(),
//				$this->get_previous_new_contact()
//			]
//		];

	}


	/**
	 * @param $datum
	 *
	 * @return int
	 */
	public function get_time_from_datum( $datum ) {
		return strtotime( $datum->date_created );
	}


	public function get_new_contacts() {
		$query = new Contact_Query();

		$data = $query->query( [
			'date_query' => [
				'after'  => date( 'Y-m-d H:i:s', $this->start ),
				'before' => date( 'Y-m-d H:i:s', $this->end ),
			]
		] );

		$result = $this->group_by_time( $data );

		$values = [];
		foreach ( $result as $d ) {
			$values[] = [
				't' => $d[ 2 ],
				'y' => $d[ 1 ]
			];
		}

		return $values;
//
//		return [
//			'label'           => __( 'New Contacts', 'groundhogg' ),
//			"borderColor"     => $this->get_random_color(),
//			'data'            => $values,
//			"fill"            =>false
//		];
	}


	public function get_previous_new_contact() {


		$query = new Contact_Query();

		$data = $query->query( [
			'date_query' => [
				'after'  => date( 'Y-m-d H:i:s', $this->compare_start ),
				'before' => date( 'Y-m-d H:i:s', $this->compare_end ),
			]
		] );

		$result = $this->group_by_time( $data, true );

		$values = [];
		foreach ( $result as $d ) {
			$values[] = [
				't' => $d[ 2 ],
				'y' => $d[ 1 ]
			];
		}

		return $values;

//
//		return [
//			'label'           => __( 'Previous Contacts', 'groundhogg' ),
//			"borderColor"     => $this->get_random_color(),
//			'data'            => $values,
//			"fill"            =>false
//		];


	}


	protected function get_options() {

		return [
			'responsive' => true,
			'tooltips'   => [
				'callbacks'       => [
					'label' => 'tool_tip_label',
					'title' =>  'tool_tip_title',
				],
				'mode'            => 'index',
				'intersect'       => false,
				'backgroundColor' => '#FFF',
				'bodyFontColor'   => '#000',
				'borderColor'     => '#727272',
				'borderWidth'     => 2,

			],
			'scales'     => [
				'xAxes' => [
					0 => [
						'type'       => 'time',
						'time'       => [
							'parser'        => "YYY-MM-DD HH:mm:ss",
							'tooltipFormat' => "l HH:mm"
						],
						'scaleLabel' => [
							'display'     => true,
							'labelString' => 'Date',
						]
					]
				],
				'yAxes' => [
					0 => [
						'scaleLabel' => [
							'display'     => true,
							'labelString' => 'Numbers',
						],
					],

				]
			]
		];
	}
}
