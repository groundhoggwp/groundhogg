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

		$title = $email->get_title();
		$stats = $email->get_email_stats( $this->start, $this->end );

		return [
			[
				'label' => __( 'Subject', 'groundhogg' ),
				'data'  => $title
			],
			[
				'label' => __( 'Total Delivered', 'groundhogg' ),
				'data'  => $stats[ 'sent' ]
			],
			[
				'label'      => __( 'Opens', 'groundhogg' ),
				'data'       => $stats[ 'opened' ],
				'percentage' => percentage( $stats[ 'sent' ], $stats[ 'opened' ] ) . '%'
			],
			[
				'label' => __( 'Total Clicks', 'groundhogg' ),
				'data'  => $stats[ 'all_clicks' ]
			],
			[
				'label'      => __( 'Unique Clicks', 'groundhogg' ),
				'data'       => $stats[ 'clicked' ],
				'percentage' => percentage( $stats[ 'sent' ], $stats[ 'clicked' ] ) . '%',
			],
			[
				'label' => __( 'Click Thru Rate', 'groundhogg' ),
				'data'  => percentage( $stats[ 'opened' ], $stats[ 'clicked' ] ) . '%'
			],
			[
				'label'      => __( 'Unsubscribed', 'groundhogg' ),
				'data'       => $stats[ 'unsubscribed' ],
				'percentage' => percentage( $stats[ 'sent' ], $stats[ 'unsubscribed' ] ) . '%'
			],
			[
				'email' => $email->get_as_array()
			]
		];

	}

	protected function normalize_datum( $item_key, $item_data ) {
		// TODO: Implement normalize_datum() method.
	}

	function get_label() {
		return [];
	}
}