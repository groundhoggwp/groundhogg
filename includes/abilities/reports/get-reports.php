<?php

namespace Groundhogg\Abilities\Reports;

use Groundhogg\Abilities\Ability;
use Groundhogg\Api\V4\Reports_Api;
use Groundhogg\DB\Query\Filters;
use Groundhogg\Reports;
use Groundhogg\Utils\DateTimeHelper;
use Throwable;
use WP_Error;
use function Groundhogg\array_find;

/**
 * Pulls report data, mirroring GET gh/v4/reports and gh/v4/custom-reports.
 * See groundhogg/list-report-types to discover valid ids for both params below.
 *
 * - `reports`: built-in report ids (\Groundhogg\Reports), computed for the date
 *   window given by `range` (a named range, matching the admin's own date
 *   picker) or by `after`/`before` directly. Shape varies by report: "total_*" /
 *   "num_*" / "*_rate" return a single value (often with a prior-period
 *   comparison), "chart_*" return chart series data, "table_*" return an array
 *   of rows. Some reports also read `params` (e.g. funnel_id, email_id,
 *   step_id, broadcast_id) to scope to one object - see each report's own
 *   requirements; an unscoped report ignores params it doesn't use.
 *
 * - `custom_reports`: ids from the site's Reports > Custom builder (the
 *   gh_custom_reports option). These are NOT date-windowed by this call's
 *   range/after/before - a custom report's own stored filters are the only
 *   thing that bounds it. Computed via Reports_Api::get_report_data() (the same
 *   method the REST route uses) - reused directly rather than reimplemented,
 *   since its query-building is nontrivial and would drift if duplicated.
 *
 * An id in either list that isn't recognized is skipped and reported in
 * `errors`, rather than failing the whole call.
 */
class Get_Reports extends Ability {

	protected const NAME       = 'groundhogg/get-reports';
	protected const CATEGORY   = 'groundhogg-reports';
	protected const CAPABILITY = 'view_reports';

