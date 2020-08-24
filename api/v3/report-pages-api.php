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
				"contacts"   => "Contacts",
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
								'id'   => 'total_new_contacts',
								'type' => 'stats',
								'lg'   => 3,
								'md'   => 6,
								'sm'   => 12
							],

							[
								'id'   => 'total_confirmed_contacts',
								'type' => 'stats',
								'lg'   => 3,
								'md'   => 6,
								'sm'   => 12
							],
							[
								'id'   => 'total_engaged_contacts',
								'type' => 'stats',
								'lg'   => 3,
								'md'   => 6,
								'sm'   => 12
							],
							[
								'id'   => 'total_unsubscribed_contacts',
								'type' => 'stats',
								'lg'   => 3,
								'md'   => 6,
								'sm'   => 12
							],
						],
						[
							[
								'id'   => 'total_emails_sent',
								'type' => 'stats',
								'lg'   => 4,
								'md'   => 6,
								'sm'   => 12
							],
							[
								'id'   => 'email_open_rate',
								'type' => 'stats',
								'lg'   => 4,
								'md'   => 6,
								'sm'   => 12
							],
							[
								'id'   => 'email_click_rate',
								'type' => 'stats',
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
			],
			"contacts" => [
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
								'type' => 'stats',
								'lg'   => 3,
								'md'   => 6,
								'sm'   => 12
							],

							[
								'id'   => 'total_confirmed_contacts',
								'type' => 'stats',
								'lg'   => 3,
								'md'   => 6,
								'sm'   => 12
							],
							[
								'id'   => 'total_engaged_contacts',
								'type' => 'stats',
								'lg'   => 3,
								'md'   => 6,
								'sm'   => 12
							],
							[
								'id'   => 'total_unsubscribed_contacts',
								'type' => 'stats',
								'lg'   => 3,
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
						],[
							[
								'id'   => 'chart_contacts_by_country',
								'type' => 'pie',
								'lg'   => 6,
								'md'   => 12,
								'sm'   => 12
							],
							[
								'id'   => 'chart_contacts_by_region',
								'type' => 'pie',
								'lg'   => 6,
								'md'   => 12,
								'sm'   => 12
							]
						],
						[
							[
								'id'   => 'table_contacts_by_search_engines',
								'type' => 'table',
								'lg'   => 6,
								'md'   => 12,
								'sm'   => 12
							],
							[
								'id'   => 'table_contacts_by_social_media',
								'type' => 'table',
								'lg'   => 6,
								'md'   => 12,
								'sm'   => 12
							]
						],[
							[
								'id'   => 'table_contacts_by_source_page',
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
						],[
							[
								'id'   => 'table_list_engagement',
								'type' => 'table',
								'lg'   => 12,
								'md'   => 12,
								'sm'   => 12
							],

						],

					]
				],
			],
			"email" =>[
				"page"    => $page,
				"reports" => [
					"rows" => [
						[
							[
								'id'   => 'chart_email_activity',
								'type' => 'line-chart',
								'lg'   => 12,
								'md'   => 12,
								'sm'   => 12
							],
						],
						[
							[
								'id'   => 'total_emails_sent',
								'type' => 'stats',
								'lg'   => 4,
								'md'   => 6,
								'sm'   => 12
							],

							[
								'id'   => 'email_open_rate',
								'type' => 'stats',
								'lg'   => 4,
								'md'   => 6,
								'sm'   => 12
							],
							[
								'id'   => 'email_click_rate',
								'type' => 'stats',
								'lg'   => 4,
								'md'   => 6,
								'sm'   => 12
							],

						],[
							[
								'id'   => 'total_unsubscribed_contacts',
								'type' => 'stats',
								'lg'   => 3,
								'md'   => 6,
								'sm'   => 12
							],

							[
								'id'   => 'total_spam_contacts',
								'type' => 'stats',
								'lg'   => 3,
								'md'   => 6,
								'sm'   => 12
							],
							[
								'id'   => 'total_bounces_contacts',
								'type' => 'stats',
								'lg'   => 3,
								'md'   => 6,
								'sm'   => 12
							],[
								'id'   => 'total_complaints_contacts',
								'type' => 'stats',
								'lg'   => 3,
								'md'   => 6,
								'sm'   => 12
							],

						],
						[
							[
								'id'   => 'table_broadcast_stats',
								'type' => 'table',
								'lg'   => 6,
								'md'   => 12,
								'sm'   => 12
							],
							[
								'id'   => 'table_top_performing_broadcasts',
								'type' => 'table',
								'lg'   => 6,
								'md'   => 12,
								'sm'   => 12
							]
						],
						[
							[
								'id'   => 'table_top_performing_emails',
								'type' => 'table',
								'lg'   => 6,
								'md'   => 12,
								'sm'   => 12
							],
							[
								'id'   => 'table_worst_performing_emails',
								'type' => 'table',
								'lg'   => 6,
								'md'   => 12,
								'sm'   => 12
							]
						],
					]
				],
			],
			"broadcasts" => [
				"page"    => $page,
				"reports" => [
					"rows" => [

						[
							[
								'id'   => 'chart_last_broadcast',
								'type' => 'pie',
								'lg'   => 6,
								'md'   => 12,
								'sm'   => 12
							],
							[
								'id'   => 'table_broadcast_stats',
								'type' => 'table',
								'lg'   => 6,
								'md'   => 12,
								'sm'   => 12
							]
						],
						[
							[
								'id'   => 'table_broadcast_link_clicked',
								'type' => 'table',
								'lg'   => 12,
								'md'   => 12,
								'sm'   => 12
							],

						],
					]
				],
			],
			"forms" => [
				"page"    => $page,
				"reports" => [
					"rows" => [
						[
							[
								'id'   => 'table_form_activity',
								'type' => 'table',
								'lg'   => 12,
								'md'   => 12,
								'sm'   => 12
							],

						],
					]
				],
			],

		];

		if ($page_array[$page]) {

			$data = $page_array [$page];
		} else
		{
			$data = [
				'error' =>  'page Not found'
			];
		}


		return self::SUCCESS_RESPONSE( $data );
	}

}