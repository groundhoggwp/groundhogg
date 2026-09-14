<?php

namespace Groundhogg\Abilities\Reports;

use Groundhogg\Abilities\Ability;
use Groundhogg\Reports;

/**
 * Lists the report IDs available to groundhogg/get-reports: every built-in
 * report (\Groundhogg\Reports::get_registered_reports()) and every custom
 * report configured under Reports > Custom (the gh_custom_reports option).
 *
 * This only lists what's available - it doesn't compute anything, so it's cheap
 * to call before deciding which report IDs to actually pull data for.
 */
class List_Report_Types extends Ability {

	protected const NAME       = 'groundhogg/list-report-types';
	protected const CATEGORY   = 'groundhogg-reports';
	protected const CAPABILITY = 'view_reports';

	protected const READONLY   = true;
	protected const IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'List Report Types', 'groundhogg' ),
			'description' => __( 'List the report IDs available to groundhogg/get-reports: built-in report ids, and the site\'s configured custom reports. Naming convention for built-in ids: "total_*" / "num_*" / "*_rate" return a single number (often with a prior-period comparison), "chart_*" return chart series data, "table_*" return an array of rows.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'built_in' => [
						'type'        => 'array',
						'items'       => [ 'type' => 'string' ],
						'description' => __( 'Built-in report ids. Pass any of these in groundhogg/get-reports\' `reports` param.', 'groundhogg' ),
					],
					'custom_reports' => [
						'type'        => 'array',
						'description' => __( 'The site\'s custom reports (Reports > Custom in the admin), as stored - fields beyond id/name/type vary by report. Pass a report\'s id in groundhogg/get-reports\' `custom_reports` param.', 'groundhogg' ),
						'items'       => [
							'type'                 => 'object',
							'additionalProperties' => true,
							'properties'           => [
								'id'   => [ 'type' => 'string' ],
								'name' => [ 'type' => 'string' ],
								'type' => [
									'type'        => 'string',
									'description' => __( 'e.g. "number", "table", "pie_chart".', 'groundhogg' ),
								],
							],
						],
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		return [
			'built_in'       => Reports::get_registered_reports(),
			'custom_reports' => array_values( get_option( 'gh_custom_reports', [] ) ),
		];
	}
}
