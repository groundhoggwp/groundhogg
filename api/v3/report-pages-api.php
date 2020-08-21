<?php

namespace Groundhogg\Api\V3;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Report_Pages_Api extends Base {

	public function register_routes() {
		register_rest_route( self::NAME_SPACE, '/pages', [
			[
				'methods'  => WP_REST_Server::READABLE,
				'callback' => [ $this, 'get_pages' ]
			],
			[
				'methods'  => WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'get_page_layout' ]
			]
		] );

	}

	/**
	 * Get list of pages to display
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function get_pages( WP_REST_Request $request ) {

		$pages = [
			"pages" => [
				"overview"   => "Overview",
				"contacts"   => "Courses",
				"email"      => "Email",
				"broadcasts" => "Broadcasts",
				"forms"      => 'Forms'

			]
		];

		return self::SUCCESS_RESPONSE( $pages );
	}

	/**
	 * Get list of pages to display
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function get_page_layout( WP_REST_Request $request ) {

		$page = $request->get_param( 'page' );

		$page_array = [
			"overview" => [
				"page"    => $page,
				"reports" => [
					"rows" => [
						[
							[
								'id'   => 'chart_new_contacts',
								'type' => 'line-chart',
								'lg'   => 12,
								'md'   => 12,
								'sm'   => 12
							],
						],
						[
							[
								'id'   => 'line-chart',
								'type' => 'line-chart',
								'lg'   => 12,
								'md'   => 12,
								'sm'   => 12
							],
						],
						[
							[
								"id "  => 'state_new_enrollments',
								"type" => 'stats',
								"lg"   => 3,
								"md"   => 6,
								"sm"   => 12,
								"xs"   => 12
							],

							[
								"id "  => 'state_new_users',
								"type" => 'stats',
								"lg"   => 3,
								"md"   => 6,
								"sm"   => 12,
								"xs"   => 12
							],
							[
								"id "  => 'state_course_completed',
								"type" => 'stats',
								"lg"   => 3,
								"md"   => 6,
								"sm"   => 12,
								"xs"   => 12
							],
							[
								"id "  => 'state_course_completion_rate',
								"type" => 'stats',
								"lg"   => 3,
								"md"   => 6,
								"sm"   => 12,
								"xs"   => 12
							],


						],
						[
							[
								'id'   => 'table_most_popular_courses',
								'type' => 'table',
								'lg'   => 12,
								'md'   => 12,
								'sm'   => 12
							]
						],

					]
				]
			],
			"contacts",
			"email",
			"broadcasts",
			"forms",

		];


		switch ( $page ) {
			case 'overview':
				$data = [
					"page"    => $page,
					"reports" => [
						"rows" => [
							[
								[
									'id'   => 'chart_new_contacts',
									'type' => 'line-chart',
									'lg'   => 12,
									'md'   => 12,
									'sm'   => 12
								],
							],
							[
								[
									'id'   => 'total_new_contacts',
									'type' => 'state',
									'lg'   => 3,
									'md'   => 6,
									'sm'   => 12
								],

								[
									'id'   => 'total_confirmed_contacts',
									'type' => 'state',
									'lg'   => 3,
									'md'   => 6,
									'sm'   => 12
								],[
									'id'   => 'total_engaged_contacts',
									'type' => 'state',
									'lg'   => 3,
									'md'   => 6,
									'sm'   => 12
								],[
									'id'   => 'total_unsubscribed_contacts',
									'type' => 'state',
									'lg'   => 3,
									'md'   => 6,
									'sm'   => 12
								],
							],
							[
								[
									'id'   => 'total_emails_sent',
									'type' => 'state',
									'lg'   => 4,
									'md'   => 6,
									'sm'   => 12
								],[
									'id'   => 'email_open_rate',
									'type' => 'state',
									'lg'   => 4,
									'md'   => 6,
									'sm'   => 12
								],[
									'id'   => 'email_click_rate',
									'type' => 'state',
									'lg'   => 4,
									'md'   => 6,
									'sm'   => 12
								],
							],
							[
								[
									'id'   => 'chart_contacts_by_optin_status',
									'type' => 'pie',
									'lg'   => 6,
									'md'   => 12,
									'sm'   => 12
								],
								[
									'id'   => '',
									'type' => 'pie',
									'lg'   => 6,
									'md'   => 12,
									'sm'   => 12
								]
							],

							[
								[
									'id'   => 'table_top_converting_funnels',
									'type' => 'table',
									'lg'   => 6,
									'md'   => 12,
									'sm'   => 12
								],
								[
									'id'   => 'table_top_performing_emails',
									'type' => 'table',
									'lg'   => 6,
									'md'   => 12,
									'sm'   => 12
								]
							],

							[
								[
									'id'   => 'table_contacts_by_countries',
									'type' => 'table',
									'lg'   => 6,
									'md'   => 12,
									'sm'   => 12
								],
								[
									'id'   => 'table_contacts_by_lead_source',
									'type' => 'table',
									'lg'   => 6,
									'md'   => 12,
									'sm'   => 12
								]
							],
						]
					]
				];
				break;
			case 'courses' :
				$data = [
					"page"    => $page,
					"reports" => [
						"rows" => [

							[
								[
									'id'   => 'line',
									'type' => 'line-chart',
									'lg'   => 12,
									'md'   => 12,
									'sm'   => 12
								],
								[
									'id'   => 'bar',
									'type' => 'bar-chart',
									'lg'   => 6,
									'md'   => 12,
									'sm'   => 12

								],

							],
							[
								[
									'id'   => 'table',
									'type' => 'table',
									'lg'   => 6,
									'md'   => 12,
									'sm'   => 12
								],

							],
							[
								[
									"id "  => 'stats1',
									"type" => 'stats',
									"lg"   => 3,
									"md"   => 6,
									"sm"   => 12,
									"xs"   => 12
								],

								[
									"id "  => 'stats2',
									"type" => 'stats2',
									"lg"   => 3,
									"md"   => 6,
									"sm"   => 12,
									"xs"   => 12
								],
								[
									"id "  => 'stats3',
									"type" => 'stats3',
									"lg"   => 3,
									"md"   => 6,
									"sm"   => 12,
									"xs"   => 12
								],


							],
						]
					]
				];
				break;
			case 'test-page' :
				$data = [
					"page"    => $page,
					"reports" => [
						"rows" => [

							[
								[
									'id'   => 'line',
									'type' => 'line-chart',
									'lg'   => 6,
									'md'   => 12,
									'sm'   => 12
								],
								[
									'id'   => 'bar',
									'type' => 'bar-chart',
									'lg'   => 6,
									'md'   => 12,
									'sm'   => 12

								],

							],
							[
								[
									'id'   => 'table',
									'type' => 'table',
									'lg'   => 6,
									'md'   => 12,
									'sm'   => 12
								],

								[
									"id "  => 'stats1',
									"type" => 'stats',
									"lg"   => 6,
									"md"   => 12,
									"sm"   => 12,
									"xs"   => 12
								],

								[
									"id "  => 'stats2',
									"type" => 'stats2',
									"lg"   => 3,
									"md"   => 12,
									"sm"   => 12,
									"xs"   => 12
								],
								[
									"id "  => 'stats3',
									"type" => 'stats3',
									"lg"   => 3,
									"md"   => 12,
									"sm"   => 12,
									"xs"   => 12
								],


							],
							[


							],
						]
					]
				];
				break;

		}

		return self::SUCCESS_RESPONSE( $data );
	}

}