	protected const READONLY   = true;
	protected const IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'Get Reports', 'groundhogg' ),
			'description' => __( 'Pull data for one or more reports - built-in (see groundhogg/list-report-types\' built_in) and/or custom (its custom_reports). Built-in reports are computed over a date window (range, or after/before); custom reports use their own stored filters and ignore the date window.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [
					'reports' => [
						'type'        => 'array',
						'items'       => [ 'type' => 'string' ],
						'description' => __( 'Built-in report ids to compute - see groundhogg/list-report-types.', 'groundhogg' ),
					],
					'custom_reports' => [
						'type'        => 'array',
						'items'       => [ 'type' => 'string' ],
						'description' => __( 'Custom report ids to compute - see groundhogg/list-report-types. Not affected by range/after/before.', 'groundhogg' ),
					],
					'range' => [
						'type'        => 'string',
						'enum'        => [
							'any', 'today', 'yesterday', 'tomorrow',
							'this_week', 'last_week', 'next_week',
							'this_month', 'last_month', 'next_month',
							'this_quarter', 'last_quarter', 'next_quarter',
							'this_year', 'last_year', 'next_year',
							'24_hours', 'next_24_hours',
							'7_days', 'next_7_days',
							'14_days', 'next_14_days',
							'30_days', 'next_30_days',
							'60_days', 'next_60_days',
							'90_days', 'next_90_days',
							'365_days', 'next_365_days',
							'x_days', 'next_x_days',
							'before', 'after', 'day_of', 'between',
						],
						'description' => __( 'A named date window for built-in reports, matching the admin\'s date-range picker. "between"/"before"/"after"/"day_of" use the after/before params below; "x_days"/"next_x_days" use days. Omit to use after/before directly (defaults: 7 days ago through yesterday).', 'groundhogg' ),
					],
					'after' => [
						'type'        => 'string',
						'description' => __( 'Start of the date window (inclusive). Any strtotime()-compatible string. Used directly when range is omitted, or as the bound for range values that need it.', 'groundhogg' ),
					],
					'before' => [
						'type'        => 'string',
						'description' => __( 'End of the date window (inclusive). Same rules as after.', 'groundhogg' ),
					],
					'days' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'Day count for range "x_days" (N days ago through now) or "next_x_days" (now through N days from now).', 'groundhogg' ),
					],
					'params' => [
						'type'                 => 'object',
						'additionalProperties' => true,
						'description'          => __( 'Extra params some built-in reports read to scope themselves to one object, e.g. {"funnel_id": 12}, {"email_id": 34}, {"step_id": 56}, {"broadcast_id": 78}. Ignored by reports that don\'t use them.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'range' => [
						'type'        => [ 'object', 'null' ],
						'description' => __( 'The resolved date window applied to built-in reports (Y-m-d H:i:s, site timezone). Null if no `reports` were requested.', 'groundhogg' ),
						'properties'  => [
							'after'  => [ 'type' => 'string' ],
							'before' => [ 'type' => 'string' ],
						],
					],
					'reports' => [
						'type'                 => 'object',
						'additionalProperties' => true,
						'description'          => __( 'Built-in report id => result. Shape varies by report - see the id\'s "total_"/"chart_"/"table_" prefix.', 'groundhogg' ),
					],
					'custom_reports' => [
						'type'                 => 'object',
						'additionalProperties' => true,
						'description'          => __( 'Custom report id => the stored report config plus a "data" field holding the computed result.', 'groundhogg' ),
					],
					'errors' => [
						'type'        => 'array',
						'items'       => [ 'type' => 'string' ],
						'description' => __( 'One entry per requested id (in either list) that was not recognized, or that failed to compute.', 'groundhogg' ),
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$report_ids        = array_values( array_filter( array_map( 'sanitize_key', $input['reports'] ?? [] ) ) );
		$custom_report_ids = array_values( array_filter( array_map( 'sanitize_text_field', $input['custom_reports'] ?? [] ) ) );

		if ( empty( $report_ids ) && empty( $custom_report_ids ) ) {
			return new WP_Error( 'groundhogg_no_reports', __( 'Give at least one id in "reports" or "custom_reports".', 'groundhogg' ) );
		}

		$errors = [];

		$response = [
			'range'          => null,
			'reports'        => [],
			'custom_reports' => [],
		];

		// Read this BEFORE constructing the real, param-scoped Reports instance
		// below: get_registered_reports() internally does its own
		// `new Reports( time(), time() )` with no params to read the report list,
		// and Reports::$params is a *static* property shared by the whole class -
		// calling it after would silently wipe out the params (funnel_id, etc.)
		// this call actually wants scoped reports to see.
		$known_reports = Reports::get_registered_reports();

		// --- Built-in reports, date-windowed -----------------------------------
		if ( ! empty( $report_ids ) ) {

			if ( ! empty( $input['range'] ) ) {

				$dates = Filters::get_before_and_after_from_date_range( [
					'date_range' => $input['range'],
					'before'     => $input['before'] ?? '',
					'after'      => $input['after'] ?? '',
					'days'       => absint( $input['days'] ?? 0 ),
				] );

				$start = $dates['after'];
				$end   = $dates['before'];

			} else {
				$start = ( new DateTimeHelper( $input['after'] ?? '7 days ago' ) )->modify( '00:00:00' );
				$end   = ( new DateTimeHelper( $input['before'] ?? 'yesterday' ) )->modify( '23:59:59' );
			}

			$response['range'] = [
				'after'  => $start->ymdhis(),
				'before' => $end->ymdhis(),
			];

			// Cast rather than is_array()-check: the abilities API can hand an
			// object-typed param through as stdClass, not an array.
			$params = (array) ( $input['params'] ?? [] );

			$reporting = new Reports( $start->getTimestamp(), $end->getTimestamp(), $params );

			foreach ( $report_ids as $id ) {

				if ( ! in_array( $id, $known_reports, true ) ) {
					$errors[] = sprintf( 'Unknown report id: %s', $id );
					continue;
				}

				try {
					$response['reports'][ $id ] = $reporting->get_data( $id );
				} catch ( Throwable $e ) {
					$errors[] = sprintf( 'Report "%s" failed: %s', $id, $e->getMessage() );
				}
			}
		}

		// --- Custom reports, own stored filters, no date window ----------------
		if ( ! empty( $custom_report_ids ) ) {

			$custom_defs = get_option( 'gh_custom_reports', [] );

			// Reused directly rather than reimplemented: Reports_Api::get_report_data()
			// (public) builds the actual Contact_Query per report type/field - the
			// same computation the REST route uses. Instantiating the controller
			// outside of a request is safe: Base_Api::__construct() only hooks
			// register_routes() to an init action that has already fired by now.
			$reports_api = new Reports_Api();

			foreach ( $custom_report_ids as $id ) {

				$definition = array_find( $custom_defs, function ( $report ) use ( $id ) {
					return isset( $report['id'] ) && (string) $report['id'] === $id;
				} );

				if ( ! $definition ) {
					$errors[] = sprintf( 'Unknown custom report id: %s', $id );
					continue;
				}

				try {
					$definition['data'] = $reports_api->get_report_data( $definition );
				} catch ( Throwable $e ) {
					$errors[] = sprintf( 'Custom report "%s" failed: %s', $id, $e->getMessage() );
					continue;
				}

				$response['custom_reports'][ $id ] = $definition;
			}
		}

		$response['errors'] = $errors;

		return $response;
	}
}
