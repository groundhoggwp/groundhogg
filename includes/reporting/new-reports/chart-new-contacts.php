<?php

namespace Groundhogg\Reporting\New_Reports;

class Chart_New_Contacts extends Base_Time_Chart_Report {
	protected function get_datasets() {


		return array (
			'datasets' =>
				array (
					0 =>
						array (
							'label' => 'New Plugin Activations',
							'backgroundColor' => 'rgba(232, 116, 59 , 0.5 )',
							'borderColor' => 'rgba(232, 116, 59 , 1)',
							'data' =>
								array (
									0 =>
										array (
											't' => '2020-02-21 00:00:00',
											'y' => 3,
										),
									1 =>
										array (
											't' => '2020-02-22 00:00:00',
											'y' => 0,
										),
									2 =>
										array (
											't' => '2020-02-23 00:00:00',
											'y' => 0,
										),
									3 =>
										array (
											't' => '2020-02-24 00:00:00',
											'y' => 0,
										),
									4 =>
										array (
											't' => '2020-02-25 00:00:00',
											'y' => 0,
										),
									5 =>
										array (
											't' => '2020-02-26 00:00:00',
											'y' => 0,
										),
									6 =>
										array (
											't' => '2020-02-27 00:00:00',
											'y' => 0,
										),
								),

						),
				),
		);

		// TODO retrive data
	}
}
